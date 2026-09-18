@extends('layouts.master')

@section('title', 'İş Başvurusu')

@section('content')
@php
    // İş Başvurusu — ornek.com/is-basvurusu'nun native hâli. Rubito personası.
    // Düzen: sitenin standart sayfa başlığı + krem gövde + lemon aksan (site paleti).
    $adimlar = [
        ['tur' => 'aciklama', 'baslik' => 'Merhaba ben Rubito,',
         'metin' => "DN Kreatif yapay zeka asistanıyım. Bizden biri olmak; meydan okumayı sevmek, zekâyı somutlaştırmak ve hızlı hareket etmektir. Sana en uygun pozisyonu bulabilmemiz için birkaç sorum olacak — hazırsan başlayalım!",
         'btn' => 'Başla'],

        ['tur' => 'soru', 'name' => 'ad_soyad', 'tip' => 'text', 'zorunlu' => true,
         'baslik' => 'Adını ve soyadını öğrenebilir miyim?', 'ph' => 'Ad Soyad'],

        ['tur' => 'aciklama', 'baslik' => 'Memnun oldum {ad}!',
         'metin' => 'Seni daha yakından tanıyabilmemiz için birkaç bilgi daha alacağım.', 'btn' => 'Devam'],

        ['tur' => 'soru', 'name' => 'email', 'tip' => 'email', 'zorunlu' => true,
         'baslik' => 'E-posta adresin', 'ph' => 'ornek@eposta.com'],

        ['tur' => 'soru', 'name' => 'telefon', 'tip' => 'tel', 'zorunlu' => true,
         'baslik' => 'Telefon numaran', 'aciklama' => 'Sana hızlıca ulaşabilmemiz için istiyorum.', 'ph' => '05XX XXX XX XX'],

        ['tur' => 'soru', 'name' => 'calisma_durumu', 'tip' => 'sec', 'zorunlu' => false,
         'baslik' => 'Şu anki çalışma durumun nedir?',
         'secenekler' => ['Çalışıyorum', 'Çalışmıyorum', 'Aktif iş arayışındayım', 'Aktif iş arayışında değilim', 'İhbar sürecindeyim']],

        ['tur' => 'soru', 'name' => 'egitim_duzeyi', 'tip' => 'sec', 'zorunlu' => false,
         'baslik' => 'Eğitim düzeyin?',
         'secenekler' => ['Lise', 'Ön Lisans', 'Lisans', 'Yüksek Lisans']],

        ['tur' => 'soru', 'name' => 'pozisyon', 'tip' => 'sec', 'zorunlu' => true,
         'baslik' => 'Hangi pozisyon için başvuruyorsun?',
         'secenekler' => ['Grafik Tasarım Uzmanı', 'Web Tasarım Uzmanı', 'Sosyal Medya Uzmanı', 'Marka Danışmanlığı', 'Satış Pazarlama Uzmanı', 'İş Ortaklığı']],

        ['tur' => 'soru', 'name' => 'deneyim_yili', 'tip' => 'sayi', 'zorunlu' => false,
         'baslik' => 'Kaç yıllık deneyimin var?', 'aciklama' => 'Yıl olarak yaz (yoksa 0).', 'ph' => '0'],

        ['tur' => 'soru', 'name' => 'lokasyon', 'tip' => 'sec', 'zorunlu' => true,
         'baslik' => 'Hangi lokasyonda çalışmak istersin?',
         'secenekler' => ['Marmaris', 'İstanbul', 'Dubai', 'Uzaktan']],

        ['tur' => 'soru', 'name' => 'dil', 'tip' => 'sec', 'zorunlu' => true,
         'baslik' => 'Hangi yabancı dile hakimsin?',
         'secenekler' => ['İngilizce', 'Arapça', 'Almanca', 'Fransızca']],

        ['tur' => 'soru', 'name' => 'maas_beklenti', 'tip' => 'text', 'zorunlu' => true,
         'baslik' => 'Beklediğin maaş aralığı nedir?', 'ph' => 'Örn. 40.000 – 50.000 ₺'],

        ['tur' => 'soru', 'name' => 'cv', 'tip' => 'dosya', 'zorunlu' => true,
         'baslik' => 'CV / özgeçmişini yükler misin?', 'aciklama' => 'PDF veya Word dosyası (en fazla 8 MB).'],

        ['tur' => 'soru', 'name' => 'ek_bilgi', 'tip' => 'uzun', 'zorunlu' => true,
         'baslik' => 'Eklemek istediğin bir şey var mı?', 'ph' => 'Kendinden kısaca bahset…'],
    ];
    $soruSayisi = collect($adimlar)->where('tur', 'soru')->count();
@endphp

<style>
    :root { --bsv-sari:#b8b62e; --bsv-koyu:#1a1a0e; --bsv-ink:#2a2233; }
    .bsv-ph { background:#fff; padding:110px 0 26px; border-bottom:1px solid #e8e8e2; }
    .bsv-ph .bc { font-size:13px; color:#999; margin-bottom:10px; }
    .bsv-ph .bc a { color:#999; text-decoration:none; }
    .bsv-ph .bc .sep { color:var(--bsv-sari); margin:0 6px; }
    .bsv-ph .bc .cur { color:#1a1a1a; font-weight:600; }
    .bsv-ph h1 { color:#1a1a1a; font-size:28px; font-weight:700; margin:0; }
    .bsv-sec-alan { background:linear-gradient(150deg,#fbfbf4 0%,#f4f3e6 100%); display:flex; flex-direction:column; font-family:'Inter','Segoe UI',sans-serif; }
    .bsv-ilerleme-dis { height:5px; background:#e8e6d4; flex-shrink:0; }
    .bsv-ilerleme-ic  { height:100%; width:0; background:var(--bsv-sari); transition:width .35s ease; }
    .bsv-govde { flex:1; display:flex; align-items:center; justify-content:center; padding:54px 20px 72px; min-height:54vh; }
    .bsv-kart { width:100%; max-width:660px; }
    .bsv-adim { display:none; animation:bsvGir .4s ease; }
    .bsv-adim.aktif { display:block; }
    @keyframes bsvGir { from{opacity:0; transform:translateY(18px);} to{opacity:1; transform:translateY(0);} }
    @media (prefers-reduced-motion: reduce) { .bsv-adim, .bsv-ilerleme-ic { animation:none; transition:none; } }

    .bsv-adim.giris, .bsv-bitis { text-align:center; }
    .bsv-adim.giris .bsv-alt { justify-content:center; }
    .bsv-rubito-buyuk { display:block; width:224px; box-sizing:border-box; padding:24px 32px; background:#1a2332; border-radius:32px; margin:0 auto 24px; box-shadow:0 12px 34px rgba(26,35,50,.16); }
    .bsv-rubito-buyuk .rubito-svg { width:100%; height:auto; display:block; }

    .bsv-rozet { display:inline-flex; align-items:center; gap:8px; font-size:12.5px; font-weight:700; color:#8a8718; background:rgba(184,182,46,.14); padding:5px 12px; border-radius:999px; margin-bottom:16px; }
    .bsv-rozet-bot { width:27px; height:20px; background:#1a2332; border-radius:6px; display:inline-flex; align-items:center; justify-content:center; flex-shrink:0; }
    .bsv-rozet-bot .rubito-svg { width:20px; height:auto; display:block; }

    .bsv-soru { font-size:26px; font-weight:800; color:var(--bsv-ink); line-height:1.3; letter-spacing:-.01em; margin:0 0 8px; }
    .bsv-aciklama { font-size:15px; color:#7a7768; line-height:1.6; margin:0 0 22px; white-space:pre-line; }
    .bsv-zorunlu { color:var(--bsv-sari); font-size:13px; }
    .bsv-input, .bsv-textarea { width:100%; border:none; border-bottom:2px solid #d8d6c4; background:transparent; font-size:22px; color:var(--bsv-ink); padding:10px 2px; outline:none; font-family:inherit; transition:border-color .2s; }
    .bsv-input:focus, .bsv-textarea:focus { border-color:var(--bsv-sari); }
    .bsv-input::placeholder, .bsv-textarea::placeholder { color:#bdbaa8; }
    .bsv-textarea { resize:none; border:2px solid #e0ddca; border-radius:14px; font-size:17px; line-height:1.6; padding:16px 18px; min-height:130px; background:rgba(255,255,255,.5); }
    .bsv-textarea:focus { border-color:var(--bsv-sari); background:#fff; }
    .bsv-sec { display:flex; flex-direction:column; gap:11px; }
    .bsv-sec-btn { display:flex; align-items:center; gap:12px; padding:15px 20px; border-radius:14px; border:2px solid #e0ddca; background:rgba(255,255,255,.6); cursor:pointer; font-size:16px; font-weight:700; color:var(--bsv-ink); transition:all .16s; font-family:inherit; text-align:left; }
    .bsv-sec-btn:hover { border-color:var(--bsv-sari); background:#fff; }
    .bsv-sec-btn.secili { border-color:var(--bsv-sari); background:var(--bsv-sari); color:var(--bsv-koyu); }
    .bsv-sec-btn .harf { width:28px;height:28px;flex-shrink:0;border-radius:8px;background:rgba(0,0,0,.08);display:flex;align-items:center;justify-content:center;font-size:13px;font-weight:800; }
    .bsv-sec-btn.secili .harf { background:rgba(0,0,0,.15); }
    .bsv-dosya-alan { border:2px dashed #cfccb6; border-radius:16px; padding:30px 22px; text-align:center; background:rgba(255,255,255,.5); cursor:pointer; transition:all .18s; display:block; }
    .bsv-dosya-alan:hover { border-color:var(--bsv-sari); background:#fff; }
    .bsv-dosya-alan.dolu { border-style:solid; border-color:var(--bsv-sari); background:#fff; }
    .bsv-dosya-ikon { font-size:34px; margin-bottom:10px; }
    .bsv-dosya-metin { font-size:15px; color:#6f6c5e; font-weight:600; }
    .bsv-dosya-ad { font-size:14px; color:#8a8718; font-weight:800; margin-top:6px; word-break:break-all; }
    .bsv-alt { display:flex; align-items:center; gap:14px; margin-top:26px; }
    .bsv-ileri { display:inline-flex; align-items:center; gap:8px; padding:13px 32px; border-radius:10px; background:var(--bsv-sari); color:var(--bsv-koyu); font-weight:800; font-size:15px; border:none; cursor:pointer; box-shadow:0 6px 18px rgba(184,182,46,.32); transition:transform .12s, background .2s; text-decoration:none; }
    .bsv-ileri:hover { transform:translateY(-1px); background:#a9a728; color:var(--bsv-koyu); }
    .bsv-ipucu { font-size:12.5px; color:#a29e90; }
    .bsv-ipucu b { color:#7a7768; }
    .bsv-hata { color:#c0392b; font-size:13.5px; margin-top:8px; min-height:18px; }
    .bsv-ac-baslik { font-size:30px; font-weight:800; color:var(--bsv-ink); margin:0 0 14px; line-height:1.25; }
    .bsv-ac-metin  { font-size:15.5px; color:#6f6c5e; line-height:1.7; white-space:pre-line; margin:0 0 26px; }
    .bsv-bitis .ikon { width:88px;height:88px;border-radius:50%;background:var(--bsv-sari);color:var(--bsv-koyu);display:flex;align-items:center;justify-content:center;font-size:44px;margin:0 auto 22px; }
    .bsv-navmini { display:flex; gap:6px; align-items:center; }
    .bsv-nav-btn { width:34px;height:34px;border-radius:8px;border:1.5px solid #dedcca;background:#fff;cursor:pointer;color:#8a8676;font-size:14px;display:flex;align-items:center;justify-content:center; }
    .bsv-nav-btn:hover { border-color:var(--bsv-sari); color:var(--bsv-ink); }
    .bsv-nav-btn:disabled { opacity:.35; cursor:default; }
    @media (max-width:576px){
        .bsv-ph { padding:92px 0 20px; }
        .bsv-ph h1 { font-size:23px; }
        .bsv-soru { font-size:21px; } .bsv-ac-baslik { font-size:24px; } .bsv-input { font-size:19px; }
        .bsv-rubito-buyuk { width:190px; padding:20px 26px; }
    }
</style>

<div class="bsv-ph">
    <div class="container">
        <div class="bc">
            <a href="{{ localized_route('anasayfa') }}">{{ __('messages.home') }}</a>
            <span class="sep">&rsaquo;</span><span class="cur">İş Başvurusu</span>
        </div>
        <h1>İş Başvurusu</h1>
    </div>
</div>

<div class="bsv-sec-alan" id="bsvWrap">
    <div class="bsv-ilerleme-dis"><div class="bsv-ilerleme-ic" id="bsvIlerleme"></div></div>

    <div class="bsv-govde">
        <div class="bsv-kart">

            @foreach($adimlar as $i => $a)
                <div class="bsv-adim {{ $i === 0 ? 'aktif giris' : '' }}" data-adim="{{ $i }}" data-tur="{{ $a['tur'] }}"
                     @if($a['tur']==='soru') data-name="{{ $a['name'] }}" data-tip="{{ $a['tip'] }}" data-zorunlu="{{ $a['zorunlu'] ? 1 : 0 }}" @endif>

                    @if($a['tur'] === 'aciklama')
                        @if($i === 0)
                            <div class="bsv-rubito-buyuk">@include('tema.partials.rubito')</div>
                        @else
                            <div class="bsv-rozet"><span class="bsv-rozet-bot">@include('tema.partials.rubito')</span> Rubito</div>
                        @endif
                        <h2 class="bsv-ac-baslik" data-sablon="{{ $a['baslik'] }}">{{ $a['baslik'] }}</h2>
                        <p class="bsv-ac-metin">{{ $a['metin'] }}</p>
                        <div class="bsv-alt">
                            <button type="button" class="bsv-ileri" data-ileri>{{ $a['btn'] ?? 'Devam' }} <i class="mdi mdi-arrow-right"></i></button>
                        </div>
                    @else
                        @php $sira = collect($adimlar)->take($i)->where('tur','soru')->count() + 1; @endphp
                        <div class="bsv-rozet"><span>{{ $sira }} → {{ $soruSayisi }}</span></div>
                        <h2 class="bsv-soru">{{ $a['baslik'] }} @if($a['zorunlu'])<span class="bsv-zorunlu">*</span>@endif</h2>
                        @if(!empty($a['aciklama']))<p class="bsv-aciklama">{{ $a['aciklama'] }}</p>@endif

                        @if($a['tip'] === 'uzun')
                            <textarea class="bsv-textarea" data-giris rows="4" placeholder="{{ $a['ph'] ?? '' }}"></textarea>
                        @elseif($a['tip'] === 'sec')
                            <div class="bsv-sec" data-sec>
                                @foreach($a['secenekler'] as $si => $secenek)
                                    <button type="button" class="bsv-sec-btn" data-deger="{{ $secenek }}">
                                        <span class="harf">{{ chr(65 + $si) }}</span> {{ $secenek }}
                                    </button>
                                @endforeach
                            </div>
                            <input type="hidden" data-giris value="">
                        @elseif($a['tip'] === 'dosya')
                            <label class="bsv-dosya-alan" data-dosya-alan>
                                <div class="bsv-dosya-ikon">📄</div>
                                <div class="bsv-dosya-metin" data-dosya-metin>Dosya seçmek için tıkla veya sürükle</div>
                                <div class="bsv-dosya-ad" data-dosya-ad></div>
                                <input type="file" data-giris data-file accept=".pdf,.doc,.docx,application/pdf,application/msword,application/vnd.openxmlformats-officedocument.wordprocessingml.document" style="display:none;">
                            </label>
                        @elseif($a['tip'] === 'sayi')
                            <input type="number" min="0" max="70" class="bsv-input" data-giris placeholder="{{ $a['ph'] ?? '0' }}">
                        @else
                            <input type="{{ $a['tip'] }}" class="bsv-input" data-giris placeholder="{{ $a['ph'] ?? '' }}">
                        @endif

                        <div class="bsv-hata" data-hata></div>
                        <div class="bsv-alt">
                            <button type="button" class="bsv-ileri" data-ileri>Tamam <i class="mdi mdi-check"></i></button>
                            <span class="bsv-ipucu"><b>Enter ↵</b> ile devam</span>
                        </div>
                    @endif
                </div>
            @endforeach

            <div class="bsv-adim" data-adim="son">
                <div class="bsv-bitis">
                    <div class="ikon">✓</div>
                    <h2 class="bsv-ac-baslik">Başvurun bize ulaştı, <span id="bsvSonAd">arkadaşım</span>!</h2>
                    <p class="bsv-ac-metin" style="text-align:center;">İnsan kaynakları ekibimiz başvurunu değerlendirip uygun olması hâlinde en kısa sürede seninle iletişime geçecek.</p>
                    <a href="{{ localized_route('anasayfa') }}" class="bsv-ileri"><i class="mdi mdi-home"></i> Ana Sayfaya Dön</a>
                </div>
            </div>

            <div class="bsv-navmini" id="bsvNav" style="margin-top:22px;">
                <button type="button" class="bsv-nav-btn" id="bsvGeri" disabled aria-label="Geri"><i class="mdi mdi-chevron-up"></i></button>
                <button type="button" class="bsv-nav-btn" id="bsvIleriMini" aria-label="İleri"><i class="mdi mdi-chevron-down"></i></button>
            </div>

        </div>
    </div>
</div>

<form id="bsvForm" action="{{ route('is.basvurusu.kaydet') }}" method="POST" enctype="multipart/form-data" style="display:none;">@csrf</form>

<script>
(function () {
    const wrap    = document.getElementById('bsvWrap');
    const adimlar = [...wrap.querySelectorAll('.bsv-adim')];
    const ilerleme = document.getElementById('bsvIlerleme');
    const geriBtn = document.getElementById('bsvGeri');
    const ileriMini = document.getElementById('bsvIleriMini');
    const nav = document.getElementById('bsvNav');
    const form = document.getElementById('bsvForm');
    const cevaplar = {};
    let dosya = null;
    let aktif = 0;
    const sonIndex = adimlar.length - 1;

    function goster(n) {
        adimlar[aktif].classList.remove('aktif');
        aktif = n;
        adimlar[aktif].classList.add('aktif');
        const el = adimlar[aktif];
        ilerleme.style.width = Math.round((aktif / sonIndex) * 100) + '%';
        const sablon = el.querySelector('[data-sablon]');
        if (sablon) sablon.textContent = (sablon.dataset.sablon || '').replace('{ad}', (cevaplar.ad_soyad || '').split(' ')[0] || '');
        geriBtn.disabled = (aktif === 0);
        nav.style.display = (el.dataset.adim === 'son') ? 'none' : 'flex';
        const g = el.querySelector('[data-giris]:not([type=hidden]):not([type=file])');
        if (g) setTimeout(() => g.focus(), 120);
        if (el.dataset.adim === 'son') {
            document.getElementById('bsvSonAd').textContent = (cevaplar.ad_soyad || 'arkadaşım').split(' ')[0];
        }
    }

    function dogrula(el) {
        if (el.dataset.tur !== 'soru') return true;
        const zorunlu = el.dataset.zorunlu === '1';
        const tip = el.dataset.tip;
        const giris = el.querySelector('[data-giris]');
        const hata = el.querySelector('[data-hata]');

        if (tip === 'dosya') {
            if (zorunlu && (!giris.files || !giris.files.length)) { hata.textContent = 'Lütfen CV yükleyin.'; return false; }
            if (giris.files && giris.files[0] && giris.files[0].size > 8 * 1024 * 1024) { hata.textContent = 'Dosya en fazla 8 MB olabilir.'; return false; }
            hata.textContent = '';
            dosya = (giris.files && giris.files[0]) ? giris.files[0] : null;
            return true;
        }

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

    function gonder() {
        Object.entries(cevaplar).forEach(([k, v]) => {
            let inp = form.querySelector(`[name="${k}"]`);
            if (!inp) { inp = document.createElement('input'); inp.type = 'hidden'; inp.name = k; form.appendChild(inp); }
            inp.value = v;
        });
        const fd = new FormData(form);
        if (dosya) fd.append('cv', dosya);
        fetch(form.action, {
            method: 'POST',
            headers: { 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-TOKEN': form.querySelector('[name=_token]').value },
            body: fd
        }).then(r => r.json()).then(() => goster(sonIndex)).catch(() => goster(sonIndex));
    }

    wrap.querySelectorAll('[data-ileri]').forEach(b => b.addEventListener('click', ileri));
    geriBtn.addEventListener('click', geri);
    ileriMini.addEventListener('click', ileri);

    wrap.querySelectorAll('[data-sec]').forEach(grup => {
        grup.querySelectorAll('.bsv-sec-btn').forEach(btn => {
            btn.addEventListener('click', () => {
                grup.querySelectorAll('.bsv-sec-btn').forEach(x => x.classList.remove('secili'));
                btn.classList.add('secili');
                grup.parentElement.querySelector('[data-giris]').value = btn.dataset.deger;
                setTimeout(ileri, 220);
            });
        });
    });

    wrap.querySelectorAll('[data-dosya-alan]').forEach(alan => {
        const inp = alan.querySelector('[data-file]');
        inp.addEventListener('change', () => {
            if (inp.files && inp.files.length) {
                alan.classList.add('dolu');
                alan.querySelector('[data-dosya-metin]').textContent = '✓ Dosya seçildi';
                alan.querySelector('[data-dosya-ad]').textContent = inp.files[0].name;
            }
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
