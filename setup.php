<?php
/**
 * setup.php — Run once to create database & demo accounts
 * DELETE this file after first run for security.
 */
require_once __DIR__.'/config/db.php';
$db = getDB();
$msg = [];
$err = [];

// Demo users: admin, manager, customer
$demoUsers = [
    ['Admin User',    'admin@luxstay.com',    'Admin@LuxStay2024',   'admin'],
    ['Hotel Manager', 'manager@luxstay.com',  'Manager@LuxStay2024', 'manager'],
    ['Demo Customer', 'customer@luxstay.com', 'Customer@123',        'customer'],
];

foreach ($demoUsers as [$name,$email,$pass,$role]) {
    try {
        $exists = $db->prepare('SELECT id FROM users WHERE email=?');
        $exists->execute([$email]);
        if (!$exists->fetch()) {
            $hash = password_hash($pass, PASSWORD_BCRYPT, ['cost'=>12]);
            $db->prepare('INSERT INTO users(name,email,password,role,is_active) VALUES(?,?,?,?,1)')
               ->execute([$name,$email,$hash,$role]);
            $msg[] = "✅ Created $role: $email / $pass";
        } else {
            // Update password to ensure it's correct
            $hash = password_hash($pass, PASSWORD_BCRYPT, ['cost'=>12]);
            $db->prepare('UPDATE users SET password=?,role=?,is_active=1 WHERE email=?')
               ->execute([$hash,$role,$email]);
            $msg[] = "🔄 Updated $role: $email / $pass";
        }
    } catch(Exception $e) { $err[] = "❌ User $email: ".$e->getMessage(); }
}

// Create missing optional tables
$extraTables = [
'hotel_policies' => "CREATE TABLE IF NOT EXISTS `hotel_policies` (
  `id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `hotel_id` INT NOT NULL UNIQUE,
  `check_in_time` VARCHAR(10) DEFAULT '14:00',
  `check_out_time` VARCHAR(10) DEFAULT '11:00',
  `cancellation_policy` TEXT DEFAULT NULL,
  `smoking_policy` VARCHAR(30) DEFAULT 'not_allowed',
  `pet_policy` VARCHAR(30) DEFAULT 'not_allowed',
  `child_policy` TEXT DEFAULT NULL,
  `extra_bed_policy` TEXT DEFAULT NULL,
  `payment_methods` VARCHAR(200) DEFAULT 'esewa,khalti,cash',
  `age_restriction` TINYINT DEFAULT 0,
  `important_info` TEXT DEFAULT NULL,
  FOREIGN KEY (`hotel_id`) REFERENCES `hotels`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
'hotel_highlights' => "CREATE TABLE IF NOT EXISTS `hotel_highlights` (
  `id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `hotel_id` INT NOT NULL,
  `icon` VARCHAR(10) DEFAULT '✨',
  `title` VARCHAR(150) NOT NULL,
  `detail` VARCHAR(300) DEFAULT NULL,
  `sort_order` INT DEFAULT 0,
  FOREIGN KEY (`hotel_id`) REFERENCES `hotels`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
'hotel_offered_types' => "CREATE TABLE IF NOT EXISTS `hotel_offered_types` (
  `hotel_id` INT NOT NULL,
  `type_key` VARCHAR(30) NOT NULL,
  PRIMARY KEY (`hotel_id`,`type_key`),
  FOREIGN KEY (`hotel_id`) REFERENCES `hotels`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
'hotel_type_packages' => "CREATE TABLE IF NOT EXISTS `hotel_type_packages` (
  `id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `type_key` VARCHAR(30) NOT NULL UNIQUE,
  `display_name` VARCHAR(80) NOT NULL,
  `icon` VARCHAR(10) DEFAULT '🏨',
  `badge_color` VARCHAR(20) DEFAULT '#d4a017',
  `tagline` VARCHAR(200) DEFAULT NULL,
  `price_label` VARCHAR(80) DEFAULT NULL,
  `sort_order` INT DEFAULT 0,
  `is_active` TINYINT(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
'hotel_type_facilities' => "CREATE TABLE IF NOT EXISTS `hotel_type_facilities` (
  `id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `type_key` VARCHAR(30) NOT NULL,
  `facility_id` INT NOT NULL,
  UNIQUE KEY `uk_tf` (`type_key`,`facility_id`),
  FOREIGN KEY (`facility_id`) REFERENCES `facilities`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
];

foreach ($extraTables as $name => $sql) {
    try { $db->exec($sql); $msg[] = "✅ Table `$name` ready"; }
    catch(Exception $e) { $err[] = "❌ Table $name: ".$e->getMessage(); }
}

// Fix owner_id for demo hotels (assign to manager)
try {
    $mgr = $db->prepare('SELECT id FROM users WHERE role=? LIMIT 1');
    $mgr->execute(['manager']); $mgr = $mgr->fetch();
    if ($mgr) {
        $db->prepare('UPDATE hotels SET owner_id=? WHERE owner_id IS NULL OR owner_id=2')
           ->execute([$mgr['id']]);
        $msg[] = "✅ Hotel owner_id set to manager (id={$mgr['id']})";
    }
} catch(Exception $e) { $err[] = "❌ Hotel owner: ".$e->getMessage(); }

// Create uploads dirs
$dirs = ['hotels','rooms','users'];
foreach ($dirs as $d) {
    $path = __DIR__.'/assets/uploads/'.$d;
    if (!is_dir($path)) { mkdir($path,0755,true); $msg[] = "📁 Created uploads/$d/"; }
    else $msg[] = "📁 uploads/$d/ exists";
}
?>
<!DOCTYPE html><html lang="en" data-theme="dark">
<head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>LuxStay Setup</title>
<link rel="stylesheet" href="<?=BASE_URL?>/assets/css/style.css">
<style>body{min-height:100vh;display:flex;align-items:center;justify-content:center;padding:2rem;background:var(--bg)}.box{max-width:600px;width:100%}.item{padding:.625rem 1rem;border-radius:8px;font-size:.875rem;margin-bottom:.5rem;font-family:monospace}.ok{background:rgba(34,197,94,.08);border:1px solid rgba(34,197,94,.2);color:#4ade80}.bad{background:rgba(239,68,68,.08);border:1px solid rgba(239,68,68,.2);color:#f87171}</style>
</head><body><div class="box">
  <div class="card p3 rounded">
    <h1 style="font-family:'Playfair Display',serif;font-size:1.5rem;font-weight:800;margin-bottom:.25rem;color:var(--gold)">🏨 LuxStay Setup</h1>
    <p style="color:var(--text3);font-size:.875rem;margin-bottom:1.5rem">Database initialization & demo accounts</p>
    <?php foreach($msg as $m):?><div class="item ok"><?=htmlspecialchars($m)?></div><?php endforeach;?>
    <?php foreach($err as $e):?><div class="item bad"><?=htmlspecialchars($e)?></div><?php endforeach;?>
    <hr style="border-color:var(--border);margin:1.5rem 0">
    <h3 style="font-weight:700;margin-bottom:1rem">Demo Login Credentials</h3>
    <?php foreach([['👑 Admin','admin@luxstay.com','Admin@LuxStay2024'],['🏨 Manager','manager@luxstay.com','Manager@LuxStay2024'],['⭐ Customer','customer@luxstay.com','Customer@123']] as [$r,$e,$p]):?>
      <div style="padding:.75rem 1rem;background:var(--bg);border:1px solid var(--border);border-radius:8px;margin-bottom:.5rem;font-size:.875rem">
        <strong><?=$r?></strong><br>
        Email: <code style="color:var(--gold)"><?=$e?></code><br>
        Pass: <code style="color:var(--gold)"><?=$p?></code>
      </div>
    <?php endforeach;?>
    <hr style="border-color:var(--border);margin:1.5rem 0">
    <a href="<?=BASE_URL?>/login.php" class="btn btn-primary btn-full" style="display:block;text-align:center">→ Go to Login</a>
    <p style="text-align:center;font-size:.75rem;color:var(--text3);margin-top:.875rem">⚠️ Delete this file after setup for security.</p>
  </div>
</div></body></html>
