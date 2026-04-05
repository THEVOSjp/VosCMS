const { chromium } = require('playwright');

(async () => {
  const browser = await chromium.launch({
    headless: false,
    args: ['--no-sandbox']
  });

  try {
    const page = await browser.newPage();
    
    // 콘솔 로그 캡처
    const consoleLogs = [];
    page.on('console', msg => {
      consoleLogs.push({
        type: msg.type(),
        text: msg.text(),
        location: msg.location()
      });
      console.log(`[${msg.type().toUpperCase()}] ${msg.text()}`);
    });

    // 페이지 에러 캡처
    const pageErrors = [];
    page.on('pageerror', err => {
      pageErrors.push(err.toString());
      console.error(`[PAGE ERROR] ${err}`);
    });

    console.log('1. 페이지로 이동 중...');
    await page.goto('http://localhost/rezlyx_salon/customer/booking/lookup', {
      waitUntil: 'networkidle'
    });

    console.log('2. 예약번호 입력 중...');
    await page.fill('input[name="booking_id"]', 'RZX260328B7C5A2');
    console.log('   - 예약번호 입력 완료: RZX260328B7C5A2');

    console.log('3. 이메일 입력 중...');
    await page.fill('input[name="email"]', 'ahnsy@gmail.com');
    console.log('   - 이메일 입력 완료: ahnsy@gmail.com');

    console.log('4. 조회 버튼 클릭 중...');
    await page.click('button[type="submit"]');
    
    console.log('5. 페이지 로드 대기 중...');
    await page.waitForNavigation({ waitUntil: 'networkidle' });

    console.log('6. 스크린샷 저장 중...');
    await page.screenshot({ path: 'booking_lookup_result.png', fullPage: true });
    console.log('   - 스크린샷 저장 완료: booking_lookup_result.png');

    console.log('7. 페이지 제목 확인: ' + await page.title());

    console.log('8. "포함 서비스" 텍스트 확인 중...');
    const pageContent = await page.content();
    if (pageContent.includes('booking.detail.services') || pageContent.includes('포함 서비스')) {
      console.log('   ✓ "포함 서비스" 관련 텍스트 발견');
    } else {
      console.log('   ✗ "포함 서비스" 관련 텍스트 미발견');
    }

    console.log('\n=== 콘솔 로그 ===');
    consoleLogs.forEach(log => {
      console.log(`[${log.type}] ${log.text}`);
    });

    if (pageErrors.length > 0) {
      console.log('\n=== 페이지 에러 ===');
      pageErrors.forEach(err => console.log(err));
    } else {
      console.log('\n✓ 페이지 에러 없음');
    }

  } catch (error) {
    console.error('테스트 중 오류 발생:', error);
  } finally {
    await browser.close();
  }
})();
