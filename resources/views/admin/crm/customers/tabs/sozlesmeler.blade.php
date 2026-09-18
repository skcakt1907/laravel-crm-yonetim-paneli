{{-- Tab: Sözleşmeler (müşteriye ait) --}}
@php
    use Illuminate\Support\Facades\Schema;
    use Illuminate\Support\Facades\DB;
    use Illuminate\Support\Facades\Route;

    $sozlesmeler = collect();
    if (Schema::hasTable('crm_sozlesmeler')) {
        $sozlesmeler = DB::table('crm_sozlesmeler as s')
            ->leftJoin('crm_sozlesme_kategorileri as k', 'k.id', '=', 's.kategori_id')
            ->where('s.musteri_id', $customer->id)
            ->orderByDesc('s.id')
            ->select('s.*', 'k.ad as kategori_ad', 'k.renk as kategori_renk')
            ->get();
    }

    $durumBadge = [
        'taslak'    => ['badge-neutral', '📝 Taslak'],
        'aktif'     => ['badge-brand',   '✅ Aktif'],
        'imzalandi' => ['badge-success', '✍️ İmzalandı'],
        'iptal'     => ['badge-danger',  '✗ İptal'],
    ];
@endphp

<div class="section" style="padding:0">
    <div style="padding:16px 20px;border-bottom:1px solid var(--border);display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:8px">
        <h4 style="font-size:14px;font-weight:600">
            📄 Sözleşmeler
            <span class="badge badge-neutral" style="margin-left:6px">{{ $sozlesmeler->count() }}</span>
        </h4>
        @if(Route::has('admin.crm.sozlesmeler.create'))
        <a href="{{ route('admin.crm.sozlesmeler.create', ['musteri_id' => $customer->id]) }}" class="btn btn-primary btn-sm">
            <i data-lucide="plus"></i>
            <span>Yeni Sözleşme</span>
        </a>
        @endif
    </div>

    <div class="table-scroll">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Sözleşme</th>
                    <th>Kategori</th>
                    <th>Tutar</th>
                    <th>Durum</th>
                    <th>Tarih</th>
                    <th class="text-right">İşlem</th>
                </tr>
            </thead>
            <tbody>
                @forelse($sozlesmeler as $s)
                    @php
                        $db = $durumBadge[$s->durum ?? 'taslak'] ?? ['badge-neutral', $s->durum ?? '—'];
                    @endphp
                    <tr>
                        <td>
                            @if(Route::has('admin.crm.sozlesmeler.edit'))
                                <a href="{{ route('admin.crm.sozlesmeler.edit', $s->id) }}" style="color:var(--brand);text-decoration:none;font-weight:700">{{ $s->baslik ?? '—' }}</a>
                            @else
                                <strong>{{ $s->baslik ?? '—' }}</strong>
                            @endif
                            <div style="font-size:11px;color:var(--text-muted)">{{ $s->sozlesme_no ?? '' }}</div>
                        </td>
                        <td>
                            @if(!empty($s->kategori_ad))
                                <span class="badge" style="background:{{ $s->kategori_renk ?? '#6366f1' }}1a;color:{{ $s->kategori_renk ?? '#6366f1' }}">{{ $s->kategori_ad }}</span>
                            @else
                                <span style="color:var(--text-muted)">—</span>
                            @endif
                        </td>
                        <td>
                            @if(!is_null($s->tutar))
                                <strong style="color:var(--brand)">₺{{ number_format((float) $s->tutar, 2, ',', '.') }}</strong>
                            @else
                                <span style="color:var(--text-muted)">—</span>
                            @endif
                        </td>
                        <td><span class="badge {{ $db[0] }}">{{ $db[1] }}</span></td>
                        <td style="font-size:12px;color:var(--text-muted)">
                            {{ !empty($s->tarih) ? \Carbon\Carbon::parse($s->tarih)->format('d.m.Y') : '—' }}
                        </td>
                        <td class="text-right">
                            <div style="display:inline-flex;gap:4px;align-items:center;justify-content:flex-end">
                                @if(Route::has('admin.crm.sozlesmeler.yazdir'))
                                    <a href="{{ route('admin.crm.sozlesmeler.yazdir', $s->id) }}" target="_blank" class="table-action" title="Yazdır / PDF">
                                        <i data-lucide="printer"></i>
                                    </a>
                                @endif
                                @if(Route::has('admin.crm.sozlesmeler.word'))
                                    <a href="{{ route('admin.crm.sozlesmeler.word', $s->id) }}" class="table-action" title="Word indir">
                                        <i data-lucide="file-text"></i>
                                    </a>
                                @endif
                                @if(Route::has('admin.crm.sozlesmeler.edit'))
                                    <a href="{{ route('admin.crm.sozlesmeler.edit', $s->id) }}" class="table-action" style="color:var(--brand-dark)" title="Düzenle">
                                        <i data-lucide="pencil"></i>
                                    </a>
                                @endif
                                @if(Route::has('admin.crm.sozlesmeler.destroy'))
                                    <form action="{{ route('admin.crm.sozlesmeler.destroy', $s->id) }}" method="POST" style="display:inline" onsubmit="return confirm('Sözleşme silinsin mi?');">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="table-action" style="color:var(--danger)" title="Sil">
                                            <i data-lucide="trash-2"></i>
                                        </button>
                                    </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6">
                            <div class="empty-state" style="padding:32px 0">
                                <i data-lucide="file-text" class="empty-state-icon"></i>
                                <h4>Henüz sözleşme yok</h4>
                                <p>Bu müşteri için yukarıdaki "Yeni Sözleşme" butonuyla başla.</p>
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>