<!DOCTYPE html>
<html lang="tr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Teklif Ödeme — {{ $ayarlar->firma_adi ?? 'DN İş Ortağım' }}</title>
<style>
*{box-sizing:border-box;margin:0;padding:0;}
body{font-family:'Segoe UI',Arial,sans-serif;background:linear-gradient(135deg,#667eea 0%,#764ba2 100%);min-height:100vh;display:flex;align-items:center;justify-content:center;padding:20px;}
.card{background:#fff;border-radius:20px;box-shadow:0 20px 60px rgba(0,0,0,.2);max-width:560px;width:100%;overflow:hidden;}
.head{{!! $talep->tip === 'bayi' ? "background:linear-gradient(135deg,#f59e0b,#d97706);" : "background:linear-gradient(135deg,#8b5cf6,#6d28d9);" !!}}color:#fff;padding:40px 30px;text-align:center;}
.head .icon{font-size:64px;margin-bottom:10px;}
.head h1{font-size:24px;font-weight:700;}
.head p{opacity:.9;font-size:14px;margin-top:8px;}
.body{padding:36px 30px;}
.paket{background:{{ $talep->tip === 'bayi' ? 'linear-gradient(135deg,#fef3c7,#fde68a)' : 'linear-gradient(135deg,#ede9fe,#ddd6fe)' }};padding:28px;border-radius:14px;text-align:center;margin-bottom:24px;border:2px solid {{ $talep->tip === 'bayi' ? '#fcd34d' : '#c4b5fd' }};}
.paket .l{font-size:11px;text-transform:uppercase;letter-spacing:1px;color:{{ $talep->tip === 'bayi' ? '#92400e' : '#4c1d95' }};font-weight:700;}
.paket .n{font-size:22px;font-weight:700;color:{{ $talep->tip === 'bayi' ? '#78350f' : '#4c1d95' }};margin:10px 0;}
.paket .p{font-size:42px;font-weight:800;color:{{ $talep->tip === 'bayi' ? '#b45309' : '#6d28d9' }};}
.info{background:#f8fafc;padding:18px;border-radius:12px;margin-bottom:20px;font-size:14px;}
.info div{padding:6px 0;display:flex;justify-content:space-between;}
.info div:not(:last-child){border-bottom:1px solid #e5e7eb;}
.info .k{color:#64748b;}
.info .v{font-weight:600;color:#1e293b;}
.msg{background:#eef2ff;border-left:4px solid #667eea;padding:14px 16px;border-radius:8px;margin-bottom:20px;font-size:14px;color:#3730a3;}
.btn{width:100%;background:linear-gradient(135deg,#667eea,#764ba2);color:#fff;border:none;padding:18px;font-size:16px;font-weight:700;border-radius:12px;cursor:pointer;transition:.2s;box-shadow:0 8px 20px rgba(102,126,234,.4);}
.btn:hover{transform:translateY(-2px);box-shadow:0 12px 28px rgba(102,126,234,.5);}
.btn:disabled{opacity:.6;cursor:not-allowed;transform:none;}
.footer{text-align:center;padding:20px;color:#94a3b8;font-size:12px;}
.durum-bar{padding:14px;text-align:center;font-weight:600;font-size:14px;}
.durum-odendi{background:#fef3c7;color:#92400e;}
.durum-onaylandi{background:#d1fae5;color:#065f46;}
.durum-iptal{background:#fee2e2;color:#991b1b;}
.durum-reddedildi{background:#fee2e2;color:#991b1b;}
.durum-kabul_edildi{background:#d1fae5;color:#065f46;}
.btn-kabul:hover,.btn-red:hover{transform:translateY(-2px);}
</style>
</head>
<body>
<div class="card">
    @if(in_array($talep->durum, ['odendi','onaylandi','iptal','reddedildi']))
        <div class="durum-bar durum-{{ $talep->durum }}">
            @if($talep->durum === 'odendi') ✓ Ödeme bildirimi alındı, onay bekleniyor.
            @elseif($talep->durum === 'onaylandi') ✓ Ödemeniz onaylandı, teşekkür ederiz!
            @elseif($talep->durum === 'reddedildi') ✕ Bu teklifi reddettiniz.
            @else ✕ Bu teklif iptal edildi.
            @endif
        </div>
    @endif

    <div class="head">
        <div class="icon">{{ $talep->tip === 'bayi' ? '⭐' : '🎁' }}</div>
        <h1>{{ $talep->tip === 'bayi' ? 'Bayilik Teklifimiz' : 'Size Özel Teklifimiz' }}</h1>
        <p>{{ $ayarlar->firma_adi ?? 'DN İş Ortağım' }}</p>
    </div>

    <div class="body">
        <p style="color:#475569;margin-bottom:20px;">Merhaba <strong>{{ $customer->adi ?? 'Sayın Müşterimiz' }}</strong>,</p>

        <div class="paket">
            <div class="l">Paket</div>
            <div class="n">{{ $talep->paket_adi }}</div>
            <div class="p">{{ number_format((float)$talep->tutar, 2, ',', '.') }} ₺</div>
        </div>

        <div class="info">
            <div><span class="k">Ödeme Yöntemi</span><span class="v">{{ $talep->odeme_yontemi === 'online' ? 'Online Ödeme' : 'Havale / EFT' }}</span></div>
            <div><span class="k">Teklif Tarihi</span><span class="v">{{ \Carbon\Carbon::parse($talep->sent_at ?? $talep->created_at)->format('d.m.Y H:i') }}</span></div>
            <div><span class="k">Teklif No</span><span class="v">#{{ $talep->id }}</span></div>
        </div>

        @if($talep->mesaj)
            <div class="msg"><strong>Mesajımız:</strong><br>{!! nl2br(e($talep->mesaj)) !!}</div>
        @endif

        @if(in_array($talep->durum, ['gonderildi','bekliyor']))
            {{-- 1. AŞAMA: Müşteri henüz kabul/reddetmemiş --}}
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;margin-bottom:14px;">
                <form method="POST" action="{{ route('bayi.odeme.kabul', $talep->token) }}" onsubmit="return confirm('Bu teklifi kabul etmek istediğinize emin misiniz?');">
                    @csrf
                    <button type="submit" class="btn-kabul" style="width:100%;background:linear-gradient(135deg,#10b981,#059669);color:#fff;border:none;padding:16px;font-size:15px;font-weight:700;border-radius:12px;cursor:pointer;transition:.2s;box-shadow:0 8px 20px rgba(16,185,129,.4);">
                        ✓ TEKLİFİ KABUL ET
                    </button>
                </form>
                <button type="button" onclick="document.getElementById('redModal').style.display='flex'" class="btn-red" style="width:100%;background:linear-gradient(135deg,#ef4444,#dc2626);color:#fff;border:none;padding:16px;font-size:15px;font-weight:700;border-radius:12px;cursor:pointer;transition:.2s;box-shadow:0 8px 20px rgba(239,68,68,.4);">
                    ✕ REDDET
                </button>
            </div>

            <div style="text-align:center;color:#94a3b8;font-size:12px;margin:14px 0;">— veya doğrudan —</div>

            <form method="POST" action="{{ route('bayi.odeme.post', $talep->token) }}" onsubmit="return confirm('Ödeme yaptığınızı bildirmek istiyor musunuz? Ödemeniz manuel olarak onaylanacaktır.');">
                @csrf
                <button type="submit" class="btn">
                    @if($talep->odeme_yontemi === 'online')
                        💳 ÖDEMEYİ TAMAMLA
                    @else
                        ✓ ÖDEMEYİ YAPTIM (BİLDİR)
                    @endif
                </button>
            </form>

            @if($talep->odeme_yontemi === 'havale')
                <div style="margin-top:16px;font-size:13px;color:#64748b;text-align:center;line-height:1.6;">
                    Banka hesap bilgileri için {{ $ayarlar->firma_email ?? 'bizimle iletişim' }} kurabilirsiniz.<br>
                    Ödemeyi yaptıktan sonra yukarıdaki butona basın.
                </div>
            @endif

            {{-- RED MODAL --}}
            <div id="redModal" style="display:none;position:fixed;top:0;left:0;right:0;bottom:0;background:rgba(0,0,0,.6);z-index:999;align-items:center;justify-content:center;padding:20px;">
                <div style="background:#fff;border-radius:16px;max-width:480px;width:100%;padding:28px;">
                    <h3 style="font-size:18px;font-weight:700;color:#1e293b;margin-bottom:8px;">Teklifi Reddet</h3>
                    <p style="font-size:14px;color:#64748b;margin-bottom:16px;">Reddetme sebebinizi belirtmek ister misiniz? (Opsiyonel — bize geri bildirim olarak gelir.)</p>
                    <form method="POST" action="{{ route('bayi.odeme.red', $talep->token) }}">
                        @csrf
                        <textarea name="red_sebep" rows="4" placeholder="Örn: Bütçemize uymuyor, başka teklif aldık..." style="width:100%;border:1px solid #e2e8f0;border-radius:10px;padding:12px 14px;font-size:14px;resize:vertical;font-family:inherit;"></textarea>
                        <div style="display:flex;gap:10px;margin-top:14px;">
                            <button type="button" onclick="document.getElementById('redModal').style.display='none'" style="flex:1;padding:12px;border:1px solid #e2e8f0;background:#fff;color:#64748b;border-radius:10px;font-weight:600;cursor:pointer;">Vazgeç</button>
                            <button type="submit" style="flex:1;padding:12px;border:none;background:linear-gradient(135deg,#ef4444,#dc2626);color:#fff;border-radius:10px;font-weight:700;cursor:pointer;">Reddet</button>
                        </div>
                    </form>
                </div>
            </div>

        @elseif($talep->durum === 'kabul_edildi')
            {{-- 2. AŞAMA: Kabul etti, şimdi ödeme yapacak --}}
            <div style="background:#d1fae5;border-left:4px solid #10b981;padding:14px 16px;border-radius:8px;margin-bottom:20px;font-size:14px;color:#065f46;">
                ✓ Teklifi <strong>kabul ettiniz</strong>. Şimdi ödeme adımına geçebilirsiniz.
            </div>

            <form method="POST" action="{{ route('bayi.odeme.post', $talep->token) }}" onsubmit="return confirm('Ödeme yaptığınızı bildirmek istiyor musunuz? Ödemeniz manuel olarak onaylanacaktır.');">
                @csrf
                <button type="submit" class="btn">
                    @if($talep->odeme_yontemi === 'online')
                        💳 ÖDEMEYİ TAMAMLA
                    @else
                        ✓ ÖDEMEYİ YAPTIM (BİLDİR)
                    @endif
                </button>
            </form>

            @if($talep->odeme_yontemi === 'havale')
                <div style="margin-top:16px;font-size:13px;color:#64748b;text-align:center;line-height:1.6;">
                    Banka hesap bilgileri için {{ $ayarlar->firma_email ?? 'bizimle iletişim' }} kurabilirsiniz.<br>
                    Ödemeyi yaptıktan sonra yukarıdaki butona basın.
                </div>
            @endif
        @endif
    </div>

    <div class="footer">© {{ date('Y') }} {{ $ayarlar->firma_adi ?? 'DN İş Ortağım' }}</div>
</div>
</body>
</html>