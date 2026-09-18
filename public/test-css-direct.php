<?php
/**
 * CSS dosyasına direkt erişim testi
 */
$cssFile = __DIR__ . '/yonetim/vendors/css/vendor.bundle.base.css';

if (file_exists($cssFile)) {
    header('Content-Type: text/css; charset=utf-8');
    readfile($cssFile);
} else {
    header('Content-Type: text/html; charset=utf-8');
    echo "<h1>CSS Dosyası Bulunamadı!</h1>";
    echo "<p>Dosya yolu: " . htmlspecialchars($cssFile) . "</p>";
    echo "<p>Public klasörü: " . __DIR__ . "</p>";
    echo "<p>Dosya var mı? " . (file_exists($cssFile) ? 'EVET' : 'HAYIR') . "</p>";
}
