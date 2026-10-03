<?php
// ==========================================
// ملف التثبيت - ينشئ الجداول تلقائياً
// احذف هذا الملف بعد التثبيت!
// ==========================================
require_once 'config.php';

$db = getDB();

// إنشاء جدول التصنيفات
$db->exec("CREATE TABLE IF NOT EXISTS categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    slug VARCHAR(100) NOT NULL UNIQUE,
    image VARCHAR(500) DEFAULT '',
    sort_order INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

// إنشاء جدول المنتجات
$db->exec("CREATE TABLE IF NOT EXISTS products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(200) NOT NULL,
    description TEXT DEFAULT '',
    price DECIMAL(10,2) NOT NULL DEFAULT 0,
    old_price DECIMAL(10,2) DEFAULT NULL,
    category_id INT DEFAULT NULL,
    image1 VARCHAR(500) DEFAULT '',
    image2 VARCHAR(500) DEFAULT '',
    sizes VARCHAR(200) DEFAULT '',
    is_best_seller TINYINT(1) DEFAULT 0,
    is_new_arrival TINYINT(1) DEFAULT 0,
    in_stock TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

// إنشاء جدول الإعدادات
$db->exec("CREATE TABLE IF NOT EXISTS settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(100) NOT NULL UNIQUE,
    setting_value TEXT DEFAULT ''
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

// إنشاء جدول البانرات
$db->exec("CREATE TABLE IF NOT EXISTS banners (
    id INT AUTO_INCREMENT PRIMARY KEY,
    type VARCHAR(50) NOT NULL,
    image VARCHAR(500) DEFAULT '',
    title VARCHAR(200) DEFAULT '',
    subtitle VARCHAR(200) DEFAULT '',
    button_text VARCHAR(100) DEFAULT '',
    link VARCHAR(500) DEFAULT '#'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

// ==========================================
// إدخال البيانات الافتراضية
// ==========================================

// الإعدادات الافتراضية
$defaults = [
    ['site_name',        'الأمل'],
    ['announcement',     '🌟 شحن مجاني على الطلبات فوق 3000 جنيه | تسوقي الآن'],
    ['about_text',       'متجر الأمل متجر مصري متخصص في الملابس المحتشمة والعبايات الخليجية بأناقة عصرية تناسب المرأة المسلمة والعربية.'],
    ['phone',            '+201000000000'],
    ['email',            'info@alamal-store.com'],
    ['whatsapp',         '201000000000'],
    ['facebook',         '#'],
    ['instagram',        '#'],
    ['tiktok',           '#'],
    ['pinterest',        '#'],
    ['free_shipping_min','3000'],
];
$stmt = $db->prepare("INSERT IGNORE INTO settings (setting_key, setting_value) VALUES (?, ?)");
foreach ($defaults as $d) $stmt->execute($d);

// البانرات الافتراضية
$db->exec("INSERT IGNORE INTO banners (id, type, image, title, subtitle, button_text, link) VALUES
(1, 'main',   'https://images.unsplash.com/photo-1558171813-34a5d04de9bf?w=1600&q=80', 'أناقة محتشمة', 'تشكيلة فاخرة من العبايات والأزياء المحتشمة', 'تسوقي الآن', 'collection.php'),
(2, 'middle', 'https://images.unsplash.com/photo-1606144042614-b2417e99c4e3?w=1400&q=80', 'عروض خاصة', 'خصومات حتى 40% على تشكيلة مختارة', 'اكتشفي العروض', 'collection.php')
ON DUPLICATE KEY UPDATE type=type");

// التصنيفات الافتراضية
$cats = [
    ['عبايات',        'abayas',        'https://images.unsplash.com/photo-1558618666-fcd25c85cd64?w=600&q=80'],
    ['ملابس محتشمة', 'modest-wear',   'https://images.unsplash.com/photo-1525507119028-ed4c629a60a3?w=600&q=80'],
    ['حجاب',          'hijab',         'https://images.unsplash.com/photo-1606144042614-b2417e99c4e3?w=600&q=80'],
    ['إسدال وصلاة',  'isdals',        'https://images.unsplash.com/photo-1583391733956-6c78276477e1?w=600&q=80'],
    ['الموسم الشتوي','winter-season', 'https://images.unsplash.com/photo-1539533018257-d5d38e7a7bb7?w=600&q=80'],
];
$stmt = $db->prepare("INSERT IGNORE INTO categories (name, slug, image) VALUES (?, ?, ?)");
foreach ($cats as $c) $stmt->execute($c);

// منتجات تجريبية
$stmt = $db->prepare("SELECT id FROM categories WHERE slug = ?");

$stmt->execute(['abayas']); $abaya_id = $stmt->fetchColumn();
$stmt->execute(['modest-wear']); $modest_id = $stmt->fetchColumn();
$stmt->execute(['hijab']); $hijab_id = $stmt->fetchColumn();
$stmt->execute(['isdals']); $isdal_id = $stmt->fetchColumn();

$products = [
    ['عباية حرير أنيقة',    'عباية مصنوعة من أجود أنواع الحرير الكوري بتصميم عصري أنيق.',   2475, 2900, $abaya_id,  'https://images.unsplash.com/photo-1558618666-fcd25c85cd64?w=600&q=80', 'https://images.unsplash.com/photo-1583391733956-6c78276477e1?w=600&q=80', 'S,M,L,XL,2XL,3XL', 1, 1, 1],
    ['عباية ساتان زمردية',  'عباية ساتان بلون الزمرد الفاخر مثالية للمناسبات.',             2375, null,     $abaya_id,  'https://images.unsplash.com/photo-1583391733956-6c78276477e1?w=600&q=80', 'https://images.unsplash.com/photo-1558618666-fcd25c85cd64?w=600&q=80', 'S,M,L,XL,2XL', 1, 0, 1],
    ['كارديجان قمر الضباب', 'كارديجان فاخر بخامة ناعمة مثالي للإطلالات اليومية.',           2175, 2500,     $modest_id, 'https://images.unsplash.com/photo-1525507119028-ed4c629a60a3?w=600&q=80', 'https://images.unsplash.com/photo-1539533018257-d5d38e7a7bb7?w=600&q=80', 'M,L,XL,2XL,3XL',  1, 1, 1],
    ['حجاب حرير فاخر',      'حجاب من الحرير الناعم بتصميم أنيق يناسب جميع المناسبات.',      550,  null,     $hijab_id,  'https://images.unsplash.com/photo-1606144042614-b2417e99c4e3?w=600&q=80', 'https://images.unsplash.com/photo-1558171813-34a5d04de9bf?w=600&q=80', 'Free Size',        0, 1, 1],
    ['إسدال ملكي أحمر',     'إسدال فاخر باللون الأحمر الملكي من أجود الأقمشة.',             1175, null,     $isdal_id,  'https://images.unsplash.com/photo-1583391733956-6c78276477e1?w=600&q=80', 'https://images.unsplash.com/photo-1525507119028-ed4c629a60a3?w=600&q=80', 'One Size',         1, 0, 1],
    ['عباية يومية',          'عباية يومية مريحة بخامة مميزة مناسبة لكل الأوقات.',            2275, 2600,     $abaya_id,  'https://images.unsplash.com/photo-1558171813-34a5d04de9bf?w=600&q=80', 'https://images.unsplash.com/photo-1558618666-fcd25c85cd64?w=600&q=80', 'S,M,L,XL,2XL,3XL,4XL', 0, 0, 1],
];

$stmt = $db->prepare("INSERT INTO products (name, description, price, old_price, category_id, image1, image2, sizes, is_best_seller, is_new_arrival, in_stock) VALUES (?,?,?,?,?,?,?,?,?,?,?)");
foreach ($products as $p) {
    try { $stmt->execute($p); } catch(Exception $e) {}
}

echo '<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="UTF-8">
<title>تثبيت متجر الأمل</title>
<style>
  body{font-family:Arial;background:#f5f5f5;display:flex;align-items:center;justify-content:center;min-height:100vh;margin:0}
  .box{background:#fff;padding:40px;border-radius:12px;box-shadow:0 4px 20px rgba(0,0,0,.1);text-align:center;max-width:500px}
  h1{color:#C9A96E;margin-bottom:10px}
  p{color:#555;line-height:1.6}
  .btn{display:inline-block;margin-top:20px;padding:12px 30px;background:#1A1A1A;color:#fff;text-decoration:none;border-radius:6px;font-size:16px}
  .btn:hover{background:#C9A96E}
  .warn{background:#fff3cd;border:1px solid #ffc107;padding:12px;border-radius:6px;color:#856404;margin-top:15px;font-size:14px}
</style>
</head>
<body>
<div class="box">
  <h1>✅ تم التثبيت بنجاح!</h1>
  <p>تم إنشاء جميع جداول قاعدة البيانات وإضافة البيانات التجريبية بنجاح.</p>
  <div class="warn">⚠️ <strong>مهم:</strong> احذف ملف install.php من السيرفر الآن لأسباب أمنية!</div>
  <a href="index.php" class="btn">🏠 الذهاب للموقع</a>
  &nbsp;
  <a href="admin/index.php" class="btn" style="background:#C9A96E">⚙️ لوحة التحكم</a>
</div>
</body>
</html>';
?>
