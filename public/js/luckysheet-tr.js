/**
 * Luckysheet Türkçe çeviri katmanı (overlay)
 * ------------------------------------------------------------
 * Luckysheet'in resmi Türkçe dil paketi yok. Bu dosya, lang:'en'
 * ile yüklenen arayüzdeki İngilizce metinleri (araç çubuğu
 * ipuçları, sağ tık menüleri, sayfa sekmesi menüsü, alt istatistik
 * çubuğu, diyaloglar) ekrana geldikleri anda Türkçe'ye çevirir.
 *
 * - Sadece id/class'ında "luckysheet" geçen alanlara dokunur
 *   (panelin geri kalanını etkilemez).
 * - Hücre düzenleme kutusu ve formül çubuğuna ASLA dokunmaz
 *   (kullanıcının yazdığı içerik değişmez; hücreler zaten canvas).
 * - Sözlükte olmayan metin İngilizce kalır (zararsız).
 *
 * Yükleme: luckysheet.umd.js'ten SONRA dahil edilir.
 *
 * v2: TR/EN geçişi eklendi. Tercih localStorage'da ('ls_lang')
 * tutulur; 'en' seçiliyse bu dosya hiçbir şey yapmaz (arayüz
 * Luckysheet'in kendi İngilizcesi kalır). Butondan
 * window.LuckysheetTR.toggle() çağrılır (tercihi kaydedip
 * sayfayı yeniler — en temiz/garantili yöntem).
 */
(function () {
    'use strict';

    var TR = {
        /* --- Araç çubuğu ipuçları --- */
        'Undo': 'Geri al',
        'Redo': 'Yinele',
        'Paint format': 'Biçim boyacısı',
        'Format Painter': 'Biçim boyacısı',
        'Currency format': 'Para birimi biçimi',
        'Percentage format': 'Yüzde biçimi',
        'Decrease the number of decimal places': 'Ondalık basamağı azalt',
        'Increase the number of decimal places': 'Ondalık basamağı artır',
        'More formats': 'Diğer biçimler',
        'Font': 'Yazı tipi',
        'Font size': 'Yazı boyutu',
        'Bold (Ctrl+B)': 'Kalın (Ctrl+B)',
        'Italic (Ctrl+I)': 'İtalik (Ctrl+I)',
        'Strikethrough (Alt+Shift+5)': 'Üstü çizili (Alt+Shift+5)',
        'Underline (Alt+Shift+6)': 'Altı çizili (Alt+Shift+6)',
        'Text color': 'Yazı rengi',
        'Fill color': 'Dolgu rengi',
        'Border': 'Kenarlık',
        'Border style': 'Kenarlık stili',
        'Border color': 'Kenarlık rengi',
        'Merge cells': 'Hücreleri birleştir',
        'Merge all': 'Tümünü birleştir',
        'Merge horizontally': 'Yatay birleştir',
        'Merge vertically': 'Dikey birleştir',
        'Unmerge': 'Birleştirmeyi kaldır',
        'Horizontal align': 'Yatay hizalama',
        'Vertical align': 'Dikey hizalama',
        'Text wrap': 'Metni kaydır',
        'Text rotate': 'Metni döndür',
        'Text rotation': 'Metni döndür',
        'Insert image': 'Resim ekle',
        'Insert link': 'Bağlantı ekle',
        'Insert chart': 'Grafik ekle',
        'Chart': 'Grafik',
        'Comment': 'Not',
        'Add comment': 'Not ekle',
        'Edit comment': 'Notu düzenle',
        'Delete comment': 'Notu sil',
        'Show comment': 'Notu göster',
        'Hide comment': 'Notu gizle',
        'PivotTable': 'Özet tablo',
        'Pivot Table': 'Özet tablo',
        'Screenshot': 'Ekran görüntüsü',
        'Split text': 'Metni böl',
        'Data verification': 'Veri doğrulama',
        'Freeze top row': 'Üst satırı dondur',
        'Freeze first row': 'İlk satırı dondur',
        'Freeze first column': 'İlk sütunu dondur',
        'Freeze row': 'Satırı dondur',
        'Freeze column': 'Sütunu dondur',
        'Cancel freeze': 'Dondurmayı kaldır',
        'Sort and filter': 'Sırala ve filtrele',
        'Find and replace': 'Bul ve değiştir',
        'Formula': 'Formül',
        'More functions': 'Diğer fonksiyonlar',
        'Conditional format': 'Koşullu biçimlendirme',
        'Protect the sheet': 'Sayfayı koru',
        'Protection': 'Koruma',
        'Print': 'Yazdır',
        'More': 'Diğer',
        'Toolbox': 'Araç kutusu',

        /* --- Renk seçici --- */
        'Reset': 'Sıfırla',
        'CUSTOM': 'ÖZEL',
        'Custom': 'Özel',
        'choose color': 'renk seç',
        'Alternating colors': 'Dönüşümlü renkler',
        'Collapse': 'Daralt',

        /* --- Hizalama / kaydırma seçenekleri --- */
        'Left': 'Sol',
        'Center': 'Orta',
        'Right': 'Sağ',
        'Top': 'Üst',
        'Middle': 'Orta',
        'Bottom': 'Alt',
        'Overflow': 'Taştır',
        'Clip': 'Kırp',
        'Wrap': 'Kaydır',
        'None': 'Yok',

        /* --- Sağ tık menüsü --- */
        'copy': 'kopyala',
        'Copy': 'Kopyala',
        'copy as': 'farklı kopyala',
        'paste': 'yapıştır',
        'Paste': 'Yapıştır',
        'cut': 'kes',
        'Cut': 'Kes',
        'Insert': 'Ekle',
        'Delete': 'Sil',
        'Delete cell': 'Hücre sil',
        'Delete selected': 'Seçileni sil',
        'Delete row': 'Satırı sil',
        'Delete column': 'Sütunu sil',
        'Insert row': 'Satır ekle',
        'Insert column': 'Sütun ekle',
        'Hide': 'Gizle',
        'Hide selected': 'Seçileni gizle',
        'Show hidden': 'Gizlenenleri göster',
        'Clear content': 'İçeriği temizle',
        'Set row height': 'Satır yüksekliği',
        'Set column width': 'Sütun genişliği',
        'row': 'satır',
        'rows': 'satır',
        'column': 'sütun',
        'columns': 'sütun',
        'up': 'üste',
        'down': 'alta',
        'left': 'sola',
        'right': 'sağa',
        'Add': 'Ekle',
        'Sort': 'Sırala',
        'Sort selection': 'Seçimi sırala',
        'Custom sort': 'Özel sıralama',
        'A-Z order': 'A→Z sırala',
        'Z-A order': 'Z→A sırala',
        'Filter': 'Filtre',
        'Filter selection': 'Seçimi filtrele',
        'Create filter': 'Filtre oluştur',
        'Clear filter': 'Filtreyi temizle',
        'Create chart': 'Grafik oluştur',

        /* --- Sayfa sekmesi menüsü --- */
        'Rename': 'Yeniden adlandır',
        'Change color': 'Rengi değiştir',
        'Move to the left': 'Sola taşı',
        'Move to the right': 'Sağa taşı',
        'Move left': 'Sola taşı',
        'Move right': 'Sağa taşı',
        'Back to top': 'Başa dön',

        /* --- Diyaloglar / butonlar --- */
        'Confirm': 'Onayla',
        'OK': 'Tamam',
        'Cancel': 'İptal',
        'Close': 'Kapat',
        'Apply': 'Uygula',
        'Search': 'Ara',
        'Find': 'Bul',
        'Find all': 'Tümünü bul',
        'Find content': 'Aranan',
        'Replace': 'Değiştir',
        'Replace all': 'Tümünü değiştir',
        'Replace with': 'Yeni değer',
        'Match case': 'Büyük/küçük harf duyarlı',
        'Match entire cell contents': 'Hücrenin tamamı eşleşsin',
        'Regular expression': 'Düzenli ifade (regex)',

        /* --- Sayı biçimleri --- */
        'Automatic': 'Otomatik',
        'General': 'Genel',
        'Plain text': 'Düz metin',
        'Number': 'Sayı',
        'Percent': 'Yüzde',
        'Scientific': 'Bilimsel',
        'Accounting': 'Muhasebe',
        'Currency': 'Para birimi',
        'Date': 'Tarih',
        'Time': 'Saat',
        'Time 24H': 'Saat (24s)',
        'Date time': 'Tarih ve saat',
        'Date time 24 H': 'Tarih ve saat (24s)',
        'Thousand separator': 'Binlik ayraç',
        'Custom formats': 'Özel biçimler',

        /* --- Sağ tık menüsü (ekran görüntülerinden eklenenler) --- */
        'Copy as': 'Farklı kopyala',
        'InsertRow': 'Satır ekle',
        'InsertColumn': 'Sütun ekle',
        'Delete selected Row': 'Seçili satırı sil',
        'Delete selected Column': 'Seçili sütunu sil',
        'Matrix operation': 'Matris işlemleri',
        'Cell format config': 'Hücre biçimi ayarı',
        'Data validation': 'Veri doğrulama'
    };

    /* Alt istatistik çubuğu: "Count: 1  Sum: 2300 ..." biçimindeki etiketler */
    var LABELS = { 'Count': 'Sayım', 'Sum': 'Toplam', 'Average': 'Ortalama', 'Max': 'En büyük', 'Min': 'En küçük' };
    var LABEL_RE = /\b(Count|Sum|Average|Max|Min)\b(?=\s*[::])/g;

    /* Kullanıcı içeriğine asla dokunulmayacak alanlar (hücre editörü, formül çubuğu) */
    var EDITOR_IDS = {
        'luckysheet-input-box': 1,
        'luckysheet-rich-text-editor': 1,
        'luckysheet-functionbox-cell': 1,
        'luckysheet-helpbox-cell': 1,
        'luckysheet-wa-functionbox': 1
    };

    function inLucky(el) {
        var lucky = false;
        while (el && el !== document) {
            if (el.id) {
                if (EDITOR_IDS[el.id]) return false; // editör alanı: dokunma
                if (el.id.indexOf('luckysheet') === 0) lucky = true;
            }
            if (el.className && typeof el.className === 'string' && el.className.indexOf('luckysheet') !== -1) {
                if (el.className.indexOf('luckysheet-cell-input') !== -1) return false; // hücre editörü
                lucky = true;
            }
            el = el.parentNode;
        }
        return lucky;
    }

    function trText(node) {
        var v = node.nodeValue;
        if (!v) return;
        var t = v.trim();
        if (!t) return;
        if (TR.hasOwnProperty(t)) {
            node.nodeValue = v.replace(t, TR[t]);
            return;
        }
        LABEL_RE.lastIndex = 0;
        if (LABEL_RE.test(v)) {
            LABEL_RE.lastIndex = 0;
            node.nodeValue = v.replace(LABEL_RE, function (m) { return LABELS[m] || m; });
        }
        LABEL_RE.lastIndex = 0;
    }

    function trTitle(el) {
        if (!el.getAttribute) return;
        var ti = el.getAttribute('title');
        if (ti && TR.hasOwnProperty(ti.trim())) el.setAttribute('title', TR[ti.trim()]);
    }

    function walk(root) {
        if (!root) return;
        if (root.nodeType === 3) { // text node
            if (inLucky(root.parentNode)) trText(root);
            return;
        }
        if (root.nodeType !== 1 && root.nodeType !== 9) return;
        var tw = document.createTreeWalker(root, NodeFilter.SHOW_TEXT, null, false);
        var n;
        while ((n = tw.nextNode())) {
            if (inLucky(n.parentNode)) trText(n);
        }
        if (root.querySelectorAll) {
            var els = root.querySelectorAll('[title]');
            for (var i = 0; i < els.length; i++) {
                if (inLucky(els[i])) trTitle(els[i]);
            }
            if (root.nodeType === 1 && inLucky(root)) trTitle(root);
        }
    }

    function start() {
        walk(document.body);

        var mo = new MutationObserver(function (muts) {
            for (var i = 0; i < muts.length; i++) {
                var m = muts[i];
                if (m.type === 'characterData') {
                    if (m.target && inLucky(m.target.parentNode)) trText(m.target);
                    continue;
                }
                if (m.addedNodes) {
                    for (var j = 0; j < m.addedNodes.length; j++) walk(m.addedNodes[j]);
                }
            }
        });
        mo.observe(document.body, { childList: true, subtree: true, characterData: true });

        // Luckysheet ilk render'ı için birkaç güvenlik taraması (ilk 6 sn)
        var tries = 0;
        var iv = setInterval(function () {
            walk(document.getElementById('luckysheet') || document.body);
            if (++tries >= 6) clearInterval(iv);
        }, 1000);
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', maybeStart);
    } else {
        maybeStart();
    }

    /* ---------- TR / EN tercihi ---------- */
    function getLang() {
        try {
            var v = localStorage.getItem('ls_lang');
            return (v === 'en') ? 'en' : 'tr'; // varsayılan: tr
        } catch (e) { return 'tr'; }
    }

    function maybeStart() {
        if (getLang() === 'tr') start();
        // 'en' ise hiçbir şey yapma — Luckysheet kendi İngilizcesiyle kalır
    }

    /* Dışarıdan kullanılacak API (TR/EN butonu için) */
    window.LuckysheetTR = {
        lang: getLang,
        set: function (lang) {
            try { localStorage.setItem('ls_lang', lang === 'en' ? 'en' : 'tr'); } catch (e) {}
            location.reload(); // en temiz geçiş: menüler/ipuçları sıfırdan doğru dilde kurulur
        },
        toggle: function () {
            this.set(getLang() === 'tr' ? 'en' : 'tr');
        }
    };
})();