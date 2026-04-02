<?php
$pageTitle = 'Leave Request';
$activePage = 'leave-request.php';
require_once 'includes/auth.php';
require_roles(['employee', 'hr_officer', 'admin', 'superadmin']);
require_once 'includes/data.php';
require_once 'includes/notifications.php';

$currentUser = current_user();
$userName = $currentUser['display_name'] ?? 'Employee';
$error = '';
$success = '';

// Handle success messages from redirect
if (isset($_GET['success']) && $_GET['success'] === 'submitted') {
    $success = 'Leave request submitted successfully!';
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verify CSRF token
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $error = 'Security token expired. Please try again.';
    } else {
        $leave_type = $_POST['leave_type'] ?? '';
        $start_date = $_POST['start_date'] ?? '';
        $end_date = $_POST['end_date'] ?? '';
        $reason = $_POST['reason'] ?? '';
        
        // Validate required fields
        if (empty($leave_type) || empty($start_date) || empty($end_date) || empty($reason)) {
            $error = 'All fields are required.';
        } elseif (strtotime($start_date) > strtotime($end_date)) {
            $error = 'Start date cannot be after end date.';
        } else {
            // Here you would typically save to database
            create_notification(
                (int) ($currentUser['id'] ?? 0),
                'leave_request',
                'Leave request submitted',
                'Your leave request for ' . ucwords((string) $leave_type) . ' leave is pending review.',
                'leave-request.php'
            );
            create_notification_for_roles(
                ['admin', 'hr_officer', 'superadmin'],
                'leave_request',
                $userName . ' submitted a leave request',
                'Review the new leave request from ' . $userName . '.',
                'leave-request.php'
            );
            
            // Redirect to prevent form resubmission
            header('Location: leave-request.php?success=submitted');
            exit;
        }
    }
}

require_once 'includes/header.php';
?>

<section class="hero modern-hero">
    <div class="hero-content">
        <div class="hero-header">
            <div class="header-badge" style="background: linear-gradient(135deg, #27ae60, #2ecc71); padding: 16px; border-radius: 12px; color: white; display: flex; align-items: center; justify-content: center; width: 60px; height: 60px;">
                <i class="fas fa-calendar-alt" style="font-size: 32px;"></i>
            </div>
            <div>
                <h2 style="font-size: 36px; font-weight: 700; color: var(--primary); margin: 0 0 8px 0; line-height: 1.2;">Leave Request</h2>
                <p style="color: var(--muted); font-size: 15px; margin: 0;">Submit your leave application</p>
            </div>
        </div>

        <p style="color: var(--muted); line-height: 1.8; margin: 24px 0 28px 0; max-width: 650px; font-size: 15px;">
            Apply for different types of leave including vacation, sick leave, and special leave benefits. Track your leave balance and view the status of your applications.
        </p>
    </div>

    <div class="hero-panel modern-panel">
        <div class="leave-balance-summary">
            <div class="balance-card">
                <h4>Vacation Leave</h4>
                <div class="balance-amount">12 days</div>
                <div class="balance-detail">Remaining out of 15</div>
            </div>
            <div class="balance-card">
                <h4>Sick Leave</h4>
                <div class="balance-amount">5 days</div>
                <div class="balance-detail">Remaining out of 10</div>
            </div>
            <div class="balance-card">
                <h4>Special Leave</h4>
                <div class="balance-amount">3 days</div>
                <div class="balance-detail">Remaining out of 3</div>
            </div>
        </div>
    </div>
</section>

<!-- LEAVE APPLICATION FORM -->
<section class="card">
    <div class="section-head">
        <div>
            <span class="tag">New Application</span>
            <h3>Leave Application Form</h3>
        </div>
    </div>

    <?php if (!empty($error)): ?>
        <div class="alert" style="background: #f8d7da; color: #721c24; padding: 12px; border-radius: 6px; margin-bottom: 20px; border: 1px solid #f5c6cb;">
            <?= htmlspecialchars($error) ?>
        </div>
    <?php endif; ?>
    
    <?php if (!empty($success)): ?>
        <div class="alert" style="background: #d4edda; color: #155724; padding: 12px; border-radius: 6px; margin-bottom: 20px; border: 1px solid #c3e6cb;">
            <?= htmlspecialchars($success) ?>
        </div>
    <?php endif; ?>

    <form class="leave-form" method="post" action="leave-request.php">
        <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
        <div class="form-grid">
            <div class="form-group">
                <label for="leave_type">Leave Type</label>
                <select id="leave_type" name="leave_type" required>
                    <option value="">Select leave type</option>
                    <option value="vacation">Vacation Leave</option>
                    <option value="sick">Sick Leave</option>
                    <option value="special">Special Leave Benefits</option>
                    <option value="maternity">Maternity Leave</option>
                    <option value="paternity">Paternity Leave</option>
                    <option value="emergency">Emergency Leave</option>
                </select>
            </div>

            <div class="form-group">
                <label for="start_date">Start Date</label>
                <input type="date" id="start_date" name="start_date" required>
            </div>

            <div class="form-group">
                <label for="end_date">End Date</label>
                <input type="date" id="end_date" name="end_date" required>
            </div>

            <div class="form-group">
                <label for="days_count">Number of Days</label>
                <input type="number" id="days_count" name="days_count" min="1" readonly>
            </div>

            <div class="form-group full-width">
                <label for="reason">Reason for Leave</label>
                <textarea id="reason" name="reason" rows="4" placeholder="Please provide a detailed reason for your leave request..." required></textarea>
            </div>

            <div class="form-group full-width">
                <label for="relief_person">Relief Officer/Person to Cover Duties</label>
                <input type="text" id="relief_person" name="relief_person" placeholder="Name of person who will cover your duties">
            </div>

            <div class="form-group full-width">
                <label for="contact_info">Contact Information During Leave</label>
                <input type="text" id="contact_info" name="contact_info" placeholder="Phone number or email where you can be reached">
            </div>

            <div class="form-group full-width">
                <label>
                    <input type="checkbox" name="certification" required>
                    I certify that the information provided is true and correct
                </label>
            </div>
        </div>

        <div class="form-actions">
            <button type="submit" class="btn btn-primary">Submit Leave Request</button>
            <button type="button" class="btn btn-outline" onclick="window.history.back()">Cancel</button>
        </div>
    </form>
</section>

<!-- LEAVE HISTORY -->
<section class="card">
    <div class="section-head">
        <div>
            <span class="tag">Leave History</span>
            <h3>Your Recent Leave Applications</h3>
        </div>
    </div>

    <div class="leave-history-table">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Application Date</th>
                    <th>Leave Type</th>
                    <th>Duration</th>
                    <th>Reason</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>March 20, 2026</td>
                    <td>Vacation Leave</td>
                    <td>Mar 25-27, 2026 (3 days)</td>
                    <td>Family vacation</td>
                    <td><span class="status-badge approved">Approved</span></td>
                    <td>
                        <button class="btn btn-sm btn-outline">View</button>
                    </td>
                </tr>
                <tr>
                    <td>March 10, 2026</td>
                    <td>Sick Leave</td>
                    <td>Mar 12, 2026 (1 day)</td>
                    <td>Medical appointment</td>
                    <td><span class="status-badge approved">Approved</span></td>
                    <td>
                        <button class="btn btn-sm btn-outline">View</button>
                    </td>
                </tr>
                <tr>
                    <td>February 28, 2026</td>
                    <td>Emergency Leave</td>
                    <td>Mar 1, 2026 (1 day)</td>
                    <td>Family emergency</td>
                    <td><span class="status-badge pending">Pending</span></td>
                    <td>
                        <button class="btn btn-sm btn-outline">View</button>
                        <button class="btn btn-sm btn-warning">Cancel</button>
                    </td>
                </tr>
                <tr>
                    <td>February 15, 2026</td>
                    <td>Vacation Leave</td>
                    <td>Feb 20-21, 2026 (2 days)</td>
                    <td>Personal matters</td>
                    <td><span class="status-badge rejected">Rejected</span></td>
                    <td>
                        <button class="btn btn-sm btn-outline">View</button>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</section>

<!-- LEAVE POLICIES -->
<section class="card">
    <div class="section-head">
        <div>
            <span class="tag">Information</span>
            <h3>Leave Policies & Guidelines</h3>
        </div>
    </div>

    <div class="policy-grid">
        <div class="policy-card">
            <div class="policy-icon" style="background: linear-gradient(135deg, #3498db, #2980b9);">
                <i class="fas fa-calendar-check"></i>
            </div>
            <h4>Vacation Leave</h4>
            <p>15 days per year for regular employees. Must be filed at least 5 working days in advance.</p>
        </div>
        <div class="policy-card">
            <div class="policy-icon" style="background: linear-gradient(135deg, #e74c3c, #c0392b);">
                <i class="fas fa-heartbeat"></i>
            </div>
            <h4>Sick Leave</h4>
            <p>10 days per year. Medical certificate required for 3+ consecutive days.</p>
        </div>
        <div class="policy-card">
            <div class="policy-icon" style="background: linear-gradient(135deg, #f39c12, #e67e22);">
                <i class="fas fa-star"></i>
            </div>
            <h4>Special Leave</h4>
            <p>3 days per year for special circumstances with proper documentation.</p>
        </div>
        <div class="policy-card">
            <div class="policy-icon" style="background: linear-gradient(135deg, #9b59b6, #8e44ad);">
                <i class="fas fa-info-circle"></i>
            </div>
            <h4>Processing Time</h4>
            <p>Leave requests are processed within 3-5 working days.</p>
        </div>
    </div>
</section>

<style>
.leave-balance-summary {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 20px;
}

.balance-card {
    text-align: center;
    padding: 20px;
    background: linear-gradient(135deg, #f8f9fa, #e9ecef);
    border-radius: 12px;
    border-left: 4px solid #27ae60;
}

.balance-card h4 {
    margin: 0 0 10px 0;
    color: #2c3e50;
}

.balance-amount {
    font-size: 32px;
    font-weight: bold;
    color: #27ae60;
    margin: 0 0 5px 0;
}

.balance-detail {
    color: #7f8c8d;
    font-size: 14px;
}

.leave-form {
    max-width: 800px;
    margin: 0 auto;
}

.form-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 20px;
    margin-bottom: 30px;
}

.form-group {
    display: flex;
    flex-direction: column;
}

.form-group.full-width {
    grid-column: 1 / -1;
}

.form-group label {
    margin-bottom: 8px;
    font-weight: 600;
    color: #2c3e50;
}

.form-group input,
.form-group select,
.form-group textarea {
    padding: 12px;
    border: 1px solid #ddd;
    border-radius: 6px;
    font-size: 14px;
}

.form-group input:focus,
.form-group select:focus,
.form-group textarea:focus {
    outline: none;
    border-color: #3498db;
    box-shadow: 0 0 0 2px rgba(52, 152, 219, 0.2);
}

.form-actions {
    display: flex;
    gap: 15px;
    justify-content: center;
}

.leave-history-table {
    overflow-x: auto;
}

.data-table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 20px;
}

.data-table th,
.data-table td {
    padding: 12px;
    text-align: left;
    border-bottom: 1px solid #ddd;
}

.data-table th {
    background: #f8f9fa;
    font-weight: 600;
    color: #2c3e50;
}

.status-badge {
    padding: 4px 12px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 600;
}

.status-badge.approved {
    background: #d4edda;
    color: #155724;
}

.status-badge.pending {
    background: #fff3cd;
    color: #856404;
}

.status-badge.rejected {
    background: #f8d7da;
    color: #721c24;
}

.policy-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 20px;
    margin-top: 20px;
}

.policy-card {
    text-align: center;
    padding: 25px;
    background: #f8f9fa;
    border-radius: 12px;
}

.policy-icon {
    width: 60px;
    height: 60px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 15px auto;
    color: white;
    font-size: 24px;
}

.policy-card h4 {
    margin: 0 0 10px 0;
    color: #2c3e50;
}

.policy-card p {
    color: #7f8c8d;
    font-size: 14px;
    line-height: 1.5;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const startDate = document.getElementById('start_date');
    const endDate = document.getElementById('end_date');
    const daysCount = document.getElementById('days_count');

    function calculateDays() {
        if (startDate.value && endDate.value) {
            const start = new Date(startDate.value);
            const end = new Date(endDate.value);
            
            if (end >= start) {
                const diffTime = Math.abs(end - start);
                const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24)) + 1;
                daysCount.value = diffDays;
            } else {
                daysCount.value = '';
            }
        }
    }

    startDate.addEventListener('change', calculateDays);
    endDate.addEventListener('change', calculateDays);
});
</script>

<?php require_once 'includes/footer.php'; ?>
