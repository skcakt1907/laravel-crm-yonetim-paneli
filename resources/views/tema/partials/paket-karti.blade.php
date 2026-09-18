{{-- Paket Kartı Partial - Hem anasayfa hem paketler sayfasında kullanılır --}}
<style>
    .paket-karti .paket-resim-overlay {
        position: absolute;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%) scale(0.96);
        display: flex;
        flex-direction: column;
        gap: 8px;
        opacity: 0;
        pointer-events: none;
        transition: all 0.25s ease;
        z-index: 2;
    }

    .paket-karti:hover .paket-resim-overlay {
        opacity: 1;
        transform: translate(-50%, -50%) scale(1);
        pointer-events: auto;
    }

    .paket-karti .paket-resim-overlay a {
        min-width: 140px;
    }

    .paket-karti .paket-resim::after,
    .paket-karti .paket-resim-placeholder::after {
        content: "";
        position: absolute;
        inset: 0;
        background: linear-gradient(to bottom, rgba(0, 0, 0, 0.05), rgba(0, 0, 0, 0.45));
        opacity: 0;
        transition: opacity 0.25s ease;
        z-index: 1;
        pointer-events: none;
    }

    .paket-karti:hover .paket-resim::after,
    .paket-karti:hover .paket-resim-placeholder::after {
        opacity: 1;
    }

    /* 2. Yol: Tüm paket görsellerini "logo kutusu" gibi göster
       - Resim ortada, etrafında koyu arkaplan
       - Böylece resmin kendi içindeki beyaz boşluklar kartın kenarına yapışmıyor */
    .paket-karti .paket-resim-container {
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .paket-karti .paket-resim,
    .paket-karti .paket-resim-placeholder img {
        object-fit: contain;
        background: var(--wt-bg-alt, #eef0f4);
        border-radius: 12px;
        box-shadow: none;
    }

</style>
@php
    $resim_yolu = null;
    $noImagePath = asset('tema/img/noimage.png');

    // Önce paketin kapak resmini (yazilimlar.resim) kontrol et — admin paneli de bunu gösterir
    if (!empty($yazilim->resim) && $yazilim->resim !== '0' && $yazilim->resim !== 'null') {
        $resim_yolu = get_package_image_path($yazilim->resim, true); // true = null döndür eğer bulunamazsa
    }

    // Kapak yoksa yedek olarak webpaketresim (galeri) tablosundaki ilk resmi kullan
    if (!$resim_yolu && isset($yazilim->id)) {
        try {
            if (\Illuminate\Support\Facades\Schema::hasTable('webpaketresim')) {
                $ilkResim = \Illuminate\Support\Facades\DB::table('webpaketresim')
                    ->where('rid', $yazilim->id)
                    ->orderBy('id', 'asc')
                    ->value('resim');

                if ($ilkResim) {
                    $resim_yolu = get_package_image_path($ilkResim, true); // true = null döndür eğer bulunamazsa
                }
            }
        } catch (\Exception $e) {
            // Sessiz geç
        }
    }

    // Son çare: noimage kullan
    if (!$resim_yolu) {
        $resim_yolu = $noImagePath;
    }

    $locale = app()->getLocale() ?: session('locale', 'tr');

    // Dil bazlı para birimi ayarla
    $currency = request('currency') ?? session('currency');
    if (!$currency) {
        $currency = match($locale) {
            'en' => 'USD',
            'ar' => 'AED',
            default => 'TRY'
        };
    }

    // Fiyat bilgisini al (farklı şema isimleri için fallback)
    $tutarRaw = $yazilim->tutar ?? $yazilim->fiyat ?? null;
    $normalizedPrice = preg_replace('/[^0-9,.\-]/', '', (string) $tutarRaw);
    if (str_contains($normalizedPrice, ',') && str_contains($normalizedPrice, '.')) {
        // 1.234,56 -> 1234.56
        $normalizedPrice = str_replace('.', '', $normalizedPrice);
        $normalizedPrice = str_replace(',', '.', $normalizedPrice);
    } else {
        // 1234,56 -> 1234.56
        $normalizedPrice = str_replace(',', '.', $normalizedPrice);
    }
    $tutarNum = $normalizedPrice !== '' ? (float) $normalizedPrice : 0.0;
    $isZeroPrice = ($tutarRaw === null || $tutarRaw === '' || abs($tutarNum) < 0.000001);

    if ($isZeroPrice) {
        // Fiyat girilmemiş / 0 olan paketlerde sadece "TL" göster
        $fiyatGosterim = 'TL';
    } else {
        $fiyat = \App\Helpers\DovizKuruHelper::fiyatGoster($tutarRaw, $currency);

        // Dil bazlı sembol ve format
        $sembol = match($currency) {
            'USD' => '$',
            'EUR' => '€',
            'AED' => 'د.إ',
            default => '₺'
        };

        // Arapça için fiyat formatı (sembol sonda, RTL)
        if ($locale === 'ar' && $currency === 'AED') {
            $fiyatGosterim = $fiyat . ' ' . $sembol;
        } else {
            // Diğer diller için sembol başta
            $fiyatGosterim = $sembol . $fiyat;
        }
    }

    // Paket başlığı
    if ($locale === 'en' && !empty($yazilim->adi_en)) {
        $paketBaslik = $yazilim->adi_en;
    } elseif ($locale === 'ar' && !empty($yazilim->adi_ar)) {
        $paketBaslik = $yazilim->adi_ar;
    } else {
        $paketBaslik = $yazilim->adi;
    }

    // Buton metinleri - manuel çeviri (__() helper'ı bazen çalışmıyor)
    $viewDetailsText = \Illuminate\Support\Facades\Lang::get('messages.view_details', [], $locale);
    $buyNowText = \Illuminate\Support\Facades\Lang::get('messages.buy_now', [], $locale);
    $addToCartText = \Illuminate\Support\Facades\Lang::get('messages.add_to_cart', [], $locale);
@endphp
@php
    // Anasayfada öne çıkan kartlarda fiyatı başlığın yanında göstermek için kullanılır.
    $inlinePrice = isset($inlinePrice) ? (bool) $inlinePrice : false;
    $hitCount = $yazilim->hit_count ?? 0;
    $satisCount = $yazilim->satis_count ?? 0;
@endphp

<div class="paket-karti" style="border-radius: 14px; overflow: hidden; display: flex; flex-direction: column; transition: all 0.3s ease; width: 100%; min-height: 320px; height: 100%;">
    <div class="paket-resim-container" style="position: relative; width: 100%; padding-top: 100%; overflow: hidden; flex-shrink: 0;">
        @if($resim_yolu && $resim_yolu != asset('tema/img/noimage.png'))
        <img src="{{ $resim_yolu }}" alt="{{ $paketBaslik }}" class="paket-resim" style="position: absolute; top: 0; left: 0; width: 100%; height: 100%; object-fit: contain; object-position: center center; background: var(--wt-bg-alt, #eef0f4); padding: 12px; display: block;" loading="lazy" onerror="this.src='{{ asset('tema/img/noimage.png') }}'; this.onerror=null;">
        @else
        <div class="paket-resim-placeholder" style="position: absolute; top: 0; left: 0; width: 100%; height: 100%; display: flex; align-items: center; justify-content: center; background: var(--wt-bg-alt, #eef0f4);">
            <img src="{{ asset('tema/img/noimage.png') }}" alt="{{ $paketBaslik }}" style="width: 100%; height: 100%; object-fit: contain; opacity: 0.5;" onerror="this.style.display='none';">
        </div>
        @endif

        {{-- Hover'da görünen butonlar --}}
        <div class="paket-resim-overlay">
            <a href="{{ localized_route('paket.detay', $yazilim->seo) }}" class="btn-detay" style="padding: 10px 18px; font-size: 13px; text-align: center; border-radius: 999px; text-decoration: none; display: inline-flex; align-items: center; justify-content: center; font-weight: 600; background: #fff; color: var(--wt-accent, #4f46e5); border: 2px solid var(--wt-accent, #4f46e5); box-shadow: 0 4px 12px rgba(0,0,0,0.1);">
                <i class="mdi mdi-eye" style="margin-right: 5px;"></i> {{ $viewDetailsText }}
            </a>
            @if(!isset($moduller) || (isset($moduller) && $moduller->alan6 == "1"))
            <a href="{{ localized_route('web.paket.satinal', $yazilim->id) }}" class="btn-satin-al" style="padding: 10px 18px; font-size: 13px; text-align: center; border-radius: 999px; text-decoration: none; display: inline-flex; align-items: center; justify-content: center; font-weight: 700; background: var(--wt-accent, #4f46e5); color: #fff; border: none;">
                <i class="mdi mdi-cart" style="margin-right: 5px;"></i> {{ $buyNowText }}
            </a>
            <a href="{{ localized_route('web.paket.satinal', ['id' => $yazilim->id, 'mode' => 'sepet']) }}" class="btn-sepete-ekle" style="padding: 10px 18px; font-size: 13px; text-align: center; border-radius: 999px; text-decoration: none; display: inline-flex; align-items: center; justify-content: center; font-weight: 600; background: #fff; color: var(--wt-accent, #4f46e5); border: 1px solid var(--wt-border-strong, #d1d5db); box-shadow: 0 2px 8px rgba(0,0,0,0.06);">
                <i class="mdi mdi-basket" style="margin-right: 5px;"></i> {{ $addToCartText }}
            </a>
            @endif
        </div>
    </div>

        <div class="paket-icerik" style="padding: 15px; display: flex; flex-direction: column; flex: 1;">
        <div class="paket-bilgi" style="flex: 1;">
            @if($inlinePrice)
                {{-- Goruntulenme ve satis sayisi (anasayfa kartlari) --}}
                <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 8px;">
                    <span style="display: inline-flex; align-items: center; gap: 4px; font-size: 11px; color: var(--wt-text-muted, #94a3b8);">
                        <i class="mdi mdi-eye-outline" style="font-size: 13px;"></i> {{ __('messages.n_views', ['adet' => $hitCount]) }}
                    </span>
                    <span style="display: inline-flex; align-items: center; gap: 4px; font-size: 11px; color: var(--wt-text-muted, #94a3b8);">
                        <i class="mdi mdi-cart-outline" style="font-size: 13px;"></i> {{ __('messages.n_sales', ['adet' => $satisCount]) }}
                    </span>
                </div>
                <div class="paket-baslik-row" style="display: flex; align-items: flex-start; justify-content: space-between; gap: 10px; margin-bottom: 8px;">
                    <h3 class="paket-baslik" style="color: var(--wt-heading, #0f172a); font-size: 14px; font-weight: 700; margin-bottom: 0; line-height: 1.35; margin-top: 0; min-height: 38px; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; flex: 1;">
                        <a href="{{ localized_route('paket.detay', $yazilim->seo) }}" style="color: var(--wt-heading, #0f172a); text-decoration: none; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden;">{{ $paketBaslik }}</a>
                    </h3>
                    <span class="paket-fiyat" style="background: var(--wt-accent, #4f46e5); color: #fff; font-weight: 700; font-size: 14px; padding: 6px 14px; border-radius: 8px; display: inline-block; direction: {{ $locale === 'ar' ? 'rtl' : 'ltr' }};">
                        {{ $fiyatGosterim }}
                    </span>
                </div>
            @else
                <h3 class="paket-baslik" style="color: var(--wt-heading, #0f172a); font-size: 14px; font-weight: 700; margin-bottom: 10px; line-height: 1.35; margin-top: 0; min-height: 38px; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden;">
                    <a href="{{ localized_route('paket.detay', $yazilim->seo) }}" style="color: var(--wt-heading, #0f172a); text-decoration: none; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden;">{{ $paketBaslik }}</a>
                </h3>
            @endif

            <div class="paket-kategoriler" style="display: flex; flex-wrap: wrap; gap: 8px; margin-bottom: 15px; min-height: 28px;">
            @if(isset($yazilim->kategoriler) && $yazilim->kategoriler->count() > 0)
                @foreach($yazilim->kategoriler->take(1) as $kategori)
                    <span class="paket-kategori-badge" style="display: inline-block; padding: 5px 12px; background: var(--wt-accent-bg, rgba(79,70,229,0.06)); color: var(--wt-accent, #4f46e5); border-radius: 8px; font-size: 11px; font-weight: 700; text-transform: uppercase;">
                        <i class="mdi mdi-tag"></i> {{ \App\Helpers\TranslationHelper::translate($kategori->adi ?? 'Kategori') }}
                    </span>
                @endforeach
            @elseif(isset($yazilim->kategori_adi) && $yazilim->kategori_adi)
                <span class="paket-kategori-badge" style="display: inline-block; padding: 5px 12px; background: var(--wt-accent-bg, rgba(79,70,229,0.06)); color: var(--wt-accent, #4f46e5); border-radius: 8px; font-size: 11px; font-weight: 700; text-transform: uppercase;">
                    <i class="mdi mdi-tag"></i> {{ \App\Helpers\TranslationHelper::translate($yazilim->kategori_adi) }}
                </span>
            @endif
            </div>

            {{-- Butonlar artık resmin üzerinde hover ile görünüyor --}}
        </div>

        @if(!$inlinePrice)
            <div class="paket-footer" style="display: flex; justify-content: space-between; align-items: center; margin-top: auto; padding-top: 15px; gap: 10px;">
                {{-- Goruntulenme ve satis sayisi --}}
                <div style="display: flex; align-items: center; gap: 10px;">
                    <span style="display: inline-flex; align-items: center; gap: 3px; font-size: 11px; color: var(--wt-text-muted, #94a3b8);"><i class="mdi mdi-eye-outline" style="font-size: 13px;"></i> {{ $hitCount }}</span>
                    <span style="display: inline-flex; align-items: center; gap: 3px; font-size: 11px; color: var(--wt-text-muted, #94a3b8);"><i class="mdi mdi-cart-outline" style="font-size: 13px;"></i> {{ $satisCount }}</span>
                </div>
                <span class="paket-fiyat" style="background: var(--wt-accent, #4f46e5); color: #fff; font-weight: 700; font-size: 14px; padding: 6px 14px; border-radius: 8px; display: inline-block; margin-left: auto; direction: {{ $locale === 'ar' ? 'rtl' : 'ltr' }};">
                    {{ $fiyatGosterim }}
                </span>
            </div>
        @endif
    </div>
</div>