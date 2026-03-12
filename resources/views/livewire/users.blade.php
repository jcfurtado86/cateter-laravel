<div class="page">

    <div class="page-header">
        <h1>Usuários</h1>
        <button wire:click="openNew" class="btn btn-primary">+ Novo Usuário</button>
    </div>

    <div class="table-container">
        <table class="table">
            <thead>
                <tr>
                    <th>Nome</th>
                    <th>Email</th>
                    <th>Perfil</th>
                    <th>Status</th>
                    <th>Ações</th>
                </tr>
            </thead>
            <tbody>
                @forelse($users as $u)
                    <tr>
                        <td>{{ $u->name }}</td>
                        <td>{{ $u->email }}</td>
                        <td>{{ $u->role === 'ADMIN' ? 'Administrador' : 'Médico' }}</td>
                        <td>
                            <span class="badge {{ $u->active ? 'badge-active' : 'badge-inactive' }}">
                                {{ $u->active ? 'Ativo' : 'Inativo' }}
                            </span>
                        </td>
                        <td class="actions-cell">
                            <button wire:click="openEdit('{{ $u->id }}')" class="btn btn-secondary btn-xs">Editar</button>
                            @if($u->active)
                                <button wire:click="deactivate('{{ $u->id }}')"
                                        wire:confirm="Desativar este usuário?"
                                        class="btn btn-danger btn-xs">Desativar</button>
                            @else
                                <button wire:click="activate('{{ $u->id }}')"
                                        wire:confirm="Reativar este usuário?"
                                        class="btn btn-success btn-xs">Ativar</button>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="text-center">Nenhum usuário encontrado</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    {{ $users->links() }}

    @if($showModal)
        <div class="modal-overlay" wire:click="$set('showModal', false)">
            <div class="modal" wire:click.stop>
                <div class="modal-header">
                    <h2>{{ $editingId ? 'Editar Usuário' : 'Novo Usuário' }}</h2>
                    <button class="modal-close" wire:click="$set('showModal', false)">×</button>
                </div>
                <form wire:submit="save" class="modal-form">
                    @if($formError)
                        <div class="alert alert-error">{{ $formError }}</div>
                    @endif
                    <div class="form-group">
                        <label>Nome</label>
                        <input type="text" wire:model="name" />
                        @error('name') <span class="field-error">{{ $message }}</span> @enderror
                    </div>
                    <div class="form-group">
                        <label>Email</label>
                        <input type="email" wire:model="email" />
                        @error('email') <span class="field-error">{{ $message }}</span> @enderror
                    </div>
                    <div class="form-group">
                        <label>{{ $editingId ? 'Nova Senha (deixe em branco para manter)' : 'Senha' }}</label>
                        <input type="password" wire:model="password" autocomplete="new-password" />
                        @error('password') <span class="field-error">{{ $message }}</span> @enderror
                    </div>
                    <div class="form-group">
                        <label>Perfil</label>
                        <select wire:model="role">
                            <option value="DOCTOR">Médico</option>
                            <option value="ADMIN">Administrador</option>
                        </select>
                    </div>
                    <div class="modal-actions">
                        <button type="button" class="btn btn-ghost" wire:click="$set('showModal', false)">Cancelar</button>
                        <button type="submit" class="btn btn-primary" wire:loading.attr="disabled">
                            <span wire:loading.remove>{{ $editingId ? 'Salvar' : 'Criar' }}</span>
                            <span wire:loading>Salvando...</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif
</div>
