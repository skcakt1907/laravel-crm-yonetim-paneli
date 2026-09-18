@extends('layouts.master')

@section('title', 'Ar-Ge Anketi')

@section('content')
@php
    // Ar-Ge Anketi — ornek.com/arge-anketi'nin native hâli.
    // Rubito personası korundu. Ajans adı DN KREATİF'tir — kurumsal metinlerde
    // "İş Ortağım" YAZILMAZ (İş Ortağım panelin/platformun adı, ajansın değil).
    // Düzen: sitenin standart sayfa başlığı (beyaz bant + breadcrumb, navbar'ı geçen
    // padding) + krem gövde + lemon aksan (site paleti). Ortada büyük Rubito.
    $adimlar = [
        ['tur' => 'aciklama', 'baslik' => 'Merhaba ben Rubito,',
         'metin' => 'DN Kreatif yapay zeka asistanıyım. Rubito Ar-Ge anketi ile size sunabileceğimiz hizmetleri ön analiz yapabilmesi için gerekli tüm soruları iletecektir. Lütfen eksiksiz tamamlamaya özen gösteriniz.',
         'btn' => 'Başla'],

        ['tur' => 'soru', 'name' => 'ad_soyad', 'tip' => 'text', 'zorunlu' => true,
         'baslik' => 'Adınızı ve soyadınızı öğrenebilir miyim?', 'ph' => 'Ad Soyad'],

        ['tur' => 'aciklama', 'baslik' => 'Tanıştığımıza çok memnun oldum {ad}!',
         'metin' => 'İşinizi geliştirmek için doğru yol haritasını çizmek istiyoruz. Bu sebeple şimdi gelecek olan sorulara vereceğiniz cevaplar çok önemlidir.',
         'btn' => 'Devam'],

        ['tur' => 'soru', 'name' => 'email', 'tip' => 'email', 'zorunlu' => true,
         'baslik' => 'E-Posta adresiniz', 'aciklama' => 'Mümkünse kurumsal iş postanızı rica ederim.', 'ph' => 'ornek@firma.com'],

        ['tur' => 'soru', 'name' => 'telefon', 'tip' => 'tel', 'zorunlu' => false,
         'baslik' => 'Telefon numaranız', 'aciklama' => 'İlerleyen süreçlerde size hızlıca ulaşabilmemiz için istiyorum.', 'ph' => '05XX XXX XX XX'],

        ['tur' => 'soru', 'name' => 'dogum_tarihi', 'tip' => 'date', 'zorunlu' => false,
         'baslik' => 'Doğum tarihinizi öğrenebilir miyim?', 'aciklama' => 'Özel günlerde sizleri hatırlamak istiyoruz.'],

        ['tur' => 'aciklama', 'baslik' => 'Şimdi başlıyoruz {ad},',
         'metin' => 'Bize biraz markanızdan bahseder misiniz?', 'btn' => 'Devam'],

        ['tur' => 'soru', 'name' => 'marka_guclu', 'tip' => 'uzun', 'zorunlu' => true,
         'baslik' => 'Markanızın güçlü yönleri nelerdir?', 'ph' => 'Cevabınızı yazın…'],

        ['tur' => 'soru', 'name' => 'marka_gelistir', 'tip' => 'uzun', 'zorunlu' => true,
         'baslik' => 'Geliştirmek istediğiniz yönler nelerdir?', 'ph' => 'Cevabınızı yazın…'],

        ['tur' => 'soru', 'name' => 'geri_bildirim', 'tip' => 'evethayir', 'zorunlu' => true,
         'baslik' => 'Müşterilerin olumlu, olumsuz geri bildirimlerini önemsiyor musunuz?'],

        ['tur' => 'soru', 'name' => 'rakipler', 'tip' => 'uzun', 'zorunlu' => true,
         'baslik' => 'Sektördeki rakiplerinizi takip ediyor musunuz ve rakipler hakkında düşünceleriniz nedir? Örnek aldığınız rakipleriniz var mı?', 'ph' => 'Cevabınızı yazın…'],

        ['tur' => 'soru', 'name' => 'rakip_kampanya', 'tip' => 'uzun', 'zorunlu' => true,
         'baslik' => 'Kampanyasını beğendiğiniz, başarılı bulduğunuz, "keşke bizde yapsaydık" dediğiniz bir rakip çalışması var mı?', 'ph' => 'Cevabınızı yazın…'],

        ['tur' => 'soru', 'name' => 'ajans_calisti', 'tip' => 'evethayir', 'zorunlu' => true,
         'baslik' => 'Daha önce herhangi bir reklam ajansı ile çalıştınız mı?'],

        ['tur' => 'soru', 'name' => 'ajans_katki', 'tip' => 'uzun', 'zorunlu' => true,
         'baslik' => 'Çalıştığınız ajansın markanıza katkısı neler oldu veya varsa eksikleri nelerdir?',
         'aciklama' => 'Örneğin; zamanında iş teslimi, kreatif fikirler, sosyal medyada iyi yönlendirme veya moderasyonda yetersizlik gibi…', 'ph' => 'Cevabınızı yazın…'],

        ['tur' => 'soru', 'name' => 'ajans_beklenti', 'tip' => 'uzun', 'zorunlu' => true,
         'baslik' => 'Bir reklam ajansı ile çalışsaydınız beklentiniz ne olurdu?', 'ph' => 'Cevabınızı yazın…'],

        ['tur' => 'soru', 'name' => 'duydu_mu', 'tip' => 'evethayir', 'zorunlu' => true,
         'baslik' => 'DN Kreatif Dijital Reklam Ajansını hiç duydunuz mu?'],

        ['tur' => 'aciklama', 'baslik' => 'DN Kreatif hakkında',
         'metin' => "2018 yılında kurulan DN Kreatif, merkezi İstanbul Şişli'de bulunan 360° hizmet veren dijital reklam ajansıdır. 500'den fazla müşteriye hizmet vermiş olan firmayı 25 kişilik bir kadro desteklemektedir. StartUp, Kobi ve Kurumsala hitap eden her ölçekte çözümler üretiyoruz:\n\n• Prodüksiyon\n• Dijital pazarlama\n• Sosyal medya yönetimi\n• Kurumsal kimlik oluşturma\n• E-Ticaret ve entegrasyonları\n• Web siteleri ve web uygulamaları\n• UI / UX Tasarımları\n• Domain, hosting barındırma hizmetleri\n• Kurumsala yönelik teknoloji destek hizmetleri",
         'btn' => 'Devam'],

        ['tur' => 'soru', 'name' => 'butce', 'tip' => 'uzun', 'zorunlu' => true,
         'baslik' => 'Bugün markanızı hayalinizdeki marka yapmak için ne kadar dijitale bütçe ayırırdınız?', 'ph' => 'Cevabınızı yazın…'],
    ];
    $soruSayisi = collect($adimlar)->where('tur', 'soru')->count();
@endphp

<style>
    :root { --arge-sari:#b8b62e; --arge-koyu:#1a1a0e; --arge-ink:#2a2233; }
    /* ── Sayfa başlığı (site standardı — padding-top navbar'ı geçer) ── */
    .arge-ph { background:#fff; padding:110px 0 26px; border-bottom:1px solid #e8e8e2; }
    .arge-ph .bc { font-size:13px; color:#999; margin-bottom:10px; }
    .arge-ph .bc a { color:#999; text-decoration:none; }
    .arge-ph .bc .sep { color:var(--arge-sari); margin:0 6px; }
    .arge-ph .bc .cur { color:#1a1a1a; font-weight:600; }
    .arge-ph h1 { color:#1a1a1a; font-size:28px; font-weight:700; margin:0; }
    /* ── Gövde: krem zemin + adımlar ── */
    .arge-sec { background:linear-gradient(150deg,#fbfbf4 0%,#f4f3e6 100%); display:flex; flex-direction:column; font-family:'Inter','Segoe UI',sans-serif; }
    .arge-ilerleme-dis { height:5px; background:#e8e6d4; flex-shrink:0; }
    .arge-ilerleme-ic  { height:100%; width:0; background:var(--arge-sari); transition:width .35s ease; }
    .arge-govde { flex:1; display:flex; align-items:center; justify-content:center; padding:54px 20px 72px; min-height:54vh; }
    .arge-kart { width:100%; max-width:660px; }
    .arge-adim { display:none; animation:argeGir .4s ease; }
    .arge-adim.aktif { display:block; }
    @keyframes argeGir { from{opacity:0; transform:translateY(18px);} to{opacity:1; transform:translateY(0);} }
    @media (prefers-reduced-motion: reduce) { .arge-adim, .arge-ilerleme-ic { animation:none; transition:none; } }

    .arge-adim.giris, .arge-bitis { text-align:center; }
    .arge-adim.giris .arge-alt { justify-content:center; }
    .arge-rubito-buyuk { display:block; width:224px; box-sizing:border-box; padding:24px 32px; background:#1a2332; border-radius:32px; margin:0 auto 24px; box-shadow:0 12px 34px rgba(26,35,50,.16); }
    .arge-rubito-buyuk .rubito-svg { width:100%; height:auto; display:block; }
    .arge-sure { margin-top:14px; color:#a29e90; font-size:13px; display:flex; align-items:center; justify-content:center; gap:6px; }

    .arge-rozet { display:inline-flex; align-items:center; gap:8px; font-size:12.5px; font-weight:700; color:#8a8718; background:rgba(184,182,46,.14); padding:5px 12px; border-radius:999px; margin-bottom:16px; }
    .arge-rozet-bot { width:27px; height:20px; background:#1a2332; border-radius:6px; display:inline-flex; align-items:center; justify-content:center; flex-shrink:0; }
    .arge-rozet-bot .rubito-svg { width:20px; height:auto; display:block; }

    .arge-soru { font-size:26px; font-weight:800; color:var(--arge-ink); line-height:1.3; letter-spacing:-.01em; margin:0 0 8px; }
    .arge-aciklama { font-size:15px; color:#7a7768; line-height:1.6; margin:0 0 22px; white-space:pre-line; }
    .arge-zorunlu { color:var(--arge-sari); font-size:13px; }

    .arge-input, .arge-textarea {
        width:100%; border:none; border-bottom:2px solid #d8d6c4; background:transparent;
        font-size:22px; color:var(--arge-ink); padding:10px 2px; outline:none; font-family:inherit; transition:border-color .2s;
    }
    .arge-input:focus, .arge-textarea:focus { border-color:var(--arge-sari); }
    .arge-input::placeholder, .arge-textarea::placeholder { color:#bdbaa8; }
    .arge-textarea { resize:none; border:2px solid #e0ddca; border-radius:14px; font-size:17px; line-height:1.6; padding:16px 18px; min-height:130px; background:rgba(255,255,255,.5); }
    .arge-textarea:focus { border-color:var(--arge-sari); background:#fff; }

    .arge-eh { display:flex; gap:14px; flex-wrap:wrap; }
    .arge-eh-btn { display:inline-flex; align-items:center; gap:10px; padding:14px 26px; border-radius:14px; border:2px solid #e0ddca; background:rgba(255,255,255,.6); cursor:pointer; font-size:17px; font-weight:700; color:var(--arge-ink); transition:all .18s; font-family:inherit; }
    .arge-eh-btn:hover { border-color:var(--arge-sari); background:#fff; }
    .arge-eh-btn.secili { border-color:var(--arge-sari); background:var(--arge-sari); color:var(--arge-koyu); }
    .arge-eh-btn .harf { width:26px;height:26px;border-radius:7px;background:rgba(0,0,0,.08);display:flex;align-items:center;justify-content:center;font-size:13px; }
    .arge-eh-btn.secili .harf { background:rgba(0,0,0,.15); }

    .arge-alt { display:flex; align-items:center; gap:14px; margin-top:26px; }
    .arge-ileri { display:inline-flex; align-items:center; gap:8px; padding:13px 32px; border-radius:10px; background:var(--arge-sari); color:var(--arge-koyu); font-weight:800; font-size:15px; border:none; cursor:pointer; box-shadow:0 6px 18px rgba(184,182,46,.32); transition:transform .12s, background .2s; text-decoration:none; }
    .arge-ileri:hover { transform:translateY(-1px); background:#a9a728; color:var(--arge-koyu); }
    .arge-ileri:disabled { opacity:.45; cursor:default; transform:none; box-shadow:none; }
    .arge-ipucu { font-size:12.5px; color:#a29e90; }
    .arge-ipucu b { color:#7a7768; }
    .arge-hata { color:#c0392b; font-size:13.5px; margin-top:8px; min-height:18px; }

    .arge-ac-baslik { font-size:30px; font-weight:800; color:var(--arge-ink); margin:0 0 14px; line-height:1.25; }
    .arge-ac-metin  { font-size:15.5px; color:#6f6c5e; line-height:1.7; white-space:pre-line; margin:0 0 26px; }

    .arge-bitis .ikon { width:88px;height:88px;border-radius:50%;background:var(--arge-sari);color:var(--arge-koyu);display:flex;align-items:center;justify-content:center;font-size:44px;margin:0 auto 22px; }
    .arge-navmini { display:flex; gap:6px; align-items:center; }
    .arge-nav-btn { width:34px;height:34px;border-radius:8px;border:1.5px solid #dedcca;background:#fff;cursor:pointer;color:#8a8676;font-size:14px;display:flex;align-items:center;justify-content:center; }
    .arge-nav-btn:hover { border-color:var(--arge-sari); color:var(--arge-ink); }
    .arge-nav-btn:disabled { opacity:.35; cursor:default; }

    @media (max-width:576px){
        .arge-ph { padding:92px 0 20px; }
        .arge-ph h1 { font-size:23px; }
        .arge-soru { font-size:21px; } .arge-ac-baslik { font-size:24px; } .arge-input { font-size:19px; }
        .arge-rubito-buyuk { width:190px; padding:20px 26px; }
    }
</style>

<div class="arge-ph">
    <div class="container">
        <div class="bc">
            <a href="{{ localized_route('anasayfa') }}">{{ __('messages.home') }}</a>
            <span class="sep">&rsaquo;</span><span class="cur">Ar-Ge Anketi</span>
        </div>
        <h1>Ar-Ge Anketi</h1>
    </div>
</div>

<div class="arge-sec" id="argeWrap">
    <div class="arge-ilerleme-dis"><div class="arge-ilerleme-ic" id="argeIlerleme"></div></div>

    <div class="arge-govde">
        <div class="arge-kart">

            @foreach($adimlar as $i => $a)
                <div class="arge-adim {{ $i === 0 ? 'aktif giris' : '' }}" data-adim="{{ $i }}" data-tur="{{ $a['tur'] }}"
                     @if($a['tur']==='soru') data-name="{{ $a['name'] }}" data-tip="{{ $a['tip'] }}" data-zorunlu="{{ $a['zorunlu'] ? 1 : 0 }}" @endif>

                    @if($a['tur'] === 'aciklama')
                        @if($i === 0)
                            <div class="arge-rubito-buyuk">@include('tema.partials.rubito')</div>
                        @else
                            <div class="arge-rozet"><span class="arge-rozet-bot">@include('tema.partials.rubito')</span> Rubito</div>
                        @endif
                        <h2 class="arge-ac-baslik" data-sablon="{{ $a['baslik'] }}">{{ $a['baslik'] }}</h2>
                        <p class="arge-ac-metin">{{ $a['metin'] }}</p>
                        <div class="arge-alt">
                            <button type="button" class="arge-ileri" data-ileri>{{ $a['btn'] ?? 'Devam' }} <i class="mdi mdi-arrow-right"></i></button>
                        </div>
                        @if($i === 0)
                            <div class="arge-sure"><i class="mdi mdi-clock-outline"></i> 7 dakika sürer</div>
                        @endif
                    @else
                        @php $sira = collect($adimlar)->take($i)->where('tur','soru')->count() + 1; @endphp
                        <div class="arge-rozet"><span>{{ $sira }} → {{ $soruSayisi }}</span></div>
                        <h2 class="arge-soru">{{ $a['baslik'] }} @if($a['zorunlu'])<span class="arge-zorunlu">*</span>@endif</h2>
                        @if(!empty($a['aciklama']))<p class="arge-aciklama">{{ $a['aciklama'] }}</p>@endif

                        @if($a['tip'] === 'uzun')
                            <textarea class="arge-textarea" data-giris rows="4" placeholder="{{ $a['ph'] ?? '' }}"></textarea>
                        @elseif($a['tip'] === 'evethayir')
                            <div class="arge-eh" data-eh>
                                <button type="button" class="arge-eh-btn" data-deger="evet"><span class="harf">E</span> Evet</button>
                                <button type="button" class="arge-eh-btn" data-deger="hayir"><span class="harf">H</span> Hayır</button>
                            </div>
                            <input type="hidden" data-giris value="">
                        @else
                            <input type="{{ $a['tip'] }}" class="arge-input" data-giris placeholder="{{ $a['ph'] ?? '' }}">
                        @endif

                        <div class="arge-hata" data-hata></div>
                        <div class="arge-alt">
                            <button type="button" class="arge-ileri" data-ileri>Tamam <i class="mdi mdi-check"></i></button>
                            <span class="arge-ipucu"><b>Enter ↵</b> ile devam</span>
                        </div>
                    @endif
                </div>
            @endforeach

            {{-- Teşekkür ekranı --}}
            <div class="arge-adim" data-adim="son">
                <div class="arge-bitis">
                    <div class="ikon">✓</div>
                    <h2 class="arge-ac-baslik">Yanıtların için teşekkürler, <span id="argeSonAd">arkadaşım</span>!</h2>
                    <p class="arge-ac-metin" style="text-align:center;">Ar-Ge ekibimiz cevaplarını inceleyip en kısa sürede size özel bir yol haritasıyla dönecek.</p>
                    <a href="{{ localized_route('anasayfa') }}" class="arge-ileri"><i class="mdi mdi-home"></i> Ana Sayfaya Dön</a>
                </div>
            </div>

            {{-- Alt navigasyon (geri) --}}
            <div class="arge-navmini" id="argeNav" style="margin-top:22px;">
                <button type="button" class="arge-nav-btn" id="argeGeri" disabled aria-label="Geri"><i class="mdi mdi-chevron-up"></i></button>
                <button type="button" class="arge-nav-btn" id="argeIleriMini" aria-label="İleri"><i class="mdi mdi-chevron-down"></i></button>
            </div>

        </div>
    </div>
</div>

<form id="argeForm" action="{{ route('arge.anketi.kaydet') }}" method="POST" style="display:none;">@csrf</form>

<script>
(function () {
    const wrap   = document.getElementById('argeWrap');
    const adimlar = [...wrap.querySelectorAll('.arge-adim')];
    const ilerleme = document.getElementById('argeIlerleme');
    const geriBtn = document.getElementById('argeGeri');
    const ileriMini = document.getElementById('argeIleriMini');
    const nav = document.getElementById('argeNav');
    const form = document.getElementById('argeForm');
    const cevaplar = {};
    let aktif = 0;
    const sonIndex = adimlar.length - 1;

    function goster(n) {
        adimlar[aktif].classList.remove('aktif');
        aktif = n;
        adimlar[aktif].classList.add('aktif');
        const el = adimlar[aktif];
        ilerleme.style.width = Math.round((aktif / sonIndex) * 100) + '%';
        const sablon = el.querySelector('[data-sablon]');
        if (sablon) sablon.textContent = (sablon.dataset.sablon || '').replace('{ad}', cevaplar.ad_soyad || '');
        geriBtn.disabled = (aktif === 0);
        nav.style.display = (el.dataset.adim === 'son') ? 'none' : 'flex';
        const g = el.querySelector('[data-giris]:not([type=hidden])');
        if (g) setTimeout(() => g.focus(), 120);
        if (el.dataset.adim === 'son') {
            document.getElementById('argeSonAd').textContent = (cevaplar.ad_soyad || 'arkadaşım').split(' ')[0];
        }
    }

    function dogrula(el) {
        const tur = el.dataset.tur;
        if (tur !== 'soru') return true;
        const zorunlu = el.dataset.zorunlu === '1';
        const tip = el.dataset.tip;
        const giris = el.querySelector('[data-giris]');
        const hata = el.querySelector('[data-hata]');
        const deger = (giris.value || '').trim();
        if (zorunlu && !deger) { hata.textContent = 'Bu alan zorunlu.'; return false; }
        if (tip === 'email' && deger && !/^[^@\s]+@[^@\s]+\.[a-z]{2,}$/i.test(deger)) { hata.textContent = 'Geçerli bir e-posta girin.'; return false; }
        hata.textContent = '';
        cevaplar[el.dataset.name] = deger;
        return true;
    }

    function ileri() {
        const el = adimlar[aktif];
        if (!dogrula(el)) return;
        if (aktif < sonIndex - 1) { goster(aktif + 1); }
        else if (aktif === sonIndex - 1) { gonder(); }
    }
    function geri() { if (aktif > 0) goster(aktif - 1); }

    // Çift gönderim kilidi: cevap gelene kadar ekran son soruda kaldığı için
    // kullanıcının her Enter'ı / tıklaması yeni bir POST atıyordu (mükerrer kayıt).
    let gonderiliyor = false;

    function gonder() {
        if (gonderiliyor) return;
        gonderiliyor = true;

        // Butonları kilitle + "Gönderiliyor…" durumu göster
        const el = adimlar[aktif];
        const btn = el.querySelector('[data-ileri]');
        let eskiHtml = null;
        if (btn) {
            eskiHtml = btn.innerHTML;
            btn.disabled = true;
            btn.innerHTML = 'Gönderiliyor… <i class="mdi mdi-loading mdi-spin"></i>';
        }
        ileriMini.disabled = true;
        geriBtn.disabled = true;

        const bitir = () => goster(sonIndex);
        const hata = () => {
            // Gönderilemedi: kilidi aç ki kullanıcı tekrar deneyebilsin
            gonderiliyor = false;
            if (btn) { btn.disabled = false; btn.innerHTML = eskiHtml; }
            ileriMini.disabled = false;
            geriBtn.disabled = (aktif === 0);
            const h = el.querySelector('[data-hata]');
            if (h) h.textContent = 'Gönderilemedi, lütfen tekrar deneyin.';
        };

        Object.entries(cevaplar).forEach(([k, v]) => {
            let inp = form.querySelector(`[name="${k}"]`);
            if (!inp) { inp = document.createElement('input'); inp.type = 'hidden'; inp.name = k; form.appendChild(inp); }
            inp.value = v;
        });
        const fd = new FormData(form);
        fetch(form.action, {
            method: 'POST',
            headers: { 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-TOKEN': form.querySelector('[name=_token]').value },
            body: fd
        }).then(r => {
            if (!r.ok) throw new Error('HTTP ' + r.status);
            return r.json();
        }).then(bitir).catch(hata);
    }

    wrap.querySelectorAll('[data-ileri]').forEach(b => b.addEventListener('click', ileri));
    geriBtn.addEventListener('click', geri);
    ileriMini.addEventListener('click', ileri);

    wrap.querySelectorAll('[data-eh]').forEach(grup => {
        grup.querySelectorAll('.arge-eh-btn').forEach(btn => {
            btn.addEventListener('click', () => {
                grup.querySelectorAll('.arge-eh-btn').forEach(x => x.classList.remove('secili'));
                btn.classList.add('secili');
                grup.parentElement.querySelector('[data-giris]').value = btn.dataset.deger;
                setTimeout(ileri, 220);
            });
        });
    });

    wrap.addEventListener('keydown', e => {
        if (e.key !== 'Enter') return;
        const el = adimlar[aktif];
        const giris = el.querySelector('[data-giris]');
        if (giris && giris.tagName === 'TEXTAREA' && e.shiftKey) return;
        e.preventDefault();
        ileri();
    });

    goster(0);
})();
</script>
@endsection
