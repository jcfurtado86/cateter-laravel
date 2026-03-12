<div class="page">
    <div class="page-header">
        <h1>Cateteres Ativos</h1>
        <span class="text-muted">{{ $total }} ativo{{ $total !== 1 ? 's' : '' }}</span>
    </div>

    <div class="filter-bar">
        @foreach(['all' => 'Todos', 'overdue' => 'Vencido', 'urgent' => 'Urgente', 'warning' => 'Atenção', 'ok' => 'OK'] as $key => $label)
            <button wire:click="$set('filter', '{{ $key }}')"
                    class="btn btn-sm {{ $filter === $key ? 'btn-primary' : 'btn-ghost' }}">
                {{ $label }} <span class="filter-count">({{ $counts[$key] }})</span>
            </button>
        @endforeach
    </div>

    @if(count($records) === 0)
        <div class="empty-state">Nenhum cateter encontrado.</div>
    @else
        <div class="table-container">
            <table class="table">
                <thead>
                    <tr>
                        <th>Paciente</th>
                        <th>Prontuário</th>
                        <th>Indicação</th>
                        <th>Calibre</th>
                        <th>Inserção</th>
                        <th>Prazo Mín.</th>
                        <th>Prazo Máx.</th>
                        <th>Dias Restantes</th>
                        <th>Status</th>
                        <th>Médico</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($records as $record)
                        @php
                            $labels = ['overdue' => 'Vencido', 'urgent' => 'Urgente', 'warning' => 'Atenção', 'ok' => 'OK'];
                        @endphp
                        <tr>
                            <td>{{ $record->patient->full_name }}</td>
                            <td>{{ $record->patient->record_number }}</td>
                            <td>{{ $record->indication }}</td>
                            <td>{{ $record->caliber }}</td>
                            <td>{{ \Carbon\Carbon::parse($record->insertion_date)->format('d/m/Y') }}</td>
                            <td>{{ \Carbon\Carbon::parse($record->min_removal_date)->format('d/m/Y') }}</td>
                            <td>{{ \Carbon\Carbon::parse($record->max_removal_date)->format('d/m/Y') }}</td>
                            <td class="{{ $record->days_left <= 0 ? 'text-danger' : '' }}">
                                {{ $record->days_left <= 0 ? 'VENCIDO' : $record->days_left.'d' }}
                            </td>
                            <td><span class="alert-badge {{ $record->alert_level }}">{{ $labels[$record->alert_level] }}</span></td>
                            <td>{{ $record->createdBy->name }}</td>
                            <td class="actions-cell">
                                <a href="{{ route('patients.show', $record->patient->id) }}" class="btn btn-secondary btn-xs">Ver</a>
                                <button wire:click="openNotifModal('{{ $record->id }}')" class="btn btn-success btn-xs">Notificar</button>
                                @can('manage', \App\Models\CatheterRecord::class)
                                <button wire:click="remove('{{ $record->id }}')"
                                        wire:confirm="Confirmar retirada do cateter?"
                                        class="btn btn-danger btn-xs">Retirar</button>
                                @endcan
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        {{ $records->links('vendor.pagination.default') }}
    @endif

    @if($showNotifModal)
        <div class="modal-overlay" wire:click="$set('showNotifModal', false)" x-data @keydown.escape.window="$wire.set('showNotifModal', false)">
            <div class="modal" @click.stop>
                <div class="modal-header">
                    <h2>Enviar Notificação</h2>
                    <button class="modal-close" wire:click="$set('showNotifModal', false)">×</button>
                </div>
                <form wire:submit="sendNotification" class="modal-form">
                    <div class="form-group">
                        <label>Destinatário</label>
                        <p style="margin:4px 0 0; font-weight:500; color:var(--gray-800);">{{ $notifPhone ?: '—' }}</p>
                    </div>
                    <div class="form-group">
                        <label>Mensagem</label>
                        <textarea wire:model="notifMessage" rows="6" style="resize:vertical;"></textarea>
                        @error('notifMessage') <span class="field-error">{{ $message }}</span> @enderror
                    </div>
                    <div class="modal-actions">
                        <button type="button" class="btn btn-ghost" wire:click="$set('showNotifModal', false)">Cancelar</button>
                        <button type="submit" class="btn btn-success" wire:loading.attr="disabled">
                            <span wire:loading.remove>Enviar</span>
                            <span wire:loading>Enviando...</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif
</div>
