<div class="page">
    <div class="page-header">
        <div>
            <a href="{{ route('patients') }}" class="back-link">← Pacientes</a>
            <h1>{{ $patient->full_name }}</h1>
            <p class="text-muted">Prontuário: {{ $patient->record_number }}</p>
        </div>
        @if(auth()->user()->role === 'DOCTOR')
            <div class="header-actions">
                <button wire:click="openNewCatheter" class="btn btn-primary">+ Registrar Cateter</button>
            </div>
        @endif
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
                    <dt>Inserção</dt><dd>{{ \Carbon\Carbon::parse($active->insertion_date)->format('d/m/Y') }}</dd>
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
                                <td>{{ \Carbon\Carbon::parse($r->insertion_date)->format('d/m/Y') }}</td>
                                <td>{{ $r->procedure_type === 'ELETIVO' ? 'Eletivo' : 'Urgência' }}</td>
                                <td>{{ $r->indication }}</td>
                                <td>{{ $r->caliber }}</td>
                                <td>{{ \Carbon\Carbon::parse($r->max_removal_date)->format('d/m/Y') }}</td>
                                <td>
                                    @if($r->removed_at)
                                        {{ \Carbon\Carbon::parse($r->removed_at)->format('d/m/Y') }}
                                    @else
                                        <span class="badge badge-active">Ativo</span>
                                    @endif
                                </td>
                                <td>{{ $r->createdBy->name }}</td>
                                <td>{{ $r->removedBy?->name ?? '—' }}</td>
                                <td>
                                    @if(!$r->removed_at && auth()->user()->role === 'DOCTOR')
                                        <button wire:click="openEditCatheter('{{ $r->id }}')" class="btn btn-secondary btn-xs">Editar</button>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>

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
                            <label>Data de Colocação</label>
                            <input type="date" wire:model="insertionDate" />
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
</div>
