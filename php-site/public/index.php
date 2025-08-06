<?php
session_start();
require_once __DIR__ . '/../includes/db.php';
$db = getDB();

// redirect to dashboard if logged in
if (isset($_SESSION['user_id'])) {
    header('Location: dashboard.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Invalid email address';
    } elseif (strlen($password) < 4) {
        $error = 'Password too short';
    } else {
        if ($action === 'register') {
            // create user
            $stmt = $db->prepare('INSERT INTO users(email, password_hash) VALUES(:e, :p)');
            try {
                $stmt->execute([
                    ':e' => $email,
                    ':p' => password_hash($password, PASSWORD_DEFAULT)
                ]);
            } catch (PDOException $ex) {
                $error = 'Email already registered';
            }
            if (!$error) {
                $_SESSION['user_id'] = $db->lastInsertId();
                header('Location: dashboard.php');
                exit;
            }
        } elseif ($action === 'login') {
            $stmt = $db->prepare('SELECT id, password_hash FROM users WHERE email = :e');
            $stmt->execute([':e' => $email]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($user && password_verify($password, $user['password_hash'])) {
                $_SESSION['user_id'] = $user['id'];
                header('Location: dashboard.php');
                exit;
            } else {
                $error = 'Invalid credentials';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Voice Clone – Login</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
</head>
<body class="bg-light">
<div class="container py-5">
    <h1 class="mb-4 text-center">Voice Clone – PHP</h1>
    <?php if ($error): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
    <div class="row justify-content-center">
        <div class="col-md-6">
            <ul class="nav nav-tabs mb-3" id="authTab" role="tablist">
              <li class="nav-item" role="presentation">
                <button class="nav-link active" id="login-tab" data-bs-toggle="tab" data-bs-target="#login" type="button" role="tab">Login</button>
              </li>
              <li class="nav-item" role="presentation">
                <button class="nav-link" id="register-tab" data-bs-toggle="tab" data-bs-target="#register" type="button" role="tab">Register</button>
              </li>
            </ul>
            <div class="tab-content" id="authTabContent">
              <div class="tab-pane fade show active" id="login" role="tabpanel">
                <form method="POST" class="card card-body">
                    <input type="hidden" name="action" value="login">
                    <div class="mb-3">
                        <label class="form-label">Email</label>
                        <input type="email" name="email" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Password</label>
                        <input type="password" name="password" class="form-control" required>
                    </div>
                    <button class="btn btn-primary w-100">Login</button>
                </form>
              </div>
              <div class="tab-pane fade" id="register" role="tabpanel">
                <form method="POST" class="card card-body">
                    <input type="hidden" name="action" value="register">
                    <div class="mb-3">
                        <label class="form-label">Email</label>
                        <input type="email" name="email" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Password</label>
                        <input type="password" name="password" class="form-control" required>
                    </div>
                    <button class="btn btn-success w-100">Register</button>
                </form>
              </div>
            </div>
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>