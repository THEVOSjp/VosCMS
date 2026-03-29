<?php

/**
 * Booking translations - Turkish
 */

return [
    // Page titles
    'title' => 'Rezervasyon Yap',
    'service_list' => 'Hizmet Listesi',
    'select_service' => 'Hizmet Seçin',
    'select_date' => 'Tarih Seçin',
    'select_time' => 'Saat Seçin',
    'enter_info' => 'Bilgi Girin',
    'confirm_booking' => 'Rezervasyonu Onayla',
    'confirm_info' => 'Lütfen rezervasyon bilgilerinizi onaylayın',
    'complete_booking' => 'Rezervasyonu Tamamla',
    'select_service_datetime' => 'Lütfen hizmet ve tercih ettiğiniz tarih/saati seçin',
    'staff_designation_guide' => 'Belirli personel ile randevu için lütfen personel sayfasına gidin',
    'go_staff_booking' => 'Personel Randevusu',
    'select_datetime' => 'Lütfen tarih ve saat seçin',
    'no_services' => 'Şu anda mevcut hizmet bulunmamaktadır.',
    'contact_admin' => 'Lütfen yönetici ile iletişime geçin.',
    'notes' => 'Özel İstekler',
    'notes_placeholder' => 'Özel isteklerinizi girin',
    'customer' => 'Müşteri',
    'phone' => 'Telefon',
    'date_label' => 'Tarih',
    'time_label' => 'Saat',
    'total_price' => 'Toplam Tutar',
    'cancel_policy' => 'Rezervasyon saatinden 24 saat öncesine kadar iptal yapılabilir. Daha geç iptallerde iptal ücreti uygulanabilir.',
    'success' => 'Rezervasyon tamamlandı!',
    'success_desc' => 'Onay mesajı gönderilecektir. Lütfen rezervasyon numaranızı saklayın.',
    'submitting' => 'İşleniyor...',
    'select_staff' => 'Lütfen personel seçin',
    'no_preference' => 'Tercih yok',
    'staff' => 'Personel',
    'designation_fee' => 'Atama ücreti',
    'designation_fee_badge' => '+:amount',
    'loading_slots' => 'Mevcut saatler kontrol ediliyor...',
    'no_available_slots' => 'Seçilen tarihte uygun saat bulunmuyor.',
    'items_selected' => 'seçildi',
    'total_duration' => 'Toplam süre',

    // Steps
    'step' => [
        'service' => 'Hizmet Seç',
        'datetime' => 'Tarih/Saat',
        'info' => 'Bilgiler',
        'confirm' => 'Onayla',
    ],

    // Service
    'service' => [
        'title' => 'Hizmet',
        'name' => 'Hizmet Adı',
        'description' => 'Açıklama',
        'duration' => 'Süre',
        'price' => 'Fiyat',
        'category' => 'Kategori',
        'select' => 'Seç',
        'view_detail' => 'Detayları Görüntüle',
        'no_services' => 'Mevcut hizmet bulunmamaktadır.',
    ],

    // Date/Time
    'date' => [
        'title' => 'Rezervasyon Tarihi',
        'select_date' => 'Lütfen bir tarih seçin',
        'available' => 'Müsait',
        'unavailable' => 'Müsait Değil',
        'fully_booked' => 'Dolu',
        'past_date' => 'Geçmiş Tarih',
    ],

    'time' => [
        'title' => 'Rezervasyon Saati',
        'select_time' => 'Lütfen bir saat seçin',
        'available_slots' => 'Müsait Saatler',
        'no_slots' => 'Müsait saat bulunmamaktadır.',
        'remaining' => ':count yer kaldı',
    ],

    // Booking form
    'form' => [
        'customer_name' => 'Ad Soyad',
        'customer_email' => 'E-posta',
        'customer_phone' => 'Telefon',
        'guests' => 'Kişi Sayısı',
        'notes' => 'Özel İstekler',
        'notes_placeholder' => 'Özel isteklerinizi girin',
    ],

    // Confirmation
    'confirm' => [
        'title' => 'Rezervasyonu Onayla',
        'summary' => 'Rezervasyon Özeti',
        'service_info' => 'Hizmet Bilgileri',
        'booking_info' => 'Rezervasyon Bilgileri',
        'customer_info' => 'Müşteri Bilgileri',
        'total_price' => 'Toplam',
        'agree_terms' => 'Rezervasyon şartlarını kabul ediyorum',
        'submit' => 'Rezervasyonu Tamamla',
    ],

    // Complete
    'complete' => [
        'title' => 'Rezervasyon Tamamlandı',
        'success' => 'Rezervasyonunuz tamamlandı!',
        'booking_code' => 'Rezervasyon Kodu',
        'check_email' => 'E-posta adresinize bir onay e-postası gönderildi.',
        'view_detail' => 'Rezervasyon Detaylarını Görüntüle',
        'book_another' => 'Başka Rezervasyon Yap',
    ],

    // Lookup
    'lookup' => [
        'title' => 'Rezervasyon Sorgula',
        'description' => 'Rezervasyonunuzu bulmak için bilgilerinizi girin.',
        'booking_code' => 'Rezervasyon Kodu',
        'booking_code_placeholder' => 'RZ250301XXXXXX',
        'email' => 'E-posta',
        'email_placeholder' => 'Rezervasyonda kullanılan e-posta',
        'phone' => 'Telefon Numarası',
        'phone_placeholder' => 'Rezervasyonda kullanılan telefon numarası',
        'search' => 'Ara',
        'search_method' => 'Arama Yöntemi',
        'by_code' => 'Rezervasyon Kodu ile Ara',
        'by_email' => 'E-posta ile Ara',
        'by_phone' => 'Telefon ile Ara',
        'not_found' => 'Rezervasyon bulunamadı. Lütfen bilgilerinizi kontrol edin.',
        'input_required' => 'Lütfen rezervasyon kodu ve e-posta veya telefon numarası girin.',
        'result_title' => 'Arama Sonuçları',
        'multiple_results' => ':count rezervasyon bulundu.',
        'hint' => 'Doğru sonuçlar için rezervasyon kodunun yanı sıra e-posta veya telefon numaranızı girin.',
        'help_text' => 'Rezervasyonunuzu bulamıyor musunuz?',
        'contact_support' => 'Destek ile İletişime Geçin',
    ],

    // Detail
    'detail' => [
        'title' => 'Rezervasyon Detayları',
        'status' => 'Durum',
        'booking_date' => 'Tarih ve Saat',
        'service' => 'Hizmet',
        'services' => 'Hizmetler',
        'guests' => 'Kişi Sayısı',
        'total_price' => 'Toplam Fiyat',
        'payment_status' => 'Ödeme Durumu',
        'notes' => 'Özel İstekler',
        'created_at' => 'Rezervasyon Tarihi',
        'duration_unit' => 'dakika',
        'staff_not_assigned' => 'Atanmamış',
        'back_to_lookup' => 'Rezervasyon Arama',
        'payment' => 'Ödeme Detayları',
        'total' => 'Ara Toplam',
        'discount' => 'İndirim',
        'points_used' => 'Kullanılan Puanlar',
        'final_amount' => 'Son Tutar',
        'staff' => 'Personel',
        'designation_fee' => 'Atama Ücreti',
        'cancel_info' => 'İptal Detayları',
        'cancelled_at' => 'İptal Tarihi',
        'cancel_reason' => 'İptal Nedeni',
    ],

    // Cancel
    'cancel' => [
        'title' => 'Rezervasyon İptali',
        'confirm' => 'Bu rezervasyonu iptal etmek istediğinizden emin misiniz?',
        'reason' => 'İptal Nedeni',
        'reason_placeholder' => 'Lütfen iptal nedenini girin',
        'submit' => 'Rezervasyonu İptal Et',
        'success' => 'Rezervasyonunuz iptal edildi.',
        'cannot_cancel' => 'Bu rezervasyon iptal edilemez.',
    ],

    // Status messages
    'status' => [
        'pending' => 'Rezervasyonunuz alındı. Lütfen onay için bekleyin.',
        'confirmed' => 'Rezervasyonunuz onaylandı.',
        'cancelled' => 'Rezervasyonunuz iptal edildi.',
        'completed' => 'Hizmet tamamlandı.',
        'no_show' => 'Gelmedi olarak işaretlendi.',
    ],

    // Payment status
    'payment' => [
        'unpaid' => 'Ödenmemiş',
        'paid' => 'Ödendi',
        'partial' => 'Kısmen ödenmiş',
        'refunded' => 'İade edildi',
        'needs_payment' => 'Ödeme gerekli',
        'needs_payment_desc' => 'Ödeme tamamlandıktan sonra rezervasyonunuz onaylanacaktır.',
        'pay_now' => 'Şimdi öde',
        'charge_amount' => 'Ödenecek Tutar',
        'back_to_detail' => 'Rezervasyon detaylarına dön',
        'loading' => 'Ödeme yükleniyor...',
        'deposit' => 'Kapora',
        'deposit_notice' => 'Kalan tutar yerinde ödenecektir.',
        'retry' => 'Tekrar öde',
        'cancel_reservation' => 'Rezervasyonu iptal et',
        'applied_price' => 'uygulanan fiyat',
    ],

    // Error messages
    'error' => [
        'service_not_found' => 'Hizmet bulunamadı.',
        'slot_unavailable' => 'Seçilen saat müsait değil.',
        'past_date' => 'Geçmiş tarihler için rezervasyon yapılamaz.',
        'max_capacity' => 'Maksimum kapasite aşıldı.',
        'booking_failed' => 'Rezervasyon işlenirken bir hata oluştu.',
        'required_fields' => 'Lütfen adınızı ve iletişim bilgilerinizi girin.',
        'invalid_service' => 'Geçersiz hizmet.',
    ],

    'member_discount' => 'Üye İndirimi',
    'use_points' => 'Puan Kullan',
    'points_balance' => 'Bakiye',
    'use_all' => 'Tümünü Kullan',
    'points_default_name' => 'Puan',
    'deposit_pay_now' => 'Depozito (Şimdi Öde)',
    'deposit_remaining_later' => 'Kalan bakiye hizmet sırasında tahsil edilecektir',
    'next' => 'İleri',
    'categories' => 'kategori',
    'service_count' => 'hizmet',
    'expected_points' => 'Tahmini puan',
    'reservation_complete' => 'Rezervasyon tamamlandı',
    'reservation_complete_desc' => 'Lütfen rezervasyon detaylarını kontrol edin',
    'reservation_number' => 'Rezervasyon No.',
    'check_summary' => 'Detayları gör',
];
