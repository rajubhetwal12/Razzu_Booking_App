<?php
$pageTitle='Reset Password — LuxStay';
require_once __DIR__.'/config/db.php';
require_once __DIR__.'/includes/auth.php';
if(isLoggedIn())redirect(BASE_URL.'/');
$step=(int)($_SESSION['fp_step']??1);$err='';$ok='';
if($_SERVER['REQUEST_METHOD']==='POST'){
  $db=getDB();$act=$_POST['act']??'';
  if($act==='email'){
    $em=trim($_POST['email']??'');
    $u=$db->prepare('SELECT id,name FROM users WHERE email=? AND is_active=1 LIMIT 1');$u->execute([$em]);$u=$u->fetch();
    if(!$u)$err='No account found with that email.';
    else{$otp=rand(100000,999999);$_SESSION['fp_email']=$em;$_SESSION['fp_uid']=$u['id'];$_SESSION['fp_otp']=(string)$otp;$_SESSION['fp_exp']=time()+600;$_SESSION['fp_step']=2;$step=2;$ok='OTP sent! Demo OTP: <strong style="color:var(--gold)">'.$otp.'</strong>';}
  }elseif($act==='otp'){
    $entered=trim($_POST['otp']??'');
    if(!isset($_SESSION['fp_otp'])||time()>($_SESSION['fp_exp']??0)){$err='OTP expired. Try again.';foreach(['fp_step','fp_otp','fp_email','fp_uid','fp_exp'] as $k)unset($_SESSION[$k]);$step=1;}
    elseif($entered!==$_SESSION['fp_otp'])$err='Wrong OTP.';
    else{$_SESSION['fp_step']=3;$step=3;$ok='OTP verified! Set your new password.';}
  }elseif($act==='reset'){
    $np=$_POST['password']??'';$cn=$_POST['confirm']??'';
    if(!isset($_SESSION['fp_uid']))redirect(BASE_URL.'/forgot-password.php');
    elseif(strlen($np)<6)$err='Password must be at least 6 characters.';
    elseif(!preg_match('/[A-Z]/',$np))$err='Needs uppercase letter.';
    elseif(!preg_match('/[0-9]/',$np))$err='Needs a number.';
    elseif($np!==$cn)$err='Passwords do not match.';
    else{$hash=password_hash($np,PASSWORD_BCRYPT,['cost'=>12]);$db->prepare('UPDATE users SET password=? WHERE id=?')->execute([$hash,$_SESSION['fp_uid']]);foreach(['fp_step','fp_otp','fp_email','fp_uid','fp_exp'] as $k)unset($_SESSION[$k]);flash('success','✅ Password reset! Please sign in with your new password.');redirect(BASE_URL.'/login.php');}
  }
}
?><!DOCTYPE html>
<html lang="en" data-theme="dark">
<head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title><?=e($pageTitle)?></title>
<link rel="stylesheet" href="<?=BASE_URL?>/assets/css/style.css">
<link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><text y='.9em' font-size='90'>🏨</text></svg>">
<style>
body{min-height:100vh;display:flex;align-items:center;justify-content:center;padding:2rem 1rem;background:var(--bg)}
.auth-box{width:100%;max-width:420px}
.auth-card{background:var(--glass);backdrop-filter:blur(24px);border:1px solid rgba(255,255,255,.1);border-radius:20px;padding:2.25rem;box-shadow:0 24px 80px rgba(0,0,0,.5)}
[data-theme=light] .auth-card{background:var(--bg2);border-color:var(--border)}
.steps{display:flex;align-items:center;justify-content:center;gap:.375rem;margin-bottom:2rem}
.step-dot{width:32px;height:32px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:.78rem;font-weight:700;border:2px solid var(--border);color:var(--text3)}
.step-dot.done{background:var(--gold);color:#08081a;border-color:var(--gold)}
.step-dot.curr{border-color:var(--gold);color:var(--gold)}
.step-line{flex:1;height:2px;max-width:40px;background:var(--border)}.step-line.done{background:var(--gold)}
.pos-tr{position:fixed;top:1rem;right:1rem;z-index:999}
</style></head><body>
<div class="pos-tr"><button class="theme-btn" id="theme-btn">☀️</button></div>
<div class="auth-box">
  <div class="tc mb3">
    <a href="<?=BASE_URL?>/login.php" style="font-size:.8rem;color:var(--text3)">← Back to Login</a>
    <div style="width:52px;height:52px;background:linear-gradient(135deg,var(--gold),var(--gold2));border-radius:16px;display:flex;align-items:center;justify-content:center;font-size:1.4rem;margin:.875rem auto .75rem">🔑</div>
    <h1 style="font-family:'Playfair Display',serif;font-size:1.5rem;font-weight:800">Reset Password</h1>
    <p class="muted" style="font-size:.875rem;margin-top:.25rem">Step <?=$step?> of 3</p>
  </div>
  <div class="steps">
    <div class="step-dot <?=$step>=1?'done':''?>">1</div>
    <div class="step-line <?=$step>=2?'done':''?>"></div>
    <div class="step-dot <?=$step>2?'done':($step==2?'curr':'')?>">2</div>
    <div class="step-line <?=$step>=3?'done':''?>"></div>
    <div class="step-dot <?=$step==3?'curr':''?>">3</div>
  </div>
  <div class="auth-card">
    <?php if($err):?><div class="alert alert-error"><?=$err?></div><?php endif;?>
    <?php if($ok):?><div class="alert alert-success"><?=$ok?></div><?php endif;?>
    <?php if($step===1):?>
      <h2 style="font-weight:700;margin-bottom:.375rem">Enter Your Email</h2>
      <p class="muted" style="font-size:.8rem;margin-bottom:1.25rem">We'll generate an OTP for you.</p>
      <form method="POST"><input type="hidden" name="act" value="email">
        <div class="form-group"><label class="form-label">Email Address</label>
          <div class="input-wrap"><span class="input-icon">✉️</span>
            <input type="email" name="email" class="form-input" placeholder="your@email.com" required autofocus>
          </div>
        </div>
        <button type="submit" class="btn btn-primary btn-full">Send OTP →</button>
      </form>
    <?php elseif($step===2):?>
      <h2 style="font-weight:700;margin-bottom:.375rem">Enter OTP</h2>
      <p class="muted" style="font-size:.8rem;margin-bottom:1.25rem">Sent to <strong style="color:var(--gold)"><?=e($_SESSION['fp_email']??'')?></strong>. Expires in 10 min.</p>
      <form method="POST"><input type="hidden" name="act" value="otp">
        <div class="form-group"><label class="form-label">6-Digit OTP</label>
          <input type="text" name="otp" class="form-input" maxlength="6" inputmode="numeric" required autofocus style="font-size:1.5rem;letter-spacing:.35em;text-align:center">
        </div>
        <button type="submit" class="btn btn-primary btn-full">Verify OTP →</button>
      </form>
      <form method="POST" style="margin-top:.625rem"><input type="hidden" name="act" value="email"><input type="hidden" name="email" value="<?=e($_SESSION['fp_email']??'')?>"><button type="submit" style="width:100%;background:none;border:none;color:var(--text3);cursor:pointer;font-size:.8rem;font-family:Inter,sans-serif">Resend OTP</button></form>
    <?php elseif($step===3):?>
      <h2 style="font-weight:700;margin-bottom:.375rem">New Password</h2>
      <p class="muted" style="font-size:.8rem;margin-bottom:1.25rem">Choose a strong password.</p>
      <form method="POST"><input type="hidden" name="act" value="reset">
        <div class="form-group"><label class="form-label">New Password</label>
          <div class="input-wrap"><span class="input-icon">🔒</span>
            <input type="password" name="password" id="np" class="form-input" placeholder="Uppercase + number" required>
            <button type="button" class="input-eye" data-eye="np">👁️</button>
          </div>
        </div>
        <div class="form-group"><label class="form-label">Confirm Password</label>
          <div class="input-wrap"><span class="input-icon">🔒</span>
            <input type="password" name="confirm" id="cp" class="form-input" placeholder="Repeat password" required>
            <button type="button" class="input-eye" data-eye="cp">👁️</button>
          </div>
        </div>
        <button type="submit" class="btn btn-primary btn-full">Reset Password →</button>
      </form>
    <?php endif;?>
  </div>
</div>
<script src="<?=BASE_URL?>/assets/js/main.js"></script>
</body></html>
