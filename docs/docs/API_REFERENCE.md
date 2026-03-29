# API 레퍼런스

RezlyX 예약 시스템 RESTful API 문서입니다.

## 기본 정보

- **Base URL**: `https://your-domain.com/api`
- **응답 형식**: JSON
- **인증**: Bearer Token (JWT)

### 응답 구조

```json
{
  "success": true,
  "message": "성공 메시지",
  "data": { ... }
}
```

### 에러 응답

```json
{
  "success": false,
  "message": "에러 메시지",
  "errors": { "field": ["에러 상세"] }
}
```

---

## 서비스 API

### 서비스 목록

```
GET /api/services
```

**Parameters**
| 파라미터 | 타입 | 설명 |
|---------|------|------|
| category_id | int | 카테고리 필터 (선택) |

**Response**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "name": "기본 커트",
      "slug": "basic-cut",
      "duration": 30,
      "price": 20000,
      "currency": "KRW",
      "is_active": true
    }
  ]
}
```

### 서비스 상세

```
GET /api/services/{id}
```

**Response**
```json
{
  "success": true,
  "data": {
    "service": { ... },
    "category": { ... }
  }
}
```

---

## 예약 API

### 가용 날짜 조회

```
GET /api/services/{id}/available-dates
```

**Parameters**
| 파라미터 | 타입 | 설명 |
|---------|------|------|
| year | int | 연도 (기본: 현재) |
| month | int | 월 (기본: 현재) |

**Response**
```json
{
  "success": true,
  "data": {
    "year": 2025,
    "month": 3,
    "dates": [
      { "date": "2025-03-01", "available": true, "slots_count": 8 },
      { "date": "2025-03-02", "available": false, "slots_count": 0 }
    ]
  }
}
```

### 가용 시간대 조회

```
GET /api/services/{id}/available-slots?date=2025-03-01
```

**Parameters**
| 파라미터 | 타입 | 필수 | 설명 |
|---------|------|------|------|
| date | string | Yes | 날짜 (YYYY-MM-DD) |

**Response**
```json
{
  "success": true,
  "data": {
    "date": "2025-03-01",
    "service_id": 1,
    "slots": [
      { "time": "09:00", "end_time": "09:30", "available": 3, "max_capacity": 5 },
      { "time": "09:30", "end_time": "10:00", "available": 2, "max_capacity": 5 }
    ]
  }
}
```

### 예약 생성

```
POST /api/reservations
```

**Request Body**
```json
{
  "service_id": 1,
  "customer_name": "홍길동",
  "customer_email": "hong@example.com",
  "customer_phone": "010-1234-5678",
  "booking_date": "2025-03-01",
  "start_time": "10:00",
  "guests": 1,
  "notes": "창가 자리 요청"
}
```

**Response (201)**
```json
{
  "success": true,
  "message": "예약이 완료되었습니다.",
  "data": {
    "booking_code": "RZ250301ABC123",
    "booking_date": "2025-03-01",
    "start_time": "10:00:00",
    "end_time": "10:30:00",
    "status": "pending",
    "total_price": 20000
  }
}
```

### 예약 조회

```
GET /api/reservations/{booking_code}
```

**Parameters**
| 파라미터 | 타입 | 설명 |
|---------|------|------|
| email | string | 이메일 인증 (선택) |

**Response**
```json
{
  "success": true,
  "data": {
    "reservation": {
      "id": 1,
      "booking_code": "RZ250301ABC123",
      "customer_name": "홍길동",
      "booking_date": "2025-03-01",
      "start_time": "10:00:00",
      "status": "confirmed"
    },
    "service": { ... }
  }
}
```

### 예약 취소

```
POST /api/reservations/{booking_code}/cancel
```

**Request Body**
```json
{
  "email": "hong@example.com",
  "reason": "일정 변경"
}
```

**Response**
```json
{
  "success": true,
  "message": "예약이 취소되었습니다.",
  "data": {
    "booking_code": "RZ250301ABC123",
    "status": "cancelled"
  }
}
```

---

## 인증 API

### 로그인

```
POST /api/auth/login
```

**Request Body**
```json
{
  "email": "user@example.com",
  "password": "password123"
}
```

**Response**
```json
{
  "success": true,
  "data": {
    "user": { "id": 1, "email": "user@example.com", "name": "홍길동" },
    "token": "eyJ...",
    "expires_at": "2025-03-08T10:00:00Z"
  }
}
```

### 회원가입

```
POST /api/auth/register
```

**Request Body**
```json
{
  "name": "홍길동",
  "email": "user@example.com",
  "password": "password123",
  "password_confirmation": "password123",
  "phone": "010-1234-5678"
}
```

### 현재 사용자 조회

```
GET /api/auth/me
Authorization: Bearer {token}
```

**Response**
```json
{
  "success": true,
  "data": {
    "id": 1,
    "email": "user@example.com",
    "name": "홍길동",
    "phone": "010-1234-5678",
    "role": "user"
  }
}
```

---

## 사용자 API

> 인증 필요: `Authorization: Bearer {token}`

### 내 예약 목록

```
GET /api/user/reservations
```

**Parameters**
| 파라미터 | 타입 | 설명 |
|---------|------|------|
| status | string | 상태 필터 |
| page | int | 페이지 번호 |
| per_page | int | 페이지당 개수 |

### 프로필 수정

```
PUT /api/user/profile
```

**Request Body**
```json
{
  "name": "홍길동",
  "phone": "010-1234-5678"
}
```

### 비밀번호 변경

```
PUT /api/user/password
```

**Request Body**
```json
{
  "current_password": "oldpassword",
  "password": "newpassword123",
  "password_confirmation": "newpassword123"
}
```

### 내 메시지 목록

```
GET /api/user/messages
```

**Parameters**
| 파라미터 | 타입 | 설명 |
|---------|------|------|
| page | int | 페이지 번호 |
| per_page | int | 페이지당 개수 (기본: 20) |
| unread_only | bool | 읽지 않은 메시지만 |

**Response**
```json
{
  "success": true,
  "data": {
    "messages": [
      {
        "id": 1,
        "title": "예약 확정 안내",
        "body": "예약이 확정되었습니다.",
        "type": "reservation",
        "url": "/mypage/reservations/123",
        "is_read": false,
        "created_at": "2026-03-02T10:00:00Z"
      }
    ],
    "unread_count": 3,
    "total": 15,
    "page": 1,
    "per_page": 20
  }
}
```

### 메시지 읽음 처리

```
POST /api/user/messages/{id}/read
```

**Response**
```json
{
  "success": true,
  "message": "메시지를 읽음으로 표시했습니다."
}
```

### 모든 메시지 읽음 처리

```
POST /api/user/messages/read-all
```

**Response**
```json
{
  "success": true,
  "message": "모든 메시지를 읽음으로 표시했습니다.",
  "data": {
    "updated_count": 5
  }
}
```

### 메시지 삭제

```
DELETE /api/user/messages/{id}
```

**Response**
```json
{
  "success": true,
  "message": "메시지가 삭제되었습니다."
}
```

---

## 관리자 API

> 인증 필요 + 관리자 권한

### 대시보드 통계

```
GET /api/admin/stats
```

**Parameters**
| 파라미터 | 타입 | 설명 |
|---------|------|------|
| period | string | today/week/month/year |

### 예약 목록 (관리자)

```
GET /api/admin/reservations
```

**Parameters**
| 파라미터 | 타입 | 설명 |
|---------|------|------|
| date | string | 날짜 필터 |
| status | string | 상태 필터 |
| service_id | int | 서비스 필터 |

### 예약 상태 변경

```
POST /api/admin/reservations/{id}/confirm
POST /api/admin/reservations/{id}/cancel
POST /api/admin/reservations/{id}/complete
```

### 서비스 관리

```
GET    /api/admin/services
POST   /api/admin/services
PUT    /api/admin/services/{id}
DELETE /api/admin/services/{id}
POST   /api/admin/services/{id}/toggle
```

---

## 상태 코드

| 코드 | 설명 |
|------|------|
| 200 | 성공 |
| 201 | 생성됨 |
| 400 | 잘못된 요청 |
| 401 | 인증 필요 |
| 403 | 권한 없음 |
| 404 | 찾을 수 없음 |
| 422 | 유효성 검사 실패 |
| 500 | 서버 에러 |

## Rate Limiting

- 인증된 사용자: 분당 60회
- 비인증 사용자: 분당 30회
- 제한 초과 시 429 응답

## 예약 상태

| 상태 | 설명 |
|------|------|
| pending | 대기중 |
| confirmed | 확정 |
| cancelled | 취소됨 |
| completed | 완료 |
| no_show | 노쇼 |
