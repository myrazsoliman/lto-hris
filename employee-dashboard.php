<?php
$pageTitle = 'Employee Dashboard';
$activePage = 'employee-dashboard.php';
require_once 'includes/auth.php';

// Only check if user is logged in, no role restrictions
if (!is_logged_in()) {
    header('Location: index.php?login=1&message=Please login to access the dashboard');
    exit;
}

require_once 'includes/header.php';
require_once 'includes/data.php';

// Get current user information
$currentUser = current_user();
$userName = $currentUser['display_name'] ?? 'Employee';
?>

<section class="hero modern-hero">
    <div class="hero-content">
        <div class="hero-header">
            <div class="header-badge" style="background: linear-gradient(135deg, #16a085, #1abc9c); padding: 16px; border-radius: 12px; color: white; display: flex; align-items: center; justify-content: center; width: 60px; height: 60px;">
                <i class="fas fa-user" style="font-size: 32px;"></i>
            </div>
            <div>
                <h2 style="font-size: 36px; font-weight: 700; color: var(--primary); margin: 0 0 8px 0; line-height: 1.2;">Welcome, <?php echo htmlspecialchars($userName); ?></h2>
                <p style="color: var(--muted); font-size: 15px; margin: 0;">Your Personal HR Portal</p>
            </div>
        </div>

        <p style="color: var(--muted); line-height: 1.8; margin: 24px 0 28px 0; max-width: 650px; font-size: 15px;">
            Access your personal information, manage leave requests, view compliance requirements, and stay updated with company announcements through your self-service employee portal.
        </p>

        <div class="quick-actions">
            <a href="documents.php" class="quick-action-card quick-action-teal">
                <div class="action-icon" style="background: #16a085; color: white;"><i class="fas fa-folder-open"></i></div>
                <div class="action-content">
                    <h4>My Documents</h4>
                    <p>Manage personal files</p>
                </div>
                <div class="action-arrow">→</div>
            </a>
            <a href="pds.php" class="quick-action-card quick-action-blue">
                <div class="action-icon" style="background: #2196f3; color: white;"><i class="fas fa-file-alt"></i></div>
                <div class="action-content">
                    <h4>My PDS</h4>
                    <p>Personnel Data Sheet</p>
                </div>
                <div class="action-arrow">→</div>
            </a>
            <a href="leave-request.php" class="quick-action-card quick-action-green">
                <div class="action-icon" style="background: #27ae60; color: white;"><i class="fas fa-calendar-check"></i></div>
                <div class="action-content">
                    <h4>Leave Request</h4>
                    <p>Apply for leave</p>
                </div>
                <div class="action-arrow">→</div>
            </a>
            <a href="saln.php" class="quick-action-card quick-action-orange">
                <div class="action-icon" style="background: #f39c12; color: white;"><i class="fas fa-balance-scale"></i></div>
                <div class="action-content">
                    <h4>SALN Filing</h4>
                    <p>Annual declaration</p>
                </div>
                <div class="action-arrow">→</div>
            </a>
        </div>
    </div>

    <div class="hero-panel modern-panel">
        <div class="stat-widget" style="border-top: 4px solid #16a085;">
            <div class="stat-header" style="background: linear-gradient(135deg, #16a085, #1abc9c);">
                <span class="stat-icon"><i class="fas fa-user-check"></i></span>
                <h4>Profile Status</h4>
            </div>
            <div class="stat-body">
                <p class="stat-number" style="color: #16a085;">Complete</p>
                <p class="stat-label">Your profile is up to date</p>
                <div style="margin-top: 12px; background: #e8f6f3; height: 4px; border-radius: 2px; overflow: hidden;">
                    <div style="width: 100%; height: 100%; background: linear-gradient(90deg, #16a085, #1abc9c); border-radius: 2px;"></div>
                </div>
            </div>
        </div>

        <div class="stat-widget" style="border-top: 4px solid #27ae60;">
            <div class="stat-header" style="background: linear-gradient(135deg, #27ae60, #2ecc71);">
                <span class="stat-icon"><i class="fas fa-calendar-alt"></i></span>
                <h4>Leave Balance</h4>
            </div>
            <div class="stat-body">
                <p class="stat-number" style="color: #27ae60;">12 days</p>
                <p class="stat-label">Vacation leave remaining</p>
                <div style="margin-top: 12px; padding: 8px 12px; background: #e8f5e9; border-radius: 6px; border-left: 3px solid #27ae60; font-size: 12px; color: #229954; font-weight: 500;">
                    5 sick leave days available
                </div>
            </div>
        </div>

        <div class="stat-widget" style="border-top: 4px solid #f39c12;">
            <div class="stat-header" style="background: linear-gradient(135deg, #f39c12, #e67e22);">
                <span class="stat-icon"><i class="fas fa-clock"></i></span>
                <h4>Work Hours</h4>
            </div>
            <div class="stat-body">
                <p class="stat-number" style="color: #f39c12;">168</p>
                <p class="stat-label">Hours this month</p>
                <div style="margin-top: 12px; background: #fef5e7; height: 4px; border-radius: 2px; overflow: hidden;">
                    <div style="width: 95%; height: 100%; background: linear-gradient(90deg, #f39c12, #e67e22); border-radius: 2px;"></div>
                </div>
            </div>
        </div>

        <div class="stat-widget" style="border-top: 4px solid #e74c3c;">
            <div class="stat-header" style="background: linear-gradient(135deg, #e74c3c, #c0392b);">
                <span class="stat-icon"><i class="fas fa-exclamation-circle"></i></span>
                <h4>Actions Needed</h4>
            </div>
            <div class="stat-body">
                <p class="stat-number" style="color: #e74c3c;">2</p>
                <p class="stat-label">Pending tasks</p>
                <div style="margin-top: 12px; display: flex; gap: 6px;">
                    <span style="background: #ffcdd2; color: #c62828; padding: 4px 10px; border-radius: 12px; font-size: 11px; font-weight: 600;">Urgent</span>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- PERSONAL INFORMATION SECTION -->
<section class="activities-section">
    <div class="section-title">
        <h3><i class="fas fa-user-circle"></i> Personal Information</h3>
        <p>Manage your personal details and employment information</p>
    </div>

    <div class="activities-container">
        <div class="personal-info-grid">
            <!-- Employee Profile Card -->
            <div class="personal-card">
                <div class="personal-card-header" style="background: linear-gradient(135deg, #16a085, #1abc9c);">
                    <i class="fas fa-id-badge"></i>
                    <h4>Employee Profile</h4>
                </div>
                <div class="personal-card-body">
                    <div class="profile-info">
                        <div class="profile-item">
                            <span class="profile-label">Employee ID:</span>
                            <span class="profile-value">EMP-<?php echo str_pad(rand(1, 999), 3, '0', STR_PAD_LEFT); ?></span>
                        </div>
                        <div class="profile-item">
                            <span class="profile-label">Department:</span>
                            <span class="profile-value">Operations</span>
                        </div>
                        <div class="profile-item">
                            <span class="profile-label">Position:</span>
                            <span class="profile-value">Administrative Officer</span>
                        </div>
                        <div class="profile-item">
                            <span class="profile-label">Date Hired:</span>
                            <span class="profile-value">January 15, 2022</span>
                        </div>
                        <div class="profile-item">
                            <span class="profile-label">Employment Status:</span>
                            <span class="profile-value status-active">Active</span>
                        </div>
                    </div>
                    <div class="personal-actions">
                        <button class="btn btn-primary">Update Profile</button>
                        <button class="btn btn-outline">View Full Details</button>
                    </div>
                </div>
            </div>

            <!-- Leave Management -->
            <div class="personal-card">
                <div class="personal-card-header" style="background: linear-gradient(135deg, #27ae60, #2ecc71);">
                    <i class="fas fa-calendar-alt"></i>
                    <h4>Leave Management</h4>
                </div>
                <div class="personal-card-body">
                    <div class="leave-balance">
                        <div class="leave-type">
                            <span class="leave-label">Vacation Leave</span>
                            <div class="leave-info">
                                <span class="leave-days">12</span>
                                <span class="leave-total">/ 15 days</span>
                            </div>
                            <div class="leave-progress">
                                <div class="progress-fill" style="width: 80%; background: #27ae60;"></div>
                            </div>
                        </div>
                        <div class="leave-type">
                            <span class="leave-label">Sick Leave</span>
                            <div class="leave-info">
                                <span class="leave-days">5</span>
                                <span class="leave-total">/ 10 days</span>
                            </div>
                            <div class="leave-progress">
                                <div class="progress-fill" style="width: 50%; background: #f39c12;"></div>
                            </div>
                        </div>
                        <div class="leave-type">
                            <span class="leave-label">Special Leave</span>
                            <div class="leave-info">
                                <span class="leave-days">3</span>
                                <span class="leave-total">/ 3 days</span>
                            </div>
                            <div class="leave-progress">
                                <div class="progress-fill" style="width: 100%; background: #e74c3c;"></div>
                            </div>
                        </div>
                    </div>
                    <div class="personal-actions">
                        <button class="btn btn-success">Apply for Leave</button>
                        <button class="btn btn-outline">Leave History</button>
                    </div>
                </div>
            </div>

            <!-- Compliance Status -->
            <div class="personal-card">
                <div class="personal-card-header" style="background: linear-gradient(135deg, #f39c12, #e67e22);">
                    <i class="fas fa-clipboard-check"></i>
                    <h4>Compliance Status</h4>
                </div>
                <div class="personal-card-body">
                    <div class="compliance-status">
                        <div class="compliance-item">
                            <span class="compliance-label">PDS 2026</span>
                            <span class="compliance-badge complete">Submitted</span>
                        </div>
                        <div class="compliance-item">
                            <span class="compliance-label">SALN 2025</span>
                            <span class="compliance-badge complete">Filed</span>
                        </div>
                        <div class="compliance-item">
                            <span class="compliance-label">Medical Certificate</span>
                            <span class="compliance-badge warning">Expiring Soon</span>
                        </div>
                        <div class="compliance-item">
                            <span class="compliance-label">Training Records</span>
                            <span class="compliance-badge complete">Updated</span>
                        </div>
                    </div>
                    <div class="personal-actions">
                        <button class="btn btn-warning">Update Compliance</button>
                        <button class="btn btn-outline">View All Documents</button>
                    </div>
                </div>
            </div>

            <!-- Recent Activities -->
            <div class="personal-card">
                <div class="personal-card-header" style="background: linear-gradient(135deg, #8e44ad, #9b59b6);">
                    <i class="fas fa-history"></i>
                    <h4>Recent Activities</h4>
                </div>
                <div class="personal-card-body">
                    <div class="activity-list">
                        <div class="activity-entry">
                            <span class="activity-date">March 20, 2026</span>
                            <span class="activity-desc">PDS form updated</span>
                        </div>
                        <div class="activity-entry">
                            <span class="activity-date">March 15, 2026</span>
                            <span class="activity-desc">Leave request approved</span>
                        </div>
                        <div class="activity-entry">
                            <span class="activity-date">March 10, 2026</span>
                            <span class="activity-desc">Profile information updated</span>
                        </div>
                        <div class="activity-entry">
                            <span class="activity-date">March 1, 2026</span>
                            <span class="activity-desc">SALN 2025 filed</span>
                        </div>
                    </div>
                    <div class="personal-actions">
                        <button class="btn btn-info">View All Activities</button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Right Side Personal Dashboard -->
        <div class="activities-sidebar">
            <div class="sidebar-card">
                <h4><i class="fas fa-tasks"></i> Pending Actions</h4>
                <div class="pending-item">
                    <span class="pending-dot urgent"></span>
                    <span class="pending-text">Medical certificate renewal</span>
                    <span class="pending-date">Due: April 15</span>
                </div>
                <div class="pending-item">
                    <span class="pending-dot warning"></span>
                    <span class="pending-text">Update emergency contact</span>
                    <span class="pending-date">Overdue</span>
                </div>
                <div class="pending-item">
                    <span class="pending-dot info"></span>
                    <span class="pending-text">Review training schedule</span>
                    <span class="pending-date">This week</span>
                </div>
            </div>

            <div class="sidebar-card">
                <h4><i class="fas fa-bullhorn"></i> Announcements</h4>
                <div class="announcement-item">
                    <span class="announcement-date">March 21, 2026</span>
                    <span class="announcement-title">Holiday Schedule Update</span>
                    <span class="announcement-desc">Please review the updated holiday schedule for Q2 2026</span>
                </div>
                <div class="announcement-item">
                    <span class="announcement-date">March 18, 2026</span>
                    <span class="announcement-title">Training Opportunity</span>
                    <span class="announcement-desc">New digital literacy training program now available</span>
                </div>
            </div>

            <div class="sidebar-card">
                <h4><i class="fas fa-calendar"></i> Upcoming Events</h4>
                <div class="event-item">
                    <span class="event-date">Apr 1</span>
                    <span class="event-title">Team Meeting</span>
                    <span class="event-time">9:00 AM</span>
                </div>
                <div class="event-item">
                    <span class="event-date">Apr 5</span>
                    <span class="event-title">Training Session</span>
                    <span class="event-time">2:00 PM</span>
                </div>
                <div class="event-item">
                    <span class="event-date">Apr 15</span>
                    <span class="event-title">Medical Check-up</span>
                    <span class="event-time">10:00 AM</span>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- QUICK ACCESS TOOLS -->
<section class="card">
    <div class="section-head">
        <div>
            <span class="tag">Quick Tools</span>
            <h3>Self-Service Tools & Resources</h3>
        </div>
    </div>

    <div class="tools-grid">
        <div class="tool-card">
            <div class="tool-icon" style="background: linear-gradient(135deg, #3498db, #2980b9);">
                <i class="fas fa-download"></i>
            </div>
            <h4>Download Forms</h4>
            <p>Access and download HR forms and documents</p>
            <button class="btn btn-outline">Browse Forms</button>
        </div>
        <div class="tool-card">
            <div class="tool-icon" style="background: linear-gradient(135deg, #e74c3c, #c0392b);">
                <i class="fas fa-file-upload"></i>
            </div>
            <h4>Upload Documents</h4>
            <p>Submit required documents and supporting files</p>
            <button class="btn btn-outline">Upload Now</button>
        </div>
        <div class="tool-card">
            <div class="tool-icon" style="background: linear-gradient(135deg, #f39c12, #e67e22);">
                <i class="fas fa-print"></i>
            </div>
            <h4>Print Records</h4>
            <p>Generate and print your employment records</p>
            <button class="btn btn-outline">Print Options</button>
        </div>
        <div class="tool-card">
            <div class="tool-icon" style="background: linear-gradient(135deg, #27ae60, #229954);">
                <i class="fas fa-question-circle"></i>
            </div>
            <h4>Help & Support</h4>
            <p>Get assistance with HR-related questions</p>
            <a href="help.php" class="btn btn-outline">Get Help</a>
        </div>
    </div>
</section>

<?php require_once 'includes/footer.php'; ?>
