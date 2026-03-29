-- RezlyX v1.10.0: 게시글 확장 변수 + 대댓글 깊이
ALTER TABLE rzx_board_posts ADD COLUMN extra_vars JSON DEFAULT NULL;
UPDATE rzx_boards SET comment_max_depth = 3 WHERE comment_max_depth = 0;
