<?php

/**
 * Authentication translations - Turkish
 */

return [
    // Login
    'login' => [
        'title' => 'Giriş Yap',
        'description' => 'Rezervasyonlarınızı yönetmek için hesabınıza giriş yapın',
        'email' => 'E-posta',
        'email_placeholder' => 'ornek@email.com',
        'password' => 'Şifre',
        'password_placeholder' => '••••••••',
        'remember' => 'Beni hatırla',
        'forgot' => 'Şifremi unuttum?',
        'submit' => 'Giriş Yap',
        'no_account' => 'Hesabınız yok mu?',
        'register_link' => 'Kayıt Ol',
        'back_home' => '← Ana Sayfaya Dön',
        'success' => 'Başarıyla giriş yapıldı.',
        'failed' => 'Geçersiz e-posta veya şifre.',
        'required' => 'Lütfen e-posta ve şifrenizi girin.',
        'error' => 'Giriş sırasında bir hata oluştu.',
        'social_only' => 'Bu hesap sosyal medya ile kayıt olmuştur. Lütfen sosyal medya ile giriş yapın.',
    ],

    // Register
    'register' => [
        'title' => 'Kayıt Ol',
        'description' => 'RezlyX ile rezervasyon yapmaya başlayın',
        'name' => 'Ad Soyad',
        'name_placeholder' => 'Ahmet Yılmaz',
        'email' => 'E-posta',
        'email_placeholder' => 'ornek@email.com',
        'phone' => 'Telefon',
        'phone_placeholder' => '0532 123 4567',
        'phone_hint' => 'Ülke kodunu seçin ve telefon numaranızı girin',
        'password' => 'Şifre',
        'password_placeholder' => 'En az 12 karakter',
        'password_hint' => 'En az 12 karakter, büyük/küçük harf, rakam ve özel karakter içermelidir',
        'password_confirm' => 'Şifre Tekrar',
        'password_confirm_placeholder' => 'Şifreyi tekrar girin',
        'agree_terms' => ' Kabul ediyorum',
        'agree_privacy' => ' Kabul ediyorum',
        'submit' => 'Kayıt Ol',
        'has_account' => 'Zaten hesabınız var mı?',
        'login_link' => 'Giriş Yap',
        'success' => 'Kayıt başarıyla tamamlandı.',
        'success_login' => 'Giriş Sayfasına Git',
        'email_exists' => 'Bu e-posta adresi zaten kayıtlı.',
        'error' => 'Kayıt sırasında bir hata oluştu.',
    ],

    // Forgot password
    'forgot' => [
        'title' => 'Şifremi Unuttum',
        'description' => 'E-posta adresinizi girin, size şifre sıfırlama bağlantısı göndereceğiz.',
        'email' => 'E-posta',
        'submit' => 'Sıfırlama Bağlantısı Gönder',
        'back_login' => 'Giriş Sayfasına Dön',
        'success' => 'Şifre sıfırlama bağlantısı e-posta adresinize gönderildi.',
        'not_found' => 'E-posta adresi bulunamadı.',
    ],

    // Reset password
    'reset' => [
        'title' => 'Şifre Sıfırla',
        'email' => 'E-posta',
        'password' => 'Yeni Şifre',
        'password_confirm' => 'Yeni Şifre Tekrar',
        'submit' => 'Şifreyi Sıfırla',
        'success' => 'Şifreniz sıfırlandı.',
        'invalid_token' => 'Geçersiz token.',
        'expired_token' => 'Token süresi dolmuş.',
    ],

    // Logout
    'logout' => [
        'success' => 'Başarıyla çıkış yapıldı.',
    ],

    // Email verification
    'verify' => [
        'title' => 'E-posta Doğrulama',
        'description' => 'E-posta adresinize bir doğrulama e-postası gönderdik. Lütfen e-postanızı kontrol edin.',
        'resend' => 'Doğrulama E-postasını Tekrar Gönder',
        'success' => 'E-posta başarıyla doğrulandı.',
        'already_verified' => 'E-posta zaten doğrulanmış.',
    ],

    // Social login
    'social' => [
        'or' => 'veya',
        'google' => 'Google ile Giriş Yap',
        'kakao' => 'Kakao ile Giriş Yap',
        'naver' => 'Naver ile Giriş Yap',
        'line' => 'LINE ile Giriş Yap',
    ],

    // Social login buttons
    'login_with_line' => 'LINE ile Giriş Yap',
    'login_with_google' => 'Google ile Giriş Yap',
    'login_with_kakao' => 'Kakao ile Giriş Yap',
    'login_with_naver' => 'Naver ile Giriş Yap',
    'login_with_apple' => 'Apple ile Giriş Yap',
    'login_with_facebook' => 'Facebook ile Giriş Yap',
    'or_continue_with' => 'veya',

    // Terms Agreement
    'terms' => [
        'title' => 'Şartlar ve Koşullar',
        'subtitle' => 'Hizmeti kullanmak için lütfen şartları kabul edin',
        'agree_all' => 'Tüm şartları kabul ediyorum',
        'required' => 'Zorunlu',
        'optional' => 'İsteğe Bağlı',
        'required_mark' => 'Zorunlu',
        'required_note' => '* zorunlu maddeleri belirtir',
        'required_alert' => 'Lütfen tüm zorunlu şartları kabul edin.',
        'notice' => 'Şartları kabul etmezseniz hizmeti kullanamayabilirsiniz.',
        'view_content' => 'İçeriği görüntüle',
        'hide_content' => 'İçeriği gizle',
        'translation_pending' => 'Çeviri devam ediyor',
    ],

    // My Page
    'mypage' => [
        'title' => 'Hesabım',
        'welcome' => 'Merhaba, :name!',
        'member_since' => ':date tarihinden beri üye',
        'menu' => [
            'dashboard' => 'Kontrol Paneli',
            'reservations' => 'Rezervasyonlar',
            'profile' => 'Profil',
            'services' => 'Hizmet Yönetimi',
            'settings' => 'Ayarlar',
            'password' => 'Şifre Değiştir',
            'withdraw' => 'Hesabı Sil',
            'logout' => 'Çıkış Yap',
        ],
        'stats' => [
            'total_reservations' => 'Toplam Rezervasyon',
            'upcoming' => 'Yaklaşan',
            'completed' => 'Tamamlanan',
            'cancelled' => 'İptal Edilen',
        ],
        'recent_reservations' => 'Son Rezervasyonlar',
        'no_reservations' => 'Rezervasyon bulunamadı.',
        'view_all' => 'Tümünü Görüntüle',
        'quick_actions' => 'Hızlı İşlemler',
        'make_reservation' => 'Rezervasyon Yap',
    ],

    // Profile
    'profile' => [
            'services' => 'Hizmet Yönetimi',
        'title' => 'Profil',
        'description' => 'Profil bilgilerim.',
        'edit_title' => 'Profili Düzenle',
        'edit_description' => 'Kişisel bilgilerinizi düzenleyin.',
        'edit_button' => 'Düzenle',
        'name' => 'Ad Soyad',
        'email' => 'E-posta',
        'email_hint' => 'E-posta değiştirilemez.',
        'phone' => 'Telefon',
        'not_set' => 'Ayarlanmamış',
        'submit' => 'Kaydet',
        'success' => 'Profil başarıyla güncellendi.',
        'error' => 'Profil güncellenirken bir hata oluştu.',
    ],

    // Settings
    'settings' => [
        'title' => 'Gizlilik Ayarları',
        'description' => 'Diğer kullanıcılara gösterilecek bilgileri seçin.',
        'info' => 'Devre dışı bırakılan öğeler diğer kullanıcılara görünmez. Ad her zaman görünür.',
        'success' => 'Ayarlar kaydedildi.',
        'error' => 'Ayarlar kaydedilirken hata oluştu.',
        'no_fields' => 'Yapılandırılabilir alan yok.',
        'fields' => [
            'email' => 'E-posta', 'email_desc' => 'E-posta adresinizi diğer kullanıcılara gösterin.',
            'profile_photo' => 'Profil Fotoğrafı', 'profile_photo_desc' => 'Profil fotoğrafınızı diğer kullanıcılara gösterin.',
            'phone' => 'Telefon', 'phone_desc' => 'Telefon numaranızı diğer kullanıcılara gösterin.',
            'birth_date' => 'Doğum Tarihi', 'birth_date_desc' => 'Doğum tarihinizi diğer kullanıcılara gösterin.',
            'gender' => 'Cinsiyet', 'gender_desc' => 'Cinsiyetinizi diğer kullanıcılara gösterin.',
            'company' => 'Şirket', 'company_desc' => 'Şirket bilginizi diğer kullanıcılara gösterin.',
            'blog' => 'Blog', 'blog_desc' => 'Blog URL\'nizi diğer kullanıcılara gösterin.',
        ],
    ],

    // Change Password
    'password_change' => [
        'title' => 'Şifre Değiştir',
        'description' => 'Güvenlik için lütfen şifrenizi düzenli olarak değiştirin.',
        'current' => 'Mevcut Şifre',
        'current_placeholder' => 'Mevcut şifrenizi girin',
        'new' => 'Yeni Şifre',
        'new_placeholder' => 'Yeni şifrenizi girin',
        'confirm' => 'Yeni Şifre Tekrar',
        'confirm_placeholder' => 'Yeni şifreyi tekrar girin',
        'submit' => 'Şifreyi Değiştir',
        'success' => 'Şifre başarıyla değiştirildi.',
        'error' => 'Şifre değiştirilirken bir hata oluştu.',
        'wrong_password' => 'Mevcut şifre yanlış.',
    ],

    // Hesabı Sil
    'withdraw' => [
        'title' => 'Hesabı Sil',
        'description' => 'Hesap silindiğinde kişisel bilgileriniz hemen anonimleştirilecektir. Bu işlem geri alınamaz.',
        'warning_title' => 'Devam etmeden önce lütfen dikkatlice okuyun',
        'warnings' => [
            'account' => 'Ad, e-posta, telefon numarası, doğum tarihi ve profil fotoğrafı dahil tüm kişisel bilgiler hemen anonimleştirilecektir. Kimliğiniz artık belirlenemeyecektir.',
            'reservation' => 'Aktif veya yaklaşan rezervasyonlarınız varsa, hesabınızı silmeden önce iptal edin. Silme işleminden sonra rezervasyonlar değiştirilemez veya iptal edilemez.',
            'payment' => 'Ödeme ve işlem kayıtları, ilgili vergi yasalarına göre (Kore: 5 yıl, Japonya: 7 yıl) anonimleştirilmiş biçimde yasal saklama süresi boyunca tutulacaktır.',
            'recovery' => 'Silinen hesaplar kurtarılamaz. Aynı e-posta ile tekrar kayıt olabilirsiniz, ancak önceki rezervasyonlar, puanlar ve mesajlar dahil tüm veriler geri yüklenmez.',
            'social' => 'Sosyal giriş (Google, Kakao, LINE vb.) ile kayıt olduysanız, o sosyal hizmetle bağlantı da kaldırılacaktır.',
            'message' => 'Alınan tüm mesajlar ve bildirim geçmişi kalıcı olarak silinecektir.',
        ],
        'retention_notice' => '※ Yürürlükteki yasalar tarafından gerekli kılınan işlem kayıtları, kimlik belirlenemez biçimde yasal süre boyunca saklanacak ve ardından tamamen silinecektir.',
        'reason' => 'Silme nedeni',
        'reason_placeholder' => 'Lütfen bir neden seçin',
        'reasons' => [
            'not_using' => 'Hizmeti artık kullanmıyorum',
            'other_service' => 'Başka bir hizmete geçiş',
            'dissatisfied' => 'Hizmetten memnun değilim',
            'privacy' => 'Gizlilik endişeleri',
            'too_many_emails' => 'Çok fazla e-posta/bildirim',
            'other' => 'Diğer',
        ],
        'reason_other' => 'Diğer neden',
        'reason_other_placeholder' => 'Lütfen nedeninizi girin',
        'password' => 'Şifreyi onayla',
        'password_placeholder' => 'Mevcut şifrenizi girin',
        'password_hint' => 'Kimliğinizi doğrulamak için mevcut şifrenizi girin.',
        'confirm_text' => 'Yukarıdaki tüm bilgileri okudum ve anladım, kişisel verilerimin anonimleştirilmesini ve hesabımın silinmesini kabul ediyorum.',
        'submit' => 'Hesabı Sil',
        'success' => 'Hesabınız silindi. Hizmetimizi kullandığınız için teşekkürler.',
        'wrong_password' => 'Şifre yanlış.',
        'error' => 'Hesap silinirken bir hata oluştu.',
        'confirm_required' => 'Lütfen onay kutusunu işaretleyin.',
    ],
];
