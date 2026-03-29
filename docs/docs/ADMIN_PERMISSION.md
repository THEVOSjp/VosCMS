# RezlyX 관리자 권한 시스템

## 개요

RezlyX는 **3중 연동 구조**로 관리자 권한을 관리합니다.
슈퍼바이저(최고 관리자)는 시스템 보호 대상이며, 일반 스태프에게는 개별 권한을 부여합니다.

---

## 1. 계정 구조 (3중 연동)

```
rzx_users (회원)         ← 인증 기반 (필수)
    ↕ user_id
rzx_staff (스태프)       ← 조직 소속 (필수)
    ↕ staff_id
rzx_admins (관리자)      ← 권한 부여
```

### 원칙

- **관리자가 되려면 반드시 회원 + 스태프에 등록되어 있어야 함**
- 회원 탈퇴 → 스태프 비활성 → 관리자 권한 자동 박탈
- 스태프 해제 → 관리자 권한 자동 박탈
- 단, **슈퍼바이저는 어떤 경로로든 삭제/해제/비활성화 불가**

---

## 2. 역할 (Role)

| 역할 | DB 값 | 설명 |
|------|-------|------|
| 슈퍼바이저 | `master` | 시스템 최고 관리자. 설치 시 생성. 삭제 불가 |
| 매니저 | `manager` | 운영 관리자. 대부분 메뉴 접근 가능 (커스터마이징) |
| 스태프 | `staff` | 일반 직원. 개별 권한 지정 |

---

## 3. 슈퍼바이저 보호 정책

### 3.1 보호 대상

슈퍼바이저(`role='master'`)는 다음 동작이 **모두 차단**됩니다:

| 동작 | 차단 위치 |
|------|----------|
| 회원 탈퇴 (본인) | `rzx_users` 탈퇴 API |
| 회원 삭제 (관리자가) | 회원 관리 API |
| 스태프 해제/삭제 | 스태프 관리 API |
| 스태프 비활성화 | 스태프 관리 API |
| 관리자 권한 삭제 | 관리자 관리 API |
| 관리자 비활성화 | 관리자 관리 API |
| 역할 변경 (master → 하위) | 관리자 관리 API |

### 3.2 슈퍼바이저가 할 수 있는 것

- 본인 비밀번호 변경
- 본인 프로필(이름, 이메일 등) 수정
- 모든 관리 메뉴 접근
- 다른 관리자 생성/수정/삭제
- 다른 스태프의 권한 관리

### 3.3 최소 1명 유지

- 마지막 슈퍼바이저는 삭제 불가
- 슈퍼바이저를 추가하려면 기존 슈퍼바이저만 가능

### 3.4 슈퍼바이저 변경 방법

- **UI에서는 불가** — DB 직접 접근 또는 별도 시스템 관리 CLI만 가능
- 이는 미장원(사용자)이 실수로 해제하는 것을 방지하기 위한 설계

---

## 4. 권한 (Permissions)

### 4.1 권한 단위

| 권한 키 | 설명 | 관련 메뉴 |
|---------|------|----------|
| `dashboard` | 대시보드 | /admin |
| `reservations` | 예약 관리 | /admin/reservations |
| `counter` | 카운터 (정산) | /admin/counter |
| `services` | 서비스 관리 | /admin/services |
| `staff` | 스태프 관리 | /admin/staff |
| `staff.schedule` | 스케줄 관리 | /admin/staff/schedule |
| `staff.attendance` | 근태 관리 | /admin/staff/attendance |
| `members` | 회원 관리 | /admin/members |
| `site` | 사이트 관리 | /admin/site |
| `site.pages` | 페이지 관리 | /admin/site/pages |
| `site.widgets` | 위젯 관리 | /admin/site/widgets |
| `site.design` | 디자인 관리 | /admin/site/design |
| `site.menus` | 메뉴 관리 | /admin/site/menus |
| `settings` | 사이트 설정 | /admin/settings |

### 4.2 권한 저장 구조

`rzx_admins.permissions` 컬럼 (JSON):

```json
// 슈퍼바이저 (master) - permissions 무시, 항상 전체 접근
null

// 매니저 예시 - 대부분 접근 가능
["dashboard","reservations","counter","services","staff","staff.schedule",
 "staff.attendance","members","site","site.pages","settings"]

// 카운터 전용 스태프
["dashboard","counter"]

// 근태 + 예약만 보는 스태프
["dashboard","reservations","staff.attendance"]
```

### 4.3 권한 검사 로직

```php
class AdminAuth {
    // 현재 관리자가 특정 권한이 있는지 확인
    public static function can(string $permission): bool {
        $admin = self::current();
        if (!$admin) return false;

        // master는 항상 true
        if ($admin['role'] === 'master') return true;

        // permissions JSON 파싱
        $perms = json_decode($admin['permissions'] ?? '[]', true) ?: [];

        // 정확 매칭 또는 상위 권한 포함
        // 예: 'staff.schedule' → 'staff' 권한으로도 접근 가능
        foreach ($perms as $p) {
            if ($p === $permission) return true;
            if (str_starts_with($permission, $p . '.')) return true;
        }
        return false;
    }
}
```

---

## 5. DB 스키마

### 5.1 rzx_admins 테이블 (수정)

```sql
CREATE TABLE rzx_admins (
    id          CHAR(36) PRIMARY KEY,       -- UUID
    user_id     CHAR(36) NOT NULL,          -- rzx_users.id (필수)
    staff_id    INT DEFAULT NULL,           -- rzx_staff.id (필수)
    email       VARCHAR(255) NOT NULL,
    password    VARCHAR(255) NOT NULL,
    name        VARCHAR(100) NOT NULL,
    role        ENUM('master','manager','staff') DEFAULT 'staff',
    permissions JSON DEFAULT NULL,          -- 권한 목록 (master는 null)
    status      ENUM('active','inactive') DEFAULT 'active',
    last_login_at TIMESTAMP NULL,
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    UNIQUE KEY uk_email (email),
    UNIQUE KEY uk_user_id (user_id),
    UNIQUE KEY uk_staff_id (staff_id),
    KEY idx_role (role),
    KEY idx_status (status)
);
```

**변경 사항:**
- `user_id` 추가 (NOT NULL) — 회원 연동 필수
- `staff_id` 추가 — 스태프 연동 필수
- `permissions` 추가 (JSON) — 개별 권한 목록

### 5.2 연동 관계

```
rzx_users.id ←──── rzx_admins.user_id     (1:1)
rzx_staff.id ←──── rzx_admins.staff_id    (1:1)
rzx_users.id ←──── rzx_staff.user_id      (1:1, 기존)
```

---

## 6. 설치 시 슈퍼바이저 생성 흐름

```
1. 설치 위자드 시작
2. DB 생성 및 마이그레이션
3. 관리자 정보 입력 (이름, 이메일, 비밀번호)
4. rzx_users INSERT → user_id 획득
5. rzx_staff INSERT (user_id 연결) → staff_id 획득
6. rzx_admins INSERT (user_id, staff_id, role='master')
7. 설치 완료
```

---

## 7. 관리자 로그인 흐름

```
1. GET /admin/login → 관리자 로그인 폼 표시
2. POST /admin/login → 이메일/비밀번호 검증
3. rzx_admins에서 이메일 조회
4. password_verify() 확인
5. status='active' 확인
6. 연결된 rzx_users, rzx_staff 상태 확인
7. 세션 설정:
   - $_SESSION['admin_id']
   - $_SESSION['admin_role']
   - $_SESSION['admin_permissions']
   - $_SESSION['admin_name']
8. /admin (대시보드)로 리다이렉트
```

**세션 분리:**
- 고객 세션: `$_SESSION['user_id']`
- 관리자 세션: `$_SESSION['admin_id']`
- 동시 로그인 가능 (같은 사람이 고객+관리자)

---

## 8. 접근 제어 적용 위치

### 8.1 미들웨어 (index.php)

```php
// 관리자 경로 진입 시
if (str_starts_with($route, 'admin')) {
    // 1) 로그인 확인
    if (!isset($_SESSION['admin_id'])) {
        redirect('/admin/login');
    }

    // 2) 권한 확인
    $requiredPerm = getRequiredPermission($adminRoute);
    if ($requiredPerm && !AdminAuth::can($requiredPerm)) {
        // 403 접근 거부
        show403();
    }
}
```

### 8.2 사이드바 메뉴 (admin-sidebar.php)

```php
// 권한 없는 메뉴는 숨김
<?php if (AdminAuth::can('reservations')): ?>
    <li>예약 관리</li>
<?php endif; ?>

<?php if (AdminAuth::can('staff.attendance')): ?>
    <li>근태 관리</li>
<?php endif; ?>
```

### 8.3 API 엔드포인트

```php
// 각 POST 액션에서도 권한 체크
if ($action === 'delete_staff') {
    if (!AdminAuth::can('staff')) {
        echo json_encode(['error' => '권한이 없습니다']);
        exit;
    }
    // ...
}
```

---

## 9. 슈퍼바이저 보호 구현 체크리스트

| 위치 | 체크 내용 |
|------|----------|
| 회원 탈퇴 API | user_id가 master admin에 연결되어 있으면 거부 |
| 회원 삭제 API | user_id가 master admin에 연결되어 있으면 거부 |
| 스태프 삭제 API | staff_id가 master admin에 연결되어 있으면 거부 |
| 스태프 비활성화 API | staff_id가 master admin에 연결되어 있으면 거부 |
| 관리자 삭제 API | role='master'이면 거부 |
| 관리자 비활성화 API | role='master'이면 거부 |
| 관리자 역할 변경 API | master→하위 변경 거부 |
| 회원 목록 UI | master 연결 회원은 삭제 버튼 숨김 |
| 스태프 목록 UI | master 연결 스태프는 삭제/비활성 버튼 숨김 |
| 관리자 목록 UI | master는 삭제/비활성 버튼 숨김 |

---

## 10. 구현 순서

```
Phase 1: DB 스키마 수정
  - rzx_admins에 user_id, staff_id, permissions 컬럼 추가
  - 기존 master 계정에 연동 데이터 설정

Phase 2: AdminAuth 클래스 생성
  - rzxlib/Core/Auth/AdminAuth.php
  - login(), logout(), current(), can(), isMaster()
  - 슈퍼바이저 보호 헬퍼: isSupervisorUser(), isSupervisorStaff()

Phase 3: 관리자 로그인 페이지
  - resources/views/admin/login.php
  - 로그인 폼 + POST 처리
  - 세션 분리 (고객/관리자)

Phase 4: 미들웨어 적용
  - index.php에 관리자 인증 + 권한 체크 삽입
  - 미인증 시 /admin/login 리다이렉트
  - 권한 부족 시 403 페이지

Phase 5: 사이드바 권한 필터
  - admin-sidebar.php 메뉴에 AdminAuth::can() 적용
  - 권한 없는 메뉴 숨김

Phase 6: 관리자 관리 UI
  - /admin/settings/admins (또는 /admin/staff 내)
  - 관리자 목록, 권한 편집, 추가/삭제
  - 슈퍼바이저 보호 UI (삭제/비활성 버튼 숨김)

Phase 7: 슈퍼바이저 보호 적용
  - 회원 탈퇴/삭제 API에 보호 로직 추가
  - 스태프 삭제/비활성 API에 보호 로직 추가
  - 관리자 삭제/변경 API에 보호 로직 추가

Phase 8: 설치 위자드 통합 (추후)
  - 설치 시 자동으로 회원→스태프→관리자(master) 생성
```
