<?php
/**
 * Database Configuration
 * CTISMS - Customer Ticketing & IT Support Management System
 */

define('DB_HOST',    'localhost');
define('DB_NAME',    'ctisms');
define('DB_USER',    'root');       // Change for production
define('DB_PASS',    '');           // Change for production
define('DB_CHARSET', 'utf8mb4');

// Application Settings
define('APP_NAME',    'CTISMS');
define('APP_URL',     'http://localhost/ctisms/public');
define('APP_VERSION', '1.0.0');
define('APP_ENV',     'development'); // 'production' in live

// Email Settings (PHPMailer)
define('MAIL_HOST',       'smtp.gmail.com');
define('MAIL_PORT',       587);
define('MAIL_USERNAME',   'your-email@gmail.com');
define('MAIL_PASSWORD',   'your-app-password');
define('MAIL_FROM_EMAIL', 'noreply@ctisms.com');
define('MAIL_FROM_NAME',  'CTISMS Support');
define('MAIL_ENABLED',    false); // Set to true when email is configured

// File Upload Settings
define('UPLOAD_DIR',      __DIR__ . '/../public/uploads/attachments/');
define('UPLOAD_MAX_SIZE', 10 * 1024 * 1024); // 10MB
define('UPLOAD_ALLOWED',  ['jpg','jpeg','png','gif','pdf','doc','docx','xls','xlsx','txt','zip']);

// Session Settings
define('SESSION_LIFETIME', 7200); // 2 hours in seconds
define('SESSION_NAME',     'CTISMS_SESSION');

// Pagination
define('ITEMS_PER_PAGE', 15);

// Timezone
date_default_timezone_set('Australia/Sydney');
