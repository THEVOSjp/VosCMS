<!-- ⑥ 부가 서비스 -->
<?php
$_defAddons = [
    ['key'=>'install','label'=>'설치 지원','desc'=>'VosCMS 설치 및 초기 설정을 대행합니다. 도메인 연결, SSL 설정, 기본 환경 구성 포함.','price'=>0,'unit'=>'','checked'=>true,'one_time'=>true,'type'=>'checkbox'],
    ['key'=>'support','label'=>'기술 지원 (1년)','desc'=>'이메일/채팅 기술 지원, 버그 수정, 보안 업데이트 적용, 장애 대응 (영업일 기준 24시간 이내 응답).','price'=>120000,'unit'=>'/년','type'=>'checkbox'],
    ['key'=>'custom','label'=>'커스터마이징 개발','desc'=>'맞춤 디자인, 전용 플러그인 개발, 외부 시스템 연동, 데이터 마이그레이션 등.','price'=>0,'unit'=>'별도 견적','type'=>'checkbox'],
    ['key'=>'bizmail','label'=>'비즈니스 메일','desc'=>'대용량 첨부파일 전송 (최대 10GB), 계정당 10GB 저장공간, 광고 없는 웹메일, 스팸 필터.','price'=>5000,'unit'=>'/계정/월','type'=>'checkbox'],
];
$_defMaintenance = [
    ['label'=>'Basic','price'=>10000,'desc'=>'보안 업데이트 적용, 월 1회 백업 확인'],
    ['label'=>'Standard','price'=>20000,'desc'=>'보안 업데이트, 플러그인/코어 업데이트, 주 1회 백업, 이메일 기술지원'],
    ['label'=>'Pro','price'=>30000,'desc'=>'Standard + 성능 모니터링, 장애 대응 (24h 이내), 일일 백업, 월 1회 리포트'],
    ['label'=>'Enterprise','price'=>50000,'desc'=>'Pro + 전담 매니저, 긴급 장애 대응 (4h 이내), 커스텀 기능 월 2건, 트래픽 분석','badge'=>'포털 · 쇼핑몰'],
];
$_addons = json_decode($serviceSettings['service_addons'] ?? '', true) ?: $_defAddons;
$_maintenance = json_decode($serviceSettings['service_maintenance'] ?? '', true) ?: $_defMaintenance;
?>
<div class="bg-white dark:bg-zinc-800 rounded-xl shadow-sm border dark:border-zinc-700 overflow-hidden mb-6">
    <div class="px-6 py-4 border-b border-zinc-100 dark:border-zinc-700">
        <h3 class="text-sm font-bold text-zinc-900 dark:text-white flex items-center gap-2">
            <svg class="w-4 h-4 text-rose-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/></svg>
            부가 서비스
        </h3>
    </div>
    <div class="p-6 space-y-6">
        <!-- 단일 서비스 -->
        <div>
            <h4 class="text-xs font-bold text-zinc-500 dark:text-zinc-400 uppercase tracking-wider mb-3">서비스 항목</h4>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="text-[11px] text-zinc-400 dark:text-zinc-500 border-b border-zinc-200 dark:border-zinc-600">
                            <th class="pb-2 pr-2 text-left">서비스명</th>
                            <th class="pb-2 px-2 text-left">설명</th>
                            <th class="pb-2 px-2 text-center w-28">가격 (<?= $_dispCur ?>)</th>
                            <th class="pb-2 px-2 text-center w-24">단위</th>
                            <th class="pb-2 px-2 text-center w-14">기본체크</th>
                            <th class="pb-2 px-2 text-center w-14">1회성</th>
                            <th class="pb-2 w-8"></th>
                        </tr>
                    </thead>
                    <tbody id="addonRows">
                        <?php foreach ($_addons as $addon): ?>
                        <tr class="border-b border-zinc-50 dark:border-zinc-700/50 addon-row">
                            <td class="py-1.5 pr-2"><input type="text" value="<?= htmlspecialchars($addon['label'] ?? '') ?>" class="addon-label <?= $_inp ?> text-xs"></td>
                            <td class="py-1.5 px-2"><input type="text" value="<?= htmlspecialchars($addon['desc'] ?? '') ?>" class="addon-desc <?= $_inp ?> text-xs"></td>
                            <td class="py-1.5 px-2"><input type="number" value="<?= (int)($addon['price'] ?? 0) ?>" class="addon-price <?= $_inp ?> text-xs text-center" min="0"></td>
                            <td class="py-1.5 px-2"><input type="text" value="<?= htmlspecialchars($addon['unit'] ?? '') ?>" class="addon-unit <?= $_inp ?> text-xs text-center" placeholder="/년, /월, 별도 견적"></td>
                            <td class="py-1.5 px-2 text-center"><input type="checkbox" class="addon-checked rounded text-blue-600" <?= !empty($addon['checked']) ? 'checked' : '' ?>></td>
                            <td class="py-1.5 px-2 text-center"><input type="checkbox" class="addon-onetime rounded text-amber-600" <?= !empty($addon['one_time']) ? 'checked' : '' ?>></td>
                            <td class="py-1.5 text-center"><button type="button" onclick="this.closest('tr').remove()" class="text-red-400 hover:text-red-600 p-0.5"><svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg></button></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <button type="button" onclick="addAddonRow()" class="mt-2 text-xs text-blue-600 dark:text-blue-400 hover:underline">+ 서비스 항목 추가</button>
        </div>

        <!-- 유지보수 등급 -->
        <div>
            <h4 class="text-xs font-bold text-zinc-500 dark:text-zinc-400 uppercase tracking-wider mb-3">정기 유지보수 등급</h4>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="text-[11px] text-zinc-400 dark:text-zinc-500 border-b border-zinc-200 dark:border-zinc-600">
                            <th class="pb-2 pr-2 text-left w-28">등급명</th>
                            <th class="pb-2 px-2 text-center w-28">월 가격 (<?= $_dispCur ?>)</th>
                            <th class="pb-2 px-2 text-left">설명</th>
                            <th class="pb-2 px-2 text-left w-24">뱃지</th>
                            <th class="pb-2 w-8"></th>
                        </tr>
                    </thead>
                    <tbody id="maintRows">
                        <?php foreach ($_maintenance as $mt): ?>
                        <tr class="border-b border-zinc-50 dark:border-zinc-700/50 maint-row">
                            <td class="py-1.5 pr-2"><input type="text" value="<?= htmlspecialchars($mt['label'] ?? '') ?>" class="maint-label <?= $_inp ?> text-xs"></td>
                            <td class="py-1.5 px-2"><input type="number" value="<?= (int)($mt['price'] ?? 0) ?>" class="maint-price <?= $_inp ?> text-xs text-center" min="0"></td>
                            <td class="py-1.5 px-2"><input type="text" value="<?= htmlspecialchars($mt['desc'] ?? '') ?>" class="maint-desc <?= $_inp ?> text-xs"></td>
                            <td class="py-1.5 px-2"><input type="text" value="<?= htmlspecialchars($mt['badge'] ?? '') ?>" class="maint-badge <?= $_inp ?> text-xs" placeholder="선택"></td>
                            <td class="py-1.5 text-center"><button type="button" onclick="this.closest('tr').remove()" class="text-red-400 hover:text-red-600 p-0.5"><svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg></button></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <button type="button" onclick="addMaintRow()" class="mt-2 text-xs text-blue-600 dark:text-blue-400 hover:underline">+ 유지보수 등급 추가</button>
        </div>
    </div>
</div>

