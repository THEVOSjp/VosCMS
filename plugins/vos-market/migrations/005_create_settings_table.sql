CREATE TABLE IF NOT EXISTS `{prefix}mkt_settings` (
    `key`        VARCHAR(100)   NOT NULL,
    `value`      TEXT           NULL,
    `created_at` TIMESTAMP      NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP      NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO `{prefix}mkt_settings` (`key`,`value`) VALUES
('market_name',          'VosCMS 마켓플레이스'),
('default_currency',     'JPY'),
('default_commission_rate', '30'),
('partner_portal_url',   'https://partner.21ces.com'),
('max_upload_mb',        '50'),
('partner_registration_open', '1'),
('auto_approve_partners',     '0'),
('require_license_for_download', '1');
