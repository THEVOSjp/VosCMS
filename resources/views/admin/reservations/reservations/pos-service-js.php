<script>
/**
 * POS 서비스 상세 모달 JS (pos-js.php에서 분리)
 * POS 객체에 서비스 관련 메서드 확장
 */
Object.assign(POS, {
    // ─── 이미지 URL 헬퍼 ───
    _resolveImgUrl(path) {
        if (!path) return '';
        if (path.startsWith('http')) return path;
        // adminUrl에서 /theadmin 또는 /admin 등 마지막 경로 세그먼트 제거
        const appUrl = this.adminUrl.replace(/\/[^/]+$/, '');
        return path.startsWith('/') ? appUrl + path : appUrl + '/' + path;
    },

    // ─── 서비스 상세 모달 ───
    _svcCustomer: null,

    async showServices(r) {
        console.log('[POS] Show services for:', r.customer_name, r.customer_phone);
        this._svcCustomer = { name: r.customer_name, phone: r.customer_phone, email: r.customer_email || '', date: r.reservation_date, source: r.source || 'walk_in', user_id: r.user_id || '', reservation_ids: r.reservation_ids || [] };

        // 헤더: 고객 프로필 (왼쪽) + 스태프 (오른쪽, API 후 갱신)
        document.getElementById('posServiceTitle').innerHTML = '<?= __('reservations.pos_service_detail') ?>' + (r.reservation_number ? ' <span class="text-xs font-mono font-normal text-zinc-400 ml-2">' + this.escHtml(r.reservation_number) + '</span>' : '');
        document.getElementById('posServiceCustomer').innerHTML = `
            <div class="px-5 py-3 border-b border-zinc-100 dark:border-zinc-700">
                <div class="flex items-center justify-between">
                    <!-- 왼쪽: 고객 프로필 -->
                    <div class="flex items-center gap-3 flex-1 min-w-0">
                        <div id="posProfileImg" class="w-20 h-20 rounded-full bg-zinc-200 dark:bg-zinc-700 flex items-center justify-center flex-shrink-0 border-2 border-zinc-300 dark:border-zinc-600">
                            <svg class="w-8 h-8 text-zinc-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                        </div>
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center gap-2">
                                <span class="text-base font-bold text-white drop-shadow-sm">${this.escHtml(r.customer_name)}</span>
                                <span id="posCustBadges" class="flex items-center gap-1"></span>
                            </div>
                            <div class="flex items-center gap-3 text-xs text-white/70 mt-1">
                                <span class="font-mono">${fmtPhone(r.customer_phone)}</span>
                                <span>${this.escHtml(r.reservation_date)}</span>
                            </div>
                            <div id="posCustStats" class="text-xs text-white/70 mt-0.5"></div>
                        </div>
                    </div>
                    <!-- 오른쪽: 배정/지명 스태프 (API 후 갱신) -->
                    <div id="posStaffHeader" class="flex-shrink-0 ml-4 hidden">
                    </div>
                </div>
            </div>`;

        document.getElementById('posServiceList').innerHTML = '<div class="text-center py-6 text-zinc-400"><svg class="w-5 h-5 animate-spin mx-auto mb-1" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg></div>';
        document.getElementById('posServiceTotal').innerHTML = '';
        document.getElementById('posCustomerDetail').innerHTML = '';
        document.getElementById('posAddServiceArea').classList.add('hidden');
        document.getElementById('posAssignStaffArea').classList.add('hidden');
        document.getElementById('posMemoArea').classList.add('hidden');
        document.getElementById('posServiceModal').classList.remove('hidden');

        try {
            const ids = (r.reservation_ids || []).join(',');
            const resp = await fetch(`${this.adminUrl}/reservations/customer-services?ids=${encodeURIComponent(ids)}&_t=${Date.now()}`);
            const data = await resp.json();
            console.log('[POS] Customer services:', data);
            if (data.success) {
                // 대표 서비스 이미지 배경 적용
                const heroImg = (data.data || []).find(s => s.service_image)?.service_image;
                const headerEl = document.getElementById('posServiceCustomer');
                const heroEl = document.getElementById('posServiceHero');
                const overlayEl = document.getElementById('posServiceHeroOverlay');
                const showModalImg = typeof posConfig !== 'undefined' ? posConfig.showModalImage : true;
                const modalOpacity = typeof posConfig !== 'undefined' ? posConfig.modalImageOpacity : 50;
                if (heroImg && showModalImg) {
                    const baseUrl = '<?= $config['app_url'] ?? '' ?>';
                    const path = heroImg.startsWith('storage/') ? heroImg : 'storage/' + heroImg;
                    const imgUrl = heroImg.startsWith('http') ? heroImg : (baseUrl + '/' + path);
                    heroEl.style.backgroundImage = `url('${imgUrl}')`;
                    const isDark = document.documentElement.classList.contains('dark');
                    const overlayOp = 1 - (modalOpacity / 100);
                    const opHigh = Math.min(0.95, overlayOp + 0.1);
                    overlayEl.style.background = isDark
                        ? `linear-gradient(to bottom, rgba(39,39,42,${opHigh}), rgba(39,39,42,${overlayOp + 0.15}))`
                        : `linear-gradient(to bottom, rgba(255,255,255,${opHigh}), rgba(255,255,255,${overlayOp + 0.15}))`;
                    overlayEl.style.backdropFilter = 'blur(2px)';
                } else {
                    heroEl.style.backgroundImage = '';
                    overlayEl.style.background = '';
                    overlayEl.style.backdropFilter = '';
                }
                this.renderCustomerProfile(data.customer || {}, data.memos || []);
                this.renderStaffHeader(data.data, data.customer || {});
                this._bundleData = data.bundle || null;
                this.renderServiceList(data.data);
            } else {
                document.getElementById('posServiceList').innerHTML = '<p class="text-center text-red-500 text-sm py-4">' + (data.message || 'Error') + '</p>';
            }
        } catch (err) {
            console.error('[POS] Fetch services error:', err);
            document.getElementById('posServiceList').innerHTML = '<p class="text-center text-red-500 text-sm py-4">오류 발생</p>';
        }
    },

    renderStaffHeader(services, customer) {
        const el = document.getElementById('posStaffHeader');
        if (!services || services.length === 0) { el.classList.add('hidden'); return; }

        // 첫 번째 서비스의 스태프 정보 사용
        const s = services[0];
        if (!s.staff_id || !s.staff_name) { el.classList.add('hidden'); return; }

        const isDesignation = (customer.designation_fee || 0) > 0;
        const typeBadge = isDesignation
            ? '<span class="px-1.5 py-0.5 text-[10px] font-medium rounded-full bg-violet-100 text-violet-700 dark:bg-violet-900/30 dark:text-violet-400"><?= __('reservations.pos_designation') ?></span>'
            : '<span class="px-1.5 py-0.5 text-[10px] font-medium rounded-full bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400"><?= __('reservations.pos_assignment') ?></span>';

        let avatarHtml = `<svg class="w-8 h-8 text-zinc-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>`;
        if (s.staff_avatar) {
            avatarHtml = `<img src="${this.escHtml(this._resolveImgUrl(s.staff_avatar))}" class="w-16 h-16 rounded-full object-cover">`;
        }

        const borderColor = isDesignation ? 'border-violet-300 dark:border-violet-600' : 'border-emerald-300 dark:border-emerald-600';
        const changeBtn = !isDesignation
            ? `<button type="button" onclick="POS.toggleAssignStaff()" class="mt-1.5 px-2 py-1 text-[10px] font-medium text-emerald-600 hover:bg-emerald-100 dark:hover:bg-emerald-900/20 rounded border border-emerald-300 dark:border-emerald-700 transition">변경</button>`
            : '';

        el.innerHTML = `
            <div class="flex flex-col items-center text-center">
                <div class="w-16 h-16 rounded-full bg-zinc-200 dark:bg-zinc-700 flex items-center justify-center border-2 ${borderColor} mb-1.5">
                    ${avatarHtml}
                </div>
                <span class="text-sm font-semibold text-zinc-900 dark:text-white">${this.escHtml(s.staff_name)}</span>
                <div class="mt-1">${typeBadge}</div>
                ${isDesignation ? `<span class="text-[10px] text-violet-500 mt-0.5">${this.fmtCurrency(customer.designation_fee)}</span>` : ''}
                ${changeBtn}
            </div>`;
        el.classList.remove('hidden');
        console.log('[POS] Staff header rendered:', s.staff_name, isDesignation ? 'designation' : 'assignment');
    },

    renderCustomerProfile(c, memos) {
        const genderLabel = { male: '<?= __('reservations.show_customer_gender_male') ?>', female: '<?= __('reservations.show_customer_gender_female') ?>', other: '<?= __('reservations.show_customer_gender_other') ?>' };

        // 프로필 이미지 업데이트 (w-20 h-20)
        if (c.profile_image) {
            document.getElementById('posProfileImg').innerHTML = `<img src="${this.escHtml(this._resolveImgUrl(c.profile_image))}" class="w-20 h-20 rounded-full object-cover">`;
        }

        // 뱃지: 회원/비회원, 등급
        let badges = '';
        if (c.is_member) {
            badges += `<span class="px-1.5 py-0.5 text-[10px] font-medium rounded-full bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400"><?= __('reservations.show_customer_member') ?></span>`;
            if (c.grade_name) {
                badges += `<span class="px-1.5 py-0.5 rounded-full text-[10px] font-semibold text-white" style="background:${c.grade_color || '#6B7280'}">${this.escHtml(c.grade_name)}</span>`;
            }
        } else {
            badges += `<span class="px-1.5 py-0.5 text-[10px] font-medium rounded-full bg-zinc-100 text-zinc-600 dark:bg-zinc-700 dark:text-zinc-400"><?= __('reservations.show_customer_guest') ?></span>`;
        }
        document.getElementById('posCustBadges').innerHTML = badges;

        // 통계: 방문횟수
        let stats = '';
        if (c.is_member) {
            stats = `<?= __('reservations.pos_visit') ?? '방문' ?> <b class="text-zinc-700 dark:text-zinc-300">${c.visit_completed}</b><?= __('reservations.pos_visit_count') ?? '회' ?>`;
            if (c.visit_no_show > 0) stats += ` · <span class="text-red-400"><?= __('reservations.pos_noshow') ?? '노쇼' ?> ${c.visit_no_show}</span>`;
        }
        document.getElementById('posCustStats').innerHTML = stats;

        // 오른쪽 패널: 고객 상세 정보
        let detailHtml = '';

        // 고객 정보 테이블
        if (c.is_member) {
            let age = '';
            <?php
            $_posAgeLabel = ['ko'=>'세','en'=>'y/o','ja'=>'歳','zh_CN'=>'岁','zh_TW'=>'歲','de'=>'J.','es'=>'años','fr'=>'ans','id'=>'thn','mn'=>'нас','ru'=>'лет','tr'=>'yaş','vi'=>'tuổi'];
            $_posAgeUnit = $_posAgeLabel[$config['locale'] ?? 'ko'] ?? 'y/o';
            $_posCustLabels = [
                'info' => __('reservations.show_customer_info'),
                'age' => __('reservations.show_customer_birth'),
                'gender' => __('reservations.show_customer_gender'),
                'discount' => __('reservations.show_member_discount'),
                'joined' => __('reservations.show_customer_joined'),
            ];
            ?>
            if (c.birth_date) age = (new Date().getFullYear() - parseInt(c.birth_date.substring(0, 4))) + '<?= $_posAgeUnit ?>';
            detailHtml += `<div>
                <p class="text-[10px] font-semibold text-zinc-500 dark:text-zinc-400 uppercase tracking-wider mb-1.5"><?= $_posCustLabels['info'] ?></p>
                <div class="space-y-1 text-xs">
                    ${age ? `<div class="flex justify-between"><span class="text-zinc-500"><?= $_posCustLabels['age'] ?></span><span class="text-zinc-900 dark:text-white">${age}</span></div>` : ''}
                    ${c.gender ? `<div class="flex justify-between"><span class="text-zinc-500"><?= $_posCustLabels['gender'] ?></span><span class="text-zinc-900 dark:text-white">${genderLabel[c.gender] || ''}</span></div>` : ''}
                    ${c.discount_rate > 0 ? `<div class="flex justify-between"><span class="text-zinc-500"><?= $_posCustLabels['discount'] ?></span><span class="text-red-500">${c.discount_rate}%</span></div>` : ''}
                    ${c.points_balance > 0 ? `<div class="flex justify-between"><span class="text-zinc-500"><?= get_points_name() ?></span><span class="text-emerald-600">${this.fmtCurrency(c.points_balance)}</span></div>` : ''}
                    ${c.member_since ? `<div class="flex justify-between"><span class="text-zinc-500"><?= $_posCustLabels['joined'] ?></span><span class="text-zinc-700 dark:text-zinc-300">${c.member_since.substring(0,10)}</span></div>` : ''}
                </div>
            </div>`;
        }

        // 고객 요구사항
        if (c.notes) {
            detailHtml += `<div class="p-2.5 bg-amber-50 dark:bg-amber-900/10 rounded-lg border border-amber-200 dark:border-amber-800/30">
                <p class="text-[10px] font-semibold text-amber-600 dark:text-amber-400 mb-0.5 flex items-center gap-1">
                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z"/></svg>
                    <?= __('reservations.show_customer_request') ?? '고객 요구사항' ?></p>
                <p class="text-xs text-zinc-700 dark:text-zinc-300 whitespace-pre-wrap">${this.escHtml(c.notes)}</p>
            </div>`;
        }

        // 관리자 메모 (admin_notes)
        if (c.admin_notes) {
            detailHtml += `<div class="p-2.5 bg-zinc-50 dark:bg-zinc-900 rounded-lg border border-zinc-200 dark:border-zinc-700">
                <p class="text-[10px] font-semibold text-zinc-500 dark:text-zinc-400 mb-0.5"><?= __('reservations.show_admin_notes') ?? '관리자 메모' ?></p>
                <p class="text-xs text-zinc-700 dark:text-zinc-300 whitespace-pre-wrap">${this.escHtml(c.admin_notes)}</p>
            </div>`;
        }

        document.getElementById('posCustomerDetail').innerHTML = detailHtml;

        // 메모 영역 (회원만)
        if (c.is_member) {
            document.getElementById('posMemoArea').classList.remove('hidden');
            const memoHtml = memos.length > 0
                ? memos.map(m => `<div class="border-l-2 border-zinc-300 dark:border-zinc-600 pl-2 py-0.5">
                    <p class="text-xs text-zinc-700 dark:text-zinc-300">${this.escHtml(m.content)}</p>
                    <p class="text-[10px] text-zinc-400">${(m.created_at || '').substring(0,16)} · ${this.escHtml(m.admin_name || '')}</p>
                </div>`).join('')
                : '<p class="text-xs text-zinc-400 text-center"><?= __('reservations.pos_no_memo') ?? '메모 없음' ?></p>';
            document.getElementById('posMemoList').innerHTML = memoHtml;
        }
    },

    renderServiceList(services) {
        this._serviceData = services;
        const statusCls = {
            pending: 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400',
            confirmed: 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400',
            completed: 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400',
        };
        const statusLabel = {
            pending: '<?= __('reservations.pos_waiting') ?>',
            confirmed: '<?= __('reservations.pos_in_service') ?>',
            completed: '<?= __('reservations.actions.complete') ?>',
        };

        let totalAmount = 0, totalDuration = 0;
        let totalDesignationFee = 0;
        const seenReservations = {};
        let totalPaid = 0;
        let totalFinalAmount = 0;

        // 번들/개별 서비스 분리
        const bundledSvcs = [], extraSvcs = [];
        let bundleInfo = null, bundleResId = null;
        services.forEach(s => {
            const price = parseFloat(s.price || 0);
            const dur = parseInt(s.service_duration || 0);
            totalAmount += price;
            totalDuration += dur;
            if (!seenReservations[s.reservation_id]) {
                seenReservations[s.reservation_id] = true;
                totalPaid += parseFloat(s.reservation_paid || 0);
                totalDesignationFee += parseFloat(s.designation_fee || 0);
                totalFinalAmount += parseFloat(s.final_amount || 0);
            }
            if (s.bundle_id) {
                bundledSvcs.push(s);
                if (!bundleInfo && s.bundle_price) { bundleInfo = { price: parseFloat(s.bundle_price), resId: s.reservation_id }; bundleResId = s.reservation_id; }
            } else {
                extraSvcs.push(s);
            }
        });

        const bundledTotal = bundledSvcs.reduce((a, s) => a + parseFloat(s.price || 0), 0);

        const renderSvc = (s, showDelete) => {
            const price = parseFloat(s.price || 0);
            const dur = parseInt(s.service_duration || 0);
            const badge = statusCls[s.status] || 'bg-zinc-100 text-zinc-700';
            const label = statusLabel[s.status] || s.status;
            const startT = (s.start_time || '').substring(0, 5);
            const endT = (s.end_time || '').substring(0, 5);
            return `<div class="flex items-center justify-between p-3 bg-zinc-50 dark:bg-zinc-900 rounded-lg">
                <div class="flex-1 min-w-0">
                    <div class="flex items-center gap-2 mb-1">
                        <span class="text-sm font-semibold text-zinc-900 dark:text-white truncate">${this.escHtml(s.service_name || '-')}</span>
                        <span class="px-1.5 py-0.5 rounded text-[10px] font-medium flex-shrink-0 ${badge}">${label}</span>
                        ${s.bundle_id ? '<span class="px-1.5 py-0.5 rounded text-[10px] font-medium bg-purple-100 text-purple-700 dark:bg-purple-900/30 dark:text-purple-400"><?= htmlspecialchars($_bdnLabel ?? '') ?></span>' : ''}
                    </div>
                    <div class="text-xs text-zinc-500">${startT}${endT ? ' ~ ' + endT : ''} · ${dur}<?= __('reservations.pos_min') ?>${s.staff_name ? ' · <span class="text-violet-600 dark:text-violet-400">' + this.escHtml(s.staff_name) + '</span>' : ''}</div>
                </div>
                <div class="flex items-center gap-2 flex-shrink-0 ml-2">
                    <span class="text-sm font-bold text-zinc-900 dark:text-white">${this.fmtCurrency(price)}</span>
                    ${showDelete ? `<button type="button" onclick="POS.removeService('${this.escHtml(s.reservation_id)}','${this.escHtml(s.service_id)}')"
                        class="p-1 text-red-400 hover:text-red-600 hover:bg-red-50 dark:hover:bg-red-900/20 rounded transition">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                    </button>` : ''}
                </div>
            </div>`;
        };

        let listHtml = '';

        // 번들 상품 (API의 bundle 데이터 사용)
        const bundle = this._bundleData;
        if (bundle && bundle.items && bundle.items.length > 0) {
            listHtml += `<div class="mb-2 p-3 bg-amber-50 dark:bg-amber-900/10 rounded-lg border border-amber-200 dark:border-amber-800/30">
                <div class="flex items-center justify-between mb-2">
                    <div>
                        <p class="text-xs font-medium text-amber-600 dark:text-amber-400"><?= htmlspecialchars($_bdnLabel ?? '') ?></p>
                        <p class="text-sm font-bold text-zinc-900 dark:text-white">${this.escHtml(bundle.name)}</p>
                        <p class="text-xs text-zinc-500">${bundle.items.length}<?= __('booking.service_count') ?> · ${this.fmtCurrency(bundle.price)}</p>
                    </div>
                    <button type="button" onclick="POS.removeBundle('${bundleResId || ''}')"
                        class="p-1.5 text-red-400 hover:text-red-600 hover:bg-red-50 dark:hover:bg-red-900/20 rounded-lg transition shrink-0">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                    </button>
                </div>
                <div class="space-y-1">` +
                bundle.items.map(bi => {
                    return `<div class="flex items-center justify-between py-1.5 px-2 bg-white/60 dark:bg-zinc-800/60 rounded">
                        <span class="text-xs text-zinc-700 dark:text-zinc-300 truncate">${this.escHtml(bi.name || '-')}</span>
                        <span class="text-xs text-zinc-500 shrink-0 ml-2">${bi.duration || 0}<?= __('reservations.pos_min') ?> · ${this.fmtCurrency(parseFloat(bi.price || 0))}</span>
                    </div>`;
                }).join('') +
            `</div></div>`;
        }

        // 이용 서비스 목록 (번들 서비스는 삭제 불가, 추가 서비스는 삭제 가능)
        const allSvcsHtml = services.map(s => {
            const isBundled = !!s.bundle_id;
            return renderSvc(s, !isBundled); // 번들이면 삭제 없음, 아니면 삭제 있음
        }).join('');
        if (allSvcsHtml) listHtml += allSvcsHtml;

        // 타이틀에 예약번호 업데이트
        if (services.length > 0 && services[0].reservation_number) {
            document.getElementById('posServiceTitle').innerHTML = '<?= __('reservations.pos_service_detail') ?> <span class="text-xs font-mono font-normal text-zinc-400 ml-2">' + this.escHtml(services[0].reservation_number) + '</span>';
        }

        document.getElementById('posServiceList').innerHTML = listHtml || '<p class="text-center text-zinc-400 text-sm py-4"><?= __('reservations.pos_no_services') ?></p>';

        // 최종 금액: DB의 final_amount 합계 사용 (번들/할인/적립금 반영된 정확한 값)
        const finalTotal = totalFinalAmount > 0 ? totalFinalAmount : (totalAmount + totalDesignationFee);
        const remaining = Math.max(0, finalTotal - totalPaid);

        const extraTotal = extraSvcs.reduce((a, s) => a + parseFloat(s.price || 0), 0);

        let totalHtml = '';

        // 소계 (서비스 합계)
        totalHtml += `<div class="flex items-center justify-between py-2 text-sm">
            <span class="text-zinc-500"><?= __('reservations.pos_pay_total') ?> (${services.length}<?= __('reservations.pos_service_count') ?>) · ${totalDuration}<?= __('reservations.pos_min') ?></span>
            <span class="font-bold text-zinc-900 dark:text-white">${this.fmtCurrency(totalAmount)}</span>
        </div>`;

        // 번들 적용가
        if (bundleInfo && bundleInfo.price > 0) {
            totalHtml += `<div class="flex items-center justify-between pb-1 text-sm font-semibold text-green-600 dark:text-green-400">
                <span><?= htmlspecialchars($_bdnLabel ?? '') ?> <?= __('booking.payment.applied_price') ?? '적용가' ?></span>
                <span>${this.fmtCurrency(bundleInfo.price)}</span>
            </div>`;
        }

        // 추가 서비스 (번들 외)
        if (extraTotal > 0 && bundleInfo) {
            totalHtml += `<div class="flex items-center justify-between pb-1 text-sm">
                <span class="text-zinc-500"><?= __('reservations.show_add_service') ?? '추가 서비스' ?></span>
                <span class="text-zinc-900 dark:text-white">+${this.fmtCurrency(extraTotal)}</span>
            </div>`;
        }

        // 지명료
        if (totalDesignationFee > 0) {
            totalHtml += `<div class="flex items-center justify-between pb-1 text-sm">
                <span class="text-amber-600 dark:text-amber-400"><?= __('reservations.pos_pay_designation') ?></span>
                <span class="text-amber-600 dark:text-amber-400">+${this.fmtCurrency(totalDesignationFee)}</span>
            </div>`;
        }

        // 최종 결제 금액
        totalHtml += `<div class="flex items-center justify-between py-2 border-t border-zinc-200 dark:border-zinc-700 text-sm font-semibold">
            <span class="text-zinc-900 dark:text-white"><?= __('reservations.show_final_amount') ?></span>
            <span class="text-zinc-900 dark:text-white">${this.fmtCurrency(finalTotal)}</span>
        </div>`;

        // 결제 완료
        if (totalPaid > 0) {
            totalHtml += `<div class="flex items-center justify-between pb-1 text-sm">
                <span class="text-emerald-600"><?= __('reservations.pos_pay_paid') ?></span>
                <span class="text-emerald-600">-${this.fmtCurrency(totalPaid)}</span>
            </div>`;
        }

        // 잔액
        if (remaining > 0) {
            totalHtml += `<div class="flex items-center justify-between pt-1 text-sm font-bold">
                <span class="text-red-600"><?= __('reservations.pos_pay_remaining') ?></span>
                <span class="text-red-600">${this.fmtCurrency(remaining)}</span>
            </div>`;
        } else if (totalPaid > 0) {
            totalHtml += `<div class="flex items-center justify-between pt-1 text-sm font-bold">
                <span class="text-emerald-600"><?= __('reservations.pos_pay_paid') ?></span>
                <span class="text-emerald-600">${this.fmtCurrency(totalPaid)}</span>
            </div>`;
        }

        document.getElementById('posServiceTotal').innerHTML = totalHtml;

        this._existingServiceIds = services.map(s => String(s.service_id));
        this._currentStaffId = services.length > 0 ? String(services[0].staff_id || '') : '';
    },

    _existingServiceIds: [],
    _currentStaffId: '',

    toggleAddService() {
        const area = document.getElementById('posAddServiceArea');
        const isHidden = area.classList.contains('hidden');
        if (isHidden) {
            area.classList.remove('hidden');
            document.getElementById('posAssignStaffArea').classList.add('hidden');

            // 번들 목록
            let bundleHtml = '';
            if (typeof posAllBundles !== 'undefined' && posAllBundles.length > 0) {
                bundleHtml = '<div class="mb-3 pb-3 border-b border-zinc-200 dark:border-zinc-700">'
                    + '<p class="text-xs font-semibold text-amber-700 dark:text-amber-400 mb-2"><?= __('bundles.recommended') ?> <?= htmlspecialchars($_bdnLabel ?? '') ?></p>'
                    + posAllBundles.map(b => {
                        const svcIds = (b.service_ids || []).join(',');
                        return `<label class="flex items-center p-2.5 rounded-lg border-2 border-amber-200 dark:border-amber-700 cursor-pointer hover:bg-amber-50 dark:hover:bg-amber-900/10 mb-1.5">
                            <input type="checkbox" value="${b.id}" data-bundle="1" data-services="${svcIds}" class="pos-add-bundle-check mr-3 rounded text-amber-600" onchange="POS.onAddBundleCheck(this)">
                            <div class="flex-1">
                                <p class="text-sm font-semibold text-zinc-900 dark:text-white">${this.escHtml(b.name)}</p>
                                <p class="text-xs text-zinc-500">${b.service_count || 0}<?= __('booking.service_count') ?></p>
                            </div>
                            <span class="text-sm font-bold text-amber-600">${this.fmtCurrency(b.bundle_price)}</span>
                        </label>`;
                    }).join('')
                    + '</div>';
            }

            // 개별 서비스 목록
            const svcHtml = posAllServices.map(s => {
                const already = this._existingServiceIds.includes(String(s.id));
                return `<label class="flex items-center p-2.5 rounded-lg border border-zinc-200 dark:border-zinc-700 ${already ? 'opacity-40' : 'cursor-pointer hover:bg-zinc-50 dark:hover:bg-zinc-700/50'}">
                    <input type="checkbox" value="${s.id}" class="pos-add-svc-check mr-3 rounded text-blue-600" ${already ? 'disabled' : ''} onchange="POS.onAddServiceCheck()">
                    <div class="flex-1">
                        <p class="text-sm font-medium text-zinc-900 dark:text-white">${this.escHtml(s.name)}</p>
                        <p class="text-xs text-zinc-500">${s.duration}<?= __('reservations.pos_min') ?></p>
                    </div>
                    <span class="text-sm font-bold text-zinc-700 dark:text-zinc-300">${this.fmtCurrency(s.price)}</span>
                </label>`;
            }).join('');
            document.getElementById('posAddServiceList').innerHTML = bundleHtml + svcHtml;
            document.getElementById('posAddServiceBtn').disabled = true;
        } else {
            area.classList.add('hidden');
        }
    },

    onAddServiceCheck() {
        const svcChecked = document.querySelectorAll('.pos-add-svc-check:checked');
        const bdlChecked = document.querySelectorAll('.pos-add-bundle-check:checked');
        document.getElementById('posAddServiceBtn').disabled = svcChecked.length === 0 && bdlChecked.length === 0;
        console.log('[POS] Add service checked:', svcChecked.length, 'bundles:', bdlChecked.length);
    },

    onAddBundleCheck(cb) {
        const svcIds = (cb.dataset.services || '').split(',').filter(Boolean);
        console.log('[POS] Bundle toggled, services:', svcIds, 'checked:', cb.checked);
        svcIds.forEach(function(id) {
            const svcCb = document.querySelector('.pos-add-svc-check[value="' + id + '"]');
            if (svcCb && !svcCb.disabled) svcCb.checked = cb.checked;
        });
        this.onAddServiceCheck();
    },

    async submitAddService() {
        const svcChecked = document.querySelectorAll('.pos-add-svc-check:checked');
        const bdlChecked = document.querySelectorAll('.pos-add-bundle-check:checked');
        if (svcChecked.length === 0 && bdlChecked.length === 0) return;
        const btn = document.getElementById('posAddServiceBtn');
        btn.disabled = true;
        btn.textContent = '<?= __('admin.messages.processing') ?>';

        const c = this._svcCustomer;
        const body = new URLSearchParams();
        body.append('_token', this.csrfToken);
        body.append('customer_name', c.name);
        body.append('customer_phone', c.phone);
        body.append('customer_email', c.email);
        body.append('reservation_date', c.date);
        body.append('source', c.source);
        if (c.user_id) body.append('user_id', c.user_id);

        // 번들 선택 시: 번들 ID + 번들 포함 서비스
        let bundleId = '';
        if (bdlChecked.length > 0) {
            bundleId = bdlChecked[0].value;
            body.append('bundle_id', bundleId);
            const bdlSvcIds = (bdlChecked[0].dataset.services || '').split(',').filter(Boolean);
            bdlSvcIds.forEach(id => body.append('service_ids[]', id));
        }
        // 추가 개별 서비스 (번들에 포함 안 된 것만)
        svcChecked.forEach(cb => {
            if (!cb.disabled) body.append('service_ids[]', cb.value);
        });

        console.log('[POS] Adding services, bundle:', bundleId, 'svcs:', [...svcChecked].map(cb => cb.value));
        try {
            const resp = await fetch(`${this.adminUrl}/reservations/add-service`, { method: 'POST', body });
            const data = await resp.json();
            console.log('[POS] Add service result:', data);
            if (data.success) {
                // 새 예약 ID를 목록에 추가
                if (data.ids && data.ids.length > 0) {
                    const c = this._svcCustomer;
                    c.reservation_ids = [...(c.reservation_ids || []), ...data.ids];
                }
                // 서비스 추가 영역 닫기
                document.getElementById('posAddServiceArea').classList.add('hidden');
                // 서비스 목록 다시 로드
                const c = this._svcCustomer;
                const ids = (c.reservation_ids || []).join(',');
                const r2 = await fetch(`${this.adminUrl}/reservations/customer-services?ids=${encodeURIComponent(ids)}&_t=${Date.now()}`, { cache: 'no-store' });
                const d2 = await r2.json();
                console.log('[POS] Refreshed services after add:', d2);
                if (d2.success) {
                    this._bundleData = d2.bundle || null;
                    this.renderStaffHeader(d2.data, d2.customer || {});
                    this.renderServiceList(d2.data);
                }
                btn.disabled = false;
                btn.textContent = '<?= __('reservations.pos_add_service_submit') ?>';
            } else {
                alert(data.message || '서비스 추가 실패');
                btn.disabled = false;
                btn.textContent = '<?= __('reservations.pos_add_service_submit') ?>';
            }
        } catch (err) {
            console.error('[POS] Add service error:', err);
            alert('오류가 발생했습니다.');
            btn.disabled = false;
            btn.textContent = '<?= __('reservations.pos_add_service_submit') ?>';
        }
    },

    // ─── 스태프 배정 ───
    toggleAssignStaff() {
        const area = document.getElementById('posAssignStaffArea');
        const isHidden = area.classList.contains('hidden');
        if (isHidden) {
            area.classList.remove('hidden');
            document.getElementById('posAddServiceArea').classList.add('hidden');
            // 이미 목록이 있으면 재렌더링 안 함
            if (document.getElementById('posAssignStaffList').children.length > 0) {
                console.log('[POS] Staff list already rendered, skip');
                return;
            }
            const currentId = this._currentStaffId;
            const html = posAllStaff.map(s => {
                const isCurrent = String(s.id) === currentId;
                const fee = parseFloat(s.designation_fee || 0);
                return `<label class="flex items-center p-2.5 rounded-lg border ${isCurrent ? 'border-violet-400 bg-violet-50 dark:bg-violet-900/20' : 'border-zinc-200 dark:border-zinc-700'} cursor-pointer hover:bg-violet-50 dark:hover:bg-violet-900/10" onclick="event.stopPropagation()">
                    <input type="radio" name="pos_assign_staff" value="${s.id}" class="pos-staff-radio mr-3 text-violet-600" ${isCurrent ? 'checked' : ''} onchange="event.stopPropagation();POS.onStaffRadioChange()" onclick="event.stopPropagation()">
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-medium text-zinc-900 dark:text-white">${this.escHtml(s.name)}${isCurrent ? ' <span class="text-xs text-violet-500">(<?= __('reservations.pos_current_staff') ?>)</span>' : ''}</p>
                        ${fee > 0 ? '<p class="text-xs text-zinc-500"><?= __('reservations.pos_designation_fee') ?> ' + this.fmtCurrency(fee) + '</p>' : ''}
                    </div>
                </label>`;
            }).join('');
            document.getElementById('posAssignStaffList').innerHTML = html || '<p class="text-center text-zinc-400 text-sm py-4"><?= __('reservations.pos_no_staff') ?></p>';
            document.getElementById('posAssignStaffBtn').disabled = !currentId;
            console.log('[POS] Toggle assign staff, current:', currentId);
        } else {
            area.classList.add('hidden');
        }
    },

    onStaffRadioChange() {
        const selected = document.querySelector('.pos-staff-radio:checked');
        document.getElementById('posAssignStaffBtn').disabled = !selected;
        console.log('[POS] Staff radio changed:', selected?.value, 'btn disabled:', !selected);
    },

    async submitAssignStaff() {
        const selected = document.querySelector('.pos-staff-radio:checked');
        if (!selected) return;
        const btn = document.getElementById('posAssignStaffBtn');
        btn.disabled = true;
        btn.textContent = '<?= __('admin.messages.processing') ?>';

        const c = this._svcCustomer;
        const body = new URLSearchParams();
        body.append('_token', this.csrfToken);
        (c.reservation_ids || []).forEach(id => body.append('reservation_ids[]', id));
        body.append('staff_id', selected.value);

        console.log('[POS] Assigning staff:', selected.value, 'to reservations:', c.reservation_ids);
        try {
            const resp = await fetch(`${this.adminUrl}/reservations/assign-staff`, { method: 'POST', body });
            const data = await resp.json();
            console.log('[POS] Assign staff result:', data);
            if (data.success) {
                console.log('[POS] Staff assigned, refreshing services...');
                document.getElementById('posAssignStaffArea').classList.add('hidden');
                document.getElementById('posAssignStaffList').innerHTML = '';
                // 서비스 목록 새로 불러오기
                this.showServices(this._svcCustomer);
            } else {
                alert(data.message || '스태프 배정 실패');
                btn.disabled = false;
                btn.textContent = '<?= __('reservations.pos_assign_staff_submit') ?>';
            }
        } catch (err) {
            console.error('[POS] Assign staff error:', err);
            alert('오류가 발생했습니다.');
            btn.disabled = false;
            btn.textContent = '<?= __('reservations.pos_assign_staff_submit') ?>';
        }
    },

    // ─── 메모 저장 (POS 모달) ───
    async submitMemo() {
        const input = document.getElementById('posMemoInput');
        const content = (input.value || '').trim();
        if (!content) return;
        const c = this._svcCustomer;
        if (!c.user_id) { alert('<?= __('reservations.pos_memo_member_only') ?? '회원만 메모를 저장할 수 있습니다.' ?>'); return; }

        const rIds = c.reservation_ids || [];
        const body = new URLSearchParams();
        body.append('_token', this.csrfToken);
        body.append('user_id', c.user_id);
        body.append('content', content);
        if (rIds.length > 0) body.append('reservation_id', rIds[0]);

        console.log('[POS] Saving memo for user:', c.user_id);
        try {
            const resp = await fetch(`${this.adminUrl}/reservations/save-memo`, { method: 'POST', body });
            const data = await resp.json();
            console.log('[POS] Save memo result:', data);
            if (data.success) {
                input.value = '';
                const list = document.getElementById('posMemoList');
                const newHtml = `<div class="border-l-2 border-blue-400 pl-2 py-0.5">
                    <p class="text-xs text-zinc-700 dark:text-zinc-300">${this.escHtml(content)}</p>
                    <p class="text-[10px] text-zinc-400">방금 · ${this.escHtml(data.admin_name || 'Admin')}</p>
                </div>`;
                list.innerHTML = newHtml + list.innerHTML.replace('메모 없음', '');
            } else {
                alert(data.message || '메모 저장 실패');
            }
        } catch (err) {
            console.error('[POS] Save memo error:', err);
        }
    },

    // ─── 서비스 삭제 ───
    _serviceData: [],

    async removeBundle(reservationId) {
        if (!confirm('<?= __('reservations.show_remove_bundle_confirm') ?? '번들과 포함된 서비스를 모두 삭제하시겠습니까?' ?>')) return;
        console.log('[POS] Remove bundle for reservation:', reservationId);
        try {
            const body = new URLSearchParams();
            body.append('_token', '<?= $csrfToken ?? '' ?>');
            body.append('reservation_id', reservationId);
            const resp = await fetch(`${this.adminUrl}/reservations/remove-bundle`, { method: 'POST', body });
            const data = await resp.json();
            if (data.error) { alert(data.message || 'Error'); return; }
            // 서비스 목록 새로고침
            const ids = this._svcCustomer.reservation_ids.join(',');
            const r2 = await fetch(`${this.adminUrl}/reservations/customer-services?ids=${encodeURIComponent(ids)}&_t=${Date.now()}`, { cache: 'no-store' });
            const d2 = await r2.json();
            if (d2.success) {
                this._bundleData = d2.bundle || null;
                this.renderServiceList(d2.data || []);
                this.renderStaffHeader(d2.data || [], d2.customer || {});
            }
        } catch (err) { console.error('[POS] Remove bundle error:', err); alert('Error'); }
    },

    async removeService(reservationId, serviceId) {
        if (!confirm('<?= __('reservations.pos_remove_service_confirm') ?>')) return;
        console.log('[POS] Remove service:', serviceId, 'from reservation:', reservationId);

        const body = new URLSearchParams();
        body.append('_token', this.csrfToken);
        body.append('reservation_id', reservationId);
        body.append('service_id', serviceId);

        try {
            const resp = await fetch(`${this.adminUrl}/reservations/remove-service`, { method: 'POST', body });
            const data = await resp.json();
            console.log('[POS] Remove service result:', data);
            if (data.success) {
                // 클라이언트 데이터에서 해당 서비스 제거
                this._serviceData = this._serviceData.filter(s =>
                    !(String(s.reservation_id) === String(reservationId) && String(s.service_id) === String(serviceId))
                );
                console.log('[POS] Remaining services in client:', this._serviceData.length);

                if (this._serviceData.length === 0) {
                    location.reload();
                    return;
                }
                // DOM 직접 갱신
                this.renderServiceList(this._serviceData);
            } else {
                alert(data.message || '삭제 실패');
            }
        } catch (err) {
            console.error('[POS] Remove service error:', err);
            alert('오류가 발생했습니다: ' + err.message);
        }
    },

    closeServiceModal(e) {
        if (e && e.target !== e.currentTarget) return;
        document.getElementById('posServiceModal').classList.add('hidden');
    },
});
</script>
