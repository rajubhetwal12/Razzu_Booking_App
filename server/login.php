<?php
$pageTitle = 'Sign In — LuxStay';
require_once 'config/db.php';
require_once 'includes/auth.php';

if (isLoggedIn()) {
    $role = currentUser()['role'];
    redirect(BASE_URL . '/dashboard/' . $role . '.php');
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (!$email || !$password) {
        $error = 'Please enter email and password.';
    } else {
        $db   = getDB();
        $stmt = $db->prepare('SELECT * FROM users WHERE email = ? AND is_active = 1 LIMIT 1');
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            loginUser($user);
            flash('success', 'Welcome back, ' . $user['name'] . '!');
            redirect(BASE_URL . '/dashboard/' . $user['role'] . '.php');
        } else {
            $error = 'Invalid email or password.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en" data-theme="dark">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title><?= $pageTitle ?></title>
<link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
<link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><text y='.9em' font-size='90'>🏨</text></svg>">
<style>
.login-page{min-height:100vh;display:flex;align-items:center;justify-content:center;padding:2rem 1rem;background:var(--bg)}
.login-page::before{content:'';position:fixed;top:50%;left:50%;transform:translate(-50%,-50%);width:600px;height:600px;background:radial-gradient(ellipse,rgba(212,160,23,0.07),transparent 70%);pointer-events:none;border-radius:50%}
.login-box{width:100%;max-width:440px;position:relative;z-index:1}
.login-logo{text-align:center;margin-bottom:2rem}
.login-logo .logo-icon{width:52px;height:52px;font-size:1.4rem;margin:0 auto .75rem;box-shadow:0 8px 32px rgba(212,160,23,0.4)}
.login-logo h1{font-family:'Playfair Display',serif;font-size:1.625rem;font-weight:800;color:var(--text);margin-bottom:.25rem}
.login-logo p{font-size:.875rem;color:var(--text3)}
.login-card{background:var(--glass);backdrop-filter:blur(24px);-webkit-backdrop-filter:blur(24px);border:1px solid rgba(255,255,255,.1);border-radius:18px;padding:2.25rem;box-shadow:0 24px 60px rgba(0,0,0,.5)}
[data-theme="light"] .login-card{background:var(--bg2);border-color:var(--border);box-shadow:0 8px 40px rgba(0,0,0,.1)}
.demo-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:.5rem;margin-top:1rem}
.demo-btn{padding:.625rem .5rem;border-radius:10px;font-size:.75rem;font-weight:700;cursor:pointer;border:1px solid;background:transparent;font-family:inherit;transition:all .2s;text-align:center}
.demo-btn:hover{transform:translateY(-1px)}
.demo-admin{border-color:rgba(239,68,68,.4);color:#f87171}
.demo-admin:hover{background:rgba(239,68,68,.08)}
.demo-manager{border-color:rgba(59,130,246,.4);color:#60a5fa}
.demo-manager:hover{background:rgba(59,130,246,.08)}
.demo-customer{border-color:rgba(212,160,23,.4);color:var(--gold)}
.demo-customer:hover{background:rgba(212,160,23,.08)}
.cred-hint{margin-top:.875rem;padding:.75rem 1rem;background:rgba(212,160,23,.06);border:1px solid rgba(212,160,23,.2);border-radius:10px;font-size:.8rem}
.cred-hint p{margin-bottom:.25rem;color:var(--text2)}
.cred-hint span{color:var(--text);font-weight:600}
</style>
</head>
<body>
<div class="login-page">
  <!-- Theme toggle top right -->
  <div style="position:fixed;top:1rem;right:1rem;z-index:10">
    <button class="theme-toggle" id="theme-toggle" title="Toggle theme">☀️</button>
  </div>

  <div class="login-box">
    <div class="login-logo">
      <div class="logo-icon" style="width:52px;height:52px;font-size:1.4rem;border-radius:14px;display:flex;align-items:center;justify-content:center;margin:0 auto .75rem;background:linear-gradient(135deg,#d4a017,#f5c842);box-shadow:0 8px 32px rgba(212,160,23,0.4)">🏨</div>
      <h1>Welcome Back</h1>
      <p>Sign in to your LuxStay account</p>
    </div>

    <div class="login-card">
      <?php if ($error): ?>
        <div class="alert alert-error"><?= e($error) ?></div>
      <?php endif; ?>

      <form method="POST" autocomplete="on">
        <div class="form-group">
          <label class="form-label">Email Address</label>
          <div class="input-wrapper">
            <span class="input-icon">✉️</span>
            <input type="email" name="email" id="inp-email" class="form-input"
              placeholder="your@email.com" value="<?= e($_POST['email'] ?? '') ?>" required autofocus>
          </div>
        </div>

        <div class="form-group">
          <label class="form-label">Password</label>
          <div class="input-wrapper">
            <span class="input-icon">🔒</span>
            <input type="password" name="password" id="inp-password" class="form-input"
              placeholder="Enter your password" required>
            <button type="button" class="input-icon-right" onclick="togglePass()">👁️</button>
          </div>
        </div>

        <div style="text-align:right;margin-bottom:1.25rem">
          <a href="forgot-password.php" class="link-gold" style="font-size:.8125rem">Forgot password?</a>
        </div>

        <button type="submit" class="btn-full btn-primary">Sign In →</button>
      </form>

      <div style="position:relative;text-align:center;margin:1.25rem 0">
        <div style="height:1px;background:var(--border)"></div>
        <span style="position:absolute;top:50%;left:50%;transform:translate(-50%,-50%);background:var(--glass);padding:0 .75rem;font-size:.75rem;color:var(--text3)">Demo Accounts</span>
      </div>

      <div class="demo-grid">
        <button class="demo-btn demo-admin" onclick="fillDemo('admin@luxstay.com','Admin@LuxStay2024')">
          🔴 Admin
        </button>
        <button class="demo-btn demo-manager" onclick="fillDemo('manager@luxstay.com','Manager@LuxStay2024')">
          🔵 Manager
        </button>
        <button class="demo-btn demo-customer" onclick="fillDemo('customer@luxstay.com','Customer@123')">
          ⭐ Customer
        </button>
      </div>

      <div class="cred-hint" id="cred-hint" style="display:none">
        <p>📧 Email: <span id="hint-email"></span></p>
        <p>🔑 Password: <span id="hint-pass"></span></p>
        <p style="font-size:.7rem;color:var(--text3);margin-top:.5rem">Click Sign In to continue</p>
      </div>
    </div>

    <p style="text-align:center;margin-top:1.25rem;font-size:.875rem;color:var(--text3)">
      Don't have an account?
      <a href="<?= BASE_URL ?>/register.php" class="link-gold">Sign up free</a>
    </p>
  </div>
</div>

<script>
// Theme toggle
const tb = document.getElementById('theme-toggle');
const saved = localStorage.getItem('theme')||'dark';
document.documentElement.setAttribute('data-theme', saved);
if(tb) tb.textContent = saved==='dark'?'☀️':'🌙';
if(tb) tb.onclick = ()=>{
  const c = document.documentElement.getAttribute('data-theme');
  const n = c==='dark'?'light':'dark';
  document.documentElement.setAttribute('data-theme',n);
  localStorage.setItem('theme',n);
  tb.textContent = n==='dark'?'☀️':'🌙';
};

function fillDemo(email, pass) {
  document.getElementById('inp-email').value = email;
  document.getElementById('inp-password').value = pass;
  document.getElementById('hint-email').textContent = email;
  document.getElementById('hint-pass').textContent = pass;
  document.getElementById('cred-hint').style.display = 'block';
}
function togglePass() {
  const inp = document.getElementById('inp-password');
  inp.type = inp.type === 'password' ? 'text' : 'password';
}
</script>
</body>
</html>
