<?php
/**
 * CTISMS - Public Entry Point
 * Redirects to login or dashboard based on auth state
 */
require_once __DIR__ . '/../app/bootstrap.php';

if (Auth::check()) {
    redirect(Auth::dashboardUrl());
} else {
    redirect(APP_URL . '/auth/login.php');
}
