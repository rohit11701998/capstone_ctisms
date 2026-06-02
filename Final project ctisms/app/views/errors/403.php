<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>403 Access Denied — CTISMS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; background: #f0f2f8; display:flex; align-items:center; justify-content:center; min-height:100vh; }
        .error-card { text-align:center; padding:60px 40px; background:#fff; border-radius:16px; max-width:440px; box-shadow:0 8px 40px rgba(26,35,126,0.12); }
        .error-code { font-size:80px; font-weight:700; color:#c62828; line-height:1; }
    </style>
</head>
<body>
<div class="error-card">
    <div class="error-code">403</div>
    <h2 class="mt-2 mb-1" style="color:#1a237e">Access Denied</h2>
    <p class="text-muted mb-4">You don't have permission to access this page.</p>
    <a href="javascript:history.back()" class="btn btn-outline-primary me-2">Go Back</a>
    <a href="<?= defined('APP_URL') ? APP_URL : '/ctisms/public' ?>/auth/login.php" class="btn btn-primary">Login</a>
</div>
</body>
</html>
