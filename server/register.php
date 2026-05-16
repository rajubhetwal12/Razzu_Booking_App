<?php
$pageTitle = 'Register — LuxStay';
require_once 'config/db.php';
require_once 'includes/auth.php';
if (isLoggedIn()) redirect(BASE_URL . '/');

$error = $success = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name     = trim($_POST['name'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm  = $_POST['confirm'] ?? '';
    $phone    = trim($_POST['phone'] ?? '');

    if (!$name || !$email || !$password) { $error = 'All fields are required.'; }
    elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) { $error = 'Invalid email address.'; }
    elseif (strlen($password) < 6) { $error = 'Password must be at least 6 characters.'; }
    elseif (!preg_match('/[A-Z]/', $password)) { $error = 'Password must contain at least one uppercase letter.'; }
    elseif (!preg_match('/[a-z]/', $password)) { $error = 'Password must contain at least one lowercase letter.'; }
    elseif (!preg_match('/[0-9]/', $password)) { $error = 'Password must contain at least one number.'; }
    elseif ($password !== $confirm) { $error = 'Passwords do not match.'; }
    else {
        $db = getDB();
        $existing = $db->prepare('SELECT id FROM users WHERE email = ?');
        $existing->execute([$email]);
        if ($existing->fetch()) { $error = 'Email already registered.'; }
        else {
            $hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
            $stmt = $db->prepare('INSERT INTO users (name,email,password,phone,role) VALUES (?,?,?,?,"customer")');
            $stmt->execute([$name, $email, $hash, $phone]);
            $userId = $db->lastInsertId();
            $user = ['id'=>$userId,'name'=>$name,'email'=>$email,'role'=>'customer','avatar'=>null];
            loginUser($user);
            redirect(BASE_URL . '/dashboard/customer.php');
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
.reg-page{min-height:100vh;display:flex;align-items:center;justify-content:center;padding:5rem 1rem 2rem;background:var(--bg)}
.reg-page::before{content:'';position:fixed;top:30%;right:0;width:400px;height:400px;background:radial-gradient(ellipse,rgba(212,160,23,0.06),transparent 70%);pointer-events:none}
.reg-box{width:100%;max-width:480px}
.reg-card{background:var(--glass);backdrop-filter:blur(24px);border:1px solid rgba(255,255,255,.1);border-radius:18px;padding:2.25rem}
[data-theme="light"] .reg-card{background:var(--bg2);border-color:var(--border)}
.pass-rules{margin-top:.5rem;display:flex;flex-wrap:wrap;gap:.375rem}
.rule{font-size:.7rem;padding:.15rem .5rem;border-radius:999px;background:var(--card);color:var(--text3);border:1px solid var(--border);transition:all .2s}
.rule.ok{background:rgba(34,197,94,.1);color:#4ade80;border-color:rgba(34,197,94,.3)}
</style>
</head>
<body>
<div style="position:fixed;top:1rem;right:1rem;z-index:10">
  <button class="theme-toggle" id="theme-toggle">☀️</button>
</div>

<div class="reg-page">
  <div class="reg-box">
    <div style="text-align:center;margin-bottom:2rem">
      <div style="width:48px;height:48px;background:linear-gradient(135deg,#d4a017,#f5c842);border-radius:14px;display:flex;align-items:center;justify-content:center;font-size:1.3rem;margin:0 auto .75rem">🏨</div>
      <h1 style="font-family:'Playfair Display',serif;font-size:1.5rem;font-weight:800">Create Account</h1>
      <p style="color:var(--text3);font-size:.875rem;margin-top:.25rem">Join 50,000+ luxury travelers</p>
    </div>

    <div class="reg-card">
      <?php if ($error): ?><div class="alert alert-error"><?= e($error) ?></div><?php endif; ?>

      <form method="POST" id="reg-form" autocomplete="on">
        <div class="grid-2">
          <div class="form-group">
            <label class="form-label">Full Name</label>
            <div class="input-wrapper">
              <span class="input-icon">👤</span>
              <input type="text" name="name" class="form-input" placeholder="John Doe"
                value="<?= e($_POST['name']??'') ?>" required>
            </div>
          </div>
          <div class="form-group">
            <label class="form-label">Phone</label>
            <div class="input-wrapper">
              <span class="input-icon">📞</span>
              <input type="tel" name="phone" class="form-input" placeholder="+977-98XXXXXXXX"
                value="<?= e($_POST['phone']??'') ?>">
            </div>
          </div>
        </div>

        <div class="form-group">
          <label class="form-label">Email Address</label>
          <div class="input-wrapper">
            <span class="input-icon">✉️</span>
            <input type="email" name="email" class="form-input" placeholder="your@email.com"
              value="<?= e($_POST['email']??'') ?>" required>
          </div>
        </div>

        <div class="form-group">
          <label class="form-label">Password</label>
          <div class="input-wrapper">
            <span class="input-icon">🔒</span>
            <input type="password" name="password" id="inp-pass" class="form-input"
              placeholder="Min 6 chars, uppercase, number" required oninput="checkPass(this.value)">
            <button type="button" class="input-icon-right" onclick="tp('inp-pass')">👁️</button>
          </div>
          <div class="pass-rules">
            <span class="rule" id="r-len">6+ chars</span>
            <span class="rule" id="r-up">Uppercase</span>
            <span class="rule" id="r-low">Lowercase</span>
            <span class="rule" id="r-num">Number</span>
          </div>
        </div>

        <div class="form-group">
          <label class="form-label">Confirm Password</label>
          <div class="input-wrapper">
            <span class="input-icon">🔒</span>
            <input type="password" name="confirm" id="inp-confirm" class="form-input"
              placeholder="Repeat password" required>
            <button type="button" class="input-icon-right" onclick="tp('inp-confirm')">👁️</button>
          </div>
        </div>

        <button type="submit" class="btn-full btn-primary" style="margin-top:.5rem">Create Account →</button>
      </form>
    </div>

    <p style="text-align:center;margin-top:1.25rem;font-size:.875rem;color:var(--text3)">
      Already have an account? <a href="<?= BASE_URL ?>/login.php" class="link-gold">Sign in</a>
    </p>
  </div>
</div>

<script>
const tb=document.getElementById('theme-toggle');
const sv=localStorage.getItem('theme')||'dark';
document.documentElement.setAttribute('data-theme',sv);
if(tb){tb.textContent=sv==='dark'?'☀️':'🌙';tb.onclick=()=>{const c=document.documentElement.getAttribute('data-theme'),n=c==='dark'?'light':'dark';document.documentElement.setAttribute('data-theme',n);localStorage.setItem('theme',n);tb.textContent=n==='dark'?'☀️':'🌙';};}

function tp(id){const el=document.getElementById(id);el.type=el.type==='password'?'text':'password';}
function checkPass(v){
  document.getElementById('r-len').className='rule'+(v.length>=6?' ok':'');
  document.getElementById('r-up').className='rule'+(/[A-Z]/.test(v)?' ok':'');
  document.getElementById('r-low').className='rule'+(/[a-z]/.test(v)?' ok':'');
  document.getElementById('r-num').className='rule'+(/[0-9]/.test(v)?' ok':'');
}
</script>
</body>
</html>
