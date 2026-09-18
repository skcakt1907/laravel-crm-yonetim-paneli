@php
    $rows = is_iterable($data) ? collect($data) : collect();
    $cols = $rows->count() ? array_keys((array) $rows->first()) : [];
    $basliklar = [
        'satis'   => 'Satış Raporu',
        'kazanc'  => 'Kazanç Raporu',
        'musteri' => 'Müşteri Raporu',
        'odeme'   => 'Ödeme Raporu',
    ];
    $raporAdi = $basliklar[$tip] ?? (ucfirst($tip) . ' Raporu');
    // Kolon adlarını okunaklı yap
    $etiket = fn($k) => ucwords(str_replace('_', ' ', $k));
@endphp
<!DOCTYPE html>
<html lang="tr">
<head>
<meta charset="UTF-8">
<title>{{ $raporAdi }}</title>
<style>
    *{box-sizing:border-box}
    body{font-family:'DejaVu Sans','Segoe UI',Arial,sans-serif;color:#1f2430;margin:0;padding:28px;font-size:13px}
    .head{display:flex;justify-content:space-between;align-items:flex-start;border-bottom:3px solid #b8b62e;padding-bottom:14px;margin-bottom:20px}
    .brand{font-size:22px;font-weight:800;color:#8a8a1f}
    .brand small{display:block;font-size:12px;color:#6b7280;font-weight:500;margin-top:2px}
    .meta{text-align:right;font-size:12px;color:#4b5563;line-height:1.7}
    .meta b{color:#1f2430}
    h1{font-size:18px;margin:0 0 4px}
    table{width:100%;border-collapse:collapse;margin-top:14px}
    th{background:#faf9f0;border:1px solid #e6e3d2;padding:8px 10px;text-align:left;font-size:11px;text-transform:uppercase;letter-spacing:.4px;color:#8a8a1f}
    td{border:1px solid #eee;padding:7px 10px;font-size:12px}
    tr:nth-child(even) td{background:#fcfcf7}
    .empty{padding:40px;text-align:center;color:#9aa0a6;border:1px dashed #ddd;border-radius:10px;margin-top:14px}
    .foot{margin-top:24px;text-align:center;color:#9aa0a6;font-size:11px;border-top:1px solid #eee;padding-top:10px}
    @media print{body{padding:0}}
</style>
</head>
<body>
    <div class="head">
        <div>
            <div class="brand">İş Ortağım <small>Bayi Paneli — Rapor</small></div>
        </div>
        <div class="meta">
            <div><b>{{ $raporAdi }}</b></div>
            <div>Bayi: <b>{{ $bayi->bayi_kodu ?? ('#'.($bayi->id ?? '-')) }}</b></div>
            <div>Tarih: <b>{{ $tarih }}</b></div>
            <div>Kayıt: <b>{{ $rows->count() }}</b></div>
        </div>
    </div>

    <h1>{{ $raporAdi }}</h1>

    @if($rows->count())
        <table>
            <thead>
                <tr>@foreach($cols as $c)<th>{{ $etiket($c) }}</th>@endforeach</tr>
            </thead>
            <tbody>
                @foreach($rows as $r)
                    @php $arr = (array) $r; @endphp
                    <tr>@foreach($cols as $c)<td>{{ is_scalar($arr[$c] ?? '') ? ($arr[$c] ?? '') : json_encode($arr[$c]) }}</td>@endforeach</tr>
                @endforeach
            </tbody>
        </table>
    @else
        <div class="empty">Bu rapor için kayıt bulunamadı.</div>
    @endif

    <div class="foot">Bu rapor {{ $tarih }} tarihinde İş Ortağım Bayi Paneli üzerinden oluşturulmuştur.</div>
</body>
</html>
