<?php
session_start();

// Enhanced session validation
function validateUserSession() {
    if (!isset($_SESSION['email']) || !isset($_SESSION['role']) || !isset($_SESSION['user_id']) || !isset($_SESSION['login_time'])) {
        return false;
    }
    
    if ($_SESSION['role'] !== 'user') {
        return false;
    }
    
    // Optional: Check if session is too old (24 hours)
    if (time() - $_SESSION['login_time'] > 86400) {
        session_unset();
        session_destroy();
        return false;
    }
    
    return true;
}

if (!validateUserSession()) {
    header("Location: index.php");
    exit();
}

require_once 'config.php';

// Check if this is a fresh login (welcome message logic)
$show_welcome = false;
if (!isset($_SESSION['welcome_shown'])) {
    $show_welcome = true;
    $_SESSION['welcome_shown'] = true;
}


// Get flash messages from session and clear them immediately
$success = isset($_SESSION['success']) ? $_SESSION['success'] : '';
$error = isset($_SESSION['error']) ? $_SESSION['error'] : '';
unset($_SESSION['success']);
unset($_SESSION['error']);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['book_appointment'])) {
    // Validate session before processing appointment
    if (!validateUserSession()) {
        header("Location: index.php");
        exit();
    }
    
    $full_name = $_POST['full_name'];
    $age = $_POST['age'];
    $gender = $_POST['gender'];
    $contact_number = $_POST['contact_number'];
    $appointment_date = $_POST['appointment_date'];
    $appointment_time = $_POST['appointment_time'];
    $user_id = $_SESSION['user_id'];

    // Check if user already has an appointment booked
    $stmt = $conn->prepare("SELECT * FROM appointments WHERE user_id = ? AND status = 'pending'");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $_SESSION['error'] = "You already have an appointment booked. Please delete or complete your existing appointment before booking a new one.";
        header("Location: " . $_SERVER['PHP_SELF']);
        exit();
    } else {
        // Check if appointment date and time are within allowed range
        $appointment_datetime = strtotime($appointment_date . ' ' . $appointment_time);
        $current_datetime = strtotime(date('Y-m-d H:i'));

        if ($appointment_datetime < $current_datetime) {
            $_SESSION['error'] = "Appointment time is in the past. Please select a future time.";
            header("Location: " . $_SERVER['PHP_SELF']);
            exit();
        } elseif (date('w', $appointment_datetime) >= 1 && date('w', $appointment_datetime) <= 5 && date('H', $appointment_datetime) >= 8 && date('H', $appointment_datetime) <= 16) {
            // Check if there are already 2 appointments booked for the same hour and day
            $stmt = $conn->prepare("SELECT COUNT(*) as count FROM appointments WHERE appointment_date = ? AND HOUR(appointment_time) = HOUR(?)");
            $stmt->bind_param("ss", $appointment_date, $appointment_time);
            $stmt->execute();
            $result = $stmt->get_result();
            $row = $result->fetch_assoc();

            if ($row['count'] >= 2) {
                $_SESSION['error'] = "Sorry, there are already 2 appointments booked for this time slot.";
                header("Location: " . $_SERVER['PHP_SELF']);
                exit();
            } else {
                // Proceed with booking the appointment
                $stmt = $conn->prepare("INSERT INTO appointments (user_id, full_name, age, gender, contact_number, appointment_date, appointment_time, status) VALUES (?, ?, ?, ?, ?, ?, ?, 'pending')");
                $stmt->bind_param("isissss", $user_id, $full_name, $age, $gender, $contact_number, $appointment_date, $appointment_time);

                if ($stmt->execute()) {
                    $_SESSION['success'] = "Appointment booked successfully!";
                    header("Location: " . $_SERVER['PHP_SELF']);
                    exit();
                } else {
                    $_SESSION['error'] = "Failed to book appointment. Please try again.";
                    header("Location: " . $_SERVER['PHP_SELF']);
                    exit();
                }
                $stmt->close();
            }
        } else {
            $_SESSION['error'] = "Appointment date and time are not within allowed range.";
            header("Location: " . $_SERVER['PHP_SELF']);
            exit();
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_appointment'])) {
    // Validate session before processing deletion
    if (!validateUserSession()) {
        header("Location: index.php");
        exit();
    }
    
    $appointment_id = $_POST['appointment_id'];

    // Delete the appointment
    $stmt = $conn->prepare("DELETE FROM appointments WHERE id = ?");
    $stmt->bind_param("i", $appointment_id);
    $stmt->execute();

    if ($stmt->affected_rows > 0) {
        $_SESSION['success'] = "Appointment deleted successfully!";
        header("Location: " . $_SERVER['PHP_SELF']);
        exit();
    } else {
        $_SESSION['error'] = "Failed to delete appointment. Please try again.";
        error_log("Error deleting appointment: " . $stmt->error);
        header("Location: " . $_SERVER['PHP_SELF']);
        exit();
    }
    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.2.0/css/all.min.css">
  <link rel="stylesheet" href="admin_and_users_css/user_page.css">
  <link href="https://fonts.cdnfonts.com/css/poppins" rel="stylesheet">
  <title>User Page | Primelot Realty</title>
  <link rel="icon" type="image/svg+xml" href="Images/company_brand.svg">
  <link rel="icon" type="image/png" href="Images/company_brand.svg">
</head>
<style>
/* Profile Offcanvas Styling */
.profile-offcanvas {
    width: 320px !important;
}

.profile-offcanvas .offcanvas-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
}

.profile-offcanvas .btn-close {
    filter: brightness(0) invert(1);
}

.profile-header {
    background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
}

.avatar-circle {
    width: 80px;
    height: 80px;
    border-radius: 50%;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 36px;
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
}

.menu-section {
    border-bottom: 1px solid #e9ecef;
}

.menu-section-title {
    font-size: 12px;
    font-weight: 600;
    color: #6c757d;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    background-color: #f8f9fa;
}

.menu-item {
    display: flex;
    align-items: center;
    padding: 15px 20px;
    color: #333;
    text-decoration: none;
    transition: all 0.3s ease;
    border-left: 3px solid transparent;
}

.menu-item:hover {
    background-color: #f8f9fa;
    border-left-color: #667eea;
    color: #667eea;
}

.menu-item i:first-child {
    width: 24px;
    margin-right: 15px;
    font-size: 18px;
}

.menu-item.text-danger:hover {
    background-color: #fff5f5;
    border-left-color: #dc3545;
    color: #dc3545;
}

.profile-footer {
    background-color: #f8f9fa;
    border-top: 1px solid #e9ecef;
}

/* Modal Improvements */
.modal-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
}

.modal-header .btn-close {
    filter: brightness(0) invert(1);
}

.form-floating > .form-control:disabled {
    background-color: #e9ecef;
}

/* Responsive */
@media (max-width: 576px) {
    .profile-offcanvas {
        width: 280px !important;
    }
    
    .avatar-circle {
        width: 70px;
        height: 70px;
        font-size: 32px;
    }
    
    .menu-item {
        padding: 12px 15px;
        font-size: 14px;
    }
}
</style>
    
<body>

<!-- Session validation check in JavaScript -->
<script>
// Check if session is still valid every 30 seconds
setInterval(function() {
    fetch('check_session.php')
    .then(response => response.json())
    .then(data => {
        if (!data.valid || data.role !== 'user') {
            alert('Your session has expired. Please log in again.');
            window.location.href = 'index.php';
        }
    })
    .catch(error => {
        console.log('Session check failed:', error);
    });
}, 30000); // Check every 30 seconds
</script>


<!-- Navbar Top -->
<nav class="navbar top_navbar navbar-expand-lg fixed-top">
    
        <a class="navbar-brand" href="#home">
            <img src="Images/navbar_brand.svg" alt="Logo">
        </a>
        <div class="responsive_links border">
            <div class="nav-links-scrollable">
                <ul class="navbar-nav">
                    <li class="nav-item">
                        <a href="#home" class="nav-link">Home</a>
                    </li>
                    <li class="nav-item">
                        <a href="#aboutus" class="nav-link">About Us</a>
                    </li>
                    <li class="nav-item">
                        <a href="#properties" class="nav-link">Properties</a>
                    </li>
                    <li class="nav-item">
                        <a href="#offers" class="nav-link">Offers</a>
                    </li>
                    <li class="nav-item">
                        <a href="#awards" class="nav-link">Awards</a>
                    </li>
                </ul>
            </div>
        </div>
        <div class="btn-group top_buttons">
            <button type="button" class="btn appointment_btn" data-bs-toggle="modal" data-bs-target="#myAppointmentsModal">My Appointments</button>
            <button type="button" class="btn appointment_icon border border-rounded" data-bs-toggle="modal" data-bs-target="#myAppointmentsModal">
                <i class="fa-solid fa-calendar-check icon"></i>
            </button>
            <div class="dropdown dropstart text-end">
                <button type="button" class="btn dropdown-toggle border border-rounded" data-bs-toggle="dropdown">
                    <i class="fa-solid fa-user icon"></i>
                </button>
                <ul class="dropdown-menu">
                    <li><a class="dropdown-item" href="#" data-bs-toggle="offcanvas" data-bs-target="#profileOffcanvas">Profile</a></li>
                    <li><a class="dropdown-item" href="#" data-bs-toggle="modal" data-bs-target="#logoutModal">Logout</a></li>
                </ul>
            </div>
        </div>
    
</nav>

<div class="container-fluid main_body_container d-flex">

<!-- Left Navbar -->
    <div class="navbar left_navbar">
        <ul class="navbar-nav left_nav">
            <li class="nav-item Company_brand"><a class="nav-link" href="#home"><img class="img-fluid d-block" src="Images/company_brand.svg" alt="Company Logo"></a></li>
            <li class="nav-item txt_navs"><a class="nav-link text-white" href="#home"><i class="fa-etch fa-solid fa-house"></i>Home</a></li>
            <li class="nav-item txt_navs"><a class="nav-link text-white" href="#aboutus"><i class="fa-etch fa-solid fa-user"></i>About Us</a></li>
            <li class="nav-item txt_navs"><a class="nav-link text-white" href="#properties"><i class="fa-etch fa-solid fa-building"></i>Properties</a></li>
            <li class="nav-item txt_navs"><a class="nav-link text-white" href="#offers"><i class="fa-etch fa-solid fa-gift"></i>Offers</a></li>
            <li class="nav-item txt_navs"><a class="nav-link text-white" href="#awards"><i class="fa-etch fa-solid fa-trophy"></i>Awards</a></li>
        </ul>
    </div>

<!--Right Content-->
<div class="right_content border">

<!--Enhanced Profile Offcanvas-->
<div class="offcanvas offcanvas-start profile-offcanvas" tabindex="-1" id="profileOffcanvas">
    <div class="offcanvas-header border-bottom">
        <h5 class="offcanvas-title fw-bold">
            <i class="fas fa-user-circle me-2"></i>My Profile
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
    </div>
    
    <div class="offcanvas-body p-0">
        <!-- Profile Header -->
        <div class="profile-header text-center p-4 bg-gradient">
            <div class="profile-avatar d-flex text-center align-items-center justify-content-center mx-auto mb-3">
                <div class="avatar-circle">
                    <i class="fas fa-user"></i>
                </div>
            </div>
            <h6 class="mb-1 fw-bold"><?php echo htmlspecialchars($_SESSION['name']); ?></h6>
            <p class="text-muted small mb-0"><?php echo htmlspecialchars($_SESSION['email']); ?></p>
        </div>

        <!-- Profile Menu -->
        <div class="profile-menu">
            <!-- Account Settings -->
            <div class="menu-section">
                <h6 class="menu-section-title px-4 py-2 mb-0">Account Settings</h6>
                <a href="#" class="menu-item" data-bs-toggle="modal" data-bs-target="#editProfileModal" data-bs-dismiss="offcanvas">
                    <i class="fas fa-user-edit"></i>
                    <span>Edit Profile</span>
                    <i class="fas fa-chevron-right ms-auto"></i>
                </a>
                <a href="#" class="menu-item" data-bs-toggle="modal" data-bs-target="#changePasswordModal" data-bs-dismiss="offcanvas">
                    <i class="fas fa-key"></i>
                    <span>Change Password</span>
                    <i class="fas fa-chevron-right ms-auto"></i>
                </a>
                <a href="#" class="menu-item" data-bs-toggle="modal" data-bs-target="#myAppointmentsModal" data-bs-dismiss="offcanvas">
                    <i class="fas fa-calendar-check"></i>
                    <span>My Appointments</span>
                    <i class="fas fa-chevron-right ms-auto"></i>
                </a>
            </div>

            <!-- Help & Support -->
            <div class="menu-section">
                <h6 class="menu-section-title px-4 py-2 mb-0">Help & Support</h6>
                <a href="#" class="menu-item" data-bs-toggle="modal" data-bs-target="#helpModal" data-bs-dismiss="offcanvas">
                    <i class="fas fa-question-circle"></i>
                    <span>Help Center</span>
                    <i class="fas fa-chevron-right ms-auto"></i>
                </a>
                <a href="#" class="menu-item" data-bs-toggle="modal" data-bs-target="#contactSupportModal" data-bs-dismiss="offcanvas">
                    <i class="fas fa-headset"></i>
                    <span>Contact Support</span>
                    <i class="fas fa-chevron-right ms-auto"></i>
                </a>
            </div>

            <!-- Legal -->
            <div class="menu-section">
                <h6 class="menu-section-title px-4 py-2 mb-0">Legal</h6>
                <a href="#" class="menu-item" data-bs-toggle="modal" data-bs-target="#termsModal" data-bs-dismiss="offcanvas">
                    <i class="fas fa-file-contract"></i>
                    <span>Terms & Conditions</span>
                    <i class="fas fa-chevron-right ms-auto"></i>
                </a>
                <a href="#" class="menu-item" data-bs-toggle="modal" data-bs-target="#privacyModal" data-bs-dismiss="offcanvas">
                    <i class="fas fa-shield-alt"></i>
                    <span>Privacy Policy</span>
                    <i class="fas fa-chevron-right ms-auto"></i>
                </a>
            </div>

            <!-- Danger Zone -->
            <div class="menu-section border-top mt-3">
                <h6 class="menu-section-title px-4 py-2 mb-0 text-danger">Danger Zone</h6>
                <a href="#" class="menu-item text-danger" data-bs-toggle="modal" data-bs-target="#deleteAccountModal" data-bs-dismiss="offcanvas">
                    <i class="fas fa-user-times"></i>
                    <span>Delete Account</span>
                    <i class="fas fa-chevron-right ms-auto"></i>
                </a>
            </div>

            <!-- Logout -->
            <div class="menu-section border-top">
                <a href="#" class="menu-item text-danger" data-bs-toggle="modal" data-bs-target="#logoutModal" data-bs-dismiss="offcanvas">
                    <i class="fas fa-sign-out-alt"></i>
                    <span>Logout</span>
                    <i class="fas fa-chevron-right ms-auto"></i>
                </a>
            </div>
        </div>

        <!-- Profile Footer -->
        <div class="profile-footer text-center py-3 mt-auto">
            <p class="small text-muted mb-1">Primelot Realty © 2025</p>
            <p class="small text-muted mb-0">Version 1.0.0</p>
        </div>
    </div>
</div>

<!-- Edit Profile Modal -->
<div class="modal fade" id="editProfileModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-user-edit me-2"></i>Edit Profile</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="editProfileForm" method="post" action="update_profile.php">
                    <div class="form-floating mb-3">
                        <input type="text" class="form-control" id="editName" name="name" value="<?php echo htmlspecialchars($_SESSION['name']); ?>" required>
                        <label for="editName">Full Name</label>
                    </div>
                    <div class="form-floating mb-3">
                        <input type="email" class="form-control" id="editEmail" value="<?php echo htmlspecialchars($_SESSION['email']); ?>" disabled>
                        <label for="editEmail">Email (Cannot be changed)</label>
                    </div>
                    <div class="alert alert-info small">
                        <i class="fas fa-info-circle me-1"></i>
                        Your email address cannot be changed for security reasons.
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" form="editProfileForm" class="btn btn-primary">Save Changes</button>
            </div>
        </div>
    </div>
</div>

<!-- Change Password Modal -->
<div class="modal fade" id="changePasswordModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-key me-2"></i>Change Password</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="changePasswordForm" method="post" action="change_password.php">
                    <div class="form-floating mb-3">
                        <input type="password" class="form-control" id="currentPassword" name="current_password" required>
                        <label for="currentPassword">Current Password</label>
                    </div>
                    <div class="form-floating mb-3">
                        <input type="password" class="form-control" id="newPassword" name="new_password" minlength="6" required>
                        <label for="newPassword">New Password</label>
                    </div>
                    <div class="form-floating mb-3">
                        <input type="password" class="form-control" id="confirmPassword" name="confirm_password" minlength="6" required>
                        <label for="confirmPassword">Confirm New Password</label>
                    </div>
                    <div class="alert alert-warning small">
                        <i class="fas fa-exclamation-triangle me-1"></i>
                        Password must be at least 6 characters long.
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" form="changePasswordForm" class="btn btn-primary">Update Password</button>
            </div>
        </div>
    </div>
</div>

<!-- Help Center Modal -->
<div class="modal fade" id="helpModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-question-circle me-2"></i>Help Center</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="accordion" id="helpAccordion">
                    <div class="accordion-item">
                        <h2 class="accordion-header">
                            <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#helpOne">
                                How do I book an appointment?
                            </button>
                        </h2>
                        <div id="helpOne" class="accordion-collapse collapse show" data-bs-parent="#helpAccordion">
                            <div class="accordion-body">
                                Click on the "Book Appointment Now" button on the home page or use the "My Appointments" button in the navigation. Fill in your details and select your preferred date and time.
                            </div>
                        </div>
                    </div>
                    <div class="accordion-item">
                        <h2 class="accordion-header">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#helpTwo">
                                How do I cancel my appointment?
                            </button>
                        </h2>
                        <div id="helpTwo" class="accordion-collapse collapse" data-bs-parent="#helpAccordion">
                            <div class="accordion-body">
                                Go to "My Appointments", find your booking, and click the delete/cancel button. Please note that cancellations should be made at least 24 hours in advance.
                            </div>
                        </div>
                    </div>
                    <div class="accordion-item">
                        <h2 class="accordion-header">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#helpThree">
                                What are your business hours?
                            </button>
                        </h2>
                        <div id="helpThree" class="accordion-collapse collapse" data-bs-parent="#helpAccordion">
                            <div class="accordion-body">
                                We're open Monday to Friday, 8:00 AM to 4:00 PM. We're closed on weekends and public holidays. All visits require a confirmed appointment.
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-primary" data-bs-dismiss="modal">Got it, thanks!</button>
            </div>
        </div>
    </div>
</div>

<!-- Contact Support Modal -->
<div class="modal fade" id="contactSupportModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-headset me-2"></i>Contact Support</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="contactSupportForm" method="post" action="send_support.php">
                    <div class="form-floating mb-3">
                        <input type="text" class="form-control" id="supportName" name="name" value="<?php echo htmlspecialchars($_SESSION['name']); ?>" readonly>
                        <label for="supportName">Your Name</label>
                    </div>
                    <div class="form-floating mb-3">
                        <input type="email" class="form-control" id="supportEmail" name="email" value="<?php echo htmlspecialchars($_SESSION['email']); ?>" readonly>
                        <label for="supportEmail">Your Email</label>
                    </div>
                    <div class="form-floating mb-3">
                        <select class="form-select" id="supportSubject" name="subject" required>
                            <option value="">Select a topic...</option>
                            <option value="booking">Booking Issues</option>
                            <option value="technical">Technical Problems</option>
                            <option value="account">Account Issues</option>
                            <option value="general">General Inquiry</option>
                            <option value="other">Other</option>
                        </select>
                        <label for="supportSubject">Subject</label>
                    </div>
                    <div class="form-floating mb-3">
                        <textarea class="form-control" id="supportMessage" name="message" style="height: 120px" required></textarea>
                        <label for="supportMessage">Your Message</label>
                    </div>
                    <div class="alert alert-info small">
                        <i class="fas fa-clock me-1"></i>
                        We typically respond within 24 hours during business days.
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" form="contactSupportForm" class="btn btn-primary">Send Message</button>
            </div>
        </div>
    </div>
</div>

<!-- Terms & Conditions Modal -->
<div class="modal fade" id="termsModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-file-contract me-2"></i>Terms & Conditions</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <h6 class="fw-bold">1. Acceptance of Terms</h6>
                <p class="small">By accessing and using Primelot Realty's services, you accept and agree to be bound by these Terms and Conditions.</p>
                
                <h6 class="fw-bold mt-3">2. Appointment Policy</h6>
                <p class="small">All property viewings require a confirmed appointment. Appointments must be made through our website and are subject to availability during business hours (Monday-Friday, 8:00 AM - 4:00 PM).</p>
                
                <h6 class="fw-bold mt-3">3. Cancellation Policy</h6>
                <p class="small">Appointments may be cancelled up to 24 hours in advance. Late arrivals or no-shows will forfeit their appointment slot and must reschedule.</p>
                
                <h6 class="fw-bold mt-3">4. User Responsibilities</h6>
                <p class="small">Users must provide accurate information, maintain account security, and present valid identification and appointment confirmation upon arrival at our premises.</p>
                
                <h6 class="fw-bold mt-3">5. Disclaimer</h6>
                <p class="small mb-0">This website is created for educational purposes only. Property information, images, and pricing are for demonstration purposes and should not be considered factual.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-primary" data-bs-dismiss="modal">I Understand</button>
            </div>
        </div>
    </div>
</div>

<!-- Privacy Policy Modal -->
<div class="modal fade" id="privacyModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-shield-alt me-2"></i>Privacy Policy</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <h6 class="fw-bold">Information We Collect</h6>
                <p class="small">We collect personal information including your name, email address, contact number, and appointment details when you register and book appointments through our platform.</p>
                
                <h6 class="fw-bold mt-3">How We Use Your Information</h6>
                <p class="small">Your information is used to process appointments, communicate appointment details, verify identity at check-in, and improve our services.</p>
                
                <h6 class="fw-bold mt-3">Data Security</h6>
                <p class="small">We implement security measures to protect your personal information. Passwords are encrypted, and we use secure connections for data transmission.</p>
                
                <h6 class="fw-bold mt-3">Information Sharing</h6>
                <p class="small">We do not sell, trade, or share your personal information with third parties except as required by law or to provide our services.</p>
                
                <h6 class="fw-bold mt-3">Your Rights</h6>
                <p class="small">You have the right to access, update, or delete your personal information. Contact our support team for assistance.</p>
                
                <h6 class="fw-bold mt-3">Educational Purpose</h6>
                <p class="small mb-0">This platform is for educational purposes. All data handling practices are implemented as learning demonstrations.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-primary" data-bs-dismiss="modal">I Understand</button>
            </div>
        </div>
    </div>
</div>

<!-- Delete Account Modal -->
<div class="modal fade" id="deleteAccountModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-danger">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title"><i class="fas fa-exclamation-triangle me-2"></i>Delete Account</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="text-center mb-4">
                    <i class="fas fa-user-times text-danger" style="font-size: 64px;"></i>
                </div>
                
                <div class="alert alert-danger mb-3">
                    <h6 class="alert-heading fw-bold mb-2">
                        <i class="fas fa-exclamation-circle me-1"></i>Warning: This action is permanent!
                    </h6>
                    <p class="small mb-0">Once you delete your account, there is no going back. This will permanently delete:</p>
                </div>
                
                <ul class="small mb-4">
                    <li>Your profile information</li>
                    <li>All your appointment history</li>
                    <li>All your saved preferences</li>
                    <li>Your access to Primelot Realty services</li>
                </ul>
                
                <form id="deleteAccountForm" method="post" action="delete_account.php">
                    <div class="mb-3">
                        <label for="deletePassword" class="form-label fw-bold">Enter your password to confirm:</label>
                        <input type="password" class="form-control form-control-lg" id="deletePassword" name="confirm_password" placeholder="Your password" required>
                        <div class="form-text">You must enter your current password to delete your account.</div>
                    </div>
                    
                    <div class="form-check mb-3">
                        <input class="form-check-input" type="checkbox" id="confirmDelete" required>
                        <label class="form-check-label small" for="confirmDelete">
                            I understand that this action cannot be undone and I want to permanently delete my account.
                        </label>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="fas fa-times me-1"></i>Cancel
                </button>
                <button type="submit" form="deleteAccountForm" class="btn btn-danger" id="confirmDeleteBtn">
                    <i class="fas fa-trash-alt me-1"></i>Delete My Account
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Logout Confirmation Modal -->
<div class="modal fade" id="logoutModal" tabindex="-1" aria-labelledby="logoutModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="logoutModalLabel">Confirm Logout</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        Are you sure you want to logout?
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        <a href="logout.php" class="btn btn-danger">Logout</a>
      </div>
    </div>
  </div>
</div>


<!--Book Appointment Modal-->
<div class="modal fade" id="bookAppointmentModal">
    <div class="modal-dialog modal-dialog-scrollable modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <div class="modal-title mt-1 d-flex justify-content-center w-100 text-center text-shadow"><h1>Book Appointment</h1></div>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="container">
                    <?php if ($error) echo "<div class='alert alert-danger'>$error</div>"; ?>
                    
                    <form method="post" id="appointmentForm">
                        <div class="form-floating my-3">
                            <input type="text" class="form-control" id="full_name" name="full_name" placeholder="Full Name" required>
                            <label for="full_name">Full Name</label>
                            <p id="full_name_error" class="error-message text-danger mt-3 ms-1"></p>
                        </div>

                        <div class="form-floating my-3">
                            <input type="number" class="form-control" id="age" name="age" placeholder="Age" required min="18">
                            <label for="age">Age</label>
                        </div>

                        <div class="form-floating my-3">
                            <select class="form-select" id="gender" name="gender" required>
                                <option value="">--Select Gender--</option>
                                <option value="Male">Male</option>
                                <option value="Female">Female</option>
                                <option value="Other">Other</option>
                            </select>
                            <label for="gender" class="form-label">Gender</label>
                        </div>

                        <div class="form-floating my-3">
                            <input type="text" class="form-control" id="contact_number" name="contact_number" placeholder="Contact Number" required>
                            <label for="contact_number">Contact Number</label>
                            <p id="contact_number_error" class="error-message text-danger mt-3 ms-1"></p>
                        </div>

                        <div class="form-floating my-3">
                            <input type="date" class="form-control" id="appointment_date" name="appointment_date" placeholder="Appointment Date" required>
                            <label for="appointment_date">Appointment Date</label>
                        </div>

                        <div class="form-floating my-3">
                            <input type="time" class="form-control" id="appointment_time" name="appointment_time" placeholder="Appointment Time" required>
                            <label for="appointment_time">Appointment Time</label>
                        </div>

                        <div class="modal-footer">
                            <button type="submit" name="book_appointment" class="btn py-3 px-4 mt-2">Submit Appointment</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>


<!--Alert Messages-->
<?php if ($success): ?>
<div class="alert alert-success alert-dismissible fade show position-fixed top-0 start-50 translate-middle-x mt-3" 
     id="successAlert" style="z-index: 9999;">
    <i class="fas fa-check-circle"></i>
    <strong>Success!</strong> <?= htmlspecialchars($success) ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<?php if ($error): ?>
<div class="alert alert-danger alert-dismissible fade show position-fixed top-0 start-50 translate-middle-x mt-3" 
     id="errorAlert" style="z-index: 9999;">
    <i class="fas fa-exclamation-circle"></i>
    <strong>Error!</strong> <?= htmlspecialchars($error) ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<!--My Appointments Modal-->
<div class="modal fade" id="myAppointmentsModal">
    <div class="modal-dialog modal-fullscreen modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <div class="modal-title d-flex justify-content-center w-100 text-center mt-3"><h1 class="text-shadow fw-bold">My Appointments</h1></div>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body">
                <div class="container">
                    <div class="table-responsive-sm">
                        <table class="table table-bordered text-center align-middle">
                            <thead>
                                <tr class="text-center align-middle text-shadow table-dark">
                                <th>Full Name</th>
                                <th>Age</th>
                                <th>Gender</th>
                                <th>Contact Number</th>
                                <th>Appointment Date</th>
                                <th>Appointment Time</th>
                                <th>Booked At</th>
                                <th>Delete</th>
                                </tr>
                            </thead>
                            <tbody>
                             <?php
                             $myAppointments = $conn->query("SELECT *, DATE_FORMAT(appointment_time, '%h:%i %p') as formatted_time, 
                             DATE_FORMAT(created_at, '%Y-%m-%d %h:%i %p') as formatted_created_at FROM appointments WHERE user_id = " . $_SESSION['user_id'] . " ORDER BY created_at DESC");
                             while($row = $myAppointments->fetch_assoc()):
                             ?>

                            <tr class="text-center table-info">
                            <td><?= htmlspecialchars($row['full_name']) ?></td>
                            <td><?= htmlspecialchars($row['age']) ?></td>
                            <td><?= htmlspecialchars($row['gender']) ?></td>
                            <td><?= htmlspecialchars($row['contact_number']) ?></td>
                            <td><?= htmlspecialchars($row['appointment_date']) ?></td>
                            <td><?= htmlspecialchars($row['formatted_time']) ?></td>
                            <td><?= htmlspecialchars($row['formatted_created_at']) ?></td>
                            <td>
                                <button class="btn-sm border-0 bg-transparent text-shadow" onclick="deleteAppointment(<?= $row['id'] ?>)"><i class="fa-solid fa-trash-can"></i></button>
                            </td>
                            </tr>
                            
                            <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!--More Information Modal-->
<div class="modal fade" id="moreInfoModal">
    <div class="modal-dialog modal-dialog-scrollable modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <div class="modal-title d-flex justify-content-center w-100 text-center mt-3"><h4 class="text-shadow fw-bold">Business Hours & Appointment Policy</h4></div>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body">
                <div class="container-sm">
                    
                        <div class="mt-2">
                            <p>At <strong>Primelot Realty</strong>, we value both professionalism and the time of our clients. Our office is open <strong>Monday to Friday, from 8:00 AM until 4:00 PM</strong>. 
                                We operate strictly on an appointment basis, ensuring that each client receives our full attention and dedicated service. 
                                Please note that our office remains closed on weekends and all public holidays, during which we will not be able to accommodate any bookings.</p>
                        </div>

                        <div class="mt-2">
                            <p>For security and verification purposes, clients are required to <strong>present a screenshot of their confirmed appointment upon arrival</strong>, which must be shown to the guards at the entrance. 
                            This verification step ensures that only booked and verified clients can be attended to.</p>
                        </div>

                        <div class="mt-2">
                            <p>Each appointment is scheduled within a two-hour timeframe, with a maximum of two clients allowed per slot. This system ensures smooth operations and prevents overcrowding during working hours. 
                        It is important to emphasize that appointments must be strictly followed according to the selected date and time. <strong>Late arrivals or clients who miss their scheduled slot will not be accommodated</strong>, and rescheduling will be required.</p>
                        </div>

                        <div class="mt-2">
                            <p>By implementing these policies, Primelot Realty maintains an organized, secure, and professional environment where every client is served with efficiency and respect.</p>
                        </div>

                        <div class="modal-footer ">
                            <button type="submit" name="book_appointment" class="btn py-3 px-4 mt-2" data-bs-toggle="modal" data-bs-target="#bookAppointmentModal">Book Appointment</button>
                        </div>
                </div>

            </div>
        </div>
    </div>
</div>

<!-- Home Section -->
    <section id="home">
        <div class="container">
            <div class="row">
                <div class="col-sm-6">
                    <h1>Find Your in Primelot Realty</h1>
                    <p>Where every lot is a promise, and every home is a new beginning.</p>
                    <span>“Secure your future in our exclusive subdivision”</span>
                    <button type="button" class="btn" data-bs-toggle="modal" data-bs-target="#bookAppointmentModal">BOOK APPOINTMENT NOW!</button>
                </div>
                <div class="col-sm-6">
                    <img class="d-block w-100 img-fluid" src="Images/home_icon.png" alt="Home Icon">
                </div>
            </div>
        </div>
    </section>

    
<!-- About Us Section -->
    <section class="container-fluid" id="aboutus">
        <div class="container sponsors_container d-flex flex-column align-items-center">
            <h4 class="text-center">Sponsored By</h4>
            <img class="d-block img-fluid" src="Images/pornhub.svg" alt="Sponsors">
        </div>
        <div class="container pb-4">
            <div class="row p-2">
                <div class="col-sm-6">
                    <div id="jmb-carousel" class="carousel slide" data-bs-ride="carousel">
                        <!-- Indicators -->
                        <div class="carousel-indicators">
                            <button type="button" data-bs-target="#jmb-carousel" data-bs-slide-to="0" class="active"></button>
                            <button type="button" data-bs-target="#jmb-carousel" data-bs-slide-to="1"></button>
                            <button type="button" data-bs-target="#jmb-carousel" data-bs-slide-to="2"></button>
                        </div>
                        <!-- Carousel Inner -->
                        <div class="carousel-inner">
                            <div class="carousel-item active">
                                <img src="Images/building1.jpg" class="d-block w-100 img-fluid" alt="Commercial Building">
                            </div>
                            <div class="carousel-item">
                                <img src="Images/building2.jpg" class="d-block w-100 img-fluid" alt="Commercial Building">
                            </div>
                            <div class="carousel-item">
                                <img src="Images/building3.jpg" class="d-block w-100 img-fluid" alt="Commercial Building">
                            </div>
                        </div>
                    </div>
                    <!-- Description -->
                    <div class="mt-3 text-left">
                        <h5 class="text-center">JMB CORPORATION</h5>
                        <p>
                            <strong>JMB CORP.</strong> is a trusted building and construction company committed to delivering high-quality projects with precision and excellence. We specialize in creating durable, innovative, and sustainable structures that bring visions to life.
                        </p>
                        <span>"From residential homes to commercial buildings, we take pride in every detail."
                        - JMB Owner/Manager</span>
                        <div class="jmb_section d-flex justify-content-end">
                        <button type="button" class="btn mt-3 py-3 px-4" 
                        onclick="window.open('https://www.google.com/maps/place/Palmera+St,+Tugbok+District,+Davao+City,+8000/@7.0666709,125.5989192,17z/data=!3m1!4b1!4m5!3m4!1s0x329f9e2539b6c373:0x896728f490959108!8m2!3d7.0666709!4d125.5989192', '_blank')">
                        See Company Location</button>
                        </div>
                    </div>
                </div>

                <div class="col-sm-6">
                    <div id="appointment-carousel" class="carousel slide" data-bs-ride="carousel">
                        <!-- Indicators -->
                        <div class="carousel-indicators">
                            <button type="button" data-bs-target="#appointment-carousel" data-bs-slide-to="0" class="active"></button>
                            <button type="button" data-bs-target="#appointment-carousel" data-bs-slide-to="1"></button>
                            <button type="button" data-bs-target="#appointment-carousel" data-bs-slide-to="2"></button>
                        </div>
                        <!-- Carousel Inner -->
                        <div class="carousel-inner">
                            <div class="carousel-item active" data-bs-interval="3000">
                                <img src="Images/appointment1.jpg" class="d-block w-100 img-fluid" alt="Appointment">
                            </div>
                            <div class="carousel-item" data-bs-interval="3000">
                                <img src="Images/appointment2.jpg" class="d-block w-100 img-fluid" alt="Appointment">
                            </div>
                            <div class="carousel-item" data-bs-interval="3000">
                                <img src="Images/appointment3.jpg" class="d-block w-100 img-fluid" alt="Appointment">
                            </div>
                        </div>
                    </div>
                    <!-- Description -->
                    <div class="mt-3 text-left">
                        <h5 class="text-center">Connecting With Our Clients</h5>
                        <p>
                            At <strong>JMB CORP.</strong>, we take pride in owning and developing the <strong>Primelot Realty Subdivision</strong>. These moments with our clients and dedicated staff reflect our commitment to providing not just quality houses and lots, but also a seamless and trustworthy experience for every buyer and renter.
                        </p>
                        <span>
                            From consultations to formal meetings, every appointment is a step toward helping families and individuals find their ideal home within Primelot Realty.
                        </span>
                        <div class="d-flex justify-content-end">
                            <button type="button" class="btn mt-3 py-3 px-4" data-bs-toggle="modal" data-bs-target="#moreInfoModal">See More Information</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>


<!--Property Section-->
    <section class="container-fluid m-0" id="properties">
        <div class="page-header m-0">
            <h3 class="text-center text-white">Primelot Properties</h3>
        </div>
        <div class="container">

            <div class="row first_row">
                <div class="col-sm-6">
                    <div class="card mb-3">
                        <img src="Images/small_mansion.jpg" class="card-img-top d-block w-100 img-fluid" alt="Small mansions">
                        <div class="card-body">
                            <h5 class="card-title">Modern Small Mansions</h5>
                            <p class="card-text">Discover our selection of luxurious, modern small mansions, designed to redefine your lifestyle and create a vibrant and captivating space.</p>
                            <button type="button" class="btn small_mansions border w-50">More Info</button>
                        </div>
                    </div>
                </div>
                <div class="col-sm-6">
                    <div class="card mb-3">
                        <img src="Images/large_mansions.jpg" class="card-img-top d-block w-100 img-fluid" alt="Large Mansions">
                        <div class="card-body">
                            <h5 class="card-title">Luxurious Large Mansions</h5>
                            <p class="card-text">Experience the epitome of luxury and elegance with our exquisite selection of large mansions, meticulously crafted to captivate and inspire.</p>
                            <button type="button" class="btn big_mansions border w-50">More Info</button>
                        </div>
                    </div>
                </div>
            </div>

            <div class="container-fluid small_mansions-info p-0">

                <div class="row">

                  <div class="col-sm-7">
                    <div id="smallMansion" class="carousel slide" data-bs-ride="carousel">
                      <div class="carousel-indicators">
                       <button type="button" data-bs-target="#smallMansion" data-bs-slide-to="0" class="active" aria-current="true" aria-label="Slide 1"></button>
                       <button type="button" data-bs-target="#smallMansion" data-bs-slide-to="1" aria-label="Slide 2"></button>
                       <button type="button" data-bs-target="#smallMansion" data-bs-slide-to="2" aria-label="Slide 3"></button>
                       <button type="button" data-bs-target="#smallMansion" data-bs-slide-to="3" aria-label="Slide 4"></button>
                       <button type="button" data-bs-target="#smallMansion" data-bs-slide-to="4" aria-label="Slide 5"></button>
                    </div>
                <div class="carousel-inner">

                <div class="carousel-item active" data-bs-interval="10000">
                    <img src="Images/small_house1.jpg" class="d-block w-100 img-fluid" alt="Small Mansion">
                    <div class="carousel-caption d-none d-md-block">
                        <h5>Mansion's Front</h5>
                    </div>
                </div>

                <div class="carousel-item" data-bs-interval="2000">
                    <img src="Images/small_house2.jpg" class="d-block w-100 img-fluid" alt="Small Mansion">
                    <div class="carousel-caption d-none d-md-block">
                        <h5>Mansion's Side</h5>
                    </div>
                </div>

                <div class="carousel-item">
                    <img src="Images/small_house3.jpg" class="d-block w-100 img-fluid" alt="Small Mansion">
                    <div class="carousel-caption d-none d-md-block">
                        <h5>Mansion's Backyard</h5>
                    </div>
                </div>

                <div class="carousel-item">
                    <img src="Images/small_house4.jpg" class="d-block w-100 img-fluid" alt="Small Mansion">
                    <div class="carousel-caption d-none d-md-block">
                        <h5>Mansion's Interior</h5>
                    </div>
                </div>

                <div class="carousel-item">
                    <img src="Images/small_house5.jpg" class="d-block w-100 img-fluid" alt="Small Mansion">
                    <div class="carousel-caption d-none d-md-block">
                        <h5>Outdoor's Sofa</h5>
                    </div>
                </div>

            </div>
            <button class="carousel-control-prev" type="button" data-bs-target="#smallMansion" data-bs-slide="prev">
                <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                <span class="visually-hidden">Previous</span>
            </button>
            <button class="carousel-control-next" type="button" data-bs-target="#smallMansion" data-bs-slide="next">
                <span class="carousel-control-next-icon" aria-hidden="true"></span>
                <span class="visually-hidden">Next</span>
            </button>
        </div>
    </div>

        <div class="col-sm-5">
          <h4>Modern Small Mansions</h4>
          <h6>Price: $1,000,000</h6>
          <p><strong>Description:</strong> A cozy 2-storey home with modern design, perfect for small families.</p>
          <p><strong>Features:</strong> Living Room, Dining Room, Family Room, Balcony, Garage</p>
          <p><strong>Call-to-Action</strong></p>
          <h6>Key Details</h6>
          <ul>
            <li>Bedrooms: 3</li>
            <li>Bathrooms: 2</li>
            <li>Lot Area: 140 sqm</li>
            <li>Floor Area: 100 sqm</li>
            <li>Carport: 1 Car</li>
          </ul>  
          <button type="button" class="btn" data-bs-toggle="modal" data-bs-target="#bookAppointmentModal">Book Appointment</button>
          <button type="button" class="btn ms-2" id="backToProperties">Back</button>
        </div>

    </div>

  </div>

  <div class="container-fluid big_mansions-info mb-4">

    <div class="row">

        <div class="col-sm-7">
            <div id="bigMansion" class="carousel slide" data-bs-ride="carousel"> 

                <div class="carousel-indicators">
                    <button type="button" data-bs-target="#bigMansion" data-bs-slide-to="0" class="active" aria-current="true" aria-label="Slide 1"></button>
                    <button type="button" data-bs-target="#bigMansion" data-bs-slide-to="1" aria-label="Slide 2"></button>
                    <button type="button" data-bs-target="#bigMansion" data-bs-slide-to="2" aria-label="Slide 3"></button>
                    <button type="button" data-bs-target="#bigMansion" data-bs-slide-to="3" aria-label="Slide 4"></button>
                    <button type="button" data-bs-target="#bigMansion" data-bs-slide-to="4" aria-label="Slide 5"></button>
                    <button type="button" data-bs-target="#bigMansion" data-bs-slide-to="5" aria-label="Slide 6"></button>
                    <button type="button" data-bs-target="#bigMansion" data-bs-slide-to="6" aria-label="Slide 7"></button>
                </div>
                <div class="carousel-inner">

                <div class="carousel-item active">
                    <img src="Images/large_house1.jpg" class="d-block w-100 img-fluid" alt="Large Mansion">
                    <div class="carousel-caption d-none d-md-block">
                        <h5>Large Mansion</h5>
                    </div>
                </div>

                <div class="carousel-item">
                    <img src="Images/large_house2.jpg" class="d-block w-100 img-fluid" alt="Large Mansion">
                    <div class="carousel-caption d-none d-md-block">
                        <h5>Large Mansion</h5>
                    </div>
                </div>

                <div class="carousel-item">
                    <img src="Images/large_house3.jpg" class="d-block w-100 img-fluid" alt="Large Mansion">
                    <div class="carousel-caption d-none d-md-block">
                        <h5>Large Mansion</h5>
                    </div>
                </div>

                <div class="carousel-item">
                    <img src="Images/large_house4.jpg" class="d-block w-100 img-fluid" alt="Large Mansion">
                    <div class="carousel-caption d-none d-md-block">
                        <h5>Large Mansion</h5>
                    </div>
                </div>

                <div class="carousel-item">
                    <img src="Images/large_house5.jpg" class="d-block w-100 img-fluid" alt="Large Mansion">
                    <div class="carousel-caption d-none d-md-block">
                        <h5>Large Mansion</h5>
                    </div>
                </div>
                
                <div class="carousel-item">
                    <img src="Images/large_house6.jpg" class="d-block w-100 img-fluid" alt="Large Mansion">
                    <div class="carousel-caption d-none d-md-block">
                        <h5>Large Mansion</h5>
                    </div>
                </div>

                <div class="carousel-item">
                    <img src="Images/large_house7.jpg" class="d-block w-100 img-fluid" alt="Large Mansion">
                    <div class="carousel-caption d-none d-md-block">
                        <h5>Large Mansion</h5>
                    </div>
                </div>

                </div>
                <button class="carousel-control-prev" type="button" data-bs-target="#bigMansion" data-bs-slide="prev">
                    <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                    <span class="visually-hidden">Previous</span>
                </button>
                <button class="carousel-control-next" type="button" data-bs-target="#bigMansion" data-bs-slide="next">
                    <span class="carousel-control-next-icon" aria-hidden="true"></span>
                    <span class="visually-hidden">Next</span>
                </button>

            </div>
        </div>

        <div class="col-sm-5">
          <h4>Luxurios Large Mansions</h4>
          <h6>Price: $1,500,000</h6>
          <p><strong>Description:</strong> A prestigious mansion with spacious interiors, elegant finishes, and luxury amenities.</p>
          <p><strong>Features:</strong> Swimming pool, balcony, landscaped garden, family lounge</p>
          <h6>Key Details</h6>
          <ul>
            <li>Lot Area: 500 sqm</li>
            <li>Floor Area: 400 sqm</li>
            <li>Bedrooms: 6</li>
            <li>Bathrooms: 5</li>
            <li>Garage: 2-3 Cars</li>
          </ul>
          <p><strong>Call-to-Action</strong></p>  
          <button type="button" class="btn" data-bs-toggle="modal" data-bs-target="#bookAppointmentModal">Book Appointment</button>
          <button type="button" class="btn ms-2" id="backToProperty">Back</button>
        </div>
        </div>
  </div>

  </div>

 </section>


<!--Offers Section-->
   <section class="container-fluid m-0" id="offers">
    <div class="container-sm text-center py-2">
        <h3 class="fw-medium text-shadow">Our Offers</h3>
        <p class="offers_text">At Primelot Realty, we provide spaces and opportunities that fit your lifestyle and events.
        <br>Choose from our range of offers below:</p>
    </div>

    <div class="container-sm">
        <div class="row">
            <div class="col-sm-4">
                <div class="card h-100 shadow-sm">
                    <i class="fas fa-home fa-3x mb-3"></i>
                    <h5 class="card-title fw-bold">Houses & Lots for Sale</h5>
                    <p class="card-text">Find your dream home or lot with Primelot Realty. We offer affordable and premium properties tailored to your needs.</p>
                </div>
            </div>

            <div class="col-sm-4">
                <div class="card h-100 shadow-sm">
                    <i class="fas fa-door-open fa-3x mb-3"></i>
                    <h5 class="card-title fw-bold">Houses & Lots for Rent</h5>
                    <p class="card-text">Looking to rent a house or lot? Primelot Realty has a wide range of rental options to suit your lifestyle and budget.</p>
                </div>
            </div>

            <div class="col-sm-4">
                <div class="card h-100 shadow-sm">
                    <i class="fas fa-swimming-pool fa-3x mb-3"></i>
                    <h5 class="card-title fw-bold">Event Spaces – Pool & Courts</h5>
                    <p class="card-text">Looking for a place to hold your next event? Primelot Realty has a wide range of event spaces to suit your needs.</p>
                </div>
            </div>
        </div>

        <div class="row justify-content-center">
            <div class="col-lg-12">
                <div class="text-center mt-5 mb-2">
                    <h5 class="fw-medium text-shadow">Frequently Asked Questions</h5>
                    <p class="lead text-white">Find answers to common questions about Primelot Realty</p>
                </div>
                <div class="accordion" id="faqAccordion">
                    <!-- FAQ Item 1 -->
                    <div class="accordion-item">
                        <h3 class="accordion-header" id="headingOne">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseOne" aria-expanded="false" aria-controls="collapseOne">
                                Why Appointment Booking Only?
                            </button>
                        </h3>
                        <div id="collapseOne" class="accordion-collapse collapse" aria-labelledby="headingOne" data-bs-parent="#faqAccordion">
                            <div class="accordion-body">
                                We value trust and safety at Primelot Realty. To strictly avoid scams — for both us and our clients — we do not allow direct online transactions. Instead, all bookings and purchases must begin with an appointment through our website. This ensures that we meet renters or clients face-to-face before any transaction is processed.
                            </div>
                        </div>
                    </div>
                    <!-- FAQ Item 2 -->
                    <div class="accordion-item">
                        <h3 class="accordion-header" id="headingTwo">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseTwo" aria-expanded="false" aria-controls="collapseTwo">
                                What Documents Do I Need for Property Viewing?
                            </button>
                        </h3>
                        <div id="collapseTwo" class="accordion-collapse collapse" aria-labelledby="headingTwo" data-bs-parent="#faqAccordion">
                            <div class="accordion-body">
                                Please bring a valid government-issued ID, proof of income or employment, and any specific requirements mentioned during your appointment booking. This helps us provide you with the most suitable property options during your visit.
                            </div>
                        </div>
                    </div>
                    <!-- FAQ Item 3 -->
                    <div class="accordion-item">
                        <h3 class="accordion-header" id="headingThree">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseThree" aria-expanded="false" aria-controls="collapseThree">
                                How Long Does the Appointment Process Take?
                            </button>
                        </h3>
                        <div id="collapseThree" class="accordion-collapse collapse" aria-labelledby="headingThree" data-bs-parent="#faqAccordion">
                            <div class="accordion-body">
                                Typical appointments last 30-45 minutes, including property viewing and initial consultation. If you're interested in multiple properties, we can extend the session or schedule follow-up appointments to ensure you have adequate time to make informed decisions.
                            </div>
                        </div>
                    </div>
                    <!-- FAQ Item 4 -->
                    <div class="accordion-item">
                        <h3 class="accordion-header" id="headingFour">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseFour" aria-expanded="false" aria-controls="collapseFour">
                                Can I Reschedule My Appointment?
                            </button>
                        </h3>
                        <div id="collapseFour" class="accordion-collapse collapse" aria-labelledby="headingFour" data-bs-parent="#faqAccordion">
                            <div class="accordion-body">
                                Yes, you can reschedule your appointment up to 24 hours in advance through our website or by calling our office directly. We understand that schedules can change and we're happy to accommodate your needs whenever possible.
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>
   </section>

<!--Awards Section-->
  <section class="container-fluid m-0" id="awards">
    <div class="container">
        <div class="row">
            <div class="col-md-6 first_awards_section">
                <h4 class="fw-bold text-shadow">Awards and Recognitions</h4>
                <p class="lead">"At <strong>Primelot Realty Subdivision</strong>, excellence is at the heart of everything we do. 
                Our passion for building communities, commitment to quality, and dedication to our clients have earned us recognition and trust in the real estate industry."</p>
                <div class="mt-1 text-left border-bottom p-1">
                    <h6 class="text-shadow d-flex align-items-end gap-2"><img class="img-fluid" src="Images/best_trophy.png" alt="Best Subdivision Development 2024"> Best Subdivision Development 2024</h6>
                    <p>Recognized for modern designs, sustainable planning, and creating communities built to last.</p>
                </div>
                <div class="mt-1 text-left border-bottom p-1">
                    <h6 class="text-shadow d-flex align-items-end gap-2"><img class="img-fluid" src="Images/trusted_trophy.png" alt="Best Subdivision Development 2024"> Trusted Real Estate Brand</h6>
                    <p>Awarded for integrity, transparency, and reliability in every client transaction.</p>
                </div>
                <div class="mt-1 text-left border-bottom p-1">
                    <h6 class="text-shadow d-flex align-items-end gap-2"><img class="img-fluid" src="Images/excellence_trophy.png" alt="Best Subdivision Development 2024"> Excellence in Customer Service</h6>
                    <p>Honored for delivering exceptional service and ensuring client satisfaction throughout the buying or renting process.</p>
                </div>
                <div class="mt-1 text-left border-bottom p-1">
                    <h6 class="text-shadow d-flex align-items-end gap-2"><img class="img-fluid" src="Images/top_choice.png" alt="Best Subdivision Development 2024"> Top Choice for Families Award</h6>
                    <p>Celebrated for providing safe, family-friendly environments with quality amenities and a welcoming community.</p>
                </div>
                <div class="mt-1 text-left border-bottom p-1">
                    <h6 class="text-shadow d-flex align-items-end gap-2"><img class="img-fluid" src="Images/innovation_trophy.png" alt="Best Subdivision Development 2024"> Innovation in Real Estate Development</h6>
                    <p>Acknowledged for introducing modern solutions, creative layouts, and forward-thinking designs that elevate community living.</p>
                </div>
            </div>

            <div class="col-md-6 second_awards_section d-flex justify-content-center">
                <img class="d-block img-fluid" src="Images/golden_trophy.png" alt="Awards">
            </div>
        </div>
    </div>
   </section>

<!--Footer Section-->
   <footer>
    <section class="container-fluid m-0 pt-4 pb-2">
            <div class="container-sm">
                <h5 class="text-center text-white text-shadow py-3">© 2023 Primelot Realty Subdivision. No copyright intended, for educational purposes only.</h5>
                <div class="row mt-2">
                    <div class="col-4">
                        <div class="d-flex flex-column text-left justify-content-center">
                            <h6>Image & Icon Credits</h6>
                            <ul class="list-unstyled d-flex flex-column text-left">
                                <li><a href="#" target="_blank">Pexels</a></li>
                                <li><a href="#" target="_blank">Pixabay</a></li>
                            </ul>
                        </div>
                    </div>

                    <div class="col-4">
                        <h6>You may contact us at:</h6>
                        <ul class="list-unstyled d-flex flex-column text-left">
                            <li><strong>Phone:</strong> (123) 456-7890</li>
                            <li><strong>Email:</strong> Primelot@gmail.com</li>
                            <li><strong>Address:</strong> Marilog District, Davao City, Philippines</li>
                        </ul>
                    </div>
                    <div class="col-4">
                        <h6>To Be Updated</h6>
                        <span>Follow Us On Our Social Media:</span>
                        <div class="d-flex gap-2 text-left">
                            <a href="https://www.facebook.com/jonymar.barrete.1" target="_blank"><i class="fa-brands fa-facebook d-block w-100 img-fluid"></i></a>
                            <a href="https://www.instagram.com/barrzzz69/" target="_blank"><i class="fa-brands fa-instagram d-block w-100 img-fluid"></i></a>
                            <a href="https://www.instagram.com/barrzzz69/" target="_blank"><i class="fa-brands fa-twitter d-block w-100 img-fluid"></i></a>
                            <a href="https://www.youtube.com/@JonymarBarrete" target="_blank"><i class="fa-brands fa-youtube d-block w-100 img-fluid"></i></a>
                            <a href="https://www.tiktok.com/@jonymarbarrete?_t=ZS-8zvl8vK64hT&_r=1" target="_blank"><i class="fa-brands fa-tiktok d-block w-100 img-fluid"></i></a>
                            <a href="https://www.tiktok.com/@jonymarbarrete?_t=ZS-8zvl8vK64hT&_r=1" target="_blank"><i class="fa-brands fa-linkedin d-block w-100 img-fluid"></i></a>
                        </div>
                    </div>
                </div>
            </div>
            <div class="container">
                <h6 class="text-center text-white text-shadow" style="letter-spacing: 2px;">Developed By : <a href="https://www.facebook.com/jonymar.barrete.1" target="_blank">Jony Mar Barrete</a></h6>
            </div>
        </section>
   </footer>

</div>

    <!-- Bootstrap JS -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
    
<script>
    // Wait for DOM to be fully loaded
    document.addEventListener('DOMContentLoaded', function() {
        // Get references to the elements
        const smallMansionsBtn = document.querySelector('.small_mansions');
        const bigMansionsBtn = document.querySelector('.big_mansions');
        const firstRow = document.querySelector('.first_row');
        const smallMansionsInfo = document.querySelector('.small_mansions-info');
        const bigMansionsInfo = document.querySelector('.big_mansions-info');
        const backBtn = document.getElementById('backToProperties');
        const backBtn2 = document.getElementById('backToProperty');

        // Add click event listener to the "More Info" button
        smallMansionsBtn.addEventListener('click', function() {
            // Hide the first row
            firstRow.style.display = 'none';

            // Show the small mansions info section
            smallMansionsInfo.style.display = 'block';
        });

        // Add click event listener to the "More Info" button
        bigMansionsBtn.addEventListener('click', function() {
            // Hide the first row
            firstRow.style.display = 'none';

            // Show the big mansions info section
            bigMansionsInfo.style.display = 'block';
        });

        // Add click event listener to the "Back" button
        backBtn.addEventListener('click', function() {
            // Show the first row
            firstRow.style.display = 'flex';

            // Hide the small mansions info section
            smallMansionsInfo.style.display = 'none';
        });

        backBtn2.addEventListener('click', function() {
            // Show the first row
            firstRow.style.display = 'flex';

            // Hide the small mansions info section
            bigMansionsInfo.style.display = 'none';
        });

        // Auto-hide success alert after 5 seconds
        const successAlert = document.getElementById('successAlert');
        if (successAlert) {
            setTimeout(function() {
                hideAlert('successAlert');
            }, 5000);
            
            // Close the modal if success alert exists (means appointment was booked)
            const modal = bootstrap.Modal.getInstance(document.getElementById('bookAppointmentModal'));
            if (modal) {
                modal.hide();
            }
            
            // Reset the form
            const form = document.getElementById('appointmentForm');
            if (form) {
                form.reset();
            }
            
            // Clear any error messages
            document.querySelectorAll('.error-message').forEach(function(element) {
                element.textContent = '';
            });
        }
        
        // Auto-hide error alert after 5 seconds  
        const errorAlert = document.getElementById('errorAlert');
        if (errorAlert) {
            setTimeout(function() {
                hideAlert('errorAlert');
            }, 5000);
        }
    });

        // Function to hide  with fade effect
    function hideAlert(alertId) {
        const alert = document.getElementById(alertId);
        if (alert) {
            const bsAlert = new bootstrap.Alert(alert);
            bsAlert.close();
        }
    }

    function deleteAppointment(appointmentId) {
        if (confirm("Are you sure you want to delete this appointment?")) {
            var form = document.createElement("form");
            form.method = "post";
            form.action = "user_page.php";

            var input1 = document.createElement("input");
            input1.type = "hidden";
            input1.name = "appointment_id";
            input1.value = appointmentId;
            form.appendChild(input1);

            var input2 = document.createElement("input");
            input2.type = "hidden";
            input2.name = "delete_appointment";
            input2.value = "true";
            form.appendChild(input2);

            document.body.appendChild(form);
            form.submit();
        }
    }

    const fullNameInput = document.getElementById('full_name');
    const contactNumberInput = document.getElementById('contact_number');
    const fullNameError = document.getElementById('full_name_error');
    const contactNumberError = document.getElementById('contact_number_error');

    fullNameInput.addEventListener('input', handleFullNameInput);
    contactNumberInput.addEventListener('input', handleContactNumberInput);

    fullNameInput.addEventListener('input', () => {
        const fullNameValue = fullNameInput.value.trim();
        if (!/^[a-zA-Z\s]+$/.test(fullNameValue)) {
            fullNameError.textContent = 'Full name must be a string only.';
        } else if (fullNameValue.split(' ').length < 2) {
            fullNameError.textContent = 'Full name must be at least two words (first name and last name).';
        } else {
            fullNameError.textContent = '';
        }
    });

    function handleFullNameInput() {
        const fullNameValue = fullNameInput.value.trim();
        if (!/^[a-zA-Z\s]+$/.test(fullNameValue)) {
            fullNameError.textContent = 'Full name must be a string only.';
        } else if (fullNameValue.split(' ').length < 2) {
            fullNameError.textContent = 'Full name must be at least two words (first name and last name).';
        } else {
            fullNameError.textContent = '';
        }
    }

// Updated contact number validation for user_page.php
let contactNumberTimeout;
contactNumberInput.addEventListener('input', () => {
    const contactNumberValue = contactNumberInput.value.trim();
    
    // Clear previous timeout
    clearTimeout(contactNumberTimeout);
    
    // Clear error message while typing
    contactNumberError.textContent = '';
    
    // Only validate after user stops typing for 1 second
    contactNumberTimeout = setTimeout(() => {
        if (contactNumberValue === '') {
            contactNumberError.textContent = '';
            return;
        }
        
        // Check if it's a valid Philippine mobile number (11 digits starting with 09)
        if (contactNumberValue.length === 11 && /^09\d{9}$/.test(contactNumberValue)) {
            contactNumberInput.value = '+63' + contactNumberValue.substring(1);
            contactNumberError.textContent = '';
        }
        // Check if it's already formatted Philippine mobile number (+63 followed by 10 digits)
        else if (contactNumberValue.length === 13 && /^\+63\d{10}$/.test(contactNumberValue)) {
            contactNumberError.textContent = '';
        }
        // Check if it's a landline number (7 digits)
        else if (contactNumberValue.length === 7 && /^\d{7}$/.test(contactNumberValue)) {
            contactNumberError.textContent = '';
        }
        // Check if user typed + in wrong place or mixed characters
        else if (contactNumberValue.includes('+') && !/^\+63\d{10}$/.test(contactNumberValue)) {
            contactNumberError.textContent = 'Invalid format. Do not manually add + symbol.';
        }
        // Check if it contains non-digits (except valid + format)
        else if (!/^[\d+]*$/.test(contactNumberValue)) {
            contactNumberError.textContent = 'Contact number must be numbers only.';
        }
        // Invalid length or format
        else {
            contactNumberError.textContent = 'Invalid contact number. Please enter a valid mobile or telephone number.';
        }
    }, 1000); // Wait 1 second after user stops typing
});


function handleContactNumberInput() {
    const contactNumberValue = contactNumberInput.value.trim();
    if (!/^\d+$/.test(contactNumberValue)) {
        contactNumberError.textContent = 'Contact number must be numbers only.';
    } else if (contactNumberValue.length === 11 && /^09\d{9}$/.test(contactNumberValue)) {
        contactNumberInput.value = '+63' + contactNumberValue.substring(1);
        contactNumberError.textContent = '';
    } else if (contactNumberValue.length === 7 && /^\d{7}$/.test(contactNumberValue)) {
        contactNumberError.textContent = '';
    } else {
        contactNumberError.textContent = 'Invalid contact number. Please enter a valid mobile or telephone number.';
    }
}

</script>
    
<script>
// Add form validation for Change Password
document.getElementById('changePasswordForm')?.addEventListener('submit', function(e) {
    const newPassword = document.getElementById('newPassword').value;
    const confirmPassword = document.getElementById('confirmPassword').value;
    
    if (newPassword !== confirmPassword) {
        e.preventDefault();
        alert('New password and confirm password do not match!');
        return false;
    }
});

// Add form validation for Contact Support
document.getElementById('contactSupportForm')?.addEventListener('submit', function(e) {
    const subject = document.getElementById('supportSubject').value;
    const message = document.getElementById('supportMessage').value;
    
    if (!subject) {
        e.preventDefault();
        alert('Please select a subject for your support request.');
        return false;
    }
    
    if (message.trim().length < 10) {
        e.preventDefault();
        alert('Please provide a more detailed message (at least 10 characters).');
        return false;
    }
});

// Add form validation for Delete Account
document.getElementById('deleteAccountForm')?.addEventListener('submit', function(e) {
    const password = document.getElementById('deletePassword').value;
    const confirmCheckbox = document.getElementById('confirmDelete').checked;
    
    if (!password) {
        e.preventDefault();
        alert('Please enter your password to confirm account deletion.');
        return false;
    }
    
    if (!confirmCheckbox) {
        e.preventDefault();
        alert('Please check the confirmation box to proceed with account deletion.');
        return false;
    }
    
    // Final confirmation
    const finalConfirm = confirm('Are you absolutely sure? This action CANNOT be undone. Your account and all data will be permanently deleted.');
    if (!finalConfirm) {
        e.preventDefault();
        return false;
    }
});

// Disable delete button until checkbox is checked
document.getElementById('confirmDelete')?.addEventListener('change', function() {
    const deleteBtn = document.getElementById('confirmDeleteBtn');
    if (this.checked) {
        deleteBtn.disabled = false;
    } else {
        deleteBtn.disabled = true;
    }
});

// Initialize delete button as disabled
window.addEventListener('DOMContentLoaded', function() {
    const deleteBtn = document.getElementById('confirmDeleteBtn');
    if (deleteBtn) {
        deleteBtn.disabled = true;
    }
});
</script>
    
</body>
</html>

