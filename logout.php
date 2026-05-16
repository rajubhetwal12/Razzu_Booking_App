<?php
require_once __DIR__.'/config/db.php';
require_once __DIR__.'/includes/auth.php';
logoutUser();
redirect(BASE_URL.'/login.php');
