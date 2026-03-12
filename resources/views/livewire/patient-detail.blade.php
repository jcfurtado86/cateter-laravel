<div class="page">
    <div class="page-header">
        <div>
            <a href="{{ route('patients') }}" class="back-link">← Pacientes</a>
            <h1>{{ $patient->full_name }}</h1>
            <p class="text-muted">Prontuário: {{ $patient->record_number }}</p>
        </div>
        <div class="header-actions">
            <button wire:click="openNotifModal" class="btn btn-success">Enviar Notificação</button>
            @if(auth()->user()->role === 'DOCTOR')
                <button wire:click="openNewCatheter" class="btn btn-primary">+ Registrar Cateter</button>
            @endif
        </div>
    </div>

    @php
        $activeRecords = $patient->catheterRecords->whereNull('removed_at');
        $cols = '280px' . str_repeat(' 1fr', $activeRecords->count() ?: 1);
    @endphp
    <div style="display:grid; grid-template-columns:{{ $cols }}; gap:16px;">
        <div class="card" style="border-top:4px solid #3b82f6;">
            <div class="card-header-row"><h3>Dados do Paciente</h3></div>
            <dl class="data-list">
                <dt>Nascimento</dt>
                <dd>{{ $patient->birth_date->format('d/m/Y') }} ({{ $patient->birth_date->age }} anos)</dd>
                <dt>Sexo</dt>
                <dd>{{ ['M'=>'Masculino','F'=>'Feminino','OUTRO'=>'Outro'][$patient->sex] }}</dd>
                <dt>Raça</dt><dd>{{ $patient->race }}</dd>
                <dt>Telefone</dt><dd>{{ $patient->phone }}</dd>
                @if($patient->createdBy)
                    <dt>Cadastrado por</dt><dd>{{ $patient->createdBy->name }}</dd>
                @endif
            </dl>
        </div>

        @foreach($activeRecords as $active)
            @php
                $days = (int) ceil((strtotime($active->max_removal_date) - time()) / 86400);
                $cardClass = $days <= 0 ? 'card-danger' : ($days <= 3 ? 'card-warning' : '');
            @endphp
            <div class="card {{ $cardClass }}" style="border-top:4px solid {{ $days <= 0 ? '#ef4444' : ($days <= 3 ? '#f59e0b' : '#22c55e') }}; display:flex; flex-direction:column;">
                <div class="card-header-row"><h3>Cateter Ativo</h3></div>
                <dl class="data-list" style="flex:1;">
                    <dt>Inserção</dt><dd>{{ \Carbon\Carbon::parse($active->insertion_date)->format('d/m/Y H:i') }}</dd>
                    <dt>Indicação</dt><dd>{{ $active->indication }}</dd>
                    <dt>Calibre</dt><dd>{{ $active->caliber }}</dd>
                    <dt>Lado</dt><dd>{{ $active->insertion_side === 'DIREITO' ? 'Direito' : 'Esquerdo' }}</dd>
                    <dt>Prazo Mín. Retirada</dt><dd>{{ \Carbon\Carbon::parse($active->min_removal_date)->format('d/m/Y') }}</dd>
                    <dt>Prazo Máx. Retirada</dt>
                    <dd class="{{ $days <= 0 ? 'text-danger' : '' }}">
                        {{ \Carbon\Carbon::parse($active->max_removal_date)->format('d/m/Y') }}
                        <span class="text-muted">({{ $days <= 0 ? 'VENCIDO' : $days.' dias restantes' }})</span>
                    </dd>
                </dl>
                @if(auth()->user()->role === 'DOCTOR')
                    <div style="display:flex; justify-content:flex-end; margin-top:12px;">
                        <button wire:click="removeCatheter('{{ $active->id }}')"
                                wire:confirm="Confirmar retirada do cateter?"
                                class="btn btn-danger btn-sm">
                            Registrar Retirada
                        </button>
                    </div>
                @endif
            </div>
        @endforeach
    </div>

    <div class="section" style="margin-top:24px">
        <h2>Histórico de Cateteres</h2>
        @if($patient->catheterRecords->isEmpty())
            <div class="empty-state">Nenhum registro.</div>
        @else
            <div class="table-container">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Data Inserção</th>
                            <th>Tipo</th>
                            <th>Indicação</th>
                            <th>Calibre</th>
                            <th>Prazo Máx.</th>
                            <th>Retirado em</th>
                            <th>Cadastrado por</th>
                            <th>Retirado por</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($patient->catheterRecords->sortBy(fn($r) => [is_null($r->removed_at) ? 0 : 1, -$r->insertion_date->timestamp]) as $r)
                            <tr>
                                <td>{{ \Carbon\Carbon::parse($r->insertion_date)->format('d/m/Y H:i') }}</td>
                                <td>{{ $r->procedure_type === 'ELETIVO' ? 'Eletivo' : 'Urgência' }}</td>
                                <td>{{ $r->indication }}</td>
                                <td>{{ $r->caliber }}</td>
                                <td>{{ \Carbon\Carbon::parse($r->max_removal_date)->format('d/m/Y') }}</td>
                                <td>
                                    @if($r->removed_at)
                                        {{ \Carbon\Carbon::parse($r->removed_at)->format('d/m/Y H:i') }}
                                    @else
                                        <span class="badge badge-active">Ativo</span>
                                    @endif
                                </td>
                                <td>{{ $r->createdBy->name }}</td>
                                <td>{{ $r->removedBy?->name ?? '—' }}</td>
                                <td>
                                    @if(!$r->removed_at && auth()->user()->role === 'DOCTOR')
                                        <button wire:click="openEditCatheter('{{ $r->id }}')" class="btn btn-secondary btn-xs">Editar</button>
                                    @elseif($r->removed_at)
                                        <button wire:click="openDetailModal('{{ $r->id }}')" class="btn btn-ghost btn-xs">Ver detalhes</button>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>

    {{-- Modal Notificação --}}
    @if($showNotifModal)
        <div class="modal-overlay" wire:click="$set('showNotifModal', false)">
            <div class="modal" wire:click.stop>
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

    {{-- Modal Cateter --}}
    @if($showCatheterModal)
        <div class="modal-overlay" wire:click="$set('showCatheterModal', false)">
            <div class="modal modal-lg" wire:click.stop>
                <div class="modal-header">
                    <h2>{{ $editingCatheterId ? 'Editar Cateter' : 'Registrar Cateter' }}</h2>
                    <button class="modal-close" wire:click="$set('showCatheterModal', false)">×</button>
                </div>
                <form wire:submit="saveCatheter" class="modal-form">
                    @if($formError)
                        <div class="alert alert-error">{{ $formError }}</div>
                    @endif
                    <div class="form-row">
                        <div class="form-group">
                            <label>Data e Hora de Colocação</label>
                            <input type="datetime-local" wire:model="insertionDate" />
                            @error('insertionDate') <span class="field-error">{{ $message }}</span> @enderror
                        </div>
                        <div class="form-group">
                            <label>Tipo de Procedimento</label>
                            <select wire:model="procedureType">
                                <option value="ELETIVO">Eletivo</option>
                                <option value="URGENCIA">Urgência</option>
                            </select>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Indicação</label>
                        <input type="text" wire:model="indication" />
                        @error('indication') <span class="field-error">{{ $message }}</span> @enderror
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label>Calibre</label>
                            <input type="text" wire:model="caliber" placeholder="Ex: 18Fr" />
                        </div>
                        <div class="form-group">
                            <label>Lado de Inserção</label>
                            <select wire:model="insertionSide">
                                <option value="DIREITO">Direito</option>
                                <option value="ESQUERDO">Esquerdo</option>
                            </select>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Tipo de Passagem</label>
                        <input type="text" wire:model="passageType" />
                        @error('passageType') <span class="field-error">{{ $message }}</span> @enderror
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label>Período Mínimo (dias)</label>
                            <input type="number" wire:model="minDays" min="1" />
                        </div>
                        <div class="form-group">
                            <label>Período Máximo (dias)</label>
                            <input type="number" wire:model="maxDays" min="1" />
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group checkbox-group">
                            <label>
                                <input type="checkbox" wire:model="hadPreviousCatheter" />
                                Possuía cateter prévio
                            </label>
                        </div>
                        <div class="form-group checkbox-group">
                            <label>
                                <input type="checkbox" wire:model="safetyWire" />
                                Fio de segurança
                            </label>
                        </div>
                    </div>
                    <div class="modal-actions">
                        <button type="button" class="btn btn-ghost" wire:click="$set('showCatheterModal', false)">Cancelar</button>
                        <button type="submit" class="btn btn-primary" wire:loading.attr="disabled">
                            <span wire:loading.remove>{{ $editingCatheterId ? 'Salvar' : 'Registrar' }}</span>
                            <span wire:loading>Salvando...</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif

    {{-- Modal Detalhes Cateter --}}
    @if($showDetailModal && $detailRecord)
        <div class="modal-overlay" wire:click="$set('showDetailModal', false)">
            <div class="modal" wire:click.stop>
                <div class="modal-header">
                    <h2>Detalhes do Cateter</h2>
                    <button class="modal-close" wire:click="$set('showDetailModal', false)">×</button>
                </div>
                <dl class="data-list" style="padding:0 4px;">
                    <dt>Inserção</dt><dd>{{ $detailRecord['insertion_date'] }}</dd>
                    <dt>Tipo de Procedimento</dt><dd>{{ $detailRecord['procedure_type'] }}</dd>
                    <dt>Indicação</dt><dd>{{ $detailRecord['indication'] }}</dd>
                    <dt>Calibre</dt><dd>{{ $detailRecord['caliber'] }}</dd>
                    <dt>Lado</dt><dd>{{ $detailRecord['insertion_side'] }}</dd>
                    <dt>Tipo de Passagem</dt><dd>{{ $detailRecord['passage_type'] }}</dd>
                    <dt>Fio de Segurança</dt><dd>{{ $detailRecord['safety_wire'] }}</dd>
                    <dt>Cateter Prévio</dt><dd>{{ $detailRecord['had_previous_catheter'] }}</dd>
                    <dt>Prazo Mín. Retirada</dt><dd>{{ $detailRecord['min_removal_date'] }}</dd>
                    <dt>Prazo Máx. Retirada</dt><dd>{{ $detailRecord['max_removal_date'] }}</dd>
                    <dt>Retirado em</dt><dd>{{ $detailRecord['removed_at'] }}</dd>
                    <dt>Cadastrado por</dt><dd>{{ $detailRecord['created_by'] }}</dd>
                    <dt>Retirado por</dt><dd>{{ $detailRecord['removed_by'] }}</dd>
                </dl>
                <div class="modal-actions" style="margin-top:16px;">
                    <button class="btn btn-ghost" wire:click="$set('showDetailModal', false)">Fechar</button>
                </div>
            </div>
        </div>
    @endif
</div>
