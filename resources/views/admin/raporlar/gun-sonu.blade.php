@extends('admin._layout')

@section('title', 'Gün Sonu Raporu')

@section('content')

<div class="breadcrumb">
    <a href="{{ route('admin.dashboard') }}">Anasayfa</a>
    <span class="sep">/</span>
    <span class="current">Gün Sonu Raporu</span>
</div>

<div class="page-header">
    <div>
        <h1 class="page-title"><i data-lucide="clipboard-list"></i> Gün Sonu Raporu</h1>
        <div class="page-subtitle">CRM ve personel aktivite özeti · {{ $veri['tarih']->format('d.m.Y') }}</div>
    </div>
</div>

@if(session('success'))
    <div class="alert alert-success"><i data-lucide="check-circle"></i><div>{{ session('success') }}</div></div>
@endif
@if(session('error'))
    <div class="alert alert-danger"><i data-lucide="alert-circle"></i><div>{{ session('error') }}</div></div>
@endif

{{-- Tarih seçimi + elle gönder --}}
<div class="section" style="margin-bottom:16px;display:flex;gap:10px;flex-wrap:wrap;align-items:flex-end">
    <form method="GET" style="display:flex;gap:8px;align-items:flex-end">
        <div class="form-group" style="margin-bottom:0;min-width:170px">
            <label class="form-label" style="font-size:11px">Tarih</label>
            <input type="date" name="tarih" value="{{ $veri['tarih']->toDateString() }}" class="form-input">
        </div>
        <button type="submit" class="btn btn-primary"><i data-lucide="search"></i> <span>Göster</span></button>
    </form>

    <div style="flex:1"></div>

    <form method="POST" action="{{ route('admin.raporlar.gun-sonu.gonder') }}"
          onsubmit="return confirm('{{ $veri['tarih']->format('d.m.Y') }} gün sonu bildirimi mail olarak gönderilsin mi?')">
        @csrf
        <input type="hidden" name="tarih" value="{{ $veri['tarih']->toDateString() }}">
        <button type="submit" class="btn btn-secondary">
            <i data-lucide="mail"></i>
            <span>Raporu Şimdi Gönder ({{ count($alicilar) }} kişi)</span>
        </button>
    </form>
</div>

<div class="form-help" style="margin-bottom:16px">
    Bu rapor <strong>her gün 19:00</strong>'da otomatik olarak mail ile gönderilir.
    Yukarıdaki butonla dilediğiniz an elle de gönderebilirsiniz.
</div>

{{-- Özet kutuları --}}
<div class="mini-stat-grid" style="margin-bottom:18px">
    <div class="mini-stat success">
        <div class="lbl">Yeni Müşteri</div>
        <div class="val">{{ $veri['yeni_musteriler']->count() }}</div>
    </div>
    <div class="mini-stat">
        <div class="lbl">Yeni Not</div>
        <div class="val">{{ $veri['yeni_notlar']->count() }}</div>
    </div>
    <div class="mini-stat">
        <div class="lbl">Yeni Görev</div>
        <div class="val">{{ $veri['yeni_gorevler']->count() }}</div>
    </div>
    <div class="mini-stat success">
        <div class="lbl">Tamamlanan Görev</div>
        <div class="val">{{ $veri['tamamlanan_gorevler']->count() }}</div>
    </div>
    <div class="mini-stat">
        <div class="lbl">Personel İşlemi</div>
        <div class="val">{{ $veri['personel_islemleri']->count() }}</div>
    </div>
    <div class="mini-stat danger">
        <div class="lbl">Bekleyen Ticket</div>
        <div class="val" style="color:var(--danger)">{{ $veri['ticket_ozet']->sum('toplam_bekleyen') }}</div>
    </div>
</div>

{{-- Ticket Özeti --}}
<div class="table-wrap" style="margin-bottom:18px">
    <div class="card-header" style="padding:16px 18px;margin-bottom:0;display:flex;justify-content:space-between;align-items:center">
        <div class="card-title">🎫 Ticket Özeti (Kişi Bazında)</div>
        @if(\Illuminate\Support\Facades\Route::has('admin.tickets.index'))
        <a href="{{ route('admin.tickets.index') }}" class="btn btn-sm btn-secondary">Tüm Ticketlar</a>
        @endif
    </div>
    <div class="table-scroll">
        <table class="data-table">
            <thead>
                <tr><th>Kişi</th><th class="text-center">Bugün Çözülen</th><th class="text-center">Bugün Açılan (Bekleyen)</th><th class="text-center">Toplam Bekleyen</th></tr>
            </thead>
            <tbody>
                @forelse($veri['ticket_ozet'] as $t)
                <tr>
                    <td><strong>{{ $t->alici }}</strong></td>
                    <td class="text-center" style="color:var(--success);font-weight:700">{{ $t->bugun_cozulen }}</td>
                    <td class="text-center">{{ $t->bugun_bekleyen }}</td>
                    <td class="text-center" style="color:var(--danger);font-weight:700">{{ $t->toplam_bekleyen }}</td>
                </tr>
                @empty
                <tr><td colspan="4"><div class="empty-state" style="padding:28px 0"><p>Bugün ticket hareketi yok.</p></div></td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="form-help" style="padding:10px 18px">"Toplam Bekleyen" tarihe bakmaz — o kişinin şu an cevap bekleyen tüm ticket'ları.</div>
</div>

{{-- Yeni Müşteriler --}}
<div class="table-wrap" style="margin-bottom:18px">
    <div class="card-header" style="padding:16px 18px;margin-bottom:0"><div class="card-title">👤 Yeni Müşteriler</div></div>
    <div class="table-scroll">
        <table class="data-table">
            <thead><tr><th>Müşteri</th><th>Kaynak</th><th>Sorumlu</th><th class="text-right">Saat</th></tr></thead>
            <tbody>
                @forelse($veri['yeni_musteriler'] as $m)
                <tr>
                    <td>{{ $m->adi }} <span style="color:var(--text-secondary)">{{ $m->unvan }}</span></td>
                    <td style="font-size:12px">{{ $m->kaynak ?: '—' }}</td>
                    <td style="font-size:12px">{{ $m->sorumlu ?: '—' }}</td>
                    <td class="text-right" style="font-size:12px">{{ \Illuminate\Support\Carbon::parse($m->created_at)->format('H:i') }}</td>
                </tr>
                @empty
                <tr><td colspan="4"><div class="empty-state" style="padding:28px 0"><p>Bugün yeni müşteri eklenmedi.</p></div></td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

{{-- Yeni Notlar --}}
<div class="table-wrap" style="margin-bottom:18px">
    <div class="card-header" style="padding:16px 18px;margin-bottom:0"><div class="card-title">📝 Yeni Notlar</div></div>
    <div class="table-scroll">
        <table class="data-table">
            <thead><tr><th>Müşteri</th><th>Not</th><th>Yazan</th><th class="text-right">Saat</th></tr></thead>
            <tbody>
                @forelse($veri['yeni_notlar'] as $n)
                <tr>
                    <td>{{ $n->musteri ?: '—' }}</td>
                    <td style="font-size:12px;color:var(--text-secondary)">{{ \Illuminate\Support\Str::limit($n->baslik ?: $n->icerik, 60) }}</td>
                    <td style="font-size:12px">{{ $n->yazan ?: '—' }}</td>
                    <td class="text-right" style="font-size:12px">{{ \Illuminate\Support\Carbon::parse($n->created_at)->format('H:i') }}</td>
                </tr>
                @empty
                <tr><td colspan="4"><div class="empty-state" style="padding:28px 0"><p>Bugün yeni not eklenmedi.</p></div></td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

{{-- Görevler --}}
<div class="table-wrap" style="margin-bottom:18px">
    <div class="card-header" style="padding:16px 18px;margin-bottom:0"><div class="card-title">✅ Yeni / Tamamlanan Görevler</div></div>
    <div class="table-scroll">
        <table class="data-table">
            <thead><tr><th>Konu</th><th>Müşteri</th><th>Atanan</th><th>Durum</th><th class="text-right">Saat</th></tr></thead>
            <tbody>
                @forelse($veri['yeni_gorevler'] as $g)
                <tr>
                    <td>{{ $g->konu }}</td>
                    <td style="font-size:12px">{{ $g->musteri ?: '—' }}</td>
                    <td style="font-size:12px">{{ $g->atanan ?: '—' }}</td>
                    <td><span class="badge badge-info">Yeni</span></td>
                    <td class="text-right" style="font-size:12px">{{ \Illuminate\Support\Carbon::parse($g->created_at)->format('H:i') }}</td>
                </tr>
                @empty
                @endforelse
                @forelse($veri['tamamlanan_gorevler'] as $g)
                <tr>
                    <td>{{ $g->konu }}</td>
                    <td style="font-size:12px">{{ $g->musteri ?: '—' }}</td>
                    <td style="font-size:12px">{{ $g->atanan ?: '—' }}</td>
                    <td><span class="badge badge-success">Tamamlandı</span></td>
                    <td class="text-right" style="font-size:12px">{{ \Illuminate\Support\Carbon::parse($g->tamamlandi_at)->format('H:i') }}</td>
                </tr>
                @empty
                @endforelse
                @if($veri['yeni_gorevler']->isEmpty() && $veri['tamamlanan_gorevler']->isEmpty())
                <tr><td colspan="5"><div class="empty-state" style="padding:28px 0"><p>Bugün görev hareketi yok.</p></div></td></tr>
                @endif
            </tbody>
        </table>
    </div>
</div>

{{-- Personel Özeti --}}
<div class="table-wrap" style="margin-bottom:18px">
    <div class="card-header" style="padding:16px 18px;margin-bottom:0"><div class="card-title">👥 Personel Özeti</div></div>
    <div class="table-scroll">
        <table class="data-table">
            <thead><tr><th>Kişi</th><th class="text-right">İşlem Adedi</th></tr></thead>
            <tbody>
                @forelse($veri['personel_ozet'] as $kisi => $adet)
                <tr><td><strong>{{ $kisi }}</strong></td><td class="text-right">{{ $adet }} işlem</td></tr>
                @empty
                <tr><td colspan="2"><div class="empty-state" style="padding:28px 0"><p>Bugün kayıtlı işlem yok.</p></div></td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

{{-- Tüm Personel İşlemleri --}}
<div class="table-wrap">
    <div class="card-header" style="padding:16px 18px;margin-bottom:0"><div class="card-title">📜 Tüm Personel İşlemleri</div></div>
    <div class="table-scroll">
        <table class="data-table">
            <thead><tr><th class="text-right" style="width:70px">Saat</th><th>Kişi</th><th>Açıklama</th></tr></thead>
            <tbody>
                @forelse($veri['personel_islemleri'] as $i)
                <tr>
                    <td class="text-right" style="font-size:12px">{{ \Illuminate\Support\Carbon::parse($i->tarih)->format('H:i') }}</td>
                    <td style="font-size:12px"><strong>{{ $i->kullanici_adi ?: 'Sistem' }}</strong></td>
                    <td style="font-size:12px;color:var(--text-secondary)">{{ $i->aciklama }}</td>
                </tr>
                @empty
                <tr><td colspan="3"><div class="empty-state" style="padding:28px 0"><p>Bugün kayıtlı işlem yok.</p></div></td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

@endsection
