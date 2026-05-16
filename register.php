<?php
require_once __DIR__.'/config/db.php';
require_once __DIR__.'/includes/auth.php';
if(isLoggedIn())redirect(BASE_URL.'/');
$err='';
if($_SERVER['REQUEST_METHOD']==='POST'){
  $name=trim($_POST['name']??'');$email=trim($_POST['email']??'');
  $phone=trim($_POST['phone']??'');$pass=$_POST['password']??'';$conf=$_POST['confirm']??'';
  if(!$name||!$email||!$pass)          $err='Name, email and password are required.';
  elseif(!filter_var($email,FILTER_VALIDATE_EMAIL)) $err='Enter a valid email address.';
  elseif(strlen($pass)<6)              $err='Password must be at least 6 characters.';
  elseif(!preg_match('/[A-Z]/',$pass)) $err='Password needs at least one uppercase letter.';
  elseif(!preg_match('/[0-9]/',$pass)) $err='Password needs at least one number.';
  elseif($pass!==$conf)                $err='Passwords do not match.';
  else{
    $db=getDB();
    $st=$db->prepare('SELECT id FROM users WHERE email=?');$st->execute([$email]);
    if($st->fetch()) $err='An account with this email already exists.';
    else{
      $hash=password_hash($pass,PASSWORD_BCRYPT,['cost'=>12]);
      $db->prepare('INSERT INTO users(name,email,password,phone,role,is_active)VALUES(?,?,?,?,"customer",1)')->execute([$name,$email,$hash,$phone]);
      $uid=(int)$db->lastInsertId();
      loginUser(['id'=>$uid,'name'=>$name,'email'=>$email,'role'=>'customer']);
      $db->prepare('INSERT INTO notifications(user_id,title,message,type)VALUES(?,?,?,"system")')->execute([$uid,'🎉 Welcome to LuxStay!','Your account is ready. Start exploring luxury hotels across Nepal.']);
      flash('success','Account created! Welcome, '.$name.'!');
      redirect(BASE_URL.'/dashboard/customer.php');
    }
  }
}
$pageTitle='Create Account — LuxStay';
?><!DOCTYPE html>
<html lang="en" data-theme="dark">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title><?=e($pageTitle)?></title>
<link rel="stylesheet" href="<?=BASE_URL?>/assets/css/style.css">
<link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><text y='.9em' font-size='90'>🏨</text></svg>">
<style>
body{min-height:100vh;display:flex;align-items:center;justify-content:center;padding:2rem 1rem;background:var(--bg)}
body::before{content:'';position:fixed;top:40%;left:50%;transform:translate(-50%,-50%);width:600px;height:600px;border-radius:50%;background:radial-gradient(circle,rgba(212,160,23,.05),transparent 70%);pointer-events:none}
.auth-box{width:100%;max-width:500px;position:relative;z-index:1}
.auth-card{background:var(--glass);backdrop-filter:blur(24px);-webkit-backdrop-filter:blur(24px);border:1px solid rgba(255,255,255,.1);border-radius:20px;padding:2.25rem;box-shadow:0 24px 80px rgba(0,0,0,.55)}
[data-theme=light] .auth-card{background:var(--bg2);border-color:var(--border);box-shadow:var(--shadow)}
.rule{display:inline-block;font-size:.68rem;padding:.15rem .5rem;border-radius:999px;background:var(--card);color:var(--text3);border:1px solid var(--border);transition:all .25s;margin:.2rem .12rem}
.rule.ok{background:rgba(34,197,94,.1);color:#4ade80;border-color:rgba(34,197,94,.3)}
.pos-tr{position:fixed;top:1rem;right:1rem;z-index:999}
</style>
</head>
<body>
<div class="pos-tr"><button class="theme-btn" id="theme-btn">☀️</button></div>
<div class="auth-box">
  <div class="tc mb3">
    <a href="<?=BASE_URL?>/" style="font-size:.8rem;color:var(--text3)">← Back to Home</a>
    <div style="width:54px;height:54px;background:linear-gradient(135deg,var(--gold),var(--gold2));border-radius:16px;display:flex;align-items:center;justify-content:center;font-size:1.5rem;margin:.875rem auto .75rem;box-shadow:0 8px 32px rgba(212,160,23,.4)">🏨</div>
    <h1 style="font-family:'Playfair Display',serif;font-size:1.5rem;font-weight:800">Create Account</h1>
    <p style="color:var(--text3);font-size:.875rem;margin-top:.25rem">Join 50,000+ luxury travelers</p>
  </div>
  <div class="auth-card">
    <?php if($err):?><div class="alert alert-error"><?=e($err)?></div><?php endif;?>
    <form method="POST" novalidate>
      <div class="form-grid-2">
        <div class="form-group">
          <label class="form-label">Full Name *</label>
          <div class="input-wrap"><span class="input-icon">👤</span>
            <input type="text" name="name" class="form-input" placeholder="John Doe" value="<?=e($_POST['name']??'')?>" required>
          </div>
        </div>
        <div class="form-group">
          <label class="form-label">Phone</label>
          <div class="input-wrap"><span class="input-icon">📞</span>
            <input type="tel" name="phone" class="form-input" placeholder="+977-98XXXXXXXX" value="<?=e($_POST['phone']??'')?>">
          </div>
        </div>
      </div>
      <div class="form-group">
        <label class="form-label">Email Address *</label>
        <div class="input-wrap"><span class="input-icon">✉️</span>
          <input type="email" name="email" class="form-input" placeholder="your@email.com" value="<?=e($_POST['email']??'')?>" required>
        </div>
      </div>
      <div class="form-group">
        <label class="form-label">Password *</label>
        <div class="input-wrap"><span class="input-icon">🔒</span>
          <input type="password" name="password" id="np" class="form-input" placeholder="Min 6 chars + uppercase + number" required oninput="chkP(this.value)">
          <button type="button" class="input-eye" data-eye="np">👁️</button>
        </div>
        <div style="margin-top:.45rem">
          <span class="rule" id="r6">6+ chars</span>
          <span class="rule" id="rU">Uppercase</span>
          <span class="rule" id="rN">Number</span>
        </div>
      </div>
      <div class="form-group">
        <label class="form-label">Confirm Password *</label>
        <div class="input-wrap"><span class="input-icon">🔒</span>
          <input type="password" name="confirm" id="cp" class="form-input" placeholder="Repeat password" required>
          <button type="button" class="input-eye" data-eye="cp">👁️</button>
        </div>
      </div>
      <button type="submit" class="btn btn-primary btn-full mt2">Create Account →</button>
    </form>
  </div>
  <p class="tc mt3" style="font-size:.875rem;color:var(--text3)">Already have an account? <a href="<?=BASE_URL?>/login.php" style="color:var(--gold);font-weight:600">Sign in →</a></p>
</div>
<script src="<?=BASE_URL?>/assets/js/main.js"></script>
<script>
function chkP(v){
  document.getElementById('r6').className='rule'+(v.length>=6?' ok':'');
  document.getElementById('rU').className='rule'+(/[A-Z]/.test(v)?' ok':'');
  document.getElementById('rN').className='rule'+(/[0-9]/.test(v)?' ok':'');
}
</script>
</body></html>
