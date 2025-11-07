<?php

define('TASKS_FILE', __DIR__ . '/tasks.txt');
define('SUBSCRIBERS_FILE', __DIR__ . '/subscribers.txt');
define('PENDING_FILE', __DIR__ . '/pending_subscriptions.txt');

function addTask($task_name) {
    $task_name = trim($task_name);

    if (empty($task_name)) {
        return false;
    }

    $tasks = getAllTasks();

    foreach ($tasks as $task) {
        if (strcasecmp($task['name'], $task_name) === 0) {
            return false;
        }
    }

    $task_id = uniqid();
    $task_data = $task_id . '|' . $task_name . '|0' . PHP_EOL;

    file_put_contents(TASKS_FILE, $task_data, FILE_APPEND | LOCK_EX);

    return true;
}

function getAllTasks() {
    if (!file_exists(TASKS_FILE)) {
        touch(TASKS_FILE);
        return [];
    }

    $content = file_get_contents(TASKS_FILE);
    if (empty(trim($content))) {
        return [];
    }

    $lines = explode(PHP_EOL, trim($content));
    $tasks = [];

    foreach ($lines as $line) {
        if (empty(trim($line))) {
            continue;
        }

        $parts = explode('|', $line);
        if (count($parts) === 3) {
            $tasks[] = [
                'id' => $parts[0],
                'name' => $parts[1],
                'completed' => (int)$parts[2]
            ];
        }
    }

    return $tasks;
}

function markTaskAsCompleted($task_id, $is_completed) {
    $tasks = getAllTasks();
    $found = false;

    foreach ($tasks as &$task) {
        if ($task['id'] === $task_id) {
            $task['completed'] = $is_completed ? 1 : 0;
            $found = true;
            break;
        }
    }

    if (!$found) {
        return false;
    }

    $content = '';
    foreach ($tasks as $task) {
        $content .= $task['id'] . '|' . $task['name'] . '|' . $task['completed'] . PHP_EOL;
    }

    file_put_contents(TASKS_FILE, $content, LOCK_EX);

    return true;
}

function deleteTask($task_id) {
    $tasks = getAllTasks();
    $new_tasks = [];
    $found = false;

    foreach ($tasks as $task) {
        if ($task['id'] !== $task_id) {
            $new_tasks[] = $task;
        } else {
            $found = true;
        }
    }

    if (!$found) {
        return false;
    }

    $content = '';
    foreach ($new_tasks as $task) {
        $content .= $task['id'] . '|' . $task['name'] . '|' . $task['completed'] . PHP_EOL;
    }

    file_put_contents(TASKS_FILE, $content, LOCK_EX);

    return true;
}

function generateVerificationCode() {
    return str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);
}

function subscribeEmail($email) {
    $email = trim(strtolower($email));

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return ['success' => false, 'message' => 'Invalid email address'];
    }

    if (file_exists(SUBSCRIBERS_FILE)) {
        $subscribers = file(SUBSCRIBERS_FILE, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if (in_array($email, $subscribers)) {
            return ['success' => false, 'message' => 'Email already subscribed'];
        }
    }

    $pending = [];
    if (file_exists(PENDING_FILE)) {
        $pending_lines = file(PENDING_FILE, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($pending_lines as $line) {
            $parts = explode('|', $line);
            if (count($parts) === 3) {
                $pending[$parts[0]] = true;
            }
        }
    }

    if (isset($pending[$email])) {
        return ['success' => false, 'message' => 'Verification email already sent. Please check your inbox.'];
    }

    $code = generateVerificationCode();
    $timestamp = time();

    $pending_data = $email . '|' . $code . '|' . $timestamp . PHP_EOL;
    file_put_contents(PENDING_FILE, $pending_data, FILE_APPEND | LOCK_EX);

    $verify_link = getBaseUrl() . '/src/verify.php?email=' . urlencode($email) . '&code=' . $code;

    $subject = 'Task Scheduler - Verify Your Email';
    $message = "Welcome to Task Scheduler!\n\n";
    $message .= "Please verify your email address by clicking the link below:\n\n";
    $message .= $verify_link . "\n\n";
    $message .= "Your verification code is: " . $code . "\n\n";
    $message .= "This link will expire in 24 hours.\n\n";
    $message .= "If you didn't request this, please ignore this email.";

    $headers = "From: noreply@taskscheduler.com\r\n";
    $headers .= "Reply-To: noreply@taskscheduler.com\r\n";
    $headers .= "X-Mailer: PHP/" . phpversion();

    mail($email, $subject, $message, $headers);

    return ['success' => true, 'message' => 'Verification email sent! Please check your inbox.'];
}

function verifySubscription($email, $code) {
    $email = trim(strtolower($email));

    if (!file_exists(PENDING_FILE)) {
        return false;
    }

    $pending_lines = file(PENDING_FILE, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    $new_pending = [];
    $verified = false;

    foreach ($pending_lines as $line) {
        $parts = explode('|', $line);
        if (count($parts) === 3) {
            $pending_email = $parts[0];
            $pending_code = $parts[1];
            $timestamp = (int)$parts[2];

            if ($pending_email === $email && $pending_code === $code) {
                if (time() - $timestamp < 86400) {
                    $verified = true;

                    file_put_contents(SUBSCRIBERS_FILE, $email . PHP_EOL, FILE_APPEND | LOCK_EX);
                } else {
                    continue;
                }
            } else {
                $new_pending[] = $line;
            }
        }
    }

    file_put_contents(PENDING_FILE, implode(PHP_EOL, $new_pending) . (count($new_pending) > 0 ? PHP_EOL : ''), LOCK_EX);

    return $verified;
}

function unsubscribeEmail($email) {
    $email = trim(strtolower($email));

    if (!file_exists(SUBSCRIBERS_FILE)) {
        return false;
    }

    $subscribers = file(SUBSCRIBERS_FILE, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    $new_subscribers = [];
    $found = false;

    foreach ($subscribers as $subscriber) {
        if (trim(strtolower($subscriber)) !== $email) {
            $new_subscribers[] = $subscriber;
        } else {
            $found = true;
        }
    }

    if (!$found) {
        return false;
    }

    file_put_contents(SUBSCRIBERS_FILE, implode(PHP_EOL, $new_subscribers) . (count($new_subscribers) > 0 ? PHP_EOL : ''), LOCK_EX);

    return true;
}

function sendTaskReminders() {
    if (!file_exists(SUBSCRIBERS_FILE)) {
        return;
    }

    $subscribers = file(SUBSCRIBERS_FILE, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

    if (empty($subscribers)) {
        return;
    }

    $tasks = getAllTasks();
    $pending_tasks = array_filter($tasks, function($task) {
        return $task['completed'] == 0;
    });

    if (empty($pending_tasks)) {
        return;
    }

    foreach ($subscribers as $email) {
        $email = trim($email);
        if (!empty($email)) {
            sendTaskEmail($email, $pending_tasks);
        }
    }
}

function sendTaskEmail($email, $pending_tasks) {
    $subject = 'Task Scheduler - Hourly Reminder';

    $message = "Hello,\n\n";
    $message .= "Here are your pending tasks:\n\n";

    $count = 1;
    foreach ($pending_tasks as $task) {
        $message .= $count . ". " . $task['name'] . "\n";
        $count++;
    }

    $message .= "\n";
    $message .= "Total pending tasks: " . count($pending_tasks) . "\n\n";
    $message .= "Visit " . getBaseUrl() . "/src/ to manage your tasks.\n\n";

    $unsubscribe_link = getBaseUrl() . '/src/unsubscribe.php?email=' . urlencode($email);
    $message .= "---\n";
    $message .= "To unsubscribe from these reminders, click here: " . $unsubscribe_link;

    $headers = "From: noreply@taskscheduler.com\r\n";
    $headers .= "Reply-To: noreply@taskscheduler.com\r\n";
    $headers .= "X-Mailer: PHP/" . phpversion();

    mail($email, $subject, $message, $headers);
}

function getBaseUrl() {
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $script = dirname($_SERVER['SCRIPT_NAME'] ?? '');
    $base = rtrim($script, '/');

    if (strpos($base, '/src') !== false) {
        $base = substr($base, 0, strpos($base, '/src'));
    }

    return $protocol . '://' . $host . $base;
}
