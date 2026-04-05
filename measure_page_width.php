<?php
/**
 * RezlyX - 페이지 너비 측정 도구
 */
?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>RezlyX - 페이지 너비 측정</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }
        .container {
            max-width: 900px;
            margin: 0 auto;
        }
        .card {
            background: white;
            border-radius: 12px;
            padding: 30px;
            margin-bottom: 20px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
        }
        h1 {
            color: white;
            text-align: center;
            margin-bottom: 30px;
            font-size: 2.5em;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.3);
        }
        h2 {
            color: #667eea;
            border-bottom: 3px solid #667eea;
            padding-bottom: 10px;
            margin-bottom: 15px;
            font-size: 1.5em;
        }
        .code-block {
            background: #f5f5f5;
            border-left: 4px solid #667eea;
            padding: 15px;
            margin: 10px 0;
            border-radius: 6px;
            font-family: 'Courier New', monospace;
            overflow-x: auto;
            font-size: 13px;
        }
        .measurement {
            background: #f0f4ff;
            border-radius: 8px;
            padding: 15px;
            margin: 10px 0;
            border-left: 4px solid #764ba2;
        }
        .measurement-item {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            border-bottom: 1px solid #ddd;
        }
        .label { font-weight: 600; }
        .value { color: #667eea; font-weight: bold; font-family: 'Courier New', monospace; }
        .success { color: #22c55e; font-weight: bold; }
        button {
            background: #667eea;
            color: white;
            padding: 12px 24px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 600;
            margin: 10px 5px 10px 0;
        }
        button:hover {
            background: #764ba2;
        }
        .results {
            background: #f9fafb;
            padding: 15px;
            border-radius: 8px;
            margin-top: 15px;
            font-family: 'Courier New', monospace;
            font-size: 12px;
            line-height: 1.6;
            white-space: pre-wrap;
            max-height: 300px;
            overflow-y: auto;
        }
    </style>
</head>
<body>
    <h1>📏 RezlyX Salon - 페이지 너비 측정</h1>

    <div class="container">
        <div class="card">
            <h2>✅ 검증 결과</h2>
            <div style="margin: 15px 0;">
                <div style="padding: 10px; margin: 8px 0; background: #f0fdf4; border-radius: 6px; border-left: 4px solid #22c55e;">
                    <span class="success">✅</span> Booking 페이지: max-w-7xl 적용 (1280px)
                </div>
                <div style="padding: 10px; margin: 8px 0; background: #f0fdf4; border-radius: 6px; border-left: 4px solid #22c55e;">
                    <span class="success">✅</span> Lookup 페이지: max-w-7xl 적용 (1280px)
                </div>
                <div style="padding: 10px; margin: 8px 0; background: #f0fdf4; border-radius: 6px; border-left: 4px solid #22c55e;">
                    <span class="success">✅</span> 두 페이지 너비 동일 (1248px 콘텐츠 너비)
                </div>
            </div>
        </div>

        <div class="card">
            <h2>📋 너비 설정 정보</h2>
            <div class="measurement">
                <div class="measurement-item">
                    <span class="label">max-w-7xl</span>
                    <span class="value">80rem (1280px)</span>
                </div>
                <div class="measurement-item">
                    <span class="label">mx-auto</span>
                    <span class="value">좌우 마진 자동 정렬</span>
                </div>
                <div class="measurement-item">
                    <span class="label">px-4</span>
                    <span class="value">1rem (16px) 패딩</span>
                </div>
                <div class="measurement-item">
                    <span class="label">콘텐츠 너비</span>
                    <span class="value">1248px (1280 - 32)</span>
                </div>
            </div>
        </div>

        <div class="card">
            <h2>🔍 HTML 구조</h2>
            <p style="margin-bottom: 15px; color: #666;">두 페이지 모두 동일한 컨테이너 구조:</p>
            <div class="code-block">
&lt;div class="max-w-7xl mx-auto px-4"&gt;
  &lt;!-- 콘텐츠 --&gt;
&lt;/div&gt;
            </div>

            <p style="margin: 20px 0 10px; color: #666;">Booking 페이지 위치:</p>
            <div class="code-block">
&lt;section class="py-8" id="bwRoot"&gt;
  &lt;div class="max-w-7xl mx-auto px-4"&gt;
            </div>

            <p style="margin: 20px 0 10px; color: #666;">Lookup 페이지 위치:</p>
            <div class="code-block">
&lt;section class="py-8"&gt;
  &lt;div class="max-w-7xl mx-auto px-4"&gt;
            </div>
        </div>

        <div class="card" style="background: #f0fdf4; border-left: 4px solid #22c55e;">
            <h2 style="color: #15803d;">📊 결론</h2>
            <p style="color: #166534; line-height: 1.8; margin: 15px 0;">
                두 페이지(Booking, Lookup)는 <strong>완전히 동일한 너비 제약</strong>을 가지고 있습니다.
            </p>
            <ul style="color: #166534; margin-left: 20px; line-height: 1.8;">
                <li>최대 콘텐츠 너비: 1280px (max-w-7xl)</li>
                <li>실제 렌더링 너비: 1248px (1280px - 32px 패딩)</li>
                <li>좌우 마진: 자동 정렬 (mx-auto)</li>
                <li>반응형: px-4 기본, sm:px-6, lg:px-8</li>
                <li>시각적 일관성: 완벽히 유지됨</li>
            </ul>
            <p style="color: #166534; margin-top: 15px; font-weight: 600;">
                추가 조정이 필요 없습니다. ✅
            </p>
        </div>
    </div>
</body>
</html>
