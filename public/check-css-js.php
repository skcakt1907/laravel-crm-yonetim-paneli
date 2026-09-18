<?php
/**
 * CSS ve JS dosyalarının gerçekten erişilebilir olup olmadığını kontrol eder
 */
header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html>
<head>
    <title>CSS/JS Dosya Kontrolü</title>
    <style>
        body { font-family: Arial; margin: 20px; background: #f5f5f5; }
        .success { color: green; font-weight: bold; }
        .error { color: red; font-weight: bold; }
        .info { background: white; padding: 15px; margin: 10px 0; border-radius: 5px; border-left: 4px solid #007bff; }
        .test-link { display: inline-block; margin: 5px; padding: 8px 15px; background: #007bff; color: white; text-decoration: none; border-radius: 3px; }
        .test-link:hover { background: #0056b3; }
        table { width: 100%; border-collapse: collapse; background: white; margin-top: 20px; }
        th, td { border: 1px solid #ddd; padding: 12px; text-align: left; }
        th { background: #007bff; color: white; }
        tr:nth-child(even) { background: #f9f9f9; }
    </style>
</head>
<body>
    <h1>🔍 CSS/JS Dosya Kontrolü</h1>
    
    <div class="info">
        <strong>Sunucu:</strong> <?php echo $_SERVER['HTTP_HOST'] ?? 'N/A'; ?><br>
        <strong>Zaman:</strong> <?php echo date('Y-m-d H:i:s'); ?><br>
        <strong>PHP Version:</strong> <?php echo PHP_VERSION; ?>
    </div>
    
    <h2>📋 Test Dosyaları</h2>
    <p>Aşağıdaki linklere tıklayarak dosyaların erişilebilir olup olmadığını kontrol edin:</p>
    
    <?php
    $basePath = __DIR__;
    $testFiles = [
        'CSS' => [
            'yonetim/vendors/iconfonts/mdi/font/css/materialdesignicons.min.css',
            'yonetim/vendors/css/vendor.bundle.base.css',
            'yonetim/vendors/css/vendor.bundle.addons.css',
            'yonetim/vendors/iconfonts/ti-icons/css/themify-icons.css',
            'yonetim/vendors/iconfonts/simple-line-icon/css/simple-line-icons.css',
            'yonetim/vendors/iconfonts/font-awesome/css/font-awesome.min.css',
            'yonetim/css/vertical-layout-light/style.css',
            'yonetim/css/admin-layout.css',
        ],
        'JavaScript' => [
            'yonetim/vendors/js/vendor.bundle.base.js',
            'yonetim/vendors/js/vendor.bundle.addons.js',
            'yonetim/js/off-canvas.js',
            'yonetim/js/hoverable-collapse.js',
            'yonetim/js/settings.js',
            'yonetim/js/todolist.js',
            'yonetim/js/admin-layout.js',
        ]
    ];
    
    foreach ($testFiles as $type => $files) {
        echo "<h3>{$type} Dosyaları</h3>";
        echo "<table>";
        echo "<tr><th>Dosya</th><th>Durum</th><th>Boyut</th><th>MIME Type</th><th>Test</th></tr>";
        
        foreach ($files as $file) {
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
            
            $status = $exists ? 'success' : 'error';
            $statusText = $exists ? '✓ VAR' : '✗ YOK';
            $statusIcon = $exists ? '✅' : '❌';
            
            echo "<tr>";
            echo "<td><strong>{$file}</strong></td>";
            echo "<td class='{$status}'>{$statusIcon} {$statusText}</td>";
            echo "<td>" . ($size > 0 ? number_format($size) . ' bytes (' . round($size/1024, 2) . ' KB)' : '-') . "</td>";
            echo "<td>{$mimeType}</td>";
            echo "<td>";
            if ($exists) {
                $url = '/' . $file;
                echo "<a href='{$url}' target='_blank' class='test-link'>Test Et</a>";
            } else {
                echo "<span style='color: red;'>Dosya bulunamadı</span>";
            }
            echo "</td>";
            echo "</tr>";
        }
        
        echo "</table><br>";
    }
    ?>
    
    <h2>🔧 Öneriler</h2>
    <div class="info">
        <strong>Eğer dosyalar VAR ama hala yüklenmiyorsa:</strong>
        <ol>
            <li><strong>Tarayıcı Cache'ini Temizle:</strong> Ctrl+Shift+Delete veya Hard Refresh: Ctrl+F5</li>
            <li><strong>Gizli Modda Test Et:</strong> Tarayıcıyı gizli modda açıp test edin</li>
            <li><strong>Network Tab'ı Kontrol Et:</strong> F12 > Network > CSS/JS dosyalarının durumunu kontrol edin</li>
            <li><strong>.htaccess Kontrolü:</strong> <a href="/debug-rewrite.php" target="_blank">debug-rewrite.php</a> dosyasını çalıştırın</li>
        </ol>
        
        <strong>Eğer dosyalar YOK ise:</strong>
        <ol>
            <li>Local'deki <code>public/yonetim/</code> klasörünü FTP ile canlı sunucuya yükleyin</li>
            <li>Dosya izinlerini kontrol edin (644 veya 755)</li>
            <li>Dosya yollarının doğru olduğundan emin olun</li>
        </ol>
    </div>
    
    <hr>
    <p><small>Bu dosyayı kontrol sonrası silmeyi unutmayın!</small></p>
</body>
</html>
