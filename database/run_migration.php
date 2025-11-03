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
    ]
];

if (!isset($migrations[$migration])) {
    die('Invalid migration type. Use: ?type=notifications or ?type=chat');
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
echo '<h3>Run Migrations:</h3>';
echo '<a href="?type=notifications" style="margin-right: 1rem; padding: 0.5rem 1rem; background: #0423b2; color: white; text-decoration: none; border-radius: 4px; display: inline-block;">Run Notifications Migration</a>';
echo '<a href="?type=chat" style="margin-right: 1rem; padding: 0.5rem 1rem; background: #0423b2; color: white; text-decoration: none; border-radius: 4px; display: inline-block;">Run Chat Migration</a>';
echo '<br><br>';
echo '<h3>Quick Links:</h3>';
echo '<a href="/notifications.php" style="margin-right: 1rem;">Go to Notifications</a>';
echo '<a href="/chat.php">Go to Chat</a>';
?>
