-- RezlyX: 페이지 타입 컬럼 추가 (document, widget, external)
ALTER TABLE rzx_page_contents ADD COLUMN page_type VARCHAR(20) NOT NULL DEFAULT 'document' AFTER page_slug;
