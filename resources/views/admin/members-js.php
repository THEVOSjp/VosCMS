<?php
/**
 * RezlyX Admin - 회원 관리 JavaScript
 * 프로필 사진 Cropper.js + 국제전화번호 컴포넌트 연동
 */
?>
<script>
(function() {
    'use strict';

    var memberCropper = null;
    var memberCroppedBlob = null;

    function showAlert(msg, type) {
        var box = document.getElementById('alertBox');
        box.className = 'mb-6 p-4 rounded-lg border ' +
            (type === 'success'
                ? 'bg-green-50 dark:bg-green-900/30 text-green-800 dark:text-green-300 border-green-200 dark:border-green-800'
                : 'bg-red-50 dark:bg-red-900/30 text-red-800 dark:text-red-300 border-red-200 dark:border-red-800');
        box.textContent = msg;
        box.classList.remove('hidden');
        setTimeout(function() { box.classList.add('hidden'); }, 4000);
        console.log('[Members] Alert:', type, msg);
    }

    function postData(formData) {
        return fetch(window.location.pathname, {
            method: 'POST',
            body: formData
        }).then(function(r) { return r.json(); });
    }

    function setVal(id, value) {
        var el = document.getElementById(id);
        if (el) el.value = value || '';
    }

    // 아바타 미리보기 설정
    function setAvatarPreview(src) {
        var preview = document.getElementById('memberAvatarPreview');
        if (!preview) return;
        if (src) {
            preview.innerHTML = '<img src="' + src + '" class="w-full h-full object-cover">';
        } else {
            preview.innerHTML = '<svg class="w-8 h-8 text-zinc-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>';
        }
    }

    // 전화번호 파싱 (국제번호에서 국가코드와 번호 분리)
    function parsePhone(phone) {
        if (!phone) return { code: '+82', number: '' };
        phone = phone.trim();

        // 국가코드 패턴 매칭 (긴 코드부터)
        var codes = ['+1876','+1868','+1869','+1809','+1787','+1767','+1758','+1671','+1473','+1441','+1268','+1246','+1242',
            '+886','+880','+856','+855','+850','+998','+996','+995','+994','+993','+992','+977','+976','+975','+974','+973',
            '+971','+968','+967','+966','+965','+964','+963','+962','+961','+960','+853','+852','+692','+691','+686','+685',
            '+680','+679','+678','+677','+676','+675','+674','+670','+673','+599','+598','+596','+595','+593','+591','+590',
            '+507','+506','+505','+504','+503','+502','+421','+420','+389','+387','+386','+385','+383','+382','+381','+380',
            '+378','+377','+376','+375','+374','+372','+371','+370','+359','+358','+357','+356','+355','+354','+353','+352',
            '+351','+350','+297','+291','+268','+267','+266','+265','+264','+263','+261','+260','+258','+256','+255','+254',
            '+253','+252','+251','+249','+248','+244','+243','+242','+241','+238','+237','+235','+234','+233','+232','+231',
            '+230','+229','+228','+227','+226','+225','+224','+223','+221','+220','+218','+216','+213','+212','+211',
            '+98','+95','+94','+93','+92','+91','+90','+86','+84','+82','+81','+66','+65','+63','+62','+61','+60',
            '+58','+57','+56','+55','+54','+53','+52','+51','+49','+48','+47','+46','+45','+44','+43','+41',
            '+40','+39','+36','+34','+33','+32','+31','+30','+27','+20','+7','+1'];

        for (var i = 0; i < codes.length; i++) {
            if (phone.startsWith(codes[i])) {
                return { code: codes[i], number: phone.substring(codes[i].length) };
            }
        }

        // 로컬 번호 (0으로 시작하면 한국으로 간주)
        if (phone.startsWith('0')) {
            return { code: '+82', number: phone };
        }
        return { code: '+82', number: phone };
    }

    // ── 회원 추가 모달 ──
    window.openCreateMember = function() {
        document.getElementById('memberModalTitle').textContent = '<?= __('admin.members.list.create') ?>';
        document.getElementById('memberAction').value = 'create_member';
        document.getElementById('memberId').value = '';

        setVal('memberName', '');
        setVal('memberEmail', '');
        setVal('memberPassword', '');
        setVal('memberBirthDate', '');
        setVal('memberGender', '');
        setVal('memberCompany', '');
        setVal('memberBlog', '');
        setVal('memberGrade', '');
        setVal('memberStatus', 'active');

        // 전화번호 컴포넌트 초기화
        if (typeof PhoneInput !== 'undefined') {
            PhoneInput.setValue('memberPhone', '+82', '');
        }

        // 프로필 이미지 초기화
        setAvatarPreview(null);
        memberCroppedBlob = null;
        var fileInput = document.getElementById('memberProfileImage');
        if (fileInput) fileInput.value = '';

        // 비밀번호 필수 표시
        var pwReq = document.getElementById('memberPasswordRequired');
        var pwHint = document.getElementById('memberPasswordHint');
        var pwInput = document.getElementById('memberPassword');
        if (pwReq) pwReq.classList.remove('hidden');
        if (pwHint) pwHint.classList.add('hidden');
        if (pwInput) pwInput.required = true;

        document.getElementById('memberInfoBox').classList.add('hidden');
        document.getElementById('memberModal').classList.remove('hidden');
        document.getElementById('memberName').focus();
        console.log('[Members] Modal opened: create');
    };

    // ── 회원 수정 모달 ──
    window.editMember = function(m) {
        document.getElementById('memberModalTitle').textContent = '<?= __('admin.members.list.edit') ?>';
        document.getElementById('memberAction').value = 'update_member';
        document.getElementById('memberId').value = m.id;

        setVal('memberName', m.name);
        setVal('memberEmail', m.email);
        setVal('memberPassword', '');
        setVal('memberBirthDate', m.birth_date);
        setVal('memberGender', m.gender);
        setVal('memberCompany', m.company);
        setVal('memberBlog', m.blog);
        setVal('memberGrade', m.grade_id);
        setVal('memberStatus', m.status || 'active');

        // 전화번호 컴포넌트 값 설정
        if (typeof PhoneInput !== 'undefined') {
            var parsed = parsePhone(m.phone);
            PhoneInput.setValue('memberPhone', parsed.code, parsed.number);
        }

        // 프로필 이미지
        memberCroppedBlob = null;
        var fileInput = document.getElementById('memberProfileImage');
        if (fileInput) fileInput.value = '';
        if (m.profile_image) {
            var imgUrl = m.profile_image.startsWith('http') ? m.profile_image : '<?= $baseUrl ?>' + m.profile_image;
            setAvatarPreview(imgUrl);
        } else {
            setAvatarPreview(null);
        }

        // 비밀번호 선택 표시
        var pwReq = document.getElementById('memberPasswordRequired');
        var pwHint = document.getElementById('memberPasswordHint');
        var pwInput = document.getElementById('memberPassword');
        if (pwReq) pwReq.classList.add('hidden');
        if (pwHint) pwHint.classList.remove('hidden');
        if (pwInput) pwInput.required = false;

        // 정보 표시
        document.getElementById('memberInfoId').textContent = m.id;
        document.getElementById('memberInfoJoined').textContent = m.created_at ? m.created_at.substring(0, 10) : '-';
        document.getElementById('memberInfoLogin').textContent = m.last_login_at ? m.last_login_at.substring(0, 16).replace('T', ' ') : '-';
        document.getElementById('memberInfoBox').classList.remove('hidden');

        document.getElementById('memberModal').classList.remove('hidden');
        document.getElementById('memberName').focus();
        console.log('[Members] Modal opened: edit', m.id);
    };

    window.closeMemberModal = function() {
        document.getElementById('memberModal').classList.add('hidden');
        console.log('[Members] Modal closed');
    };

    window.saveMember = function() {
        var name = document.getElementById('memberName').value.trim();
        var email = document.getElementById('memberEmail').value.trim();
        if (!name || !email) {
            if (!name) document.getElementById('memberName').focus();
            else document.getElementById('memberEmail').focus();
            return;
        }

        var action = document.getElementById('memberAction').value;
        var pwInput = document.getElementById('memberPassword');
        if (action === 'create_member' && pwInput && !pwInput.value.trim()) {
            pwInput.focus();
            return;
        }

        var form = document.getElementById('memberForm');
        var formData = new FormData(form);

        // 전화번호 컴포넌트에서 값 명시적으로 가져오기
        if (typeof PhoneInput !== 'undefined') {
            var phoneValue = PhoneInput.getValue('memberPhone');
            console.log('[Members] Phone value from component:', phoneValue);
            if (phoneValue && phoneValue.fullNumber) {
                formData.set('phone', phoneValue.fullNumber);
            }
        }

        // Cropper로 잘린 이미지가 있으면 교체
        if (memberCroppedBlob) {
            formData.delete('profile_image');
            formData.set('profile_image', memberCroppedBlob, 'profile.jpg');
        }

        // FormData 디버그 로그
        console.log('[Members] Saving:', action, name);
        for (var pair of formData.entries()) {
            if (pair[0] !== 'profile_image') console.log('[Members] FormData:', pair[0], '=', pair[1]);
        }

        postData(formData).then(function(data) {
            if (data.success) {
                showAlert(data.message, 'success');
                closeMemberModal();
                setTimeout(function() { location.reload(); }, 800);
            } else {
                showAlert(data.message || '<?= __('admin.members.list.error.generic') ?>', 'error');
            }
        }).catch(function(err) {
            console.error('[Members] Save error:', err);
            showAlert('<?= __('admin.members.list.error.server') ?>', 'error');
        });
    };

    window.deleteMember = function(id) {
        if (!confirm('<?= __('admin.members.list.confirm_delete') ?>')) return;
        console.log('[Members] Deleting:', id);

        var formData = new FormData();
        formData.append('action', 'delete_member');
        formData.append('id', id);

        postData(formData).then(function(data) {
            if (data.success) {
                showAlert(data.message, 'success');
                var row = document.getElementById('member-' + id);
                if (row) row.remove();
            } else {
                showAlert(data.message || 'Error', 'error');
            }
        }).catch(function(err) {
            console.error('[Members] Delete error:', err);
            showAlert('<?= __('admin.members.list.error.server') ?>', 'error');
        });
    };

    // ── 프로필 사진 Cropper ──
    window.openMemberCropper = function(input) {
        if (!input.files || !input.files[0]) return;
        var file = input.files[0];
        var reader = new FileReader();
        reader.onload = function(e) {
            var img = document.getElementById('memberCropperImage');
            if (!img) return;
            img.src = e.target.result;
            document.getElementById('memberCropperModal').classList.remove('hidden');

            if (memberCropper) memberCropper.destroy();
            memberCropper = new Cropper(img, {
                aspectRatio: 1,
                viewMode: 1,
                dragMode: 'move',
                autoCropArea: 0.9,
                cropBoxResizable: true,
                background: false,
            });
            console.log('[Members] Cropper opened');
        };
        reader.readAsDataURL(file);
    };

    window.closeMemberCropper = function() {
        document.getElementById('memberCropperModal').classList.add('hidden');
        if (memberCropper) { memberCropper.destroy(); memberCropper = null; }
        var fileInput = document.getElementById('memberProfileImage');
        if (fileInput) fileInput.value = '';
        console.log('[Members] Cropper closed');
    };

    window.memberCropperAction = function(action, value) {
        if (!memberCropper) return;
        if (action === 'zoom') memberCropper.zoom(value);
        else if (action === 'rotate') memberCropper.rotate(value);
        else if (action === 'reset') memberCropper.reset();
        console.log('[Members] Cropper action:', action, value);
    };

    window.applyMemberCrop = function() {
        if (!memberCropper) return;
        var canvas = memberCropper.getCroppedCanvas({ width: 400, height: 400 });
        canvas.toBlob(function(blob) {
            memberCroppedBlob = blob;
            setAvatarPreview(canvas.toDataURL('image/jpeg', 0.9));
            document.getElementById('memberCropperModal').classList.add('hidden');
            memberCropper.destroy();
            memberCropper = null;
            console.log('[Members] Crop applied, size:', blob.size);
        }, 'image/jpeg', 0.9);
    };

    // ESC 닫기
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            var cropperModal = document.getElementById('memberCropperModal');
            if (cropperModal && !cropperModal.classList.contains('hidden')) {
                closeMemberCropper();
            } else {
                closeMemberModal();
            }
        }
    });

    console.log('[Members] Page initialized');
})();
</script>
