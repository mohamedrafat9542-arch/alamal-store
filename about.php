<?php
require_once 'config.php';
$pageTitle = 'من نحن - ' . getSetting('site_name', 'الأمل');
include 'header.php';
$aboutText = getSetting('about_text','متجر الأمل للأزياء المحتشمة.');
$phone     = getSetting('phone','');
$email     = getSetting('email','');
$whatsapp  = getSetting('whatsapp','201000000000');
?>
<section style="padding:80px 20px;background:#fafafa">
  <div class="container" style="max-width:800px;margin:0 auto;text-align:center">
    <h1 style="font-family:'Playfair Display',serif;font-size:42px;margin-bottom:20px">من نحن</h1>
    <div style="width:50px;height:2px;background:#C9A96E;margin:0 auto 30px"></div>
    <p style="font-size:17px;line-height:1.9;color:#555;margin-bottom:40px"><?= nl2br(htmlspecialchars($aboutText)) ?></p>
    <div style="display:flex;gap:20px;justify-content:center;flex-wrap:wrap">
      <?php if ($phone): ?>
      <a href="tel:<?= htmlspecialchars($phone) ?>" style="display:inline-flex;align-items:center;gap:8px;background:#1A1A1A;color:#fff;padding:14px 28px;border-radius:4px;font-size:14px;font-weight:600">
        <i class="fas fa-phone"></i> <?= htmlspecialchars($phone) ?>
      </a>
      <?php endif; ?>
      <a href="https://wa.me/<?= htmlspecialchars($whatsapp) ?>" target="_blank" style="display:inline-flex;align-items:center;gap:8px;background:#25D366;color:#fff;padding:14px 28px;border-radius:4px;font-size:14px;font-weight:600">
        <i class="fab fa-whatsapp"></i> تواصلي عبر واتساب
      </a>
    </div>
  </div>
</section>
<?php include 'footer.php'; ?>
