/**
 * Dil parametresini tüm linklerde koru
 */
(function() {
    'use strict';
    
    // Mevcut dil parametresini al
    function getCurrentLang() {
        const urlParams = new URLSearchParams(window.location.search);
        return urlParams.get('lang') || null;
    }
    
    // URL'e dil parametresi ekle
    function addLangToUrl(url, lang) {
        if (!lang || lang === 'tr') {
            return url; // Türkçe için lang parametresi ekleme
        }
        
        try {
            const urlObj = new URL(url, window.location.origin);
            urlObj.searchParams.set('lang', lang);
            return urlObj.toString();
        } catch (e) {
            // Relative URL ise
            const separator = url.includes('?') ? '&' : '?';
            return url + separator + 'lang=' + lang;
        }
    }
    
    // Sayfa yüklendiğinde tüm linkleri güncelle
    function updateAllLinks() {
        // Önce sessionStorage'dan dil al, yoksa URL'den
        let currentLang = sessionStorage.getItem('current_lang');
        if (!currentLang) {
            currentLang = getCurrentLang();
            if (currentLang) {
                sessionStorage.setItem('current_lang', currentLang);
            }
        }
        
        if (!currentLang) {
            return; // Dil parametresi yoksa işlem yapma
        }
        
        // Tüm <a> tag'lerini bul
        const links = document.querySelectorAll('a[href]');
        links.forEach(function(link) {
            const href = link.getAttribute('href');
            
            // Eğer zaten lang parametresi varsa veya external link ise atla
            if (!href || href.includes('lang=') || href.startsWith('http://') || href.startsWith('https://') || href.startsWith('javascript:') || href.startsWith('mailto:') || href.startsWith('tel:')) {
                return;
            }
            
            // Admin, API, payment gibi route'ları atla
            if (href.includes('/admin/') || href.includes('/api/') || href.includes('/payment/') || href.includes('/odeme/') || href.includes('/_class/')) {
                return;
            }
            
            // Hash (#) içeren linkleri atla
            if (href.includes('#')) {
                return;
            }
            
            // Yeni URL'i oluştur
            const newHref = addLangToUrl(href, currentLang);
            if (newHref !== href) {
                link.setAttribute('href', newHref);
            }
        });
        
        // Form action'larını da güncelle
        const forms = document.querySelectorAll('form[action]');
        forms.forEach(function(form) {
            const action = form.getAttribute('action');
            if (action && !action.includes('lang=') && !action.startsWith('http')) {
                const newAction = addLangToUrl(action, currentLang);
                if (newAction !== action) {
                    form.setAttribute('action', newAction);
                }
            }
        });
    }
    
    // Sayfa yüklendiğinde çalıştır
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', updateAllLinks);
    } else {
        updateAllLinks();
    }
    
    // Dinamik içerik için MutationObserver
    const observer = new MutationObserver(function(mutations) {
        let shouldUpdate = false;
        mutations.forEach(function(mutation) {
            if (mutation.addedNodes.length > 0) {
                shouldUpdate = true;
            }
        });
        if (shouldUpdate) {
            setTimeout(updateAllLinks, 100);
        }
    });
    
    observer.observe(document.body, {
        childList: true,
        subtree: true
    });
    
    // Link tıklamalarını dinle ve lang parametresini koru
    document.addEventListener('click', function(e) {
        const link = e.target.closest('a[href]');
        if (link) {
            const href = link.getAttribute('href');
            const currentLang = sessionStorage.getItem('current_lang') || getCurrentLang();
            
            if (currentLang && currentLang !== 'tr' && href && !href.includes('lang=') && 
                !href.startsWith('http') && !href.startsWith('javascript:') && 
                !href.startsWith('mailto:') && !href.startsWith('tel:') &&
                !href.includes('/admin/') && !href.includes('/api/') && !href.includes('#') &&
                !href.includes('/payment/') && !href.includes('/odeme/')) {
                
                e.preventDefault();
                const newHref = addLangToUrl(href, currentLang);
                window.location.href = newHref;
            }
        }
    });
    
    // URL değiştiğinde sessionStorage'ı güncelle
    window.addEventListener('popstate', function() {
        const lang = getCurrentLang();
        if (lang) {
            sessionStorage.setItem('current_lang', lang);
        }
    });
})();

