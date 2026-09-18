@extends('layouts.panel')

@section('page_title', 'Kredilerim')

@section('panel_content')
<div style="background:#fff; border-radius:16px; padding:26px; box-shadow:0 6px 24px rgba(26,35,50,.06); border:1px solid #ececec;">

<div style="margin-bottom:25px">
<h5 style="color:#1a2332;font-size:24px;font-weight:700;margin:0">
<i class="mdi mdi-cash-multiple" style="color:#b8b62e;margin-right:10px"></i> Kredilerim
</h5>
<div style="color:#64748b;font-size:13px;margin-top:6px">
<a href="{{ route('hesabim') }}" style="color:#b8b62e;text-decoration:none">{{ __('messages.customer_panel') }}</a> / Kredilerim
</div>
</div>

@if(session('success'))
<div style="background:rgba(34,197,94,0.12);border:1px solid rgba(34,197,94,0.4);color:#86efac;border-radius:12px;padding:12px 18px;margin-bottom:18px">
<i class="mdi mdi-check-circle"></i> {{ session('success') }}
</div>
@endif

@php
    $toplamAna = $krediler->sum('ana_para');
    $toplamKalan = $krediler->sum('kalan_borc');
    $toplamOdenen = $krediler->sum('odenen_tutar');
@endphp

{{-- Üst istatistikler --}}
<div style="display:grid;grid-template-columns:repeat(3,1fr);gap:15px;margin-bottom:25px">
<div style="background:rgba(184,182,46,.10);border:1px solid rgba(184,182,46,.28);border-radius:14px;padding:18px">
<div style="color:#64748b;font-size:12px;text-transform:uppercase">{{ __('messages.total_loans_opened') }}</div>
<div style="color:#b8b62e;font-size:24px;font-weight:800;margin-top:6px">₺{{ number_format((float) $toplamAna, 2, ',', '.') }}</div>
<div style="color:#94a3b8;font-size:11px;margin-top:4px">{{ $krediler->count() }} kredi</div>
</div>
<div style="background:rgba(34,197,94,0.12);border:1px solid rgba(34,197,94,0.3);border-radius:14px;padding:18px">
<div style="color:#64748b;font-size:12px;text-transform:uppercase">{{ __('messages.paid_amount') }}</div>
<div style="color:#22c55e;font-size:24px;font-weight:800;margin-top:6px">₺{{ number_format((float) $toplamOdenen, 2, ',', '.') }}</div>
</div>
<div style="background:rgba(239,68,68,0.12);border:1px solid rgba(239,68,68,0.3);border-radius:14px;padding:18px">
<div style="color:#64748b;font-size:12px;text-transform:uppercase">{{ __('messages.remaining_debt') }}</div>
<div style="color:#ef4444;font-size:24px;font-weight:800;margin-top:6px">₺{{ number_format((float) $toplamKalan, 2, ',', '.') }}</div>
</div>
</div>

@forelse($krediler as $k)
@php
    $bugun = now()->toDateString();
    $renkler = ['aktif'=>'#facc15','kapandi'=>'#22c55e','gecikmede'=>'#ef4444','iptal'=>'#6b7280'];
    $durumRenk = $renkler[$k->durum] ?? '#facc15';
    $progress = $k->toplam_geri_odeme > 0 ? ($k->odenen_tutar / $k->toplam_geri_odeme) * 100 : 0;
@endphp
<div style="background:#ffffff;border:1px solid #ececec;border-left:4px solid {{ $durumRenk }};border-radius:16px;padding:20px;margin-bottom:18px">

<div style="display:flex;justify-content:space-between;align-items:start;flex-wrap:wrap;gap:15px;margin-bottom:15px">
<div>
<div style="color:#94a3b8;font-size:11px">{{ $k->kredi_no }}</div>
<div style="color:#1a2332;font-size:20px;font-weight:800;margin-top:4px">
₺{{ number_format((float) $k->ana_para, 2, ',', '.') }}
@if($k->durum == 'aktif')<span style="background:rgba(184,182,46,.16);color:#b8b62e;padding:3px 10px;border-radius:8px;font-size:11px;font-weight:600;margin-left:8px">{{ __('messages.active_pending') }}</span>
@elseif($k->durum == 'kapandi')<span style="background:rgba(34,197,94,.2);color:#22c55e;padding:3px 10px;border-radius:8px;font-size:11px;font-weight:600;margin-left:8px">{{ __('messages.closed_check') }}</span>
@elseif($k->durum == 'gecikmede')<span style="background:rgba(239,68,68,.2);color:#ef4444;padding:3px 10px;border-radius:8px;font-size:11px;font-weight:600;margin-left:8px">⚠️ Gecikme</span>
@endif
</div>
<div style="color:#64748b;font-size:13px;margin-top:6px">
{{ $k->vade_ay }} ay {{ ($k->faiz_orani ?? 0) == 0 ? '(faizsiz)' : '• %' . number_format((float) $k->faiz_orani, 2) . ' faiz' }} •
Aylık taksit: <strong style="color:#b8b62e">₺{{ number_format((float) $k->aylik_taksit, 2, ',', '.') }}</strong>
</div>
@if(!empty($k->aciklama))<div style="color:#94a3b8;font-size:12px;margin-top:4px">{{ $k->aciklama }}</div>@endif
</div>
<div style="text-align:right">
<div style="color:#94a3b8;font-size:11px">{{ __('messages.remaining_debt') }}</div>
<div style="color:{{ $k->kalan_borc > 0 ? '#ef4444' : '#22c55e' }};font-size:22px;font-weight:800">₺{{ number_format((float) $k->kalan_borc, 2, ',', '.') }}</div>
<div style="color:#94a3b8;font-size:11px;margin-top:4px">Ödenen: ₺{{ number_format((float) $k->odenen_tutar, 2, ',', '.') }}</div>
</div>
</div>

{{-- Progress bar --}}
<div style="background:#eef0f2;border-radius:8px;height:8px;overflow:hidden;margin-bottom:15px">
<div style="background:linear-gradient(90deg,#22c55e,#84cc16);height:100%;width:{{ $progress }}%;transition:width .3s"></div>
</div>
<div style="color:#94a3b8;font-size:11px;text-align:right;margin-bottom:15px">%{{ number_format($progress, 1) }} ödendi</div>

{{-- Taksit listesi --}}
<details>
<summary style="cursor:pointer;color:#b8b62e;font-size:13px;font-weight:600;padding:8px 0">📋 Taksit Detayı ({{ count($k->taksitler) }})</summary>
<div style="margin-top:10px;overflow-x:auto">
<table style="width:100%;font-size:12px;border-collapse:collapse">
<thead>
<tr style="background:#f8fafc;color:#64748b">
<th style="text-align:left;padding:8px 12px">#</th>
<th style="text-align:left;padding:8px 12px">Vade</th>
<th style="text-align:left;padding:8px 12px">{{ __('messages.table_amount') }}</th>
<th style="text-align:left;padding:8px 12px">{{ __('messages.paid_amount') }}</th>
<th style="text-align:left;padding:8px 12px">{{ __('messages.table_status') }}</th>
</tr>
</thead>
<tbody>
@foreach($k->taksitler as $t)
@php $gecikme = $t->durum != 'odendi' && $t->vade_tarihi < $bugun; @endphp
<tr style="border-bottom:1px solid #f3f4f6">
<td style="padding:8px 12px;color:#64748b">{{ $t->sira }}</td>
<td style="padding:8px 12px;color:#1a2332">{{ \Carbon\Carbon::parse($t->vade_tarihi)->format('d.m.Y') }}</td>
<td style="padding:8px 12px;color:#1a2332;font-weight:600">₺{{ number_format((float) $t->taksit_tutari, 2, ',', '.') }}</td>
<td style="padding:8px 12px;color:#22c55e">₺{{ number_format((float) $t->odenen_tutar, 2, ',', '.') }}</td>
<td style="padding:8px 12px">
@if($t->durum == 'odendi')<span style="color:#22c55e">{{ __('messages.paid_done') }}</span>
@elseif($t->durum == 'kismi')<span style="color:#fbbf24">{{ __('messages.partial') }}</span>
@elseif($gecikme)<span style="color:#ef4444">⚠️ Gecikti</span>
@else<span style="color:#94a3b8">⏳ Bekliyor</span>@endif
</td>
</tr>
@endforeach
</tbody>
</table>
</div>
</details>

</div>
@empty
<div style="text-align:center;padding:60px 20px">
<i class="mdi mdi-cash-multiple" style="font-size:60px;color:rgba(184,182,46,.5)"></i>
<h5 style="color:#1a2332;font-size:18px;margin-top:18px">{{ __('messages.no_loans') }}</h5>
<p style="color:#94a3b8;font-size:14px">{{ __('messages.loans_appear_here') }}</p>
</div>
@endforelse

</div>
@endsection
