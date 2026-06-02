<?php
// logout.php - Week 2
session_start();
session_destroy();
header("Location: login.php");
exit();
?>
