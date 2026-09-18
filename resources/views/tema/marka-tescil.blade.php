@extends('layouts.master')

@section('title', 'Marka Tescil')
@section('description', 'Markanızı yasal güvence altına alın. DN Kreatif İş Ortağım ile hızlı ve kolay marka tescil başvurusu, uzman desteği ve dijital süreç yönetimi.')

@section('content')

{{-- Breadcrumb header --}}
<div style="background:#fff; padding: 110px 0 26px; border-bottom:1px solid #e8e8e2;">
  <div class="container">
    <div style="font-size:13px; color:#999; margin-bottom:10px;">
      <a href="{{ route('anasayfa') }}" style="color:#999; text-decoration:none;">{{ __('messages.home') }}</a>
      <span style="color:#b8b62e; margin:0 6px;">&rsaquo;</span>
      <span style="color:#1a1a1a; font-weight:600;">Marka Tescil</span>
    </div>
    <h1 style="color:#1a1a1a; font-size:28px; font-weight:700; margin:0;">Marka Tescil</h1>
    <p style="color:#999; font-size:14px; margin:6px 0 0;">Markanızı yasal güvence altına alın, emeğinizi koruyun.</p>
  </div>
</div>

{{-- HERO / INTRO --}}
<section style="background:linear-gradient(115deg,#16160f 0%,#26251b 100%); padding:64px 0; position:relative; overflow:hidden;">
  <div style="position:absolute; top:-60px; right:-60px; width:260px; height:260px; border-radius:50%; background:rgba(184,182,46,.18);"></div>
  <div class="container" style="position:relative;">
    <div class="row align-items-center">
      <div class="col-lg-8">
        <span style="display:inline-block; font-size:12px; font-weight:800; letter-spacing:.12em; color:#e2e05f; text-transform:uppercase; margin-bottom:12px;"><i class="mdi mdi-shield"></i> Marka Koruma Hizmeti</span>
        <h2 style="color:#fff !important; font-size:34px; font-weight:800; line-height:1.2; margin:0 0 14px;">Markanızı Hemen Tescilleyin, <span style="color:#e2e05f !important;">Yasal Güvence</span> Altına Alın</h2>
        <p style="color:rgba(255,255,255,.85); font-size:17px; line-height:1.7; margin:0 0 26px; max-width:620px;">Markanız işinizin en değerli varlığıdır. Tescil ile markanızı taklitlere karşı korur, tüm yasal haklara sahip olursunuz. Başvurudan sonuca kadar tüm süreci sizin için uçtan uca yönetiyoruz.</p>
        <div style="display:flex; gap:12px; flex-wrap:wrap;">
          <a href="{{ localized_route('iletisim') }}" style="display:inline-flex; align-items:center; gap:8px; padding:14px 30px; border-radius:12px; background:#b8b62e; color:#1a1a0e; font-weight:800; font-size:15px; text-decoration:none; box-shadow:0 10px 30px rgba(184,182,46,.35);"><i class="mdi mdi-send"></i> Başvuru / Teklif Al</a>
          <a href="#sss" style="display:inline-flex; align-items:center; gap:8px; padding:14px 30px; border-radius:12px; background:transparent; color:#fff; font-weight:700; font-size:15px; text-decoration:none; border:1.5px solid rgba(255,255,255,.3);">Sık Sorulan Sorular</a>
        </div>
      </div>
    </div>
  </div>
</section>

{{-- MARKA TESCİLİ NEDİR --}}
<section style="background:#fff; padding:60px 0;">
  <div class="container">
    <div class="row align-items-center g-4">
      <div class="col-lg-7">
        <span style="font-size:12px; font-weight:800; letter-spacing:.1em; color:#b8b62e; text-transform:uppercase;">Marka Tescili Nedir?</span>
        <h2 style="color:#0f172a; font-size:28px; font-weight:800; margin:8px 0 16px;">Emeğinizin Yasal Kalkanı</h2>
        <p style="color:#475569; font-size:16px; line-height:1.8; margin:0 0 14px;">Marka tescili; işletmenizin ürün ve hizmetlerini ayırt eden isim, logo veya sloganı yasal olarak koruma altına alan resmi bir sistemdir. Tescilli bir marka, izinsiz kullanıma karşı size <strong>münhasır kullanım hakkı</strong> ve gerektiğinde hukuki yollara başvurma imkânı sağlar.</p>
        <p style="color:#475569; font-size:16px; line-height:1.8; margin:0;">Tescilsiz bir marka, üzerinde yıllarca emek verseniz bile bir başkası tarafından tescil ettirilebilir. Bu da markanızı kaybetme riski demektir. Tescil, bu riski ortadan kaldırır.</p>
      </div>
      <div class="col-lg-5">
        <div style="background:#f7f7f0; border:1px solid #ececdf; border-radius:16px; padding:28px;">
          <div style="display:flex; gap:14px; margin-bottom:18px; align-items:flex-start;">
            <i class="mdi mdi-check-decagram" style="font-size:24px; color:#9a981f;"></i>
            <div><strong style="color:#1a1a1a;">Münhasır Hak</strong><div style="color:#64748b; font-size:14px;">Markanızı yalnızca siz kullanırsınız.</div></div>
          </div>
          <div style="display:flex; gap:14px; margin-bottom:18px; align-items:flex-start;">
            <i class="mdi mdi-scale-balance" style="font-size:24px; color:#9a981f;"></i>
            <div><strong style="color:#1a1a1a;">Hukuki Koruma</strong><div style="color:#64748b; font-size:14px;">Taklit ve izinsiz kullanıma karşı dava hakkı.</div></div>
          </div>
          <div style="display:flex; gap:14px; align-items:flex-start;">
            <i class="mdi mdi-calendar-check" style="font-size:24px; color:#9a981f;"></i>
            <div><strong style="color:#1a1a1a;">10 Yıl Geçerlilik</strong><div style="color:#64748b; font-size:14px;">Süre sonunda kolayca yenilenebilir.</div></div>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>

{{-- 3 ADIMDA TESCİL --}}
<section style="background:#f0f0ec; padding:60px 0;">
  <div class="container">
    <div style="text-align:center; margin-bottom:40px;">
      <span style="font-size:12px; font-weight:800; letter-spacing:.1em; color:#b8b62e; text-transform:uppercase;">Süreç</span>
      <h2 style="color:#0f172a; font-size:28px; font-weight:800; margin:8px 0 0;">3 Kolay Adımda Marka Tescili</h2>
    </div>
    <div class="row g-4">
      @foreach([
        ['1','mdi-file-document-outline','Başvurunuzu Oluşturun','Marka bilgilerinizi iletin; sizin için başvuru dosyasını hazırlayalım.'],
        ['2','mdi-magnify','Ön Değerlendirme','Uzmanlarımız benzerlik araştırması yapar ve 1 iş günü içinde sizi bilgilendirir.'],
        ['3','mdi-monitor-dashboard','Süreci Yönetin','Başvurudan tescil belgesine kadar tüm aşamaları dijital olarak takip edin.'],
      ] as $adim)
      <div class="col-md-4">
        <div style="background:#fff; border:1px solid #ececec; border-radius:16px; padding:32px 26px; height:100%; text-align:center; box-shadow:0 2px 8px rgba(0,0,0,.05);">
          <div style="width:64px; height:64px; margin:0 auto 18px; border-radius:16px; background:rgba(184,182,46,.14); display:flex; align-items:center; justify-content:center; position:relative;">
            <i class="mdi {{ $adim[1] }}" style="font-size:30px; color:#9a981f;"></i>
            <span style="position:absolute; top:-10px; right:-10px; width:28px; height:28px; border-radius:50%; background:#b8b62e; color:#1a1a0e; font-weight:800; font-size:14px; display:flex; align-items:center; justify-content:center;">{{ $adim[0] }}</span>
          </div>
          <h3 style="color:#0f172a; font-size:19px; font-weight:700; margin:0 0 10px;">{{ $adim[2] }}</h3>
          <p style="color:#64748b; font-size:14.5px; line-height:1.65; margin:0;">{{ $adim[3] }}</p>
        </div>
      </div>
      @endforeach
    </div>
  </div>
</section>

{{-- AVANTAJLAR --}}
<section style="background:#fff; padding:60px 0;">
  <div class="container">
    <div style="text-align:center; margin-bottom:40px;">
      <span style="font-size:12px; font-weight:800; letter-spacing:.1em; color:#b8b62e; text-transform:uppercase;">Neden Tescil?</span>
      <h2 style="color:#0f172a; font-size:28px; font-weight:800; margin:8px 0 0;">Marka Tescilinin Avantajları</h2>
    </div>
    <div class="row g-4">
      @foreach([
        ['mdi-shield','Yasal Koruma','Markanız kanunlarla korunur; izinsiz kullananlara karşı yaptırım hakkınız olur.'],
        ['mdi-basket','E-Ticaret Güvenliği','Pazaryerlerinde marka onayı ve taklit ürünlere karşı koruma sağlarsınız.'],
        ['mdi-calendar-range','10 Yıllık Kapsam','Tescil 10 yıl geçerlidir ve süresiz olarak yenilenebilir.'],
        ['mdi-gavel','İhlallere Karşı Dava','Marka hakkınızı ihlal edenlere karşı yasal süreç başlatabilirsiniz.'],
        ['mdi-trending-up','Marka Değeri','Tescilli marka; devredilebilir, lisanslanabilir bir ticari varlıktır.'],
        ['mdi-headset','Uzman Desteği','Başvuru ve itiraz süreçlerinde deneyimli ekibimiz yanınızda olur.'],
      ] as $av)
      <div class="col-md-6 col-lg-4">
        <div style="display:flex; gap:16px; align-items:flex-start; background:#f7f7f0; border:1px solid #ececdf; border-radius:14px; padding:22px; height:100%;">
          <div style="width:46px; height:46px; flex-shrink:0; border-radius:12px; background:#fff; border:1px solid #ececdf; display:flex; align-items:center; justify-content:center;">
            <i class="mdi {{ $av[0] }}" style="font-size:22px; color:#9a981f;"></i>
          </div>
          <div>
            <h3 style="color:#0f172a; font-size:17px; font-weight:700; margin:0 0 6px;">{{ $av[1] }}</h3>
            <p style="color:#64748b; font-size:14px; line-height:1.6; margin:0;">{{ $av[2] }}</p>
          </div>
        </div>
      </div>
      @endforeach
    </div>
  </div>
</section>

{{-- BELGELER & MARKA ÇEŞİTLERİ --}}
<section style="background:#f0f0ec; padding:60px 0;">
  <div class="container">
    <div class="row g-4">
      <div class="col-lg-6">
        <div style="background:#fff; border:1px solid #ececec; border-radius:16px; padding:32px; height:100%;">
          <h3 style="color:#0f172a; font-size:21px; font-weight:800; margin:0 0 18px;"><i class="mdi mdi-file-document-outline" style="color:#b8b62e;"></i> Gerekli Belgeler</h3>
          <ul style="list-style:none; padding:0; margin:0;">
            @foreach(['Marka örneği (logo / isim görseli)','Başvuru sahibi bilgileri (kişi veya firma)','Vekâletname (süreci biz yönetelim diye)','Markanın kullanılacağı sınıf/sektör bilgisi'] as $b)
            <li style="display:flex; gap:12px; align-items:flex-start; padding:11px 0; border-bottom:1px solid #f0f0ec;">
              <i class="mdi mdi-check-circle" style="color:#9a981f; font-size:19px;"></i>
              <span style="color:#475569; font-size:15px;">{{ $b }}</span>
            </li>
            @endforeach
          </ul>
        </div>
      </div>
      <div class="col-lg-6">
        <div style="background:#fff; border:1px solid #ececec; border-radius:16px; padding:32px; height:100%;">
          <h3 style="color:#0f172a; font-size:21px; font-weight:800; margin:0 0 18px;"><i class="mdi mdi-shape-outline" style="color:#b8b62e;"></i> Marka Çeşitleri</h3>
          <ul style="list-style:none; padding:0; margin:0;">
            @foreach([
              ['Ticaret Markası','Ürünler üzerinde kullanılan markalar.'],
              ['Hizmet Markası','Sunulan hizmetleri ayırt eden markalar.'],
              ['Garanti Markası','Belirli standartları garanti eden markalar.'],
              ['Ortak Marka','Bir grup/işletme topluluğunun kullandığı markalar.'],
            ] as $c)
            <li style="display:flex; gap:12px; align-items:flex-start; padding:11px 0; border-bottom:1px solid #f0f0ec;">
              <i class="mdi mdi-label-outline" style="color:#9a981f; font-size:19px;"></i>
              <span style="color:#475569; font-size:15px;"><strong style="color:#1a1a1a;">{{ $c[0] }}</strong> — {{ $c[1] }}</span>
            </li>
            @endforeach
          </ul>
        </div>
      </div>
    </div>
  </div>
</section>

{{-- SSS --}}
<section id="sss" style="background:#fff; padding:60px 0;">
  <div class="container">
    <div style="text-align:center; margin-bottom:40px;">
      <span style="font-size:12px; font-weight:800; letter-spacing:.1em; color:#b8b62e; text-transform:uppercase;">SSS</span>
      <h2 style="color:#0f172a; font-size:28px; font-weight:800; margin:8px 0 0;">Sık Sorulan Sorular</h2>
    </div>
    <div style="max-width:820px; margin:0 auto;">
      @foreach([
        ['Marka tescili ne kadar sürede tamamlanır?','Başvuru sonrası resmî inceleme ve yayın süreçleriyle birlikte ortalama 6-12 ay sürer. Ön değerlendirme ise 1 iş günü içinde yapılır.'],
        ['Tescil kaç yıl geçerlidir?','Marka tescili 10 yıl boyunca geçerlidir ve süre sonunda süresiz olarak yenilenebilir.'],
        ['Başvurudan önce benzerlik araştırması şart mı?','Zorunlu olmasa da güçlü tavsiye edilir. Benzer bir marka varsa başvurunuz reddedilebilir; ön araştırma bu riski azaltır.'],
        ['Markamı hangi sınıflarda tescil ettirmeliyim?','Markanın kullanılacağı ürün/hizmet alanlarına göre sınıf seçilir. Doğru sınıflandırmayı birlikte belirleriz.'],
        ['Süreci kendim mi takip edeceğim?','Hayır. Vekâlet ile tüm başvuru, bildirim ve itiraz süreçlerini biz yönetir, sizi her aşamada bilgilendiririz.'],
      ] as $sss)
      <details style="background:#f7f7f0; border:1px solid #ececdf; border-radius:12px; padding:0; margin-bottom:12px;">
        <summary style="cursor:pointer; padding:18px 22px; font-weight:700; color:#1a1a1a; font-size:16px; list-style:none; display:flex; justify-content:space-between; align-items:center;">
          {{ $sss[0] }}
          <i class="mdi mdi-plus" style="color:#9a981f;"></i>
        </summary>
        <div style="padding:0 22px 20px; color:#475569; font-size:15px; line-height:1.7;">{{ $sss[1] }}</div>
      </details>
      @endforeach
    </div>
  </div>
</section>

{{-- CTA STRIP --}}
<section style="background:linear-gradient(115deg,#b8b62e 0%,#d8d64a 100%); padding:48px 0;">
  <div class="container">
    <div class="row align-items-center">
      <div class="col-lg-8">
        <h3 style="color:#1a1a0e; font-size:26px; font-weight:800; margin:0 0 6px;">Markanızı korumaya hazır mısınız?</h3>
        <p style="color:#3a3a1e; font-size:16px; margin:0;">Uzman ekibimiz başvurunuzu baştan sona yönetsin. Hemen teklif alın.</p>
      </div>
      <div class="col-lg-4 text-lg-end mt-3 mt-lg-0">
        <a href="{{ localized_route('iletisim') }}" style="display:inline-flex; align-items:center; gap:8px; padding:15px 34px; border-radius:12px; background:#16160f; color:#fff; font-weight:800; font-size:16px; text-decoration:none;"><i class="mdi mdi-send"></i> Başvuru / Teklif Al</a>
      </div>
    </div>
  </div>
</section>

<style>
  #sss details[open] summary .mdi-plus:before { content:"\F0374"; } /* minus */
</style>
@endsection
