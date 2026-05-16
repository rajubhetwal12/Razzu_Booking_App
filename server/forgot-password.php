<?php
$pageTitle = 'Forgot Password — LuxStay';
require_once 'config/db.php';
require_once 'includes/auth.php';
if (isLoggedIn()) redirect(BASE_URL . '/');

$step = (int)($_POST['step'] ?? $_GET['step'] ?? 1);
$msg = $err = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $db = getDB();

    if ($step === 1) {
        $email = trim($_POST['email'] ?? '');
        $user = $db->prepare('SELECT id FROM users WHERE email=? AND is_active=1');
        $user->execute([$email]); $user = $user->fetch();
        if (!$user) { $err = 'No account found with that email.'; }
        else {
            // In real app: send OTP via email. Here we use a demo OTP: 123456
            $_SESSION['reset_email'] = $email;
            $_SESSION['reset_otp']   = '123456';
            $step = 2;
            $msg = 'OTP sent! (Demo OTP: <strong>123456</strong>)';
        }
    } elseif ($step === 2) {
        $otp = trim($_POST['otp'] ?? '');
        if ($otp !== ($_SESSION['reset_otp'] ?? '')) { $err = 'Invalid OTP.'; $step = 2; }
        else { $step = 3; $msg = 'OTP verified!'; }
    } elseif ($step === 3) {
        $pass    = $_POST['password'] ?? '';
        $confirm = $_POST['confirm'] ?? '';
        if (strlen($pass) < 6)                     { $err = 'Password too short.'; $step = 3; }
        elseif (!preg_match('/[A-Z]/', $pass))      { $err = 'Need uppercase letter.'; $step = 3; }
        elseif (!preg_match('/[0-9]/', $pass))      { $err = 'Need a number.'; $step = 3; }
        elseif ($pass !== $confirm)                 { $err = 'Passwords do not match.'; $step = 3; }
        else {
            $hash = password_hash($pass, PASSWORD_BCRYPT, ['cost' => 12]);
            $db->prepare('UPDATE users SET password=? WHERE email=?')->execute([$hash, $_SESSION['reset_email']]);
            unset($_SESSION['reset_email'], $_SESSION['reset_otp']);
            flash('success', 'Password updated! Please sign in.');
            redirect(BASE_URL . '/login.php');
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en" data-theme="dark">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title><?= $pageTitle ?></title>
<link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
<link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><text y='.9em' font-size='90'>🏨</text></svg>">
<style>
.fp-page{min-height:100vh;display:flex;align-items:center;justify-content:center;padding:2rem 1rem;background:var(--bg)}
.fp-box{width:100%;max-width:420px}
.fp-card{background:var(--glass);backdrop-filter:blur(24px);border:1px solid rgba(255,255,255,.1);border-radius:18px;padding:2rem}
[data-theme="light"] .fp-card{background:var(--bg2);border-color:var(--border)}
.step-dots{display:flex;gap:.5rem;justify-content:center;margin-bottom:1.5rem}
.dot{width:8px;height:8px;border-radius:50%;background:var(--border);transition:all .3s}
.dot.active{width:24px;border-radius:4px;background:var(--gold)}
</style>
</head>
<body>
<div style="position:fixed;top:1rem;right:1rem;z-index:10">
  <button class="theme-toggle" id="theme-toggle">☀️</button>
</div>

<div class="fp-page">
  <div class="fp-box">
    <div style="text-align:center;margin-bottom:2rem">
      <a href="login.php" style="font-size:.875rem;color:var(--text3);text-decoration:none">← Back to Sign In</a>
      <div style="width:48px;height:48px;background:linear-gradient(135deg,#d4a017,#f5c842);border-radius:14px;display:flex;align-items:center;justify-content:center;font-size:1.3rem;margin:.875rem auto .625rem">🔑</div>
      <h1 style="font-family:'Playfair Display',serif;font-size:1.5rem;font-weight:800;color:var(--text)">Reset Password</h1>
      <p style="color:var(--text3);font-size:.875rem;margin-top:.25rem">
        <?= ['','Enter your email','Enter OTP code','Set new password'][$step] ?>
      </p>
    </div>

    <!-- Step dots -->
    <div class="step-dots">
      <div class="dot <?= $step >= 1 ? 'active' : '' ?>"></div>
      <div class="dot <?= $step >= 2 ? 'active' : '' ?>"></div>
      <div class="dot <?= $step >= 3 ? 'active' : '' ?>"></div>
    </div>

    <div class="fp-card">
      <?php if ($err): ?><div class="alert alert-error"><?= $err ?></div><?php endif; ?>
      <?php if ($msg):  ?><div class="alert alert-success"><?= $msg ?></div><?php endif; ?>

      <form method="POST">
        <input type="hidden" name="step" value="<?= $step ?>">

        <?php if ($step === 1): ?>
          <div class="form-group">
            <label class="form-label">Registered Email</label>
            <div class="input-wrapper">
              <span class="input-icon">✉️</span>
              <input type="email" name="email" class="form-input" placeholder="your@email.com" required autofocus>
            </div>
          </div>
          <button type="submit" class="btn-full btn-primary">Send OTP →</button>

        <?php elseif ($step === 2): ?>
          <div class="form-group">
            <label class="form-label">Enter OTP</label>
            <input type="text" name="otp" class="form-input" placeholder="6-digit code" maxlength="6" required autofocus style="text-align:center;font-size:1.25rem;letter-spacing:.5rem">
            <p style="font-size:.75rem;color:var(--text3);margin-top:.5rem;text-align:center">Demo OTP: <strong style="color:var(--gold)">123456</strong></p>
          </div>
          <button type="submit" class="btn-full btn-primary">Verify OTP →</button>

        <?php elseif ($step === 3): ?>
          <div class="form-group">
            <label class="form-label">New Password</label>
            <div class="input-wrapper">
              <span class="input-icon">🔒</span>
              <input type="password" name="password" id="np" class="form-input" placeholder="New password" required>
              <button type="button" class="input-icon-right" onclick="tp('np')">👁️</button>
            </div>
          </div>
          <div class="form-group">
            <label class="form-label">Confirm Password</label>
            <div class="input-wrapper">
              <span class="input-icon">🔒</span>
              <input type="password" name="confirm" id="cp" class="form-input" placeholder="Repeat password" required>
              <button type="button" class="input-icon-right" onclick="tp('cp')">👁️</button>
            </div>
          </div>
          <button type="submit" class="btn-full btn-primary">Reset Password ✓</button>
        <?php endif; ?>
      </form>
    </div>
  </div>
</div>

<script>
const tb=document.getElementById('theme-toggle');
const sv=localStorage.getItem('theme')||'dark';
document.documentElement.setAttribute('data-theme',sv);
if(tb){tb.textContent=sv==='dark'?'☀️':'🌙';tb.onclick=()=>{const c=document.documentElement.getAttribute('data-theme'),n=c==='dark'?'light':'dark';document.documentElement.setAttribute('data-theme',n);localStorage.setItem('theme',n);tb.textContent=n==='dark'?'☀️':'🌙';};}
function tp(id){const el=document.getElementById(id);el.type=el.type==='password'?'text':'password';}
</script>
</body>
</html>
