{{-- Müşteri Randevuları sekmesi — randevu modülünden (crm_musteri_id bağıyla) --}}
@php
    $musteriRandevulari = collect();
    if (\Illuminate\Support\Facades\Schema::hasTable('randevular')) {
        try {
            $musteriRandevulari = \Illuminate\Support\Facades\DB::table('randevular as r')
                ->leftJoin('randevu_calisanlar as c', 'c.id', '=', 'r.calisan_id')
                ->leftJoin('randevu_hizmetler as h', 'h.id', '=', 'r.hizmet_id')
                ->where('r.crm_musteri_id', $customer->id)
                ->select('r.*', 'c.ad as calisan_ad', 'h.ad as lokasyon_ad')
                ->orderByDesc('r.baslangic')
                ->limit(200)
                ->get();
        } catch (\Throwable $e) {}
    }
    $rdvDurumMap = [
        'beklemede' => ['label' => 'Beklemede', 'class' => 'badge-neutral', 'icon' => '📝'],
        'onaylandi' => ['label' => 'Onaylandı', 'class' => 'badge-info',    'icon' => '👍'],
        'geldi'     => ['label' => 'Geldi',     'class' => 'badge-success', 'icon' => '✅'],
        'iptal'     => ['label' => 'İptal',     'class' => 'badge-danger',  'icon' => '✖'],
    ];
@endphp

<div class="section">
    <div class="section-title" style="display:flex;align-items:center;justify-content:space-between">
        <span>📅 Randevular ({{ count($musteriRandevulari) }})</span>
        @if(Route::has('admin.randevu.randevular'))
            <a href="{{ route('admin.randevu.randevular') }}" class="btn btn-primary btn-sm">
                <i data-lucide="plus"></i> <span>Randevu Takvimi</span>
            </a>
        @endif
    </div>

    @if(count($musteriRandevulari) === 0)
        <div class="empty-state">
            <i data-lucide="calendar" class="empty-state-icon"></i>
            <h4>Henüz randevu yok</h4>
            <p>Bu müşteri için randevu takviminden randevu oluşturabilirsin.</p>
        </div>
    @else
        <div class="table-wrap">
            <div class="table-scroll">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Tarih / Saat</th>
                            <th>Lokasyon</th>
                            <th>Çalışan</th>
                            <th>Süre</th>
                            <th>Durum</th>
                            <th>Oluşturan</th>
                            <th>Not</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($musteriRandevulari as $rdv)
                            @php
                                $rd = $rdvDurumMap[$rdv->durum ?? 'beklemede'] ?? $rdvDurumMap['beklemede'];
                                $bas = \Carbon\Carbon::parse($rdv->baslangic);
                                $sure = !empty($rdv->bitis) ? $bas->diffInMinutes(\Carbon\Carbon::parse($rdv->bitis)) : null;
                                $gecmis = $bas->isPast();
                            @endphp
                            <tr @if($gecmis) style="opacity:.7" @endif>
                                <td style="white-space:nowrap">
                                    <div style="font-weight:600">{{ $bas->format('d.m.Y') }}</div>
                                    <div style="font-size:12px;color:var(--text-muted)">{{ $bas->format('H:i') }}</div>
                                </td>
                                <td style="font-size:13px">{{ $rdv->lokasyon_ad ?? '—' }}</td>
                                <td style="font-size:13px">{{ $rdv->calisan_ad ?? '—' }}</td>
                                <td style="font-size:13px">{{ $sure ? $sure . ' dk' : '—' }}</td>
                                <td><span class="badge {{ $rd['class'] }}">{{ $rd['icon'] }} {{ $rd['label'] }}</span></td>
                                <td style="font-size:13px;color:var(--text-secondary)">{{ $rdv->olusturan_adi ?? '—' }}</td>
                                <td style="font-size:12px;color:var(--text-muted);max-width:220px">{{ \Illuminate\Support\Str::limit($rdv->notlar ?? '', 60) ?: '—' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif
</div>