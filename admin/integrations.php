<?php
require_once '../config.php';

$auth = new Auth();

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
$pageTitle = 'Integrações';
$applications = $auth->getUserApplications($session['user_id']);

// Handle form submission
$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_settings') {
    try {
        $updates = 0;
        foreach ($_POST as $key => $value) {
            if ($key !== 'action' && strpos($key, 'setting_') === 0) {
                $setting_key = substr($key, 8); // Remove 'setting_' prefix

                // Handle checkboxes (enabled flags)
                if (strpos($setting_key, '_enabled') !== false) {
                    $value = isset($_POST[$key]) ? 'true' : 'false';
                }

                $auth->setSetting($setting_key, $value, $session['user_id']);
                $updates++;
            }
        }

        $message = "Configurações atualizadas com sucesso! ($updates itens)";
        $messageType = 'success';
    } catch (Exception $e) {
        $message = 'Erro ao atualizar configurações: ' . $e->getMessage();
        $messageType = 'error';
    }
}

// Get all settings grouped by category
$db = Database::getInstance()->getConnection();
$stmt = $db->query("SELECT * FROM system_settings ORDER BY category, setting_key");
$all_settings = $stmt->fetchAll();

$settings_by_category = [];
foreach ($all_settings as $setting) {
    $category = $setting['category'] ?? 'general';
    if (!isset($settings_by_category[$category])) {
        $settings_by_category[$category] = [];
    }
    $settings_by_category[$category][] = $setting;
}

$category_names = [
    'analytics' => ['name' => 'Google Analytics', 'icon' => '📊'],
    'advertising' => ['name' => 'Publicidade', 'icon' => '📢'],
    'security' => ['name' => 'Segurança', 'icon' => '🔒'],
    'general' => ['name' => 'Geral', 'icon' => '⚙️']
];
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
                <div class="flex items-center justify-between" style="margin-bottom: 2rem;">
                    <div>
                        <h1 style="margin-bottom: 0.5rem;">🔗 Integrações</h1>
                        <p style="color: var(--text-secondary);">Configure integrações com serviços externos</p>
                    </div>
                    <a href="/settings.php" class="btn btn-outline">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M19 12H5M12 19l-7-7 7-7"/>
                        </svg>
                        Voltar
                    </a>
                </div>

                <?php if ($message): ?>
                <div class="alert" style="margin-bottom: 2rem; padding: 1rem; border-radius: var(--radius); background: <?php echo $messageType === 'success' ? 'var(--color-success)' : 'var(--color-error)'; ?>; color: white;">
                    <?php echo htmlspecialchars($message); ?>
                </div>
                <?php endif; ?>

                <form method="POST">
                    <input type="hidden" name="action" value="update_settings">

                    <?php foreach ($settings_by_category as $category => $settings): ?>
                    <div class="card" style="margin-bottom: 2rem;">
                        <div class="card-header">
                            <h2 class="card-title">
                                <?php echo $category_names[$category]['icon'] ?? '⚙️'; ?>
                                <?php echo $category_names[$category]['name'] ?? ucfirst($category); ?>
                            </h2>
                        </div>
                        <div class="card-body">
                            <div style="display: grid; gap: 1.5rem;">
                                <?php foreach ($settings as $setting): ?>
                                    <?php if ($setting['setting_type'] === 'boolean' && strpos($setting['setting_key'], '_enabled') !== false): ?>
                                        <!-- Checkbox for enabled flags -->
                                        <div style="display: flex; align-items: center; gap: 1rem; padding: 1rem; background: var(--bg-secondary); border-radius: var(--radius);">
                                            <label style="display: flex; align-items: center; gap: 0.75rem; cursor: pointer; flex: 1;">
                                                <input type="checkbox"
                                                       name="setting_<?php echo $setting['setting_key']; ?>"
                                                       value="true"
                                                       <?php echo ($setting['setting_value'] === 'true' || $setting['setting_value'] === true) ? 'checked' : ''; ?>
                                                       style="width: 20px; height: 20px;">
                                                <div>
                                                    <div style="font-weight: 600;"><?php echo htmlspecialchars($setting['description']); ?></div>
                                                    <div style="font-size: 0.875rem; color: var(--text-secondary);">
                                                        <?php echo htmlspecialchars($setting['setting_key']); ?>
                                                    </div>
                                                </div>
                                            </label>
                                        </div>
                                    <?php else: ?>
                                        <!-- Regular input fields -->
                                        <div class="form-group">
                                            <label class="form-label">
                                                <?php echo htmlspecialchars($setting['description'] ?: $setting['setting_key']); ?>
                                                <?php if ($setting['is_sensitive']): ?>
                                                    <span style="color: var(--color-warning); font-size: 0.875rem;">(Confidencial)</span>
                                                <?php endif; ?>
                                            </label>

                                            <?php if ($setting['setting_type'] === 'json'): ?>
                                                <textarea name="setting_<?php echo $setting['setting_key']; ?>"
                                                          class="form-input"
                                                          rows="4"
                                                          placeholder='{"key": "value"}'
                                                          style="font-family: monospace;"><?php
                                                    if ($setting['is_sensitive'] && $setting['setting_value']) {
                                                        echo ''; // Don't show sensitive JSON
                                                    } else {
                                                        echo htmlspecialchars($setting['setting_value'] ? json_encode(json_decode($setting['setting_value']), JSON_PRETTY_PRINT) : '');
                                                    }
                                                ?></textarea>
                                            <?php elseif ($setting['is_sensitive']): ?>
                                                <input type="password"
                                                       name="setting_<?php echo $setting['setting_key']; ?>"
                                                       class="form-input"
                                                       placeholder="<?php echo $setting['setting_value'] ? '••••••••' : 'Não configurado'; ?>"
                                                       autocomplete="off">
                                            <?php else: ?>
                                                <input type="<?php echo $setting['setting_type'] === 'number' ? 'number' : 'text'; ?>"
                                                       name="setting_<?php echo $setting['setting_key']; ?>"
                                                       class="form-input"
                                                       value="<?php echo htmlspecialchars($setting['setting_value'] ?? ''); ?>"
                                                       <?php if ($setting['setting_type'] === 'number'): ?>
                                                       step="0.01"
                                                       min="0"
                                                       max="1"
                                                       <?php endif; ?>>
                                            <?php endif; ?>

                                            <small style="color: var(--text-secondary); font-size: 0.875rem; display: block; margin-top: 0.5rem;">
                                                Key: <code><?php echo htmlspecialchars($setting['setting_key']); ?></code>
                                            </small>
                                        </div>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>

                    <div style="display: flex; gap: 1rem; justify-content: flex-end;">
                        <a href="/settings.php" class="btn btn-outline">Cancelar</a>
                        <button type="submit" class="btn btn-primary">Salvar Configurações</button>
                    </div>
                </form>
            </main>
        </div>
    </div>

    <script src="/public/js/main.js"></script>
</body>
</html>
