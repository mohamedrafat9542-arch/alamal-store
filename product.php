<?php
// ==========================================
// صفحة المنتج - متجر الأمل
// ==========================================
require_once 'config.php';

$id = (int)($_GET['id'] ?? 0);
if (!$id) { header('Location: index.php'); exit; }

try {
    $db = getDB();
    $stmt = $db->prepare("SELECT p.*, c.name as cat_name, c.slug as cat_slug FROM products p LEFT JOIN categories c ON p.category_id=c.id WHERE p.id=?");
    $stmt->execute([$id]);
    $product = $stmt->fetch();
    if (!$product) { header('Location: index.php'); exit; }

    // منتجات مشابهة
    $related = $db->prepare("SELECT * FROM products WHERE category_id=? AND id!=? AND in_stock=1 ORDER BY RAND() LIMIT 4");
    $related->execute([$product['category_id'], $id]);
    $relatedProducts = $related->fetchAll();

    $whatsapp = getSetting('whatsapp', '201000000000');
    $pageTitle = htmlspecialchars($product['name']) . ' - ' . getSetting('site_name', 'الأمل');
} catch (Exception $e) {
    header('Location: index.php'); exit;
}

$sizes = array_filter(array_map('trim', explode(',', $product['sizes'] ?? '')));

$galleryImages = array_values(array_unique(array_filter([$product['image1'] ?? '', $product['image2'] ?? ''])));
if (empty($galleryImages)) $galleryImages = ['https://placehold.co/600x800/f5f5f5/999?text=No+Image'];

include 'header.php';
?>

<!-- تفاصيل المنتج -->
<div class="product-detail">
  <!-- معرض الصور -->
  <div class="product-gallery">
    <div class="product-gallery-main" id="main-img-wrap" onclick="openLightbox()">
      <img id="main-img" src="<?= htmlspecialchars($galleryImages[0]) ?>" alt="<?= htmlspecialchars($product['name']) ?>">
      <div class="zoom-hint"><i class="fas fa-search-plus"></i> مرري للتكبير · اضغطي للعرض الكامل</div>
    </div>
    <?php if (count($galleryImages) > 1): ?>
    <div class="product-gallery-thumbs">
      <?php foreach ($galleryImages as $i => $img): ?>
      <div class="thumb<?= $i === 0 ? ' active' : '' ?>" onclick="switchImg(<?= $i ?>, this)">
        <img src="<?= htmlspecialchars($img) ?>" alt="">
      </div>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>
  </div>

  <!-- معلومات المنتج -->
  <div class="product-detail-info">
    <!-- breadcrumb -->
    <div class="breadcrumb">
      <a href="index.php">الرئيسية</a> &nbsp;/&nbsp;
      <?php if ($product['cat_name']): ?>
      <a href="collection.php?cat=<?= urlencode($product['cat_slug']) ?>"><?= htmlspecialchars($product['cat_name']) ?></a> &nbsp;/&nbsp;
      <?php endif; ?>
      <?= htmlspecialchars($product['name']) ?>
    </div>

    <?php if ($product['is_new_arrival']): ?>
    <span style="background:#C9A96E;color:#fff;font-size:11px;font-weight:700;padding:4px 12px;letter-spacing:1px;text-transform:uppercase;display:inline-block;margin-bottom:12px">NEW</span>
    <?php endif; ?>

    <h1 class="product-detail-title"><?= htmlspecialchars($product['name']) ?></h1>

    <div class="product-detail-price">
      <?php if ($product['old_price'] && $product['old_price'] > $product['price']): ?>
      <span class="old">LE <?= number_format($product['old_price'], 0) ?></span>
      <?php endif; ?>
      LE <?= number_format($product['price'], 0) ?>
    </div>

    <?php if (!empty($sizes)): ?>
    <div class="size-select-label">اختاري المقاس</div>
    <div class="size-buttons" id="size-buttons">
      <?php foreach ($sizes as $s): ?>
      <button class="size-btn" onclick="chooseSize(this, '<?= htmlspecialchars($s) ?>')"><?= htmlspecialchars($s) ?></button>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <?php if ($product['in_stock']): ?>
    <button class="btn-whatsapp-large" onclick="orderNow()">
      <i class="fab fa-whatsapp" style="font-size:22px"></i>
      اطلبي الآن عبر واتساب
    </button>
    <?php else: ?>
    <div style="background:#f8d7da;border:1px solid #f5c6cb;color:#721c24;padding:14px;border-radius:6px;margin-bottom:20px;font-weight:600;text-align:center">
      ⚠️ هذا المنتج غير متوفر حالياً
    </div>
    <?php endif; ?>

    <!-- الوصف -->
    <?php if ($product['description']): ?>
    <div class="product-description">
      <h4 style="font-size:14px;font-weight:700;margin-bottom:12px;text-transform:uppercase;letter-spacing:0.5px">تفاصيل المنتج</h4>
      <p><?= nl2br(htmlspecialchars($product['description'])) ?></p>
    </div>
    <?php endif; ?>

    <!-- معلومات الشحن -->
    <div style="margin-top:24px;padding:16px;background:#f9f9f9;border-radius:6px">
      <p style="font-size:13px;color:#555;display:flex;align-items:center;gap:8px;margin-bottom:8px">
        <i class="fas fa-truck" style="color:#C9A96E"></i> شحن سريع لجميع المحافظات
      </p>
      <?php if (getSetting('return_enabled', '1') === '1'): ?>
      <p style="font-size:13px;color:#555;display:flex;align-items:center;gap:8px;margin-bottom:8px">
        <i class="fas fa-undo" style="color:#C9A96E"></i> <?= htmlspecialchars(getSetting('return_title','إرجاع مجاني')) ?> - <?= htmlspecialchars(getSetting('return_text','أرجعي المنتج خلال 14 يوم')) ?>
      </p>
      <?php endif; ?>
      <p style="font-size:13px;color:#555;display:flex;align-items:center;gap:8px">
        <i class="fas fa-shield-alt" style="color:#C9A96E"></i> ضمان جودة المنتج
      </p>
    </div>
  </div>
</div>

<!-- منتجات مشابهة -->
<?php if (!empty($relatedProducts)): ?>
<section class="section" style="background:#fafafa">
  <div class="container">
    <div class="section-header">
      <h2>قد يعجبك أيضاً</h2>
      <div class="divider"></div>
    </div>
    <div class="products-grid">
      <?php foreach ($relatedProducts as $p):
        $psizes = array_filter(array_map('trim', explode(',', $p['sizes'] ?? '')));
        $pName = htmlspecialchars($p['name']);
        $pPrice = number_format($p['price'], 0);
        ?>
        <div class="product-card" data-name="<?= $pName ?>">
          <a href="product.php?id=<?= $p['id'] ?>">
            <div class="product-image-wrap">
              <img class="product-img-1" src="<?= htmlspecialchars($p['image1'] ?: 'https://placehold.co/400x530/f5f5f5/999?text=No+Image') ?>" alt="<?= $pName ?>" loading="lazy">
              <img class="product-img-2" src="<?= htmlspecialchars($p['image2'] ?: $p['image1'] ?: 'https://placehold.co/400x530/f5f5f5/999?text=No+Image') ?>" alt="<?= $pName ?>" loading="lazy">
            </div>
          </a>
          <div class="product-info">
            <div class="product-name"><a href="product.php?id=<?= $p['id'] ?>"><?= $pName ?></a></div>
            <div class="product-prices"><span class="price-current">LE <?= $pPrice ?></span></div>
            <?php if (!empty($psizes)): ?>
            <div class="product-sizes">
              <?php foreach ($psizes as $s): ?>
              <span class="size-tag" data-size="<?= htmlspecialchars($s) ?>" onclick="selectSize(this,'<?= $pName ?>','<?= $pPrice ?>','<?= $whatsapp ?>')"><?= htmlspecialchars($s) ?></span>
              <?php endforeach; ?>
            </div>
            <?php endif; ?>
            <button class="btn-whatsapp" onclick="orderWhatsapp('<?= $pName ?>','<?= $pPrice ?>','<?= $whatsapp ?>')">
              <i class="fab fa-whatsapp"></i> اطلبي عبر واتساب
            </button>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>
<?php endif; ?>

<!-- العرض الكامل للصورة (Lightbox) -->
<div class="lightbox-overlay" id="lightbox-overlay">
  <button class="lightbox-close" onclick="closeLightbox()" aria-label="إغلاق"><i class="fas fa-times"></i></button>
  <button class="lightbox-nav lightbox-prev" onclick="lightboxNav(-1)" aria-label="السابق"><i class="fas fa-chevron-right"></i></button>
  <img id="lightbox-img" class="lightbox-img" src="" alt="">
  <button class="lightbox-nav lightbox-next" onclick="lightboxNav(1)" aria-label="التالي"><i class="fas fa-chevron-left"></i></button>
  <div class="lightbox-counter" id="lightbox-counter"></div>
</div>

<script>
const productName  = "<?= addslashes($product['name']) ?>";
const productPrice = "<?= number_format($product['price'], 0) ?>";
const waNumber     = "<?= $whatsapp ?>";
let selectedSize   = '';

const galleryImages = <?= json_encode($galleryImages, JSON_UNESCAPED_SLASHES) ?>;
let currentIndex = 0;

// ===== التبديل بين الصور المصغرة =====
function switchImg(index, el) {
  currentIndex = index;
  document.getElementById('main-img').src = galleryImages[index];
  document.querySelectorAll('.thumb').forEach(t => t.classList.remove('active'));
  el.classList.add('active');
}

// ===== تكبير الصورة بحركة الماوس (Zoom) =====
(function() {
  const wrap = document.getElementById('main-img-wrap');
  const img  = document.getElementById('main-img');
  if (!wrap || !img) return;

  // نتأكد إن في وحدة إدخال بتدعم hover (ماوس) قبل ما نفعّل الزووم
  let supportsHover = true;
  try { supportsHover = window.matchMedia('(any-hover: hover)').matches; } catch (e) {}
  if (!supportsHover) return;

  wrap.addEventListener('mousemove', function(e) {
    const rect = wrap.getBoundingClientRect();
    const x = ((e.clientX - rect.left) / rect.width)  * 100;
    const y = ((e.clientY - rect.top)  / rect.height) * 100;
    img.style.transformOrigin = x + '% ' + y + '%';
    wrap.classList.add('zoomed');
  });

  wrap.addEventListener('mouseleave', function() {
    wrap.classList.remove('zoomed');
    img.style.transformOrigin = 'center center';
  });
})();

// ===== العرض الكامل للصورة (Lightbox) =====
const lightboxOverlay = document.getElementById('lightbox-overlay');
const lightboxImg     = document.getElementById('lightbox-img');
const lightboxCounter = document.getElementById('lightbox-counter');

function updateLightbox() {
  lightboxImg.src = galleryImages[currentIndex];
  lightboxCounter.textContent = galleryImages.length > 1 ? (currentIndex + 1) + ' / ' + galleryImages.length : '';
  document.querySelectorAll('.lightbox-nav').forEach(b => b.style.display = galleryImages.length > 1 ? 'flex' : 'none');
  document.querySelectorAll('.thumb').forEach((t, i) => t.classList.toggle('active', i === currentIndex));
  document.getElementById('main-img').src = galleryImages[currentIndex];
}

function openLightbox() {
  updateLightbox();
  lightboxOverlay.classList.add('open');
  document.body.style.overflow = 'hidden';
}

function closeLightbox() {
  lightboxOverlay.classList.remove('open');
  document.body.style.overflow = '';
}

function lightboxNav(dir) {
  currentIndex = (currentIndex + dir + galleryImages.length) % galleryImages.length;
  updateLightbox();
}

// إغلاق بالنقر على الخلفية المظلمة
lightboxOverlay.addEventListener('click', function(e) {
  if (e.target === lightboxOverlay) closeLightbox();
});

// التنقل بلوحة المفاتيح
document.addEventListener('keydown', function(e) {
  if (!lightboxOverlay.classList.contains('open')) return;
  if (e.key === 'Escape')     closeLightbox();
  if (e.key === 'ArrowLeft')  lightboxNav(1);
  if (e.key === 'ArrowRight') lightboxNav(-1);
});

// السحب على الموبايل للتنقل بين الصور
(function() {
  let touchStartX = 0;
  lightboxOverlay.addEventListener('touchstart', e => touchStartX = e.changedTouches[0].clientX, {passive: true});
  lightboxOverlay.addEventListener('touchend', e => {
    const diff = e.changedTouches[0].clientX - touchStartX;
    if (Math.abs(diff) > 50) lightboxNav(diff > 0 ? -1 : 1);
  }, {passive: true});
})();

function chooseSize(el, size) {
  document.querySelectorAll('.size-btn').forEach(b => b.classList.remove('active'));
  el.classList.add('active');
  selectedSize = size;
}
function orderNow() {
  if (!selectedSize && document.querySelectorAll('.size-btn').length > 0) {
    alert('من فضلك اختاري المقاس أولاً');
    return;
  }
  const sizeText = selectedSize ? `\nالمقاس: ${selectedSize}` : '';
  const msg = `مرحباً، أريد طلب:\n\nالمنتج: ${productName}${sizeText}\nالسعر: ${productPrice} LE\n\nهل المنتج متاح؟`;
  window.open(`https://wa.me/${waNumber}?text=${encodeURIComponent(msg)}`, '_blank');
}
</script>

<?php include 'footer.php'; ?>
