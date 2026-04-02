<?php
$pageTitle = 'Dashboard';
$activePage = 'index.php';
require_once 'includes/auth.php';
require_login();
require_once 'includes/header.php';
?>

<section class="hero modern-hero">
    <div class="hero-content">
        <div class="hero-header">
            <div class="header-badge" style="background: linear-gradient(135deg, #0f4c81, #1768a7); padding: 16px; border-radius: 12px; color: white; display: flex; align-items: center; justify-content: center; width: 60px; height: 60px;">
                <i class="fas fa-building" style="font-size: 32px;"></i>
            </div>
            <div>
                <h2 style="font-size: 36px; font-weight: 700; color: var(--primary); margin: 0 0 8px 0; line-height: 1.2;">Welcome to LTO HRIS</h2>
                <p style="color: var(--muted); font-size: 15px; margin: 0;">Land Transportation Office Human Resource Information System</p>
            </div>
        </div>

        <p style="color: var(--muted); line-height: 1.8; margin: 24px 0 28px 0; max-width: 650px; font-size: 15px;">
            Manage employee records, maintain compliance with government regulations, and streamline HR operations through our secure and centralized platform. Access personnel data, compliance documents, and required forms in one place.
        </p>

        <div class="quick-actions">
            <a href="employees.php" class="quick-action-card quick-action-green">
                <div class="action-icon" style="background: #4caf50; color: white;"><i class="fas fa-users"></i></div>
                <div class="action-content">
                    <h4>Manage Employees</h4>
                    <p>View & manage personnel records</p>
                </div>
                <div class="action-arrow">→</div>
            </a>
            <a href="pds.php" class="quick-action-card quick-action-blue">
                <div class="action-icon" style="background: #2196f3; color: white;"><i class="fas fa-clipboard"></i></div>
                <div class="action-content">
                    <h4>PDS Records</h4>
                    <p>Personnel Data Sheet</p>
                </div>
                <div class="action-arrow">→</div>
            </a>
            <a href="saln.php" class="quick-action-card quick-action-orange">
                <div class="action-icon" style="background: #ff9800; color: white;"><i class="fas fa-file-alt"></i></div>
                <div class="action-content">
                    <h4>SALN Filing</h4>
                    <p>Statement of Assets & Liabilities</p>
                </div>
                <div class="action-arrow">→</div>
            </a>
            <a href="csc-forms.php" class="quick-action-card quick-action-purple">
                <div class="action-icon" style="background: #9c27b0; color: white;"><i class="fas fa-file"></i></div>
                <div class="action-content">
                    <h4>CSC Forms</h4>
                    <p>Government compliance forms</p>
                </div>
                <div class="action-arrow">→</div>
            </a>
        </div>
    </div>

    <div class="hero-panel modern-panel">
        <div class="stat-widget" style="border-top: 4px solid #4caf50;">
            <div class="stat-header" style="background: linear-gradient(135deg, #4caf50, #45a049);">
                <span class="stat-icon"><i class="fas fa-users"></i></span>
                <h4>Active Employees</h4>
            </div>
            <div class="stat-body">
                <p class="stat-number" style="color: #4caf50;"><?php echo $dashboardMetrics['employee_status']['active']; ?></p>
                <p class="stat-label">Out of <?php echo $dashboardMetrics['employee_status']['total']; ?> total staff</p>
                <div style="margin-top: 12px; background: #e8f5e9; height: 4px; border-radius: 2px; overflow: hidden;">
                    <div style="width: 87.5%; height: 100%; background: linear-gradient(90deg, #4caf50, #7cb342); border-radius: 2px;"></div>
                </div>
            </div>
        </div>

        <div class="stat-widget" style="border-top: 4px solid #ff9800;">
            <div class="stat-header" style="background: linear-gradient(135deg, #ff9800, #f57c00);">
                <span class="stat-icon"><i class="fas fa-exclamation-triangle"></i></span>
                <h4>Alerts</h4>
            </div>
            <div class="stat-body">
                <p class="stat-number" style="color: #ff9800;">4</p>
                <p class="stat-label">Items requiring attention</p>
                <div style="margin-top: 12px; padding: 8px 12px; background: #fff3e0; border-radius: 6px; border-left: 3px solid #ff9800; font-size: 12px; color: #e65100; font-weight: 500;">
                    Action needed: SALN filings due
                </div>
            </div>
        </div>

        <div class="stat-widget" style="border-top: 4px solid #2196f3;">
            <div class="stat-header" style="background: linear-gradient(135deg, #2196f3, #1976d2);">
                <span class="stat-icon"><i class="fas fa-check"></i></span>
                <h4>Compliance</h4>
            </div>
            <div class="stat-body">
                <p class="stat-number" style="color: #2196f3;"><?php echo $dashboardMetrics['compliance']['saln_compliance_rate']; ?>%</p>
                <p class="stat-label">SALN filing rate this year</p>
                <div style="margin-top: 12px; background: #e3f2fd; height: 4px; border-radius: 2px; overflow: hidden;">
                    <div style="width: 92%; height: 100%; background: linear-gradient(90deg, #2196f3, #00bcd4); border-radius: 2px;"></div>
                </div>
            </div>
        </div>

        <div class="stat-widget" style="border-top: 4px solid #e74c3c;">
            <div class="stat-header" style="background: linear-gradient(135deg, #e74c3c, #c0392b);">
                <span class="stat-icon"><i class="fas fa-clipboard-list"></i></span>
                <h4>Pending</h4>
            </div>
            <div class="stat-body">
                <p class="stat-number" style="color: #e74c3c;"><?php echo $dashboardMetrics['leave_summary']['pending_requests']; ?></p>
                <p class="stat-label">Leave requests pending approval</p>
                <div style="margin-top: 12px; display: flex; gap: 6px;">
                    <span style="background: #ffcdd2; color: #c62828; padding: 4px 10px; border-radius: 12px; font-size: 11px; font-weight: 600;">Pending</span>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- RECENT ACTIVITIES FEED SECTION -->
<section class="activities-section">
    <div class="section-title">
        <h3><i class="fas fa-history"></i> Recent HR Activities</h3>
        <p>Latest updates and activities from the HR system</p>
    </div>

    <div class="activities-container">
        <div class="activity-timeline">
            <!-- Activity Item 1 -->
            <div class="activity-item" style="border-left-color: #e74c3c;">
                <div class="activity-icon" style="background: linear-gradient(135deg, #e74c3c, #c0392b);">
                    <i class="fas fa-exclamation-circle"></i>
                </div>
                <div class="activity-content">
                    <h4>SALN Deadline Approaching</h4>
                    <p>4 employees need to file SALN before April 15</p>
                    <div class="activity-meta">
                        <span class="activity-badge urgent">Urgent</span>
                        <span class="activity-time">Today, 10:30 AM</span>
                    </div>
                </div>
            </div>

            <!-- Activity Item 2 -->
            <div class="activity-item" style="border-left-color: #2196f3;">
                <div class="activity-icon" style="background: linear-gradient(135deg, #2196f3, #1976d2);">
                    <i class="fas fa-file-upload"></i>
                </div>
                <div class="activity-content">
                    <h4>PDS Form Submitted</h4>
                    <p>Maria Santos completed Personnel Data Sheet filing</p>
                    <div class="activity-meta">
                        <span class="activity-badge success">Completed</span>
                        <span class="activity-time">March 20, 2:45 PM</span>
                    </div>
                </div>
            </div>

            <!-- Activity Item 3 -->
            <div class="activity-item" style="border-left-color: #ff9800;">
                <div class="activity-icon" style="background: linear-gradient(135deg, #ff9800, #f57c00);">
                    <i class="fas fa-calendar-exclamation"></i>
                </div>
                <div class="activity-content">
                    <h4>Medical Certificate Expired</h4>
                    <p>Kevin Dela Rosa requires new medical clearance</p>
                    <div class="activity-meta">
                        <span class="activity-badge warning">Action Needed</span>
                        <span class="activity-time">March 20, 11:15 AM</span>
                    </div>
                </div>
            </div>

            <!-- Activity Item 4 -->
            <div class="activity-item" style="border-left-color: #4caf50;">
                <div class="activity-icon" style="background: linear-gradient(135deg, #4caf50, #388e3c);">
                    <i class="fas fa-user-plus"></i>
                </div>
                <div class="activity-content">
                    <h4>New Employee Onboarded</h4>
                    <p>Maria Garcia successfully onboarded to ICT department</p>
                    <div class="activity-meta">
                        <span class="activity-badge info">New Hire</span>
                        <span class="activity-time">March 18, 9:00 AM</span>
                    </div>
                </div>
            </div>

            <!-- Activity Item 5 -->
            <div class="activity-item" style="border-left-color: #9c27b0;">
                <div class="activity-icon" style="background: linear-gradient(135deg, #9c27b0, #6a1b9a);">
                    <i class="fas fa-signature"></i>
                </div>
                <div class="activity-content">
                    <h4>Leave Request Approved</h4>
                    <p>Angela Cruz's 5-day vacation leave has been approved</p>
                    <div class="activity-meta">
                        <span class="activity-badge success">Approved</span>
                        <span class="activity-time">March 15, 3:20 PM</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Right Side Dashboard Stats -->
        <div class="activities-sidebar">
            <div class="sidebar-card">
                <h4><i class="fas fa-chart-pie"></i> Today's Summary</h4>
                <div class="summary-stat">
                    <span class="stat-label">New Activities</span>
                    <span class="stat-value">5</span>
                </div>
                <div class="summary-stat">
                    <span class="stat-label">Pending Actions</span>
                    <span class="stat-value">8</span>
                </div>
                <div class="summary-stat">
                    <span class="stat-label">Compliance Rate</span>
                    <span class="stat-value">92%</span>
                </div>
            </div>

            <div class="sidebar-card">
                <h4><i class="fas fa-bell"></i> Priority Alerts</h4>
                <div class="alert-item urgent-alert">
                    <span class="alert-dot"></span>
                    <span>SALN Filings Due</span>
                </div>
                <div class="alert-item warning-alert">
                    <span class="alert-dot"></span>
                    <span>Medical Exams</span>
                </div>
                <div class="alert-item info-alert">
                    <span class="alert-dot"></span>
                    <span>Contract Renewal</span>
                </div>
            </div>

            <div class="sidebar-card">
                <h4><i class="fas fa-people-group"></i> Department Status</h4>
                <div class="dept-bar">
                    <div class="dept-label">Admin</div>
                    <div class="dept-progress" style="width: 100%; background: linear-gradient(90deg, #4caf50, #7cb342);"></div>
                </div>
                <div class="dept-bar">
                    <div class="dept-label">Operations</div>
                    <div class="dept-progress" style="width: 87%; background: linear-gradient(90deg, #2196f3, #00bcd4);"></div>
                </div>
                <div class="dept-bar">
                    <div class="dept-label">ICT</div>
                    <div class="dept-progress" style="width: 86%; background: linear-gradient(90deg, #ff9800, #ffc107);"></div>
                </div>
            </div>
        </div>
    </div>
</section>

<section class="grid two-col vision-mission">
    <article class="card">
        <div class="section-head">
            <div>
                <span class="tag">Vision</span>
                <h3>Vision</h3>
            </div>
        </div>
        <p class="text-muted">
            LTO shall be one of the leading national agencies in promoting Eco-friendly,
            safe and efficient land transport system. We optimize technology driven processes and systems in ensuring dynamic,
            transparent and client focused services
        </p>
    </article>

    <article class="card">
        <div class="section-head">
            <div>
                <span class="tag">Mission</span>
                <h3>Mission</h3>
            </div>
        </div>
        <ul class="list">
            Rationalize the land transportation services and facilities and to effectively implement the various transportation laws, 
            rules and regulations. It is the responsibility of those involved in the public service to be more vigilant in their part in the 
            over-all development scheme of the national leadership. Hence, promotion of safety and comfort in land travel is a continuing 
            commitment of LTO.
    </article>
</section>

<section class="card">
    <div class="section-head">
        <div>
            <span class="tag">Modules</span>
        </div>
    </div>

    <div class="feature-grid">
        <div class="feature-card">
            <h4>Employee Records</h4>
            <p>Manage personnel profiles and view individual employee details.</p>
        </div>
        <div class="feature-card">
            <h4>PDS</h4>
            <p>Maintain personnel data sheet records in an organized interface.</p>
        </div>
        <div class="feature-card">
            <h4>CSC Forms</h4>
            <p>Track and manage government-required civil service forms.</p>
        </div>
        <div class="feature-card">
            <h4>SALN Monitoring</h4>
            <p>Monitor annual filing compliance and submission status.</p>
        </div>
    </div>
</section>

<?php require_once 'includes/footer.php'; ?>
