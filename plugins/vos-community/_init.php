<?php
/**
 * vos-community 공통 부트스트랩.
 * 각 view 진입 시 require_once 로 호출.
 * - lang 자동 로드 (현재 locale, 없으면 en fallback)
 */
require_once BASE_PATH . '/rzxlib/Core/I18n/Translator.php';
require_once BASE_PATH . '/rzxlib/Core/Helpers/functions.php';

$_commLocale = \RzxLib\Core\I18n\Translator::getLocale();
$_commLang = __DIR__ . '/lang/' . $_commLocale . '.php';
if (!file_exists($_commLang)) {
    $_commLang = __DIR__ . '/lang/en.php';
}
if (file_exists($_commLang)) {
    \RzxLib\Core\I18n\Translator::merge('community', require $_commLang);
}
