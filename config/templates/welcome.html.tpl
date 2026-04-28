<!DOCTYPE html>
<html lang="ko">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>{{DOMAIN}} — 호스팅 활성화</title>
<style>
  * { margin: 0; padding: 0; box-sizing: border-box; }
  body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); min-height: 100vh; display: flex; align-items: center; justify-content: center; color: #333; padding: 20px; }
  .card { background: white; border-radius: 16px; padding: 48px 40px; max-width: 560px; width: 100%; box-shadow: 0 20px 60px rgba(0,0,0,.2); }
  .badge { display: inline-block; padding: 6px 14px; background: #d1fae5; color: #065f46; border-radius: 20px; font-size: 12px; font-weight: 600; margin-bottom: 20px; }
  h1 { font-size: 28px; margin-bottom: 12px; color: #111; }
  .domain { color: #4f46e5; font-family: monospace; }
  p { color: #6b7280; line-height: 1.6; margin-bottom: 16px; }
  .info { background: #f3f4f6; border-radius: 12px; padding: 20px; margin: 24px 0; }
  .info dt { font-size: 11px; color: #9ca3af; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 4px; }
  .info dd { font-family: monospace; font-size: 14px; color: #111; margin-bottom: 12px; }
  .info dd:last-child { margin-bottom: 0; }
  .footer { font-size: 12px; color: #9ca3af; text-align: center; margin-top: 32px; }
  a { color: #4f46e5; text-decoration: none; }
</style>
</head>
<body>
<div class="card">
  <span class="badge">✓ Hosting Active</span>
  <h1>호스팅이 활성화되었습니다</h1>
  <p><span class="domain">{{DOMAIN}}</span> 의 웹 호스팅 환경이 정상적으로 셋업되었습니다.</p>

  <div class="info">
    <dt>주문 번호</dt><dd>{{ORDER}}</dd>
    <dt>도메인</dt><dd>{{DOMAIN}}</dd>
    <dt>용량</dt><dd>{{CAPACITY}}</dd>
    <dt>활성화일</dt><dd>{{DATE}}</dd>
  </div>

  <p>FTP / SSH 접속 정보 및 사용 안내는 마이페이지에서 확인하실 수 있습니다. 이 페이지는 첫 파일 업로드 시 자동으로 사용자의 콘텐츠로 교체됩니다.</p>

  <div class="footer">
    Powered by <a href="https://voscms.com" target="_blank">VosCMS</a>
  </div>
</div>
</body>
</html>
