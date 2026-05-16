<?php
// includes/header.php — safely re-requires if needed
defined('BASE_URL') || require_once __DIR__.'/../config/db.php';
function_exists('isLoggedIn') || require_once __DIR__.'/auth.php';
$_u=isLoggedIn()?currentUser():['id'=>null,'name'=>'','role'=>''];
$_page=basename($_SERVER['PHP_SELF'],'.php');
$_flashOk=flash('success');$_flashErr=flash('error');$_flashInf=flash('info');
?><!DOCTYPE html>
<html lang="en" data-theme="dark">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title><?=isset($pageTitle)?e($pageTitle):'LuxStay — Premium Hotel Booking Nepal'?></title>
<meta name="description" content="<?=isset($pageDesc)?e($pageDesc):'Discover luxury hotels across Nepal. Instant booking, best prices.'?>">
<link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><text y='.9em' font-size='90'>🏨</text></svg>">
<link rel="stylesheet" href="<?=BASE_URL?>/assets/css/style.css">
<link rel="preconnect" href="https://fonts.googleapis.com">
<script src="https://cdnjs.cloudflare.com/ajax/libs/three.js/r134/three.min.js" defer></script>
</head>
<body>

<nav class="nav">
  <div class="nav-inner">
    <a href="<?=BASE_URL?>/" class="logo">
      <div class="logo-icon">🏨</div>
      <span class="logo-text">LuxStay</span>
    </a>
    <div class="nav-links">
      <a href="<?=BASE_URL?>/"                          class="nav-link <?=$_page==='index'?'active':''?>">Home</a>
      <a href="<?=BASE_URL?>/hotels.php"                class="nav-link <?=$_page==='hotels'?'active':''?>">Hotels</a>
      <a href="<?=BASE_URL?>/hotels.php?featured=1"     class="nav-link">Featured</a>
      <a href="<?=BASE_URL?>/hotels.php?sort=price_asc" class="nav-link">Deals</a>
    </div>
    <div class="nav-end">
      <button class="theme-btn" id="theme-btn" title="Toggle theme">☀️</button>
      <?php if($_u['id']):?>
        <a href="<?=BASE_URL?>/dashboard/<?=$_u['role']?>.php" class="btn-nav-outline">Dashboard</a>
        <a href="<?=BASE_URL?>/logout.php" class="btn-nav-gold">Logout</a>
      <?php else:?>
        <a href="<?=BASE_URL?>/login.php"    class="btn-nav-outline">Sign In</a>
        <a href="<?=BASE_URL?>/register.php" class="btn-nav-gold">Register</a>
      <?php endif;?>
      <button class="ham" id="ham">☰</button>
    </div>
  </div>
</nav>

<div class="mob-nav" id="mob-nav">
  <a href="<?=BASE_URL?>/"                      class="nav-link">🏠 Home</a>
  <a href="<?=BASE_URL?>/hotels.php"            class="nav-link">🏨 Hotels</a>
  <a href="<?=BASE_URL?>/hotels.php?featured=1" class="nav-link">⭐ Featured</a>
  <?php if($_u['id']):?>
  <a href="<?=BASE_URL?>/dashboard/<?=$_u['role']?>.php" class="nav-link">📊 Dashboard</a>
  <?php endif;?>
  <div class="mob-btns">
    <?php if($_u['id']):?>
      <a href="<?=BASE_URL?>/logout.php"          class="btn btn-outline btn-sm">Logout</a>
    <?php else:?>
      <a href="<?=BASE_URL?>/login.php"           class="btn btn-outline btn-sm">Sign In</a>
      <a href="<?=BASE_URL?>/register.php"        class="btn btn-primary btn-sm">Register</a>
    <?php endif;?>
  </div>
</div>

<div class="page">
<?php if($_flashOk):?><div class="container mt2"><div class="alert alert-success"><?=e($_flashOk)?></div></div><?php endif;?>
<?php if($_flashErr):?><div class="container mt2"><div class="alert alert-error"><?=e($_flashErr)?></div></div><?php endif;?>
<?php if($_flashInf):?><div class="container mt2"><div class="alert alert-info"><?=e($_flashInf)?></div></div><?php endif;?>
