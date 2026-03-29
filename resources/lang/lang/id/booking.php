<?php

/**
 * Booking translations - Indonesian
 */

return [
    // Page titles
    'title' => 'Pesan Sekarang',
    'service_list' => 'Daftar Layanan',
    'select_service' => 'Pilih Layanan',
    'select_date' => 'Pilih Tanggal',
    'select_time' => 'Pilih Waktu',
    'enter_info' => 'Masukkan Informasi',
    'confirm_booking' => 'Konfirmasi Pemesanan',
    'confirm_info' => 'Silakan konfirmasi informasi pemesanan Anda',
    'complete_booking' => 'Selesaikan Pemesanan',
    'select_service_datetime' => 'Silakan pilih layanan dan tanggal/waktu yang diinginkan',
    'staff_designation_guide' => 'Untuk pemesanan dengan staf tertentu, silakan lanjutkan dari halaman staf',
    'go_staff_booking' => 'Pemesanan Staf Tertentu',
    'select_datetime' => 'Silakan pilih tanggal dan waktu',
    'no_services' => 'Tidak ada layanan tersedia saat ini.',
    'contact_admin' => 'Silakan hubungi administrator.',
    'notes' => 'Permintaan Khusus',
    'notes_placeholder' => 'Masukkan permintaan khusus',
    'customer' => 'Pelanggan',
    'phone' => 'Telepon',
    'date_label' => 'Tanggal',
    'time_label' => 'Waktu',
    'total_price' => 'Total Jumlah',
    'cancel_policy' => 'Pembatalan diperbolehkan hingga 24 jam sebelum waktu reservasi. Biaya pembatalan mungkin berlaku untuk pembatalan lebih lambat.',
    'success' => 'Reservasi selesai!',
    'success_desc' => 'Konfirmasi akan dikirim. Silakan simpan nomor reservasi Anda.',
    'submitting' => 'Memproses...',
    'select_staff' => 'Pilih staf',
    'no_preference' => 'Tidak ada preferensi',
    'staff' => 'Staf',
    'designation_fee' => 'Biaya penunjukan',
    'designation_fee_badge' => '+:amount',
    'loading_slots' => 'Memeriksa waktu tersedia...',
    'no_available_slots' => 'Tidak ada waktu tersedia pada tanggal yang dipilih.',
    'items_selected' => 'dipilih',
    'total_duration' => 'Total durasi',

    // Steps
    'step' => [
        'service' => 'Pilih Layanan',
        'datetime' => 'Tanggal/Waktu',
        'info' => 'Informasi',
        'confirm' => 'Konfirmasi',
    ],

    // Service
    'service' => [
        'title' => 'Layanan',
        'name' => 'Nama Layanan',
        'description' => 'Deskripsi',
        'duration' => 'Durasi',
        'price' => 'Harga',
        'category' => 'Kategori',
        'select' => 'Pilih',
        'view_detail' => 'Lihat Detail',
        'no_services' => 'Tidak ada layanan tersedia.',
    ],

    // Date/Time
    'date' => [
        'title' => 'Tanggal Pemesanan',
        'select_date' => 'Silakan pilih tanggal',
        'available' => 'Tersedia',
        'unavailable' => 'Tidak Tersedia',
        'fully_booked' => 'Penuh',
        'past_date' => 'Tanggal Lewat',
    ],

    'time' => [
        'title' => 'Waktu Pemesanan',
        'select_time' => 'Silakan pilih waktu',
        'available_slots' => 'Slot Waktu Tersedia',
        'no_slots' => 'Tidak ada slot waktu tersedia.',
        'remaining' => ':count tempat tersisa',
    ],

    // Booking form
    'form' => [
        'customer_name' => 'Nama',
        'customer_email' => 'Email',
        'customer_phone' => 'Telepon',
        'guests' => 'Jumlah Tamu',
        'notes' => 'Permintaan Khusus',
        'notes_placeholder' => 'Masukkan permintaan khusus',
    ],

    // Confirmation
    'confirm' => [
        'title' => 'Konfirmasi Pemesanan',
        'summary' => 'Ringkasan Pemesanan',
        'service_info' => 'Informasi Layanan',
        'booking_info' => 'Informasi Pemesanan',
        'customer_info' => 'Informasi Pelanggan',
        'total_price' => 'Total',
        'agree_terms' => 'Saya setuju dengan syarat pemesanan',
        'submit' => 'Selesaikan Pemesanan',
    ],

    // Complete
    'complete' => [
        'title' => 'Pemesanan Selesai',
        'success' => 'Pemesanan Anda telah selesai!',
        'booking_code' => 'Kode Pemesanan',
        'check_email' => 'Email konfirmasi telah dikirim ke alamat email Anda.',
        'view_detail' => 'Lihat Detail Pemesanan',
        'book_another' => 'Buat Pemesanan Lain',
    ],

    // Lookup
    'lookup' => [
        'title' => 'Cari Pemesanan',
        'description' => 'Masukkan informasi pemesanan Anda untuk menemukan reservasi Anda.',
        'booking_code' => 'Kode Pemesanan',
        'booking_code_placeholder' => 'RZ250301XXXXXX',
        'email' => 'Email',
        'email_placeholder' => 'Email yang digunakan untuk pemesanan',
        'phone' => 'Nomor Telepon',
        'phone_placeholder' => 'Nomor telepon yang digunakan untuk pemesanan',
        'search' => 'Cari',
        'search_method' => 'Metode Pencarian',
        'by_code' => 'Cari berdasarkan Kode Pemesanan',
        'by_email' => 'Cari berdasarkan Email',
        'by_phone' => 'Cari berdasarkan Telepon',
        'not_found' => 'Pemesanan tidak ditemukan. Silakan periksa informasi Anda.',
        'input_required' => 'Silakan masukkan kode pemesanan dan email atau nomor telepon.',
        'result_title' => 'Hasil Pencarian',
        'multiple_results' => ':count pemesanan ditemukan.',
        'hint' => 'Untuk hasil yang akurat, masukkan kode pemesanan bersama dengan email atau nomor telepon Anda.',
        'help_text' => 'Tidak dapat menemukan pemesanan Anda?',
        'contact_support' => 'Hubungi Dukungan',
    ],

    // Detail
    'detail' => [
        'title' => 'Detail Pemesanan',
        'status' => 'Status',
        'booking_date' => 'Tanggal & Waktu',
        'service' => 'Layanan',
        'services' => 'Layanan',
        'guests' => 'Tamu',
        'total_price' => 'Total Harga',
        'payment_status' => 'Status Pembayaran',
        'notes' => 'Permintaan Khusus',
        'created_at' => 'Dipesan Pada',
        'duration_unit' => 'menit',
        'staff_not_assigned' => 'Belum ditentukan',
        'back_to_lookup' => 'Pencarian Pemesanan',
        'payment' => 'Detail Pembayaran',
        'total' => 'Subtotal',
        'discount' => 'Diskon',
        'points_used' => 'Poin yang Digunakan',
        'final_amount' => 'Jumlah Akhir',
        'staff' => 'Staf',
        'designation_fee' => 'Biaya Penunjukan',
        'cancel_info' => 'Detail Pembatalan',
        'cancelled_at' => 'Dibatalkan Pada',
        'cancel_reason' => 'Alasan Pembatalan',
    ],

    // Cancel
    'cancel' => [
        'title' => 'Batalkan Pemesanan',
        'confirm' => 'Apakah Anda yakin ingin membatalkan pemesanan ini?',
        'reason' => 'Alasan Pembatalan',
        'reason_placeholder' => 'Silakan masukkan alasan pembatalan',
        'submit' => 'Batalkan Pemesanan',
        'success' => 'Pemesanan Anda telah dibatalkan.',
        'cannot_cancel' => 'Pemesanan ini tidak dapat dibatalkan.',
    ],

    // Status messages
    'status' => [
        'pending' => 'Pemesanan Anda telah diterima. Silakan tunggu konfirmasi.',
        'confirmed' => 'Pemesanan Anda telah dikonfirmasi.',
        'cancelled' => 'Pemesanan Anda telah dibatalkan.',
        'completed' => 'Layanan selesai.',
        'no_show' => 'Ditandai sebagai tidak hadir.',
    ],

    // Payment status
    'payment' => [
        'unpaid' => 'Belum dibayar',
        'paid' => 'Sudah dibayar',
        'partial' => 'Dibayar sebagian',
        'refunded' => 'Dikembalikan',
    ],

    // Error messages
    'error' => [
        'service_not_found' => 'Layanan tidak ditemukan.',
        'slot_unavailable' => 'Slot waktu yang dipilih tidak tersedia.',
        'past_date' => 'Tidak dapat memesan untuk tanggal yang sudah lewat.',
        'max_capacity' => 'Kapasitas maksimum terlampaui.',
        'booking_failed' => 'Terjadi kesalahan saat memproses pemesanan Anda.',
        'required_fields' => 'Silakan masukkan nama dan informasi kontak Anda.',
        'invalid_service' => 'Layanan tidak valid.',
    ],

    'member_discount' => 'Diskon Anggota',
    'use_points' => 'Gunakan Poin',
    'points_balance' => 'Saldo',
    'use_all' => 'Gunakan Semua',
    'points_default_name' => 'Poin',
    'deposit_pay_now' => 'Deposit (Bayar Sekarang)',
    'deposit_remaining_later' => 'Sisa saldo akan ditagih saat layanan',
    'next' => 'Selanjutnya',
    'categories' => 'kategori',
    'service_count' => 'layanan',
    'expected_points' => 'Poin diperkirakan',
    'reservation_complete' => 'Reservasi selesai',
    'reservation_complete_desc' => 'Silakan periksa detail reservasi',
    'reservation_number' => 'No. Reservasi',
    'check_summary' => 'Lihat detail',
];
