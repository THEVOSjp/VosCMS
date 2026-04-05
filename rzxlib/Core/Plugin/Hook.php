<?php
namespace RzxLib\Core\Plugin;

/**
 * VosCMS Hook System
 *
 * 코어 이벤트에 플러그인이 개입하는 포인트.
 * 단순 콜백 방식으로 시작, 필요 시 확장.
 *
 * 사용법:
 *   Hook::on('admin.sidebar.render', function($ctx) { ... });
 *   Hook::trigger('admin.sidebar.render', $context);
 */
class Hook
{
    /** @var array<string, callable[]> 등록된 리스너 */
    private static array $listeners = [];

    /**
     * 훅 리스너 등록
     *
     * @param string   $event    이벤트명 (예: 'admin.sidebar.render')
     * @param callable $callback 콜백 함수
     * @param int      $priority 우선순위 (낮을수록 먼저 실행, 기본 10)
     */
    public static function on(string $event, callable $callback, int $priority = 10): void
    {
        self::$listeners[$event][] = [
            'callback' => $callback,
            'priority' => $priority,
        ];
    }

    /**
     * 훅 실행 (트리거)
     *
     * @param string $event   이벤트명
     * @param mixed  $context 컨텍스트 데이터 (참조 전달 가능)
     * @return mixed 마지막 리스너의 반환값
     */
    public static function trigger(string $event, mixed &$context = null): mixed
    {
        if (empty(self::$listeners[$event])) {
            return null;
        }

        // 우선순위 정렬
        usort(self::$listeners[$event], fn($a, $b) => $a['priority'] <=> $b['priority']);

        $result = null;
        foreach (self::$listeners[$event] as $listener) {
            $result = call_user_func($listener['callback'], $context);
        }
        return $result;
    }

    /**
     * 훅 필터 (값을 변환하여 반환)
     *
     * @param string $event 이벤트명
     * @param mixed  $value 필터링할 값
     * @return mixed 필터링된 값
     */
    public static function filter(string $event, mixed $value): mixed
    {
        if (empty(self::$listeners[$event])) {
            return $value;
        }

        usort(self::$listeners[$event], fn($a, $b) => $a['priority'] <=> $b['priority']);

        foreach (self::$listeners[$event] as $listener) {
            $value = call_user_func($listener['callback'], $value);
        }
        return $value;
    }

    /**
     * 특정 이벤트에 리스너가 있는지 확인
     */
    public static function has(string $event): bool
    {
        return !empty(self::$listeners[$event]);
    }

    /**
     * 모든 리스너 초기화 (테스트용)
     */
    public static function clear(): void
    {
        self::$listeners = [];
    }
}
