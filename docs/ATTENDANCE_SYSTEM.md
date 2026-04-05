---
name: RezlyX 직원 근태 관리 시스템
description: 근태 관리 핵심 기능 정의 + 구현 현황 + 미구현 항목
type: project
---

## 직원 근태 관리 시스템

### 1. 출퇴근 기록 ✅ 구현완료
| 기능 | 상태 | 파일 |
|------|------|------|
| 출근/퇴근 타임스탬프 | ✅ | attendance.php (clock_in/clock_out) |
| 외출/복귀 | ✅ | attendance.php (break_out/break_in) |
| 외근/복귀 | ✅ | attendance.php (outside_out/outside_in) |
| 근무시간 자동 계산 | ✅ | work_hours = clock_out - clock_in - break_minutes |
| 카드리더 키오스크 | ✅ | attendance-kiosk.php (전체화면, 카드 태그) |
| 수동 입력 (manual) | ✅ | source ENUM('manual','card') |
| 메모 기록 | ✅ | memo TEXT |

**DB 테이블:** `rzx_attendance`
- id, staff_id, clock_in, clock_out, break_out, break_in, break_minutes, work_hours
- status: working, completed, absent, late, early_leave, break, outside
- source: manual, card

### 2. 근무 스케줄 (시프트) ✅ 구현완료
| 기능 | 상태 | 파일 |
|------|------|------|
| 요일별 반복 스케줄 | ✅ | schedule.php + rzx_staff_schedules |
| 날짜별 오버라이드 | ✅ | rzx_staff_schedule_overrides |
| 근무/휴무 설정 | ✅ | is_working 플래그 |
| 시작/종료/휴식 시간 | ✅ | start_time, end_time, break_start, break_end |

**DB 테이블:** `rzx_staff_schedules` (요일별), `rzx_staff_schedule_overrides` (날짜별)

### 3. 리포트/통계 ✅ 구현완료
| 기능 | 상태 | 파일 |
|------|------|------|
| 오늘 출근 현황 | ✅ | attendance.php |
| 근태 기록 조회 (필터) | ✅ | attendance-history.php |
| 월간 대시보드 | ✅ | attendance-dashboard.php |
| 출근율/평균근무/지각 통계 | ✅ | attendance-dashboard.php |
| 기간별 리포트 | ✅ | attendance-report.php |
| 개인 리포트 | ✅ | attendance-report-personal.php |
| 근태 통계 | ✅ | attendance-report-stats.php |
| CSV 내보내기 | ✅ | attendance-report.php (UTF-8 BOM) |

### 4. 기록 관리 ✅ 구현완료
| 기능 | 상태 | 파일 |
|------|------|------|
| 기록 수정 | ✅ | attendance-history.php (update_record) |
| 기록 삭제 | ✅ | attendance-history.php (delete_record) |
| 수동 기록 추가 | ✅ | attendance-history.php (add_record) |

### 5. 다국어 ✅ 구현완료
- ko, ja, en + 9개 언어 (es, de, fr, id, ru, mn, tr, vi, zh_CN, zh_TW)
- 번역 키: staff.attendance.* (출근/퇴근/외출/복귀/상태/에러/통계)

---

## 미구현 기능 (향후 개발)

### 6. 휴가/결근 관리 ❌ 미구현
- [ ] 유급휴가 잔여일수 자동 계산
- [ ] 휴가 신청 → 승인 워크플로우
- [ ] 종류: 연차, 반차, 병가, 경조사, 특별휴가
- [ ] 결근/지각/조퇴 자동 판정 (스케줄 비교)
- **필요 테이블:** `rzx_staff_leave` (staff_id, type, start_date, end_date, status, reason)
- **필요 테이블:** `rzx_staff_leave_balance` (staff_id, year, total_days, used_days, remaining_days)

### 7. 급여 연동 데이터 ❌ 미구현
- [ ] 월간 근무시간 집계 (정규/연장/야간/휴일 구분)
- [ ] 시급/월급 기초 데이터
- [ ] 교통비/수당 항목
- [ ] 급여 소프트웨어 연동 CSV 형식
- **필요 테이블:** `rzx_staff_payroll` (staff_id, month, base_hours, overtime_hours, night_hours, holiday_hours)

### 8. 시프트 고급 기능 ❌ 미구현
- [ ] 월간 시프트표 (캘린더 뷰로 배치)
- [ ] 시프트 패턴 템플릿 (조조/주간/야간/반일)
- [ ] 스태프별 희망 근무일 수집
- [ ] 시프트 교환 요청 (스태프 간 교대)
- [ ] 예약 캘린더와 시프트 연동 (예약 많은 날 스태프 배치)

### 9. GPS/IP 제한 ❌ 미구현
- [ ] 매장 위치 기반 출퇴근 제한
- [ ] IP 화이트리스트
- [ ] 위치 기록 저장

### 10. 인건비 분석 ❌ 미구현
- [ ] POS 매출 대비 인건비 비율
- [ ] 스태프별 시술 건수 대비 근무시간
- [ ] 인건비 추이 그래프

---

## 파일 구조

```
resources/views/admin/staff/
  attendance.php              — 오늘 출퇴근 현황 + API
  attendance-history.php      — 기록 조회/수정/삭제/추가
  attendance-dashboard.php    — 월간 대시보드 (통계)
  attendance-kiosk.php        — 카드리더 전체화면
  attendance-report.php       — 기간별 리포트 + CSV
  attendance-report-personal.php — 개인 리포트
  attendance-report-stats.php — 근태 통계
  attendance-js.php           — 출퇴근 JS (clockIn/Out/breakOut/In)
  attendance-kiosk-js.php     — 키오스크 카드리더 JS
  schedule.php                — 스케줄 관리
  schedule-js.php             — 스케줄 JS
```

## 라우트
```
/admin/staff/attendance              — 오늘 현황
/admin/staff/attendance/history      — 기록 조회
/admin/staff/attendance/dashboard    — 월간 대시보드
/admin/staff/attendance/kiosk        — 키오스크 모드
/admin/staff/attendance/report       — 리포트 + CSV
/admin/staff/attendance/report/personal/{id} — 개인 리포트
/admin/staff/attendance/report/stats — 통계
/admin/staff/schedule                — 스케줄 관리
```
