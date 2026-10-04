<?php
/**
 * service-book.php — Handles service booking requests from service detail pages.
 * Validates inputs, saves to contact_messages, sends admin notification,
 * and sets session flash messages before redirecting.
 */
require_once __DIR__ . '/config/config.php';
ensure_services_schema($pdo);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . SITE_ROOT_URL . '/page.php?view=services');
    exit;
}

verify_csrf();

$serviceId = (int) ($_POST['service_id'] ?? 0);
$stmt = $pdo->prepare('SELECT * FROM services WHERE id = :id AND is_visible = 1 LIMIT 1');
$stmt->execute([':id' => $serviceId]);
$service = $stmt->fetch();

$redirectUrl = $service ? service_url($service) . '#book' : SITE_ROOT_URL . '/page.php?view=services';

// Honeypot check for bots
if (!empty($_POST['website'])) {
    header('Location: ' . $redirectUrl);
    exit;
}

$name     = trim($_POST['name'] ?? '');
$email    = trim($_POST['email'] ?? '');
$phone    = trim($_POST['phone'] ?? '');
$company  = trim($_POST['company'] ?? '');
$package  = trim($_POST['package'] ?? '');
$timeline = trim($_POST['timeline'] ?? '');
$budget   = trim($_POST['budget'] ?? '');
$message  = trim($_POST['message'] ?? '');

$errors = [];
if ($name === '' || mb_strlen($name) > 120) {
    $errors[] = 'Please enter your name.';
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL) || mb_strlen($email) > 150) {
    $errors[] = 'Please enter a valid email address.';
}
if ($message === '' || mb_strlen($message) > 4000) {
    $errors[] = 'Please share a brief description of your project or requirements.';
}
if (!$service) {
    $errors[] = 'The selected service is currently unavailable.';
}

if ($errors) {
    $_SESSION['service_booking_errors'] = $errors;
    $_SESSION['service_booking_old'] = [
        'name'     => $name,
        'email'    => $email,
        'phone'    => $phone,
        'company'  => $company,
        'package'  => $package,
        'timeline' => $timeline,
        'budget'   => $budget,
        'message'  => $message,
    ];
    header('Location: ' . $redirectUrl);
    exit;
}

$serviceTitle = $service['title'];
$mailSubject = "Service Booking: {$serviceTitle} - {$name}";

$fullMessage = "=== NEW SERVICE BOOKING REQUEST ===\n\n";
$fullMessage .= "Service:   {$serviceTitle}\n";
$fullMessage .= "Client:    {$name}\n";
$fullMessage .= "Email:     {$email}\n";
if ($phone !== '') {
    $fullMessage .= "Phone:     {$phone}\n";
}
if ($company !== '') {
    $fullMessage .= "Company:   {$company}\n";
}
if ($package !== '') {
    $fullMessage .= "Package:   {$package}\n";
}
if ($timeline !== '') {
    $fullMessage .= "Timeline:  {$timeline}\n";
}
if ($budget !== '') {
    $fullMessage .= "Budget:    {$budget}\n";
}
$fullMessage .= "\n--- Project Details ---\n{$message}\n";

// Save to contact_messages so dashboard admin sees it under Messages
$saveStmt = $pdo->prepare(
    'INSERT INTO contact_messages (name, email, subject, message, ip_address) VALUES (:name, :email, :subject, :message, :ip)'
);
$saveStmt->execute([
    ':name'    => $name,
    ':email'   => $email,
    ':subject' => mb_substr($mailSubject, 0, 200),
    ':message' => $fullMessage,
    ':ip'      => $_SERVER['REMOTE_ADDR'] ?? null,
]);

// Send email notification to site admin
$notificationEmail = get_setting($pdo, 'contact_notification_email', get_setting($pdo, 'contact_email'));
if (filter_var($notificationEmail, FILTER_VALIDATE_EMAIL)) {
    $safeSubject = preg_replace('/[\r\n]+/', ' ', $mailSubject);
    $mailHeaders = "From: {$notificationEmail}\r\n" .
        "Reply-To: {$email}\r\n" .
        "Content-Type: text/plain; charset=UTF-8\r\n";
    if (!@mail($notificationEmail, $safeSubject, $fullMessage, $mailHeaders)) {
        error_log('SERVICE BOOKING EMAIL FAILED for request from ' . $email);
    }
}

$_SESSION['service_booking_success'] = $serviceId;
header('Location: ' . $redirectUrl);
exit;
