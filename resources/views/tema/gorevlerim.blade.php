@extends('layouts.panel')

@section('page_title', 'Görevlerim')

@section('panel_content')
<div class="col-md-12 border-left-3 main-content">
    <div class="title-area mb-4">
        <h5 class="title">
            <i class="fa fa-tasks"></i>
            {{ __('messages.my_tasks') }}
        </h5>
    </div>

    @if($gorevler->isEmpty())
        <div class="alert alert-info" style="border-radius:8px">
            <i class="mdi mdi-information-outline"></i>
            {{ __('messages.no_tasks') }}
        </div>
    @else
        <table id="datatable" class="table table-bordered table-striped">
            <thead>
                <tr>
                    <th scope="col" class="text-left">Görev</th>
                    <th scope="col" class="text-center" style="width:130px;">{{ __('messages.due_date_label') }}</th>
                    <th scope="col" class="text-center" style="width:140px;">{{ __('messages.table_status') }}</th>
                </tr>
            </thead>
            <tbody>
                @php
                    $durumlar = [
                        'beklemede'  => ['Beklemede', 'badge-warning', '#f59e0b'],
                        'devam'      => ['Devam Ediyor', 'badge-info', '#3b82f6'],
                        'tamamlandi' => ['Tamamlandı', 'badge-success', '#10b981'],
                    ];
                @endphp
                @foreach($gorevler as $g)
                    @php
                        $d = $durumlar[$g->durum ?? 'beklemede'] ?? $durumlar['beklemede'];
                        $sonTarih = null;
                        if (!empty($g->son_tarih)) {
                            try { $sonTarih = \Carbon\Carbon::parse($g->son_tarih); } catch (\Throwable $e) {}
                        }
                    @endphp
                    <tr>
                        <td class="text-left">
                            <strong>{{ $g->konu ?? $g->baslik ?? '—' }}</strong>
                            @if(!empty($g->aciklama))
                                <div style="font-size:12px;color:#6b7280;margin-top:3px">
                                    {{ \Illuminate\Support\Str::limit($g->aciklama, 140) }}
                                </div>
                            @endif
                        </td>
                        <td class="text-center">{{ $sonTarih ? $sonTarih->format('d.m.Y') : '—' }}</td>
                        <td class="text-center">
                            <span style="display:inline-block;padding:3px 10px;border-radius:12px;font-size:11.5px;font-weight:600;color:#fff;background:{{ $d[2] }}">
                                {{ $d[0] }}
                            </span>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif
</div>
@endsection