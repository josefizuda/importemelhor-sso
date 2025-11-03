<?php
/**
 * Migration Runner
 * Execute this file once to run the notifications migration
 * Access: https://auth.importemelhor.com.br/database/run_migration.php
 */

require_once '../config.php';

// Security check - only allow admin
session_start();
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

try {
    // Read migration file
    $sql = file_get_contents(__DIR__ . '/notifications_migration.sql');

    // Execute migration
    $db->exec($sql);

    echo "✅ Migration executed successfully!\n\n";
    echo "The following has been created:\n";
    echo "- notifications table\n";
    echo "- notification_reads table\n";
    echo "- sp_get_user_notifications() function\n";
    echo "- sp_count_unread_notifications() function\n";
    echo "- Indexes for performance\n\n";
    echo "You can now use the notification system!\n";

} catch (PDOException $e) {
    echo "❌ Error running migration:\n";
    echo $e->getMessage() . "\n\n";
    echo "If tables already exist, you can ignore this error.\n";
}

echo "</pre>";
echo '<br><a href="/admin/notifications.php">Go to Notifications</a>';
?>
