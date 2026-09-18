<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Fatura {{ $fatura->fatura_no }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 13px;
            color: #1e293b;
            background: #fff;
            padding: 30px;
        }

        /* Header */
        .header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 3px solid #d4d25b;
        }
        .firma-adi {
            font-size: 22px;
            font-weight: 700;
            color: #0f172a;
            margin-bottom: 6px;
        }
        .firma-bilgi {
            font-size: 12px;
            color: #64748b;
            line-height: 1.6;
        }
        .fatura-baslik {
            text-align: right;
        }
        .fatura-baslik h1 {
            font-size: 28px;
            font-weight: 700;
            color: #0f172a;
            letter-spacing: -0.5px;
        }
        .fatura-no {
            font-size: 14px;
            color: #64748b;
            margin-top: 5px;
        }
        .fatura-tarih {
            font-size: 13px;
            color: #475569;
            margin-top: 4px;
        }

        /* Status badge */
        .durum-badge {
            display: inline-block;
            margin-top: 10px;
            padding: 5px 14px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 700;
        }
        .durum-odendi { background: #dcfce7; color: #16a34a; }
        .durum-bekliyor { background: #fee2e2; color: #dc2626; }

        /* Müşteri bilgisi */
        .musteri-bilgi {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            padding: 18px 20px;
            margin-bottom: 25px;
        }
        .musteri-baslik {
            font-size: 11px;
            font-weight: 700;
            color: #94a3b8;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 8px;
        }
        .musteri-ad {
            font-size: 16px;
            font-weight: 700;
            color: #0f172a;
            margin-bottom: 4px;
        }
        .musteri-detay {
            font-size: 12px;
            color: #64748b;
            line-height: 1.6;
        }

        /* Fatura detay grid */
        .detay-grid {
            width: 100%;
            margin-bottom: 25px;
            border-collapse: collapse;
        }
        .detay-grid td {
            width: 33%;
            padding: 12px 15px;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            vertical-align: top;
        }
        .detay-label {
            font-size: 11px;
            font-weight: 700;
            color: #94a3b8;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 5px;
        }
        .detay-deger {
            font-size: 14px;
            font-weight: 600;
            color: #1e293b;
        }

        /* Ürün tablosu */
        .urun-tablo {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 25px;
        }
        .urun-tablo thead tr {
            background: #0f172a;
        }
        .urun-tablo thead th {
            padding: 12px 15px;
            color: #fff;
            font-size: 12px;
            font-weight: 700;
            text-align: left;
        }
        .urun-tablo thead th:last-child,
        .urun-tablo thead th:nth-child(2),
        .urun-tablo thead th:nth-child(3) {
            text-align: right;
        }
        .urun-tablo tbody tr {
            border-bottom: 1px solid #e2e8f0;
        }
        .urun-tablo tbody tr:nth-child(even) {
            background: #f8fafc;
        }
        .urun-tablo tbody td {
            padding: 12px 15px;
            font-size: 13px;
            color: #334155;
        }
        .urun-tablo tbody td:last-child,
        .urun-tablo tbody td:nth-child(2),
        .urun-tablo tbody td:nth-child(3) {
            text-align: right;
        }

        /* Toplam */
        .toplam-alan {
            width: 100%;
            margin-bottom: 25px;
        }
        .toplam-satir {
            display: flex;
            justify-content: flex-end;
        }
        .toplam-tablo {
            width: 280px;
            border-collapse: collapse;
        }
        .toplam-tablo td {
            padding: 8px 15px;
            font-size: 13px;
        }
        .toplam-tablo tr:last-child {
            background: #d4d25b;
        }
        .toplam-tablo tr:last-child td {
            font-size: 16px;
            font-weight: 800;
            color: #000;
            padding: 12px 15px;
        }
        .toplam-label { color: #64748b; }
        .toplam-deger { text-align: right; font-weight: 600; color: #1e293b; }

        /* Ödeme bilgisi */
        .odeme-bilgi {
            background: #f0fdf4;
            border: 1px solid #bbf7d0;
            border-radius: 10px;
            padding: 16px 20px;
            margin-bottom: 20px;
        }
        .odeme-bilgi-baslik {
            font-size: 13px;
            font-weight: 700;
            color: #16a34a;
            margin-bottom: 5px;
        }
        .odeme-bilgi-detay {
            font-size: 12px;
            color: #166534;
        }

        /* Açıklama */
        .aciklama-alan {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            padding: 16px 20px;
            margin-bottom: 20px;
        }
        .aciklama-baslik {
            font-size: 11px;
            font-weight: 700;
            color: #94a3b8;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 8px;
        }
        .aciklama-icerik {
            font-size: 13px;
            color: #475569;
            line-height: 1.6;
        }

        /* Footer */
        .footer {
            margin-top: 30px;
            padding-top: 20px;
            border-top: 2px solid #e2e8f0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .footer-sol {
            font-size: 11px;
            color: #94a3b8;
            line-height: 1.6;
        }
        .footer-sag {
            font-size: 11px;
            color: #94a3b8;
            text-align: right;
        }
        .gold-line {
            width: 40px;
            height: 3px;
            background: #d4d25b;
            margin: 8px 0;
        }

        /* dompdf float fix */
        .clearfix::after { content: ""; display: table; clear: both; }
        .float-left { float: left; }
        .float-right { float: right; }
    </style>
</head>
<body>

    <!-- HEADER -->
    <table width="100%" style="margin-bottom:30px; padding-bottom:20px; border-bottom: 3px solid #d4d25b;">
        <tr>
            <td style="vertical-align:top; width:60%;">
                <div class="firma-adi">{{ $ayarlar->fatura_firma_adi ?? $ayarlar->firma_adi ?? config('app.name') }}</div>
                <div class="firma-bilgi">
                    @if($ayarlar->firma_adres ?? null){{ $ayarlar->firma_adres }}<br>@endif
                    @if($ayarlar->firma_telefon ?? null)Tel: {{ $ayarlar->firma_telefon }}<br>@endif
                    @if($ayarlar->firma_email ?? null){{ $ayarlar->firma_email }}@endif
                </div>
            </td>
            <td style="vertical-align:top; text-align:right; width:40%;">
                <div style="font-size:28px; font-weight:700; color:#0f172a;">FATURA</div>
                <div class="fatura-no"># {{ $fatura->fatura_no }}</div>
                <div class="fatura-tarih">{{ $fatura->tarih ? $fatura->tarih->format('d.m.Y') : '-' }}</div>
                <div>
                    @if($fatura->durum)
                        <span class="durum-badge durum-odendi">✓ Ödendi</span>
                    @else
                        <span class="durum-badge durum-bekliyor">⏳ Ödeme Bekleniyor</span>
                    @endif
                </div>
            </td>
        </tr>
    </table>

    <!-- MÜŞTERİ BİLGİSİ -->
    @php $uye = $fatura->uye; @endphp
    @if($uye)
    <div class="musteri-bilgi" style="margin-bottom:25px;">
        <div class="musteri-baslik">Fatura Kesilen</div>
        <div class="musteri-ad">{{ $uye->ad }} {{ $uye->soyad }}</div>
        <div class="musteri-detay">
            {{ $uye->email }}
            @if($uye->telefon) &nbsp;|&nbsp; {{ $uye->telefon }} @endif
            @if($uye->fatura_unvan ?? null)<br>{{ $uye->fatura_unvan }}@endif
            @if($uye->fatura_tc ?? null) &nbsp;|&nbsp; TC/VN: {{ $uye->fatura_tc }} @endif
            @if($uye->fatura_adres ?? null)<br>{{ $uye->fatura_adres }}@if($uye->fatura_sehir ?? null), {{ $uye->fatura_sehir }}@endif@endif
        </div>
    </div>
    @endif

    <!-- FATURA DETAY -->
    <table class="detay-grid" style="margin-bottom:25px;">
        <tr>
            <td style="width:33%; padding:12px 15px; background:#f8fafc; border:1px solid #e2e8f0;">
                <div class="detay-label">Fatura No</div>
                <div class="detay-deger">{{ $fatura->fatura_no }}</div>
            </td>
            <td style="width:33%; padding:12px 15px; background:#f8fafc; border:1px solid #e2e8f0;">
                <div class="detay-label">Fatura Tarihi</div>
                <div class="detay-deger">{{ $fatura->tarih ? $fatura->tarih->format('d.m.Y') : '-' }}</div>
            </td>
            <td style="width:33%; padding:12px 15px; background:#f8fafc; border:1px solid #e2e8f0;">
                <div class="detay-label">Ödeme Yöntemi</div>
                <div class="detay-deger">{{ $fatura->odeme_yontemi ?? 'Çevrimiçi Ödeme' }}</div>
            </td>
        </tr>
    </table>

    <!-- ÜRÜN / HİZMET TABLOSU -->
    <table class="urun-tablo" style="margin-bottom:20px;">
        <thead>
            <tr>
                <th style="width:60%; padding:12px 15px; color:#fff; font-size:12px; font-weight:700; text-align:left; background:#0f172a;">Ürün / Hizmet</th>
                <th style="width:10%; padding:12px 15px; color:#fff; font-size:12px; font-weight:700; text-align:center; background:#0f172a;">Adet</th>
                <th style="width:15%; padding:12px 15px; color:#fff; font-size:12px; font-weight:700; text-align:right; background:#0f172a;">Birim Fiyat</th>
                <th style="width:15%; padding:12px 15px; color:#fff; font-size:12px; font-weight:700; text-align:right; background:#0f172a;">Toplam</th>
            </tr>
        </thead>
        <tbody>
            @if($fatura->kalemler && count($fatura->kalemler) > 0)
                @foreach($fatura->kalemler as $kalem)
                <tr style="border-bottom:1px solid #e2e8f0;">
                    <td style="padding:12px 15px; font-size:13px; color:#334155;">{{ $kalem->ad ?? $kalem->adi ?? '-' }}</td>
                    <td style="padding:12px 15px; font-size:13px; color:#334155; text-align:center;">{{ $kalem->miktar ?? 1 }}</td>
                    <td style="padding:12px 15px; font-size:13px; color:#334155; text-align:right;">{{ number_format($kalem->birim_fiyat ?? $kalem->fiyat ?? 0, 2, ',', '.') }} ₺</td>
                    <td style="padding:12px 15px; font-size:13px; color:#334155; text-align:right; font-weight:600;">{{ number_format(($kalem->birim_fiyat ?? $kalem->fiyat ?? 0) * ($kalem->miktar ?? 1), 2, ',', '.') }} ₺</td>
                </tr>
                @endforeach
            @else
                <tr>
                    <td style="padding:12px 15px; font-size:13px; color:#334155;">{{ $fatura->aciklama ?? 'Hizmet Bedeli' }}</td>
                    <td style="padding:12px 15px; font-size:13px; color:#334155; text-align:center;">1</td>
                    <td style="padding:12px 15px; font-size:13px; color:#334155; text-align:right;">{{ number_format($fatura->tutar, 2, ',', '.') }} ₺</td>
                    <td style="padding:12px 15px; font-size:13px; color:#334155; text-align:right; font-weight:600;">{{ number_format($fatura->tutar, 2, ',', '.') }} ₺</td>
                </tr>
            @endif
        </tbody>
    </table>

    <!-- TOPLAM -->
    <table width="100%" style="margin-bottom:25px;">
        <tr>
            <td width="55%"></td>
            <td width="45%">
                <table width="100%" style="border-collapse:collapse;">
                    @if($fatura->kdv && $fatura->kdv > 0)
                    <tr>
                        <td style="padding:8px 15px; font-size:13px; color:#64748b;">Ara Toplam</td>
                        <td style="padding:8px 15px; font-size:13px; color:#1e293b; font-weight:600; text-align:right;">{{ number_format($fatura->tutar, 2, ',', '.') }} ₺</td>
                    </tr>
                    <tr>
                        <td style="padding:8px 15px; font-size:13px; color:#64748b;">KDV</td>
                        <td style="padding:8px 15px; font-size:13px; color:#1e293b; font-weight:600; text-align:right;">{{ number_format($fatura->kdv, 2, ',', '.') }} ₺</td>
                    </tr>
                    @endif
                    <tr style="background:#d4d25b;">
                        <td style="padding:12px 15px; font-size:16px; font-weight:800; color:#000;">GENEL TOPLAM</td>
                        <td style="padding:12px 15px; font-size:16px; font-weight:800; color:#000; text-align:right;">{{ number_format($fatura->toplam ?? $fatura->tutar, 2, ',', '.') }} ₺</td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    <!-- ÖDEME DURUMU -->
    @if($fatura->durum && $fatura->odeme_tarihi)
    <div class="odeme-bilgi" style="background:#f0fdf4; border:1px solid #bbf7d0; border-radius:10px; padding:16px 20px; margin-bottom:20px;">
        <div class="odeme-bilgi-baslik" style="font-size:13px; font-weight:700; color:#16a34a; margin-bottom:5px;">✓ Ödeme Alındı</div>
        <div class="odeme-bilgi-detay" style="font-size:12px; color:#166534;">
            Ödeme Tarihi: {{ $fatura->odeme_tarihi->format('d.m.Y H:i') }}
            @if($fatura->odeme_yontemi) &nbsp;|&nbsp; Yöntem: {{ $fatura->odeme_yontemi }} @endif
        </div>
    </div>
    @endif

    <!-- AÇIKLAMA -->
    @if($fatura->aciklama && ($fatura->kalemler && count($fatura->kalemler) > 0))
    <div style="background:#f8fafc; border:1px solid #e2e8f0; border-radius:10px; padding:16px 20px; margin-bottom:20px;">
        <div style="font-size:11px; font-weight:700; color:#94a3b8; text-transform:uppercase; letter-spacing:0.5px; margin-bottom:8px;">Açıklama</div>
        <div style="font-size:13px; color:#475569; line-height:1.6;">{{ $fatura->aciklama }}</div>
    </div>
    @endif

    <!-- FOOTER -->
    <table width="100%" style="margin-top:30px; padding-top:20px; border-top:2px solid #e2e8f0;">
        <tr>
            <td style="font-size:11px; color:#94a3b8; line-height:1.6; vertical-align:bottom;">
                {{ $ayarlar->fatura_firma_adi ?? $ayarlar->firma_adi ?? config('app.name') }}<br>
                @if($ayarlar->firma_adres ?? null){{ $ayarlar->firma_adres }}<br>@endif
                @if($ayarlar->firma_email ?? null){{ $ayarlar->firma_email }}@endif
            </td>
            <td style="text-align:right; font-size:11px; color:#94a3b8; vertical-align:bottom;">
                Belge Tarihi: {{ now()->format('d.m.Y H:i') }}<br>
                Bu belge elektronik olarak oluşturulmuştur.
            </td>
        </tr>
    </table>

</body>
</html>
