@extends('admin._layout')

@section('title', 'Personel Kartı')

@push('head')
<style>
    .hg-back{display:inline-flex;align-items:center;gap:6px;color: var(--text-secondary);text-decoration:none;font-size:13.5px;font-weight:600;margin-bottom:16px}
    .hg-back:hover{color: var(--brand-hover)}
    .hg-top{display:flex;align-items:center;gap:16px;margin-bottom:22px;flex-wrap:wrap}
    .hg-avatar{width:64px;height:64px;border-radius: 16px;background: var(--brand);color: var(--text);display:flex;align-items:center;justify-content:center;font-weight:800;font-size:26px}
    .hg-ad{font-size:22px;font-weight:800;color: var(--text);margin:0}
    .hg-rol{font-size:12.5px;font-weight:600;padding:3px 11px;border-radius: 20px;display:inline-block;margin-top:5px}
    .hg-cols{display:grid;grid-template-columns:1.4fr 1fr;gap:20px;align-items:start}
    .hg-card{background: var(--surface);border: 1px solid var(--border);border-radius: 16px;padding:22px;margin-bottom:20px}
    .hg-card h3{font-size:15px;font-weight:700;margin:0 0 16px;display:flex;align-items:center;gap:8px;color: var(--text)}
    .hg-row{display:grid;grid-template-columns:1fr 1fr;gap:14px;margin-bottom:14px}
    .hg-row.tek{grid-template-columns:1fr}
    .hg-grup{display:flex;flex-direction:column;gap:6px}
    .hg-label{font-size:12.5px;font-weight:700;color: var(--text-secondary)}.hg-input,.hg-textarea{border: 1.5px solid var(--border);border-radius: 10px;padding:10px 12px;font-size:13.5px;font-family:inherit;color: var(--text);width:100%;box-sizing:border-box;background: var(--surface);}
    .hg-input:focus,.hg-textarea:focus{outline: none;border-color: var(--brand);box-shadow:0 0 0 3px rgba(184,182,46,.1)}.hg-textarea{resize:vertical;min-height:70px;background: var(--surface);color: var(--text);}
    .hg-save{background: var(--brand);color: var(--text);border: none;border-radius: 11px;padding:11px 24px;font-weight:700;cursor:pointer;font-size:14px;display:inline-flex;align-items:center;gap:7px}
    .hg-save:hover{background: var(--brand-hover)}
    /* İzin bakiyesi */
    .hg-bakiye{display:flex;gap:10px;margin-bottom:18px}
    .hg-bk{flex:1;background: var(--surface);border: 1px solid var(--border);border-radius: 12px;padding:14px;text-align:center}
    .hg-bk-v{font-size:24px;font-weight:800}
    .hg-bk-l{font-size:11.5px;color: var(--text-muted);font-weight:600;margin-top:3px}
    .hg-bk.hak .hg-bk-v{color: var(--info)}
    .hg-bk.kul .hg-bk-v{color: var(--warning)}
    .hg-bk.kal .hg-bk-v{color: var(--success)}
    /* İzin listesi */
    .hg-izin{display:flex;align-items:center;justify-content:space-between;padding:11px 0;border-bottom: 1px solid var(--border);font-size:13px}
    .hg-izin:last-child{border-bottom: none}
    .hg-d{font-size:11.5px;font-weight:600;padding:2px 9px;border-radius: 20px}
    .hg-d.bekliyor{background: rgba(245,158,11,.14);color: var(--warning)}
    .hg-d.onaylandi{background: rgba(16,185,129,.12);color: var(--success)}
    .hg-d.reddedildi{background: rgba(239,68,68,.12);color: var(--danger)}
    /* Dosyalar */
    .hg-dosya{display:flex;align-items:center;gap:11px;padding:10px 12px;border: 1px solid #f1f3f5;border-radius: 10px;margin-bottom:8px}
    .hg-dosya i.fi{width:18px;height:18px;color: var(--brand-hover);flex-shrink:0}
    .hg-dosya a{flex:1;font-size:13.5px;color: var(--text-secondary);text-decoration:none;font-weight:600;word-break:break-all}
    .hg-dosya a:hover{color: var(--brand-hover)}
    .hg-dosya-tip{font-size:11px;color: var(--text-muted);background: var(--bg-subtle);padding:2px 8px;border-radius: 20px}
    .hg-del{width:30px;height:30px;border-radius: 8px;border: 1px solid var(--border);background: var(--surface);color: var(--text-secondary);display:flex;align-items:center;justify-content:center;cursor:pointer}
    .hg-del:hover{border-color: var(--danger);color: var(--danger)}
    .hg-yukle{display:flex;gap:9px;margin-top:12px;flex-wrap:wrap;align-items:center}
    .hg-yukle input[type=file]{font-size:13px;flex:1;min-width:160px}.hg-yukle select{border: 1.5px solid var(--border);border-radius: 9px;padding:8px 10px;font-size:13px;background: var(--surface);color: var(--text);}
    .hg-yukle button{background: #475569;color: var(--text-inverse);border: none;border-radius: 9px;padding:9px 16px;font-weight:600;cursor:pointer;font-size:13px}
    @media(max-width:900px){.hg-cols{grid-template-columns:1fr}}
    @media(max-width:600px){.hg-row{grid-template-columns:1fr}}
</style>
@endpush

@section('content')
<a href="{{ route('admin.hrm.personel.index') }}" class="hg-back"><i data-lucide="arrow-left" style="width:16px;height:16px"></i> Personel listesine dön</a>

@if(session('success'))<div style="background: rgba(16,185,129,.1);border: 1px solid rgba(16,185,129,.3);color: var(--success);padding:12px 16px;border-radius: 12px;margin-bottom:18px;font-size:14px">{{ session('success') }}</div>@endif
@if(session('error'))<div style="background: rgba(239,68,68,.1);border: 1px solid rgba(239,68,68,.3);color: var(--danger);padding:12px 16px;border-radius: 12px;margin-bottom:18px;font-size:14px">{{ session('error') }}</div>@endif
@if($errors->any())<div style="background: rgba(239,68,68,.1);border: 1px solid rgba(239,68,68,.3);color: var(--danger);padding:12px 16px;border-radius: 12px;margin-bottom:18px;font-size:14px">@foreach($errors->all() as $e)<div>{{ $e }}</div>@endforeach</div>@endif

<div class="hg-top">
    <div class="hg-avatar">{{ mb_strtoupper(mb_substr($personel->adi ?? $personel->kullaniciadi ?? '?', 0, 1)) }}</div>
    <div>
        <h1 class="hg-ad">{{ $personel->adi ?? $personel->kullaniciadi }}</h1>
        @if(!empty($personel->rol_adi))<span class="hg-rol" style="background: {{ $personel->rol_renk ?? '#64748b' }}22;color: var(--brand-hover)">{{ $personel->rol_adi }}</span>@endif
        @if(!empty($personel->email))<span style="font-size:13px;color: var(--text-muted);margin-left:8px">{{ $personel->email }}</span>@endif
    </div>
</div>

<div class="hg-cols">
    {{-- SOL: Özlük formu --}}
    <div>
        <form action="{{ route('admin.hrm.personel.ozluk', $personel->id) }}" method="POST" class="hg-card">
            @csrf
            <h3><i data-lucide="id-card" style="width:18px;height:18px"></i> Özlük Bilgileri</h3>
            <div class="hg-row">
                <div class="hg-grup"><label class="hg-label">İşe Başlama Tarihi</label><input type="date" name="ise_baslama_tarihi" class="hg-input" value="{{ old('ise_baslama_tarihi', $ozluk->ise_baslama_tarihi ?? '') }}"></div>
                <div class="hg-grup"><label class="hg-label">Doğum Tarihi</label><input type="date" name="dogum_tarihi" class="hg-input" value="{{ old('dogum_tarihi', $ozluk->dogum_tarihi ?? '') }}"></div>
            </div>
            <div class="hg-row">
                <div class="hg-grup"><label class="hg-label">Departman</label><input type="text" name="departman" class="hg-input" value="{{ old('departman', $ozluk->departman ?? '') }}" placeholder="Örn: Grafik, Pazarlama"></div>
                <div class="hg-grup"><label class="hg-label">Pozisyon</label><input type="text" name="pozisyon" class="hg-input" value="{{ old('pozisyon', $ozluk->pozisyon ?? '') }}" placeholder="Örn: Tasarımcı"></div>
            </div>
            <div class="hg-row">
                <div class="hg-grup"><label class="hg-label">TC Kimlik No</label><input type="text" name="tc" class="hg-input" maxlength="11" value="{{ old('tc', $ozluk->tc ?? '') }}"></div>
                <div class="hg-grup"><label class="hg-label">Telefon</label><input type="text" name="telefon" class="hg-input" value="{{ old('telefon', $ozluk->telefon ?? '') }}"></div>
            </div>
            <div class="hg-row tek">
                <div class="hg-grup"><label class="hg-label">Adres</label><textarea name="adres" class="hg-textarea">{{ old('adres', $ozluk->adres ?? '') }}</textarea></div>
            </div>
            <div class="hg-row">
                <div class="hg-grup"><label class="hg-label">Acil Durum Kişisi</label><input type="text" name="acil_durum_kisi" class="hg-input" value="{{ old('acil_durum_kisi', $ozluk->acil_durum_kisi ?? '') }}"></div>
                <div class="hg-grup"><label class="hg-label">Acil Durum Tel</label><input type="text" name="acil_durum_tel" class="hg-input" value="{{ old('acil_durum_tel', $ozluk->acil_durum_tel ?? '') }}"></div>
            </div>
            <div class="hg-row">
                <div class="hg-grup"><label class="hg-label">Yıllık İzin Hakkı (gün)</label><input type="number" name="yillik_izin_hakki" class="hg-input" min="0" max="365" value="{{ old('yillik_izin_hakki', $ozluk->yillik_izin_hakki ?? 14) }}"></div>
                <div></div>
            </div>
            <div class="hg-row tek">
                <div class="hg-grup"><label class="hg-label">Notlar</label><textarea name="notlar" class="hg-textarea">{{ old('notlar', $ozluk->notlar ?? '') }}</textarea></div>
            </div>
            <button type="submit" class="hg-save"><i data-lucide="check" style="width:17px;height:17px"></i> Kaydet</button>
        </form>

        {{-- Özlük dosyaları --}}
        <div class="hg-card">
            <h3><i data-lucide="folder" style="width:18px;height:18px"></i> Özlük Dosyaları</h3>
            @forelse($dosyalar as $d)
            <div class="hg-dosya">
                <i data-lucide="file-text" class="fi"></i>
                <a href="{{ asset($d->dosya_yolu) }}" target="_blank">{{ $d->dosya_adi }}</a>
                <span class="hg-dosya-tip">{{ ucfirst($d->tip) }}</span>
                <form action="{{ route('admin.hrm.personel.dosya.sil', [$personel->id, $d->id]) }}" method="POST" onsubmit="return confirm('Dosya silinsin mi?')">
                    @csrf @method('DELETE')
                    <button type="submit" class="hg-del"><i data-lucide="trash-2" style="width:15px;height:15px"></i></button>
                </form>
            </div>
            @empty
            <p style="color: var(--text-muted);font-size:13px">Henüz dosya yüklenmemiş.</p>
            @endforelse

            <form action="{{ route('admin.hrm.personel.dosya.yukle', $personel->id) }}" method="POST" enctype="multipart/form-data" class="hg-yukle">
                @csrf
                <input type="file" name="dosya" required>
                <select name="tip">
                    <option value="sozlesme">Sözleşme</option>
                    <option value="kimlik">Kimlik</option>
                    <option value="diploma">Diploma</option>
                    <option value="diger">Diğer</option>
                </select>
                <button type="submit"><i data-lucide="upload" style="width:14px;height:14px;vertical-align:-2px"></i> Yükle</button>
            </form>
        </div>
    </div>

    {{-- SAĞ: İzin bakiyesi + geçmiş --}}
    <div>
        <div class="hg-card">
            <h3><i data-lucide="calendar-check" style="width:18px;height:18px"></i> Yıllık İzin Bakiyesi</h3>
            <div class="hg-bakiye">
                <div class="hg-bk hak"><div class="hg-bk-v">{{ $izinBakiye['hak'] }}</div><div class="hg-bk-l">HAK</div></div>
                <div class="hg-bk kul"><div class="hg-bk-v">{{ $izinBakiye['kullanilan'] }}</div><div class="hg-bk-l">KULLANILAN</div></div>
                <div class="hg-bk kal"><div class="hg-bk-v">{{ $izinBakiye['kalan'] }}</div><div class="hg-bk-l">KALAN</div></div>
            </div>
        </div>

        <div class="hg-card">
            <h3><i data-lucide="clock" style="width:18px;height:18px"></i> Son İzin Talepleri</h3>
            @forelse($izinler as $iz)
            <div class="hg-izin">
                <div>
                    <strong>{{ ['yillik'=>'Yıllık','hastalik'=>'Hastalık','mazeret'=>'Mazeret','ucretsiz'=>'Ücretsiz'][$iz->izin_tipi] ?? $iz->izin_tipi }}</strong>
                    <div style="color: var(--text-muted);font-size:12px">{{ \Carbon\Carbon::parse($iz->baslangic)->format('d.m.Y') }} - {{ \Carbon\Carbon::parse($iz->bitis)->format('d.m.Y') }} ({{ $iz->gun_sayisi }}g)</div>
                </div>
                <span class="hg-d {{ $iz->durum }}">{{ ['bekliyor'=>'Bekliyor','onaylandi'=>'Onaylandı','reddedildi'=>'Reddedildi'][$iz->durum] ?? $iz->durum }}</span>
            </div>
            @empty
            <p style="color: var(--text-muted);font-size:13px">İzin talebi yok.</p>
            @endforelse
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>if(window.lucide)lucide.createIcons();</script>
@endpush
