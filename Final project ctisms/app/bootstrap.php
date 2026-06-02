<?php
/**
 * Application Bootstrap
 * Include this at the top of every public PHP file
 */

// Error display based on environment
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Base paths
define('ROOT_PATH', dirname(__DIR__));
define('APP_PATH',  ROOT_PATH . '/app');

// Load configuration
require_once ROOT_PATH . '/config/config.php';
require_once ROOT_PATH . '/config/database.php';

// Load models
require_once APP_PATH . '/models/Model.php';
require_once APP_PATH . '/models/UserModel.php';
require_once APP_PATH . '/models/TicketModel.php';
require_once APP_PATH . '/models/OtherModels.php';

// Load helpers & middleware
require_once APP_PATH . '/helpers.php';
require_once APP_PATH . '/middleware/Auth.php';
require_once APP_PATH . '/services/MailService.php';

// Start session
Auth::startSession();
