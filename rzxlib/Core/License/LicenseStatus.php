<?php
/**
 * VosCMS License Status
 *
 * LicenseClient::check()의 반환 객체.
 * 뷰에서 경고 배너 표시, 플러그인 제한 등에 사용.
 */

namespace RzxLib\Core\License;

class LicenseStatus
{
    public string $state;             // active, unregistered, unverified, invalid, cached
    public string $plan;              // free, standard, professional, enterprise
    public array $allowedPlugins;     // 허용된 플러그인 ID 목록
    public array $unauthorizedPlugins; // 미인증 설치된 플러그인
    public ?string $warning;          // 경고 메시지 (없으면 null)
    public ?string $warningLevel;     // info, warning, danger
    public ?int $daysLeft;            // 만료까지 남은 일수
    public ?string $expiresAt;
    public ?string $latestVersion;
    public bool $updateAvailable;
    public ?string $error;            // 에러 코드

    private function __construct()
    {
        $this->state = 'active';
        $this->plan = 'free';
        $this->allowedPlugins = [];
        $this->unauthorizedPlugins = [];
        $this->warning = null;
        $this->warningLevel = null;
        $this->daysLeft = null;
        $this->expiresAt = null;
        $this->latestVersion = null;
        $this->updateAvailable = false;
        $this->error = null;
    }

    /**
     * 정상 인증
     */
    public static function active(array $response): self
    {
        $s = new self();
        $s->state = 'active';
        $s->plan = $response['plan'] ?? 'free';
        $s->allowedPlugins      = $response['allowed_plugins'] ?? [];
        $s->unauthorizedPlugins = array_values(array_filter(
            $response['unauthorized_plugins'] ?? [],
            fn($id) => !in_array($id, self::BUNDLE_PLUGINS)
        ));
        $s->daysLeft = $response['days_left'] ?? null;
        $s->expiresAt = $response['expires_at'] ?? null;
        $s->latestVersion = $response['latest_version'] ?? null;
        $s->updateAvailable = $response['update_available'] ?? false;

        // 미인증 플러그인 경고
        if (!empty($s->unauthorizedPlugins)) {
            $count = count($s->unauthorizedPlugins);
            $s->warning = "{$count}개 플러그인이 미인증 상태입니다.";
            $s->warningLevel = 'warning';
        }

        // 만료 임박 경고
        if ($s->daysLeft !== null && $s->daysLeft <= 30) {
            $s->warning = "라이선스가 {$s->daysLeft}일 후 만료됩니다.";
            $s->warningLevel = $s->daysLeft <= 7 ? 'danger' : 'warning';
        }

        return $s;
    }

    /**
     * 캐시에서 복원
     */
    public static function fromCache(array $cache, ?string $networkWarning = null): self
    {
        $s = new self();
        $s->state = 'cached';
        $s->plan = $cache['plan'] ?? 'free';
        $s->allowedPlugins      = $cache['allowed_plugins'] ?? [];
        $s->unauthorizedPlugins = array_values(array_filter(
            $cache['unauthorized_plugins'] ?? [],
            fn($id) => !in_array($id, self::BUNDLE_PLUGINS)
        ));
        $s->daysLeft = $cache['days_left'] ?? null;
        $s->expiresAt = $cache['expires_at'] ?? null;
        $s->latestVersion = $cache['latest_version'] ?? null;
        $s->updateAvailable = $cache['update_available'] ?? false;

        if ($networkWarning === 'server_unreachable') {
            $cacheExpires = $cache['cache_expires_at'] ?? null;
            $cacheDaysLeft = $cacheExpires ? max(0, (int) ceil((strtotime($cacheExpires) - time()) / 86400)) : 0;
            $s->warning = "라이선스 서버 연결 불가 (캐시 {$cacheDaysLeft}일 남음)";
            $s->warningLevel = $cacheDaysLeft <= 2 ? 'danger' : 'info';
        }

        return $s;
    }

    /**
     * 라이선스 키 미등록 (install.php에서 건너뛰기)
     */
    public static function unregistered(): self
    {
        $s = new self();
        $s->state = 'unregistered';
        $s->warning = '라이선스가 등록되지 않았습니다.';
        $s->warningLevel = 'info';
        return $s;
    }

    /**
     * 인증 불가 (캐시 만료 + 서버 불가)
     */
    public static function unverified(): self
    {
        $s = new self();
        $s->state = 'unverified';
        $s->warning = '라이선스 인증이 필요합니다. 서버에 연결할 수 없습니다.';
        $s->warningLevel = 'danger';
        return $s;
    }

    /**
     * 라이선스 무효 (revoked, expired, domain mismatch)
     */
    public static function invalid(string $error, string $message = ''): self
    {
        $s = new self();
        $s->state = 'invalid';
        $s->error = $error;
        $s->warningLevel = 'danger';

        $s->warning = match ($error) {
            'domain_mismatch' => '라이선스 키가 다른 도메인에 등록되어 있습니다.',
            'license_expired' => '라이선스가 만료되었습니다.',
            'license_suspended' => '라이선스가 정지되었습니다.',
            'license_revoked' => '라이선스가 취소되었습니다.',
            'invalid_key' => '유효하지 않은 라이선스 키입니다.',
            default => $message ?: '라이선스 인증에 실패했습니다.',
        };

        return $s;
    }

    /**
     * 정상 상태인지
     */
    public function isOk(): bool
    {
        return in_array($this->state, ['active', 'cached']);
    }

    /** 기본 번들 플러그인 — 플랜 무관 항상 허용 */
    private const BUNDLE_PLUGINS = ['vos-autoinstall', 'vos-marketplace'];

    /**
     * 플러그인 사용 가능 여부
     */
    public function canUsePlugin(string $pluginId): bool
    {
        if (in_array($pluginId, self::BUNDLE_PLUGINS)) return true;

        // 라이선스 미확인 상태에서는 일단 허용 (경고만)
        if ($this->state === 'unregistered' || $this->state === 'cached') return true;

        return in_array($pluginId, $this->allowedPlugins);
    }

    /**
     * 배열 변환 (뷰에서 사용)
     */
    public function toArray(): array
    {
        return [
            'state' => $this->state,
            'plan' => $this->plan,
            'allowed_plugins' => $this->allowedPlugins,
            'unauthorized_plugins' => $this->unauthorizedPlugins,
            'warning' => $this->warning,
            'warning_level' => $this->warningLevel,
            'days_left' => $this->daysLeft,
            'expires_at' => $this->expiresAt,
            'latest_version' => $this->latestVersion,
            'update_available' => $this->updateAvailable,
            'error' => $this->error,
            'is_ok' => $this->isOk(),
        ];
    }
}
