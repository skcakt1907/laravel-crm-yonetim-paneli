<!DOCTYPE html>
<html lang="tr">
<head>
<meta charset="UTF-8">
<title>{{ $baslik ?? 'Görev Bildirimi' }}</title>
</head>
<body style="margin:0;padding:0;background:#f5f5f5;font-family:'Segoe UI',Arial,sans-serif;color:#333">

<table width="100%" cellpadding="0" cellspacing="0" style="background:#f5f5f5;padding:40px 20px">
<tr><td align="center">

<table width="100%" cellpadding="0" cellspacing="0" style="max-width:600px;background:#fff;border-radius:16px;overflow:hidden;box-shadow:0 4px 20px rgba(0,0,0,0.08)">

{{-- HEADER --}}
<tr>
<td style="background:linear-gradient(135deg,#b8b62e,#8a8a1f);padding:32px 24px;text-align:center">
    <h1 style="margin:0;color:#000;font-size:22px;font-weight:700">{{ $baslik ?? 'Görev Bildirimi' }}</h1>
</td>
</tr>

{{-- CONTENT --}}
<tr>
<td style="padding:32px 28px">

<p style="margin:0 0 16px;font-size:15px;line-height:1.6">
    Merhaba <strong style="color:#8a8a1f">{{ $aliciAdi ?? '' }}</strong>,
</p>

<p style="margin:0 0 20px;font-size:15px;line-height:1.6">
    {!! $govde ?? '' !!}
</p>

{{-- Görev kartı --}}
<div style="background:#fafafa;border-left:4px solid #b8b62e;padding:16px 20px;border-radius:8px;margin-bottom:24px">
    <div style="font-size:12px;color:#999;margin-bottom:6px;text-transform:uppercase;letter-spacing:1px">
        📋 Görev
    </div>
    <div style="font-size:16px;font-weight:700;color:#333;margin-bottom:8px">{{ $konu ?? '' }}</div>
    @if(!empty($departman))
        <div style="font-size:13px;color:#666;margin-bottom:8px">🏷️ {{ $departman }}</div>
    @endif
    @if(!empty($aciklama))
        <div style="font-size:14px;line-height:1.6;color:#444;white-space:pre-wrap;border-top:1px solid #eee;padding-top:10px;margin-top:6px">{{ $aciklama }}</div>
    @endif
</div>

{{-- Buton --}}
@if(!empty($url))
<div style="text-align:center;margin-bottom:24px">
    <a href="{{ $url }}" style="display:inline-block;background:linear-gradient(135deg,#b8b62e,#8a8a1f);color:#000;text-decoration:none;padding:14px 32px;border-radius:10px;font-weight:700;font-size:14px;box-shadow:0 4px 14px rgba(184,182,46,0.35)">
        🔗 Görevi Aç
    </a>
</div>
@endif

<div style="border-top:1px solid #eee;padding-top:20px;margin-top:24px">
    <p style="margin:0;font-size:12px;color:#999;line-height:1.6">
        Bu otomatik bir bildirim mailidir. İş Ortağım yönetim panelindeki görev hareketleri için gönderilir.
    </p>
</div>

</td>
</tr>

{{-- FOOTER --}}
<tr>
<td style="background:#fafafa;padding:20px 24px;text-align:center;border-top:1px solid #eee">
    <p style="margin:0;font-size:13px;color:#666">
        <strong style="color:#8a8a1f">İş Ortağım</strong> — DN Kreatif Yönetim Paneli
    </p>
    <p style="margin:6px 0 0;font-size:11px;color:#aaa">
        © {{ date('Y') }} DN Grup Medya ve Teknoloji A.Ş.
    </p>
</td>
</tr>

</table>

</td></tr>
</table>

</body>
</html>
