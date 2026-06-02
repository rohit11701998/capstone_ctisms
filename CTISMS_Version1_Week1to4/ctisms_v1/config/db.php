<?php
// config/db.php
// Week 1 - Database connection setup
// This was the first file created in the project

$host     = "localhost";
$dbname   = "ctisms_v1";
$username = "root";
$password = "";   // XAMPP default is blank

try {
    $pdo = new PDO(
        "mysql:host=$host;dbname=$dbname;charset=utf8mb4",
        $username,
        $password
    );
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("<h3 style='color:red;font-family:Arial;padding:20px'>
        Database Error: " . $e->getMessage() . "<br><br>
        Make sure XAMPP MySQL is running and the database 'ctisms_v1' exists.
    </h3>");
}
?>
