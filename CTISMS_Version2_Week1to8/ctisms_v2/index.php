<?php
require_once 'includes/auth.php';
if (isLoggedIn()) {
    header('Location: ' . dashboardUrl());
} else {
    header('Location: /ctisms_v2/login.php');
}
exit;
