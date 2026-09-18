// Paketler Sayfası JavaScript
// Arama davranışı: mobilde sabit alt alta, desktop'ta toggle

document.addEventListener('DOMContentLoaded', function() {
    var toggleBtn = document.getElementById('paketSearchToggle');
    var input = document.getElementById('paketSearchInput');

    if (!toggleBtn || !input) {
        return;
    }

    var isMobile = window.innerWidth <= 768;

    if (isMobile) {
        // MOBİL: input her zaman butonun altında ve tam genişlikte olsun
        input.style.display = 'block';
        input.style.maxWidth = '100%';
        input.style.flex = '1 1 100%';
        input.style.opacity = '1';
        input.style.width = '100%';
        input.style.color = '#fff';

        // Buton sadece input'u odaklasın, toggle yok
        toggleBtn.addEventListener('click', function(e) {
            e.preventDefault();
            input.focus();
        });

        return;
    }

    // DESKTOP: Toggle davranışı
    toggleBtn.addEventListener('click', function(e) {
        e.preventDefault();

        var isOpen = input.style.opacity === '1';

        if (!isOpen) {
            input.style.display = 'block';
            input.style.maxWidth = '260px';
            input.style.flex = '1 1 auto';
            input.style.opacity = '1';
            input.style.width = '100%';
            input.style.color = '#fff';
            setTimeout(function() {
                input.focus();
            }, 150);
        } else {
            input.value = '';
            input.style.maxWidth = '0';
            input.style.flex = '0 1 0';
            input.style.opacity = '0';
            input.style.width = '0';
        }
    });
});

// Infinite scroll: paketler sayfasında aşağı kaydırdıkça yeni kartları yükle
document.addEventListener('DOMContentLoaded', function () {
    const sentinel = document.getElementById('paketler-infinite-sentinel');
    const list = document.getElementById('paketler-list');
    if (!sentinel || !list) return;
    
    const limit = 9;
    let offset = document.querySelectorAll('#paketler-list .paketler-item').length;
    let hasMore = true;
    let isLoading = false;
    
    // Path'ten kategori id'sini al: /paketler/{kategori}
    const pathParts = window.location.pathname.split('/').filter(Boolean);
    const kategori = (pathParts.length >= 2 && pathParts[0] === 'paketler') ? pathParts[1] : null;
    
    async function loadMore() {
        if (isLoading || !hasMore) return;
        isLoading = true;
        
        try {
            const params = new URLSearchParams(window.location.search);
            if (kategori) {
                params.set('kategori', kategori);
            } else {
                params.delete('kategori');
            }
            
            params.set('offset', offset);
            params.set('limit', limit);
            
            const url = window.location.origin + '/paketler/load?' + params.toString();
            
            const res = await fetch(url, {
                method: 'GET',
                headers: {
                    'Accept': 'application/json'
                }
            });
            
            if (!res.ok) {
                throw new Error('Load failed: ' + res.status);
            }
            
            const data = await res.json();
            
            if (data.html) {
                list.insertAdjacentHTML('beforeend', data.html);
            }
            
            offset = typeof data.nextOffset === 'number' ? data.nextOffset : (offset + limit);
            hasMore = !!data.hasMore;
        } catch (e) {
            // Hata olursa infinite scroll durur (console'da görünsün)
            console.error(e);
            hasMore = false;
        } finally {
            isLoading = false;
        }
    }
    
    const observer = new IntersectionObserver(function (entries) {
        const first = entries[0];
        if (first && first.isIntersecting) {
            loadMore();
        }
    }, {
        root: null,
        rootMargin: '300px',
        threshold: 0
    });
    
    observer.observe(sentinel);
});
