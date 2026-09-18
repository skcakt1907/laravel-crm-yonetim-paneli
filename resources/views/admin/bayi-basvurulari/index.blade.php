@extends('admin._layout')

@section('title', 'İş Ortağı Başvuruları')

@section('content')

@php
    $sy = $sayilar ?? [
        'toplam'     => $basvurular->total(),
        'beklemede'  => $bekleyen ?? 0,
        'onaylandi'  => 0,
        'reddedildi' => 0,
    ];
    $aktifDurum = request('durum');
@endphp

<div class="breadcrumb">
    <a href="{{ route('admin.dashboard') }}">Anasayfa</a>
    <span class="sep">/</span>
    <span class="current">İş Ortağı Başvuruları</span>
</div>

<div class="page-header">
    <div>
        <h1 class="page-title">
            <i data-lucide="handshake"></i>
            İş Ortağı Başvuruları
            @if(($sy['beklemede'] ?? 0) > 0)<span class="badge badge-warning">{{ $sy['beklemede'] }} bekliyor</span>@endif
        </h1>
        <div class="page-subtitle">Bayi olmak için başvuranlar · onaylanınca hesap açılır ve şifre e-posta ile gönderilir</div>
    </div>
</div>

@if(session('success'))
    <div class="alert alert-success"><i data-lucide="check-circle"></i><div>{{ session('success') }}</div></div>
@endif
@if(session('error'))
    <div class="alert alert-danger"><i data-lucide="alert-circle"></i><div>{{ session('error') }}</div></div>
@endif

<style>
.iob-stats{display:grid;grid-template-columns:repeat(4,1fr);gap:14px;margin-bottom:16px}
@media (max-width:1100px){.iob-stats{grid-template-columns:repeat(2,1fr)}}
@media (max-width:560px){.iob-stats{grid-template-columns:1fr}}
.iob-stat{display:block;text-decoration:none;background: var(--card,var(--surface));border: 1px solid var(--border,var(--border));
    border-left: 4px solid var(--bar,var(--brand));border-radius: 12px;padding:16px 18px;transition:transform .15s ease,box-shadow .15s ease}
.iob-stat:hover{transform:translateY(-2px);box-shadow:0 8px 24px rgba(31,36,25,.10)}
.iob-stat.is-active{border-color: var(--bar,var(--brand));box-shadow:0 0 0 2px rgba(184,182,46,.22)}
.iob-stat .iob-lbl{display:flex;align-items:center;gap:7px;font-size:11.5px;font-weight:800;letter-spacing:.4px;
    text-transform:uppercase;color: var(--text-muted,var(--text-muted));margin-bottom:8px}
.iob-stat .iob-lbl i{width:14px;height:14px}
.iob-stat .iob-num{font-size:26px;font-weight:800;line-height:1;color: var(--text,var(--text))}
.iob-stat .iob-sub{font-size:11.5px;color: var(--text-muted,var(--text-muted));margin-top:5px}

.iob-modal{position:fixed;inset:0;background: rgba(15,18,13,.55);z-index:9999;display:none;
    align-items:center;justify-content:center;padding:20px}
.iob-modal.acik{display:flex}
.iob-modal-kutu{background: var(--card,var(--surface));border-radius: 16px;max-width:460px;width:100%;
    padding:26px;box-shadow:0 24px 60px rgba(0,0,0,.28)}
.iob-modal-ikon{width:52px;height:52px;border-radius: 50%;display:flex;align-items:center;justify-content:center;margin-bottom:14px}
.iob-modal-kutu h3{margin:0 0 6px;font-size:19px;font-weight:800;color: var(--text,var(--text))}
.iob-modal-kutu p{margin:0 0 16px;font-size:13.5px;line-height:1.6;color: var(--text-secondary,#6b6f63)}
.iob-modal-alt{display:flex;gap:10px;justify-content:flex-end;margin-top:20px}
@media (prefers-reduced-motion:reduce){.iob-stat{transition:none}}

/* ─── filtre çubuğu ─── */
.iob-filtre{display:flex;gap:10px;flex-wrap:wrap;align-items:center;
    background: var(--card,var(--surface));border: 1px solid var(--border,var(--border));
    border-radius: 12px;padding:12px 14px;margin-bottom:16px}
.iob-ara{position:relative;flex:1;min-width:240px;max-width:420px}
.iob-ara i{position:absolute;left:13px;top:50%;transform:translateY(-50%);
    width:16px;height:16px;color: var(--text-muted,#9a9d90);pointer-events:none}
.iob-ara input{width:100%;height:42px;padding:0 14px 0 38px;font-size:13.5px;
    border: 1px solid var(--border,var(--border));border-radius: 10px;
    background: var(--bg,#fbfbf7);color: var(--text,var(--text));outline: none;
    transition:border-color .15s ease,box-shadow .15s ease}
.iob-ara input:focus{border-color: var(--brand,var(--brand));box-shadow:0 0 0 3px rgba(184,182,46,.16)}
.iob-sec{height:42px;padding:0 34px 0 13px;font-size:13.5px;border-radius: 10px;
    border: 1px solid var(--border,var(--border));background: var(--bg,#fbfbf7);
    color: var(--text,var(--text));cursor:pointer;outline: none;min-width:170px;
    appearance:none;-webkit-appearance:none;
    background-image:url("data:image/svg+xml;charset=utf-8,%3Csvg xmlns='http://www.w3.org/2000/svg' width='14' height='14' fill='none' stroke='%239a9d90' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpath d='M3 5l4 4 4-4'/%3E%3C/svg%3E");
    background-repeat:no-repeat;background-position:right 12px center}
.iob-sec:focus{border-color: var(--brand,var(--brand));box-shadow:0 0 0 3px rgba(184,182,46,.16)}
.iob-btn{height:42px;padding:0 20px;border: 0;border-radius: 10px;cursor:pointer;
    font-size:13.5px;font-weight:700;display:inline-flex;align-items:center;gap:7px;
    background: var(--brand,var(--brand));color: #1a1a0e;transition:filter .15s ease}
.iob-btn:hover{filter:brightness(.94)}
.iob-btn i{width:15px;height:15px}
.iob-temizle{height:42px;padding:0 14px;display:inline-flex;align-items:center;gap:6px;
    border-radius: 10px;font-size:13px;font-weight:600;text-decoration:none;
    color: var(--text-muted,var(--text-muted));border: 1px solid var(--border,var(--border));
    transition:background .15s ease}
.iob-temizle:hover{background: var(--hover,#f7f8f3)}
.iob-temizle i{width:14px;height:14px}
.iob-sonuc{margin-left:auto;font-size:12.5px;color: var(--text-muted,var(--text-muted));white-space:nowrap}
@media (max-width:700px){.iob-sonuc{margin-left:0;width:100%}}
</style>

<div class="iob-stats">
    <a href="{{ route('admin.bayi-basvurulari.index') }}"
       class="iob-stat {{ $aktifDurum ? '' : 'is-active' }}" style="--bar:#8a8d80">
        <div class="iob-lbl"><i data-lucide="inbox"></i> Toplam</div>
        <div class="iob-num">{{ $sy['toplam'] }}</div>
        <div class="iob-sub">Tüm başvurular</div>
    </a>
    <a href="{{ route('admin.bayi-basvurulari.index', ['durum' => 'beklemede']) }}"
       class="iob-stat {{ $aktifDurum === 'beklemede' ? 'is-active' : '' }}" style="--bar:#f59e0b">
        <div class="iob-lbl"><i data-lucide="clock"></i> Bekleyen</div>
        <div class="iob-num">{{ $sy['beklemede'] }}</div>
        <div class="iob-sub">Karar bekliyor</div>
    </a>
    <a href="{{ route('admin.bayi-basvurulari.index', ['durum' => 'onaylandi']) }}"
       class="iob-stat {{ $aktifDurum === 'onaylandi' ? 'is-active' : '' }}" style="--bar:#10b981">
        <div class="iob-lbl"><i data-lucide="check-circle"></i> Onaylanan</div>
        <div class="iob-num">{{ $sy['onaylandi'] }}</div>
        <div class="iob-sub">Bayi hesabı açıldı</div>
    </a>
    <a href="{{ route('admin.bayi-basvurulari.index', ['durum' => 'reddedildi']) }}"
       class="iob-stat {{ $aktifDurum === 'reddedildi' ? 'is-active' : '' }}" style="--bar:#ef4444">
        <div class="iob-lbl"><i data-lucide="x-circle"></i> Reddedilen</div>
        <div class="iob-num">{{ $sy['reddedildi'] }}</div>
        <div class="iob-sub">Olumsuz sonuçlandı</div>
    </a>
</div>

<form method="GET" class="iob-filtre">
    <div class="iob-ara">
        <i data-lucide="search"></i>
        <input type="text" name="ara" value="{{ request('ara') }}"
               placeholder="Firma, kişi, e-posta veya telefon ara">
    </div>
    <select name="durum" class="iob-sec">
        <option value="">Tüm durumlar</option>
        <option value="beklemede"   @selected(request('durum')=='beklemede')>Bekleyen</option>
        <option value="onaylandi"   @selected(request('durum')=='onaylandi')>Onaylanan</option>
        <option value="reddedildi"  @selected(request('durum')=='reddedildi')>Reddedilen</option>
    </select>
    <button type="submit" class="iob-btn"><i data-lucide="search"></i> Filtrele</button>
    @if(request('ara') || request('durum'))
        <a href="{{ route('admin.bayi-basvurulari.index') }}" class="iob-temizle">
            <i data-lucide="x"></i> Temizle
        </a>
        <span class="iob-sonuc">{{ $basvurular->total() }} sonuç bulundu</span>
    @endif
</form>

@if($basvurular->isEmpty())
    <div class="section">
        <div class="empty-state">
            <i data-lucide="handshake" class="empty-state-icon"></i>
            <h4>Başvuru yok</h4>
            <p><code>/is-ortagi-basvuru</code> sayfasından başvuru geldikçe burada listelenir.</p>
        </div>
    </div>
@else
    <div class="table-wrap">
        <div class="table-scroll">
            <table class="data-table">
                <thead>
                    <tr>
                        <th style="width:60px">#</th>
                        <th style="width:230px">Firma / Yetkili</th>
                        <th>İletişim</th>
                        <th style="width:130px">Şehir</th>
                        <th style="width:130px">Durum</th>
                        <th style="width:130px">Tarih</th>
                        <th class="text-right" style="width:170px">İşlem</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($basvurular as $b)
                        @php $yeni = !$b->okundu && $b->durum === 'beklemede'; @endphp
                        <tr @if($yeni) style="background: linear-gradient(90deg, rgba(184,182,46,.07), transparent)" @endif>
                            <td style="color: var(--text-muted);font-size:12px;vertical-align:top;padding-top:14px">#{{ $b->id }}</td>
                            <td style="vertical-align:top;padding-top:12px">
                                <div style="display:flex;gap:10px;align-items:center">
                                    <div style="width:36px;height:36px;border-radius: 50%;background: linear-gradient(135deg,var(--brand),var(--brand-dark));color: var(--text);display:flex;align-items:center;justify-content:center;font-weight:700;font-size:13px;flex-shrink:0">
                                        {{ mb_strtoupper(mb_substr($b->firma_adi, 0, 1, 'UTF-8'), 'UTF-8') }}
                                    </div>
                                    <div style="min-width:0">
                                        <div style="font-weight:600;font-size:13px;color: var(--text)">{{ $b->firma_adi }}</div>
                                        <div style="font-size:11.5px;color: var(--text-muted)">{{ $b->ad_soyad }}</div>
                                        @if($yeni)<span class="badge badge-warning" style="font-size:9px">YENİ</span>@endif
                                    </div>
                                </div>
                            </td>
                            <td style="vertical-align:top;padding-top:14px;font-size:12.5px">
                                <div><i data-lucide="mail" style="width:12px;height:12px"></i> {{ $b->email }}</div>
                                <div style="color: var(--text-muted);margin-top:2px"><i data-lucide="phone" style="width:12px;height:12px"></i> {{ $b->telefon }}</div>
                            </td>
                            <td style="vertical-align:top;padding-top:14px;font-size:12.5px;color: var(--text-muted)">
                                {{ trim($b->il . ' ' . ($b->ilce ?? '')) ?: '—' }}
                            </td>
                            <td style="vertical-align:top;padding-top:12px">
                                @if($b->durum === 'beklemede')
                                    <span class="badge badge-warning">Bekliyor</span>
                                @elseif($b->durum === 'onaylandi')
                                    <span class="badge badge-success">Onaylandı</span>
                                @else
                                    <span class="badge badge-danger">Reddedildi</span>
                                @endif
                            </td>
                            <td style="vertical-align:top;padding-top:14px;font-size:12px;color: var(--text-muted)">
                                {{ $b->created_at ? $b->created_at->format('d.m.Y H:i') : '—' }}
                            </td>
                            <td class="text-right" style="vertical-align:top;padding-top:10px">
                                <div class="table-actions" style="justify-content:flex-end">
                                    @if($b->durum === 'beklemede')
                                        <button type="button" class="table-action" style="color: var(--success,var(--success))"
                                                onclick="iobOnayAc({{ $b->id }}, @js($b->firma_adi), @js($b->email))" title="Onayla">
                                            <i data-lucide="check"></i>
                                        </button>
                                        <button type="button" class="table-action" style="color: var(--warning,var(--warning))"
                                                onclick="iobRedAc({{ $b->id }}, @js($b->firma_adi))" title="Reddet">
                                            <i data-lucide="x"></i>
                                        </button>
                                    @endif
                                    <a href="{{ route('admin.bayi-basvurulari.goster', $b->id) }}" class="table-action" style="color: var(--brand-dark)" title="Gör"><i data-lucide="eye"></i></a>
                                    <button type="button" class="table-action" style="color: var(--danger)" onclick="iobSil({{ $b->id }})" title="Sil"><i data-lucide="trash-2"></i></button>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <div style="margin-top:16px">{{ $basvurular->links() }}</div>
@endif

{{-- ═══ ONAY MODALI ═══ --}}
<div class="iob-modal" id="iobOnayModal">
    <div class="iob-modal-kutu">
        <div class="iob-modal-ikon" style="background: rgba(16,185,129,.14);color: var(--success)">
            <i data-lucide="check-circle" style="width:26px;height:26px"></i>
        </div>
        <h3>Başvuruyu onayla</h3>
        <p>
            <strong id="iobOnayFirma"></strong> için <strong>bayi paneli hesabı otomatik oluşturulur</strong>,
            şifre üretilir ve <strong id="iobOnayMail"></strong> adresine gönderilir.
            Başvuranın mevcut bir üye hesabı varsa şifresi bu yeni şifreyle değişir. Bu işlem geri alınamaz.
        </p>
        <form method="POST" id="iobOnayForm">
            @csrf
            <label class="form-label">Komisyon oranı (%)</label>
            <input type="number" name="komisyon_orani" id="iobKomisyon" value="10" min="0" max="100" step="0.5"
                   class="form-control" style="max-width:150px">
            <div class="iob-modal-alt">
                <button type="button" class="btn" onclick="iobKapat('iobOnayModal')">Vazgeç</button>
                <button type="submit" class="btn btn-success"><i data-lucide="check"></i> Onayla ve hesabı aç</button>
            </div>
        </form>
    </div>
</div>

{{-- ═══ RED MODALI ═══ --}}
<div class="iob-modal" id="iobRedModal">
    <div class="iob-modal-kutu">
        <div class="iob-modal-ikon" style="background: rgba(239,68,68,.14);color: var(--danger)">
            <i data-lucide="x-circle" style="width:26px;height:26px"></i>
        </div>
        <h3>Başvuruyu reddet</h3>
        <p><strong id="iobRedFirma"></strong> başvurusu reddedilecek ve başvurana bilgi maili gidecek. Hesap açılmaz.</p>
        <form method="POST" id="iobRedForm">
            @csrf
            <label class="form-label">Red nedeni (isteğe bağlı — başvurana iletilir)</label>
            <input type="text" name="red_nedeni" class="form-control" maxlength="500"
                   placeholder="Örn: Şu an bölgenizde bayi kontenjanı dolu">
            <div class="iob-modal-alt">
                <button type="button" class="btn" onclick="iobKapat('iobRedModal')">Vazgeç</button>
                <button type="submit" class="btn btn-danger"><i data-lucide="x"></i> Reddet</button>
            </div>
        </form>
    </div>
</div>

<form id="iobSilForm" method="POST" style="display:none">
    @csrf
    @method('DELETE')
</form>

<script>
var IOB_KOK = '{{ url("admin/bayi-basvurulari") }}';

function iobAc(id)    { document.getElementById(id).classList.add('acik'); }
function iobKapat(id) { document.getElementById(id).classList.remove('acik'); }

function iobOnayAc(id, firma, mail) {
    document.getElementById('iobOnayFirma').textContent = firma;
    document.getElementById('iobOnayMail').textContent  = mail;
    document.getElementById('iobOnayForm').action = IOB_KOK + '/' + id + '/onayla';
    document.getElementById('iobKomisyon').value = 10;
    iobAc('iobOnayModal');
}

function iobRedAc(id, firma) {
    document.getElementById('iobRedFirma').textContent = firma;
    document.getElementById('iobRedForm').action = IOB_KOK + '/' + id + '/reddet';
    iobAc('iobRedModal');
}

function iobSil(id) {
    if (!confirm('Bu başvuru silinsin mi? Bu işlem geri alınamaz.')) return;
    var f = document.getElementById('iobSilForm');
    f.action = IOB_KOK + '/' + id;
    f.submit();
}

// Dışarı tıkla / ESC ile kapat
document.addEventListener('click', function (e) {
    if (e.target.classList && e.target.classList.contains('iob-modal')) {
        e.target.classList.remove('acik');
    }
});
document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape') {
        iobKapat('iobOnayModal');
        iobKapat('iobRedModal');
    }
});
</script>
@endsection