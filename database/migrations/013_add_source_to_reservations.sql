-- 013: 예약 소스 구분 컬럼 추가 (online, walk_in, admin)
-- 2026-03-15

ALTER TABLE rzx_reservations
ADD COLUMN source VARCHAR(20) NOT NULL DEFAULT 'online' AFTER status;
