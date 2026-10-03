<?php
// ==========================================
// إعدادات قاعدة البيانات - متجر الأمل
// ==========================================
define('DB_HOST', 'localhost');
define('DB_NAME', 'rafavuaf_alamal_store');   // اسم قاعدة البيانات على Namecheap
define('DB_USER', 'rafavuaf_alamal_admin');   // مستخدم قاعدة البيانات على Namecheap
define('DB_PASS', 'lPN]Z_hxyENn~#4$');        // باسورد قاعدة البيانات
define('DB_CHARSET', 'utf8mb4');

// إعدادات الموقع
// يكتشف البروتوكول (http أو https) واسم المجلد تلقائيًا عشان روابط CSS والصور تشتغل صح
$isHttps = (
    (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ||
    (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https') ||
    (!empty($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443)
);
$protocol = $isHttps ? 'https://' : 'http://';
$basePath = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'])), '/');
define('SITE_URL', $protocol . $_SERVER['HTTP_HOST'] . $basePath);
define('ADMIN_PASSWORD', 'Alamal@2026#Secure');

// الاتصال بقاعدة البيانات
function getDB() {
    static $pdo = null;
    if ($pdo === null) {
        try {
            $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
            $pdo = new PDO($dsn, DB_USER, DB_PASS, [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ]);
        } catch (PDOException $e) {
            die('<div style="font-family:Arial;padding:20px;color:red;">خطأ في الاتصال بقاعدة البيانات: ' . $e->getMessage() . '</div>');
        }
    }
    return $pdo;
}

// دالة لجلب إعداد معين
function getSetting($key, $default = '') {
    try {
        $db = getDB();
        $stmt = $db->prepare("SELECT setting_value FROM settings WHERE setting_key = ?");
        $stmt->execute([$key]);
        $row = $stmt->fetch();
        return $row ? $row['setting_value'] : $default;
    } catch (Exception $e) {
        return $default;
    }
}
?>
