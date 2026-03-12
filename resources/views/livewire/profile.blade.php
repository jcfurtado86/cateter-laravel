<div>
    <button wire:click="openEdit" class="btn btn-ghost btn-sm" style="width:100%">Editar Perfil</button>
    <button wire:click="openPassword" class="btn btn-ghost btn-sm" style="width:100%">Alterar Senha</button>

    @if($showEditModal)
        <div class="modal-overlay" wire:click="$set('showEditModal', false)" x-data @keydown.escape.window="$wire.set('showEditModal', false)">
            <div class="modal" wire:click.stop>
                <div class="modal-header">
                    <h2>Editar Perfil</h2>
                    <button class="modal-close" wire:click="$set('showEditModal', false)">×</button>
                </div>
                <form wire:submit="saveProfile" class="modal-form">
                    @if($editError)
                        <div class="alert alert-error">{{ $editError }}</div>
                    @endif
                    <div class="form-group">
                        <label>Nome Completo</label>
                        <input type="text" wire:model="editName" />
                    </div>
                    <div class="form-group">
                        <label>Email</label>
                        <input type="email" wire:model="editEmail" />
                    </div>
                    <div class="modal-actions">
                        <button type="button" class="btn btn-ghost" wire:click="$set('showEditModal', false)">Cancelar</button>
                        <button type="submit" class="btn btn-primary" wire:loading.attr="disabled">
                            <span wire:loading.remove>Salvar</span>
                            <span wire:loading>Salvando...</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif

    @if($showPasswordModal)
        <div class="modal-overlay" wire:click="$set('showPasswordModal', false)" x-data @keydown.escape.window="$wire.set('showPasswordModal', false)">
            <div class="modal" wire:click.stop>
                <div class="modal-header">
                    <h2>Alterar Senha</h2>
                    <button class="modal-close" wire:click="$set('showPasswordModal', false)">×</button>
                </div>
                <form wire:submit="savePassword" class="modal-form">
                    @if($passwordError)
                        <div class="alert alert-error">{{ $passwordError }}</div>
                    @endif
                    <div class="form-group">
                        <label>Senha Atual</label>
                        <input type="password" wire:model="oldPassword" autocomplete="current-password" placeholder="••••••••" />
                    </div>
                    <div class="form-group">
                        <label>Nova Senha</label>
                        <input type="password" wire:model="newPassword" autocomplete="new-password" placeholder="••••••••" />
                        <small style="color:#666;margin-top:4px">Mínimo 6 caracteres</small>
                    </div>
                    <div class="form-group">
                        <label>Confirmar Nova Senha</label>
                        <input type="password" wire:model="confirmPassword" autocomplete="new-password" placeholder="••••••••" />
                    </div>
                    <div class="modal-actions">
                        <button type="button" class="btn btn-ghost" wire:click="$set('showPasswordModal', false)">Cancelar</button>
                        <button type="submit" class="btn btn-primary" wire:loading.attr="disabled">
                            <span wire:loading.remove>Alterar Senha</span>
                            <span wire:loading>Alterando...</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif
</div>
