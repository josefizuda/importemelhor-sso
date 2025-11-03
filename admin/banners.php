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
$pageTitle = 'Gerenciar Banners';

// Handle form submissions
$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'create':
                $result = $auth->createBanner(
                    $_POST['title'],
                    $_POST['description'],
                    $_POST['image_url'],
                    $_POST['link_url'] ?? '',
                    $_POST['link_text'] ?? '',
                    (int)$_POST['display_order'] ?? 0,
                    $session['user_id'],
                    $_POST['start_date'] ?: null,
                    $_POST['end_date'] ?: null
                );
                $message = $result ? 'Banner criado com sucesso!' : 'Erro ao criar banner.';
                $messageType = $result ? 'success' : 'error';
                break;

            case 'update':
                $result = $auth->updateBanner(
                    (int)$_POST['id'],
                    $_POST['title'],
                    $_POST['description'],
                    $_POST['image_url'],
                    $_POST['link_url'] ?? '',
                    $_POST['link_text'] ?? '',
                    (int)$_POST['display_order'] ?? 0,
                    isset($_POST['is_active']),
                    $_POST['start_date'] ?: null,
                    $_POST['end_date'] ?: null
                );
                $message = $result ? 'Banner atualizado com sucesso!' : 'Erro ao atualizar banner.';
                $messageType = $result ? 'success' : 'error';
                break;

            case 'delete':
                $result = $auth->deleteBanner((int)$_POST['id']);
                $message = $result ? 'Banner deletado com sucesso!' : 'Erro ao deletar banner.';
                $messageType = $result ? 'success' : 'error';
                break;

            case 'toggle':
                $result = $auth->toggleBannerStatus((int)$_POST['id']);
                $message = $result ? 'Status alterado com sucesso!' : 'Erro ao alterar status.';
                $messageType = $result ? 'success' : 'error';
                break;
        }
    }
}

$banners = $auth->getAllBanners();
$applications = $auth->getUserApplications($session['user_id']);
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
                        <h1 style="margin-bottom: 0.5rem;">Gerenciar Banners</h1>
                        <p style="color: var(--color-gray-500);">Gerencie os banners exibidos no carousel da dashboard</p>
                    </div>
                    <button onclick="openCreateModal()" class="btn btn-primary">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M12 5v14M5 12h14"/>
                        </svg>
                        Novo Banner
                    </button>
                </div>

                <!-- Message -->
                <?php if ($message): ?>
                <div class="alert alert-<?php echo $messageType; ?>" style="margin-bottom: 2rem; padding: 1rem; border-radius: var(--radius); background: <?php echo $messageType === 'success' ? 'var(--color-success)' : 'var(--color-error)'; ?>; color: white;">
                    <?php echo htmlspecialchars($message); ?>
                </div>
                <?php endif; ?>

                <!-- Banners Table -->
                <div class="table-container">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Preview</th>
                                <th>Título</th>
                                <th>Ordem</th>
                                <th>Status</th>
                                <th>Período</th>
                                <th>Criado por</th>
                                <th>Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($banners as $banner): ?>
                            <tr>
                                <td>
                                    <img src="<?php echo htmlspecialchars($banner['image_url']); ?>"
                                         alt="<?php echo htmlspecialchars($banner['title']); ?>"
                                         style="width: 120px; height: 60px; object-fit: cover; border-radius: var(--radius-sm);">
                                </td>
                                <td>
                                    <div style="font-weight: 600;"><?php echo htmlspecialchars($banner['title']); ?></div>
                                    <div style="font-size: 0.875rem; color: var(--color-gray-500);">
                                        <?php echo htmlspecialchars(substr($banner['description'] ?? '', 0, 50)); ?>...
                                    </div>
                                </td>
                                <td><?php echo $banner['display_order']; ?></td>
                                <td>
                                    <span style="padding: 0.25rem 0.75rem; border-radius: 9999px; font-size: 0.75rem; font-weight: 600; background: <?php echo $banner['is_active'] ? 'var(--color-success)' : 'var(--color-gray-300)'; ?>; color: <?php echo $banner['is_active'] ? 'white' : 'var(--color-gray-700)'; ?>;">
                                        <?php echo $banner['is_active'] ? 'Ativo' : 'Inativo'; ?>
                                    </span>
                                </td>
                                <td style="font-size: 0.875rem;">
                                    <?php if ($banner['start_date'] || $banner['end_date']): ?>
                                        <?php echo $banner['start_date'] ? date('d/m/Y', strtotime($banner['start_date'])) : '∞'; ?> -
                                        <?php echo $banner['end_date'] ? date('d/m/Y', strtotime($banner['end_date'])) : '∞'; ?>
                                    <?php else: ?>
                                        Sem limite
                                    <?php endif; ?>
                                </td>
                                <td><?php echo htmlspecialchars($banner['created_by_name'] ?? 'Sistema'); ?></td>
                                <td>
                                    <div style="display: flex; gap: 0.5rem;">
                                        <button onclick='editBanner(<?php echo json_encode($banner); ?>)' class="btn btn-outline" style="padding: 0.5rem; font-size: 0.875rem;">
                                            Editar
                                        </button>
                                        <form method="POST" style="display: inline;">
                                            <input type="hidden" name="action" value="toggle">
                                            <input type="hidden" name="id" value="<?php echo $banner['id']; ?>">
                                            <button type="submit" class="btn btn-outline" style="padding: 0.5rem; font-size: 0.875rem;">
                                                <?php echo $banner['is_active'] ? 'Desativar' : 'Ativar'; ?>
                                            </button>
                                        </form>
                                        <form method="POST" onsubmit="return confirm('Tem certeza que deseja deletar este banner?');" style="display: inline;">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="id" value="<?php echo $banner['id']; ?>">
                                            <button type="submit" class="btn" style="padding: 0.5rem; font-size: 0.875rem; background: var(--color-error); color: white;">
                                                Deletar
                                            </button>
                                        </form>
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

    <!-- Modal -->
    <div id="bannerModal" style="display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.5); z-index: 9999; align-items: center; justify-content: center;">
        <div style="background: white; border-radius: var(--radius-lg); width: 90%; max-width: 600px; max-height: 90vh; overflow-y: auto;">
            <div style="padding: 1.5rem; border-bottom: 1px solid var(--color-gray-200);">
                <h2 id="modalTitle">Novo Banner</h2>
            </div>
            <form method="POST" style="padding: 1.5rem;">
                <input type="hidden" name="action" id="formAction" value="create">
                <input type="hidden" name="id" id="bannerId">

                <div class="form-group">
                    <label class="form-label">Título *</label>
                    <input type="text" name="title" id="title" class="form-input" required>
                </div>

                <div class="form-group">
                    <label class="form-label">Descrição</label>
                    <textarea name="description" id="description" class="form-input" rows="3"></textarea>
                </div>

                <div class="form-group">
                    <label class="form-label">Imagem do Banner *</label>
                    <div style="display: flex; gap: 1rem; margin-bottom: 0.5rem;">
                        <button type="button" onclick="document.getElementById('imageUpload').click()" class="btn btn-outline" style="flex: 1;">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/>
                                <polyline points="17 8 12 3 7 8"/>
                                <line x1="12" y1="3" x2="12" y2="15"/>
                            </svg>
                            Upload Imagem
                        </button>
                        <input type="file" id="imageUpload" accept="image/*" style="display: none;" onchange="handleImageUpload(this)">
                    </div>
                    <input type="url" name="image_url" id="image_url" class="form-input" required placeholder="https:// ou faça upload acima">
                    <small style="color: var(--color-gray-500); font-size: 0.875rem;">Tamanho recomendado: 1200x300px. Max: 5MB</small>
                    <div id="uploadProgress" style="display: none; margin-top: 0.5rem; color: var(--color-info);">
                        Uploading...
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">URL do Link</label>
                    <input type="url" name="link_url" id="link_url" class="form-input" placeholder="https://...">
                </div>

                <div class="form-group">
                    <label class="form-label">Texto do Botão</label>
                    <input type="text" name="link_text" id="link_text" class="form-input" placeholder="Saiba Mais">
                </div>

                <div class="form-group">
                    <label class="form-label">Ordem de Exibição</label>
                    <input type="number" name="display_order" id="display_order" class="form-input" value="0" min="0">
                </div>

                <div class="grid grid-cols-2" style="gap: 1rem;">
                    <div class="form-group">
                        <label class="form-label">Data de Início</label>
                        <input type="datetime-local" name="start_date" id="start_date" class="form-input">
                    </div>

                    <div class="form-group">
                        <label class="form-label">Data de Término</label>
                        <input type="datetime-local" name="end_date" id="end_date" class="form-input">
                    </div>
                </div>

                <div class="form-group" id="activeCheckbox" style="display: none;">
                    <label style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer;">
                        <input type="checkbox" name="is_active" id="is_active">
                        <span class="form-label" style="margin: 0;">Banner Ativo</span>
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

    <script>
        function openCreateModal() {
            document.getElementById('modalTitle').textContent = 'Novo Banner';
            document.getElementById('formAction').value = 'create';
            document.getElementById('bannerId').value = '';
            document.getElementById('title').value = '';
            document.getElementById('description').value = '';
            document.getElementById('image_url').value = '';
            document.getElementById('link_url').value = '';
            document.getElementById('link_text').value = '';
            document.getElementById('display_order').value = '0';
            document.getElementById('start_date').value = '';
            document.getElementById('end_date').value = '';
            document.getElementById('activeCheckbox').style.display = 'none';
            document.getElementById('bannerModal').style.display = 'flex';
        }

        function editBanner(banner) {
            document.getElementById('modalTitle').textContent = 'Editar Banner';
            document.getElementById('formAction').value = 'update';
            document.getElementById('bannerId').value = banner.id;
            document.getElementById('title').value = banner.title;
            document.getElementById('description').value = banner.description || '';
            document.getElementById('image_url').value = banner.image_url;
            document.getElementById('link_url').value = banner.link_url || '';
            document.getElementById('link_text').value = banner.link_text || '';
            document.getElementById('display_order').value = banner.display_order;
            document.getElementById('start_date').value = banner.start_date ? banner.start_date.replace(' ', 'T').substring(0, 16) : '';
            document.getElementById('end_date').value = banner.end_date ? banner.end_date.replace(' ', 'T').substring(0, 16) : '';
            document.getElementById('is_active').checked = banner.is_active;
            document.getElementById('activeCheckbox').style.display = 'block';
            document.getElementById('bannerModal').style.display = 'flex';
        }

        function closeModal() {
            document.getElementById('bannerModal').style.display = 'none';
        }

        // Image upload handler
        async function handleImageUpload(input) {
            if (!input.files || !input.files[0]) return;

            const file = input.files[0];
            const formData = new FormData();
            formData.append('image', file);

            const progressDiv = document.getElementById('uploadProgress');
            progressDiv.style.display = 'block';
            progressDiv.textContent = 'Uploading...';

            try {
                const response = await fetch('/admin/upload_image.php', {
                    method: 'POST',
                    body: formData
                });

                const result = await response.json();

                if (response.ok && result.success) {
                    // Set the image URL in the input
                    const fullUrl = window.location.origin + result.url;
                    document.getElementById('image_url').value = fullUrl;
                    progressDiv.textContent = 'Upload concluído!';
                    progressDiv.style.color = 'var(--color-success)';

                    setTimeout(() => {
                        progressDiv.style.display = 'none';
                    }, 2000);
                } else {
                    progressDiv.textContent = 'Erro: ' + (result.error || 'Upload failed');
                    progressDiv.style.color = 'var(--color-error)';
                }
            } catch (error) {
                progressDiv.textContent = 'Erro ao fazer upload';
                progressDiv.style.color = 'var(--color-error)';
                console.error('Upload error:', error);
            }
        }

        // Close modal when clicking outside
        document.getElementById('bannerModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeModal();
            }
        });
    </script>
    <script src="/public/js/main.js"></script>
</body>
</html>
