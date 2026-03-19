-- RezlyX: 게시글 확장 변수 값 저장 컬럼 추가
ALTER TABLE rzx_board_posts ADD COLUMN extra_vars JSON DEFAULT NULL;
