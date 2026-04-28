server {
    listen 80;
    listen [::]:80;
    server_name {{DOMAIN}};

    root {{DOCROOT}};
    index index.php index.html;

    # 호스팅 식별 (관리자 추적용)
    # X-Hosting-Order: {{ORDER}}
    # X-Hosting-User: {{USER}}

    access_log /var/www/customers/{{ORDER}}/logs/access.log;
    error_log  /var/www/customers/{{ORDER}}/logs/error.log warn;

    client_max_body_size 64M;

    # Let's Encrypt ACME challenge — HTTPS 발급/갱신용
    location /.well-known/acme-challenge/ {
        root /var/www/customers/{{ORDER}}/public_html;
    }

    # 보안 — dotfiles (acme-challenge 외)
    location ~ /\.(?!well-known) {
        deny all;
    }

    # PHP-FPM — 사용자 전용 pool socket
    location ~ \.php$ {
        try_files $uri =404;
        fastcgi_split_path_info ^(.+\.php)(/.+)$;
        fastcgi_pass unix:/var/run/php/{{ORDER}}.sock;
        fastcgi_index index.php;
        include fastcgi_params;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        fastcgi_param HOSTING_ORDER {{ORDER}};
        fastcgi_param HOSTING_USER {{USER}};
        fastcgi_read_timeout 60s;
    }

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    # gzip
    gzip on;
    gzip_types text/plain text/css application/json application/javascript text/xml application/xml application/xml+rss text/javascript;

    # 보안 헤더 (HTTPS 적용 후 추가)
    # add_header Strict-Transport-Security "max-age=31536000" always;
    # add_header X-Content-Type-Options nosniff;
    # add_header X-Frame-Options SAMEORIGIN;
}
