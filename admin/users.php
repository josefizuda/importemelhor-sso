<?php
require_once '../config.php';

$auth = new Auth();

// Check authentication
if (!isset($_COOKIE['sso_token'])) {
    header('Location: /index.php');
    exit;
}

$session = $auth->validateSession($_COOKIE['sso_token']);

if (!$session) {
    $auth->clearSessionCookie();
    header('Location: /index.php');
    exit;
}

// Check admin permission
$isAdmin = ($session['email'] === 'app@importemelhor.com.br');
if (!$isAdmin) {
    header('Location: /dashboard.php');
    exit;
}

$firstName = explode(' ', $session['name'])[0];
$pageTitle = 'Gerenciar Usuários';

// Handle form submissions
$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'toggle_user':
                $result = $auth->toggleUserStatus((int)$_POST['user_id']);
                $message = $result ? 'Status do usuário alterado com sucesso!' : 'Erro ao alterar status.';
                $messageType = $result ? 'success' : 'error';
                break;

            case 'grant_access':
                $result = $auth->grantAppAccess((int)$_POST['user_id'], (int)$_POST['app_id'], $session['user_id']);
                $message = $result ? 'Acesso concedido com sucesso!' : 'Erro ao conceder acesso.';
                $messageType = $result ? 'success' : 'error';
                break;

            case 'revoke_access':
                $result = $auth->revokeAppAccess((int)$_POST['user_id'], (int)$_POST['app_id']);
                $message = $result ? 'Acesso revogado com sucesso!' : 'Erro ao revogar acesso.';
                $messageType = $result ? 'success' : 'error';
                break;

            case 'change_role':
                $result = $auth->updateUserRole((int)$_POST['user_id'], (int)$_POST['role_id']);
                $message = $result ? 'Role do usuário atualizada com sucesso!' : 'Erro ao atualizar role.';
                $messageType = $result ? 'success' : 'error';
                break;
        }
    }
}

$users = $auth->getAllUsers();
$applications = $auth->getUserApplications($session['user_id']);
$roles = $auth->getAllRoles();

// Get all applications for permission management
$db = Database::getInstance()->getConnection();
$stmt = $db->prepare("SELECT * FROM applications WHERE is_active = TRUE ORDER BY app_name");
$stmt->execute();
$allApps = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?> - Importe Melhor SSO</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/public/css/main.css">
</head>
<body>
    <div class="app-wrapper">
        <?php include '../includes/sidebar.php'; ?>

        <div class="main-container">
            <?php include '../includes/header.php'; ?>

            <main class="main-content">
                <!-- Page Header -->
                <div class="flex items-center justify-between" style="margin-bottom: 2rem;">
                    <div>
                        <h1 style="margin-bottom: 0.5rem;">Gerenciar Usuários</h1>
                        <p style="color: var(--color-gray-500);">Gerencie usuários e suas permissões de acesso</p>
                    </div>
                </div>

                <!-- Message -->
                <?php if ($message): ?>
                <div class="alert alert-<?php echo $messageType; ?>" style="margin-bottom: 2rem; padding: 1rem; border-radius: var(--radius); background: <?php echo $messageType === 'success' ? 'var(--color-success)' : 'var(--color-error)'; ?>; color: white;">
                    <?php echo htmlspecialchars($message); ?>
                </div>
                <?php endif; ?>

                <!-- Stats Cards -->
                <div class="grid grid-cols-4" style="margin-bottom: 2rem;">
                    <div class="stats-card">
                        <div class="stats-header">
                            <div class="stats-icon primary">
                                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/>
                                    <circle cx="9" cy="7" r="4"/>
                                    <path d="M23 21v-2a4 4 0 0 0-3-3.87"/>
                                    <path d="M16 3.13a4 4 0 0 1 0 7.75"/>
                                </svg>
                            </div>
                        </div>
                        <div class="stats-value"><?php echo count($users); ?></div>
                        <div class="stats-label">Total de Usuários</div>
                    </div>

                    <div class="stats-card">
                        <div class="stats-header">
                            <div class="stats-icon success">
                                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/>
                                    <polyline points="22 4 12 14.01 9 11.01"/>
                                </svg>
                            </div>
                        </div>
                        <div class="stats-value"><?php echo count(array_filter($users, fn($u) => $u['is_active'])); ?></div>
                        <div class="stats-label">Usuários Ativos</div>
                    </div>

                    <div class="stats-card">
                        <div class="stats-header">
                            <div class="stats-icon accent">
                                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <rect x="3" y="3" width="7" height="7"/>
                                    <rect x="14" y="3" width="7" height="7"/>
                                    <rect x="14" y="14" width="7" height="7"/>
                                    <rect x="3" y="14" width="7" height="7"/>
                                </svg>
                            </div>
                        </div>
                        <div class="stats-value"><?php echo count($allApps); ?></div>
                        <div class="stats-label">Aplicações Disponíveis</div>
                    </div>

                    <div class="stats-card">
                        <div class="stats-header">
                            <div class="stats-icon warning">
                                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/>
                                </svg>
                            </div>
                        </div>
                        <div class="stats-value"><?php echo array_sum(array_column($users, 'active_sessions')); ?></div>
                        <div class="stats-label">Sessões Ativas</div>
                    </div>
                </div>

                <!-- Users Table -->
                <div class="table-container">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Usuário</th>
                                <th>Email</th>
                                <th>Departamento</th>
                                <th>Apps</th>
                                <th>Último Acesso</th>
                                <th>Status</th>
                                <th>Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($users as $user): ?>
                            <tr>
                                <td>
                                    <div style="display: flex; align-items: center; gap: 0.75rem;">
                                        <div class="user-avatar" style="width: 40px; height: 40px; font-size: 1rem;">
                                            <?php echo strtoupper(substr($user['name'], 0, 1)); ?>
                                        </div>
                                        <div>
                                            <div style="font-weight: 600;"><?php echo htmlspecialchars($user['name']); ?></div>
                                            <?php if ($user['job_title']): ?>
                                            <div style="font-size: 0.75rem; color: var(--color-gray-500);">
                                                <?php echo htmlspecialchars($user['job_title']); ?>
                                            </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </td>
                                <td><?php echo htmlspecialchars($user['email']); ?></td>
                                <td><?php echo htmlspecialchars($user['department'] ?? '-'); ?></td>
                                <td>
                                    <span style="padding: 0.25rem 0.75rem; border-radius: 9999px; font-size: 0.75rem; font-weight: 600; background: var(--color-gray-200); color: var(--color-gray-700);">
                                        <?php echo $user['apps_count']; ?> apps
                                    </span>
                                </td>
                                <td style="font-size: 0.875rem;">
                                    <?php echo $user['last_login'] ? date('d/m/Y H:i', strtotime($user['last_login'])) : 'Nunca'; ?>
                                </td>
                                <td>
                                    <span style="padding: 0.25rem 0.75rem; border-radius: 9999px; font-size: 0.75rem; font-weight: 600; background: <?php echo $user['is_active'] ? 'var(--color-success)' : 'var(--color-error)'; ?>; color: white;">
                                        <?php echo $user['is_active'] ? 'Ativo' : 'Inativo'; ?>
                                    </span>
                                </td>
                                <td>
                                    <div style="display: flex; gap: 0.5rem;">
                                        <button onclick="managePermissions(<?php echo $user['id']; ?>, '<?php echo htmlspecialchars($user['name']); ?>')" class="btn btn-outline" style="padding: 0.5rem; font-size: 0.875rem;">
                                            Permissões
                                        </button>
                                        <?php if ($user['email'] !== 'app@importemelhor.com.br'): ?>
                                        <form method="POST" style="display: inline;">
                                            <input type="hidden" name="action" value="toggle_user">
                                            <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                            <button type="submit" class="btn btn-outline" style="padding: 0.5rem; font-size: 0.875rem;">
                                                <?php echo $user['is_active'] ? 'Desativar' : 'Ativar'; ?>
                                            </button>
                                        </form>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </main>
        </div>
    </div>

    <!-- Permissions Modal -->
    <div id="permissionsModal" style="display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.5); z-index: 9999; align-items: center; justify-content: center;">
        <div style="background: white; border-radius: var(--radius-lg); width: 90%; max-width: 700px; max-height: 90vh; overflow-y: auto;">
            <div style="padding: 1.5rem; border-bottom: 1px solid var(--color-gray-200);">
                <h2 id="modalTitle">Gerenciar Permissões</h2>
                <p style="color: var(--color-gray-500); font-size: 0.875rem; margin-top: 0.5rem;" id="modalSubtitle"></p>
            </div>
            <div id="permissionsContent" style="padding: 1.5rem;">
                <!-- Content will be loaded dynamically -->
            </div>
            <div style="padding: 1.5rem; border-top: 1px solid var(--color-gray-200); display: flex; justify-content: flex-end;">
                <button onclick="closePermissionsModal()" class="btn btn-outline">Fechar</button>
            </div>
        </div>
    </div>

    <script>
        async function managePermissions(userId, userName) {
            document.getElementById('modalSubtitle').textContent = userName;
            document.getElementById('permissionsModal').style.display = 'flex';

            // Fetch user permissions
            const response = await fetch(`/admin/get_user_permissions.php?user_id=${userId}`);
            const data = await response.json();

            let html = '<div class="grid grid-cols-1" style="gap: 1rem;">';

            data.forEach(app => {
                html += `
                    <div style="padding: 1rem; border: 1px solid var(--color-gray-200); border-radius: var(--radius); display: flex; align-items: center; justify-content: space-between;">
                        <div style="display: flex; align-items: center; gap: 1rem;">
                            <div style="font-size: 2rem;">${app.icon_emoji}</div>
                            <div>
                                <div style="font-weight: 600;">${app.app_name}</div>
                                <div style="font-size: 0.875rem; color: var(--color-gray-500);">${app.app_description || ''}</div>
                                ${app.has_access && app.granted_at ? `
                                    <div style="font-size: 0.75rem; color: var(--color-gray-400); margin-top: 0.25rem;">
                                        Concedido em ${new Date(app.granted_at).toLocaleDateString('pt-BR')}
                                        ${app.granted_by_name ? ' por ' + app.granted_by_name : ''}
                                    </div>
                                ` : ''}
                            </div>
                        </div>
                        <form method="POST">
                            <input type="hidden" name="action" value="${app.has_access ? 'revoke_access' : 'grant_access'}">
                            <input type="hidden" name="user_id" value="${userId}">
                            <input type="hidden" name="app_id" value="${app.id}">
                            <button type="submit" class="btn ${app.has_access ? 'btn-outline' : 'btn-primary'}" style="padding: 0.5rem 1rem; font-size: 0.875rem;">
                                ${app.has_access ? 'Revogar' : 'Conceder'}
                            </button>
                        </form>
                    </div>
                `;
            });

            html += '</div>';
            document.getElementById('permissionsContent').innerHTML = html;
        }

        function closePermissionsModal() {
            document.getElementById('permissionsModal').style.display = 'none';
        }

        // Close modal when clicking outside
        document.getElementById('permissionsModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closePermissionsModal();
            }
        });
    </script>
    <script src="/public/js/main.js"></script>
</body>
</html>
