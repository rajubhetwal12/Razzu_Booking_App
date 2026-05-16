<?php
require_once 'includes/auth.php';
logoutUser();
redirect(BASE_URL . '/login.php');
