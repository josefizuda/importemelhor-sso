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
$pageTitle = 'Gerenciar Roles';
$applications = $auth->getUserApplications($session['user_id']);

// Handle form submissions
$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        $permissions = [
            'is_admin' => isset($_POST['is_admin']),
            'can_manage_users' => isset($_POST['can_manage_users']),
            'can_manage_banners' => isset($_POST['can_manage_banners']),
            'can_manage_apps' => isset($_POST['can_manage_apps']),
            'can_access_external_sites' => isset($_POST['can_access_external_sites'])
        ];

        switch ($_POST['action']) {
            case 'create':
                $result = $auth->createRole($_POST['name'], $_POST['slug'], $_POST['description'], $permissions);
                $message = $result ? 'Role criada com sucesso!' : 'Erro ao criar role.';
                $messageType = $result ? 'success' : 'error';
                break;

            case 'update':
                $result = $auth->updateRole((int)$_POST['id'], $_POST['name'], $_POST['description'], $permissions);
                $message = $result ? 'Role atualizada com sucesso!' : 'Erro ao atualizar role.';
                $messageType = $result ? 'success' : 'error';
                break;
        }
    }
}

$roles = $auth->getAllRoles();
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
                        <h1 style="margin-bottom: 0.5rem;">Gerenciar Roles/Tipos de Usuário</h1>
                        <p style="color: var(--text-secondary);">Gerencie os tipos de usuários e suas permissões</p>
                    </div>
                    <button onclick="openCreateModal()" class="btn btn-primary">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M12 5v14M5 12h14"/>
                        </svg>
                        Nova Role
                    </button>
                </div>

                <!-- Message -->
                <?php if ($message): ?>
                <div class="alert" style="margin-bottom: 2rem; padding: 1rem; border-radius: var(--radius); background: <?php echo $messageType === 'success' ? 'var(--color-success)' : 'var(--color-error)'; ?>; color: white;">
                    <?php echo htmlspecialchars($message); ?>
                </div>
                <?php endif; ?>

                <!-- Roles Cards -->
                <div class="grid grid-cols-3" style="margin-bottom: 2rem;">
                    <?php foreach ($roles as $role): ?>
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title"><?php echo htmlspecialchars($role['name']); ?></h3>
                            <span style="padding: 0.25rem 0.75rem; border-radius: 9999px; font-size: 0.75rem; font-weight: 600; background: var(--color-gray-200); color: var(--color-gray-700);">
                                <?php echo $role['users_count']; ?> usuários
                            </span>
                        </div>
                        <p style="color: var(--text-secondary); margin-bottom: 1rem;">
                            <?php echo htmlspecialchars($role['description'] ?? ''); ?>
                        </p>

                        <div style="margin-bottom: 1rem;">
                            <strong style="display: block; margin-bottom: 0.5rem; font-size: 0.875rem;">Permissões:</strong>
                            <div style="display: flex; flex-wrap: wrap; gap: 0.5rem;">
                                <?php if ($role['is_admin']): ?>
                                <span class="badge">Administrador</span>
                                <?php endif; ?>
                                <?php if ($role['can_manage_users']): ?>
                                <span class="badge">Gerenciar Usuários</span>
                                <?php endif; ?>
                                <?php if ($role['can_manage_banners']): ?>
                                <span class="badge">Gerenciar Banners</span>
                                <?php endif; ?>
                                <?php if ($role['can_manage_apps']): ?>
                                <span class="badge">Gerenciar Apps</span>
                                <?php endif; ?>
                                <?php if ($role['can_access_external_sites']): ?>
                                <span class="badge">Acessar Sites Externos</span>
                                <?php endif; ?>
                            </div>
                        </div>

                        <button onclick='editRole(<?php echo json_encode($role); ?>)' class="btn btn-outline" style="width: 100%;">
                            Editar
                        </button>
                    </div>
                    <?php endforeach; ?>
                </div>
            </main>
        </div>
    </div>

    <!-- Modal -->
    <div id="roleModal" style="display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.5); z-index: 9999; align-items: center; justify-content: center;">
        <div style="background: var(--bg-primary); border-radius: var(--radius-lg); width: 90%; max-width: 600px; max-height: 90vh; overflow-y: auto;">
            <div style="padding: 1.5rem; border-bottom: 1px solid var(--border-color);">
                <h2 id="modalTitle">Nova Role</h2>
            </div>
            <form method="POST" style="padding: 1.5rem;">
                <input type="hidden" name="action" id="formAction" value="create">
                <input type="hidden" name="id" id="roleId">

                <div class="form-group">
                    <label class="form-label">Nome *</label>
                    <input type="text" name="name" id="name" class="form-input" required>
                </div>

                <div class="form-group" id="slugGroup">
                    <label class="form-label">Slug * (identificador único)</label>
                    <input type="text" name="slug" id="slug" class="form-input" required pattern="[a-z0-9-]+" placeholder="ex: gerente">
                    <small style="color: var(--text-secondary); font-size: 0.875rem;">Apenas letras minúsculas, números e hífens</small>
                </div>

                <div class="form-group">
                    <label class="form-label">Descrição</label>
                    <textarea name="description" id="description" class="form-input" rows="3"></textarea>
                </div>

                <div class="form-group">
                    <label style="font-weight: 600; margin-bottom: 1rem; display: block;">Permissões</label>

                    <label style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer; margin-bottom: 0.75rem;">
                        <input type="checkbox" name="is_admin" id="is_admin">
                        <span>Administrador (acesso total)</span>
                    </label>

                    <label style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer; margin-bottom: 0.75rem;">
                        <input type="checkbox" name="can_manage_users" id="can_manage_users">
                        <span>Gerenciar Usuários</span>
                    </label>

                    <label style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer; margin-bottom: 0.75rem;">
                        <input type="checkbox" name="can_manage_banners" id="can_manage_banners">
                        <span>Gerenciar Banners</span>
                    </label>

                    <label style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer; margin-bottom: 0.75rem;">
                        <input type="checkbox" name="can_manage_apps" id="can_manage_apps">
                        <span>Gerenciar Aplicações</span>
                    </label>

                    <label style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer;">
                        <input type="checkbox" name="can_access_external_sites" id="can_access_external_sites">
                        <span>Acessar Sites Externos</span>
                    </label>
                </div>

                <div style="display: flex; gap: 1rem; justify-content: flex-end; margin-top: 1.5rem;">
                    <button type="button" onclick="closeModal()" class="btn btn-outline">
                        Cancelar
                    </button>
                    <button type="submit" class="btn btn-primary">
                        Salvar
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
        background: var(--color-primary);
        color: white;
    }
    </style>

    <script>
        function openCreateModal() {
            document.getElementById('modalTitle').textContent = 'Nova Role';
            document.getElementById('formAction').value = 'create';
            document.getElementById('roleId').value = '';
            document.getElementById('name').value = '';
            document.getElementById('slug').value = '';
            document.getElementById('description').value = '';
            document.getElementById('is_admin').checked = false;
            document.getElementById('can_manage_users').checked = false;
            document.getElementById('can_manage_banners').checked = false;
            document.getElementById('can_manage_apps').checked = false;
            document.getElementById('can_access_external_sites').checked = false;
            document.getElementById('slugGroup').style.display = 'block';
            document.getElementById('roleModal').style.display = 'flex';
        }

        function editRole(role) {
            document.getElementById('modalTitle').textContent = 'Editar Role';
            document.getElementById('formAction').value = 'update';
            document.getElementById('roleId').value = role.id;
            document.getElementById('name').value = role.name;
            document.getElementById('slug').value = role.slug;
            document.getElementById('description').value = role.description || '';
            document.getElementById('is_admin').checked = role.is_admin;
            document.getElementById('can_manage_users').checked = role.can_manage_users;
            document.getElementById('can_manage_banners').checked = role.can_manage_banners;
            document.getElementById('can_manage_apps').checked = role.can_manage_apps;
            document.getElementById('can_access_external_sites').checked = role.can_access_external_sites;
            document.getElementById('slugGroup').style.display = 'none';
            document.getElementById('roleModal').style.display = 'flex';
        }

        function closeModal() {
            document.getElementById('roleModal').style.display = 'none';
        }

        // Close modal when clicking outside
        document.getElementById('roleModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeModal();
            }
        });
    </script>
    <script src="/public/js/main.js"></script>
</body>
</html>
