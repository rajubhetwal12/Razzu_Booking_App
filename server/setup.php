<?php
// setup.php — Run this ONCE to create admin & manager accounts
// Visit: http://localhost/luxstay/setup.php then DELETE this file!

require_once 'config/db.php';

$db = getDB();

// Check if already set up
$check = $db->query("SELECT COUNT(*) FROM users WHERE role IN ('admin','manager')")->fetchColumn();
if ($check > 0) {
    die('<p style="font-family:sans-serif;color:green;padding:2rem;">✅ Already set up! Admin and Manager accounts exist. <a href="login.php">Go to Login</a></p>');
}

// Hash passwords
$adminHash   = password_hash('Admin@LuxStay2024',   PASSWORD_BCRYPT, ['cost' => 12]);
$managerHash = password_hash('Manager@LuxStay2024', PASSWORD_BCRYPT, ['cost' => 12]);

// Insert admin
$db->prepare("UPDATE users SET password = ? WHERE email = 'admin@luxstay.com'")->execute([$adminHash]);
$db->prepare("UPDATE users SET password = ? WHERE email = 'manager@luxstay.com'")->execute([$managerHash]);

// If rows didn't exist, insert them
$adminCheck = $db->query("SELECT id FROM users WHERE email='admin@luxstay.com'")->fetch();
if (!$adminCheck) {
    $db->prepare("INSERT INTO users (name,email,password,role,is_active) VALUES (?,?,?,?,1)")
       ->execute(['LuxStay Admin','admin@luxstay.com',$adminHash,'admin']);
}
$managerCheck = $db->query("SELECT id FROM users WHERE email='manager@luxstay.com'")->fetch();
if (!$managerCheck) {
    $db->prepare("INSERT INTO users (name,email,password,role,is_active) VALUES (?,?,?,?,1)")
       ->execute(['Hotel Manager','manager@luxstay.com',$managerHash,'manager']);
}

echo '
<!DOCTYPE html><html>
<head><title>LuxStay Setup</title>
<style>body{font-family:sans-serif;background:#0a0a18;color:#fff;display:flex;align-items:center;justify-content:center;min-height:100vh;margin:0}
.box{background:#1a1a2e;border:1px solid #d4a01744;border-radius:16px;padding:2rem;max-width:400px;text-align:center}
h2{color:#d4a017}table{width:100%;margin:1rem 0;border-collapse:collapse}
td,th{padding:0.5rem;text-align:left;border-bottom:1px solid #ffffff22;font-size:.875rem}
th{color:#d4a017}code{background:#ffffff11;padding:2px 8px;border-radius:4px}
a{color:#d4a017;font-weight:700;display:inline-block;margin-top:1rem}</style>
</head><body>
<div class="box">
  <h2>✅ LuxStay Setup Complete!</h2>
  <p>Accounts created successfully:</p>
  <table>
    <tr><th>Role</th><th>Email</th><th>Password</th></tr>
    <tr><td>Admin</td><td>admin@luxstay.com</td><td><code>Admin@LuxStay2024</code></td></tr>
    <tr><td>Manager</td><td>manager@luxstay.com</td><td><code>Manager@LuxStay2024</code></td></tr>
    <tr><td>Customer</td><td colspan="2">Register with any password</td></tr>
  </table>
  <p style="color:#ff6b6b;font-size:.8rem">⚠️ Delete setup.php after this!</p>
  <a href="login.php">→ Go to Login</a>
</div>
</body></html>';
