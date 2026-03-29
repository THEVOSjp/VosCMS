-- ============================================================================
-- RezlyX Terms Translations Seed
-- ============================================================================
-- 약관 다국어 번역 시드 데이터
-- 실제 운영에서는 관리자 페이지에서 입력한 값으로 대체됩니다

SET NAMES utf8mb4;

-- -----------------------------------------------------------------------------
-- 약관 1: 이용약관 (Terms of Service)
-- -----------------------------------------------------------------------------
-- 한국어
INSERT INTO `rzx_translations` (`lang_key`, `locale`, `content`) VALUES
('term.1.title', 'ko', '이용약관'),
('term.1.content', 'ko', '<p>본 약관은 서비스 이용에 관한 기본적인 사항을 규정합니다.</p><p>서비스를 이용하시기 전에 본 약관을 주의 깊게 읽어주시기 바랍니다.</p>')
ON DUPLICATE KEY UPDATE `content` = VALUES(`content`);

-- 영어
INSERT INTO `rzx_translations` (`lang_key`, `locale`, `content`) VALUES
('term.1.title', 'en', 'Terms of Service'),
('term.1.content', 'en', '<p>These Terms of Service govern your use of our services.</p><p>Please read these terms carefully before using our services.</p>')
ON DUPLICATE KEY UPDATE `content` = VALUES(`content`);

-- 일본어
INSERT INTO `rzx_translations` (`lang_key`, `locale`, `content`) VALUES
('term.1.title', 'ja', '利用規約'),
('term.1.content', 'ja', '<p>本規約は、サービスのご利用に関する基本的な事項を定めています。</p><p>サービスをご利用になる前に、本規約をよくお読みください。</p>')
ON DUPLICATE KEY UPDATE `content` = VALUES(`content`);

-- -----------------------------------------------------------------------------
-- 약관 2: 개인정보 처리방침 (Privacy Policy)
-- -----------------------------------------------------------------------------
-- 한국어
INSERT INTO `rzx_translations` (`lang_key`, `locale`, `content`) VALUES
('term.2.title', 'ko', '개인정보 처리방침'),
('term.2.content', 'ko', '<p>당사는 고객의 개인정보를 중요시하며, 개인정보보호법을 준수합니다.</p><p>수집된 개인정보는 서비스 제공 목적으로만 사용됩니다.</p>')
ON DUPLICATE KEY UPDATE `content` = VALUES(`content`);

-- 영어
INSERT INTO `rzx_translations` (`lang_key`, `locale`, `content`) VALUES
('term.2.title', 'en', 'Privacy Policy'),
('term.2.content', 'en', '<p>We value your privacy and comply with applicable privacy laws.</p><p>Personal information collected is used only for service provision purposes.</p>')
ON DUPLICATE KEY UPDATE `content` = VALUES(`content`);

-- 일본어
INSERT INTO `rzx_translations` (`lang_key`, `locale`, `content`) VALUES
('term.2.title', 'ja', 'プライバシーポリシー'),
('term.2.content', 'ja', '<p>当社はお客様の個人情報を重視し、個人情報保護法を遵守します。</p><p>収集した個人情報は、サービス提供の目的にのみ使用されます。</p>')
ON DUPLICATE KEY UPDATE `content` = VALUES(`content`);

-- -----------------------------------------------------------------------------
-- 약관 3: 마케팅 정보 수신 동의 (Marketing Consent)
-- -----------------------------------------------------------------------------
-- 한국어
INSERT INTO `rzx_translations` (`lang_key`, `locale`, `content`) VALUES
('term.3.title', 'ko', '마케팅 정보 수신 동의'),
('term.3.content', 'ko', '<p>이벤트, 프로모션 등 마케팅 정보를 이메일, SMS로 받아보실 수 있습니다.</p><p>동의하지 않으셔도 서비스 이용에 제한이 없습니다.</p>')
ON DUPLICATE KEY UPDATE `content` = VALUES(`content`);

-- 영어
INSERT INTO `rzx_translations` (`lang_key`, `locale`, `content`) VALUES
('term.3.title', 'en', 'Marketing Consent'),
('term.3.content', 'en', '<p>You can receive marketing information such as events and promotions via email and SMS.</p><p>You can use our services without agreeing to this.</p>')
ON DUPLICATE KEY UPDATE `content` = VALUES(`content`);

-- 일본어
INSERT INTO `rzx_translations` (`lang_key`, `locale`, `content`) VALUES
('term.3.title', 'ja', 'マーケティング情報の受信同意'),
('term.3.content', 'ja', '<p>イベントやプロモーションなどのマーケティング情報をメールやSMSでお受け取りいただけます。</p><p>同意されなくてもサービスのご利用に制限はありません。</p>')
ON DUPLICATE KEY UPDATE `content` = VALUES(`content`);

-- Record migration
INSERT INTO `rzx_migrations` (`migration`, `batch`)
SELECT '010_seed_terms_translations', COALESCE(MAX(batch), 0) + 1 FROM `rzx_migrations`
ON DUPLICATE KEY UPDATE `migration` = VALUES(`migration`);
