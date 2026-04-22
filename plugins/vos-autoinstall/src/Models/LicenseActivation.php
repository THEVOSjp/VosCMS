<?php

declare(strict_types=1);

namespace VosMarketplace\Models;

use RzxLib\Core\Database\Connection;
use RzxLib\Core\Database\QueryBuilder;

class LicenseActivation
{
    protected static string $table = 'mp_license_activations';

    public ?int $id = null;
    public int $license_id = 0;
    public string $domain = '';
    public ?string $instance_id = null;
    public ?string $ip_address = null;
    public ?string $voscms_version = null;
    public ?string $activated_at = null;
    public ?string $last_check_at = null;
    public ?string $deactivated_at = null;
    public bool $is_active = true;

    public static function query(): QueryBuilder
    {
        return Connection::getInstance()->table(static::$table);
    }

    public static function find(int $id): ?static
    {
        $data = static::query()->where('id', $id)->first();
        return $data ? static::fromArray($data) : null;
    }

    public static function findByLicenseDomain(int $licenseId, string $domain): ?static
    {
        $data = static::query()
            ->where('license_id', $licenseId)
            ->where('domain', $domain)
            ->first();
        return $data ? static::fromArray($data) : null;
    }

    public static function forLicense(int $licenseId): array
    {
        return static::query()
            ->where('license_id', $licenseId)
            ->orderBy('activated_at', 'DESC')
            ->get();
    }

    public static function countActive(int $licenseId): int
    {
        return (int) static::query()
            ->where('license_id', $licenseId)
            ->where('is_active', 1)
            ->count();
    }

    /**
     * 도메인 정규화: www 제거, 포트 제거, 소문자, 슬래시 제거
     */
    public static function normalizeDomain(string $domain): string
    {
        $domain = strtolower(trim($domain));
        $domain = preg_replace('#^https?://#', '', $domain);
        $domain = preg_replace('#^www\.#', '', $domain);
        $domain = preg_replace('#:\d+$#', '', $domain);
        $domain = rtrim($domain, '/');
        return $domain;
    }

    public static function fromArray(array $data): static
    {
        $a = new static();
        $a->id = isset($data['id']) ? (int) $data['id'] : null;
        $a->license_id = (int) ($data['license_id'] ?? 0);
        $a->domain = $data['domain'] ?? '';
        $a->instance_id = $data['instance_id'] ?? null;
        $a->ip_address = $data['ip_address'] ?? null;
        $a->voscms_version = $data['voscms_version'] ?? null;
        $a->activated_at = $data['activated_at'] ?? null;
        $a->last_check_at = $data['last_check_at'] ?? null;
        $a->deactivated_at = $data['deactivated_at'] ?? null;
        $a->is_active = (bool) ($data['is_active'] ?? true);
        return $a;
    }

    public function toArray(): array
    {
        return [
            'license_id' => $this->license_id,
            'domain' => $this->domain,
            'instance_id' => $this->instance_id,
            'ip_address' => $this->ip_address,
            'voscms_version' => $this->voscms_version,
            'is_active' => (int) $this->is_active,
        ];
    }

    public function save(): bool
    {
        $data = $this->toArray();

        if ($this->id) {
            return static::query()->where('id', $this->id)->update($data) >= 0;
        } else {
            $data['activated_at'] = date('Y-m-d H:i:s');
            $data['last_check_at'] = date('Y-m-d H:i:s');
            $result = static::query()->insert($data);
            if ($result) {
                $this->id = (int) Connection::getInstance()->getPdo()->lastInsertId();
            }
            return $result;
        }
    }

    public function deactivate(): bool
    {
        $this->is_active = false;
        $this->deactivated_at = date('Y-m-d H:i:s');
        return static::query()->where('id', $this->id)->update([
            'is_active' => 0,
            'deactivated_at' => $this->deactivated_at,
        ]) >= 0;
    }

    public function updateHeartbeat(): bool
    {
        $this->last_check_at = date('Y-m-d H:i:s');
        return static::query()->where('id', $this->id)->update([
            'last_check_at' => $this->last_check_at,
        ]) >= 0;
    }
}
