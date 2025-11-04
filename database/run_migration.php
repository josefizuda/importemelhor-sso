<?php
/**
 * Migration Runner
 * Execute this file once to run the notifications migration
 * Access: https://auth.importemelhor.com.br/database/run_migration.php
 */

require_once '../config.php';

// Security check - only allow admin
// Note: session_start() is already called in config.php
$auth = new Auth();

if (!isset($_COOKIE['sso_token'])) {
    die('Unauthorized - Please login first');
}

$session = $auth->validateSession($_COOKIE['sso_token']);
if (!$session || $session['email'] !== 'app@importemelhor.com.br') {
    die('Unauthorized - Admin only');
}

$db = Database::getInstance()->getConnection();

echo "<h1>Running Notifications Migration</h1>";
echo "<pre>";

// Check which migration to run
$migration = $_GET['type'] ?? 'notifications';

$migrations = [
    'notifications' => [
        'file' => 'notifications_migration.sql',
        'name' => 'Notifications System',
        'items' => [
            'notifications table',
            'notification_reads table',
            'sp_get_user_notifications() function',
            'sp_count_unread_notifications() function',
            'Indexes for performance'
        ]
    ],
    'chat' => [
        'file' => 'chat_migration.sql',
        'name' => 'Chat System',
        'items' => [
            'chat_conversations table',
            'chat_participants table',
            'chat_messages table',
            'chat_message_reads table',
            'sp_get_user_conversations() function',
            'sp_get_conversation_messages() function',
            'sp_count_total_unread_messages() function',
            'Triggers and indexes'
        ]
    ],
    'chat_permission' => [
        'file' => 'add_chat_permission.sql',
        'name' => 'Chat Permission',
        'items' => [
            'can_access_chat column in user_roles table'
        ]
    ],
    'integrations' => [
        'file' => 'integrations_settings.sql',
        'name' => 'System Integrations',
        'items' => [
            'system_settings table',
            'Google Analytics 4 settings',
            'Facebook Pixel settings',
            'Google Ads settings',
            'reCAPTCHA settings'
        ]
    ]
];

if (!isset($migrations[$migration])) {
    die('Invalid migration type. Use: ?type=notifications, ?type=chat, ?type=chat_permission, or ?type=integrations');
}

$config = $migrations[$migration];

try {
    // Read migration file
    $sql = file_get_contents(__DIR__ . '/' . $config['file']);

    // Execute migration
    $db->exec($sql);

    echo "✅ {$config['name']} Migration executed successfully!\n\n";
    echo "The following has been created:\n";
    foreach ($config['items'] as $item) {
        echo "- $item\n";
    }
    echo "\nYou can now use the {$config['name']}!\n";

} catch (PDOException $e) {
    echo "❌ Error running migration:\n";
    echo $e->getMessage() . "\n\n";
    echo "If tables already exist, you can ignore this error.\n";
}

echo "</pre>";
echo '<h3>Available Migrations:</h3>';
echo '<div style="margin-bottom: 2rem;">';
echo '<a href="?type=notifications" style="margin-right: 1rem; margin-bottom: 0.5rem; padding: 0.75rem 1.5rem; background: #0423b2; color: white; text-decoration: none; border-radius: 4px; display: inline-block;">📢 Notifications</a>';
echo '<a href="?type=chat" style="margin-right: 1rem; margin-bottom: 0.5rem; padding: 0.75rem 1.5rem; background: #0423b2; color: white; text-decoration: none; border-radius: 4px; display: inline-block;">💬 Chat System</a>';
echo '<a href="?type=chat_permission" style="margin-right: 1rem; margin-bottom: 0.5rem; padding: 0.75rem 1.5rem; background: #0423b2; color: white; text-decoration: none; border-radius: 4px; display: inline-block;">🔐 Chat Permission</a>';
echo '<a href="?type=integrations" style="margin-right: 1rem; margin-bottom: 0.5rem; padding: 0.75rem 1.5rem; background: #28a745; color: white; text-decoration: none; border-radius: 4px; display: inline-block;">🔗 Integrations</a>';
echo '</div>';
echo '<h3>Quick Links:</h3>';
echo '<a href="/system_status.php" style="margin-right: 1rem; padding: 0.5rem 1rem; background: #ffc107; color: #333; font-weight: bold; text-decoration: none; border-radius: 4px; display: inline-block;">🔍 System Status</a>';
echo '<a href="/database/auto_fix_chat.php" style="margin-right: 1rem; padding: 0.5rem 1rem; background: #dc3545; color: white; text-decoration: none; border-radius: 4px; display: inline-block;">🔧 Auto-Fix Chat</a>';
echo '<a href="/chat.php" style="margin-right: 1rem;">Go to Chat</a>';
echo '<a href="/dashboard.php">Go to Dashboard</a>';
?>
