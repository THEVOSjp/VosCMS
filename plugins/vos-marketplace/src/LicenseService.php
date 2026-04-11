<?php

declare(strict_types=1);

namespace VosMarketplace;

use VosMarketplace\Models\License;
use VosMarketplace\Models\LicenseActivation;
use VosMarketplace\Models\MarketplaceOrder;
use VosMarketplace\Models\OrderItem;

class LicenseService
{
    private \PDO $pdo;
    private string $prefix;

    public function __construct(\PDO $pdo, string $prefix = 'rzx_')
    {
        $this->pdo = $pdo;
        $this->prefix = $prefix;
    }

    /**
     * 주문에 대해 라이선스 생성
     */
    public function generateForOrder(MarketplaceOrder $order): array
    {
        $items = OrderItem::forOrder($order->id);
        $licenses = [];

        foreach ($items as $itemData) {
            $license = new License();
            $license->order_item_id = (int) $itemData['id'];
            $license->item_id = (int) $itemData['item_id'];
            $license->admin_id = $order->admin_id;
            $license->type = 'single';
            $license->max_activations = 1;
            $license->status = 'active';

            if ($license->save()) {
                $licenses[] = $license;
            }
        }

        return $licenses;
    }

    /**
     * 라이선스 검증 및 활성화
     */
    public function validate(string $licenseKey, string $domain, string $action = 'activate', array $meta = []): array
    {
        $license = License::findByKey($licenseKey);
        if (!$license) {
            return ['valid' => false, 'error' => 'invalid_key', 'message' => 'License key not found'];
        }

        if (!$license->isValid()) {
            $reason = $license->isExpired() ? 'expired' : 'inactive';
            return ['valid' => false, 'error' => $reason, 'message' => "License is {$reason}"];
        }

        $domain = LicenseActivation::normalizeDomain($domain);

        switch ($action) {
            case 'activate':
                return $this->activateLicense($license, $domain, $meta);

            case 'deactivate':
                return $this->deactivateLicense($license, $domain);

            case 'heartbeat':
                return $this->heartbeat($license, $domain);

            default:
                return ['valid' => false, 'error' => 'invalid_action', 'message' => 'Unknown action'];
        }
    }

    /**
     * 라이선스 활성화
     */
    private function activateLicense(License $license, string $domain, array $meta = []): array
    {
        // 이미 이 도메인에 활성화되어 있는지 확인
        $existing = LicenseActivation::findByLicenseDomain($license->id, $domain);
        if ($existing && $existing->is_active) {
            $existing->updateHeartbeat();
            return [
                'valid' => true,
                'message' => 'Already activated',
                'license_id' => $license->id,
                'expires_at' => $license->expires_at,
            ];
        }

        // 비활성화된 기존 활성화가 있으면 재활성화
        if ($existing && !$existing->is_active) {
            if (!$license->canActivate()) {
                return [
                    'valid' => false,
                    'error' => 'max_activations',
                    'message' => "Maximum activations ({$license->max_activations}) reached",
                ];
            }
            $existing->is_active = true;
            $existing->deactivated_at = null;
            $existing->instance_id = $meta['instance_id'] ?? null;
            $existing->ip_address = $meta['ip_address'] ?? null;
            $existing->voscms_version = $meta['voscms_version'] ?? null;
            $existing->save();

            return [
                'valid' => true,
                'message' => 'Reactivated',
                'license_id' => $license->id,
                'expires_at' => $license->expires_at,
            ];
        }

        // 새로운 활성화
        if (!$license->canActivate()) {
            return [
                'valid' => false,
                'error' => 'max_activations',
                'message' => "Maximum activations ({$license->max_activations}) reached",
            ];
        }

        $activation = new LicenseActivation();
        $activation->license_id = $license->id;
        $activation->domain = $domain;
        $activation->instance_id = $meta['instance_id'] ?? null;
        $activation->ip_address = $meta['ip_address'] ?? null;
        $activation->voscms_version = $meta['voscms_version'] ?? null;
        $activation->is_active = true;
        $activation->save();

        return [
            'valid' => true,
            'message' => 'Activated',
            'license_id' => $license->id,
            'expires_at' => $license->expires_at,
        ];
    }

    /**
     * 라이선스 비활성화
     */
    private function deactivateLicense(License $license, string $domain): array
    {
        $activation = LicenseActivation::findByLicenseDomain($license->id, $domain);
        if (!$activation || !$activation->is_active) {
            return ['valid' => true, 'message' => 'Not activated on this domain'];
        }

        $activation->deactivate();

        return [
            'valid' => true,
            'message' => 'Deactivated',
            'license_id' => $license->id,
        ];
    }

    /**
     * Heartbeat (유효성 확인)
     */
    private function heartbeat(License $license, string $domain): array
    {
        $activation = LicenseActivation::findByLicenseDomain($license->id, $domain);
        if (!$activation || !$activation->is_active) {
            return ['valid' => false, 'error' => 'not_activated', 'message' => 'Not activated on this domain'];
        }

        $activation->updateHeartbeat();

        return [
            'valid' => true,
            'message' => 'OK',
            'license_id' => $license->id,
            'expires_at' => $license->expires_at,
        ];
    }

    /**
     * 관리자의 라이선스 목록
     */
    public function getLicensesForAdmin(string $adminId): array
    {
        $licenses = License::forAdmin($adminId);
        $result = [];

        foreach ($licenses as $licenseData) {
            $license = License::fromArray($licenseData);
            $activations = $license->getActivations();
            $result[] = [
                'license' => $licenseData,
                'activations' => $activations,
                'active_count' => LicenseActivation::countActive($license->id),
            ];
        }

        return $result;
    }

    /**
     * 라이선스 취소
     */
    public function revokeLicense(int $licenseId): bool
    {
        $license = License::find($licenseId);
        if (!$license) return false;

        $license->status = 'revoked';
        return $license->save();
    }
}
