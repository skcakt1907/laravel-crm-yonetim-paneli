{{-- Tab: Notlar --}}
@include('admin._partials.mention-autocomplete')

<div class="section">
    <div class="section-title">
        <i data-lucide="plus-circle"></i>
        <span>Yeni Not Ekle</span>
    </div>

    <form action="{{ route('admin.crm.musteriler.not-ekle', $customer->id) }}" method="POST" enctype="multipart/form-data">
        @csrf
        <div class="form-grid">
            <div class="form-group full">
                <label class="form-label">Başlık (opsiyonel)</label>
                <input type="text" name="baslik" value="{{ old('baslik') }}" class="form-input" placeholder="Örn: Telefon görüşmesi" maxlength="150">
            </div>

            <div class="form-group full">
                <label class="form-label">İçerik <span class="required">*</span></label>
                <textarea name="icerik" class="form-textarea mention-enabled" rows="5" required placeholder="Not yaz... @kullaniciadi ile bir admini etiketle" autocomplete="off">{{ old('icerik') }}</textarea>
                <div class="form-help">💡 <strong>@kullaniciadi</strong> yazarak admin etiketle — etiketlenenlere mail gönderilir</div>
            </div>

            <div class="form-group full">
                <label class="form-label">Dosya Ekle <span style="color:var(--text-muted);font-size:11px">(PDF, JPG, PNG, DOCX, XLSX)</span></label>
                <input type="file" name="dosya" accept=".pdf,.jpg,.jpeg,.png,.docx,.xlsx" class="form-input">
            </div>
        </div>

        <div style="display:flex;justify-content:flex-end;gap:8px;margin-top:12px">
            <button type="reset" class="btn btn-ghost btn-sm">Temizle</button>
            <button type="submit" class="btn btn-primary btn-sm">
                <i data-lucide="save"></i>
                <span>Not Ekle</span>
            </button>
        </div>
    </form>
</div>

@php
    $notlar = \App\Models\CRM\Note::where('musteri_id', $customer->id)->orderByDesc('id')->get();
    $yoneticilerMap = \Illuminate\Support\Facades\DB::table('yoneticiler')->pluck('adi', 'id');
@endphp

@forelse($notlar as $n)
<div class="section">
    <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:12px;margin-bottom:8px">
        <div>
            <strong style="color:var(--brand);font-size:13px">{{ $yoneticilerMap[$n->olusturan_id] ?? 'Admin' }}</strong>
            <span style="color:var(--text-muted);font-size:11px;margin-left:8px">{{ \Carbon\Carbon::parse($n->created_at)->format('d.m.Y H:i') }}</span>
            @if(!empty($n->baslik))<div style="font-weight:600;margin-top:4px">{{ $n->baslik }}</div>@endif
        </div>
        <div style="display:flex;align-items:center;gap:6px">
            @if(!empty($n->dosya))
                <a href="{{ asset('storage/' . $n->dosya) }}" target="_blank" class="badge badge-brand" style="text-decoration:none">
                    <i data-lucide="paperclip" style="width:11px;height:11px"></i>
                    Dosya
                </a>
            @endif
            <form action="{{ route('admin.crm.musteriler.not-sil', ['id' => $customer->id, 'noteId' => $n->id]) }}" method="POST" onsubmit="return confirm('Not silinsin mi?');">
                @csrf @method('DELETE')
                <button type="submit" class="table-action" style="color:var(--danger)" title="Sil">
                    <i data-lucide="trash-2"></i>
                </button>
            </form>
        </div>
    </div>
    <div style="white-space:pre-wrap;font-size:13.5px;line-height:1.6;color:var(--text)">
        {!! preg_replace('/@([a-zA-Z0-9_\.]+)/', '<span class="badge badge-brand" style="display:inline-flex">@$1</span>', e($n->icerik)) !!}
    </div>
</div>
@empty
<div class="section">
    <div class="empty-state">
        <i data-lucide="sticky-note" class="empty-state-icon"></i>
        <h4>Henüz not yok</h4>
        <p>Yukarıdan ilk notu ekle.</p>
    </div>
</div>
@endforelse