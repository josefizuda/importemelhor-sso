<?php
require_once 'config.php';

$auth = new Auth();

// Check if user is logged in
if (!isset($_COOKIE['sso_token'])) {
    die('Por favor, faça login primeiro');
}

$session = $auth->validateSession($_COOKIE['sso_token']);
if (!$session) {
    die('Sessão inválida');
}

// Get database connection
$db = Database::getInstance()->getConnection();

echo "<h1>Diagnóstico do Sistema de Chat</h1>";
echo "<style>body { font-family: Arial; padding: 20px; } .ok { color: green; } .error { color: red; } .warning { color: orange; }</style>";

// Check if chat tables exist
$tables_to_check = ['chat_conversations', 'chat_participants', 'chat_messages', 'chat_message_reads'];
$all_tables_exist = true;

echo "<h2>1. Verificando Tabelas do Banco de Dados</h2>";
foreach ($tables_to_check as $table) {
    try {
        $stmt = $db->query("SELECT EXISTS (SELECT FROM information_schema.tables WHERE table_schema = 'public' AND table_name = '$table')");
        $exists = $stmt->fetch()['exists'];

        if ($exists) {
            echo "<p class='ok'>✓ Tabela '$table' existe</p>";
        } else {
            echo "<p class='error'>✗ Tabela '$table' NÃO existe</p>";
            $all_tables_exist = false;
        }
    } catch (Exception $e) {
        echo "<p class='error'>✗ Erro ao verificar tabela '$table': " . $e->getMessage() . "</p>";
        $all_tables_exist = false;
    }
}

// Check if can_access_chat column exists
echo "<h2>2. Verificando Coluna de Permissão de Chat</h2>";
try {
    $stmt = $db->query("SELECT EXISTS (SELECT FROM information_schema.columns WHERE table_name = 'user_roles' AND column_name = 'can_access_chat')");
    $exists = $stmt->fetch()['exists'];

    if ($exists) {
        echo "<p class='ok'>✓ Coluna 'can_access_chat' existe na tabela user_roles</p>";
    } else {
        echo "<p class='error'>✗ Coluna 'can_access_chat' NÃO existe na tabela user_roles</p>";
    }
} catch (Exception $e) {
    echo "<p class='error'>✗ Erro ao verificar coluna: " . $e->getMessage() . "</p>";
}

// Check user's chat permission
echo "<h2>3. Verificando Permissão do Usuário</h2>";
try {
    $hasPermission = $auth->checkPermission($session['user_id'], 'access_chat');
    if ($hasPermission) {
        echo "<p class='ok'>✓ Você tem permissão para acessar o chat</p>";
    } else {
        echo "<p class='error'>✗ Você NÃO tem permissão para acessar o chat</p>";
    }
} catch (Exception $e) {
    echo "<p class='error'>✗ Erro ao verificar permissão: " . $e->getMessage() . "</p>";
}

// Check stored procedures
echo "<h2>4. Verificando Stored Procedures</h2>";
$procedures = ['sp_get_user_conversations', 'sp_get_conversation_messages', 'sp_count_total_unread_messages'];
foreach ($procedures as $proc) {
    try {
        $stmt = $db->query("SELECT EXISTS (SELECT FROM pg_proc WHERE proname = '$proc')");
        $exists = $stmt->fetch()['exists'];

        if ($exists) {
            echo "<p class='ok'>✓ Procedure '$proc' existe</p>";
        } else {
            echo "<p class='error'>✗ Procedure '$proc' NÃO existe</p>";
        }
    } catch (Exception $e) {
        echo "<p class='error'>✗ Erro ao verificar procedure: " . $e->getMessage() . "</p>";
    }
}

// Summary
echo "<h2>Resumo</h2>";
if ($all_tables_exist) {
    echo "<p class='ok'><strong>✓ Sistema de chat configurado corretamente!</strong></p>";
    echo "<p><a href='/chat.php' style='padding: 10px 20px; background: #0066cc; color: white; text-decoration: none; border-radius: 5px;'>Ir para o Chat</a></p>";
} else {
    echo "<p class='error'><strong>✗ Sistema de chat NÃO está configurado!</strong></p>";
    echo "<p class='warning'>Por favor, execute as migrações do chat:</p>";
    echo "<ol>";
    echo "<li>Acesse: <a href='/database/run_migration.php?type=chat' target='_blank'>/database/run_migration.php?type=chat</a></li>";
    echo "<li>Clique em 'Executar Migrações do Chat'</li>";
    echo "<li>Depois, acesse: <a href='/database/run_migration.php' target='_blank'>/database/run_migration.php</a> e execute 'Adicionar Permissão de Chat'</li>";
    echo "<li>Volte aqui para verificar novamente</li>";
    echo "</ol>";
}
?>
