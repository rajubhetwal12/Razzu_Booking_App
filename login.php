<?php
require_once __DIR__.'/config/db.php';
require_once __DIR__.'/includes/auth.php';
if(isLoggedIn()){$r=currentUser()['role'];redirect(BASE_URL.'/dashboard/'.$r.'.php');}

$err='';
if($_SERVER['REQUEST_METHOD']==='POST'){
  $email=trim($_POST['email']??'');
  $pass=$_POST['password']??'';
  if(!$email||!$pass){$err='Please enter email and password.';}
  else{
    $db=getDB();
    $st=$db->prepare('SELECT id,name,email,password,role,is_active FROM users WHERE email=? LIMIT 1');
    $st->execute([$email]);$u=$st->fetch();
    if(!$u)                          $err='No account found with that email.';
    elseif(!$u['is_active'])         $err='Your account has been suspended.';
    elseif(!password_verify($pass,$u['password'])) $err='Incorrect password. Try again.';
    else{loginUser($u);flash('success','Welcome back, '.$u['name'].'! 👋');redirect(BASE_URL.'/dashboard/'.$u['role'].'.php');}
  }
}
$pageTitle='Sign In — LuxStay';
?><!DOCTYPE html>
<html lang="en" data-theme="dark">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title><?=e($pageTitle)?></title>
<link rel="stylesheet" href="<?=BASE_URL?>/assets/css/style.css">
<link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><text y='.9em' font-size='90'>🏨</text></svg>">
<style>
body{min-height:100vh;display:flex;align-items:center;justify-content:center;padding:2rem 1rem;background:var(--bg)}
body::before{content:'';position:fixed;top:40%;left:50%;transform:translate(-50%,-50%);
  width:600px;height:600px;border-radius:50%;
  background:radial-gradient(circle,rgba(212,160,23,.06),transparent 70%);pointer-events:none}
.auth-box{width:100%;max-width:420px;position:relative;z-index:1}
.auth-card{background:var(--glass);backdrop-filter:blur(24px);-webkit-backdrop-filter:blur(24px);
           border:1px solid rgba(255,255,255,.1);border-radius:20px;padding:2.25rem;
           box-shadow:0 24px 80px rgba(0,0,0,.55)}
[data-theme=light] .auth-card{background:var(--bg2);border-color:var(--border);box-shadow:var(--shadow)}
.auth-icon{width:56px;height:56px;background:linear-gradient(135deg,var(--gold),var(--gold2));
           border-radius:16px;display:flex;align-items:center;justify-content:center;
           font-size:1.5rem;margin:0 auto 1rem;box-shadow:0 8px 32px rgba(212,160,23,.4)}
.demo-grid{display:grid;grid-template-columns:1fr 1fr 1fr;gap:.5rem;margin-top:.875rem}
.demo-btn{padding:.625rem .375rem;border-radius:10px;font-size:.72rem;font-weight:700;
          cursor:pointer;border:1.5px solid;background:transparent;
          font-family:Inter,sans-serif;transition:all .2s;text-align:center}
.demo-btn:hover{transform:translateY(-1px)}
.d-admin{border-color:rgba(239,68,68,.4);color:#f87171}.d-admin:hover{background:rgba(239,68,68,.08)}
.d-mgr{border-color:rgba(59,130,246,.4);color:#60a5fa}.d-mgr:hover{background:rgba(59,130,246,.08)}
.d-cust{border-color:rgba(212,160,23,.4);color:var(--gold)}.d-cust:hover{background:rgba(212,160,23,.08)}
.hint{margin-top:.75rem;padding:.75rem 1rem;background:rgba(212,160,23,.06);
      border:1px solid rgba(212,160,23,.15);border-radius:10px;font-size:.8rem;display:none}
.pos-tr{position:fixed;top:1rem;right:1rem;z-index:999}
</style>
</head>
<body>
<div class="pos-tr"><button class="theme-btn" id="theme-btn">☀️</button></div>
<div class="auth-box">
  <div class="tc mb3">
    <a href="<?=BASE_URL?>/" style="font-size:.8rem;color:var(--text3)">← Back to Home</a>
    <div class="auth-icon mt2">🏨</div>
    <h1 style="font-family:'Playfair Display',serif;font-size:1.625rem;font-weight:800">Welcome Back</h1>
    <p style="color:var(--text3);font-size:.875rem;margin-top:.25rem">Sign in to your LuxStay account</p>
  </div>
  <div class="auth-card">
    <?php if($err):?><div class="alert alert-error"><?=e($err)?></div><?php endif;?>
    <form method="POST" novalidate>
      <div class="form-group">
        <label class="form-label">Email Address</label>
        <div class="input-wrap"><span class="input-icon">✉️</span>
          <input type="email" name="email" id="em" class="form-input" placeholder="your@email.com" value="<?=e($_POST['email']??'')?>" required autofocus>
        </div>
      </div>
      <div class="form-group">
        <label class="form-label">Password</label>
        <div class="input-wrap"><span class="input-icon">🔒</span>
          <input type="password" name="password" id="pw" class="form-input" placeholder="Your password" required>
          <button type="button" class="input-eye" data-eye="pw">👁️</button>
        </div>
      </div>
      <div style="text-align:right;margin-bottom:1.125rem">
        <a href="<?=BASE_URL?>/forgot-password.php" style="font-size:.8rem;color:var(--gold)">Forgot password?</a>
      </div>
      <button type="submit" class="btn btn-primary btn-full">Sign In →</button>
    </form>
    <div style="position:relative;text-align:center;margin:1.25rem 0">
      <hr class="divider" style="margin:0"><span style="position:absolute;top:-10px;left:50%;transform:translateX(-50%);background:var(--glass);padding:0 .875rem;font-size:.72rem;color:var(--text3)">Quick Demo Login</span>
    </div>
    <div class="demo-grid">
      <button class="demo-btn d-admin" onclick="demo('admin@luxstay.com','Admin@LuxStay2024','Admin')">👑 Admin</button>
      <button class="demo-btn d-mgr"   onclick="demo('manager@luxstay.com','Manager@LuxStay2024','Manager')">🏨 Manager</button>
      <button class="demo-btn d-cust"  onclick="demo('customer@luxstay.com','Customer@123','Customer')">⭐ Customer</button>
    </div>
    <div class="hint" id="hint">
      <div style="color:var(--text2)">📧 <span id="h-e" style="color:var(--gold)"></span></div>
      <div style="color:var(--text2);margin-top:.25rem">🔑 <span id="h-p"></span></div>
      <div id="h-note" style="color:var(--text3);font-size:.72rem;margin-top:.375rem"></div>
    </div>
  </div>
  <p class="tc mt3" style="font-size:.875rem;color:var(--text3)">No account? <a href="<?=BASE_URL?>/register.php" style="color:var(--gold);font-weight:600">Register free →</a></p>
</div>
<script src="<?=BASE_URL?>/assets/js/main.js"></script>
<script>
function demo(e,p,r){
  document.getElementById('em').value=e;
  document.getElementById('pw').value=p;
  document.getElementById('h-e').textContent=e;
  document.getElementById('h-p').textContent=p;
  const note=document.getElementById('h-note');
  note.textContent=r==='Customer'?'ℹ️ New customers: register first (uppercase + number required)':'';
  document.getElementById('hint').style.display='block';
}
</script>
</body></html>
