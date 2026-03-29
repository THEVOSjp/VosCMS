# 예약 시스템

RezlyX의 핵심 예약 시스템에 대한 문서입니다.

## 목차

1. [모델](#모델)
2. [서비스 클래스](#서비스-클래스)
3. [데이터베이스 스키마](#데이터베이스-스키마)
4. [사용 예시](#사용-예시)

---

## 모델

### Service (서비스/상품)

예약 가능한 서비스나 상품을 나타냅니다.

```php
use RzxLib\Reservation\Models\Service;

// 모든 서비스 가져오기
$services = Service::all();

// 활성 서비스만
$services = Service::active();

// ID로 찾기
$service = Service::find(1);
$service = Service::findOrFail(1); // 없으면 예외

// Slug로 찾기
$service = Service::findBySlug('basic-cut');

// 카테고리별 서비스
$services = Service::byCategory(1);
```

#### 주요 속성

| 속성 | 타입 | 설명 |
|------|------|------|
| `id` | int | 고유 ID |
| `name` | string | 서비스명 |
| `slug` | string | URL 슬러그 |
| `description` | string | 상세 설명 |
| `duration` | int | 소요시간 (분) |
| `price` | float | 가격 |
| `currency` | string | 통화 (기본: KRW) |
| `category_id` | int | 카테고리 ID |
| `is_active` | bool | 활성 상태 |
| `max_capacity` | int | 동시 예약 가능 인원 |
| `buffer_time` | int | 예약 간 버퍼 시간 (분) |
| `advance_booking_days` | int | 예약 가능 기간 (일) |
| `min_notice_hours` | int | 최소 예약 사전 알림 (시간) |

#### 메서드

```php
// 저장
$service = new Service();
$service->name = '새 서비스';
$service->price = 50000;
$service->duration = 60;
$service->save();

// 포맷팅
$service->formattedPrice();    // "50,000원"
$service->formattedDuration(); // "1시간"

// 가용성 확인
$service->isAvailableOn('2025-03-01'); // true/false

// 가용 시간대 가져오기
$slots = $service->getAvailableSlots('2025-03-01');
```

---

### ServiceCategory (서비스 카테고리)

서비스를 분류하는 카테고리입니다.

```php
use RzxLib\Reservation\Models\ServiceCategory;

// 모든 카테고리
$categories = ServiceCategory::all();

// 활성 카테고리만
$categories = ServiceCategory::active();

// 최상위 카테고리
$roots = ServiceCategory::roots();

// ID로 찾기
$category = ServiceCategory::find(1);

// 하위 카테고리
$children = $category->children();

// 부모 카테고리
$parent = $category->parent();

// 이 카테고리의 서비스
$services = $category->services();
$count = $category->servicesCount();
```

---

### TimeSlot (시간대)

예약 가능한 시간대를 정의합니다.

```php
use RzxLib\Reservation\Models\TimeSlot;

// 서비스의 특정 날짜 가용 슬롯
$slots = TimeSlot::availableForService($serviceId, '2025-03-01');

// 요일별 기본 슬롯
$slots = TimeSlot::defaultSlots(1); // 월요일

// 날짜 차단 여부 확인
$blocked = TimeSlot::isDateBlocked('2025-03-01');
$blocked = TimeSlot::isDateBlocked('2025-03-01', $serviceId);

// 날짜 차단
TimeSlot::blockDate('2025-12-25'); // 크리스마스

// 날짜 차단 해제
TimeSlot::unblockDate('2025-12-25');
```

#### 시간대 생성

```php
// 기본 운영 시간 설정 (월~금)
for ($day = 1; $day <= 5; $day++) {
    $slot = new TimeSlot();
    $slot->day_of_week = $day;
    $slot->start_time = '09:00';
    $slot->end_time = '18:00';
    $slot->max_bookings = 10;
    $slot->save();
}

// 특정 서비스 전용 시간대
$slot = new TimeSlot();
$slot->service_id = 1;
$slot->day_of_week = 6; // 토요일
$slot->start_time = '10:00';
$slot->end_time = '15:00';
$slot->save();
```

---

### Reservation (예약)

고객 예약 정보를 나타냅니다.

```php
use RzxLib\Reservation\Models\Reservation;

// ID로 찾기
$reservation = Reservation::find(1);

// 예약번호로 찾기
$reservation = Reservation::findByCode('RZ250301ABC123');

// 특정 날짜의 예약
$reservations = Reservation::forDate('2025-03-01');

// 서비스 + 날짜로 예약 조회
$reservations = Reservation::forServiceAndDate($serviceId, '2025-03-01');

// 사용자의 예약
$reservations = Reservation::forUser($userId);
$reservations = Reservation::forUser($userId, 'confirmed');

// 다가오는 예약
$upcoming = Reservation::upcoming(10);

// 오늘 예약
$today = Reservation::today();
```

#### 예약 상태

| 상태 | 설명 |
|------|------|
| `pending` | 대기중 |
| `confirmed` | 확정 |
| `cancelled` | 취소됨 |
| `completed` | 완료 |
| `no_show` | 노쇼 |

#### 결제 상태

| 상태 | 설명 |
|------|------|
| `pending` | 결제 대기 |
| `paid` | 결제 완료 |
| `refunded` | 환불 완료 |
| `partial` | 부분 결제 |

#### 상태 변경

```php
// 예약 확정
$reservation->confirm();

// 예약 취소
$reservation->cancel('고객 요청');

// 예약 완료
$reservation->complete();

// 노쇼 처리
$reservation->markNoShow();

// 결제 완료
$reservation->markPaid('payment_123', 'card');

// 환불
$reservation->markRefunded();
```

#### 헬퍼 메서드

```php
// 상태 라벨
$reservation->getStatusLabel();  // "확정"
$reservation->getStatusColor();  // "blue"

// 취소 가능 여부
$reservation->isCancellable();   // true/false

// 날짜/시간 포맷
$reservation->getFormattedDateTime(); // "2025년 03월 01일 (토) 10:00"
$reservation->getFormattedPrice();    // "50,000원"

// 시간 확인
$reservation->isPast();   // 지난 예약인지
$reservation->isToday();  // 오늘 예약인지

// 서비스 가져오기
$service = $reservation->getService();
```

---

## 서비스 클래스

### ReservationService

예약 관련 비즈니스 로직을 처리합니다.

```php
use RzxLib\Reservation\Services\ReservationService;

$reservationService = new ReservationService();
```

#### 예약 생성

```php
$reservation = $reservationService->create([
    'service_id' => 1,
    'customer_name' => '홍길동',
    'customer_email' => 'hong@example.com',
    'customer_phone' => '010-1234-5678',
    'booking_date' => '2025-03-01',
    'start_time' => '10:00',
    'guests' => 1,
    'notes' => '창가 자리 요청',
]);

echo $reservation->booking_code; // "RZ250301ABC123"
```

#### 예약 수정

```php
$reservation = Reservation::find(1);

$reservation = $reservationService->update($reservation, [
    'booking_date' => '2025-03-02',
    'start_time' => '14:00',
    'notes' => '수정된 메모',
]);
```

#### 예약 취소

```php
$reservation = Reservation::find(1);

$reservation = $reservationService->cancel($reservation, '일정 변경');
```

#### 예약 확정

```php
$reservation = $reservationService->confirm($reservation);
```

#### 가용성 확인

```php
$service = Service::find(1);

// 특정 시간 가용 여부 (예외 발생)
try {
    $reservationService->checkAvailability(
        $service,
        '2025-03-01',
        '10:00',
        2  // 인원
    );
    // 예약 가능
} catch (\RuntimeException $e) {
    // 예약 불가능
    echo $e->getMessage();
}
```

#### 가용 시간대 조회

```php
$service = Service::find(1);

// 특정 날짜의 가용 시간대
$slots = $reservationService->getAvailableSlots($service, '2025-03-01');

// 결과 예시
// [
//     ['time' => '09:00', 'end_time' => '10:00', 'available' => 3, 'max_capacity' => 5],
//     ['time' => '10:00', 'end_time' => '11:00', 'available' => 2, 'max_capacity' => 5],
//     ...
// ]
```

#### 가용 날짜 조회

```php
// 특정 월의 가용 날짜
$dates = $reservationService->getAvailableDates($service, 2025, 3);

// 결과 예시
// [
//     ['date' => '2025-03-01', 'available' => true, 'slots_count' => 8],
//     ['date' => '2025-03-02', 'available' => false, 'slots_count' => 0],
//     ...
// ]
```

#### 통계 조회

```php
$stats = $reservationService->getStatistics('2025-03-01', '2025-03-31');

// 결과 예시
// [
//     'total' => 150,
//     'confirmed' => 100,
//     'pending' => 20,
//     'cancelled' => 15,
//     'completed' => 10,
//     'no_show' => 5,
//     'total_revenue' => 5000000,
//     'by_date' => ['2025-03-01' => 10, ...],
//     'by_service' => [1 => 50, 2 => 30, ...],
// ]
```

---

## 데이터베이스 스키마

### 테이블 구조

| 테이블 | 설명 |
|--------|------|
| `rzx_service_categories` | 서비스 카테고리 |
| `rzx_services` | 서비스/상품 |
| `rzx_time_slots` | 예약 시간대 |
| `rzx_reservations` | 예약 |
| `rzx_reservation_logs` | 예약 이력 |
| `rzx_users` | 사용자 |
| `rzx_settings` | 시스템 설정 |

### 마이그레이션 실행

```bash
mysql -u root -p rezlyx < database/migrations/001_create_reservation_tables.sql
```

---

## 사용 예시

### 예약 폼 처리

```php
use RzxLib\Reservation\Services\ReservationService;
use RzxLib\Core\Http\Request;
use RzxLib\Core\Http\Response;
use RzxLib\Core\Validation\ValidationException;

class BookingController extends Controller
{
    protected ReservationService $reservationService;

    public function __construct()
    {
        $this->reservationService = new ReservationService();
    }

    // 예약 가능 시간 조회 API
    public function getSlots(Request $request): Response
    {
        $serviceId = (int) $request->input('service_id');
        $date = $request->input('date');

        $service = Service::findOrFail($serviceId);
        $slots = $this->reservationService->getAvailableSlots($service, $date);

        return $this->json(['slots' => $slots]);
    }

    // 예약 생성
    public function store(Request $request): Response
    {
        try {
            $reservation = $this->reservationService->create($request->all());

            return $this->success([
                'booking_code' => $reservation->booking_code,
                'datetime' => $reservation->getFormattedDateTime(),
            ], '예약이 완료되었습니다.');

        } catch (ValidationException $e) {
            return $this->json([
                'error' => true,
                'message' => '입력값을 확인해주세요.',
                'errors' => $e->errors(),
            ], 422);

        } catch (\RuntimeException $e) {
            return $this->error($e->getMessage());
        }
    }

    // 예약 조회
    public function show(Request $request, string $code): Response
    {
        $reservation = Reservation::findByCode($code);

        if (!$reservation) {
            return $this->notFound('예약을 찾을 수 없습니다.');
        }

        return $this->json([
            'reservation' => $reservation->toArray(),
            'service' => $reservation->getService()?->toArray(),
        ]);
    }

    // 예약 취소
    public function cancel(Request $request, string $code): Response
    {
        $reservation = Reservation::findByCode($code);

        if (!$reservation) {
            return $this->notFound('예약을 찾을 수 없습니다.');
        }

        try {
            $reason = $request->input('reason');
            $this->reservationService->cancel($reservation, $reason);

            return $this->success(null, '예약이 취소되었습니다.');

        } catch (\RuntimeException $e) {
            return $this->error($e->getMessage());
        }
    }
}
```

### 관리자 예약 관리

```php
class AdminReservationController extends Controller
{
    protected ReservationService $reservationService;

    public function __construct()
    {
        $this->reservationService = new ReservationService();
    }

    // 예약 목록
    public function index(Request $request): Response
    {
        $date = $request->input('date', date('Y-m-d'));
        $status = $request->input('status');

        $query = Reservation::query()
            ->where('booking_date', $date)
            ->orderBy('start_time');

        if ($status) {
            $query->where('status', $status);
        }

        $reservations = $query->get();

        return $this->view('admin.reservations.index', [
            'reservations' => array_map([Reservation::class, 'fromArray'], $reservations),
            'date' => $date,
        ]);
    }

    // 예약 확정
    public function confirm(Request $request, int $id): Response
    {
        $reservation = Reservation::findOrFail($id);
        $this->reservationService->confirm($reservation);

        return $this->redirect('/admin/reservations')
            ->withSuccess('예약이 확정되었습니다.');
    }

    // 통계 대시보드
    public function statistics(Request $request): Response
    {
        $startDate = $request->input('start', date('Y-m-01'));
        $endDate = $request->input('end', date('Y-m-t'));

        $stats = $this->reservationService->getStatistics($startDate, $endDate);

        return $this->view('admin.reservations.statistics', [
            'stats' => $stats,
            'startDate' => $startDate,
            'endDate' => $endDate,
        ]);
    }
}
```

---

## 관련 파일

| 파일 | 설명 |
|------|------|
| `rzxlib/Reservation/Models/Service.php` | 서비스 모델 |
| `rzxlib/Reservation/Models/ServiceCategory.php` | 카테고리 모델 |
| `rzxlib/Reservation/Models/TimeSlot.php` | 시간대 모델 |
| `rzxlib/Reservation/Models/Reservation.php` | 예약 모델 |
| `rzxlib/Reservation/Services/ReservationService.php` | 예약 서비스 |
| `database/migrations/001_create_reservation_tables.sql` | DB 마이그레이션 |
