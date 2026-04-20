<?php

declare(strict_types=1);

namespace RzxLib\Core\Translate;

/**
 * 설정에 따라 적절한 번역 드라이버 인스턴스를 생성.
 *
 * 사용:
 *   $translator = TranslatorFactory::make();
 *   if ($translator->isAvailable()) { ... }
 *
 * 드라이버는 config/translator.php 에서 지정:
 *   return ['driver' => 'gemma', 'endpoint' => 'https://ai.21ces.com/...'];
 *
 * config 가 없거나 driver 를 인식 못 하면 NullTranslator 로 폴백.
 */
final class TranslatorFactory
{
    private static ?TranslatorInterface $instance = null;

    public static function make(): TranslatorInterface
    {
        if (self::$instance !== null) {
            return self::$instance;
        }

        $configFile = defined('BASE_PATH') ? BASE_PATH . '/config/translator.php' : null;
        $config = ($configFile && file_exists($configFile)) ? (require $configFile) : [];
        $driver = $config['driver'] ?? 'null';

        self::$instance = match ($driver) {
            // 'gemma' => new GemmaTranslator($config),  // Phase 2
            default => new NullTranslator(),
        };

        return self::$instance;
    }

    /** 테스트/재설정용 */
    public static function reset(): void
    {
        self::$instance = null;
    }
}
