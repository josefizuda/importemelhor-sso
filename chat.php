<?php
require_once 'config.php';

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

$firstName = explode(' ', $session['name'])[0];
$pageTitle = 'Chat';
$applications = $auth->getUserApplications($session['user_id']);

// Check chat permission
$hasPermission = $auth->checkPermission($session['user_id'], 'access_chat');
if (!$hasPermission) {
    header('Location: /dashboard.php');
    exit;
}

// Get conversation ID if specified
$conversation_id = isset($_GET['c']) ? (int)$_GET['c'] : null;

// Handle new conversation
if (isset($_GET['user']) && !$conversation_id) {
    $other_user_id = (int)$_GET['user'];
    $conversation_id = $auth->getOrCreateConversation($session['user_id'], $other_user_id);
    header('Location: /chat.php?c=' . $conversation_id);
    exit;
}

// Get all conversations
$conversations = $auth->getUserConversations($session['user_id']);

// Get messages for selected conversation
$messages = [];
$other_user = null;
if ($conversation_id) {
    // Check if user is participant
    if (!$auth->isConversationParticipant($conversation_id, $session['user_id'])) {
        header('Location: /chat.php');
        exit;
    }

    $messages = $auth->getConversationMessages($conversation_id, $session['user_id']);

    // Get other user info for 1-on-1 chats
    foreach ($conversations as $conv) {
        if ($conv['conversation_id'] == $conversation_id && !$conv['is_group']) {
            $other_user = [
                'id' => $conv['other_user_id'],
                'name' => $conv['other_user_name'],
                'photo' => $conv['other_user_photo']
            ];
            break;
        }
    }

    // Mark messages as read
    foreach ($messages as $msg) {
        if ($msg['sender_id'] != $session['user_id'] && !$msg['is_read']) {
            $auth->markMessageAsRead($msg['message_id'], $session['user_id']);
        }
    }
}

// Get all users for new conversation
$all_users = $auth->getAllUsers();
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
    <style>
        .chat-container {
            display: flex;
            height: calc(100vh - 140px);
            background: var(--bg-primary);
            border-radius: var(--radius-lg);
            overflow: hidden;
            box-shadow: var(--shadow-lg);
        }

        /* Conversations List */
        .conversations-sidebar {
            width: 320px;
            border-right: 1px solid var(--border-color);
            display: flex;
            flex-direction: column;
            background: var(--bg-primary);
        }

        .conversations-header {
            padding: 1.5rem;
            border-bottom: 1px solid var(--border-color);
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .conversations-header h2 {
            margin: 0;
            font-size: 1.25rem;
        }

        .btn-new-chat {
            padding: 0.5rem;
            background: var(--color-primary);
            color: white;
            border: none;
            border-radius: var(--radius);
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .conversations-search {
            padding: 1rem;
            border-bottom: 1px solid var(--border-color);
        }

        .conversations-search input {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid var(--border-color);
            border-radius: var(--radius);
            font-size: 0.875rem;
        }

        .conversations-list {
            flex: 1;
            overflow-y: auto;
        }

        .conversation-item {
            padding: 1rem;
            border-bottom: 1px solid var(--border-color);
            cursor: pointer;
            transition: var(--transition);
            display: flex;
            align-items: center;
            gap: 1rem;
            text-decoration: none;
            color: inherit;
        }

        .conversation-item:hover {
            background: var(--color-gray-50);
        }

        .conversation-item.active {
            background: var(--color-primary);
            color: white;
        }

        .conversation-avatar {
            width: 48px;
            height: 48px;
            border-radius: 50%;
            background: var(--color-gray-300);
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            font-size: 1.125rem;
            flex-shrink: 0;
        }

        .conversation-item.active .conversation-avatar {
            background: rgba(255,255,255,0.2);
        }

        .conversation-info {
            flex: 1;
            min-width: 0;
        }

        .conversation-name {
            font-weight: 600;
            margin-bottom: 0.25rem;
        }

        .conversation-last-message {
            font-size: 0.875rem;
            opacity: 0.7;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .conversation-meta {
            display: flex;
            flex-direction: column;
            align-items: flex-end;
            gap: 0.25rem;
        }

        .conversation-time {
            font-size: 0.75rem;
            opacity: 0.6;
        }

        .conversation-unread {
            background: var(--color-error);
            color: white;
            border-radius: 9999px;
            padding: 0.125rem 0.5rem;
            font-size: 0.75rem;
            font-weight: 600;
            min-width: 20px;
            text-align: center;
        }

        /* Chat Area */
        .chat-area {
            flex: 1;
            display: flex;
            flex-direction: column;
            background: var(--bg-primary);
        }

        .chat-header {
            padding: 1.5rem;
            border-bottom: 1px solid var(--border-color);
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .chat-header-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: var(--color-gray-300);
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
        }

        .chat-header-info h3 {
            margin: 0 0 0.25rem 0;
            font-size: 1.125rem;
        }

        .chat-header-status {
            font-size: 0.875rem;
            color: var(--text-secondary);
        }

        .chat-messages {
            flex: 1;
            overflow-y: auto;
            padding: 1.5rem;
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }

        .message {
            display: flex;
            gap: 0.75rem;
            max-width: 70%;
        }

        .message.own {
            align-self: flex-end;
            flex-direction: row-reverse;
        }

        .message-avatar {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            background: var(--color-gray-300);
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            font-size: 0.75rem;
            flex-shrink: 0;
        }

        .message-content {
            flex: 1;
        }

        .message-bubble {
            padding: 0.75rem 1rem;
            border-radius: 1rem;
            background: var(--color-gray-100);
            word-wrap: break-word;
        }

        .message.own .message-bubble {
            background: var(--color-primary);
            color: white;
        }

        .message-time {
            font-size: 0.75rem;
            color: var(--text-tertiary);
            margin-top: 0.25rem;
        }

        .message-image img {
            max-width: 300px;
            border-radius: var(--radius);
            cursor: pointer;
        }

        .message-audio {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .message-file {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 0.75rem;
            background: var(--color-gray-100);
            border-radius: var(--radius);
        }

        /* Chat Input */
        .chat-input-area {
            padding: 1.5rem;
            border-top: 1px solid var(--border-color);
            background: var(--bg-primary);
        }

        .chat-input-wrapper {
            display: flex;
            gap: 0.75rem;
            align-items: flex-end;
        }

        .chat-input-actions {
            display: flex;
            gap: 0.5rem;
        }

        .chat-input-btn {
            width: 40px;
            height: 40px;
            border-radius: var(--radius);
            border: 1px solid var(--border-color);
            background: var(--bg-primary);
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: var(--transition);
        }

        .chat-input-btn:hover {
            background: var(--color-gray-50);
            border-color: var(--color-primary);
        }

        .chat-input {
            flex: 1;
            padding: 0.75rem 1rem;
            border: 1px solid var(--border-color);
            border-radius: var(--radius-lg);
            resize: none;
            min-height: 40px;
            max-height: 120px;
            font-family: inherit;
        }

        .chat-send-btn {
            width: 40px;
            height: 40px;
            border-radius: var(--radius);
            border: none;
            background: var(--color-primary);
            color: white;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: var(--transition);
        }

        .chat-send-btn:hover {
            background: var(--color-primary-dark);
        }

        .chat-empty {
            flex: 1;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            color: var(--text-secondary);
        }

        .chat-empty svg {
            margin-bottom: 1rem;
            opacity: 0.3;
        }

        @media (max-width: 768px) {
            .conversations-sidebar {
                width: 100%;
                position: absolute;
                z-index: 10;
                display: none;
            }

            .conversations-sidebar.active {
                display: flex;
            }

            .chat-container {
                height: calc(100vh - 80px);
            }
        }
    </style>
</head>
<body>
    <div class="app-wrapper">
        <?php include 'includes/sidebar.php'; ?>

        <div class="main-container">
            <?php include 'includes/header.php'; ?>

            <main class="main-content" style="padding: 1rem;">
                <div class="chat-container">
                    <!-- Conversations Sidebar -->
                    <div class="conversations-sidebar">
                        <div class="conversations-header">
                            <h2>Mensagens</h2>
                            <button class="btn-new-chat" onclick="showNewChatModal()" title="Nova conversa">
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M12 5v14M5 12h14"/>
                                </svg>
                            </button>
                        </div>

                        <div class="conversations-search">
                            <input type="text" placeholder="Buscar conversas..." id="searchInput" onkeyup="filterConversations()">
                        </div>

                        <div class="conversations-list" id="conversationsList">
                            <?php if (empty($conversations)): ?>
                            <div style="padding: 2rem; text-align: center; color: var(--text-secondary);">
                                <p>Nenhuma conversa ainda</p>
                                <p style="font-size: 0.875rem; margin-top: 0.5rem;">Clique no + para iniciar uma nova conversa</p>
                            </div>
                            <?php else: ?>
                                <?php foreach ($conversations as $conv): ?>
                                <a href="/chat.php?c=<?php echo $conv['conversation_id']; ?>"
                                   class="conversation-item <?php echo ($conversation_id == $conv['conversation_id']) ? 'active' : ''; ?>">
                                    <div class="conversation-avatar">
                                        <?php
                                        $displayName = $conv['is_group'] ? $conv['conversation_name'] : $conv['other_user_name'];
                                        echo strtoupper(substr($displayName, 0, 1));
                                        ?>
                                    </div>
                                    <div class="conversation-info">
                                        <div class="conversation-name"><?php echo htmlspecialchars($displayName); ?></div>
                                        <div class="conversation-last-message">
                                            <?php if ($conv['last_message']): ?>
                                                <?php if ($conv['last_message_sender']): ?>
                                                    <?php echo htmlspecialchars($conv['last_message_sender']); ?>:
                                                <?php endif; ?>
                                                <?php echo htmlspecialchars(mb_substr($conv['last_message'], 0, 30)); ?>...
                                            <?php else: ?>
                                                Nenhuma mensagem ainda
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <div class="conversation-meta">
                                        <?php if ($conv['last_message_at']): ?>
                                        <span class="conversation-time">
                                            <?php
                                            $time = strtotime($conv['last_message_at']);
                                            $today = strtotime('today');
                                            if ($time >= $today) {
                                                echo date('H:i', $time);
                                            } else {
                                                echo date('d/m', $time);
                                            }
                                            ?>
                                        </span>
                                        <?php endif; ?>
                                        <?php if ($conv['unread_count'] > 0): ?>
                                        <span class="conversation-unread"><?php echo $conv['unread_count']; ?></span>
                                        <?php endif; ?>
                                    </div>
                                </a>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Chat Area -->
                    <div class="chat-area">
                        <?php if ($conversation_id && $other_user): ?>
                            <!-- Chat Header -->
                            <div class="chat-header">
                                <div class="chat-header-avatar">
                                    <?php echo strtoupper(substr($other_user['name'], 0, 1)); ?>
                                </div>
                                <div class="chat-header-info">
                                    <h3><?php echo htmlspecialchars($other_user['name']); ?></h3>
                                    <div class="chat-header-status">Online</div>
                                </div>
                            </div>

                            <!-- Messages -->
                            <div class="chat-messages" id="chatMessages">
                                <?php foreach ($messages as $msg): ?>
                                <div class="message <?php echo ($msg['sender_id'] == $session['user_id']) ? 'own' : ''; ?>">
                                    <div class="message-avatar">
                                        <?php echo strtoupper(substr($msg['sender_name'], 0, 1)); ?>
                                    </div>
                                    <div class="message-content">
                                        <?php if ($msg['message_type'] === 'text'): ?>
                                            <div class="message-bubble">
                                                <?php echo nl2br(htmlspecialchars($msg['message'])); ?>
                                            </div>
                                        <?php elseif ($msg['message_type'] === 'image'): ?>
                                            <div class="message-image">
                                                <img src="<?php echo htmlspecialchars($msg['file_url']); ?>" alt="Image">
                                                <?php if ($msg['message']): ?>
                                                <div class="message-bubble" style="margin-top: 0.5rem;">
                                                    <?php echo nl2br(htmlspecialchars($msg['message'])); ?>
                                                </div>
                                                <?php endif; ?>
                                            </div>
                                        <?php elseif ($msg['message_type'] === 'audio'): ?>
                                            <div class="message-audio">
                                                <audio controls style="max-width: 300px;">
                                                    <source src="<?php echo htmlspecialchars($msg['file_url']); ?>" type="<?php echo htmlspecialchars($msg['file_mime_type']); ?>">
                                                </audio>
                                            </div>
                                        <?php elseif ($msg['message_type'] === 'file'): ?>
                                            <div class="message-file">
                                                <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                    <path d="M13 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V9z"/>
                                                    <polyline points="13 2 13 9 20 9"/>
                                                </svg>
                                                <div>
                                                    <div style="font-weight: 600;"><?php echo htmlspecialchars($msg['file_name']); ?></div>
                                                    <div style="font-size: 0.75rem; color: var(--text-secondary);">
                                                        <?php echo number_format($msg['file_size'] / 1024, 2); ?> KB
                                                    </div>
                                                </div>
                                                <a href="<?php echo htmlspecialchars($msg['file_url']); ?>" download class="btn btn-sm btn-primary">
                                                    Download
                                                </a>
                                            </div>
                                        <?php endif; ?>
                                        <div class="message-time">
                                            <?php echo date('H:i', strtotime($msg['created_at'])); ?>
                                        </div>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>

                            <!-- Input Area -->
                            <div class="chat-input-area">
                                <form id="chatForm" onsubmit="sendMessage(event)">
                                    <div class="chat-input-wrapper">
                                        <div class="chat-input-actions">
                                            <button type="button" class="chat-input-btn" onclick="document.getElementById('imageInput').click()" title="Enviar imagem">
                                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                    <rect x="3" y="3" width="18" height="18" rx="2" ry="2"/>
                                                    <circle cx="8.5" cy="8.5" r="1.5"/>
                                                    <polyline points="21 15 16 10 5 21"/>
                                                </svg>
                                            </button>
                                            <input type="file" id="imageInput" accept="image/*" style="display: none;" onchange="handleFileSelect(this, 'image')">

                                            <button type="button" class="chat-input-btn" onclick="document.getElementById('audioInput').click()" title="Enviar áudio">
                                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                    <path d="M12 1a3 3 0 0 0-3 3v8a3 3 0 0 0 6 0V4a3 3 0 0 0-3-3z"/>
                                                    <path d="M19 10v2a7 7 0 0 1-14 0v-2"/>
                                                    <line x1="12" y1="19" x2="12" y2="23"/>
                                                    <line x1="8" y1="23" x2="16" y2="23"/>
                                                </svg>
                                            </button>
                                            <input type="file" id="audioInput" accept="audio/*" style="display: none;" onchange="handleFileSelect(this, 'audio')">

                                            <button type="button" class="chat-input-btn" onclick="document.getElementById('fileInput').click()" title="Enviar arquivo">
                                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                    <path d="M21.44 11.05l-9.19 9.19a6 6 0 0 1-8.49-8.49l9.19-9.19a4 4 0 0 1 5.66 5.66l-9.2 9.19a2 2 0 0 1-2.83-2.83l8.49-8.48"/>
                                                </svg>
                                            </button>
                                            <input type="file" id="fileInput" style="display: none;" onchange="handleFileSelect(this, 'file')">
                                        </div>

                                        <textarea id="messageInput" class="chat-input" placeholder="Digite sua mensagem..." rows="1"></textarea>

                                        <button type="submit" class="chat-send-btn">
                                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                <line x1="22" y1="2" x2="11" y2="13"/>
                                                <polygon points="22 2 15 22 11 13 2 9 22 2"/>
                                            </svg>
                                        </button>
                                    </div>
                                </form>
                            </div>
                        <?php else: ?>
                            <!-- Empty State -->
                            <div class="chat-empty">
                                <svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/>
                                </svg>
                                <h3>Selecione uma conversa</h3>
                                <p>Escolha uma conversa da lista ou inicie uma nova</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <!-- New Chat Modal -->
    <div id="newChatModal" style="display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.5); z-index: 9999; align-items: center; justify-content: center;">
        <div style="background: var(--bg-primary); border-radius: var(--radius-lg); width: 90%; max-width: 500px; max-height: 80vh; overflow-y: auto;">
            <div style="padding: 1.5rem; border-bottom: 1px solid var(--border-color);">
                <h2>Nova Conversa</h2>
            </div>
            <div style="padding: 1rem;">
                <input type="text" id="userSearch" placeholder="Buscar usuário..." style="width: 100%; padding: 0.75rem; border: 1px solid var(--border-color); border-radius: var(--radius); margin-bottom: 1rem;">
                <div id="usersList">
                    <?php foreach ($all_users as $user): ?>
                        <?php if ($user['id'] != $session['user_id']): ?>
                        <a href="/chat.php?user=<?php echo $user['id']; ?>" style="display: flex; align-items: center; gap: 1rem; padding: 0.75rem; border-radius: var(--radius); text-decoration: none; color: inherit; transition: var(--transition);" onmouseover="this.style.background='var(--color-gray-50)'" onmouseout="this.style.background='transparent'">
                            <div style="width: 40px; height: 40px; border-radius: 50%; background: var(--color-gray-300); display: flex; align-items: center; justify-content: center; font-weight: 600;">
                                <?php echo strtoupper(substr($user['name'], 0, 1)); ?>
                            </div>
                            <div>
                                <div style="font-weight: 600;"><?php echo htmlspecialchars($user['name']); ?></div>
                                <div style="font-size: 0.875rem; color: var(--text-secondary);"><?php echo htmlspecialchars($user['email']); ?></div>
                            </div>
                        </a>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </div>
            </div>
            <div style="padding: 1.5rem; border-top: 1px solid var(--border-color); display: flex; justify-content: flex-end;">
                <button onclick="closeNewChatModal()" class="btn btn-outline">Cancelar</button>
            </div>
        </div>
    </div>

    <script>
        function showNewChatModal() {
            document.getElementById('newChatModal').style.display = 'flex';
        }

        function closeNewChatModal() {
            document.getElementById('newChatModal').style.display = 'none';
        }

        function filterConversations() {
            const search = document.getElementById('searchInput').value.toLowerCase();
            const items = document.querySelectorAll('.conversation-item');
            items.forEach(item => {
                const name = item.querySelector('.conversation-name').textContent.toLowerCase();
                item.style.display = name.includes(search) ? 'flex' : 'none';
            });
        }

        function sendMessage(event) {
            event.preventDefault();
            const message = document.getElementById('messageInput').value.trim();
            if (!message) return;

            // Send via API (você precisará criar o endpoint)
            fetch('/api/send_message.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    conversation_id: <?php echo $conversation_id ?? 0; ?>,
                    message: message,
                    message_type: 'text'
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    document.getElementById('messageInput').value = '';
                    location.reload(); // Reload para mostrar nova mensagem
                }
            });
        }

        function handleFileSelect(input, type) {
            const file = input.files[0];
            if (!file) return;

            // Upload file (você precisará criar o endpoint)
            const formData = new FormData();
            formData.append('file', file);
            formData.append('type', type);
            formData.append('conversation_id', <?php echo $conversation_id ?? 0; ?>);

            fetch('/api/upload_chat_file.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                }
            });
        }

        // Scroll to bottom on load
        const chatMessages = document.getElementById('chatMessages');
        if (chatMessages) {
            chatMessages.scrollTop = chatMessages.scrollHeight;
        }

        // Auto-resize textarea
        const messageInput = document.getElementById('messageInput');
        if (messageInput) {
            messageInput.addEventListener('input', function() {
                this.style.height = 'auto';
                this.style.height = Math.min(this.scrollHeight, 120) + 'px';
            });
        }

        // Close modal when clicking outside
        document.getElementById('newChatModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeNewChatModal();
            }
        });
    </script>
    <script src="/public/js/main.js"></script>
</body>
</html>
