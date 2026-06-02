<?php
require_once __DIR__ . '/../../app/bootstrap.php';

if (Auth::check()) {
    $log = new ActivityLogModel();
    $log->log('logout', 'User ' . Auth::name() . ' logged out');
}

Auth::logout();
redirectTo(APP_URL . '/auth/login.php', 'success', 'You have been logged out successfully.');
