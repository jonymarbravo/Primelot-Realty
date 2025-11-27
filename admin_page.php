<?php 
session_start();

// Enhanced session validation for admin
function validateAdminSession() {
    if (!isset($_SESSION['email']) || !isset($_SESSION['role']) || !isset($_SESSION['user_id']) || !isset($_SESSION['login_time'])) {
        return false;
    }
    
    if ($_SESSION['role'] !== 'admin') {
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

if (!validateAdminSession()) {
    header("Location: index.php");
    exit();
}

require_once 'config.php';

// Handle appointment deletion
if (isset($_GET['delete_appointment']) && isset($_GET['appointment_id'])) {
    $appointment_id = intval($_GET['appointment_id']);
    
    // Start transaction for data integrity
    $conn->begin_transaction();
    
    try {
        // Delete from appointments table
        $delete_query = "DELETE FROM appointments WHERE id = ?";
        $delete_stmt = $conn->prepare($delete_query);
        $delete_stmt->bind_param("i", $appointment_id);
        
        if ($delete_stmt->execute()) {
            $conn->commit();
            $_SESSION['success_message'] = "Appointment deleted successfully!";
            $_SESSION['show_appointments'] = true; // Add this to stay on appointments view
        } else {
            throw new Exception("Failed to delete appointment");
        }
        
    } catch (Exception $e) {
        $conn->rollback();
        $_SESSION['error_message'] = "Error deleting appointment: " . $e->getMessage();
        $_SESSION['show_appointments'] = true; // Stay on appointments view even for errors
    }
    
    // Redirect to prevent resubmission
    header("Location: admin_page.php");
    exit();
}

// Get appointments data
$appointments = $conn->query("SELECT a.*, DATE_FORMAT(a.appointment_time, '%h:%i %p') as formatted_time, DATE_FORMAT(a.created_at, '%Y-%m-%d %h:%i %p') as formatted_created_at, u.name as user_name, u.email FROM appointments a JOIN users u ON a.user_id = u.id ORDER BY a.created_at DESC");

// Get statistics data for current month
$current_month = date('Y-m');
$current_year = date('Y');

// Get monthly appointment statistics for the current year
$stats_query = "
    SELECT 
        MONTH(appointment_date) as month,
        MONTHNAME(appointment_date) as month_name,
        COUNT(*) as total_appointments
    FROM appointments 
    WHERE YEAR(appointment_date) = ? 
    GROUP BY MONTH(appointment_date), MONTHNAME(appointment_date)
    ORDER BY MONTH(appointment_date)
";

$stats_stmt = $conn->prepare($stats_query);
$stats_stmt->bind_param("i", $current_year);
$stats_stmt->execute();
$stats_result = $stats_stmt->get_result();

$monthly_stats = [];
while ($row = $stats_result->fetch_assoc()) {
    $monthly_stats[] = $row;
}

// Get total appointments for current month
$current_month_query = "SELECT COUNT(*) as current_month_total FROM appointments WHERE DATE_FORMAT(appointment_date, '%Y-%m') = ?";
$current_month_stmt = $conn->prepare($current_month_query);
$current_month_stmt->bind_param("s", $current_month);
$current_month_stmt->execute();
$current_month_result = $current_month_stmt->get_result();
$current_month_data = $current_month_result->fetch_assoc();

// Get total appointments overall
$total_appointments_query = "SELECT COUNT(*) as total FROM appointments";
$total_result = $conn->query($total_appointments_query);
$total_appointments = $total_result->fetch_assoc()['total'];

// Get average appointments per month
$avg_query = "SELECT AVG(monthly_count) as avg_monthly FROM (SELECT COUNT(*) as monthly_count FROM appointments GROUP BY YEAR(appointment_date), MONTH(appointment_date)) as monthly_totals";
$avg_result = $conn->query($avg_query);
$avg_appointments = round($avg_result->fetch_assoc()['avg_monthly'], 1);

// Check if we should show appointments after deletion
$show_appointments = isset($_SESSION['show_appointments']);
if ($show_appointments) {
    unset($_SESSION['show_appointments']); // Clear the flag
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.2.0/css/all.min.css">
  <link rel="stylesheet" href="admin_and_users_css/admin_page.css" />
  <link href="https://fonts.cdnfonts.com/css/poppins" rel="stylesheet">
  <title>Admin Page | Primelot Realty</title>
  <link rel="icon" type="image/svg+xml" href="Images/company_brand.svg">
  <link rel="icon" type="image/png" href="Images/company_brand.svg">
  <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/3.9.1/chart.min.js"></script>
</head>
<body>

<!-- Session validation check in JavaScript -->
<script>
// Check if session is still valid every 30 seconds
setInterval(function() {
    fetch('check_session.php')
    .then(response => response.json())
    .then(data => {
        if (!data.valid || data.role !== 'admin') {
            alert('Your session has expired. Please log in again.');
            window.location.href = 'index.php';
        }
    })
    .catch(error => {
        console.log('Session check failed:', error);
    });
}, 30000); // Check every 30 seconds
</script>

<!-- Logout Button Fixed Position -->
<div class="logout-btn-container">
    <button type="button" class="logout-btn" data-bs-toggle="modal" data-bs-target="#logoutModal" title="Logout">
        <i class="fas fa-sign-out-alt"></i>
    </button>
</div>

<!-- Logout Confirmation Modal -->
<div class="modal fade" id="logoutModal" tabindex="-1" aria-labelledby="logoutModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header bg-danger text-white">
        <h5 class="modal-title" id="logoutModalLabel">
            <i class="fas fa-exclamation-triangle me-2"></i>Confirm Logout
        </h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body text-center py-4">
        <i class="fas fa-question-circle text-warning mb-3" style="font-size: 3rem;"></i>
        <h6 class="mb-3">Are you sure you want to logout?</h6>
        <p class="text-muted mb-0">You will need to log in again to access the admin dashboard.</p>
      </div>
      <div class="modal-footer justify-content-center">
        <button type="button" class="btn btn-secondary px-4" data-bs-dismiss="modal">
            <i class="fas fa-times me-1"></i>Cancel
        </button>
        <a href="logout.php" class="btn btn-danger px-4">
            <i class="fas fa-sign-out-alt me-1"></i>Logout
        </a>
      </div>
    </div>
  </div>
</div>

<!-- Success/Error Messages -->
<?php if (isset($_SESSION['success_message'])): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="fas fa-check-circle"></i> <?= $_SESSION['success_message'] ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php unset($_SESSION['success_message']); ?>
<?php endif; ?>

<?php if (isset($_SESSION['error_message'])): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="fas fa-exclamation-circle"></i> <?= $_SESSION['error_message'] ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php unset($_SESSION['error_message']); ?>
<?php endif; ?>

<div class="main_container container-fluid border">

    <div class="page-header border-bottom">
        <h1 class="text-center text-shadow">Admin Dashboard</h1>
    </div>

    <div class="buttons_container container">
        <div class="appointment_link_btn w-100 <?php echo $show_appointments ? 'active' : ''; ?>">
            <a id="appointmentsButton" href="#">Appointments</a>
        </div>
        <div class="statistic_link_btn w-100">
            <a id="statisticsButton" href="#">Statistics</a>
        </div>
    </div>

    <div class="admin_contents container-fluid">

        <div class="welcoming_txt <?php echo !$show_appointments ? 'active' : ''; ?> w-100">
            <h1 class="text-center text-shadow">WELCOME TO PRIMELOT REALTY ADMIN DASHBOARD</h1>
        </div>

        <div class="appointments_container container-fluid" <?php echo $show_appointments ? 'style="display: block;"' : ''; ?>>
            <div class="table-responsive-sm">
                <table class="table table-bordered text-center align-middle">
                    <thead>
                        <tr class="text-center align-middle text-shadow table-dark">
                            <th>User Name</th>
                            <th>Email</th>
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
                        <?php if ($appointments->num_rows > 0): ?>
                            <?php while($row = $appointments->fetch_assoc()): ?>
                                <tr class="text-center table-info">
                                    <td><?= htmlspecialchars($row['user_name']) ?></td>
                                    <td><?= htmlspecialchars($row['email']) ?></td>
                                    <td><?= htmlspecialchars($row['full_name']) ?></td>
                                    <td><?= htmlspecialchars($row['age']) ?></td>
                                    <td><?= htmlspecialchars($row['gender']) ?></td>
                                    <td><?= htmlspecialchars($row['contact_number']) ?></td>
                                    <td><?= htmlspecialchars($row['appointment_date']) ?></td>
                                    <td><?= htmlspecialchars($row['formatted_time']) ?></td>
                                    <td><?= htmlspecialchars($row['formatted_created_at']) ?></td>
                                    <td>
                                        <button class="btn-sm text-shadow delete-btn" onclick="deleteAppointment(<?= $row['id'] ?>)">
                                            <i class="fa-solid fa-trash-can"></i>
                                        </button>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="10" class="text-center">No appointments found</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="statistics_container container">
            <div class="row">
                <div class="col-12 mb-4">
                    <h2 class="text-center text-shadow">Appointment Statistics - <?= date('Y') ?></h2>
                </div>
            </div>
            
            <!-- Statistics Cards -->
            <div class="row mb-4">
                <div class="col-lg-4 col-md-6 mb-3">
                    <div class="card text-center stats-card">
                        <div class="card-body">
                            <h5 class="card-title">This Month</h5>
                            <h2 class="card-text text-primary"><?= $current_month_data['current_month_total'] ?></h2>
                            <p class="card-text">Appointments in <?= date('F Y') ?></p>
                        </div>
                    </div>
                </div>
                <div class="col-lg-4 col-md-6 mb-3">
                    <div class="card text-center stats-card">
                        <div class="card-body">
                            <h5 class="card-title">Total Appointments</h5>
                            <h2 class="card-text text-success"><?= $total_appointments ?></h2>
                            <p class="card-text">All time appointments</p>
                        </div>
                    </div>
                </div>
                <div class="col-lg-4 col-md-12 mb-3">
                    <div class="card text-center stats-card">
                        <div class="card-body">
                            <h5 class="card-title">Monthly Average</h5>
                            <h2 class="card-text text-info"><?= $avg_appointments ?></h2>
                            <p class="card-text">Average per month</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Chart Container -->
            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0 text-center">Monthly Appointment Trends - <?= date('Y') ?></h5>
                        </div>
                        <div class="card-body p-2">
                            <div class="chart-container">
                                <canvas id="appointmentsChart" width="400" height="200"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>

</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>

<script>
    // Check if we should show appointments immediately after page load (for post-deletion)
    const shouldShowAppointments = <?= $show_appointments ? 'true' : 'false' ?>;
    
    const welcomingTxt = document.querySelector('.welcoming_txt');
    const appointmentsButton = document.getElementById('appointmentsButton');
    const statisticsButton = document.getElementById('statisticsButton');
    const appointmentDiv = document.querySelector('.appointment_link_btn');
    const statisticDiv = document.querySelector('.statistic_link_btn');
    const appointmentsContainer = document.querySelector('.appointments_container');
    const statisticsContainer = document.querySelector('.statistics_container');

    // Function to show appointments
    const showAppointments = () => {
        appointmentsContainer.style.display = 'block';
        welcomingTxt.style.display = 'none';
        statisticsContainer.style.display = 'none';
        
        // Remove active class from statistic button and add to appointment button
        statisticDiv.classList.remove('active');
        appointmentDiv.classList.add('active');
    };

    // Function to show statistics
    const showStatistics = () => {
        welcomingTxt.style.display = 'none';
        appointmentsContainer.style.display = 'none';
        statisticsContainer.style.display = 'block';
        
        // Remove active class from appointment button and add to statistic button
        appointmentDiv.classList.remove('active');
        statisticDiv.classList.add('active');
        
        // Initialize chart when statistics are shown
        if (!window.chartInitialized) {
            initializeChart();
            window.chartInitialized = true;
        }
    };

    // Show appointments immediately if flag is set
    if (shouldShowAppointments) {
        showAppointments();
    }

    // Add event listeners to both the buttons and their parent divs
    appointmentsButton.addEventListener('click', (e) => {
        e.preventDefault();
        showAppointments();
    });

    appointmentDiv.addEventListener('click', (e) => {
        e.preventDefault();
        showAppointments();
    });

    statisticsButton.addEventListener('click', (e) => {
        e.preventDefault();
        showStatistics();
    });

    statisticDiv.addEventListener('click', (e) => {
        e.preventDefault();
        showStatistics();
    });

    // Delete appointment function
    const deleteAppointment = (id) => {
        if (confirm("Are you sure you want to delete this appointment? This action cannot be undone.")) {
            // Show loading state
            const deleteBtn = event.target.closest('.delete-btn');
            const originalContent = deleteBtn.innerHTML;
            deleteBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
            deleteBtn.disabled = true;
            
            // Redirect to delete
            window.location.href = "admin_page.php?appointment_id=" + id + "&delete_appointment=true";
        }
    }

    // Initialize Chart
    function initializeChart() {
        const ctx = document.getElementById('appointmentsChart').getContext('2d');
        
        // Prepare data from PHP
        const monthlyData = <?= json_encode($monthly_stats) ?>;
        
        // Create arrays for all months
        const allMonths = ['January', 'February', 'March', 'April', 'May', 'June', 
                          'July', 'August', 'September', 'October', 'November', 'December'];
        
        const chartData = allMonths.map(month => {
            const found = monthlyData.find(data => data.month_name === month);
            return found ? found.total_appointments : 0;
        });

        const currentMonth = new Date().getMonth();
        const backgroundColors = allMonths.map((month, index) => 
            index === currentMonth ? 'rgba(54, 162, 235, 0.8)' : 'rgba(54, 162, 235, 0.5)'
        );

        window.appointmentsChart = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: allMonths,
                datasets: [{
                    label: 'Number of Appointments',
                    data: chartData,
                    backgroundColor: backgroundColors,
                    borderColor: 'rgba(54, 162, 235, 1)',
                    borderWidth: 2,
                    borderRadius: 5,
                    borderSkipped: false,
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    title: {
                        display: false
                    },
                    legend: {
                        display: false
                    },
                    tooltip: {
                        callbacks: {
                            title: function(context) {
                                return context[0].label + ' <?= date('Y') ?>';
                            },
                            label: function(context) {
                                return 'Appointments: ' + context.parsed.y;
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            stepSize: 1,
                            callback: function(value) {
                                return Number.isInteger(value) ? value : '';
                            }
                        },
                        title: {
                            display: true,
                            text: 'Number of Appointments'
                        }
                    },
                    x: {
                        title: {
                            display: true,
                            text: 'Months'
                        },
                        ticks: {
                            maxRotation: 45,
                            minRotation: 0
                        }
                    }
                },
                animation: {
                    duration: 1000,
                    easing: 'easeInOutQuart'
                }
            }
        });
    }

    // Auto-dismiss alerts after 5 seconds
    setTimeout(() => {
        const alerts = document.querySelectorAll('.alert');
        alerts.forEach(alert => {
            const bsAlert = new bootstrap.Alert(alert);
            bsAlert.close();
        });
    }, 5000);
</script>

</body>
</html>