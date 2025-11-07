<?php

require_once __DIR__ . '/functions.php';

$log_file = __DIR__ . '/cron_log.txt';

$timestamp = date('Y-m-d H:i:s');
$log_message = "[{$timestamp}] Starting hourly task reminder job...\n";

file_put_contents($log_file, $log_message, FILE_APPEND);

try {
    sendTaskReminders();

    $log_message = "[{$timestamp}] Task reminders sent successfully.\n";
    file_put_contents($log_file, $log_message, FILE_APPEND);

    echo "Task reminders sent successfully at {$timestamp}\n";

} catch (Exception $e) {
    $log_message = "[{$timestamp}] ERROR: " . $e->getMessage() . "\n";
    file_put_contents($log_file, $log_message, FILE_APPEND);

    echo "Error sending task reminders: " . $e->getMessage() . "\n";
}
