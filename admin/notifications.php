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
$isAdmin = ($auth->isAdmin($session['user_id']));
if (!$isAdmin) {
    header('Location: /dashboard.php');
    exit;
}

$firstName = explode(' ', $session['name'])[0];
$pageTitle = 'Notificações';
$applications = $auth->getUserApplications($session['user_id']);

// Handle form submissions
$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'create':
                $title = $_POST['title'];
                $notificationMessage = $_POST['message'];
                $type = $_POST['type'];
                $target_type = $_POST['target_type'];
                $target_value = null;

                if ($target_type === 'user') {
                    $target_value = $_POST['target_user'];
                } elseif ($target_type === 'department') {
                    $target_value = $_POST['target_department'];
                }

                $expires_at = !empty($_POST['expires_at']) ? $_POST['expires_at'] : null;

                $result = $auth->createNotification(
                    $title,
                    $notificationMessage,
                    $type,
                    $target_type,
                    $target_value,
                    $session['user_id'],
                    $expires_at
                );

                $message = $result ? 'Notificação criada com sucesso!' : 'Erro ao criar notificação.';
                $messageType = $result ? 'success' : 'error';
                break;

            case 'delete':
                $result = $auth->deleteNotification((int)$_POST['id']);
                $message = $result ? 'Notificação excluída com sucesso!' : 'Erro ao excluir notificação.';
                $messageType = $result ? 'success' : 'error';
                break;
        }
    }
}

// Get all notifications
$notifications = $auth->getAllNotifications();

// Get all users for dropdown
$users = $auth->getAllUsers();

// Get unique departments
$departments = array_unique(array_filter(array_column($users, 'department')));
sort($departments);
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
                        <h1 style="margin-bottom: 0.5rem;">Gerenciar Notificações</h1>
                        <p style="color: var(--text-secondary);">Envie notificações para usuários, departamentos ou todos</p>
                    </div>
                    <button onclick="openCreateModal()" class="btn btn-primary">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M12 5v14M5 12h14"/>
                        </svg>
                        Nova Notificação
                    </button>
                </div>

                <!-- Message -->
                <?php if ($message): ?>
                <div class="alert" style="margin-bottom: 2rem; padding: 1rem; border-radius: var(--radius); background: <?php echo $messageType === 'success' ? 'var(--color-success)' : 'var(--color-error)'; ?>; color: white;">
                    <?php echo htmlspecialchars($message); ?>
                </div>
                <?php endif; ?>

                <!-- Notifications Table -->
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Todas as Notificações</h3>
                    </div>
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Título</th>
                                    <th>Mensagem</th>
                                    <th>Tipo</th>
                                    <th>Destino</th>
                                    <th>Leituras</th>
                                    <th>Criado por</th>
                                    <th>Data</th>
                                    <th>Ações</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($notifications as $notification): ?>
                                <tr>
                                    <td style="font-weight: 600;"><?php echo htmlspecialchars($notification['title']); ?></td>
                                    <td style="max-width: 300px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                                        <?php echo htmlspecialchars($notification['message']); ?>
                                    </td>
                                    <td>
                                        <span class="badge badge-<?php echo $notification['type']; ?>">
                                            <?php echo ucfirst($notification['type']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php
                                        if ($notification['target_type'] === 'all') {
                                            echo '<span class="badge badge-primary">Todos</span>';
                                        } elseif ($notification['target_type'] === 'department') {
                                            echo '<span class="badge badge-secondary">Depto: ' . htmlspecialchars($notification['target_value']) . '</span>';
                                        } else {
                                            echo '<span class="badge badge-secondary">Usuário ID: ' . htmlspecialchars($notification['target_value']) . '</span>';
                                        }
                                        ?>
                                    </td>
                                    <td><?php echo $notification['read_count']; ?> leituras</td>
                                    <td><?php echo htmlspecialchars($notification['created_by_name'] ?? 'Sistema'); ?></td>
                                    <td><?php echo date('d/m/Y H:i', strtotime($notification['created_at'])); ?></td>
                                    <td>
                                        <form method="POST" style="display: inline;" onsubmit="return confirm('Tem certeza que deseja excluir esta notificação?');">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="id" value="<?php echo $notification['id']; ?>">
                                            <button type="submit" class="btn-icon" style="color: var(--color-error);">
                                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                    <path d="M3 6h18M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/>
                                                </svg>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <!-- Create Modal -->
    <div id="notificationModal" style="display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.5); z-index: 9999; align-items: center; justify-content: center;">
        <div style="background: var(--bg-primary); border-radius: var(--radius-lg); width: 90%; max-width: 600px; max-height: 90vh; overflow-y: auto;">
            <div style="padding: 1.5rem; border-bottom: 1px solid var(--border-color);">
                <h2>Nova Notificação</h2>
            </div>
            <form method="POST" style="padding: 1.5rem;">
                <input type="hidden" name="action" value="create">

                <div class="form-group">
                    <label class="form-label">Título *</label>
                    <input type="text" name="title" class="form-input" required>
                </div>

                <div class="form-group">
                    <label class="form-label">Mensagem *</label>
                    <textarea name="message" class="form-input" rows="3" required></textarea>
                </div>

                <div class="form-group">
                    <label class="form-label">Tipo *</label>
                    <select name="type" class="form-input" required>
                        <option value="info">Informação</option>
                        <option value="success">Sucesso</option>
                        <option value="warning">Aviso</option>
                        <option value="error">Erro</option>
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label">Destino *</label>
                    <select name="target_type" id="targetType" class="form-input" required onchange="updateTargetFields()">
                        <option value="all">Todos os usuários</option>
                        <option value="department">Departamento específico</option>
                        <option value="user">Usuário específico</option>
                    </select>
                </div>

                <div class="form-group" id="targetUserGroup" style="display: none;">
                    <label class="form-label">Selecione o usuário</label>
                    <select name="target_user" class="form-input">
                        <option value="">Selecione...</option>
                        <?php foreach ($users as $user): ?>
                        <option value="<?php echo $user['id']; ?>">
                            <?php echo htmlspecialchars($user['name'] . ' (' . $user['email'] . ')'); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group" id="targetDepartmentGroup" style="display: none;">
                    <label class="form-label">Selecione o departamento</label>
                    <select name="target_department" class="form-input">
                        <option value="">Selecione...</option>
                        <?php foreach ($departments as $dept): ?>
                        <option value="<?php echo htmlspecialchars($dept); ?>">
                            <?php echo htmlspecialchars($dept); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label">Data de expiração (opcional)</label>
                    <input type="datetime-local" name="expires_at" class="form-input">
                    <small style="color: var(--text-secondary); font-size: 0.875rem;">Deixe em branco para notificação permanente</small>
                </div>

                <div style="display: flex; gap: 1rem; justify-content: flex-end; margin-top: 1.5rem;">
                    <button type="button" onclick="closeModal()" class="btn btn-outline">
                        Cancelar
                    </button>
                    <button type="submit" class="btn btn-primary">
                        Enviar Notificação
                    </button>
                </div>
            </form>
        </div>
    </div>

    <style>
    .badge {
        padding: 0.25rem 0.75rem;
        border-radius: 9999px;
        font-size: 0.75rem;
        font-weight: 600;
        display: inline-block;
    }
    .badge-info { background: var(--color-info); color: white; }
    .badge-success { background: var(--color-success); color: white; }
    .badge-warning { background: var(--color-warning); color: white; }
    .badge-error { background: var(--color-error); color: white; }
    .badge-primary { background: var(--color-primary); color: white; }
    .badge-secondary { background: var(--color-gray-500); color: white; }

    .btn-icon {
        background: transparent;
        border: none;
        cursor: pointer;
        padding: 0.25rem;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        transition: var(--transition);
    }
    .btn-icon:hover {
        opacity: 0.7;
    }
    </style>

    <script>
        function openCreateModal() {
            document.getElementById('notificationModal').style.display = 'flex';
        }

        function closeModal() {
            document.getElementById('notificationModal').style.display = 'none';
        }

        function updateTargetFields() {
            const targetType = document.getElementById('targetType').value;
            const userGroup = document.getElementById('targetUserGroup');
            const deptGroup = document.getElementById('targetDepartmentGroup');

            userGroup.style.display = 'none';
            deptGroup.style.display = 'none';

            if (targetType === 'user') {
                userGroup.style.display = 'block';
                userGroup.querySelector('select').required = true;
            } else if (targetType === 'department') {
                deptGroup.style.display = 'block';
                deptGroup.querySelector('select').required = true;
            }
        }

        // Close modal when clicking outside
        document.getElementById('notificationModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeModal();
            }
        });
    </script>
    <script src="/public/js/main.js"></script>
</body>
</html>
