# Sistema de Monitoramento de Cateteres

Aplicação web hospitalar para controle e monitoramento de cateteres vesicais, com rastreamento de prazos, histórico por paciente, alertas automáticos e notificações.

---

## Stack Utilizada

| Camada | Tecnologia |
|--------|-----------|
| Backend | PHP 8.2+ / Laravel 12 |
| Frontend reativo | Livewire 3 |
| JavaScript | Alpine.js 3 |
| CSS | Tailwind CSS 3 (com CSS customizado) |
| Build | Vite 7 |
| Banco de dados | PostgreSQL (recomendado) ou SQLite |
| Autenticação | Laravel Breeze (sessão por banco) |

---

## Pré-requisitos

- PHP >= 8.2 com extensões: `pdo_pgsql`, `mbstring`, `openssl`, `tokenizer`, `xml`
- Composer
- Node.js >= 18 + npm
- PostgreSQL >= 14 (ou SQLite para desenvolvimento rápido)

---

## Como Rodar em Desenvolvimento

### 1. Instalar dependências

```bash
composer install
npm install
```

### 2. Configurar o ambiente

```bash
cp .env.example .env
php artisan key:generate
```

Edite o `.env` com as configurações do seu banco (ver seção [Variáveis de Ambiente](#variáveis-de-ambiente)).

### 3. Criar e migrar o banco

```bash
php artisan migrate
```

### 4. Popular com dados de mock

```bash
php artisan db:seed
```

Isso cria 2 usuários padrão e 30 pacientes com histórico de cateteres em estados variados (vencidos, urgentes, em atenção, ok).

### 5. Compilar assets

```bash
npm run build
# ou em modo watch durante o desenvolvimento:
npm run dev
```

### 6. Iniciar o servidor

```bash
php artisan serve
```

Acesse: `http://localhost:8000`

---

### Atalho: rodar tudo junto (composer script)

```bash
composer run dev
```

Sobe em paralelo: servidor HTTP, queue listener, log watcher e Vite dev server.

---

## Credenciais Padrão (após seed)

| Perfil | E-mail | Senha |
|--------|--------|-------|
| Administrador | admin@cateter.com | admin123 |
| Médico | medico@cateter.com | medico123 |

---

## Variáveis de Ambiente

### Aplicação

| Variável | Descrição | Padrão |
|----------|-----------|--------|
| `APP_NAME` | Nome da aplicação exibido na interface | `Laravel` |
| `APP_ENV` | Ambiente (`local`, `production`) | `local` |
| `APP_KEY` | Chave de criptografia — gerada com `php artisan key:generate` | — |
| `APP_DEBUG` | Exibe erros detalhados | `true` |
| `APP_URL` | URL base da aplicação | `http://localhost` |

### Banco de Dados

Para **PostgreSQL** (recomendado):

```env
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=cateter
DB_USERNAME=postgres
DB_PASSWORD=sua_senha
```

Para **SQLite** (desenvolvimento rápido, sem instalar Postgres):

```env
DB_CONNECTION=sqlite
# Cria o arquivo automaticamente em database/database.sqlite
```

### Sessão, Cache e Filas

| Variável | Descrição | Padrão |
|----------|-----------|--------|
| `SESSION_DRIVER` | Driver de sessão | `database` |
| `SESSION_LIFETIME` | Tempo de sessão em minutos | `120` |
| `QUEUE_CONNECTION` | Driver de fila | `database` |
| `CACHE_STORE` | Driver de cache | `database` |

### E-mail (opcional)

Por padrão, e-mails são escritos no log (`MAIL_MAILER=log`). Para envio real, configure um servidor SMTP:

```env
MAIL_MAILER=smtp
MAIL_HOST=smtp.seuservidor.com
MAIL_PORT=587
MAIL_USERNAME=seu@email.com
MAIL_PASSWORD=sua_senha
MAIL_FROM_ADDRESS=noreply@hospital.com
MAIL_FROM_NAME="Sistema Cateter"
```

---

## Rodando o Seed

O seed popula o banco com dados realistas para testes:

```bash
# Seed completo (usuários + pacientes + cateteres)
php artisan db:seed

# Limpar o banco e re-seedar do zero
php artisan migrate:fresh --seed
```

### O que o seed cria

- **2 usuários**: Administrador e Médico
- **30 pacientes** com nomes, prontuários, dados demográficos e telefones fictícios
- **1 a 3 registros de cateter por paciente**, distribuídos em estados variados:
  - Pacientes 1–5: cateter ativo **vencido**
  - Pacientes 6–12: cateter ativo vencendo **amanhã (urgente)**
  - Pacientes 13–20: cateter ativo vencendo em **2–3 dias (atenção)**
  - Pacientes 21–30: cateter ativo com prazo **confortável**
  - Cateteres anteriores (histórico) ficam como retirados

---

## Estrutura de Pastas

```
cateter-laravel/
├── app/
│   ├── Console/
│   │   └── Commands/
│   │       └── SendCatheterAlerts.php   # Command agendado: envia alertas automáticos
│   ├── Helpers/
│   │   └── AuditHelper.php              # Registra ações no log de auditoria
│   ├── Http/
│   │   └── Middleware/                  # Middlewares HTTP (autenticação, etc.)
│   ├── Livewire/
│   │   ├── Concerns/
│   │   │   └── HasNotificationModal.php # Trait: lógica do modal de notificação
│   │   ├── Actions/
│   │   │   └── Logout.php               # Ação de logout
│   │   ├── Forms/
│   │   │   └── LoginForm.php            # Formulário de login
│   │   ├── Dashboard.php                # Página inicial com alertas e estatísticas
│   │   ├── Patients.php                 # Listagem e cadastro de pacientes
│   │   ├── PatientDetail.php            # Detalhes, cateteres e notificações por paciente
│   │   ├── Catheters.php                # Lista de cateteres ativos com filtros
│   │   ├── Users.php                    # Gestão de usuários (admin only)
│   │   ├── Notifications.php            # Histórico de notificações
│   │   ├── Logs.php                     # Log de auditoria (admin only)
│   │   └── Profile.php                  # Perfil e troca de senha do usuário
│   ├── Models/
│   │   ├── User.php                     # Usuário do sistema (ADMIN ou DOCTOR)
│   │   ├── Patient.php                  # Paciente
│   │   ├── CatheterRecord.php           # Registro de cateter
│   │   ├── Notification.php             # Notificação enviada
│   │   ├── AuditLog.php                 # Entrada de auditoria
│   │   └── AuthLog.php                  # Log de autenticação
│   ├── Policies/
│   │   ├── PatientPolicy.php            # Autorização: quem pode criar/editar pacientes
│   │   └── CatheterRecordPolicy.php     # Autorização: quem pode registrar/retirar cateteres
│   ├── Providers/
│   │   └── AppServiceProvider.php       # Registro de policies e configurações globais
│   └── Services/
│       ├── CatheterAlertService.php     # Calcula dias restantes e nível de alerta
│       └── NotificationService.php      # Monta e registra notificações (manual e automático)
│
├── database/
│   ├── migrations/                      # Estrutura do banco de dados
│   └── seeders/
│       ├── DatabaseSeeder.php           # Orquestra o seed (usuários + chama PatientSeeder)
│       └── PatientSeeder.php            # Gera 30 pacientes com cateteres em estados variados
│
├── resources/
│   ├── css/
│   │   └── app.css                      # Estilos customizados (variáveis, layout, componentes)
│   ├── js/
│   │   └── app.js                       # Bootstrap do Alpine.js e Livewire
│   └── views/
│       ├── layouts/
│       │   └── app.blade.php            # Layout base com sidebar e toast
│       ├── livewire/                    # Views dos componentes Livewire
│       │   ├── dashboard.blade.php
│       │   ├── patients.blade.php
│       │   ├── patient-detail.blade.php
│       │   ├── catheters.blade.php
│       │   ├── users.blade.php
│       │   ├── notifications.blade.php
│       │   ├── logs.blade.php
│       │   └── profile.blade.php
│       └── vendor/
│           └── pagination/
│               └── default.blade.php    # Template customizado de paginação
│
├── routes/
│   ├── web.php                          # Rotas da aplicação
│   └── console.php                      # Agendamento: catheters:send-alerts às 08:00
│
├── .env.example                         # Template de variáveis de ambiente
├── composer.json
└── package.json
```

---

## Alertas Automáticos

O sistema envia notificações automáticas quando o prazo máximo de retirada se aproxima:

| Tipo | Quando |
|------|--------|
| `ALERT_3D` | 3 dias antes do prazo máximo |
| `ALERT_1D` | 1 dia antes |
| `ALERT_DUE` | No dia do vencimento |
| `MANUAL` | Enviado manualmente pelo usuário via interface |

O command é agendado para rodar **diariamente às 08:00**. Em produção, adicione ao crontab do servidor:

```bash
* * * * * cd /caminho/do/projeto && php artisan schedule:run >> /dev/null 2>&1
```

Para disparar manualmente:

```bash
php artisan catheters:send-alerts
```

---

## Perfis de Acesso

| Ação | ADMIN | DOCTOR |
|------|-------|--------|
| Ver dashboard, pacientes, cateteres | ✅ | ✅ |
| Cadastrar / editar pacientes | ❌ | ✅ |
| Registrar / editar / retirar cateteres | ❌ | ✅ |
| Enviar notificações | ✅ | ✅ |
| Gerenciar usuários | ✅ | ❌ |
| Ver logs de auditoria | ✅ | ❌ |
