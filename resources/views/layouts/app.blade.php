<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'Cateter') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body>
    <div class="app-layout">
        <aside class="sidebar">
            <div class="sidebar-header">
                <h2>Cateter</h2>
                <span class="sidebar-subtitle">Monitoramento</span>
            </div>
            <nav class="sidebar-nav">
                <a href="{{ route('dashboard') }}" class="nav-item {{ request()->routeIs('dashboard') ? 'active' : '' }}">Dashboard</a>
                <a href="{{ route('patients') }}" class="nav-item {{ request()->routeIs('patients*') ? 'active' : '' }}">Pacientes</a>
                <a href="{{ route('catheters') }}" class="nav-item {{ request()->routeIs('catheters*') ? 'active' : '' }}">Cateteres</a>
                <a href="{{ route('notifications') }}" class="nav-item {{ request()->routeIs('notifications') ? 'active' : '' }}">Notificações</a>
                @if(auth()->user()->role === 'ADMIN')
                    <a href="{{ route('users') }}" class="nav-item {{ request()->routeIs('users') ? 'active' : '' }}">Usuários</a>
                    <a href="{{ route('logs') }}" class="nav-item {{ request()->routeIs('logs') ? 'active' : '' }}">Logs</a>
                @endif
            </nav>
            <div class="sidebar-footer">
                <div class="user-info">
                    <span class="user-name">{{ auth()->user()->name }}</span>
                    <span class="user-role">{{ auth()->user()->role === 'ADMIN' ? 'Administrador' : 'Médico' }}</span>
                </div>
                <livewire:profile />
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="btn btn-ghost btn-sm" style="width:100%">Sair</button>
                </form>
            </div>
        </aside>
        <main class="main-content">
            {{ $slot }}
        </main>
    </div>

    <div x-data="{ message: '', show: false }"
         x-on:toast.window="message = $event.detail.message; show = true; setTimeout(() => show = false, 3000)"
         x-show="show"
         style="position:fixed; top:20px; right:20px; z-index:9999; max-width:320px; display:none;"
         class="alert alert-success">
        <span x-text="message"></span>
    </div>
    @livewireScriptConfig
</body>
</html>
