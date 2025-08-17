<?php
// contact_submit.php
//
// HOW THIS WORKS
// - Reads POSTed form fields: name, email, enquiry (+ a hidden honeypot)
// - Validates them (very basic, friendly errors)
// - Sends email via Gmail SMTP using PHPMailer
// - Shows a simple success/fail message
//
// REQUIREMENTS
// - Upload PHPMailer 'src' folder next to this file (from https://github.com/PHPMailer/PHPMailer)
// - Create a Gmail "App password" (Google Account → Security → 2-Step Verification → App Passwords)

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// 1) Only accept POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  http_response_code(405);
  exit('Method Not Allowed');
}

// 2) Gather inputs safely
$name    = trim($_POST['name']    ?? '');
$email   = trim($_POST['email']   ?? '');
$enquiry = trim($_POST['enquiry'] ?? '');
// Honeypot (add a hidden <input name="website"> in your form)
$trap    = trim($_POST['website'] ?? '');

// 3) Quick bot check (if filled, quietly stop)
if ($trap !== '') {
  // pretend success so bots don't try again
  exit('OK');
}

// 4) Validate
$errors = [];
if ($name === '') {
  $errors[] = 'Please enter your name.';
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
  $errors[] = 'Please enter a valid email address.';
}
if ($enquiry === '') {
  $errors[] = 'Please enter your enquiry.';
} elseif (mb_strlen($enquiry) > 4000) {
  $errors[] = 'Please keep your message under 4000 characters.';
}

if ($errors) {
  // Build list HTML safely
  $list = '<ul>';
  foreach ($errors as $e) {
    $list .= '<li>' . htmlspecialchars($e, ENT_QUOTES, 'UTF-8') . '</li>';
  }
  $list .= '</ul>';

  echo '<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>There was a problem</title>
  <link rel="stylesheet" href="css/style.css">
  <meta http-equiv="refresh" content="7;url=contact.html">
</head>
<body>
  <div class="main-content">
    <h2>There was a problem</h2>
    ' . $list . '
    <p>You will be redirected back to the contact form in 7 seconds…</p>
    <p><a href="contact.html">Go back now</a></p>
  </div>
</body>
</html>';
  exit;
}

// 5) Load PHPMailer (manual include from src/)
require __DIR__ . '/src/Exception.php';
require __DIR__ . '/src/PHPMailer.php';
require __DIR__ . '/src/SMTP.php';

// 6) Send via Gmail SMTP
try {
  $mail = new PHPMailer(true);

  // SMTP settings (Gmail)
  $mail->isSMTP();
  $mail->Host       = 'smtp.gmail.com';
  $mail->SMTPAuth   = true;

  // TODO: SET THESE TWO LINES:
  $mail->Username   = 'pixelandwoodcodestudio@gmail.com';     // your Gmail address
  $mail->Password   = 'ceftumxaithysloz';  // 16-char App Password from Google

  $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
  $mail->Port       = 587;

  // From/To
  // With Gmail SMTP, setFrom should normally be your Gmail (or an approved alias)
  $mail->setFrom('pixelandwoodcodestudio@gmail.com', 'Pixel & Wood Tutoring');
  $mail->addAddress('pixelandwoodcodestudio@gmail.com', 'Pixel & Wood Tutoring'); // where you receive messages
  $mail->addReplyTo($email, $name); // replying in Gmail goes to the visitor

  // Email content
  $mail->Subject = 'New enquiry from Pixel & Wood website';
  $body  = "Name: {$name}\n";
  $body .= "Email: {$email}\n\n";
  $body .= "Message:\n{$enquiry}\n";
  $mail->Body    = $body;
  $mail->AltBody = $body;

  // Optional: set a reasonable timeout
  $mail->Timeout = 15;

  // Send it
  $mail->send();

  // Simple success message with full HTML and redirect
  echo '<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Message Sent</title>
  <link rel="stylesheet" href="css/style.css">
</head>
<body>
  <div class="main-content">
    <h2>Thanks! Your message has been sent.</h2>
    <p>I\'ll reply to you by email soon.</p>
    <p>You will be redirected to the homepage in 5 seconds...</p>
  </div>
  <meta http-equiv="refresh" content="5;url=index.html">
</body>
</html>';
  exit;

} catch (Exception $e) {
  echo '<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Message Failed</title>
  <link rel="stylesheet" href="css/style.css">
</head>
<body>
  <div class="main-content">
    <h2>Sorry—your message could not be sent right now.</h2>
    <p>Please email me directly at <a href="mailto:pixelandwoodcodestudio@gmail.com">pixelandwoodcodestudio@gmail.com</a>.</p>
    <p>You will be redirected to the homepage in 5 seconds...</p>
  </div>
  <meta http-equiv="refresh" content="5;url=index.html">
</body>
</html>';
  exit;
}