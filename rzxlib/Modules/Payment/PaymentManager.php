<?php
namespace RzxLib\Modules\Payment;

use RzxLib\Modules\Payment\Contracts\PaymentGatewayInterface;
use RzxLib\Modules\Payment\Gateways\StripeGateway;
use RzxLib\Modules\Payment\Gateways\PayjpGateway;

/**
 * 결제 게이트웨이 관리자
 * DB 설정(payment_config)에서 PG사 정보를 읽어 게이트웨이 인스턴스를 반환
 */
class PaymentManager
{
    private \PDO $pdo;
    private string $prefix;
    private ?array $config = null;

    public function __construct(\PDO $pdo, string $prefix = 'rzx_')
    {
        $this->pdo = $pdo;
        $this->prefix = $prefix;
    }

    /** 결제 설정 로드 */
    public function getConfig(): array
    {
        if ($this->config === null) {
            $stmt = $this->pdo->prepare("SELECT `value` FROM {$this->prefix}settings WHERE `key` = 'payment_config'");
            $stmt->execute();
            $this->config = json_decode($stmt->fetchColumn() ?: '{}', true) ?: [];
        }
        return $this->config;
    }

    /** 결제 활성화 여부 */
    public function isEnabled(): bool
    {
        $c = $this->getConfig();
        return ($c['enabled'] ?? '0') === '1'
            && !empty($c['public_key'])
            && !empty($c['secret_key']);
    }

    /** 테스트 모드 여부 */
    public function isTestMode(): bool
    {
        return ($this->getConfig()['test_mode'] ?? '1') === '1';
    }

    /** 현재 설정된 게이트웨이 이름 */
    public function getGatewayName(): string
    {
        return $this->getConfig()['gateway'] ?? 'stripe';
    }

    /** 게이트웨이 인스턴스 반환 */
    public function gateway(?string $name = null): PaymentGatewayInterface
    {
        $c = $this->getConfig();
        $gw = $name ?? ($c['gateway'] ?? 'stripe');

        // gateways 구조 또는 직접 키
        $gateways = $c['gateways'] ?? [];
        $gwConf = $gateways[$gw] ?? [];
        $secKey = $gwConf['secret_key'] ?? $c['secret_key'] ?? '';
        $pubKey = $gwConf['public_key'] ?? $c['public_key'] ?? '';

        switch ($gw) {
            case 'stripe':
                return new StripeGateway($secKey, $pubKey);
            case 'payjp':
                return new PayjpGateway($secKey, $pubKey);
            // TODO: toss, portone
            default:
                throw new \RuntimeException("Unsupported payment gateway: {$gw}");
        }
    }

    /** Public key (클라이언트용) */
    public function getPublicKey(): string
    {
        return $this->getConfig()['public_key'] ?? '';
    }
}
