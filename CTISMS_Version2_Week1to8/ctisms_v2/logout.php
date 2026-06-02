<?php
require_once 'includes/auth.php';
session_destroy();
header('Location: /ctisms_v2/login.php');
exit;
