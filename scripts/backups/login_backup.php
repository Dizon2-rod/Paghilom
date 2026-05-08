<?php
require_once 'config.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check(); // ✅ Security check

    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($username && $password) {
        // Check if input is email or username; support both password_hash and legacy password column
        $stmt = $mysqli->prepare("SELECT id, name, email, COALESCE(password_hash, password) AS password_hash, role, IFNULL(is_active,1) AS is_active FROM users WHERE email = ? OR name = ? LIMIT 1");
        if ($stmt) {
            $stmt->bind_param("ss", $username, $username);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result && $result->num_rows === 1) {
                $user = $result->fetch_assoc();

                if (!empty($user['is_active']) && password_verify($password, $user['password_hash'])) {
                    // ✅ Login successful - Update last login
                    $update = $mysqli->prepare("UPDATE users SET last_login = NOW(), login_attempts = 0 WHERE id = ?");
                    if ($update) { $update->bind_param("i", $user['id']); $update->execute(); }
                    
                    $_SESSION['user'] = [
                        'id' => $user['id'],
                        'name' => $user['name'],
                        'email' => $user['email'],
                        'role' => $user['role']
                    ];

                    // Redirect based on role
$redirect = $_GET['redirect'] ?? (in_array($user['role'], ['admin', 'staff']) ? 'admin/dashboard.php' : 'index.php');
                    header("Location: " . $redirect);
                    exit;
                } else {
                    // Track failed login attempt
                    $update = $mysqli->prepare("UPDATE users SET login_attempts = login_attempts + 1 WHERE id = ?");
                    if ($update) { $update->bind_param("i", $user['id']); $update->execute(); }
                    $error = empty($user['is_active']) ? "Account is disabled." : "Incorrect password.";
                }
            } else {
                $error = "Account not found. Please check your credentials.";
            }

            $stmt->close();
        } else {
            $error = "Database error. Please contact support.";
        }
    } else {
        $error = "Please fill in all fields.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Login - <?= APP_NAME ?></title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">

  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    body {
      background: linear-gradient(135deg, #1e3d1f, #2e8b57);
      height: 100vh;
      font-family: 'Poppins', sans-serif;
      display: flex;
      align-items: center;
      justify-content: center;
      color: #fff;
    }

    .login-card {
      background: #fff;
      color: #333;
      border-radius: 15px;
      box-shadow: 0 10px 25px rgba(0,0,0,0.3);
      padding: 35px 40px;
      width: 100%;
      max-width: 400px;
      text-align: center;
      animation: fadeIn 0.6s ease-in-out;
    }

    .login-card img {
      width: 100px;
      height: 100px;
      object-fit: cover;
      border-radius: 50%;
      margin-bottom: 15px;
      border: 3px solid #2e8b57;
    }

    .btn-green {
      background-color: #2e8b57;
      border: none;
      transition: 0.3s;
      color: #fff;
      font-weight: 600;
    }

    .btn-green:hover {
      background-color: #1e3d1f;
    }

    .show-password {
      position: absolute;
      right: 15px;
      top: 10px;
      cursor: pointer;
      color: #666;
    }

    .form-group {
      position: relative;
    }

    @keyframes fadeIn {
      from { opacity: 0; transform: translateY(-20px); }
      to { opacity: 1; transform: translateY(0); }
    }
  </style>
</head>
<body>

<div class="login-card">
  <img src="assets/uploads/logo.jpeg" alt="Paghilom Cafe Logo">
  <h3 class="fw-bold mb-3"><?= APP_NAME ?></h3>

  <?php if ($error): ?>
    <div class="alert alert-danger py-2"><?= e($error) ?></div>
  <?php endif; ?>

  <form method="POST" autocomplete="off">
    <?= csrf_field() ?>

    <div class="mb-3 text-start">
      <label class="form-label">Username</label>
      <input type="text" name="username" class="form-control" required>
    </div>

    <div class="mb-3 text-start form-group">
      <label class="form-label">Password</label>
      <input type="password" name="password" id="password" class="form-control" required>
      <span class="show-password" onclick="togglePassword()">👁️</span>
    </div>

    <button type="submit" class="btn btn-green w-100 py-2">Login</button>

    <div class="mt-3">
      <a href="register.php" class="text-decoration-none">Create Account</a> |
      <a href="forgot_password.php" class="text-decoration-none">Forgot Password?</a>
    </div>
  </form>
</div>

<script>
function togglePassword() {
  const field = document.getElementById('password');
  field.type = field.type === 'password' ? 'text' : 'password';
}
</script>

</body>
</html>
