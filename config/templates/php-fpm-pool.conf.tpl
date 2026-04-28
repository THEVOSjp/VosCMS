; VosCMS Hosting — PHP-FPM pool per customer
; Order:  {{ORDER}}
; User:   {{USER}}
; Domain: {{DOMAIN}}

[{{ORDER}}]
user = {{USER}}
group = www-data

listen = /var/run/php/{{ORDER}}.sock
listen.owner = www-data
listen.group = www-data
listen.mode = 0660

; ondemand — 트래픽 적은 호스팅에 메모리 효율적 (필요할 때만 프로세스 생성)
pm = ondemand
pm.max_children = 8
pm.process_idle_timeout = 60s
pm.max_requests = 500

; 보안 — 사용자 영역 외부 파일 접근 차단
php_admin_value[open_basedir] = /var/www/customers/{{ORDER}}:/tmp:/usr/share/php
php_admin_value[upload_tmp_dir] = /var/www/customers/{{ORDER}}/tmp
php_admin_value[session.save_path] = /var/www/customers/{{ORDER}}/tmp
php_admin_value[sys_temp_dir] = /var/www/customers/{{ORDER}}/tmp

; 호스팅 식별 (디버깅용)
env[HOSTING_ORDER] = {{ORDER}}
env[HOSTING_USER] = {{USER}}
env[HOSTING_DOMAIN] = {{DOMAIN}}

; 위험 함수 비활성화
php_admin_value[disable_functions] = exec,passthru,shell_exec,system,proc_open,popen,pcntl_exec

; 로그
php_admin_value[error_log] = /var/www/customers/{{ORDER}}/logs/php-error.log
php_admin_flag[log_errors] = on
php_admin_value[memory_limit] = 256M
php_admin_value[max_execution_time] = 60
php_admin_value[upload_max_filesize] = 64M
php_admin_value[post_max_size] = 64M
