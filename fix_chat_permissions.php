<?php
require_once 'config.php';

$auth = new Auth();

// Check if user is logged in and is admin
if (!isset($_COOKIE['sso_token'])) {
    die('Por favor, faça login primeiro');
}

$session = $auth->validateSession($_COOKIE['sso_token']);
if (!$session || $session['email'] !== 'app@importemelhor.com.br') {
    die('Acesso negado - Somente o admin principal pode executar esta ação');
}

// Get database connection
$db = Database::getInstance()->getConnection();

echo "<!DOCTYPE html>
<html lang='pt-BR'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>Corrigir Permissões de Chat</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            max-width: 900px;
            margin: 40px auto;
            padding: 20px;
            background: #f5f5f5;
        }
        .container {
            background: white;
            border-radius: 8px;
            padding: 30px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        h1 { color: #0423b2; margin-bottom: 20px; }
        h2 { color: #333; margin-top: 30px; border-bottom: 2px solid #0423b2; padding-bottom: 10px; }
        .ok { color: #28a745; font-weight: bold; }
        .error { color: #dc3545; font-weight: bold; }
        .info { background: #d1ecf1; border-left: 4px solid #0c5460; padding: 15px; margin: 15px 0; }
        .success { background: #d4edda; border-left: 4px solid #28a745; padding: 15px; margin: 15px 0; }
        table { width: 100%; border-collapse: collapse; margin: 20px 0; }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid #ddd; }
        th { background: #f8f9fa; font-weight: 600; }
        tr:hover { background: #f8f9fa; }
        .badge {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 0.875rem;
            font-weight: 600;
        }
        .badge-success { background: #28a745; color: white; }
        .badge-danger { background: #dc3545; color: white; }
        .btn {
            display: inline-block;
            padding: 12px 24px;
            background: #0423b2;
            color: white;
            text-decoration: none;
            border-radius: 4px;
            margin: 10px 5px;
            border: none;
            cursor: pointer;
            font-size: 1rem;
        }
        .btn:hover { background: #0334a0; }
    </style>
</head>
<body>
    <div class='container'>";

echo "<h1>🔧 Corrigir Permissões de Chat</h1>";

// Check if can_access_chat column exists
try {
    $stmt = $db->query("SELECT EXISTS (SELECT FROM information_schema.columns WHERE table_name = 'user_roles' AND column_name = 'can_access_chat')");
    $column_exists = $stmt->fetch()['exists'];

    if (!$column_exists) {
        echo "<div class='error'>❌ A coluna 'can_access_chat' não existe na tabela user_roles!</div>";
        echo "<div class='info'><strong>Solução:</strong> Execute primeiro a migração de permissão de chat:
              <br><a href='/database/run_migration.php?type=chat_permission' class='btn'>Adicionar Permissão de Chat</a></div>";
        echo "</div></body></html>";
        exit;
    }
} catch (Exception $e) {
    echo "<div class='error'>❌ Erro ao verificar coluna: " . htmlspecialchars($e->getMessage()) . "</div>";
    echo "</div></body></html>";
    exit;
}

echo "<h2>Status Atual das Roles</h2>";

// Show current state
try {
    $stmt = $db->query("SELECT id, name, slug, can_access_chat FROM user_roles ORDER BY id");
    $roles = $stmt->fetchAll();

    echo "<table>";
    echo "<tr><th>ID</th><th>Nome</th><th>Slug</th><th>Acesso ao Chat</th></tr>";

    $needs_update = false;
    foreach ($roles as $role) {
        $has_access = $role['can_access_chat'] ?? false;
        if (!$has_access) {
            $needs_update = true;
        }

        echo "<tr>";
        echo "<td>" . htmlspecialchars($role['id']) . "</td>";
        echo "<td>" . htmlspecialchars($role['name']) . "</td>";
        echo "<td>" . htmlspecialchars($role['slug']) . "</td>";
        echo "<td>";
        if ($has_access) {
            echo "<span class='badge badge-success'>✓ Habilitado</span>";
        } else {
            echo "<span class='badge badge-danger'>✗ Desabilitado</span>";
        }
        echo "</td>";
        echo "</tr>";
    }
    echo "</table>";

    if ($needs_update && !isset($_POST['execute'])) {
        echo "<div class='info'>";
        echo "<strong>⚠️ Algumas roles não têm acesso ao chat habilitado!</strong><br>";
        echo "Clique no botão abaixo para habilitar o acesso ao chat para todas as roles:";
        echo "<form method='POST' style='margin-top: 15px;'>";
        echo "<input type='hidden' name='execute' value='1'>";
        echo "<button type='submit' class='btn'>Habilitar Chat para Todas as Roles</button>";
        echo "</form>";
        echo "</div>";
    } elseif (isset($_POST['execute'])) {
        // Execute the update
        try {
            $stmt = $db->prepare("UPDATE user_roles SET can_access_chat = TRUE WHERE can_access_chat IS NULL OR can_access_chat = FALSE");
            $stmt->execute();
            $updated = $stmt->rowCount();

            echo "<div class='success'>";
            echo "<strong>✅ Sucesso!</strong><br>";
            echo "Foram atualizadas <strong>{$updated}</strong> role(s).<br>";
            echo "Agora todos os usuários devem conseguir ver o chat!";
            echo "</div>";

            echo "<h2>Status Após Atualização</h2>";

            // Show updated state
            $stmt = $db->query("SELECT id, name, slug, can_access_chat FROM user_roles ORDER BY id");
            $roles = $stmt->fetchAll();

            echo "<table>";
            echo "<tr><th>ID</th><th>Nome</th><th>Slug</th><th>Acesso ao Chat</th></tr>";

            foreach ($roles as $role) {
                $has_access = $role['can_access_chat'] ?? false;

                echo "<tr>";
                echo "<td>" . htmlspecialchars($role['id']) . "</td>";
                echo "<td>" . htmlspecialchars($role['name']) . "</td>";
                echo "<td>" . htmlspecialchars($role['slug']) . "</td>";
                echo "<td>";
                if ($has_access) {
                    echo "<span class='badge badge-success'>✓ Habilitado</span>";
                } else {
                    echo "<span class='badge badge-danger'>✗ Desabilitado</span>";
                }
                echo "</td>";
                echo "</tr>";
            }
            echo "</table>";

            echo "<div class='info'>";
            echo "<strong>📝 Próximos Passos:</strong><br>";
            echo "1. Faça logout e login novamente com outros usuários<br>";
            echo "2. O menu 'Chat' deve aparecer na barra lateral<br>";
            echo "3. Se não aparecer, verifique a role do usuário em <a href='/admin/users.php'>Usuários</a>";
            echo "</div>";

        } catch (Exception $e) {
            echo "<div class='error'>❌ Erro ao atualizar: " . htmlspecialchars($e->getMessage()) . "</div>";
        }
    } else {
        echo "<div class='success'>";
        echo "<strong>✅ Todas as roles já têm acesso ao chat habilitado!</strong><br>";
        echo "Se algum usuário não está vendo o chat, verifique:";
        echo "<ul>";
        echo "<li>Se o usuário fez logout e login novamente</li>";
        echo "<li>Se a role do usuário está corretamente atribuída</li>";
        echo "<li>Se as tabelas do chat foram criadas</li>";
        echo "</ul>";
        echo "</div>";
    }

} catch (Exception $e) {
    echo "<div class='error'>❌ Erro: " . htmlspecialchars($e->getMessage()) . "</div>";
}

echo "<h2>Links Úteis</h2>";
echo "<a href='/check_chat_setup.php' class='btn'>Verificar Configuração do Chat</a>";
echo "<a href='/admin/roles.php' class='btn'>Gerenciar Roles</a>";
echo "<a href='/admin/users.php' class='btn'>Gerenciar Usuários</a>";
echo "<a href='/chat.php' class='btn'>Ir para o Chat</a>";

echo "</div></body></html>";
?>
