// Admin API Settings JavaScript
// Clipboard kopyalama fonksiyonu

function copyToClipboard(text) {
    navigator.clipboard.writeText(text).then(function() {
        alert('Kopyalandı: ' + text);
    });
}
