{{-- Tab: Destek (Chat UI) --}}
@php
    $musteriUyeId = null;
    if (!empty($customer->email)) {
        $musteriUyeId = \Illuminate\Support\Facades\DB::table('uyeler')
            ->where('email', $customer->email)
            ->value('id');
    }

    $talepler = collect();
    if ($musteriUyeId && \Illuminate\Support\Facades\Schema::hasTable('destek')) {
        $talepler = \Illuminate\Support\Facades\DB::table('destek')
            ->where('uyeid', $musteriUyeId)
            ->where(function ($q) { $q->whereNull('ustid')->orWhere('ustid', 0); })
            ->orderByDesc('id')->get();
    }
    $secilenTalepId = (int) request('talep_id', $talepler->first()->id ?? 0);
    $mesajlar = collect();
    $anaTalep = null;
    if ($secilenTalepId) {
        $anaTalep = \Illuminate\Support\Facades\DB::table('destek')->where('id', $secilenTalepId)->first();
        if ($anaTalep) {
            $cevaplar = \Illuminate\Support\Facades\DB::table('destek')
                ->where('ustid', $secilenTalepId)->orderBy('id')->get();
            $mesajlar = collect([$anaTalep])->merge($cevaplar);
        }
    }
@endphp

@if(empty($customer->email))
<div class="alert alert-danger" style="margin-bottom:16px">
    <i data-lucide="alert-circle"></i>
    <div>
        <strong>Müşteri e-postası tanımlı değil.</strong>
        Destek talebi açmak için önce müşteriye e-posta ekleyin.
        <a href="{{ route('admin.crm.musteriler.edit', $customer->id) }}" style="color:var(--danger);font-weight:600;text-decoration:underline">Müşteriyi düzenle</a>
    </div>
</div>
@elseif(!$musteriUyeId)
<div class="alert alert-info" style="margin-bottom:16px">
    <i data-lucide="info"></i>
    <div>
        <strong>Bilgi:</strong> Müşteri henüz üye olarak kayıtlı değil.
        İlk destek talebi açıldığında otomatik <strong>üye kaydı da oluşturulacak</strong>.
    </div>
</div>
@endif

<div class="chat-container">

    {{-- Sol: Talep listesi --}}
    <div class="chat-list">
        <div class="chat-list-header">
            <strong style="font-size:13px">🎫 Destek Talepleri</strong>
            <button type="button" onclick="document.getElementById('yeniTalepForm').style.display = document.getElementById('yeniTalepForm').style.display === 'none' ? '' : 'none'" class="btn btn-primary btn-sm" style="padding:4px 10px;font-size:11px">
                <i data-lucide="plus"></i>
                <span>Yeni</span>
            </button>
        </div>

        <form id="yeniTalepForm" action="{{ route('admin.crm.musteriler.destek.mesaj', $customer->id) }}" method="POST" style="display:none;padding:12px;border-bottom:1px solid var(--border)">
            @csrf
            <input type="text" name="baslik" required placeholder="Konu" class="form-input" style="font-size:12px;padding:6px 10px;margin-bottom:6px">
            <textarea name="mesaj" rows="3" required placeholder="İlk mesaj..." class="form-textarea" style="font-size:12px;padding:6px 10px;margin-bottom:6px"></textarea>
            <button class="btn btn-primary btn-sm" style="width:100%;font-size:11px">
                <i data-lucide="send"></i>
                <span>Talebi Aç</span>
            </button>
        </form>

        <div style="flex:1;overflow-y:auto">
            @forelse($talepler as $t)
                <a href="?tab=destek&talep_id={{ $t->id }}" class="chat-list-item {{ $t->id == $secilenTalepId ? 'active' : '' }}">
                    <div style="display:flex;align-items:center;justify-content:space-between;gap:6px">
                        <div class="title" style="flex:1;min-width:0;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">{{ $t->baslik }}</div>
                        @if(($t->durum ?? 0) == 1)
                            <span class="badge badge-success" style="font-size:9px;padding:2px 6px">✓</span>
                        @else
                            <span class="badge badge-warning" style="font-size:9px;padding:2px 6px">⏳</span>
                        @endif
                    </div>
                    <div class="date">{{ $t->tarih ?? '—' }}</div>
                </a>
            @empty
                <div class="empty-state" style="padding:24px 12px">
                    <i data-lucide="message-square" class="empty-state-icon"></i>
                    <p style="font-size:12px">Talep yok</p>
                </div>
            @endforelse
        </div>
    </div>

    {{-- Sağ: Chat penceresi --}}
    <div class="chat-window">
        @if($anaTalep)
            <div class="chat-header">
                <div>
                    <strong style="font-size:14px">{{ $anaTalep->baslik }}</strong>
                    <div style="font-size:11px;color:var(--text-muted);margin-top:2px">
                        {{ $customer->adi ?? '' }} {{ $customer->soyad ?? '' }} · Talep #{{ $secilenTalepId }}
                        @if(($anaTalep->durum ?? 0) == 2)
                            · <span style="color:var(--text-muted)">🔒 Müşteri tarafından kapatıldı</span>
                        @endif
                    </div>
                </div>
                <div style="display:flex;align-items:center;gap:8px">
                    @if(($anaTalep->durum ?? 0) == 2)
                        <span class="badge badge-neutral">🔒 Kapalı</span>
                    @elseif(($anaTalep->durum ?? 0) == 1)
                        <span class="badge badge-success">✓ Çözüldü</span>
                    @else
                        <span class="badge badge-warning">⏳ Açık</span>
                    @endif

                    <button type="button" onclick="talepSil({{ $secilenTalepId }})"
                            class="btn btn-ghost btn-sm" style="color:var(--danger);padding:4px 10px"
                            title="Talebi tamamen sil">
                        <i data-lucide="trash-2" style="width:14px;height:14px"></i>
                    </button>
                </div>
            </div>

            <div class="chat-body" id="chatBox">
                @foreach($mesajlar as $m)
                    @php
                        // Yeni şema: gonderen_tip varsa onu kullan (B-saf)
                        // Eski şema: uyeid kontrolü (geriye uyumluluk)
                        $isAdmin = false;
                        if (isset($m->gonderen_tip)) {
                            $isAdmin = ($m->gonderen_tip === 'admin');
                        } else {
                            // Geriye uyumluluk: cevaplarda uyeid=0 admin
                            $isAdmin = ((int) ($m->uyeid ?? -1) === 0);
                        }
                        $isMusteri = !$isAdmin;
                        $isAnaTalep = (int) $m->id === (int) $secilenTalepId;
                    @endphp
                    <div class="chat-msg {{ $isMusteri ? 'from-customer' : 'from-admin' }}" style="position:relative">
                        <div class="msg-meta">
                            {{ $isMusteri ? '💬 ' . ($customer->adi ?? 'Müşteri') : '👤 Admin' }} · {{ $m->tarih ?? '' }}
                        </div>
                        <div style="white-space:pre-wrap">{{ $m->mesaj }}</div>

                        {{-- Sil butonu — sadece ana talep DEĞİLSE (ana talebi başlıktaki sil butonu siliyor) --}}
                        @if(!$isAnaTalep)
                            <button type="button" onclick="mesajSil({{ $secilenTalepId }}, {{ $m->id }})"
                                    class="msg-sil-btn" title="Bu mesajı sil">
                                <i data-lucide="trash-2" style="width:11px;height:11px"></i>
                            </button>
                        @endif
                    </div>
                @endforeach
            </div>

            <form action="{{ route('admin.crm.musteriler.destek.mesaj', $customer->id) }}" method="POST" class="chat-input">
                @csrf
                <input type="hidden" name="ustid" value="{{ $secilenTalepId }}">
                <input type="text" name="mesaj" required placeholder="Cevap yaz..." class="form-input">
                <button class="btn btn-primary">
                    <i data-lucide="send"></i>
                    <span>Gönder</span>
                </button>
            </form>
        @else
            <div style="flex:1;display:flex;align-items:center;justify-content:center;padding:32px">
                <div class="empty-state">
                    <i data-lucide="message-square" class="empty-state-icon"></i>
                    <h4>Bir talep seç</h4>
                    <p>Veya <strong>+ Yeni</strong> ile aç</p>
                </div>
            </div>
        @endif
    </div>

</div>

<style>
    .chat-msg .msg-sil-btn {
        position: absolute;
        top: 4px;
        right: 4px;
        background: rgba(239, 68, 68, 0.08);
        color: var(--danger);
        border: none;
        border-radius: 4px;
        padding: 3px 6px;
        opacity: 0;
        transition: opacity 0.15s;
        cursor: pointer;
    }
    .chat-msg:hover .msg-sil-btn { opacity: 1; }
    .chat-msg .msg-sil-btn:hover { background: rgba(239, 68, 68, 0.18); }
</style>

{{-- ═══════════════════════════════════════════════════════════════
    Sil formları — chat-window DIŞINDA (iç içe form yasak!)
═══════════════════════════════════════════════════════════════ --}}
@if($anaTalep)
    {{-- Talep silme formu (komple talep + tüm cevaplar) --}}
    <form id="talep-sil-form-{{ $secilenTalepId }}"
          action="{{ route('admin.crm.musteriler.destek.sil', ['id' => $customer->id, 'talepId' => $secilenTalepId]) }}"
          method="POST" style="display:none">
        @csrf @method('DELETE')
    </form>

    {{-- Her cevap için ayrı sil formu --}}
    @foreach($mesajlar as $m)
        @if((int) $m->id !== (int) $secilenTalepId)
            <form id="mesaj-sil-form-{{ $m->id }}"
                  action="{{ route('admin.crm.musteriler.destek.mesaj.sil', ['id' => $customer->id, 'talepId' => $secilenTalepId, 'mesajId' => $m->id]) }}"
                  method="POST" style="display:none">
                @csrf @method('DELETE')
            </form>
        @endif
    @endforeach
@endif

<script>
(function() {
    var b = document.getElementById('chatBox');
    if (b) b.scrollTop = b.scrollHeight;
})();

function talepSil(talepId) {
    if (!confirm('Bu destek talebini ve TÜM cevaplarını silmek istediğine emin misin?\nBu işlem geri alınamaz!')) return;
    var f = document.getElementById('talep-sil-form-' + talepId);
    if (f) f.submit();
}

function mesajSil(talepId, mesajId) {
    if (!confirm('Bu mesajı silmek istediğine emin misin?\nBu işlem geri alınamaz!')) return;
    var f = document.getElementById('mesaj-sil-form-' + mesajId);
    if (f) f.submit();
}
</script>