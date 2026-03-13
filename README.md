# Sistema de Monitoramento de Cateteres

Aplicação web hospitalar para controle e monitoramento de cateteres vesicais. Permite registrar pacientes, acompanhar inserções e prazos de retirada, e enviar notificações de alerta.

---

## Índice

1. [Stack e Por Que Cada Escolha](#stack)
2. [Pré-requisitos](#pré-requisitos)
3. [Configuração do Ambiente](#configuração-do-ambiente)
4. [Variáveis de Ambiente](#variáveis-de-ambiente)
5. [Como Popular o Banco com Dados de Teste](#como-popular-o-banco-com-dados-de-teste)
6. [Como Rodar em Desenvolvimento](#como-rodar-em-desenvolvimento)
7. [Comandos Úteis do Dia a Dia](#comandos-úteis-do-dia-a-dia)
8. [Arquitetura do Projeto](#arquitetura-do-projeto)
9. [Estrutura de Pastas](#estrutura-de-pastas)
10. [Relacionamentos do Banco de Dados](#relacionamentos-do-banco-de-dados)
11. [Como Adicionar uma Nova Feature](#como-adicionar-uma-nova-feature)
12. [Perfis de Acesso](#perfis-de-acesso)
13. [Alertas Automáticos](#alertas-automáticos)
14. [Troubleshooting](#troubleshooting)

---

## Stack

| Camada | Tecnologia | Por quê |
|--------|-----------|---------|
| Backend | PHP 8.2+ / Laravel 12 | Framework principal — rotas, ORM, autenticação, agendamento |
| Frontend reativo | Livewire 3 | Componentes PHP que reagem a eventos sem precisar de uma API REST separada |
| JavaScript | Alpine.js 3 | Pequenas interações no cliente (abrir/fechar modal, máscara de telefone) |
| CSS | Tailwind CSS 3 + CSS customizado | Utilitários do Tailwind + variáveis e classes de layout próprias em `app.css` |
| Build | Vite 7 | Compila e empacota CSS/JS para produção |
| Banco de dados | PostgreSQL (recomendado) / SQLite | PostgreSQL para produção; SQLite para desenvolvimento local sem instalar nada |
| Autenticação | Laravel Breeze | Sessão por banco, sem JWT — simples e suficiente para uso interno |

### O que é Livewire?

Livewire é o coração da interatividade. Cada página é um **componente PHP** (`app/Livewire/`) com uma classe e uma view Blade correspondente. Quando o usuário clica em um botão com `wire:click`, o Livewire faz uma requisição AJAX transparente, chama o método PHP e re-renderiza só a parte da página que mudou. Você escreve apenas PHP — sem controllers REST, sem fetch/axios manual.

```
Usuário clica em "Notificar"
    → wire:click="openNotifModal('id')"
    → Livewire chama Catheters::openNotifModal()
    → Estado do componente muda ($showNotifModal = true)
    → Livewire re-renderiza o componente
    → Modal aparece na tela
```

---

## Pré-requisitos

- PHP >= 8.2 com extensões: `pdo_pgsql` (ou `pdo_sqlite`), `mbstring`, `openssl`, `tokenizer`, `xml`
- Composer
- Node.js >= 18 + npm
- PostgreSQL >= 14 **ou** SQLite (zero configuração)

Para verificar se tudo está instalado:

```bash
php -v          # deve mostrar 8.2+
composer -V
node -v         # deve mostrar 18+
psql --version  # se usar PostgreSQL
```

---

## Configuração do Ambiente

```bash
# 1. Clonar e entrar no projeto
git clone <url-do-repo>
cd cateter-laravel

# 2. Instalar dependências PHP e Node
composer install
npm install

# 3. Criar o arquivo de configuração
cp .env.example .env
php artisan key:generate   # gera APP_KEY automaticamente

# 4. Editar o .env com as configurações do banco (ver seção abaixo)

# 5. Criar as tabelas do banco
php artisan migrate

# 6. Popular com dados de teste
php artisan db:seed

# 7. Compilar os assets
npm run build

# 8. Iniciar o servidor
php artisan serve
```

Acesse: **http://localhost:8000**

---

## Variáveis de Ambiente

### Obrigatórias

| Variável | O que faz | Exemplo |
|----------|-----------|---------|
| `APP_KEY` | Chave de criptografia da sessão. Gerada automaticamente com `php artisan key:generate` | `base64:abc...` |
| `DB_CONNECTION` | Driver do banco | `pgsql` ou `sqlite` |

### Banco de Dados

**Opção 1 — PostgreSQL** (recomendado para desenvolvimento próximo da produção):

```env
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=cateter
DB_USERNAME=postgres
DB_PASSWORD=sua_senha
```

**Opção 2 — SQLite** (zero configuração, ótimo para começar):

```env
DB_CONNECTION=sqlite
# O arquivo database/database.sqlite é criado automaticamente no migrate
```

### Opcionais (têm padrão funcional)

| Variável | Padrão | Quando alterar |
|----------|--------|----------------|
| `APP_NAME` | `Laravel` | Altere para o nome exibido na aba do browser |
| `APP_ENV` | `local` | Use `production` em produção |
| `APP_DEBUG` | `true` | Mude para `false` em produção |
| `APP_URL` | `http://localhost` | URL real em produção |
| `SESSION_LIFETIME` | `120` | Minutos até expirar a sessão |
| `MAIL_MAILER` | `log` | E-mails vão para `storage/logs/laravel.log`. Configure `smtp` para envio real |

---

## Como Popular o Banco com Dados de Teste

```bash
# Seed inicial (só roda se o banco estiver vazio)
php artisan db:seed

# Apagar tudo e recomeçar do zero (útil durante desenvolvimento)
php artisan migrate:fresh --seed
```

### O que o seed cria

**Usuários:**

| Perfil | E-mail | Senha |
|--------|--------|-------|
| Administrador | admin@cateter.com | admin123 |
| Médico | medico@cateter.com | medico123 |

**30 pacientes** com 1–3 registros de cateter cada, em estados distribuídos:

| Pacientes | Estado do cateter ativo |
|-----------|------------------------|
| 1 a 5 | Vencido (prazo já passou) |
| 6 a 12 | Urgente (vence amanhã) |
| 13 a 20 | Atenção (vence em 2–3 dias) |
| 21 a 30 | OK (prazo confortável) |

Cateteres anteriores de cada paciente ficam com `removed_at` preenchido (histórico).

---

## Como Rodar em Desenvolvimento

### Opção simples (dois terminais)

**Terminal 1** — servidor PHP:
```bash
php artisan serve
```

**Terminal 2** — assets em tempo real (recompila CSS/JS ao salvar):
```bash
npm run dev
```

### Opção completa (um comando)

```bash
composer run dev
```

Sobe em paralelo: servidor HTTP, queue listener, log watcher (`pail`) e Vite dev server.

---

## Comandos Úteis do Dia a Dia

```bash
# Criar uma nova migration
php artisan make:migration add_campo_to_tabela

# Criar um novo componente Livewire (classe + view)
php artisan make:livewire NomeDoComponente

# Criar um novo model com migration
php artisan make:model NomeDoModel -m

# Rodar só as migrations novas
php artisan migrate

# Apagar tudo e recriar (perde os dados)
php artisan migrate:fresh --seed

# Limpar caches (fazer isso após mudar .env ou views)
php artisan view:clear
php artisan config:clear
php artisan cache:clear

# Testar no terminal interativo
php artisan tinker

# Disparar o envio de alertas automáticos manualmente
php artisan catheters:send-alerts

# Ver logs em tempo real
php artisan pail
```

---

## Arquitetura do Projeto

O projeto segue o padrão **Livewire Full-Stack**: não há API REST nem controllers tradicionais para as páginas. Cada rota aponta direto para um componente Livewire.

### Camadas

```
┌─────────────────────────────────────────────────┐
│  View (Blade)                                   │  resources/views/livewire/
│  wire:click, wire:model, @click (Alpine)        │
└────────────────────┬────────────────────────────┘
                     │ Livewire (AJAX automático)
┌────────────────────▼────────────────────────────┐
│  Componente Livewire (PHP)                      │  app/Livewire/
│  Propriedades públicas = estado da tela         │
│  Métodos públicos = ações do usuário            │
└────────────────────┬────────────────────────────┘
                     │
        ┌────────────┴────────────┐
        ▼                         ▼
┌───────────────┐        ┌────────────────────┐
│  Services     │        │  Models (Eloquent)  │
│  Lógica de    │        │  Acesso ao banco    │
│  negócio      │        │                    │
└───────────────┘        └────────────────────┘
```

### Services (regras de negócio)

| Service | Responsabilidade |
|---------|-----------------|
| `CatheterAlertService` | Calcula dias restantes e classifica o nível de alerta (ok/warning/urgent/overdue) |
| `NotificationService` | Monta a mensagem de notificação e salva no banco (manual ou automático) |

### Trait compartilhado

`HasNotificationModal` — contém o estado e o método `sendNotification()` do modal de envio. É reutilizado pelos componentes `Catheters` e `PatientDetail`, evitando duplicar código.

### Policies (autorização)

Definem quem pode fazer o quê:

| Policy | Controla |
|--------|----------|
| `PatientPolicy` | Criar e editar pacientes (só DOCTOR) |
| `CatheterRecordPolicy` | Registrar, editar e retirar cateteres (só DOCTOR) |

Uso nas views: `@can('manage', \App\Models\CatheterRecord::class)`
Uso nos componentes: `Gate::authorize('manage', CatheterRecord::class)`

---

## Estrutura de Pastas

```
cateter-laravel/
├── app/
│   ├── Console/Commands/
│   │   └── SendCatheterAlerts.php    # Envia alertas automáticos (agendado às 08:00)
│   ├── Helpers/
│   │   └── AuditHelper.php           # Helper estático para registrar ações no audit_logs
│   ├── Livewire/
│   │   ├── Concerns/
│   │   │   └── HasNotificationModal.php  # Trait: estado + lógica do modal de notificação
│   │   ├── Actions/Logout.php
│   │   ├── Forms/LoginForm.php
│   │   ├── Dashboard.php             # Alertas, estatísticas, gráficos
│   │   ├── Patients.php              # Lista + cadastro de pacientes
│   │   ├── PatientDetail.php         # Detalhes do paciente + cateteres + histórico
│   │   ├── Catheters.php             # Lista de cateteres ativos com filtros
│   │   ├── Users.php                 # Gestão de usuários (admin only)
│   │   ├── Notifications.php         # Histórico de notificações
│   │   ├── Logs.php                  # Log de auditoria (admin only)
│   │   └── Profile.php               # Perfil e troca de senha
│   ├── Models/
│   │   ├── User.php                  # role: ADMIN | DOCTOR
│   │   ├── Patient.php
│   │   ├── CatheterRecord.php        # Registro de cateter (ativo ou retirado)
│   │   ├── Notification.php          # Notificação enviada
│   │   ├── AuditLog.php              # Trilha de auditoria
│   │   └── AuthLog.php               # Log de login/logout
│   ├── Policies/
│   │   ├── PatientPolicy.php
│   │   └── CatheterRecordPolicy.php
│   ├── Providers/
│   │   └── AppServiceProvider.php    # Registra as policies explicitamente
│   └── Services/
│       ├── CatheterAlertService.php
│       └── NotificationService.php
│
├── database/
│   ├── migrations/                   # Uma migration por tabela/alteração
│   └── seeders/
│       ├── DatabaseSeeder.php        # Cria usuários e chama PatientSeeder
│       └── PatientSeeder.php         # Gera 30 pacientes com cateteres variados
│
├── resources/
│   ├── css/app.css                   # Variáveis CSS, layout, classes de componentes
│   ├── js/app.js                     # Bootstrap Livewire + Alpine
│   └── views/
│       ├── layouts/app.blade.php     # Layout base (sidebar, toast, scripts)
│       ├── livewire/                 # View de cada componente (mesmo nome da classe)
│       └── vendor/pagination/        # Template customizado de paginação
│
├── routes/
│   ├── web.php                       # Rotas → componentes Livewire
│   └── console.php                   # Schedule: catheters:send-alerts às 08:00
│
└── .env.example
```

---

## Relacionamentos do Banco de Dados

```
users
  └─< catheter_records (created_by_id, removed_by_id)
  └─< patients (created_by_id)
  └─< notifications (sent_by_id)
  └─< audit_logs (user_id)
  └─< auth_logs (user_id)

patients
  └─< catheter_records (patient_id)
  └─< notifications (patient_id)

catheter_records
  ├── patient_id → patients
  ├── created_by_id → users
  └── removed_by_id → users (nullable — null = cateter ainda ativo)
```

**Regra importante:** um cateter com `removed_at = null` é considerado **ativo**. Retirar um cateter = preencher `removed_at` e `removed_by_id`.

---

## Como Adicionar uma Nova Feature

### Exemplo: adicionar uma nova página "Relatórios"

**1. Criar o componente Livewire:**

```bash
php artisan make:livewire Reports
```

Isso cria:
- `app/Livewire/Reports.php` — a classe PHP
- `resources/views/livewire/reports.blade.php` — a view

**2. Adicionar a rota** em `routes/web.php`:

```php
Route::get('/reports', Reports::class)->name('reports')->middleware('auth');
```

**3. Implementar a classe** (`app/Livewire/Reports.php`):

```php
class Reports extends Component
{
    public function render()
    {
        $data = CatheterRecord::whereNotNull('removed_at')->get();
        return view('livewire.reports', compact('data'))->layout('layouts.app');
    }
}
```

**4. Criar a view** (`resources/views/livewire/reports.blade.php`):

```html
<div class="page">
    <div class="page-header">
        <h1>Relatórios</h1>
    </div>
    <!-- conteúdo aqui -->
</div>
```

**5. Adicionar no menu lateral** (`resources/views/layouts/app.blade.php`):

```html
<a href="{{ route('reports') }}" class="nav-link">Relatórios</a>
```

---

### Exemplo: adicionar um campo novo ao cateter

**1. Criar a migration:**

```bash
php artisan make:migration add_notes_to_catheter_records
```

**2. Editar a migration** (`database/migrations/..._add_notes_to_catheter_records.php`):

```php
public function up(): void
{
    Schema::table('catheter_records', function (Blueprint $table) {
        $table->text('notes')->nullable()->after('passage_type');
    });
}
```

**3. Rodar:**

```bash
php artisan migrate
```

**4. Adicionar ao `$fillable`** do model `CatheterRecord.php`:

```php
protected $fillable = [
    // ... existentes ...
    'notes',
];
```

**5. Adicionar propriedade e campo** no componente `PatientDetail.php` e na view `patient-detail.blade.php`.

---

## Perfis de Acesso

| Ação | ADMIN | DOCTOR |
|------|-------|--------|
| Ver dashboard, pacientes, cateteres, notificações | ✅ | ✅ |
| Cadastrar / editar pacientes | ❌ | ✅ |
| Registrar / editar / retirar cateteres | ❌ | ✅ |
| Enviar notificações manualmente | ✅ | ✅ |
| Gerenciar usuários | ✅ | ❌ |
| Ver logs de auditoria | ✅ | ❌ |

A verificação de acesso usa **Laravel Policies** — não verifique `role` diretamente nas views se puder usar `@can`.

---

## Alertas Automáticos

O command `catheters:send-alerts` roda **diariamente às 08:00** e envia notificações para cateteres próximos do prazo:

| Tipo | Condição |
|------|----------|
| `ALERT_3D` | `max_removal_date` = hoje + 3 dias |
| `ALERT_1D` | `max_removal_date` = hoje + 1 dia |
| `ALERT_DUE` | `max_removal_date` = hoje |
| `MANUAL` | Enviado pelo usuário via interface |

A deduplicação evita reenvio: se já existe uma notificação do mesmo tipo para o mesmo paciente no mesmo dia, ela é ignorada.

**Para ativar em produção**, adicione ao crontab do servidor:

```bash
* * * * * cd /caminho/do/projeto && php artisan schedule:run >> /dev/null 2>&1
```

**Para disparar manualmente:**

```bash
php artisan catheters:send-alerts
```

> **Nota:** atualmente o envio é um mock — salva no banco com `status = 'SENT'` mas não chama nenhuma API externa. Para integrar com WhatsApp (Evolution API), implemente a chamada HTTP em `NotificationService::sendAuto()`.

---

## Troubleshooting

### Modal não abre / Alpine dá erro `Expression: "$wire."`

Causa: `wire:click.stop` sem nome de método vira `$wire.` no Alpine.
Solução: use `@click.stop` no elemento filho em vez de `wire:click.stop`.

### `@can` não está escondendo elementos

Causa: policy não registrada.
Solução: verifique `AppServiceProvider.php` — as policies devem estar registradas com `Gate::policy()`.

### Mudei o `.env` mas nada mudou

```bash
php artisan config:clear
```

### Mudei uma view mas o browser mostra a versão antiga

```bash
php artisan view:clear
```

### Página fica em branco ou dá erro 500

```bash
php artisan pail   # ver logs em tempo real
# ou
tail -f storage/logs/laravel.log
```

### `migrate:fresh` dá erro de "table already exists"

Significa que o banco tem tabelas que não estão no histórico de migrations. Use:

```bash
php artisan migrate:fresh --seed
```

Isso apaga **todas** as tabelas e recria do zero.
