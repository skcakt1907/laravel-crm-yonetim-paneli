@extends('admin._layout')

@section('title', 'Cari: ' . $musteri->adi)

@push('head')
<style>
    .mb-head{display:flex;align-items:center;justify-content:space-between;gap:16px;flex-wrap:wrap;margin-bottom:8px}
    .mb-head h1{font-size:23px;font-weight:800;margin:0;display:flex;align-items:center;gap:10px}
    .mb-geri{display:inline-flex;align-items:center;gap:6px;color: var(--text-secondary);text-decoration:none;font-size:13.5px;font-weight:600;margin-bottom:14px}
    .mb-geri:hover{color: var(--text)}
    .mb-musteri-meta{color: var(--text-muted);font-size:13.5px;margin-bottom:20px}

    .mb-genel{background: var(--surface);border: 1px solid var(--border);border-radius: 18px;padding:24px;margin-bottom:22px;display:flex;align-items:center;justify-content:space-between;gap:20px;flex-wrap:wrap}
    .mb-ozet-blok{text-align:center}
    .mb-ozet-blok .lbl{font-size:11px;color: var(--text-muted);text-transform:uppercase;letter-spacing:.4px;display:block}
    .mb-ozet-blok .val{font-size:26px;font-weight:800;margin-top:4px}
    .mb-ozet-blok .val.borc{color: var(--danger)}
    .mb-ozet-blok .val.odeme{color: var(--success)}
    .mb-ozet-blok .val.net.eksi{color: var(--danger)}
    .mb-ozet-blok .val.net.art{color: var(--success)}

    .mb-kart{background: var(--surface);border: 1px solid var(--border);border-radius: 18px;overflow:hidden}
    .mb-kart-govde{padding:18px 22px}

    .mb-form{display:grid;grid-template-columns:130px 1fr 150px 150px auto;gap:10px;align-items:end;margin-bottom:18px;padding:16px;background: var(--bg-subtle);border-radius: 12px;border: 1px solid var(--border)}
    .mb-form label{font-size:11px;font-weight:700;color: var(--text-secondary);display:block;margin-bottom:5px}
    .mb-form select,.mb-form input{width:100%;padding:9px 11px;border: 1px solid var(--border);border-radius: 8px;font-size:13.5px;font-family:inherit;outline: none;background: var(--surface)}
    .mb-form select:focus,.mb-form input:focus{border-color: var(--brand)}
    .mb-form-btn{padding:9px 18px;background: var(--brand);color: var(--text);border: none;border-radius: 8px;font-weight:700;font-size:13.5px;cursor:pointer;white-space:nowrap;height:38px}
    .mb-form-btn:hover{background: var(--brand-hover)}

    .mb-tablo{width:100%;border-collapse: collapse;font-size:13.5px}
    .mb-tablo th{text-align:left;padding:9px 10px;color: var(--text-muted);font-size:11px;text-transform:uppercase;letter-spacing:.4px;border-bottom: 2px solid var(--border);font-weight:700}
    .mb-tablo th.sag,.mb-tablo td.sag{text-align:right}
    .mb-tablo td{padding:10px;border-bottom: 1px solid #f5f5f5;vertical-align:middle}
    .mb-tablo tr:hover td{background: var(--surface-hover)}
    .mb-pill{display:inline-block;padding:3px 10px;border-radius: 999px;font-size:11px;font-weight:700}
    .mb-pill.borc{background: var(--danger-soft);color: var(--danger)}
    .mb-pill.odeme{background: var(--success-soft);color: #065f46}
    .mb-tutar.borc{color: var(--danger);font-weight:700}
    .mb-tutar.odeme{color: var(--success);font-weight:700}
    .mb-islem-btn{background: none;border: none;cursor:pointer;padding:5px;border-radius: 6px;color: var(--text-muted)}
    .mb-islem-btn:hover{background: var(--bg-subtle);color: var(--text)}
    .mb-islem-btn.sil:hover{background: var(--danger-soft);color: var(--danger)}
    .mb-bos{text-align:center;padding:26px;color: var(--text-muted);font-size:13.5px}

    .mb-modal-arka{display:none;position:fixed;inset:0;background: rgba(0,0,0,.5);z-index:9999;align-items:center;justify-content:center;padding:20px}
    .mb-modal-arka.acik{display:flex}
    .mb-modal{background: var(--surface);border-radius: 16px;padding:24px;max-width:460px;width:100%;box-shadow:0 24px 48px rgba(0,0,0,.2)}
    .mb-modal h3{font-size:18px;font-weight:800;margin:0 0 18px;color: var(--text)}
    .mb-modal label{font-size:12px;font-weight:700;color: var(--text-secondary);display:block;margin-bottom:5px;margin-top:12px}.mb-modal select,.mb-modal input,.mb-modal textarea{width:100%;padding:10px 12px;border: 1px solid var(--border);border-radius: 8px;font-size:14px;font-family:inherit;outline: none;background: var(--surface);color: var(--text);}
    .mb-modal-footer{display:flex;justify-content:flex-end;gap:10px;margin-top:20px}
    .mb-btn-iptal{padding:9px 18px;background: var(--bg-subtle);color: var(--text-secondary);border: none;border-radius: 8px;font-weight:700;cursor:pointer}
    .mb-btn-kaydet{padding:9px 18px;background: var(--brand);color: var(--text);border: none;border-radius: 8px;font-weight:700;cursor:pointer}

    .mb-alert{padding:13px 18px;border-radius: 12px;margin-bottom:18px;font-size:14px;font-weight:600}
    .mb-alert.ok{background: var(--success-soft);color: #065f46}
    .mb-alert.err{background: var(--danger-soft);color: var(--danger)}
    @media(max-width:760px){ .mb-form{grid-template-columns:1fr 1fr;gap:8px} }
</style>
@endpush

@section('content')
<a href="{{ route('admin.musteri-borc.index') }}" class="mb-geri"><i data-lucide="arrow-left" style="width:16px;height:16px"></i> Müşteri listesine dön</a>

<div class="mb-head">
    <h1><i data-lucide="user"></i> {{ $musteri->adi }}</h1>
</div>
<div class="mb-musteri-meta">
    @if($musteri->unvan){{ $musteri->unvan }} &nbsp;•&nbsp; @endif
    @if($musteri->email){{ $musteri->email }} &nbsp;•&nbsp; @endif
    {{ $musteri->telefon }}
</div>

@if(session('success'))<div class="mb-alert ok">{{ session('success') }}</div>@endif
@if(session('error'))<div class="mb-alert err">{{ session('error') }}</div>@endif
@if($errors->any())<div class="mb-alert err">{{ $errors->first() }}</div>@endif

{{-- ÖZET --}}
@php $netArtida = $ozet['net'] <= 0; @endphp
<div class="mb-genel">
    <div class="mb-ozet-blok"><span class="lbl">Toplam Borç</span><span class="val borc">₺{{ number_format($ozet['borc'], 2, ',', '.') }}</span></div>
    <div class="mb-ozet-blok"><span class="lbl">Tahsilat</span><span class="val odeme">₺{{ number_format($ozet['odeme'], 2, ',', '.') }}</span></div>
    <div class="mb-ozet-blok"><span class="lbl">Kalan Borç</span><span class="val net {{ $netArtida ? 'art' : 'eksi' }}">₺{{ number_format(abs($ozet['net']), 2, ',', '.') }}</span></div>
</div>

<div class="mb-kart">
    <div class="mb-kart-govde">

        {{-- EKLEME FORMU --}}
        <form action="{{ route('admin.musteri-borc.store') }}" method="POST" class="mb-form">
            @csrf
            <input type="hidden" name="musteri_id" value="{{ $musteri->id }}">
            <div>
                <label>Tür</label>
                <select name="tip" required>
                    <option value="borc">Borç (+)</option>
                    <option value="odeme">Ödeme (−)</option>
                </select>
            </div>
            <div>
                <label>Başlık</label>
                <input type="text" name="baslik" maxlength="200" required placeholder="Örn: Web tasarım hizmeti">
            </div>
            <div>
                <label>Tutar (₺)</label>
                <input type="number" name="tutar" step="0.01" min="0" required placeholder="0,00">
            </div>
            <div>
                <label>Tarih</label>
                <input type="date" name="tarih" value="{{ date('Y-m-d') }}" required>
            </div>
            <button type="submit" class="mb-form-btn"><i data-lucide="plus" style="width:14px;height:14px;display:inline;vertical-align:middle"></i> Ekle</button>
        </form>

        {{-- LİSTE --}}
        @if($kayitlar->count() > 0)
        <table class="mb-tablo">
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
                @foreach($kayitlar as $k)
                <tr>
                    <td><span class="mb-pill {{ $k->tip }}">{{ $k->tip === 'borc' ? 'Borç' : 'Ödeme' }}</span></td>
                    <td>
                        <strong>{{ $k->baslik }}</strong>
                        @if(!empty($k->aciklama))<br><span style="color: var(--text-muted);font-size:12px">{{ $k->aciklama }}</span>@endif
                    </td>
                    <td>{{ \Carbon\Carbon::parse($k->tarih)->format('d.m.Y') }}</td>
                    <td class="sag mb-tutar {{ $k->tip }}">{{ $k->tip === 'borc' ? '+' : '−' }}₺{{ number_format($k->tutar, 2, ',', '.') }}</td>
                    <td>
                        <button type="button" class="mb-islem-btn"
                            onclick="mbDuzenle({{ $k->id }}, '{{ $k->tip }}', @js($k->baslik), '{{ $k->tutar }}', '{{ \Carbon\Carbon::parse($k->tarih)->format('Y-m-d') }}', @js($k->aciklama ?? ''))"
                            title="Düzenle"><i data-lucide="pencil" style="width:15px;height:15px"></i></button>
                        <button type="button" class="mb-islem-btn sil"
                            onclick="if(confirm('Bu kaydı silmek istediğinize emin misiniz?')) document.getElementById('mbSil{{ $k->id }}').submit();"
                            title="Sil"><i data-lucide="trash-2" style="width:15px;height:15px"></i></button>
                        <form id="mbSil{{ $k->id }}" action="{{ route('admin.musteri-borc.destroy', $k->id) }}" method="POST" style="display:none">
                            @csrf @method('DELETE')
                        </form>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
        @else
        <div class="mb-bos">Bu müşteride henüz kayıt yok. Yukarıdan borç veya ödeme ekleyin.</div>
        @endif

    </div>
</div>

{{-- DÜZENLEME MODAL --}}
<div class="mb-modal-arka" id="mbModal">
    <div class="mb-modal">
        <h3>Kaydı Düzenle</h3>
        <form id="mbDuzenleForm" method="POST">
            @csrf @method('PUT')
            <label>Tür</label>
            <select name="tip" id="mbmTip" required>
                <option value="borc">Borç (+)</option>
                <option value="odeme">Ödeme (−)</option>
            </select>
            <label>Başlık</label>
            <input type="text" name="baslik" id="mbmBaslik" maxlength="200" required>
            <label>Tutar (₺)</label>
            <input type="number" name="tutar" id="mbmTutar" step="0.01" min="0" required>
            <label>Tarih</label>
            <input type="date" name="tarih" id="mbmTarih" required>
            <label>Açıklama (isteğe bağlı)</label>
            <textarea name="aciklama" id="mbmAciklama" rows="2"></textarea>
            <div class="mb-modal-footer">
                <button type="button" class="mb-btn-iptal" onclick="mbModalKapat()">İptal</button>
                <button type="submit" class="mb-btn-kaydet">Kaydet</button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
function mbDuzenle(id, tip, baslik, tutar, tarih, aciklama) {
    var form = document.getElementById('mbDuzenleForm');
    form.action = '{{ url('admin/musteri-borc') }}/' + id;
    document.getElementById('mbmTip').value = tip;
    document.getElementById('mbmBaslik').value = baslik;
    document.getElementById('mbmTutar').value = tutar;
    document.getElementById('mbmTarih').value = tarih;
    document.getElementById('mbmAciklama').value = aciklama || '';
    document.getElementById('mbModal').classList.add('acik');
}
function mbModalKapat() {
    document.getElementById('mbModal').classList.remove('acik');
}
document.getElementById('mbModal') && document.getElementById('mbModal').addEventListener('click', function(e) {
    if (e.target === this) mbModalKapat();
});
</script>
@endpush
