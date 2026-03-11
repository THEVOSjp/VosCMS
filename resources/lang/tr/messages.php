<?php
/**
 * Turkish Language File - Messages
 */

return [
    // Common
    'app_name' => 'RezlyX',
    'welcome' => 'Hoş Geldiniz',
    'home' => 'Ana Sayfa',
    'back' => 'Geri',
    'next' => 'İleri',
    'cancel' => 'İptal',
    'confirm' => 'Onayla',
    'save' => 'Kaydet',
    'delete' => 'Sil',
    'edit' => 'Düzenle',
    'search' => 'Ara',
    'loading' => 'Yükleniyor...',
    'no_data' => 'Veri bulunamadı.',
    'error' => 'Bir hata oluştu.',
    'success' => 'İşlem başarıyla tamamlandı.',

    // Auth
    'auth' => [
        'login' => 'Giriş Yap',
        'logout' => 'Çıkış Yap',
        'register' => 'Kayıt Ol',
        'email' => 'E-posta',
        'password' => 'Şifre',
        'password_confirm' => 'Şifre Tekrar',
        'remember_me' => 'Beni hatırla',
        'forgot_password' => 'Şifremi unuttum?',
        'reset_password' => 'Şifre Sıfırla',
        'invalid_credentials' => 'Geçersiz e-posta veya şifre.',
        'account_inactive' => 'Bu hesap devre dışı.',
    ],

    // Reservation
    'reservation' => [
        'title' => 'Rezervasyon',
        'new' => 'Yeni Rezervasyon',
        'my_reservations' => 'Rezervasyonlarım',
        'select_service' => 'Hizmet Seçin',
        'select_date' => 'Tarih Seçin',
        'select_time' => 'Saat Seçin',
        'customer_info' => 'Bilgileriniz',
        'payment' => 'Ödeme',
        'confirmation' => 'Onay',
        'status' => [
            'pending' => 'Beklemede',
            'confirmed' => 'Onaylandı',
            'completed' => 'Tamamlandı',
            'cancelled' => 'İptal Edildi',
            'no_show' => 'Gelmedi',
        ],
    ],

    // Services
    'service' => [
        'title' => 'Hizmetler',
        'category' => 'Kategori',
        'price' => 'Fiyat',
        'duration' => 'Süre',
        'description' => 'Açıklama',
        'options' => 'Seçenekler',
    ],

    // Member
    'member' => [
        'profile' => 'Profilim',
        'points' => 'Puanlar',
        'grade' => 'Üyelik Seviyesi',
        'reservations' => 'Rezervasyon Geçmişi',
        'payments' => 'Ödeme Geçmişi',
        'settings' => 'Ayarlar',
    ],

    // Payment
    'payment' => [
        'title' => 'Ödeme',
        'amount' => 'Tutar',
        'method' => 'Ödeme Yöntemi',
        'card' => 'Kredi Kartı',
        'bank_transfer' => 'Banka Havalesi',
        'virtual_account' => 'Sanal Hesap',
        'points' => 'Puanlar',
        'use_points' => 'Puan Kullan',
        'available_points' => 'Kullanılabilir Puanlar',
        'complete' => 'Ödeme Tamamlandı',
        'failed' => 'Ödeme Başarısız',
    ],

    // Time
    'time' => [
        'today' => 'Bugün',
        'tomorrow' => 'Yarın',
        'minutes' => 'dk',
        'hours' => 'saat',
        'days' => 'gün',
    ],

    // Validation
    'validation' => [
        'required' => ':attribute alanı gereklidir.',
        'email' => 'Lütfen geçerli bir e-posta adresi girin.',
        'min' => ':attribute en az :min karakter olmalıdır.',
        'max' => ':attribute en fazla :max karakter olabilir.',
        'confirmed' => ':attribute doğrulaması eşleşmiyor.',
    ],
];
