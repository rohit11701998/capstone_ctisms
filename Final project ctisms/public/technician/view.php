<?php
/**
 * Technician: View Ticket
 * Delegates to the shared customer/view.php with technician role context
 */
require_once __DIR__ . '/../../app/bootstrap.php';
Auth::requireRole('technician');

// Include the shared view — Auth::role() returns 'technician' so role checks inside work correctly
include __DIR__ . '/../customer/view.php';
