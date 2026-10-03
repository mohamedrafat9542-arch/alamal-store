<?php
// ==========================================
// Header مشترك - متجر الأمل
// ==========================================
require_once __DIR__ . '/config.php';

$siteName    = getSetting('site_name', 'الأمل');
$announcement= getSetting('announcement', 'شحن مجاني على الطلبات فوق 3000 جنيه');
$whatsapp    = getSetting('whatsapp', '201000000000');
$facebook    = getSetting('facebook', '#');
$instagram   = getSetting('instagram', '#');
$tiktok      = getSetting('tiktok', '#');

// جلب التصنيفات للقائمة
try {
    $db = getDB();
    $cats = $db->query("SELECT name, slug FROM categories ORDER BY sort_order, id")->fetchAll();
} catch (Exception $e) {
    $cats = [];
}

$baseUrl = SITE_URL;
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= htmlspecialchars($pageTitle ?? $siteName) ?></title>
  <meta name="description" content="<?= htmlspecialchars(getSetting('about_text', '')) ?>">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;500;600;700&family=Cairo:wght@300;400;500;600;700&family=Inter:wght@300;400;500&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link rel="stylesheet" href="<?= $baseUrl ?>/assets/style.css">
  <script>
    // نطبّق الوضع المحفوظ فورًا قبل ما الصفحة تترسم عشان نمنع "ومضة" اللون الغلط
    (function() {
      var saved = localStorage.getItem('theme');
      var theme = saved || (window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light');
      document.documentElement.setAttribute('data-theme', theme);
    })();
  </script>
</head>
<body>

<!-- شريط الإعلانات -->
<div class="announcement-bar">
  <?= htmlspecialchars($announcement) ?>
</div>

<!-- الهيدر -->
<header class="header">
  <div class="header-inner">
    <!-- لوجو -->
    <a href="<?= $baseUrl ?>/index.php" class="logo">
      <?= htmlspecialchars($siteName) ?> <span>✦</span>
    </a>

    <!-- القائمة الرئيسية -->
    <nav class="nav">
      <ul>
        <li><a href="<?= $baseUrl ?>/collection.php?filter=best_seller">Best Sellers</a></li>
        <li><a href="<?= $baseUrl ?>/collection.php?filter=new_arrival">New Arrivals</a></li>
        <?php foreach ($cats as $cat): ?>
        <li><a href="<?= $baseUrl ?>/collection.php?cat=<?= urlencode($cat['slug']) ?>"><?= htmlspecialchars($cat['name']) ?></a></li>
        <?php endforeach; ?>
        <li><a href="<?= $baseUrl ?>/about.php">About Us</a></li>
      </ul>
    </nav>

    <!-- الأيقونات -->
    <div class="header-icons">
      <button class="theme-toggle" id="theme-toggle" title="تبديل الوضع الليلي" aria-label="تبديل الوضع الليلي">
        <i class="fas fa-moon theme-icon-dark"></i>
        <i class="fas fa-sun theme-icon-light"></i>
      </button>
      <a href="#" onclick="toggleSearch()" title="بحث"><i class="fas fa-search"></i></a>
      <a href="https://wa.me/<?= htmlspecialchars($whatsapp) ?>" target="_blank" class="wa-icon" title="واتساب"><i class="fab fa-whatsapp"></i></a>
      <button class="menu-toggle" onclick="toggleMobileNav()"><i class="fas fa-bars"></i></button>
    </div>
  </div>
</header>

<!-- قائمة الموبايل -->
<div class="overlay-bg" id="overlay-bg" onclick="toggleMobileNav()"></div>
<nav class="mobile-nav" id="mobile-nav">
  <button class="mobile-nav-close" onclick="toggleMobileNav()"><i class="fas fa-times"></i></button>
  <a href="<?= $baseUrl ?>/collection.php?filter=best_seller">Best Sellers</a>
  <a href="<?= $baseUrl ?>/collection.php?filter=new_arrival">New Arrivals</a>
  <?php foreach ($cats as $cat): ?>
  <a href="<?= $baseUrl ?>/collection.php?cat=<?= urlencode($cat['slug']) ?>"><?= htmlspecialchars($cat['name']) ?></a>
  <?php endforeach; ?>
  <a href="<?= $baseUrl ?>/about.php">About Us</a>
  <a href="https://wa.me/<?= htmlspecialchars($whatsapp) ?>" target="_blank"><i class="fab fa-whatsapp"></i> واتساب</a>
  <button class="theme-toggle theme-toggle-mobile" id="theme-toggle-mobile">
    <i class="fas fa-moon theme-icon-dark"></i>
    <i class="fas fa-sun theme-icon-light"></i>
    <span class="theme-toggle-label">تبديل الوضع الليلي</span>
  </button>
</nav>

<!-- نافذة البحث -->
<div class="search-overlay" id="search-overlay">
  <button class="search-close" onclick="toggleSearch()"><i class="fas fa-times"></i></button>
  <div class="search-box-wrap">
    <form action="<?= $baseUrl ?>/collection.php" method="GET">
      <input type="text" name="q" class="search-input" placeholder="ابحثي عن منتج..." autocomplete="off" id="search-input">
    </form>
  </div>
</div>
