<?php
// ==========================================
// Footer مشترك - متجر الأمل
// ==========================================
$aboutText  = getSetting('about_text', 'متجر الأمل للأزياء المحتشمة.');
$phone      = getSetting('phone', '+201000000000');
$email      = getSetting('email', 'info@example.com');
$facebook   = getSetting('facebook', '#');
$instagram  = getSetting('instagram', '#');
$tiktok     = getSetting('tiktok', '#');
$pinterest  = getSetting('pinterest', '#');
$whatsappFt = getSetting('whatsapp', '201000000000');
$siteName2  = getSetting('site_name', 'الأمل');
?>

<!-- شريط المميزات -->
<section class="features-bar section-sm">
  <div class="container">
    <div class="features-grid">
      <div class="feature-item">
        <div class="feature-icon"><i class="fas fa-truck"></i></div>
        <div class="feature-text">
          <h4>شحن مجاني</h4>
          <p>على الطلبات فوق <?= getSetting('free_shipping_min', '3000') ?> LE</p>
        </div>
      </div>
      <div class="feature-item">
        <div class="feature-icon"><i class="fas fa-shield-alt"></i></div>
        <div class="feature-text">
          <h4>دفع آمن</h4>
          <p>حلول دفع آمنة ومضمونة</p>
        </div>
      </div>
      <div class="feature-item">
        <div class="feature-icon"><i class="fas fa-headset"></i></div>
        <div class="feature-text">
          <h4>دعم 24/7</h4>
          <p>نحن دائماً هنا لمساعدتك</p>
        </div>
      </div>
      <?php if (getSetting('return_enabled', '1') === '1'): ?>
      <div class="feature-item">
        <div class="feature-icon"><i class="fas fa-undo"></i></div>
        <div class="feature-text">
          <h4><?= htmlspecialchars(getSetting('return_title', 'إرجاع مجاني')) ?></h4>
          <p><?= htmlspecialchars(getSetting('return_text', 'أرجعي المنتج خلال 14 يوم')) ?></p>
        </div>
      </div>
      <?php endif; ?>
    </div>
  </div>
</section>

<!-- الفوتر -->
<footer class="footer">
  <div class="footer-grid">
    <!-- العلامة التجارية -->
    <div class="footer-brand">
      <div class="logo"><?= htmlspecialchars($siteName2) ?> <span style="color:#C9A96E">✦</span></div>
      <p><?= htmlspecialchars($aboutText) ?></p>
      <p style="margin-top:12px;font-size:13px;color:rgba(255,255,255,.5)">
        <i class="fas fa-phone" style="color:#C9A96E;margin-left:6px"></i> <?= htmlspecialchars($phone) ?><br>
        <i class="fas fa-envelope" style="color:#C9A96E;margin-left:6px;margin-top:6px;display:inline-block"></i> <?= htmlspecialchars($email) ?>
      </p>
      <div class="footer-social">
        <?php if ($facebook && $facebook !== '#'): ?>
        <a href="<?= htmlspecialchars($facebook) ?>" target="_blank" class="social-btn"><i class="fab fa-facebook-f"></i></a>
        <?php endif; ?>
        <?php if ($instagram && $instagram !== '#'): ?>
        <a href="<?= htmlspecialchars($instagram) ?>" target="_blank" class="social-btn"><i class="fab fa-instagram"></i></a>
        <?php endif; ?>
        <?php if ($tiktok && $tiktok !== '#'): ?>
        <a href="<?= htmlspecialchars($tiktok) ?>" target="_blank" class="social-btn"><i class="fab fa-tiktok"></i></a>
        <?php endif; ?>
        <?php if ($pinterest && $pinterest !== '#'): ?>
        <a href="<?= htmlspecialchars($pinterest) ?>" target="_blank" class="social-btn"><i class="fab fa-pinterest-p"></i></a>
        <?php endif; ?>
        <a href="https://wa.me/<?= htmlspecialchars($whatsappFt) ?>" target="_blank" class="social-btn" style="background:#25D366;border-color:#25D366"><i class="fab fa-whatsapp"></i></a>
      </div>
    </div>

    <!-- التصنيفات -->
    <div class="footer-col">
      <h4>التصنيفات</h4>
      <ul>
        <li><a href="collection.php?filter=best_seller">الأكثر مبيعاً</a></li>
        <li><a href="collection.php?filter=new_arrival">وصل حديثاً</a></li>
        <?php foreach ($cats as $cat): ?>
        <li><a href="collection.php?cat=<?= urlencode($cat['slug']) ?>"><?= htmlspecialchars($cat['name']) ?></a></li>
        <?php endforeach; ?>
        <li><a href="collection.php?filter=sale">التخفيضات</a></li>
      </ul>
    </div>

    <!-- السياسات -->
    <div class="footer-col">
      <h4>السياسات</h4>
      <ul>
        <li><a href="#">سياسة الإرجاع</a></li>
        <li><a href="#">سياسة الخصوصية</a></li>
        <li><a href="#">معلومات التواصل</a></li>
        <li><a href="about.php">من نحن</a></li>
      </ul>
    </div>

    <!-- مشاركة الموقع -->
    <div class="footer-col">
      <h4><?= htmlspecialchars(getSetting('share_title', 'شاركي مع صديقاتك')) ?></h4>
      <p style="font-size:13px;margin-bottom:16px;line-height:1.7"><?= htmlspecialchars(getSetting('share_text', 'شاركي الموقع مع صديقاتك واحصلي على مفاجآت وخصومات حصرية!')) ?></p>
      <button id="share-site-btn"
        data-url="<?= htmlspecialchars(getSetting('share_link', SITE_URL)) ?>"
        data-title="<?= htmlspecialchars($siteName2) ?>"
        data-text="<?= htmlspecialchars(getSetting('share_text', 'شاركي الموقع مع صديقاتك واحصلي على مفاجآت وخصومات حصرية!')) ?>"
        style="padding:10px 20px;background:#C9A96E;color:#fff;border:none;border-radius:4px;cursor:pointer;font-size:13px;font-weight:700;display:inline-flex;align-items:center;gap:8px">
        <i class="fas fa-share-alt"></i> <?= htmlspecialchars(getSetting('share_button_text', 'شاركي الآن')) ?>
      </button>
      <div style="margin-top:20px">
        <a href="https://wa.me/<?= htmlspecialchars($whatsappFt) ?>" target="_blank" style="display:inline-flex;align-items:center;gap:8px;background:#25D366;color:#fff;padding:10px 20px;border-radius:6px;font-size:14px;font-weight:700">
          <i class="fab fa-whatsapp" style="font-size:18px"></i> اطلبي عبر واتساب
        </a>
      </div>
    </div>
  </div>

  <!-- حقوق النشر -->
  <div class="footer-bottom">
    <div class="container">
      &copy; <?= date('Y') ?> <?= htmlspecialchars($siteName2) ?>. جميع الحقوق محفوظة.
    </div>
  </div>
</footer>

<!-- JavaScript -->
<script>
// قائمة الموبايل
function toggleMobileNav() {
  document.getElementById('mobile-nav').classList.toggle('open');
  document.getElementById('overlay-bg').classList.toggle('open');
}

// تبديل الوضع الليلي/النهاري
(function() {
  function applyTheme(theme) {
    document.documentElement.setAttribute('data-theme', theme);
    try { localStorage.setItem('theme', theme); } catch (e) {}
  }
  function currentTheme() {
    return document.documentElement.getAttribute('data-theme') === 'dark' ? 'dark' : 'light';
  }
  function toggleTheme() {
    applyTheme(currentTheme() === 'dark' ? 'light' : 'dark');
  }
  // نستخدم event delegation على مستوى الصفحة كلها بدل ربط الزرار مباشرة،
  // عشان يشتغل مضمون 100% مهما كان ترتيب تحميل السكريبتات
  document.addEventListener('click', function(e) {
    if (e.target.closest('#theme-toggle') || e.target.closest('#theme-toggle-mobile')) {
      toggleTheme();
    }
  });
})();

// البحث
function toggleSearch() {
  const overlay = document.getElementById('search-overlay');
  overlay.classList.toggle('open');
  if (overlay.classList.contains('open')) {
    setTimeout(() => document.getElementById('search-input').focus(), 100);
  }
}
document.addEventListener('keydown', e => {
  if (e.key === 'Escape') {
    document.getElementById('search-overlay').classList.remove('open');
    document.getElementById('mobile-nav').classList.remove('open');
    document.getElementById('overlay-bg').classList.remove('open');
  }
});

// اختيار المقاس وإرسال واتساب
function selectSize(el, productName, price, waNumber) {
  document.querySelectorAll('.size-tag').forEach(t => t.classList.remove('active'));
  el.classList.add('active');
  const size = el.dataset.size;
  const card = el.closest('.product-card');
  if (card) {
    card.dataset.selectedSize = size;
  }
}

function orderWhatsapp(productName, price, waNumber) {
  const card = document.querySelector(`.product-card[data-name="${productName}"]`);
  const size = card ? (card.dataset.selectedSize || 'غير محدد') : 'غير محدد';
  const msg = `مرحباً، أريد طلب:\n\nالمنتج: ${productName}\nالمقاس: ${size}\nالسعر: ${price} LE\n\nهل المنتج متاح؟`;
  window.open(`https://wa.me/${waNumber}?text=${encodeURIComponent(msg)}`, '_blank');
}

// مشاركة الموقع مع الأصدقاء
const shareBtn = document.getElementById('share-site-btn');
if (shareBtn) {
  shareBtn.addEventListener('click', function() {
    const url   = this.dataset.url;
    const title = this.dataset.title;
    const text  = this.dataset.text;

    if (navigator.share) {
      // على الموبايل: بتفتح قائمة المشاركة الأصلية (واتساب، فيسبوك، نسخ الرابط...)
      navigator.share({ title: title, text: text, url: url }).catch(() => {});
    } else {
      // على الديسكتوب اللي مش بيدعم navigator.share: نفتح مشاركة واتساب مباشرة
      const waText = encodeURIComponent(text + '\n' + url);
      window.open(`https://wa.me/?text=${waText}`, '_blank');
    }
  });
}
</script>
</body>
</html>
