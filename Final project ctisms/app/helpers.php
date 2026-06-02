<?php
/**
 * Global Helper Functions
 */

/**
 * Safely escape HTML output (XSS protection)
 */
function e(mixed $val): string
{
    return htmlspecialchars((string)($val ?? ''), ENT_QUOTES | ENT_HTML5, 'UTF-8');
}

/**
 * Redirect to URL
 */
function redirect(string $url): never
{
    header('Location: ' . $url);
    exit;
}

/**
 * Redirect back with flash message
 */
function redirectBack(string $type, string $message): never
{
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
    $back = $_SERVER['HTTP_REFERER'] ?? APP_URL;
    redirect($back);
}

/**
 * Redirect to URL with flash
 */
function redirectTo(string $url, string $type = 'success', string $message = ''): never
{
    if ($message) $_SESSION['flash'] = ['type' => $type, 'message' => $message];
    redirect($url);
}

/**
 * Get and clear flash message
 */
function flash(): ?array
{
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}

/**
 * Render flash alert HTML
 */
function renderFlash(): string
{
    $flash = flash();
    if (!$flash) return '';
    $map = ['success'=>'success','error'=>'danger','warning'=>'warning','info'=>'info'];
    $cls = $map[$flash['type']] ?? 'info';
    return '<div class="alert alert-'.$cls.' alert-dismissible fade show" role="alert">'
        . e($flash['message'])
        . '<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>';
}

/**
 * Format datetime for display
 */
function formatDate(string $date, string $format = 'd M Y, H:i'): string
{
    if (!$date) return '—';
    return (new DateTime($date))->format($format);
}

/**
 * Human-readable time ago
 */
function timeAgo(string $datetime): string
{
    $time  = strtotime($datetime);
    $diff  = time() - $time;

    if ($diff < 60)      return 'just now';
    if ($diff < 3600)    return floor($diff/60) . 'm ago';
    if ($diff < 86400)   return floor($diff/3600) . 'h ago';
    if ($diff < 604800)  return floor($diff/86400) . 'd ago';
    return (new DateTime($datetime))->format('d M Y');
}

/**
 * Format bytes to human-readable size
 */
function formatBytes(int $bytes): string
{
    if ($bytes >= 1048576) return round($bytes/1048576, 1) . ' MB';
    if ($bytes >= 1024)    return round($bytes/1024, 1) . ' KB';
    return $bytes . ' B';
}

/**
 * Get Bootstrap badge class for ticket status
 */
function statusBadge(string $status): string
{
    $statuses = TicketModel::STATUSES;
    $color = $statuses[$status]['color'] ?? 'secondary';
    $label = $statuses[$status]['label'] ?? ucfirst($status);
    return '<span class="badge bg-'.$color.'">'.e($label).'</span>';
}

/**
 * Get Bootstrap badge class for priority
 */
function priorityBadge(string $priority): string
{
    $priorities = TicketModel::PRIORITIES;
    $color = $priorities[$priority]['color'] ?? 'secondary';
    $label = $priorities[$priority]['label'] ?? ucfirst($priority);
    $icon  = match($priority) {
        'critical' => '🔴',
        'high'     => '🟠',
        'medium'   => '🟡',
        default    => '🟢',
    };
    return '<span class="badge bg-'.$color.'">'.$icon.' '.e($label).'</span>';
}

/**
 * SLA badge - shows if breached or time remaining
 */
function slaBadge(string $deadline, int $breached): string
{
    if ($breached) {
        return '<span class="badge bg-danger"><i class="bi bi-exclamation-triangle"></i> SLA Breached</span>';
    }
    $remaining = strtotime($deadline) - time();
    if ($remaining < 0) {
        return '<span class="badge bg-danger">SLA Overdue</span>';
    }
    if ($remaining < 3600) {
        $mins = floor($remaining/60);
        return '<span class="badge bg-warning text-dark">'.$mins.'m remaining</span>';
    }
    $hours = floor($remaining/3600);
    $color = $hours < 4 ? 'warning text-dark' : 'success';
    return '<span class="badge bg-'.$color.'">'.$hours.'h remaining</span>';
}

/**
 * Paginate HTML output
 */
function paginationLinks(array $pagination, string $baseUrl): string
{
    if ($pagination['last_page'] <= 1) return '';
    $current = $pagination['current_page'];
    $last    = $pagination['last_page'];
    $sep = strpos($baseUrl, '?') !== false ? '&' : '?';

    $html  = '<nav><ul class="pagination pagination-sm justify-content-center">';
    $html .= '<li class="page-item'.($current<=1?' disabled':'').'">'
           . '<a class="page-link" href="'.$baseUrl.$sep.'page='.($current-1).'">‹ Prev</a></li>';

    for ($i = max(1,$current-2); $i <= min($last,$current+2); $i++) {
        $active = $i === $current ? ' active' : '';
        $html  .= '<li class="page-item'.$active.'">'
                . '<a class="page-link" href="'.$baseUrl.$sep.'page='.$i.'">'.$i.'</a></li>';
    }

    $html .= '<li class="page-item'.($current>=$last?' disabled':'').'">'
           . '<a class="page-link" href="'.$baseUrl.$sep.'page='.($current+1).'">Next ›</a></li>';
    $html .= '</ul></nav>';
    return $html;
}

/**
 * Validate and handle file upload
 */
function handleFileUpload(array $file, string $destDir): array|false
{
    if ($file['error'] !== UPLOAD_ERR_OK) return false;
    if ($file['size'] > UPLOAD_MAX_SIZE) return false;

    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, UPLOAD_ALLOWED)) return false;

    $storedName = bin2hex(random_bytes(16)) . '.' . $ext;
    $destPath   = rtrim($destDir, '/') . '/' . $storedName;

    if (!move_uploaded_file($file['tmp_name'], $destPath)) return false;

    return [
        'stored_name'   => $storedName,
        'original_name' => basename($file['name']),
        'size'          => $file['size'],
        'mime_type'     => mime_content_type($destPath),
    ];
}

/**
 * CSRF token generation
 */
function csrfToken(): string
{
    if (empty($_SESSION['_csrf'])) {
        $_SESSION['_csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['_csrf'];
}

function csrfField(): string
{
    return '<input type="hidden" name="_csrf" value="'.e(csrfToken()).'">';
}

function verifyCsrf(): void
{
    $token = $_POST['_csrf'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    if (!hash_equals(csrfToken(), $token)) {
        http_response_code(403);
        die('CSRF token mismatch. Please go back and try again.');
    }
}
