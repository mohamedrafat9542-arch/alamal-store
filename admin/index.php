<?php
// ==========================================
// لوحة التحكم - متجر الأمل
// ==========================================
session_start();
require_once '../config.php';

// ==========================================
// دوال رفع الصور من الجهاز
// ==========================================

// يبني رابط الصورة من جذر الموقع (مش من مجلد admin) عشان يشتغل صح في كل الصفحات
function getUploadUrl($filename) {
    $siteBasePath = rtrim(str_replace('\\', '/', dirname(dirname($_SERVER['SCRIPT_NAME']))), '/');
    return $siteBasePath . '/uploads/' . $filename;
}

// تعالج حقل صورة واحد: لو المستخدم رفع ملف من جهازه بتستخدمه، وإلا بتستخدم الرابط المكتوب،
// وإلا بترجع القيمة الحالية (عند التعديل) عشان الصورة القديمة متتمسحش من غير قصد
function processImage($fileField, $urlField, $fallback = '') {
    global $formErrors;

    if (!empty($_FILES[$fileField]['name']) && $_FILES[$fileField]['error'] === UPLOAD_ERR_OK) {
        $allowedExt = ['jpg', 'jpeg', 'png', 'webp', 'gif'];
        $maxSize    = 5 * 1024 * 1024; // 5 ميجابايت
        $origName   = $_FILES[$fileField]['name'];
        $ext        = strtolower(pathinfo($origName, PATHINFO_EXTENSION));

        if (!in_array($ext, $allowedExt)) {
            $formErrors[] = 'صيغة الصورة "' . htmlspecialchars($origName) . '" غير مدعومة (المسموح: JPG, PNG, WEBP, GIF)';
            $urlVal = trim($_POST[$urlField] ?? '');
            return $urlVal !== '' ? $urlVal : $fallback;
        }
        if ($_FILES[$fileField]['size'] > $maxSize) {
            $formErrors[] = 'حجم الصورة "' . htmlspecialchars($origName) . '" أكبر من 5 ميجابايت';
            $urlVal = trim($_POST[$urlField] ?? '');
            return $urlVal !== '' ? $urlVal : $fallback;
        }

        $uploadDir = __DIR__ . '/../uploads/';
        if (!is_dir($uploadDir)) @mkdir($uploadDir, 0755, true);

        $newName  = 'img_' . date('YmdHis') . '_' . mt_rand(1000, 9999) . '.' . $ext;
        $destPath = $uploadDir . $newName;

        if (move_uploaded_file($_FILES[$fileField]['tmp_name'], $destPath)) {
            return getUploadUrl($newName);
        }
        $formErrors[] = 'فشل رفع الصورة "' . htmlspecialchars($origName) . '"، تأكد من صلاحيات مجلد uploads';
    }

    $urlVal = trim($_POST[$urlField] ?? '');
    return $urlVal !== '' ? $urlVal : $fallback;
}

$formErrors = [];

// تسجيل الدخول والخروج
if (($_POST['action'] ?? '') === 'login') {
    $storedHash = getSetting('admin_password_hash', '');
    $inputPass  = $_POST['password'] ?? '';
    $valid      = ($storedHash !== '') ? password_verify($inputPass, $storedHash) : ($inputPass === ADMIN_PASSWORD);

    if ($valid) {
        $_SESSION['admin_logged_in'] = true;
        header('Location: index.php');
        exit;
    } else {
        $loginError = 'كلمة المرور غير صحيحة';
    }
}
if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: index.php');
    exit;
}

// التحقق من تسجيل الدخول
if (!($_SESSION['admin_logged_in'] ?? false)) {
    showLoginPage($loginError ?? null);
    exit;
}

$db  = getDB();
$tab = $_GET['tab'] ?? 'dashboard';
$msg = '';
$msgType = 'success';

// ==========================================
// معالجة النماذج
// ==========================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // إضافة منتج
    if ($action === 'add_product') {
        $image1 = processImage('image1_file', 'image1', '');
        $image2 = processImage('image2_file', 'image2', '');
        $sizes  = implode(',', $_POST['sizes'] ?? []);
        $stmt = $db->prepare("INSERT INTO products (name, description, price, old_price, category_id, image1, image2, sizes, is_best_seller, is_new_arrival, in_stock) VALUES (?,?,?,?,?,?,?,?,?,?,?)");
        $stmt->execute([
            trim($_POST['name']),
            trim($_POST['description']),
            (float)$_POST['price'],
            ($_POST['old_price'] ? (float)$_POST['old_price'] : null),
            ($_POST['category_id'] ? (int)$_POST['category_id'] : null),
            $image1,
            $image2,
            $sizes,
            isset($_POST['is_best_seller']) ? 1 : 0,
            isset($_POST['is_new_arrival']) ? 1 : 0,
            isset($_POST['in_stock']) ? 1 : 0,
        ]);
        if (!empty($formErrors)) { $msg = implode(' | ', $formErrors); $msgType = 'error'; }
        else { $msg = 'تم إضافة المنتج بنجاح ✓'; }
        $tab = 'products';
    }

    // تعديل منتج
    if ($action === 'edit_product') {
        $pid = (int)$_POST['product_id'];

        // نجيب الصور الحالية عشان نستخدمها كقيمة احتياطية لو مفيش رفع أو رابط جديد
        $curStmt = $db->prepare("SELECT image1, image2 FROM products WHERE id=?");
        $curStmt->execute([$pid]);
        $curImg = $curStmt->fetch() ?: ['image1' => '', 'image2' => ''];

        $image1 = processImage('image1_file', 'image1', $curImg['image1']);
        $image2 = processImage('image2_file', 'image2', $curImg['image2']);
        $sizes  = implode(',', $_POST['sizes'] ?? []);

        $stmt = $db->prepare("UPDATE products SET name=?, description=?, price=?, old_price=?, category_id=?, image1=?, image2=?, sizes=?, is_best_seller=?, is_new_arrival=?, in_stock=? WHERE id=?");
        $stmt->execute([
            trim($_POST['name']),
            trim($_POST['description']),
            (float)$_POST['price'],
            ($_POST['old_price'] ? (float)$_POST['old_price'] : null),
            ($_POST['category_id'] ? (int)$_POST['category_id'] : null),
            $image1,
            $image2,
            $sizes,
            isset($_POST['is_best_seller']) ? 1 : 0,
            isset($_POST['is_new_arrival']) ? 1 : 0,
            isset($_POST['in_stock']) ? 1 : 0,
            $pid,
        ]);
        if (!empty($formErrors)) { $msg = implode(' | ', $formErrors); $msgType = 'error'; }
        else { $msg = 'تم تحديث المنتج بنجاح ✓'; }
        $tab = 'products';
    }

    // حذف منتج
    if ($action === 'delete_product') {
        $db->prepare("DELETE FROM products WHERE id=?")->execute([(int)$_POST['id']]);
        $msg = 'تم حذف المنتج ✓';
        $tab = 'products';
    }

    // إضافة تصنيف
    if ($action === 'add_category') {
        $name = trim($_POST['name']);
        $slug = strtolower(preg_replace('/[^a-z0-9]+/', '-', trim($_POST['slug'])));
        $slug = trim($slug, '-');
        if ($slug === '') { $slug = 'category-' . time(); } // لو مفيش Slug صالح اتكتب، نولّد واحد تلقائي

        // نتأكد إن الـ slug مش مكرر، ولو مكرر نضيف رقم تلقائي بدل ما نفشل بصمت
        $baseSlug = $slug; $i = 2;
        while (true) {
            $chk = $db->prepare("SELECT id FROM categories WHERE slug = ?");
            $chk->execute([$slug]);
            if (!$chk->fetch()) break;
            $slug = $baseSlug . '-' . $i;
            $i++;
        }

        $image = processImage('image_file', 'image', '');
        $db->prepare("INSERT INTO categories (name, slug, image) VALUES (?,?,?)")->execute([$name, $slug, $image]);
        if (!empty($formErrors)) { $msg = implode(' | ', $formErrors); $msgType = 'error'; }
        else { $msg = 'تم إضافة التصنيف بنجاح ✓'; }
        $tab = 'categories';
    }

    // تعديل تصنيف
    if ($action === 'edit_category') {
        $cid  = (int)$_POST['category_id'];
        $name = trim($_POST['name']);
        $slug = strtolower(preg_replace('/[^a-z0-9]+/', '-', trim($_POST['slug'])));
        $slug = trim($slug, '-');
        if ($slug === '') { $slug = 'category-' . time(); }

        // نتأكد إن الـ slug مش مكرر مع تصنيف تاني (باستثناء التصنيف اللي بيتعدل نفسه)
        $baseSlug = $slug; $i = 2;
        while (true) {
            $chk = $db->prepare("SELECT id FROM categories WHERE slug = ? AND id != ?");
            $chk->execute([$slug, $cid]);
            if (!$chk->fetch()) break;
            $slug = $baseSlug . '-' . $i;
            $i++;
        }

        $curStmt = $db->prepare("SELECT image FROM categories WHERE id=?");
        $curStmt->execute([$cid]);
        $curImg = $curStmt->fetch() ?: ['image' => ''];

        $image = processImage('image_file', 'image', $curImg['image']);
        $db->prepare("UPDATE categories SET name=?, slug=?, image=? WHERE id=?")->execute([$name, $slug, $image, $cid]);
        if (!empty($formErrors)) { $msg = implode(' | ', $formErrors); $msgType = 'error'; }
        else { $msg = 'تم تحديث التصنيف بنجاح ✓'; }
        $tab = 'categories';
    }

    // حذف تصنيف
    if ($action === 'delete_category') {
        $db->prepare("DELETE FROM categories WHERE id=?")->execute([(int)$_POST['id']]);
        $msg = 'تم حذف التصنيف ✓';
        $tab = 'categories';
    }

    // حفظ البانرات
    if ($action === 'save_banners') {
        $curBanners = [];
        foreach ($db->query("SELECT type, image FROM banners")->fetchAll() as $row) {
            $curBanners[$row['type']] = $row['image'];
        }
        foreach (['main', 'middle'] as $type) {
            $image = processImage($type.'_image_file', $type.'_image', $curBanners[$type] ?? '');
            $db->prepare("UPDATE banners SET image=?, title=?, subtitle=?, button_text=?, link=? WHERE type=?")
               ->execute([$image, trim($_POST[$type.'_title']), trim($_POST[$type.'_subtitle']), trim($_POST[$type.'_btn']), trim($_POST[$type.'_link']), $type]);
        }
        if (!empty($formErrors)) { $msg = implode(' | ', $formErrors); $msgType = 'error'; }
        else { $msg = 'تم حفظ البانرات بنجاح ✓'; }
        $tab = 'banners';
    }

    // حفظ الإعدادات
    if ($action === 'save_settings') {
        $fields = ['site_name','announcement','about_text','phone','email','whatsapp','free_shipping_min','return_title','return_text','share_title','share_text','share_link','share_button_text'];
        $stmt = $db->prepare("INSERT INTO settings (setting_key, setting_value) VALUES (?,?) ON DUPLICATE KEY UPDATE setting_value=?");
        foreach ($fields as $f) {
            $val = trim($_POST[$f] ?? '');
            $stmt->execute([$f, $val, $val]);
        }
        // خيار إظهار/إخفاء قسم سياسة الإرجاع بالكامل
        $returnEnabled = isset($_POST['return_enabled']) ? '1' : '0';
        $stmt->execute(['return_enabled', $returnEnabled, $returnEnabled]);
        $msg = 'تم حفظ الإعدادات بنجاح ✓';
        $tab = 'settings';
    }

    // تغيير كلمة مرور لوحة التحكم
    if ($action === 'change_password') {
        $current = $_POST['current_password'] ?? '';
        $new     = $_POST['new_password'] ?? '';
        $confirm = $_POST['confirm_password'] ?? '';

        $storedHash   = getSetting('admin_password_hash', '');
        $currentValid = ($storedHash !== '') ? password_verify($current, $storedHash) : ($current === ADMIN_PASSWORD);

        if (!$currentValid) {
            $msg = 'كلمة المرور الحالية غير صحيحة';
            $msgType = 'error';
        } elseif (strlen($new) < 6) {
            $msg = 'كلمة المرور الجديدة يجب أن تكون 6 أحرف على الأقل';
            $msgType = 'error';
        } elseif ($new !== $confirm) {
            $msg = 'كلمة المرور الجديدة وتأكيدها غير متطابقين';
            $msgType = 'error';
        } else {
            $newHash = password_hash($new, PASSWORD_DEFAULT);
            $stmt = $db->prepare("INSERT INTO settings (setting_key, setting_value) VALUES (?,?) ON DUPLICATE KEY UPDATE setting_value=?");
            $stmt->execute(['admin_password_hash', $newHash, $newHash]);
            $msg = 'تم تغيير كلمة المرور بنجاح ✓';
        }
        $tab = 'settings';
    }

    // حفظ السوشيال ميديا
    if ($action === 'save_social') {
        $fields = ['facebook','instagram','tiktok','pinterest'];
        $stmt = $db->prepare("INSERT INTO settings (setting_key, setting_value) VALUES (?,?) ON DUPLICATE KEY UPDATE setting_value=?");
        foreach ($fields as $f) {
            $val = trim($_POST[$f] ?? '');
            $stmt->execute([$f, $val, $val]);
        }
        $msg = 'تم حفظ روابط السوشيال ميديا ✓';
        $tab = 'social';
    }
}

// ==========================================
// جلب البيانات
// ==========================================
$products   = $db->query("SELECT p.*, c.name as cat_name FROM products p LEFT JOIN categories c ON p.category_id=c.id ORDER BY p.id DESC")->fetchAll();
$categories = $db->query("SELECT * FROM categories ORDER BY sort_order, id")->fetchAll();
$banners    = [];
foreach ($db->query("SELECT * FROM banners")->fetchAll() as $b) $banners[$b['type']] = $b;

// إحصائيات
$totalProducts = count($products);
$inStock       = count(array_filter($products, fn($p) => $p['in_stock']));
$bestSellers   = count(array_filter($products, fn($p) => $p['is_best_seller']));
$newArrivals   = count(array_filter($products, fn($p) => $p['is_new_arrival']));

// منتج للتعديل
$editProduct = null;
if (isset($_GET['edit'])) {
    $stmt = $db->prepare("SELECT * FROM products WHERE id=?");
    $stmt->execute([(int)$_GET['edit']]);
    $editProduct = $stmt->fetch();
    $tab = 'products';
}

// تصنيف للتعديل
$editCategory = null;
if (isset($_GET['edit_cat'])) {
    $stmt = $db->prepare("SELECT * FROM categories WHERE id=?");
    $stmt->execute([(int)$_GET['edit_cat']]);
    $editCategory = $stmt->fetch();
    $tab = 'categories';
}

$allSizes = ['S','M','L','XL','2XL','3XL','4XL','Free Size','One Size'];

// ===== عرض صفحة اللوجن =====
function showLoginPage($error = null) {
    $siteName = 'الأمل';
    try { require_once '../config.php'; $siteName = getSetting('site_name', 'الأمل'); } catch(Exception $e) {}
    ?>
    <!DOCTYPE html>
    <html lang="ar" dir="rtl">
    <head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
    <title>تسجيل دخول لوحة التحكم</title>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700&family=Playfair+Display:wght@700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/style.css">
    </head>
    <body>
    <div class="login-page">
      <div class="login-box">
        <div class="login-logo"><?= htmlspecialchars($siteName) ?> <span>✦</span></div>
        <p class="login-sub">لوحة تحكم المتجر</p>
        <?php if ($error): ?><div class="alert alert-error"><?= htmlspecialchars($error) ?></div><?php endif; ?>
        <form method="POST">
          <input type="hidden" name="action" value="login">
          <div class="form-group">
            <label>كلمة المرور</label>
            <input type="password" name="password" class="form-control" placeholder="أدخل كلمة المرور" required autofocus>
          </div>
          <button type="submit" class="btn-login">دخول</button>
        </form>
      </div>
    </div>
    </body></html>
    <?php
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>لوحة التحكم - متجر <?= htmlspecialchars(getSetting('site_name','الأمل')) ?></title>
<link href="https://fonts.googleapis.com/css2?family=Cairo:wght@300;400;500;600;700&family=Playfair+Display:wght@600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link rel="stylesheet" href="../assets/style.css">
</head>
<body class="admin-body">

<!-- Sidebar -->
<aside class="admin-sidebar">
  <div class="admin-logo">
    <h2><?= htmlspecialchars(getSetting('site_name','الأمل')) ?> ✦</h2>
    <p>لوحة التحكم</p>
  </div>
  <nav class="admin-nav">
    <a href="?tab=dashboard" class="<?= $tab==='dashboard'?'active':'' ?>"><span>📊</span> الرئيسية</a>
    <a href="?tab=products"  class="<?= $tab==='products' ?'active':'' ?>"><span>👗</span> المنتجات</a>
    <a href="?tab=categories"class="<?= $tab==='categories'?'active':'' ?>"><span>📂</span> التصنيفات</a>
    <a href="?tab=banners"   class="<?= $tab==='banners'  ?'active':'' ?>"><span>🖼️</span> البانرات</a>
    <a href="?tab=settings"  class="<?= $tab==='settings' ?'active':'' ?>"><span>⚙️</span> الإعدادات</a>
    <a href="?tab=social"    class="<?= $tab==='social'   ?'active':'' ?>"><span>📱</span> السوشيال</a>
    <a href="../index.php" target="_blank" style="margin-top:auto"><span>🌐</span> عرض الموقع</a>
    <a href="?logout=1" style="color:#e74c3c !important"><span>🚪</span> تسجيل خروج</a>
  </nav>
</aside>

<!-- المحتوى الرئيسي -->
<main class="admin-content">
  <div class="admin-header">
    <h1><?php
      $titles = ['dashboard'=>'لوحة التحكم','products'=>'المنتجات','categories'=>'التصنيفات','banners'=>'البانرات','settings'=>'الإعدادات العامة','social'=>'السوشيال ميديا'];
      echo $titles[$tab] ?? 'لوحة التحكم';
    ?></h1>
    <a href="../index.php" target="_blank" style="font-size:13px;color:#C9A96E;font-weight:600">
      <i class="fas fa-external-link-alt"></i> عرض الموقع
    </a>
  </div>

  <?php if ($msg): ?>
  <div class="alert alert-<?= $msgType ?>"><?= htmlspecialchars($msg) ?></div>
  <?php endif; ?>

  <!-- ===== لوحة المعلومات ===== -->
  <?php if ($tab === 'dashboard'): ?>
  <div class="stat-cards">
    <div class="stat-card"><h3><?= $totalProducts ?></h3><p>إجمالي المنتجات</p></div>
    <div class="stat-card" style="border-color:#27ae60"><h3><?= $inStock ?></h3><p>متاح حالياً</p></div>
    <div class="stat-card" style="border-color:#e74c3c"><h3><?= $totalProducts - $inStock ?></h3><p>نفذت الكمية</p></div>
    <div class="stat-card" style="border-color:#3498db"><h3><?= count($categories) ?></h3><p>التصنيفات</p></div>
  </div>
  <div class="admin-card">
    <h3>آخر المنتجات المضافة</h3>
    <table class="admin-table">
      <thead><tr><th>الصورة</th><th>الاسم</th><th>السعر</th><th>التصنيف</th><th>الحالة</th></tr></thead>
      <tbody>
        <?php foreach (array_slice($products, 0, 8) as $p): ?>
        <tr>
          <td><img src="<?= htmlspecialchars($p['image1']??'') ?>" alt=""></td>
          <td><strong><?= htmlspecialchars($p['name']) ?></strong></td>
          <td>LE <?= number_format($p['price'],0) ?></td>
          <td><?= htmlspecialchars($p['cat_name']??'-') ?></td>
          <td><?= $p['in_stock'] ? '<span style="color:#27ae60;font-weight:700">✓ متاح</span>' : '<span style="color:#e74c3c;font-weight:700">✗ نفذ</span>' ?></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php endif; ?>

  <!-- ===== المنتجات ===== -->
  <?php if ($tab === 'products'): ?>

  <!-- نموذج إضافة/تعديل -->
  <div class="admin-card">
    <h3><?= $editProduct ? '✏️ تعديل المنتج' : '➕ إضافة منتج جديد' ?></h3>
    <form method="POST" enctype="multipart/form-data">
      <input type="hidden" name="action" value="<?= $editProduct ? 'edit_product' : 'add_product' ?>">
      <?php if ($editProduct): ?>
      <input type="hidden" name="product_id" value="<?= $editProduct['id'] ?>">
      <?php endif; ?>

      <div class="form-row">
        <div class="form-group">
          <label>اسم المنتج *</label>
          <input type="text" name="name" class="form-control" required value="<?= htmlspecialchars($editProduct['name']??'') ?>">
        </div>
        <div class="form-group">
          <label>التصنيف</label>
          <select name="category_id" class="form-control">
            <option value="">-- اختر تصنيف --</option>
            <?php foreach ($categories as $c): ?>
            <option value="<?= $c['id'] ?>" <?= ($editProduct['category_id']??'')==$c['id']?'selected':'' ?>><?= htmlspecialchars($c['name']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>

      <div class="form-row">
        <div class="form-group">
          <label>السعر (LE) *</label>
          <input type="number" name="price" class="form-control" required step="0.01" value="<?= $editProduct['price']??'' ?>">
        </div>
        <div class="form-group">
          <label>السعر قبل الخصم (اختياري)</label>
          <input type="number" name="old_price" class="form-control" step="0.01" value="<?= $editProduct['old_price']??'' ?>">
        </div>
      </div>

      <div class="form-row">
        <div class="form-group">
          <label>الصورة الرئيسية</label>
          <div class="img-input-box">
            <input type="url" name="image1" class="form-control" value="<?= htmlspecialchars($editProduct['image1']??'') ?>" placeholder="الصق رابط الصورة هنا: https://...">
            <div class="img-input-or"><span></span>أو<span></span></div>
            <input type="file" name="image1_file" accept="image/jpeg,image/png,image/webp,image/gif" class="form-control img-upload-input" data-preview="preview-image1">
            <img id="preview-image1" class="img-preview" src="<?= htmlspecialchars($editProduct['image1']??'') ?>" style="<?= empty($editProduct['image1']) ? 'display:none' : '' ?>">
          </div>
        </div>
        <div class="form-group">
          <label>الصورة الثانية (hover)</label>
          <div class="img-input-box">
            <input type="url" name="image2" class="form-control" value="<?= htmlspecialchars($editProduct['image2']??'') ?>" placeholder="الصق رابط الصورة هنا: https://...">
            <div class="img-input-or"><span></span>أو<span></span></div>
            <input type="file" name="image2_file" accept="image/jpeg,image/png,image/webp,image/gif" class="form-control img-upload-input" data-preview="preview-image2">
            <img id="preview-image2" class="img-preview" src="<?= htmlspecialchars($editProduct['image2']??'') ?>" style="<?= empty($editProduct['image2']) ? 'display:none' : '' ?>">
          </div>
        </div>
      </div>

      <div class="form-group">
        <label>الوصف</label>
        <textarea name="description" class="form-control"><?= htmlspecialchars($editProduct['description']??'') ?></textarea>
      </div>

      <div class="form-group">
        <label>المقاسات المتاحة</label>
        <div class="sizes-checkboxes">
          <?php
          $selSizes = array_map('trim', explode(',', $editProduct['sizes'] ?? ''));
          foreach ($allSizes as $s):
          ?>
          <label class="size-check">
            <input type="checkbox" name="sizes[]" value="<?= $s ?>" <?= in_array($s,$selSizes)?'checked':'' ?>>
            <span><?= $s ?></span>
          </label>
          <?php endforeach; ?>
        </div>
      </div>

      <div class="form-row">
        <div class="form-group">
          <label style="display:flex;align-items:center;gap:12px;cursor:pointer">
            <input type="checkbox" name="is_best_seller" <?= ($editProduct['is_best_seller']??0)?'checked':'' ?> style="width:18px;height:18px;accent-color:#C9A96E">
            الأكثر مبيعاً (Best Seller)
          </label>
        </div>
        <div class="form-group">
          <label style="display:flex;align-items:center;gap:12px;cursor:pointer">
            <input type="checkbox" name="is_new_arrival" <?= ($editProduct['is_new_arrival']??0)?'checked':'' ?> style="width:18px;height:18px;accent-color:#C9A96E">
            وصل حديثاً (New Arrival)
          </label>
        </div>
        <div class="form-group">
          <label style="display:flex;align-items:center;gap:12px;cursor:pointer">
            <input type="checkbox" name="in_stock" <?= ($editProduct['in_stock']??1)?'checked':'' ?> style="width:18px;height:18px;accent-color:#27ae60">
            متاح في المخزن
          </label>
        </div>
      </div>

      <button type="submit" class="btn-save">
        <?= $editProduct ? '💾 حفظ التعديلات' : '➕ إضافة المنتج' ?>
      </button>
      <?php if ($editProduct): ?>
      <a href="?tab=products" style="margin-right:12px;color:#888;font-size:14px">إلغاء</a>
      <?php endif; ?>
    </form>
  </div>

  <!-- جدول المنتجات -->
  <div class="admin-card">
    <h3>📋 قائمة المنتجات (<?= count($products) ?>)</h3>
    <div style="overflow-x:auto">
    <table class="admin-table">
      <thead>
        <tr>
          <th>#</th><th>الصورة</th><th>الاسم</th><th>السعر</th><th>التصنيف</th><th>الحالة</th><th>مميز</th><th>الإجراءات</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($products as $p): ?>
        <tr>
          <td><?= $p['id'] ?></td>
          <td><?php if ($p['image1']): ?><img src="<?= htmlspecialchars($p['image1']) ?>" alt=""><?php endif; ?></td>
          <td><strong><?= htmlspecialchars($p['name']) ?></strong></td>
          <td>LE <?= number_format($p['price'],0) ?></td>
          <td><?= htmlspecialchars($p['cat_name']??'-') ?></td>
          <td><?= $p['in_stock'] ? '<span style="color:#27ae60;font-weight:700;font-size:12px">✓ متاح</span>' : '<span style="color:#e74c3c;font-weight:700;font-size:12px">✗ نفذ</span>' ?></td>
          <td>
            <?= $p['is_best_seller'] ? '<span style="background:#C9A96E;color:#fff;padding:2px 8px;border-radius:3px;font-size:11px">Best</span> ' : '' ?>
            <?= $p['is_new_arrival'] ? '<span style="background:#3498db;color:#fff;padding:2px 8px;border-radius:3px;font-size:11px">New</span>' : '' ?>
          </td>
          <td style="display:flex;gap:6px;flex-wrap:wrap">
            <a href="?tab=products&edit=<?= $p['id'] ?>" class="btn-edit">تعديل</a>
            <form method="POST" style="display:inline" onsubmit="return confirm('هل تريد حذف هذا المنتج؟')">
              <input type="hidden" name="action" value="delete_product">
              <input type="hidden" name="id" value="<?= $p['id'] ?>">
              <button type="submit" class="btn-danger">حذف</button>
            </form>
            <a href="../product.php?id=<?= $p['id'] ?>" target="_blank" style="padding:7px 12px;background:#ecf0f1;color:#333;border-radius:4px;font-size:12px">عرض</a>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
    </div>
  </div>
  <?php endif; ?>

  <!-- ===== التصنيفات ===== -->
  <?php if ($tab === 'categories'): ?>
  <div class="admin-card">
    <h3><?= $editCategory ? '✏️ تعديل التصنيف' : '➕ إضافة تصنيف جديد' ?></h3>
    <form method="POST" enctype="multipart/form-data">
      <input type="hidden" name="action" value="<?= $editCategory ? 'edit_category' : 'add_category' ?>">
      <?php if ($editCategory): ?>
      <input type="hidden" name="category_id" value="<?= $editCategory['id'] ?>">
      <?php endif; ?>
      <div class="form-row">
        <div class="form-group">
          <label>اسم التصنيف *</label>
          <input type="text" name="name" class="form-control" required placeholder="مثال: عبايات" value="<?= htmlspecialchars($editCategory['name'] ?? '') ?>">
        </div>
        <div class="form-group">
          <label>الـ Slug (بالإنجليزية) - اختياري</label>
          <input type="text" name="slug" class="form-control" placeholder="مثال: abayas (هيتولّد تلقائيًا لو سبتيه فاضي)" value="<?= htmlspecialchars($editCategory['slug'] ?? '') ?>">
        </div>
      </div>
      <div class="form-group">
        <label>صورة التصنيف</label>
        <div class="img-input-box">
          <input type="url" name="image" class="form-control" placeholder="الصق رابط الصورة هنا: https://..." value="<?= htmlspecialchars($editCategory['image'] ?? '') ?>">
          <div class="img-input-or"><span></span>أو<span></span></div>
          <input type="file" name="image_file" accept="image/jpeg,image/png,image/webp,image/gif" class="form-control img-upload-input" data-preview="preview-catimage">
          <img id="preview-catimage" class="img-preview" src="<?= htmlspecialchars($editCategory['image'] ?? '') ?>" style="<?= empty($editCategory['image']) ? 'display:none' : '' ?>">
        </div>
      </div>
      <button type="submit" class="btn-save"><?= $editCategory ? '💾 حفظ التعديلات' : '➕ إضافة التصنيف' ?></button>
      <?php if ($editCategory): ?>
      <a href="?tab=categories" style="margin-right:10px;color:#999;font-size:13px">إلغاء التعديل</a>
      <?php endif; ?>
    </form>
  </div>
  <div class="admin-card">
    <h3>📂 التصنيفات الحالية</h3>
    <table class="admin-table">
      <thead><tr><th>#</th><th>الصورة</th><th>الاسم</th><th>Slug</th><th>الإجراءات</th></tr></thead>
      <tbody>
        <?php foreach ($categories as $c): ?>
        <tr>
          <td><?= $c['id'] ?></td>
          <td><?php if ($c['image']): ?><img src="<?= htmlspecialchars($c['image']) ?>" alt="" style="width:50px;height:50px;object-fit:cover;border-radius:4px"><?php endif; ?></td>
          <td><strong><?= htmlspecialchars($c['name']) ?></strong></td>
          <td><code style="background:#f5f5f5;padding:2px 8px;border-radius:3px;font-size:12px"><?= htmlspecialchars($c['slug']) ?></code></td>
          <td style="display:flex;gap:6px;flex-wrap:wrap">
            <a href="?tab=categories&edit_cat=<?= $c['id'] ?>" class="btn-edit">تعديل</a>
            <form method="POST" style="display:inline" onsubmit="return confirm('حذف هذا التصنيف؟')">
              <input type="hidden" name="action" value="delete_category">
              <input type="hidden" name="id" value="<?= $c['id'] ?>">
              <button type="submit" class="btn-danger">حذف</button>
            </form>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php endif; ?>

  <!-- ===== البانرات ===== -->
  <?php if ($tab === 'banners'): ?>
  <form method="POST" enctype="multipart/form-data">
    <input type="hidden" name="action" value="save_banners">
    <div class="admin-card">
      <h3>🖼️ البانر الرئيسي</h3>
      <div class="form-group">
        <label>صورة البانر</label>
        <div class="img-input-box">
          <input type="url" name="main_image" class="form-control" value="<?= htmlspecialchars($banners['main']['image']??'') ?>" placeholder="الصق رابط الصورة هنا: https://...">
          <div class="img-input-or"><span></span>أو<span></span></div>
          <input type="file" name="main_image_file" accept="image/jpeg,image/png,image/webp,image/gif" class="form-control img-upload-input" data-preview="preview-main-banner">
          <img id="preview-main-banner" class="img-preview img-preview-wide" src="<?= htmlspecialchars($banners['main']['image']??'') ?>" style="<?= empty($banners['main']['image']) ? 'display:none' : '' ?>">
        </div>
      </div>
      <div class="form-row">
        <div class="form-group">
          <label>العنوان</label>
          <input type="text" name="main_title" class="form-control" value="<?= htmlspecialchars($banners['main']['title']??'') ?>">
        </div>
        <div class="form-group">
          <label>النص الفرعي</label>
          <input type="text" name="main_subtitle" class="form-control" value="<?= htmlspecialchars($banners['main']['subtitle']??'') ?>">
        </div>
      </div>
      <div class="form-row">
        <div class="form-group">
          <label>نص الزر</label>
          <input type="text" name="main_btn" class="form-control" value="<?= htmlspecialchars($banners['main']['button_text']??'') ?>">
        </div>
        <div class="form-group">
          <label>رابط الزر</label>
          <input type="text" name="main_link" class="form-control" value="<?= htmlspecialchars($banners['main']['link']??'') ?>">
        </div>
      </div>
    </div>
    <div class="admin-card">
      <h3>🖼️ البانر الوسطي</h3>
      <div class="form-group">
        <label>صورة البانر</label>
        <div class="img-input-box">
          <input type="url" name="middle_image" class="form-control" value="<?= htmlspecialchars($banners['middle']['image']??'') ?>" placeholder="الصق رابط الصورة هنا: https://...">
          <div class="img-input-or"><span></span>أو<span></span></div>
          <input type="file" name="middle_image_file" accept="image/jpeg,image/png,image/webp,image/gif" class="form-control img-upload-input" data-preview="preview-middle-banner">
          <img id="preview-middle-banner" class="img-preview img-preview-wide" src="<?= htmlspecialchars($banners['middle']['image']??'') ?>" style="<?= empty($banners['middle']['image']) ? 'display:none' : '' ?>">
        </div>
      </div>
      <div class="form-row">
        <div class="form-group">
          <label>العنوان</label>
          <input type="text" name="middle_title" class="form-control" value="<?= htmlspecialchars($banners['middle']['title']??'') ?>">
        </div>
        <div class="form-group">
          <label>النص الفرعي</label>
          <input type="text" name="middle_subtitle" class="form-control" value="<?= htmlspecialchars($banners['middle']['subtitle']??'') ?>">
        </div>
      </div>
      <div class="form-row">
        <div class="form-group">
          <label>نص الزر</label>
          <input type="text" name="middle_btn" class="form-control" value="<?= htmlspecialchars($banners['middle']['button_text']??'') ?>">
        </div>
        <div class="form-group">
          <label>رابط الزر</label>
          <input type="text" name="middle_link" class="form-control" value="<?= htmlspecialchars($banners['middle']['link']??'') ?>">
        </div>
      </div>
    </div>
    <button type="submit" class="btn-save">💾 حفظ البانرات</button>
  </form>
  <?php endif; ?>

  <!-- ===== الإعدادات ===== -->
  <?php if ($tab === 'settings'): ?>
  <div class="admin-card">
    <h3>⚙️ الإعدادات العامة</h3>
    <form method="POST">
      <input type="hidden" name="action" value="save_settings">
      <div class="form-row">
        <div class="form-group">
          <label>اسم المتجر</label>
          <input type="text" name="site_name" class="form-control" value="<?= htmlspecialchars(getSetting('site_name','')) ?>">
        </div>
        <div class="form-group">
          <label>حد الشحن المجاني (LE)</label>
          <input type="number" name="free_shipping_min" class="form-control" value="<?= htmlspecialchars(getSetting('free_shipping_min','3000')) ?>">
        </div>
      </div>
      <div class="form-group">
        <label>نص الشريط الإعلاني</label>
        <input type="text" name="announcement" class="form-control" value="<?= htmlspecialchars(getSetting('announcement','')) ?>">
      </div>
      <div class="form-group">
        <label>نص "من نحن"</label>
        <textarea name="about_text" class="form-control"><?= htmlspecialchars(getSetting('about_text','')) ?></textarea>
      </div>
      <div class="form-row">
        <div class="form-group">
          <label>رقم الهاتف</label>
          <input type="text" name="phone" class="form-control" value="<?= htmlspecialchars(getSetting('phone','')) ?>">
        </div>
        <div class="form-group">
          <label>البريد الإلكتروني</label>
          <input type="email" name="email" class="form-control" value="<?= htmlspecialchars(getSetting('email','')) ?>">
        </div>
      </div>
      <div class="form-group">
        <label>رقم واتساب (بدون + مثال: 201012345678)</label>
        <input type="text" name="whatsapp" class="form-control" value="<?= htmlspecialchars(getSetting('whatsapp','')) ?>">
      </div>

      <hr style="border:none;border-top:1px solid #eee;margin:24px 0">
      <h4 style="margin-bottom:16px;color:#1a1a1a">🔄 سياسة الإرجاع</h4>
      <div class="form-group">
        <label style="display:flex;align-items:center;gap:12px;cursor:pointer">
          <input type="checkbox" name="return_enabled" <?= getSetting('return_enabled','1')==='1'?'checked':'' ?> style="width:18px;height:18px;accent-color:#27ae60">
          إظهار قسم سياسة الإرجاع في الموقع
        </label>
      </div>
      <div class="form-row">
        <div class="form-group">
          <label>عنوان سياسة الإرجاع</label>
          <input type="text" name="return_title" class="form-control" value="<?= htmlspecialchars(getSetting('return_title','إرجاع مجاني')) ?>" placeholder="مثال: إرجاع مجاني">
        </div>
        <div class="form-group">
          <label>نص سياسة الإرجاع</label>
          <input type="text" name="return_text" class="form-control" value="<?= htmlspecialchars(getSetting('return_text','أرجعي المنتج خلال 14 يوم')) ?>" placeholder="مثال: أرجعي المنتج خلال 14 يوم">
        </div>
      </div>
      <hr style="border:none;border-top:1px solid #eee;margin:24px 0">
      <h4 style="margin-bottom:16px;color:#1a1a1a">📢 مشاركة الموقع (فوتر الموقع)</h4>
      <div class="form-row">
        <div class="form-group">
          <label>عنوان قسم المشاركة</label>
          <input type="text" name="share_title" class="form-control" value="<?= htmlspecialchars(getSetting('share_title','شاركي مع صديقاتك')) ?>" placeholder="مثال: شاركي مع صديقاتك">
        </div>
        <div class="form-group">
          <label>نص زرار المشاركة</label>
          <input type="text" name="share_button_text" class="form-control" value="<?= htmlspecialchars(getSetting('share_button_text','شاركي الآن')) ?>" placeholder="مثال: شاركي الآن">
        </div>
      </div>
      <div class="form-group">
        <label>نص وصف المشاركة</label>
        <input type="text" name="share_text" class="form-control" value="<?= htmlspecialchars(getSetting('share_text','شاركي الموقع مع صديقاتك واحصلي على مفاجآت وخصومات حصرية!')) ?>" placeholder="النص اللي هيظهر ويتشارك مع الرابط">
      </div>
      <div class="form-group">
        <label>الرابط اللي هيتشارك عند الضغط على الزرار</label>
        <input type="url" name="share_link" class="form-control" value="<?= htmlspecialchars(getSetting('share_link', SITE_URL)) ?>" placeholder="افتراضيًا رابط موقعك">
      </div>

      <button type="submit" class="btn-save">💾 حفظ الإعدادات</button>
    </form>
  </div>

  <!-- تغيير كلمة مرور لوحة التحكم -->
  <div class="admin-card" style="margin-top:24px">
    <h3>🔒 تغيير كلمة المرور</h3>
    <form method="POST">
      <input type="hidden" name="action" value="change_password">
      <div class="form-group">
        <label>كلمة المرور الحالية</label>
        <input type="password" name="current_password" class="form-control" required autocomplete="current-password">
      </div>
      <div class="form-row">
        <div class="form-group">
          <label>كلمة المرور الجديدة (6 أحرف على الأقل)</label>
          <input type="password" name="new_password" class="form-control" required minlength="6" autocomplete="new-password">
        </div>
        <div class="form-group">
          <label>تأكيد كلمة المرور الجديدة</label>
          <input type="password" name="confirm_password" class="form-control" required minlength="6" autocomplete="new-password">
        </div>
      </div>
      <button type="submit" class="btn-save">🔒 تغيير كلمة المرور</button>
    </form>
  </div>
  <?php endif; ?>

  <!-- ===== السوشيال ميديا ===== -->
  <?php if ($tab === 'social'): ?>
  <div class="admin-card">
    <h3>📱 روابط السوشيال ميديا</h3>
    <form method="POST">
      <input type="hidden" name="action" value="save_social">
      <div class="form-group">
        <label><i class="fab fa-facebook" style="color:#1877f2"></i> Facebook</label>
        <input type="url" name="facebook" class="form-control" value="<?= htmlspecialchars(getSetting('facebook','')) ?>" placeholder="https://facebook.com/...">
      </div>
      <div class="form-group">
        <label><i class="fab fa-instagram" style="color:#e4405f"></i> Instagram</label>
        <input type="url" name="instagram" class="form-control" value="<?= htmlspecialchars(getSetting('instagram','')) ?>" placeholder="https://instagram.com/...">
      </div>
      <div class="form-group">
        <label><i class="fab fa-tiktok"></i> TikTok</label>
        <input type="url" name="tiktok" class="form-control" value="<?= htmlspecialchars(getSetting('tiktok','')) ?>" placeholder="https://tiktok.com/...">
      </div>
      <div class="form-group">
        <label><i class="fab fa-pinterest" style="color:#e60023"></i> Pinterest</label>
        <input type="url" name="pinterest" class="form-control" value="<?= htmlspecialchars(getSetting('pinterest','')) ?>" placeholder="https://pinterest.com/...">
      </div>
      <button type="submit" class="btn-save">💾 حفظ الروابط</button>
    </form>
  </div>
  <?php endif; ?>

</main>

<style>
.img-input-box {
  padding: 12px;
  background: #faf9f7;
  border: 1px dashed #ddd;
  border-radius: 6px;
  display: flex;
  flex-direction: column;
  gap: 8px;
}
.img-input-box .form-control { margin: 0; }
.img-input-or {
  display: flex;
  align-items: center;
  gap: 10px;
  color: #999;
  font-size: 12px;
}
.img-input-or span { flex: 1; border-top: 1px solid #e5e5e5; }
.img-preview {
  width: 90px;
  height: 90px;
  object-fit: cover;
  border-radius: 4px;
  border: 1px solid #eee;
}
.img-preview-wide {
  width: 100%;
  height: 140px;
  object-fit: cover;
}
</style>

<script>
// معاينة فورية للصورة عند اختيارها من الجهاز
document.querySelectorAll('.img-upload-input').forEach(function(input) {
  input.addEventListener('change', function() {
    const previewId = this.getAttribute('data-preview');
    const preview = document.getElementById(previewId);
    if (!preview) return;
    if (this.files && this.files[0]) {
      const reader = new FileReader();
      reader.onload = function(e) {
        preview.src = e.target.result;
        preview.style.display = 'block';
      };
      reader.readAsDataURL(this.files[0]);
      // لو اختار ملف، امسح خانة الرابط عشان مايحصلش لبس أي واحد هيتاخد
      const box = this.closest('.img-input-box');
      if (box) {
        const urlInput = box.querySelector('input[type="url"]');
        if (urlInput) urlInput.value = '';
      }
    }
  });
});
// لو المستخدم كتب رابط يدوي، نمسح اختيار الملف عشان الرابط هو اللي يتاخد
document.querySelectorAll('.img-input-box input[type="url"]').forEach(function(urlInput) {
  urlInput.addEventListener('input', function() {
    const box = this.closest('.img-input-box');
    if (box) {
      const fileInput = box.querySelector('input[type="file"]');
      if (fileInput) fileInput.value = '';
    }
    if (this.value.trim() !== '') {
      const preview = box.querySelector('.img-preview');
      if (preview) { preview.src = this.value.trim(); preview.style.display = 'block'; }
    }
  });
});
</script>

</body>
</html>
