@extends('admin._layout')

@section('title', 'Ayın Elemanı')

@push('head')
<style>
    .ae-ava { border-radius:50%; object-fit:cover; display:inline-flex; align-items:center; justify-content:center; color:#fff; font-weight:700; flex:0 0 auto; }
    .ae-top { display:flex; align-items:center; gap:20px; padding:26px; border-radius:18px;
              background:linear-gradient(135deg, rgba(245,158,11,.18), rgba(245,158,11,.04));
              border:1px solid rgba(245,158,11,.35); margin-bottom:22px; flex-wrap:wrap; }
    .ae-crown { font-size:34px; }
    .ae-metrics { display:flex; gap:22px; margin-left:auto; text-align:center; flex-wrap:wrap; }
    .ae-metrics .v { font-size:24px; font-weight:800; color:var(--text); }
    .ae-metrics .l { font-size:11.5px; color:var(--text-muted); text-transform:uppercase; letter-spacing:.04em; }
    .ae-metrics .big .v { font-size:32px; color:#f59e0b; }
    .ae-puan { font-weight:800; color:#f59e0b; }
    .ae-sub { font-size:12px; color:var(--text-muted); }
    .ae-info { font-size:12.5px; color:var(--text-muted); background:var(--bg-subtle,#f8f8f3); border:1px solid var(--border); border-radius:10px; padding:10px 14px; margin-bottom:18px; }
    .ae-dilek-form { display:flex; gap:10px; flex-wrap:wrap; align-items:flex-end; }
    .ae-dilek-form .fg { display:flex; flex-direction:column; gap:4px; }
    .ae-dilek-form label { font-size:12px; font-weight:600; color:var(--text-muted); }
    .ae-dilek-form input, .ae-dilek-form select { padding:9px 11px; border:1px solid var(--border,#d1d5db); border-radius:8px; background:var(--bg,#fff); color:var(--text,#111); font-size:14px; }
    .ae-ayar-grup { margin-bottom:16px; }
    .ae-ayar-grup-baslik { font-size:12px; font-weight:700; text-transform:uppercase; letter-spacing:.04em; color:var(--text-muted); margin-bottom:8px; }
    .ae-ayar-grid { display:grid; grid-template-columns:repeat(auto-fill, minmax(220px, 1fr)); gap:12px; }
    .ae-ayar-alan { display:flex; flex-direction:column; gap:4px; }
    .ae-ayar-alan label { font-size:12.5px; font-weight:600; color:var(--text); }
    .ae-ayar-alan input { padding:9px 11px; border:1px solid var(--border,#d1d5db); border-radius:8px; background:var(--bg,#fff); color:var(--text,#111); font-size:14px; }
</style>
@endpush

@section('content')

<div class="breadcrumb">
    <a href="{{ route('admin.dashboard') }}">Anasayfa</a>
    <span class="sep">/</span>
    <span class="current">Ayın Elemanı</span>
</div>

<div class="page-header">
    <div>
        <h1 class="page-title">🏆 Ayın Elemanı</h1>
        <div class="page-subtitle">{{ $d->translatedFormat('F Y') }} — performans puanı (erken teslim + iş hacmi + iyi dilek)</div>
    </div>
    <div class="page-actions">
        <form method="GET" action="{{ route('admin.randevu.ayin-elemani') }}">
            <input type="month" name="ay" value="{{ $ay }}" class="form-input" style="max-width:180px" onchange="this.form.submit()">
        </form>
    </div>
</div>

@if(session('success'))<div class="alert alert-success" style="margin-bottom:14px">{{ session('success') }}</div>@endif
@if(session('error'))<div class="alert alert-danger" style="margin-bottom:14px">{{ session('error') }}</div>@endif

<div class="ae-info">
    <b>Puanlama:</b> Randevu ×{{ $agirliklar['randevu'] }} · Görev her aşamada puan kazandırır
    (Beklemede {{ $agirliklar['gorev_beklemede'] }} → Devam {{ $agirliklar['gorev_devam'] }} →
    Müşteri Bekleniyor {{ $agirliklar['gorev_musteri_bekleniyor'] }} → Tamamlandı {{ $agirliklar['gorev_tamamlandi'] }})
    + tamamlananlarda süre/zorluk (gün ×{{ $agirliklar['gorev_efor_gun'] }}, en çok {{ $agirliklar['gorev_efor_max'] }})
    · Kanban ×{{ $agirliklar['kanban'] }} · Erken/zamanında teslim {{ $agirliklar['erken_taban'] }} + erken gün ×{{ $agirliklar['erken_gun'] }}
    · + İyi Dilek Puanı.@if($patronMu) <a href="#puan-ayar" style="font-weight:600">Ayarları düzenle ↓</a>@endif
</div>

@php
    $avatar = function($c, $size) {
        $initials = collect(preg_split('/\s+/', trim($c->ad)))->map(fn($p)=>mb_substr($p,0,1,'UTF-8'))->take(2)->implode('');
        if (!empty($c->foto)) {
            return '<img class="ae-ava" style="width:'.$size.'px;height:'.$size.'px" src="'.asset($c->foto).'">';
        }
        return '<span class="ae-ava" style="width:'.$size.'px;height:'.$size.'px;font-size:'.round($size/2.6).'px;background:'.($c->renk ?? '#f59e0b').'">'.mb_strtoupper($initials,'UTF-8').'</span>';
    };
    $bir = $siralama->first();
@endphp

@if(!$bir || $bir->toplam == 0)
    <div class="section">
        <div class="empty-state">
            <i data-lucide="trophy" class="empty-state-icon"></i>
            <h4>Bu ay henüz puan oluşmadı</h4>
            <p>{{ $d->translatedFormat('F Y') }} için randevu/görev/puan kaydı bulunmuyor.</p>
        </div>
    </div>
@else
    {{-- Ayın Elemanı --}}
    <div class="ae-top">
        <span class="ae-crown">👑</span>
        {!! $avatar($bir, 72) !!}
        <div>
            <div style="font-size:20px;font-weight:800;color:var(--text)">{{ $bir->ad }}</div>
            <span class="badge badge-brand" style="margin-top:4px">Ayın Elemanı</span>
        </div>
        <div class="ae-metrics">
            <div class="big"><div class="v">{{ number_format($bir->toplam) }}</div><div class="l">Toplam Puan</div></div>
            <div><div class="v">{{ $bir->randevu }}</div><div class="l">Randevu</div></div>
            <div><div class="v">{{ $bir->gorev }}</div><div class="l">Görev</div></div>
            <div><div class="v">{{ $bir->erken }}</div><div class="l">Erken Teslim</div></div>
            <div><div class="v">{{ $bir->iyi_dilek }}</div><div class="l">İyi Dilek</div></div>
        </div>
    </div>

    {{-- Tam sıralama + kırılım --}}
    <div class="table-wrap">
        <div class="table-scroll">
            <table class="data-table">
                <thead>
                    <tr>
                        <th style="width:60px">Sıra</th>
                        <th>Çalışan</th>
                        <th title="Randevu adedi">Randevu</th>
                        <th title="Bu ay puanlanan görev (aşamasına göre puan alır)">Görev</th>
                        <th title="Zamanında/erken teslim (gün)">Erken</th>
                        <th title="Tamamlanan kanban kartı">Kanban</th>
                        <th title="İş Puanı (randevu+görev+kanban)">İş P.</th>
                        <th title="Erken teslim bonusu">Erken B.</th>
                        <th title="İyi Dilek Puanı">İyi Dilek</th>
                        <th>Toplam</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($siralama as $i => $c)
                        <tr>
                            <td style="font-weight:700">
                                @if($i===0) 🥇 @elseif($i===1) 🥈 @elseif($i===2) 🥉 @else {{ $i+1 }} @endif
                            </td>
                            <td>
                                <div style="display:flex;align-items:center;gap:10px">
                                    {!! $avatar($c, 34) !!}
                                    <span style="font-weight:600;color:var(--text)">{{ $c->ad }}</span>
                                </div>
                            </td>
                            <td>{{ $c->randevu }}</td>
                            <td>
                                {{ $c->gorev }}
                                @if($c->gorev)
                                    <div class="ae-sub" title="Beklemede / Devam / Müşteri Bekleniyor / Tamamlandı">
                                        @if($c->asama['beklemede'])B:{{ $c->asama['beklemede'] }} @endif
                                        @if($c->asama['devam'])D:{{ $c->asama['devam'] }} @endif
                                        @if($c->asama['musteri_bekleniyor'])M:{{ $c->asama['musteri_bekleniyor'] }} @endif
                                        @if($c->asama['tamamlandi'])T:{{ $c->asama['tamamlandi'] }}@endif
                                    </div>
                                @endif
                            </td>
                            <td>{{ $c->erken }}@if($c->erken_gun) <span class="ae-sub">({{ $c->erken_gun }}g)</span>@endif</td>
                            <td>{{ $c->kanban }}</td>
                            <td>{{ number_format($c->is_puani) }}</td>
                            <td>{{ number_format($c->erken_bonus) }}</td>
                            <td>{{ $c->iyi_dilek }}</td>
                            <td><span class="ae-puan" style="font-size:16px">{{ number_format($c->toplam) }}</span></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
@endif

{{-- İYİ DİLEK PUANI MODÜLÜ --}}
<div class="section" style="margin-top:22px">
    <div class="section-title"><i data-lucide="heart-handshake"></i> <span>İyi Dilek Puanı</span></div>

    @if($patronMu)
        <form method="POST" action="{{ route('admin.randevu.iyi-dilek-ver') }}" class="ae-dilek-form" style="margin:12px 0 18px">
            @csrf
            <input type="hidden" name="ay" value="{{ $ay }}">
            <div class="fg">
                <label>Çalışan</label>
                <select name="yonetici_id" required>
                    <option value="">— Seç —</option>
                    @foreach($calisanlar as $c)
                        <option value="{{ $c->yonetici_id }}">{{ $c->ad }}</option>
                    @endforeach
                </select>
            </div>
            <div class="fg">
                <label>Puan (1-100)</label>
                <input type="number" name="puan" min="1" max="100" value="10" required style="width:110px">
            </div>
            <div class="fg" style="flex:1 1 220px">
                <label>Açıklama (opsiyonel)</label>
                <input type="text" name="aciklama" maxlength="500" placeholder="Örn: Müşteri memnuniyeti çok iyiydi">
            </div>
            <button type="submit" class="btn btn-primary"><i data-lucide="plus"></i> <span>Puan Ver</span></button>
        </form>
    @else
        <div class="ae-info" style="margin:10px 0 16px">İyi dilek puanını yalnızca <b>patron</b> rolündeki kullanıcılar verebilir.</div>
    @endif

    @if(count($dilekGecmis))
        <div class="table-wrap">
            <div class="table-scroll">
                <table class="data-table">
                    <thead>
                        <tr><th>Çalışan</th><th>Puan</th><th>Açıklama</th><th>Veren</th><th>Tarih</th>@if($patronMu)<th></th>@endif</tr>
                    </thead>
                    <tbody>
                        @foreach($dilekGecmis as $g)
                            <tr>
                                <td style="font-weight:600">{{ $calisanAd[$g->yonetici_id] ?? ('#'.$g->yonetici_id) }}</td>
                                <td><span class="badge badge-brand">+{{ $g->puan }}</span></td>
                                <td>{{ $g->aciklama ?: '—' }}</td>
                                <td class="ae-sub">{{ $g->veren_adi ?: '—' }}</td>
                                <td class="ae-sub">{{ \Illuminate\Support\Carbon::parse($g->created_at)->format('d.m.Y H:i') }}</td>
                                @if($patronMu)
                                <td>
                                    <form method="POST" action="{{ route('admin.randevu.iyi-dilek-sil', $g->id) }}" onsubmit="return confirm('Silinsin mi?')">
                                        @csrf @method('DELETE')
                                        <button class="btn btn-sm btn-ghost" type="submit"><i data-lucide="trash-2" style="width:15px;height:15px"></i></button>
                                    </form>
                                </td>
                                @endif
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @else
        <p class="ae-sub">Bu ay henüz iyi dilek puanı verilmedi.</p>
    @endif
</div>

{{-- PUANLAMA AYARLARI (yalnızca patron) --}}
@if($patronMu)
<div class="section" id="puan-ayar" style="margin-top:22px">
    <div class="section-title"><i data-lucide="sliders-horizontal"></i> <span>Puanlama Ayarları</span></div>
    <div class="ae-info" style="margin:10px 0 16px">
        Aşağıdaki puanları değiştirip kaydedebilirsiniz. Değişiklik <b>tüm aylar</b> için geçerli olur ve
        sıralama anında yeni puanlara göre hesaplanır. Görev her aşamaya ilerlediğinde ulaştığı aşamanın puanını alır.
    </div>

    <form method="POST" action="{{ route('admin.randevu.puan-ayar-kaydet') }}">
        @csrf
        @php
            $gruplar = [];
            foreach ($puanMeta as $anahtar => $bilgi) { $gruplar[$bilgi['grup']][$anahtar] = $bilgi['etiket']; }
        @endphp

        @foreach($gruplar as $grupAd => $alanlar)
            <div class="ae-ayar-grup">
                <div class="ae-ayar-grup-baslik">{{ $grupAd }}</div>
                <div class="ae-ayar-grid">
                    @foreach($alanlar as $anahtar => $etiket)
                        <div class="ae-ayar-alan">
                            <label for="puan_{{ $anahtar }}">{{ $etiket }}</label>
                            <input type="number" step="0.5" min="0" max="9999"
                                   id="puan_{{ $anahtar }}" name="puan[{{ $anahtar }}]"
                                   value="{{ $agirliklar[$anahtar] ?? 0 }}">
                        </div>
                    @endforeach
                </div>
            </div>
        @endforeach

        <div style="display:flex;gap:10px;margin-top:16px;flex-wrap:wrap">
            <button type="submit" class="btn btn-primary"><i data-lucide="save"></i> <span>Ayarları Kaydet</span></button>
        </div>
    </form>

    <form method="POST" action="{{ route('admin.randevu.puan-ayar-sifirla') }}"
          onsubmit="return confirm('Tüm puan ağırlıkları varsayılana döndürülecek. Onaylıyor musunuz?')"
          style="margin-top:10px">
        @csrf
        <button type="submit" class="btn btn-ghost btn-sm"><i data-lucide="rotate-ccw" style="width:15px;height:15px"></i> <span>Varsayılana Döndür</span></button>
    </form>
</div>
@endif

@endsection