const { test, expect } = require('@playwright/test');

const BASE = 'http://localhost/isortagim/public';
const ADMIN = BASE + '/admin';
const ADMIN_USER = 'NesimiAtes';
const ADMIN_PASS = 'test1234';

// ─── Yardımcı: Admin login ───────────────────────────────────────────────────
async function adminLogin(page) {
    await page.goto(`${ADMIN}/giris`);
    await page.fill('input[name="kullanici_adi"]', ADMIN_USER);
    await page.fill('input[name="sifre"]', ADMIN_PASS);
    await page.click('button[type="submit"]');
    await page.waitForLoadState('networkidle');
}

function noError(page) {
    return Promise.all([
        expect(page.locator('body')).not.toContainText('Whoops!'),
        expect(page.locator('body')).not.toContainText('ErrorException'),
        expect(page.locator('body')).not.toContainText('500 | Server Error'),
    ]);
}

// ════════════════════════════════════════════════════════════════════════════
// 1. MÜŞTERİ ARAYÜZÜ — Public Sayfalar
// ════════════════════════════════════════════════════════════════════════════
test.describe('1 · Müşteri — Public Sayfalar', () => {

    test('Ana sayfa', async ({ page }) => {
        await page.goto(BASE);
        await expect(page).not.toHaveURL(/error|exception/i);
        await noError(page);
    });

    test('Paketler listesi', async ({ page }) => {
        await page.goto(`${BASE}/paketler`);
        await noError(page);
    });

    test('Paketler — kategori filtresi', async ({ page }) => {
        await page.goto(`${BASE}/paketler`);
        const sel = page.locator('select[name="kategoriler"]');
        if (await sel.count() > 0) {
            const opts = await sel.locator('option').all();
            if (opts.length > 1) {
                const val = await opts[1].getAttribute('value');
                if (val && val.startsWith('http')) {
                    await page.goto(val);
                    await noError(page);
                }
            }
        }
    });

    test('Paketler — arama', async ({ page }) => {
        await page.goto(`${BASE}/paketler`);
        const inp = page.locator('input[name="kelime"]');
        if (await inp.count() > 0) {
            await inp.fill('web');
            await page.click('button[type="submit"]');
            await page.waitForLoadState('networkidle');
            await noError(page);
        }
    });

    test('Paket detay sayfası', async ({ page }) => {
        await page.goto(`${BASE}/paketler`);
        const link = page.locator('a[href*="/paket/"]').first();
        if (await link.count() > 0) {
            await link.click();
            await page.waitForLoadState('networkidle');
            await noError(page);
            await expect(page.locator('body')).not.toContainText('404');
        }
    });

    test('Hosting sayfası', async ({ page }) => {
        await page.goto(`${BASE}/hostingler`);
        await noError(page);
    });

    test('Alan adı sayfası', async ({ page }) => {
        await page.goto(`${BASE}/alan-adi`);
        await noError(page);
    });

    test('Alan adı — domain arama', async ({ page }) => {
        await page.goto(`${BASE}/alan-adi`);
        const inp = page.locator('input[name="alanadi"]').first();
        if (await inp.count() > 0) {
            await inp.fill('testdomain');
            // .com checkbox / ilk uzantıyı seç
            const btn = page.locator('button[type="submit"], button:has-text("Sorgula"), button:has-text("Ara")').first();
            if (await btn.count() > 0) {
                await btn.click();
                await page.waitForLoadState('networkidle');
                await noError(page);
            }
        }
    });

    test('Blog sayfası', async ({ page }) => {
        await page.goto(`${BASE}/blog`);
        await noError(page);
    });

    test('Blog detay sayfası', async ({ page }) => {
        await page.goto(`${BASE}/blog`);
        const link = page.locator('a[href*="/blog/"]').first();
        if (await link.count() > 0) {
            await link.click();
            await page.waitForLoadState('networkidle');
            await noError(page);
        }
    });

    test('Referanslar sayfası', async ({ page }) => {
        await page.goto(`${BASE}/referanslar`);
        await noError(page);
    });

    test('İletişim sayfası', async ({ page }) => {
        await page.goto(`${BASE}/iletisim`);
        await noError(page);
        await expect(page.locator('form')).toBeVisible();
    });

    test('Giriş sayfası', async ({ page }) => {
        await page.goto(`${BASE}/giris`);
        await noError(page);
        await expect(page.locator('input[type="password"]')).toBeVisible();
    });

    test('Kayıt sayfası', async ({ page }) => {
        await page.goto(`${BASE}/kayit`);
        await noError(page);
    });

    test('404 sayfası', async ({ page }) => {
        await page.goto(`${BASE}/olmayan-sayfa-xyz-123`);
        await noError(page);
    });

});

// ════════════════════════════════════════════════════════════════════════════
// 2. DİL DEĞİŞTİRME
// ════════════════════════════════════════════════════════════════════════════
test.describe('2 · Dil Değiştirme', () => {

    test('Ana sayfa — EN', async ({ page }) => {
        await page.goto(`${BASE}?lang=en`);
        await noError(page);
    });

    test('Ana sayfa — AR', async ({ page }) => {
        await page.goto(`${BASE}?lang=ar`);
        await noError(page);
    });

    test('Paketler — EN', async ({ page }) => {
        await page.goto(`${BASE}/paketler?lang=en`);
        await noError(page);
    });

    test('Alan adı — EN', async ({ page }) => {
        await page.goto(`${BASE}/alan-adi?lang=en`);
        await noError(page);
    });

});

// ════════════════════════════════════════════════════════════════════════════
// 3. ADMİN — Giriş
// ════════════════════════════════════════════════════════════════════════════
test.describe('3 · Admin — Giriş', () => {

    test('Admin giriş sayfası yükleniyor', async ({ page }) => {
        await page.goto(`${ADMIN}/giris`);
        await noError(page);
        await expect(page.locator('input[name="kullanici_adi"]')).toBeVisible();
    });

    test('Admin login başarılı', async ({ page }) => {
        await adminLogin(page);
        await expect(page).toHaveURL(/dashboard/);
        await noError(page);
    });

    test('Admin yanlış şifreyle giriş reddedilir', async ({ page }) => {
        await page.goto(`${ADMIN}/giris`);
        await page.fill('input[name="kullanici_adi"]', 'NesimiAtes');
        await page.fill('input[name="sifre"]', 'yanlis_sifre');
        await page.click('button[type="submit"]');
        await page.waitForLoadState('networkidle');
        await expect(page).toHaveURL(/giris/);
    });

});

// ════════════════════════════════════════════════════════════════════════════
// 4. ADMİN — İçerik Sayfaları
// ════════════════════════════════════════════════════════════════════════════
test.describe('4 · Admin — İçerik', () => {

    test('Dashboard', async ({ page }) => {
        await adminLogin(page);
        await noError(page);
        await expect(page.locator('body')).not.toContainText('Unauthorized');
    });

    test('Paketler listesi', async ({ page }) => {
        await adminLogin(page);
        await page.goto(`${ADMIN}/paketler`);
        await noError(page);
    });

    test('Paket Ekle formu — TR/EN/AR tablar ve geçiş', async ({ page }) => {
        await adminLogin(page);
        await page.goto(`${ADMIN}/paketler/ekle`);
        await noError(page);
        await expect(page.locator('button[data-tab-lang="tr"]')).toBeVisible();
        await expect(page.locator('button[data-tab-lang="en"]')).toBeVisible();
        await expect(page.locator('button[data-tab-lang="ar"]')).toBeVisible();
        await page.locator('button[data-tab-lang="en"]').click();
        await expect(page.locator('[x-show*="en"]').first()).toBeAttached();
        await page.locator('button[data-tab-lang="ar"]').click();
        await expect(page.locator('[x-show*="ar"]').first()).toBeAttached();
    });

    test('Paket Düzenle formu açılıyor', async ({ page }) => {
        await adminLogin(page);
        await page.goto(`${ADMIN}/paketler`);
        const editLink = page.locator('a[href*="/duzenle"]').first();
        if (await editLink.count() > 0) {
            await editLink.click();
            await page.waitForLoadState('networkidle');
            await noError(page);
            await expect(page.locator('button[data-tab-lang="tr"]')).toBeVisible();
        }
    });

    test('Sayfalar listesi ve Ekle formu — TR/EN/AR tablar', async ({ page }) => {
        await adminLogin(page);
        await page.goto(`${ADMIN}/sayfalar`);
        await noError(page);
        await page.goto(`${ADMIN}/sayfalar/ekle`);
        await noError(page);
        await expect(page.locator('button[data-tab-lang="tr"]')).toBeVisible();
        await expect(page.locator('button[data-tab-lang="en"]')).toBeVisible();
        await expect(page.locator('button[data-tab-lang="ar"]')).toBeVisible();
    });

    test('Blog listesi', async ({ page }) => {
        await adminLogin(page);
        await page.goto(`${ADMIN}/blog`);
        await noError(page);
    });

    test('Blog Ekle formu', async ({ page }) => {
        await adminLogin(page);
        await page.goto(`${ADMIN}/blog/ekle`);
        await noError(page);
    });

    test('Hizmetler listesi', async ({ page }) => {
        await adminLogin(page);
        await page.goto(`${ADMIN}/hizmetler`);
        await noError(page);
    });

    test('Hizmet Ekle formu', async ({ page }) => {
        await adminLogin(page);
        await page.goto(`${ADMIN}/hizmetler/ekle`);
        await noError(page);
    });

});

// ════════════════════════════════════════════════════════════════════════════
// 5. ADMİN — CRM & Diğer
// ════════════════════════════════════════════════════════════════════════════
test.describe('5 · Admin — CRM & Diğer', () => {

    test('CRM Müşteriler', async ({ page }) => {
        await adminLogin(page);
        await page.goto(`${ADMIN}/crm/musteriler`);
        await noError(page);
    });

    test('CRM Kanban', async ({ page }) => {
        await adminLogin(page);
        await page.goto(`${ADMIN}/crm/kanban`);
        await noError(page);
    });

    test('Alan Adı Fiyatları', async ({ page }) => {
        await adminLogin(page);
        await page.goto(`${ADMIN}/domain/fiyatlar`);
        await noError(page);
    });

    test('Genel Ayarlar', async ({ page }) => {
        await adminLogin(page);
        await page.goto(`${ADMIN}/ayarlar`);
        await noError(page);
    });

    test('Yöneticiler listesi', async ({ page }) => {
        await adminLogin(page);
        await page.goto(`${ADMIN}/yoneticiler`);
        await noError(page);
    });

    test('Faturalar', async ({ page }) => {
        await adminLogin(page);
        await page.goto(`${ADMIN}/faturalar/bekleyen`);
        await noError(page);
    });

    test('Dil Yönetimi listesi', async ({ page }) => {
        await adminLogin(page);
        await page.goto(`${ADMIN}/diller`);
        await noError(page);
    });

    test('Kampanyalar', async ({ page }) => {
        await adminLogin(page);
        await page.goto(`${ADMIN}/kampanyalar`);
        await noError(page);
    });

    test('Slider', async ({ page }) => {
        await adminLogin(page);
        await page.goto(`${ADMIN}/slider`);
        await noError(page);
    });

    test('Import/Export sayfası', async ({ page }) => {
        await adminLogin(page);
        await page.goto(`${ADMIN}/import-export`);
        await noError(page);
    });

    test('Tablolar kilit — kurulum sayfası', async ({ page }) => {
        await adminLogin(page);
        await page.goto(`${ADMIN}/tablolar/kilit/kurulum`);
        await noError(page);
        await expect(page.locator('body')).not.toContainText('500');
    });

    test('Tablolar kilit — PIN sayfası', async ({ page }) => {
        await adminLogin(page);
        await page.goto(`${ADMIN}/tablolar/kilit/pin`);
        await noError(page);
    });

    test('Kategoriler listesi', async ({ page }) => {
        await adminLogin(page);
        await page.goto(`${ADMIN}/kategoriler`);
        await noError(page);
    });

    test('Referanslar listesi', async ({ page }) => {
        await adminLogin(page);
        await page.goto(`${ADMIN}/referanslar`);
        await noError(page);
    });

    test('Yorumlar listesi', async ({ page }) => {
        await adminLogin(page);
        await page.goto(`${ADMIN}/yorumlar`);
        await noError(page);
    });

    test('Kuponlar listesi', async ({ page }) => {
        await adminLogin(page);
        await page.goto(`${ADMIN}/kuponlar`);
        await noError(page);
    });

    test('Mesajlar listesi', async ({ page }) => {
        await adminLogin(page);
        await page.goto(`${ADMIN}/mesajlar`);
        await noError(page);
    });

    test('Destek listesi', async ({ page }) => {
        await adminLogin(page);
        await page.goto(`${ADMIN}/destek`);
        await noError(page);
    });

    test('Ticket listesi', async ({ page }) => {
        await adminLogin(page);
        await page.goto(`${ADMIN}/tickets`);
        await noError(page);
    });

    test('Bildirimler', async ({ page }) => {
        await adminLogin(page);
        await page.goto(`${ADMIN}/bildirimler`);
        await noError(page);
    });

    test('Not Defteri', async ({ page }) => {
        await adminLogin(page);
        await page.goto(`${ADMIN}/not-defteri`);
        await noError(page);
    });

    test('E-Bülten', async ({ page }) => {
        await adminLogin(page);
        await page.goto(`${ADMIN}/ebulten`);
        await noError(page);
    });

    test('Bayiler listesi', async ({ page }) => {
        await adminLogin(page);
        await page.goto(`${ADMIN}/bayiler`);
        await noError(page);
    });

    test('Roller listesi', async ({ page }) => {
        await adminLogin(page);
        await page.goto(`${ADMIN}/roller`);
        await noError(page);
    });

    test('Üyeler listesi', async ({ page }) => {
        await adminLogin(page);
        await page.goto(`${ADMIN}/uyeler`);
        await noError(page);
    });

    test('Banka Hesapları', async ({ page }) => {
        await adminLogin(page);
        await page.goto(`${ADMIN}/banka-hesaplari`);
        await noError(page);
    });

    test('Ürünler listesi', async ({ page }) => {
        await adminLogin(page);
        await page.goto(`${ADMIN}/urunler`);
        await noError(page);
    });

    test('Hosting Paketler', async ({ page }) => {
        await adminLogin(page);
        await page.goto(`${ADMIN}/hosting/paketler`);
        await noError(page);
    });

    test('Hosting Satışlar', async ({ page }) => {
        await adminLogin(page);
        await page.goto(`${ADMIN}/hosting/satislar`);
        await noError(page);
    });

    test('Satışlar — Hosting', async ({ page }) => {
        await adminLogin(page);
        await page.goto(`${ADMIN}/satislar/hosting`);
        await noError(page);
    });

    test('Satışlar — Web Paket', async ({ page }) => {
        await adminLogin(page);
        await page.goto(`${ADMIN}/satislar/web-paket`);
        await noError(page);
    });

    test('Satışlar — Domain', async ({ page }) => {
        await adminLogin(page);
        await page.goto(`${ADMIN}/satislar/domain`);
        await noError(page);
    });

    test('Domain Satışlar', async ({ page }) => {
        await adminLogin(page);
        await page.goto(`${ADMIN}/domain/satislar`);
        await noError(page);
    });

    test('Menüler — Header', async ({ page }) => {
        await adminLogin(page);
        await page.goto(`${ADMIN}/menuler/header`);
        await noError(page);
    });

    test('Menüler — Footer', async ({ page }) => {
        await adminLogin(page);
        await page.goto(`${ADMIN}/menuler/footer`);
        await noError(page);
    });

    test('Odeme Bildirim', async ({ page }) => {
        await adminLogin(page);
        await page.goto(`${ADMIN}/odeme-bildirim`);
        await noError(page);
    });

    test('Profil', async ({ page }) => {
        await adminLogin(page);
        await page.goto(`${ADMIN}/profil`);
        await noError(page);
    });

    test('Ayarlar — Genel', async ({ page }) => {
        await adminLogin(page);
        await page.goto(`${ADMIN}/ayarlar`);
        await noError(page);
    });

    test('Ayarlar — Sanal Pos', async ({ page }) => {
        await adminLogin(page);
        await page.goto(`${ADMIN}/ayarlar/sanal-pos`);
        await noError(page);
    });

    test('CRM Müşteri Oluştur formu', async ({ page }) => {
        await adminLogin(page);
        await page.goto(`${ADMIN}/crm/musteriler/create`);
        await noError(page);
        await expect(page.locator('input[name="adi"]')).toBeVisible();
    });

    test('CRM Fırsatlar', async ({ page }) => {
        await adminLogin(page);
        await page.goto(`${ADMIN}/crm/firsatlar`);
        await noError(page);
    });

    test('CRM Görevler', async ({ page }) => {
        await adminLogin(page);
        await page.goto(`${ADMIN}/crm/gorevler`);
        await noError(page);
    });

    test('CRM Pipelines', async ({ page }) => {
        await adminLogin(page);
        await page.goto(`${ADMIN}/crm/pipelines`);
        await noError(page);
    });

    test('CRM Domain Takip', async ({ page }) => {
        await adminLogin(page);
        await page.goto(`${ADMIN}/crm/domains`);
        await noError(page);
    });

    test('CRM Müşteri detay sayfası', async ({ page }) => {
        await adminLogin(page);
        await page.goto(`${ADMIN}/crm/musteriler`);
        const detayLink = page.locator('a[href*="/crm/musteriler/"][href*="/show"], a.action-btn[href*="/crm/musteriler/"]').first();
        if (await detayLink.count() > 0) {
            await detayLink.click();
            await page.waitForLoadState('networkidle');
            await noError(page);
        }
    });

});

// ════════════════════════════════════════════════════════════════════════════
// 6. NAVBAR & UI
// ════════════════════════════════════════════════════════════════════════════
test.describe('6 · Navbar & UI', () => {

    test('Navbar nav linkleri 500 dönmüyor', async ({ page }) => {
        await page.goto(BASE);
        const links = await page.locator('nav a, header a').all();
        const hrefs = new Set();
        for (const link of links) {
            const href = await link.getAttribute('href');
            if (href && href.includes('localhost') && !href.includes('#') && !href.includes('javascript')) {
                hrefs.add(href);
            }
        }
        for (const href of [...hrefs].slice(0, 8)) {
            const res = await page.goto(href);
            expect(res.status()).not.toBe(500);
        }
    });

    test('Para birimi dropdown — javascript:void(0) kullanıyor', async ({ page }) => {
        await page.goto(BASE);
        const trigger = page.locator('a[href="javascript:void(0)"]').first();
        // dropdown trigger varsa kontrol et
        if (await trigger.count() > 0) {
            await expect(trigger).toBeVisible();
        }
    });

});
