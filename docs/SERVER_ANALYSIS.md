# RezlyX 개발/테스트 서버 분석 및 구축 계획

> 최종 업데이트: 2026-03-31

## 1. 서버 하드웨어

| 항목 | 내용 |
|------|------|
| 호스트명 | thevos-X99 |
| CPU | Intel Xeon E5-2683 v4 @ 2.10GHz (16코어 32스레드) |
| 메모리 | 64GB DDR4 (사용 2.8GB / 여유 58GB) |
| 디스크 | NVMe SSD 120GB (사용 30GB / 여유 81GB) |
| 네트워크 | 1Gbps (enp6s0) |
| 마더보드 | X99 플랫폼 |

**평가**: 개발/테스트/데모 서버로 충분. 향후 SaaS 확장 시 SATA SSD/HDD 추가 권장.

## 2. 소프트웨어 현황

| 항목 | 상태 | 버전 |
|------|------|------|
| OS | ✅ 설치됨 | Ubuntu 24.04.2 LTS |
| 커널 | ✅ | 6.14.0-33-generic |
| Nginx | ✅ **설치 완료** | 1.24.0 |
| PHP | ✅ **설치 완료** | 8.3.6 (FPM) |
| MariaDB | ✅ **설치 완료** | 10.11.14 |
| Composer | ✅ **설치 완료** | 2.9.5 |
| Git | ✅ | 2.43.0 |
| Docker | ✅ (대기) | 28.3.2 |
| Node.js | ❌ 미설치 | 필요 시 설치 |

### PHP 확장 모듈

```
bcmath, curl, gd, intl, mbstring, mysqli, mysqlnd, pdo_mysql, zip, opcache
```

## 3. 네트워크 구성

```
인터넷 ──► 공유기/라우터 (192.169.0.1)
                │
                ├── NAS (Xpenology DSM 7.x) ── 21ces.com 연결 중
                │
                └── 개발서버 (192.169.0.10) ── thevos-X99
```

| 항목 | 값 |
|------|------|
| 서버 IP | 192.169.0.10 |
| 게이트웨이 | 192.169.0.1 |
| DNS | 8.8.8.8, 8.8.4.4 |
| 서브넷 | 192.169.0.0/24 |

## 4. 사용자 권한

| 항목 | 상태 |
|------|------|
| 사용자 | thevos |
| sudo | ✅ (sudo 그룹) |
| docker | ✅ (docker 그룹) |

## 5. 설치 완료 내역 (2026-03-31)

### 5.1 구축 방식: 직접 설치 (네이티브)

Docker 대신 직접 설치를 선택한 이유:
- XAMPP 불안정 대체 목적 → 네이티브가 가장 안정적
- 단일 프로젝트 개발서버 → 컨테이너 격리 불필요
- 디버깅 직관적, 성능 오버헤드 없음

### 5.2 설치된 패키지

```bash
# apt install
nginx mariadb-server
php8.3-fpm php8.3-mysql php8.3-mbstring php8.3-xml php8.3-curl
php8.3-gd php8.3-zip php8.3-intl php8.3-bcmath php8.3-opcache

# 별도 설치
composer 2.9.5 (/usr/local/bin/composer)
```

### 5.3 서비스 상태

| 서비스 | 상태 | 자동시작 |
|--------|------|----------|
| nginx | ✅ active | ✅ enabled |
| php8.3-fpm | ✅ active | ✅ enabled |
| mariadb | ✅ active | ✅ enabled |

### 5.4 MariaDB 설정

| 항목 | 값 |
|------|------|
| DB명 | rezlyx_dev |
| 사용자 | rezlyx |
| 비밀번호 | rezlyx2026! |
| 호스트 | localhost |

### 5.5 Nginx 가상호스트

**파일**: `/etc/nginx/sites-available/rezlyx`

```nginx
server {
    listen 80;
    server_name dev.21ces.com salon.21ces.com 192.169.0.10;
    root /var/www/rezlyx;
    index index.php index.html;
    client_max_body_size 50M;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }
    location ~ \.php$ {
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:/var/run/php/php8.3-fpm.sock;
    }
}
```

### 5.6 디렉토리 구조

```
/var/www/rezlyx/          ← RezlyX 웹 루트 (소유: thevos:www-data, 775)
/var/log/nginx/           ← Nginx 로그
  ├── rezlyx_access.log
  └── rezlyx_error.log
```

### 5.7 동작 확인

- `http://192.169.0.10/` → "RezlyX Dev Server Ready" ✅
- `http://192.169.0.10/info.php` → phpinfo() 정상 출력 ✅
- PHP ↔ MariaDB 연결: 확인 필요 (소스 배포 후)

## 6. 남은 작업

### 6.1 개발서버

- [x] Nginx 설치 + 가상호스트 설정
- [x] PHP 8.3-FPM + 확장 모듈 설치
- [x] MariaDB 설치 + DB/사용자 생성
- [x] Composer 설치
- [x] 방화벽 80포트 허용
- [x] 서비스 자동시작 설정
- [ ] RezlyX 소스 배포 (/var/www/rezlyx/)
- [ ] .env 파일 설정
- [ ] DB 마이그레이션 실행
- [ ] storage 디렉토리 권한 설정

### 6.2 NAS (Xpenology DSM 7.x)

- [ ] 역방향 프록시 규칙 추가 (dev.21ces.com → 192.169.0.10:80)
- [ ] SSL 인증서 발급 (Let's Encrypt)
- [ ] 인증서를 역방향 프록시에 연결

**설정 경로**: DSM → 제어판 → 로그인 포털 → 고급 → 역방향 프록시

| 설명 | 소스 | 대상 |
|------|------|------|
| RezlyX 개발 | `https://dev.21ces.com:443` | `http://192.169.0.10:80` |
| 미용실 데모 | `https://salon.21ces.com:443` | `http://192.169.0.10:80` |

### 6.3 DNS (도메인 관리자 패널)

- [ ] dev.21ces.com A 레코드 추가 (NAS 외부 IP)
- [ ] salon.21ces.com A 레코드 추가 (NAS 외부 IP)

## 7. 서브도메인 연결 구조

```
[브라우저] dev.21ces.com
    │
    ▼ DNS → NAS 외부 IP
[NAS] Xpenology DSM 역방향 프록시
    │ Host: dev.21ces.com → http://192.169.0.10:80
    ▼
[개발서버] Nginx (server_name dev.21ces.com)
    │
    ▼
[PHP-FPM] /var/www/rezlyx/index.php
    │
    ▼
[MariaDB] rezlyx_dev DB
```

## 8. 참고사항

- 개발서버는 내부망(192.169.0.x)에 위치, NAS 리버스 프록시를 통해서만 외부 접근
- NAS가 SSL 종단 처리 → 개발서버는 HTTP(80)만 사용
- Docker는 향후 SaaS 멀티테넌트 운영 시 활용 (현재 대기)
- 기존 Docker 이미지(EC-Cube 등)는 정리 가능 (약 2GB 확보)
- info.php는 보안상 소스 배포 후 삭제할 것
