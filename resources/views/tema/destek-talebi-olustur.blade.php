@extends('layouts.panel')

@section('page_title', 'Destek Sistemi')

@section('panel_content')
<div class="col-md-12 border-left-3 main-content">
    <div class="title-area mb-4">
        <h5 class="title">
            <i class="fa fa-life-ring"></i>
            {{ __('messages.new_support_ticket') }}
        </h5>
        <div class="pull-right">
            <strong><a href="{{ route('hesabim') }}">{{ __('messages.my_account') }}</a></strong> /
            <strong><a href="{{ route('destek.taleplerim') }}">Destek Taleplerim</a></strong>
        </div>
    </div>

    @includeIf('tema.partials.alert-messages')

    <form action="{{ route('destek.olustur.post') }}" method="POST" enctype="multipart/form-data">
        @csrf
        <div class="row">
            <div class="form-group col-md-6">
                <label for="baslik">{{ __('messages.title') }} <span class="text-danger">*</span></label>
                <input type="text" id="baslik" name="baslik" class="form-control" value="{{ old('baslik') }}" placeholder="{{ __('messages.subject_placeholder') }}" required>
                @error('baslik')<div class="text-danger">{{ $message }}</div>@enderror
            </div>

            <div class="form-group col-md-6">
                <label for="hizmet">Kategori</label>
                <select id="hizmet" name="hizmet" class="form-control">
                    @foreach(['Genel','Müşteri İlişkileri','Hosting','Domain','Web Yazılım ve Tasarım','Faturalama','Teknik Destek','Diğer'] as $kat)
                        <option value="{{ $kat }}" {{ old('hizmet') == $kat ? 'selected' : '' }}>{{ $kat }}</option>
                    @endforeach
                </select>
            </div>

            <div class="form-group col-md-6">
                <label for="oncelik">{{ __('messages.priority') }}</label>
                <select name="oncelik" class="form-control" id="oncelik">
                    <option value="0" {{ old('oncelik', '0') == '0' ? 'selected' : '' }}>Normal</option>
                    <option value="1" {{ old('oncelik') == '1' ? 'selected' : '' }}>{{ __('messages.high') }}</option>
                    <option value="2" {{ old('oncelik') == '2' ? 'selected' : '' }}>Acil</option>
                </select>
            </div>

            <div class="form-group col-md-12">
                <label for="mesaj">{{ __('messages.message') }} <span class="text-danger">*</span></label>
                <textarea name="mesaj" id="mesaj" style="height:200px" placeholder="{{ __('messages.issue_placeholder') }}" class="form-control" required>{{ old('mesaj') }}</textarea>
                @error('mesaj')<div class="text-danger">{{ $message }}</div>@enderror
            </div>

            <div class="col-12">
                <label for="dosya">{{ __('messages.attach_file_button') }}</label>
                <input type="file" id="dosya" name="dosya" accept="image/*,.pdf,.doc,.docx,.zip,.rar">
                <div class="clear"></div>
                <p style="font-size:13px;display:inline-block;width:100%;letter-spacing:1px;word-wrap:break-word">{{ __('messages.allowed_formats') }}</p>
                @error('dosya')<div class="text-danger">{{ $message }}</div>@enderror
                <input type="submit" class="btn btn-primary pull-right" value="+ Talebi Oluştur">
            </div>
        </div>
    </form>
</div>
@endsection
