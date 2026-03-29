-- RezlyX v1.9.3: 기본 게시판 데이터

INSERT INTO rzx_boards (id, slug, title, category, skin, layout, per_page, allow_comment, perm_list, perm_read, perm_write, perm_comment, perm_manage, is_active, sort_order)
VALUES
(1, 'notice', '공지사항', 'notice', 'default', 'default', 20, 1, 'all', 'all', 'admin', 'member', 'admin', 1, 0),
(2, 'qna', '질문과 답변', 'qna', 'default', 'default', 20, 1, 'all', 'all', 'member', 'member', 'admin', 1, 1),
(3, 'faq', '자주 묻는 질문', 'faq', 'default', 'default', 20, 1, 'all', 'all', 'admin', 'member', 'admin', 1, 2),
(4, 'free', '자유게시판', 'board', 'default', 'default', 20, 1, 'all', 'all', 'member', 'member', 'admin', 1, 3)
ON DUPLICATE KEY UPDATE slug = VALUES(slug);
