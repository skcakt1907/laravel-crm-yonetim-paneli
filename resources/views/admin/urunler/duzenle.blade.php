<!DOCTYPE html>
<html lang="tr">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="csrf-token" content="{{ csrf_token() }}">
<title>Ürün Düzenle — İş Ortağım Admin</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Space+Grotesk:wght@600;700&display=swap" rel="stylesheet">
@vite(["resources/css/app.css"])
<style>*{transition:background-color .4s,color .4s;-webkit-font-smoothing:antialiased}body{font-family:'Inter',sans-serif;background: #000;color: var(--text-inverse)}.font-display{font-family:'Space Grotesk',sans-serif}.glass{background: rgba(20,20,20,.85);backdrop-filter:blur(20px);border: 1px solid rgba(184,182,46,.25)}body.light{background: var(--surface);color: var(--text)}body.light .glass{background: rgba(255,255,255,.95);border: 1px solid rgba(0,0,0,.08);box-shadow:0 4px 20px rgba(0,0,0,.04)}body.light .text-white{color: var(--text)!important}body.light .text-white\/70{color: rgba(0,0,0,.7)!important}body.light .text-white\/60{color: rgba(0,0,0,.6)!important}body.light .text-white\/50{color: rgba(0,0,0,.5)!important}body.light .text-white\/40{color: rgba(0,0,0,.4)!important}body.light .border-yellow-500\/20{border-color: rgba(0,0,0,.1)!important}body.light .border-yellow-500\/10,body.light .border-yellow-500\/5{border-color: rgba(0,0,0,.05)!important}body.light .nav-link,body.light .ayar-link{color: var(--text)!important}body.light input,body.light select,body.light textarea{background: rgba(0,0,0,.04)!important;color: var(--text)!important;border-color: rgba(0,0,0,.1)!important}.gradient-text{background: linear-gradient(135deg,var(--brand-medium),var(--brand),var(--brand-hover));-webkit-background-clip:text;-webkit-text-fill-color:transparent}.count{color: var(--text-inverse);text-shadow:0 0 40px rgba(184,182,46,.4)}body.light .count{color: var(--text);text-shadow:0 0 40px rgba(184,182,46,.5)}::-webkit-scrollbar{width:8px;height:8px}::-webkit-scrollbar-thumb{background: linear-gradient(180deg,var(--brand),var(--brand-hover));border-radius: 8px}.nav-link{color: var(--text-inverse);text-decoration:none;display:flex;align-items:center;gap:12px;padding:10px 12px;border-radius: 12px;transition:all .3s}.nav-link.active{background: linear-gradient(135deg,rgba(184,182,46,.2),rgba(184,182,46,.05));color: var(--brand)!important;border-left: 3px solid var(--brand)}.nav-link.active .emoji-icon{transform:scale(1.15);filter:drop-shadow(0 0 12px rgba(184,182,46,.8))}.nav-link:hover{background: rgba(255,255,255,.05)}.emoji-icon{font-size:22px;display:inline-flex;width:36px;height:36px;align-items:center;justify-content:center;transition:all .3s}.sidebar-section-title{font-size:11px;text-transform:uppercase;letter-spacing:1.5px;color: rgba(184,182,46,.7);font-weight:700;padding:14px 12px 6px;margin-top:8px;border-top: 1px solid rgba(184,182,46,.15)}body.light .sidebar-section-title{color: var(--warning)}.badge-danger{background: rgba(239,68,68,.2);color: var(--danger);border: 1px solid rgba(239,68,68,.3)}.ml-auto{margin-left:auto}.sidebar-divider{height:1px;background: linear-gradient(90deg,transparent,rgba(184,182,46,.5),transparent);margin:1rem 0}.theme-toggle-nav{width:40px;height:40px;border-radius: 50%;background: linear-gradient(135deg,var(--brand),var(--brand-hover));display:inline-flex;align-items:center;justify-content:center;cursor:pointer;box-shadow:0 4px 14px rgba(184,182,46,.3);font-size:18px;border: none;transition:all .3s;color: var(--text)}.theme-toggle-nav:hover{transform:scale(1.1) rotate(15deg)}.theme-toggle-nav .icon-sun{display:none}body.light .theme-toggle-nav .icon-sun{display:block}body.light .theme-toggle-nav .icon-moon{display:none}.btn-y{background: linear-gradient(135deg,var(--brand),var(--brand-hover));color: var(--text);font-weight:700;padding:10px 20px;border-radius: 12px;box-shadow:0 8px 20px rgba(184,182,46,.3);border: none;cursor:pointer;text-decoration:none;display:inline-block;transition:all .3s}.btn-y:hover{transform:translateY(-2px)}.btn-o{background: rgba(255,255,255,.05);border: 1px solid rgba(184,182,46,.3);color: var(--text-inverse);padding:10px 20px;border-radius: 12px;cursor:pointer;text-decoration:none;display:inline-block;transition:all .3s}.btn-o:hover{background: rgba(184,182,46,.1)}body.light .btn-o{background: rgba(0,0,0,.03);color: var(--text)}input,select,textarea{background: rgba(255,255,255,.07);border: 1px solid rgba(184,182,46,.25);color: var(--text-inverse);padding:14px 24px;border-radius: 12px;width:100%;font-family:inherit;font-size:15px;font-weight:500;letter-spacing:.2px;line-height:1.6;box-sizing:border-box;transition:all .2s;text-indent:8px}input:focus,select:focus,textarea:focus{border-color: var(--brand);outline: none;background: rgba(255,255,255,.1);box-shadow:0 0 0 3px rgba(184,182,46,.2)}select option{background: #1a1a1a;color: var(--text-inverse);padding:8px}body.light select option{background: var(--surface);color: var(--text)}.pagination{display:flex;list-style:none;gap:6px;padding:0;margin:24px 0;flex-wrap:wrap;justify-content:center}.page-item{list-style:none}.page-item .page-link{display:inline-flex;align-items:center;justify-content:center;min-width:40px;height:40px;padding:0 14px;border-radius: 10px;background: rgba(255,255,255,.05);color: var(--text-inverse);border: 1px solid rgba(184,182,46,.25);text-decoration:none;font-weight:600;transition:all .2s;font-size:14px}.page-item .page-link:hover{background: rgba(184,182,46,.15);border-color: var(--brand);color: var(--brand)}.page-item.active .page-link{background: linear-gradient(135deg,var(--brand),var(--brand-hover));color: var(--text);border-color: var(--brand);box-shadow:0 4px 14px rgba(184,182,46,.35)}.page-item.disabled .page-link{opacity:.4;cursor:not-allowed;background: rgba(255,255,255,.02)}body.light .page-item .page-link{background: rgba(0,0,0,.04);color: var(--text);border-color: rgba(0,0,0,.1)}body.light .page-item.active .page-link{color: var(--text)}input::placeholder,textarea::placeholder{color: rgba(255,255,255,.5);font-weight:400;letter-spacing:.3px}body.light input::placeholder,body.light textarea::placeholder{color: rgba(0,0,0,.3)}.field-label{display:flex;align-items:center;gap:6px;font-size:14px;font-weight:600;margin-bottom:8px}.required{color: var(--danger)}.help-text{font-size:12px;color: rgba(255,255,255,.5);margin-top:6px}body.light .help-text{color: rgba(0,0,0,.5)}.section-title{font-size:12px;text-transform:uppercase;letter-spacing:2px;color: var(--brand);font-weight:700;margin-bottom:16px;padding-bottom:12px;border-bottom: 1px solid rgba(184,182,46,.2);display:flex;align-items:center;gap:8px}.toggle{position:relative;display:inline-block;width:50px;height:28px}.toggle input{opacity:0;width:0;height:0;padding:0;background: var(--surface);color: var(--text);}.slider{position:absolute;cursor:pointer;top:0;left:0;right:0;bottom:0;background: rgba(255,255,255,.1);transition:.3s;border-radius: 28px;border: 1px solid rgba(184,182,46,.3)}.slider:before{position:absolute;content:"";height:20px;width:20px;left:3px;bottom:3px;background: var(--surface);transition:.3s;border-radius: 50%}.toggle input:checked+.slider{background: linear-gradient(135deg,var(--brand),var(--brand-hover));border-color: var(--brand)}.toggle input:checked+.slider:before{transform:translateX(22px);background: #000}.badge{padding:4px 10px;border-radius: 8px;font-weight:600;font-size:11px;display:inline-flex;align-items:center;gap:4px}.badge-success{background: rgba(16,185,129,.2);color: #6ee7b7;border: 1px solid rgba(16,185,129,.3)}.badge-danger{background: rgba(239,68,68,.2);color: var(--danger);border: 1px solid rgba(239,68,68,.3)}.badge-warning{background: rgba(251,146,60,.2);color: #fdba74;border: 1px solid rgba(251,146,60,.3)}.badge-yellow{background: rgba(184,182,46,.2);color: var(--brand);border: 1px solid rgba(184,182,46,.3)}.badge-secondary{background: rgba(255,255,255,.1);color: var(--text-inverse);border: 1px solid rgba(255,255,255,.2)}tr.data-row{transition:background .2s}tr.data-row:hover{background: rgba(184,182,46,.05)}body.light tr.data-row:hover{background: rgba(184,182,46,.1)}.action-btn{width:32px;height:32px;display:inline-flex;align-items:center;justify-content:center;border-radius: 8px;transition:all .2s;cursor:pointer;text-decoration:none;border: none;background: transparent;color: inherit}.action-btn:hover{background: rgba(184,182,46,.15);transform:scale(1.1)}.sidebar-collapsed{width:80px!important}.sidebar-collapsed .nav-text,.sidebar-collapsed .nav-section,.sidebar-collapsed .logo-text{display:none}.nav-group{margin-bottom:6px}.nav-group-header{width:100%;display:flex;align-items:center;gap:12px;padding:10px 12px;border-radius: 12px;background: transparent;border: none;color: var(--brand);font-family:inherit;font-size:13px;cursor:pointer;text-transform:uppercase;letter-spacing:1px;transition:all .3s}.nav-group-header:hover{background: rgba(184,182,46,.1)}body.light .nav-group-header{color: var(--warning)}.chevron{font-size:10px;transition:transform .3s;display:inline-block}.chevron.chevron-open{transform:rotate(90deg)}.nav-group-items{padding-left:8px;display:flex;flex-direction:column;gap:2px;margin-top:4px;animation:fadeIn .3s}.nav-group-items.hidden{display:none}@keyframes fadeIn{from{opacity:0;transform:translateY(-5px)}to{opacity:1;transform:translateY(0)}}.sidebar-collapsed .nav-group-header,.sidebar-collapsed .nav-group-items{display:none}.stat-card{transition:all .4s cubic-bezier(0.4,0,0.2,1)}.stat-card:hover{transform:translateY(-5px);box-shadow:0 25px 50px -12px rgba(184,182,46,.3)}.modal-bg{position:fixed;inset:0;background: rgba(0,0,0,.7);backdrop-filter:blur(8px);z-index:100;display:none;align-items:center;justify-content:center;padding:20px}.modal-bg.show{display:flex}.modal{max-width:700px;width:100%;max-height:90vh;overflow-y:auto;background: #0a0a0a;border: 1px solid rgba(184,182,46,.3);border-radius: 20px}body.light .modal{background: var(--surface);border-color: rgba(0,0,0,.1)}body.light .glass{background: var(--surface)!important;border: 1px solid rgba(0,0,0,.08)!important;box-shadow:0 4px 24px rgba(0,0,0,.06)!important;backdrop-filter:none!important}body.light aside{background: var(--surface-hover)!important;border-right-color: rgba(0,0,0,.08)!important}body.light header{background: var(--surface)!important;border-bottom-color: rgba(0,0,0,.08)!important}body.light .text-white{color: var(--text)!important}body.light .text-white\/70{color: rgba(15,23,42,.78)!important}body.light .text-white\/60{color: rgba(15,23,42,.72)!important}body.light .text-white\/50{color: rgba(15,23,42,.58)!important}body.light .text-white\/40{color: rgba(15,23,42,.5)!important}body.light .text-yellow-400{color: var(--warning)!important}body.light .text-yellow-300,body.light .text-yellow-500{color: var(--warning)!important}body.light .text-fde047,body.light .text-yellow-200{color: var(--warning)!important}body.light .nav-link{color: var(--text)!important}body.light .nav-link:hover{background: rgba(0,0,0,.04)!important}body.light .nav-link.active{background: linear-gradient(135deg,rgba(184,182,46,.25),rgba(184,182,46,.1))!important;color: var(--warning)!important;border-left-color: var(--brand-hover)!important}body.light .nav-group-header{color: var(--warning)!important}body.light .nav-group-header:hover{background: rgba(184,182,46,.12)!important}body.light input,body.light select,body.light textarea{background: var(--bg-subtle)!important;color: var(--text)!important;border: 1px solid rgba(0,0,0,.12)!important}body.light input:focus,body.light select:focus,body.light textarea:focus{border-color: var(--brand-hover)!important;background: var(--surface)!important;box-shadow:0 0 0 3px rgba(184,182,46,.18)!important}body.light input::placeholder,body.light textarea::placeholder{color: rgba(15,23,42,.4)!important}body.light select option{background: var(--surface)!important;color: var(--text)!important}body.light .field-label{color: var(--text)!important}body.light .section-title{color: var(--warning)!important;border-bottom-color: rgba(0,0,0,.08)!important}body.light .help-text{color: rgba(15,23,42,.55)!important}body.light .btn-o{background: var(--bg-subtle)!important;border-color: rgba(0,0,0,.12)!important;color: var(--text)!important}body.light .btn-o:hover{background: var(--warning-soft)!important;border-color: var(--brand-hover)!important}body.light table thead tr{background: var(--warning-soft)!important}body.light table thead th{color: var(--warning)!important}body.light tr.data-row{background: var(--surface)!important}body.light tr.data-row:hover{background: #fffbeb!important}body.light tr.data-row td,body.light table tbody td{border-color: rgba(0,0,0,.06)!important}body.light .border-yellow-500\/20,body.light .border-yellow-500\/25{border-color: rgba(0,0,0,.1)!important}body.light .border-yellow-500\/10,body.light .border-yellow-500\/5{border-color: rgba(0,0,0,.05)!important}body.light .badge-success{background: rgba(16,185,129,.15)!important;color: #047857!important;border-color: rgba(16,185,129,.4)!important}body.light .badge-danger{background: rgba(239,68,68,.12)!important;color: #b91c1c!important;border-color: rgba(239,68,68,.4)!important}body.light .badge-warning{background: rgba(251,146,60,.15)!important;color: #c2410c!important;border-color: rgba(251,146,60,.4)!important}body.light .badge-yellow{background: rgba(184,182,46,.2)!important;color: var(--warning)!important;border-color: rgba(245,158,11,.5)!important}body.light .badge-secondary{background: var(--bg-subtle)!important;color: var(--text)!important;border-color: rgba(0,0,0,.1)!important}body.light .page-item .page-link{background: var(--surface)!important;color: var(--text)!important;border-color: rgba(0,0,0,.1)!important}body.light .page-item .page-link:hover{background: var(--warning-soft)!important;border-color: var(--brand-hover)!important;color: var(--warning)!important}body.light .page-item.active .page-link{background: linear-gradient(135deg,var(--brand),var(--brand-hover))!important;color: var(--text)!important}body.light .gradient-text{background: linear-gradient(135deg,#b45309,var(--brand-hover),#d97706)!important;-webkit-background-clip:text!important;-webkit-text-fill-color:transparent!important}body.light .slider{background: #e2e8f0!important;border-color: rgba(0,0,0,.12)!important}body.light .slider:before{background: var(--surface)!important}body.light .sidebar-section-title{color: var(--warning)!important;border-top-color: rgba(0,0,0,.08)!important}body.light .action-btn:hover{background: rgba(184,182,46,.2)!important}body.light{background: var(--bg-subtle)!important}input[type="file"]{padding:8px 12px!important;text-indent:0!important;cursor:pointer;background: rgba(255,255,255,.05)!important;border: 1px dashed rgba(184,182,46,.4)!important}input[type="file"]::-webkit-file-upload-button{background: linear-gradient(135deg,var(--brand),var(--brand-hover));color: var(--text);font-weight:700;border: none;padding:6px 14px;border-radius: 8px;cursor:pointer;margin-right:10px;font-family:inherit}body.light input[type="file"]{background: var(--bg-subtle)!important;border-color: rgba(245,158,11,.5)!important;color: var(--text)!important}input[type="checkbox"]{width:auto!important;padding:0!important;text-indent:0!important;background: transparent!important;border: none!important}select{appearance:none;-webkit-appearance:none;-moz-appearance:none;background-image:url("data:image/svg+xml;charset=UTF-8,%3csvg xmlns=%27http://www.w3.org/2000/svg%27 viewBox=%270 0 16 16%27%3e%3cpath fill=%27%23facc15%27 d=%27M8 11L3 6h10z%27/%3e%3c/svg%3e")!important;background-repeat:no-repeat!important;background-position:right 14px center!important;background-size:16px!important;padding-right:42px!important;cursor:pointer;background: var(--surface);color: var(--text);}select:hover{border-color: var(--brand)!important}body.light select{background-image:url("data:image/svg+xml;charset=UTF-8,%3csvg xmlns=%27http://www.w3.org/2000/svg%27 viewBox=%270 0 16 16%27%3e%3cpath fill=%27%23475569%27 d=%27M8 11L3 6h10z%27/%3e%3c/svg%3e")!important;background-repeat:no-repeat!important;background-position:right 14px center!important;background-size:16px!important;background-color: var(--bg-subtle)!important}select option{background: #1a1a1a!important;color: var(--text-inverse)!important;padding:10px 14px!important;font-weight:500}body.light select option{background: var(--surface)!important;color: var(--text)!important}select::-ms-expand{display:none}select[multiple]{background-image:none!important;padding-right:24px!important;background: var(--surface);color: var(--text);}.user-menu-item{display:flex;align-items:center;gap:10px;padding:10px 14px;color: var(--text-inverse);text-decoration:none;border-radius: 8px;font-size:14px;transition:all .2s}.user-menu-name{color: var(--text-inverse)}.user-menu-sub{color: rgba(255,255,255,.5)}body.light .user-menu-name{color: var(--text)!important}body.light .user-menu-sub{color: rgba(15,23,42,.55)!important}.user-menu-item:hover{background: rgba(184,182,46,.15);color: var(--brand)}body.light .user-menu-dropdown{background: var(--surface)!important;border-color: rgba(0,0,0,.1)!important;box-shadow:0 12px 32px rgba(0,0,0,.15)!important}body.light .user-menu-item{color: var(--text)}body.light .user-menu-item:hover{background: var(--warning-soft)!important;color: var(--warning)!important}</style>
    <link rel="stylesheet" href="{{ asset('css/admin-light-fix.css') }}">
</head>
<body>
<div class="flex min-h-screen">
<aside id="sidebar" class="hidden lg:flex flex-col w-72 glass border-r border-yellow-500/25 sticky top-0 h-screen overflow-y-auto z-30">
<div class="p-6 border-b border-yellow-500/20"><a href="{{ route('admin.dashboard') }}" class="flex items-center gap-3"><div class="w-11 h-11 rounded-xl bg-gradient-to-br from-yellow-400 to-amber-500 flex items-center justify-center text-black font-extrabold text-xl">İ</div><div class="logo-text"><div class="font-display font-bold text-lg">İş Ortağım</div><div class="text-xs text-yellow-400">Admin Panel</div></div></a></div>
<nav class="flex-1 p-4 space-y-1">
@php $_admin_rol = session('admin_rol', 2); @endphp

@if($_admin_rol == 1 || $_admin_rol == 2 || $_admin_rol == 5)

<a href="{{ route('admin.dashboard') }}" class="nav-link {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}"><span class="emoji-icon">🏠</span><span class="nav-text">Anasayfa</span></a>

<div class="nav-group" data-group="crm">
<button type="button" class="nav-group-header" onclick="toggleGroup('crm')"><span class="emoji-icon">📋</span><span class="nav-text font-bold flex-1 text-left">CRM Yönetimi</span><span class="nav-text chevron {{ request()->routeIs('admin.crm.*') ? 'chevron-open' : '' }}">▶</span></button>
<div class="nav-group-items {{ request()->routeIs('admin.crm.*') ? '' : 'hidden' }}" data-group-items="crm">
<a href="{{ route('admin.crm.musteriler.index') }}" class="nav-link {{ request()->routeIs('admin.crm.musteriler.*') ? 'active' : '' }}"><span class="emoji-icon">👥</span><span class="nav-text">Müşteriler</span></a>
<a href="{{ route('admin.crm.firsatlar.index') }}" class="nav-link {{ request()->routeIs('admin.crm.firsatlar.*') ? 'active' : '' }}"><span class="emoji-icon">📈</span><span class="nav-text">Fırsatlar</span></a>
<a href="{{ route('admin.crm.gorevler.index') }}" class="nav-link {{ request()->routeIs('admin.crm.gorevler.*') ? 'active' : '' }}"><span class="emoji-icon">✅</span><span class="nav-text">Görevler</span></a>
<a href="{{ route('admin.crm.kanban.index') }}" class="nav-link {{ request()->routeIs('admin.crm.kanban.*') ? 'active' : '' }}"><span class="emoji-icon">📋</span><span class="nav-text">Kanban Board</span></a>
</div></div>


<div class="nav-group" data-group="diller">
<button type="button" class="nav-group-header" onclick="toggleGroup('diller')"><span class="emoji-icon">🌐</span><span class="nav-text font-bold flex-1 text-left">Dil Yönetimi</span><span class="nav-text chevron {{ request()->routeIs('admin.diller.*') ? 'chevron-open' : '' }}">▶</span></button>
<div class="nav-group-items {{ request()->routeIs('admin.diller.*') ? '' : 'hidden' }}" data-group-items="diller">
<a href="{{ route('admin.diller.ekle') }}" class="nav-link {{ request()->routeIs('admin.diller.ekle') ? 'active' : '' }}"><span class="emoji-icon">➕</span><span class="nav-text">Dil Ekle</span></a>
<a href="{{ route('admin.diller.index') }}" class="nav-link {{ request()->routeIs('admin.diller.index') ? 'active' : '' }}"><span class="emoji-icon">📋</span><span class="nav-text">Dil Listele</span></a>
</div></div>

<div class="nav-group" data-group="menuler">
<button type="button" class="nav-group-header" onclick="toggleGroup('menuler')"><span class="emoji-icon">📑</span><span class="nav-text font-bold flex-1 text-left">Menü Yönetimi</span><span class="nav-text chevron {{ request()->routeIs('admin.menuler.*') ? 'chevron-open' : '' }}">▶</span></button>
<div class="nav-group-items {{ request()->routeIs('admin.menuler.*') ? '' : 'hidden' }}" data-group-items="menuler">
<a href="{{ route('admin.menuler.header') }}" class="nav-link {{ request()->routeIs('admin.menuler.header') ? 'active' : '' }}"><span class="emoji-icon">🍔</span><span class="nav-text">Header Menü</span></a>
<a href="{{ route('admin.menuler.footer') }}" class="nav-link {{ request()->routeIs('admin.menuler.footer') ? 'active' : '' }}"><span class="emoji-icon">🦶</span><span class="nav-text">Footer Menü</span></a>
</div></div>



<div class="nav-group" data-group="muhasebe">
<button type="button" class="nav-group-header" onclick="toggleGroup('muhasebe')"><span class="emoji-icon">💰</span><span class="nav-text font-bold flex-1 text-left">Muhasebe</span><span class="nav-text chevron {{ request()->routeIs('admin.faturalar.*') || request()->routeIs('admin.banka.*') ? 'chevron-open' : '' }}">▶</span></button>
<div class="nav-group-items {{ request()->routeIs('admin.faturalar.*') || request()->routeIs('admin.banka.*') ? '' : 'hidden' }}" data-group-items="muhasebe">
<a href="{{ route('admin.faturalar.bekleyen') }}" class="nav-link {{ request()->routeIs('admin.faturalar.bekleyen') ? 'active' : '' }}"><span class="emoji-icon">⏳</span><span class="nav-text">Bekleyen Faturalar</span></a>
<a href="{{ route('admin.faturalar.onaylanan') }}" class="nav-link {{ request()->routeIs('admin.faturalar.onaylanan') ? 'active' : '' }}"><span class="emoji-icon">✅</span><span class="nav-text">Onaylanan Faturalar</span></a>
<a href="{{ route('admin.faturalar.index') }}" class="nav-link {{ request()->routeIs('admin.faturalar.index') ? 'active' : '' }}"><span class="emoji-icon">🧾</span><span class="nav-text">Tüm Faturalar</span></a>
<a href="{{ route('admin.banka.index') }}" class="nav-link {{ request()->routeIs('admin.banka.*') ? 'active' : '' }}"><span class="emoji-icon">🏦</span><span class="nav-text">Banka Hesapları</span></a>
</div></div>

<div class="nav-group" data-group="destek">
<button type="button" class="nav-group-header" onclick="toggleGroup('destek')"><span class="emoji-icon">🆘</span><span class="nav-text font-bold flex-1 text-left">Destek Merkezi</span><span class="nav-text chevron {{ request()->routeIs('admin.destek*') || request()->routeIs('admin.iletisim*') ? 'chevron-open' : '' }}">▶</span></button>
<div class="nav-group-items {{ request()->routeIs('admin.destek*') || request()->routeIs('admin.iletisim*') ? '' : 'hidden' }}" data-group-items="destek">
<a href="{{ route('admin.destek.index') }}" class="nav-link {{ request()->routeIs('admin.destek*') ? 'active' : '' }}"><span class="emoji-icon">🆘</span><span class="nav-text">Destek Talepleri</span></a>
<a href="{{ route('admin.iletisim.index') }}" class="nav-link {{ request()->routeIs('admin.iletisim*') ? 'active' : '' }}"><span class="emoji-icon">📞</span><span class="nav-text">İletişim Mesajları</span></a>
</div></div>

<a href="{{ route('admin.mesajlar.index') }}" class="nav-link {{ request()->routeIs('admin.mesajlar*') ? 'active' : '' }}"><span class="emoji-icon">💬</span><span class="nav-text">Mesajlar</span></a>

<div class="nav-group" data-group="bayilik">
<button type="button" class="nav-group-header" onclick="toggleGroup('bayilik')"><span class="emoji-icon">🏪</span><span class="nav-text font-bold flex-1 text-left">Bayilik Yönetimi</span><span class="nav-text chevron {{ request()->routeIs('admin.bayiler.*') || request()->routeIs('admin.bayilik.*') ? 'chevron-open' : '' }}">▶</span></button>
<div class="nav-group-items {{ request()->routeIs('admin.bayiler.*') || request()->routeIs('admin.bayilik.*') ? '' : 'hidden' }}" data-group-items="bayilik">
<a href="{{ route('admin.bayiler.index') }}" class="nav-link {{ request()->routeIs('admin.bayiler.*') ? 'active' : '' }}"><span class="emoji-icon">🏪</span><span class="nav-text">Bayilikler</span></a>
<a href="{{ route('admin.bayilik.satislar') }}" class="nav-link {{ request()->routeIs('admin.bayilik.*') ? 'active' : '' }}"><span class="emoji-icon">📊</span><span class="nav-text">Bayilik Satışlar</span></a>
</div></div>

<div class="sidebar-section-title">SATIŞ YÖNETİMİ</div>

<div class="nav-group" data-group="hosting">
<button type="button" class="nav-group-header" onclick="toggleGroup('hosting')"><span class="emoji-icon">🖥️</span><span class="nav-text font-bold flex-1 text-left">Hosting Yönetimi</span><span class="nav-text chevron {{ request()->routeIs('admin.satislar.hosting') || request()->routeIs('admin.hosting.*') ? 'chevron-open' : '' }}">▶</span></button>
<div class="nav-group-items {{ request()->routeIs('admin.satislar.hosting') || request()->routeIs('admin.hosting.*') ? '' : 'hidden' }}" data-group-items="hosting">
<a href="{{ route('admin.satislar.hosting') }}" class="nav-link {{ request()->routeIs('admin.satislar.hosting') ? 'active' : '' }}"><span class="emoji-icon">💰</span><span class="nav-text">Hosting Satışlar</span></a>
<a href="{{ route('admin.hosting.paketler.index') }}" class="nav-link {{ request()->routeIs('admin.hosting.*') ? 'active' : '' }}"><span class="emoji-icon">📦</span><span class="nav-text">Hosting Paketler</span></a>
</div></div>

<div class="nav-group" data-group="alanadi">
<button type="button" class="nav-group-header" onclick="toggleGroup('alanadi')"><span class="emoji-icon">🌍</span><span class="nav-text font-bold flex-1 text-left">Alan Adı Yönetimi</span><span class="nav-text chevron {{ request()->routeIs('admin.satislar.domain') || request()->routeIs('admin.domain.*') || request()->routeIs('admin.domain-orders.*') || request()->routeIs('admin.crm.domains.*') ? 'chevron-open' : '' }}">▶</span></button>
<div class="nav-group-items {{ request()->routeIs('admin.satislar.domain') || request()->routeIs('admin.domain.*') || request()->routeIs('admin.domain-orders.*') || request()->routeIs('admin.crm.domains.*') ? '' : 'hidden' }}" data-group-items="alanadi">
<a href="{{ route('admin.crm.domains.index') }}" class="nav-link {{ request()->routeIs('admin.crm.domains.*') ? 'active' : '' }}"><span class="emoji-icon">🌐</span><span class="nav-text">Domain Takip</span></a>
<a href="{{ route('admin.satislar.domain') }}" class="nav-link {{ request()->routeIs('admin.satislar.domain') ? 'active' : '' }}"><span class="emoji-icon">💰</span><span class="nav-text">Alan Adı Satışlar</span></a>
<a href="{{ route('admin.domain.fiyatlar.index') }}" class="nav-link {{ request()->routeIs('admin.domain.fiyatlar.*') ? 'active' : '' }}"><span class="emoji-icon">💵</span><span class="nav-text">Alan Adı Fiyatları</span></a>
<a href="{{ route('admin.hizmet-fiyatlari.index') }}" class="nav-link {{ request()->routeIs('admin.hizmet-fiyatlari.*') ? 'active' : '' }}"><span class="emoji-icon">🧾</span><span class="nav-text">Hizmet Fiyatları</span></a>
</div></div>

<div class="nav-group" data-group="webpaket">
<button type="button" class="nav-group-header" onclick="toggleGroup('webpaket')"><span class="emoji-icon">📦</span><span class="nav-text font-bold flex-1 text-left">Web Paket Yönetimi</span><span class="nav-text chevron {{ request()->routeIs('admin.satislar.web-paket') || request()->routeIs('admin.paketler.*') || request()->routeIs('admin.kategoriler.*') ? 'chevron-open' : '' }}">▶</span></button>
<div class="nav-group-items {{ request()->routeIs('admin.satislar.web-paket') || request()->routeIs('admin.paketler.*') || request()->routeIs('admin.kategoriler.*') ? '' : 'hidden' }}" data-group-items="webpaket">
<a href="{{ route('admin.satislar.web-paket') }}" class="nav-link {{ request()->routeIs('admin.satislar.web-paket') ? 'active' : '' }}"><span class="emoji-icon">💰</span><span class="nav-text">Web Paket Satışlar</span></a>
<a href="{{ route('admin.paketler.index') }}" class="nav-link {{ request()->routeIs('admin.paketler.index') ? 'active' : '' }}"><span class="emoji-icon">📦</span><span class="nav-text">Web Paketler</span></a>
<a href="{{ route('admin.paketler.ozel') }}" class="nav-link {{ request()->routeIs('admin.paketler.ozel*') ? 'active' : '' }}"><span class="emoji-icon">🎁</span><span class="nav-text">Müşteriye Özel Teklifler</span></a>
<a href="{{ route('admin.kategoriler.index') }}" class="nav-link {{ request()->routeIs('admin.kategoriler.*') ? 'active' : '' }}"><span class="emoji-icon">📂</span><span class="nav-text">Web Kategoriler</span></a>
</div></div>

<div class="nav-group" data-group="hizmet">
<button type="button" class="nav-group-header" onclick="toggleGroup('hizmet')"><span class="emoji-icon">🛠️</span><span class="nav-text font-bold flex-1 text-left">Hizmet Yönetimi</span><span class="nav-text chevron {{ request()->routeIs('admin.hizmetler.*') ? 'chevron-open' : '' }}">▶</span></button>
<div class="nav-group-items {{ request()->routeIs('admin.hizmetler.*') ? '' : 'hidden' }}" data-group-items="hizmet">
<a href="{{ route('admin.hizmetler.index') }}" class="nav-link {{ request()->routeIs('admin.hizmetler.index') ? 'active' : '' }}"><span class="emoji-icon">📋</span><span class="nav-text">Hizmet Listele</span></a>
<a href="{{ route('admin.hizmetler.ekle') }}" class="nav-link {{ request()->routeIs('admin.hizmetler.ekle') ? 'active' : '' }}"><span class="emoji-icon">➕</span><span class="nav-text">Hizmet Ekle</span></a>
</div></div>

<div class="sidebar-section-title">İÇERİK YÖNETİMİ</div>

<div class="nav-group" data-group="icerik">
<button type="button" class="nav-group-header" onclick="toggleGroup('icerik')"><span class="emoji-icon">📄</span><span class="nav-text font-bold flex-1 text-left">İçerikler</span><span class="nav-text chevron {{ request()->routeIs('admin.sayfalar*') || request()->routeIs('admin.slider*') || request()->routeIs('admin.blog*') || request()->routeIs('admin.referanslar*') || request()->routeIs('admin.kampanyalar*') || request()->routeIs('admin.yorumlar*') ? 'chevron-open' : '' }}">▶</span></button>
<div class="nav-group-items {{ request()->routeIs('admin.sayfalar*') || request()->routeIs('admin.slider*') || request()->routeIs('admin.blog*') || request()->routeIs('admin.referanslar*') || request()->routeIs('admin.kampanyalar*') || request()->routeIs('admin.yorumlar*') ? '' : 'hidden' }}" data-group-items="icerik">
<a href="{{ route('admin.sayfalar.index') }}" class="nav-link {{ request()->routeIs('admin.sayfalar*') ? 'active' : '' }}"><span class="emoji-icon">📄</span><span class="nav-text">Sayfalar</span></a>
<a href="{{ route('admin.slider.index') }}" class="nav-link {{ request()->routeIs('admin.slider*') ? 'active' : '' }}"><span class="emoji-icon">🖼️</span><span class="nav-text">Slider</span></a>
<a href="{{ route('admin.blog.index') }}" class="nav-link {{ request()->routeIs('admin.blog*') ? 'active' : '' }}"><span class="emoji-icon">📝</span><span class="nav-text">Blog</span></a>
<a href="{{ route('admin.referanslar.index') }}" class="nav-link {{ request()->routeIs('admin.referanslar*') ? 'active' : '' }}"><span class="emoji-icon">⭐</span><span class="nav-text">Referanslar</span></a>
<a href="{{ route('admin.kampanyalar.index') }}" class="nav-link {{ request()->routeIs('admin.kampanyalar*') ? 'active' : '' }}"><span class="emoji-icon">🎉</span><span class="nav-text">Kampanyalar / Fırsatlar</span></a>
<a href="{{ route('admin.yorumlar.index') }}" class="nav-link {{ request()->routeIs('admin.yorumlar*') ? 'active' : '' }}"><span class="emoji-icon">💬</span><span class="nav-text">Yorumlar</span></a>
</div></div>


<div class="nav-group" data-group="ayarlar">
<button type="button" class="nav-group-header" onclick="toggleGroup('ayarlar')"><span class="emoji-icon">⚙️</span><span class="nav-text font-bold flex-1 text-left">Site Yönetimi</span><span class="nav-text chevron {{ request()->routeIs('admin.ayarlar.*') || request()->routeIs('admin.kuponlar.*') || request()->routeIs('admin.mail-templates.*') ? 'chevron-open' : '' }}">▶</span></button>
<div class="nav-group-items {{ request()->routeIs('admin.ayarlar.*') || request()->routeIs('admin.kuponlar.*') || request()->routeIs('admin.mail-templates.*') ? '' : 'hidden' }}" data-group-items="ayarlar">
<a href="{{ route('admin.ayarlar.index') }}" class="nav-link {{ request()->routeIs('admin.ayarlar.index') ? 'active' : '' }}"><span class="emoji-icon">🔧</span><span class="nav-text">Genel Ayarlar</span></a>
<a href="{{ route('admin.ayarlar.api') }}" class="nav-link {{ request()->routeIs('admin.ayarlar.api') ? 'active' : '' }}"><span class="emoji-icon">🔌</span><span class="nav-text">API Ayarları</span></a>
<a href="{{ route('admin.ayarlar.iletisim') }}" class="nav-link {{ request()->routeIs('admin.ayarlar.iletisim') ? 'active' : '' }}"><span class="emoji-icon">📞</span><span class="nav-text">İletişim Ayarları</span></a>
<a href="{{ route('admin.ayarlar.sosyal') }}" class="nav-link {{ request()->routeIs('admin.ayarlar.sosyal') ? 'active' : '' }}"><span class="emoji-icon">📱</span><span class="nav-text">Sosyal Medya Ayarları</span></a>
<a href="{{ route('admin.ayarlar.modul') }}" class="nav-link {{ request()->routeIs('admin.ayarlar.modul') ? 'active' : '' }}"><span class="emoji-icon">🧩</span><span class="nav-text">Modül Ayarları</span></a>
<a href="{{ route('admin.ayarlar.limit') }}" class="nav-link {{ request()->routeIs('admin.ayarlar.limit') ? 'active' : '' }}"><span class="emoji-icon">📊</span><span class="nav-text">Limit Ayarları</span></a>
<a href="{{ route('admin.ayarlar.bakim') }}" class="nav-link {{ request()->routeIs('admin.ayarlar.bakim') ? 'active' : '' }}"><span class="emoji-icon">🛠️</span><span class="nav-text">Site Bakım Modu</span></a>
<a href="{{ route('admin.ayarlar.mail') }}" class="nav-link {{ request()->routeIs('admin.ayarlar.mail') ? 'active' : '' }}"><span class="emoji-icon">📧</span><span class="nav-text">Mail Ayarları</span></a>
<a href="{{ route('admin.mail-templates.index') }}" class="nav-link {{ request()->routeIs('admin.mail-templates.*') ? 'active' : '' }}"><span class="emoji-icon">📩</span><span class="nav-text">Mail Şablonları</span></a>
<a href="{{ route('admin.ayarlar.sms') }}" class="nav-link {{ request()->routeIs('admin.ayarlar.sms') ? 'active' : '' }}"><span class="emoji-icon">💬</span><span class="nav-text">SMS Ayarları</span></a>
<a href="{{ route('admin.ayarlar.sanal') }}" class="nav-link {{ request()->routeIs('admin.ayarlar.sanal') ? 'active' : '' }}"><span class="emoji-icon">💳</span><span class="nav-text">Sanal Pos Ayarları</span></a>
<a href="{{ route('admin.ayarlar.arkaplan') }}" class="nav-link {{ request()->routeIs('admin.ayarlar.arkaplan') ? 'active' : '' }}"><span class="emoji-icon">🖼️</span><span class="nav-text">Arkaplan Ayarları</span></a>
<a href="{{ route('admin.kuponlar.index') }}" class="nav-link {{ request()->routeIs('admin.kuponlar.*') ? 'active' : '' }}"><span class="emoji-icon">🎟️</span><span class="nav-text">Kuponlar</span></a>
</div></div>

@if($_admin_rol == 1)
<a href="{{ route('admin.yoneticiler.index') }}" class="nav-link {{ request()->routeIs('admin.yoneticiler*') ? 'active' : '' }}"><span class="emoji-icon">🔐</span><span class="nav-text">Yöneticiler</span></a>

<a href="{{ route('admin.tickets.index') }}" class="nav-link {{ request()->routeIs('admin.tickets*') ? 'active' : '' }}"><span class="emoji-icon">🎫</span><span class="nav-text">Ticket Sistemi</span></a>

<a href="{{ route('admin.import.export') }}" class="nav-link {{ request()->routeIs('admin.import*') ? 'active' : '' }}"><span class="emoji-icon">📤</span><span class="nav-text">Import/Export</span></a>
<a href="{{ route('admin.tablolar.kilit.pin') }}" class="nav-link {{ request()->routeIs('admin.tablolar*') ? 'active' : '' }}"><span class="emoji-icon">📊</span><span class="nav-text">Tablolar</span></a>
@endif

@endif

@if($_admin_rol == 3)
<div class="sidebar-section-title">🤝 BAYİ PANELİ</div>
<a href="{{ route('admin.bayi.dashboard') }}" class="nav-link {{ request()->routeIs('admin.bayi.dashboard') ? 'active' : '' }}"><span class="emoji-icon">📊</span><span class="nav-text">Dashboard</span></a>
<a href="{{ route('admin.bayi.satislar') }}" class="nav-link {{ request()->routeIs('admin.bayi.satislar*') ? 'active' : '' }}"><span class="emoji-icon">🛒</span><span class="nav-text">Satışlarım</span></a>
<a href="{{ route('admin.bayi.kazanclar') }}" class="nav-link {{ request()->routeIs('admin.bayi.kazanclar*') ? 'active' : '' }}"><span class="emoji-icon">💰</span><span class="nav-text">Kazançlarım</span></a>
<a href="{{ route('admin.bayi.odeme.talepleri') }}" class="nav-link {{ request()->routeIs('admin.bayi.odeme*') ? 'active' : '' }}"><span class="emoji-icon">💳</span><span class="nav-text">Ödeme Talepleri</span></a>
<div class="nav-group" data-group="bayimusteriler">
<button type="button" class="nav-group-header" onclick="toggleGroup('bayimusteriler')"><span class="emoji-icon">👥</span><span class="nav-text font-bold flex-1 text-left">Müşterilerim</span><span class="nav-text chevron {{ request()->routeIs('admin.bayi.musteri*') ? 'chevron-open' : '' }}">▶</span></button>
<div class="nav-group-items {{ request()->routeIs('admin.bayi.musteri*') ? '' : 'hidden' }}" data-group-items="bayimusteriler">
<a href="{{ route('admin.bayi.musteriler') }}" class="nav-link {{ request()->routeIs('admin.bayi.musteriler') ? 'active' : '' }}"><span class="emoji-icon">📋</span><span class="nav-text">Müşterilerim</span></a>
<a href="{{ route('admin.bayi.musteri.ekle') }}" class="nav-link {{ request()->routeIs('admin.bayi.musteri.ekle') ? 'active' : '' }}"><span class="emoji-icon">➕</span><span class="nav-text">Yeni Müşteri Ekle</span></a>
</div></div>
<a href="{{ route('admin.bayi.referans.link') }}" class="nav-link {{ request()->routeIs('admin.bayi.referans.link*') ? 'active' : '' }}"><span class="emoji-icon">🔗</span><span class="nav-text">Referans Linki</span></a>
<a href="{{ route('admin.bayi.profil') }}" class="nav-link {{ request()->routeIs('admin.bayi.profil*') ? 'active' : '' }}"><span class="emoji-icon">👤</span><span class="nav-text">Profil</span></a>
@endif

<div class="sidebar-divider"></div>
<a href="{{ route('admin.cikis') }}" class="nav-link"><span class="emoji-icon">🚪</span><span class="nav-text">Çıkış</span></a>
</nav>
</aside>

<div class="flex-1 flex flex-col min-w-0">
<header class="glass border-b border-yellow-500/20 sticky top-0 z-20"><div class="flex items-center justify-between gap-4 px-4 lg:px-8 h-16"><div class="flex items-center gap-3"><button id="sidebarToggle" class="p-2 hover:bg-yellow-500/10 rounded-lg text-2xl">☰</button></div>@php
    $_adminId = session('admin_id');
    $_okunmamisBildirim = 0;
    try {
        if (\Illuminate\Support\Facades\Schema::hasTable('bildirimler') && $_adminId) {
            $_okunmamisBildirim = \Illuminate\Support\Facades\DB::table('bildirimler')
                ->where('admin_id', $_adminId)->where('okundu', 0)->count();
        }
    } catch (\Throwable $e) {}
@endphp
<a href="{{ route('admin.bildirimler.index') }}" class="theme-toggle-nav" title="Bildirimler" style="background: linear-gradient(135deg,#1f2937,#0f172a);color: #fde047;position:relative;text-decoration:none;margin-right:4px">🔔@if($_okunmamisBildirim > 0)<span style="position:absolute;top:-4px;right:-4px;background: var(--danger);color: var(--text-inverse);font-size:10px;font-weight:700;min-width:18px;height:18px;border-radius: 9px;padding:0 5px;display:flex;align-items:center;justify-content:center;border: 2px solid #000">{{ $_okunmamisBildirim > 99 ? '99+' : $_okunmamisBildirim }}</span>@endif</a><button id="themeToggle" class="theme-toggle-nav" title="Tema değiştir"><span class="icon-moon">🌙</span><span class="icon-sun">☀️</span></button>
<div class="user-menu relative pl-3 border-l border-yellow-500/20">
  <button type="button" onclick="toggleUserMenu(event)" class="flex items-center gap-3 cursor-pointer hover:bg-yellow-500/10 rounded-xl px-2 py-1 transition" style="border: none;background: transparent;color: inherit">
    <div class="hidden lg:block text-right">
      <div class="text-sm font-semibold">{{ session('admin_adi', 'Admin') }}</div>
      <div class="text-xs text-yellow-400">{{ session('admin_rol') == 1 ? 'Patron' : (session('admin_rol') == 2 ? 'Çalışan' : (session('admin_rol') == 3 ? 'Bayi' : 'Kullanıcı')) }}</div>
    </div>
    <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-yellow-400 to-amber-500 flex items-center justify-center text-black font-bold">{{ strtoupper(substr(session('admin_adi', 'A'), 0, 1)) }}</div>
    <span class="text-xs text-white/50">▾</span>
  </button>
  <div id="userMenu" class="user-menu-dropdown" style="position:absolute;top:calc(100% + 8px);right:0;min-width:240px;background: #0a0a0a;border: 1px solid rgba(184,182,46,.3);border-radius: 14px;box-shadow:0 12px 32px rgba(0,0,0,.5);padding:8px;display:none;z-index:50">
    <div style="padding:10px 14px;border-bottom: 1px solid rgba(184,182,46,.15);margin-bottom:6px">
      <div class="user-menu-name" style="font-size:14px;font-weight:700">{{ session('admin_adi', 'Admin') }}</div>
      <div class="user-menu-sub" style="font-size:11px">{{ session('admin_kullanici_adi', '') }}</div>
    </div>
    @if(Route::has('admin.profil'))
    <a href="{{ route('admin.profil') }}" class="user-menu-item">👤 <span>Profilim</span></a>
    @endif
    <a href="{{ url('/') }}" target="_blank" class="user-menu-item">🌐 <span>Siteyi Gör</span></a>
    <div style="height:1px;background: rgba(184,182,46,.15);margin:6px 0"></div>
    <a href="{{ route('admin.cikis') }}" class="user-menu-item" style="color: var(--danger)">🚪 <span>Çıkış Yap</span></a>
  </div>
</div></div></header>

@if(session('success'))<div class="m-4 p-4 bg-emerald-500/15 border border-emerald-500/40 text-emerald-300 rounded-xl flex items-center gap-3"><span class="text-2xl">✅</span><span>{{ session('success') }}</span></div>@endif
@if(session('error'))<div class="m-4 p-4 bg-rose-500/15 border border-rose-500/40 text-rose-300 rounded-xl flex items-center gap-3"><span class="text-2xl">❌</span><span>{{ session('error') }}</span></div>@endif
@if($errors->any())<div class="m-4 p-4 bg-rose-500/15 border border-rose-500/40 text-rose-300 rounded-xl"><strong>❌ Form Hataları:</strong><ul class="text-sm mt-2 ml-6 list-disc">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>@endif

<main class="flex-1 p-4 lg:p-8">

<div class="max-w-4xl mx-auto">
<div class="mb-6">
<div class="flex items-center gap-2 text-sm text-white/60 mb-2"><a href="{{ route('admin.dashboard') }}" class="hover:text-yellow-400">🏠</a> <span>›</span> <a href="{{ route('admin.urunler.index') }}" class="hover:text-yellow-400">📦 Ürün</a> <span>›</span> <span>Düzenle</span></div>
<h1 class="font-display text-3xl font-extrabold flex items-center gap-3">✏️ Düzenle</h1>
</div>
<form action="{{ route('admin.urunler.duzenlePost', $urun->id) }}" method="POST" enctype="multipart/form-data" class="space-y-6">
@csrf
<div class="glass rounded-2xl p-6">
<div class="section-title">📦 Ürün</div>
<div class="grid md:grid-cols-2 gap-5">
<div><label class="field-label">📛 Ürün Adı <span class="required">*</span></label><input type="text" name="adi" value="{{ old('adi', $urun->adi ?? '') }}" required></div>
<div><label class="field-label">💰 Fiyat</label><input type="number" name="fiyat" value="{{ old('fiyat', $urun->fiyat ?? '') }}"></div>
<div class="md:col-span-2"><label class="field-label">📝 Açıklama</label><textarea name="aciklama" rows="4">{{ old('aciklama', $urun->aciklama ?? '') }}</textarea></div>
</div>
<div class="mt-5 flex items-center justify-between p-4 bg-yellow-500/5 border border-yellow-500/10 rounded-xl">
<div><div class="font-semibold">⚡ Aktif</div></div>
<label class="toggle"><input type="checkbox" name="durum" value="1" {{ old('durum', $urun->durum ?? 1) ? 'checked' : '' }}><span class="slider"></span></label>
</div>
</div>
<div class="sticky bottom-4 glass rounded-2xl p-4 flex items-center justify-end gap-3">
<a href="{{ route('admin.urunler.index') }}" class="btn-o">❌ İptal</a>
<button type="submit" class="btn-y">💾 Kaydet</button>
</div>
</form>
</div>

</main>
</div>
</div>

<script>

const t=document.getElementById('themeToggle');
if(localStorage.getItem('admin_theme')==='light')document.body.classList.add('light');
if(t)t.addEventListener('click',()=>{document.body.classList.toggle('light');localStorage.setItem('admin_theme',document.body.classList.contains('light')?'light':'dark')});
(document.getElementById('sidebarToggle')||{addEventListener:()=>{}}).addEventListener('click',()=>document.getElementById('sidebar').classList.toggle('sidebar-collapsed'));

function toggleUserMenu(e){e.stopPropagation();const m=document.getElementById('userMenu');m.style.display=m.style.display==='block'?'none':'block';}
document.addEventListener('click',e=>{const m=document.getElementById('userMenu');if(m&&!e.target.closest('.user-menu'))m.style.display='none';});
function toggleGroup(key){const items=document.querySelector('[data-group-items="'+key+'"]');const chevron=document.querySelector('[data-group="'+key+'"] .chevron');items.classList.toggle('hidden');chevron.classList.toggle('chevron-open');const open=Array.from(document.querySelectorAll('.nav-group-items:not(.hidden)')).map(e=>e.dataset.groupItems);localStorage.setItem('sidebar_open_groups',JSON.stringify(open));}
window.addEventListener('DOMContentLoaded',function(){try{const saved=JSON.parse(localStorage.getItem('sidebar_open_groups')||'null');if(saved){document.querySelectorAll('.nav-group-items').forEach(it=>{const key=it.dataset.groupItems;if(saved.includes(key)){it.classList.remove('hidden');document.querySelector('[data-group="'+key+'"] .chevron').classList.add('chevron-open');}else{it.classList.add('hidden');document.querySelector('[data-group="'+key+'"] .chevron').classList.remove('chevron-open');}});}}catch(e){}});


</script>
</body>
</html>
