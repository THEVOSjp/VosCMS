<?php
/**
 * Compliance (Data Management Guide) - Indonesian
 */
return [
    'data_type' => [
        'reservation' => 'Informasi reservasi (nama, kontak, tanggal/waktu)',
        'payment' => 'Bukti pembayaran / slip penjualan',
        'cash_receipt' => 'Catatan penerbitan tanda terima tunai',
        'customer_card' => 'Kartu pelanggan (riwayat perawatan, dll.)',
        'medical_record' => 'Rekam medis',
        'treatment_history' => 'Riwayat perawatan (bahan kimia, alergi, dll.)',
        'allergy_info' => 'Informasi alergi',
        'guest_register' => 'Buku tamu',
        'receipt' => 'Tanda terima / voucher',
        'karte' => 'Kartu klinis pelanggan (perawatan, bahan kimia, alergi)',
        'allergy_health' => 'Informasi kesehatan / alergi',
    ],
    'kr' => [
        'law' => [
            'privacy' => 'Undang-Undang Perlindungan Informasi Pribadi (개인정보보호법)',
            'vat' => 'Undang-Undang Pajak Pertambahan Nilai (부가가치세법)',
            'income_tax' => 'Undang-Undang Pajak Penghasilan (소득세법)',
            'electronic_commerce' => 'Undang-Undang Perdagangan Elektronik (전자상거래법)',
            'medical' => 'Undang-Undang Layanan Medis (의료법)',
            'tourism' => 'Undang-Undang Promosi Pariwisata (관광진흥법)',
        ],
        'retention' => [
            'after_purpose' => 'Hapus segera setelah tujuan tercapai',
            '5years' => '5 tahun',
            '10years' => '10 tahun',
            '3years' => '3 tahun',
            'consent_period' => 'Periode yang ditentukan dalam persetujuan eksplisit',
        ],
        'note' => [
            'reservation' => 'Kelola informasi pelanggan yang telah selesai secara terpisah dari catatan pajak',
            'payment' => 'Harus disimpan untuk keperluan dokumentasi penjualan',
            'customer_card' => 'Diperlukan formulir persetujuan untuk pengumpulan data pribadi; periode penyimpanan harus ditentukan',
            'medical_record' => 'Rekam medis harus disimpan berdasarkan Undang-Undang Layanan Medis',
            'treatment_history' => 'Formulir persetujuan diperlukan jika digunakan untuk manajemen pelanggan tetap',
            'allergy_info' => 'Informasi terkait kesehatan memerlukan penanganan khusus',
            'guest_register' => 'Penyimpanan diperlukan berdasarkan undang-undang bisnis akomodasi',
        ],
        'tip' => [
            'purpose_delete' => 'Menyimpan data pelanggan lebih lama dari yang diperlukan setelah kunjungan dapat merupakan pelanggaran hukum.',
            'tax_separate' => 'Catatan pajak (dokumentasi penjualan) dan data pribadi pelanggan harus dikelola secara terpisah.',
            'platform_booking' => 'Untuk pemesanan platform (mis. Naver, Kakao), data yang dikelola platform dan data yang dikelola sendiri adalah terpisah.',
            'beauty_consent' => 'Jika memelihara kartu pelanggan untuk manajemen pelanggan tetap, Anda harus mendapatkan persetujuan dan menentukan periode penyimpanan.',
            'medical_strict' => 'Rekam medis harus disimpan secara ketat berdasarkan Undang-Undang Layanan Medis, dengan kewajiban penyimpanan minimum 10 tahun.',
            'food_allergy' => 'Informasi alergi pelanggan diklasifikasikan sebagai data sensitif dan memerlukan pengelolaan yang lebih ketat.',
        ],
        'ref' => [
            'pipc' => 'Komisi Perlindungan Informasi Pribadi (PIPC)',
            'law' => 'Institut Penelitian Legislasi Korea',
        ],
    ],
    'jp' => [
        'law' => [
            'privacy' => 'Undang-Undang Perlindungan Informasi Pribadi (個人情報保護法)',
            'corporate_tax' => 'Undang-Undang Pajak Korporasi (法人税法)',
            'medical_practitioners' => 'Undang-Undang Praktisi Medis (医師法)',
            'food_sanitation' => 'Undang-Undang Sanitasi Makanan (食品衛生法)',
            'inn_act' => 'Undang-Undang Bisnis Penginapan (旅館業法)',
        ],
        'retention' => [
            'after_purpose' => 'Hapus setelah tujuan tercapai',
            '7years' => '7 tahun',
            '5years' => '5 tahun',
            '3years' => '3 tahun',
            'consent_period' => 'Periode yang ditentukan dalam persetujuan eksplisit',
            'careful' => 'Pengelolaan ketat sebagai informasi pribadi sensitif',
        ],
        'note' => [
            'reservation' => 'Penghapusan dini informasi pribadi setelah reservasi selesai adalah prinsipnya',
            'payment' => 'Kewajiban penyimpanan sebagai buku terkait penjualan',
            'customer_card' => 'Diperlukan persetujuan untuk penanganan informasi pribadi',
            'medical_record' => 'Rekam medis harus disimpan berdasarkan Undang-Undang Praktisi Medis',
            'karte' => 'Kartu klinis (riwayat perawatan, informasi bahan kimia, alergi) memiliki sifat kuasi-medis dan memerlukan pengelolaan yang hati-hati',
            'sensitive_info' => 'Memerlukan penanganan yang sangat ketat sebagai informasi pribadi sensitif (data kesehatan)',
            'food_allergy' => 'Informasi alergi harus dikelola dengan hati-hati sebagai data terkait kesehatan',
            'guest_register' => 'Kewajiban penyimpanan buku tamu berdasarkan Undang-Undang Bisnis Penginapan',
        ],
        'tip' => [
            'purpose_delete' => 'Menyimpan data pelanggan lebih lama dari yang diperlukan setelah reservasi dapat merupakan pelanggaran hukum.',
            'tax_separate' => 'Catatan pajak (dokumentasi penjualan) dan data pribadi pelanggan harus dikelola secara terpisah.',
            'karte_caution' => 'Kartu klinis pelanggan (riwayat perawatan, informasi bahan kimia, alergi) memiliki sifat kuasi-medis dan memerlukan pengelolaan yang lebih hati-hati.',
            'sensitive_info' => 'Informasi terkait kesehatan seperti alergi obat diklasifikasikan sebagai informasi pribadi sensitif dan memerlukan penanganan yang lebih ketat.',
            'medical_strict' => 'Rekam medis harus disimpan secara ketat berdasarkan Undang-Undang Praktisi Medis, dengan kewajiban penyimpanan minimum 5 tahun.',
        ],
        'ref' => [
            'ppc' => 'Komisi Perlindungan Informasi Pribadi (PPC)',
            'e_gov' => 'Pencarian Hukum dan Peraturan e-Gov',
        ],
    ],
    'default' => [
        'retention' => [
            'check_local' => 'Periksa persyaratan hukum setempat',
        ],
        'basis' => [
            'local_privacy' => 'Undang-undang perlindungan privasi setempat',
            'local_tax' => 'Undang-undang pajak setempat',
        ],
        'tip' => [
            'check_local_law' => 'Pastikan untuk memeriksa undang-undang perlindungan privasi dan penyimpanan data di negara Anda.',
            'minimize_data' => 'Minimalkan data pribadi yang Anda kumpulkan dan hapus informasi yang tidak diperlukan segera.',
            'get_consent' => 'Selalu dapatkan persetujuan saat mengumpulkan data pelanggan dan tentukan periode penyimpanan.',
        ],
    ],
];
