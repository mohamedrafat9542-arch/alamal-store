<?php
// ==========================================
// الصفحة الرئيسية - متجر الأمل
// ==========================================
require_once 'config.php';
$pageTitle = getSetting('site_name', 'الأمل') . ' - أزياء محتشمة فاخرة';

try {
    $db = getDB();

    // جلب التصنيفات
    $categories = $db->query("SELECT * FROM categories ORDER BY sort_order, id")->fetchAll();

    // جلب البانرات
    $bannerMain   = $db->query("SELECT * FROM banners WHERE type='main'   LIMIT 1")->fetch();
    $bannerMiddle = $db->query("SELECT * FROM banners WHERE type='middle' LIMIT 1")->fetch();

    // جلب منتجات كل قسم
    $abayaProducts  = $db->query("SELECT p.*,c.name as cat_name FROM products p LEFT JOIN categories c ON p.category_id=c.id WHERE c.slug='abayas' AND p.in_stock=1 ORDER BY p.id DESC LIMIT 8")->fetchAll();
    $modestProducts = $db->query("SELECT p.*,c.name as cat_name FROM products p LEFT JOIN categories c ON p.category_id=c.id WHERE c.slug='modest-wear' AND p.in_stock=1 ORDER BY p.id DESC LIMIT 8")->fetchAll();
    $hijabProducts  = $db->query("SELECT p.*,c.name as cat_name FROM products p LEFT JOIN categories c ON p.category_id=c.id WHERE c.slug='hijab' AND p.in_stock=1 ORDER BY p.id DESC LIMIT 8")->fetchAll();
    $isdalProducts  = $db->query("SELECT p.*,c.name as cat_name FROM products p LEFT JOIN categories c ON p.category_id=c.id WHERE c.slug='isdals' AND p.in_stock=1 ORDER BY p.id DESC LIMIT 8")->fetchAll();
    $bestSellers    = $db->query("SELECT p.*,c.name as cat_name FROM products p LEFT JOIN categories c ON p.category_id=c.id WHERE p.is_best_seller=1 AND p.in_stock=1 ORDER BY p.id DESC LIMIT 8")->fetchAll();
    $newArrivals    = $db->query("SELECT p.*,c.name as cat_name FROM products p LEFT JOIN categories c ON p.category_id=c.id WHERE p.is_new_arrival=1 ORDER BY p.id DESC LIMIT 8")->fetchAll();

    $whatsapp = getSetting('whatsapp', '201000000000');
} catch (Exception $e) {
    $categories = $abayaProducts = $modestProducts = $hijabProducts = $isdalProducts = $bestSellers = $newArrivals = [];
    $bannerMain = $bannerMiddle = null;
    $whatsapp = '201000000000';
}

include 'header.php';

// دالة مساعدة لعرض بطاقة المنتج
function productCard($p, $wa) {
    $sizes = array_filter(array_map('trim', explode(',', $p['sizes'] ?? '')));
    $badge = '';
    if ($p['is_new_arrival']) $badge = '<span class="badge new">New</span>';
    if ($p['old_price'] && $p['old_price'] > $p['price']) $badge = '<span class="badge sale">Sale</span>';
    $pName = htmlspecialchars($p['name']);
    $pPrice = number_format($p['price'], 0);
    ?>
    <div class="product-card" data-name="<?= $pName ?>">
      <a href="product.php?id=<?= $p['id'] ?>">
        <div class="product-image-wrap">
          <?= $badge ?>
          <?php if (!$p['in_stock']): ?>
          <div class="sold-out-overlay"><span>نفذت الكمية</span></div>
          <?php endif; ?>
          <img class="product-img-1" src="<?= htmlspecialchars($p['image1'] ?: 'https://placehold.co/400x530/f5f5f5/999?text='.urlencode($p['name'])) ?>" alt="<?= $pName ?>" loading="lazy">
          <img class="product-img-2" src="<?= htmlspecialchars($p['image2'] ?: $p['image1'] ?: '') ?>" alt="<?= $pName ?>" loading="lazy">
        </div>
      </a>
      <div class="product-info">
        <div class="product-name"><a href="product.php?id=<?= $p['id'] ?>"><?= $pName ?></a></div>
        <div class="product-prices">
          <span class="price-current">LE <?= $pPrice ?></span>
          <?php if ($p['old_price'] && $p['old_price'] > $p['price']): ?>
          <span class="price-old">LE <?= number_format($p['old_price'], 0) ?></span>
          <?php endif; ?>
        </div>
        <?php if (!empty($sizes)): ?>
        <div class="product-sizes">
          <?php foreach ($sizes as $s): ?>
          <span class="size-tag" data-size="<?= htmlspecialchars($s) ?>" onclick="selectSize(this,'<?= $pName ?>','<?= $pPrice ?>','<?= $wa ?>')"><?= htmlspecialchars($s) ?></span>
          <?php endforeach; ?>
        </div>
        <?php endif; ?>
        <?php if ($p['in_stock']): ?>
        <button class="btn-whatsapp" onclick="orderWhatsapp('<?= $pName ?>','<?= $pPrice ?>','<?= $wa ?>')">
          <i class="fab fa-whatsapp"></i> اطلبي عبر واتساب
        </button>
        <?php endif; ?>
      </div>
    </div>
    <?php
}
?>

<!-- البانر الرئيسي -->
<section class="hero">
  <img class="hero-img" src="<?= htmlspecialchars($bannerMain['image'] ?? 'https://images.unsplash.com/photo-1558171813-34a5d04de9bf?w=1600&q=80') ?>" alt="<?= htmlspecialchars($bannerMain['title'] ?? '') ?>">
  <div class="hero-overlay">
    <div class="hero-content">
      <p class="hero-eyebrow">✦ مجموعة <?= date('Y') ?></p>
      <h1 class="hero-title"><?= htmlspecialchars($bannerMain['title'] ?? 'أناقة محتشمة') ?></h1>
      <p class="hero-sub"><?= htmlspecialchars($bannerMain['subtitle'] ?? 'تشكيلة فاخرة من العبايات والأزياء المحتشمة') ?></p>
      <a href="<?= htmlspecialchars($bannerMain['link'] ?? 'collection.php') ?>" class="btn-primary">
        <?= htmlspecialchars($bannerMain['button_text'] ?? 'تسوقي الآن') ?>
      </a>
    </div>
  </div>
</section>

<!-- قسم التصنيفات -->
<?php if (!empty($categories)): ?>
<section class="section" style="background:#fafafa">
  <div class="container">
    <div class="section-header">
      <h2>تسوقي حسب التصنيف</h2>
      <div class="divider"></div>
    </div>
    <div class="collections-grid">
      <?php foreach ($categories as $cat): ?>
      <a href="collection.php?cat=<?= urlencode($cat['slug']) ?>" class="collection-card">
        <img src="<?= htmlspecialchars($cat['image'] ?: 'https://placehold.co/400x530/f5f5f5/999') ?>" alt="<?= htmlspecialchars($cat['name']) ?>" loading="lazy">
        <div class="collection-card-overlay">
          <h3><?= htmlspecialchars($cat['name']) ?></h3>
        </div>
      </a>
      <?php endforeach; ?>
    </div>
  </div>
</section>
<?php endif; ?>

<!-- قسم العبايات -->
<?php if (!empty($abayaProducts)): ?>
<section class="section">
  <div class="container">
    <div class="section-header">
      <h2>العبايات</h2>
      <div class="divider"></div>
      <a href="collection.php?cat=abayas" style="display:inline-block;margin-top:14px;font-size:12px;letter-spacing:1.5px;text-transform:uppercase;font-weight:700;color:var(--gold);border-bottom:1px solid var(--gold)">عرض الكل</a>
    </div>
    <div class="products-grid">
      <?php foreach ($abayaProducts as $p) productCard($p, $whatsapp); ?>
    </div>
  </div>
</section>
<?php endif; ?>

<!-- قسم الملابس المحتشمة -->
<?php if (!empty($modestProducts)): ?>
<section class="section" style="background:#fafafa">
  <div class="container">
    <div class="section-header">
      <h2>الملابس المحتشمة</h2>
      <div class="divider"></div>
      <a href="collection.php?cat=modest-wear" style="display:inline-block;margin-top:14px;font-size:12px;letter-spacing:1.5px;text-transform:uppercase;font-weight:700;color:var(--gold);border-bottom:1px solid var(--gold)">عرض الكل</a>
    </div>
    <div class="products-grid">
      <?php foreach ($modestProducts as $p) productCard($p, $whatsapp); ?>
    </div>
  </div>
</section>
<?php endif; ?>

<!-- البانر الوسطي -->
<?php if ($bannerMiddle): ?>
<section class="middle-banner">
  <img src="<?= htmlspecialchars($bannerMiddle['image']) ?>" alt="<?= htmlspecialchars($bannerMiddle['title']) ?>">
  <div class="middle-banner-overlay">
    <h2><?= htmlspecialchars($bannerMiddle['title']) ?></h2>
    <?php if (!empty($bannerMiddle['subtitle'])): ?>
    <p><?= htmlspecialchars($bannerMiddle['subtitle']) ?></p>
    <?php endif; ?>
    <?php if (!empty($bannerMiddle['button_text'])): ?>
    <a href="<?= htmlspecialchars($bannerMiddle['link'] ?? '#') ?>" class="btn-primary">
      <?= htmlspecialchars($bannerMiddle['button_text']) ?>
    </a>
    <?php endif; ?>
  </div>
</section>
<?php endif; ?>

<!-- قسم الإسدال -->
<?php if (!empty($isdalProducts)): ?>
<section class="section">
  <div class="container">
    <div class="section-header">
      <h2>إسدال وملابس الصلاة</h2>
      <div class="divider"></div>
      <a href="collection.php?cat=isdals" style="display:inline-block;margin-top:14px;font-size:12px;letter-spacing:1.5px;text-transform:uppercase;font-weight:700;color:var(--gold);border-bottom:1px solid var(--gold)">عرض الكل</a>
    </div>
    <div class="products-grid">
      <?php foreach ($isdalProducts as $p) productCard($p, $whatsapp); ?>
    </div>
  </div>
</section>
<?php endif; ?>

<!-- قسم الحجاب -->
<?php if (!empty($hijabProducts)): ?>
<section class="section" style="background:#fafafa">
  <div class="container">
    <div class="section-header">
      <h2>الحجاب</h2>
      <div class="divider"></div>
      <a href="collection.php?cat=hijab" style="display:inline-block;margin-top:14px;font-size:12px;letter-spacing:1.5px;text-transform:uppercase;font-weight:700;color:var(--gold);border-bottom:1px solid var(--gold)">عرض الكل</a>
    </div>
    <div class="products-grid">
      <?php foreach ($hijabProducts as $p) productCard($p, $whatsapp); ?>
    </div>
  </div>
</section>
<?php endif; ?>

<!-- الأكثر مبيعاً -->
<?php if (!empty($bestSellers)): ?>
<section class="section">
  <div class="container">
    <div class="section-header">
      <h2>الأكثر مبيعاً</h2>
      <div class="divider"></div>
    </div>
    <div class="products-grid">
      <?php foreach ($bestSellers as $p) productCard($p, $whatsapp); ?>
    </div>
  </div>
</section>
<?php endif; ?>

<!-- وصل حديثاً -->
<?php if (!empty($newArrivals)): ?>
<section class="section" style="background:#fafafa">
  <div class="container">
    <div class="section-header">
      <h2>وصل حديثاً</h2>
      <div class="divider"></div>
    </div>
    <div class="products-grid">
      <?php foreach ($newArrivals as $p) productCard($p, $whatsapp); ?>
    </div>
  </div>
</section>
<?php endif; ?>

<?php include 'footer.php'; ?>
