<?php
/**
 * 미용실 기본 서비스 시드 스크립트
 * 실행: php database/seed_services.php
 */

// .env 로드
$envFile = __DIR__ . '/../.env';
if (!file_exists($envFile)) die(".env not found\n");
foreach (file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
    if (str_starts_with(trim($line), '#')) continue;
    if (strpos($line, '=') !== false) {
        [$k, $v] = explode('=', $line, 2);
        $_ENV[trim($k)] = trim($v);
    }
}

$pdo = new PDO(
    "mysql:host={$_ENV['DB_HOST']};dbname={$_ENV['DB_DATABASE']};charset=utf8mb4",
    $_ENV['DB_USERNAME'], $_ENV['DB_PASSWORD'] ?? '',
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);
$prefix = $_ENV['DB_PREFIX'] ?? 'rzx_';

function genUUID() {
    $d = bin2hex(random_bytes(16));
    return substr($d,0,8).'-'.substr($d,8,4).'-'.substr($d,12,4).'-'.substr($d,16,4).'-'.substr($d,20,12);
}

// 카테고리 ID 매핑
$cats = $pdo->query("SELECT id, slug FROM {$prefix}service_categories")->fetchAll(PDO::FETCH_KEY_PAIR);
$catBySlug = array_flip($cats); // slug => id

// 서비스 정의: [slug, category_slug, price, duration, buffer, sort_order]
$services = [
    // 헤어 (category: hair)
    ['women-cut',       'hair', 35000, 60,  10, 1],
    ['men-cut',         'hair', 20000, 30,  5,  2],
    ['kids-cut',        'hair', 15000, 30,  5,  3],
    ['shampoo-blowdry', 'hair', 20000, 40,  5,  4],
    ['perm',            'hair', 80000, 120, 10, 5],
    ['digital-perm',    'hair', 100000,150, 10, 6],
    ['color',           'hair', 70000, 90,  10, 7],
    ['highlights',      'hair', 90000, 120, 10, 8],
    ['bleach',          'hair', 80000, 90,  10, 9],
    ['treatment',       'hair', 40000, 45,  5,  10],
    ['scalp-care',      'hair', 50000, 60,  10, 11],
    ['bang-trim',       'hair', 5000,  10,  0,  12],
    // 네일 (category: nail)
    ['gel-nail',        'nail', 50000, 60,  5,  13],
    ['nail-art',        'nail', 70000, 90,  5,  14],
    ['nail-care',       'nail', 30000, 40,  5,  15],
    ['nail-removal',    'nail', 15000, 20,  5,  16],
    // 메이크업 (category: makeup)
    ['daily-makeup',    'makeup', 50000, 45,  5,  17],
    ['wedding-makeup',  'makeup', 150000,90, 10, 18],
    ['special-makeup',  'makeup', 80000, 60,  5,  19],
    // 마사지 (category: massage)
    ['head-spa',        'massage', 60000, 60,  10, 20],
    ['shoulder-massage','massage', 40000, 30,  5,  21],
    // 컨설팅 (category: consulting)
    ['hair-consulting', 'consulting', 0, 15, 0, 22],
    ['style-consulting','consulting', 30000, 30, 5, 23],
];

// 13개 언어 번역 데이터
$translations = [
    'women-cut' => [
        'name' => [
            'ko' => '여성 커트', 'en' => 'Women\'s Haircut', 'ja' => 'レディースカット',
            'zh_CN' => '女士剪发', 'zh_TW' => '女士剪髮', 'de' => 'Damenhaarschnitt',
            'es' => 'Corte de mujer', 'fr' => 'Coupe femme', 'mn' => 'Эмэгтэй үс засалт',
            'ru' => 'Женская стрижка', 'tr' => 'Kadın saç kesimi', 'vi' => 'Cắt tóc nữ', 'id' => 'Potong rambut wanita',
        ],
        'desc' => [
            'ko' => '샴푸, 커트, 드라이 포함', 'en' => 'Includes shampoo, cut & blow dry',
            'ja' => 'シャンプー・カット・ブロー込み', 'zh_CN' => '含洗发、剪发、吹干',
            'zh_TW' => '含洗髮、剪髮、吹乾', 'de' => 'Inkl. Shampoo, Schnitt & Föhnen',
            'es' => 'Incluye champú, corte y secado', 'fr' => 'Shampooing, coupe et brushing inclus',
            'mn' => 'Угаалга, засалт, хатаалт багтсан', 'ru' => 'Включает мытьё, стрижку и укладку',
            'tr' => 'Yıkama, kesim ve fön dahil', 'vi' => 'Bao gồm gội, cắt và sấy', 'id' => 'Termasuk keramas, potong & blow dry',
        ],
    ],
    'men-cut' => [
        'name' => [
            'ko' => '남성 커트', 'en' => 'Men\'s Haircut', 'ja' => 'メンズカット',
            'zh_CN' => '男士剪发', 'zh_TW' => '男士剪髮', 'de' => 'Herrenhaarschnitt',
            'es' => 'Corte de hombre', 'fr' => 'Coupe homme', 'mn' => 'Эрэгтэй үс засалт',
            'ru' => 'Мужская стрижка', 'tr' => 'Erkek saç kesimi', 'vi' => 'Cắt tóc nam', 'id' => 'Potong rambut pria',
        ],
        'desc' => [
            'ko' => '샴푸, 커트, 드라이 포함', 'en' => 'Includes shampoo, cut & blow dry',
            'ja' => 'シャンプー・カット・ブロー込み', 'zh_CN' => '含洗发、剪发、吹干',
            'zh_TW' => '含洗髮、剪髮、吹乾', 'de' => 'Inkl. Shampoo, Schnitt & Föhnen',
            'es' => 'Incluye champú, corte y secado', 'fr' => 'Shampooing, coupe et brushing inclus',
            'mn' => 'Угаалга, засалт, хатаалт багтсан', 'ru' => 'Включает мытьё, стрижку и укладку',
            'tr' => 'Yıkama, kesim ve fön dahil', 'vi' => 'Bao gồm gội, cắt và sấy', 'id' => 'Termasuk keramas, potong & blow dry',
        ],
    ],
    'kids-cut' => [
        'name' => [
            'ko' => '아동 커트', 'en' => 'Kids Haircut', 'ja' => 'キッズカット',
            'zh_CN' => '儿童剪发', 'zh_TW' => '兒童剪髮', 'de' => 'Kinderhaarschnitt',
            'es' => 'Corte infantil', 'fr' => 'Coupe enfant', 'mn' => 'Хүүхдийн үс засалт',
            'ru' => 'Детская стрижка', 'tr' => 'Çocuk saç kesimi', 'vi' => 'Cắt tóc trẻ em', 'id' => 'Potong rambut anak',
        ],
        'desc' => [
            'ko' => '12세 이하 아동', 'en' => 'For children under 12', 'ja' => '12歳以下のお子様',
            'zh_CN' => '12岁以下儿童', 'zh_TW' => '12歲以下兒童', 'de' => 'Für Kinder unter 12',
            'es' => 'Para niños menores de 12 años', 'fr' => 'Pour les enfants de moins de 12 ans',
            'mn' => '12-с доош насны хүүхэд', 'ru' => 'Для детей до 12 лет',
            'tr' => '12 yaş altı çocuklar için', 'vi' => 'Dành cho trẻ dưới 12 tuổi', 'id' => 'Untuk anak di bawah 12 tahun',
        ],
    ],
    'shampoo-blowdry' => [
        'name' => [
            'ko' => '샴푸 & 블로우드라이', 'en' => 'Shampoo & Blow Dry', 'ja' => 'シャンプー＆ブロー',
            'zh_CN' => '洗发吹干', 'zh_TW' => '洗髮吹乾', 'de' => 'Shampoo & Föhnen',
            'es' => 'Champú y secado', 'fr' => 'Shampooing & brushing', 'mn' => 'Угаалга & хатаалт',
            'ru' => 'Мытьё и укладка', 'tr' => 'Yıkama ve fön', 'vi' => 'Gội & sấy', 'id' => 'Keramas & blow dry',
        ],
        'desc' => [
            'ko' => '샴푸 후 스타일링 블로우', 'en' => 'Shampoo followed by styling blow dry',
            'ja' => 'シャンプー後スタイリングブロー', 'zh_CN' => '洗发后造型吹干',
            'zh_TW' => '洗髮後造型吹乾', 'de' => 'Shampoo mit anschließendem Styling',
            'es' => 'Lavado seguido de secado con estilo', 'fr' => 'Shampooing suivi d\'un brushing',
            'mn' => 'Угаалгын дараа загварчлал', 'ru' => 'Мытьё с последующей укладкой',
            'tr' => 'Yıkama sonrası şekillendirme', 'vi' => 'Gội đầu và tạo kiểu', 'id' => 'Keramas diikuti styling blow dry',
        ],
    ],
    'perm' => [
        'name' => [
            'ko' => '일반 펌', 'en' => 'Perm', 'ja' => 'パーマ',
            'zh_CN' => '烫发', 'zh_TW' => '燙髮', 'de' => 'Dauerwelle',
            'es' => 'Permanente', 'fr' => 'Permanente', 'mn' => 'Буржуулалт',
            'ru' => 'Химическая завивка', 'tr' => 'Perma', 'vi' => 'Uốn tóc', 'id' => 'Pengeritingan',
        ],
        'desc' => [
            'ko' => '일반 펌 시술 (샴푸, 트리트먼트 포함)', 'en' => 'Standard perm (incl. shampoo & treatment)',
            'ja' => 'パーマ施術（シャンプー・トリートメント込み）', 'zh_CN' => '标准烫发（含洗发、护理）',
            'zh_TW' => '標準燙髮（含洗髮、護理）', 'de' => 'Standard-Dauerwelle (inkl. Shampoo & Pflege)',
            'es' => 'Permanente estándar (incl. champú y tratamiento)', 'fr' => 'Permanente standard (shampooing et soin inclus)',
            'mn' => 'Энгийн буржуулалт (угаалга, арчилгаа багтсан)', 'ru' => 'Стандартная завивка (вкл. мытьё и уход)',
            'tr' => 'Standart perma (yıkama ve bakım dahil)', 'vi' => 'Uốn thường (gồm gội và dưỡng)', 'id' => 'Pengeritingan standar (termasuk keramas & perawatan)',
        ],
    ],
    'digital-perm' => [
        'name' => [
            'ko' => '디지털 펌', 'en' => 'Digital Perm', 'ja' => 'デジタルパーマ',
            'zh_CN' => '数码烫', 'zh_TW' => '溫塑燙', 'de' => 'Digitale Dauerwelle',
            'es' => 'Permanente digital', 'fr' => 'Permanente digitale', 'mn' => 'Дижитал буржуулалт',
            'ru' => 'Цифровая завивка', 'tr' => 'Dijital perma', 'vi' => 'Uốn kỹ thuật số', 'id' => 'Digital perm',
        ],
        'desc' => [
            'ko' => '열펌 시술로 자연스러운 컬', 'en' => 'Hot perm for natural-looking curls',
            'ja' => 'ホットパーマで自然なカール', 'zh_CN' => '热烫打造自然卷发',
            'zh_TW' => '熱燙打造自然捲髮', 'de' => 'Heißwelle für natürliche Locken',
            'es' => 'Permanente térmica para rizos naturales', 'fr' => 'Permanente chaude pour boucles naturelles',
            'mn' => 'Халуун буржуулалтаар байгалийн буржгар', 'ru' => 'Горячая завивка для естественных локонов',
            'tr' => 'Doğal bukleler için sıcak perma', 'vi' => 'Uốn nóng tạo lọn tự nhiên', 'id' => 'Perm panas untuk ikal alami',
        ],
    ],
    'color' => [
        'name' => [
            'ko' => '염색', 'en' => 'Hair Color', 'ja' => 'ヘアカラー',
            'zh_CN' => '染发', 'zh_TW' => '染髮', 'de' => 'Haarfarbe',
            'es' => 'Tinte', 'fr' => 'Coloration', 'mn' => 'Үс будалт',
            'ru' => 'Окрашивание', 'tr' => 'Saç boyama', 'vi' => 'Nhuộm tóc', 'id' => 'Pewarnaan rambut',
        ],
        'desc' => [
            'ko' => '전체 염색 (뿌리~끝)', 'en' => 'Full hair coloring (roots to ends)',
            'ja' => 'フルカラー（根元〜毛先）', 'zh_CN' => '全头染发（发根至发梢）',
            'zh_TW' => '全頭染髮（髮根至髮梢）', 'de' => 'Komplettfärbung (Ansatz bis Spitzen)',
            'es' => 'Coloración completa (raíz a puntas)', 'fr' => 'Coloration complète (racines aux pointes)',
            'mn' => 'Бүтэн үс будалт (үндэснээс үзүүр хүртэл)', 'ru' => 'Полное окрашивание (от корней до кончиков)',
            'tr' => 'Tam saç boyama (kökten uca)', 'vi' => 'Nhuộm toàn bộ (gốc đến ngọn)', 'id' => 'Pewarnaan penuh (akar hingga ujung)',
        ],
    ],
    'highlights' => [
        'name' => [
            'ko' => '하이라이트', 'en' => 'Highlights', 'ja' => 'ハイライト',
            'zh_CN' => '挑染', 'zh_TW' => '挑染', 'de' => 'Strähnchen',
            'es' => 'Mechas', 'fr' => 'Mèches', 'mn' => 'Хайлайт',
            'ru' => 'Мелирование', 'tr' => 'Röfle', 'vi' => 'Highlight', 'id' => 'Highlight',
        ],
        'desc' => [
            'ko' => '부분 하이라이트 염색', 'en' => 'Partial highlight coloring',
            'ja' => '部分ハイライトカラー', 'zh_CN' => '局部挑染', 'zh_TW' => '局部挑染',
            'de' => 'Teilweise Strähnchen', 'es' => 'Mechas parciales', 'fr' => 'Mèches partielles',
            'mn' => 'Хэсэгчилсэн хайлайт', 'ru' => 'Частичное мелирование',
            'tr' => 'Kısmi röfle', 'vi' => 'Nhuộm highlight từng phần', 'id' => 'Highlight sebagian',
        ],
    ],
    'bleach' => [
        'name' => [
            'ko' => '탈색', 'en' => 'Bleach', 'ja' => 'ブリーチ',
            'zh_CN' => '漂发', 'zh_TW' => '漂髮', 'de' => 'Blondierung',
            'es' => 'Decoloración', 'fr' => 'Décoloration', 'mn' => 'Будаг арилгалт',
            'ru' => 'Обесцвечивание', 'tr' => 'Ağartma', 'vi' => 'Tẩy tóc', 'id' => 'Bleaching',
        ],
        'desc' => [
            'ko' => '전체 탈색 1회', 'en' => 'Full bleach (1 session)',
            'ja' => 'フルブリーチ1回', 'zh_CN' => '全头漂发1次', 'zh_TW' => '全頭漂髮1次',
            'de' => 'Komplett-Blondierung (1x)', 'es' => 'Decoloración completa (1 sesión)',
            'fr' => 'Décoloration complète (1 séance)', 'mn' => 'Бүтэн будаг арилгалт 1 удаа',
            'ru' => 'Полное обесцвечивание (1 раз)', 'tr' => 'Tam ağartma (1 seans)',
            'vi' => 'Tẩy toàn bộ 1 lần', 'id' => 'Bleaching penuh (1 sesi)',
        ],
    ],
    'treatment' => [
        'name' => [
            'ko' => '트리트먼트', 'en' => 'Hair Treatment', 'ja' => 'トリートメント',
            'zh_CN' => '护理', 'zh_TW' => '護理', 'de' => 'Haarbehandlung',
            'es' => 'Tratamiento capilar', 'fr' => 'Soin capillaire', 'mn' => 'Үсний арчилгаа',
            'ru' => 'Уход за волосами', 'tr' => 'Saç bakımı', 'vi' => 'Dưỡng tóc', 'id' => 'Perawatan rambut',
        ],
        'desc' => [
            'ko' => '손상 모발 집중 트리트먼트', 'en' => 'Intensive treatment for damaged hair',
            'ja' => 'ダメージヘア集中トリートメント', 'zh_CN' => '受损发质深层护理',
            'zh_TW' => '受損髮質深層護理', 'de' => 'Intensivbehandlung für geschädigtes Haar',
            'es' => 'Tratamiento intensivo para cabello dañado', 'fr' => 'Soin intensif pour cheveux abîmés',
            'mn' => 'Гэмтсэн үсний эрчимтэй арчилгаа', 'ru' => 'Интенсивный уход для повреждённых волос',
            'tr' => 'Yıpranmış saçlar için yoğun bakım', 'vi' => 'Dưỡng chuyên sâu cho tóc hư tổn', 'id' => 'Perawatan intensif rambut rusak',
        ],
    ],
    'scalp-care' => [
        'name' => [
            'ko' => '두피 케어', 'en' => 'Scalp Care', 'ja' => 'スカルプケア',
            'zh_CN' => '头皮护理', 'zh_TW' => '頭皮護理', 'de' => 'Kopfhautpflege',
            'es' => 'Cuidado del cuero cabelludo', 'fr' => 'Soin du cuir chevelu', 'mn' => 'Хуйхны арчилгаа',
            'ru' => 'Уход за кожей головы', 'tr' => 'Saç derisi bakımı', 'vi' => 'Chăm sóc da đầu', 'id' => 'Perawatan kulit kepala',
        ],
        'desc' => [
            'ko' => '두피 스케일링 및 영양 공급', 'en' => 'Scalp scaling and nourishing',
            'ja' => 'スカルプスケーリング＆栄養補給', 'zh_CN' => '头皮清洁与营养',
            'zh_TW' => '頭皮清潔與營養', 'de' => 'Kopfhaut-Peeling und Nährstoffversorgung',
            'es' => 'Exfoliación y nutrición del cuero cabelludo', 'fr' => 'Gommage et nutrition du cuir chevelu',
            'mn' => 'Хуйхны цэвэрлэгээ ба тэжээл', 'ru' => 'Пилинг и питание кожи головы',
            'tr' => 'Saç derisi peeling ve besleme', 'vi' => 'Tẩy tế bào chết và dưỡng da đầu', 'id' => 'Scaling kulit kepala dan nutrisi',
        ],
    ],
    'bang-trim' => [
        'name' => [
            'ko' => '앞머리 다듬기', 'en' => 'Bang Trim', 'ja' => '前髪カット',
            'zh_CN' => '刘海修剪', 'zh_TW' => '瀏海修剪', 'de' => 'Ponyschnitt',
            'es' => 'Recorte de flequillo', 'fr' => 'Coupe de frange', 'mn' => 'Хөмсөг засалт',
            'ru' => 'Подравнивание чёлки', 'tr' => 'Kahkül düzeltme', 'vi' => 'Cắt mái', 'id' => 'Potong poni',
        ],
        'desc' => [
            'ko' => '앞머리만 간단히 다듬기', 'en' => 'Quick bang trim only',
            'ja' => '前髪のみ簡単カット', 'zh_CN' => '仅修剪刘海', 'zh_TW' => '僅修剪瀏海',
            'de' => 'Nur Ponyschnitt', 'es' => 'Solo recorte de flequillo',
            'fr' => 'Coupe de frange uniquement', 'mn' => 'Зөвхөн хөмсөг засалт',
            'ru' => 'Только подравнивание чёлки', 'tr' => 'Sadece kahkül düzeltme',
            'vi' => 'Chỉ cắt mái', 'id' => 'Hanya potong poni',
        ],
    ],
    'gel-nail' => [
        'name' => [
            'ko' => '젤 네일', 'en' => 'Gel Nails', 'ja' => 'ジェルネイル',
            'zh_CN' => '凝胶美甲', 'zh_TW' => '凝膠美甲', 'de' => 'Gel-Nägel',
            'es' => 'Uñas de gel', 'fr' => 'Ongles en gel', 'mn' => 'Гель хумс',
            'ru' => 'Гель-маникюр', 'tr' => 'Jel tırnak', 'vi' => 'Sơn gel', 'id' => 'Kuku gel',
        ],
        'desc' => [
            'ko' => '젤 네일 풀세트', 'en' => 'Full set gel nails', 'ja' => 'ジェルネイルフルセット',
            'zh_CN' => '全套凝胶美甲', 'zh_TW' => '全套凝膠美甲', 'de' => 'Gel-Nägel Komplettset',
            'es' => 'Set completo de uñas de gel', 'fr' => 'Pose complète d\'ongles en gel',
            'mn' => 'Гель хумсны бүрэн багц', 'ru' => 'Полный набор гель-маникюра',
            'tr' => 'Tam set jel tırnak', 'vi' => 'Trọn bộ sơn gel', 'id' => 'Set lengkap kuku gel',
        ],
    ],
    'nail-art' => [
        'name' => [
            'ko' => '네일 아트', 'en' => 'Nail Art', 'ja' => 'ネイルアート',
            'zh_CN' => '美甲艺术', 'zh_TW' => '美甲藝術', 'de' => 'Nagelkunst',
            'es' => 'Arte de uñas', 'fr' => 'Nail art', 'mn' => 'Хумсны урлаг',
            'ru' => 'Нейл-арт', 'tr' => 'Tırnak sanatı', 'vi' => 'Vẽ móng nghệ thuật', 'id' => 'Nail art',
        ],
        'desc' => [
            'ko' => '디자인 네일 아트 (10본)', 'en' => 'Design nail art (10 nails)', 'ja' => 'デザインネイルアート（10本）',
            'zh_CN' => '设计美甲（10指）', 'zh_TW' => '設計美甲（10指）', 'de' => 'Design-Nagelkunst (10 Nägel)',
            'es' => 'Diseño de uñas (10 uñas)', 'fr' => 'Nail art design (10 ongles)',
            'mn' => 'Загварын хумсны урлаг (10 хуруу)', 'ru' => 'Дизайн нейл-арт (10 ногтей)',
            'tr' => 'Tasarım tırnak sanatı (10 tırnak)', 'vi' => 'Vẽ nail thiết kế (10 móng)', 'id' => 'Desain nail art (10 kuku)',
        ],
    ],
    'nail-care' => [
        'name' => [
            'ko' => '네일 케어', 'en' => 'Nail Care', 'ja' => 'ネイルケア',
            'zh_CN' => '指甲护理', 'zh_TW' => '指甲護理', 'de' => 'Nagelpflege',
            'es' => 'Cuidado de uñas', 'fr' => 'Soin des ongles', 'mn' => 'Хумсны арчилгаа',
            'ru' => 'Уход за ногтями', 'tr' => 'Tırnak bakımı', 'vi' => 'Chăm sóc móng', 'id' => 'Perawatan kuku',
        ],
        'desc' => [
            'ko' => '큐티클 정리 및 손톱 케어', 'en' => 'Cuticle care and nail grooming', 'ja' => 'キューティクルケア＆爪ケア',
            'zh_CN' => '角质处理及指甲护理', 'zh_TW' => '角質處理及指甲護理', 'de' => 'Nagelhautpflege und Nagelbehandlung',
            'es' => 'Cuidado de cutículas y uñas', 'fr' => 'Soin des cuticules et des ongles',
            'mn' => 'Хумсны арьсны арчилгаа', 'ru' => 'Уход за кутикулой и ногтями',
            'tr' => 'Tırnak eti bakımı ve tırnak düzenleme', 'vi' => 'Chăm sóc biểu bì và móng', 'id' => 'Perawatan kutikula dan kuku',
        ],
    ],
    'nail-removal' => [
        'name' => [
            'ko' => '젤 제거', 'en' => 'Gel Removal', 'ja' => 'ジェルオフ',
            'zh_CN' => '卸甲', 'zh_TW' => '卸甲', 'de' => 'Gel-Entfernung',
            'es' => 'Remoción de gel', 'fr' => 'Dépose de gel', 'mn' => 'Гель арилгалт',
            'ru' => 'Снятие геля', 'tr' => 'Jel çıkarma', 'vi' => 'Tháo gel', 'id' => 'Pelepasan gel',
        ],
        'desc' => [
            'ko' => '기존 젤 네일 제거', 'en' => 'Removal of existing gel nails', 'ja' => '既存ジェルネイルのオフ',
            'zh_CN' => '卸除现有凝胶甲', 'zh_TW' => '卸除現有凝膠甲', 'de' => 'Entfernung bestehender Gel-Nägel',
            'es' => 'Remoción de gel existente', 'fr' => 'Dépose du gel existant',
            'mn' => 'Хуучин гель хумс арилгах', 'ru' => 'Снятие существующего гель-маникюра',
            'tr' => 'Mevcut jel çıkarma', 'vi' => 'Tháo gel cũ', 'id' => 'Pelepasan kuku gel yang ada',
        ],
    ],
    'daily-makeup' => [
        'name' => [
            'ko' => '데일리 메이크업', 'en' => 'Daily Makeup', 'ja' => 'デイリーメイク',
            'zh_CN' => '日常妆容', 'zh_TW' => '日常妝容', 'de' => 'Tages-Make-up',
            'es' => 'Maquillaje diario', 'fr' => 'Maquillage quotidien', 'mn' => 'Өдрийн нүүр будалт',
            'ru' => 'Дневной макияж', 'tr' => 'Günlük makyaj', 'vi' => 'Trang điểm hàng ngày', 'id' => 'Makeup harian',
        ],
        'desc' => [
            'ko' => '자연스러운 데일리 메이크업', 'en' => 'Natural daily makeup look', 'ja' => 'ナチュラルデイリーメイク',
            'zh_CN' => '自然日常妆容', 'zh_TW' => '自然日常妝容', 'de' => 'Natürliches Tages-Make-up',
            'es' => 'Maquillaje natural diario', 'fr' => 'Maquillage quotidien naturel',
            'mn' => 'Байгалийн өдрийн нүүр будалт', 'ru' => 'Естественный дневной макияж',
            'tr' => 'Doğal günlük makyaj', 'vi' => 'Trang điểm tự nhiên hàng ngày', 'id' => 'Makeup harian alami',
        ],
    ],
    'wedding-makeup' => [
        'name' => [
            'ko' => '웨딩 메이크업', 'en' => 'Wedding Makeup', 'ja' => 'ウェディングメイク',
            'zh_CN' => '婚礼妆容', 'zh_TW' => '婚禮妝容', 'de' => 'Hochzeits-Make-up',
            'es' => 'Maquillaje de boda', 'fr' => 'Maquillage de mariage', 'mn' => 'Хуримын нүүр будалт',
            'ru' => 'Свадебный макияж', 'tr' => 'Düğün makyajı', 'vi' => 'Trang điểm cưới', 'id' => 'Makeup pernikahan',
        ],
        'desc' => [
            'ko' => '웨딩 전문 메이크업 (리허설 포함)', 'en' => 'Professional wedding makeup (incl. rehearsal)',
            'ja' => 'ウェディング専門メイク（リハーサル込み）', 'zh_CN' => '专业婚礼妆容（含彩排）',
            'zh_TW' => '專業婚禮妝容（含彩排）', 'de' => 'Professionelles Hochzeits-Make-up (inkl. Probe)',
            'es' => 'Maquillaje profesional de boda (incl. ensayo)', 'fr' => 'Maquillage professionnel de mariage (essai inclus)',
            'mn' => 'Мэргэжлийн хуримын будалт (туршилт багтсан)', 'ru' => 'Профессиональный свадебный макияж (вкл. репетицию)',
            'tr' => 'Profesyonel düğün makyajı (prova dahil)', 'vi' => 'Trang điểm cưới chuyên nghiệp (gồm thử)', 'id' => 'Makeup pernikahan profesional (termasuk rehearsal)',
        ],
    ],
    'special-makeup' => [
        'name' => [
            'ko' => '특수 메이크업', 'en' => 'Special Event Makeup', 'ja' => 'スペシャルメイク',
            'zh_CN' => '特殊场合妆容', 'zh_TW' => '特殊場合妝容', 'de' => 'Event-Make-up',
            'es' => 'Maquillaje para eventos', 'fr' => 'Maquillage événementiel', 'mn' => 'Тусгай нүүр будалт',
            'ru' => 'Макияж для мероприятий', 'tr' => 'Özel etkinlik makyajı', 'vi' => 'Trang điểm sự kiện', 'id' => 'Makeup acara khusus',
        ],
        'desc' => [
            'ko' => '파티, 촬영 등 특수 메이크업', 'en' => 'Makeup for parties, photoshoots, etc.',
            'ja' => 'パーティー・撮影などのスペシャルメイク', 'zh_CN' => '派对、拍摄等特殊妆容',
            'zh_TW' => '派對、拍攝等特殊妝容', 'de' => 'Make-up für Partys, Fotoshootings usw.',
            'es' => 'Maquillaje para fiestas, sesiones de fotos, etc.', 'fr' => 'Maquillage pour fêtes, shootings photo, etc.',
            'mn' => 'Үдэшлэг, зураг авалт зэрэг тусгай будалт', 'ru' => 'Макияж для вечеринок, фотосессий и т.д.',
            'tr' => 'Parti, fotoğraf çekimi vb. için makyaj', 'vi' => 'Trang điểm tiệc, chụp hình, v.v.', 'id' => 'Makeup untuk pesta, pemotretan, dll.',
        ],
    ],
    'head-spa' => [
        'name' => [
            'ko' => '헤드 스파', 'en' => 'Head Spa', 'ja' => 'ヘッドスパ',
            'zh_CN' => '头部水疗', 'zh_TW' => '頭部水療', 'de' => 'Kopf-Spa',
            'es' => 'Spa de cabeza', 'fr' => 'Spa crânien', 'mn' => 'Толгойн спа',
            'ru' => 'Спа для головы', 'tr' => 'Kafa spa', 'vi' => 'Spa đầu', 'id' => 'Head spa',
        ],
        'desc' => [
            'ko' => '두피 마사지 & 딥 클렌징 스파', 'en' => 'Scalp massage & deep cleansing spa',
            'ja' => 'スカルプマッサージ＆ディープクレンジング', 'zh_CN' => '头皮按摩和深层清洁水疗',
            'zh_TW' => '頭皮按摩和深層清潔水療', 'de' => 'Kopfhautmassage & Tiefenreinigung',
            'es' => 'Masaje del cuero cabelludo y limpieza profunda', 'fr' => 'Massage du cuir chevelu et nettoyage en profondeur',
            'mn' => 'Хуйхны массаж & гүнзгий цэвэрлэгээ', 'ru' => 'Массаж кожи головы и глубокое очищение',
            'tr' => 'Saç derisi masajı ve derin temizleme', 'vi' => 'Massage da đầu & tẩy sâu', 'id' => 'Pijat kulit kepala & deep cleansing',
        ],
    ],
    'shoulder-massage' => [
        'name' => [
            'ko' => '어깨 마사지', 'en' => 'Shoulder Massage', 'ja' => '肩マッサージ',
            'zh_CN' => '肩部按摩', 'zh_TW' => '肩部按摩', 'de' => 'Schultermassage',
            'es' => 'Masaje de hombros', 'fr' => 'Massage des épaules', 'mn' => 'Мөрний массаж',
            'ru' => 'Массаж плеч', 'tr' => 'Omuz masajı', 'vi' => 'Massage vai', 'id' => 'Pijat bahu',
        ],
        'desc' => [
            'ko' => '시술 중 목·어깨 마사지 추가', 'en' => 'Add-on neck & shoulder massage',
            'ja' => '施術中の首・肩マッサージ追加', 'zh_CN' => '服务中加颈肩按摩',
            'zh_TW' => '服務中加頸肩按摩', 'de' => 'Zusätzliche Nacken- & Schultermassage',
            'es' => 'Masaje adicional de cuello y hombros', 'fr' => 'Massage nuque et épaules en complément',
            'mn' => 'Хүзүү, мөрний нэмэлт массаж', 'ru' => 'Дополнительный массаж шеи и плеч',
            'tr' => 'Ek boyun ve omuz masajı', 'vi' => 'Thêm massage cổ vai', 'id' => 'Tambahan pijat leher & bahu',
        ],
    ],
    'hair-consulting' => [
        'name' => [
            'ko' => '헤어 상담', 'en' => 'Hair Consultation', 'ja' => 'ヘアカウンセリング',
            'zh_CN' => '发型咨询', 'zh_TW' => '髮型諮詢', 'de' => 'Haarberatung',
            'es' => 'Consulta capilar', 'fr' => 'Consultation capillaire', 'mn' => 'Үсний зөвлөгөө',
            'ru' => 'Консультация по волосам', 'tr' => 'Saç danışmanlığı', 'vi' => 'Tư vấn tóc', 'id' => 'Konsultasi rambut',
        ],
        'desc' => [
            'ko' => '무료 헤어 스타일 상담', 'en' => 'Free hair style consultation',
            'ja' => '無料ヘアスタイル相談', 'zh_CN' => '免费发型咨询', 'zh_TW' => '免費髮型諮詢',
            'de' => 'Kostenlose Haarberatung', 'es' => 'Consulta gratuita de estilo',
            'fr' => 'Consultation coiffure gratuite', 'mn' => 'Үнэгүй загварын зөвлөгөө',
            'ru' => 'Бесплатная консультация по стилю', 'tr' => 'Ücretsiz saç stili danışmanlığı',
            'vi' => 'Tư vấn kiểu tóc miễn phí', 'id' => 'Konsultasi gaya rambut gratis',
        ],
    ],
    'style-consulting' => [
        'name' => [
            'ko' => '스타일 컨설팅', 'en' => 'Style Consulting', 'ja' => 'スタイルコンサルティング',
            'zh_CN' => '形象咨询', 'zh_TW' => '形象諮詢', 'de' => 'Stilberatung',
            'es' => 'Asesoría de estilo', 'fr' => 'Conseil en style', 'mn' => 'Загварын зөвлөх',
            'ru' => 'Стиль-консалтинг', 'tr' => 'Stil danışmanlığı', 'vi' => 'Tư vấn phong cách', 'id' => 'Konsultasi gaya',
        ],
        'desc' => [
            'ko' => '얼굴형·피부톤에 맞는 종합 스타일 제안', 'en' => 'Complete style advice based on face shape & skin tone',
            'ja' => '顔型・肌色に合わせた総合スタイル提案', 'zh_CN' => '根据脸型和肤色的综合形象建议',
            'zh_TW' => '根據臉型和膚色的綜合形象建議', 'de' => 'Umfassende Stilberatung nach Gesichtsform & Hautton',
            'es' => 'Asesoría completa según forma de cara y tono de piel', 'fr' => 'Conseil style complet selon la forme du visage et le teint',
            'mn' => 'Нүүрний хэлбэр, арьсны өнгөнд тохирсон загварын зөвлөгөө',
            'ru' => 'Комплексные рекомендации по стилю с учётом формы лица и тона кожи',
            'tr' => 'Yüz şekli ve cilt tonuna göre kapsamlı stil önerisi',
            'vi' => 'Tư vấn phong cách tổng hợp theo khuôn mặt & tông da',
            'id' => 'Saran gaya lengkap berdasarkan bentuk wajah & warna kulit',
        ],
    ],
];

// ─── 실행 ───
$pdo->beginTransaction();
try {
    $insertSvc = $pdo->prepare("INSERT INTO {$prefix}services (id, category_id, name, slug, description, price, duration, buffer_time, sort_order, is_active) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 1)");
    $insertTr = $pdo->prepare("INSERT INTO {$prefix}translations (lang_key, locale, content) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE content = VALUES(content)");

    $count = 0;
    foreach ($services as [$slug, $catSlug, $price, $duration, $buffer, $sort]) {
        $uuid = genUUID();
        $catId = $catBySlug[$catSlug] ?? null;
        $tr = $translations[$slug] ?? null;
        if (!$tr) { echo "SKIP: {$slug} (no translation data)\n"; continue; }

        // 기본 언어(ko) 값을 DB name/description에 저장
        $name = $tr['name']['ko'];
        $desc = $tr['desc']['ko'] ?? '';

        $insertSvc->execute([$uuid, $catId, $name, $slug, $desc, $price, $duration, $buffer, $sort]);
        echo "INSERT service: {$slug} ({$name}) → {$uuid}\n";

        // 13개 언어 번역 등록
        foreach ($tr['name'] as $locale => $val) {
            $insertTr->execute(["service.{$uuid}.name", $locale, $val]);
        }
        foreach ($tr['desc'] as $locale => $val) {
            $insertTr->execute(["service.{$uuid}.description", $locale, $val]);
        }
        $count++;
    }

    $pdo->commit();
    echo "\n=== Done: {$count} services inserted with 13 language translations ===\n";
} catch (Exception $e) {
    $pdo->rollBack();
    echo "ERROR: " . $e->getMessage() . "\n";
}
