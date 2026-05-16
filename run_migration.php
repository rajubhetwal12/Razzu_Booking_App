<?php
require_once __DIR__.'/config/db.php';
$db = getDB();
$results = [];

/* ── 1. SQL Migrations ── */
$migrations = [
    'hotel_settings_migration.sql' => 'Hotel Policies, Highlights & Type Packages',
    'hotel_types_migration.sql'    => 'Hotel Type Facilities Mapping',
];
foreach ($migrations as $file => $label) {
    if (!file_exists(__DIR__.'/'.$file)) {
        $results[] = ['label'=>$label,'status'=>'skipped','msg'=>"File $file not found"]; continue;
    }
    try {
        $sql = file_get_contents(__DIR__.'/'.$file);
        $statements = array_filter(array_map('trim', explode(';', $sql)));
        $count = 0;
        foreach ($statements as $stmt) {
            if ($stmt && stripos(ltrim($stmt),'--')!==0) {
                try { $db->exec($stmt); $count++; } catch(PDOException $e) { /* skip already-exists */ }
            }
        }
        $results[] = ['label'=>$label,'status'=>'ok','msg'=>"Ran $count statements"];
    } catch(Exception $e) {
        $results[] = ['label'=>$label,'status'=>'error','msg'=>$e->getMessage()];
    }
}

/* ── 2. Seed / fix admin & manager accounts ── */
$accounts = [
    ['LuxStay Admin',   'admin@luxstay.com',   'Admin@LuxStay2024',   'admin'],
    ['Hotel Manager',   'manager@luxstay.com', 'Manager@LuxStay2024', 'manager'],
    ['Demo Customer',   'customer@luxstay.com','Customer@123',        'customer'],
];
$seeded = [];
foreach ($accounts as [$name,$email,$pass,$role]) {
    $hash = password_hash($pass, PASSWORD_BCRYPT, ['cost'=>12]);
    $exist = $db->prepare('SELECT id FROM users WHERE email=?'); $exist->execute([$email]); $exist=$exist->fetch();
    if ($exist) {
        $db->prepare('UPDATE users SET password=?,role=?,is_active=1 WHERE email=?')->execute([$hash,$role,$email]);
        $seeded[] = "Updated: $name ($email) → role=$role";
    } else {
        $db->prepare('INSERT INTO users(name,email,password,role,is_active)VALUES(?,?,?,?,1)')->execute([$name,$email,$hash,$role]);
        $seeded[] = "Created: $name ($email) → role=$role";
    }
}
$results[] = ['label'=>'Demo Accounts','status'=>'ok','msg'=>implode(' | ',$seeded)];

/* ── 3. Promote Raju Bhetwal (or any registered user) to manager ── */
$promoted = [];
$rajuUsers = $db->query("SELECT id,name,email,role FROM users WHERE name LIKE '%Raju%' OR name LIKE '%raju%'")->fetchAll();
foreach ($rajuUsers as $ru) {
    if ($ru['role']==='customer') {
        $db->prepare('UPDATE users SET role=? WHERE id=?')->execute(['manager',$ru['id']]);
        $promoted[] = $ru['name'].' ('.$ru['email'].') promoted to manager';
    }
}
if ($promoted) $results[] = ['label'=>'User Promotion','status'=>'ok','msg'=>implode(', ',$promoted)];

/* ── 4. Ensure hotels table has needed columns ── */
try {
    $cols = $db->query("SHOW COLUMNS FROM hotels")->fetchAll(PDO::FETCH_COLUMN);
    if (!in_array('check_in_time',$cols))
        $db->exec("ALTER TABLE hotels ADD COLUMN check_in_time VARCHAR(10) DEFAULT '14:00' AFTER longitude");
    if (!in_array('check_out_time',$cols))
        $db->exec("ALTER TABLE hotels ADD COLUMN check_out_time VARCHAR(10) DEFAULT '11:00' AFTER check_in_time");
    $results[] = ['label'=>'Schema Check','status'=>'ok','msg'=>'Hotels table columns verified'];
} catch(Exception $e) {
    $results[] = ['label'=>'Schema Check','status'=>'error','msg'=>$e->getMessage()];
}
?><!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><title>LuxStay Setup & Migration</title>
<style>
*{box-sizing:border-box;margin:0;padding:0}
body{font-family:system-ui,sans-serif;background:#0d0d1a;color:#e2e8f0;padding:2rem;min-height:100vh}
.card{background:#1a1a2e;border:1px solid #2d2d44;border-radius:16px;padding:2rem;max-width:700px;margin:0 auto}
h1{font-size:1.5rem;margin-bottom:.5rem;color:#d4a017}
.sub{color:#94a3b8;font-size:.875rem;margin-bottom:1.5rem}
.row{padding:.875rem 1rem;border-radius:10px;margin-bottom:.625rem;display:flex;align-items:flex-start;gap:1rem}
.ok{background:#052e16;border:1px solid #15803d}
.error{background:#2d0000;border:1px solid #991b1b}
.skipped{background:#1c1c2e;border:1px solid #4b4b6b}
.badge{padding:.25rem .75rem;border-radius:20px;font-size:.72rem;font-weight:700;white-space:nowrap}
.badge-ok{background:#15803d}.badge-err{background:#b91c1c}.badge-sk{background:#4b5563}
.msg{font-size:.8rem;color:#94a3b8;margin-top:.25rem}
.creds{background:#0f172a;border:1px solid #d4a01744;border-radius:12px;padding:1.25rem;margin:1.5rem 0}
.creds h3{color:#d4a017;margin-bottom:.75rem;font-size:1rem}
table{width:100%;border-collapse:collapse;font-size:.85rem}
th{text-align:left;color:#d4a017;padding:.375rem .5rem;border-bottom:1px solid #2d2d44}
td{padding:.375rem .5rem;border-bottom:1px solid #1e1e30}
code{background:#ffffff11;padding:2px 6px;border-radius:4px;font-family:monospace}
.actions{display:flex;gap:.75rem;flex-wrap:wrap;margin-top:1.5rem}
.btn{padding:.625rem 1.25rem;border-radius:8px;text-decoration:none;font-weight:700;font-size:.875rem;display:inline-block}
.btn-gold{background:#d4a017;color:#000}
.btn-outline{border:1px solid #d4a017;color:#d4a017}
</style></head><body>
<div class="card">
  <h1>🔄 LuxStay Setup & Migration</h1>
  <p class="sub">Runs database migrations and seeds demo accounts</p>

  <?php foreach($results as $r): ?>
  <div class="row <?=$r['status']==='ok'?'ok':($r['status']==='error'?'error':'skipped')?>">
    <span class="badge <?=$r['status']==='ok'?'badge-ok':($r['status']==='error'?'badge-err':'badge-sk')?>"><?=strtoupper($r['status'])?></span>
    <div><strong><?=htmlspecialchars($r['label'])?></strong><div class="msg"><?=htmlspecialchars($r['msg'])?></div></div>
  </div>
  <?php endforeach; ?>

  <div class="creds">
    <h3>🔑 Login Credentials</h3>
    <table>
      <tr><th>Role</th><th>Email</th><th>Password</th></tr>
      <tr><td>Admin</td><td>admin@luxstay.com</td><td><code>Admin@LuxStay2024</code></td></tr>
      <tr><td>Manager</td><td>manager@luxstay.com</td><td><code>Manager@LuxStay2024</code></td></tr>
      <tr><td>Customer</td><td>customer@luxstay.com</td><td><code>Customer@123</code></td></tr>
    </table>
    <p style="font-size:.78rem;color:#94a3b8;margin-top:.75rem">ℹ️ Any user named "Raju" registered as customer has been auto-promoted to manager.</p>
  </div>

  <div class="actions">
    <a href="<?=BASE_URL?>/login.php" class="btn btn-gold">→ Go to Login</a>
    <a href="<?=BASE_URL?>/dashboard/manager.php" class="btn btn-outline">Manager Dashboard</a>
    <a href="<?=BASE_URL?>/dashboard/admin.php" class="btn btn-outline">Admin Dashboard</a>
  </div>
</div>
</body></html>
