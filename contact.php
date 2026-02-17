<?php
// ================================
// contact.php  (FINAL CLEAN VERSION)
// ================================

require __DIR__ . '/vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Load environment variables
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

// Start session BEFORE any output
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/* =====================================================
   HANDLE FORM SUBMISSION (MUST BE BEFORE ANY HTML)
   ===================================================== */

$errors = [];
$old = ['name' => '', 'email' => '', 'message' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Collect & sanitize input
    $old['name']    = trim($_POST['name'] ?? '');
    $old['email']   = trim($_POST['email'] ?? '');
    $old['message'] = trim($_POST['message'] ?? '');

    // Validation
    if ($old['name'] === '') {
        $errors['name'] = 'Please enter your name.';
    }

    if (!filter_var($old['email'], FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'Enter a valid email address.';
    }

    if ($old['message'] === '') {
        $errors['message'] = 'Please write a message.';
    }

    // If no validation errors → process form
    if (!$errors) {

        /* ========= SAVE TO CSV (backup lead storage) ========= */
        $dir = __DIR__ . '/data';
        if (!is_dir($dir)) {
            mkdir($dir, 0775, true);
        }

        $csv = $dir . '/contacts.csv';
        $row = [
            date('c'),
            $old['name'],
            $old['email'],
            preg_replace("/[\r\n]+/", " ", $old['message']),
            $_SERVER['REMOTE_ADDR'] ?? ''
        ];

        $quoted = array_map(
            fn($v) => '"' . str_replace('"', '""', $v) . '"',
            $row
        );

        file_put_contents($csv, implode(',', $quoted) . PHP_EOL, FILE_APPEND);


        /* ========= SEND EMAIL ========= */

        $mail = new PHPMailer(true);

        try {

            $mail->isSMTP();
            $mail->Host       = 'smtp.gmail.com';
            $mail->SMTPAuth   = true;
            $mail->Username   = $_ENV['SMTP_USER'];
            $mail->Password   = $_ENV['SMTP_PASS'];
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
            $mail->Port       = 465;

            // Sender (YOU)
            $mail->setFrom($_ENV['SMTP_USER'], 'Swift Designs Studio Website');

            // Where YOU receive messages
            $mail->addAddress($_ENV['SMTP_USER']);

            // Visitor becomes reply-to
            $mail->addReplyTo($old['email'], $old['name']);

            $mail->isHTML(true);
            $mail->Subject = 'New Contact Form Submission';

            $mail->Body = "
                <h3>New Website Inquiry</h3>
                <strong>Name:</strong> {$old['name']}<br>
                <strong>Email:</strong> {$old['email']}<br><br>
                <strong>Message:</strong><br>
                " . nl2br(htmlspecialchars($old['message'])) . "
            ";

            $mail->send();

            // SUCCESS → REDIRECT (THIS IS THE FIX)
            header("Location: /thank-you.php");
            exit;

        } catch (Exception $e) {
            $errors['mail'] = "Mailer Error: Please try again later.";
            error_log("Mailer Error: " . $mail->ErrorInfo);
        }
    }
}

/* =====================================================
   ONLY NOW DO WE OUTPUT HTML
   ===================================================== */

$page = 'contact';
include __DIR__ . '/header.php';
?>

<main class="contact-page">

<!-- FULL WIDTH HERO -->
<section class="contact-hero">
  <div class="contact-hero-inner">
      <h1 class="contact-title">Contact Us</h1>

      <p class="contact-lead">
        Have questions or want to work together?
      </p>

      <p class="contact-sub">
        Reach out via the form below or through any of the following methods:
      </p>
  </div>
</section>

<!-- MAIN CONTACT CONTENT -->
<section class="contact-area">
  <div class="container">


    <!-- Contact Cards -->
    <div class="contact-grid">

      <div class="contact-card">
        <i class="fa-solid fa-envelope"></i>
        <h4>Email</h4>
        <p><a href="mailto:info@swiftdesignsstudio.com">info@swiftdesignsstudio.com</a></p>
      </div>

      <div class="contact-card">
        <i class="fa-solid fa-phone"></i>
        <h4>Phone</h4>
        <p><a href="tel:3344564667">334-456-4667</a></p>
      </div>

      <div class="contact-card">
        <i class="fa-solid fa-location-dot"></i>
        <h4>Location</h4>
        <p>San Diego, California</p>
      </div>

      <div class="contact-card">
        <i class="fa-solid fa-share-nodes"></i>
        <h4>Social Media</h4>
        <div class="social-icons">
          <a href="https://linkedin.com/in/coreyshamburger" target="_blank" aria-label="LinkedIn">
            <i class="fa-brands fa-linkedin-in"></i>
          </a>
          <a href="https://github.com/SwiftDesignsStudio" target="_blank" aria-label="GitHub">
            <i class="fa-brands fa-github"></i>
          </a>
        </div>
      </div>
    </div>

    <!-- FORM -->
    <div class="contact-form-section">
      <form id="contact-form" action="contact.php" method="POST">

        <div class="form-grid">

          <div class="form-field">
            <input type="text" name="name" placeholder="Enter your full name *"
              value="<?= htmlspecialchars($old['name']); ?>" required>
          </div>

          <div class="form-field">
            <input type="email" name="email" placeholder="Enter your email *"
              value="<?= htmlspecialchars($old['email']); ?>" required>
          </div>

          <div class="form-field full-span">
            <textarea name="message" placeholder="Your Message *" required><?= htmlspecialchars($old['message']); ?></textarea>
          </div>

        </div>

        <div class="contact-submit-wrap">
          <button type="submit" class="contact-submit-btn">Send Message</button>
        </div>


      </form>
    </div>

  </div>
</section>

</main>



<?php include __DIR__ . '/footer.php'; ?>
