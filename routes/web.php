<?php
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\SayfaController;
use App\Http\Controllers\BlogController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\SepetController;
use App\Http\Controllers\FaturaController;
use App\Http\Controllers\DestekController;
use App\Http\Controllers\HostingController;
use App\Http\Controllers\StaticFileController;

// Statik dosyaları serve et (CSS, JS, images, fonts) - .htaccess çalışmadığında geçici çözüm
// Bu route EN ÜSTTE olmalı - diğer route'lardan önce
// Sadece statik dosya uzantıları için çalışır
Route::get('yonetim/{path}', [StaticFileController::class, 'serve'])
    ->where('path', '.*\.(css|js|jpg|jpeg|png|gif|ico|svg|webp|woff|woff2|ttf|eot|map|json)$')
    ->name('static.serve');

// Dil ve Para Birimi Değiştirme (middleware dışında)
// Dil middleware'ini tüm route'lara uygula
Route::middleware('web')->group(function () {
    // Dil ve para birimi değiştirme
    Route::get('/change-language/{lang}', function ($lang) {
        $map = [
            'tr' => 1,
            'en' => 2,
            'ar' => 3,
        ];

        if (isset($map[$lang])) {
            session([
                'locale' => $lang,
                'k_dil' => $map[$lang],
            ]);
            app()->setLocale($lang);
        }

        $previousUrl = url()->previous();
        $fallbackUrl = route('anasayfa');

        if (!$previousUrl || str_contains($previousUrl, '/change-language/')) {
            return redirect()->to($fallbackUrl . '?lang=' . $lang);
        }

        $parsed = parse_url($previousUrl);
        $query = [];

        if (!empty($parsed['query'])) {
            parse_str($parsed['query'], $query);
        }

        $query['lang'] = $lang;
        $newQuery = http_build_query($query);

        $scheme = $parsed['scheme'] ?? request()->getScheme();
        $host = $parsed['host'] ?? request()->getHost();
        $port = isset($parsed['port']) ? ':' . $parsed['port'] : '';
        $path = $parsed['path'] ?? '';
        $fragment = isset($parsed['fragment']) ? '#' . $parsed['fragment'] : '';

        $updatedUrl = "{$scheme}://{$host}{$port}{$path}";
        if ($newQuery) {
            $updatedUrl .= '?' . $newQuery;
        }
        $updatedUrl .= $fragment;

        return redirect()->to($updatedUrl);
    })->name('change.language');

    Route::get('/change-currency/{currency}', function ($currency) {
        \App\Helpers\CurrencyHelper::setCurrency($currency);
        return redirect()->back();
    })->name('change.currency');

    // Domain Sorgulama (API)
    Route::post('/domain/sorgula', [\App\Http\Controllers\DomainSearchController::class, 'sorgula'])->name('domain.sorgula');
    Route::post('/sepet/domain-ekle', [\App\Http\Controllers\DomainSearchController::class, 'sepeteEkle'])->name('sepet.domain.ekle');


// Ana Sayfa
Route::get('/', [HomeController::class, 'index'])->name('anasayfa');
    // Sayfalar
    Route::get('/sayfa/{seo}', [SayfaController::class, 'show'])->name('sayfa.detay');
    // Public paket teklif görüntüleme (login gerektirmez, token ile) — eski linkler için korunuyor
    Route::get('/teklif-detay/{token}', [\App\Http\Controllers\Admin\PaketTeklifController::class, 'goster'])->name('teklif.detay.public');
    // Public paket teklif — temiz SEO link (slug ile). /detay/{seo} route'undan ÖNCE tanımlı olmalı.
    Route::get('/detay/teklif/{slug}', [\App\Http\Controllers\Admin\PaketTeklifController::class, 'gosterSlug'])->name('teklif.detay.slug');

    // Odeme Bildirim Formu (public)
    Route::get('/odeme-bildirim-formu', [\App\Http\Controllers\Admin\OdemeBildirimController::class, 'formGoster'])->name('odeme.bildirim.formu');
    Route::post('/odeme-bildirim-formu', [\App\Http\Controllers\Admin\OdemeBildirimController::class, 'formGonder'])->name('odeme.bildirim.formu.post');

    // Blog
    Route::get('/blog', [BlogController::class, 'index'])->name('blog');
    Route::get('/blog/{seo}', [BlogController::class, 'show'])->name('blog.detay');
    Route::get('/blog/kategori/{kategori}', [BlogController::class, 'kategori'])->name('blog.kategori');

    // Hizmetler
    Route::get('/hizmet/{seo}', [HomeController::class, 'hizmet'])->name('hizmet.detay');

    // Referanslar
    Route::get('/referanslar', [HomeController::class, 'referanslar'])->name('referanslar');
    Route::get('/referans/{seo}', [HomeController::class, 'referansDetay'])->name('referans.detay');

    // Hosting & Paketler
    Route::get('/hosting', [HomeController::class, 'hosting'])->name('hosting');
    Route::get('/hosting/{seo}', [HomeController::class, 'hosting'])->name('hosting.detay');
    Route::get('/paketler', [HomeController::class, 'paketler'])->name('paketler');
    Route::get('/paketler/{kategori}', [HomeController::class, 'paketler'])->name('paketler.kategori');
    // Infinite scroll endpoint for paketler page
    Route::get('/paketler/load', [HomeController::class, 'paketlerLoad'])->name('paketler.load');
    Route::get('/detay/{seo}', [HomeController::class, 'paketDetay'])->name('paket.detay');

    // Domain
    Route::get('/domain-tescil', [HomeController::class, 'domainTescil'])->name('domain.tescil');

    // Marka Tescil (hizmet sayfası)
    Route::get('/marka-tescil', [HomeController::class, 'markaTescil'])->name('marka.tescil');

    // Fırsatlar
    Route::get('/firsatlar', [HomeController::class, 'firsatlar'])->name('firsatlar');
    Route::get('/firsat/{seo}', [HomeController::class, 'firsatDetay'])->name('firsat.detay');
    Route::get('/alanadi-satinal/{seo?}', [HomeController::class, 'alanadiSatinal'])->name('alanadi.satinal');

    // İletişim
    Route::get('/iletisim', [HomeController::class, 'iletisim'])->name('iletisim');
    Route::post('/iletisim', [HomeController::class, 'iletisimPost'])->name('iletisim.post');

    // Ar-Ge Anketi (Rubito) — ornek.com/arge-anketi'nin native hâli
    Route::get('/arge-anketi', [\App\Http\Controllers\ArgeAnketiController::class, 'goster'])->name('arge.anketi');
    Route::post('/arge-anketi', [\App\Http\Controllers\ArgeAnketiController::class, 'kaydet'])->name('arge.anketi.kaydet');

    // İş Başvurusu (Rubito) — ornek.com/is-basvurusu'nun native hâli
    Route::get('/is-basvurusu', [\App\Http\Controllers\IsBasvuruController::class, 'goster'])->name('is.basvurusu');
    Route::post('/is-basvurusu', [\App\Http\Controllers\IsBasvuruController::class, 'kaydet'])->name('is.basvurusu.kaydet');

    // İş Ortağı (Bayi) Başvurusu — halka açık, giriş gerektirmez, ŞİFRE ALINMAZ.
    // Hesap yalnızca admin onayında açılır; şifre otomatik üretilip e-posta ile gönderilir.
    Route::get('/is-ortagi-basvuru', [\App\Http\Controllers\IsOrtagiBasvuruController::class, 'goster'])->name('isortagi.basvuru');
    Route::post('/is-ortagi-basvuru', [\App\Http\Controllers\IsOrtagiBasvuruController::class, 'kaydet'])
        ->middleware('throttle:5,10')->name('isortagi.basvuru.kaydet');

    // Referans Detay (kaldırıldı - zaten yukarıda tanımlı, satır 97)

    // Referans Link Takibi
    Route::get('/ref/{ref}', [\App\Http\Controllers\ReferansController::class, 'tikla'])->name('referans.tikla');

    // Auth Routes
    Route::get('/giris', [AuthController::class, 'giris'])->name('giris');
    Route::post('/giris', [AuthController::class, 'girisPost'])->middleware('throttle:5,1')->name('giris.post');
    Route::get('/kayit', [AuthController::class, 'kayit'])->name('kayit');
    Route::post('/kayit', [AuthController::class, 'kayitPost'])->middleware('throttle:5,10')->name('kayit.post');
    // Kod tabanlı e-posta doğrulama (kayıt spam önlemi)
    Route::get('/kayit/dogrula', [AuthController::class, 'kayitDogrulaGoster'])->name('kayit.dogrula');
    Route::post('/kayit/dogrula', [AuthController::class, 'kayitDogrula'])->middleware('throttle:10,10')->name('kayit.dogrula.post');
    Route::post('/kayit/dogrula/tekrar', [AuthController::class, 'kayitKodTekrar'])->middleware('throttle:3,5')->name('kayit.dogrula.tekrar');
    Route::match(['get', 'post'], '/cikis', [AuthController::class, 'cikis'])->name('cikis');

    // E-posta doğrulama
    Route::get('/email-onayla/{id}/{hash}', [AuthController::class, 'emailVerify'])
        ->middleware('signed')
        ->name('email.verify');
    Route::post('/email-onayla/tekrar-gonder', [AuthController::class, 'emailVerifyResend'])
        ->middleware('throttle:3,1')
        ->name('email.verify.resend');

    /*
     * BÜLTEN ABONELİKTEN ÇIKMA (12.08.2026)
     * Gmail/Outlook toplu gönderimde List-Unsubscribe başlığını zorunlu
     * tutuyor; bu başlık bu adresleri gösterir. Giriş GEREKTİRMEZ (kullanıcı
     * mailden tek tıkla çıkabilmeli), güvenlik imzalı URL ile sağlanır.
     * POST ayrıca Gmail'in "tek tıkla abonelikten çık" isteğini karşılar,
     * bu yüzden CSRF'den muaf tutulmalıdır (bkz. bootstrap/app.php).
     */
    Route::get('/bulten-cik/{email}', [\App\Http\Controllers\BultenAbonelikController::class, 'goster'])
        ->name('bulten.cik');
    Route::post('/bulten-cik/{email}', [\App\Http\Controllers\BultenAbonelikController::class, 'cik'])
        ->name('bulten.cik.onayla');

    // Şifre Sıfırlama
    Route::get('/sifre-sifirlama', [AuthController::class, 'sifreSifirlama'])->name('sifre.sifirlama');
    Route::post('/sifre-sifirlama/kod-gonder', [AuthController::class, 'sifreSifirlamaKodGonder'])->middleware('throttle:5,10')->name('sifre.sifirlama.kod.gonder');
    Route::get('/sifre-sifirlama/kod-dogrula', [AuthController::class, 'sifreSifirlamaKodDogrula'])->name('sifre.sifirlama.kod.dogrula');
    Route::post('/sifre-sifirlama/kod-dogrula', [AuthController::class, 'sifreSifirlamaKodDogrulaPost'])->middleware('throttle:10,10')->name('sifre.sifirlama.kod.dogrula.post');
    Route::get('/sifre-sifirlama/tekrar-gonder', [AuthController::class, 'sifreSifirlamaTekrarGonder'])->middleware('throttle:3,1')->name('sifre.sifirlama.tekrar');

    // Email Doğrulama
    Route::get('/email-dogrula', [\App\Http\Controllers\Auth\EmailVerificationController::class, 'notice'])->name('verification.notice');
    Route::get('/email-dogrula/{id}/{hash}', [\App\Http\Controllers\Auth\EmailVerificationController::class, 'verify'])->name('verification.verify');
    Route::post('/email-dogrula/tekrar-gonder', [\App\Http\Controllers\Auth\EmailVerificationController::class, 'resend'])->middleware('throttle:3,10')->name('verification.resend');

    // Üye Paneli (Auth Required)
    // Randevu değerlendirme (SMS/mail linki — giriş gerektirmez, token korumalı)
    Route::get('/randevu-degerlendirme/{token}', [\App\Http\Controllers\RandevuDegerlendirmeController::class, 'goster'])->name('randevu.degerlendirme');
    Route::post('/randevu-degerlendirme/{token}', [\App\Http\Controllers\RandevuDegerlendirmeController::class, 'kaydet'])->name('randevu.degerlendirme.kaydet');

    Route::middleware(['uye.auth'])->group(function () {
        Route::get('/hesabim', [AuthController::class, 'hesabim'])->name('hesabim');
        Route::get('/bilgilerim', [AuthController::class, 'bilgilerim'])->name('bilgilerim');
        Route::post('/bilgilerim', [AuthController::class, 'bilgilerimPost'])->name('bilgilerim.post');
        Route::post('/sifre-degistir/kod-gonder', [AuthController::class, 'sifreDegistirKodGonder'])->middleware('throttle:5,10')->name('sifre.degistir.kod.gonder');
        Route::get('/tekliflerim', [AuthController::class, 'tekliflerim'])->name('tekliflerim');
        // Teklif onay/red (29 Mayıs 2026)
        Route::post('/tekliflerim/{prefixedId}/onayla', [AuthController::class, 'teklifOnayla'])->name('teklif.onayla');
        Route::post('/tekliflerim/{prefixedId}/reddet', [AuthController::class, 'teklifReddet'])->name('teklif.reddet');

        // Müşteri Bildirimleri
        Route::get('/bildirimlerim', [\App\Http\Controllers\UyeBildirimController::class, 'index'])->name('bildirimlerim');
        Route::get('/bildirimlerim/sayim', [\App\Http\Controllers\UyeBildirimController::class, 'sayim'])->name('bildirimlerim.sayim');
        Route::post('/bildirim/{id}/okundu', [\App\Http\Controllers\UyeBildirimController::class, 'okundu'])->name('bildirim.okundu');
        Route::post('/bildirimler/hepsini-oku', [\App\Http\Controllers\UyeBildirimController::class, 'hepsiniOku'])->name('bildirimler.hepsini.oku');

        // Randevularım (üye paneli — randevu modülü)
        Route::get('/randevularim', [\App\Http\Controllers\UyeRandevuController::class, 'index'])->name('randevularim');
        Route::get('/gorevlerim', [\App\Http\Controllers\UyeGorevController::class, 'index'])->name('gorevlerim');
        Route::delete('/bildirim/{id}', [\App\Http\Controllers\UyeBildirimController::class, 'sil'])->name('bildirim.sil');
        Route::delete('/bildirimler/hepsini-sil', [\App\Http\Controllers\UyeBildirimController::class, 'hepsiniSil'])->name('bildirimler.hepsini.sil');

        // Destek
        Route::get('/destek-taleplerim', [DestekController::class, 'index'])->name('destek.taleplerim');
        Route::get('/destek-talebi-olustur', [DestekController::class, 'olustur'])->name('destek.talebi.olustur');
        Route::post('/destek-talebi-olustur', [DestekController::class, 'olusturPost'])->name('destek.olustur.post');
        Route::get('/destek/{id}', [DestekController::class, 'detay'])->name('destek.detay');
        Route::post('/destek/{id}/cevapla', [DestekController::class, 'cevapla'])->name('destek.cevapla');
        // AJAX POLLING: Anlık mesajlaşma (29 Mayıs 2026)
        Route::get('/destek/{id}/yeni-mesajlar', [DestekController::class, 'yeniMesajlar'])->name('destek.yeni-mesajlar');
        Route::post('/destek/{id}/cevapla-ajax', [DestekController::class, 'cevaplaAjax'])->name('destek.cevapla.ajax');
        Route::post('/destek/{id}/kapat', [DestekController::class, 'kapat'])->name('destek.kapat');

        // Müşteri ↔ yönetim DM (havuz)
        Route::get('/mesajlarim', [\App\Http\Controllers\MusteriDmController::class, 'index'])->name('mesajlarim');
        Route::get('/mesajlarim/konusma', [\App\Http\Controllers\MusteriDmController::class, 'konusma'])->name('musteri-dm.konusma');
        Route::get('/mesajlarim/okunmamis', [\App\Http\Controllers\MusteriDmController::class, 'okunmamis'])->name('musteri-dm.okunmamis');
        Route::post('/mesajlarim/gonder', [\App\Http\Controllers\MusteriDmController::class, 'gonder'])->name('musteri-dm.gonder');
        Route::post('/mesajlarim/mesaj/{mid}/sil', [\App\Http\Controllers\MusteriDmController::class, 'mesajSil'])->whereNumber('mid')->name('musteri-dm.mesaj.sil');
        Route::post('/mesajlarim/mesaj/{mid}/duzenle', [\App\Http\Controllers\MusteriDmController::class, 'mesajDuzenle'])->whereNumber('mid')->name('musteri-dm.mesaj.duzenle');


        // Hosting & Hizmetler
        Route::get('/hostinglerim', [HostingController::class, 'index'])->name('hostinglerim');
        Route::get('/hosting/{id}/detay', [HostingController::class, 'detay'])->name('uye.hosting.detay');
        Route::get('/hosting/{id}/yenile', [HostingController::class, 'yenile'])->name('hosting.yenile');
        Route::get('/web-paketlerim', [HomeController::class, 'webPaketlerim'])->name('web.paketlerim');
        Route::get('/alan-adlarim', [HomeController::class, 'alanAdlarim'])->name('alan.adlarim');
        // Birleşik hizmet listesi (web paketi + hosting + domain) — panel "Hizmetlerim" kartı buraya bağlı
        Route::get('/aldigim-hizmetler', [HomeController::class, 'aldigimHizmetler'])->name('aldigim.hizmetler');

        // Müşteri panel — Özel Tekliflerim (CRM tarafı) — 29 Mayıs 2026
        Route::get('/hesabim/ozel-tekliflerim',          [\App\Http\Controllers\CrmTeklifController::class, 'index'])->name('uye.crm.tekliflerim');
        Route::get('/hesabim/ozel-teklif/{id}',          [\App\Http\Controllers\CrmTeklifController::class, 'detay'])->name('uye.crm.teklif.detay');
        Route::post('/hesabim/ozel-teklif/{id}/kabul',   [\App\Http\Controllers\CrmTeklifController::class, 'kabul'])->name('uye.crm.teklif.kabul');
        Route::post('/hesabim/ozel-teklif/{id}/red',     [\App\Http\Controllers\CrmTeklifController::class, 'red'])->name('uye.crm.teklif.red');
        Route::post('/hesabim/ozel-teklif/{id}/odedim',  [\App\Http\Controllers\CrmTeklifController::class, 'odedim'])->name('uye.crm.teklif.odedim');

        // Faturalar
        Route::get('/faturalarim', [FaturaController::class, 'index'])->name('faturalarim');
        Route::get('/fatura/{id}/detay', [FaturaController::class, 'detay'])->name('fatura.detay');
        Route::get('/fatura/{id}/ode', [FaturaController::class, 'ode'])->name('fatura.ode');
        Route::post('/fatura/{id}/ode', [FaturaController::class, 'odemeBaslat'])->name('fatura.ode.baslat');
        Route::get('/fatura/{id}/indir', [FaturaController::class, 'indir'])->name('fatura.indir');

        // Paket değerlendirme (puan + yorum) — üye girişi gerekli
        Route::post('/yorum-ekle', [\App\Http\Controllers\YorumController::class, 'ekle'])->name('yorum.ekle');

        // Sepet
        Route::get('/sepet', [SepetController::class, 'index'])->name('sepet');
        Route::post('/sepet/ekle', [SepetController::class, 'ekle'])->name('sepet.ekle');
        Route::delete('/sepet/{id}/sil', [SepetController::class, 'sil'])->name('sepet.sil');
        Route::put('/sepet/{id}/guncelle', [SepetController::class, 'guncelle'])->name('sepet.guncelle');
        Route::delete('/sepet/temizle', [SepetController::class, 'temizle'])->name('sepet.temizle');
        Route::match(['get', 'post'], '/sepet/onayla', [SepetController::class, 'onayla'])->name('sepet.onayla');
        Route::post('/sepet/bakiye-odeme', [SepetController::class, 'bakiyeOdeme'])->name('sepet.bakiye.odeme');
        // DN Bank coin ile ödeme + müşteri DN Bank paneli
        Route::post('/sepet/dnbank-odeme', [SepetController::class, 'dnbankOdeme'])->name('sepet.dnbank.odeme');
        Route::get('/dnbank', [\App\Http\Controllers\DnBankController::class, 'index'])->name('dnbank');
        Route::get('/kredilerim', [\App\Http\Controllers\DnBankController::class, 'index'])->name('kredilerim');
        Route::post('/dnbank/kredi/{krediId}/onayla', [\App\Http\Controllers\DnBankController::class, 'krediOnayla'])->name('dnbank.kredi.onayla')->where('krediId', '[0-9]+');
        Route::post('/dnbank/kredi/{krediId}/reddet', [\App\Http\Controllers\DnBankController::class, 'krediReddet'])->name('dnbank.kredi.reddet')->where('krediId', '[0-9]+');
        Route::post('/dnbank/kredi-talep', [\App\Http\Controllers\DnBankController::class, 'krediTalepEt'])->name('dnbank.kredi.talep');
        Route::post('/sepet/kupon-uygula', [SepetController::class, 'kuponUygula'])->name('sepet.kupon.uygula');
        Route::post('/sepet/kupon-kaldir', [SepetController::class, 'kuponKaldir'])->name('sepet.kupon.kaldir');

        // Bayi Kodu
        Route::post('/sepet/bayi-kodu-uygula', [SepetController::class, 'bayiKoduUygula'])->name('sepet.bayi.uygula');
        Route::post('/sepet/bayi-kodu-kaldir', [SepetController::class, 'bayiKoduKaldir'])->name('sepet.bayi.kaldir');

        // Satın Alma
        Route::get('/hosting-satinal/{id}', [HomeController::class, 'hostingSatinal'])->name('hosting.satinal');
        Route::get('/web-paket-satinal/{id}', [HomeController::class, 'webPaketSatinal'])->name('web.paket.satinal');
        Route::get('/hizmet-satinal/{id}', [HomeController::class, 'hizmetSatinal'])->name('hizmet.satinal');

        // Diğer
        Route::get('/bakiyem', [HomeController::class, 'bakiyem'])->name('bakiyem');
        Route::post('/bakiye/yukle', [HomeController::class, 'bakiyeYukle'])->name('bakiye.yukle');
        Route::get('/bakiye/yukleme/sonuc', [HomeController::class, 'bakiyeYuklemeSonuc'])->name('bakiye.yukleme.sonuc');
        Route::get('/dosyalarim', [HomeController::class, 'dosyalarim'])->name('dosyalarim');
        Route::get('/favorilerim', [HomeController::class, 'favorilerim'])->name('favorilerim');

        // Müşteri özel sayfalar (hizmetler, sözleşmeler, raporlar, e-faturalar, referanslar)
        Route::get('/hizmetlerim',    [HomeController::class, 'hizmetlerim'])->name('hizmetlerim');
        Route::get('/sozlesmelerim',  [HomeController::class, 'sozlesmelerim'])->name('sozlesmelerim');
        Route::get('/raporlarim',     [HomeController::class, 'raporlarim'])->name('raporlarim');
        // Rapor müşteri detay + indirme (29 Mayıs 2026 fix)
        Route::get('/raporlarim/{id}', [\App\Http\Controllers\MusteriRaporController::class, 'detay'])->name('rapor.detay');
        Route::get('/rapor/{id}/indir', [\App\Http\Controllers\MusteriRaporController::class, 'indir'])->name('rapor.indir');
        Route::get('/efaturalarim',   [HomeController::class, 'efaturalarim'])->name('efaturalarim');
        Route::get('/referanslarim',  [HomeController::class, 'referanslarim'])->name('referanslarim');

        // Bayi Basvurusu
        Route::get('/bayi-basvuru', [\App\Http\Controllers\BayiBasvuruController::class, 'form'])->name('bayi.basvuru');
        Route::post('/bayi-basvuru', [\App\Http\Controllers\BayiBasvuruController::class, 'basvur'])->name('bayi.basvuru.post');

        // Müşteri teklif ödeme linki (bayi + özel paket)
        Route::get('/teklif/{token}', [\App\Http\Controllers\BayiOdemeController::class, 'show'])->name('bayi.odeme.show');
        Route::post('/teklif/{token}/ode', [\App\Http\Controllers\BayiOdemeController::class, 'markPaid'])->name('bayi.odeme.post');
        Route::get('/teklif/{token}/tesekkur', [\App\Http\Controllers\BayiOdemeController::class, 'tesekkur'])->name('bayi.odeme.tesekkur');
    Route::post('/teklif/{token}/kabul', [\App\Http\Controllers\BayiOdemeController::class, 'kabul'])->name('bayi.odeme.kabul');
    Route::post('/teklif/{token}/red',   [\App\Http\Controllers\BayiOdemeController::class, 'red'])->name('bayi.odeme.red');
    });


    // Ödeme Routes
    Route::post('/odeme/paytr', [HomeController::class, 'paytrOdeme'])->name('odeme.paytr');
    Route::post('/odeme/iyzico', [HomeController::class, 'iyzicoOdeme'])->name('odeme.iyzico');
    Route::get('/siparis-sonuc', [HomeController::class, 'siparisSonuc'])->name('siparis.sonuc');

    // Domain Sorgulama ve Satın Alma
    Route::get('/domain/check', [\App\Http\Controllers\DomainOrderController::class, 'check'])->name('domain.check');
    Route::post('/domain/sepete-ekle', [\App\Http\Controllers\DomainOrderController::class, 'sepeteEkle'])->name('domain.sepete.ekle');
    Route::post('/domain/odeme-basarili', [\App\Http\Controllers\DomainOrderController::class, 'paymentSuccess'])->name('domain.payment.success');
    Route::get('/domain/durum/{order_id}', [\App\Http\Controllers\DomainOrderController::class, 'checkStatus'])->name('domain.status');
    Route::post('/domain/satin-al', [\App\Http\Controllers\DomainPurchaseController::class, 'satinAl'])->name('domain.satin.al');

    // Ödeme Webhook'ları
    Route::post('/payment/paytr/callback', [\App\Http\Controllers\PaymentWebhookController::class, 'paytrCallback'])->name('payment.paytr.callback');
    Route::post('/payment/iyzico/callback', [\App\Http\Controllers\PaymentWebhookController::class, 'iyzicoCallback'])->name('payment.iyzico.callback');

    // Sitemap
    Route::get('/sitemap.xml', [HomeController::class, 'sitemap'])->name('sitemap');

    // 404
    Route::fallback(function () {
        return view('404');
    });
});

// ========================
// ADMIN: Paket Teklifleri
// ========================
Route::prefix('admin')->name('admin.')->group(function () {
    Route::middleware(['admin.auth', 'rol'])->group(function () {
        Route::get('/paketler/teklif-olustur', [\App\Http\Controllers\Admin\PaketTeklifController::class, 'create'])->name('paketler.teklif.create');
        Route::post('/paketler/teklif-olustur', [\App\Http\Controllers\Admin\PaketTeklifController::class, 'store'])->name('paketler.teklif.store');
        Route::get('/paketler/teklif/{id}/duzenle', [\App\Http\Controllers\Admin\PaketTeklifController::class, 'duzenle'])->name('paketler.teklif.duzenle');
        Route::post('/paketler/teklif/{id}/duzenle', [\App\Http\Controllers\Admin\PaketTeklifController::class, 'guncelle'])->name('paketler.teklif.guncelle');
        Route::get('/paketler/teklif/{id}', [\App\Http\Controllers\Admin\PaketTeklifController::class, 'goruntule'])->name('paketler.teklif.goruntule');
        Route::delete('/paketler/teklif/{id}', [\App\Http\Controllers\Admin\PaketTeklifController::class, 'sil'])->name('paketler.teklif.sil');
    });
});

// ========================
// ADMIN PANEL ROUTES
// ========================
Route::prefix('admin')->name('admin.')->group(function () {
    // Admin Giriş (middleware dışında)
    Route::get('/giris', [\App\Http\Controllers\Admin\AdminAuthController::class, 'giris'])->name('giris');

    // Admin Sifre Sifirlama (middleware disinda)
    Route::get('/sifre-sifirlama', [\App\Http\Controllers\Admin\AdminAuthController::class, 'sifreSifirlama'])->name('sifre.sifirlama');
    Route::post('/sifre-sifirlama/kod-gonder', [\App\Http\Controllers\Admin\AdminAuthController::class, 'sifreSifirlamaKodGonder'])->middleware('throttle:5,1')->name('sifre.sifirlama.kod.gonder');
    Route::get('/sifre-sifirlama/kod-dogrula', [\App\Http\Controllers\Admin\AdminAuthController::class, 'sifreSifirlamaKodDogrula'])->name('sifre.sifirlama.kod.dogrula');
    Route::get('/sifre-sifirlama/tekrar-gonder', [\App\Http\Controllers\Admin\AdminAuthController::class, 'sifreSifirlamaTekrarGonder'])->middleware('throttle:3,1')->name('sifre.sifirlama.tekrar');
    Route::post('/sifre-sifirlama/kod-dogrula', [\App\Http\Controllers\Admin\AdminAuthController::class, 'sifreSifirlamaKodDogrulaPost'])->middleware('throttle:5,1')->name('sifre.sifirlama.kod.dogrula.post');
    Route::get('/login', [\App\Http\Controllers\Admin\AdminAuthController::class, 'giris'])->name('login'); // Alternatif route
    Route::post('/giris', [\App\Http\Controllers\Admin\AdminAuthController::class, 'girisPost'])->middleware('throttle:5,1')->name('giris.post');
    Route::post('/login', [\App\Http\Controllers\Admin\AdminAuthController::class, 'girisPost'])->middleware('throttle:5,1')->name('login.post'); // Alternatif route
    Route::get('/cikis', [\App\Http\Controllers\Admin\AdminAuthController::class, 'cikis'])->name('cikis');

    // /admin redirect - eğer giriş yapılmışsa dashboard'a, değilse login'e
    Route::get('/', function() {
        if (session()->has('admin_logged_in') && session('admin_logged_in')) {
            return redirect()->route('admin.dashboard');
        }
        return redirect()->route('admin.giris');
    })->name('index');

    // Admin Panel (Auth Required)
    Route::middleware(['admin.auth', 'rol'])->group(function () {
        // Dashboard
        Route::get('/dashboard', [\App\Http\Controllers\Admin\DashboardController::class, 'index'])->name('dashboard');
        Route::get('/', function() {
            return redirect()->route('admin.dashboard');
        })->name('home.redirect');

        // İşlem Geçmişi + Geri-Al (Faz 0 — AI asistanı altyapısı)
        Route::get('/islem-gecmisi', [\App\Http\Controllers\Admin\IslemGecmisiController::class, 'index'])->name('islem-gecmisi.index');
        Route::post('/islem-gecmisi/{id}/geri-al', [\App\Http\Controllers\Admin\IslemGecmisiController::class, 'geriAl'])->name('islem-gecmisi.geri-al');

        // AI Asistan (Faz 1a — sadece OKUMA; API anahtarı yoksa "yakında" modunda açılır)
        Route::get('/ai-asistan', [\App\Http\Controllers\Admin\AiAsistanController::class, 'index'])->name('ai-asistan.index');
        // NOT (11.08.2026): 'ai-asistan.sohbet' ve 'ai-asistan.temizle' route'ları
        // kaldırıldı — AiAsistanController'da bu metotlar hiç yazılmamıştı
        // (sayfa "yakında" modunda, backend Claude API anahtarı bekliyor) ve
        // arayüz de onları çağırmıyordu. Tetiklenselerdi 500 verirlerdi.
        // Backend yazıldığında bu iki satır geri eklenecek.

        // Profil Ayarları
        Route::get('/profil', [\App\Http\Controllers\Admin\ProfilController::class, 'index'])->name('profil');
        Route::post('/profil/guncelle', [\App\Http\Controllers\Admin\ProfilController::class, 'guncelle'])->name('profil.guncelle');
        Route::post('/profil/sifre-degistir', [\App\Http\Controllers\Admin\ProfilController::class, 'sifreDegistir'])->name('profil.sifre.degistir');

        // Sayfalar
        Route::get('/sayfalar', [\App\Http\Controllers\Admin\SayfalarController::class, 'index'])->name('sayfalar.index');
        Route::get('/sayfalar/ekle', [\App\Http\Controllers\Admin\SayfalarController::class, 'ekle'])->name('sayfalar.ekle');
        Route::post('/sayfalar/ekle', [\App\Http\Controllers\Admin\SayfalarController::class, 'eklePost'])->name('sayfalar.eklePost');
        Route::get('/sayfalar/{id}/duzenle', [\App\Http\Controllers\Admin\SayfalarController::class, 'duzenle'])->name('sayfalar.duzenle');
        Route::post('/sayfalar/{id}/duzenle', [\App\Http\Controllers\Admin\SayfalarController::class, 'duzenlePost'])->name('sayfalar.duzenlePost');
        Route::post('/sayfalar/{id}/builder', [\App\Http\Controllers\Admin\SayfalarController::class, 'saveBuilder'])->name('sayfalar.builder.save');
        Route::delete('/sayfalar/{id}', [\App\Http\Controllers\Admin\SayfalarController::class, 'sil'])->name('sayfalar.sil');

        // Domain Siparişleri
        Route::get('/domain-orders', [\App\Http\Controllers\Admin\DomainOrderController::class, 'index'])->name('domain-orders.index');
        Route::get('/domain-orders/{id}', [\App\Http\Controllers\Admin\DomainOrderController::class, 'show'])->name('domain-orders.show');
        Route::post('/domain-orders/{id}/check-status', [\App\Http\Controllers\Admin\DomainOrderController::class, 'checkStatus'])->name('domain-orders.check-status');
        Route::post('/domain-orders/{id}/retry', [\App\Http\Controllers\Admin\DomainOrderController::class, 'retry'])->name('domain-orders.retry');
        Route::post('/domain-orders/{id}/renew', [\App\Http\Controllers\Admin\DomainOrderController::class, 'renew'])->name('domain-orders.renew');

        // Slider
        Route::get('/slider', [\App\Http\Controllers\Admin\SliderController::class, 'index'])->name('slider.index');
        Route::get('/slider/ekle', [\App\Http\Controllers\Admin\SliderController::class, 'ekle'])->name('slider.ekle');
        Route::post('/slider/ekle', [\App\Http\Controllers\Admin\SliderController::class, 'eklePost'])->name('slider.eklePost');
        Route::get('/slider/{id}/duzenle', [\App\Http\Controllers\Admin\SliderController::class, 'duzenle'])->name('slider.duzenle');
        Route::post('/slider/{id}/duzenle', [\App\Http\Controllers\Admin\SliderController::class, 'duzenlePost'])->name('slider.duzenlePost');
        Route::delete('/slider/{id}', [\App\Http\Controllers\Admin\SliderController::class, 'sil'])->name('slider.sil');

        // Paketler
        Route::get('/paketler', [\App\Http\Controllers\Admin\PaketController::class, 'index'])->name('paketler.index');
        Route::get('/paketler/anasayfa', [\App\Http\Controllers\Admin\PaketController::class, 'anasayfaPaketleri'])->name('paketler.anasayfa');
        Route::post('/paketler/anasayfa', [\App\Http\Controllers\Admin\PaketController::class, 'anasayfaPaketleriKaydet'])->name('paketler.anasayfa.kaydet');
        Route::get('/paketler/ozel-teklifler', [\App\Http\Controllers\Admin\PaketController::class, 'ozelTeklifler'])->name('paketler.ozel');

        /* ═══ Kategoriye toplu indirim (kart #236) — {id} kalıplarından ÖNCE gelmeli ═══ */
        Route::get('/paketler/toplu-indirim', [\App\Http\Controllers\Admin\TopluIndirimController::class, 'index'])->name('paketler.toplu-indirim');
        Route::post('/paketler/toplu-indirim/uygula', [\App\Http\Controllers\Admin\TopluIndirimController::class, 'uygula'])->name('paketler.toplu-indirim.uygula');
        Route::post('/paketler/toplu-indirim/kaldir', [\App\Http\Controllers\Admin\TopluIndirimController::class, 'kaldir'])->name('paketler.toplu-indirim.kaldir');
        Route::get('/paketler/ekle', [\App\Http\Controllers\Admin\PaketController::class, 'ekle'])->name('paketler.ekle');
        Route::post('/paketler/ekle', [\App\Http\Controllers\Admin\PaketController::class, 'eklePost'])->name('paketler.eklePost');
        Route::get('/paketler/{id}/duzenle', [\App\Http\Controllers\Admin\PaketController::class, 'duzenle'])->name('paketler.duzenle');
        Route::post('/paketler/{id}/duzenle', [\App\Http\Controllers\Admin\PaketController::class, 'duzenlePost'])->name('paketler.duzenlePost');
        Route::delete('/paketler/{id}', [\App\Http\Controllers\Admin\PaketController::class, 'sil'])->name('paketler.sil');
        Route::post('/paketler/{id}/sepet-oner-toggle', [\App\Http\Controllers\Admin\PaketController::class, 'sepetOnerToggle'])->name('paketler.sepet-oner-toggle')->where('id', '[0-9]+');
        Route::post('/paketler/upload-image', [\App\Http\Controllers\Admin\PaketController::class, 'uploadImage'])->name('paketler.uploadImage');

        Route::post('/paketler/{id}/resim-sil', function($id) {
            try {
                $paket = DB::table('yazilimlar')->where('id', $id)->first();

                if (!$paket) {
                    return response()->json(['success' => false, 'message' => 'Paket bulunamadi'], 404);
                }

                if ($paket->resim) {
                    // Fiziksel dosyayı sil (3 klasörde de ara)
                    $klasorler = ['kapak/', 'ana/', 'kucuk/', ''];
                    foreach ($klasorler as $klasor) {
                        $dosyaYolu = public_path('tema/uploads/webpaketleri/' . $klasor . $paket->resim);
                        if (file_exists($dosyaYolu)) {
                            @unlink($dosyaYolu);
                        }
                    }
                }

                // Veritabanından sil
                DB::table('yazilimlar')->where('id', $id)->update(['resim' => null]);

                return response()->json(['success' => true, 'message' => 'Resim basariyla silindi']);
            } catch (\Exception $e) {
                return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
            }
        })->name('paketler.resim-sil');

        Route::delete('/paketler/icerik-resim-sil/{id}', [\App\Http\Controllers\Admin\PaketController::class, 'icerikResimSil'])->name('paketler.icerik-resim-sil');

        // Web Kategoriler
        Route::get('/kategoriler', [\App\Http\Controllers\Admin\KategoriController::class, 'index'])->name('kategoriler.index');
        Route::get('/kategoriler/ekle', [\App\Http\Controllers\Admin\KategoriController::class, 'ekle'])->name('kategoriler.ekle');
        Route::post('/kategoriler/ekle', [\App\Http\Controllers\Admin\KategoriController::class, 'eklePost'])->name('kategoriler.eklePost');
        Route::get('/kategoriler/{id}/duzenle', [\App\Http\Controllers\Admin\KategoriController::class, 'duzenle'])->name('kategoriler.duzenle');
        Route::post('/kategoriler/{id}/duzenle', [\App\Http\Controllers\Admin\KategoriController::class, 'duzenlePost'])->name('kategoriler.duzenlePost');
        Route::delete('/kategoriler/{id}', [\App\Http\Controllers\Admin\KategoriController::class, 'sil'])->name('kategoriler.sil');

        Route::post('/paketler/{id}/guncelle', function($id) {
            $validated = request()->validate([
                'adi' => 'required|string|max:255',
                'tutar' => 'required|numeric',
                'resim' => 'nullable|image|mimes:jpeg,jpg,png,webp|max:10240',
                'icerik_resimleri' => 'nullable|array|max:5',
                'icerik_resimleri.*' => 'image|max:10240',
            ]);

            $data = [
                'adi' => $validated['adi'],
                'tutar' => $validated['tutar'],
                'kisa' => request('kisa'),
                'aciklama' => request('aciklama'),
                'ozellik' => request('ozellik'),
                'kategori' => request('kategori'),
                'sira' => request('sira', 0),
                'demo_link' => request('demo_link'),
                'durum' => request('durum', 0),
                'anasayfa' => request('anasayfa', 0),
            ];

            // Kapak resmi yükleme
            if (request()->hasFile('resim')) {
                $resim = request()->file('resim');
                $dosyaAdi = time() . '_' . $id . '.' . $resim->getClientOriginalExtension();

                // Hedef klasör (webpaketleri kökü)
                $hedefKlasor = public_path('tema/uploads/webpaketleri');

                try {
                    if (!is_dir($hedefKlasor)) {
                        mkdir($hedefKlasor, 0775, true);
                    }

                    $resim->move($hedefKlasor, $dosyaAdi);
                    @chmod($hedefKlasor . '/' . $dosyaAdi, 0644);

                    $data['resim'] = $dosyaAdi;
                } catch (\Exception $e) {
                    // hata olursa sessiz geç
                }
            }

            DB::table('yazilimlar')->where('id', $id)->update($data);

            // İçerik görselleri (galeri) - webpaketresim tablosuna kaydet
            if (request()->hasFile('icerik_resimleri')) {
                $galeriDosyalari = request()->file('icerik_resimleri');
                if (!is_array($galeriDosyalari)) {
                    $galeriDosyalari = [$galeriDosyalari];
                }

                $maxGorselAdedi = 5;
                if (count($galeriDosyalari) > $maxGorselAdedi) {
                    $galeriDosyalari = array_slice($galeriDosyalari, 0, $maxGorselAdedi);
                }

                $hedefKlasor = public_path('tema/uploads/webpaketleri');
                if (!is_dir($hedefKlasor)) {
                    @mkdir($hedefKlasor, 0775, true);
                }

                foreach ($galeriDosyalari as $index => $gorsel) {
                    if (!$gorsel->isValid()) {
                        continue;
                    }

                    $dosyaAdi = time() . '_' . $id . '_' . $index . '.' . $gorsel->getClientOriginalExtension();

                    try {
                        $gorsel->move($hedefKlasor, $dosyaAdi);
                        @chmod($hedefKlasor . DIRECTORY_SEPARATOR . $dosyaAdi, 0644);
                    } catch (\Exception $e) {
                        continue;
                    }

                    if (Schema::hasTable('webpaketresim')) {
                        DB::table('webpaketresim')->insert([
                            'rid' => $id,
                            'resim' => $dosyaAdi,
                        ]);
                    }
                }
            }

            return redirect()->route('admin.paketler.index')->with('success', 'Paket basariyla guncellendi');
        })->name('paketler.guncelle');

        // Mesajlar
        Route::get('/mesajlar', [\App\Http\Controllers\Admin\MesajlarController::class, 'index'])->name('mesajlar.index');
        Route::get('/mesajlar/{id}', [\App\Http\Controllers\Admin\MesajlarController::class, 'detay'])->name('mesajlar.detay');
        Route::delete('/mesajlar/{id}', [\App\Http\Controllers\Admin\MesajlarController::class, 'sil'])->name('mesajlar.sil');

        // Kuponlar
        Route::get('/kuponlar', [\App\Http\Controllers\Admin\KuponlarController::class, 'index'])->name('kuponlar.index');
        Route::get('/kuponlar/ekle', [\App\Http\Controllers\Admin\KuponlarController::class, 'ekle'])->name('kuponlar.ekle');
        Route::post('/kuponlar/ekle', [\App\Http\Controllers\Admin\KuponlarController::class, 'eklePost'])->name('kuponlar.eklePost');
        Route::get('/kuponlar/{id}/duzenle', [\App\Http\Controllers\Admin\KuponlarController::class, 'duzenle'])->name('kuponlar.duzenle');
        Route::post('/kuponlar/{id}/duzenle', [\App\Http\Controllers\Admin\KuponlarController::class, 'duzenlePost'])->name('kuponlar.duzenlePost');
        Route::delete('/kuponlar/{id}', [\App\Http\Controllers\Admin\KuponlarController::class, 'sil'])->name('kuponlar.sil');

        // Banka Hesapları
        Route::get('/banka-hesaplari', [\App\Http\Controllers\Admin\BankaController::class, 'index'])->name('banka.index');
        Route::get('/banka-hesaplari/ekle', [\App\Http\Controllers\Admin\BankaController::class, 'ekle'])->name('banka.ekle');
        Route::post('/banka-hesaplari/ekle', [\App\Http\Controllers\Admin\BankaController::class, 'eklePost'])->name('banka.eklePost');
        Route::get('/banka-hesaplari/{id}/duzenle', [\App\Http\Controllers\Admin\BankaController::class, 'duzenle'])->name('banka.duzenle');
        Route::post('/banka-hesaplari/{id}/duzenle', [\App\Http\Controllers\Admin\BankaController::class, 'duzenlePost'])->name('banka.duzenlePost');
        Route::delete('/banka-hesaplari/{id}', [\App\Http\Controllers\Admin\BankaController::class, 'sil'])->name('banka.sil');

        // Yöneticiler
        Route::get('/yoneticiler', [\App\Http\Controllers\Admin\YoneticiController::class, 'index'])->name('yoneticiler.index');
        Route::get('/yoneticiler/ekle', [\App\Http\Controllers\Admin\YoneticiController::class, 'ekle'])->name('yoneticiler.ekle');
        Route::post('/yoneticiler/ekle', [\App\Http\Controllers\Admin\YoneticiController::class, 'eklePost'])->name('yoneticiler.eklePost');
        Route::get('/yoneticiler/{id}/duzenle', [\App\Http\Controllers\Admin\YoneticiController::class, 'duzenle'])->name('yoneticiler.duzenle');
        Route::post('/yoneticiler/{id}/duzenle', [\App\Http\Controllers\Admin\YoneticiController::class, 'duzenlePost'])->name('yoneticiler.duzenlePost');
        Route::delete('/yoneticiler/{id}', [\App\Http\Controllers\Admin\YoneticiController::class, 'sil'])->name('yoneticiler.sil');

        // Roller (dinamik rol & yetki yonetimi)
        Route::get('/roller', [\App\Http\Controllers\Admin\RolController::class, 'index'])->name('roller.index');
        Route::get('/roller/ekle', [\App\Http\Controllers\Admin\RolController::class, 'ekle'])->name('roller.ekle');
        Route::post('/roller/ekle', [\App\Http\Controllers\Admin\RolController::class, 'eklePost'])->name('roller.eklePost');
        Route::get('/roller/{id}/duzenle', [\App\Http\Controllers\Admin\RolController::class, 'duzenle'])->name('roller.duzenle');
        Route::post('/roller/{id}/duzenle', [\App\Http\Controllers\Admin\RolController::class, 'duzenlePost'])->name('roller.duzenlePost');
        Route::delete('/roller/{id}', [\App\Http\Controllers\Admin\RolController::class, 'sil'])->name('roller.sil');

        // Üyeler  (CRM tek yüz — GET ekranları CRM'e yönlenir,
        //          POST/işlemler login+fatura+bakiye için arka planda kalır)
        Route::get('/uyeler', [\App\Http\Controllers\Admin\UyeController::class, 'index'])->name('uyeler.index');
        Route::get('/uyeler/ekle', [\App\Http\Controllers\Admin\UyeController::class, 'ekle'])->name('uyeler.ekle');
        Route::get('/uyeler/{id}/detay', [\App\Http\Controllers\Admin\UyeController::class, 'detay'])->name('uyeler.detay');
        // --- ARKA PLAN İŞLEMLERİ (DOKUNMA — sistem bunları kullanıyor) ---
        Route::post('/uyeler/store', [\App\Http\Controllers\Admin\UyeController::class, 'store'])->name('uyeler.store');
        Route::post('/uyeler/{id}/durum/{durum}', [\App\Http\Controllers\Admin\UyeController::class, 'durumDegistir'])->name('uyeler.durum');
        Route::post('/uyeler/{id}/sifre', [\App\Http\Controllers\Admin\UyeController::class, 'sifreGuncelle'])->name('uyeler.sifre');
        Route::delete('/uyeler/{id}', [\App\Http\Controllers\Admin\UyeController::class, 'sil'])->name('uyeler.sil');
        Route::get('/uyeler/export', [\App\Http\Controllers\Admin\UyeController::class, 'export'])->name('uyeler.export');
        Route::get('/uyeler/export-page', [\App\Http\Controllers\Admin\UyeController::class, 'exportPage'])->name('uyeler.export-page');

        // Bayiler
        Route::get('/bayiler', [\App\Http\Controllers\Admin\BayiController::class, 'index'])->name('bayiler.index');
        Route::get('/bayiler/ekle', [\App\Http\Controllers\Admin\BayiController::class, 'ekle'])->name('bayiler.ekle');
        Route::post('/bayiler/ekle', [\App\Http\Controllers\Admin\BayiController::class, 'eklePost'])->name('bayiler.eklePost');
        Route::get('/bayiler/ayarlar', [\App\Http\Controllers\Admin\BayiController::class, 'ayarlar'])->name('bayiler.ayarlar');
        // DİKKAT: {id} kalıplı route'lardan ÖNCE gelmeli, yoksa 'ara-uye' id sanılır.
        Route::get('/bayiler/ara-uye', [\App\Http\Controllers\Admin\BayiController::class, 'aramaUye'])->name('bayiler.ara-uye');
        Route::post('/bayiler/ayarlar', [\App\Http\Controllers\Admin\BayiController::class, 'ayarlarGuncelle'])->name('bayiler.ayarlar.guncelle');
        Route::post('/bayiler/toplu-guncelle', [\App\Http\Controllers\Admin\BayiController::class, 'topluGuncelle'])->name('bayiler.toplu.guncelle');
        Route::get('/bayiler/{id}/detay', [\App\Http\Controllers\Admin\BayiController::class, 'detay'])->name('bayiler.detay');
        Route::get('/bayiler/{id}/kredi', [\App\Http\Controllers\Admin\BayiController::class, 'kredi'])
            ->name('bayiler.kredi');
        Route::post('/bayiler/{id}/kredi', [\App\Http\Controllers\Admin\BayiController::class, 'krediGuncelle'])
            ->name('bayiler.kredi.guncelle');
        Route::post('/bayiler/{id}/kredi/sifirla', [\App\Http\Controllers\Admin\BayiController::class, 'krediSifirla'])
            ->name('bayiler.kredi.sifirla');
        Route::get('/bayiler/{id}/duzenle', [\App\Http\Controllers\Admin\BayiController::class, 'duzenle'])->name('bayiler.duzenle');
        Route::post('/bayiler/{id}/guncelle', [\App\Http\Controllers\Admin\BayiController::class, 'guncelle'])->name('bayiler.guncelle');
        Route::post('/bayiler/{id}/onay/{durum}', [\App\Http\Controllers\Admin\BayiController::class, 'onayDegistir'])->name('bayiler.onay');
        Route::post('/bayiler/{id}/komisyon', [\App\Http\Controllers\Admin\BayiController::class, 'komisyonGuncelle'])->name('bayiler.komisyon');
        Route::post('/bayiler/odeme-talep/{id}/onayla', [\App\Http\Controllers\Admin\BayiController::class, 'odemeTalepOnayla'])->name('bayiler.odeme.onayla');
        Route::post('/bayiler/odeme-talep/{id}/reddet', [\App\Http\Controllers\Admin\BayiController::class, 'odemeTalepReddet'])->name('bayiler.odeme.reddet');
        // NOT (11.08.2026): "DN Ofis Partnerliği" (tahsilat + teslimat takibi)
        // route'ları kaldırıldı — BayiController'da partnerlikTahsilat /
        // partnerlikTeslimat metotları hiç yazılmamıştı ve arayüzden de
        // çağrılmıyorlardı. Özellik yazıldığında geri eklenecek.

        Route::delete('/bayiler/{id}', [\App\Http\Controllers\Admin\BayiController::class, 'sil'])->name('bayiler.sil');

        // Promo Kod Onayları
        Route::get('/promo-onay', [\App\Http\Controllers\Admin\PromoKodOnayController::class, 'index'])->name('promo.onay');
        Route::post('/promo-onay/{id}/onayla', [\App\Http\Controllers\Admin\PromoKodOnayController::class, 'onayla'])->name('promo.onayla');
        Route::post('/promo-onay/{id}/reddet', [\App\Http\Controllers\Admin\PromoKodOnayController::class, 'reddet'])->name('promo.reddet');

        // Destek
        Route::get('/destek', [\App\Http\Controllers\Admin\DestekController::class, 'index'])->name('destek.index');
        Route::get('/destek/{id}/detay', [\App\Http\Controllers\Admin\DestekController::class, 'detay'])->name('destek.detay');
        Route::post('/destek/{id}/cevapla', [\App\Http\Controllers\Admin\DestekController::class, 'cevapla'])->name('destek.cevapla');
        // AJAX POLLING: Anlık mesajlaşma admin tarafı (29 Mayıs 2026)
        Route::get('/destek/{id}/yeni-mesajlar', [\App\Http\Controllers\Admin\DestekController::class, 'yeniMesajlar'])->name('destek.yeni-mesajlar');
        Route::post('/destek/{id}/cevapla-ajax', [\App\Http\Controllers\Admin\DestekController::class, 'cevaplaAjax'])->name('destek.cevapla.ajax');
        Route::post('/destek/{id}/durum/{durum}', [\App\Http\Controllers\Admin\DestekController::class, 'durumDegistir'])->name('destek.durum');
        Route::delete('/destek/{id}', [\App\Http\Controllers\Admin\DestekController::class, 'sil'])->name('destek.sil');
        Route::delete('/destek/{id}/cevap/{cevapId}', [\App\Http\Controllers\Admin\DestekController::class, 'cevapSil'])
        ->name('destek.cevap.sil');
        Route::post('/destek/{id}/ata', [\App\Http\Controllers\Admin\DestekController::class, 'ata'])->name('destek.ata');
        Route::get('/destek/olustur', [\App\Http\Controllers\Admin\DestekController::class, 'olustur'])->name('destek.olustur');
        Route::post('/destek/olustur', [\App\Http\Controllers\Admin\DestekController::class, 'olusturPost'])->name('destek.olustur.post');

        // Faturalar
        Route::get('/faturalar', [\App\Http\Controllers\Admin\FaturaController::class, 'index'])->name('faturalar.index');
        Route::get('/faturalar/ekle', [\App\Http\Controllers\Admin\FaturaController::class, 'ekle'])->name('faturalar.ekle');
        Route::post('/faturalar/ekle', [\App\Http\Controllers\Admin\FaturaController::class, 'eklePost'])->name('faturalar.eklePost');
        Route::get('/faturalar/bekleyen', [\App\Http\Controllers\Admin\FaturaController::class, 'bekleyen'])->name('faturalar.bekleyen');
        Route::get('/faturalar/onaylanan', [\App\Http\Controllers\Admin\FaturaController::class, 'onaylanan'])->name('faturalar.onaylanan');
        Route::get('/faturalar/{id}/detay', [\App\Http\Controllers\Admin\FaturaController::class, 'detay'])->name('faturalar.detay');
        Route::get('/faturalar/{id}/duzenle', [\App\Http\Controllers\Admin\FaturaController::class, 'duzenle'])->name('faturalar.duzenle');
        Route::post('/faturalar/{id}/duzenle', [\App\Http\Controllers\Admin\FaturaController::class, 'duzenlePost'])->name('faturalar.duzenlePost');
        Route::get('/faturalar/{id}/goster', [\App\Http\Controllers\Admin\FaturaController::class, 'goster'])->name('faturalar.goster');
        Route::post('/faturalar/{id}/durum/{durum}', [\App\Http\Controllers\Admin\FaturaController::class, 'durumDegistir'])->name('faturalar.durum');
        Route::post('/faturalar/{id}/hatirlat', [\App\Http\Controllers\Admin\FaturaController::class, 'hatirlatGonder'])->name('faturalar.hatirlat');
        Route::delete('/faturalar/{id}', [\App\Http\Controllers\Admin\FaturaController::class, 'sil'])->name('faturalar.sil');
        Route::get('/faturalar/export/{tip}', [\App\Http\Controllers\Admin\FaturaController::class, 'export'])->name('faturalar.export');
        Route::get('/faturalar/tablolara-aktar/{tip}', [\App\Http\Controllers\Admin\FaturaController::class, 'tablolaraAktar'])->name('faturalar.tablolara-aktar');
        Route::get('/faturalar/export-page/{tip}', [\App\Http\Controllers\Admin\FaturaController::class, 'exportPage'])->name('faturalar.export-page');

        // İletişim Mesajları
        Route::get('/iletisim', [\App\Http\Controllers\Admin\IletisimController::class, 'index'])->name('iletisim.index');
        Route::get('/iletisim/{id}/detay', [\App\Http\Controllers\Admin\IletisimController::class, 'detay'])->name('iletisim.detay');
        Route::delete('/iletisim/{id}', [\App\Http\Controllers\Admin\IletisimController::class, 'sil'])->name('iletisim.sil');
        Route::post('/iletisim/{id}/durum', [\App\Http\Controllers\Admin\IletisimController::class, 'durumDegistir'])->name('iletisim.durum');
        Route::post('/iletisim/toplu-sil', [\App\Http\Controllers\Admin\IletisimController::class, 'topluSil'])->name('iletisim.toplu-sil');

        // Site Yönetimi - Ayarlar
        Route::get('/ayarlar/api', [\App\Http\Controllers\Admin\AyarlarController::class, 'api'])->name('ayarlar.api');
        Route::post('/ayarlar/api', [\App\Http\Controllers\Admin\AyarlarController::class, 'apiPost'])->name('ayarlar.api.post');
        Route::get('/ayarlar/iletisim', [\App\Http\Controllers\Admin\AyarlarController::class, 'iletisim'])->name('ayarlar.iletisim');
        Route::post('/ayarlar/iletisim', [\App\Http\Controllers\Admin\AyarlarController::class, 'iletisimPost'])->name('ayarlar.iletisim.post');
        Route::get('/ayarlar/sosyal-medya', [\App\Http\Controllers\Admin\AyarlarController::class, 'sosyalMedya'])->name('ayarlar.sosyal');
        Route::post('/ayarlar/sosyal-medya', [\App\Http\Controllers\Admin\AyarlarController::class, 'sosyalMedyaPost'])->name('ayarlar.sosyal.post');
        Route::get('/ayarlar/modul', [\App\Http\Controllers\Admin\AyarlarController::class, 'modul'])->name('ayarlar.modul');
        Route::post('/ayarlar/modul', [\App\Http\Controllers\Admin\AyarlarController::class, 'modulPost'])->name('ayarlar.modul.post');
        Route::get('/ayarlar/limit', [\App\Http\Controllers\Admin\AyarlarController::class, 'limit'])->name('ayarlar.limit');
        Route::post('/ayarlar/limit', [\App\Http\Controllers\Admin\AyarlarController::class, 'limitPost'])->name('ayarlar.limit.post');
        Route::get('/ayarlar/bakim', [\App\Http\Controllers\Admin\AyarlarController::class, 'bakim'])->name('ayarlar.bakim');
        Route::get('/ayarlar/fatura', [\App\Http\Controllers\Admin\AyarlarController::class, 'fatura'])->name('ayarlar.fatura');
        Route::post('/ayarlar/fatura', [\App\Http\Controllers\Admin\AyarlarController::class, 'faturaPost'])->name('ayarlar.fatura.post');
        Route::post('/ayarlar/bakim', [\App\Http\Controllers\Admin\AyarlarController::class, 'bakimPost'])->name('ayarlar.bakim.post');
        Route::get('/ayarlar/mail', [\App\Http\Controllers\Admin\AyarlarController::class, 'mail'])->name('ayarlar.mail');
        Route::post('/ayarlar/mail', [\App\Http\Controllers\Admin\AyarlarController::class, 'mailPost'])->name('ayarlar.mail.post');
        Route::post('/ayarlar/mail/test', [\App\Http\Controllers\Admin\AyarlarController::class, 'mailTest'])->name('ayarlar.mail.test');

        // Mail Şablonları
        Route::get('/mail-templates', [\App\Http\Controllers\Admin\MailTemplateController::class, 'index'])->name('mail-templates.index');
        Route::get('/mail-templates/create', [\App\Http\Controllers\Admin\MailTemplateController::class, 'create'])->name('mail-templates.create');
        Route::post('/mail-templates', [\App\Http\Controllers\Admin\MailTemplateController::class, 'store'])->name('mail-templates.store');
        Route::get('/mail-templates/{id}/edit', [\App\Http\Controllers\Admin\MailTemplateController::class, 'edit'])->name('mail-templates.edit');
        Route::put('/mail-templates/{id}', [\App\Http\Controllers\Admin\MailTemplateController::class, 'update'])->name('mail-templates.update');
        Route::delete('/mail-templates/{id}', [\App\Http\Controllers\Admin\MailTemplateController::class, 'destroy'])->name('mail-templates.destroy');

        // Ek Hizmetler (madde 15) — paket/ilan "Yanında Satın Alınabilecekler"
        Route::get('/ek-hizmetler', [\App\Http\Controllers\Admin\EkHizmetController::class, 'index'])->name('ek-hizmetler.index');
        Route::post('/ek-hizmetler', [\App\Http\Controllers\Admin\EkHizmetController::class, 'store'])->name('ek-hizmetler.store');
        Route::put('/ek-hizmetler/{id}', [\App\Http\Controllers\Admin\EkHizmetController::class, 'update'])->name('ek-hizmetler.update');
        Route::post('/ek-hizmetler/{id}/toggle', [\App\Http\Controllers\Admin\EkHizmetController::class, 'toggle'])->name('ek-hizmetler.toggle');
        Route::delete('/ek-hizmetler/{id}', [\App\Http\Controllers\Admin\EkHizmetController::class, 'destroy'])->name('ek-hizmetler.destroy');
        Route::get('/ayarlar/sms', [\App\Http\Controllers\Admin\AyarlarController::class, 'sms'])->name('ayarlar.sms');
        Route::post('/ayarlar/sms', [\App\Http\Controllers\Admin\AyarlarController::class, 'smsPost'])->name('ayarlar.sms.post');
        Route::post('/ayarlar/sms/test', [\App\Http\Controllers\Admin\AyarlarController::class, 'smsTest'])->name('ayarlar.sms.test');
        Route::get('/ayarlar/sanal-pos', [\App\Http\Controllers\Admin\AyarlarController::class, 'sanalPos'])->name('ayarlar.sanal');
        Route::post('/ayarlar/sanal-pos', [\App\Http\Controllers\Admin\AyarlarController::class, 'sanalPosPost'])->name('ayarlar.sanal.post');
        Route::get('/ayarlar/arkaplan', [\App\Http\Controllers\Admin\AyarlarController::class, 'arkaplan'])->name('ayarlar.arkaplan');
        Route::post('/ayarlar/arkaplan', [\App\Http\Controllers\Admin\AyarlarController::class, 'arkaplanPost'])->name('ayarlar.arkaplan.post');

        // Sayfa Bazlı Bakım Modu
        Route::get('/ayarlar/sayfa-bakim', [\App\Http\Controllers\Admin\AyarlarController::class, 'sayfaBakim'])->name('ayarlar.sayfa-bakim');
        Route::post('/ayarlar/sayfa-bakim', [\App\Http\Controllers\Admin\AyarlarController::class, 'sayfaBakimStore'])->name('ayarlar.sayfa-bakim.store');
        Route::put('/ayarlar/sayfa-bakim/{id}', [\App\Http\Controllers\Admin\AyarlarController::class, 'sayfaBakimUpdate'])->name('ayarlar.sayfa-bakim.update');
        Route::post('/ayarlar/sayfa-bakim/{id}/toggle', [\App\Http\Controllers\Admin\AyarlarController::class, 'sayfaBakimToggle'])->name('ayarlar.sayfa-bakim.toggle');
        Route::delete('/ayarlar/sayfa-bakim/{id}', [\App\Http\Controllers\Admin\AyarlarController::class, 'sayfaBakimDelete'])->name('ayarlar.sayfa-bakim.delete');

        // Dil Yönetimi
        Route::get('/diller', [\App\Http\Controllers\Admin\DilController::class, 'index'])->name('diller.index');
        Route::get('/diller/ekle', [\App\Http\Controllers\Admin\DilController::class, 'ekle'])->name('diller.ekle');
        Route::post('/diller/ekle', [\App\Http\Controllers\Admin\DilController::class, 'eklePost'])->name('diller.ekle.post');
        Route::delete('/diller/{id}', [\App\Http\Controllers\Admin\DilController::class, 'sil'])->name('diller.sil');
        Route::post('/diller/{id}/durum', [\App\Http\Controllers\Admin\DilController::class, 'durumDegistir'])->name('diller.durum');

        // Menü Yönetimi
        // Navbar Menü Yönetimi
        Route::get('/menuler/header', [\App\Http\Controllers\Admin\MenuController::class, 'header'])->name('menuler.header');
        Route::post('/menuler/header/ekle', [\App\Http\Controllers\Admin\MenuController::class, 'headerEkle'])->name('menuler.header.ekle');
        Route::get('/menuler/header/{id}/duzenle', [\App\Http\Controllers\Admin\MenuController::class, 'headerDuzenle'])->name('menuler.header.duzenle');
        Route::post('/menuler/header/{id}/duzenle', [\App\Http\Controllers\Admin\MenuController::class, 'headerDuzenlePost'])->name('menuler.header.duzenle.post');
        Route::delete('/menuler/header/{id}', [\App\Http\Controllers\Admin\MenuController::class, 'headerSil'])->name('menuler.header.sil');
        Route::post('/menuler/header/sira-guncelle', [\App\Http\Controllers\Admin\MenuController::class, 'headerSiraGuncelle'])->name('menuler.header.sira-guncelle');

        // Alt Menü Yönetimi
        Route::post('/menuler/alt-menu/{ustMenuId}/ekle', [\App\Http\Controllers\Admin\MenuController::class, 'altMenuEkle'])->name('menuler.alt-menu.ekle');
        Route::delete('/menuler/alt-menu/{id}/sil', [\App\Http\Controllers\Admin\MenuController::class, 'altMenuSil'])->name('menuler.alt-menu.sil');

        // Navbar Ayarları (Top Bar Elementleri)
        Route::get('/menuler/navbar-ayarlari', [\App\Http\Controllers\Admin\MenuController::class, 'navbarAyarlari'])->name('menuler.navbar-ayarlari');
        Route::post('/menuler/navbar-ayarlari/kaydet', [\App\Http\Controllers\Admin\MenuController::class, 'navbarAyarlariKaydet'])->name('menuler.navbar-ayarlari.kaydet');

        // Footer Menü
        Route::get('/menuler/footer', [\App\Http\Controllers\Admin\MenuController::class, 'footer'])->name('menuler.footer');
        Route::post('/menuler/footer/ekle', [\App\Http\Controllers\Admin\MenuController::class, 'footerEkle'])->name('menuler.footer.ekle');

        // Bayilik Satışlar
        Route::get('/bayilik-satislar', [\App\Http\Controllers\Admin\BayilikSatisController::class, 'index'])->name('bayilik.satislar');

        // Yorumlar
        Route::get('/yorumlar', [\App\Http\Controllers\Admin\YorumController::class, 'index'])->name('yorumlar.index');

        // Blog
        Route::get('/blog', [\App\Http\Controllers\Admin\BlogController::class, 'index'])->name('blog.index');
        Route::get('/blog/ekle', [\App\Http\Controllers\Admin\BlogController::class, 'ekle'])->name('blog.ekle');
        Route::post('/blog/ekle', [\App\Http\Controllers\Admin\BlogController::class, 'eklePost'])->name('blog.eklePost');
        Route::get('/blog/{id}/duzenle', [\App\Http\Controllers\Admin\BlogController::class, 'duzenle'])->name('blog.duzenle');
        Route::post('/blog/{id}/duzenle', [\App\Http\Controllers\Admin\BlogController::class, 'duzenlePost'])->name('blog.duzenlePost');
        Route::delete('/blog/{id}', [\App\Http\Controllers\Admin\BlogController::class, 'sil'])->name('blog.sil');

        // Referanslar
        Route::get('/referanslar', [\App\Http\Controllers\Admin\ReferansController::class, 'index'])->name('referanslar.index');
        Route::get('/referanslar/ekle', [\App\Http\Controllers\Admin\ReferansController::class, 'ekle'])->name('referanslar.ekle');
        Route::post('/referanslar/ekle', [\App\Http\Controllers\Admin\ReferansController::class, 'eklePost'])->name('referanslar.eklePost');
        Route::get('/referanslar/{id}/duzenle', [\App\Http\Controllers\Admin\ReferansController::class, 'duzenle'])->name('referanslar.duzenle');
        Route::post('/referanslar/{id}/duzenle', [\App\Http\Controllers\Admin\ReferansController::class, 'duzenlePost'])->name('referanslar.duzenlePost');
        Route::delete('/referanslar/{id}', [\App\Http\Controllers\Admin\ReferansController::class, 'sil'])->name('referanslar.sil');

        // Kampanyalar/Fırsatlar
        Route::get('/kampanyalar', [\App\Http\Controllers\Admin\KampanyaController::class, 'index'])->name('kampanyalar.index');
        Route::get('/kampanyalar/ekle', [\App\Http\Controllers\Admin\KampanyaController::class, 'create'])->name('kampanyalar.ekle');
        Route::post('/kampanyalar/ekle', [\App\Http\Controllers\Admin\KampanyaController::class, 'store'])->name('kampanyalar.eklePost');
        Route::get('/kampanyalar/{id}/duzenle', [\App\Http\Controllers\Admin\KampanyaController::class, 'edit'])->name('kampanyalar.duzenle');
        Route::post('/kampanyalar/{id}/duzenle', [\App\Http\Controllers\Admin\KampanyaController::class, 'update'])->name('kampanyalar.duzenlePost');
        Route::delete('/kampanyalar/{id}', [\App\Http\Controllers\Admin\KampanyaController::class, 'destroy'])->name('kampanyalar.sil');

        // Çalışan Ticket Sistemi
        Route::get('/tickets', [\App\Http\Controllers\Admin\TicketController::class, 'index'])->name('tickets.index');
        Route::get('/tickets/olustur', [\App\Http\Controllers\Admin\TicketController::class, 'olustur'])->name('tickets.olustur');
        Route::post('/tickets/olustur', [\App\Http\Controllers\Admin\TicketController::class, 'olusturPost'])->name('tickets.olustur.post');
        Route::get('/tickets/{id}/detay', [\App\Http\Controllers\Admin\TicketController::class, 'detay'])->name('tickets.detay');
        Route::post('/tickets/{id}/cevapla', [\App\Http\Controllers\Admin\TicketController::class, 'cevapla'])->name('tickets.cevapla');
        Route::post('/tickets/{id}/durum/{durum}', [\App\Http\Controllers\Admin\TicketController::class, 'durumDegistir'])->name('tickets.durum');
        // Açılmış bir ticket'ın atananlarını sonradan değiştir (çoklu seçim)
        Route::post('/tickets/{id}/atananlar', [\App\Http\Controllers\Admin\TicketController::class, 'atananlariGuncelle'])->name('tickets.atananlar');
        Route::delete('/tickets/{id}', [\App\Http\Controllers\Admin\TicketController::class, 'sil'])->name('tickets.sil');
        Route::get('/tickets/bildirimler', [\App\Http\Controllers\Admin\TicketController::class, 'bildirimler'])->name('tickets.bildirimler');
        Route::post('/tickets/bildirim/{id}/okundu', [\App\Http\Controllers\Admin\TicketController::class, 'bildirimOkundu'])->name('tickets.bildirim.okundu');

        // CRM Modülü (Sadece Patron ve Çalışan)
        Route::prefix('crm')->name('crm.')->middleware(['rol'])->group(function () {

            // ═══════════════════════════════════════════════════════════════
            // MÜŞTERİLER — Custom route'lar (resource'tan ÖNCE, sıra kritik!)
            // ═══════════════════════════════════════════════════════════════

            // --- Static path'ler (en üstte, "musteriler/{id}" ile karışmasın) ---
            Route::get('musteriler/export',          [\App\Http\Controllers\Admin\CRM\CustomerController::class, 'export'])
                ->name('musteriler.export');
            Route::get('musteriler/export-template', [\App\Http\Controllers\Admin\CRM\CustomerController::class, 'exportTemplate'])
                ->name('musteriler.export-template');
            Route::post('musteriler/import',         [\App\Http\Controllers\Admin\CRM\CustomerController::class, 'import'])
                ->name('musteriler.import');
            Route::post('musteriler/import-uyeler',  [\App\Http\Controllers\Admin\CRM\CustomerController::class, 'importFromUyeler'])
                ->name('musteriler.import-uyeler');
            Route::post('musteriler/toplu-mail',     [\App\Http\Controllers\Admin\CRM\CustomerController::class, 'sendBulkMail'])
                ->name('musteriler.toplu-mail');
            Route::post('musteriler/toplu-islem',    [\App\Http\Controllers\Admin\CRM\CustomerController::class, 'topluIslem'])
                ->name('musteriler.toplu-islem');

            Route::post('musteriler/{id}/hizli-gonder', [\App\Http\Controllers\Admin\CRM\CustomerController::class, 'hizliGonder'])
                ->name('musteriler.hizli-gonder');

            // --- ID'li custom route'lar (mevcut + yeni — hepsi {id}/... formatında) ---
            Route::post('musteriler/{id}/not-ekle',          [\App\Http\Controllers\Admin\CRM\CustomerController::class, 'storeNote'])
                ->name('musteriler.not-ekle');
            Route::delete('musteriler/{id}/not/{noteId}',    [\App\Http\Controllers\Admin\CRM\CustomerController::class, 'deleteNote'])
                ->name('musteriler.not-sil');
            Route::post('musteriler/{id}/bilgi-guncelle',    [\App\Http\Controllers\Admin\CRM\CustomerController::class, 'updateBilgiler'])
                ->name('musteriler.bilgi-guncelle');
            Route::post('musteriler/{id}/bakiye-hareketi',   [\App\Http\Controllers\Admin\CRM\CustomerController::class, 'bakiyeHareketi'])
                ->name('musteriler.bakiye-hareketi');
            Route::post('musteriler/{id}/mail-gonder',       [\App\Http\Controllers\Admin\CRM\CustomerController::class, 'sendMail'])
                ->name('musteriler.mail-gonder');
            Route::post('musteriler/{id}/gecici-sifre',      [\App\Http\Controllers\Admin\CRM\CustomerController::class, 'geciciSifreGonder'])
                ->name('musteriler.gecici-sifre');
            Route::post('musteriler/{id}/bayi-teklif',       [\App\Http\Controllers\Admin\CRM\CustomerController::class, 'bayiTeklifGonder'])
                ->name('musteriler.bayi-teklif');
            Route::post('musteriler/{id}/ozel-teklif',       [\App\Http\Controllers\Admin\CRM\CustomerController::class, 'ozelTeklifGonder'])
                ->name('musteriler.ozel-teklif');
            Route::post('musteriler/{id}/teklif/{talepId}/onayla', [\App\Http\Controllers\Admin\CRM\CustomerController::class, 'bayiTeklifOnayla'])
                ->name('musteriler.bayi-teklif-onayla');
            Route::delete('musteriler/{id}/teklif/{talepId}/sil', [\App\Http\Controllers\Admin\CRM\CustomerController::class, 'ozelTeklifSil'])
                ->name('musteriler.teklif-sil');

            // --- Durum toggle (2 farklı method, aynı metoda gider) ---
            Route::patch('musteriler/{id}/durum',        [\App\Http\Controllers\Admin\CRM\CustomerController::class, 'toggleDurum'])
                ->name('musteriler.durum');
            Route::post('musteriler/{id}/toggle-durum',  [\App\Http\Controllers\Admin\CRM\CustomerController::class, 'toggleDurum'])
                ->name('musteriler.toggle-durum');

            // --- Tab form'ları: fatura, hizmet, hosting, domain, e-fatura, destek, referans ---
            Route::post('musteriler/{id}/fatura-ekle',   [\App\Http\Controllers\Admin\CRM\CustomerController::class, 'storeFatura'])
                ->name('musteriler.fatura.ekle');
            Route::post('musteriler/{id}/hizmet-ekle',   [\App\Http\Controllers\Admin\CRM\CustomerController::class, 'storeHizmet'])
                ->name('musteriler.hizmet.ekle');
            Route::post('musteriler/{id}/hosting-ekle',  [\App\Http\Controllers\Admin\CRM\CustomerController::class, 'storeHosting'])
                ->name('musteriler.hosting.ekle');
            Route::post('musteriler/{id}/domain-ekle',   [\App\Http\Controllers\Admin\CRM\CustomerController::class, 'storeDomain'])
                ->name('musteriler.domain.ekle');
            Route::post('musteriler/{id}/efatura-ekle',  [\App\Http\Controllers\Admin\CRM\CustomerController::class, 'storeEfatura'])
                ->name('musteriler.efatura.ekle');
            Route::post('musteriler/{id}/destek-mesaj',  [\App\Http\Controllers\Admin\CRM\CustomerController::class, 'storeDestekMesaj'])
                ->name('musteriler.destek.mesaj');
            Route::delete('musteriler/{id}/destek/{talepId}', [\App\Http\Controllers\Admin\CRM\CustomerController::class, 'destekTalepSil'])
            ->name('musteriler.destek.sil');

            Route::delete('musteriler/{id}/destek/{talepId}/mesaj/{mesajId}', [\App\Http\Controllers\Admin\CRM\CustomerController::class, 'destekMesajSil'])
            ->name('musteriler.destek.mesaj.sil');
            Route::post('musteriler/{id}/referans-ekle', [\App\Http\Controllers\Admin\CRM\CustomerController::class, 'storeReferans'])
                ->name('musteriler.referans.ekle');

            // Müşteri referans bağı: "bu müşteriyi kim getirdi" → her alımından %10
            Route::post('musteriler/{id}/referans-bagla', [\App\Http\Controllers\Admin\CRM\CustomerController::class, 'referansBagla'])
                ->name('musteriler.referans.bagla');
            Route::post('musteriler/{id}/referans-bagi-kaldir', [\App\Http\Controllers\Admin\CRM\CustomerController::class, 'referansBagiKaldir'])
                ->name('musteriler.referans.kaldir');

            // --- Rapor (3 route: ekle/tekrar gönder/sil) ---
            Route::post('musteriler/{id}/rapor-ekle',                  [\App\Http\Controllers\Admin\CRM\CustomerController::class, 'storeRapor'])
                ->name('musteriler.rapor.ekle');
            Route::post('musteriler/{id}/rapor/{raporId}/tekrar',      [\App\Http\Controllers\Admin\CRM\CustomerController::class, 'resendRapor'])
                ->name('musteriler.rapor.tekrar');
            Route::delete('musteriler/{id}/rapor/{raporId}',           [\App\Http\Controllers\Admin\CRM\CustomerController::class, 'deleteRapor'])
                ->name('musteriler.rapor.sil');

            // --- Kredi (3 route: ekle/sil/taksit-ode) ---
            Route::post('musteriler/{id}/kredi-ekle',                          [\App\Http\Controllers\Admin\CRM\CustomerController::class, 'storeKredi'])
                ->name('musteriler.kredi.ekle');
            Route::delete('musteriler/{id}/kredi/{krediId}',                   [\App\Http\Controllers\Admin\CRM\CustomerController::class, 'deleteKredi'])
                ->name('musteriler.kredi.sil');
            Route::post('musteriler/{id}/kredi/{krediId}/taksit/{taksitId}/ode', [\App\Http\Controllers\Admin\CRM\CustomerController::class, 'odemeTaksit'])
                ->name('musteriler.kredi.taksit.ode');
            // NOT (11.08.2026): 'musteriler.kredi.onayla' route'u kaldırıldı —
            // CustomerController'da krediOnayla metodu yok (DnBankController'a
            // taşınmış, route güncellenmemiş) ve arayüzden çağrılmıyordu.
            // Kredi onayı artık: admin.dnbank-krediler.onayla
            // --- Resource EN SON (id=integer constraint ile) ---
            // {id} sadece numerik kabul edecek; "export", "import" gibi kelimeler bu route'a düşmez
            Route::resource('musteriler', \App\Http\Controllers\Admin\CRM\CustomerController::class)
                ->parameters(['musteriler' => 'id'])
                ->where(['id' => '[0-9]+']);

            Route::resource('firsatlar', \App\Http\Controllers\Admin\CRM\OpportunityController::class)
                ->parameters(['firsatlar' => 'id']);
            Route::patch('firsatlar/{id}/stage', [\App\Http\Controllers\Admin\CRM\OpportunityController::class, 'updateStage'])
                ->name('firsatlar.stage');

            Route::resource('gorevler', \App\Http\Controllers\Admin\CRM\TaskController::class)
                ->parameters(['gorevler' => 'id']);
            Route::delete('gorevler/{id}/dosya/{fileId}', [\App\Http\Controllers\Admin\CRM\TaskController::class, 'deleteFile'])
            ->name('gorevler.dosya.sil');
            // Görev içi chat mesajı + Kanban'a taşıma (stajyer paketi 02.06.2026)
            Route::post('gorevler/{id}/mesaj', [\App\Http\Controllers\Admin\CRM\TaskController::class, 'mesajGonder'])
            ->name('gorevler.mesaj');
            Route::delete('gorevler/mesaj/{mesajId}', [\App\Http\Controllers\Admin\CRM\TaskController::class, 'mesajSil'])
            ->name('gorevler.mesaj.sil');
            Route::post('gorevler/mesaj/{mesajId}/duzenle', [\App\Http\Controllers\Admin\CRM\TaskController::class, 'mesajDuzenle'])
            ->name('gorevler.mesaj.duzenle');
            Route::post('gorevler/{id}/reaksiyon', [\App\Http\Controllers\Admin\CRM\TaskController::class, 'reaksiyonToggle'])
            ->name('gorevler.reaksiyon');
            Route::post('gorevler/{id}/kanbana', [\App\Http\Controllers\Admin\CRM\TaskController::class, 'kanbanaTasi'])
            ->name('gorevler.kanbana');
            Route::post('gorevler/{id}/tamamla', [\App\Http\Controllers\Admin\CRM\TaskController::class, 'tamamlaToggle'])
            ->name('gorevler.tamamla');
            Route::post('gorevler/{id}/durum', [\App\Http\Controllers\Admin\CRM\TaskController::class, 'durumDegistir'])
            ->name('gorevler.durum');
            Route::post('gorevler/{id}/ata', [\App\Http\Controllers\Admin\CRM\TaskController::class, 'ata'])
            ->name('gorevler.ata');

            Route::get('pipelines', [\App\Http\Controllers\Admin\CRM\PipelineController::class, 'index'])->name('pipelines.index');
            Route::post('pipelines', [\App\Http\Controllers\Admin\CRM\PipelineController::class, 'store'])->name('pipelines.store');
            Route::put('pipelines/{pipeline}', [\App\Http\Controllers\Admin\CRM\PipelineController::class, 'update'])->name('pipelines.update');
            Route::delete('pipelines/{pipeline}', [\App\Http\Controllers\Admin\CRM\PipelineController::class, 'destroy'])->name('pipelines.destroy');

            Route::post('pipelines/{pipeline}/stages', [\App\Http\Controllers\Admin\CRM\PipelineController::class, 'storeStage'])->name('pipelines.stages.store');
            Route::put('pipelines/{pipeline}/stages/{stage}', [\App\Http\Controllers\Admin\CRM\PipelineController::class, 'updateStage'])->name('pipelines.stages.update');
            Route::delete('pipelines/{pipeline}/stages/{stage}', [\App\Http\Controllers\Admin\CRM\PipelineController::class, 'destroyStage'])->name('pipelines.stages.destroy');

            // Domain Takip
            Route::get('domains', [\App\Http\Controllers\Admin\CRM\DomainTrackingController::class, 'index'])->name('domains.index');

            /* ═══ Domain & Hosting KÂR RAPORU (5. öncelik) — {kaynak}/{id} kalıbından ÖNCE ═══ */
            Route::get('domains/kar-raporu', [\App\Http\Controllers\Admin\CRM\DomainTrackingController::class, 'karRaporu'])->name('domains.kar-raporu');

            /* ═══ Domain listesini CSV indir — {kaynak}/{id} kalıbından ÖNCE ═══ */
            Route::get('domains/disa-aktar', [\App\Http\Controllers\Admin\CRM\DomainTrackingController::class, 'disaAktar'])
                ->name('domains.disa-aktar');

            /* ═══ Toplu tutar girişi — tutarı boş domain kayıtları tek ekranda ═══ */
            Route::get('domains/toplu-tutar', [\App\Http\Controllers\Admin\CRM\TopluTutarController::class, 'index'])
                ->name('domains.toplu-tutar');
            Route::post('domains/toplu-tutar', [\App\Http\Controllers\Admin\CRM\TopluTutarController::class, 'kaydet'])
                ->name('domains.toplu-tutar.kaydet');

            Route::post('domains/metunic-sync', [\App\Http\Controllers\Admin\CRM\DomainTrackingController::class, 'metunicSenkron'])->name('domains.metunic-sync');
            // Metunic'e özel kayıtlara elle tutar/maliyet girme
            Route::post('domains/metunic/{id}/tutar', [\App\Http\Controllers\Admin\CRM\DomainTrackingController::class, 'metunicTutar'])
                ->whereNumber('id')->name('domains.metunic-tutar');
            Route::get('domains/create', [\App\Http\Controllers\Admin\CRM\DomainTrackingController::class, 'create'])->name('domains.create');
            Route::post('domains', [\App\Http\Controllers\Admin\CRM\DomainTrackingController::class, 'store'])->name('domains.store');
            Route::get('domains/{kaynak}/{id}', [\App\Http\Controllers\Admin\CRM\DomainTrackingController::class, 'show'])
                ->where('kaynak', 'order|manuel|metunic|hosting')
                ->name('domains.show');
            Route::get('domains/{kaynak}/{id}/edit', [\App\Http\Controllers\Admin\CRM\DomainTrackingController::class, 'edit'])
                ->where('kaynak', 'order|manuel|hosting')
                ->name('domains.edit');

            // GÖREV #214/4 — yenileme ödeme bildirimi (mail + SMS)
            Route::post('domains/{kaynak}/{id}/odeme-bildirimi', [\App\Http\Controllers\Admin\CRM\DomainTrackingController::class, 'odemeBildirimi'])
                ->where('kaynak', 'order|manuel|metunic|hosting')
                ->middleware('throttle:30,10')
                ->name('domains.odeme-bildirimi');

            Route::put('domains/{kaynak}/{id}', [\App\Http\Controllers\Admin\CRM\DomainTrackingController::class, 'update'])
                ->where('kaynak', 'order|manuel|hosting')
                ->name('domains.update');

            Route::delete('domains/{kaynak}/{id}', [\App\Http\Controllers\Admin\CRM\DomainTrackingController::class, 'destroy'])
                ->where('kaynak', 'order|manuel|hosting')
                ->name('domains.destroy');

            // ════════════════════════════════════════════════════════════
            // SANAL TUR HOSTING TAKİP — Domain & Hosting Takip'in tek-tablolu kopyası
            // ════════════════════════════════════════════════════════════
            Route::get('sanaltur-hosting', [\App\Http\Controllers\Admin\CRM\SanalTurHostingController::class, 'index'])->name('sanaltur-hosting.index');
            Route::get('sanaltur-hosting/create', [\App\Http\Controllers\Admin\CRM\SanalTurHostingController::class, 'create'])->name('sanaltur-hosting.create');
            Route::post('sanaltur-hosting', [\App\Http\Controllers\Admin\CRM\SanalTurHostingController::class, 'store'])->name('sanaltur-hosting.store');
            Route::get('sanaltur-hosting/{id}', [\App\Http\Controllers\Admin\CRM\SanalTurHostingController::class, 'show'])->whereNumber('id')->name('sanaltur-hosting.show');
            Route::get('sanaltur-hosting/{id}/edit', [\App\Http\Controllers\Admin\CRM\SanalTurHostingController::class, 'edit'])->whereNumber('id')->name('sanaltur-hosting.edit');
            Route::put('sanaltur-hosting/{id}', [\App\Http\Controllers\Admin\CRM\SanalTurHostingController::class, 'update'])->whereNumber('id')->name('sanaltur-hosting.update');
            Route::delete('sanaltur-hosting/{id}', [\App\Http\Controllers\Admin\CRM\SanalTurHostingController::class, 'destroy'])->whereNumber('id')->name('sanaltur-hosting.destroy');

            // Kanban Board Routes
            Route::resource('kanban', \App\Http\Controllers\Admin\CRM\KanbanBoardController::class)
                ->parameters(['kanban' => 'id']);

            // Kanban List Routes
            Route::post('kanban/{boardId}/lists', [\App\Http\Controllers\Admin\CRM\KanbanBoardController::class, 'storeList'])->name('kanban.lists.store');
            Route::put('kanban/{boardId}/lists/{listId}', [\App\Http\Controllers\Admin\CRM\KanbanBoardController::class, 'updateList'])->name('kanban.lists.update');
            Route::delete('kanban/{boardId}/lists/{listId}', [\App\Http\Controllers\Admin\CRM\KanbanBoardController::class, 'destroyList'])->name('kanban.lists.destroy');

            // Kanban Card Routes
            Route::post('kanban/{boardId}/lists/{listId}/cards', [\App\Http\Controllers\Admin\CRM\KanbanBoardController::class, 'storeCard'])->name('kanban.cards.store');
            Route::put('kanban/{boardId}/lists/{listId}/cards/{cardId}', [\App\Http\Controllers\Admin\CRM\KanbanBoardController::class, 'updateCard'])->name('kanban.cards.update');
            Route::delete('kanban/{boardId}/lists/{listId}/cards/{cardId}', [\App\Http\Controllers\Admin\CRM\KanbanBoardController::class, 'destroyCard'])->name('kanban.cards.destroy');
            Route::get('kanban/{boardId}/cards/{cardId}', [\App\Http\Controllers\Admin\CRM\KanbanBoardController::class, 'getCardDetails'])->name('kanban.cards.details');

            // Drag & Drop Routes
            Route::post('kanban/{boardId}/move-card', [\App\Http\Controllers\Admin\CRM\KanbanBoardController::class, 'moveCard'])->name('kanban.move-card');
            Route::post('kanban/{boardId}/reorder-lists', [\App\Http\Controllers\Admin\CRM\KanbanBoardController::class, 'reorderLists'])->name('kanban.reorder-lists');
            // Kanban Arşiv & Takvim & İstatistik
            Route::get('kanban/{boardId}/arsiv', [\App\Http\Controllers\Admin\CRM\KanbanBoardController::class, 'archivedCards'])
                ->name('kanban.arsiv');
            // Kartı kopyala (rota eksikti — arayüz POST atıyor, 405 veriyordu)
            Route::post('kanban/{boardId}/lists/{listId}/cards/{cardId}/copy', [\App\Http\Controllers\Admin\CRM\KanbanBoardController::class, 'copyCard'])
                ->name('kanban.cards.copy');
            Route::post('kanban/{boardId}/lists/{listId}/cards/{cardId}/archive', [\App\Http\Controllers\Admin\CRM\KanbanBoardController::class, 'archiveCard'])
                ->name('kanban.cards.archive');
            Route::post('kanban/{boardId}/cards/{cardId}/restore', [\App\Http\Controllers\Admin\CRM\KanbanBoardController::class, 'restoreCard'])
                ->name('kanban.cards.restore');
            Route::get('kanban/{boardId}/takvim', [\App\Http\Controllers\Admin\CRM\KanbanBoardController::class, 'calendarView'])
                ->name('kanban.takvim');
            Route::get('kanban/{boardId}/stats', [\App\Http\Controllers\Admin\CRM\KanbanBoardController::class, 'boardStats'])
                ->name('kanban.stats');
            // Kanban Card Comments
            Route::post('kanban/{boardId}/cards/{cardId}/comments', [\App\Http\Controllers\Admin\CRM\KanbanBoardController::class, 'addComment'])->name('kanban.cards.comments.store');
            Route::delete('kanban/{boardId}/cards/{cardId}/comments/{commentId}', [\App\Http\Controllers\Admin\CRM\KanbanBoardController::class, 'deleteComment'])->name('kanban.cards.comments.destroy');

            // Kanban Card Checklists
            Route::post('kanban/{boardId}/cards/{cardId}/checklists', [\App\Http\Controllers\Admin\CRM\KanbanBoardController::class, 'createChecklist'])->name('kanban.cards.checklists.store');
            Route::delete('kanban/{boardId}/cards/{cardId}/checklists/{checklistId}', [\App\Http\Controllers\Admin\CRM\KanbanBoardController::class, 'deleteChecklist'])->name('kanban.cards.checklists.destroy');
            Route::post('kanban/{boardId}/cards/{cardId}/checklists/{checklistId}/items', [\App\Http\Controllers\Admin\CRM\KanbanBoardController::class, 'addChecklistItem'])->name('kanban.checklists.items.store');
            Route::post('kanban/{boardId}/cards/{cardId}/checklists/{checklistId}/items/{itemId}/toggle', [\App\Http\Controllers\Admin\CRM\KanbanBoardController::class, 'toggleChecklistItem'])->name('kanban.checklists.items.toggle');
            // Kanban Kategori Yönetimi
            Route::delete('kanban/kategori/sil', [\App\Http\Controllers\Admin\CRM\KanbanBoardController::class, 'deleteKategori'])
                ->name('kanban.kategori.sil');
            Route::post('kanban/kategori/yeniden-adlandir', [\App\Http\Controllers\Admin\CRM\KanbanBoardController::class, 'renameKategori'])
                ->name('kanban.kategori.yeniden-adlandir');
            // Kanban Card Attachments
            Route::post('kanban/{boardId}/cards/{cardId}/attachments', [\App\Http\Controllers\Admin\CRM\KanbanBoardController::class, 'addAttachment'])->name('kanban.cards.attachments.store');
            Route::delete('kanban/{boardId}/cards/{cardId}/attachments/{attachmentId}', [\App\Http\Controllers\Admin\CRM\KanbanBoardController::class, 'deleteAttachment'])->name('kanban.cards.attachments.destroy');

            // Kanban Card Members
            Route::post('kanban/{boardId}/cards/{cardId}/members/toggle', [\App\Http\Controllers\Admin\CRM\KanbanBoardController::class, 'toggleMember'])->name('kanban.cards.members.toggle');

            // Kanban Board Members
            Route::post('kanban/{boardId}/members/toggle', [\App\Http\Controllers\Admin\CRM\KanbanBoardController::class, 'toggleBoardMember'])->name('kanban.board.members.toggle');

            // Kart kapak görseli (stajyer paketi 02.06.2026)
            Route::post('kanban/{boardId}/cards/{cardId}/cover', [\App\Http\Controllers\Admin\CRM\KanbanBoardController::class, 'uploadCover'])->name('kanban.cards.cover.store');
            Route::delete('kanban/{boardId}/cards/{cardId}/cover', [\App\Http\Controllers\Admin\CRM\KanbanBoardController::class, 'deleteCover'])->name('kanban.cards.cover.destroy');

            // Kanban kilitle/aç (sadece pano sahibi)
            Route::post('kanban/{boardId}/toggle-lock', [\App\Http\Controllers\Admin\CRM\KanbanBoardController::class, 'toggleLock'])->name('kanban.toggle-lock');

            // --- CRM Sözleşmeler Modülü ---
            Route::get('sozlesmeler',                   [\App\Http\Controllers\Admin\CRM\SozlesmeController::class, 'index'])->name('sozlesmeler.index');
            Route::get('sozlesmeler/ekle',              [\App\Http\Controllers\Admin\CRM\SozlesmeController::class, 'create'])->name('sozlesmeler.create');
            Route::post('sozlesmeler',                  [\App\Http\Controllers\Admin\CRM\SozlesmeController::class, 'store'])->name('sozlesmeler.store');
            Route::post('sozlesme-kategorileri',        [\App\Http\Controllers\Admin\CRM\SozlesmeController::class, 'kategoriEkle'])->name('sozlesmeler.kategori.ekle');
            Route::delete('sozlesme-kategorileri/{id}', [\App\Http\Controllers\Admin\CRM\SozlesmeController::class, 'kategoriSil'])->name('sozlesmeler.kategori.sil')->where('id', '[0-9]+');
            Route::get('sozlesmeler/{id}/yazdir',       [\App\Http\Controllers\Admin\CRM\SozlesmeController::class, 'yazdir'])->name('sozlesmeler.yazdir')->where('id', '[0-9]+');
            Route::get('sozlesmeler/{id}/word',         [\App\Http\Controllers\Admin\CRM\SozlesmeController::class, 'word'])->name('sozlesmeler.word')->where('id', '[0-9]+');
            Route::get('sozlesmeler/{id}/duzenle',      [\App\Http\Controllers\Admin\CRM\SozlesmeController::class, 'edit'])->name('sozlesmeler.edit')->where('id', '[0-9]+');
            Route::put('sozlesmeler/{id}',              [\App\Http\Controllers\Admin\CRM\SozlesmeController::class, 'update'])->name('sozlesmeler.update')->where('id', '[0-9]+');
            Route::delete('sozlesmeler/{id}',           [\App\Http\Controllers\Admin\CRM\SozlesmeController::class, 'destroy'])->name('sozlesmeler.destroy')->where('id', '[0-9]+');

            /* --- Sosyal Medya Paylaşım Takibi ---
               Kasa (ozel-kayitlar) marka/platform bilgisini tutar;
               burası o platformların GÜNLÜK paylaşım takibidir.
               Yetki kontrolü controller içinde (Patron + Çalışan). */
            Route::get('sosyal-medya-takip',                       [\App\Http\Controllers\Admin\SosyalMedyaTakipController::class, 'index'])->name('sosyal-medya-takip.index');
            Route::post('sosyal-medya-takip/paylasim',             [\App\Http\Controllers\Admin\SosyalMedyaTakipController::class, 'paylasimEkle'])->name('sosyal-medya-takip.paylasim.ekle');
            Route::delete('sosyal-medya-takip/paylasim/{id}',      [\App\Http\Controllers\Admin\SosyalMedyaTakipController::class, 'paylasimSil'])->name('sosyal-medya-takip.paylasim.sil')->where('id', '[0-9]+');
            Route::post('sosyal-medya-takip/gunden-kaldir',        [\App\Http\Controllers\Admin\SosyalMedyaTakipController::class, 'gundenKaldir'])->name('sosyal-medya-takip.gunden-kaldir');
            Route::post('sosyal-medya-takip/gun-notu',             [\App\Http\Controllers\Admin\SosyalMedyaTakipController::class, 'gunNotu'])->name('sosyal-medya-takip.gun-notu');
            Route::delete('sosyal-medya-takip/gun-notu/{id}',      [\App\Http\Controllers\Admin\SosyalMedyaTakipController::class, 'gunNotuSil'])->name('sosyal-medya-takip.gun-notu.sil')->where('id', '[0-9]+');

            // Plan yonetimi — haftalik sablon + tarihe ozel istisna (ayri controller)
            Route::get('sosyal-medya-plan',                        [\App\Http\Controllers\Admin\SosyalMedyaPlanController::class, 'index'])->name('sosyal-medya-takip.plan');
            Route::post('sosyal-medya-plan/hesap',                  [\App\Http\Controllers\Admin\SosyalMedyaPlanController::class, 'hesapEkle'])->name('sosyal-medya-takip.hesap.ekle');
            Route::get('sosyal-medya-aylik',                       [\App\Http\Controllers\Admin\SosyalMedyaRaporController::class, 'aylik'])->name('sosyal-medya-takip.aylik');
            Route::post('sosyal-medya-plan',                       [\App\Http\Controllers\Admin\SosyalMedyaPlanController::class, 'kaydet'])->name('sosyal-medya-takip.plan.kaydet');
            Route::post('sosyal-medya-plan/rapor-alicilari',       [\App\Http\Controllers\Admin\SosyalMedyaPlanController::class, 'raporAlicilari'])->name('sosyal-medya-takip.rapor-alicilari');
            Route::post('sosyal-medya-plan/istisna',               [\App\Http\Controllers\Admin\SosyalMedyaPlanController::class, 'istisnaEkle'])->name('sosyal-medya-takip.istisna.ekle');
            Route::get('sosyal-medya-plan/etkinlikler',             [\App\Http\Controllers\Admin\SosyalMedyaPlanController::class, 'etkinlikler'])->name('sosyal-medya-takip.etkinlikler');
            Route::post('sosyal-medya-plan/kalem',                  [\App\Http\Controllers\Admin\SosyalMedyaPlanController::class, 'kalemEkle'])->name('sosyal-medya-takip.kalem.ekle');
            Route::delete('sosyal-medya-plan/kalem/{id}',           [\App\Http\Controllers\Admin\SosyalMedyaPlanController::class, 'kalemSil'])->name('sosyal-medya-takip.kalem.sil')->where('id', '[0-9]+');
            Route::delete('sosyal-medya-plan/istisna/{id}',        [\App\Http\Controllers\Admin\SosyalMedyaPlanController::class, 'istisnaSil'])->name('sosyal-medya-takip.istisna.sil')->where('id', '[0-9]+');

            // --- Sosyal Medya Kasası ---
            Route::get('ozel-kayitlar/disa-aktar',      [\App\Http\Controllers\Admin\CRM\OzelKayitController::class, 'export'])->name('ozel-kayitlar.export');
            Route::get('ozel-kayitlar/sablon',          [\App\Http\Controllers\Admin\CRM\OzelKayitController::class, 'exportTemplate'])->name('ozel-kayitlar.export-template');
            Route::post('ozel-kayitlar/ice-aktar',      [\App\Http\Controllers\Admin\CRM\OzelKayitController::class, 'import'])->name('ozel-kayitlar.import');
            Route::get('ozel-kayitlar/ekle',            [\App\Http\Controllers\Admin\CRM\OzelKayitController::class, 'create'])->name('ozel-kayitlar.create');
            Route::get('ozel-kayitlar',                 [\App\Http\Controllers\Admin\CRM\OzelKayitController::class, 'index'])->name('ozel-kayitlar.index');
            Route::post('ozel-kayitlar',                [\App\Http\Controllers\Admin\CRM\OzelKayitController::class, 'store'])->name('ozel-kayitlar.store');
            Route::get('ozel-kayitlar/{id}',            [\App\Http\Controllers\Admin\CRM\OzelKayitController::class, 'show'])->name('ozel-kayitlar.show')->where('id', '[0-9]+');
            Route::get('ozel-kayitlar/{id}/duzenle',    [\App\Http\Controllers\Admin\CRM\OzelKayitController::class, 'edit'])->name('ozel-kayitlar.edit')->where('id', '[0-9]+');
            Route::put('ozel-kayitlar/{id}',            [\App\Http\Controllers\Admin\CRM\OzelKayitController::class, 'update'])->name('ozel-kayitlar.update')->where('id', '[0-9]+');
            Route::post('ozel-kayitlar/{id}/durum',     [\App\Http\Controllers\Admin\CRM\OzelKayitController::class, 'durumDegistir'])->name('ozel-kayitlar.durum')->where('id', '[0-9]+');
            Route::delete('ozel-kayitlar/{id}',         [\App\Http\Controllers\Admin\CRM\OzelKayitController::class, 'destroy'])->name('ozel-kayitlar.destroy')->where('id', '[0-9]+');
            Route::post('ozel-kayitlar/{id}/hesap',     [\App\Http\Controllers\Admin\CRM\OzelKayitController::class, 'hesapEkle'])->name('ozel-kayitlar.hesap.ekle')->where('id', '[0-9]+');
            Route::put('ozel-kayitlar/{id}/hesap/{hesapId}',    [\App\Http\Controllers\Admin\CRM\OzelKayitController::class, 'hesapGuncelle'])->name('ozel-kayitlar.hesap.guncelle')->where(['id' => '[0-9]+', 'hesapId' => '[0-9]+']);
            Route::delete('ozel-kayitlar/{id}/hesap/{hesapId}', [\App\Http\Controllers\Admin\CRM\OzelKayitController::class, 'hesapSil'])->name('ozel-kayitlar.hesap.sil')->where(['id' => '[0-9]+', 'hesapId' => '[0-9]+']);
        });

        // ═══ Presence + Admin DM (tüm yöneticilere açık — rol kapısından muaf) ═══
        Route::withoutMiddleware(['rol'])->group(function () {
            Route::post('/presence/ping', [\App\Http\Controllers\Admin\PresenceController::class, 'ping'])->name('presence.ping');
            Route::get('/dm', [\App\Http\Controllers\Admin\AdminDmController::class, 'index'])->name('dm.index');
            Route::get('/dm/kisiler', [\App\Http\Controllers\Admin\AdminDmController::class, 'kisilerJson'])->name('dm.kisiler');
            Route::get('/dm/okunmamis', [\App\Http\Controllers\Admin\AdminDmController::class, 'okunmamis'])->name('dm.okunmamis');
            Route::post('/dm/gonder', [\App\Http\Controllers\Admin\AdminDmController::class, 'gonder'])->name('dm.gonder');
            Route::post('/dm/ilet', [\App\Http\Controllers\Admin\AdminDmController::class, 'ilet'])->name('dm.ilet');
            Route::post('/dm/mesaj/{mid}/sil', [\App\Http\Controllers\Admin\AdminDmController::class, 'mesajSil'])->whereNumber('mid')->name('dm.mesaj.sil');
            Route::post('/dm/mesaj/{mid}/duzenle', [\App\Http\Controllers\Admin\AdminDmController::class, 'mesajDuzenle'])->whereNumber('mid')->name('dm.mesaj.duzenle');
            Route::get('/dm/{id}', [\App\Http\Controllers\Admin\AdminDmController::class, 'konusma'])->whereNumber('id')->name('dm.konusma');
            Route::get('/dm/{id}/mesajlar', [\App\Http\Controllers\Admin\AdminDmController::class, 'mesajlar'])->whereNumber('id')->name('dm.mesajlar');

            // Müşteri (üye) ↔ yönetici DM
            Route::get('/musteri-dm', [\App\Http\Controllers\Admin\MusteriDmController::class, 'index'])->name('musteri-dm.index');
            Route::get('/musteri-dm/kisiler', [\App\Http\Controllers\Admin\MusteriDmController::class, 'kisilerJson'])->name('musteri-dm.kisiler');
            Route::get('/musteri-dm/ara', [\App\Http\Controllers\Admin\MusteriDmController::class, 'ara'])->name('musteri-dm.ara');
            Route::get('/musteri-dm/okunmamis', [\App\Http\Controllers\Admin\MusteriDmController::class, 'okunmamis'])->name('musteri-dm.okunmamis');
            Route::post('/musteri-dm/gonder', [\App\Http\Controllers\Admin\MusteriDmController::class, 'gonder'])->name('musteri-dm.gonder');
            Route::post('/musteri-dm/mesaj/{mid}/sil', [\App\Http\Controllers\Admin\MusteriDmController::class, 'mesajSil'])->whereNumber('mid')->name('musteri-dm.mesaj.sil');
            Route::post('/musteri-dm/mesaj/{mid}/duzenle', [\App\Http\Controllers\Admin\MusteriDmController::class, 'mesajDuzenle'])->whereNumber('mid')->name('musteri-dm.mesaj.duzenle');
            Route::post('/musteri-dm/{id}/ustlen', [\App\Http\Controllers\Admin\MusteriDmController::class, 'ustlen'])->whereNumber('id')->name('musteri-dm.ustlen');
            Route::post('/musteri-dm/{id}/birak', [\App\Http\Controllers\Admin\MusteriDmController::class, 'birak'])->whereNumber('id')->name('musteri-dm.birak');
            Route::get('/musteri-dm/{id}', [\App\Http\Controllers\Admin\MusteriDmController::class, 'konusma'])->whereNumber('id')->name('musteri-dm.konusma');
            Route::get('/musteri-dm/{id}/mesajlar', [\App\Http\Controllers\Admin\MusteriDmController::class, 'mesajlar'])->whereNumber('id')->name('musteri-dm.mesajlar');
        });

        // Import/Export (Toplu Veri Yönetimi)
        Route::get('/import-export', [\App\Http\Controllers\Admin\ImportExportController::class, 'index'])->name('import.export');
        Route::get('/export/uyeler', [\App\Http\Controllers\Admin\ImportExportController::class, 'uyelerExport'])->name('export.uyeler');
        Route::get('/export/faturalar', [\App\Http\Controllers\Admin\ImportExportController::class, 'faturalarExport'])->name('export.faturalar');
        Route::get('/export/paketler', [\App\Http\Controllers\Admin\ImportExportController::class, 'paketlerExport'])->name('export.paketler');
        Route::post('/import/uyeler', [\App\Http\Controllers\Admin\ImportExportController::class, 'uyelerImport'])->name('import.uyeler');
        Route::post('/import/paketler', [\App\Http\Controllers\Admin\ImportExportController::class, 'paketlerImport'])->name('import.paketler');
        Route::get('/template/uyeler', [\App\Http\Controllers\Admin\ImportExportController::class, 'uyelerTemplate'])->name('import.template.uyeler');
        Route::post('/toplu-sil', [\App\Http\Controllers\Admin\ImportExportController::class, 'topluSil'])->name('toplu.sil');

        // Satış Yönetimi
        Route::get('/satislar/hosting', [\App\Http\Controllers\Admin\SatisController::class, 'hostingSatislar'])->name('satislar.hosting');
        Route::get('/satislar/web-paket', [\App\Http\Controllers\Admin\SatisController::class, 'webPaketSatislar'])->name('satislar.web-paket');
        Route::get('/satislar/domain', [\App\Http\Controllers\Admin\SatisController::class, 'domainSatislar'])->name('satislar.domain');
        Route::get('/satislar/{id}/detay', [\App\Http\Controllers\Admin\SatisController::class, 'detay'])->name('satislar.detay');

        // Hosting Yönetimi
        Route::get('/hosting/paketler', [\App\Http\Controllers\Admin\HostingController::class, 'paketler'])->name('hosting.paketler.index');
        Route::get('/hosting/paketler/ekle', [\App\Http\Controllers\Admin\HostingController::class, 'paketEkle'])->name('hosting.paketler.ekle');
        Route::post('/hosting/paketler/ekle', [\App\Http\Controllers\Admin\HostingController::class, 'paketEklePost'])->name('hosting.paketler.eklePost');
        Route::get('/hosting/paketler/{id}/duzenle', [\App\Http\Controllers\Admin\HostingController::class, 'paketDuzenle'])->name('hosting.paketler.duzenle');
        Route::post('/hosting/paketler/{id}/duzenle', [\App\Http\Controllers\Admin\HostingController::class, 'paketDuzenlePost'])->name('hosting.paketler.duzenlePost');
        Route::delete('/hosting/paketler/{id}', [\App\Http\Controllers\Admin\HostingController::class, 'paketSil'])->name('hosting.paketler.sil');
        Route::get('/hosting/satislar', [\App\Http\Controllers\Admin\HostingController::class, 'satislar'])->name('hosting.satislar.index');

        // Alan Adı Yönetimi
        Route::get('/domain/fiyatlar', [\App\Http\Controllers\Admin\DomainController::class, 'fiyatlar'])->name('domain.fiyatlar.index');
        Route::get('/domain/fiyatlar/ekle', [\App\Http\Controllers\Admin\DomainController::class, 'fiyatEkle'])->name('domain.fiyatlar.ekle');
        Route::post('/domain/fiyatlar/ekle', [\App\Http\Controllers\Admin\DomainController::class, 'fiyatEklePost'])->name('domain.fiyatlar.eklePost');
        Route::get('/domain/fiyatlar/{id}/duzenle', [\App\Http\Controllers\Admin\DomainController::class, 'fiyatDuzenle'])->name('domain.fiyatlar.duzenle');
        Route::post('/domain/fiyatlar/{id}/duzenle', [\App\Http\Controllers\Admin\DomainController::class, 'fiyatDuzenlePost'])->name('domain.fiyatlar.duzenlePost');
        Route::get('/giris-loglari', [\App\Http\Controllers\Admin\GirisLogController::class, 'index'])->name('giris-loglari.index');
        Route::post('/domain/fiyatlar/sira', [\App\Http\Controllers\Admin\DomainController::class, 'siraKaydet'])->name('domain.fiyatlar.sira');
        Route::delete('/domain/fiyatlar/{id}', [\App\Http\Controllers\Admin\DomainController::class, 'fiyatSil'])->name('domain.fiyatlar.sil');
        Route::get('/domain/satislar', [\App\Http\Controllers\Admin\DomainController::class, 'satislar'])->name('domain.satislar.index');

        /*
         * NOT (11.08.2026): Buradaki 5 "Rehber" yönlendirme route'u silindi.
         * Modül CRM'e taşınmıştı ve bunlar sadece redirect yapıyordu; üstelik
         * `admin.` grubu içinde tekrar `admin.rehber.*` diye isimlendirildikleri
         * için gerçek isimleri `admin.admin.rehber.*` olmuştu — yani hiçbir
         * blade zaten bunlara erişemiyordu. Yerine: admin.crm.musteriler.*
         */
        // ════════════════════════════════════════════════════════════
        // TOPLU MESAJ (Mail + SMS) MODÜLÜ
        // ════════════════════════════════════════════════════════════
        Route::prefix('toplu-mesaj')->name('toplu-mesaj.')->group(function () {
            // Ana sayfa (tab'lı UI: Mail / SMS / Şablonlar / Geçmiş)
            Route::get('/', [\App\Http\Controllers\Admin\TopluMesajController::class, 'index'])
                ->name('index');

            // Mail gönder
            Route::post('/mail-gonder', [\App\Http\Controllers\Admin\TopluMesajController::class, 'sendMail'])
                ->name('mail.gonder');

            // SMS gönder
            Route::post('/sms-gonder', [\App\Http\Controllers\Admin\TopluMesajController::class, 'sendSms'])
                ->name('sms.gonder');

            // Reklam / Kampanya gönder (izinli üyelere) — Madde 8
            Route::post('/reklam-gonder', [\App\Http\Controllers\Admin\TopluMesajController::class, 'sendReklam'])
                ->name('reklam.gonder');

            // Mail şablonu canlı önizleme
            Route::match(['get', 'post'], '/onizleme', [\App\Http\Controllers\Admin\TopluMesajController::class, 'onizleme'])
                ->name('onizleme');

            // Mail/Reklam mesajına görsel yükle (AJAX)
            Route::post('/gorsel-yukle', [\App\Http\Controllers\Admin\TopluMesajController::class, 'gorselYukle'])
                ->name('gorsel.yukle');

            // Şablon yönetimi
            Route::post('/sablon-kaydet', [\App\Http\Controllers\Admin\TopluMesajController::class, 'sablonKaydet'])
                ->name('sablon.kaydet');
            Route::post('/sablon/{id}/guncelle', [\App\Http\Controllers\Admin\TopluMesajController::class, 'sablonGuncelle'])
                ->name('sablon.guncelle');
            Route::delete('/sablon/{id}', [\App\Http\Controllers\Admin\TopluMesajController::class, 'sablonSil'])
                ->name('sablon.sil');
            Route::get('/sablon/{id}', [\App\Http\Controllers\Admin\TopluMesajController::class, 'sablonGetir'])
                ->name('sablon.getir');

            // Alıcı listesini AJAX ile getir (filtre/manuel seçim için)
            Route::get('/alicilar', [\App\Http\Controllers\Admin\TopluMesajController::class, 'alicilar'])
                ->name('alicilar');
        });

        // ════════════════════════════════════════════════════════════
        // TÜRKİYE GEO (il/ilçe/mahalle kademeli dropdown JSON uçları) — Konum Sistemi
        // ════════════════════════════════════════════════════════════
        Route::get('/geo/iller', [\App\Http\Controllers\Admin\GeoController::class, 'iller'])->name('geo.iller');
        Route::get('/geo/ilceler', [\App\Http\Controllers\Admin\GeoController::class, 'ilceler'])->name('geo.ilceler');
        Route::get('/geo/mahalleler', [\App\Http\Controllers\Admin\GeoController::class, 'mahalleler'])->name('geo.mahalleler');

        // ════════════════════════════════════════════════════════════
        // TAKVİM (Madde 5) — tüm yöneticiler görebilir
        // ════════════════════════════════════════════════════════════
        Route::get('/takvim', [\App\Http\Controllers\Admin\TakvimController::class, 'index'])->name('takvim.index');
        Route::get('/takvim/etkinlikler', [\App\Http\Controllers\Admin\TakvimController::class, 'events'])->name('takvim.events');
        Route::post('/takvim', [\App\Http\Controllers\Admin\TakvimController::class, 'store'])->name('takvim.store');
        Route::post('/takvim/{id}/guncelle', [\App\Http\Controllers\Admin\TakvimController::class, 'update'])->name('takvim.update');
        Route::post('/takvim/{id}/tasi', [\App\Http\Controllers\Admin\TakvimController::class, 'tasi'])->name('takvim.tasi');
        Route::delete('/takvim/{id}', [\App\Http\Controllers\Admin\TakvimController::class, 'destroy'])->name('takvim.destroy');

        // Randevu Yönetimi
        Route::get('/randevu/randevular',   [\App\Http\Controllers\Admin\RandevuController::class, 'randevular'])->name('randevu.randevular');
        Route::get('/randevu/events',       [\App\Http\Controllers\Admin\RandevuController::class, 'events'])->name('randevu.events');
        Route::post('/randevu/kaydet',      [\App\Http\Controllers\Admin\RandevuController::class, 'kaydet'])->name('randevu.kaydet');
        Route::post('/randevu/{id}/tasi',   [\App\Http\Controllers\Admin\RandevuController::class, 'tasi'])->name('randevu.tasi');
        Route::delete('/randevu/{id}',      [\App\Http\Controllers\Admin\RandevuController::class, 'sil'])->name('randevu.sil');
        Route::get('/randevu/hizmetler',              [\App\Http\Controllers\Admin\RandevuController::class, 'hizmetler'])->name('randevu.hizmetler');
        Route::get('/randevu/hizmetler/ekle',         [\App\Http\Controllers\Admin\RandevuController::class, 'hizmetForm'])->name('randevu.hizmet-ekle');
        Route::get('/randevu/hizmetler/{id}/duzenle', [\App\Http\Controllers\Admin\RandevuController::class, 'hizmetForm'])->name('randevu.hizmet-duzenle');
        Route::post('/randevu/hizmetler/kaydet',      [\App\Http\Controllers\Admin\RandevuController::class, 'hizmetKaydet'])->name('randevu.hizmet-kaydet');
        Route::delete('/randevu/hizmetler/{id}',      [\App\Http\Controllers\Admin\RandevuController::class, 'hizmetSil'])->name('randevu.hizmet-sil');
        Route::get('/randevu/musteriler',              [\App\Http\Controllers\Admin\RandevuController::class, 'musteriler'])->name('randevu.musteriler');
        Route::get('/randevu/musteriler/ekle',         [\App\Http\Controllers\Admin\RandevuController::class, 'musteriForm'])->name('randevu.musteri-ekle');
        Route::get('/randevu/musteriler/{id}/duzenle', [\App\Http\Controllers\Admin\RandevuController::class, 'musteriForm'])->name('randevu.musteri-duzenle');
        Route::post('/randevu/musteriler/kaydet',      [\App\Http\Controllers\Admin\RandevuController::class, 'musteriKaydet'])->name('randevu.musteri-kaydet');
        Route::delete('/randevu/musteriler/{id}',      [\App\Http\Controllers\Admin\RandevuController::class, 'musteriSil'])->name('randevu.musteri-sil');
        Route::get('/randevu/calisanlar',              [\App\Http\Controllers\Admin\RandevuController::class, 'calisanlar'])->name('randevu.calisanlar');
        Route::get('/randevu/calisanlar/ekle',         [\App\Http\Controllers\Admin\RandevuController::class, 'calisanForm'])->name('randevu.calisan-ekle');
        Route::get('/randevu/calisanlar/{id}/duzenle', [\App\Http\Controllers\Admin\RandevuController::class, 'calisanForm'])->name('randevu.calisan-duzenle');
        Route::post('/randevu/calisanlar/kaydet',      [\App\Http\Controllers\Admin\RandevuController::class, 'calisanKaydet'])->name('randevu.calisan-kaydet');
        Route::delete('/randevu/calisanlar/{id}',      [\App\Http\Controllers\Admin\RandevuController::class, 'calisanSil'])->name('randevu.calisan-sil');
        Route::get('/randevu/ayin-elemani', [\App\Http\Controllers\Admin\RandevuController::class, 'ayinElemani'])->name('randevu.ayin-elemani');
        Route::post('/randevu/ayin-elemani/iyi-dilek',         [\App\Http\Controllers\Admin\RandevuController::class, 'iyiDilekVer'])->name('randevu.iyi-dilek-ver');
        Route::delete('/randevu/ayin-elemani/iyi-dilek/{id}',  [\App\Http\Controllers\Admin\RandevuController::class, 'iyiDilekSil'])->name('randevu.iyi-dilek-sil');
        Route::post('/randevu/ayin-elemani/puan-ayar',         [\App\Http\Controllers\Admin\RandevuController::class, 'puanAyarKaydet'])->name('randevu.puan-ayar-kaydet');
        Route::post('/randevu/ayin-elemani/puan-ayar/sifirla', [\App\Http\Controllers\Admin\RandevuController::class, 'puanAyarSifirla'])->name('randevu.puan-ayar-sifirla');
        Route::get('/randevu/degerlendirmeler', [\App\Http\Controllers\Admin\RandevuDegerlendirmeController::class, 'index'])->name('randevu.degerlendirmeler');

        // ════════════════════════════════════════════════════════════
        // DATA CENTER
        // ════════════════════════════════════════════════════════════
        Route::get('/data-center', [\App\Http\Controllers\Admin\DataCenterController::class, 'index'])->name('data-center.index');
        Route::post('/data-center/kisi-kaydet',            [\App\Http\Controllers\Admin\DataCenterController::class, 'kisiKaydet'])->name('data-center.kisi-kaydet');
        Route::post('/data-center/musteri/{id}/aktife-al', [\App\Http\Controllers\Admin\DataCenterController::class, 'musteriAktifeAl'])->name('data-center.musteri-aktif');
        Route::post('/data-center/musteri/{id}/arsivle',   [\App\Http\Controllers\Admin\DataCenterController::class, 'musteriArsivle'])->name('data-center.musteri-arsivle');
        Route::post('/data-center/toplu-mail', [\App\Http\Controllers\Admin\DataCenterController::class, 'topluMail'])->name('data-center.toplu-mail');
        Route::post('/data-center/toplu-sms',  [\App\Http\Controllers\Admin\DataCenterController::class, 'topluSms'])->name('data-center.toplu-sms');
        Route::post('/data-center/ice-aktar',  [\App\Http\Controllers\Admin\DataCenterController::class, 'iceAktar'])->name('data-center.ice-aktar');

        // Data Center — LİSTELER (yeni modül)
        Route::get('/data-center/listeler',          [\App\Http\Controllers\Admin\DataCenterController::class, 'listeler'])->name('data-center.listeler');
        Route::post('/data-center/listeler',         [\App\Http\Controllers\Admin\DataCenterController::class, 'listeKaydet'])->name('data-center.liste-kaydet');
        Route::put('/data-center/listeler/{id}',     [\App\Http\Controllers\Admin\DataCenterController::class, 'listeGuncelle'])->name('data-center.liste-guncelle');
        Route::delete('/data-center/listeler/{id}',  [\App\Http\Controllers\Admin\DataCenterController::class, 'listeSil'])->name('data-center.liste-sil');
        Route::post('/data-center/listeye-ata',      [\App\Http\Controllers\Admin\DataCenterController::class, 'listeyeAta'])->name('data-center.listeye-ata');

        // ════════════════════════════════════════════════════════════
        // ENGELLENEN KULLANICILAR YÖNETİMİ
        // ════════════════════════════════════════════════════════════
        Route::prefix('engellenenler')->name('engellenenler.')->group(function () {
            // Liste
            Route::get('/', [\App\Http\Controllers\Admin\EngellenenlerController::class, 'index'])
                ->name('index');

            // Toplu engel kaldır (önce - özel route)
            Route::post('/toplu-aktive', [\App\Http\Controllers\Admin\EngellenenlerController::class, 'topluAktiveEt'])
                ->name('toplu-aktive');

            // Toplu sil (önce - özel route)
            Route::delete('/toplu-sil', [\App\Http\Controllers\Admin\EngellenenlerController::class, 'topluSil'])
                ->name('toplu-sil');

            // Tek kullanıcı engeli kaldır
            Route::post('/{id}/aktive', [\App\Http\Controllers\Admin\EngellenenlerController::class, 'aktiveEt'])
                ->name('aktive')
                ->where('id', '[0-9]+');

            // Aktifi engelle (manuel)
            Route::post('/{id}/engelle', [\App\Http\Controllers\Admin\EngellenenlerController::class, 'engelle'])
                ->name('engelle')
                ->where('id', '[0-9]+');

            // Tek kullanıcı sil
            Route::delete('/{id}', [\App\Http\Controllers\Admin\EngellenenlerController::class, 'sil'])
                ->name('sil')
                ->where('id', '[0-9]+');
        });

        // ════════════════════════════════════════════════════════════
        // ADMIN BİLDİRİMLER
        // ════════════════════════════════════════════════════════════
        Route::prefix('bildirimler')->name('bildirimler.')->group(function () {
            Route::get('/', [\App\Http\Controllers\Admin\AdminBildirimController::class, 'index'])
                ->name('index');
            Route::get('/sayim', [\App\Http\Controllers\Admin\AdminBildirimController::class, 'sayim'])
                ->name('sayim');
            Route::post('/hepsini-oku', [\App\Http\Controllers\Admin\AdminBildirimController::class, 'hepsiniOku'])
                ->name('hepsini-oku');
            Route::delete('/hepsini-sil', [\App\Http\Controllers\Admin\AdminBildirimController::class, 'hepsiniSil'])
                ->name('hepsini-sil');
            Route::post('/{id}/okundu', [\App\Http\Controllers\Admin\AdminBildirimController::class, 'okundu'])
                ->name('okundu')
                ->where('id', '[0-9]+');
            Route::delete('/{id}', [\App\Http\Controllers\Admin\AdminBildirimController::class, 'sil'])
                ->name('sil')
                ->where('id', '[0-9]+');
        });

        // ════════════════════════════════════════════════════════════
        // DUYURULAR (Madde 7) — yöneticiden yöneticiye duyuru
        // ════════════════════════════════════════════════════════════
        Route::prefix('duyurular')->name('duyurular.')->group(function () {
            Route::get('/', [\App\Http\Controllers\Admin\DuyuruController::class, 'index'])->name('index');
            Route::get('/olustur', [\App\Http\Controllers\Admin\DuyuruController::class, 'olustur'])->name('olustur');
            Route::post('/', [\App\Http\Controllers\Admin\DuyuruController::class, 'store'])->name('store');
            Route::get('/{id}', [\App\Http\Controllers\Admin\DuyuruController::class, 'goster'])->name('goster')->where('id', '[0-9]+');
            Route::delete('/{id}', [\App\Http\Controllers\Admin\DuyuruController::class, 'sil'])->name('sil')->where('id', '[0-9]+');
        });


        // ════════════════════════════════════════════════════════════
        // HARCAMALAR / GİDERLER (Madde 10) — şirket gider takibi
        // ════════════════════════════════════════════════════════════
        Route::prefix('giderler')->name('giderler.')->group(function () {
            Route::get('/', [\App\Http\Controllers\Admin\GiderController::class, 'index'])->name('index');
            Route::get('/excel', [\App\Http\Controllers\Admin\GiderController::class, 'excelAktar'])->name('excel');
            Route::get('/tablolara-aktar', [\App\Http\Controllers\Admin\GiderController::class, 'tablolaraAktar'])->name('tablolara-aktar');

            Route::get('/olustur', [\App\Http\Controllers\Admin\GiderController::class, 'olustur'])->name('olustur');
            Route::post('/', [\App\Http\Controllers\Admin\GiderController::class, 'store'])->name('store');
            Route::get('/{id}/duzenle', [\App\Http\Controllers\Admin\GiderController::class, 'duzenle'])->name('duzenle')->where('id', '[0-9]+');
            Route::put('/{id}', [\App\Http\Controllers\Admin\GiderController::class, 'guncelle'])->name('guncelle')->where('id', '[0-9]+');
            Route::post('/{id}/durum', [\App\Http\Controllers\Admin\GiderController::class, 'durumDegistir'])->name('durum')->where('id', '[0-9]+');
            Route::delete('/{id}', [\App\Http\Controllers\Admin\GiderController::class, 'sil'])->name('sil')->where('id', '[0-9]+');

            // Envanter (Harcamalar sayfasında tab)
            Route::get('/envanter', [\App\Http\Controllers\Admin\GiderController::class, 'envanterIndex'])->name('envanter');
            Route::get('/envanter/olustur', [\App\Http\Controllers\Admin\GiderController::class, 'envanterOlustur'])->name('envanter.olustur');
            Route::post('/envanter', [\App\Http\Controllers\Admin\GiderController::class, 'envanterStore'])->name('envanter.store');
            Route::get('/envanter/{id}/duzenle', [\App\Http\Controllers\Admin\GiderController::class, 'envanterDuzenle'])->name('envanter.duzenle')->where('id', '[0-9]+');
            Route::put('/envanter/{id}', [\App\Http\Controllers\Admin\GiderController::class, 'envanterGuncelle'])->name('envanter.guncelle')->where('id', '[0-9]+');
            Route::delete('/envanter/{id}', [\App\Http\Controllers\Admin\GiderController::class, 'envanterSil'])->name('envanter.sil')->where('id', '[0-9]+');
        });

        // ════════════════════════════════════════════════════════════
        // AYLIK BİLDİRİMLİ ÖDEMELER — periyodik ödeme + hatırlatma (Muhasebe)
        // ════════════════════════════════════════════════════════════
        Route::prefix('aylik-odemeler')->name('aylik-odemeler.')->group(function () {
            Route::get('/', [\App\Http\Controllers\Admin\AylikOdemeController::class, 'index'])->name('index');
            Route::get('/olustur', [\App\Http\Controllers\Admin\AylikOdemeController::class, 'olustur'])->name('olustur');
            Route::post('/', [\App\Http\Controllers\Admin\AylikOdemeController::class, 'store'])->name('store');
            Route::get('/{id}/duzenle', [\App\Http\Controllers\Admin\AylikOdemeController::class, 'duzenle'])->name('duzenle')->where('id', '[0-9]+');
            Route::put('/{id}', [\App\Http\Controllers\Admin\AylikOdemeController::class, 'guncelle'])->name('guncelle')->where('id', '[0-9]+');
            Route::post('/{id}/durum', [\App\Http\Controllers\Admin\AylikOdemeController::class, 'durumDegistir'])->name('durum')->where('id', '[0-9]+');
            Route::delete('/{id}', [\App\Http\Controllers\Admin\AylikOdemeController::class, 'sil'])->name('sil')->where('id', '[0-9]+');
        });

        // ════════════════════════════════════════════════════════════
        // AYLIK BİLDİRİMLİ ALACAKLAR (görev #217/6) — ödemelerin alacak tarafı
        // ════════════════════════════════════════════════════════════
        Route::prefix('aylik-alacaklar')->name('aylik-alacaklar.')->group(function () {
            Route::get('/', [\App\Http\Controllers\Admin\AylikAlacakController::class, 'index'])->name('index');
            Route::get('/olustur', [\App\Http\Controllers\Admin\AylikAlacakController::class, 'olustur'])->name('olustur');
            Route::post('/', [\App\Http\Controllers\Admin\AylikAlacakController::class, 'store'])->name('store');
            Route::get('/{id}/duzenle', [\App\Http\Controllers\Admin\AylikAlacakController::class, 'duzenle'])->name('duzenle')->where('id', '[0-9]+');
            Route::put('/{id}', [\App\Http\Controllers\Admin\AylikAlacakController::class, 'guncelle'])->name('guncelle')->where('id', '[0-9]+');
            Route::post('/{id}/durum', [\App\Http\Controllers\Admin\AylikAlacakController::class, 'durumDegistir'])->name('durum')->where('id', '[0-9]+');
            Route::delete('/{id}', [\App\Http\Controllers\Admin\AylikAlacakController::class, 'sil'])->name('sil')->where('id', '[0-9]+');
        });

        /* ═══ TEMİZLİK KONTROL (17.08.2026) ═══ */
        Route::prefix('temizlik')->name('temizlik.')->group(function () {
            Route::get('/', [\App\Http\Controllers\Admin\TemizlikKontrolController::class, 'index'])->name('index');
            Route::post('/baslat', [\App\Http\Controllers\Admin\TemizlikKontrolController::class, 'baslat'])->name('baslat');
            Route::get('/maddeler', [\App\Http\Controllers\Admin\TemizlikKontrolController::class, 'maddeler'])->name('maddeler');
            Route::post('/maddeler', [\App\Http\Controllers\Admin\TemizlikKontrolController::class, 'maddeEkle'])->name('madde.ekle');
            Route::post('/maddeler/{id}', [\App\Http\Controllers\Admin\TemizlikKontrolController::class, 'maddeGuncelle'])->name('madde.guncelle')->where('id', '[0-9]+');
            Route::delete('/maddeler/{id}', [\App\Http\Controllers\Admin\TemizlikKontrolController::class, 'maddeSil'])->name('madde.sil')->where('id', '[0-9]+');
            Route::get('/{id}', [\App\Http\Controllers\Admin\TemizlikKontrolController::class, 'goster'])->name('goster')->where('id', '[0-9]+');
            Route::post('/{id}/isaretle/{detayId}', [\App\Http\Controllers\Admin\TemizlikKontrolController::class, 'isaretle'])->name('isaretle')->where(['id' => '[0-9]+', 'detayId' => '[0-9]+']);
            Route::post('/{id}/not', [\App\Http\Controllers\Admin\TemizlikKontrolController::class, 'notKaydet'])->name('not')->where('id', '[0-9]+');
            Route::delete('/{id}', [\App\Http\Controllers\Admin\TemizlikKontrolController::class, 'sil'])->name('sil')->where('id', '[0-9]+');
        });

        /* ═══ BİLDİRİM MERKEZİ — tüm hatırlatmalar tek ekranda ═══ */
        Route::get('/bildirim-merkezi', [\App\Http\Controllers\Admin\BildirimMerkeziController::class, 'index'])
            ->name('bildirim-merkezi');

        /* ═══ DN BANK — KREDİ TALEPLERİ (admin onay ekranı, 07.08.2026) ═══ */
        Route::prefix('dnbank-krediler')->name('dnbank-krediler.')->group(function () {
            Route::get('/', [\App\Http\Controllers\Admin\DnBankKrediController::class, 'index'])->name('index');
            Route::get('/{id}', [\App\Http\Controllers\Admin\DnBankKrediController::class, 'goster'])->name('goster')->where('id', '[0-9]+');
            Route::post('/{id}/onayla', [\App\Http\Controllers\Admin\DnBankKrediController::class, 'onayla'])->name('onayla')->where('id', '[0-9]+');
            Route::post('/{id}/reddet', [\App\Http\Controllers\Admin\DnBankKrediController::class, 'reddet'])->name('reddet')->where('id', '[0-9]+');
        });


        // ════════════════════════════════════════════════════════════
        // AYIN İŞ ORTAKLARI (Madde 6) — müşteri ciro raporu
        // ════════════════════════════════════════════════════════════
        Route::get('/is-ortaklari', [\App\Http\Controllers\Admin\IsOrtaklariController::class, 'index'])->name('is-ortaklari.index');
        // Günlük Raporlar — Tüm Hareketler (gelir/gider) + elle mail gönderimi
        Route::get('/raporlar/gunluk', [\App\Http\Controllers\Admin\GunlukRaporController::class, 'index'])->name('raporlar.gunluk');
        Route::post('/raporlar/gunluk/gonder', [\App\Http\Controllers\Admin\GunlukRaporController::class, 'gonder'])->name('raporlar.gunluk.gonder');
        // Gün Sonu Raporu — CRM + personel aktivite özeti + elle mail gönderimi
        Route::get('/raporlar/gun-sonu', [\App\Http\Controllers\Admin\GunSonuRaporController::class, 'index'])->name('raporlar.gun-sonu');
        Route::post('/raporlar/gun-sonu/gonder', [\App\Http\Controllers\Admin\GunSonuRaporController::class, 'gonder'])->name('raporlar.gun-sonu.gonder');

        /* ═══ VERİ ARAÇLARI — SSH olmadan bakım komutları (31.07.2026) ═══ */
        Route::get('/bakim/veri-araclari', [\App\Http\Controllers\Admin\VeriAraclariController::class, 'index'])->name('bakim.veri-araclari');
        Route::post('/bakim/veri-araclari', [\App\Http\Controllers\Admin\VeriAraclariController::class, 'calistir'])
            ->middleware('throttle:20,10')->name('bakim.veri-araclari.calistir');

        Route::get('/gelir-gider', [\App\Http\Controllers\Admin\GelirGiderController::class, 'index'])->name('gelir-gider.index');
        // Standart aylık kalemler (manuel — bağımsız, mevcut hesapları etkilemez)
        Route::post('/gelir-gider/standart', [\App\Http\Controllers\Admin\GelirGiderController::class, 'standartEkle'])->name('gelir-gider.standart.ekle');
        Route::delete('/gelir-gider/standart/{id}', [\App\Http\Controllers\Admin\GelirGiderController::class, 'standartSil'])->name('gelir-gider.standart.sil')->where('id', '[0-9]+');

        // ════════════════════════════════════════════════════════════
        // BORÇ TAKİP — SGK / Vergi / Krediler (borç + ödeme takibi)
        // ════════════════════════════════════════════════════════════
        Route::get('/borc-takip', [\App\Http\Controllers\Admin\BorcTakipController::class, 'index'])->name('borc-takip.index');
        Route::post('/borc-takip', [\App\Http\Controllers\Admin\BorcTakipController::class, 'store'])->name('borc-takip.store');
        Route::put('/borc-takip/{id}', [\App\Http\Controllers\Admin\BorcTakipController::class, 'update'])->name('borc-takip.update')->where('id', '[0-9]+');
        Route::delete('/borc-takip/{id}', [\App\Http\Controllers\Admin\BorcTakipController::class, 'destroy'])->name('borc-takip.destroy')->where('id', '[0-9]+');

        // ════════════════════════════════════════════════════════════
        // MÜŞTERİ BORÇ TAKİP — müşteri bazlı cari (borç + ödeme takibi)
        // ════════════════════════════════════════════════════════════
        Route::get('/musteri-borc', [\App\Http\Controllers\Admin\MusteriBorcController::class, 'index'])->name('musteri-borc.index');
        Route::get('/musteri-borc/{musteri}', [\App\Http\Controllers\Admin\MusteriBorcController::class, 'goster'])->name('musteri-borc.goster')->where('musteri', '[0-9]+');
        Route::post('/musteri-borc', [\App\Http\Controllers\Admin\MusteriBorcController::class, 'store'])->name('musteri-borc.store');
        Route::put('/musteri-borc/{id}', [\App\Http\Controllers\Admin\MusteriBorcController::class, 'update'])->name('musteri-borc.update')->where('id', '[0-9]+');
        Route::delete('/musteri-borc/{id}', [\App\Http\Controllers\Admin\MusteriBorcController::class, 'destroy'])->name('musteri-borc.destroy')->where('id', '[0-9]+');

        // ════════════════════════════════════════════════════════════
        // HRM (İnsan Kaynakları) — personel, izin, özlük dosyaları
        // ════════════════════════════════════════════════════════════
        Route::prefix('hrm')->name('hrm.')->group(function () {
            // Personel
            Route::prefix('personel')->name('personel.')->group(function () {
                Route::get('/', [\App\Http\Controllers\Admin\Hrm\PersonelController::class, 'index'])->name('index');
                Route::get('/{id}', [\App\Http\Controllers\Admin\Hrm\PersonelController::class, 'goster'])->name('goster')->where('id', '[0-9]+');
                Route::post('/{id}/ozluk', [\App\Http\Controllers\Admin\Hrm\PersonelController::class, 'ozlukKaydet'])->name('ozluk')->where('id', '[0-9]+');
                Route::post('/{id}/dosya', [\App\Http\Controllers\Admin\Hrm\PersonelController::class, 'dosyaYukle'])->name('dosya.yukle')->where('id', '[0-9]+');
                Route::delete('/{id}/dosya/{dosyaId}', [\App\Http\Controllers\Admin\Hrm\PersonelController::class, 'dosyaSil'])->name('dosya.sil')->where(['id'=>'[0-9]+','dosyaId'=>'[0-9]+']);
            });
            // İzin talepleri
            Route::prefix('izin')->name('izin.')->group(function () {
                Route::get('/', [\App\Http\Controllers\Admin\Hrm\IzinController::class, 'index'])->name('index');
                Route::get('/olustur', [\App\Http\Controllers\Admin\Hrm\IzinController::class, 'olustur'])->name('olustur');
                Route::post('/', [\App\Http\Controllers\Admin\Hrm\IzinController::class, 'store'])->name('store');
                Route::post('/{id}/onayla', [\App\Http\Controllers\Admin\Hrm\IzinController::class, 'onayla'])->name('onayla')->where('id', '[0-9]+');
                Route::post('/{id}/reddet', [\App\Http\Controllers\Admin\Hrm\IzinController::class, 'reddet'])->name('reddet')->where('id', '[0-9]+');
                Route::delete('/{id}', [\App\Http\Controllers\Admin\Hrm\IzinController::class, 'sil'])->name('sil')->where('id', '[0-9]+');
            });
            // Anlik durum bildirimi -- herkes birbirini gorur, herkes
            // kendi durumunu degistirir; yoneticiler baskasininkini de.
            Route::prefix('durum')->name('durum.')->group(function () {
                Route::get('/', [\App\Http\Controllers\Admin\Hrm\DurumController::class, 'index'])->name('index');
                // Gecmis raporu -- yalnizca OKUMA, tarih araligi + kisi suzgeci
                Route::get('/gecmis', [\App\Http\Controllers\Admin\Hrm\DurumController::class, 'gecmis'])->name('gecmis');
                Route::post('/degistir', [\App\Http\Controllers\Admin\Hrm\DurumController::class, 'degistir'])->name('degistir');
                Route::post('/tip', [\App\Http\Controllers\Admin\Hrm\DurumController::class, 'tipEkle'])->name('tip.ekle');
                Route::post('/tip/{id}', [\App\Http\Controllers\Admin\Hrm\DurumController::class, 'tipGuncelle'])->name('tip.guncelle')->where('id', '[0-9]+');
                Route::delete('/tip/{id}', [\App\Http\Controllers\Admin\Hrm\DurumController::class, 'tipSil'])->name('tip.sil')->where('id', '[0-9]+');
                Route::post('/alicilar', [\App\Http\Controllers\Admin\Hrm\DurumController::class, 'alicilar'])->name('alicilar');
            });

            // Gun plani -- saatli program. Kisi kendi gununu planlar,
            // yoneticiler herkesinkini gorur ve duzenler.
            Route::prefix('plan')->name('plan.')->group(function () {
                Route::get('/', [\App\Http\Controllers\Admin\Hrm\PlanController::class, 'index'])->name('index');
                Route::post('/', [\App\Http\Controllers\Admin\Hrm\PlanController::class, 'ekle'])->name('ekle');
                Route::post('/kopyala', [\App\Http\Controllers\Admin\Hrm\PlanController::class, 'kopyala'])->name('kopyala');
                Route::post('/{id}/isaretle', [\App\Http\Controllers\Admin\Hrm\PlanController::class, 'isaretle'])->name('isaretle')->where('id', '[0-9]+');
                Route::delete('/{id}', [\App\Http\Controllers\Admin\Hrm\PlanController::class, 'sil'])->name('sil')->where('id', '[0-9]+');
            });

            // Avans talepleri
            Route::prefix('avans')->name('avans.')->group(function () {
                Route::get('/', [\App\Http\Controllers\Admin\Hrm\AvansController::class, 'index'])->name('index');
                Route::get('/olustur', [\App\Http\Controllers\Admin\Hrm\AvansController::class, 'olustur'])->name('olustur');
                Route::post('/', [\App\Http\Controllers\Admin\Hrm\AvansController::class, 'store'])->name('store');
                Route::post('/{id}/onayla', [\App\Http\Controllers\Admin\Hrm\AvansController::class, 'onayla'])->name('onayla')->where('id', '[0-9]+');
                Route::post('/{id}/reddet', [\App\Http\Controllers\Admin\Hrm\AvansController::class, 'reddet'])->name('reddet')->where('id', '[0-9]+');
                Route::delete('/{id}', [\App\Http\Controllers\Admin\Hrm\AvansController::class, 'sil'])->name('sil')->where('id', '[0-9]+');
            });
        });

        // ============ Geri uyumluluk: admin.bildirim.* alias'ları (29 Mayıs 2026 fix) ============
        // Blade'lerde admin.bildirim.okundu / admin.bildirim.sil kullanılıyor (tekil)
        // Group altında admin.bildirimler.* (çoğul) tanımlandığı için bu alias'lar lazım
        Route::post('/bildirimler/{id}/okundu', [\App\Http\Controllers\Admin\AdminBildirimController::class, 'okundu'])
            ->name('bildirim.okundu')->where('id', '[0-9]+');
        Route::delete('/bildirimler/{id}', [\App\Http\Controllers\Admin\AdminBildirimController::class, 'sil'])
            ->name('bildirim.sil')->where('id', '[0-9]+');

        // NOT (11.08.2026): '/rehber/bildirim-sablonlari' yönlendirme route'u
        // silindi — yukarıdaki diğer 5 Rehber route'uyla aynı gerekçe.

        // Ürün Yönetimi
        Route::get('/urunler', [\App\Http\Controllers\Admin\UrunController::class, 'index'])->name('urunler.index');
        Route::get('/urunler/ekle', [\App\Http\Controllers\Admin\UrunController::class, 'ekle'])->name('urunler.ekle');
        Route::post('/urunler/ekle', [\App\Http\Controllers\Admin\UrunController::class, 'eklePost'])->name('urunler.eklePost');
        Route::get('/urunler/{id}/duzenle', [\App\Http\Controllers\Admin\UrunController::class, 'duzenle'])->name('urunler.duzenle');
        Route::post('/urunler/{id}/duzenle', [\App\Http\Controllers\Admin\UrunController::class, 'duzenlePost'])->name('urunler.duzenlePost');
        Route::delete('/urunler/{id}', [\App\Http\Controllers\Admin\UrunController::class, 'sil'])->name('urunler.sil');

        // Hizmet Yönetimi
        Route::get('/hizmetler', [\App\Http\Controllers\Admin\HizmetController::class, 'index'])->name('hizmetler.index');
        Route::get('/hizmetler/ekle', [\App\Http\Controllers\Admin\HizmetController::class, 'ekle'])->name('hizmetler.ekle');
        Route::post('/hizmetler/ekle', [\App\Http\Controllers\Admin\HizmetController::class, 'eklePost'])->name('hizmetler.eklePost');
        Route::get('/hizmetler/{id}/duzenle', [\App\Http\Controllers\Admin\HizmetController::class, 'duzenle'])->name('hizmetler.duzenle');
        Route::post('/hizmetler/{id}/duzenle', [\App\Http\Controllers\Admin\HizmetController::class, 'duzenlePost'])->name('hizmetler.duzenlePost');
        Route::delete('/hizmetler/{id}', [\App\Http\Controllers\Admin\HizmetController::class, 'sil'])->name('hizmetler.sil');

        // E-Bülten
        Route::get('/ebulten', [\App\Http\Controllers\Admin\EBultenController::class, 'index'])->name('ebulten.index');
        Route::delete('/ebulten/{id}', [\App\Http\Controllers\Admin\EBultenController::class, 'sil'])->name('ebulten.sil');
        Route::get('/ebulten/toplu-mail', [\App\Http\Controllers\Admin\EBultenController::class, 'topluMail'])->name('ebulten.toplu-mail');
        Route::post('/ebulten/toplu-mail', [\App\Http\Controllers\Admin\EBultenController::class, 'topluMailGonder'])->name('ebulten.toplu-mail.gonder');

        // Yorumlar
        Route::get('/yorumlar', [\App\Http\Controllers\Admin\YorumController::class, 'index'])->name('yorumlar.index');
        Route::post('/yorumlar/{id}/durum/{durum}', [\App\Http\Controllers\Admin\YorumController::class, 'durumDegistir'])->name('yorumlar.durum');
        Route::delete('/yorumlar/{id}', [\App\Http\Controllers\Admin\YorumController::class, 'sil'])->name('yorumlar.sil');

        // Ar-Ge Anketi cevapları (Rubito)
        Route::get('/arge-anketleri', [\App\Http\Controllers\Admin\ArgeAnketiController::class, 'index'])->name('arge-anketleri.index');
        Route::get('/arge-anketleri/{id}', [\App\Http\Controllers\Admin\ArgeAnketiController::class, 'goster'])->name('arge-anketleri.goster')->where('id', '[0-9]+');
        Route::delete('/arge-anketleri/{id}', [\App\Http\Controllers\Admin\ArgeAnketiController::class, 'sil'])->name('arge-anketleri.sil')->where('id', '[0-9]+');

        // İş Başvuruları (Rubito)
        Route::get('/is-basvurulari', [\App\Http\Controllers\Admin\IsBasvuruController::class, 'index'])->name('is-basvurulari.index');
        Route::get('/is-basvurulari/{id}', [\App\Http\Controllers\Admin\IsBasvuruController::class, 'goster'])->name('is-basvurulari.goster')->where('id', '[0-9]+');
        Route::delete('/is-basvurulari/{id}', [\App\Http\Controllers\Admin\IsBasvuruController::class, 'sil'])->name('is-basvurulari.sil')->where('id', '[0-9]+');

        // İş Ortağı (Bayi) Başvuruları — onay verilince hesap açılır + şifre maillenir
        Route::get('/bayi-basvurulari', [\App\Http\Controllers\Admin\BayiBasvuruController::class, 'index'])->name('bayi-basvurulari.index');
        Route::get('/bayi-basvurulari/{id}', [\App\Http\Controllers\Admin\BayiBasvuruController::class, 'goster'])->name('bayi-basvurulari.goster')->where('id', '[0-9]+');
        Route::post('/bayi-basvurulari/{id}/onayla', [\App\Http\Controllers\Admin\BayiBasvuruController::class, 'onayla'])->name('bayi-basvurulari.onayla')->where('id', '[0-9]+');
        Route::post('/bayi-basvurulari/{id}/reddet', [\App\Http\Controllers\Admin\BayiBasvuruController::class, 'reddet'])->name('bayi-basvurulari.reddet')->where('id', '[0-9]+');
        Route::delete('/bayi-basvurulari/{id}', [\App\Http\Controllers\Admin\BayiBasvuruController::class, 'sil'])->name('bayi-basvurulari.sil')->where('id', '[0-9]+');

        // Not Defteri
        Route::get('/not-defteri', [\App\Http\Controllers\Admin\NotDefteriController::class, 'index'])->name('not-defteri.index');
        // NOT (11.08.2026): 'not-defteri.hizli' route'u kaldırıldı —
        // NotDefteriController'da hizliKaydet metodu yok (index/ekle/guncelle/sil
        // var) ve arayüzden çağrılmıyordu. Not ekleme: not-defteri.ekle
        Route::post('/not-defteri/ekle', [\App\Http\Controllers\Admin\NotDefteriController::class, 'ekle'])->name('not-defteri.ekle');
        Route::post('/not-defteri/{id}/guncelle', [\App\Http\Controllers\Admin\NotDefteriController::class, 'guncelle'])->name('not-defteri.guncelle');
        Route::delete('/not-defteri/{id}', [\App\Http\Controllers\Admin\NotDefteriController::class, 'sil'])->name('not-defteri.sil');

        // Ödeme Bildirim Formu
        Route::get('/odeme-bildirim', [\App\Http\Controllers\Admin\OdemeBildirimController::class, 'index'])->name('odeme-bildirim.index');
        Route::get('/odeme-bildirim/{id}/detay', [\App\Http\Controllers\Admin\OdemeBildirimController::class, 'detay'])->name('odeme-bildirim.detay');
        Route::post('/odeme-bildirim/{id}/durum/{durum}', [\App\Http\Controllers\Admin\OdemeBildirimController::class, 'durumDegistir'])->name('odeme-bildirim.durum');
        Route::delete('/odeme-bildirim/{id}', [\App\Http\Controllers\Admin\OdemeBildirimController::class, 'sil'])->name('odeme-bildirim.sil');

        // Ayarlar
        Route::get('/ayarlar', [\App\Http\Controllers\Admin\AyarlarController::class, 'index'])->name('ayarlar.index');
        Route::post('/ayarlar/guncelle', [\App\Http\Controllers\Admin\AyarlarController::class, 'guncelle'])->name('ayarlar.guncelle');
        Route::post('/ayarlar/bakim-modu', [\App\Http\Controllers\Admin\AyarlarController::class, 'bakimModu'])->name('ayarlar.bakim.modu');

        // ========================
        // BAYİ PANELİ ROUTE'LARI
        // ========================
        Route::prefix('bayi')->name('bayi.')->middleware(['admin.auth','rol:bayi'])->group(function () {
            // Dashboard
            Route::get('/dashboard', [\App\Http\Controllers\Admin\BayiPanelController::class, 'dashboard'])->name('dashboard');

            // Satış Yönetimi
            Route::get('/satislar', [\App\Http\Controllers\Admin\BayiPanelController::class, 'satislar'])->name('satislar');
            Route::get('/satis/ekle', [\App\Http\Controllers\Admin\BayiPanelController::class, 'satisEkle'])->name('satis.ekle');
            Route::post('/satis/ekle', [\App\Http\Controllers\Admin\BayiPanelController::class, 'satisEklePost'])->name('satis.ekle.post');
            Route::get('/satis/{id}/detay', [\App\Http\Controllers\Admin\BayiPanelController::class, 'satisDetay'])->name('satis.detay');
            Route::get('/kazanclar', [\App\Http\Controllers\Admin\BayiPanelController::class, 'kazanclar'])->name('kazanclar');

            // Müşteri Yönetimi
            Route::get('/musteriler', [\App\Http\Controllers\Admin\BayiPanelController::class, 'musteriler'])->name('musteriler');
            Route::get('/musteri/ekle', [\App\Http\Controllers\Admin\BayiPanelController::class, 'musteriEkle'])->name('musteri.ekle');
            Route::post('/musteri/ekle', [\App\Http\Controllers\Admin\BayiPanelController::class, 'musteriEklePost'])->name('musteri.ekle.post');
            Route::get('/musteri/{id}/detay', [\App\Http\Controllers\Admin\BayiPanelController::class, 'musteriDetay'])->name('musteri.detay');

            // ÖNER-KAZAN (eski "Referans Linki" + "Alt Bayiler" bunun yerine geçti)
            // Referans linki ve promosyon kodu özellikleri KALDIRILDI.
            // NOT (11.08.2026): 'oner.kazan' ve 'oner.kazan.davet' route'ları
            // kaldırıldı — BayiReferansController'da onerKazan / davetGonder
            // metotları hiç yazılmamıştı (mevcut: link, istatistik, altBayiler,
            // altBayiEkle) ve arayüzden çağrılmıyorlardı. Görünüm dosyası
            // (bayi/referans/oner-kazan.blade.php) ileride kullanılmak üzere
            // duruyor. Özellik yazıldığında bu iki satır geri eklenecek.

            // Pazarlama
            Route::get('/kampanyalar', [\App\Http\Controllers\Admin\BayiPazarlamaController::class, 'kampanyalar'])->name('kampanyalar');
            Route::get('/email-sablonlar', [\App\Http\Controllers\Admin\BayiPazarlamaController::class, 'emailSablonlar'])->name('email.sablonlar');
            Route::post('/email-gonder', [\App\Http\Controllers\Admin\BayiPazarlamaController::class, 'emailGonder'])->name('email.gonder');

            // Raporlar
            Route::get('/raporlar', [\App\Http\Controllers\Admin\BayiRaporController::class, 'index'])->name('raporlar');
            Route::get('/rapor/pdf/{tip}', [\App\Http\Controllers\Admin\BayiRaporController::class, 'pdfIndir'])->name('rapor.pdf');
            Route::get('/rapor/excel/{tip}', [\App\Http\Controllers\Admin\BayiRaporController::class, 'excelIndir'])->name('rapor.excel');

            // Ödemeler
            Route::get('/odeme', [\App\Http\Controllers\Admin\BayiPanelController::class, 'odeme'])->name('odeme');
            Route::get('/odeme-talepleri', [\App\Http\Controllers\Admin\BayiPanelController::class, 'odemeTalepleri'])->name('odeme.talepleri');
            Route::get('/odeme-talep-olustur', [\App\Http\Controllers\Admin\BayiPanelController::class, 'odemeTalepOlusturForm'])->name('odeme.talep.olustur');
            Route::post('/odeme-talep-olustur', [\App\Http\Controllers\Admin\BayiPanelController::class, 'odemeTalebiOlustur'])->name('odeme.talep.olustur.post');
            // NOT (2026-06-09): Aşağıdaki 4 route'un controller metotları (gecmis, bankaHesaplari,
            // bankaHesapEkle, bankaHesapSil) hiçbir controller'da YOK → hit edilirse 500 + route:cache'i
            // kırıyordu. Özellik tamamlanınca metotları Admin\BayiOdemeController'a ekleyip aç.
            // Route::get('/odeme-gecmisi', [\App\Http\Controllers\Admin\BayiOdemeController::class, 'gecmis'])->name('odeme.gecmis');
            // Route::get('/banka-hesaplari', [\App\Http\Controllers\Admin\BayiOdemeController::class, 'bankaHesaplari'])->name('banka.hesaplari');
            // Route::post('/banka-hesap/ekle', [\App\Http\Controllers\Admin\BayiOdemeController::class, 'bankaHesapEkle'])->name('banka.hesap.ekle');
            // Route::delete('/banka-hesap/{id}', [\App\Http\Controllers\Admin\BayiOdemeController::class, 'bankaHesapSil'])->name('banka.hesap.sil');

            // Destek
            Route::get('/destek/tickets', [\App\Http\Controllers\Admin\BayiDestekController::class, 'tickets'])->name('destek.tickets');
            Route::get('/destek/ticket/olustur', [\App\Http\Controllers\Admin\BayiDestekController::class, 'ticketOlustur'])->name('destek.ticket.olustur');
            Route::post('/destek/ticket/olustur', [\App\Http\Controllers\Admin\BayiDestekController::class, 'ticketOlusturPost'])->name('destek.ticket.olustur.post');
            Route::get('/destek/ticket/{id}', [\App\Http\Controllers\Admin\BayiDestekController::class, 'ticketDetay'])->name('destek.ticket.detay');
            Route::post('/destek/ticket/{id}/cevap', [\App\Http\Controllers\Admin\BayiDestekController::class, 'ticketCevap'])->name('destek.ticket.cevap');
            Route::get('/sss', [\App\Http\Controllers\Admin\BayiDestekController::class, 'sss'])->name('sss');

            // Bildirimler
            Route::get('/bildirimler', [\App\Http\Controllers\Admin\BayiBildirimController::class, 'index'])->name('bildirimler');
            Route::post('/bildirim/{id}/okundu', [\App\Http\Controllers\Admin\BayiBildirimController::class, 'okundu'])->name('bildirim.okundu');
            Route::post('/bildirimler/hepsini-oku', [\App\Http\Controllers\Admin\BayiBildirimController::class, 'hepsiniOku'])->name('bildirimler.hepsini.oku');


            // Ayarlar
            Route::get('/profil', [\App\Http\Controllers\Admin\BayiPanelController::class, 'profil'])->name('profil');
            Route::post('/profil', [\App\Http\Controllers\Admin\BayiPanelController::class, 'profilGuncelle'])->name('profil.guncelle');
            Route::get('/guvenlik', [\App\Http\Controllers\Admin\BayiAyarController::class, 'guvenlik'])->name('guvenlik');
            Route::post('/sifre-degistir', [\App\Http\Controllers\Admin\BayiAyarController::class, 'sifreDegistir'])->name('sifre.degistir');
            Route::post('/2fa-aktif', [\App\Http\Controllers\Admin\BayiAyarController::class, 'ikiFactorAktif'])->name('2fa.aktif');
            Route::get('/bildirim-ayarlari', [\App\Http\Controllers\Admin\BayiAyarController::class, 'bildirimAyarlari'])->name('bildirim.ayarlari');
            Route::post('/bildirim-ayarlari', [\App\Http\Controllers\Admin\BayiAyarController::class, 'bildirimAyarlariGuncelle'])->name('bildirim.ayarlari.guncelle');

        });
        // ═══ HİZMET FİYATLARI ═══
            Route::get('/hizmet-fiyatlari', [\App\Http\Controllers\Admin\HizmetFiyatlariController::class, 'index'])->name('hizmet-fiyatlari.index');
            Route::post('/hizmet-fiyatlari', [\App\Http\Controllers\Admin\HizmetFiyatlariController::class, 'update'])->name('hizmet-fiyatlari.update');
            Route::delete('/hizmet-fiyatlari/{id}', [\App\Http\Controllers\Admin\HizmetFiyatlariController::class, 'destroy'])->name('hizmet-fiyatlari.destroy');

            // ═══ TABLOLAR (Spreadsheet) ═══
            Route::prefix('tablolar/kilit')->name('tablolar.kilit.')->group(function () {
                Route::get('/kurulum', [\App\Http\Controllers\Admin\TablolarKilitController::class, 'kurulum'])->name('kurulum');
                Route::post('/kurulum', [\App\Http\Controllers\Admin\TablolarKilitController::class, 'kurulumKaydet'])->name('kurulum.kaydet');
                Route::get('/pin', [\App\Http\Controllers\Admin\TablolarKilitController::class, 'pinEkrani'])->name('pin');
                Route::post('/pin', [\App\Http\Controllers\Admin\TablolarKilitController::class, 'pinDogrula'])->name('pin.dogrula');
                Route::get('/cikis', [\App\Http\Controllers\Admin\TablolarKilitController::class, 'cikis'])->name('cikis');
                Route::get('/ayarlar', [\App\Http\Controllers\Admin\TablolarKilitController::class, 'ayarlar'])->name('ayarlar');
                Route::post('/ayarlar', [\App\Http\Controllers\Admin\TablolarKilitController::class, 'ayarlarKaydet'])->name('ayarlar.kaydet');
            });

            Route::prefix('tablolar')->name('tablolar.')->middleware('tablolar.kilit')->group(function () {
                Route::get('/', [\App\Http\Controllers\Admin\SpreadsheetController::class, 'index'])->name('index');
                Route::get('/create', [\App\Http\Controllers\Admin\SpreadsheetController::class, 'create'])->name('create');
                Route::post('/', [\App\Http\Controllers\Admin\SpreadsheetController::class, 'store'])->name('store');
                Route::get('/{id}', [\App\Http\Controllers\Admin\SpreadsheetController::class, 'show'])->name('show');
                Route::get('/{id}/edit', [\App\Http\Controllers\Admin\SpreadsheetController::class, 'edit'])->name('edit');
                Route::post('/{id}/save', [\App\Http\Controllers\Admin\SpreadsheetController::class, 'save'])->name('save');
                Route::post('/{id}/toggle-autosave', [\App\Http\Controllers\Admin\SpreadsheetController::class, 'toggleAutoSave'])->name('toggle-autosave');
                Route::put('/{id}', [\App\Http\Controllers\Admin\SpreadsheetController::class, 'update'])->name('update');
                Route::delete('/{id}', [\App\Http\Controllers\Admin\SpreadsheetController::class, 'destroy'])->name('destroy');
            });
    });
});


// Test Mail Route
Route::get('/mail-test', function () {
    try {
        $testEmail = request('email', env('MAIL_FROM_ADDRESS', 'test@example.com'));

        \Illuminate\Support\Facades\Mail::raw('Bu bir test e-postasıdır. Mail ayarlarınız doğru çalışıyor!', function ($message) use ($testEmail) {
            $message->to($testEmail)
                    ->subject('Test E-postası - ' . config('app.name'));
        });

        return response('Test maili başarıyla gönderildi! E-posta adresi: ' . $testEmail, 200);
    } catch (\Exception $e) {
        return response('Mail gönderilemedi: ' . $e->getMessage(), 500);
    }
});


// ════════════════════════════════════════════════════════════
// CRON: Gider hatırlatma (günde 1 kez cPanel cron job ile çağrılır)
// Örnek cron URL: https://dev.crm.ornek.com/cron/gider-hatirlatma?key=<CRON_KEY>
// admin.auth DIŞINDA — cron giriş yapamaz, ?key ile korunur.
// ════════════════════════════════════════════════════════════
Route::get('/cron/gider-hatirlatma', [\App\Http\Controllers\Admin\GiderController::class, 'hatirlatmalariGonder'])->name('cron.gider.hatirlatma');

// CRON: Aylık bildirimli ödeme hatırlatma (günde 1 kez)
// Örnek: https://dev.crm.ornek.com/cron/aylik-odeme-hatirlatma?key=<CRON_KEY>
Route::get('/cron/aylik-odeme-hatirlatma', [\App\Http\Controllers\Admin\AylikOdemeController::class, 'hatirlatmalariGonder'])->name('cron.aylik-odeme.hatirlatma');

/* ===========================================================
 * TEMA DEMOLARI - public DISINDAKI  tema-demolar/  klasorunden servis.
 * Symlink GEREKMEZ: "tema-demolar" klasorunu Laravel kokune koy,
 * demo linkleri /temalar/<slug>/ otomatik calisir. Yedek dostu.
 * =========================================================== */
Route::get('/temalar/{path?}', function (string $path = '') {
    $base = realpath(base_path('tema-demolar'));
    if ($base === false) abort(404);
    $rel = str_replace('..', '', $path);
    $target = $base . '/' . $rel;
    if ($rel === '' || is_dir($target)) {
        $target = rtrim($target, '/') . '/index.html';
    }
    $full = realpath($target);
    if ($full === false || strncmp($full, $base, strlen($base)) !== 0 || !is_file($full)) abort(404);
    // Doğru Content-Type: response()->file() finfo ile CSS/JS'i "text/plain" görüyor;
    // site 'nosniff' başlığı yolladığı için tarayıcı text/plain stylesheet'i REDDEDİYOR.
    // Uzantıya göre doğru MIME ver.
    $ext = strtolower(pathinfo($full, PATHINFO_EXTENSION));
    $mimeMap = [
        'css' => 'text/css', 'js' => 'application/javascript', 'mjs' => 'application/javascript',
        'html' => 'text/html', 'htm' => 'text/html', 'svg' => 'image/svg+xml',
        'json' => 'application/json', 'map' => 'application/json',
        'woff' => 'font/woff', 'woff2' => 'font/woff2', 'ttf' => 'font/ttf',
        'otf' => 'font/otf', 'eot' => 'application/vnd.ms-fontobject',
        'png' => 'image/png', 'jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg',
        'gif' => 'image/gif', 'webp' => 'image/webp', 'ico' => 'image/x-icon',
        'mp4' => 'video/mp4', 'webm' => 'video/webm',
    ];
    // HTML: <base href> enjekte et. Locale middleware trailing slash'ı kırptığı için
    // (/temalar/x/ -> /temalar/x) demo içindeki relatif css/img yolları kayıyordu.
    // <base> ile tüm relatif yollar demo klasörüne sabitlenir (slash olsun olmasın çalışır).
    if ($ext === 'html' || $ext === 'htm') {
        $dir = trim(str_replace($base, '', dirname($full)), '/\\');
        $baseHref = '/temalar/' . ($dir !== '' ? str_replace('\\', '/', $dir) . '/' : '');
        $html = file_get_contents($full);
        if (stripos($html, '<base ') === false) {
            $html = preg_replace('/(<head[^>]*>)/i', '$1' . "\n    <base href=\"{$baseHref}\">", $html, 1);
        }
        return response($html, 200, ['Content-Type' => 'text/html; charset=UTF-8']);
    }
    $headers = isset($mimeMap[$ext]) ? ['Content-Type' => $mimeMap[$ext]] : [];
    return response()->file($full, $headers);
})->where('path', '.*')->name('tema.demo');
