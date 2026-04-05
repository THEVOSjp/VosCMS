<?php
/**
 * 두 페이지의 너비 비교 테스트
 */
?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>RezlyX Salon - 페이지 너비 비교</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #f5f5f5; padding: 20px; }
        .comparison-container { 
            display: grid; 
            grid-template-columns: 1fr 1fr; 
            gap: 20px; 
            max-width: 100%;
            margin: 0 auto;
        }
        .page-frame {
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        .page-header {
            background: #2c3e50;
            color: white;
            padding: 15px;
            font-weight: bold;
            text-align: center;
        }
        .page-info {
            background: #ecf0f1;
            padding: 10px 15px;
            font-size: 12px;
            color: #34495e;
            border-bottom: 1px solid #bdc3c7;
        }
        iframe {
            width: 100%;
            height: 700px;
            border: none;
            display: block;
        }
        h1 {
            text-align: center;
            margin-bottom: 30px;
            color: #2c3e50;
        }
        .info-text {
            text-align: center;
            margin-bottom: 20px;
            color: #555;
            font-size: 14px;
        }
        .success { color: #27ae60; font-weight: bold; }
        .warning { color: #e74c3c; font-weight: bold; }
    </style>
</head>
<body>
    <h1>RezlyX Salon - 페이지 너비 검증</h1>
    
    <div class="info-text">
        <p>두 페이지의 콘텐츠 너비가 동일한지 확인합니다.</p>
        <p>모두 <span class="success">max-w-7xl</span> 클래스로 제약되어 있습니다.</p>
    </div>
    
    <div class="comparison-container">
        <div class="page-frame">
            <div class="page-header">Booking 페이지</div>
            <div class="page-info">
                Container: <code>max-w-7xl mx-auto px-4</code>
            </div>
            <iframe src="http://localhost/rezlyx_salon/booking"></iframe>
        </div>
        
        <div class="page-frame">
            <div class="page-header">Lookup 페이지</div>
            <div class="page-info">
                Container: <code>max-w-7xl mx-auto px-4</code>
            </div>
            <iframe src="http://localhost/rezlyx_salon/lookup"></iframe>
        </div>
    </div>
</body>
</html>
