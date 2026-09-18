@extends('admin._layout')

@section('title', 'Temizlik Kontrol')

@section('content')

<div class="breadcrumb">
    <a href="{{ route('admin.dashboard') }}">Anasayfa</a>
    <span class="sep">/</span>
    <span class="current">Temizlik Kontrol</span>
</div>

<div class="page-header">
    <div>
        <h1 class="page-title"><i data-lucide="spray-can"></i> Temizlik Kontrol</h1>
        <div class="page-subtitle">Yapılan temizliklerin kontrol kayıtları</div>
    </div>
    <div style="display:flex;gap:8px;flex-wrap:wrap">
        <a href="{{ route('admin.temizlik.maddeler') }}" class="btn btn-secondary">
            <i data-lucide="list-checks"></i> <span>Kontrol Listesi ({{ $maddeSayisi }})</span>
        </a>
        <form method="POST" action="{{ route('admin.temizlik.baslat') }}" style="display:inline">
            @csrf
            <button type="submit" class="btn btn-primary">
                <i data-lucide="plus"></i> <span>Yeni Kontrol Başlat</span>
            </button>
        </form>
    </div>
</div>

@if(session('success'))
    <div class="alert alert-success"><i data-lucide="check-circle"></i><div>{{ session('success') }}</div></div>
@endif
@if(session('error'))
    <div class="alert alert-danger"><i data-lucide="alert-circle"></i><div>{{ session('error') }}</div></div>
@endif

@if($maddeSayisi === 0)
    <div class="alert alert-info">
        <i data-lucide="info"></i>
        <div>
            Henüz kontrol listesi oluşturulmamış.
            <a href="{{ route('admin.temizlik.maddeler') }}"><strong>Kontrol Listesi</strong></a>
            sayfasından yapılacak maddeleri ekleyin (örn. "Tuvaletler", "Zeminler", "Camlar"),
            sonra buradan yeni kontrol başlatın.
        </div>
    </div>
@endif

<div class="table-wrap">
    <div class="table-scroll">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Kontrol</th>
                    <th>Tarih</th>
                    <th>Başlatan</th>
                    <th style="width:190px">Durum</th>
                    <th class="text-right">İşlem</th>
                </tr>
            </thead>
            <tbody>
                @forelse($kontroller as $k)
                    @php
                        $toplam  = (int) $k->madde_sayisi;
                        $yapilan = (int) $k->yapilan_sayisi;
                        $yuzde   = $toplam > 0 ? round($yapilan / $toplam * 100) : 0;
                        $bitti   = $toplam > 0 && $yapilan === $toplam;
                    @endphp
                    <tr class="clickable" onclick="window.location='{{ route('admin.temizlik.goster', $k->id) }}'">
                        <td>
                            <strong>{{ $k->baslik ?: 'Temizlik Kontrolü' }}</strong>
                            @if($k->not)
                                <div style="font-size:11px;color:var(--text-muted);margin-top:2px">
                                    {{ \Illuminate\Support\Str::limit($k->not, 60) }}
                                </div>
                            @endif
                        </td>
                        <td style="font-size:13px">
                            {{ \Illuminate\Support\Carbon::parse($k->kontrol_tarihi)->format('d.m.Y') }}
                            <div style="font-size:11px;color:var(--text-muted)">
                                oluşturma: {{ \Illuminate\Support\Carbon::parse($k->created_at)->format('d.m.Y H:i') }}
                            </div>
                        </td>
                        <td style="font-size:12px">{{ $k->olusturan_adi ?: '—' }}</td>
                        <td>
                            <div style="display:flex;align-items:center;gap:8px">
                                <div style="flex:1;height:7px;background:var(--bg-subtle);border-radius:4px;overflow:hidden;min-width:70px">
                                    <div style="height:100%;width:{{ $yuzde }}%;background:{{ $bitti ? 'var(--success)' : 'var(--brand)' }}"></div>
                                </div>
                                <span style="font-size:12px;font-weight:700;white-space:nowrap">{{ $yapilan }}/{{ $toplam }}</span>
                            </div>
                            @if($bitti && $k->tamamlanma_tarihi)
                                <div style="font-size:11px;color:var(--success);margin-top:3px">
                                    ✓ {{ \Illuminate\Support\Carbon::parse($k->tamamlanma_tarihi)->format('d.m.Y H:i') }}
                                </div>
                            @endif
                        </td>
                        <td class="text-right no-row-click" onclick="event.stopPropagation()">
                            <div style="display:flex;gap:6px;justify-content:flex-end">
                                <a href="{{ route('admin.temizlik.goster', $k->id) }}" class="table-action" title="Aç">
                                    <i data-lucide="eye"></i>
                                </a>
                                <form method="POST" action="{{ route('admin.temizlik.sil', $k->id) }}"
                                      onsubmit="return confirm('Bu kontrol kaydı silinsin mi?')">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="table-action" style="color:var(--danger)" title="Sil">
                                        <i data-lucide="trash-2"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5">
                        <div class="empty-state" style="padding:34px 0">
                            <i data-lucide="spray-can" class="empty-state-icon"></i>
                            <h4>Henüz kontrol kaydı yok</h4>
                            <p>Yukarıdaki <strong>Yeni Kontrol Başlat</strong> butonuyla ilk kaydı oluşturun.</p>
                        </div>
                    </td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<div style="margin-top:16px">{{ $kontroller->links() }}</div>

@endsection
