<?php

declare(strict_types=1);

namespace RzxLib\Core\Translate;

/**
 * 번역 드라이버 인터페이스.
 *
 * 모든 번역 엔진(AI·외부 API·수동·NullTranslator)이 구현해야 한다.
 * 교체 가능하도록 config/translator.php 에서 driver 지정.
 *
 * 현재 구현체:
 *   - NullTranslator    원문 그대로 반환 (AI 없을 때 폴백)
 *   - GemmaTranslator   ai.21ces.com Gemma 4 연동 (Phase 2, GPU 준비 후)
 */
interface TranslatorInterface
{
    /**
     * 단일 텍스트 번역.
     *
     * @param string $text         원본 텍스트 (markdown 허용)
     * @param string $sourceLocale 원본 언어 코드 (ko, en, ...)
     * @param string $targetLocale 목표 언어 코드
     * @return string 번역된 텍스트. 실패 시 원문 반환 (예외 던지지 않음).
     */
    public function translate(string $text, string $sourceLocale, string $targetLocale): string;

    /**
     * 번역 엔진이 현재 사용 가능한지.
     * 예: GPU 서버 접속 가능한지, API 토큰 유효한지 등.
     */
    public function isAvailable(): bool;

    /**
     * 드라이버 식별자 (telemetry/로깅용).
     */
    public function name(): string;
}
