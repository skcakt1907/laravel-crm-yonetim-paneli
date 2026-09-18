@extends('layouts.panel')

@section('page_title', 'DN Bank')

@section('panel_content')
@php
    $cur = session('currency', 'TRY');
    $fmt = fn($v) => \App\Helpers\CurrencyHelper::format((float) $v, $cur);
    $bugun = now()->toDateString();
@endphp
<div class="col-md-12 border-left-3 main-content">
    <div class="title-area mb-4">
        <h5 class="title">
            <i class="fa fa-university"></i>
            DN Bank
        </h5>
        <div class="pull-right">
            <strong><a href="{{ route('hesabim') }}">{{ __('messages.my_account') }}</a></strong> /
            DN Bank
        </div>
    </div>

    @includeIf('tema.partials.alert-messages')

    {{-- Özet kartları --}}
    <div class="row mb-4">
        <div class="col-md-4 mb-3">
            <div style="background:linear-gradient(135deg,#1a2332,#2a3850); color:#fff; padding:20px; border-radius:14px;">
                <div style="font-size:13px; opacity:.8;"><i class="mdi mdi-bank"></i> {{ __('messages.available_coin') }}</div>
                <div style="font-size:28px; font-weight:800; margin-top:6px;">{{ $fmt($coin) }}</div>
                <div style="font-size:12px; opacity:.7; margin-top:4px;">{{ __('messages.coin_usage_note') }}</div>
            </div>
        </div>
        <div class="col-md-4 mb-3">
            <div style="background:#fff; border:1px solid #ececec; padding:20px; border-radius:14px;">
                <div style="font-size:13px; color:#888;"><i class="mdi mdi-cash-multiple"></i> {{ __('messages.total_debt_remaining') }}</div>
                <div style="font-size:28px; font-weight:800; margin-top:6px; color:{{ $toplamBorc > 0 ? '#ef4444' : '#10b981' }};">{{ $fmt($toplamBorc) }}</div>
                <div style="font-size:12px; color:#aaa; margin-top:4px;">Ödenen: {{ $fmt($toplamOdenen) }} / {{ $fmt($toplamKredi) }}</div>
            </div>
        </div>
        <div class="col-md-4 mb-3">
            <div style="background:#fff; border:1px solid #ececec; padding:20px; border-radius:14px;">
                <div style="font-size:13px; color:#888;"><i class="mdi mdi-file-document-multiple"></i> {{ __('messages.loan_count') }}</div>
                <div style="font-size:28px; font-weight:800; margin-top:6px;">{{ $krediler->count() }}</div>
                <div style="font-size:12px; color:#aaa; margin-top:4px;">DN Bank kredisi</div>
            </div>
        </div>
    </div>

    {{-- Kredi talep formu --}}
    <div style="background:#fff; border:1px solid #ececec; border-radius:14px; padding:20px; margin-bottom:24px;">
        <h6 style="font-weight:700; margin:0 0 6px;"><i class="mdi mdi-hand-coin-outline"></i> {{ __('messages.request_loan') }}</h6>
        <p style="font-size:13px; color:#888; margin:0 0 16px;">{{ __('messages.loan_request_note') }}</p>
        <form method="POST" action="{{ route('dnbank.kredi.talep') }}">
            @csrf
            <div class="row">
                <div class="col-md-4 mb-3">
                    <label style="font-size:13px; font-weight:600;">{{ __('messages.amount_try_required') }}</label>
                    <input type="number" name="ana_para" step="0.01" min="1" required class="form-control" placeholder="10000.00">
                </div>
                <div class="col-md-4 mb-3">
                    <label style="font-size:13px; font-weight:600;">Vade (ay) *</label>
                    <input type="number" name="vade_ay" min="1" max="60" required class="form-control" placeholder="12" value="12">
                </div>
                <div class="col-md-4 mb-3">
                    <label style="font-size:13px; font-weight:600;">{{ __('messages.description') }}</label>
                    <input type="text" name="aciklama" class="form-control" placeholder="{{ __('messages.what_for_placeholder') }}">
                </div>
            </div>
            <div style="text-align:right;">
                <button type="submit" style="background:#1a2332; color:#cddc39; border:none; padding:10px 22px; border-radius:8px; font-weight:600; cursor:pointer;">
                    <i class="mdi mdi-send"></i> {{ __('messages.send_request') }}
                </button>
            </div>
        </form>
    </div>

    {{-- Geri ödeme planları (krediler + taksitler) --}}
    <div style="background:#fff; border:1px solid #ececec; border-radius:14px; padding:20px; margin-bottom:24px;">
        <h6 style="font-weight:700; margin:0 0 16px;"><i class="mdi mdi-calendar-clock"></i> {{ __('messages.repayment_plans') }}</h6>

        @forelse($krediler as $k)
            @php
                $kt = $taksitler[$k->id] ?? collect();
                $durumBadge = [
                    'aktif'     => ['#eef2ff', '#3730a3', '⏳ Aktif'],
                    'kapandi'   => ['#ecfdf5', '#047857', '✓ Kapandı'],
                    'gecikmede' => ['#fef2f2', '#b91c1c', '⚠️ Gecikme'],
                    'iptal'     => ['#f3f4f6', '#6b7280', '⏸ İptal'],
                ][$k->durum] ?? ['#eef2ff', '#3730a3', $k->durum];
            @endphp
            <div style="border:1px solid #eee; border-radius:12px; margin-bottom:16px; overflow:hidden;">
                <div style="padding:16px; background:#fafafa; display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:10px;">
                    <div>
                        <div style="font-size:11px; color:#999;">{{ $k->kredi_no }} · {{ \Carbon\Carbon::parse($k->created_at)->format('d.m.Y') }}</div>
                        <div style="font-size:20px; font-weight:800; margin-top:2px;">
                            {{ $fmt($k->ana_para) }}
                            <span style="margin-left:8px; font-size:12px; padding:3px 10px; border-radius:20px; background:{{ $durumBadge[0] }}; color:{{ $durumBadge[1] }};">{{ $durumBadge[2] }}</span>
                        </div>
                        <div style="font-size:12px; color:#777; margin-top:2px;">
                            {{ $k->vade_ay }} ay {{ ((float)($k->faiz_orani ?? 0)) == 0 ? '(faizsiz)' : '· %'.number_format((float)$k->faiz_orani,2).' faiz' }}
                            · Aylık: <strong>{{ $fmt($k->aylik_taksit) }}</strong>
                        </div>
                        @if(!empty($k->aciklama))<div style="font-size:12px; color:#999; margin-top:2px;">{{ $k->aciklama }}</div>@endif
                    </div>
                    <div style="text-align:right;">
                        <div style="font-size:11px; color:#999;">{{ __('messages.remaining_debt') }}</div>
                        <div style="font-size:22px; font-weight:800; color:{{ $k->kalan_borc > 0 ? '#ef4444' : '#10b981' }};">{{ $fmt($k->kalan_borc) }}</div>
                    </div>
                </div>

                @if(isset($k->onay_durumu) && $k->onay_durumu === 'bekliyor')
                <div style="padding:16px; background:#fffbeb; border-top:1px solid #fde68a;">
                    <div style="font-size:13px; color:#92400e; margin-bottom:10px;">
                        <i class="mdi mdi-alert-circle-outline"></i>
                        {{ __('messages.loan_awaiting_your_approval') }} <strong>Kabul ederseniz {{ $fmt($k->ana_para) }} coin hesabınıza yüklenir</strong> ve {{ $k->vade_ay }} aylık taksitli geri ödeme başlar.
                    </div>
                    <div style="display:flex; gap:8px; flex-wrap:wrap;">
                        <form method="POST" action="{{ route('dnbank.kredi.onayla', $k->id) }}" style="margin:0;"
                              onsubmit="return confirm('Krediyi kabul ediyor musunuz? {{ $fmt($k->ana_para) }} coin yüklenecek.')">
                            @csrf
                            <button type="submit" style="background:#10b981; color:#fff; border:none; padding:9px 18px; border-radius:8px; font-weight:600; cursor:pointer;">
                                <i class="mdi mdi-check"></i> Kabul Ediyorum
                            </button>
                        </form>
                        <form method="POST" action="{{ route('dnbank.kredi.reddet', $k->id) }}" style="margin:0;"
                              onsubmit="return confirm('Krediyi reddetmek istediğinize emin misiniz?')">
                            @csrf
                            <button type="submit" style="background:#fff; color:#ef4444; border:1px solid #ef4444; padding:9px 18px; border-radius:8px; font-weight:600; cursor:pointer;">
                                <i class="mdi mdi-close"></i> Reddet
                            </button>
                        </form>
                    </div>
                </div>
                @elseif(isset($k->onay_durumu) && $k->onay_durumu === 'reddedildi')
                <div style="padding:12px 16px; background:#fef2f2; border-top:1px solid #fecaca; font-size:13px; color:#b91c1c;">
                    <i class="mdi mdi-close-circle-outline"></i> Bu kredi reddedildi.
                </div>
                @endif

                @if($kt->count())
                <div style="overflow-x:auto;">
                    <table class="table" style="margin:0; font-size:13px;">
                        <thead>
                            <tr style="background:#fff;">
                                <th style="border:none;">#</th>
                                <th style="border:none;">Vade</th>
                                <th style="border:none;">{{ __('messages.table_amount') }}</th>
                                <th style="border:none;">{{ __('messages.paid_amount') }}</th>
                                <th style="border:none;">{{ __('messages.table_status') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($kt as $t)
                                @php $gecikme = $t->durum != 'odendi' && $t->vade_tarihi < $bugun; @endphp
                                <tr>
                                    <td>{{ $t->sira }}/{{ $k->vade_ay }}</td>
                                    <td>{{ \Carbon\Carbon::parse($t->vade_tarihi)->format('d.m.Y') }}</td>
                                    <td><strong>{{ $fmt($t->taksit_tutari) }}</strong></td>
                                    <td style="color:#10b981;">{{ $fmt($t->odenen_tutar) }}</td>
                                    <td>
                                        @if($t->durum == 'odendi')
                                            <span style="color:#047857;">{{ __('messages.paid_check') }}</span>
                                        @elseif($t->durum == 'kismi')
                                            <span style="color:#b45309;">{{ __('messages.partial') }}</span>
                                        @elseif($gecikme)
                                            <span style="color:#b91c1c;">⚠️ Gecikti</span>
                                        @else
                                            <span style="color:#6b7280;">⏳ Bekliyor</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @endif
            </div>
        @empty
            <div style="text-align:center; padding:30px; color:#999;">
                <i class="mdi mdi-bank-outline" style="font-size:40px; opacity:.4;"></i>
                <p style="margin-top:10px;">{{ __('messages.no_dnbank_loans') }}</p>
            </div>
        @endforelse
    </div>

    {{-- Coin hareketleri --}}
    <div style="background:#fff; border:1px solid #ececec; border-radius:14px; padding:20px;">
        <h6 style="font-weight:700; margin:0 0 16px;"><i class="mdi mdi-swap-vertical"></i> Coin Hareketleri</h6>
        @if($hareketler->count())
        <div style="overflow-x:auto;">
            <table class="table" style="font-size:13px;">
                <thead>
                    <tr>
                        <th>{{ __('messages.table_date') }}</th>
                        <th>{{ __('messages.action') }}</th>
                        <th>{{ __('messages.description') }}</th>
                        <th class="text-right">{{ __('messages.table_amount') }}</th>
                        <th class="text-right">Kalan</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($hareketler as $h)
                        @php
                            $tipAd = [
                                'kredi_yukleme' => '🏦 Kredi yüklendi',
                                'harcama'       => '🛒 Harcama',
                                'iade'          => '↩️ İade',
                                'duzeltme'      => '⚙️ Düzeltme',
                            ][$h->tip] ?? $h->tip;
                            $art = (float) $h->tutar >= 0;
                        @endphp
                        <tr>
                            <td>{{ $h->created_at ? \Carbon\Carbon::parse($h->created_at)->format('d.m.Y H:i') : '-' }}</td>
                            <td>{{ $tipAd }}</td>
                            <td style="color:#777;">{{ $h->aciklama }}</td>
                            <td class="text-right" style="font-weight:700; color:{{ $art ? '#10b981' : '#ef4444' }};">
                                {{ $art ? '+' : '' }}{{ $fmt($h->tutar) }}
                            </td>
                            <td class="text-right">{{ $fmt($h->bakiye_sonra) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @else
        <div style="text-align:center; padding:24px; color:#999;">{{ __('messages.no_transactions') }}</div>
        @endif
    </div>
</div>

@push('scripts')
<script>
// -- 419 (CSRF token suresi doldu) KORUMASI --
// Musteri sayfada uzun sure bekleyince CSRF token eskir ve onayla/reddet 419 verir.
// Form gonderilmeden hemen once token'i sayfadaki taze meta etiketinden alip guncelliyoruz.
(function () {
    var meta = document.querySelector('meta[name="csrf-token"]');
    if (!meta) return;
    document.querySelectorAll('form[method="POST"], form[method="post"]').forEach(function (form) {
        form.addEventListener('submit', function () {
            var freshToken = meta.getAttribute('content');
            var input = form.querySelector('input[name="_token"]');
            if (input) {
                input.value = freshToken;
            } else {
                input = document.createElement('input');
                input.type = 'hidden';
                input.name = '_token';
                input.value = freshToken;
                form.appendChild(input);
            }
        });
    });
})();
</script>
@endpush
@endsection