@extends('admin._layout')

@section('title', 'DN Bank Kredi Talepleri')

@section('content')

<div class="breadcrumb">
    <a href="{{ route('admin.dashboard') }}">Anasayfa</a>
    <span class="sep">/</span>
    <span class="current">DN Bank Kredi Talepleri</span>
</div>

<div class="page-header">
    <div>
        <h1 class="page-title"><i data-lucide="landmark"></i> DN Bank Kredi Talepleri</h1>
        <div class="page-subtitle">Müşterilerin talep ettiği krediler — onaylanınca coin hesaplarına yüklenir</div>
    </div>
</div>

@if(session('success'))
    <div class="alert alert-success"><i data-lucide="check-circle"></i><div>{{ session('success') }}</div></div>
@endif
@if(session('info'))
    <div class="alert alert-info"><i data-lucide="info"></i><div>{{ session('info') }}</div></div>
@endif
@if(session('error'))
    <div class="alert alert-danger"><i data-lucide="alert-circle"></i><div>{{ session('error') }}</div></div>
@endif

{{-- Durum sekmeleri --}}
<div class="section" style="margin-bottom:16px;display:flex;gap:8px;flex-wrap:wrap">
    @php
        $sekmeler = [
            'bekliyor'    => ['Bekleyen', $bekleyenSayisi],
            'onaylandi'   => ['Onaylanan', $onaylananSayisi],
            'reddedildi'  => ['Reddedilen', $reddedilenSayisi],
            'tumu'        => ['Tümü', null],
        ];
    @endphp
    @foreach($sekmeler as $key => [$etiket, $sayi])
        <a href="{{ route('admin.dnbank-krediler.index', ['durum' => $key]) }}"
           class="btn btn-sm {{ $durum === $key ? 'btn-primary' : 'btn-secondary' }}">
            {{ $etiket }}@if($sayi !== null) ({{ $sayi }}) @endif
        </a>
    @endforeach
</div>

<div class="table-wrap">
    <div class="table-scroll">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Kredi No</th>
                    <th>Müşteri</th>
                    <th class="text-right">Ana Para</th>
                    <th class="text-center">Vade</th>
                    <th>Durum</th>
                    <th>Talep Tarihi</th>
                    <th class="text-right">İşlem</th>
                </tr>
            </thead>
            <tbody>
                @forelse($krediler as $k)
                <tr>
                    <td style="font-family:monospace;font-size:12px">{{ $k->kredi_no ?: '#'.$k->id }}</td>
                    <td>
                        {{ $k->musteri_adi ?: '—' }}
                        @if($k->musteri_email)<div style="font-size:11px;color:var(--text-muted)">{{ $k->musteri_email }}</div>@endif
                    </td>
                    <td class="text-right" style="font-weight:700">₺{{ number_format((float) $k->ana_para, 2, ',', '.') }}</td>
                    <td class="text-center">{{ $k->vade_ay }} ay</td>
                    <td>
                        @php
                            $renk = match($k->onay_durumu) {
                                'onaylandi' => 'badge-success',
                                'reddedildi' => 'badge-danger',
                                default => 'badge-warning',
                            };
                            $metin = match($k->onay_durumu) {
                                'onaylandi' => 'Onaylandı',
                                'reddedildi' => 'Reddedildi',
                                default => 'Bekliyor',
                            };
                        @endphp
                        <span class="badge {{ $renk }}">{{ $metin }}</span>
                    </td>
                    <td style="font-size:12px">{{ \Illuminate\Support\Carbon::parse($k->created_at)->format('d.m.Y H:i') }}</td>
                    <td class="text-right">
                        <div style="display:flex;gap:6px;justify-content:flex-end">
                            <a href="{{ route('admin.dnbank-krediler.goster', $k->id) }}" class="table-action" title="Detay">
                                <i data-lucide="eye"></i>
                            </a>
                            @if($k->onay_durumu === 'bekliyor')
                                <form method="POST" action="{{ route('admin.dnbank-krediler.onayla', $k->id) }}"
                                      onsubmit="return confirm('₺{{ number_format((float) $k->ana_para, 2, ',', '.') }} tutarındaki kredi onaylansın mı? Coin hemen yüklenecek.')">
                                    @csrf
                                    <button type="submit" class="table-action" style="color:var(--success)" title="Onayla">
                                        <i data-lucide="check"></i>
                                    </button>
                                </form>
                                <form method="POST" action="{{ route('admin.dnbank-krediler.reddet', $k->id) }}"
                                      onsubmit="return confirm('Bu kredi talebi reddedilsin mi?')">
                                    @csrf
                                    <button type="submit" class="table-action" style="color:var(--danger)" title="Reddet">
                                        <i data-lucide="x"></i>
                                    </button>
                                </form>
                            @endif
                        </div>
                    </td>
                </tr>
                @empty
                <tr><td colspan="7">
                    <div class="empty-state" style="padding:32px 0">
                        <i data-lucide="landmark" class="empty-state-icon"></i>
                        <h4>Kayıt yok</h4>
                        <p>Bu durumda kredi talebi bulunmuyor.</p>
                    </div>
                </td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<div style="margin-top:16px">
    {{ $krediler->links() }}
</div>

@endsection
