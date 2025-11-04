<!DOCTYPE html>
<html>
<head>
    <title>Status do Sistema - Importe Melhor SSO</title>
    <style>
        body { font-family: Arial; max-width: 1000px; margin: 20px auto; padding: 20px; background: #f5f5f5; }
        .card { background: white; padding: 20px; margin: 15px 0; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        h1 { color: #0423b2; }
        h2 { color: #333; border-bottom: 2px solid #0423b2; padding-bottom: 10px; }
        .status { padding: 10px; margin: 10px 0; border-radius: 4px; }
        .ok { background: #d4edda; color: #155724; border-left: 4px solid #28a745; }
        .error { background: #f8d7da; color: #721c24; border-left: 4px solid #dc3545; }
        .warning { background: #fff3cd; color: #856404; border-left: 4px solid #ffc107; }
        .btn { display: inline-block; padding: 12px 24px; background: #0423b2; color: white; text-decoration: none; border-radius: 4px; margin: 5px; }
        .btn:hover { background: #0334a0; }
        .btn-danger { background: #dc3545; }
        .btn-danger:hover { background: #c82333; }
        code { background: #f4f4f4; padding: 2px 6px; border-radius: 3px; }
    </style>
</head>
<body>

<h1>🔍 Status do Sistema - Importe Melhor SSO</h1>

<?php
require_once 'config.php';

if (!isset($_COOKIE['sso_token'])) {
    echo "<div class='card error'>Por favor, faça login primeiro.</div>";
    echo "<a href='/index.php' class='btn'>Ir para Login</a>";
    exit;
}

$auth = new Auth();
$session = $auth->validateSession($_COOKIE['sso_token']);

if (!$session) {
    echo "<div class='card error'>Sessão inválida.</div>";
    exit;
}

$db = Database::getInstance()->getConnection();
$isAdmin = $auth->isAdmin($session['user_id']);

echo "<div class='card'>";
echo "<p><strong>Usuário:</strong> " . htmlspecialchars($session['name']) . " (" . htmlspecialchars($session['email']) . ")</p>";
echo "<p><strong>Admin:</strong> " . ($isAdmin ? 'Sim' : 'Não') . "</p>";
echo "</div>";

// Check Analytics Menu
echo "<div class='card'>";
echo "<h2>📊 Analytics</h2>";
if ($isAdmin) {
    echo "<div class='status ok'>✓ O menu Analytics ESTÁ no sidebar (apenas para admins)</div>";
    echo "<p>Procure em: <strong>Menu Lateral → Administração → Analytics</strong></p>";
    echo "<a href='/admin/analytics.php' class='btn'>Ir para Analytics</a>";
} else {
    echo "<div class='status warning'>⚠️ Você não é admin - o menu Analytics só aparece para administradores</div>";
}
echo "</div>";

// Check Chat
echo "<div class='card'>";
echo "<h2>💬 Chat</h2>";

$chat_issues = [];

// Check chat tables
$tables = ['chat_conversations', 'chat_participants', 'chat_messages', 'chat_message_reads'];
foreach ($tables as $table) {
    $stmt = $db->query("SELECT EXISTS (SELECT FROM information_schema.tables WHERE table_schema = 'public' AND table_name = '$table')");
    if (!$stmt->fetch()['exists']) {
        $chat_issues[] = "Tabela <code>$table</code> não existe";
    }
}

// Check role permission column
$stmt = $db->query("SELECT EXISTS (SELECT FROM information_schema.columns WHERE table_name = 'user_roles' AND column_name = 'can_access_chat')");
if (!$stmt->fetch()['exists']) {
    $chat_issues[] = "Coluna <code>can_access_chat</code> não existe na tabela <code>user_roles</code>";
}

// Check user permission column
$stmt = $db->query("SELECT EXISTS (SELECT FROM information_schema.columns WHERE table_name = 'users' AND column_name = 'can_access_chat')");
if (!$stmt->fetch()['exists']) {
    $chat_issues[] = "Coluna <code>can_access_chat</code> não existe na tabela <code>users</code>";
}

// Check user's chat permission
$hasChat = $auth->checkPermission($session['user_id'], 'access_chat');

if (empty($chat_issues)) {
    echo "<div class='status ok'>✓ Todas as tabelas e colunas do chat existem</div>";

    if ($hasChat) {
        echo "<div class='status ok'>✓ Você TEM permissão para acessar o chat</div>";
        echo "<p>O menu Chat deve aparecer em: <strong>Menu Lateral → Menu Principal → Chat</strong></p>";
        echo "<a href='/chat.php' class='btn'>Ir para o Chat</a>";
    } else {
        echo "<div class='status error'>✗ Você NÃO tem permissão para acessar o chat</div>";
        echo "<p><strong>Solução:</strong> Peça a um admin para:</p>";
        echo "<ol>";
        echo "<li>Ir em <strong>Usuários</strong></li>";
        echo "<li>Clicar em <strong>Permissões</strong> no seu usuário</li>";
        echo "<li>Habilitar <strong>Acesso ao Chat</strong></li>";
        echo "</ol>";
        if ($isAdmin) {
            echo "<a href='/fix_chat_permissions.php' class='btn'>Habilitar Chat para Todos</a>";
        }
    }
} else {
    echo "<div class='status error'>";
    echo "<strong>✗ Problemas encontrados:</strong><ul>";
    foreach ($chat_issues as $issue) {
        echo "<li>$issue</li>";
    }
    echo "</ul></div>";

    echo "<p><strong>💡 SOLUÇÃO FÁCIL:</strong> Clique no botão abaixo para corrigir tudo automaticamente:</p>";
    echo "<a href='/database/auto_fix_chat.php' class='btn btn-danger'>🔧 Corrigir Chat Automaticamente</a>";
}

echo "</div>";

// Check Settings/Integrations
echo "<div class='card'>";
echo "<h2>⚙️ Configurações / Integrações</h2>";

$stmt = $db->query("SELECT EXISTS (SELECT FROM information_schema.tables WHERE table_schema = 'public' AND table_name = 'system_settings')");
$settings_exists = $stmt->fetch()['exists'];

if ($settings_exists) {
    echo "<div class='status ok'>✓ Tabela de configurações existe</div>";

    $stmt = $db->query("SELECT COUNT(*) as count FROM system_settings");
    $count = $stmt->fetch()['count'];

    echo "<p>Configurações cadastradas: <strong>$count</strong></p>";

    if ($count > 0) {
        echo "<div class='status ok'>✓ Integrações estão configuradas no banco</div>";
        if ($isAdmin) {
            echo "<p>Agora só falta criar a interface para editar. Vou criar agora!</p>";
            echo "<a href='/settings.php' class='btn'>Ir para Configurações</a>";
        }
    } else {
        echo "<div class='status warning'>⚠️ Tabela existe mas está vazia</div>";
        echo "<p><strong>Solução:</strong> Execute a migração de integrações</p>";
        if ($isAdmin) {
            echo "<a href='/database/run_migration.php?type=integrations' class='btn'>Executar Migração</a>";
        }
    }
} else {
    echo "<div class='status error'>✗ Tabela de configurações não existe</div>";
    echo "<p><strong>Solução:</strong> Execute a migração de integrações</p>";
    if ($isAdmin) {
        echo "<a href='/database/run_migration.php?type=integrations' class='btn'>Criar Tabela de Configurações</a>";
    }
}

echo "</div>";

echo "<div class='card' style='text-align: center; margin-top: 30px;'>";
echo "<a href='/dashboard.php' class='btn'>Voltar ao Dashboard</a>";
if ($isAdmin) {
    echo "<a href='/database/run_migration.php' class='btn'>Ver Todas as Migrações</a>";
}
echo "</div>";
?>

</body>
</html>
