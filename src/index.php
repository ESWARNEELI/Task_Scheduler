<?php
require_once 'functions.php';

$message = '';
$message_type = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'add_task':
                if (!empty($_POST['task_name'])) {
                    if (addTask($_POST['task_name'])) {
                        $message = 'Task added successfully!';
                        $message_type = 'success';
                    } else {
                        $message = 'Task already exists or invalid input!';
                        $message_type = 'error';
                    }
                }
                break;

            case 'toggle_task':
                if (!empty($_POST['task_id'])) {
                    $is_completed = isset($_POST['is_completed']) && $_POST['is_completed'] === '1' ? 1 : 0;
                    markTaskAsCompleted($_POST['task_id'], $is_completed);
                }
                break;

            case 'delete_task':
                if (!empty($_POST['task_id'])) {
                    if (deleteTask($_POST['task_id'])) {
                        $message = 'Task deleted successfully!';
                        $message_type = 'success';
                    }
                }
                break;

            case 'subscribe':
                if (!empty($_POST['email'])) {
                    $result = subscribeEmail($_POST['email']);
                    $message = $result['message'];
                    $message_type = $result['success'] ? 'success' : 'error';
                }
                break;
        }
    }
}

$tasks = getAllTasks();
$pending_count = count(array_filter($tasks, function($task) {
    return $task['completed'] == 0;
}));
$completed_count = count($tasks) - $pending_count;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Task Scheduler - Manage Your Tasks</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
            line-height: 1.6;
        }

        .container {
            max-width: 900px;
            margin: 0 auto;
        }

        .header {
            text-align: center;
            color: white;
            margin-bottom: 30px;
            animation: fadeInDown 0.6s ease-out;
        }

        .header h1 {
            font-size: 2.5rem;
            margin-bottom: 10px;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.2);
        }

        .header p {
            font-size: 1.1rem;
            opacity: 0.9;
        }

        .stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
            animation: fadeInUp 0.6s ease-out 0.2s backwards;
        }

        .stat-card {
            background: white;
            padding: 20px;
            border-radius: 12px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            text-align: center;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 6px 12px rgba(0,0,0,0.15);
        }

        .stat-card .number {
            font-size: 2.5rem;
            font-weight: bold;
            color: #667eea;
            margin-bottom: 5px;
        }

        .stat-card .label {
            color: #666;
            font-size: 0.95rem;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .card {
            background: white;
            border-radius: 16px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            padding: 30px;
            margin-bottom: 30px;
            animation: fadeInUp 0.6s ease-out 0.3s backwards;
        }

        .card h2 {
            color: #333;
            margin-bottom: 20px;
            font-size: 1.5rem;
            border-bottom: 3px solid #667eea;
            padding-bottom: 10px;
        }

        .message {
            padding: 15px 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            animation: slideIn 0.3s ease-out;
        }

        .message.success {
            background: #d4edda;
            color: #155724;
            border-left: 4px solid #28a745;
        }

        .message.error {
            background: #f8d7da;
            color: #721c24;
            border-left: 4px solid #dc3545;
        }

        .form-group {
            margin-bottom: 20px;
        }

        label {
            display: block;
            color: #555;
            margin-bottom: 8px;
            font-weight: 500;
        }

        input[type="text"],
        input[type="email"] {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 1rem;
            transition: all 0.3s ease;
        }

        input[type="text"]:focus,
        input[type="email"]:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        .btn {
            padding: 12px 30px;
            border: none;
            border-radius: 8px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(102, 126, 234, 0.4);
        }

        .btn-success {
            background: #28a745;
            color: white;
        }

        .btn-success:hover {
            background: #218838;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(40, 167, 69, 0.3);
        }

        .btn-danger {
            background: #dc3545;
            color: white;
            padding: 8px 16px;
            font-size: 0.9rem;
        }

        .btn-danger:hover {
            background: #c82333;
        }

        .task-list {
            list-style: none;
        }

        .task-item {
            background: #f8f9fa;
            padding: 18px 20px;
            margin-bottom: 12px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            transition: all 0.3s ease;
            border-left: 4px solid #667eea;
            animation: slideIn 0.3s ease-out;
        }

        .task-item:hover {
            background: #e9ecef;
            transform: translateX(5px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }

        .task-item.completed {
            opacity: 0.7;
            border-left-color: #28a745;
        }

        .task-content {
            display: flex;
            align-items: center;
            flex: 1;
            gap: 15px;
        }

        .task-checkbox {
            width: 24px;
            height: 24px;
            cursor: pointer;
            accent-color: #667eea;
        }

        .task-name {
            font-size: 1.1rem;
            color: #333;
            flex: 1;
        }

        .task-item.completed .task-name {
            text-decoration: line-through;
            color: #999;
        }

        .task-actions {
            display: flex;
            gap: 10px;
        }

        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #999;
        }

        .empty-state svg {
            width: 80px;
            height: 80px;
            margin-bottom: 20px;
            opacity: 0.5;
        }

        .empty-state h3 {
            font-size: 1.3rem;
            margin-bottom: 10px;
            color: #666;
        }

        .empty-state p {
            color: #999;
        }

        @keyframes fadeInDown {
            from {
                opacity: 0;
                transform: translateY(-30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateX(-20px);
            }
            to {
                opacity: 1;
                transform: translateX(0);
            }
        }

        @media (max-width: 768px) {
            .header h1 {
                font-size: 2rem;
            }

            .card {
                padding: 20px;
            }

            .task-item {
                flex-direction: column;
                align-items: flex-start;
                gap: 15px;
            }

            .task-actions {
                width: 100%;
                justify-content: flex-end;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>📋 Task Scheduler</h1>
            <p>Manage your tasks and never miss a deadline</p>
        </div>

        <div class="stats">
            <div class="stat-card">
                <div class="number"><?php echo count($tasks); ?></div>
                <div class="label">Total Tasks</div>
            </div>
            <div class="stat-card">
                <div class="number"><?php echo $pending_count; ?></div>
                <div class="label">Pending</div>
            </div>
            <div class="stat-card">
                <div class="number"><?php echo $completed_count; ?></div>
                <div class="label">Completed</div>
            </div>
        </div>

        <div class="card">
            <h2>➕ Add New Task</h2>
            <?php if ($message): ?>
                <div class="message <?php echo $message_type; ?>">
                    <?php echo htmlspecialchars($message); ?>
                </div>
            <?php endif; ?>
            <form method="POST" action="">
                <input type="hidden" name="action" value="add_task">
                <div class="form-group">
                    <label for="task_name">Task Name</label>
                    <input
                        type="text"
                        id="task_name"
                        name="task_name"
                        placeholder="Enter your task..."
                        required
                        autocomplete="off"
                    >
                </div>
                <button type="submit" class="btn btn-primary">Add Task</button>
            </form>
        </div>

        <div class="card">
            <h2>📝 Your Tasks</h2>
            <?php if (empty($tasks)): ?>
                <div class="empty-state">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor">
                        <path d="M9 2C8.44772 2 8 2.44772 8 3V4H6C4.89543 4 4 4.89543 4 6V20C4 21.1046 4.89543 22 6 22H18C19.1046 22 20 21.1046 20 20V6C20 4.89543 19.1046 4 18 4H16V3C16 2.44772 15.5523 2 15 2C14.4477 2 14 2.44772 14 3V4H10V3C10 2.44772 9.55228 2 9 2Z"/>
                    </svg>
                    <h3>No tasks yet!</h3>
                    <p>Add your first task above to get started</p>
                </div>
            <?php else: ?>
                <ul class="task-list">
                    <?php foreach ($tasks as $task): ?>
                        <li class="task-item <?php echo $task['completed'] ? 'completed' : ''; ?>">
                            <div class="task-content">
                                <form method="POST" action="" style="margin: 0;">
                                    <input type="hidden" name="action" value="toggle_task">
                                    <input type="hidden" name="task_id" value="<?php echo htmlspecialchars($task['id']); ?>">
                                    <input type="hidden" name="is_completed" value="<?php echo $task['completed'] ? '0' : '1'; ?>">
                                    <input
                                        type="checkbox"
                                        class="task-checkbox"
                                        <?php echo $task['completed'] ? 'checked' : ''; ?>
                                        onchange="this.form.submit()"
                                    >
                                </form>
                                <span class="task-name"><?php echo htmlspecialchars($task['name']); ?></span>
                            </div>
                            <div class="task-actions">
                                <form method="POST" action="" style="margin: 0;">
                                    <input type="hidden" name="action" value="delete_task">
                                    <input type="hidden" name="task_id" value="<?php echo htmlspecialchars($task['id']); ?>">
                                    <button type="submit" class="btn btn-danger" onclick="return confirm('Are you sure you want to delete this task?')">Delete</button>
                                </form>
                            </div>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </div>

        <div class="card">
            <h2>🔔 Email Reminders</h2>
            <p style="color: #666; margin-bottom: 20px;">Subscribe to receive hourly email reminders for your pending tasks.</p>
            <form method="POST" action="">
                <input type="hidden" name="action" value="subscribe">
                <div class="form-group">
                    <label for="email">Your Email Address</label>
                    <input
                        type="email"
                        id="email"
                        name="email"
                        placeholder="your.email@example.com"
                        required
                        autocomplete="email"
                    >
                </div>
                <button type="submit" class="btn btn-success">Subscribe to Reminders</button>
            </form>
        </div>
    </div>
</body>
</html>
