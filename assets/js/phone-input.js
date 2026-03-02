/**
 * 전화번호 입력 컴포넌트 JavaScript 모듈
 *
 * 사용법:
 * <script src="/assets/js/phone-input.js"></script>
 * <script>
 *   document.addEventListener('DOMContentLoaded', function() {
 *     PhoneInput.init();
 *     // 또는 특정 컨테이너만 초기화
 *     // PhoneInput.init(document.querySelector('.my-form'));
 *   });
 * </script>
 */

const PhoneInput = (function() {
    'use strict';

    /**
     * 전화번호 포맷팅 함수 (숫자만 허용, 하이픈 자동 삽입)
     */
    function formatPhoneNumber(value, countryCode) {
        // 숫자만 추출
        let numbers = value.replace(/[^\d]/g, '');

        // 국가별 포맷팅
        if (countryCode === '+82') {
            // 한국: 최대 11자리
            numbers = numbers.slice(0, 11);

            // 휴대폰: 010-1234-5678 (3-4-4)
            if (numbers.startsWith('010') || numbers.startsWith('011') || numbers.startsWith('016') ||
                numbers.startsWith('017') || numbers.startsWith('018') || numbers.startsWith('019')) {
                if (numbers.length <= 3) return numbers;
                if (numbers.length <= 7) return numbers.slice(0, 3) + '-' + numbers.slice(3);
                return numbers.slice(0, 3) + '-' + numbers.slice(3, 7) + '-' + numbers.slice(7);
            } else if (numbers.startsWith('02')) {
                // 서울: 02-1234-5678 (2-4-4)
                numbers = numbers.slice(0, 10);
                if (numbers.length <= 2) return numbers;
                if (numbers.length <= 6) return numbers.slice(0, 2) + '-' + numbers.slice(2);
                return numbers.slice(0, 2) + '-' + numbers.slice(2, 6) + '-' + numbers.slice(6);
            } else {
                // 지역번호: 031-123-4567 (3-3-4 또는 3-4-4)
                if (numbers.length <= 3) return numbers;
                if (numbers.length <= 7) return numbers.slice(0, 3) + '-' + numbers.slice(3);
                return numbers.slice(0, 3) + '-' + numbers.slice(3, 7) + '-' + numbers.slice(7);
            }
        } else if (countryCode === '+81') {
            // 일본: 090-1234-5678 (최대 11자리)
            numbers = numbers.slice(0, 11);
            if (numbers.length <= 3) return numbers;
            if (numbers.length <= 7) return numbers.slice(0, 3) + '-' + numbers.slice(3);
            return numbers.slice(0, 3) + '-' + numbers.slice(3, 7) + '-' + numbers.slice(7);
        } else if (countryCode === '+1') {
            // 미국: 123-456-7890 (최대 10자리)
            numbers = numbers.slice(0, 10);
            if (numbers.length <= 3) return numbers;
            if (numbers.length <= 6) return numbers.slice(0, 3) + '-' + numbers.slice(3);
            return numbers.slice(0, 3) + '-' + numbers.slice(3, 6) + '-' + numbers.slice(6);
        } else if (countryCode === '+61') {
            // 호주
            if (numbers.startsWith('0')) {
                // 국내형식: 0412-345-678 (10자리)
                numbers = numbers.slice(0, 10);
                if (numbers.length <= 4) return numbers;
                if (numbers.length <= 7) return numbers.slice(0, 4) + '-' + numbers.slice(4);
                return numbers.slice(0, 4) + '-' + numbers.slice(4, 7) + '-' + numbers.slice(7);
            } else {
                // 국제형식: 412-345-678 (9자리)
                numbers = numbers.slice(0, 9);
                if (numbers.length <= 3) return numbers;
                if (numbers.length <= 6) return numbers.slice(0, 3) + '-' + numbers.slice(3);
                return numbers.slice(0, 3) + '-' + numbers.slice(3, 6) + '-' + numbers.slice(6);
            }
        } else if (countryCode === '+86') {
            // 중국: 138-1234-5678 (11자리)
            numbers = numbers.slice(0, 11);
            if (numbers.length <= 3) return numbers;
            if (numbers.length <= 7) return numbers.slice(0, 3) + '-' + numbers.slice(3);
            return numbers.slice(0, 3) + '-' + numbers.slice(3, 7) + '-' + numbers.slice(7);
        } else if (countryCode === '+44') {
            // 영국: 7XXX-XXX-XXX (10자리)
            numbers = numbers.slice(0, 10);
            if (numbers.length <= 4) return numbers;
            if (numbers.length <= 7) return numbers.slice(0, 4) + '-' + numbers.slice(4);
            return numbers.slice(0, 4) + '-' + numbers.slice(4, 7) + '-' + numbers.slice(7);
        } else if (countryCode === '+49') {
            // 독일: 151-1234-5678 (다양)
            numbers = numbers.slice(0, 11);
            if (numbers.length <= 3) return numbers;
            if (numbers.length <= 7) return numbers.slice(0, 3) + '-' + numbers.slice(3);
            return numbers.slice(0, 3) + '-' + numbers.slice(3, 7) + '-' + numbers.slice(7);
        } else if (countryCode === '+33') {
            // 프랑스: 6-12-34-56-78 (9자리)
            numbers = numbers.slice(0, 9);
            if (numbers.length <= 1) return numbers;
            if (numbers.length <= 3) return numbers.slice(0, 1) + '-' + numbers.slice(1);
            if (numbers.length <= 5) return numbers.slice(0, 1) + '-' + numbers.slice(1, 3) + '-' + numbers.slice(3);
            if (numbers.length <= 7) return numbers.slice(0, 1) + '-' + numbers.slice(1, 3) + '-' + numbers.slice(3, 5) + '-' + numbers.slice(5);
            return numbers.slice(0, 1) + '-' + numbers.slice(1, 3) + '-' + numbers.slice(3, 5) + '-' + numbers.slice(5, 7) + '-' + numbers.slice(7);
        } else {
            // 기타: 하이픈 없이 숫자만 (최대 15자리)
            return numbers.slice(0, 15);
        }
    }

    /**
     * 전화번호 조합 함수
     */
    function updatePhoneValue(component) {
        const idPrefix = component.dataset.idPrefix;
        const countryInput = component.querySelector(`#${idPrefix}_country`);
        const numberInput = component.querySelector(`#${idPrefix}_number`);
        const hiddenInput = component.querySelector(`#${idPrefix}`);

        if (!countryInput || !numberInput || !hiddenInput) return;

        if (numberInput.value.trim()) {
            const numbers = numberInput.value.replace(/[^\d]/g, '');
            hiddenInput.value = countryInput.value + numbers;
        } else {
            hiddenInput.value = '';
        }
        console.log('[PhoneInput] 전화번호 조합:', hiddenInput.value);
    }

    /**
     * 단일 컴포넌트 초기화
     */
    function initComponent(component) {
        const idPrefix = component.dataset.idPrefix;

        const countryInput = component.querySelector(`#${idPrefix}_country`);
        const numberInput = component.querySelector(`#${idPrefix}_number`);
        const dropdownBtn = component.querySelector('.phone-country-dropdown-btn');
        const dropdown = component.querySelector('.phone-country-dropdown');
        const options = component.querySelectorAll('.phone-country-option');
        const selectedFlag = component.querySelector('.phone-selected-flag');
        const selectedCountry = component.querySelector('.phone-selected-country');
        const selectedCode = component.querySelector('.phone-selected-code');

        if (!countryInput || !numberInput || !dropdownBtn || !dropdown) {
            console.warn('[PhoneInput] 필수 요소를 찾을 수 없습니다:', idPrefix);
            return;
        }

        // 드롭다운 토글
        dropdownBtn.addEventListener('click', function(e) {
            e.stopPropagation();
            dropdown.classList.toggle('hidden');
            console.log('[PhoneInput] 국가코드 드롭다운 토글');
        });

        // 국가 선택
        options.forEach(function(option) {
            option.addEventListener('click', function() {
                const code = option.dataset.code;
                const flag = option.dataset.flag;
                const name = option.dataset.name;

                if (selectedFlag) selectedFlag.textContent = flag;
                if (selectedCountry) selectedCountry.textContent = name;
                if (selectedCode) selectedCode.textContent = code;
                countryInput.value = code;

                dropdown.classList.add('hidden');

                // 전화번호 포맷 재적용
                numberInput.value = formatPhoneNumber(numberInput.value, code);
                updatePhoneValue(component);
                console.log('[PhoneInput] 국가코드 변경:', code);
            });
        });

        // 전화번호 입력 이벤트
        numberInput.addEventListener('input', function() {
            const formatted = formatPhoneNumber(this.value, countryInput.value);
            this.value = formatted;
            updatePhoneValue(component);
        });

        // 초기 값 설정
        updatePhoneValue(component);

        console.log('[PhoneInput] 컴포넌트 초기화 완료:', idPrefix);
    }

    /**
     * 외부 클릭 시 모든 드롭다운 닫기
     */
    function setupGlobalClickHandler() {
        document.addEventListener('click', function(e) {
            const dropdowns = document.querySelectorAll('.phone-country-dropdown');
            dropdowns.forEach(function(dropdown) {
                const wrapper = dropdown.closest('.phone-country-dropdown-wrapper');
                if (wrapper && !wrapper.contains(e.target)) {
                    dropdown.classList.add('hidden');
                }
            });
        });
    }

    /**
     * 초기화 함수
     * @param {HTMLElement} container - 초기화할 컨테이너 (선택사항, 기본값: document)
     */
    function init(container) {
        const root = container || document;
        const components = root.querySelectorAll('.phone-input-component');

        components.forEach(function(component) {
            // 이미 초기화된 컴포넌트는 건너뛰기
            if (component.dataset.initialized) return;
            component.dataset.initialized = 'true';

            initComponent(component);
        });

        // 전역 클릭 핸들러는 한 번만 설정
        if (!window._phoneInputGlobalHandlerSet) {
            setupGlobalClickHandler();
            window._phoneInputGlobalHandlerSet = true;
        }

        console.log('[PhoneInput] 모듈 초기화 완료, 컴포넌트 수:', components.length);
    }

    /**
     * 특정 컴포넌트의 값 가져오기
     * @param {string} idPrefix - 컴포넌트 ID 접두사
     * @returns {object} - { countryCode, phoneNumber, fullNumber }
     */
    function getValue(idPrefix) {
        const countryInput = document.getElementById(`${idPrefix}_country`);
        const numberInput = document.getElementById(`${idPrefix}_number`);
        const hiddenInput = document.getElementById(idPrefix);

        return {
            countryCode: countryInput ? countryInput.value : '',
            phoneNumber: numberInput ? numberInput.value : '',
            fullNumber: hiddenInput ? hiddenInput.value : ''
        };
    }

    /**
     * 특정 컴포넌트의 값 설정하기
     * @param {string} idPrefix - 컴포넌트 ID 접두사
     * @param {string} countryCode - 국가 코드 (예: '+82')
     * @param {string} phoneNumber - 전화번호 (포맷팅 전)
     */
    function setValue(idPrefix, countryCode, phoneNumber) {
        const component = document.querySelector(`.phone-input-component[data-id-prefix="${idPrefix}"]`);
        if (!component) return;

        const countryInput = component.querySelector(`#${idPrefix}_country`);
        const numberInput = component.querySelector(`#${idPrefix}_number`);
        const selectedCountry = component.querySelector('.phone-selected-country');
        const selectedCode = component.querySelector('.phone-selected-code');
        const selectedFlag = component.querySelector('.phone-selected-flag');

        if (countryInput && countryCode) {
            countryInput.value = countryCode;

            // 드롭다운에서 해당 국가 찾아서 UI 업데이트
            const option = component.querySelector(`.phone-country-option[data-code="${countryCode}"]`);
            if (option) {
                if (selectedFlag) selectedFlag.textContent = option.dataset.flag;
                if (selectedCountry) selectedCountry.textContent = option.dataset.name;
                if (selectedCode) selectedCode.textContent = option.dataset.code;
            }
        }

        if (numberInput && phoneNumber !== undefined) {
            numberInput.value = formatPhoneNumber(phoneNumber, countryInput ? countryInput.value : '+82');
        }

        updatePhoneValue(component);
    }

    // Public API
    return {
        init: init,
        getValue: getValue,
        setValue: setValue,
        formatPhoneNumber: formatPhoneNumber
    };
})();

// Auto-init on DOMContentLoaded if not manually initialized
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', function() {
        PhoneInput.init();
    });
} else {
    // DOM is already ready
    PhoneInput.init();
}
