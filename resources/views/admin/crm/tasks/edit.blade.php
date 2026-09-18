@extends('admin._layout')

@section('title', 'Görev Düzenle')

@section('content')

<div class="breadcrumb">
    <a href="{{ route('admin.dashboard') }}">Anasayfa</a>
    <span class="sep">/</span>
    <a href="{{ route('admin.crm.gorevler.index') }}">Görevler</a>
    <span class="sep">/</span>
    <span class="current">{{ Str::limit($task->konu, 30) }}</span>
</div>

<div class="page-header">
    <div>
        <h1 class="page-title">
            @if($task->durum === 'tamamlandi')✅
            @elseif($task->durum === 'devam')⏳
            @else📝
            @endif
            {{ $task->konu }}
        </h1>
        <div class="page-subtitle">
            Görevi düzenle, dosya ekle veya tamamlandı olarak işaretle
            @if(!empty($task->tamamlandi_at))
                · <span style="color:var(--success)">Tamamlandı: {{ \Carbon\Carbon::parse($task->tamamlandi_at)->diffForHumans() }}</span>
            @endif
        </div>
    </div>
    <div class="page-actions">
        <a href="{{ route('admin.crm.gorevler.index') }}" class="btn btn-secondary btn-sm">
            <i data-lucide="arrow-left"></i>
            <span>Listeye Dön</span>
        </a>
    </div>
</div>

@if($errors->any())
    <div class="alert alert-danger" style="margin-bottom:16px">
        <strong>Form Hataları:</strong>
        <ul style="margin:6px 0 0 18px;font-size:13px">
            @foreach($errors->all() as $e)
                <li>{{ $e }}</li>
            @endforeach
        </ul>
    </div>
@endif

@include('admin.crm.tasks._form')

@endsection