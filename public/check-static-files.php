<?php
/**
 * Canlı sunucuda statik dosyaların erişilebilirliğini kontrol eder
 * Tarayıcıdan: https://yourdomain.com/check-static-files.php
 */

header('Content-Type: text/html; charset=utf-8');

$basePath = __DIR__;
$checks = [];

// Kontrol edilecek dosyalar
$filesToCheck = [
    'yonetim/css/admin-layout.css',
    'yonetim/js/admin-layout.js',
    'yonetim/vendors/css/vendor.bundle.base.css',
    'yonetim/vendors/js/vendor.bundle.base.js',
    'tema/css/style.css',
    'tema/js/scripts.js',
];

echo "<h1>Statik Dosya Kontrolü</h1>";
echo "<style>
    body { font-family: Arial, sans-serif; margin: 20px; }
    .success { color: green; }
    .error { color: red; }
    .warning { color: orange; }
    table { border-collapse: collapse; width: 100%; margin-top: 20px; }
    th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
    th { background-color: #f2f2f2; }
</style>";

echo "<table>";
echo "<tr><th>Dosya</th><th>Durum</th><th>Yol</th><th>MIME Type</th><th>Boyut</th></tr>";

foreach ($filesToCheck as $file) {
    $fullPath = $basePath . '/' . $file;
    $exists = file_exists($fullPath);
    $size = $exists ? filesize($fullPath) : 0;
    
    // MIME type kontrolü
    $mimeType = 'N/A';
    if ($exists) {
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $fullPath);
        finfo_close($finfo);
    }
    
    // URL kontrolü
    $url = '/' . $file;
    $status = $exists ? 'success' : 'error';
    $statusText = $exists ? '✓ Var' : '✗ Yok';
    
    echo "<tr>";
    echo "<td><strong>{$file}</strong></td>";
    echo "<td class='{$status}'>{$statusText}</td>";
    echo "<td>{$fullPath}</td>";
    echo "<td>{$mimeType}</td>";
    echo "<td>" . ($size > 0 ? number_format($size) . ' bytes' : '-') . "</td>";
    echo "</tr>";
    
    $checks[] = [
        'file' => $file,
        'exists' => $exists,
        'mime' => $mimeType,
        'size' => $size
    ];
}

echo "</table>";

// Sunucu bilgileri
echo "<h2>Sunucu Bilgileri</h2>";
echo "<ul>";
echo "<li><strong>PHP Version:</strong> " . PHP_VERSION . "</li>";
echo "<li><strong>Server Software:</strong> " . ($_SERVER['SERVER_SOFTWARE'] ?? 'Bilinmiyor') . "</li>";
echo "<li><strong>Document Root:</strong> " . ($_SERVER['DOCUMENT_ROOT'] ?? 'Bilinmiyor') . "</li>";
echo "<li><strong>Script Path:</strong> " . __DIR__ . "</li>";
echo "<li><strong>Request URI:</strong> " . ($_SERVER['REQUEST_URI'] ?? 'Bilinmiyor') . "</li>";
echo "</ul>";

// .htaccess kontrolü
$htaccessPath = $basePath . '/.htaccess';
echo "<h2>.htaccess Kontrolü</h2>";
if (file_exists($htaccessPath)) {
    echo "<p class='success'>✓ .htaccess dosyası mevcut</p>";
    echo "<p><strong>Boyut:</strong> " . filesize($htaccessPath) . " bytes</p>";
    
    // mod_rewrite kontrolü
    if (function_exists('apache_get_modules')) {
        $modules = apache_get_modules();
        $hasRewrite = in_array('mod_rewrite', $modules);
        $hasMime = in_array('mod_mime', $modules);
        $hasHeaders = in_array('mod_headers', $modules);
        
        echo "<h3>Apache Modülleri</h3>";
        echo "<ul>";
        echo "<li class='" . ($hasRewrite ? 'success' : 'error') . "'>mod_rewrite: " . ($hasRewrite ? '✓ Aktif' : '✗ Pasif') . "</li>";
        echo "<li class='" . ($hasMime ? 'success' : 'error') . "'>mod_mime: " . ($hasMime ? '✓ Aktif' : '✗ Pasif') . "</li>";
        echo "<li class='" . ($hasHeaders ? 'success' : 'error') . "'>mod_headers: " . ($hasHeaders ? '✓ Aktif' : '✗ Pasif') . "</li>";
        echo "</ul>";
    } else {
        echo "<p class='warning'>⚠ Apache modül kontrolü yapılamıyor (Nginx veya başka bir sunucu kullanılıyor olabilir)</p>";
    }
} else {
    echo "<p class='error'>✗ .htaccess dosyası bulunamadı!</p>";
}

// Öneriler
echo "<h2>Öneriler</h2>";
echo "<ul>";

$missingFiles = array_filter($checks, function($check) {
    return !$check['exists'];
});

if (count($missingFiles) > 0) {
    echo "<li class='error'><strong>Eksik Dosyalar:</strong> " . count($missingFiles) . " dosya bulunamadı. Lütfen dosyaları yükleyin.</li>";
}

$wrongMime = array_filter($checks, function($check) {
    if (!$check['exists']) return false;
    $ext = pathinfo($check['file'], PATHINFO_EXTENSION);
    $expectedMime = [
        'css' => 'text/css',
        'js' => ['application/javascript', 'text/javascript'],
    ];
    
    if (isset($expectedMime[$ext])) {
        if (is_array($expectedMime[$ext])) {
            return !in_array($check['mime'], $expectedMime[$ext]);
        }
        return $check['mime'] !== $expectedMime[$ext];
    }
    return false;
});

if (count($wrongMime) > 0) {
    echo "<li class='warning'><strong>Yanlış MIME Type:</strong> " . count($wrongMime) . " dosyanın MIME type'ı beklenen değerden farklı.</li>";
}

echo "<li>Canlı sunucuda <code>APP_URL</code> environment variable'ının doğru ayarlandığından emin olun.</li>";
echo "<li>Eğer alt klasörde çalışıyorsanız, <code>ASSET_URL</code> environment variable'ını ayarlayın.</li>";
echo "<li>Tarayıcı cache'ini temizleyin (Ctrl+Shift+Delete veya Hard Refresh: Ctrl+F5).</li>";
echo "<li>Web sunucusu loglarını kontrol edin (404 hataları var mı?).</li>";

echo "</ul>";

echo "<hr>";
echo "<p><small>Bu dosyayı kontrol sonrası silmeyi unutmayın!</small></p>";
?>
