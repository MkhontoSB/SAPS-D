
<?php
// login.php
session_start();

// If user already logged in, redirect to dashboard
if (isset($_SESSION['user'])) {
    header("Location: dashboard.php");
    exit();
}

$error = '';

// Handle token verification from facial recognition
if (isset($_GET['token'])) {
    $token = $_GET['token'];

    $url = 'http://localhost:5000/php-auth';
    $data = ['token' => $token];

    $options = [
        'http' => [
            'header'  => "Content-type: application/json\r\n",
            'method'  => 'POST',
            'content' => json_encode($data)
        ]
    ];

    $context  = stream_context_create($options);
    $result = file_get_contents($url, false, $context);

    if ($result !== FALSE) {
        $response = json_decode($result, true);

        if ($response['success'] && $response['verified']) {
            $_SESSION['user'] = $response['user'];
            header("Location: dashboard.php");
            exit();
        } else {
            $error = $response['message'] ?? "Token verification failed";
        }
    } else {
        $error = "Unable to connect to authentication service";
    }
}

// Handle badge login
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $badge_id = $_POST['badge_id'] ?? '';

    if (empty($badge_id)) {
        $error = "Please enter your badge ID";
    } else {
        $url = 'http://localhost:5000/manual-login';
        $data = ['badge_id' => $badge_id];

        $options = [
            'http' => [
                'header'  => "Content-type: application/json\r\n",
                'method'  => 'POST',
                'content' => json_encode($data)
            ]
        ];

        $context  = stream_context_create($options);
        $result = file_get_contents($url, false, $context);

        if ($result !== FALSE) {
            $response = json_decode($result, true);

            if ($response['success'] && $response['verified']) {
                $_SESSION['user'] = $response['user'];
                header("Location: dashboard.php");
                exit();
            } else {
                $error = $response['message'] ?? "Authentication failed";
            }
        } else {
            $error = "Unable to connect to authentication service";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>SAPS Login</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .login-container { max-width: 400px; margin: 100px auto; }
        .login-options { display: flex; margin-bottom: 20px; }
        .login-option { flex: 1; text-align: center; padding: 15px; background: #163a67; cursor: pointer; }
        .login-option:first-child { border-radius: 6px 0 0 6px; }
        .login-option:last-child { border-radius: 0 6px 6px 0; }
        .login-option.active { background: #1d4b85; font-weight: bold; }
        .login-form { display: none; }
        .login-form.active { display: block; }
        .error { color: #ff6b6b; margin: 10px 0; padding: 10px; background: rgba(255, 107, 107, 0.1); border-radius: 4px; }
    </style>
</head>
<body>
<div class="container login-container">
    <img src="logo.png" alt="SAPS Logo" class="logo">
    <h2>Secure Access System</h2>

    <?php if ($error): ?>
        <div class="error"><?= $error ?></div>
    <?php endif; ?>

    <div class="login-options">
        <div class="login-option active" id="badgeOption">Badge Login</div>
        <div class="login-option" id="facialOption">Facial Recognition</div>
    </div>

    <div class="login-form active" id="badgeLogin">
        <form method="POST">
            <input type="text" name="badge_id" placeholder="Enter your 5-digit badge ID" required>
            <button type="submit" class="btn-primary">Login with Badge</button>
        </form>
    </div>

    <div class="login-form" id="facialLogin">
        <p>Use facial recognition portal:</p>
        <a href="http://localhost:5000/login" class="btn-success" style="display:block; text-align:center;">
            Go to Facial Recognition
        </a>
    </div>
</div>

<script>
document.getElementById('badgeOption').addEventListener('click', function() {
    this.classList.add('active');
    document.getElementById('facialOption').classList.remove('active');
    document.getElementById('badgeLogin').classList.add('active');
    document.getElementById('facialLogin').classList.remove('active');
});

document.getElementById('facialOption').addEventListener('click', function() {
    this.classList.add('active');
    document.getElementById('badgeOption').classList.remove('active');
    document.getElementById('facialLogin').classList.add('active');
    document.getElementById('badgeLogin').classList.remove('active');
});
</script>
</body>
</html>
