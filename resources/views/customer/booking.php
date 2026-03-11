<?php
/**
 * RezlyX Booking Page
 */

require_once BASE_PATH . '/rzxlib/Core/Auth/Auth.php';
use RzxLib\Core\Auth\Auth;

$pageTitle = ($config['app_name'] ?? 'RezlyX') . ' - ' . __('common.nav.booking');
$baseUrl = $config['app_url'] ?? '';
$appName = $config['app_name'] ?? 'RezlyX';

// 로그인 상태 확인
$isLoggedIn = Auth::check();
$currentUser = $isLoggedIn ? Auth::user() : null;

// Load services from database
$services = [];
try {
    $pdo = new PDO(
        'mysql:host=' . ($_ENV['DB_HOST'] ?? 'localhost') . ';dbname=' . ($_ENV['DB_DATABASE'] ?? 'rezlyx'),
        $_ENV['DB_USERNAME'] ?? 'root',
        $_ENV['DB_PASSWORD'] ?? '',
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    $prefix = $_ENV['DB_PREFIX'] ?? 'rzx_';
    $stmt = $pdo->query("SELECT * FROM {$prefix}services WHERE is_active = 1 ORDER BY sort_order, name");
    $services = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // Services will be empty if DB connection fails
    if ($config['debug'] ?? false) {
        error_log('Booking page DB error: ' . $e->getMessage());
    }
}

// 통화·가격 표시 설정 (서비스 설정 > 기본설정)
$serviceCurrency = $siteSettings['service_currency'] ?? 'KRW';
$priceDisplay = $siteSettings['service_price_display'] ?? 'show';
$_currencySymbols = ['KRW' => '₩', 'USD' => '$', 'JPY' => '¥', 'EUR' => '€', 'CNY' => '¥'];
$currencySymbol = $_currencySymbols[$serviceCurrency] ?? $serviceCurrency;

// 헤더 포함
include BASE_PATH . '/resources/views/partials/header.php';
?>

    <!-- 예약 페이지 추가 스타일 -->
    <style>
        .step-active { background-color: #2563eb; color: white; }
        .step-completed { background-color: #22c55e; color: white; }
        .step-inactive { background-color: #e5e7eb; color: #6b7280; }
        .dark .step-inactive { background-color: #3f3f46; color: #a1a1aa; }
    </style>

    <!-- Main Content -->
    <main class="max-w-4xl mx-auto px-4 py-8">
        <!-- Page Title -->
        <div class="text-center mb-8">
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white mb-2"><?= __('common.nav.booking') ?></h1>
            <p class="text-gray-600 dark:text-zinc-400"><?= __('booking.select_service_datetime') ?></p>
        </div>

        <!-- Progress Steps -->
        <div class="flex items-center justify-center mb-8">
            <div class="flex items-center">
                <div id="step1Indicator" class="w-10 h-10 rounded-full flex items-center justify-center font-semibold step-active">1</div>
                <span class="ml-2 text-sm font-medium text-gray-900 dark:text-white hidden sm:inline"><?= __('booking.step.service') ?></span>
            </div>
            <div class="w-12 h-1 mx-2 bg-gray-200 dark:bg-zinc-700" id="connector1"></div>
            <div class="flex items-center">
                <div id="step2Indicator" class="w-10 h-10 rounded-full flex items-center justify-center font-semibold step-inactive">2</div>
                <span class="ml-2 text-sm font-medium text-gray-500 dark:text-zinc-400 hidden sm:inline"><?= __('booking.step.datetime') ?></span>
            </div>
            <div class="w-12 h-1 mx-2 bg-gray-200 dark:bg-zinc-700" id="connector2"></div>
            <div class="flex items-center">
                <div id="step3Indicator" class="w-10 h-10 rounded-full flex items-center justify-center font-semibold step-inactive">3</div>
                <span class="ml-2 text-sm font-medium text-gray-500 dark:text-zinc-400 hidden sm:inline"><?= __('booking.step.info') ?></span>
            </div>
            <div class="w-12 h-1 mx-2 bg-gray-200 dark:bg-zinc-700" id="connector3"></div>
            <div class="flex items-center">
                <div id="step4Indicator" class="w-10 h-10 rounded-full flex items-center justify-center font-semibold step-inactive">4</div>
                <span class="ml-2 text-sm font-medium text-gray-500 dark:text-zinc-400 hidden sm:inline"><?= __('booking.step.confirm') ?></span>
            </div>
        </div>

        <!-- Step 1: Service Selection -->
        <div id="step1" class="bg-white dark:bg-zinc-800 rounded-2xl shadow-lg p-6 md:p-8">
            <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-6"><?= __('booking.select_service') ?></h2>

            <?php if (empty($services)): ?>
            <div class="text-center py-12">
                <svg class="w-16 h-16 text-gray-300 dark:text-zinc-600 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
                </svg>
                <p class="text-gray-500 dark:text-zinc-400"><?= __('booking.no_services') ?></p>
                <p class="text-sm text-gray-400 dark:text-zinc-500 mt-2"><?= __('booking.contact_admin') ?></p>
            </div>
            <?php else: ?>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <?php foreach ($services as $service): ?>
                <label class="service-card cursor-pointer">
                    <input type="radio" name="service" value="<?php echo $service['id']; ?>" class="hidden"
                           data-name="<?php echo htmlspecialchars($service['name']); ?>"
                           data-price="<?php echo $service['price']; ?>"
                           data-duration="<?php echo $service['duration'] ?? 60; ?>">
                    <div class="border-2 border-gray-200 dark:border-zinc-700 rounded-xl p-4 hover:border-blue-500 dark:hover:border-blue-400 transition-all">
                        <div class="flex items-start justify-between">
                            <div>
                                <h3 class="font-semibold text-gray-900 dark:text-white"><?php echo htmlspecialchars($service['name']); ?></h3>
                                <p class="text-sm text-gray-500 dark:text-zinc-400 mt-1"><?php echo htmlspecialchars($service['description'] ?? ''); ?></p>
                            </div>
                            <div class="text-right">
                                <?php if ($priceDisplay === 'show'): ?>
                                <span class="text-lg font-bold text-blue-600 dark:text-blue-400"><?= $currencySymbol ?><?php echo number_format($service['price']); ?></span>
                                <?php elseif ($priceDisplay === 'contact'): ?>
                                <span class="text-sm text-gray-500 dark:text-zinc-400"><?= __('admin.services.settings.general.price_contact') ?></span>
                                <?php endif; ?>
                                <p class="text-xs text-gray-400"><?php echo $service['duration'] ?? 60; ?><?= __('common.minutes') ?></p>
                            </div>
                        </div>
                    </div>
                </label>
                <?php endforeach; ?>
            </div>
            <div class="flex justify-end mt-6">
                <button type="button" onclick="goToStep(2)" id="step1Next" class="px-6 py-3 bg-blue-600 text-white font-semibold rounded-lg hover:bg-blue-700 transition disabled:opacity-50 disabled:cursor-not-allowed" disabled>
                    <?= __('common.buttons.next') ?>
                    <svg class="w-4 h-4 inline ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                    </svg>
                </button>
            </div>
            <?php endif; ?>
        </div>

        <!-- Step 2: Date & Time Selection -->
        <div id="step2" class="bg-white dark:bg-zinc-800 rounded-2xl shadow-lg p-6 md:p-8 hidden">
            <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-6"><?= __('booking.select_datetime') ?></h2>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Date Selection -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-zinc-300 mb-2"><?= __('booking.select_date') ?></label>
                    <input type="date" id="bookingDate"
                           min="<?php echo date('Y-m-d'); ?>"
                           class="w-full px-4 py-3 border border-gray-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                </div>

                <!-- Time Selection -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-zinc-300 mb-2"><?= __('booking.select_time') ?></label>
                    <div id="timeSlots" class="grid grid-cols-3 gap-2 max-h-48 overflow-y-auto">
                        <!-- Time slots will be generated by JavaScript -->
                    </div>
                </div>
            </div>

            <div class="flex justify-between mt-6">
                <button type="button" onclick="goToStep(1)" class="px-6 py-3 border border-gray-300 dark:border-zinc-600 text-gray-700 dark:text-zinc-300 font-semibold rounded-lg hover:bg-gray-50 dark:hover:bg-zinc-700 transition">
                    <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                    </svg>
                    <?= __('common.buttons.previous') ?>
                </button>
                <button type="button" onclick="goToStep(3)" id="step2Next" class="px-6 py-3 bg-blue-600 text-white font-semibold rounded-lg hover:bg-blue-700 transition disabled:opacity-50 disabled:cursor-not-allowed" disabled>
                    <?= __('common.buttons.next') ?>
                    <svg class="w-4 h-4 inline ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                    </svg>
                </button>
            </div>
        </div>

        <!-- Step 3: Customer Information -->
        <div id="step3" class="bg-white dark:bg-zinc-800 rounded-2xl shadow-lg p-6 md:p-8 hidden">
            <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-6"><?= __('booking.enter_info') ?></h2>

            <form id="bookingForm" class="space-y-4">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label for="customerName" class="block text-sm font-medium text-gray-700 dark:text-zinc-300 mb-1"><?= __('auth.register.name') ?> <span class="text-red-500">*</span></label>
                        <input type="text" id="customerName" name="name" required
                               value="<?= $isLoggedIn ? htmlspecialchars($currentUser['name'] ?? '') : '' ?>"
                               class="w-full px-4 py-3 border border-gray-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                               placeholder="<?= __('auth.register.name_placeholder') ?>">
                    </div>
                    <div>
                        <label for="customerPhone" class="block text-sm font-medium text-gray-700 dark:text-zinc-300 mb-1"><?= __('auth.register.phone') ?> <span class="text-red-500">*</span></label>
                        <input type="tel" id="customerPhone" name="phone" required
                               value="<?= $isLoggedIn ? htmlspecialchars($currentUser['phone'] ?? '') : '' ?>"
                               class="w-full px-4 py-3 border border-gray-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                               placeholder="<?= __('auth.register.phone_placeholder') ?>">
                    </div>
                </div>
                <div>
                    <label for="customerEmail" class="block text-sm font-medium text-gray-700 dark:text-zinc-300 mb-1"><?= __('auth.register.email') ?></label>
                    <input type="email" id="customerEmail" name="email"
                           value="<?= $isLoggedIn ? htmlspecialchars($currentUser['email'] ?? '') : '' ?>"
                           class="w-full px-4 py-3 border border-gray-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                           placeholder="<?= __('auth.register.email_placeholder') ?>">
                </div>
                <div>
                    <label for="customerMemo" class="block text-sm font-medium text-gray-700 dark:text-zinc-300 mb-1"><?= __('booking.notes') ?></label>
                    <textarea id="customerMemo" name="memo" rows="3"
                              class="w-full px-4 py-3 border border-gray-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                              placeholder="<?= __('booking.notes_placeholder') ?>"></textarea>
                </div>
            </form>

            <div class="flex justify-between mt-6">
                <button type="button" onclick="goToStep(2)" class="px-6 py-3 border border-gray-300 dark:border-zinc-600 text-gray-700 dark:text-zinc-300 font-semibold rounded-lg hover:bg-gray-50 dark:hover:bg-zinc-700 transition">
                    <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                    </svg>
                    <?= __('common.buttons.previous') ?>
                </button>
                <button type="button" onclick="goToStep(4)" id="step3Next" class="px-6 py-3 bg-blue-600 text-white font-semibold rounded-lg hover:bg-blue-700 transition">
                    <?= __('booking.confirm_booking') ?>
                    <svg class="w-4 h-4 inline ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                    </svg>
                </button>
            </div>
        </div>

        <!-- Step 4: Confirmation -->
        <div id="step4" class="bg-white dark:bg-zinc-800 rounded-2xl shadow-lg p-6 md:p-8 hidden">
            <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-6"><?= __('booking.confirm_info') ?></h2>

            <div class="bg-gray-50 dark:bg-zinc-700/50 rounded-xl p-6 space-y-4">
                <div class="flex justify-between items-center pb-4 border-b dark:border-zinc-600">
                    <span class="text-gray-600 dark:text-zinc-400"><?= __('booking.service.title') ?></span>
                    <span id="confirmService" class="font-semibold text-gray-900 dark:text-white">-</span>
                </div>
                <div class="flex justify-between items-center pb-4 border-b dark:border-zinc-600">
                    <span class="text-gray-600 dark:text-zinc-400"><?= __('booking.date') ?></span>
                    <span id="confirmDate" class="font-semibold text-gray-900 dark:text-white">-</span>
                </div>
                <div class="flex justify-between items-center pb-4 border-b dark:border-zinc-600">
                    <span class="text-gray-600 dark:text-zinc-400"><?= __('booking.time') ?></span>
                    <span id="confirmTime" class="font-semibold text-gray-900 dark:text-white">-</span>
                </div>
                <div class="flex justify-between items-center pb-4 border-b dark:border-zinc-600">
                    <span class="text-gray-600 dark:text-zinc-400"><?= __('booking.customer') ?></span>
                    <span id="confirmName" class="font-semibold text-gray-900 dark:text-white">-</span>
                </div>
                <div class="flex justify-between items-center pb-4 border-b dark:border-zinc-600">
                    <span class="text-gray-600 dark:text-zinc-400"><?= __('booking.phone') ?></span>
                    <span id="confirmPhone" class="font-semibold text-gray-900 dark:text-white">-</span>
                </div>
                <div class="flex justify-between items-center pt-2">
                    <span class="text-lg font-semibold text-gray-900 dark:text-white"><?= __('booking.total_price') ?></span>
                    <span id="confirmPrice" class="text-2xl font-bold text-blue-600 dark:text-blue-400">-</span>
                </div>
            </div>

            <div class="mt-6 p-4 bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800 rounded-lg">
                <div class="flex items-start">
                    <svg class="w-5 h-5 text-amber-600 dark:text-amber-400 mt-0.5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                    </svg>
                    <p class="text-sm text-amber-700 dark:text-amber-300">
                        <?= __('booking.cancel_policy') ?>
                    </p>
                </div>
            </div>

            <div class="flex justify-between mt-6">
                <button type="button" onclick="goToStep(3)" class="px-6 py-3 border border-gray-300 dark:border-zinc-600 text-gray-700 dark:text-zinc-300 font-semibold rounded-lg hover:bg-gray-50 dark:hover:bg-zinc-700 transition">
                    <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                    </svg>
                    <?= __('common.buttons.previous') ?>
                </button>
                <button type="button" onclick="submitBooking()" id="submitBtn" class="px-8 py-3 bg-blue-600 text-white font-semibold rounded-lg hover:bg-blue-700 transition shadow-lg shadow-blue-500/30">
                    <?= __('booking.complete_booking') ?>
                </button>
            </div>
        </div>
    </main>

    <!-- 예약 페이지 전용 스크립트 -->
    <script>
        // Booking state
        const bookingData = {
            serviceId: null,
            serviceName: '',
            servicePrice: 0,
            serviceDuration: 60,
            date: '',
            time: '',
            customerName: '',
            customerPhone: '',
            customerEmail: '',
            customerMemo: ''
        };

        // Service selection
        document.querySelectorAll('.service-card input[type="radio"]').forEach(radio => {
            radio.addEventListener('change', function() {
                // Update visual selection
                document.querySelectorAll('.service-card > div').forEach(card => {
                    card.classList.remove('border-blue-500', 'dark:border-blue-400', 'bg-blue-50', 'dark:bg-blue-900/20');
                    card.classList.add('border-gray-200', 'dark:border-zinc-700');
                });
                this.parentElement.querySelector('div').classList.remove('border-gray-200', 'dark:border-zinc-700');
                this.parentElement.querySelector('div').classList.add('border-blue-500', 'dark:border-blue-400', 'bg-blue-50', 'dark:bg-blue-900/20');

                // Update booking data
                bookingData.serviceId = this.value;
                bookingData.serviceName = this.dataset.name;
                bookingData.servicePrice = parseInt(this.dataset.price);
                bookingData.serviceDuration = parseInt(this.dataset.duration);

                // Enable next button
                document.getElementById('step1Next').disabled = false;
                console.log('[Booking] 서비스 선택:', bookingData.serviceName);
            });
        });

        // Date selection
        document.getElementById('bookingDate').addEventListener('change', function() {
            bookingData.date = this.value;
            generateTimeSlots();
            validateStep2();
            console.log('[Booking] 날짜 선택:', bookingData.date);
        });

        // Generate time slots
        function generateTimeSlots() {
            const container = document.getElementById('timeSlots');
            container.innerHTML = '';

            // Generate slots from 09:00 to 18:00
            for (let hour = 9; hour <= 18; hour++) {
                for (let min = 0; min < 60; min += 30) {
                    const time = `${hour.toString().padStart(2, '0')}:${min.toString().padStart(2, '0')}`;
                    const btn = document.createElement('button');
                    btn.type = 'button';
                    btn.className = 'time-slot px-3 py-2 text-sm border border-gray-300 dark:border-zinc-600 rounded-lg hover:border-blue-500 dark:hover:border-blue-400 text-gray-700 dark:text-zinc-300 transition';
                    btn.textContent = time;
                    btn.onclick = () => selectTime(time, btn);
                    container.appendChild(btn);
                }
            }
        }

        function selectTime(time, btn) {
            document.querySelectorAll('.time-slot').forEach(slot => {
                slot.classList.remove('bg-blue-600', 'text-white', 'border-blue-600');
                slot.classList.add('border-gray-300', 'dark:border-zinc-600', 'text-gray-700', 'dark:text-zinc-300');
            });
            btn.classList.remove('border-gray-300', 'dark:border-zinc-600', 'text-gray-700', 'dark:text-zinc-300');
            btn.classList.add('bg-blue-600', 'text-white', 'border-blue-600');
            bookingData.time = time;
            validateStep2();
            console.log('[Booking] 시간 선택:', time);
        }

        function validateStep2() {
            const valid = bookingData.date && bookingData.time;
            document.getElementById('step2Next').disabled = !valid;
        }

        // Navigation between steps
        function goToStep(step) {
            // Hide all steps
            for (let i = 1; i <= 4; i++) {
                document.getElementById(`step${i}`).classList.add('hidden');
                document.getElementById(`step${i}Indicator`).classList.remove('step-active', 'step-completed');
                document.getElementById(`step${i}Indicator`).classList.add('step-inactive');
            }

            // Show current step
            document.getElementById(`step${step}`).classList.remove('hidden');
            document.getElementById(`step${step}Indicator`).classList.remove('step-inactive');
            document.getElementById(`step${step}Indicator`).classList.add('step-active');

            // Mark previous steps as completed
            for (let i = 1; i < step; i++) {
                document.getElementById(`step${i}Indicator`).classList.remove('step-inactive', 'step-active');
                document.getElementById(`step${i}Indicator`).classList.add('step-completed');
            }

            // Update connectors
            for (let i = 1; i < 4; i++) {
                const connector = document.getElementById(`connector${i}`);
                if (i < step) {
                    connector.classList.remove('bg-gray-200', 'dark:bg-zinc-700');
                    connector.classList.add('bg-green-500');
                } else {
                    connector.classList.remove('bg-green-500');
                    connector.classList.add('bg-gray-200', 'dark:bg-zinc-700');
                }
            }

            // If going to step 4, update confirmation
            if (step === 4) {
                updateConfirmation();
            }

            console.log('[Booking] 단계 이동:', step);
        }

        function updateConfirmation() {
            bookingData.customerName = document.getElementById('customerName').value;
            bookingData.customerPhone = document.getElementById('customerPhone').value;
            bookingData.customerEmail = document.getElementById('customerEmail').value;
            bookingData.customerMemo = document.getElementById('customerMemo').value;

            document.getElementById('confirmService').textContent = bookingData.serviceName;
            document.getElementById('confirmDate').textContent = bookingData.date;
            document.getElementById('confirmTime').textContent = bookingData.time;
            document.getElementById('confirmName').textContent = bookingData.customerName;
            document.getElementById('confirmPhone').textContent = bookingData.customerPhone;
            <?php if ($priceDisplay === 'show'): ?>
            document.getElementById('confirmPrice').textContent = '<?= $currencySymbol ?>' + bookingData.servicePrice.toLocaleString();
            <?php elseif ($priceDisplay === 'contact'): ?>
            document.getElementById('confirmPrice').textContent = '<?= __('admin.services.settings.general.price_contact') ?>';
            <?php else: ?>
            document.getElementById('confirmPrice').textContent = '-';
            <?php endif; ?>
        }

        function submitBooking() {
            // Validate required fields
            if (!bookingData.customerName || !bookingData.customerPhone) {
                alert('<?= __('booking.error.required_fields') ?>');
                goToStep(3);
                return;
            }

            console.log('[Booking] 예약 제출:', bookingData);

            // TODO: Submit to server via AJAX
            alert('<?= __('booking.success') ?>');
            window.location.href = '<?php echo $baseUrl; ?>/';
        }

        // Initialize time slots
        generateTimeSlots();
        console.log('[Booking] 예약 페이지 로드 완료');
    </script>

<?php
// 푸터 포함
include BASE_PATH . '/resources/views/partials/footer.php';
?>
