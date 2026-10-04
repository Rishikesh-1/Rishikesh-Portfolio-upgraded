<?php
require_once __DIR__ . '/config/config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . SITE_ROOT_URL . '/#contact');
    exit;
}

verify_csrf();

$name     = trim($_POST['name'] ?? '');
$email    = trim($_POST['email'] ?? '');
$subject  = trim($_POST['subject'] ?? '');
$message  = trim($_POST['message'] ?? '');
$returnTo = trim($_POST['return_to'] ?? '');
$redirectUrl = ($returnTo === 'contact-page') ? (SITE_ROOT_URL . '/page.php?view=contact') : (SITE_ROOT_URL . '/#contact');

$errors = [];
if ($name === '' || mb_strlen($name) > 120) $errors[] = 'Please enter a valid name.';
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Please enter a valid email.';
if ($message === '' || mb_strlen($message) > 4000) $errors[] = 'Please enter a message.';

if ($errors) {
    // Simple bounce-back; a real deployment could re-render the form with $errors.
    $_SESSION['contact_errors'] = $errors;
    header('Location: ' . $redirectUrl);
    exit;
}

$stmt = $pdo->prepare(
    'INSERT INTO contact_messages (name, email, subject, message, ip_address) VALUES (:name, :email, :subject, :message, :ip)'
);
$stmt->execute([
    ':name'    => $name,
    ':email'   => $email,
    ':subject' => $subject,
    ':message' => $message,
    ':ip'      => $_SERVER['REMOTE_ADDR'] ?? null,
]);
    // Email is a notification only; the database insert above must remain reliable
    // even when a host has not enabled or configured PHP mail delivery.
    $notificationEmail = get_setting($pdo, 'contact_notification_email', get_setting($pdo, 'contact_email'));
    if (filter_var($notificationEmail, FILTER_VALIDATE_EMAIL)) {
        $safeSubject = preg_replace('/[\r\n]+/', ' ', $subject);
        $mailSubject = 'New portfolio contact' . ($safeSubject !== '' ? ': ' . $safeSubject : '');
        $mailBody = "New contact form submission\n\n" .
            "Name: {$name}\nEmail: {$email}\nSubject: {$safeSubject}\n\nMessage:\n{$message}\n";
        $mailHeaders = "From: {$notificationEmail}\r\n" .
            "Reply-To: {$email}\r\n" .
            "Content-Type: text/plain; charset=UTF-8\r\n";
        if (!@mail($notificationEmail, $mailSubject, $mailBody, $mailHeaders)) {
            error_log('CONTACT EMAIL FAILED for message from ' . $email);
        }
    }

$_SESSION['contact_success'] = true;
header('Location: ' . $redirectUrl);
exit;
