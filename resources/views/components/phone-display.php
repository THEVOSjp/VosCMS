<?php
/**
 * 전화번호 표시 컴포넌트 (읽기 전용, 서버사이드 포맷)
 *
 * format_phone() 헬퍼 함수를 사용하여 국제 전화번호를 국가별 포맷으로 표시합니다.
 * JavaScript 의존 없음.
 *
 * 사용법:
 * <?php
 * $phoneDisplayConfig = [
 *     'value' => '+821012345678',   // 전체 전화번호 (필수)
 *     'class' => '',                // 추가 CSS 클래스 (선택)
 *     'fallback' => '-',            // 값 없을 때 표시 (선택)
 * ];
 * include BASE_PATH . '/resources/views/components/phone-display.php';
 * ?>
 *
 * 표시 결과: +82 010-1234-5678
 */

$_pdc = $phoneDisplayConfig ?? [];
$_pdValue = $_pdc['value'] ?? '';
$_pdClass = $_pdc['class'] ?? '';
$_pdFallback = $_pdc['fallback'] ?? '-';

$_pdFormatted = $_pdValue ? format_phone($_pdValue) : $_pdFallback;
?><span class="<?= htmlspecialchars($_pdClass) ?>"><?= htmlspecialchars($_pdFormatted) ?></span>
