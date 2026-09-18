@extends('layouts.panel')

@section('page_title', 'Paket Tekliflerim')

@section('panel_content')
<div class="col-md-12 border-left-3 main-content">
    <div class="title-area mb-4">
        <h5 class="title">
            <i class="fas fa-briefcase"></i>
            {{ __('messages.my_package_offers') }}
        </h5>
        <a href="{{ Route::has('paketler') ? route('paketler') : url('/paketler') }}" class="btn btn-sm btn-outline-primary pull-right">
            <i class="fa fa-plus"></i> {{ __('messages.new_offer') }}
        </a>
    </div>

    {{-- Flash mesajları site geneli tek popup (toast) ile gösteriliyor. --}}

    @if($teklifler && $teklifler->count() > 0)
        @foreach($teklifler as $teklif)
        @php
            $durumMap = [
                'onaylandi'  => ['success', 'Onaylandı'],
                'reddedildi' => ['danger',  'Reddedildi'],
                'beklemede'  => ['warning', 'Beklemede'],
            ];
            $d = $durumMap[$teklif->durum] ?? ['secondary', 'Bilinmiyor'];
        @endphp
        <div class="panel panel-default mb-3">
            <div class="panel-heading">
                <strong><i class="fa fa-file-alt"></i> {{ $teklif->baslik ?? 'Paket Teklifi' }}</strong>
                <span class="pull-right">
                    @if(!empty($teklif->token) && \Illuminate\Support\Facades\Route::has('teklif.detay.public'))
                        <a href="{{ route('teklif.detay.public', $teklif->token) }}" target="_blank"
                           class="btn btn-xs btn-outline-primary" style="margin-right:8px;border-radius:6px;font-weight:600">
                            <i class="fa fa-external-link-alt"></i> Detay
                        </a>
                    @endif
                    <span class="badge badge-{{ $d[0] }}">{{ $d[1] }}</span>
                </span>
            </div>
            <div class="panel-body">
                <div class="row">
                    <div class="col-md-8">
                        <p class="text-muted"><i class="fa fa-calendar"></i> Oluşturulma: {{ \Carbon\Carbon::parse($teklif->created_at)->format('d.m.Y H:i') }}</p>
                        @if($teklif->aciklama)
                            <div style="background:#f8fafc;border:1px solid #ececec;border-radius:10px;padding:12px 14px;color:#475569;font-size:13.5px;line-height:1.55;margin-top:6px">{{ $teklif->aciklama }}</div>
                        @endif
                    </div>
                    <div class="col-md-4 text-right">
                        <h3 class="mb-0"><strong>{{ number_format($teklif->toplam_tl, 2, ',', '.') }} ₺</strong></h3>
                        @if($teklif->para_birimi && $teklif->para_birimi !== 'TL')
                            <small class="text-muted">≈ {{ number_format($teklif->toplam_para_birimi_tutar, 2, ',', '.') }} {{ $teklif->para_birimi }}</small>
                        @endif
                    </div>
                </div>

                @if($teklif->paketler && $teklif->paketler->count() > 0)
                <hr>
                <h6><i class="fa fa-list"></i> {{ __('messages.packages_in_offer') }}</h6>
                <table class="table table-bordered table-striped mb-0">
                    <thead>
                        <tr>
                            <th class="text-left">{{ __('messages.package_name') }}</th>
                            <th class="text-center" style="width:100px;">{{ __('messages.count_unit') }}</th>
                            <th class="text-right" style="width:140px;">Birim Fiyat</th>
                            <th class="text-right" style="width:140px;">{{ __('messages.total') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                    @foreach($teklif->paketler as $paket)
                        <tr>
                            <td>{{ $paket->adi }}</td>
                            <td class="text-center">{{ $paket->adet }}</td>
                            <td class="text-right">{{ number_format($paket->birim_fiyat_tl, 2, ',', '.') }} ₺</td>
                            <td class="text-right"><strong>{{ number_format($paket->satir_toplam_tl, 2, ',', '.') }} ₺</strong></td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
                @endif

                {{-- ========== ONAY / RED BUTONLARI (sadece Beklemede durumdaki teklifler icin) ========== --}}
                @if($teklif->durum === 'beklemede')
                    <hr>
                    <div style="display:flex;gap:10px;flex-wrap:wrap;justify-content:flex-end;margin-top:14px">
                        {{-- Onayla --}}
                        <form action="{{ route('teklif.onayla', $teklif->id) }}" method="POST" style="margin:0"
                              onsubmit="return confirm('Bu teklifi onaylamak istediginizden emin misiniz?\n\nOnayladiktan sonra ekibimiz sizinle iletisime gececektir.')">
                            @csrf
                            <button type="submit" class="btn btn-success" style="padding:8px 22px;font-weight:600;border-radius:8px">
                                <i class="fa fa-check"></i> Onayla
                            </button>
                        </form>

                        {{-- Reddet (modal ac) --}}
                        <button type="button" class="btn btn-outline-danger js-red-ac"
                                data-teklif="{{ $teklif->id }}"
                                style="padding:8px 22px;font-weight:600;border-radius:8px">
                            <i class="fa fa-times"></i> Reddet
                        </button>

                        {{-- Detay/Ödeme Sayfası (token varsa, yeni public teklif sayfası) --}}
                        @if(!empty($teklif->token) && \Illuminate\Support\Facades\Route::has('teklif.detay.public'))
                            <a href="{{ route('teklif.detay.public', $teklif->token) }}" target="_blank"
                               class="btn btn-outline-primary" style="padding:8px 22px;font-weight:600;border-radius:8px">
                                <i class="fa fa-external-link-alt"></i> {{ __('messages.detail_page') }}
                            </a>
                        @endif
                    </div>

                    {{-- Red Modal (body'e tasinacak) --}}
                    <div id="red-modal-{{ $teklif->id }}" class="js-red-modal"
                         style="display:none;position:fixed;top:0;left:0;right:0;bottom:0;width:100%;height:100%;background:rgba(0,0,0,.55);z-index:99999;align-items:center;justify-content:center;padding:20px">
                        <div style="background:#fff;border-radius:14px;max-width:480px;width:100%;padding:24px;box-shadow:0 20px 60px rgba(0,0,0,.3)">
                            <h5 style="margin:0 0 8px;font-weight:700;color:#dc2626">
                                <i class="fa fa-times-circle"></i> Teklifi Reddet
                            </h5>
                            <p style="color:#6b7280;font-size:13.5px;margin-bottom:14px">
                                <strong>{{ $teklif->baslik ?? 'Bu teklifi' }}</strong> reddetmek üzeresiniz.
                                Dilerseniz bir sebep belirtin (opsiyonel):
                            </p>
                            <form action="{{ route('teklif.reddet', $teklif->id) }}" method="POST" style="margin:0">
                                @csrf
                                <textarea name="red_sebebi" rows="3" maxlength="500"
                                          placeholder="Red sebebinizi yazabilirsiniz (opsiyonel)..."
                                          style="width:100%;border:1px solid #e5e7eb;border-radius:8px;padding:10px;font-family:inherit;font-size:14px;resize:vertical"></textarea>
                                <div style="display:flex;gap:8px;justify-content:flex-end;margin-top:14px">
                                    <button type="button" class="btn btn-outline-secondary js-red-kapat"
                                            data-teklif="{{ $teklif->id }}"
                                            style="padding:8px 18px;border-radius:8px">
                                        {{ __('messages.cancel') }}
                                    </button>
                                    <button type="submit" class="btn btn-danger"
                                            style="padding:8px 22px;border-radius:8px;font-weight:600">
                                        <i class="fa fa-times"></i> Reddet
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                @elseif($teklif->durum === 'onaylandi')
                    <hr>
                    <div style="background:#dcfce7;color:#15803d;padding:10px 14px;border-radius:8px;margin-top:12px;font-weight:500">
                        <i class="fa fa-check-circle"></i> {{ __('messages.offer_approved_note') }}
                    </div>
                @elseif($teklif->durum === 'reddedildi')
                    <hr>
                    <div style="background:#fee2e2;color:#dc2626;padding:10px 14px;border-radius:8px;margin-top:12px;font-weight:500">
                        <i class="fa fa-times-circle"></i> Bu teklifi reddettiniz.
                    </div>
                @endif
            </div>
        </div>
        @endforeach
    @else
    <div class="text-center p-5">
        <i class="fa fa-file-alt" style="font-size:64px;color:#ccc"></i>
        <h5 class="mt-3">{{ __('messages.no_offers') }}</h5>
        <p class="text-muted">{{ __('messages.offers_appear_here') }}</p>
    </div>
    @endif
</div>

{{-- Red modal kontrol scripti: modal'lari body'e tasir (parent overflow/transform/z-index sorunlarini onler) --}}
<script>
(function () {
    function init() {
        // Tum red modallarini dogrudan body'nin altina tasi
        document.querySelectorAll('.js-red-modal').forEach(function (modal) {
            if (modal.parentNode !== document.body) {
                document.body.appendChild(modal);
            }
        });

        // Reddet butonlari -> ilgili modali ac
        document.querySelectorAll('.js-red-ac').forEach(function (btn) {
            btn.addEventListener('click', function () {
                var id = btn.getAttribute('data-teklif');
                var modal = document.getElementById('red-modal-' + id);
                if (modal) { modal.style.display = 'flex'; }
            });
        });

        // Iptal butonlari -> modali kapat
        document.querySelectorAll('.js-red-kapat').forEach(function (btn) {
            btn.addEventListener('click', function () {
                var id = btn.getAttribute('data-teklif');
                var modal = document.getElementById('red-modal-' + id);
                if (modal) { modal.style.display = 'none'; }
            });
        });

        // Karartilmis arka plana tiklayinca kapat
        document.querySelectorAll('.js-red-modal').forEach(function (modal) {
            modal.addEventListener('click', function (e) {
                if (e.target === modal) { modal.style.display = 'none'; }
            });
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
</script>
@endsection