{{-- Tab: Raporlar --}}
@include('admin._partials.mention-autocomplete')

<div class="mini-stat-grid">
    <div class="mini-stat">
        <div class="lbl">Toplam Harcama</div>
        <div class="val">₺{{ number_format($customer->toplam_harcama ?? 0, 2, ',', '.') }}</div>
    </div>
    <div class="mini-stat info">
        <div class="lbl">Sipariş Sayısı</div>
        <div class="val">{{ count($faturalar ?? $customer->faturalar ?? []) }}</div>
    </div>
    @php
        $uyelikGun = 0;
        $kayitTarih = $customer->created_at ?? $customer->bayi_tarihi ?? null;
        if ($kayitTarih) {
            try { $uyelikGun = (int) abs(\Carbon\Carbon::parse($kayitTarih)->diffInDays(now())); } catch (\Throwable $e) {}
        }
    @endphp
    <div class="mini-stat warning">
        <div class="lbl">Üyelik Süresi</div>
        <div class="val">{{ number_format($uyelikGun) }} <span style="font-size:13px;color:var(--text-muted)">gün</span></div>
    </div>
    <div class="mini-stat success">
        <div class="lbl">Sadakat Puanı</div>
        <div class="val">{{ $customer->puan ?? 0 }}</div>
    </div>
</div>

<div style="display:flex;justify-content:flex-end;margin-bottom:14px">
    <button type="button" class="btn btn-primary btn-sm" onclick="crmToggleForm('raporEkleForm')">
        <i data-lucide="plus"></i>
        <span>Yeni Rapor Ekle</span>
    </button>
</div>

<div class="section" id="raporEkleForm" style="{{ $errors->any() ? '' : 'display:none' }}">
    <div class="section-title">
        <i data-lucide="plus-circle"></i>
        <span>Yeni Rapor Ekle</span>
    </div>

    <form action="{{ route('admin.crm.musteriler.rapor.ekle', $customer->id) }}" method="POST" enctype="multipart/form-data">
        @csrf
        <div class="form-grid">
            <div class="form-group">
                <label class="form-label">Başlık <span class="required">*</span></label>
                <input type="text" name="baslik" required class="form-input" placeholder="Örn: Mayıs 2026 Performans Raporu" value="{{ old('baslik') }}">
            </div>
            <div class="form-group full">
                <label class="form-label">Rapor İçeriği <span class="required">*</span></label>
                <textarea name="icerik" rows="6" required class="form-textarea mention-enabled" autocomplete="off" placeholder="Rapor detayını yaz... @kullaniciadi ile bir admini etiketle (müşteriye mail + bildirim ile gönderilir)">{{ old('icerik') }}</textarea>
                <div class="form-help">💡 <strong>@kullaniciadi</strong> yazarak admin etiketle — etiketlenenlere mail gönderilir</div>
            </div>
            <div class="form-group full">
                <label class="form-label">Dosya Ek (PDF, Word, Excel, resim) — opsiyonel</label>
                <input type="file" name="dosya" accept=".pdf,.doc,.docx,.xls,.xlsx,.jpg,.jpeg,.png" class="form-input">
                <div class="form-help">Maks. 10 MB</div>
            </div>
        </div>

        <div style="display:flex;justify-content:flex-end;margin-top:12px">
            <button type="submit" class="btn btn-primary btn-sm">
                <i data-lucide="send"></i>
                <span>Rapor Oluştur & Gönder</span>
            </button>
        </div>
    </form>
</div>

@php
    $musteriRaporlar = collect();
    try {
        $musteriUyeId = \Illuminate\Support\Facades\DB::table('uyeler')->where('email', $customer->email)->value('id');
        $musteriRaporlar = \Illuminate\Support\Facades\DB::table('musteri_raporlar')
            ->where(function($q) use ($musteriUyeId, $customer) {
                if ($musteriUyeId) $q->where('uyeid', $musteriUyeId);
                $q->orWhere('crm_musteri_id', $customer->id);
            })
            ->orderByDesc('id')->get();
    } catch (\Throwable $e) {}
@endphp

<div class="section" style="padding:0">
    <div style="padding:16px 20px;border-bottom:1px solid var(--border)">
        <h4 style="font-size:14px;font-weight:600">
            📊 Müşteri Raporları
            <span class="badge badge-brand" style="margin-left:6px">{{ $musteriRaporlar->count() }}</span>
        </h4>
    </div>

    <div style="padding:12px">
        @forelse($musteriRaporlar as $r)
        <div id="rapor-kart-{{ $r->id }}" style="background:var(--bg-subtle);border:1px solid var(--border);border-radius:var(--radius-md);padding:14px;margin-bottom:10px">
            <script type="text/template" id="rapor-icerik-{{ $r->id }}">{{ $r->icerik ?? '' }}</script>
            <script type="text/template" id="rapor-meta-{{ $r->id }}">{{ json_encode(['baslik' => $r->baslik ?? '—', 'tarih' => $r->tarih ? \Carbon\Carbon::parse($r->tarih)->format('d.m.Y H:i') : '—', 'dosya' => $r->dosya ?? null]) }}</script>
            <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:12px;margin-bottom:6px">
                <div style="flex:1;min-width:0">
                    <strong>📊 {{ $r->baslik ?? '—' }}</strong>
                    <div style="font-size:11px;color:var(--text-muted);margin-top:2px">
                        {{ $r->tarih ? \Carbon\Carbon::parse($r->tarih)->format('d.m.Y H:i') : '—' }}
                    </div>
                </div>
                <div style="display:flex;align-items:center;gap:4px">
                    <button type="button" class="table-action" title="İncele"
                            onclick="raporIncele({{ $r->id }})">
                        <i data-lucide="eye"></i>
                    </button>
                    <button type="button" class="table-action" title="SMS / Mail ile gönder"
                            onclick="crmRaporGonder({{ $r->id }})">
                        <i data-lucide="send"></i>
                    </button>
                    <form action="{{ route('admin.crm.musteriler.rapor.tekrar', ['id' => $customer->id, 'raporId' => $r->id]) }}" method="POST" style="display:inline">
                        @csrf
                        <button class="table-action" title="Bildirimi tekrar gönder">
                            <i data-lucide="rotate-cw"></i>
                        </button>
                    </form>
                    <form action="{{ route('admin.crm.musteriler.rapor.sil', ['id' => $customer->id, 'raporId' => $r->id]) }}" method="POST" style="display:inline" onsubmit="return confirm('Rapor silinsin mi?');">
                        @csrf @method('DELETE')
                        <button class="table-action" style="color:var(--danger)" title="Sil">
                            <i data-lucide="trash-2"></i>
                        </button>
                    </form>
                </div>
            </div>
            <div style="font-size:13px;color:var(--text-secondary);white-space:pre-wrap;margin-top:8px;line-height:1.6">{!! preg_replace('/@([a-zA-Z0-9_\.]+)/', '<span class="badge badge-brand" style="display:inline-flex">@$1</span>', e(\Illuminate\Support\Str::limit($r->icerik ?? '', 400))) !!}</div>
            @if(!empty($r->dosya))
                <a href="{{ asset($r->dosya) }}" target="_blank" class="btn btn-secondary btn-sm" style="margin-top:8px">
                    <i data-lucide="download"></i>
                    <span>Dosyayı İndir</span>
                </a>
            @endif
        </div>
        @empty
        <div class="empty-state">
            <i data-lucide="bar-chart-3" class="empty-state-icon"></i>
            <h4>Henüz rapor yok</h4>
            <p>Yukarıdan ilk raporu ekle.</p>
        </div>
        @endforelse
    </div>
</div>

{{-- Rapor İnceleme Modalı --}}
<div id="raporModal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.5);z-index:1000;align-items:center;justify-content:center;padding:20px" onclick="if(event.target===this)raporKapat()">
    <div style="background:var(--bg-card,#fff);border-radius:var(--radius-lg,14px);max-width:680px;width:100%;max-height:85vh;display:flex;flex-direction:column;box-shadow:0 24px 64px rgba(0,0,0,.3)">
        <div style="display:flex;align-items:center;justify-content:space-between;padding:16px 20px;border-bottom:1px solid var(--border)">
            <h4 id="raporModalBaslik" style="font-size:15px;font-weight:700;margin:0">📊 Rapor</h4>
            <button type="button" class="table-action" onclick="raporKapat()" title="Kapat"><i data-lucide="x"></i></button>
        </div>
        <div id="raporModalMeta" style="padding:10px 20px;font-size:12px;color:var(--text-muted);border-bottom:1px solid var(--border)"></div>
        <div id="raporModalIcerik" style="padding:20px;font-size:13.5px;line-height:1.7;white-space:pre-wrap;overflow:auto;color:var(--text-secondary)"></div>
        {{-- Dosya önizleme (PDF → iframe, görsel → img) --}}
        <div id="raporModalPreview" style="display:none;padding:0 20px 16px;flex:1;min-height:0;overflow:auto"></div>
        <div id="raporModalDosya" style="padding:14px 20px;border-top:1px solid var(--border);display:none">
            <a id="raporModalDosyaLink" href="#" target="_blank" class="btn btn-secondary btn-sm">
                <i data-lucide="download"></i><span>Dosyayı İndir / Yeni Sekmede Aç</span>
            </a>
        </div>
    </div>
</div>

<script>
function raporIncele(id) {
    var icerik = document.getElementById('rapor-icerik-' + id);
    var metaEl = document.getElementById('rapor-meta-' + id);
    if (!icerik || !metaEl) return;
    var meta = {};
    try { meta = JSON.parse(crmHtmlDecode(metaEl.textContent) || '{}'); } catch (e) {}

    document.getElementById('raporModalBaslik').textContent = '📊 ' + (meta.baslik || 'Rapor');
    document.getElementById('raporModalMeta').textContent = meta.tarih || '—';
    document.getElementById('raporModalIcerik').textContent = crmHtmlDecode(icerik.textContent || '').trim();

    var dosyaBox = document.getElementById('raporModalDosya');
    var prevBox  = document.getElementById('raporModalPreview');
    prevBox.innerHTML = ''; prevBox.style.display = 'none';
    if (meta.dosya) {
        var url = '{{ asset('') }}' + String(meta.dosya).replace(/^\/+/, '');
        var safeUrl = encodeURI(url);
        dosyaBox.style.display = '';
        document.getElementById('raporModalDosyaLink').href = url;
        var ext = (String(meta.dosya).split('.').pop() || '').toLowerCase();
        if (ext === 'pdf') {
            prevBox.innerHTML = '<iframe src="' + safeUrl + '#toolbar=1" style="width:100%;height:62vh;border:1px solid var(--border,#e5e7eb);border-radius:8px" title="PDF önizleme"></iframe>';
            prevBox.style.display = '';
        } else if (['jpg','jpeg','png','gif','webp','bmp','svg'].indexOf(ext) !== -1) {
            prevBox.innerHTML = '<img src="' + safeUrl + '" alt="Önizleme" style="max-width:100%;border-radius:8px;display:block;margin:auto">';
            prevBox.style.display = '';
        }
    } else {
        dosyaBox.style.display = 'none';
    }

    var modal = document.getElementById('raporModal');
    modal.style.display = 'flex';
    if (window.lucide) lucide.createIcons();
}
function raporKapat() { document.getElementById('raporModal').style.display = 'none'; }
document.addEventListener('keydown', function(e) { if (e.key === 'Escape') raporKapat(); });

// Raporu tek tıkla SMS/Mail ile gönder (madde 1) — üst modalı raporun bilgileriyle açar
function crmHtmlDecode(s) { var t = document.createElement('textarea'); t.innerHTML = s || ''; return t.value; }
function crmRaporGonder(id) {
    var metaEl = document.getElementById('rapor-meta-' + id);
    var icEl = document.getElementById('rapor-icerik-' + id);
    var meta = {}; try { meta = JSON.parse(crmHtmlDecode(metaEl.textContent) || '{}'); } catch (e) {}
    var baslik = meta.baslik || 'Rapor';
    var icerik = crmHtmlDecode(icEl ? icEl.textContent : '').trim();
    var opts = {
        konu: baslik,
        ek_baslik: baslik,
        mesaj: 'Sayın {ad}, "' + baslik + '" raporunuz hazır.' + (icerik ? '\n\n' + icerik.substring(0, 300) : '')
    };
    if (meta.dosya) { opts.ek_link = '{{ asset('') }}' + String(meta.dosya).replace(/^\/+/, ''); }
    if (window.crmSendOpen) window.crmSendOpen(opts);
    else alert('Gönderim penceresi yüklenemedi — sayfayı yenileyin.');
}
</script>
