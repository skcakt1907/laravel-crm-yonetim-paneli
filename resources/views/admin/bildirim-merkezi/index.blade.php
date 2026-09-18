@extends('admin._layout')

@section('title', 'Bildirim Merkezi')

@section('content')
@php $f = fn ($x) => number_format((float) $x, 2, ',', '.'); @endphp

<div class="breadcrumb">
    <a href="{{ route('admin.dashboard') }}">Anasayfa</a>
    <span class="sep">/</span>
    <span class="current">Bildirim Merkezi</span>
</div>

<div class="page-header">
    <div>
        <h1 class="page-title">Bildirim Merkezi</h1>
        <div class="page-subtitle">Domain, sözleşme, fatura, alacak ve ödeme hatırlatmaları tek ekranda</div>
    </div>
</div>

{{-- ═══ Özet ═══ --}}
<div class="mini-stat-grid">
    <div class="mini-stat danger">
        <div class="lbl">Gecikmiş</div>
        <div class="val" style="color:var(--danger)">{{ $ozet['gecikmis'] }}</div>
        <div class="sub">Tarihi geçti</div>
    </div>
    <div class="mini-stat warning">
        <div class="lbl">Bugün</div>
        <div class="val" style="color:var(--warning)">{{ $ozet['bugun'] }}</div>
        <div class="sub">Bugün vadesi dolan</div>
    </div>
    <div class="mini-stat info">
        <div class="lbl">Bu Hafta</div>
        <div class="val" style="color:var(--info)">{{ $ozet['hafta'] }}</div>
        <div class="sub">7 gün içinde</div>
    </div>
    <div class="mini-stat">
        <div class="lbl">Toplam Tutar</div>
        <div class="val">{{ $f($ozet['tutar']) }} ₺</div>
        <div class="sub">{{ $ozet['toplam'] }} kayıt</div>
    </div>
</div>

{{-- ═══ Filtre ═══ --}}
<form method="GET" class="section" style="display:flex;gap:8px;flex-wrap:wrap;align-items:flex-end;margin-top:16px;margin-bottom:16px">
    <div class="form-group" style="flex:1;min-width:190px;margin-bottom:0">
        <label class="form-label" style="font-size:11px">Tür</label>
        <select name="tur" class="form-select" onchange="this.form.submit()">
            <option value="">Tümü</option>
            @foreach($turler as $kod => $t)
                <option value="{{ $kod }}" {{ $tur === $kod ? 'selected' : '' }}>{{ $t[0] }}</option>
            @endforeach
        </select>
    </div>
    <div class="form-group" style="flex:1;min-width:150px;margin-bottom:0">
        <label class="form-label" style="font-size:11px">Kaç gün ileri</label>
        <select name="gun" class="form-select" onchange="this.form.submit()">
            @foreach([7 => '7 gün', 15 => '15 gün', 30 => '30 gün', 60 => '60 gün', 90 => '90 gün'] as $g => $et)
                <option value="{{ $g }}" {{ (int) $gun === $g ? 'selected' : '' }}>{{ $et }}</option>
            @endforeach
        </select>
    </div>
    <div class="form-group" style="flex:1;min-width:170px;margin-bottom:0">
        <label class="form-label" style="font-size:11px">Görünüm</label>
        <select name="sadece" class="form-select" onchange="this.form.submit()">
            <option value="">Hepsi</option>
            <option value="gecikmis" {{ $sadece === 'gecikmis' ? 'selected' : '' }}>Sadece gecikmişler</option>
        </select>
    </div>
    <button type="submit" class="btn btn-primary"><i data-lucide="filter"></i> <span>Filtrele</span></button>
    @if($tur || $sadece || (int) $gun !== 30)
        <a href="{{ route('admin.bildirim-merkezi') }}" class="btn btn-ghost btn-sm">
            <i data-lucide="x"></i> <span>Temizle</span>
        </a>
    @endif
</form>

{{-- ═══ Yaklaşanlar ═══ --}}
<div class="table-wrap" style="margin-bottom:18px">
    <div class="card-header" style="padding:16px 18px;margin-bottom:0">
        <div class="card-title">Yaklaşan Hatırlatmalar</div>
        <span class="badge badge-brand">{{ $yaklasanlar->count() }}</span>
    </div>
    <div class="table-scroll">
        <table class="data-table" style="min-width:960px">
            <thead>
                <tr>
                    <th style="width:150px">Tür</th>
                    <th>Konu</th>
                    <th style="width:190px">Müşteri</th>
                    <th style="width:110px">Tarih</th>
                    <th style="width:130px">Durum</th>
                    <th style="width:130px" class="text-right">Tutar</th>
                    <th style="width:70px" class="text-right">Aç</th>
                </tr>
            </thead>
            <tbody>
                @forelse($yaklasanlar as $y)
                <tr>
                    <td>
                        <span class="badge badge-neutral">
                            <i data-lucide="{{ $y['ikon'] }}" style="width:11px;height:11px"></i> {{ $y['tur_ad'] }}
                        </span>
                    </td>
                    <td>
                        <strong>{{ \Illuminate\Support\Str::limit($y['baslik'], 46) }}</strong>
                        @if($y['email'])
                            <div style="font-size:11px;color:var(--text-muted)">{{ $y['email'] }}</div>
                        @endif
                    </td>
                    <td style="font-size:12.5px">{{ \Illuminate\Support\Str::limit($y['musteri'], 26) }}</td>
                    <td style="font-size:12px">
                        {{ $y['tarih'] ? \Carbon\Carbon::parse($y['tarih'])->format('d.m.Y') : '—' }}
                    </td>
                    <td><span class="badge {{ $y['aciliyet']['sinif'] }}">{{ $y['aciliyet']['metin'] }}</span></td>
                    <td class="text-right">
                        {{ $y['tutar'] !== null ? $f($y['tutar']) . ' ₺' : '—' }}
                    </td>
                    <td class="text-right">
                        <div style="display:flex;gap:4px;justify-content:flex-end">
                            @if($y['link'])
                                <a href="{{ $y['link'] }}" class="table-action" title="Ürüne / hizmete git">
                                    <i data-lucide="external-link"></i>
                                </a>
                            @endif
                            @if($y['crm_id'] && Route::has('admin.crm.musteriler.show'))
                                <a href="{{ route('admin.crm.musteriler.show', $y['crm_id']) }}" class="table-action" title="Müşteri kartı">
                                    <i data-lucide="user"></i>
                                </a>
                            @endif
                            @if(!$y['link'] && !$y['crm_id'])
                                <span style="color:var(--text-muted)">—</span>
                            @endif
                        </div>
                    </td>
                </tr>
                @empty
                <tr><td colspan="7">
                    <div class="empty-state" style="padding:32px 0">
                        <i data-lucide="bell-off" class="empty-state-icon"></i>
                        <h4>Yaklaşan hatırlatma yok</h4>
                        <p>Seçtiğin aralıkta vadesi dolan kayıt bulunmuyor.</p>
                    </div>
                </td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="form-grid" style="align-items:start">
    {{-- ═══ Zamanlama ═══ --}}
    <div class="table-wrap">
        <div class="card-header" style="padding:16px 18px;margin-bottom:0">
            <div class="card-title">Otomatik Hatırlatmalar</div>
        </div>
        <div class="table-scroll">
            {{-- .data-table global min-width:700px — yan yana iki dar tabloda taşırıyor, burada iptal --}}
            <table class="data-table" style="min-width:0">
                <thead>
                    <tr>
                        <th>Tür</th>
                        <th style="width:70px">Saat</th>
                        <th style="width:110px">Son gönderim</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($zamanlama as $z)
                    <tr>
                        <td>
                            <i data-lucide="{{ $z['ikon'] }}" style="width:13px;height:13px"></i>
                            <strong>{{ $z['etiket'] }}</strong>
                            <div style="font-size:11px;color:var(--text-muted);font-family:monospace">{{ $z['cron'] }}</div>
                        </td>
                        <td><span class="badge badge-info">{{ $z['saat'] }}</span></td>
                        <td style="font-size:12px">
                            {{ $z['son'] ? \Carbon\Carbon::parse($z['son'])->format('d.m.Y H:i') : '—' }}
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="form-help" style="padding:12px 18px">
            Bu işler sunucudaki <code>schedule:run</code> cron'u ile çalışır.
            Kontrol: <code>php artisan schedule:list</code>
        </div>
    </div>

    {{-- ═══ Gönderilenler ═══ --}}
    <div class="table-wrap">
        <div class="card-header" style="padding:16px 18px;margin-bottom:0">
            <div class="card-title">Son Gönderilen Bildirimler</div>
            <span class="badge badge-neutral">{{ $gonderilenler->count() }}</span>
        </div>
        <div class="table-scroll" style="max-height:420px">
            <table class="data-table" style="min-width:0">
                <thead>
                    <tr>
                        <th style="width:105px">Tür</th>
                        <th>Alıcı</th>
                        <th style="width:105px">Tarih</th>
                        <th style="width:70px">Durum</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($gonderilenler as $g)
                    <tr>
                        <td>
                            <span class="badge badge-neutral" style="font-size:9.5px">
                                <i data-lucide="{{ $g['ikon'] }}" style="width:10px;height:10px"></i> {{ $g['tur_ad'] }}
                            </span>
                        </td>
                        <td style="font-size:12px">
                            {{ $g['email'] ?: '—' }}
                            @if($g['kalan_gun'] !== null)
                                <div style="font-size:11px;color:var(--text-muted)">{{ $g['kalan_gun'] }} gün kala gönderildi</div>
                            @endif
                        </td>
                        <td style="font-size:12px">
                            {{ $g['tarih'] ? \Carbon\Carbon::parse($g['tarih'])->format('d.m.Y H:i') : '—' }}
                        </td>
                        <td>
                            @if($g['basarili'])
                                <span class="badge badge-success">gitti</span>
                            @else
                                <span class="badge badge-danger" title="{{ $g['hata'] }}">hata</span>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="4">
                        <div class="empty-state" style="padding:24px 0"><p>Henüz gönderilmiş bildirim kaydı yok.</p></div>
                    </td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="form-help" style="margin-top:14px">
    Bu ekran hatırlatmaları <strong>göndermez</strong>, sadece toplar. Gönderim işini
    zamanlanmış komutlar yapar; buradan ne zaman ne gideceğini ve neyin gittiğini görürsün.
</div>

@endsection
