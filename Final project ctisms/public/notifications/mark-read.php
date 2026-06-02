<?php
require_once __DIR__ . '/../../app/bootstrap.php';
Auth::requireAuth();
(new NotificationModel())->markAllRead(Auth::id());
redirectTo(APP_URL . '/notifications/index.php', 'success', 'All notifications marked as read.');
