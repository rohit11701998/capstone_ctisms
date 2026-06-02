<?php
/**
 * Email Notification Service
 */

class MailService
{
    /**
     * Send email via PHPMailer (or fallback to PHP mail())
     */
    public static function send(string $to, string $toName, string $subject, string $body): bool
    {
        if (!MAIL_ENABLED) return true; // Silently skip if not configured

        // Try PHPMailer if available
        $phpmailerPath = __DIR__ . '/../../vendor/phpmailer/phpmailer/src/PHPMailer.php';
        if (file_exists($phpmailerPath)) {
            return self::sendWithPHPMailer($to, $toName, $subject, $body);
        }

        // Fallback to PHP mail()
        $headers  = "MIME-Version: 1.0\r\n";
        $headers .= "Content-type: text/html; charset=UTF-8\r\n";
        $headers .= "From: " . MAIL_FROM_NAME . " <" . MAIL_FROM_EMAIL . ">\r\n";
        return mail($to, $subject, $body, $headers);
    }

    private static function sendWithPHPMailer(string $to, string $toName, string $subject, string $body): bool
    {
        require_once __DIR__ . '/../../vendor/phpmailer/phpmailer/src/PHPMailer.php';
        require_once __DIR__ . '/../../vendor/phpmailer/phpmailer/src/SMTP.php';
        require_once __DIR__ . '/../../vendor/phpmailer/phpmailer/src/Exception.php';

        $mail = new PHPMailer\PHPMailer\PHPMailer(true);
        try {
            $mail->isSMTP();
            $mail->Host       = MAIL_HOST;
            $mail->SMTPAuth   = true;
            $mail->Username   = MAIL_USERNAME;
            $mail->Password   = MAIL_PASSWORD;
            $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port       = MAIL_PORT;
            $mail->setFrom(MAIL_FROM_EMAIL, MAIL_FROM_NAME);
            $mail->addAddress($to, $toName);
            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body    = $body;
            $mail->send();
            return true;
        } catch (Exception) {
            return false;
        }
    }

    /**
     * Notify customer: ticket created
     */
    public static function ticketCreated(array $ticket, array $customer): void
    {
        $subject = "[" . APP_NAME . "] Ticket #{$ticket['ticket_number']} Created";
        $body    = self::template('Ticket Created', "
            <p>Dear {$customer['name']},</p>
            <p>Your support ticket has been successfully created.</p>
            <table style='width:100%;border-collapse:collapse;'>
              <tr><td style='padding:8px;border:1px solid #ddd;font-weight:bold;'>Ticket #</td><td style='padding:8px;border:1px solid #ddd;'>{$ticket['ticket_number']}</td></tr>
              <tr><td style='padding:8px;border:1px solid #ddd;font-weight:bold;'>Title</td><td style='padding:8px;border:1px solid #ddd;'>{$ticket['title']}</td></tr>
              <tr><td style='padding:8px;border:1px solid #ddd;font-weight:bold;'>Priority</td><td style='padding:8px;border:1px solid #ddd;'>".ucfirst($ticket['priority'])."</td></tr>
              <tr><td style='padding:8px;border:1px solid #ddd;font-weight:bold;'>Status</td><td style='padding:8px;border:1px solid #ddd;'>Submitted</td></tr>
            </table>
            <p>You will receive further updates as your ticket is processed.</p>
        ");
        self::send($customer['email'], $customer['name'], $subject, $body);
    }

    /**
     * Notify technician: ticket assigned
     */
    public static function ticketAssigned(array $ticket, array $technician): void
    {
        $subject = "[" . APP_NAME . "] New Ticket Assigned: #{$ticket['ticket_number']}";
        $body    = self::template('Ticket Assigned', "
            <p>Dear {$technician['name']},</p>
            <p>A new ticket has been assigned to you.</p>
            <table style='width:100%;border-collapse:collapse;'>
              <tr><td style='padding:8px;border:1px solid #ddd;font-weight:bold;'>Ticket #</td><td style='padding:8px;border:1px solid #ddd;'>{$ticket['ticket_number']}</td></tr>
              <tr><td style='padding:8px;border:1px solid #ddd;font-weight:bold;'>Title</td><td style='padding:8px;border:1px solid #ddd;'>{$ticket['title']}</td></tr>
              <tr><td style='padding:8px;border:1px solid #ddd;font-weight:bold;'>Priority</td><td style='padding:8px;border:1px solid #ddd;'>".ucfirst($ticket['priority'])."</td></tr>
            </table>
            <p>Please log in to the system to view and process this ticket.</p>
        ");
        self::send($technician['email'], $technician['name'], $subject, $body);
    }

    /**
     * Notify customer: status changed
     */
    public static function statusChanged(array $ticket, array $customer, string $newStatus): void
    {
        $statusLabel = TicketModel::STATUSES[$newStatus]['label'] ?? ucfirst($newStatus);
        $subject = "[" . APP_NAME . "] Ticket #{$ticket['ticket_number']} Status Updated";
        $body    = self::template('Ticket Status Updated', "
            <p>Dear {$customer['name']},</p>
            <p>Your ticket status has been updated.</p>
            <table style='width:100%;border-collapse:collapse;'>
              <tr><td style='padding:8px;border:1px solid #ddd;font-weight:bold;'>Ticket #</td><td style='padding:8px;border:1px solid #ddd;'>{$ticket['ticket_number']}</td></tr>
              <tr><td style='padding:8px;border:1px solid #ddd;font-weight:bold;'>Title</td><td style='padding:8px;border:1px solid #ddd;'>{$ticket['title']}</td></tr>
              <tr><td style='padding:8px;border:1px solid #ddd;font-weight:bold;'>New Status</td><td style='padding:8px;border:1px solid #ddd;'><strong>{$statusLabel}</strong></td></tr>
            </table>
        ");
        self::send($customer['email'], $customer['name'], $subject, $body);
    }

    /**
     * Build branded email template
     */
    private static function template(string $heading, string $content): string
    {
        return "<!DOCTYPE html><html><head><meta charset='utf-8'></head><body style='font-family:Arial,sans-serif;margin:0;padding:0;background:#f5f5f5;'>
        <div style='max-width:600px;margin:30px auto;background:#fff;border-radius:8px;overflow:hidden;box-shadow:0 2px 10px rgba(0,0,0,0.1);'>
          <div style='background:linear-gradient(135deg,#1a237e,#283593);padding:30px 40px;'>
            <h1 style='color:#fff;margin:0;font-size:24px;'>".APP_NAME."</h1>
            <p style='color:rgba(255,255,255,0.7);margin:5px 0 0;'>IT Support Management System</p>
          </div>
          <div style='padding:30px 40px;'>
            <h2 style='color:#1a237e;margin-top:0;'>{$heading}</h2>
            {$content}
          </div>
          <div style='background:#f5f5f5;padding:20px 40px;text-align:center;color:#999;font-size:12px;'>
            <p>This is an automated message from ".APP_NAME.". Please do not reply to this email.</p>
          </div>
        </div></body></html>";
    }
}
