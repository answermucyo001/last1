<?php
require_once 'config.php';

$message_sent = false;
$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['send_message'])) {
    $name = mysqli_real_escape_string($conn, $_POST['name']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $subject = mysqli_real_escape_string($conn, $_POST['subject']);
    $message = mysqli_real_escape_string($conn, $_POST['message']);
    $user_id = isLoggedIn() ? $_SESSION['user_id'] : 'NULL';
    
    $query = "INSERT INTO messages (user_id, name, email, subject, message) 
              VALUES ($user_id, '$name', '$email', '$subject', '$message')";
    
    if (mysqli_query($conn, $query)) {
        $message_sent = true;
    } else {
        $error = "Failed to send message. Please try again.";
    }
}
?>

<?php include 'header.php'; ?>

<div class="contact-section">
    <h1>Contact Us</h1>
    
    <div class="contact-container">
        <div class="contact-info">
            <h2>Get in Touch</h2>
            
            <div class="info-item">
                <img src="images/location.png" alt="Location">
                <div>
                    <h3>Visit Us</h3>
                    <p>Plot 5, Kampala Road<br>Kampala, Uganda</p>
                </div>
            </div>
            
            <div class="info-item">
                <img src="images/phone.png" alt="Phone">
                <div>
                    <h3>Call Us</h3>
                    <p>+256 700 000000<br>+256 800 000000 (Toll Free)</p>
                </div>
            </div>
            
            <div class="info-item">
                <img src="images/email.png" alt="Email">
                <div>
                    <h3>Email Us</h3>
                    <p>info@pharmacygold.com<br>support@pharmacygold.com</p>
                </div>
            </div>
            
            <div class="info-item">
                <img src="images/hours.png" alt="Hours">
                <div>
                    <h3>Working Hours</h3>
                    <p>Monday - Friday: 8am - 8pm<br>Saturday: 9am - 6pm<br>Sunday: Closed</p>
                </div>
            </div>
            
            <div class="mtn-payment-info">
                <h3>MTN Mobile Money</h3>
                <p>Pay to: 0700 000000 (Pharmacy GOLD Health)</p>
            </div>
        </div>
        
        <div class="contact-form">
            <h2>Send Us a Message</h2>
            
            <?php if ($message_sent): ?>
                <div class="alert alert-success">Thank you for your message! We'll get back to you soon.</div>
            <?php endif; ?>
            
            <?php if ($error): ?>
                <div class="alert alert-error"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <form method="POST" action="">
                <div class="form-group">
                    <label for="name">Your Name</label>
                    <input type="text" id="name" name="name" required 
                           value="<?php echo isset($_SESSION['username']) ? $_SESSION['username'] : ''; ?>">
                </div>
                
                <div class="form-group">
                    <label for="email">Email Address</label>
                    <input type="email" id="email" name="email" required>
                </div>
                
                <div class="form-group">
                    <label for="subject">Subject</label>
                    <input type="text" id="subject" name="subject" required>
                </div>
                
                <div class="form-group">
                    <label for="message">Message</label>
                    <textarea id="message" name="message" rows="5" required></textarea>
                </div>
                
                <button type="submit" name="send_message" class="btn btn-primary">Send Message</button>
            </form>
        </div>
    </div>
</div>

<?php include 'footer.php'; ?>