-- VosCMS 호스팅 — 제작 프로젝트 마일스톤 + 시안 검수 (Phase 2B)
-- 관리자가 마일스톤 자유 추가, 시안 제출 → 고객 검토 → 승인/수정요청.

CREATE TABLE IF NOT EXISTS rzx_custom_milestones (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    project_id INT UNSIGNED NOT NULL,
    sequence_no SMALLINT UNSIGNED NOT NULL DEFAULT 0 COMMENT '표시 순서 (관리자 자유 정렬)',
    title VARCHAR(200) NOT NULL,
    description TEXT NULL COMMENT '관리자가 적는 단계별 설명',
    due_date DATE NULL,
    status VARCHAR(20) NOT NULL DEFAULT 'pending' COMMENT 'pending(대기) / in_progress(진행중) / submitted(시안 제출됨) / approved(승인됨) / revision_requested(수정 요청) / cancelled',
    attachments LONGTEXT NULL COMMENT '시안 첨부 파일 — JSON [{uuid,name,size,mime,ext},...]',
    submitted_at DATETIME NULL,
    approved_at DATETIME NULL,
    revision_at DATETIME NULL,
    revision_note TEXT NULL COMMENT '고객이 수정 요청 시 작성한 코멘트',
    approval_note TEXT NULL COMMENT '고객이 승인 시 적은 한마디 (선택)',
    started_at DATETIME NULL,
    completed_at DATETIME NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_project (project_id, sequence_no),
    KEY idx_status (status, due_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
