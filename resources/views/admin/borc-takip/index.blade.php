@extends('admin._layout')

@section('title', 'Borç Takip')

@push('head')
<style>
    .bt-head{display:flex;align-items:center;justify-content:space-between;gap:16px;flex-wrap:wrap;margin-bottom:8px}
    .bt-head h1{font-size:24px;font-weight:800;margin:0;display:flex;align-items:center;gap:10px}
    .bt-sub{color: var(--text-muted);font-size:13.5px;margin-bottom:20px}

    /* Genel özet kartı */
    .bt-genel{background: var(--surface);border: 1px solid var(--border);border-radius: 18px;padding:26px;margin-bottom:22px;display:flex;align-items:center;justify-content:space-between;gap:20px;flex-wrap:wrap}
    .bt-genel-l{font-size:13px;font-weight:600;color: var(--text-muted);text-transform:uppercase;letter-spacing:.5px}
    .bt-genel-v{font-size:38px;font-weight:800;margin-top:6px;line-height:1}
    .bt-genel-v.eksi{color: var(--danger)}
    .bt-genel-v.art{color: var(--success)}
    .bt-genel-aciklama{font-size:13px;color: var(--text-secondary);margin-top:8px}
    .bt-genel-ikon{width:74px;height:74px;border-radius: 18px;display:flex;align-items:center;justify-content:center;background: #fef2f2}

    /* Segment kartları (3 kategori) */
    .bt-segmentler{display:grid;grid-template-columns:1fr;gap:22px}
    .bt-segment{background: var(--surface);border: 1px solid var(--border);border-radius: 18px;overflow:hidden}
    .bt-seg-head{display:flex;align-items:center;justify-content:space-between;gap:14px;padding:18px 22px;border-bottom: 1px solid var(--border);flex-wrap:wrap}
    .bt-seg-baslik{display:flex;align-items:center;gap:12px;font-size:18px;font-weight:800;color: var(--text)}
    .bt-seg-ikon{width:44px;height:44px;border-radius: 12px;display:flex;align-items:center;justify-content:center;font-size:22px}
    .bt-seg-ozet{display:flex;gap:18px;flex-wrap:wrap;align-items:center}
    .bt-ozet-blok{text-align:right}
    .bt-ozet-blok .lbl{font-size:11px;color: var(--text-muted);text-transform:uppercase;letter-spacing:.4px;display:block}
    .bt-ozet-blok .val{font-size:17px;font-weight:800;margin-top:2px}
    .bt-ozet-blok .val.borc{color: var(--danger)}
    .bt-ozet-blok .val.odeme{color: var(--success)}
    .bt-ozet-blok .val.net.eksi{color: var(--danger)}
    .bt-ozet-blok .val.net.art{color: var(--success)}

    .bt-seg-govde{padding:18px 22px}

    /* Ekleme formu */
    .bt-form{display:grid;grid-template-columns:130px 1fr 150px 150px auto;gap:10px;align-items:end;margin-bottom:18px;padding:16px;background: var(--bg-subtle);border-radius: 12px;border: 1px solid var(--border)}
    .bt-form label{font-size:11px;font-weight:700;color: var(--text-secondary);display:block;margin-bottom:5px}
    .bt-form select,.bt-form input{width:100%;padding:9px 11px;border: 1px solid var(--border);border-radius: 8px;font-size:13.5px;font-family:inherit;outline: none;background: var(--surface)}
    .bt-form select:focus,.bt-form input:focus{border-color: var(--brand)}
    .bt-form-btn{padding:9px 18px;background: var(--brand);color: var(--text);border: none;border-radius: 8px;font-weight:700;font-size:13.5px;cursor:pointer;white-space:nowrap;height:38px}
    .bt-form-btn:hover{background: var(--brand-hover)}

    /* Liste tablosu */
    .bt-tablo{width:100%;border-collapse: collapse;font-size:13.5px}
    .bt-tablo th{text-align:left;padding:9px 10px;color: var(--text-muted);font-size:11px;text-transform:uppercase;letter-spacing:.4px;border-bottom: 2px solid var(--border);font-weight:700}
    .bt-tablo th.sag,.bt-tablo td.sag{text-align:right}
    .bt-tablo td{padding:10px;border-bottom: 1px solid #f5f5f5;vertical-align:middle}
    .bt-tablo tr:hover td{background: var(--surface-hover)}
    .bt-pill{display:inline-block;padding:3px 10px;border-radius: 999px;font-size:11px;font-weight:700}
    .bt-pill.borc{background: var(--danger-soft);color: var(--danger)}
    .bt-pill.odeme{background: var(--success-soft);color: #065f46}
    .bt-tutar.borc{color: var(--danger);font-weight:700}
    .bt-tutar.odeme{color: var(--success);font-weight:700}
    .bt-islem-btn{background: none;border: none;cursor:pointer;padding:5px;border-radius: 6px;color: var(--text-muted)}
    .bt-islem-btn:hover{background: var(--bg-subtle);color: var(--text)}
    .bt-islem-btn.sil:hover{background: var(--danger-soft);color: var(--danger)}
    .bt-bos{text-align:center;padding:26px;color: var(--text-muted);font-size:13.5px}

    /* Düzenleme modal */
    .bt-modal-arka{display:none;position:fixed;inset:0;background: rgba(0,0,0,.5);z-index:9999;align-items:center;justify-content:center;padding:20px}
    .bt-modal-arka.acik{display:flex}
    .bt-modal{background: var(--surface);border-radius: 16px;padding:24px;max-width:460px;width:100%;box-shadow:0 24px 48px rgba(0,0,0,.2)}
    .bt-modal h3{font-size:18px;font-weight:800;margin:0 0 18px;color: var(--text)}
    .bt-modal label{font-size:12px;font-weight:700;color: var(--text-secondary);display:block;margin-bottom:5px;margin-top:12px}.bt-modal select,.bt-modal input,.bt-modal textarea{width:100%;padding:10px 12px;border: 1px solid var(--border);border-radius: 8px;font-size:14px;font-family:inherit;outline: none;background: var(--surface);color: var(--text);}
    .bt-modal-footer{display:flex;justify-content:flex-end;gap:10px;margin-top:20px}
    .bt-btn-iptal{padding:9px 18px;background: var(--bg-subtle);color: var(--text-secondary);border: none;border-radius: 8px;font-weight:700;cursor:pointer}
    .bt-btn-kaydet{padding:9px 18px;background: var(--brand);color: var(--text);border: none;border-radius: 8px;font-weight:700;cursor:pointer}

    .bt-uyari{background: #fffbeb;border: 1px solid #fde68a;color: var(--warning);padding:16px 20px;border-radius: 12px;margin-bottom:18px;font-size:14px}
    .bt-alert{padding:13px 18px;border-radius: 12px;margin-bottom:18px;font-size:14px;font-weight:600}
    .bt-alert.ok{background: var(--success-soft);color: #065f46}
    .bt-alert.err{background: var(--danger-soft);color: var(--danger)}

    @media(max-width:760px){
        .bt-form{grid-template-columns:1fr 1fr;gap:8px}
        .bt-seg-ozet{width:100%;justify-content:space-between}
    }
</style>
@endpush

@section('content')
<div class="bt-head">
    <h1><i data-lucide="landmark"></i> Borç Takip</h1>
</div>
<div class="bt-sub">SGK, Vergi ve Kredi borçlarınızı takip edin — borç (+) ve ödeme (−) girerek kalan borcu görün.</div>

@if(session('success'))<div class="bt-alert ok">{{ session('success') }}</div>@endif
@if(session('error'))<div class="bt-alert err">{{ session('error') }}</div>@endif
@if($errors->any())<div class="bt-alert err">{{ $errors->first() }}</div>@endif

@if($kurulumGerekli)
<div class="bt-uyari">
    <strong>Kurulum gerekli:</strong> <code>borc_takip</code> tablosu bulunamadı.
    Lütfen <code>borc-takip-kurulum.sql</code> dosyasını phpMyAdmin'de çalıştırın, sonra sayfayı yenileyin.
</div>
@else

{{-- GENEL ÖZET --}}
@php $genelArtida = $genelToplam['net'] <= 0; @endphp
<div class="bt-genel">
    <div>
        <div class="bt-genel-l">Toplam Kalan Borç (Tüm Kategoriler)</div>
        <div class="bt-genel-v {{ $genelArtida ? 'art' : 'eksi' }}">₺{{ number_format(abs($genelToplam['net']), 2, ',', '.') }}</div>
        <div class="bt-genel-aciklama">
            Toplam Borç: <strong style="color: var(--danger)">₺{{ number_format($genelToplam['borc'], 2, ',', '.') }}</strong>
            &nbsp;•&nbsp; Toplam Ödenen: <strong style="color: var(--success)">₺{{ number_format($genelToplam['odeme'], 2, ',', '.') }}</strong>
        </div>
    </div>
    <div class="bt-genel-ikon" style="background: {{ $genelArtida ? '#ecfdf5' : '#fef2f2' }}">
        <i data-lucide="{{ $genelArtida ? 'check-circle' : 'alert-circle' }}" style="width:36px;height:36px;color: {{ $genelArtida ? '#10b981' : '#ef4444' }}"></i>
    </div>
</div>

{{-- 3 SEGMENT --}}
<div class="bt-segmentler">
@foreach($kategoriler as $key => $bilgi)
    @php
        $o = $ozet[$key] ?? ['borc'=>0,'odeme'=>0,'net'=>0,'adet'=>0];
        $netArtida = $o['net'] <= 0;
    @endphp
    <div class="bt-segment">
        <div class="bt-seg-head">
            <div class="bt-seg-baslik">
                <span class="bt-seg-ikon" style="background: {{ $bilgi['renk'] }}1a;">{{ $bilgi['ikon'] }}</span>
                {{ $bilgi['ad'] }}
            </div>
            <div class="bt-seg-ozet">
                <div class="bt-ozet-blok"><span class="lbl">Borç</span><span class="val borc">₺{{ number_format($o['borc'], 2, ',', '.') }}</span></div>
                <div class="bt-ozet-blok"><span class="lbl">Ödenen</span><span class="val odeme">₺{{ number_format($o['odeme'], 2, ',', '.') }}</span></div>
                <div class="bt-ozet-blok"><span class="lbl">Kalan Borç</span><span class="val net {{ $netArtida ? 'art' : 'eksi' }}">₺{{ number_format(abs($o['net']), 2, ',', '.') }}</span></div>
            </div>
        </div>
        <div class="bt-seg-govde">

            {{-- EKLEME FORMU --}}
            <form action="{{ route('admin.borc-takip.store') }}" method="POST" class="bt-form">
                @csrf
                <input type="hidden" name="kategori" value="{{ $key }}">
                <div>
                    <label>Tür</label>
                    <select name="tip" required>
                        <option value="borc">Borç (+)</option>
                        <option value="odeme">Ödeme (−)</option>
                    </select>
                </div>
                <div>
                    <label>Başlık</label>
                    <input type="text" name="baslik" maxlength="200" required placeholder="Örn: Haziran SGK tahakkuku">
                </div>
                <div>
                    <label>Tutar (₺)</label>
                    <input type="number" name="tutar" step="0.01" min="0" required placeholder="0,00">
                </div>
                <div>
                    <label>Tarih</label>
                    <input type="date" name="tarih" value="{{ date('Y-m-d') }}" required>
                </div>
                <button type="submit" class="bt-form-btn"><i data-lucide="plus" style="width:14px;height:14px;display:inline;vertical-align:middle"></i> Ekle</button>
            </form>

            {{-- LİSTE --}}
            @if(($veriler[$key] ?? collect())->count() > 0)
            <table class="bt-tablo">
                <thead>
                    <tr>
                        <th style="width:90px">Tür</th>
                        <th>Başlık</th>
                        <th style="width:110px">Tarih</th>
                        <th class="sag" style="width:130px">Tutar</th>
                        <th style="width:80px"></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($veriler[$key] as $k)
                    <tr>
                        <td><span class="bt-pill {{ $k->tip }}">{{ $k->tip === 'borc' ? 'Borç' : 'Ödeme' }}</span></td>
                        <td>
                            <strong>{{ $k->baslik }}</strong>
                            @if(!empty($k->aciklama))<br><span style="color: var(--text-muted);font-size:12px">{{ $k->aciklama }}</span>@endif
                        </td>
                        <td>{{ \Carbon\Carbon::parse($k->tarih)->format('d.m.Y') }}</td>
                        <td class="sag bt-tutar {{ $k->tip }}">{{ $k->tip === 'borc' ? '+' : '−' }}₺{{ number_format($k->tutar, 2, ',', '.') }}</td>
                        <td>
                            <button type="button" class="bt-islem-btn"
                                onclick="btDuzenle({{ $k->id }}, '{{ $k->tip }}', @js($k->baslik), '{{ $k->tutar }}', '{{ \Carbon\Carbon::parse($k->tarih)->format('Y-m-d') }}', @js($k->aciklama ?? ''))"
                                title="Düzenle"><i data-lucide="pencil" style="width:15px;height:15px"></i></button>
                            <button type="button" class="bt-islem-btn sil"
                                onclick="if(confirm('Bu kaydı silmek istediğinize emin misiniz?')) document.getElementById('btSil{{ $k->id }}').submit();"
                                title="Sil"><i data-lucide="trash-2" style="width:15px;height:15px"></i></button>
                            <form id="btSil{{ $k->id }}" action="{{ route('admin.borc-takip.destroy', $k->id) }}" method="POST" style="display:none">
                                @csrf @method('DELETE')
                            </form>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
            @else
            <div class="bt-bos">Bu kategoride henüz kayıt yok. Yukarıdan borç veya ödeme ekleyin.</div>
            @endif

        </div>
    </div>
@endforeach
</div>

{{-- DÜZENLEME MODAL --}}
<div class="bt-modal-arka" id="btModal">
    <div class="bt-modal">
        <h3>Kaydı Düzenle</h3>
        <form id="btDuzenleForm" method="POST">
            @csrf @method('PUT')
            <label>Tür</label>
            <select name="tip" id="btmTip" required>
                <option value="borc">Borç (+)</option>
                <option value="odeme">Ödeme (−)</option>
            </select>
            <label>Başlık</label>
            <input type="text" name="baslik" id="btmBaslik" maxlength="200" required>
            <label>Tutar (₺)</label>
            <input type="number" name="tutar" id="btmTutar" step="0.01" min="0" required>
            <label>Tarih</label>
            <input type="date" name="tarih" id="btmTarih" required>
            <label>Açıklama (isteğe bağlı)</label>
            <textarea name="aciklama" id="btmAciklama" rows="2"></textarea>
            <div class="bt-modal-footer">
                <button type="button" class="bt-btn-iptal" onclick="btModalKapat()">İptal</button>
                <button type="submit" class="bt-btn-kaydet">Kaydet</button>
            </div>
        </form>
    </div>
</div>

@endif
@endsection

@push('scripts')
<script>
function btDuzenle(id, tip, baslik, tutar, tarih, aciklama) {
    var form = document.getElementById('btDuzenleForm');
    form.action = '{{ url('admin/borc-takip') }}/' + id;
    document.getElementById('btmTip').value = tip;
    document.getElementById('btmBaslik').value = baslik;
    document.getElementById('btmTutar').value = tutar;
    document.getElementById('btmTarih').value = tarih;
    document.getElementById('btmAciklama').value = aciklama || '';
    document.getElementById('btModal').classList.add('acik');
}
function btModalKapat() {
    document.getElementById('btModal').classList.remove('acik');
}
document.getElementById('btModal') && document.getElementById('btModal').addEventListener('click', function(e) {
    if (e.target === this) btModalKapat();
});
</script>
@endpush