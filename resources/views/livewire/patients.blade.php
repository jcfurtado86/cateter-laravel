<div class="page">
    <div class="page-header">
        <h1>Pacientes</h1>
        @if(auth()->user()->role === 'DOCTOR')
            <button wire:click="openNew" class="btn btn-primary">+ Novo Paciente</button>
        @endif
    </div>

    <div class="search-bar">
        <input type="text" wire:model.live.debounce.300ms="search"
               placeholder="Buscar por nome ou prontuário..." class="search-input" />
        @if($search)
            <button wire:click="$set('search', '')" class="btn btn-ghost">Limpar</button>
        @endif
    </div>

    <div class="table-container">
        <table class="table">
            <thead>
                <tr>
                    <th>Nome</th>
                    <th>Prontuário</th>
                    <th>Nascimento / Idade</th>
                    <th>Sexo</th>
                    <th>Telefone</th>
                    <th>Cateter Ativo</th>
                    <th>Ações</th>
                </tr>
            </thead>
            <tbody>
                @forelse($patients as $p)
                    @php
                        $hasActive = $p->catheterRecords->isNotEmpty();
                        $sexLabels = ['M' => 'Masculino', 'F' => 'Feminino', 'OUTRO' => 'Outro'];
                    @endphp
                    <tr>
                        <td>{{ $p->full_name }}</td>
                        <td>{{ $p->record_number }}</td>
                        <td>
                            {{ $p->birth_date->format('d/m/Y') }}<br>
                            <small class="text-muted">{{ $p->birth_date->age }} anos</small>
                        </td>
                        <td>{{ $sexLabels[$p->sex] ?? $p->sex }}</td>
                        <td>{{ $p->phone }}</td>
                        <td>
                            @if($hasActive)
                                <span class="badge badge-active">Sim</span>
                            @else
                                <span class="badge badge-inactive">Não</span>
                            @endif
                        </td>
                        <td class="actions-cell">
                            @if(auth()->user()->role === 'DOCTOR')
                                <button wire:click="openEdit('{{ $p->id }}')" class="btn btn-secondary btn-xs">Editar</button>
                            @endif
                            <a href="{{ route('patients.show', $p->id) }}" class="btn btn-secondary btn-xs">Ver detalhes</a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="text-center">Nenhum paciente encontrado</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{ $patients->links('vendor.pagination.default') }}

    @if($showModal)
        <div class="modal-overlay" wire:click="$set('showModal', false)">
            <div class="modal" wire:click.stop>
                <div class="modal-header">
                    <h2>{{ $editingId ? 'Editar Paciente' : 'Novo Paciente' }}</h2>
                    <button class="modal-close" wire:click="$set('showModal', false)">×</button>
                </div>
                <form wire:submit="save" class="modal-form">
                    @if($formError)
                        <div class="alert alert-error">{{ $formError }}</div>
                    @endif
                    <div class="form-row">
                        <div class="form-group">
                            <label>Nome Completo</label>
                            <input type="text" wire:model="fullName" />
                            @error('fullName') <span class="field-error">{{ $message }}</span> @enderror
                        </div>
                        <div class="form-group">
                            <label>Nº Prontuário</label>
                            <input type="text" wire:model="recordNumber" />
                            @error('recordNumber') <span class="field-error">{{ $message }}</span> @enderror
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label>Data de Nascimento</label>
                            <input type="date" wire:model="birthDate" />
                        </div>
                        <div class="form-group">
                            <label>Sexo</label>
                            <select wire:model="sex">
                                <option value="M">Masculino</option>
                                <option value="F">Feminino</option>
                                <option value="OUTRO">Outro</option>
                            </select>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label>Raça</label>
                            <select wire:model="race">
                                <option value="BRANCA">Branca</option>
                                <option value="PARDA">Parda</option>
                                <option value="PRETA">Preta</option>
                                <option value="AMARELA">Amarela</option>
                                <option value="INDIGENA">Indígena</option>
                                <option value="NAO_INFORMADA">Não Informada</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Telefone</label>
                            <input type="tel" maxlength="15"
                                x-data
                                x-init="$el.value = $wire.phone ?? ''"
                                x-on:input="
                                    let d = $el.value.replace(/\D/g,'').slice(0,11);
                                    let f = d.length > 6
                                        ? '(' + d.slice(0,2) + ') ' + d.slice(2,7) + '-' + d.slice(7)
                                        : d.length > 2
                                            ? '(' + d.slice(0,2) + ') ' + d.slice(2)
                                            : d.length ? '(' + d : d;
                                    $el.value = f;
                                    $wire.set('phone', f);
                                " />
                        </div>
                    </div>
                    <div class="modal-actions">
                        <button type="button" class="btn btn-ghost" wire:click="$set('showModal', false)">Cancelar</button>
                        <button type="submit" class="btn btn-primary" wire:loading.attr="disabled">
                            <span wire:loading.remove>{{ $editingId ? 'Salvar' : 'Cadastrar' }}</span>
                            <span wire:loading>Salvando...</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif
</div>
