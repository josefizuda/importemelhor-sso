<?php
// Chat Widget - Include at bottom of pages
// Only show if user has chat permission
if (isset($auth) && isset($session)) {
    $hasChatPermission = $auth->checkPermission($session['user_id'], 'access_chat');
    $unreadChatCount = $hasChatPermission ? $auth->getTotalUnreadMessagesCount($session['user_id']) : 0;

    if ($hasChatPermission && basename($_SERVER['PHP_SELF']) !== 'chat.php'):
?>
<div id="chatWidget" class="chat-widget">
    <div class="chat-widget-button" onclick="openChatWidget()">
        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/>
        </svg>
        <?php if ($unreadChatCount > 0): ?>
        <span class="chat-widget-badge"><?php echo $unreadChatCount; ?></span>
        <?php endif; ?>
    </div>
</div>

<!-- Chat notification sound -->
<audio id="chatNotificationSound" preload="auto">
    <source src="data:audio/mp3;base64,SUQzBAAAAAAAI1RTU0UAAAAPAAADTGF2ZjU4Ljc2LjEwMAAAAAAAAAAAAAAA//tQAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAWGluZwAAAA8AAAACAAADhAC7u7u7u7u7u7u7u7u7u7u7u7u7u7u7u7u7u7u7u7u7u7u7u7u7u7u7u7u7u7u7////////////////////////////////////////////AAAAAExhdmM1OC4xMwAAAAAAAAAAAAAAACQCgAAAAAAAAAOE4Hx2" type="audio/mp3">
</audio>

<style>
.chat-widget {
    position: fixed;
    bottom: 24px;
    right: 24px;
    z-index: 9998;
}

.chat-widget-button {
    width: 60px;
    height: 60px;
    border-radius: 50%;
    background: linear-gradient(135deg, var(--color-primary), var(--color-primary-dark));
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    box-shadow: 0 4px 12px rgba(4, 35, 178, 0.4);
    transition: all 0.3s ease;
    position: relative;
}

.chat-widget-button:hover {
    transform: scale(1.1);
    box-shadow: 0 6px 16px rgba(4, 35, 178, 0.5);
}

.chat-widget-button:active {
    transform: scale(0.95);
}

.chat-widget-badge {
    position: absolute;
    top: -4px;
    right: -4px;
    min-width: 22px;
    height: 22px;
    border-radius: 11px;
    background: var(--color-error);
    color: white;
    font-size: 0.75rem;
    font-weight: 700;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 0 6px;
    border: 2px solid var(--bg-primary);
    animation: pulse 2s infinite;
}

@keyframes pulse {
    0%, 100% {
        transform: scale(1);
    }
    50% {
        transform: scale(1.1);
    }
}

.chat-widget-button svg {
    animation: bounce 2s ease-in-out infinite;
}

@keyframes bounce {
    0%, 100% {
        transform: translateY(0);
    }
    50% {
        transform: translateY(-4px);
    }
}

/* Mobile adjustments */
@media (max-width: 768px) {
    .chat-widget {
        bottom: 16px;
        right: 16px;
    }

    .chat-widget-button {
        width: 56px;
        height: 56px;
    }
}
</style>

<script>
function openChatWidget() {
    window.location.href = '/chat.php';
}

// Play notification sound when there are new messages
let lastUnreadCount = <?php echo $unreadChatCount; ?>;

// Check for new messages every 30 seconds
setInterval(function() {
    fetch('/api/get_unread_chat_count.php')
        .then(response => response.json())
        .then(data => {
            if (data.count > lastUnreadCount) {
                // New message received - play sound
                const sound = document.getElementById('chatNotificationSound');
                if (sound) {
                    sound.play().catch(e => console.log('Could not play sound:', e));
                }

                // Update badge
                const badge = document.querySelector('.chat-widget-badge');
                if (badge) {
                    badge.textContent = data.count;
                } else if (data.count > 0) {
                    // Create badge if it doesn't exist
                    const button = document.querySelector('.chat-widget-button');
                    const newBadge = document.createElement('span');
                    newBadge.className = 'chat-widget-badge';
                    newBadge.textContent = data.count;
                    button.appendChild(newBadge);
                }
            }
            lastUnreadCount = data.count;
        })
        .catch(error => console.log('Error checking messages:', error));
}, 30000); // Check every 30 seconds
</script>
<?php
    endif;
}
?>
