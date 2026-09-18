<?php
/**
 * Rewrite kurallarının çalışıp çalışmadığını test eder
 */
header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html>
<head>
    <title>Rewrite Debug</title>
    <style>
        body { font-family: Arial; margin: 20px; }
        .success { color: green; }
        .error { color: red; }
        .info { background: #f0f0f0; padding: 10px; margin: 10px 0; }
    </style>
</head>
<body>
    <h1>Rewrite Debug Test</h1>
    
    <div class="info">
        <strong>PHP Çalışıyor:</strong> <?php echo PHP_VERSION; ?><br>
        <strong>Script Path:</strong> <?php echo __FILE__; ?><br>
        <strong>Document Root:</strong> <?php echo $_SERVER['DOCUMENT_ROOT'] ?? 'N/A'; ?><br>
        <strong>Request URI:</strong> <?php echo $_SERVER['REQUEST_URI'] ?? 'N/A'; ?><br>
        <strong>Script Name:</strong> <?php echo $_SERVER['SCRIPT_NAME'] ?? 'N/A'; ?><br>
    </div>
    
    <h2>Test Dosyaları</h2>
    <ul>
        <li><a href="/yonetim/css/admin-layout.css" target="_blank">CSS Test: /yonetim/css/admin-layout.css</a></li>
        <li><a href="/yonetim/js/admin-layout.js" target="_blank">JS Test: /yonetim/js/admin-layout.js</a></li>
        <li><a href="/test.php" target="_blank">PHP Test: /test.php</a></li>
    </ul>
    
    <h2>Dosya Kontrolleri</h2>
    <?php
    $basePath = __DIR__;
    $files = [
        'yonetim/css/admin-layout.css',
        'yonetim/js/admin-layout.js',
        'yonetim/vendors/css/vendor.bundle.base.css',
        'yonetim/vendors/js/vendor.bundle.base.js',
    ];
    
    foreach ($files as $file) {
        $fullPath = $basePath . '/' . $file;
        $exists = file_exists($fullPath);
        $status = $exists ? 'success' : 'error';
        $icon = $exists ? '✓' : '✗';
        echo "<div class='{$status}'>{$icon} {$file} - " . ($exists ? 'VAR' : 'YOK') . "</div>";
    }
    ?>
    
    <h2>Apache Modülleri</h2>
    <?php
    if (function_exists('apache_get_modules')) {
        $modules = apache_get_modules();
        $required = ['mod_rewrite', 'mod_mime', 'mod_headers'];
        foreach ($required as $mod) {
            $active = in_array($mod, $modules);
            $status = $active ? 'success' : 'error';
            $icon = $active ? '✓' : '✗';
            echo "<div class='{$status}'>{$icon} {$mod}: " . ($active ? 'Aktif' : 'Pasif') . "</div>";
        }
    } else {
        echo "<div class='info'>Apache modül kontrolü yapılamıyor (Nginx veya başka bir sunucu kullanılıyor olabilir)</div>";
    }
    ?>
    
    <h2>.htaccess Kontrolü</h2>
    <?php
    $htaccess = $basePath . '/.htaccess';
    if (file_exists($htaccess)) {
        echo "<div class='success'>✓ .htaccess dosyası mevcut</div>";
        echo "<div class='info'><strong>Boyut:</strong> " . filesize($htaccess) . " bytes</div>";
        
        // .htaccess içeriğini göster (ilk 500 karakter)
        $content = file_get_contents($htaccess);
        echo "<div class='info'><strong>İlk 500 karakter:</strong><br><pre>" . htmlspecialchars(substr($content, 0, 500)) . "...</pre></div>";
    } else {
        echo "<div class='error'>✗ .htaccess dosyası bulunamadı!</div>";
    }
    ?>
</body>
</html>
