<?php
require_once '../config.php';

$auth = new Auth();

// Check authentication
if (!isset($_COOKIE['sso_token'])) {
    die('Por favor, faça login primeiro');
}

$session = $auth->validateSession($_COOKIE['sso_token']);
if (!$session || $session['email'] !== 'app@importemelhor.com.br') {
    die('Acesso negado - Somente o admin principal pode executar');
}

$db = Database::getInstance()->getConnection();

?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Correção Automática do Chat</title>
    <style>
        body { font-family: Arial; max-width: 900px; margin: 40px auto; padding: 20px; background: #f5f5f5; }
        .container { background: white; border-radius: 8px; padding: 30px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        h1 { color: #0423b2; }
        .ok { color: #28a745; font-weight: bold; }
        .error { color: #dc3545; font-weight: bold; }
        .warning { color: #ffc107; font-weight: bold; }
        .btn { padding: 12px 24px; background: #0423b2; color: white; border: none; border-radius: 4px; cursor: pointer; font-size: 1rem; text-decoration: none; display: inline-block; margin: 10px 5px; }
        .btn:hover { background: #0334a0; }
        .section { margin: 20px 0; padding: 15px; background: #f8f9fa; border-left: 4px solid #0423b2; }
        pre { background: #f4f4f4; padding: 10px; border-radius: 4px; overflow-x: auto; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔧 Correção Automática do Sistema de Chat</h1>

<?php
$errors = [];
$fixes_applied = [];

// Check and create chat tables
echo "<div class='section'><h2>1. Verificando Tabelas do Chat</h2>";

$tables_sql = file_get_contents(__DIR__ . '/chat_migration.sql');

try {
    $db->exec($tables_sql);
    echo "<p class='ok'>✓ Tabelas do chat criadas/verificadas com sucesso!</p>";
    $fixes_applied[] = "Tabelas do chat";
} catch (PDOException $e) {
    if (strpos($e->getMessage(), 'already exists') !== false) {
        echo "<p class='ok'>✓ Tabelas do chat já existem</p>";
    } else {
        echo "<p class='error'>✗ Erro ao criar tabelas: " . htmlspecialchars($e->getMessage()) . "</p>";
        $errors[] = "Tabelas do chat";
    }
}
echo "</div>";

// Check and add chat permission column
echo "<div class='section'><h2>2. Verificando Permissão de Chat nas Roles</h2>";

try {
    $check = $db->query("SELECT EXISTS (SELECT FROM information_schema.columns WHERE table_name = 'user_roles' AND column_name = 'can_access_chat')");
    $exists = $check->fetch()['exists'];

    if (!$exists) {
        $permission_sql = file_get_contents(__DIR__ . '/add_chat_permission.sql');
        $db->exec($permission_sql);
        echo "<p class='ok'>✓ Coluna can_access_chat adicionada à tabela user_roles!</p>";
        $fixes_applied[] = "Permissão de chat nas roles";
    } else {
        echo "<p class='ok'>✓ Coluna can_access_chat já existe</p>";
    }
} catch (PDOException $e) {
    echo "<p class='error'>✗ Erro: " . htmlspecialchars($e->getMessage()) . "</p>";
    $errors[] = "Permissão de chat nas roles";
}
echo "</div>";

// Check and add user chat permission column
echo "<div class='section'><h2>3. Verificando Permissão de Chat nos Usuários</h2>";

try {
    $check = $db->query("SELECT EXISTS (SELECT FROM information_schema.columns WHERE table_name = 'users' AND column_name = 'can_access_chat')");
    $exists = $check->fetch()['exists'];

    if (!$exists) {
        $user_permission_sql = file_get_contents(__DIR__ . '/add_user_chat_permission.sql');
        $db->exec($user_permission_sql);
        echo "<p class='ok'>✓ Coluna can_access_chat adicionada à tabela users!</p>";
        $fixes_applied[] = "Permissão de chat por usuário";
    } else {
        echo "<p class='ok'>✓ Coluna can_access_chat já existe na tabela users</p>";
    }
} catch (PDOException $e) {
    echo "<p class='error'>✗ Erro: " . htmlspecialchars($e->getMessage()) . "</p>";
    $errors[] = "Permissão de chat por usuário";
}
echo "</div>";

// Enable chat for all roles
echo "<div class='section'><h2>4. Habilitando Chat para Todas as Roles</h2>";

try {
    $stmt = $db->prepare("UPDATE user_roles SET can_access_chat = TRUE WHERE can_access_chat IS NULL OR can_access_chat = FALSE");
    $stmt->execute();
    $count = $stmt->rowCount();

    if ($count > 0) {
        echo "<p class='ok'>✓ Chat habilitado para $count role(s)!</p>";
        $fixes_applied[] = "Chat habilitado para todas as roles";
    } else {
        echo "<p class='ok'>✓ Todas as roles já têm chat habilitado</p>";
    }
} catch (PDOException $e) {
    echo "<p class='error'>✗ Erro: " . htmlspecialchars($e->getMessage()) . "</p>";
    $errors[] = "Habilitar chat para roles";
}
echo "</div>";

// Final status
echo "<div class='section'>";
if (empty($errors)) {
    echo "<h2 class='ok'>✅ Sistema de Chat Configurado com Sucesso!</h2>";
    if (!empty($fixes_applied)) {
        echo "<p><strong>Correções aplicadas:</strong></p><ul>";
        foreach ($fixes_applied as $fix) {
            echo "<li>$fix</li>";
        }
        echo "</ul>";
    }
    echo "<p>Agora todos os usuários devem conseguir ver e usar o chat!</p>";
    echo "<p><strong>Próximos passos:</strong></p>";
    echo "<ol>";
    echo "<li>Faça logout e login novamente</li>";
    echo "<li>O menu 'Chat' deve aparecer na barra lateral</li>";
    echo "<li>Clique no '+' para iniciar uma conversa</li>";
    echo "</ol>";
} else {
    echo "<h2 class='error'>⚠️ Alguns problemas foram encontrados</h2>";
    echo "<p><strong>Erros:</strong></p><ul>";
    foreach ($errors as $error) {
        echo "<li>$error</li>";
    }
    echo "</ul>";
    echo "<p>Por favor, verifique os logs de erro para mais detalhes.</p>";
}
echo "</div>";

echo "<div style='margin-top: 30px; text-align: center;'>";
echo "<a href='/check_chat_setup.php' class='btn'>Verificar Configuração do Chat</a>";
echo "<a href='/chat.php' class='btn'>Ir para o Chat</a>";
echo "<a href='/dashboard.php' class='btn'>Voltar ao Dashboard</a>";
echo "</div>";
?>

    </div>
</body>
</html>
