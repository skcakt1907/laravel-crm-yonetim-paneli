@extends('layouts.master')

@section('title', '404 - ' . __('messages.page_not_found'))

@section('content')
<div style="min-height: 60vh; display: flex; align-items: center; justify-content: center; padding: 60px 20px;">
    <div style="text-align: center; max-width: 500px;">
        <div style="font-size: 120px; font-weight: 800; background: linear-gradient(135deg, #d4d25b, #f0e68c); -webkit-background-clip: text; -webkit-text-fill-color: transparent; line-height: 1;">404</div>
        <h2 style="color: #e5e7eb; font-size: 24px; margin: 20px 0 10px;">{{ __('messages.page_not_found') }}</h2>
        <p style="color: rgba(255,255,255,0.6); font-size: 16px; margin-bottom: 30px;">
            {{ __('messages.page_not_found_description') }}
        </p>
        <a href="{{ url('/') }}" style="display: inline-block; padding: 12px 32px; background: linear-gradient(135deg, #d4d25b, #c4c24b); color: #0f172a; font-weight: 600; border-radius: 8px; text-decoration: none; transition: transform 0.2s;">
            {{ __('messages.return_home') }}
        </a>
    </div>
</div>
@endsection
