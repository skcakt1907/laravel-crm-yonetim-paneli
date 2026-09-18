<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $sozlesme->sozlesme_no }} · {{ $sozlesme->baslik }}</title>
    @php
        // Sözleşmede HİZMET VEREN = resmi unvan (fatura_firma_adi). Site adından bağımsızdır.
        $firma = $ayarlar->fatura_firma_adi ?: ($ayarlar->firma_adi ?? 'DN GRUP MEDYA VE TEKNOLOJİ ANONİM ŞİRKETİ');
        $musteriAd = $sozlesme->musteri->adi ?? $sozlesme->taraf_adi ?? '—';
        $tarih = $sozlesme->tarih ? \Carbon\Carbon::parse($sozlesme->tarih)->format('d.m.Y') : \Carbon\Carbon::parse($sozlesme->created_at)->format('d.m.Y');
    @endphp
    <style>
        * { box-sizing: border-box; }
        body { font-family: Arial, "Segoe UI", sans-serif; color:#1a1a1a; margin:0; background:#f1f3f5; }
        .sheet { background:#fff; max-width:800px; margin:24px auto; padding:48px 56px; box-shadow:0 2px 14px rgba(0,0,0,.08); }
        .topbar { max-width:800px; margin:16px auto 0; display:flex; justify-content:flex-end; gap:8px; padding:0 8px; }
        .btn { font-size:14px; padding:10px 18px; border-radius:8px; border:none; cursor:pointer; text-decoration:none; display:inline-flex; align-items:center; gap:6px; }
        .btn-print { background:#6366f1; color:#fff; }
        .btn-back  { background:#e9ecef; color:#333; }
        .head { display:flex; justify-content:space-between; align-items:flex-start; border-bottom:2px solid #222; padding-bottom:16px; margin-bottom:24px; }
        .head .firma { font-size:22px; font-weight:800; }
        .head .meta { text-align:right; font-size:12px; color:#555; }
        .baslik { text-align:center; font-size:20px; font-weight:700; margin:18px 0 8px; }
        .kategori { text-align:center; font-size:12px; color:#666; margin-bottom:24px; }
        .taraflar { display:flex; gap:24px; margin-bottom:24px; font-size:13px; }
        .taraf { flex:1; border:1px solid #e0e0e0; border-radius:8px; padding:12px 14px; }
        .taraf .lbl { font-size:11px; color:#888; text-transform:uppercase; letter-spacing:.5px; }
        .taraf .val { font-size:14px; font-weight:600; margin-top:3px; }
        .icerik { font-size:14px; line-height:1.7; }
        .icerik table { border-collapse:collapse; }
        .icerik td, .icerik th { border:1px solid #ccc; padding:6px; }
        .imza { display:flex; justify-content:space-between; gap:40px; margin-top:64px; }
        .imza .box { flex:1; text-align:center; }
        .imza .line { border-top:1px solid #333; margin-top:48px; padding-top:6px; font-size:13px; }
        @media print {
            body { background:#fff; }
            .topbar { display:none; }
            .sheet { box-shadow:none; margin:0; max-width:none; padding:0; }
            @page { margin: 1.6cm; }
        }
    </style>
</head>
<body>
    <div class="topbar">
        <a href="{{ route('admin.crm.sozlesmeler.index') }}" class="btn btn-back">← Geri</a>
        <a href="{{ route('admin.crm.sozlesmeler.word', $sozlesme->id) }}" class="btn btn-back" style="background:#2b579a;color:#fff">📝 Word indir</a>
        <button class="btn btn-print" onclick="window.print()">🖨️ Yazdır / PDF</button>
    </div>

    <div class="sheet">
        <div class="head">
            <div class="firma">{{ $firma }}</div>
            <div class="meta">
                <div><strong>Sözleşme No:</strong> {{ $sozlesme->sozlesme_no }}</div>
                <div><strong>Tarih:</strong> {{ $tarih }}</div>
                @if($sozlesme->tutar)<div><strong>Tutar:</strong> ₺{{ number_format((float)$sozlesme->tutar, 2, ',', '.') }}</div>@endif
            </div>
        </div>

        <div class="baslik">{{ $sozlesme->baslik }}</div>
        @if($sozlesme->kategori)<div class="kategori">{{ $sozlesme->kategori->ad }}</div>@endif

        <div class="taraflar">
            <div class="taraf">
                <div class="lbl">Hizmet Veren</div>
                <div class="val">{{ $firma }}</div>
            </div>
            <div class="taraf">
                <div class="lbl">Müşteri / Karşı Taraf</div>
                <div class="val">{{ $musteriAd }}</div>
            </div>
        </div>

        <div class="icerik">
            {!! $sozlesme->icerik ?: '<p style="color:#999">(Sözleşme metni girilmemiş)</p>' !!}
        </div>

        <div class="imza">
            <div class="box"><div class="line">{{ $firma }}</div></div>
            <div class="box"><div class="line">{{ $musteriAd }}</div></div>
        </div>
    </div>
</body>
</html>