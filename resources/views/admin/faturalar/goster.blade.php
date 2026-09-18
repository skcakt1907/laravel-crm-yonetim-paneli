<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fatura #{{ $fatura->fatura_no ?? $fatura->id }}</title>

    <link rel="icon" type="image/png" href="{{ asset('tema/uploads/favicon/favicon_dn.png') }}">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Poppins', Arial, sans-serif;
            background: #f4f4f5; color: var(--text);
            padding: 24px; font-size: 11.5px; line-height: 1.45;
            -webkit-font-smoothing: antialiased;
        }
        .toolbar { max-width: 900px; margin: 0 auto 16px; display: flex; gap: 8px; justify-content: flex-end; }
        .btn {
            display: inline-flex; align-items: center; gap: 6px; padding: 9px 16px;
            border-radius: 8px; font-weight: 600; font-size: 13px; text-decoration: none;
            border: none; cursor: pointer; font-family: inherit; transition: all .15s ease;
        }
        .btn-primary { background: var(--brand); color: var(--text); box-shadow: 0 2px 8px rgba(184,182,46,.3); }
        .btn-primary:hover { background: #a3a126; }
        .btn-secondary { background: var(--surface); color: var(--text); border: 1px solid var(--border-strong); }
        .btn-secondary:hover { background: var(--bg-subtle); }

        .fatura-container {
            max-width: 900px; margin: 0 auto; background: var(--surface);
            padding: 38px 42px; box-shadow: 0 4px 20px rgba(0,0,0,.06); border-radius: 6px;
        }

        /* ÜST: logo (sol) + e-Arşiv başlık (orta) + meta tablo (sağ) */
        .fa-ust { display: flex; justify-content: space-between; align-items: flex-start; gap: 20px; margin-bottom: 6px; }
        .fa-logo-wrap { flex: 0 0 auto; }
        .fa-logo { max-height: 70px; max-width: 200px; display: block; }
        .fa-earsiv { flex: 1; text-align: center; padding-top: 6px; }
        .fa-earsiv .baslik { font-size: 15px; font-weight: 700; color: #374151; }
        .fa-earsiv .alt { font-size: 11px; color: var(--text-secondary); }
        .fa-meta { flex: 0 0 auto; }
        .fa-meta table { border-collapse: collapse; font-size: 10.5px; }
        .fa-meta td { border: 1px solid #cbd5e1; padding: 4px 8px; }
        .fa-meta td:first-child { background: var(--bg-subtle); font-weight: 700; color: var(--text-secondary); white-space: nowrap; }
        .fa-meta td:last-child { color: var(--text); }

        /* SATICI / ALICI */
        .fa-taraf { margin-top: 18px; font-size: 11px; line-height: 1.6; }
        .fa-taraf .ad { font-size: 13px; font-weight: 700; color: var(--text); margin-bottom: 3px; }
        .fa-taraf .sayin { font-weight: 700; color: #374151; margin: 14px 0 4px; font-size: 12px; }
        .fa-ettn { margin-top: 12px; font-size: 11px; }
        .fa-ettn b { color: #374151; }

        .durum-pill { display: inline-block; margin-top: 8px; padding: 4px 12px; border-radius: 999px; font-size: 11px; font-weight: 700; }
        .durum-pill.odendi { background: var(--success-soft); color: #065f46; }
        .durum-pill.bekliyor { background: var(--warning-soft); color: var(--warning); }
        .durum-pill.iptal { background: var(--danger-soft); color: var(--danger); }

        /* KALEM TABLOSU */
        .fa-tablo { width: 100%; border-collapse: collapse; margin-top: 22px; }
        .fa-tablo th {
            background: var(--bg-subtle); color: var(--text-secondary); padding: 7px 6px; text-align: center;
            font-weight: 700; font-size: 9.5px; border: 1px solid #cbd5e1; line-height: 1.3;
        }
        .fa-tablo td { padding: 8px 6px; border: 1px solid #cbd5e1; font-size: 10.5px; vertical-align: top; }
        .fa-tablo td.sag { text-align: right; }
        .fa-tablo td.orta { text-align: center; }
        .fa-tablo td .ad { font-weight: 600; color: var(--text); }

        /* ÖZET TABLOSU (sağda, kutucuklu) */
        .fa-ozet-wrap { display: flex; justify-content: flex-end; margin-top: 18px; }
        .fa-ozet { border-collapse: collapse; min-width: 340px; }
        .fa-ozet td { border: 1px solid #cbd5e1; padding: 6px 12px; font-size: 11px; }
        .fa-ozet td:first-child { background: var(--bg-subtle); color: var(--text-secondary); text-align: right; font-weight: 600; }
        .fa-ozet td:last-child { text-align: right; font-weight: 700; color: var(--text); white-space: nowrap; }
        .fa-ozet tr.vurgu td { background: #fffbeb; }
        .fa-ozet tr.vurgu td:last-child { color: var(--warning); }

        /* BANKA */
        .fa-banka { margin-top: 28px; font-size: 11px; line-height: 1.7; white-space: pre-line; color: var(--text); font-weight: 600; }

        .fa-footer { margin-top: 26px; padding-top: 14px; border-top: 1px solid var(--border); text-align: center; color: var(--text-muted); font-size: 10px; }

        @media print {
            body { background: var(--surface); padding: 0; font-size: 10.5px; }
            .toolbar { display: none; }
            .fatura-container { box-shadow: none; padding: 14px; max-width: 100%; }
            @page { margin: 10mm; }
        }
    </style>
</head>
<body>

    <div class="toolbar">
        <button onclick="faturaKapat()" class="btn btn-secondary">⬅ Kapat</button>
        <button onclick="window.print()" class="btn btn-primary">🖨️ Yazdır / PDF</button>
    </div>

    @php
        $tutar    = (float) ($fatura->tutar ?? 0);
        $kdvOrani = isset($fatura->kdv_orani) && $fatura->kdv_orani !== null
                    ? (float) $fatura->kdv_orani
                    : (float) ($ayarlar->kdv ?? 20);
        $kdvTutar = round($tutar * $kdvOrani / 100, 2);
        $genelToplam = $tutar + $kdvTutar;

        $mAd = trim(($fatura->ad ?? '') . ' ' . ($fatura->soyad ?? ''));
        if ($mAd === '') $mAd = $fatura->firmaadi ?? ($fatura->email ?? 'Müşteri');

        // ETTN: faturada kayıtlı yoksa, fatura id'sine göre sabit üret (her açılışta aynı kalsın)
        $ettn = $fatura->ettn ?? null;
        if (empty($ettn)) {
            $seed = md5('isortagim-fatura-' . ($fatura->id ?? '0'));
            $ettn = substr($seed,0,8).'-'.substr($seed,8,4).'-'.substr($seed,12,4).'-'.substr($seed,16,4).'-'.substr($seed,20,12);
        }

        $oranMetni = rtrim(rtrim(number_format($kdvOrani, 2, ',', '.'), '0'), ',');
        $kdvOrani == (int)$kdvOrani && $oranMetni = (string)(int)$kdvOrani;

        $logoYolu = !empty($ayarlar->firma_logo)
            ? asset(ltrim($ayarlar->firma_logo, '/'))
            : asset('tema/uploads/logo/dn-kreatif-logo.png');

        $faturaTarih = !empty($fatura->tarih) ? date('d.m.Y', strtotime($fatura->tarih)) : date('d.m.Y');
        $olusmaZamani = !empty($fatura->created_at) ? date('H:i:s', strtotime($fatura->created_at)) : date('H:i:s');
    @endphp

    <div class="fatura-container">

        {{-- ÜST: logo + e-Arşiv + meta --}}
        <div class="fa-ust">
            <div class="fa-logo-wrap">
                <img src="{{ $logoYolu }}" alt="Logo" class="fa-logo" onerror="this.style.display='none'">
            </div>
            <div class="fa-earsiv">
                <div class="baslik">e-Arşiv Fatura</div>
            </div>
            <div class="fa-meta">
                <table>
                    <tr><td>Tarih:</td><td>{{ $faturaTarih }}</td></tr>
                    <tr><td>Fatura No:</td><td>{{ $fatura->fatura_no ?? ('FAT-' . str_pad($fatura->id, 6, '0', STR_PAD_LEFT)) }}</td></tr>
                    <tr><td>Özelleştirme No:</td><td>TR1.2</td></tr>
                    <tr><td>Senaryo:</td><td>EARSIVFATURA</td></tr>
                    <tr><td>Fatura Tipi:</td><td>SATIS</td></tr>
                    <tr><td>Oluşma Zamanı:</td><td>{{ $olusmaZamani }}</td></tr>
                </table>
            </div>
        </div>

        {{-- SATICI --}}
        <div class="fa-taraf">
            <div class="ad">{{ $ayarlar->fatura_firma_adi ?? $ayarlar->firma_adi ?? 'Firma Adı' }}</div>
            @if($ayarlar->fatura_adres ?? $ayarlar->firma_adres ?? null){{ $ayarlar->fatura_adres ?? $ayarlar->firma_adres }}<br>@endif
            @if($ayarlar->fatura_telefon ?? $ayarlar->firma_telefon ?? null)Tel: {{ $ayarlar->fatura_telefon ?? $ayarlar->firma_telefon }}<br>@endif
            @if($ayarlar->fatura_web ?? null)Web Sitesi: {{ $ayarlar->fatura_web }}<br>@endif
            @if($ayarlar->fatura_email ?? $ayarlar->firma_email ?? null)e-Posta: {{ $ayarlar->fatura_email ?? $ayarlar->firma_email }}<br>@endif
            @if($ayarlar->fatura_vergi_dairesi ?? null)Vergi Dairesi: {{ $ayarlar->fatura_vergi_dairesi }}<br>@endif
            @if($ayarlar->fatura_vergi_no ?? null)VKN: {{ $ayarlar->fatura_vergi_no }}<br>@endif
            @if($ayarlar->fatura_mersis ?? null)MERSİS No: {{ $ayarlar->fatura_mersis }}@endif

            {{-- ALICI --}}
            <div class="sayin">SAYIN</div>
            <strong>{{ $mAd }}</strong><br>
            @if($fatura->firmaadi ?? null){{ $fatura->firmaadi }}<br>@endif
            @if($fatura->adres ?? null){{ $fatura->adres }}<br>@endif
            @if(($fatura->il ?? null) || ($fatura->ilce ?? null)){{ $fatura->ilce ?? '' }} {{ $fatura->il ?? '' }}<br>@endif
            @if($fatura->telefon ?? null)Tel: {{ $fatura->telefon }}<br>@endif
            @if($fatura->email ?? null)e-Posta: {{ $fatura->email }}<br>@endif
            @if($fatura->vergidairesi ?? null)Vergi Dairesi: {{ $fatura->vergidairesi }}<br>@endif
            @if($fatura->vergino ?? null)VKN: {{ $fatura->vergino }}@endif

            <div class="fa-ettn"><b>ETTN:</b> {{ $ettn }}</div>

            @if(($fatura->durum ?? 0) == 1)
                <span class="durum-pill odendi">✓ ÖDENDİ</span>
            @elseif(($fatura->durum ?? 0) == 2)
                <span class="durum-pill iptal">✗ İPTAL</span>
            @else
                <span class="durum-pill bekliyor">⏳ BEKLİYOR</span>
            @endif
        </div>

        {{-- KALEM TABLOSU --}}
        <table class="fa-tablo">
            <thead>
                <tr>
                    <th style="width:34px">Sıra No</th>
                    <th>Mal Hizmet</th>
                    <th style="width:130px">Açıklama</th>
                    <th style="width:46px">Miktar</th>
                    <th style="width:80px">Birim Fiyat</th>
                    <th style="width:90px">Mal Hizmet Tutarı</th>
                    <th style="width:50px">KDV Oranı</th>
                    <th style="width:80px">KDV Tutarı</th>
                    <th style="width:130px">Satır Açıklaması</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td class="orta">1</td>
                    <td><span class="ad">{{ $fatura->baslik ?? 'Hizmet' }}</span></td>
                    <td>{{ $fatura->hizmet ?? ($fatura->aciklama ?? '') }}</td>
                    <td class="orta">1 Adet</td>
                    <td class="sag">{{ number_format($tutar, 2, ',', '.') }} TL</td>
                    <td class="sag">{{ number_format($tutar, 2, ',', '.') }} TL</td>
                    <td class="orta">%{{ $oranMetni }},00</td>
                    <td class="sag">{{ number_format($kdvTutar, 2, ',', '.') }} TL</td>
                    <td>{{ $fatura->aciklama ?? ($fatura->baslik ?? '') }}</td>
                </tr>
            </tbody>
        </table>

        {{-- ÖZET --}}
        <div class="fa-ozet-wrap">
            <table class="fa-ozet">
                <tr><td>Mal Hizmet Toplam Tutarı:</td><td>{{ number_format($tutar, 2, ',', '.') }} TL</td></tr>
                <tr><td>KDV Matrahı (%{{ $oranMetni }}):</td><td>{{ number_format($tutar, 2, ',', '.') }} TL</td></tr>
                <tr><td>Vergi Hariç Tutar:</td><td>{{ number_format($tutar, 2, ',', '.') }} TL</td></tr>
                <tr><td>Hesaplanan KDV (%{{ $oranMetni }}):</td><td>{{ number_format($kdvTutar, 2, ',', '.') }} TL</td></tr>
                <tr class="vurgu"><td>Vergiler Dahil Toplam Tutar:</td><td>{{ number_format($genelToplam, 2, ',', '.') }} TL</td></tr>
                <tr class="vurgu"><td>Ödenecek Tutar:</td><td>{{ number_format($genelToplam, 2, ',', '.') }} TL</td></tr>
                @if(!empty($fatura->para_birimi ?? null) && ($fatura->para_birimi ?? 'TL') !== 'TL' && !empty($fatura->doviz_tutar ?? null))
                <tr><td>Döviz Karşılığı:</td><td>{{ number_format($fatura->doviz_tutar, 2, ',', '.') }} {{ $fatura->para_birimi }} (TCMB Kuru: {{ number_format($fatura->kur ?? 0, 4, ',', '.') }})</td></tr>
                @endif
            </table>
        </div>

        {{-- BANKA BİLGİLERİ --}}
        @php
            $bankaMetni = '';
            if (!empty($ayarlar->fatura_banka_bilgileri)) {
                $bankaMetni = $ayarlar->fatura_banka_bilgileri;
            } elseif (!empty($ayarlar->fatura_iban) || !empty($ayarlar->fatura_banka_adi)) {
                $parcalar = [];
                if (!empty($ayarlar->fatura_banka_adi)) $parcalar[] = $ayarlar->fatura_banka_adi;
                if (!empty($ayarlar->fatura_banka_hesap_sahibi)) $parcalar[] = $ayarlar->fatura_banka_hesap_sahibi;
                if (!empty($ayarlar->fatura_iban)) $parcalar[] = 'IBAN : ' . $ayarlar->fatura_iban;
                $bankaMetni = implode("\n", $parcalar);
            }
        @endphp
        @if($bankaMetni !== '')
        <div class="fa-banka">{{ $bankaMetni }}</div>
        @endif

        <div class="fa-footer">Bu belge elektronik ortamda oluşturulmuştur.</div>
    </div>

<script>
function faturaKapat() {
    // 1) JS ile açılmış bir sekme/pencereyse kapat
    window.close();
    // 2) Pencere hâlâ açıksa (normal sekme), kapanmamıştır -> geri dön
    setTimeout(function () {
        // window.close() çalıştıysa buraya gelmez (sayfa kapanır)
        if (window.history.length > 1) {
            window.history.back();   // bir önceki sayfaya dön (fatura listesi vs.)
        } else {
            // Geçmiş yoksa fatura listesine yönlendir
            window.location.href = '{{ url('/admin/faturalar') }}';
        }
    }, 150);
}
</script>
</body>
</html>