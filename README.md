# 🚢 Sistema SSO - Importe Melhor

Sistema de Single Sign-On (SSO) usando Microsoft Azure AD (Entra ID) e PostgreSQL para centralizar a autenticação de todas as ferramentas da Importe Melhor.

## 🎯 Funcionalidades

- ✅ Login único com Microsoft (Azure AD)
- ✅ Autenticação compartilhada entre aplicações
- ✅ Controle de acesso por aplicação
- ✅ Logs de auditoria
- ✅ Sessões persistentes (7 dias)
- ✅ PostgreSQL para dados

## 📋 Requisitos

- PHP 7.4 ou superior
- PostgreSQL 12 ou superior
- Extensões PHP: PDO, pgsql, curl, json
- HTTPS obrigatório (para cookies seguros)
- Conta Microsoft 365 / Azure AD

## 🚀 Deploy no Easypanel

### 1. Criar Projeto no Easypanel
```
Projeto: auth-sso
├── Serviço 1: bd-sso (PostgreSQL)
└── Serviço 2: auth-app (PHP)
```

### 2. Configurar PostgreSQL

No serviço `bd-sso`, importe o schema SQL disponível em `/database/schema.sql`

### 3. Conectar GitHub

1. No Easypanel, configure o serviço `auth-app` para usar este repositório
2. Branch: `main`
3. Build Command: (deixe vazio para PHP)
4. Start Command: (deixe vazio para PHP)

### 4. Configurar Variáveis de Ambiente

No Easypanel, adicione as variáveis do arquivo `.env.example` com os valores reais:
```env
AZURE_CLIENT_ID=ac66d4b8-04a0-4534-9e02-3a7a49778af8
AZURE_CLIENT_SECRET=1j78Q~gZvsHljdaYw-LER~d_PXCYuDpV1uVVJbkI
AZURE_TENANT_ID=6d30004d-ea6b-4445-8824-ea27215314dd
DB_HOST=bd-sso
DB_PORT=5432
DB_NAME=importemelhor_sso
DB_USER=sso_user
DB_PASS=SUA_SENHA_DO_BANCO
APP_URL=https://auth.importemelhor.com
COOKIE_DOMAIN=.importemelhor.com
COOKIE_SECURE=true
```

### 5. Configurar Domínio e SSL

1. Adicione o domínio: `auth.importemelhor.com`
2. Ative o certificado SSL automático

## 🔧 Configuração Azure AD

Já configurado com:
- **Tenant ID:** `6d30004d-ea6b-4445-8824-ea27215314dd`
- **Client ID:** `ac66d4b8-04a0-4534-9e02-3a7a49778af8`
- **Redirect URI:** `https://auth.importemelhor.com/callback.php`

## 📦 Integrando em Outras Aplicações

### 1. Copiar o arquivo `sso-client.php`

Copie `sso-client.php` para cada aplicação que precisa de autenticação.

### 2. Adicionar no início de cada página protegida
```php
<?php
require_once 'sso-client.php';

// Substitua 'app-slug' pelo slug da aplicação no banco
$sso = new SSOClient('cca-calc'); // ou 'cleanlog', 'ecf-canton-fair', etc.
$user = $sso->getUser();

// A partir daqui, a página só é acessível se estiver logado
// Você tem acesso a:
// $user['user_id']
// $user['email']
// $user['name']
// $user['photo_url']
?>

<!DOCTYPE html>
<html>
<head>
    <title>Minha Aplicação</title>
</head>
<body>
    <h1>Olá, <?php echo htmlspecialchars($user['name']); ?>!</h1>
    <a href="https://auth.importemelhor.com/logout.php">Sair</a>
</body>
</html>
```

### 3. Dar permissões no banco
```sql
-- Conectar ao PostgreSQL
psql -U sso_user -d importemelhor_sso

-- Ver usuários cadastrados
SELECT id, name, email FROM users;

-- Dar acesso a uma aplicação específica
INSERT INTO user_app_access (user_id, app_id)
SELECT 1, id FROM applications WHERE app_slug = 'cca-calc';

-- Ou dar acesso a todas as aplicações
INSERT INTO user_app_access (user_id, app_id)
SELECT 1, id FROM applications WHERE is_active = TRUE;
```

## 🗄️ Estrutura do Banco de Dados

- **users** - Usuários do sistema
- **sessions** - Sessões ativas
- **applications** - Aplicações disponíveis
- **user_app_access** - Permissões de acesso
- **audit_logs** - Logs de auditoria

## 🔐 Segurança

- ✅ Cookies com `HttpOnly` e `Secure`
- ✅ CSRF protection
- ✅ Sessões com expiração automática
- ✅ Client Secret nunca exposto no frontend
- ✅ Prepared statements (prevenção SQL injection)

## 📊 Monitoramento

### Ver usuários ativos
```sql
SELECT * FROM vw_active_users;
```

### Ver logs de acesso
```sql
SELECT 
    u.name,
    al.action,
    al.created_at,
    al.ip_address
FROM audit_logs al
LEFT JOIN users u ON al.user_id = u.id
ORDER BY al.created_at DESC
LIMIT 50;
```

### Limpar sessões expiradas
```sql
SELECT sp_cleanup_expired_sessions();
```

## 🐛 Troubleshooting

### Erro: "Erro de conexão com banco"
- Verifique se o PostgreSQL está rodando
- Confirme credenciais no `.env`

### Erro: "SSO não funciona entre apps"
- Verifique se `COOKIE_DOMAIN` tem o ponto: `.importemelhor.com`
- Confirme que todas as apps estão em HTTPS

### Erro: "Acesso Negado"
- Execute o SQL para dar permissões ao usuário

## 📞 Suporte

Para problemas ou dúvidas, verifique:
- Logs do PHP no Easypanel
- Logs de auditoria no banco de dados
- Console do navegador (F12)

## 📄 Licença

Uso interno - Importe Melhor

---

**Desenvolvido com ❤️ para Importe Melhor** 🚢
