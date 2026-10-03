<?php
// ==========================================
// صفحة التصنيف / البحث - متجر الأمل
// ==========================================
require_once 'config.php';

$cat    = $_GET['cat']    ?? '';
$filter = $_GET['filter'] ?? '';
$q      = trim($_GET['q'] ?? '');

try {
    $db = getDB();
    $whatsapp = getSetting('whatsapp', '201000000000');

    // تحديد العنوان والاستعلام
    $title = 'جميع المنتجات';
    $params = [];

    if ($q) {
        $title = 'نتائج البحث عن: ' . htmlspecialchars($q);
        $sql = "SELECT p.*, c.name as cat_name FROM products p LEFT JOIN categories c ON p.category_id=c.id WHERE p.name LIKE ? OR p.description LIKE ? ORDER BY p.created_at DESC";
        $params = ["%$q%", "%$q%"];

    } elseif ($filter === 'best_seller') {
        $title = 'الأكثر مبيعاً';
        $sql = "SELECT p.*, c.name as cat_name FROM products p LEFT JOIN categories c ON p.category_id=c.id WHERE p.is_best_seller=1 ORDER BY p.created_at DESC";

    } elseif ($filter === 'new_arrival') {
        $title = 'وصل حديثاً';
        $sql = "SELECT p.*, c.name as cat_name FROM products p LEFT JOIN categories c ON p.category_id=c.id WHERE p.is_new_arrival=1 ORDER BY p.created_at DESC";

    } elseif ($filter === 'sale') {
        $title = 'التخفيضات';
        $sql = "SELECT p.*, c.name as cat_name FROM products p LEFT JOIN categories c ON p.category_id=c.id WHERE p.old_price IS NOT NULL AND p.old_price > p.price ORDER BY p.created_at DESC";

    } elseif ($cat) {
        $catRow = $db->prepare("SELECT * FROM categories WHERE slug=?");
        $catRow->execute([$cat]);
        $catData = $catRow->fetch();
        $title = $catData ? $catData['name'] : 'تصنيف';
        $sql = "SELECT p.*, c.name as cat_name FROM products p LEFT JOIN categories c ON p.category_id=c.id WHERE c.slug=? ORDER BY p.created_at DESC";
        $params = [$cat];

    } else {
        $sql = "SELECT p.*, c.name as cat_name FROM products p LEFT JOIN categories c ON p.category_id=c.id ORDER BY p.created_at DESC";
    }

    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $products = $stmt->fetchAll();
    $pageTitle = $title . ' - ' . getSetting('site_name', 'الأمل');

} catch (Exception $e) {
    $products = [];
    $title = 'المنتجات';
    $whatsapp = '201000000000';
    $pageTitle = 'المنتجات';
}

include 'header.php';
?>

<!-- عنوان الصفحة -->
<div class="collection-header">
  <h1><?= htmlspecialchars($title) ?></h1>
  <p style="color:#888;font-size:14px;margin-top:6px"><?= count($products) ?> منتج</p>
</div>

<!-- شبكة المنتجات -->
<section class="section">
  <div class="container">
    <?php if (empty($products)): ?>
    <div class="empty-state">
      <i class="fas fa-search" style="font-size:48px;color:#ddd;margin-bottom:16px;display:block"></i>
      <h3>لا توجد منتجات</h3>
      <p>لم يتم العثور على منتجات في هذا التصنيف حالياً.</p>
      <a href="index.php" class="btn-primary" style="margin-top:20px;display:inline-block">العودة للرئيسية</a>
    </div>
    <?php else: ?>
    <div class="products-grid">
      <?php foreach ($products as $p):
        $sizes = array_filter(array_map('trim', explode(',', $p['sizes'] ?? '')));
        $pName = htmlspecialchars($p['name']);
        $pPrice = number_format($p['price'], 0);
        $badge = '';
        if ($p['is_new_arrival']) $badge = '<span class="badge new">New</span>';
        if ($p['old_price'] && $p['old_price'] > $p['price']) $badge = '<span class="badge sale">Sale</span>';
        ?>
        <div class="product-card" data-name="<?= $pName ?>">
          <a href="product.php?id=<?= $p['id'] ?>">
            <div class="product-image-wrap">
              <?= $badge ?>
              <?php if (!$p['in_stock']): ?>
              <div class="sold-out-overlay"><span>نفذت الكمية</span></div>
              <?php endif; ?>
              <img class="product-img-1" src="<?= htmlspecialchars($p['image1'] ?: 'https://placehold.co/400x530/f5f5f5/999') ?>" alt="<?= $pName ?>" loading="lazy">
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
              <span class="size-tag" data-size="<?= htmlspecialchars($s) ?>" onclick="selectSize(this,'<?= $pName ?>','<?= $pPrice ?>','<?= $whatsapp ?>')"><?= htmlspecialchars($s) ?></span>
              <?php endforeach; ?>
            </div>
            <?php endif; ?>
            <?php if ($p['in_stock']): ?>
            <button class="btn-whatsapp" onclick="orderWhatsapp('<?= $pName ?>','<?= $pPrice ?>','<?= $whatsapp ?>')">
              <i class="fab fa-whatsapp"></i> اطلبي عبر واتساب
            </button>
            <?php endif; ?>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>
  </div>
</section>

<?php include 'footer.php'; ?>
