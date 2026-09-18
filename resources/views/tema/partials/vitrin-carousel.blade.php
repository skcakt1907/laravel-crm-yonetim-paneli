{{-- Kategori vitrini — yatay kayan carousel (Tatilim Sensin şeması)
     Beklenen: $vitrin = ['kategori' => (id, adi), 'paketler' => Collection]
     Kart: rozet · görsel · ad · kategori · puan/yorum · izlenme · başlayan fiyat --}}
@php
    $_kat = $vitrin['kategori'];
    $_paketler = $vitrin['paketler'];
    $_kid = 'vit' . $_kat->id;
    $_zemin = $zemin ?? '#ffffff';
    $_locale = app()->getLocale() ?: 'tr';
    $_currency = request('currency') ?? session('currency') ?? match ($_locale) { 'en' => 'USD', 'ar' => 'AED', default => 'TRY' };
    $_sembol = match ($_currency) { 'USD' => '$', 'EUR' => '€', 'AED' => 'د.إ', default => '₺' };
@endphp

@once
<style>
    /* Her ekranda TAM 4 kart görünsün (kaydırma korunur, kartlar büyür) */
    .vitrin-strip .vitrin-kart { flex:0 0 calc((100% - 54px) / 4) !important; width:calc((100% - 54px) / 4) !important; }
    .vitrin-strip .vitrin-kart:hover { transform:translateY(-3px); box-shadow:0 10px 26px rgba(0,0,0,.10) !important; border-color:#e5e3ad !important; }
    @media (max-width:1024px){ .vitrin-strip .vitrin-kart { flex-basis:calc((100% - 36px) / 3) !important; width:calc((100% - 36px) / 3) !important; } }
    @media (max-width:768px){ .vitrin-strip .vitrin-kart { flex-basis:calc((100% - 18px) / 2) !important; width:calc((100% - 18px) / 2) !important; } }
    @media (max-width:520px){ .vitrin-strip .vitrin-kart { flex-basis:82% !important; width:82% !important; } }
</style>
@endonce

<section class="vitrin-section" style="padding:38px 0;background:{{ $_zemin }};">
    <div class="container">
        {{-- Başlık + Tümü --}}
        <div style="display:flex;align-items:center;justify-content:space-between;gap:16px;margin-bottom:18px;flex-wrap:wrap;">
            <div>
                <h2 style="font-size:24px;font-weight:800;color:#1a1a1a;margin:0;letter-spacing:-.02em;display:flex;align-items:center;gap:10px;flex-wrap:wrap;">
                    @if(!empty($vitrin['oneCikan']))
                        <span style="font-size:12px;font-weight:800;letter-spacing:.03em;text-transform:uppercase;background:#b8b62e;color:#1a1a0e;padding:4px 10px;border-radius:999px;">★ {{ __('messages.featured') }}</span>
                    @endif
                    {{ !empty($vitrin['baslik_key']) ? __('messages.'.$vitrin['baslik_key']) : __('messages.most_preferred_in', ['kategori' => \App\Helpers\TranslationHelper::translate($_kat->adi)]) }}
                </h2>
                <p style="color:#8a8a8a;font-size:13.5px;margin:4px 0 0;">{{ __('messages.most_viewed_sub') }}</p>
            </div>
            <div style="display:flex;align-items:center;gap:8px;">
                <button type="button" class="vitrin-ok" data-ok-geri="{{ $_kid }}" onclick="vitrinKaydir('{{ $_kid }}',-1)" aria-label="{{ __('messages.back') }}"
                        style="width:38px;height:38px;border-radius:50%;border:1.5px solid #e0e0d8;background:#fff;cursor:pointer;color:#444;font-size:16px;line-height:1;">‹</button>
                <button type="button" class="vitrin-ok" data-ok-ileri="{{ $_kid }}" onclick="vitrinKaydir('{{ $_kid }}',1)" aria-label="{{ __('messages.next') }}"
                        style="width:38px;height:38px;border-radius:50%;border:1.5px solid #e0e0d8;background:#fff;cursor:pointer;color:#444;font-size:16px;line-height:1;">›</button>
                <a href="{{ localized_route('paketler.kategori', $_kat->id) }}"
                   style="margin-left:6px;padding:9px 18px;border-radius:999px;background:#b8b62e;color:#1a1a0e;font-weight:700;font-size:13.5px;text-decoration:none;white-space:nowrap;">{{ __('messages.view_all') }}</a>
            </div>
        </div>

        {{-- Yatay kayan şerit --}}
        <div id="{{ $_kid }}" class="vitrin-strip"
             style="display:flex;flex-wrap:nowrap;gap:18px;overflow-x:auto;scroll-behavior:smooth;padding:4px 2px 14px;">
            @foreach($_paketler as $i => $p)
                @php
                    // Görsel: kapak -> galeri -> noimage (mevcut kart mantığıyla aynı)
                    $_img = null;
                    if (!empty($p->resim) && $p->resim !== '0' && $p->resim !== 'null') {
                        $_img = get_package_image_path($p->resim, true);
                    }
                    if (!$_img) {
                        try {
                            if (\Illuminate\Support\Facades\Schema::hasTable('webpaketresim')) {
                                $_ilk = \Illuminate\Support\Facades\DB::table('webpaketresim')->where('rid', $p->id)->orderBy('id')->value('resim');
                                if ($_ilk) { $_img = get_package_image_path($_ilk, true); }
                            }
                        } catch (\Throwable $e) {}
                    }
                    $_img = $_img ?: asset('tema/img/noimage.png');

                    // Fiyat — paket-karti ile AYNI yardımcı. Kendi number_format'ımı
                    // kullanınca İngilizce sayfada Türkçe biçim ($1.065,52) çıkıyordu.
                    $_ham = $p->tutar ?? $p->fiyat ?? null;
                    $_tl  = (float) preg_replace('/[^0-9.]/', '', str_replace(',', '.', (string) $_ham));
                    $_fiyatMetin = \App\Helpers\DovizKuruHelper::fiyatGoster($_ham, $_currency);

                    // Paket adı — translate() SADECE sözlüğe bakıyor, paket çevirileri ise
                    // `ceviriler` tablosunda satır bazında duruyor. Bu yüzden "Pro Paket"
                    // İngilizce'de çevrilmeden kalıyordu. translateField() doğru kaynak:
                    // ceviriler tablosu -> adi_en/adi_ar sütunu -> sözlük.
                    $_ad = \App\Helpers\TranslationHelper::translateField('yazilimlar', $p->id, 'adi', $p->adi);

                    $_puan = $p->puan_ort ? (float) $p->puan_ort : null;
                    $_yorum = (int) ($p->yorum_adet ?? 0);
                    $_izlenme = (int) ($p->izlenme ?? 0);
                    $_etiket = $_puan >= 4.5 ? __('messages.rating_excellent')
                             : ($_puan >= 4 ? __('messages.rating_verygood')
                             : ($_puan >= 3 ? __('messages.rating_good') : ''));
                @endphp

                <a href="{{ localized_route('paket.detay', $p->seo) }}" class="vitrin-kart"
                   style="flex:0 0 274px;width:274px;background:#fff;border:1px solid #ececec;border-radius:16px;overflow:hidden;text-decoration:none;color:inherit;display:flex;flex-direction:column;transition:all .2s;box-shadow:0 1px 3px rgba(0,0,0,.05);">
                    {{-- Görsel + rozet --}}
                    <div style="position:relative;height:168px;background:#f5f5f0;overflow:hidden;">
                        <img src="{{ $_img }}" alt="{{ $p->adi }}" loading="lazy"
                             style="width:100%;height:100%;object-fit:cover;display:block;"
                             onerror="this.onerror=null;this.src='{{ asset('tema/img/noimage.png') }}';">
                        @if(!empty($p->anasayfa))
                            <span style="position:absolute;top:10px;left:10px;background:#b8b62e;color:#1a1a0e;font-size:11px;font-weight:800;padding:4px 10px;border-radius:999px;letter-spacing:.02em;">{{ __('messages.featured_badge') }}</span>
                        @endif
                    </div>

                    {{-- Gövde --}}
                    <div style="padding:13px 14px 15px;display:flex;flex-direction:column;flex:1;">
                        <div style="font-weight:700;font-size:14.5px;color:#1a1a1a;line-height:1.35;min-height:39px;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden;">
                            {{ $_ad }}
                        </div>
                        <div style="font-size:12px;color:#999;margin-top:5px;">
                            <i class="mdi mdi-tag-outline"></i> {{ \App\Helpers\TranslationHelper::translate($_kat->adi) }}
                        </div>

                        {{-- Puan / yorum --}}
                        <div style="display:flex;align-items:center;gap:7px;margin-top:9px;min-height:22px;">
                            @if($_puan)
                                <span style="background:#1a1a1a;color:#fff;font-size:11.5px;font-weight:800;padding:3px 7px;border-radius:6px;">{{ number_format($_puan, 1, ',', '') }}</span>
                                <span style="font-size:12px;font-weight:700;color:#1a1a1a;">{{ $_etiket }}</span>
                                <span style="font-size:11.5px;color:#999;">· {{ __('messages.n_reviews', ['adet' => $_yorum]) }}</span>
                            @else
                                <span style="font-size:11.5px;color:#bbb;">{{ __('messages.no_reviews_yet') }}</span>
                            @endif
                        </div>

                        {{-- İzlenme --}}
                        @if($_izlenme > 0)
                            @php
                                // Binlik ayracı dile göre: TR "1.509" / EN-AR "1,509"
                                $_izlenmeMetin = $_locale === 'tr'
                                    ? number_format($_izlenme, 0, ',', '.')
                                    : number_format($_izlenme, 0, '.', ',');
                            @endphp
                            <div style="font-size:11.5px;color:#8a8a8a;margin-top:4px;">
                                <i class="mdi mdi-eye-outline"></i> {{ __('messages.n_viewed', ['adet' => $_izlenmeMetin]) }}
                            </div>
                        @endif

                        {{-- Fiyat --}}
                        <div style="margin-top:auto;padding-top:11px;">
                            @if($_tl > 0)
                                <div style="font-size:11px;color:#999;">{{ __('messages.starting_from') }}</div>
                                <div style="font-size:19px;font-weight:800;color:#7d7b1a;letter-spacing:-.02em;">
                                    {{-- Arapça'da sembol sonda (paket-karti ile aynı kural) --}}
                                    @if($_locale === 'ar' && $_currency === 'AED')
                                        {{ $_fiyatMetin }} {{ $_sembol }}
                                    @else
                                        {{ $_sembol }}{{ $_fiyatMetin }}
                                    @endif
                                </div>
                            @else
                                <div style="font-size:14px;font-weight:700;color:#7d7b1a;">{{ __('messages.get_quote') }}</div>
                            @endif
                        </div>
                    </div>
                </a>
            @endforeach
        </div>
    </div>
</section>
