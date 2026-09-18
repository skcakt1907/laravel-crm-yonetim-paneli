@extends('layouts.master')

@section('title', __('messages.domain_registration'))

@section('head')
<link rel="stylesheet" href="{{ asset('tema/css/master-layout.css') }}">
<link rel="stylesheet" href="{{ asset('tema/css/domain-tescil.css') }}">
@if(request()->routeIs('domain.sorgula') || request()->has('domain'))
<meta name="domain-sorgula-url" content="{{ route('domain.sorgula') }}">
<meta name="sepet-domain-url" content="{{ route('sepet.domain.ekle') }}">
@endif
@endsection

@push('styles')
<style>
  .domain-features-grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 30px !important;
  }

  .domain-feature-card {
    margin: 0 !important;
  }

  @media (max-width: 768px) {
    .domain-features-grid {
      grid-template-columns: 1fr !important;
      gap: 18px !important;
    }
  }
</style>
@endpush

@section('content')
@php
  // YENİ TABLO: admin "domain_fiyatlar"a yazıyor. Ön yüz de oradan okusun.
  // Blade'in beklediği $alanadi->uzanti[] / kayit[] / yenileme[] dizi yapısı üretilir.
  if (\Illuminate\Support\Facades\Schema::hasTable('domain_fiyatlar')) {
    $sorgu = DB::table('domain_fiyatlar')->where('durum', 1);
    // sira kolonu varsa admin'in sürükle-bırak sırasını kullan, yoksa alfabetik
    if (\Illuminate\Support\Facades\Schema::hasColumn('domain_fiyatlar', 'sira')) {
        $sorgu->orderBy('sira', 'asc')->orderBy('uzanti', 'asc');
    } else {
        $sorgu->orderBy('uzanti', 'asc');
    }
    $satirlar = $sorgu->get();
    $alanadi = (object) [
      'uzanti'   => [],
      'kayit'    => [],
      'yenileme' => [],
    ];
    foreach ($satirlar as $s) {
      $alanadi->uzanti[]   = $s->uzanti;
      $alanadi->kayit[]    = (float) $s->kayit_fiyat;
      $alanadi->yenileme[] = (float) $s->yenileme_fiyat;
    }
  } else {
    $alanadi = DB::table('alanadi')->where('id', 1)->first();
    if ($alanadi) {
      $alanadi->uzanti = json_decode($alanadi->uzanti ?? '[]', true);
      $alanadi->kayit = json_decode($alanadi->kayit ?? '[]', true);
      $alanadi->yenileme = json_decode($alanadi->yenileme ?? '[]', true);
    }
  }
  
  use Illuminate\Support\Facades\DB;
  $headerBgStyle = \App\Helpers\HeaderBackgroundHelper::getHeaderBackgroundStyle('domain');
  
  $currency = request('currency') ?? session('currency', 'TRY');
  $currency_symbol = match($currency) {
    'USD' => '$',
    'EUR' => '€',
    'AED' => 'د.إ',
    default => '₺'
  };
@endphp

@if(request('durum') == 'success')
<script>
  Swal.fire({
    type: 'success',
    title: 'Başarılı',
    text: 'Alan adı sepetinize eklendi',
    confirmButtonText: 'Tamam',
    timer: 5000
  });
</script>
@endif

<div style="background:#fff; padding: 110px 0 26px; border-bottom:1px solid #e8e8e2;">
  <div class="container">
    <div style="font-size:13px; color:#999; margin-bottom:10px;">
      <a href="{{ route('anasayfa') }}" style="color:#999; text-decoration:none;">{{ __('messages.home') }}</a>
      <span style="color:#b8b62e; margin:0 6px;">&rsaquo;</span>
      <span style="color:#1a1a1a; font-weight:600;">{{ __('messages.domain_registration') }}</span>
    </div>
    <h1 style="color:#1a1a1a; font-size:28px; font-weight:700; margin:0;">{{ __('messages.domain_registration') }}</h1>
    <p style="color:#999; font-size:14px; margin:6px 0 0;">{{ __('messages.domain_registration_description') }}</p>
  </div>
</div>

<section class="search-domain section-padding p-5" style="background:#fff; padding: 50px 0 !important;">
  <div class="container">
    <div class="row justify-content-center">
      <div class="col-lg-10 col-md-12 col-sm-12 col-xs-12 centered wow fadeInUp" data-wow-delay="0.3s">
        <h2 class="mb-4 text-white text-center" style="font-size: 36px; font-weight: 700; margin-bottom: 30px !important;">{{ __('messages.search_domain') }}</h2>
        <div class="search-domain-content" style="background:#fff; border-radius: 16px; padding: 32px; box-shadow: 0 2px 8px rgba(0,0,0,0.06); border: 1px solid #ececec;">
          <form action="" id="domainForm" style="display: flex; gap: 15px; flex-wrap: wrap; align-items: stretch;">
            @csrf
            <input type="text" name="alanadi" id="alanadi" placeholder="{{ __('messages.enter_domain') }}" required style="flex: 1; min-width: 200px; padding: 15px 20px; font-size: 16px; border: 1px solid #e0e0d8; border-radius: 12px; background: #f7f7f4; color: #1a1a1a; outline: none; transition: all 0.3s ease;" onfocus="this.style.borderColor='rgba(184, 182, 46, 0.8)'; this.style.background='#fff';" onblur="this.style.borderColor='#e0e0d8'; this.style.background='#f7f7f4';">
            <select name="uzanti" id="uzanti" style="flex: 0 0 200px; padding: 15px 20px; font-size: 16px; border: 1px solid #e0e0d8; border-radius: 12px; background: #f7f7f4; color: #1a1a1a; outline: none; cursor: pointer; transition: all 0.3s ease;" onfocus="this.style.borderColor='rgba(184, 182, 46, 0.8)'; this.style.background='#fff';" onblur="this.style.borderColor='#e0e0d8'; this.style.background='#f7f7f4';">
              @if($alanadi && is_array($alanadi->uzanti))
                @foreach($alanadi->uzanti as $k => $v)
                  @php 
                    $degisken = explode(".", $v); 
                  @endphp
                  <option value="{{ $degisken[1] }}{{ isset($degisken[2]) && $degisken[2] ? '.' : '' }}{{ $degisken[2] ?? '' }}" style="background:#fff; color:#1a1a1a;">{{ $v }}</option>
                @endforeach
              @endif
            </select>
            <button class="bttn btn-fill" type="submit" id="domainSorgula" style="flex: 0 0 auto; padding: 15px 40px; font-size: 16px; font-weight: 700; background:#b8b62e; color:#1a1a0e; border: none; border-radius: 12px; cursor: pointer; transition: all 0.3s ease; box-shadow: 0 10px 30px rgba(184, 182, 46, 0.35);" onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 15px 40px rgba(184, 182, 46, 0.5)';" onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 10px 30px rgba(184, 182, 46, 0.35)';">{{ __('messages.query') }}</button>
          </form>
        </div>
        <div class="domain-type" style="display: grid; grid-template-columns: repeat(5, 1fr); gap: 15px; margin-top: 40px;">
          @if($alanadi && is_array($alanadi->uzanti))
            @foreach($alanadi->uzanti as $k => $v)
              @if($k > 4) @break @endif
              <div class="single-domain-type" style="background:#fff; border-radius: 14px; padding: 20px; text-align: center; border: 1px solid #ececec; box-shadow:0 2px 8px rgba(0,0,0,.06); transition: all 0.3s ease; width: 100%; height: 120px; display: flex; flex-direction: column; justify-content: center; align-items: center;" onmouseover="this.style.transform='translateY(-5px)'; this.style.borderColor='rgba(184, 182, 46, 0.5)'; this.style.boxShadow='0 8px 24px rgba(184, 182, 46, 0.18)';" onmouseout="this.style.transform='translateY(0)'; this.style.borderColor='#ececec'; this.style.boxShadow='0 2px 8px rgba(0,0,0,.06)';">
                <h3 style="color: #1a1a1a; font-size: 16px; font-weight: 700; margin: 0 0 8px 0; line-height: 1.2;">{{ $v }}/</h3>
                <span style="color: #b8b62e; font-size: 18px; font-weight: 700;">{{ \App\Helpers\DovizKuruHelper::fiyatGoster($alanadi->kayit[$k] ?? 0, $currency) }} {{ $currency_symbol }}</span>
              </div>
            @endforeach
          @endif
        </div>
        <div class="domainBilgileri" id="domainBilgileri" style="margin-top: 30px;"></div>
      </div>
    </div>
  </div>
</section>

@if($alanadi && is_array($alanadi->uzanti) && count($alanadi->uzanti) > 0)
<section class="modern-section" style="background:#f0f0ec; padding: 50px 0;">
  <div class="container">
    <div class="row">
      <div class="col-sm-12 text-center mb-5">
        <h2 style="color: #1a1a1a; font-size: 30px; font-weight: 700; margin-bottom: 15px;">{{ __('messages.domain_extension') }}</h2>
        <p style="color: #777; font-size: 16px;">{{ __('messages.domain_pricing_table') }}</p>
      </div>
    </div>
    <div class="row">
      <div class="col-sm-12">
        <div style="background:#fff; border-radius: 16px; padding: 30px; box-shadow: 0 2px 8px rgba(0,0,0,0.06); border: 1px solid #ececec; overflow-x: auto;">
          <table style="width: 100%; border-collapse: collapse;">
            <thead>
              <tr style="border-bottom: 2px solid rgba(184, 182, 46, 0.5);">
                <th style="padding: 20px; text-align: left; color: #b8b62e; font-size: 18px; font-weight: 700;">{{ __('messages.domain_extension') }}</th>
                <th style="padding: 20px; text-align: center; color: #b8b62e; font-size: 18px; font-weight: 700;">{{ __('messages.registration_price') }}</th>
                <th style="padding: 20px; text-align: center; color: #b8b62e; font-size: 18px; font-weight: 700;">{{ __('messages.renewal_price') }}</th>
              </tr>
            </thead>
            <tbody>
              @foreach($alanadi->uzanti as $k => $v)
                <tr style="border-bottom: 1px solid rgba(15,23,42,.08); transition: all 0.3s ease;" onmouseover="this.style.background='rgba(184, 182, 46, 0.10)';" onmouseout="this.style.background='transparent';">
                  <td style="padding: 20px;">
                    <span style="display: inline-block; padding: 8px 15px; background: rgba(184, 182, 46, 0.2); color: #b8b62e; border-radius: 8px; font-weight: 700; font-size: 14px;">{{ $v }}</span>
                  </td>
                  <td style="padding: 20px; text-align: center; color: #1a1a1a; font-size: 16px; font-weight: 600;">{{ \App\Helpers\DovizKuruHelper::fiyatGoster($alanadi->kayit[$k] ?? 0, $currency) }} {{ $currency_symbol }}</td>
                  <td style="padding: 20px; text-align: center; color: #1a1a1a; font-size: 16px; font-weight: 600;">{{ \App\Helpers\DovizKuruHelper::fiyatGoster($alanadi->yenileme[$k] ?? 0, $currency) }} {{ $currency_symbol }}</td>
                </tr>
              @endforeach
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</section>
@endif

<!-- ***** FEATURES ***** -->
<section id="scroll" class="history-section feat01 sec-normal" style="background:#f0f0ec; padding: 50px 0;">
  <div class="container">
    <div class="domain-features-grid">
      <div class="domain-feature-card" style="background:#fff; border-radius: 16px; padding: 35px; box-shadow: 0 2px 8px rgba(0,0,0,0.06); border: 1px solid #ececec; min-height: 250px; display: flex; flex-direction: column;">
        <div style="width: 60px; height: 60px; background: rgba(184, 182, 46, 0.2); border-radius: 15px; display: flex; align-items: center; justify-content: center; margin-bottom: 20px; flex-shrink: 0;">
          <i class="mdi mdi-web" style="font-size: 28px; color: #b8b62e;"></i>
        </div>
        <h4 style="color: #1a1a1a; font-size: 22px; font-weight: 700; margin-bottom: 15px; flex-shrink: 0;">{{ __('messages.domain_features_title') }}</h4>
        <p style="color: #777; font-size: 15px; line-height: 1.7; flex: 1; margin: 0;">{{ __('messages.domain_features_description') }}</p>
      </div>
      <div class="domain-feature-card" style="background:#fff; border-radius: 16px; padding: 35px; box-shadow: 0 2px 8px rgba(0,0,0,0.06); border: 1px solid #ececec; min-height: 250px; display: flex; flex-direction: column;">
        <div style="width: 60px; height: 60px; background: rgba(184, 182, 46, 0.2); border-radius: 15px; display: flex; align-items: center; justify-content: center; margin-bottom: 20px; flex-shrink: 0;">
          <i class="mdi mdi-headset" style="font-size: 28px; color: #b8b62e;"></i>
        </div>
        <h4 style="color: #1a1a1a; font-size: 22px; font-weight: 700; margin-bottom: 15px; flex-shrink: 0;">{{ __('messages.domain_support_title') }}</h4>
        <p style="color: #777; font-size: 15px; line-height: 1.7; flex: 1; margin: 0;">{{ __('messages.domain_support_description') }}</p>
      </div>
    </div>
  </div>
</section>

@endsection


@push('scripts')
<script src="{{ asset('tema/js/master-layout.js') }}"></script>
<script>
  // Mevcut formu yeni sisteme entegre et
  document.addEventListener('DOMContentLoaded', function() {
    const domainForm = document.getElementById('domainForm');
    const alanadiInput = document.getElementById('alanadi');
    const uzantiSelect = document.getElementById('uzanti');
    const domainSorgulaBtn = document.getElementById('domainSorgula');
    const domainBilgileri = document.getElementById('domainBilgileri');
    const currencySymbol = '{{ $currency_symbol }}';
    
    // Event delegation ile sepete ekle butonunu dinle
    document.addEventListener('click', function(e) {
      const addToCartBtn = e.target.closest('.add-to-cart-domain');
      if (addToCartBtn) {
        e.preventDefault();
        e.stopPropagation();
        
        // Eğer bir link ise href'i engelle
        if (addToCartBtn.tagName === 'A') {
          e.preventDefault();
        }
        
        const btn = addToCartBtn;
        const domain = btn.getAttribute('data-domain') || btn.dataset.domain;
        const price = parseFloat(btn.getAttribute('data-price') || btn.dataset.price || 0);
        
        if (!domain) {
          console.error('Domain bilgisi eksik');
          return;
        }
        // Not: fiyat 0/boş gelse bile devam et — backend (sepeteEkle) fiyatı uzantıdan otomatik çeker.
        
        btn.disabled = true;
        btn.innerHTML = '<i class="mdi mdi-loading"></i> Ekleniyor...';
        
        // AJAX ile sepete ekle
        const formData = new FormData();
        formData.append('domain', domain);
        formData.append('fiyat', price);
        formData.append('_token', document.querySelector('meta[name="csrf-token"]')?.content || '');
        
        fetch('{{ route('sepet.domain.ekle') }}', {
          method: 'POST',
          body: formData,
          headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': 'application/json'
          },
          credentials: 'same-origin'
        })
          .then(response => {
            // Status kontrolü
            if (!response.ok && response.status === 401) {
              // Giriş gerekli
              return response.json().then(data => {
                if (data.redirect) {
                  window.location.href = data.redirect;
                  return { success: false, redirect: true };
                }
                return data;
              });
            }
            
            // JSON parse et
            return response.json().catch(err => {
              console.error('JSON parse hatası:', err);
              return { success: false, message: 'Sunucu yanıtı işlenemedi' };
            });
          })
          .then(data => {
            if (data && data.redirect) {
              return; // Redirect zaten yapıldı
            }
            
            if (data && data.success) {
              // Başarılı mesajı göster ve sepete yönlendir
              Swal.fire({
                icon: 'success',
                title: 'Başarılı',
                text: data.message || 'Domain sepete eklendi!',
                timer: 2000,
                showConfirmButton: false
              }).then(() => {
                window.location.href = '{{ route("sepet") }}';
              });
            } else {
              // Hata mesajı göster
              Swal.fire({
                icon: 'error',
                title: 'Hata',
                text: (data && data.message) || 'Sepete eklenirken hata oluştu'
              });
              btn.disabled = false;
              btn.innerHTML = '<i class="mdi mdi-basket"></i> Sepete Ekle';
              
              // Redirect varsa yönlendir
              if (data && data.redirect) {
                setTimeout(() => {
                  window.location.href = data.redirect;
                }, 2000);
              }
            }
          })
          .catch(error => {
            console.error('Sepete ekleme hatası:', error);
            btn.disabled = false;
            btn.innerHTML = '<i class="mdi mdi-basket"></i> Sepete Ekle';
            Swal.fire({
              icon: 'error',
              title: 'Hata',
              text: 'Sepete eklenirken hata oluştu: ' + error.message
            });
          });
      }
    });
    
    if (domainForm && alanadiInput && uzantiSelect && domainSorgulaBtn) {
      domainForm.addEventListener('submit', function(e) {
        e.preventDefault();
        
        const domain_name = alanadiInput.value.trim();
        const extension = uzantiSelect.value;
        
        if (!domain_name) {
          Swal.fire({
            icon: 'error',
            title: 'Hata',
            text: 'Lütfen bir domain adı girin'
          });
          return;
        }
        
        const full_domain = domain_name + '.' + extension;
        
        domainSorgulaBtn.disabled = true;
        domainSorgulaBtn.textContent = 'Kontrol Ediliyor...';
        domainBilgileri.innerHTML = '<div class="text-center"><i class="mdi mdi-loading"></i> Kontrol ediliyor...</div>';
        
        // Mevcut sistemi kullan (domain.sorgula route'u)
        const formData = new FormData();
        formData.append('alanadi', domain_name);
        formData.append('uzanti', extension);
        formData.append('currency', '{{ $currency }}');
        formData.append('_token', document.querySelector('meta[name="csrf-token"]')?.content || '');

        fetch('{{ route('domain.sorgula') }}', {
          method: 'POST',
          body: formData,
          headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': 'application/json'
          }
        })
          .then(response => {
            console.log('Response status:', response.status);
            console.log('Response URL:', response.url);
            
            if (!response.ok) {
              // 404 veya başka bir hata
              return response.text().then(text => {
                console.error('HTTP Error Response:', text);
                throw new Error('HTTP error! status: ' + response.status + ' - ' + text.substring(0, 100));
              });
            }
            return response.json();
          })
          .then(data => {
            console.log('Response data:', data);
            domainSorgulaBtn.disabled = false;
            domainSorgulaBtn.textContent = '{{ __('messages.query') }}';
            
            if (!data || !data.success) {
              Swal.fire({
                icon: 'error',
                title: 'Hata',
                text: data.message || 'Domain kontrol edilemedi'
              });
              domainBilgileri.innerHTML = '<div class="alert alert-danger">' + (data.message || 'Domain kontrol edilemedi') + '</div>';
              return;
            }
            
            // Sonuçları göster
            if (data.results && data.results.length > 0) {
              const result = data.results[0]; // İlk sonuç
              const available = result.musait || false;
              const price = result.kayit_fiyat || 0;
              
              let html = '<div class="domain-result" style="background:#fff; border-radius: 16px; padding: 30px; box-shadow: 0 2px 8px rgba(0,0,0,0.06); border: 1px solid #ececec;">';
              html += '<h4 style="color: #1a1a1a; font-size: 24px; font-weight: 700; margin-bottom: 20px;">' + full_domain + '</h4>';
              
              if (available) {
                html += '<div style="background: rgba(40, 167, 69, 0.2); border: 2px solid rgba(40, 167, 69, 0.5); border-radius: 12px; padding: 15px; margin-bottom: 20px; display: flex; align-items: center; gap: 10px;">';
                html += '<i class="mdi mdi-link" style="font-size: 24px; color: #28a745;"></i>';
                html += '<span style="color: #1a1a1a; font-size: 16px; font-weight: 600;">Bu domain müsait!</span>';
                html += '</div>';
                html += '<div style="background: rgba(184, 182, 46, 0.10); border-radius: 12px; padding: 20px; margin-bottom: 20px;">';
                html += '<div style="color: #777; font-size: 14px; margin-bottom: 5px;">Fiyat</div>';
                html += '<div style="color: #b8b62e; font-size: 28px; font-weight: 700;">' + price.toFixed(2) + ' ' + currencySymbol + ' <span style="font-size: 16px; color: #777;">/ yıl</span></div>';
                html += '</div>';
                html += '<div class="domain-actions">';
                html += '<button type="button" class="add-to-cart-domain" data-domain="' + full_domain + '" data-price="' + price + '" style="width: 100%; padding: 15px 30px; font-size: 16px; font-weight: 700; background:#b8b62e; color:#1a1a0e; border: none; border-radius: 12px; cursor: pointer; transition: all 0.3s ease; box-shadow: 0 10px 30px rgba(184, 182, 46, 0.35);">';
                html += '<i class="mdi mdi-basket"></i> Sepete Ekle';
                html += '</button>';
                html += '</div>';
              } else {
                html += '<div style="background: rgba(220, 53, 69, 0.2); border: 2px solid rgba(220, 53, 69, 0.5); border-radius: 12px; padding: 15px; display: flex; align-items: center; gap: 10px;">';
                html += '<i class="mdi mdi-link" style="font-size: 24px; color: #dc3545;"></i>';
                html += '<span style="color: #c0392b; font-size: 16px; font-weight: 600;">Bu domain müsait değil</span>';
                html += '</div>';
              }
              
              html += '</div>';
              domainBilgileri.innerHTML = html;
          })
          .catch(error => {
            console.error('Domain sorgulama hatası:', error);
            console.error('Error details:', {
              message: error.message,
              stack: error.stack
            });
            domainSorgulaBtn.disabled = false;
            domainSorgulaBtn.textContent = '{{ __('messages.query') }}';
            
            let errorMessage = 'Domain kontrol edilirken hata oluştu';
            if (error.message.includes('404')) {
              errorMessage = 'Sayfa bulunamadı (404). Route kontrol ediliyor...';
            } else if (error.message) {
              errorMessage = error.message;
            }
            
            Swal.fire({
              icon: 'error',
              title: 'Hata',
              text: errorMessage
            });
            domainBilgileri.innerHTML = '<div class="alert alert-danger">' + errorMessage + '</div>';
          });
      });
    }
  });
</script>
@endpush