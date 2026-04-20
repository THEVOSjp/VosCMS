<?php

declare(strict_types=1);

namespace RzxLib\Core\Translate;

/**
 * 기본 폴백 번역기.
 *
 * AI 엔진이 준비되지 않았을 때 사용. 번역 요청이 오면 원문을 그대로 반환한다.
 * isAvailable() 은 false 를 반환하므로 관리자 UI 에서 "번역 버튼 비활성" 상태를 유지한다.
 */
final class NullTranslator implements TranslatorInterface
{
    public function translate(string $text, string $sourceLocale, string $targetLocale): string
    {
        return $text;
    }

    public function isAvailable(): bool
    {
        return false;
    }

    public function name(): string
    {
        return 'null';
    }
}
