<?php

/**
 * My Page translations - Turkish
 */

return [
    // Main
    'title' => 'Hesabım',
    'welcome' => 'Hoş geldiniz, :name!',

    // Navigation
    'nav' => [
        'dashboard' => 'Kontrol Paneli',
        'reservations' => 'Rezervasyonlarım',
        'profile' => 'Profil',
        'password' => 'Şifre Değiştir',
        'logout' => 'Çıkış Yap',
    ],

    // Dashboard
    'dashboard' => [
        'upcoming' => 'Yaklaşan Rezervasyonlar',
        'recent' => 'Son Rezervasyonlar',
        'no_upcoming' => 'Yaklaşan rezervasyon bulunmamaktadır.',
        'no_recent' => 'Son rezervasyon bulunmamaktadır.',
        'view_all' => 'Tümünü Görüntüle',
    ],

    // Reservations
    'reservations' => [
        'title' => 'Rezervasyon Geçmişi',
        'filter' => [
            'all' => 'Tümü',
            'pending' => 'Beklemede',
            'confirmed' => 'Onaylandı',
            'completed' => 'Tamamlandı',
            'cancelled' => 'İptal Edildi',
        ],
        'no_reservations' => 'Rezervasyon bulunamadı.',
        'booking_code' => 'Rezervasyon Kodu',
        'service' => 'Hizmet',
        'date' => 'Tarih',
        'status' => 'Durum',
        'actions' => 'İşlemler',
        'view' => 'Görüntüle',
        'cancel' => 'İptal Et',
    ],

    // Profile
    'profile' => [
        'title' => 'Profil Ayarları',
        'info' => 'Temel Bilgiler',
        'name' => 'Ad Soyad',
        'email' => 'E-posta',
        'phone' => 'Telefon',
        'save' => 'Kaydet',
        'success' => 'Profil başarıyla güncellendi.',
    ],

    // Password
    'password' => [
        'title' => 'Şifre Değiştir',
        'current' => 'Mevcut Şifre',
        'new' => 'Yeni Şifre',
        'confirm' => 'Yeni Şifre Tekrar',
        'change' => 'Şifreyi Değiştir',
        'success' => 'Şifre başarıyla değiştirildi.',
        'mismatch' => 'Mevcut şifre yanlış.',
    ],

    // Stats
    'stats' => [
        'total_bookings' => 'Toplam Rezervasyon',
        'completed' => 'Tamamlanan',
        'cancelled' => 'İptal Edilen',
        'upcoming' => 'Yaklaşan',
    ],
];
