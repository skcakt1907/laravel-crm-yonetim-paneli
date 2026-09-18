@extends('admin._layout')

@section('title', 'Görev Detay')

@section('content')

@include('admin._partials.mention-autocomplete')

<style>
.mention-tag{display:inline-block;background:rgba(184,182,46,.22);color:#8a8a1f;font-weight:700;padding:0 5px;border-radius:5px;text-decoration:none}
body.light .mention-tag{background:rgba(184,182,46,.25);color:#6d6d18}
/* ── Mesaj reaksiyonları ── */
.reak-satir{display:flex;align-items:center;gap:4px;margin-top:4px;position:relative;flex-wrap:wrap;max-width:78%}
/* Görev mesaj — hover aksiyonları (DM tarzı) */
.gmsg-acts{display:none;gap:4px;position:absolute;top:-12px}
.gmsg-row[style*="flex-end"] .gmsg-acts{right:8px}
.gmsg-row[style*="flex-start"] .gmsg-acts{left:8px}
.gmsg-row:hover .gmsg-acts{display:flex}
.gmsg-act{width:26px;height:26px;border-radius:50%;border:1px solid var(--border);background:var(--surface);color:var(--text);cursor:pointer;font-size:12px;line-height:1;display:flex;align-items:center;justify-content:center;box-shadow:0 2px 6px rgba(0,0,0,.18);padding:0}
.gmsg-act:hover{background:var(--brand);color:#1a1a1a}
.gmsg-act.gmsg-sil{color:#fff;background:#ef4444;border-color:#ef4444}
.gmsg-act.gmsg-sil:hover{background:#dc2626;border-color:#dc2626}
.gmsg-quote{border-left:3px solid rgba(0,0,0,.3);background:rgba(0,0,0,.06);padding:4px 8px;border-radius:6px;font-size:12px;margin-bottom:5px;opacity:.85;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
.reak-satir.benim-taraf{justify-content:flex-end}
.reak-chips{display:inline-flex;gap:4px;flex-wrap:wrap}
.reak-chip{display:inline-flex;align-items:center;gap:4px;border:1px solid var(--border,#e5e7eb);background:var(--surface,#fff);border-radius:999px;padding:1px 8px;font-size:13px;line-height:1.7;cursor:pointer;font-family:inherit}
.reak-chip span{font-size:11px;font-weight:700;color:var(--text-secondary,#64748b)}
.reak-chip.benim{background:rgba(184,182,46,.16);border-color:var(--brand,#b8b62e)}
.reak-chip:hover{border-color:var(--brand,#b8b62e)}
.reak-ekle{display:inline-flex;align-items:center;border:1px dashed var(--border,#cbd5e1);background:transparent;border-radius:999px;padding:1px 7px;font-size:12px;cursor:pointer;opacity:.55;transition:opacity .15s;font-family:inherit;color:var(--text-secondary,#64748b)}
.reak-ekle b{font-size:11px;margin-left:1px}
.reak-ekle:hover{opacity:1;border-color:var(--brand,#b8b62e)}
.reak-picker{position:absolute;bottom:calc(100% + 6px);left:0;display:none;gap:2px;background:var(--surface,#fff);border:1px solid var(--border,#e5e7eb);border-radius:999px;padding:4px 6px;box-shadow:0 8px 24px rgba(0,0,0,.14);z-index:60}
.reak-satir.benim-taraf .reak-picker{left:auto;right:0}
.reak-picker.acik{display:inline-flex}
.reak-picker button{border:0;background:transparent;font-size:18px;line-height:1;padding:3px 5px;border-radius:8px;cursor:pointer}
.reak-picker button:hover{background:var(--bg-subtle,#f3f4ef);transform:scale(1.15)}
</style>

@php
    $durumMap = [
        'beklemede'  => ['label' => 'Beklemede',  'class' => 'badge-neutral', 'icon' => '📝'],
        'devam'      => ['label' => 'Devam',      'class' => 'badge-warning', 'icon' => '⏳'],
        'musteri_bekleniyor' => ['label' => 'Müşteri Bekleniyor', 'class' => 'badge-warning', 'icon' => '📞'],
        'tamamlandi' => ['label' => 'Tamamlandı', 'class' => 'badge-success', 'icon' => '✅'],
    ];
    $durum = $durumMap[$task->durum] ?? $durumMap['beklemede'];
    $oncelikMap = [
        'dusuk'  => ['label' => 'Düşük',  'icon' => '🟢'],
        'normal' => ['label' => 'Normal', 'icon' => '🟡'],
        'yuksek' => ['label' => 'Yüksek', 'icon' => '🔴'],
    ];
    $oncelik = $oncelikMap[$task->oncelik ?? 'normal'] ?? $oncelikMap['normal'];
    $benimId = session('admin_id');

    // Reaksiyonlar: route web.php'ye eklenince aktif olur (eklenmeden de sayfa kırılmaz)
    $reakAktif = \Illuminate\Support\Facades\Route::has('admin.crm.gorevler.reaksiyon');
    $izinliReaksiyonlar = ['👍', '❤️', '😂', '😮', '🎉', '✅'];
    $reaksiyonlar = $reaksiyonlar ?? collect();
@endphp

<div class="breadcrumb">
    <a href="{{ route('admin.dashboard') }}">Anasayfa</a>
    <span class="sep">/</span>
    <a href="{{ route('admin.crm.gorevler.index') }}">Görevler</a>
    <span class="sep">/</span>
    <span class="current">{{ Str::limit($task->konu, 40) }}</span>
</div>

<div class="page-header">
    <div>
        <h1 class="page-title">✅ {{ $task->konu }}</h1>
        <div class="page-subtitle">
            <span class="badge {{ $durum['class'] }}">{{ $durum['icon'] }} {{ $durum['label'] }}</span>
            <span style="margin-left:8px">{{ \App\Models\CRM\Task::departmanLabel($task->departman) }}</span>
        </div>
    </div>
    <div class="page-actions">
        <form action="{{ route('admin.crm.gorevler.tamamla', $task->id) }}" method="POST" style="margin:0;display:inline">
            @csrf
            @if($task->durum === 'tamamlandi')
                <button type="submit" class="btn btn-secondary btn-sm" title="Görevi tekrar aç">
                    <span>↩ Tekrar Aç</span>
                </button>
            @else
                <button type="submit" class="btn btn-sm" style="background:var(--success,#10b981);color:#fff" title="Görevi tamamlandı yap">
                    <span>✅ Tamamlandı İşaretle</span>
                </button>
            @endif
        </form>
        <a href="{{ route('admin.crm.gorevler.index') }}" class="btn btn-secondary btn-sm">
            <i data-lucide="arrow-left"></i>
            <span>Listeye Dön</span>
        </a>
        <a href="{{ route('admin.crm.gorevler.edit', $task->id) }}" class="btn btn-primary btn-sm">
            <i data-lucide="edit-2"></i>
            <span>Düzenle</span>
        </a>
    </div>
</div>

<div class="form-grid">
    {{-- SOL: Görev bilgileri + Chat --}}
    <div>
        <div class="section">
            <div class="section-title">
                <i data-lucide="file-text"></i>
                <span>Görev Detayı</span>
            </div>
            <div style="white-space:pre-wrap;line-height:1.7;color:var(--text-secondary)">{{ $task->aciklama ?: 'Açıklama girilmemiş.' }}</div>

            @if($dosyalar->count())
                <div style="margin-top:16px;padding-top:16px;border-top:1px solid var(--border)">
                    <label class="form-label" style="margin-bottom:10px">📎 Ekli Dosyalar ({{ $dosyalar->count() }})</label>
                    <div style="display:flex;flex-direction:column;gap:6px">
                        @foreach($dosyalar as $df)
                            <a href="{{ asset($df->file_path) }}" target="_blank" style="font-weight:600;color:var(--brand);text-decoration:none;font-size:13px">
                                📄 {{ $df->original_name }}
                            </a>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>

        {{-- CHAT --}}
        <div class="section">
            <div class="section-title">
                <i data-lucide="messages-square"></i>
                <span>Ekip Yazışması ({{ $mesajlar->count() }})</span>
                <button type="button" onclick="msgRenkAc(this)" title="Mesaj balon rengi"
                        style="margin-left:auto;background:transparent;border:1px solid var(--border);width:32px;height:32px;border-radius:9px;cursor:pointer;font-size:15px">🎨</button>
            </div>

            <div style="display:flex;flex-direction:column;gap:12px;max-height:480px;overflow-y:auto;padding-right:4px;margin-bottom:16px">
                @forelse($mesajlar as $m)
                    @php
                        $benim  = $m->gonderen_id == $benimId;
                        $yetki  = $benim || (int) session('admin_rol') === 1;
                        $yanitM = !empty($m->yanit_id) ? $mesajlar->firstWhere('id', $m->yanit_id) : null;
                    @endphp
                    <div class="gmsg-row" data-mid="{{ $m->id }}" data-kim="{{ $benim ? 'Sen' : ($m->gonderen_adi ?: 'Admin') }}" data-mesaj="{{ $m->mesaj }}"
                         style="display:flex;flex-direction:column;align-items:{{ $benim ? 'flex-end' : 'flex-start' }}">
                        <div class="gmsg-bubble" style="position:relative;max-width:78%;padding:10px 14px;border-radius:14px;
                                    background:{{ $benim ? 'var(--msg-ben,#d4e9ff)' : 'var(--bg-subtle)' }};
                                    color:{{ $benim ? 'var(--msg-ben-text,#0f2e4d)' : 'var(--text)' }};
                                    border:1px solid var(--border)">
                            <div style="font-size:11px;font-weight:700;opacity:.8;margin-bottom:3px">
                                {{ $m->gonderen_adi ?: 'Admin' }}
                            </div>
                            @if($yanitM)
                            <div class="gmsg-quote">{{ ($yanitM->gonderen_id == $benimId ? 'Sen' : ($yanitM->gonderen_adi ?: 'Admin')) }}: {{ \Illuminate\Support\Str::limit($yanitM->mesaj, 80) }}</div>
                            @endif
                            @if(!empty($m->mesaj))
                            <div class="gmsg-text" style="white-space:pre-wrap;font-size:13.5px;line-height:1.5">{!! preg_replace('/@([A-Za-z0-9_\.ğüşöçıİĞÜŞÖÇ]+)/u', '<span class="mention-tag">@$1</span>', e($m->mesaj)) !!}</div>
                            @endif
                            @if(!empty($m->dosya ?? null))
                                @php $mDosya = $m->dosya; $mUz = strtolower(pathinfo($mDosya, PATHINFO_EXTENSION)); $mResim = in_array($mUz, ['jpg','jpeg','png','gif','webp','bmp','svg']); @endphp
                                <div style="margin-top:6px">
                                    @if($mResim)
                                        <a href="{{ asset($mDosya) }}" target="_blank">
                                            <img src="{{ asset($mDosya) }}" alt="{{ $m->dosya_adi ?? 'dosya' }}" loading="lazy" style="max-width:220px;max-height:220px;border-radius:10px;border:1px solid var(--border);display:block">
                                        </a>
                                    @else
                                        <a href="{{ asset($mDosya) }}" target="_blank" download style="display:inline-flex;align-items:center;gap:7px;padding:8px 12px;background:var(--bg-subtle);border:1px solid var(--border);border-radius:10px;text-decoration:none;color:var(--text);font-size:12.5px;font-weight:600">
                                            📄 {{ $m->dosya_adi ?? 'Dosya' }}
                                        </a>
                                    @endif
                                </div>
                            @endif
                            <div style="font-size:10px;opacity:.6;margin-top:4px;text-align:right">
                                {{ \Carbon\Carbon::parse($m->created_at)->format('d.m.Y H:i') }}@if(!empty($m->duzenlendi)) <span style="opacity:.75">(düzenlendi)</span>@endif
                            </div>
                            @if($benim)
                            <div style="margin-top:2px;text-align:right;font-size:10.5px;color:var(--msg-ben-text,#0f2e4d)">
                                @if(!empty($m->okundu))
                                    <span style="opacity:1;font-weight:800" title="Karşı taraf gördü">✓✓ Görüldü</span>
                                @else
                                    <span style="opacity:.55;font-weight:600" title="Gönderildi, henüz görülmedi">✓ Gönderildi</span>
                                @endif
                            </div>
                            @endif
                            <div class="gmsg-acts">
                                <button type="button" class="gmsg-act" title="Cevapla" onclick="gorevCevapla({{ $m->id }})">↩</button>
                                @if($yetki)
                                <button type="button" class="gmsg-act" title="Düzenle" onclick="gorevDuzenle({{ $m->id }})">✏️</button>
                                <button type="button" class="gmsg-act gmsg-sil" title="Sil" onclick="gorevSil({{ $m->id }})">🗑️</button>
                                @endif
                            </div>
                        </div>
                        @if($reakAktif)
                        @php $mReaks = $reaksiyonlar->get($m->id, collect())->groupBy('emoji'); @endphp
                        <div class="reak-satir{{ $benim ? ' benim-taraf' : '' }}" data-mesaj="{{ $m->id }}">
                            <span class="reak-chips" data-chips>
                                @foreach($mReaks as $emoji => $grup)
                                    @php
                                        $benVar  = $grup->contains(function ($r) use ($benimId) { return (int) $r->yonetici_id === (int) $benimId; });
                                        $kisiler = $grup->pluck('yonetici_adi')->filter()->implode(', ');
                                    @endphp
                                    <button type="button" class="reak-chip{{ $benVar ? ' benim' : '' }}" data-reak-chip data-emoji="{{ $emoji }}" title="{{ $kisiler }}">{{ $emoji }} <span>{{ $grup->count() }}</span></button>
                                @endforeach
                            </span>
                            <button type="button" class="reak-ekle" data-reak-ac title="Reaksiyon ekle"><span>🙂</span><b>+</b></button>
                            <span class="reak-picker" data-picker>
                                @foreach($izinliReaksiyonlar as $re)
                                    <button type="button" data-reak-emoji="{{ $re }}" title="{{ $re }} bırak">{{ $re }}</button>
                                @endforeach
                            </span>
                        </div>
                        @endif
                    </div>
                @empty
                    <div style="text-align:center;color:var(--text-muted);font-size:13px;padding:24px 0">
                        Henüz mesaj yok. İlk mesajı sen yaz 👇
                    </div>
                @endforelse
            </div>

            <form action="{{ route('admin.crm.gorevler.mesaj', $task->id) }}" method="POST" enctype="multipart/form-data" id="gorevMsgForm">
                @csrf
                <input type="hidden" name="yanit_id" id="gorevYanitId">
                <div id="gorevYanitBar" style="display:none;align-items:center;gap:8px;padding:8px 12px;background:var(--brand-soft);border:1px solid var(--border);border-radius:10px;margin-bottom:8px;font-size:12.5px">
                    <span style="opacity:.7">↩</span>
                    <span id="gorevYanitTxt" style="flex:1;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;color:var(--text)"></span>
                    <button type="button" onclick="gorevCevapIptal()" style="border:none;background:none;cursor:pointer;font-size:17px;color:var(--text)">×</button>
                </div>
                <div class="form-group" style="margin-bottom:10px">
                    <textarea name="mesaj" id="gorevMsgInput" rows="3" class="form-textarea mention-enabled" autocomplete="off"
                              placeholder="Ekip arkadaşlarına mesaj yaz... @kullaniciadi ile bir admini etiketle"></textarea>
                    <small class="form-help">💡 <strong>@</strong> yazıp bir admin seç — etiketlenen kişiye ayrıca e-posta gider.</small>
                </div>
                <div style="display:flex;align-items:center;gap:10px">
                    <input type="file" name="dosya" id="gorevMsgDosya" style="display:none" onchange="gorevDosyaSec(this)">
                    <button type="button" onclick="document.getElementById('gorevMsgDosya').click()" title="Dosya ekle"
                            style="border:1px solid var(--border);background:transparent;width:40px;height:40px;border-radius:10px;cursor:pointer;display:flex;align-items:center;justify-content:center;color:var(--text);flex:0 0 auto">
                        <i data-lucide="paperclip"></i>
                    </button>
                    <span id="gorevDosyaAd" style="flex:1;font-size:12.5px;color:var(--text-muted);overflow:hidden;text-overflow:ellipsis;white-space:nowrap"></span>
                    <button type="submit" class="btn btn-primary">
                        <i data-lucide="send"></i>
                        <span>Gönder</span>
                    </button>
                </div>
            </form>
            <script>
            function gorevDosyaSec(inp){
                var el = document.getElementById('gorevDosyaAd');
                if (inp.files && inp.files.length){
                    el.innerHTML = '\uD83D\uDCCE ' + inp.files[0].name.replace(/[<>&]/g, function(x){return {'<':'&lt;','>':'&gt;','&':'&amp;'}[x];}) +
                        ' <button type="button" onclick="gorevDosyaIptal()" style="border:none;background:none;color:#dc2626;cursor:pointer;font-size:14px;font-weight:800;vertical-align:middle">x</button>';
                } else { el.textContent = ''; }
            }
            function gorevDosyaIptal(){
                var inp = document.getElementById('gorevMsgDosya');
                if (inp) inp.value = '';
                document.getElementById('gorevDosyaAd').textContent = '';
            }
            (function(){
                var f = document.getElementById('gorevMsgForm');
                if (!f) return;
                f.addEventListener('submit', function(e){
                    var t = (document.getElementById('gorevMsgInput').value || '').trim();
                    var d = document.getElementById('gorevMsgDosya');
                    var has = d && d.files && d.files.length;
                    if (!t && !has){ e.preventDefault(); alert('Bir mesaj yaz ya da bir dosya ekle.'); }
                });
            })();
            </script>
        </div>
    </div>

    {{-- SAĞ: Özet --}}
    <div>
        <div class="section">
            <div class="section-title">
                <i data-lucide="info"></i>
                <span>Bilgiler</span>
            </div>

            <div style="display:flex;flex-direction:column;gap:14px;font-size:13px">
                <div>
                    <div style="color:var(--text-muted);font-size:11px;margin-bottom:2px">Departman</div>
                    <div style="font-weight:600">{{ \App\Models\CRM\Task::departmanLabel($task->departman) }}</div>
                </div>
                <div>
                    <div style="color:var(--text-muted);font-size:11px;margin-bottom:2px">Durum</div>
                    <span class="badge {{ $durum['class'] }}">{{ $durum['icon'] }} {{ $durum['label'] }}</span>
                </div>
                <div>
                    <div style="color:var(--text-muted);font-size:11px;margin-bottom:2px">Öncelik</div>
                    <div style="font-weight:600">{{ $oncelik['icon'] }} {{ $oncelik['label'] }}</div>
                </div>
                <div>
                    <div style="color:var(--text-muted);font-size:11px;margin-bottom:2px">Oluşturan</div>
                    <div style="font-weight:600">{{ $task->olusturan->adi ?? $task->olusturan_adi ?? '—' }}</div>
                </div>
                <div>
                    @php
                        $atananlar = \Illuminate\Support\Facades\Schema::hasTable('crm_task_members')
                            ? $task->atananlar
                            : collect(array_filter([$task->atanan]));
                    @endphp
                    <div style="color:var(--text-muted);font-size:11px;margin-bottom:2px">
                        {{ $atananlar->count() > 1 ? 'Sorumlular (Atananlar)' : 'Sorumlu (Atanan)' }}
                    </div>
                    <div style="font-weight:600">
                        @forelse($atananlar as $i => $a)
                            {{-- Birincil sorumlu (crm_tasks.atanan_id) yildizla isaretli --}}
                            <span title="{{ (int) $a->id === (int) $task->atanan_id ? 'Birincil sorumlu' : 'Atanan' }}">{{ (int) $a->id === (int) $task->atanan_id ? '★ ' : '' }}{{ $a->adi ?: $a->kullaniciadi }}</span>@if(!$loop->last)<span style="color:var(--text-muted)">, </span>@endif
                        @empty
                            <span style="color:var(--text-muted)">Atanmamış</span>
                        @endforelse
                    </div>
                </div>
                <div>
                    <div style="color:var(--text-muted);font-size:11px;margin-bottom:2px">Müşteri</div>
                    <div style="font-weight:600">
                        @if($task->musteri)
                            👤 {{ $task->musteri->adi }}
                        @else
                            <span style="color:var(--text-muted)">—</span>
                        @endif
                    </div>
                </div>
                <div>
                    <div style="color:var(--text-muted);font-size:11px;margin-bottom:2px">Son Tarih</div>
                    <div style="font-weight:600">
                        {{ $task->son_tarih ? \Carbon\Carbon::parse($task->son_tarih)->format('d.m.Y') : '—' }}
                    </div>
                </div>
                @if($task->tamamlandi_at)
                    <div>
                        <div style="color:var(--text-muted);font-size:11px;margin-bottom:2px">Tamamlandı</div>
                        <div style="font-weight:600;color:var(--success)">
                            ✅ {{ \Carbon\Carbon::parse($task->tamamlandi_at)->format('d.m.Y H:i') }}
                        </div>
                    </div>
                @endif
            </div>
        </div>

        {{-- GÖREVİ ATA --}}
        <div class="section">
            <div class="section-title">
                <i data-lucide="user-check"></i>
                <span>Görevi Ata</span>
            </div>
            <form action="{{ route('admin.crm.gorevler.ata', $task->id) }}" method="POST">
                @csrf
                @php
                    // Halihazirda atanmis kisiler (coklu). Tablo yoksa tekli atanana duser.
                    $seciliAtananlar = \Illuminate\Support\Facades\Schema::hasTable('crm_task_members')
                        ? $task->atananlar()->pluck('yoneticiler.id')->map(fn ($x) => (int) $x)->all()
                        : array_filter([(int) ($task->atanan_id ?? 0)]);
                @endphp
                <div class="form-group" style="margin-bottom:10px">
                    <label class="form-label">Sorumlular (atananlar)</label>
                    <div class="gorev-atama-liste">
                        @foreach($yoneticiler as $y)
                            <label class="gorev-atama-satir">
                                <input type="checkbox" name="atanan_ids[]" value="{{ $y->id }}"
                                       @checked(in_array((int) $y->id, $seciliAtananlar, true))>
                                <span>{{ $y->adi ?: $y->kullaniciadi }}</span>
                            </label>
                        @endforeach
                    </div>
                    <small class="form-help">
                        Birden fazla kişi seçebilirsiniz; <strong>ilk seçilen birincil sorumlu</strong> olur.
                        Yalnızca yeni eklenen kişilere bildirim gider.
                    </small>
                </div>
                <style>
                    .gorev-atama-liste {
                        max-height: 190px; overflow-y: auto;
                        border: 1px solid var(--border, #e5e7eb); border-radius: 10px; padding: 6px;
                    }
                    .gorev-atama-satir {
                        display: flex; align-items: center; gap: 8px;
                        padding: 6px 8px; border-radius: 8px; cursor: pointer; font-size: 14px;
                    }
                    .gorev-atama-satir:hover { background: rgba(184,182,46,.12); }
                    .gorev-atama-satir input { cursor: pointer; }
                </style>
                <button type="submit" class="btn btn-primary" style="width:100%">
                    <i data-lucide="user-check"></i>
                    <span>Görevi Ata</span>
                </button>
            </form>
        </div>

        {{-- KANBAN'A TAŞI --}}
        <div class="section">
            <div class="section-title">
                <i data-lucide="trello"></i>
                <span>Kanban'a Taşı</span>
            </div>

            @if(!empty($task->kanban_card_id))
                <div style="padding:10px 12px;background:var(--success-soft, rgba(16,185,129,.1));border:1px solid rgba(16,185,129,.25);border-radius:var(--radius-md);font-size:12.5px;color:var(--success);margin-bottom:10px">
                    ✅ Bu görev Kanban'a kart olarak aktarıldı.
                </div>
            @endif

            @if($kanbanBoards->isEmpty())
                <div style="font-size:12.5px;color:var(--text-muted)">
                    Henüz erişebileceğin bir Kanban panosu yok.
                    <a href="{{ route('admin.crm.kanban.create') }}" style="color:var(--brand)">Pano oluştur →</a>
                </div>
            @else
                <form action="{{ route('admin.crm.gorevler.kanbana', $task->id) }}" method="POST">
                    @csrf
                    <div class="form-group" style="margin-bottom:10px">
                        <label class="form-label">Hangi panoya?</label>
                        <select name="board_id" required class="form-select">
                            <option value="">— Pano seç —</option>
                            @foreach($kanbanBoards as $b)
                                <option value="{{ $b->id }}">{{ $b->adi }}</option>
                            @endforeach
                        </select>
                        <small class="form-help">Görev, seçilen panonun ilk listesine kart olarak eklenir (departman etiket olur).</small>
                    </div>
                    <button type="submit" class="btn btn-primary" style="width:100%"
                            @if(!empty($task->kanban_card_id)) onclick="return confirm('Bu görev zaten taşınmış. Tekrar kart oluşturulsun mu?')" @endif>
                        <i data-lucide="arrow-right-circle"></i>
                        <span>{{ !empty($task->kanban_card_id) ? 'Tekrar Taşı' : "Kanban'a Taşı" }}</span>
                    </button>
                </form>
            @endif
        </div>
    </div>
</div>

@if($reakAktif)
<script>
(function(){
    var REAK_URL  = "{{ route('admin.crm.gorevler.reaksiyon', $task->id) }}";
    var REAK_CSRF = "{{ csrf_token() }}";

    function escAttr(s){
        return String(s == null ? '' : s)
            .replace(/&/g,'&amp;').replace(/"/g,'&quot;')
            .replace(/</g,'&lt;').replace(/>/g,'&gt;');
    }

    function pickerKapatHepsi(){
        document.querySelectorAll('.reak-picker.acik').forEach(function(p){ p.classList.remove('acik'); });
    }

    // Sunucudan gelen guncel ozeti chip'lere bas
    function chipsBas(mesajId, reaks){
        var satir = document.querySelector('.reak-satir[data-mesaj="'+mesajId+'"]');
        if (!satir) return;
        var kutu = satir.querySelector('[data-chips]');
        var html = '';
        Object.keys(reaks || {}).forEach(function(emoji){
            var r = reaks[emoji];
            html += '<button type="button" class="reak-chip'+(r.benVar ? ' benim' : '')+'"'
                  + ' data-reak-chip data-emoji="'+escAttr(emoji)+'"'
                  + ' title="'+escAttr((r.kisiler || []).join(', '))+'">'
                  + emoji+' <span>'+r.adet+'</span></button>';
        });
        kutu.innerHTML = html;
    }

    function gonder(mesajId, emoji){
        if (!mesajId || !emoji) return;
        var fd = new FormData();
        fd.append('_token', REAK_CSRF);
        fd.append('mesaj_id', mesajId);
        fd.append('emoji', emoji);
        fetch(REAK_URL, {
            method: 'POST',
            body: fd,
            headers: {'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json'},
            credentials: 'same-origin'
        })
        .then(function(r){ return r.json(); })
        .then(function(d){ if (d && d.ok) chipsBas(d.mesaj_id, d.reaksiyonlar); })
        .catch(function(){});
    }

    document.addEventListener('click', function(e){
        // 🙂+ butonu -> bu mesajin seçicisini ac/kapat
        var acBtn = e.target.closest('[data-reak-ac]');
        if (acBtn) {
            var picker = acBtn.closest('.reak-satir').querySelector('[data-picker]');
            var acikti = picker.classList.contains('acik');
            pickerKapatHepsi();
            if (!acikti) picker.classList.add('acik');
            return;
        }
        // Seçiciden emoji sec
        var emojiBtn = e.target.closest('[data-reak-emoji]');
        if (emojiBtn) {
            var satir = emojiBtn.closest('.reak-satir');
            gonder(satir.dataset.mesaj, emojiBtn.dataset.reakEmoji);
            pickerKapatHepsi();
            return;
        }
        // Mevcut chip'e tikla -> ayni emojiyi ekle/kaldir (toggle)
        var chip = e.target.closest('[data-reak-chip]');
        if (chip) {
            var satir2 = chip.closest('.reak-satir');
            gonder(satir2.dataset.mesaj, chip.dataset.emoji);
            return;
        }
        // Disari tiklayinca seçicileri kapat
        pickerKapatHepsi();
    });
})();
</script>
@endif

<script>
/* ===== Mesaj balon rengi (bağımsız kopya — guard'lı) ===== */
(function(){
    if (window.__msgRenk) return; window.__msgRenk = true;
    var KEY='msgBenRenk', DEF='#d4e9ff';
    var PRESET=[['#d4e9ff','Soft Mavi'],['#d8f3d0','Soft Yeşil'],['#e7ddff','Soft Mor'],['#ffdce8','Soft Pembe'],['#e9edf2','Gri'],['#eef0c9','Sarı'],['#d0f0ee','Turkuaz'],['#ffe6cc','Şeftali']];
    function okunur(hex){ try{var r=parseInt(hex.substr(1,2),16),g=parseInt(hex.substr(3,2),16),b=parseInt(hex.substr(5,2),16); return (0.299*r+0.587*g+0.114*b)>140?'#0f2e4d':'#ffffff';}catch(e){return '#0f2e4d';} }
    function uygula(c){ var d=document.documentElement.style; d.setProperty('--msg-ben',c); d.setProperty('--msg-ben-text', okunur(c)); }
    window.msgRenkUygula=uygula;
    uygula(localStorage.getItem(KEY) || DEF);
    window.msgRenkSec=function(c){ localStorage.setItem(KEY,c); uygula(c); var i=document.querySelector('#msg-renk-pop input[type=color]'); if(i)i.value=c; document.querySelectorAll('#msg-renk-pop [data-sw]').forEach(function(b){ b.style.borderColor=(b.dataset.sw.toLowerCase()===c.toLowerCase()?'#333':'#fff'); }); };
    window.msgRenkAc=function(btn){
        var old=document.getElementById('msg-renk-pop'); if(old){ old.remove(); return; }
        var cur=localStorage.getItem(KEY)||DEF;
        var pop=document.createElement('div'); pop.id='msg-renk-pop';
        pop.style.cssText='position:fixed;z-index:100050;background:#fff;border:1px solid #ddd;border-radius:12px;box-shadow:0 10px 30px rgba(0,0,0,.22);padding:12px;width:210px';
        var sw=PRESET.map(function(p){return '<button type="button" data-sw="'+p[0]+'" onclick="msgRenkSec(\''+p[0]+'\')" title="'+p[1]+'" style="width:34px;height:34px;border-radius:50%;border:2px solid '+(p[0].toLowerCase()===cur.toLowerCase()?'#333':'#fff')+';background:'+p[0]+';cursor:pointer;box-shadow:0 0 0 1px #e2e2e2"></button>';}).join('');
        pop.innerHTML='<div style="font-size:12px;font-weight:700;color:#333;margin-bottom:9px">🎨 Mesaj Balon Rengi</div>'
            +'<div style="display:flex;flex-wrap:wrap;gap:8px;margin-bottom:10px">'+sw+'</div>'
            +'<label style="display:flex;align-items:center;gap:8px;font-size:12px;color:#444">Özel renk: <input type="color" value="'+cur+'" oninput="msgRenkSec(this.value)" style="width:40px;height:28px;border:1px solid #ddd;border-radius:6px;background:none;cursor:pointer"></label>';
        document.body.appendChild(pop);
        var r=btn.getBoundingClientRect();
        pop.style.top=Math.min(r.bottom+6, window.innerHeight-180)+'px';
        pop.style.left=Math.max(8, Math.min(r.left, window.innerWidth-224))+'px';
        setTimeout(function(){ document.addEventListener('click', function kapat(e){ if(!pop.contains(e.target) && e.target!==btn){ pop.remove(); document.removeEventListener('click',kapat); } }); },60);
    };
})();

/* ===== Görev mesaj: Cevapla / Düzenle / Sil ===== */
(function(){
    var CSRF = document.querySelector('meta[name="csrf-token"]')?.content || '';
    var BASE = @json(url('admin/crm/gorevler/mesaj'));

    function row(id){ return document.querySelector('.gmsg-row[data-mid="'+id+'"]'); }
    function gizliFormGonder(action, alanlar){
        var f=document.createElement('form'); f.method='POST'; f.action=action; f.style.display='none';
        function ekle(n,v){ var i=document.createElement('input'); i.type='hidden'; i.name=n; i.value=v; f.appendChild(i); }
        ekle('_token', CSRF);
        Object.keys(alanlar||{}).forEach(function(k){ ekle(k, alanlar[k]); });
        document.body.appendChild(f); f.submit();
    }

    window.gorevCevapla=function(id){
        var r=row(id); if(!r) return;
        document.getElementById('gorevYanitId').value=id;
        var bar=document.getElementById('gorevYanitBar');
        var t=(r.dataset.mesaj||'').replace(/\s+/g,' ').trim();
        document.getElementById('gorevYanitTxt').textContent=(r.dataset.kim||'')+': '+(t.length>70?t.slice(0,70)+'…':t);
        bar.style.display='flex';
        var inp=document.getElementById('gorevMsgInput'); if(inp){ inp.focus(); }
    };
    window.gorevCevapIptal=function(){
        document.getElementById('gorevYanitId').value='';
        document.getElementById('gorevYanitBar').style.display='none';
    };
    window.gorevDuzenle=function(id){
        var r=row(id); if(!r) return;
        var mevcut=r.dataset.mesaj||'';
        var yeni=prompt('Mesajı düzenle:', mevcut);
        if(yeni===null) return; yeni=yeni.trim();
        if(!yeni){ alert('Boş mesaj olamaz.'); return; }
        gizliFormGonder(BASE+'/'+id+'/duzenle', { mesaj: yeni });
    };
    window.gorevSil=function(id){
        if(!confirm('Bu mesajı silmek istediğine emin misin?')) return;
        gizliFormGonder(BASE+'/'+id, { _method: 'DELETE' });
    };
})();
</script>

@endsection