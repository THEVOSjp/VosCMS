<?php

/**
 * Authentication translations - Indonesian
 */

return [
    // Login
    'login' => [
        'title' => 'Masuk',
        'description' => 'Masuk ke akun Anda untuk mengelola reservasi',
        'email' => 'Email',
        'email_placeholder' => 'contoh@email.com',
        'password' => 'Kata Sandi',
        'password_placeholder' => '••••••••',
        'remember' => 'Ingat saya',
        'forgot' => 'Lupa kata sandi?',
        'submit' => 'Masuk',
        'no_account' => 'Belum punya akun?',
        'register_link' => 'Daftar',
        'back_home' => '← Kembali ke Beranda',
        'success' => 'Berhasil masuk.',
        'failed' => 'Email atau kata sandi tidak valid.',
        'required' => 'Silakan masukkan email dan kata sandi Anda.',
        'error' => 'Terjadi kesalahan saat login.',
        'social_only' => 'Akun ini terdaftar melalui login sosial. Silakan gunakan login sosial.',
    ],

    // Register
    'register' => [
        'title' => 'Daftar',
        'description' => 'Mulai membuat reservasi dengan RezlyX',
        'name' => 'Nama',
        'name_placeholder' => 'John Doe',
        'email' => 'Email',
        'phone' => 'Telepon',
        'phone_placeholder' => '081-234-567-890',
        'phone_hint' => 'Pilih kode negara dan masukkan nomor telepon Anda',
        'password' => 'Kata Sandi',
        'password_placeholder' => 'Minimal 12 karakter',
        'password_hint' => 'Min 12 karakter dengan huruf besar, huruf kecil, angka & karakter khusus',
        'password_confirm' => 'Konfirmasi Kata Sandi',
        'password_confirm_placeholder' => 'Masukkan ulang kata sandi',
        'agree_terms' => ' Saya setuju',
        'agree_privacy' => ' Saya setuju',
        'submit' => 'Daftar',
        'has_account' => 'Sudah punya akun?',
        'login_link' => 'Masuk',
        'success' => 'Pendaftaran berhasil diselesaikan.',
        'success_login' => 'Pergi ke Login',
        'email_exists' => 'Email ini sudah terdaftar.',
        'error' => 'Terjadi kesalahan saat pendaftaran.',
    ],

    // Forgot password
    'forgot' => [
        'title' => 'Lupa Kata Sandi',
        'description' => 'Masukkan alamat email Anda dan kami akan mengirimkan tautan reset kata sandi.',
        'email' => 'Email',
        'submit' => 'Kirim Tautan Reset',
        'back_login' => 'Kembali ke Login',
        'success' => 'Tautan reset kata sandi telah dikirim ke email Anda.',
        'not_found' => 'Alamat email tidak ditemukan.',
    ],

    // Reset password
    'reset' => [
        'title' => 'Reset Kata Sandi',
        'email' => 'Email',
        'password' => 'Kata Sandi Baru',
        'password_confirm' => 'Konfirmasi Kata Sandi Baru',
        'submit' => 'Reset Kata Sandi',
        'success' => 'Kata sandi Anda telah direset.',
        'invalid_token' => 'Token tidak valid.',
        'expired_token' => 'Token telah kedaluwarsa.',
    ],

    // Logout
    'logout' => [
        'success' => 'Berhasil keluar.',
    ],

    // Email verification
    'verify' => [
        'title' => 'Verifikasi Email',
        'description' => 'Kami telah mengirim email verifikasi ke alamat Anda. Silakan periksa email Anda.',
        'resend' => 'Kirim Ulang Email Verifikasi',
        'success' => 'Email berhasil diverifikasi.',
        'already_verified' => 'Email sudah diverifikasi.',
    ],

    // Social login
    'social' => [
        'or' => 'atau',
        'google' => 'Masuk dengan Google',
        'kakao' => 'Masuk dengan Kakao',
        'naver' => 'Masuk dengan Naver',
        'line' => 'Masuk dengan LINE',
    ],

    // Social login buttons
    'login_with_line' => 'Masuk dengan LINE',
    'login_with_google' => 'Masuk dengan Google',
    'login_with_kakao' => 'Masuk dengan Kakao',
    'login_with_naver' => 'Masuk dengan Naver',
    'login_with_apple' => 'Masuk dengan Apple',
    'login_with_facebook' => 'Masuk dengan Facebook',
    'or_continue_with' => 'atau',

    // Terms Agreement
    'terms' => [
        'title' => 'Persetujuan Syarat',
        'subtitle' => 'Silakan setujui syarat untuk menggunakan layanan',
        'agree_all' => 'Saya setuju dengan semua syarat',
        'required' => 'Wajib',
        'optional' => 'Opsional',
        'required_mark' => 'Wajib',
        'required_note' => '* menunjukkan item wajib',
        'required_alert' => 'Silakan setujui semua syarat wajib.',
        'notice' => 'Anda mungkin tidak dapat menggunakan layanan jika tidak menyetujui syarat.',
        'view_content' => 'Lihat konten',
        'hide_content' => 'Sembunyikan konten',
        'translation_pending' => 'Terjemahan sedang berlangsung',
    ],

    // My Page
    'mypage' => [
        'title' => 'Halaman Saya',
        'welcome' => 'Halo, :name!',
        'member_since' => 'Anggota sejak :date',
        'menu' => [
            'dashboard' => 'Dasbor',
            'reservations' => 'Reservasi',
            'profile' => 'Profil',
            'settings' => 'Pengaturan',
            'password' => 'Ubah Kata Sandi',
            'withdraw' => 'Hapus Akun',
            'logout' => 'Keluar',
        ],
        'stats' => [
            'total_reservations' => 'Total Reservasi',
            'upcoming' => 'Akan Datang',
            'completed' => 'Selesai',
            'cancelled' => 'Dibatalkan',
        ],
        'recent_reservations' => 'Reservasi Terbaru',
        'no_reservations' => 'Tidak ada reservasi ditemukan.',
        'view_all' => 'Lihat Semua',
        'quick_actions' => 'Aksi Cepat',
        'make_reservation' => 'Buat Reservasi',
    ],

    // Profile
    'profile' => [
        'title' => 'Profil',
        'description' => 'Informasi profil saya.',
        'edit_title' => 'Edit Profil',
        'edit_description' => 'Edit informasi pribadi Anda.',
        'edit_button' => 'Edit',
        'name' => 'Nama',
        'email' => 'Email',
        'email_hint' => 'Email tidak dapat diubah.',
        'phone' => 'Telepon',
        'not_set' => 'Belum diatur',
        'submit' => 'Simpan',
        'success' => 'Profil berhasil diperbarui.',
        'error' => 'Terjadi kesalahan saat memperbarui profil.',
    ],

    // Settings
    'settings' => [
        'title' => 'Pengaturan Privasi',
        'description' => 'Pilih informasi yang ditampilkan kepada pengguna lain.',
        'info' => 'Item yang dinonaktifkan tidak akan terlihat oleh pengguna lain. Nama selalu ditampilkan.',
        'success' => 'Pengaturan disimpan.',
        'error' => 'Terjadi kesalahan saat menyimpan pengaturan.',
        'no_fields' => 'Tidak ada bidang yang dapat dikonfigurasi.',
        'fields' => [
            'email' => 'Email', 'email_desc' => 'Tampilkan alamat email Anda kepada pengguna lain.',
            'profile_photo' => 'Foto Profil', 'profile_photo_desc' => 'Tampilkan foto profil kepada pengguna lain.',
            'phone' => 'Nomor Telepon', 'phone_desc' => 'Tampilkan nomor telepon kepada pengguna lain.',
            'birth_date' => 'Tanggal Lahir', 'birth_date_desc' => 'Tampilkan tanggal lahir kepada pengguna lain.',
            'gender' => 'Jenis Kelamin', 'gender_desc' => 'Tampilkan jenis kelamin kepada pengguna lain.',
            'company' => 'Perusahaan', 'company_desc' => 'Tampilkan perusahaan kepada pengguna lain.',
            'blog' => 'Blog', 'blog_desc' => 'Tampilkan URL blog kepada pengguna lain.',
        ],
    ],

    // Change Password
    'password_change' => [
        'title' => 'Ubah Kata Sandi',
        'description' => 'Silakan ubah kata sandi Anda secara berkala untuk keamanan.',
        'current' => 'Kata Sandi Saat Ini',
        'current_placeholder' => 'Masukkan kata sandi saat ini',
        'new' => 'Kata Sandi Baru',
        'new_placeholder' => 'Masukkan kata sandi baru',
        'confirm' => 'Konfirmasi Kata Sandi Baru',
        'confirm_placeholder' => 'Masukkan ulang kata sandi baru',
        'submit' => 'Ubah Kata Sandi',
        'success' => 'Kata sandi berhasil diubah.',
        'error' => 'Terjadi kesalahan saat mengubah kata sandi.',
        'wrong_password' => 'Kata sandi saat ini salah.',
    ],

    // Hapus Akun
    'withdraw' => [
        'title' => 'Hapus Akun',
        'description' => 'Informasi pribadi Anda akan segera dianonimkan saat penghapusan akun. Tindakan ini tidak dapat dibatalkan.',
        'warning_title' => 'Harap baca dengan teliti sebelum melanjutkan',
        'warnings' => [
            'account' => 'Semua informasi pribadi termasuk nama, email, nomor telepon, tanggal lahir, dan foto profil akan segera dianonimkan. Identifikasi Anda tidak lagi memungkinkan.',
            'reservation' => 'Jika Anda memiliki reservasi aktif atau yang akan datang, harap batalkan sebelum menghapus akun. Setelah penghapusan, reservasi tidak dapat diubah atau dibatalkan.',
            'payment' => 'Catatan pembayaran dan transaksi akan disimpan dalam bentuk anonim selama periode penyimpanan yang diwajibkan secara hukum (5 tahun menurut undang-undang pajak Korea, 7 tahun menurut undang-undang pajak Jepang).',
            'recovery' => 'Akun yang dihapus tidak dapat dipulihkan. Anda dapat mendaftar ulang dengan email yang sama, tetapi semua data sebelumnya termasuk reservasi, poin, dan pesan tidak akan dipulihkan.',
            'social' => 'Jika Anda mendaftar melalui login sosial (Google, Kakao, LINE, dll.), koneksi ke layanan sosial tersebut juga akan dihapus.',
            'message' => 'Semua pesan yang diterima dan riwayat notifikasi akan dihapus secara permanen.',
        ],
        'retention_notice' => '※ Catatan transaksi yang diwajibkan oleh hukum yang berlaku akan disimpan dalam bentuk yang tidak dapat diidentifikasi selama periode hukum, kemudian dihapus secara permanen.',
        'reason' => 'Alasan penghapusan',
        'reason_placeholder' => 'Silakan pilih alasan',
        'reasons' => [
            'not_using' => 'Tidak lagi menggunakan layanan',
            'other_service' => 'Beralih ke layanan lain',
            'dissatisfied' => 'Tidak puas dengan layanan',
            'privacy' => 'Kekhawatiran privasi',
            'too_many_emails' => 'Terlalu banyak email/notifikasi',
            'other' => 'Lainnya',
        ],
        'reason_other' => 'Alasan lainnya',
        'reason_other_placeholder' => 'Silakan masukkan alasan Anda',
        'password' => 'Konfirmasi kata sandi',
        'password_placeholder' => 'Masukkan kata sandi saat ini',
        'password_hint' => 'Masukkan kata sandi saat ini untuk verifikasi identitas.',
        'confirm_text' => 'Saya telah membaca dan memahami semua informasi di atas, dan setuju dengan anonimisasi data pribadi serta penghapusan akun.',
        'submit' => 'Hapus Akun',
        'success' => 'Akun Anda telah dihapus. Terima kasih telah menggunakan layanan kami.',
        'wrong_password' => 'Kata sandi salah.',
        'error' => 'Terjadi kesalahan saat menghapus akun.',
        'confirm_required' => 'Silakan centang persetujuan.',
    ],
];
