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

<div class="leave-request-page">
<section class="hero modern-hero leave-hero">
    <div class="hero-content">
        <div class="hero-header">
            <div class="header-badge leave-hero-badge">
                <i class="fas fa-calendar-alt leave-hero-badge-icon"></i>
            </div>
            <div>
                <h2 class="leave-hero-title">Leave Request</h2>
                <p class="leave-hero-subtitle">Submit your leave application</p>
            </div>
        </div>

        <p class="leave-hero-description">
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
        <div class="alert alert-danger">
            <?= htmlspecialchars($error) ?>
        </div>
    <?php endif; ?>
    
    <?php if (!empty($success)): ?>
        <div class="alert alert-success">
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

            <div class="form-group full-width consent-row">
                <label class="consent-check">
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
                    <td class="action-btns">
                        <button type="button" class="action-btn action-btn-view" aria-label="View request" title="View request">
                            <img src="https://img.icons8.com/?size=100&id=60022&format=png&color=000000" alt="" class="action-icon-view" aria-hidden="true">
                        </button>
                    </td>
                </tr>
                <tr>
                    <td>March 10, 2026</td>
                    <td>Sick Leave</td>
                    <td>Mar 12, 2026 (1 day)</td>
                    <td>Medical appointment</td>
                    <td><span class="status-badge approved">Approved</span></td>
                    <td class="action-btns">
                        <button type="button" class="action-btn action-btn-view" aria-label="View request" title="View request">
                            <img src="https://img.icons8.com/?size=100&id=60022&format=png&color=000000" alt="" class="action-icon-view" aria-hidden="true">
                        </button>
                    </td>
                </tr>
                <tr>
                    <td>February 28, 2026</td>
                    <td>Emergency Leave</td>
                    <td>Mar 1, 2026 (1 day)</td>
                    <td>Family emergency</td>
                    <td><span class="status-badge pending">Pending</span></td>
                    <td class="action-btns">
                        <button type="button" class="action-btn action-btn-view" aria-label="View request" title="View request">
                            <img src="https://img.icons8.com/?size=100&id=60022&format=png&color=000000" alt="" class="action-icon-view" aria-hidden="true">
                        </button>
                        <button type="button" class="action-btn action-btn-cancel" aria-label="Cancel request" title="Cancel request">
                            <img src="https://img.icons8.com/?size=100&id=T9nkeADgD3z6&format=png&color=000000" alt="" class="action-icon-cancel" aria-hidden="true">
                        </button>
                    </td>
                </tr>
                <tr>
                    <td>February 15, 2026</td>
                    <td>Vacation Leave</td>
                    <td>Feb 20-21, 2026 (2 days)</td>
                    <td>Personal matters</td>
                    <td><span class="status-badge rejected">Rejected</span></td>
                    <td class="action-btns">
                        <button type="button" class="action-btn action-btn-view" aria-label="View request" title="View request">
                            <img src="https://img.icons8.com/?size=100&id=60022&format=png&color=000000" alt="" class="action-icon-view" aria-hidden="true">
                        </button>
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
            <div class="policy-icon policy-icon-blue">
                <i class="fas fa-calendar-check"></i>
            </div>
            <h4>Vacation Leave</h4>
            <p>15 days per year for regular employees. Must be filed at least 5 working days in advance.</p>
        </div>
        <div class="policy-card">
            <div class="policy-icon policy-icon-red">
                <i class="fas fa-heartbeat"></i>
            </div>
            <h4>Sick Leave</h4>
            <p>10 days per year. Medical certificate required for 3+ consecutive days.</p>
        </div>
        <div class="policy-card">
            <div class="policy-icon policy-icon-gold">
                <i class="fas fa-star"></i>
            </div>
            <h4>Special Leave</h4>
            <p>3 days per year for special circumstances with proper documentation.</p>
        </div>
        <div class="policy-card">
            <div class="policy-icon policy-icon-violet">
                <i class="fas fa-info-circle"></i>
            </div>
            <h4>Processing Time</h4>
            <p>Leave requests are processed within 3-5 working days.</p>
        </div>
    </div>
</section>
</div>

<style>
.leave-request-page {
    display: grid;
    gap: 22px;
}

.leave-request-page .card {
    border: 1px solid rgba(228, 235, 243, 0.95);
    box-shadow: 0 14px 30px rgba(15, 35, 60, 0.08);
}

.leave-request-page .tag {
    letter-spacing: 0.08em;
}

.leave-hero-badge {
    background: linear-gradient(135deg, #1f9d57, #3acb7b);
    padding: 16px;
    border-radius: 16px;
    color: #fff;
    display: flex;
    align-items: center;
    justify-content: center;
    width: 62px;
    height: 62px;
    box-shadow: 0 14px 26px rgba(31, 157, 87, 0.34);
}

.leave-hero-badge-icon {
    font-size: 32px;
}

.leave-hero-title {
    font-size: clamp(28px, 4vw, 38px);
    font-weight: 800;
    color: var(--primary);
    margin: 0 0 8px 0;
    line-height: 1.15;
    letter-spacing: -0.015em;
}

.leave-hero-subtitle {
    color: var(--muted);
    font-size: 15px;
    margin: 0;
    font-weight: 600;
}

.leave-hero-description {
    color: #4d6178;
    line-height: 1.75;
    margin: 24px 0 12px 0;
    max-width: 650px;
    font-size: 15px;
}

.leave-balance-summary {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 14px;
}

.balance-card {
    text-align: center;
    padding: 18px 16px;
    background: linear-gradient(160deg, #f8fcff, #eef5fd);
    border: 1px solid rgba(151, 182, 217, 0.32);
    border-radius: 14px;
    border-left: 4px solid #27ae60;
}

.balance-card h4 {
    margin: 0 0 10px 0;
    color: #20324a;
    font-size: 14px;
}

.balance-amount {
    font-size: 30px;
    font-weight: 900;
    color: #1d9a54;
    margin: 0 0 4px 0;
    line-height: 1;
}

.balance-detail {
    color: #617a97;
    font-size: 12px;
    font-weight: 600;
}

.leave-request-page .alert {
    padding: 12px 14px;
    border-radius: 10px;
    margin-bottom: 18px;
    border: 1px solid transparent;
    font-weight: 600;
    font-size: 14px;
}

.leave-request-page .alert-danger {
    background: #fef1f2;
    color: #992f3b;
    border-color: #f4c1c8;
}

.leave-request-page .alert-success {
    background: #edf9f0;
    color: #1f6a3e;
    border-color: #bee6cb;
}

.leave-form {
    max-width: 920px;
    margin: 0 auto;
    background: linear-gradient(180deg, #ffffff, #f8fbff);
    border: 1px solid rgba(187, 207, 231, 0.62);
    border-radius: 18px;
    padding: 22px;
    box-shadow: 0 14px 30px rgba(15, 35, 60, 0.07);
}

.form-grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 20px;
    margin-bottom: 22px;
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
    font-weight: 800;
    color: #19395d;
    font-size: 12px;
    letter-spacing: 0.09em;
    text-transform: uppercase;
}

.form-group input,
.form-group select,
.form-group textarea {
    padding: 13px 14px;
    border: 1px solid #b7cbe3;
    border-radius: 13px;
    font-size: 14px;
    font-weight: 600;
    color: #1f3550;
    background: linear-gradient(180deg, #ffffff, #f6f9ff);
    box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.9);
    transition: border-color 140ms ease, box-shadow 140ms ease, background 140ms ease, transform 140ms ease;
}

.form-group input:hover,
.form-group select:hover,
.form-group textarea:hover {
    border-color: #92afcf;
}

.form-group input:focus,
.form-group select:focus,
.form-group textarea:focus {
    outline: none;
    border-color: rgba(15, 76, 129, 0.62);
    box-shadow: 0 0 0 4px rgba(15, 76, 129, 0.14), 0 8px 16px rgba(12, 38, 66, 0.08);
    background: #fff;
    transform: translateY(-1px);
}

.form-group textarea {
    resize: vertical;
    min-height: 136px;
}

.form-group input::placeholder,
.form-group textarea::placeholder {
    color: #6f7f92;
    font-weight: 600;
}

#days_count[readonly] {
    background: linear-gradient(180deg, #f4f8fd, #eef4fb);
    color: #3b5776;
    border-color: #b8cde4;
}

.consent-row {
    margin-top: 4px;
}

.consent-check {
    display: flex;
    align-items: center;
    gap: 10px;
    margin: 0;
    padding: 13px 14px;
    border: 1px dashed #aac3df;
    border-radius: 13px;
    background: linear-gradient(180deg, #f8fbff, #f3f8ff);
    font-weight: 700;
    color: #2f4c6d;
    letter-spacing: 0.015em;
}

.consent-check input[type="checkbox"] {
    width: 17px;
    height: 17px;
    accent-color: #0f4c81;
}

.form-actions {
    display: flex;
    gap: 12px;
    justify-content: flex-end;
    flex-wrap: wrap;
    padding-top: 6px;
}

.form-actions .btn {
    min-height: 48px;
    border-radius: 14px;
    padding-inline: 22px;
    font-weight: 800;
    font-size: 15px;
    letter-spacing: 0.01em;
}

.form-actions .btn-primary {
    background: linear-gradient(135deg, #1d6ab0, #16558d);
    border-color: #164f82;
    box-shadow: 0 12px 24px rgba(21, 85, 141, 0.24);
}

.form-actions .btn-primary:hover {
    filter: brightness(1.04);
}

.form-actions .btn-outline {
    border-color: #b4c6db;
    background: linear-gradient(180deg, #ffffff, #f4f8fd);
}

.form-actions .btn-outline:hover {
    border-color: #8da9c7;
    background: linear-gradient(180deg, #ffffff, #edf4fc);
}

.leave-history-table {
    overflow-x: auto;
    border-radius: 14px;
    border: 1px solid rgba(226, 235, 245, 0.95);
}

.data-table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 0;
    min-width: 760px;
}

.data-table th,
.data-table td {
    padding: 12px 14px;
    text-align: left;
    border-bottom: 1px solid #e8eff7;
    vertical-align: middle;
}

.data-table th {
    background: linear-gradient(180deg, #f6faff, #edf3fb);
    font-weight: 800;
    color: #304760;
    font-size: 12px;
    letter-spacing: 0.06em;
    text-transform: uppercase;
}

.data-table tbody tr:nth-child(even) {
    background: #fbfdff;
}

.data-table tbody tr:hover {
    background: #f1f7ff;
}

.status-badge {
    display: inline-flex;
    align-items: center;
    padding: 5px 11px;
    border-radius: 999px;
    font-size: 11px;
    font-weight: 800;
    letter-spacing: 0.05em;
    text-transform: uppercase;
}

.status-badge.approved {
    background: #e9f8ef;
    color: #177042;
}

.status-badge.pending {
    background: #fff7e3;
    color: #946400;
}

.status-badge.rejected {
    background: #feeef0;
    color: #9e3241;
}

.data-table .btn-sm {
    min-height: 32px;
    padding: 7px 11px;
    font-size: 12px;
    border-radius: 10px;
}

.action-btns {
    display: flex;
    align-items: center;
    gap: 8px;
}

.action-btn {
    width: 22px;
    min-width: 22px;
    height: 22px;
    min-height: 22px;
    padding: 0;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    border: 0;
    background: transparent;
    box-shadow: none;
    cursor: pointer;
    opacity: 0.85;
    transition: opacity 120ms ease, transform 120ms ease;
}

.action-btn i {
    font-size: 16px;
    line-height: 1;
}

.action-icon-view {
    width: 16px;
    height: 16px;
    display: block;
}

.action-icon-cancel {
    width: 16px;
    height: 16px;
    display: block;
}

.action-btn:hover {
    opacity: 1;
    transform: translateY(-1px);
}

.action-btn:focus-visible {
    outline: 2px solid rgba(15, 76, 129, 0.28);
    outline-offset: 2px;
    border-radius: 6px;
}

.policy-grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 16px;
    margin-top: 20px;
}

.policy-card {
    text-align: left;
    padding: 18px;
    background: linear-gradient(180deg, #fff, #f8fbff);
    border-radius: 14px;
    border: 1px solid rgba(202, 217, 235, 0.65);
    box-shadow: 0 10px 24px rgba(15, 35, 60, 0.06);
}

.policy-icon {
    width: 54px;
    height: 54px;
    border-radius: 14px;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 0 14px 0;
    color: #fff;
    font-size: 22px;
}

.policy-icon-blue {
    background: linear-gradient(135deg, #2f9ae4, #2b6fcf);
}

.policy-icon-red {
    background: linear-gradient(135deg, #ea5e5e, #cb3a3a);
}

.policy-icon-gold {
    background: linear-gradient(135deg, #f4b94f, #e58b2f);
}

.policy-icon-violet {
    background: linear-gradient(135deg, #8d6de9, #6552cf);
}

.policy-card h4 {
    margin: 0 0 8px 0;
    color: #2a425d;
    font-size: 16px;
}

.policy-card p {
    color: #60758f;
    font-size: 14px;
    line-height: 1.55;
}

@media (max-width: 1100px) {
    .leave-balance-summary {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }
}

@media (max-width: 880px) {
    .form-grid,
    .policy-grid,
    .leave-balance-summary {
        grid-template-columns: 1fr;
    }

    .leave-form {
        max-width: 100%;
        padding: 16px;
    }

    .form-actions {
        justify-content: stretch;
    }

    .form-actions .btn {
        width: 100%;
    }
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
