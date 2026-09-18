@extends('admin._layout')

@section('title', 'Başvuru — ' . $basvuru->firma_adi)

@section('content')

@php
    $bDurum = $basvuru->durum;
    $rozet  = $bDurum === 'beklemede'
        ? ['badge-warning', 'Bekliyor',   'clock',       '#f59e0b']
        : ($bDurum === 'onaylandi'
            ? ['badge-success', 'Onaylandı',  'check-circle', '#10b981']
            : ['badge-danger',  'Reddedildi', 'x-circle',     '#ef4444']);
    $telTemiz = preg_replace('/[^0-9+]/', '', (string) $basvuru->telefon);
@endphp

<div class="breadcrumb">
    <a href="{{ route('admin.dashboard') }}">Anasayfa</a>
    <span class="sep">/</span>
    <a href="{{ route('admin.bayi-basvurulari.index') }}">İş Ortağı Başvuruları</a>
    <span class="sep">/</span>
    <span class="current">#{{ $basvuru->id }}</span>
</div>

<div class="page-header">
    <div>
        <h1 class="page-title">
            <i data-lucide="handshake"></i>
            {{ $basvuru->firma_adi }}
            <span class="badge {{ $rozet[0] }}">{{ $rozet[1] }}</span>
        </h1>
        <div class="page-subtitle">{{ $basvuru->ad_soyad }} · {{ $basvuru->created_at ? $basvuru->created_at->format('d.m.Y H:i') : '' }}</div>
    </div>
    <div>
        <a href="{{ route('admin.bayi-basvurulari.index') }}" class="btn"><i data-lucide="arrow-left"></i> Listeye dön</a>
    </div>
</div>

@if(session('success'))
    <div class="alert alert-success"><i data-lucide="check-circle"></i><div>{{ session('success') }}</div></div>
@endif
@if(session('error'))
    <div class="alert alert-danger"><i data-lucide="alert-circle"></i><div>{{ session('error') }}</div></div>
@endif

<style>
.iod-grid{display:grid;grid-template-columns:1fr 320px;gap:16px;align-items:start}
@media (max-width:980px){.iod-grid{grid-template-columns:1fr}}
.iod-alanlar{display:grid;grid-template-columns:repeat(2,1fr);gap:2px 24px}
@media (max-width:640px){.iod-alanlar{grid-template-columns:1fr}}
.iod-alan{padding:13px 0;border-bottom:1px solid var(--border,#f0efe6)}
.iod-alan.tam{grid-column:1/-1}
.iod-etiket{font-size:11px;font-weight:800;letter-spacing:.4px;text-transform:uppercase;
    color:var(--text-muted,#8a8718);margin-bottom:5px}
.iod-deger{font-size:14px;line-height:1.6;color:var(--text,#2b2b1f);word-break:break-word}
.iod-deger a{color:var(--brand-dark,#8a8a1f);text-decoration:none}
.iod-deger a:hover{text-decoration:underline}
.iod-bos{color:var(--text-muted,#9a9d90)}
.iod-durum{display:flex;align-items:center;gap:12px;padding:4px 0 14px;border-bottom:1px solid var(--border,#f0efe6);margin-bottom:14px}
.iod-durum-ikon{width:44px;height:44px;border-radius:12px;display:flex;align-items:center;justify-content:center;flex-shrink:0}
.iod-hizli{display:flex;flex-direction:column;gap:8px}
.iod-hizli a{display:flex;align-items:center;gap:9px;padding:11px 13px;border-radius:10px;
    border:1px solid var(--border,#e6e6dc);text-decoration:none;font-size:13px;font-weight:600;
    color:var(--text,#2b2b1f);transition:background .15s ease}
.iod-hizli a:hover{background:var(--hover,#f7f8f3)}
.iod-hizli a i{width:15px;height:15px;color:var(--brand-dark,#8a8a1f)}
.iod-islem{display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-top:16px}
@media (max-width:860px){.iod-islem{grid-template-columns:1fr}}
</style>

<div class="iod-grid">

    {{-- ═══ SOL: başvuru bilgileri ═══ --}}
    <div class="section">
        <div class="section-header"><h3 class="section-title"><i data-lucide="file-text"></i> Başvuru Bilgileri</h3></div>

        <div class="iod-alanlar">
            <div class="iod-alan">
                <div class="iod-etiket">Firma / İşletme</div>
                <div class="iod-deger">{{ $basvuru->firma_adi }}</div>
            </div>
            <div class="iod-alan">
                <div class="iod-etiket">Yetkili</div>
                <div class="iod-deger">{{ $basvuru->ad_soyad }}</div>
            </div>
            <div class="iod-alan">
                <div class="iod-etiket">E-posta</div>
                <div class="iod-deger"><a href="mailto:{{ $basvuru->email }}">{{ $basvuru->email }}</a></div>
            </div>
            <div class="iod-alan">
                <div class="iod-etiket">Telefon</div>
                <div class="iod-deger"><a href="tel:{{ $telTemiz }}">{{ $basvuru->telefon }}</a></div>
            </div>
            <div class="iod-alan">
                <div class="iod-etiket">Şehir / İlçe</div>
                <div class="iod-deger">{{ trim($basvuru->il . ' ' . ($basvuru->ilce ?? '')) ?: '—' }}</div>
            </div>
            <div class="iod-alan">
                <div class="iod-etiket">Faaliyet alanı</div>
                <div class="iod-deger">{{ $basvuru->faaliyet ?: '—' }}</div>
            </div>
            <div class="iod-alan tam">
                <div class="iod-etiket">Mesaj</div>
                <div class="iod-deger {{ $basvuru->mesaj ? '' : 'iod-bos' }}">
                    {!! $basvuru->mesaj ? nl2br(e($basvuru->mesaj)) : 'Mesaj bırakılmamış' !!}
                </div>
            </div>
            <div class="iod-alan">
                <div class="iod-etiket">Sözleşme onayı</div>
                <div class="iod-deger">
                    @if($basvuru->kvkk)
                        <span style="color:var(--success,#10b981);font-weight:600">✓ Onaylandı</span>
                    @else
                        <span style="color:var(--danger,#ef4444);font-weight:600">Onaylanmadı</span>
                    @endif
                </div>
            </div>
            <div class="iod-alan">
                <div class="iod-etiket">IP adresi</div>
                <div class="iod-deger iod-bos" style="font-size:13px">{{ $basvuru->ip ?: '—' }}</div>
            </div>
        </div>
    </div>

    {{-- ═══ SAĞ: durum + hızlı işlemler ═══ --}}
    <div class="section">
        <div class="iod-durum">
            <div class="iod-durum-ikon" style="background:{{ $rozet[3] }}22;color:{{ $rozet[3] }}">
                <i data-lucide="{{ $rozet[2] }}" style="width:22px;height:22px"></i>
            </div>
            <div>
                <div style="font-size:15px;font-weight:800;color:var(--text)">{{ $rozet[1] }}</div>
                <div style="font-size:11.5px;color:var(--text-muted)">
                    @if($bDurum === 'beklemede')
                        Karar bekliyor
                    @else
                        {{ $basvuru->onay_tarihi ? $basvuru->onay_tarihi->format('d.m.Y H:i') : '—' }}
                    @endif
                </div>
            </div>
        </div>

        @if($bDurum !== 'beklemede')
            <div style="margin-bottom:14px">
                @if($basvuru->red_nedeni)
                    <div class="iod-etiket">Red nedeni</div>
                    <div class="iod-deger" style="font-size:13px;margin-bottom:12px">{{ $basvuru->red_nedeni }}</div>
                @endif
                @if($bDurum === 'onaylandi')
                    <div class="iod-etiket">Oluşturulan bayi</div>
                    <div class="iod-deger" style="font-size:13px">
                        @if($basvuru->bayi_id && Route::has('admin.bayiler.detay'))
                            <a href="{{ route('admin.bayiler.detay', $basvuru->bayi_id) }}">Bayi kaydını aç →</a>
                        @else
                            Bayi #{{ $basvuru->bayi_id ?? '—' }}
                        @endif
                    </div>
                @endif
            </div>
        @endif

        <div class="iod-etiket" style="margin-bottom:9px">Hızlı işlemler</div>
        <div class="iod-hizli">
            <a href="mailto:{{ $basvuru->email }}"><i data-lucide="mail"></i> E-posta gönder</a>
            <a href="tel:{{ $telTemiz }}"><i data-lucide="phone"></i> Telefonla ara</a>
            <a href="https://wa.me/{{ ltrim($telTemiz, '+') }}" target="_blank" rel="noopener">
                <i data-lucide="message-circle"></i> WhatsApp'tan yaz
            </a>
        </div>
    </div>
</div>

@if($bDurum === 'beklemede')

    <div class="iod-islem">
        <div class="section">
            <div class="section-header"><h3 class="section-title"><i data-lucide="check-circle"></i> Onayla</h3></div>
            <div style="padding:4px 0 14px;color:var(--text-secondary);font-size:13px;line-height:1.6">
                Onayladığında <strong>bayi paneli hesabı otomatik oluşturulur</strong>, şifre üretilir ve
                <strong>{{ $basvuru->email }}</strong> adresine gönderilir.
                Başvuranın mevcut bir üye hesabı varsa şifresi bu yeni şifreyle değişir. Bu işlem geri alınamaz.
            </div>
            <form method="POST" action="{{ route('admin.bayi-basvurulari.onayla', $basvuru->id) }}"
                  onsubmit="return confirm('Başvuru onaylanacak, hesap açılacak ve şifre e-posta ile gönderilecek. Onaylıyor musun?')">
                @csrf
                <label class="form-label">Komisyon oranı (%)</label>
                <input type="number" name="komisyon_orani" value="10" min="0" max="100" step="0.5"
                       class="form-control" style="max-width:150px;margin-bottom:14px">
                <div>
                    <button type="submit" class="btn btn-success"><i data-lucide="check"></i> Onayla ve hesabı aç</button>
                </div>
            </form>
        </div>

        <div class="section">
            <div class="section-header"><h3 class="section-title"><i data-lucide="x-circle"></i> Reddet</h3></div>
            <div style="padding:4px 0 14px;color:var(--text-secondary);font-size:13px;line-height:1.6">
                Hesap oluşturulmaz. Başvurana bilgilendirme maili gider; red nedeni yazarsan mailde ona da yer verilir.
            </div>
            <form method="POST" action="{{ route('admin.bayi-basvurulari.reddet', $basvuru->id) }}"
                  onsubmit="return confirm('Başvuru reddedilecek ve başvurana bilgi maili gidecek. Emin misin?')">
                @csrf
                <label class="form-label">Red nedeni (isteğe bağlı)</label>
                <input type="text" name="red_nedeni" class="form-control" maxlength="500"
                       placeholder="Örn: Şu an bölgenizde bayi kontenjanı dolu" style="margin-bottom:14px">
                <div>
                    <button type="submit" class="btn btn-danger"><i data-lucide="x"></i> Reddet</button>
                </div>
            </form>
        </div>
    </div>

@endif
@endsection