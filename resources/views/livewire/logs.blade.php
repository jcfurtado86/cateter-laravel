<div class="page">
    <div class="page-header">
        <h1>Logs do Sistema</h1>
    </div>

    <div class="search-bar">
        <div style="display:flex;gap:8px;align-items:center;flex:1;">
            <input type="text" wire:model.live.debounce.300ms="search"
                placeholder="{{ $tab === 'auth' ? 'Buscar por usuário ou e-mail...' : 'Buscar por ação ou usuário...' }}"
                class="search-input" />
            <input type="date" wire:model.live="dateFrom" class="search-input" style="max-width:160px;" title="De" />
            <input type="date" wire:model.live="dateTo" class="search-input" style="max-width:160px;" title="Até" />
        </div>
        @if($search || $dateFrom || $dateTo)
            <button wire:click="$set('search',''); $set('dateFrom',''); $set('dateTo','')" class="btn btn-ghost">Limpar</button>
        @endif
    </div>

    <div style="display:flex;gap:0;border-bottom:2px solid var(--border);margin-bottom:0;">
        <button wire:click="$set('tab','auth')"
            style="padding:10px 24px;border:none;background:none;cursor:pointer;font-weight:600;font-size:14px;border-bottom:3px solid {{ $tab === 'auth' ? 'var(--primary)' : 'transparent' }};color:{{ $tab === 'auth' ? 'var(--primary)' : 'var(--text-muted)' }};margin-bottom:-2px;">
            Autenticação
        </button>
        <button wire:click="$set('tab','audit')"
            style="padding:10px 24px;border:none;background:none;cursor:pointer;font-weight:600;font-size:14px;border-bottom:3px solid {{ $tab === 'audit' ? 'var(--primary)' : 'transparent' }};color:{{ $tab === 'audit' ? 'var(--primary)' : 'var(--text-muted)' }};margin-bottom:-2px;">
            Ações
        </button>
    </div>

    <div class="table-container">
        @if($tab === 'auth')
        <table class="table">
            <thead>
                <tr>
                    <th>Data/Hora</th>
                    <th>Evento</th>
                    <th>Usuário</th>
                    <th>E-mail</th>
                    <th>IP</th>
                </tr>
            </thead>
            <tbody>
                @forelse($logs as $log)
                <tr>
                    <td>{{ \Carbon\Carbon::parse($log->created_at)->format('d/m/Y H:i:s') }}</td>
                    <td>
                        @if($log->event === 'LOGIN')
                            <span class="badge badge-success">Login</span>
                        @elseif($log->event === 'LOGOUT')
                            <span class="badge badge-secondary">Logout</span>
                        @else
                            <span class="badge badge-danger">Falha no login</span>
                        @endif
                    </td>
                    <td>{{ $log->user?->name ?? '—' }}</td>
                    <td>{{ $log->email ?? '—' }}</td>
                    <td>{{ $log->ip_address ?? '—' }}</td>
                </tr>
                @empty
                <tr><td colspan="5" class="text-center">Nenhum registro encontrado.</td></tr>
                @endforelse
            </tbody>
        </table>
        @else
        <table class="table">
            <thead>
                <tr>
                    <th>Data/Hora</th>
                    <th>Ação</th>
                    <th>Usuário</th>
                    <th>Registro</th>
                    <th>IP</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse($logs as $log)
                <tr>
                    <td>{{ \Carbon\Carbon::parse($log->created_at)->format('d/m/Y H:i:s') }}</td>
                    <td>
                        <span class="badge {{ match(true) {
                            str_contains($log->action, 'created') || str_contains($log->action, 'inserted') => 'badge-success',
                            str_contains($log->action, 'deleted') || str_contains($log->action, 'removed') => 'badge-danger',
                            str_contains($log->action, 'sent') => 'badge-info',
                            default => 'badge-secondary'
                        } }}">{{ $log->action }}</span>
                    </td>
                    <td>{{ $log->user?->name ?? '—' }}</td>
                    <td class="text-muted" style="font-size:12px;">{{ $log->model_type }} {{ $log->model_id ? substr($log->model_id, 0, 8).'...' : '' }}</td>
                    <td>{{ $log->ip_address ?? '—' }}</td>
                    <td class="actions-cell">
                        @if($log->old_values || $log->new_values)
                        <button class="btn btn-secondary btn-xs"
                            x-data
                            x-on:click="$dispatch('open-log-detail', { antes: {{ json_encode($log->old_values) }}, depois: {{ json_encode($log->new_values) }}, modelId: '{{ $log->model_id }}', modelType: '{{ $log->model_type }}', action: '{{ $log->action }}' })">
                            Ver
                        </button>
                        @endif
                    </td>
                </tr>
                @empty
                <tr><td colspan="6" class="text-center">Nenhum registro encontrado.</td></tr>
                @endforelse
            </tbody>
        </table>
        @endif
    </div>

    <div style="padding:16px 0;">
        {{ $logs->links() }}
    </div>

    {{-- Modal detalhe --}}
    <div x-data="{ show: false, antes: null, depois: null, modelId: null, modelType: null, action: null }"
         x-on:open-log-detail.window="show = true; antes = $event.detail.antes; depois = $event.detail.depois; modelId = $event.detail.modelId; modelType = $event.detail.modelType; action = $event.detail.action"
         x-show="show"
         class="modal-overlay"
         wire:click="$set('showModal', false)">
        <div class="modal modal-lg" @click.stop>
            <div class="modal-header">
                <div>
                    <h2>Detalhe da Alteração</h2>
                    <p style="margin:4px 0 0 0;font-size:13px;color:var(--text-muted);" x-text="modelType && modelId ? `${modelType} #${modelId}` : ''"></p>
                    <p style="margin:4px 0 0 0;font-size:12px;color:var(--text-muted);" x-text="action ? `Ação: ${action}` : ''"></p>
                </div>
                <button x-on:click="show=false" class="modal-close">&times;</button>
            </div>
            <div class="modal-form">
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;">
                    <div>
                        <p style="font-weight:600;margin-bottom:8px;font-size:13px;">Antes</p>
                        <pre style="background:#f5f5f5;padding:12px;border-radius:6px;font-size:12px;overflow:auto;max-height:300px;" x-text="antes ? JSON.stringify(antes, null, 2) : '—'"></pre>
                    </div>
                    <div>
                        <p style="font-weight:600;margin-bottom:8px;font-size:13px;">Depois</p>
                        <pre style="background:#f5f5f5;padding:12px;border-radius:6px;font-size:12px;overflow:auto;max-height:300px;" x-text="depois ? JSON.stringify(depois, null, 2) : '—'"></pre>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
