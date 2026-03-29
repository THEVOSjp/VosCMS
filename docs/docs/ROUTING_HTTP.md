# 라우팅, HTTP, 미들웨어 시스템

RezlyX의 라우팅, HTTP 요청/응답 처리, 미들웨어 시스템에 대한 문서입니다.

## 목차

1. [라우팅 시스템](#라우팅-시스템)
2. [HTTP 요청](#http-요청)
3. [HTTP 응답](#http-응답)
4. [컨트롤러](#컨트롤러)
5. [미들웨어](#미들웨어)

---

## 라우팅 시스템

### 기본 라우트 정의

```php
use RzxLib\Core\Routing\Router;

$router = new Router();

// GET 요청
$router->get('/', [HomeController::class, 'index']);
$router->get('/about', [PageController::class, 'about']);

// POST 요청
$router->post('/login', [AuthController::class, 'login']);

// 기타 HTTP 메서드
$router->put('/users/{id}', [UserController::class, 'update']);
$router->patch('/users/{id}', [UserController::class, 'patch']);
$router->delete('/users/{id}', [UserController::class, 'destroy']);

// 모든 메서드
$router->any('/webhook', [WebhookController::class, 'handle']);

// 특정 메서드들
$router->match(['GET', 'POST'], '/form', [FormController::class, 'handle']);
```

### 라우트 파라미터

```php
// 필수 파라미터
$router->get('/users/{id}', [UserController::class, 'show']);

// 선택적 파라미터
$router->get('/posts/{slug?}', [PostController::class, 'show']);

// 파라미터 제약조건
$router->get('/users/{id}', [UserController::class, 'show'])
    ->where('id', '[0-9]+');

// 여러 파라미터
$router->get('/posts/{year}/{month}', [PostController::class, 'archive'])
    ->where([
        'year' => '[0-9]{4}',
        'month' => '[0-9]{2}',
    ]);

// 편의 메서드
$router->get('/orders/{id}', [OrderController::class, 'show'])
    ->whereNumber('id');      // 숫자만

$router->get('/users/{name}', [UserController::class, 'byName'])
    ->whereAlpha('name');     // 알파벳만

$router->get('/items/{uuid}', [ItemController::class, 'show'])
    ->whereUuid('uuid');      // UUID 형식
```

### 이름 있는 라우트

```php
$router->get('/dashboard', [DashboardController::class, 'index'])
    ->name('dashboard');

$router->get('/users/{id}/profile', [UserController::class, 'profile'])
    ->name('user.profile');

// URL 생성
$url = $router->route('dashboard');                    // /dashboard
$url = $router->route('user.profile', ['id' => 5]);   // /users/5/profile
```

### 라우트 그룹

```php
// 접두사 그룹
$router->group(['prefix' => 'admin'], function ($router) {
    $router->get('/', [AdminController::class, 'dashboard']);
    $router->get('/users', [AdminUserController::class, 'index']);
});
// 결과: /admin, /admin/users

// 미들웨어 그룹
$router->group(['middleware' => ['auth']], function ($router) {
    $router->get('/profile', [ProfileController::class, 'show']);
    $router->get('/settings', [SettingsController::class, 'index']);
});

// 복합 그룹
$router->group([
    'prefix' => 'api',
    'middleware' => ['auth', 'throttle'],
], function ($router) {
    $router->get('/users', [ApiUserController::class, 'index']);
    $router->post('/users', [ApiUserController::class, 'store']);
});
```

### 리소스 라우트

```php
// RESTful 리소스 (7개 라우트)
$router->resource('posts', PostController::class);
// GET    /posts          -> index
// GET    /posts/create   -> create
// POST   /posts          -> store
// GET    /posts/{id}     -> show
// GET    /posts/{id}/edit -> edit
// PUT    /posts/{id}     -> update
// DELETE /posts/{id}     -> destroy

// 일부 액션만
$router->resource('photos', PhotoController::class, [
    'only' => ['index', 'show'],
]);

// 일부 액션 제외
$router->resource('comments', CommentController::class, [
    'except' => ['create', 'edit'],
]);

// API 리소스 (create, edit 제외)
$router->apiResource('articles', ArticleController::class);
```

---

## HTTP 요청

### Request 객체

```php
use RzxLib\Core\Http\Request;

// 현재 요청 캡처
$request = Request::capture();

// HTTP 메서드
$method = $request->method();        // GET, POST, etc.
$request->isGet();                   // true/false
$request->isPost();                  // true/false
$request->isMethod('PUT');           // true/false

// URI 및 URL
$uri = $request->uri();              // /users/5
$fullUrl = $request->fullUrl();      // https://example.com/users/5?page=1

// HTTPS 확인
$request->isSecure();                // true/false
```

### 입력값 가져오기

```php
// 단일 값
$name = $request->input('name');
$name = $request->input('name', 'default');

// 모든 입력값
$all = $request->all();

// 특정 키만
$data = $request->only(['name', 'email']);

// 특정 키 제외
$data = $request->except(['password']);

// 존재 확인
if ($request->has('email')) { ... }

// 값이 있는지 확인 (빈 문자열 제외)
if ($request->filled('email')) { ... }

// 쿼리 스트링
$page = $request->query('page', 1);

// POST 데이터
$data = $request->post('data');

// JSON 바디
$data = $request->json('key');
$allJson = $request->json();
```

### 파일 업로드

```php
// 파일 존재 확인
if ($request->hasFile('avatar')) {
    $file = $request->file('avatar');

    // 파일 정보
    $name = $file->getClientOriginalName();   // original.jpg
    $ext = $file->getClientOriginalExtension(); // jpg
    $mime = $file->getMimeType();             // image/jpeg
    $size = $file->getSize();                 // bytes

    // 유효성 확인
    if ($file->isValid()) {
        // 파일 저장
        $path = $file->store('/uploads/avatars');

        // 특정 이름으로 저장
        $path = $file->move('/uploads', 'custom-name.jpg');
    }

    // 이미지 확인
    if ($file->isImage()) {
        $dimensions = $file->getImageDimensions();
        // ['width' => 800, 'height' => 600]
    }
}
```

### 헤더 및 쿠키

```php
// 헤더
$contentType = $request->header('Content-Type');
$allHeaders = $request->headers();

// Bearer 토큰
$token = $request->bearerToken();

// 쿠키
$value = $request->cookie('session_id');

// 클라이언트 정보
$ip = $request->ip();
$userAgent = $request->userAgent();
$referer = $request->referer();
```

### 요청 유형 확인

```php
// AJAX 요청
if ($request->isAjax()) { ... }

// JSON 요청
if ($request->isJson()) { ... }

// JSON 응답 원함
if ($request->wantsJson()) { ... }
```

### 입력값 검증

```php
// 검증 (실패 시 ValidationException)
$validated = $request->validate([
    'name' => 'required|string|max:100',
    'email' => 'required|email|unique:users',
    'password' => 'required|min:8|confirmed',
]);

// 검증 (예외 없이)
$result = $request->validateSafe([
    'name' => 'required|string',
]);

if ($result['valid']) {
    $data = $result['data'];
} else {
    $errors = $result['errors'];
}
```

---

## HTTP 응답

### Response 객체

```php
use RzxLib\Core\Http\Response;

// 기본 응답
$response = new Response('Hello World', 200);
$response->send();

// 체이닝
$response = (new Response('Content'))
    ->setStatusCode(201)
    ->header('X-Custom', 'Value')
    ->send();
```

### JSON 응답

```php
// JSON
$response = Response::json(['name' => 'John', 'age' => 30]);

// 상태 코드 지정
$response = Response::json(['error' => 'Not found'], 404);

// 성공 응답
$response = Response::success($data, '저장되었습니다');
// {"success": true, "message": "저장되었습니다", "data": ...}

// 에러 응답
$response = Response::error('잘못된 요청입니다', 400);
// {"error": true, "message": "잘못된 요청입니다"}

// 페이지네이션
$response = Response::paginate($items, $total, $page, $perPage);
```

### 리다이렉트

```php
use RzxLib\Core\Http\RedirectResponse;

// 기본 리다이렉트
$response = Response::redirect('/dashboard');

// 플래시 메시지와 함께
$response = (new RedirectResponse('/dashboard'))
    ->withSuccess('저장되었습니다')
    ->withInput();

// 검증 에러와 함께
$response = (new RedirectResponse('/form'))
    ->withErrors(['name' => ['이름은 필수입니다.']])
    ->withInput();
```

### 파일 응답

```php
// 파일 다운로드
$response = Response::download('/path/to/file.pdf', 'document.pdf');

// 파일 표시 (인라인)
$response = Response::file('/path/to/image.jpg');
```

### 헤더 설정

```php
// 단일 헤더
$response->header('X-Custom', 'Value');

// 여러 헤더
$response->withHeaders([
    'X-Custom-1' => 'Value1',
    'X-Custom-2' => 'Value2',
]);

// CORS
$response->withCors('*', ['GET', 'POST'], ['Content-Type']);

// 캐시
$response->withCache(60);      // 60분 캐시
$response->withNoCache();      // 캐시 비활성화
```

### 쿠키 설정

```php
$response->cookie(
    'user_preference',  // 이름
    'dark-mode',        // 값
    60,                 // 유효시간 (분)
    '/',                // 경로
    null,               // 도메인
    true,               // Secure
    true,               // HttpOnly
    'Strict'            // SameSite
);

// 쿠키 삭제
$response->forgetCookie('user_preference');
```

---

## 컨트롤러

### 기본 컨트롤러

```php
use RzxLib\Core\Http\Controller;
use RzxLib\Core\Http\Request;
use RzxLib\Core\Http\Response;

class UserController extends Controller
{
    public function index(Request $request): Response
    {
        $users = []; // DB에서 가져오기

        return $this->json(['users' => $users]);
    }

    public function show(Request $request, int $id): Response
    {
        $user = []; // DB에서 가져오기

        if (!$user) {
            return $this->notFound('사용자를 찾을 수 없습니다.');
        }

        return $this->json($user);
    }

    public function store(Request $request): Response
    {
        $data = $this->validateRequest([
            'name' => 'required|string|max:100',
            'email' => 'required|email|unique:users',
        ]);

        // 저장 로직

        return $this->success($user, '사용자가 생성되었습니다.');
    }
}
```

### 뷰 렌더링

```php
class PageController extends Controller
{
    public function about(): Response
    {
        return $this->view('pages.about', [
            'title' => '소개',
            'company' => 'RezlyX',
        ]);
    }
}
```

### 리다이렉트

```php
class AuthController extends Controller
{
    public function login(Request $request): Response
    {
        // 로그인 로직

        return $this->redirect('/dashboard')
            ->withSuccess('로그인되었습니다.');
    }

    public function logout(): Response
    {
        // 로그아웃 로직

        return $this->back(); // 이전 페이지로
    }
}
```

### 플래시 메시지

```php
// 저장
$this->flash('message', '저장되었습니다');

// 가져오기 (한 번만)
$message = $this->getFlash('message');

// 이전 입력값 저장
$this->withInput($request->all());

// 이전 입력값 가져오기
$name = $this->old('name');
```

---

## 미들웨어

### 미들웨어 인터페이스

```php
use RzxLib\Core\Middleware\MiddlewareInterface;
use RzxLib\Core\Http\Request;
use RzxLib\Core\Http\Response;

class LoggingMiddleware implements MiddlewareInterface
{
    public function handle(Request $request, Closure $next): Response
    {
        // 요청 전 처리
        $startTime = microtime(true);

        // 다음 미들웨어/핸들러 실행
        $response = $next($request);

        // 응답 후 처리
        $duration = microtime(true) - $startTime;
        error_log("Request took {$duration}s");

        return $response;
    }
}
```

### 내장 미들웨어

#### AuthMiddleware (인증)

```php
// 로그인 필수
$router->get('/dashboard', [DashboardController::class, 'index'])
    ->middleware('auth');

// 특정 가드 사용
$router->get('/admin', [AdminController::class, 'index'])
    ->middleware('auth:admin');
```

#### GuestMiddleware (게스트 전용)

```php
// 로그인하지 않은 사용자만
$router->get('/login', [AuthController::class, 'showLogin'])
    ->middleware('guest');
```

#### CsrfMiddleware (CSRF 보호)

```php
// 폼에 CSRF 토큰 추가
<form method="POST">
    <?php echo \RzxLib\Core\Middleware\CsrfMiddleware::tokenField(); ?>
    <!-- 폼 필드들 -->
</form>

// AJAX용 메타 태그
<head>
    <?php echo \RzxLib\Core\Middleware\CsrfMiddleware::tokenMeta(); ?>
</head>

// JavaScript에서 사용
const token = document.querySelector('meta[name="csrf-token"]').content;
fetch('/api/endpoint', {
    method: 'POST',
    headers: {
        'X-CSRF-TOKEN': token
    }
});
```

#### ThrottleMiddleware (요청 제한)

```php
// 기본 (60회/분)
$router->post('/api/data', [ApiController::class, 'store'])
    ->middleware('throttle');

// 커스텀 설정 (100회/분)
$router->post('/api/data', [ApiController::class, 'store'])
    ->middleware('throttle:100,60');
```

### 미들웨어 스택

```php
use RzxLib\Core\Middleware\MiddlewareStack;

$stack = new MiddlewareStack();

// 미들웨어 추가
$stack->add(new CsrfMiddleware());
$stack->add(new AuthMiddleware());

// 실행
$response = $stack->handle($request, function($request) use ($controller) {
    return $controller->handle($request);
});
```

### 커스텀 미들웨어

```php
class AdminMiddleware implements MiddlewareInterface
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = auth()->user();

        if (!$user || !$user->isAdmin()) {
            return Response::error('관리자 권한이 필요합니다.', 403);
        }

        return $next($request);
    }
}

// 등록
$stack->alias('admin', AdminMiddleware::class);

// 사용
$router->group(['middleware' => ['auth', 'admin']], function($router) {
    // 관리자 라우트
});
```

---

## 전체 예시

### routes.php

```php
use RzxLib\Core\Routing\Router;

$router = new Router();

// 공개 라우트
$router->get('/', [HomeController::class, 'index']);
$router->get('/about', [PageController::class, 'about']);

// 게스트 전용
$router->group(['middleware' => ['guest']], function($router) {
    $router->get('/login', [AuthController::class, 'showLogin']);
    $router->post('/login', [AuthController::class, 'login']);
    $router->get('/register', [AuthController::class, 'showRegister']);
    $router->post('/register', [AuthController::class, 'register']);
});

// 인증 필요
$router->group(['middleware' => ['auth']], function($router) {
    $router->get('/dashboard', [DashboardController::class, 'index']);
    $router->resource('bookings', BookingController::class);
    $router->post('/logout', [AuthController::class, 'logout']);
});

// 관리자
$router->group(['prefix' => 'admin', 'middleware' => ['auth', 'admin']], function($router) {
    $router->get('/', [AdminController::class, 'dashboard']);
    $router->resource('users', AdminUserController::class);
    $router->resource('services', AdminServiceController::class);

    // 설정 페이지 (서브페이지 구조)
    $router->get('/settings', function() {
        return redirect('/admin/settings/general');
    });
    $router->get('/settings/general', [SettingsController::class, 'general']);
    $router->post('/settings/general', [SettingsController::class, 'updateGeneral']);
    $router->get('/settings/seo', [SettingsController::class, 'seo']);
    $router->get('/settings/pwa', [SettingsController::class, 'pwa']);

    // 시스템 설정 (중첩 서브페이지)
    $router->get('/settings/system', function() {
        return redirect('/admin/settings/system/info');
    });
    $router->get('/settings/system/info', [SettingsController::class, 'systemInfo']);
    $router->get('/settings/system/cache', [SettingsController::class, 'systemCache']);
    $router->get('/settings/system/mode', [SettingsController::class, 'systemMode']);
    $router->get('/settings/system/logs', [SettingsController::class, 'systemLogs']);
});

// API
$router->group(['prefix' => 'api', 'middleware' => ['throttle']], function($router) {
    $router->apiResource('services', ApiServiceController::class);
});

return $router;
```

### index.php

```php
require __DIR__ . '/../vendor/autoload.php';

use RzxLib\Core\Http\Request;
use RzxLib\Core\Middleware\MiddlewareStack;

// 라우터 로드
$router = require __DIR__ . '/../routes/web.php';

// 요청 캡처
$request = Request::capture();

// 라우트 매칭
$route = $router->dispatch($request->method(), $request->uri());

if (!$route) {
    http_response_code(404);
    include __DIR__ . '/../resources/views/errors/404.php';
    exit;
}

// 미들웨어 스택 구성
$stack = new MiddlewareStack();
foreach ($route->getMiddleware() as $middleware) {
    $stack->add($middleware);
}

// 요청에 라우트 파라미터 설정
$request->setRouteParams($route->parameters());

// 실행
$response = $stack->handle($request, function($request) use ($route) {
    return $route->run();
});

$response->send();
```

---

## 관련 파일

| 파일 | 설명 |
|------|------|
| `rzxlib/Core/Routing/Router.php` | 메인 라우터 |
| `rzxlib/Core/Routing/Route.php` | 개별 라우트 |
| `rzxlib/Core/Routing/RouteCollection.php` | 라우트 컬렉션 |
| `rzxlib/Core/Http/Request.php` | HTTP 요청 |
| `rzxlib/Core/Http/Response.php` | HTTP 응답 |
| `rzxlib/Core/Http/RedirectResponse.php` | 리다이렉트 응답 |
| `rzxlib/Core/Http/Controller.php` | 기본 컨트롤러 |
| `rzxlib/Core/Http/UploadedFile.php` | 업로드 파일 |
| `rzxlib/Core/Middleware/MiddlewareInterface.php` | 미들웨어 인터페이스 |
| `rzxlib/Core/Middleware/MiddlewareStack.php` | 미들웨어 스택 |
| `rzxlib/Core/Middleware/AuthMiddleware.php` | 인증 미들웨어 |
| `rzxlib/Core/Middleware/GuestMiddleware.php` | 게스트 미들웨어 |
| `rzxlib/Core/Middleware/CsrfMiddleware.php` | CSRF 미들웨어 |
| `rzxlib/Core/Middleware/ThrottleMiddleware.php` | 요청 제한 미들웨어 |
