<?php
require_once __DIR__ . '/../../app/bootstrap.php';
Auth::requireRole('admin');
include __DIR__ . '/../customer/view.php';
