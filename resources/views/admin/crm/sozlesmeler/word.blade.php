<html xmlns:o='urn:schemas-microsoft-com:office:office'
      xmlns:w='urn:schemas-microsoft-com:office:word'
      xmlns='http://www.w3.org/TR/REC-html40'>
<head>
<meta charset="utf-8">
<title>{{ $sozlesme->sozlesme_no }}</title>
<!--[if gte mso 9]>
<xml><w:WordDocument><w:View>Print</w:View><w:Zoom>100</w:Zoom><w:DoNotOptimizeForBrowser/></w:WordDocument></xml>
<![endif]-->
<style>
    @page { size: 21cm 29.7cm; margin: 2cm; }
    body { font-family: 'Times New Roman', serif; font-size: 12pt; color: #000; line-height: 1.6; }
    .head { border-bottom: 2px solid #000; padding-bottom: 8pt; margin-bottom: 16pt; }
    .firma { font-size: 18pt; font-weight: bold; }
    .meta { font-size: 9pt; color: #333; }
    .baslik { text-align: center; font-size: 15pt; font-weight: bold; margin: 14pt 0 4pt; }
    .kategori { text-align: center; font-size: 10pt; color: #555; margin-bottom: 18pt; }
    table.taraflar { width: 100%; border-collapse: collapse; margin-bottom: 18pt; }
    table.taraflar td { width: 50%; border: 1px solid #999; padding: 8pt; vertical-align: top; font-size: 10pt; }
    .lbl { font-size: 8pt; color: #777; text-transform: uppercase; }
    .val { font-size: 11pt; font-weight: bold; }
    .icerik { font-size: 12pt; }
    .icerik table { border-collapse: collapse; }
    .icerik td, .icerik th { border: 1px solid #999; padding: 5pt; }
    table.imza { width: 100%; margin-top: 48pt; }
    table.imza td { width: 50%; text-align: center; font-size: 11pt; padding-top: 40pt; }
    .imzaLine { border-top: 1px solid #000; padding-top: 4pt; }
</style>
</head>
<body>
@php
    $firma = $ayarlar->site_basligi ?? $ayarlar->site_adi ?? $ayarlar->firma_adi ?? 'DN Kreatif';
    $musteriAd = $sozlesme->musteri->adi ?? $sozlesme->taraf_adi ?? '—';
    $tarih = $sozlesme->tarih ? \Carbon\Carbon::parse($sozlesme->tarih)->format('d.m.Y') : \Carbon\Carbon::parse($sozlesme->created_at)->format('d.m.Y');
@endphp

<div class="head">
    <table style="width:100%"><tr>
        <td class="firma">{{ $firma }}</td>
        <td class="meta" style="text-align:right">
            <strong>Sözleşme No:</strong> {{ $sozlesme->sozlesme_no }}<br>
            <strong>Tarih:</strong> {{ $tarih }}
            @if($sozlesme->tutar)<br><strong>Tutar:</strong> ₺{{ number_format((float)$sozlesme->tutar, 2, ',', '.') }}@endif
        </td>
    </tr></table>
</div>

<div class="baslik">{{ $sozlesme->baslik }}</div>
@if($sozlesme->kategori)<div class="kategori">{{ $sozlesme->kategori->ad }}</div>@endif

<table class="taraflar"><tr>
    <td>
        <div class="lbl">Hizmet Veren</div>
        <div class="val">{{ $firma }}</div>
    </td>
    <td>
        <div class="lbl">Müşteri / Karşı Taraf</div>
        <div class="val">{{ $musteriAd }}</div>
    </td>
</tr></table>

<div class="icerik">
    {!! $sozlesme->icerik ?: '<p>(Sözleşme metni girilmemiş)</p>' !!}
</div>

<table class="imza"><tr>
    <td><div class="imzaLine">{{ $firma }}</div></td>
    <td><div class="imzaLine">{{ $musteriAd }}</div></td>
</tr></table>

</body>
</html>
