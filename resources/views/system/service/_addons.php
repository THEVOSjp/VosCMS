<section class="bg-white dark:bg-zinc-800 rounded-2xl shadow-sm border border-gray-200 dark:border-zinc-700 overflow-hidden">
    <div class="px-6 py-4 bg-gray-50 dark:bg-zinc-800/50 border-b border-gray-200 dark:border-zinc-700">
        <div class="flex items-center gap-2">
            <span class="w-7 h-7 bg-blue-600 text-white rounded-full flex items-center justify-center text-sm font-bold">3</span>
            <h2 class="text-lg font-bold text-gray-900 dark:text-white">부가 서비스</h2>
            <span class="text-xs text-gray-400 dark:text-zinc-500 ml-1">선택사항</span>
        </div>
    </div>
    <div class="p-6 space-y-3">
        <!-- 설치 지원 -->
        <label class="flex items-start gap-4 p-4 border border-gray-200 dark:border-zinc-600 rounded-xl cursor-pointer hover:border-blue-300 dark:hover:border-blue-700 hover:bg-blue-50/50 dark:hover:bg-blue-900/20 transition">
            <input type="checkbox" name="addon_install" class="mt-1 text-blue-600 rounded" checked>
            <div class="flex-1">
                <div class="flex items-center justify-between">
                    <p class="font-semibold text-gray-900 dark:text-white">설치 지원</p>
                    <p class="text-green-600 font-bold">무료</p>
                </div>
                <p class="text-xs text-gray-500 dark:text-zinc-400 mt-1">VosCMS 설치 및 초기 설정을 대행합니다. 도메인 연결, SSL 설정, 기본 환경 구성 포함.</p>
            </div>
        </label>
        <!-- 기술 지원 -->
        <label class="flex items-start gap-4 p-4 border border-gray-200 dark:border-zinc-600 rounded-xl cursor-pointer hover:border-blue-300 dark:hover:border-blue-700 hover:bg-blue-50/50 dark:hover:bg-blue-900/20 transition">
            <input type="checkbox" name="addon_support" class="mt-1 text-blue-600 rounded">
            <div class="flex-1">
                <div class="flex items-center justify-between">
                    <p class="font-semibold text-gray-900 dark:text-white">기술 지원 (1년)</p>
                    <p class="text-blue-600 font-bold">120,000원<span class="text-xs font-normal text-gray-400">/년</span></p>
                </div>
                <p class="text-xs text-gray-500 dark:text-zinc-400 mt-1">이메일/채팅 기술 지원, 버그 수정, 보안 업데이트 적용, 장애 대응 (영업일 기준 24시간 이내 응답).</p>
            </div>
        </label>
        <!-- 정기 유지보수 -->
        <div class="p-4 border border-gray-200 dark:border-zinc-600 rounded-xl">
            <div class="flex items-center justify-between mb-3">
                <p class="font-semibold text-gray-900 dark:text-white">정기 유지보수</p>
                <span class="text-xs text-gray-400 dark:text-zinc-500">택 1</span>
            </div>
            <div class="space-y-2">
                <label class="flex items-start gap-3 p-3 border border-gray-100 dark:border-zinc-600 rounded-lg cursor-pointer hover:border-blue-300 dark:hover:border-blue-700 transition">
                    <input type="radio" name="maintenance" value="10000" class="mt-0.5 text-blue-600">
                    <div class="flex-1"><div class="flex items-center justify-between"><p class="text-sm font-medium text-gray-900 dark:text-white">Basic</p><p class="text-sm font-bold text-blue-600">10,000원<span class="text-xs font-normal text-gray-400">/월</span></p></div><p class="text-xs text-gray-500 dark:text-zinc-400 mt-0.5">보안 업데이트 적용, 월 1회 백업 확인</p></div>
                </label>
                <label class="flex items-start gap-3 p-3 border border-gray-100 dark:border-zinc-600 rounded-lg cursor-pointer hover:border-blue-300 dark:hover:border-blue-700 transition">
                    <input type="radio" name="maintenance" value="20000" class="mt-0.5 text-blue-600">
                    <div class="flex-1"><div class="flex items-center justify-between"><p class="text-sm font-medium text-gray-900 dark:text-white">Standard</p><p class="text-sm font-bold text-blue-600">20,000원<span class="text-xs font-normal text-gray-400">/월</span></p></div><p class="text-xs text-gray-500 dark:text-zinc-400 mt-0.5">보안 업데이트, 플러그인/코어 업데이트, 주 1회 백업, 이메일 기술지원</p></div>
                </label>
                <label class="flex items-start gap-3 p-3 border border-gray-100 dark:border-zinc-600 rounded-lg cursor-pointer hover:border-blue-300 dark:hover:border-blue-700 transition">
                    <input type="radio" name="maintenance" value="30000" class="mt-0.5 text-blue-600">
                    <div class="flex-1"><div class="flex items-center justify-between"><p class="text-sm font-medium text-gray-900 dark:text-white">Pro</p><p class="text-sm font-bold text-blue-600">30,000원<span class="text-xs font-normal text-gray-400">/월</span></p></div><p class="text-xs text-gray-500 dark:text-zinc-400 mt-0.5">Standard + 성능 모니터링, 장애 대응 (24h 이내), 일일 백업, 월 1회 리포트</p></div>
                </label>
                <label class="flex items-start gap-3 p-3 border border-blue-100 dark:border-blue-800 bg-blue-50/30 dark:bg-blue-900/20 rounded-lg cursor-pointer hover:border-blue-300 transition">
                    <input type="radio" name="maintenance" value="50000" class="mt-0.5 text-blue-600">
                    <div class="flex-1"><div class="flex items-center justify-between"><div class="flex items-center gap-2"><p class="text-sm font-medium text-gray-900 dark:text-white">Enterprise</p><span class="text-[10px] px-1.5 py-0.5 bg-blue-600 text-white rounded-full font-semibold">포털 · 쇼핑몰</span></div><p class="text-sm font-bold text-blue-600">50,000원<span class="text-xs font-normal text-gray-400">/월</span></p></div><p class="text-xs text-gray-500 dark:text-zinc-400 mt-0.5">Pro + 전담 매니저, 긴급 장애 대응 (4h 이내), 커스텀 기능 월 2건, 트래픽 분석</p></div>
                </label>
                <label class="flex items-start gap-3 p-3 border border-gray-100 dark:border-zinc-600 rounded-lg cursor-pointer transition">
                    <input type="radio" name="maintenance" value="0" class="mt-0.5 text-gray-400" checked>
                    <p class="text-sm text-gray-400 dark:text-zinc-500">유지보수 신청 안 함</p>
                </label>
            </div>
        </div>
        <!-- 커스터마이징 -->
        <label class="flex items-start gap-4 p-4 border border-gray-200 dark:border-zinc-600 rounded-xl cursor-pointer hover:border-blue-300 dark:hover:border-blue-700 transition">
            <input type="checkbox" name="addon_custom" class="mt-1 text-blue-600 rounded">
            <div class="flex-1">
                <div class="flex items-center justify-between"><p class="font-semibold text-gray-900 dark:text-white">커스터마이징 개발</p><p class="text-blue-600 font-bold">별도 견적</p></div>
                <p class="text-xs text-gray-500 dark:text-zinc-400 mt-1">맞춤 디자인, 전용 플러그인 개발, 외부 시스템 연동, 데이터 마이그레이션 등.</p>
            </div>
        </label>
        <!-- 기본 메일 안내 + 메일 계정 입력 -->
        <div class="p-4 border border-green-200 dark:border-green-800 bg-green-50/50 dark:bg-green-900/20 rounded-xl">
            <div class="flex items-center gap-2 mb-1">
                <svg class="w-4 h-4 text-green-600" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"/></svg>
                <p class="font-semibold text-gray-900 dark:text-white">기본 메일 5개 포함</p>
                <span class="text-xs text-green-600 font-medium">호스팅 기본 제공</span>
            </div>
            <p class="text-xs text-gray-500 dark:text-zinc-400 ml-6 mb-3">도메인 기반 이메일 5개 (예: info@yourdomain.com). 웹메일, IMAP/POP3 지원. 계정당 1GB.</p>
            <!-- 메일 계정 입력 -->
            <div class="ml-6 space-y-2" id="mailAccountsWrap">
                <p class="text-xs font-medium text-gray-600 dark:text-zinc-300 mb-1">메일 계정 설정 (최대 5개)</p>
                <div class="mail-account-row flex items-center gap-2">
                    <div class="flex-1 flex items-center border border-gray-300 dark:border-zinc-600 rounded-lg overflow-hidden">
                        <input type="text" name="mail_id[]" placeholder="info" class="flex-1 px-3 py-2 text-sm bg-white dark:bg-zinc-700 dark:text-white border-0 focus:ring-0 min-w-0">
                        <span class="px-2 text-sm text-gray-400 dark:text-zinc-500 bg-gray-50 dark:bg-zinc-600 border-l border-gray-300 dark:border-zinc-600 whitespace-nowrap" id="mailDomainSuffix">@yourdomain.com</span>
                    </div>
                    <input type="password" name="mail_pw[]" placeholder="비밀번호" class="w-36 px-3 py-2 text-sm border border-gray-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white rounded-lg focus:ring-2 focus:ring-blue-500">
                </div>
            </div>
            <button type="button" onclick="addMailAccount()" class="ml-6 mt-2 text-xs text-blue-600 hover:underline">+ 메일 계정 추가</button>
        </div>
        <!-- 비즈니스 메일 -->
        <label class="flex items-start gap-4 p-4 border border-gray-200 dark:border-zinc-600 rounded-xl cursor-pointer hover:border-blue-300 dark:hover:border-blue-700 transition">
            <input type="checkbox" name="addon_bizmail" class="mt-1 text-blue-600 rounded">
            <div class="flex-1">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-2"><p class="font-semibold text-gray-900 dark:text-white">비즈니스 메일</p><span class="text-[10px] px-1.5 py-0.5 bg-amber-100 dark:bg-amber-900/50 text-amber-700 dark:text-amber-300 rounded-full font-semibold">대용량</span></div>
                    <p class="text-blue-600 font-bold">5,000원<span class="text-xs font-normal text-gray-400">/계정/월</span></p>
                </div>
                <p class="text-xs text-gray-500 dark:text-zinc-400 mt-1">대용량 첨부파일 전송 (최대 10GB), 계정당 10GB 저장공간, 광고 없는 웹메일, 스팸 필터.</p>
                <div class="mt-2 flex items-center gap-2">
                    <span class="text-xs text-gray-500 dark:text-zinc-400">추가 계정 수:</span>
                    <select name="bizmail_count" class="px-2 py-1 border border-gray-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white rounded text-xs">
                        <option value="0">추가 없음</option><option>1</option><option>3</option><option>5</option><option>10</option><option>20</option>
                    </select>
                </div>
            </div>
        </label>
    </div>
</section>
