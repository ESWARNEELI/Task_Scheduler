<?php
require_once 'functions.php';

$email = $_GET['email'] ?? '';
$code = $_GET['code'] ?? '';
$verified = false;
$error = false;

if (!empty($email) && !empty($code)) {
    $verified = verifySubscription($email, $code);
    if (!$verified) {
        $error = true;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Email Verification - Task Scheduler</title>
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
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .verification-container {
            background: white;
            border-radius: 16px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.3);
            padding: 50px;
            max-width: 500px;
            width: 100%;
            text-align: center;
            animation: fadeInUp 0.6s ease-out;
        }

        .icon {
            width: 80px;
            height: 80px;
            margin: 0 auto 30px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 40px;
        }

        .icon.success {
            background: #d4edda;
            color: #28a745;
        }

        .icon.error {
            background: #f8d7da;
            color: #dc3545;
        }

        h1 {
            color: #333;
            margin-bottom: 20px;
            font-size: 2rem;
        }

        p {
            color: #666;
            line-height: 1.8;
            margin-bottom: 30px;
            font-size: 1.1rem;
        }

        .btn {
            display: inline-block;
            padding: 15px 40px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            text-decoration: none;
            border-radius: 8px;
            font-weight: 600;
            transition: all 0.3s ease;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(102, 126, 234, 0.4);
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

        @media (max-width: 768px) {
            .verification-container {
                padding: 30px 20px;
            }

            h1 {
                font-size: 1.5rem;
            }
        }
    </style>
</head>
<body>
    <div class="verification-container">
        <?php if ($verified): ?>
            <div class="icon success">✓</div>
            <h1>Email Verified!</h1>
            <p>Your email address has been successfully verified. You will now receive hourly reminders for your pending tasks.</p>
            <a href="index.php" class="btn">Go to Task Scheduler</a>
        <?php elseif ($error): ?>
            <div class="icon error">✕</div>
            <h1>Verification Failed</h1>
            <p>The verification link is invalid or has expired. Please try subscribing again.</p>
            <a href="index.php" class="btn">Back to Home</a>
        <?php else: ?>
            <div class="icon error">⚠</div>
            <h1>Invalid Request</h1>
            <p>Missing verification parameters. Please use the link from your email.</p>
            <a href="index.php" class="btn">Back to Home</a>
        <?php endif; ?>
    </div>
</body>
</html>
