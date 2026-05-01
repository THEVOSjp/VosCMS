<?php
/**
 * Contact 페이지 기본 위젯 시드 (vos.21ces.com 의 dev 데이터 기반).
 * install-core.php Step 4 에서 contact page 생성 후 적용.
 * 호스팅 고객은 admin 에서 본인 회사 정보로 수정 가능.
 */

return array (
  0 => 
  array (
    'widget_slug' => 'location-map',
    'sort_order' => 0,
    'config' => 
    array (
      'map_query' => '福岡市博多区古門戸町10番5-501号',
      'map_width' => 'full',
      'map_height' => '400',
      'company_name' => 
      array (
        'ko' => '株式会社ザボスECO研究所',
        'en' => 'THEVOS ECO Research Institute, Inc.',
        'ja' => '株式会社ザボスECO研究所',
        'zh_CN' => '株式会社ザボスECO研究所',
        'zh_TW' => '株式會社ザボスECO研究所',
        'de' => 'THEVOS ECO Research Institute, Inc.',
        'es' => 'THEVOS ECO Research Institute, Inc.',
        'fr' => 'THEVOS ECO Research Institute, Inc.',
        'id' => 'THEVOS ECO Research Institute, Inc.',
        'mn' => 'THEVOS ECO Research Institute, Inc.',
        'ru' => 'THEVOS ECO Research Institute, Inc.',
        'tr' => 'THEVOS ECO Research Institute, Inc.',
        'vi' => 'THEVOS ECO Research Institute, Inc.',
      ),
      'address' => 
      array (
        'ko' => '〒812-0029
후쿠오카시 하카타구
코몬도마치 10번 5-501호',
        'en' => '10-5-501, Komondomachi
Hakata-ku, Fukuoka-shi
812-0029, Japan',
        'ja' => '〒812-0029
福岡市博多区
古門戸町10番5-501号',
        'zh_CN' => '〒812-0029
日本福冈市博多区
古门户町10番5-501号',
        'zh_TW' => '〒812-0029
日本福岡市博多區
古門戶町10番5-501號',
        'de' => '10-5-501, Komondomachi
Hakata-ku, Fukuoka-shi
812-0029, Japan',
        'es' => '10-5-501, Komondomachi
Hakata-ku, Fukuoka-shi
812-0029, Japon',
        'fr' => '10-5-501, Komondomachi
Hakata-ku, Fukuoka-shi
812-0029, Japon',
        'id' => '10-5-501, Komondomachi
Hakata-ku, Fukuoka-shi
812-0029, Jepang',
        'mn' => '10-5-501, Komondomachi
Hakata-ku, Fukuoka-shi
812-0029, Japon',
        'ru' => '10-5-501, Komondomachi
Hakata-ku, Fukuoka-shi
812-0029, Japonija',
        'tr' => '10-5-501, Komondomachi
Hakata-ku, Fukuoka-shi
812-0029, Japonya',
        'vi' => '10-5-501, Komondomachi
Hakata-ku, Fukuoka-shi
812-0029, Nhat Ban',
      ),
      'phone' => '',
      'email' => 'webmaster@thevos.jp',
      'business_hours' => 
      array (
        'ko' => '월~금: 9:00 - 18:00
토·일·공휴일: 휴무',
        'en' => 'Mon-Fri: 9:00 AM - 6:00 PM
Sat, Sun & Holidays: Closed',
        'ja' => '月〜金: 9:00 - 18:00
土・日・祝日: 休業',
        'zh_CN' => '周一至周五: 9:00 - 18:00
周六、日及节假日: 休息',
        'zh_TW' => '週一至週五: 9:00 - 18:00
週六、日及假日: 休息',
        'de' => 'Mo-Fr: 9:00 - 18:00
Sa, So & Feiertage: Geschlossen',
        'es' => 'Lun-Vie: 9:00 - 18:00
Sab, Dom y festivos: Cerrado',
        'fr' => 'Lun-Ven: 9:00 - 18:00
Sam, Dim et jours feries: Ferme',
        'id' => 'Sen-Jum: 9:00 - 18:00
Sab, Min & Hari Libur: Tutup',
        'mn' => 'Да-Ба: 9:00 - 18:00
Бя, Ня & Баяр: Амарна',
        'ru' => 'Пн-Пт: 9:00 - 18:00
Сб, Вс и праздники: Выходной',
        'tr' => 'Pzt-Cum: 9:00 - 18:00
Cts, Paz & Tatil: Kapali',
        'vi' => 'T2-T6: 9:00 - 18:00
T7, CN & Ngay le: Nghi',
      ),
      'bg_color' => 'transparent',
      'service_items' => 
      array (
      ),
      'items' => 
      array (
      ),
      'cells' => 
      array (
      ),
    ),
  ),
  1 => 
  array (
    'widget_slug' => 'contact-form',
    'sort_order' => 1,
    'config' => 
    array (
      'title' => 
      array (
        'ko' => '문의하기',
        'en' => 'Contact Us',
        'ja' => 'お問い合わせ',
        'zh_CN' => '联系我们',
        'zh_TW' => '聯繫我們',
        'de' => 'Kontakt',
        'es' => 'Contáctenos',
        'fr' => 'Nous contacter',
        'id' => 'Hubungi Kami',
        'mn' => 'Холбоо барих',
        'ru' => 'Связаться с нами',
        'tr' => 'Bize Ulaşın',
        'vi' => 'Liên hệ',
      ),
      'subtitle' => 
      array (
        'ko' => '궁금한 점이나 제안이 있으시면 언제든 문의해 주세요.',
        'en' => 'Feel free to reach out with any questions or suggestions.',
        'ja' => 'ご質問やご提案がございましたら、お気軽にお問い合わせください。',
        'zh_CN' => '如有任何疑问或建议，请随时与我们联系。',
        'zh_TW' => '如有任何疑問或建議，請隨時與我們聯繫。',
        'de' => 'Kontaktieren Sie uns bei Fragen oder Anregungen.',
        'es' => 'No dude en contactarnos.',
        'fr' => 'Contactez-nous pour toute question.',
        'id' => 'Jangan ragu untuk menghubungi kami.',
        'mn' => 'Асуулт байвал холбогдоно уу.',
        'ru' => 'Свяжитесь с нами.',
        'tr' => 'Bize ulasin.',
        'vi' => 'Lien he voi chung toi.',
      ),
      'show_category' => 1,
      'receive_email' => 'webmaster@thevos.jp',
      'bg_color' => 'transparent',
      'categories' => 
      array (
        'ko' => '일반 문의
사업 제안·제휴
라이선스 문의
버그 리포트
보안 취약점 신고
기타',
        'en' => 'General Inquiry
Business / Partnership
License Inquiry
Bug Report
Security Vulnerability
Other',
        'ja' => '一般的なお問い合わせ
ビジネス提案・提携
ライセンスに関するお問い合わせ
バグ報告
セキュリティ脆弱性の報告
その他',
        'zh_CN' => '一般咨询
商务合作
许可证咨询
错误报告
安全漏洞报告
其他',
        'zh_TW' => '一般諮詢
商務合作
授權諮詢
錯誤回報
安全漏洞回報
其他',
        'de' => 'Allgemeine Anfrage
Partnerschaft
Lizenzanfrage
Fehlerbericht
Sicherheitsmeldung
Sonstiges',
        'es' => 'Consulta general
Propuesta comercial
Licencia
Error
Seguridad
Otro',
        'fr' => 'Demande generale
Proposition commerciale
Licence
Rapport de bug
Securite
Autre',
        'id' => 'Pertanyaan Umum
Proposal Bisnis
Lisensi
Laporan Bug
Keamanan
Lainnya',
        'mn' => 'Ерөнхий
Бизнес санал
Лиценз
Алдаа
Аюулгүй байдал
Бусад',
        'ru' => 'Общий вопрос
Деловое предложение
Лицензия
Ошибка
Безопасность
Другое',
        'tr' => 'Genel Soru
Is Teklifi
Lisans
Hata
Guvenlik
Diger',
        'vi' => 'Cau hoi chung
Hop tac
Giay phep
Bao loi
Bao mat
Khac',
      ),
      'service_items' => 
      array (
      ),
      'items' => 
      array (
      ),
      'cells' => 
      array (
      ),
    ),
  ),
);
