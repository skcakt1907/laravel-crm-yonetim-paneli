const { test, expect } = require('@playwright/test');

const BASE = 'http://localhost/isortagim/public';

test.describe('Müşteri Arayüzü - Genel Sayfalar', () => {

    test('Ana sayfa yükleniyor', async ({ page }) => {
        await page.goto(BASE);
        await expect(page).not.toHaveURL(/error|exception/i);
        const status = await page.evaluate(() => document.readyState);
        expect(status).toBe('complete');
        // 500 / hata sayfası değil
        await expect(page.locator('body')).not.toContainText('Whoops!');
        await expect(page.locator('body')).not.toContainText('500');
    });

    test('Paketler sayfası yükleniyor', async ({ page }) => {
        await page.goto(`${BASE}/paketler`);
        await expect(page).not.toHaveURL(/error|exception/i);
        await expect(page.locator('body')).not.toContainText('Whoops!');
    });

    test('Paketler - kategori filtresi çalışıyor', async ({ page }) => {
        await page.goto(`${BASE}/paketler`);
        const select = page.locator('select[name="kategoriler"]');
        const count = await select.locator('option').count();
        if (count > 1) {
            // İkinci option (ilk kategori) seçilince sayfa yüklensin
            const secondOptionValue = await select.locator('option').nth(1).getAttribute('value');
            await page.goto(secondOptionValue);
            await expect(page.locator('body')).not.toContainText('Whoops!');
        }
    });

    test('Paketler - arama çalışıyor', async ({ page }) => {
        await page.goto(`${BASE}/paketler`);
        await page.fill('input[name="kelime"]', 'test');
        await page.click('button[type="submit"]');
        await page.waitForLoadState('networkidle');
        await expect(page.locator('body')).not.toContainText('Whoops!');
    });

    test('Hosting sayfası yükleniyor', async ({ page }) => {
        await page.goto(`${BASE}/hostingler`);
        await expect(page.locator('body')).not.toContainText('Whoops!');
    });

    test('Alan adı (domain) sayfası yükleniyor', async ({ page }) => {
        await page.goto(`${BASE}/alan-adi`);
        const body = page.locator('body');
        await expect(body).not.toContainText('Whoops!');
    });

    test('İletişim sayfası yükleniyor', async ({ page }) => {
        await page.goto(`${BASE}/iletisim`);
        await expect(page.locator('body')).not.toContainText('Whoops!');
    });

    test('Blog sayfası yükleniyor', async ({ page }) => {
        await page.goto(`${BASE}/blog`);
        await expect(page.locator('body')).not.toContainText('Whoops!');
    });

    test('Referanslar sayfası yükleniyor', async ({ page }) => {
        await page.goto(`${BASE}/referanslar`);
        await expect(page.locator('body')).not.toContainText('Whoops!');
    });

    test('Giriş sayfası yükleniyor', async ({ page }) => {
        await page.goto(`${BASE}/giris`);
        await expect(page.locator('body')).not.toContainText('Whoops!');
        await expect(page.locator('input[type="email"], input[name="email"]')).toBeVisible();
        await expect(page.locator('input[type="password"]')).toBeVisible();
    });

    test('Kayıt sayfası yükleniyor', async ({ page }) => {
        await page.goto(`${BASE}/kayit`);
        await expect(page.locator('body')).not.toContainText('Whoops!');
    });

    test('404 sayfası düzgün görünüyor', async ({ page }) => {
        const response = await page.goto(`${BASE}/olmayan-bir-sayfa-xyz`);
        await expect(page.locator('body')).not.toContainText('Whoops!');
    });

    test('Navigasyon linkleri kırık değil', async ({ page }) => {
        await page.goto(BASE);
        const links = await page.locator('nav a, header a').all();
        const hrefs = [];
        for (const link of links) {
            const href = await link.getAttribute('href');
            if (href && href.startsWith('http') && href.includes('localhost')) {
                hrefs.push(href);
            }
        }
        for (const href of hrefs.slice(0, 10)) {
            const res = await page.goto(href);
            expect(res.status()).not.toBe(500);
        }
    });

});
