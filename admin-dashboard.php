<?php
$pageTitle = 'Admin Dashboard';
$activePage = 'admin-dashboard.php';
require_once 'includes/auth.php';
require_roles(['admin', 'hr_officer']);
require_once 'includes/header.php';
require_once 'includes/data.php';
?>

<style>
    .admin-dashboard-pro .modern-hero {
        display: block;
        margin-bottom: 24px;
    }
    .admin-dashboard-pro .hero-main-grid {
        display: grid;
        grid-template-columns: 1fr;
        gap: 18px;
        align-items: stretch;
        margin-bottom: 18px;
    }
    .admin-dashboard-pro .hero-main-grid .hero-content {
        margin-bottom: 0;
    }
    .admin-dashboard-pro .modern-hero .hero-panel.modern-panel {
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: 16px;
    }
    @media (max-width: 1200px) {
        .admin-dashboard-pro .hero-main-grid {
            grid-template-columns: 1fr;
        }
        .admin-dashboard-pro .modern-hero .hero-panel.modern-panel {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }
    }
    @media (max-width: 768px) {
        .admin-dashboard-pro .modern-hero .hero-panel.modern-panel {
            grid-template-columns: 1fr;
        }
    }
    .admin-dashboard-pro .hero.modern-hero {
        position: relative;
        overflow: hidden;
        border-radius: 24px;
        background: linear-gradient(180deg, rgba(255,255,255,.98), rgba(248,251,255,.98));
        box-shadow: 0 18px 42px rgba(15, 35, 60, 0.10);
    }
    .admin-dashboard-pro .hero.modern-hero::before {
        content: "";
        position: absolute;
        inset: 0 0 auto 0;
        height: 4px;
        background: linear-gradient(90deg, #0f4c81, #2a6eab, #7ea8d3);
    }
    .admin-dashboard-pro .header-badge--admin {
        width: 58px;
        height: 58px;
        border-radius: 14px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        color: #fff;
        background: linear-gradient(135deg, #0f4c81, #1f65a3);
        box-shadow: 0 10px 22px rgba(15, 76, 129, 0.24);
    }
    .admin-dashboard-pro .header-badge--admin i { font-size: 26px; }
    .admin-dashboard-pro .hero-title { margin: 0 0 4px; font-size: 34px; letter-spacing: -.02em; color: #0f3156; }
    .admin-dashboard-pro .hero-subtitle { margin: 0; color: #5d7088; font-size: 14px; font-weight: 600; letter-spacing: .02em; text-transform: uppercase; }
    .admin-dashboard-pro .hero-copy { margin: 22px 0 24px; max-width: 680px; color: #4f6178; line-height: 1.75; font-size: 15px; }
    .admin-dashboard-pro .quick-action-card { border: 1px solid rgba(15, 76, 129, 0.12); background: linear-gradient(180deg, #fff, #f8fbff); }
    .admin-dashboard-pro .quick-action-card .action-arrow { font-size: 0; color: #6f86a3; }
    .admin-dashboard-pro .quick-action-card .action-arrow::before {
        content: "\f061";
        font-family: "Font Awesome 6 Free";
        font-weight: 900;
        font-size: 16px;
    }
    .admin-dashboard-pro .stat-widget { border-top-width: 3px; }
    .admin-dashboard-pro .stat-widget--success { border-top-color: #2f8f56; }
    .admin-dashboard-pro .stat-widget--warning { border-top-color: #c67a1b; }
    .admin-dashboard-pro .stat-widget--info { border-top-color: #1f6fb7; }
    .admin-dashboard-pro .stat-widget--danger { border-top-color: #be3b3b; }
    .admin-dashboard-pro .stat-widget--activity { border-top-color: #254f77; }
    .admin-dashboard-pro .stat-header--success { background: linear-gradient(135deg, #2f8f56, #2a7b4b); }
    .admin-dashboard-pro .stat-header--warning { background: linear-gradient(135deg, #c67a1b, #a96a17); }
    .admin-dashboard-pro .stat-header--info { background: linear-gradient(135deg, #1f6fb7, #1a5d9a); }
    .admin-dashboard-pro .stat-header--danger { background: linear-gradient(135deg, #be3b3b, #9f2f2f); }
    .admin-dashboard-pro .stat-header--activity { background: linear-gradient(135deg, #254f77, #1d3f62); }
    .admin-dashboard-pro .stat-number--success { color: #2f8f56; }
    .admin-dashboard-pro .stat-number--warning { color: #c67a1b; }
    .admin-dashboard-pro .stat-number--info { color: #1f6fb7; }
    .admin-dashboard-pro .stat-number--danger { color: #be3b3b; }
    .admin-dashboard-pro .stat-progress { margin-top: 12px; height: 5px; border-radius: 999px; overflow: hidden; background: #e5edf7; }
    .admin-dashboard-pro .stat-progress > span { display: block; height: 100%; border-radius: inherit; }
    .admin-dashboard-pro .stat-progress--success > span { background: linear-gradient(90deg, #2f8f56, #5fb179); }
    .admin-dashboard-pro .stat-progress--info > span { background: linear-gradient(90deg, #1f6fb7, #4f95d4); }
    .admin-dashboard-pro .stat-note { margin-top: 12px; padding: 8px 10px; border-radius: 8px; border-left: 3px solid #c67a1b; background: #fff4e4; color: #8e5308; font-size: 12px; font-weight: 600; }
    .admin-dashboard-pro .stat-status-wrap { margin-top: 12px; }
    .admin-dashboard-pro .chip-status { display:inline-flex; align-items:center; height:24px; padding:0 10px; border-radius:999px; font-size:11px; font-weight:700; letter-spacing:.04em; text-transform:uppercase; background:#fde8e8; color:#a43434; }
    .admin-dashboard-pro .stat-activity-list {
        margin-top: 8px;
        display: grid;
        gap: 8px;
    }
    .admin-dashboard-pro .stat-activity-item {
        display: flex;
        justify-content: space-between;
        gap: 10px;
        padding: 9px 10px;
        border-radius: 9px;
        background: #f4f8fc;
        border: 1px solid #dce7f3;
    }
    .admin-dashboard-pro .stat-activity-time {
        color: #687b90;
        font-size: 12px;
        font-weight: 700;
        white-space: nowrap;
    }
    .admin-dashboard-pro .stat-activity-title {
        color: #1e334b;
        font-size: 13px;
        font-weight: 600;
        text-align: right;
    }
    .admin-dashboard-pro .hr-card-header--success { background: linear-gradient(135deg, #2f8f56, #2a7b4b); }
    .admin-dashboard-pro .hr-card-header--info { background: linear-gradient(135deg, #1f6fb7, #1a5d9a); }
    .admin-dashboard-pro .hr-card-header--warning { background: linear-gradient(135deg, #c67a1b, #a96a17); }
    .admin-dashboard-pro .hr-card-header--accent { background: linear-gradient(135deg, #5f4ea8, #4a3b8d); }
    .admin-dashboard-pro .dept-bar-fill--info { background: linear-gradient(90deg, #1f6fb7, #4f95d4); }
    .admin-dashboard-pro .progress-fill--success { background: linear-gradient(90deg, #2f8f56, #5fb179); }
    .admin-dashboard-pro .progress-fill--info { background: linear-gradient(90deg, #1f6fb7, #4f95d4); }
    .admin-dashboard-pro .activities-container {
        display: block;
    }
    .admin-dashboard-pro .command-center-grid {
        display: grid;
        grid-template-columns: minmax(0, 1.6fr) minmax(320px, 1fr);
        gap: 18px;
        align-items: start;
    }
    .admin-dashboard-pro .command-col {
        display: grid;
        gap: 18px;
    }
    .admin-dashboard-pro .hr-management-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 18px;
    }
    .admin-dashboard-pro .command-col--main .hr-management-grid {
        grid-template-columns: 1fr;
    }
    .admin-dashboard-pro .hr-card {
        border-radius: 16px;
        border: 1px solid #d6e2f0;
        background: linear-gradient(180deg, #ffffff, #f8fbff);
        box-shadow: 0 10px 24px rgba(20, 42, 68, 0.08);
        overflow: hidden;
        transition: transform .18s ease, box-shadow .18s ease;
    }
    .admin-dashboard-pro .hr-card:hover {
        transform: translateY(-3px);
        box-shadow: 0 16px 30px rgba(20, 42, 68, 0.12);
    }
    .admin-dashboard-pro .hr-card-header {
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 14px 16px;
        color: #fff;
    }
    .admin-dashboard-pro .hr-card-header i {
        width: 32px;
        height: 32px;
        border-radius: 9px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        background: rgba(255, 255, 255, 0.2);
        font-size: 14px;
    }
    .admin-dashboard-pro .hr-card-header h4 {
        margin: 0;
        font-size: 20px;
        font-weight: 700;
        letter-spacing: .01em;
    }
    .admin-dashboard-pro .hr-card-body {
        padding: 16px;
    }
    .admin-dashboard-pro .employee-stats,
    .admin-dashboard-pro .compliance-items,
    .admin-dashboard-pro .leave-stats {
        display: grid;
        gap: 10px;
    }
    .admin-dashboard-pro .emp-stat-item,
    .admin-dashboard-pro .compliance-item,
    .admin-dashboard-pro .leave-stat {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 10px;
        padding: 11px 12px;
        border: 1px solid #dbe7f3;
        border-radius: 11px;
        background: #f6faff;
    }
    .admin-dashboard-pro .emp-stat-label,
    .admin-dashboard-pro .compliance-label,
    .admin-dashboard-pro .leave-label {
        color: #384e66;
        font-size: 14px;
        font-weight: 600;
    }
    .admin-dashboard-pro .emp-stat-number,
    .admin-dashboard-pro .leave-value {
        color: #103e6d;
        font-size: 22px;
        font-weight: 800;
        line-height: 1;
    }
    .admin-dashboard-pro .compliance-status {
        display: inline-flex;
        align-items: center;
        height: 28px;
        padding: 0 10px;
        border-radius: 999px;
        border: 1px solid #cddff2;
        background: #eaf3ff;
        color: #1f5f96;
        font-size: 12px;
        font-weight: 700;
        white-space: nowrap;
    }
    .admin-dashboard-pro .compliance-status.complete {
        border-color: #b7dfc7;
        background: #ebf9ef;
        color: #2c7a4f;
    }
    .admin-dashboard-pro .compliance-status.warning {
        border-color: #f0d4aa;
        background: #fff5e8;
        color: #9f6112;
    }
    .admin-dashboard-pro .compliance-status.pending {
        border-color: #e7d4ff;
        background: #f5efff;
        color: #5c3b96;
    }
    .admin-dashboard-pro .hr-actions {
        margin-top: 14px;
    }
    .admin-dashboard-pro .recent-activity-card .hr-card-body {
        padding-top: 12px;
    }
    .admin-dashboard-pro .recent-activity-list {
        display: grid;
        gap: 10px;
    }
    .admin-dashboard-pro .recent-activity-item {
        display: flex;
        justify-content: space-between;
        gap: 10px;
        border: 1px solid #dbe7f3;
        border-radius: 11px;
        background: #f6faff;
        padding: 11px 12px;
    }
    .admin-dashboard-pro .recent-activity-item .date {
        color: #687b90;
        font-size: 12px;
        font-weight: 700;
        white-space: nowrap;
    }
    .admin-dashboard-pro .recent-activity-item .title {
        color: #1f344a;
        font-size: 14px;
        font-weight: 600;
        text-align: right;
    }
    .admin-dashboard-pro .hr-card .btn {
        width: 100%;
        min-height: 42px;
        border-radius: 11px;
        font-size: 15px;
        font-weight: 700;
        letter-spacing: .02em;
        box-shadow: 0 8px 18px rgba(15, 76, 129, 0.18);
    }
    .admin-dashboard-pro .hr-card .btn.btn-warning {
        background: linear-gradient(135deg, #c67a1b, #a96412);
        border-color: #a96412;
        color: #fff;
    }
    .admin-dashboard-pro .hr-card .btn.btn-warning:hover {
        background: linear-gradient(135deg, #b36e16, #995a0f);
        border-color: #995a0f;
    }
    @media (max-width: 768px) {
        .admin-dashboard-pro .hero-main-grid {
            gap: 14px;
        }
        .admin-dashboard-pro .command-center-grid {
            grid-template-columns: 1fr;
        }
        .admin-dashboard-pro .hr-management-grid {
            grid-template-columns: 1fr;
        }
    }
    @media (min-width: 769px) and (max-width: 1200px) {
        .admin-dashboard-pro .command-center-grid {
            grid-template-columns: 1fr;
        }
    }
</style>

<div class="admin-dashboard-pro">
<section class="hero modern-hero">
    <div class="hero-main-grid">
    <div class="hero-content">
        <div class="hero-header">
            <div class="header-badge header-badge--admin">
                <i class="fas fa-user-tie"></i>
            </div>
            <div>
                <h2 class="hero-title">Admin Dashboard</h2>
                <p class="hero-subtitle">Human Resource Management System</p>
            </div>
        </div>

        <div class="quick-actions">
            <a href="employees.php" class="quick-action-card quick-action-success">
                <div class="action-icon"><i class="fas fa-users"></i></div>
                <div class="action-content">
                    <h4>Manage Employees</h4>
                    <p>View & manage personnel records</p>
                </div>
                <div class="action-arrow">→</div>
            </a>
            <a href="pds.php" class="quick-action-card quick-action-blue">
                <div class="action-icon"><i class="fas fa-clipboard"></i></div>
                <div class="action-content">
                    <h4>PDS Records</h4>
                    <p>Personnel Data Sheet</p>
                </div>
                <div class="action-arrow">→</div>
            </a>
            <a href="saln.php" class="quick-action-card quick-action-primary">
                <div class="action-icon"><i class="fas fa-file-alt"></i></div>
                <div class="action-content">
                    <h4>SALN Filing</h4>
                    <p>Statement of Assets & Liabilities</p>
                </div>
                <div class="action-arrow">→</div>
            </a>
            <a href="csc-forms.php" class="quick-action-card quick-action-purple">
                <div class="action-icon"><i class="fas fa-file"></i></div>
                <div class="action-content">
                    <h4>CSC Forms</h4>
                    <p>Government compliance forms</p>
                </div>
                <div class="action-arrow">→</div>
            </a>
        </div>
    </div>

    <div class="hero-panel modern-panel">
        <div class="stat-widget stat-widget--success">
            <div class="stat-header stat-header--success">
                <span class="stat-icon"><i class="fas fa-users"></i></span>
                <h4>Active Employees</h4>
            </div>
            <div class="stat-body">
                <p class="stat-number stat-number--success"><?php echo $dashboardMetrics['employee_status']['active']; ?></p>
                <p class="stat-label">Out of <?php echo $dashboardMetrics['employee_status']['total']; ?> total staff</p>
                <div class="stat-progress stat-progress--success">
                    <span style="width: <?php echo $dashboardMetrics['employee_status']['total'] > 0 ? round(($dashboardMetrics['employee_status']['active'] / $dashboardMetrics['employee_status']['total']) * 100, 1) : 0; ?>%;"></span>
                </div>
            </div>
        </div>

        <div class="stat-widget stat-widget--warning">
            <div class="stat-header stat-header--warning">
                <span class="stat-icon"><i class="fas fa-exclamation-triangle"></i></span>
                <h4>Alerts</h4>
            </div>
            <div class="stat-body">
                <p class="stat-number stat-number--warning">4</p>
                <p class="stat-label">Items requiring attention</p>
                <div class="stat-note">
                    Action needed: SALN filings due
                </div>
            </div>
        </div>

        <div class="stat-widget stat-widget--info">
            <div class="stat-header stat-header--info">
                <span class="stat-icon"><i class="fas fa-check"></i></span>
                <h4>Compliance</h4>
            </div>
            <div class="stat-body">
                <p class="stat-number stat-number--info"><?php echo $dashboardMetrics['compliance']['saln_compliance_rate']; ?>%</p>
                <p class="stat-label">SALN filing rate this year</p>
                <div class="stat-progress stat-progress--info">
                    <span style="width: <?php echo (float) $dashboardMetrics['compliance']['saln_compliance_rate']; ?>%;"></span>
                </div>
            </div>
        </div>

    </div>
</section>

<!-- HR MANAGEMENT SECTION -->
<section class="activities-section">
    <div class="section-title">
        <h3><i class="fas fa-briefcase"></i> HR Management Overview</h3>
        <p>Comprehensive view of employee management and compliance status</p>
    </div>

    <div class="activities-container">
        <div class="command-center-grid">
            <div class="command-col command-col--main">
                <div class="hr-management-grid">
                    <!-- Employee Status Overview -->
                    <div class="hr-card">
                        <div class="hr-card-header hr-card-header--success">
                            <i class="fas fa-users"></i>
                            <h4>Employee Status</h4>
                        </div>
                        <div class="hr-card-body">
                            <div class="employee-stats">
                                <div class="emp-stat-item">
                                    <span class="emp-stat-number"><?php echo $dashboardMetrics['employee_status']['active']; ?></span>
                                    <span class="emp-stat-label">Active</span>
                                </div>
                                <div class="emp-stat-item">
                                    <span class="emp-stat-number"><?php echo $dashboardMetrics['employee_status']['on_leave']; ?></span>
                                    <span class="emp-stat-label">On Leave</span>
                                </div>
                                <div class="emp-stat-item">
                                    <span class="emp-stat-number"><?php echo $dashboardMetrics['employee_status']['probationary']; ?></span>
                                    <span class="emp-stat-label">Probationary</span>
                                </div>
                                <div class="emp-stat-item">
                                    <span class="emp-stat-number"><?php echo $dashboardMetrics['employee_status']['inactive']; ?></span>
                                    <span class="emp-stat-label">Inactive</span>
                                </div>
                            </div>
                            <div class="hr-actions">
                                <button class="btn btn-primary">View All Employees</button>
                            </div>
                        </div>
                    </div>

                    <!-- Compliance Tracking -->
                    <div class="hr-card">
                        <div class="hr-card-header hr-card-header--warning">
                            <i class="fas fa-clipboard-check"></i>
                            <h4>Compliance Tracking</h4>
                        </div>
                        <div class="hr-card-body">
                            <div class="compliance-items">
                                <div class="compliance-item">
                                    <span class="compliance-label">PDS Filed</span>
                                    <span class="compliance-status complete"><?php echo $dashboardMetrics['compliance']['pds_filed']; ?>/<?php echo $dashboardMetrics['employee_status']['total']; ?></span>
                                </div>
                                <div class="compliance-item">
                                    <span class="compliance-label">SALN Filed</span>
                                    <span class="compliance-status warning"><?php echo $dashboardMetrics['compliance']['saln_filed_current_year']; ?>/<?php echo $dashboardMetrics['employee_status']['total']; ?></span>
                                </div>
                                <div class="compliance-item">
                                    <span class="compliance-label">Medical Clearance</span>
                                    <span class="compliance-status complete"><?php echo $dashboardMetrics['compliance']['medical_clearance_valid']; ?>/<?php echo $dashboardMetrics['employee_status']['total']; ?></span>
                                </div>
                                <div class="compliance-item">
                                    <span class="compliance-label">CSC Forms</span>
                                    <span class="compliance-status pending"><?php echo $dashboardMetrics['compliance']['csc_forms_completed']; ?> completed</span>
                                </div>
                            </div>
                            <div class="hr-actions">
                                <button class="btn btn-warning">View Compliance Report</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="command-col command-col--side">
                <!-- Leave Management -->
                <div class="hr-card">
                    <div class="hr-card-header hr-card-header--accent">
                        <i class="fas fa-calendar-alt"></i>
                        <h4>Leave Management</h4>
                    </div>
                    <div class="hr-card-body">
                        <div class="leave-stats">
                            <div class="leave-stat">
                                <span class="leave-label">Pending Requests</span>
                                <span class="leave-value"><?php echo $dashboardMetrics['leave_summary']['pending_requests']; ?></span>
                            </div>
                            <div class="leave-stat">
                                <span class="leave-label">Approved This Month</span>
                                <span class="leave-value"><?php echo $dashboardMetrics['leave_summary']['approved_this_month']; ?></span>
                            </div>
                            <div class="leave-stat">
                                <span class="leave-label">On Leave Today</span>
                                <span class="leave-value"><?php echo $dashboardMetrics['leave_summary']['on_leave_today']; ?></span>
                            </div>
                        </div>
                        <div class="hr-actions">
                            <button class="btn btn-primary">Process Leave Requests</button>
                        </div>
                    </div>
                </div>

                <div class="hr-card recent-activity-card">
                    <div class="hr-card-header hr-card-header--info">
                        <i class="fas fa-history"></i>
                        <h4>Recent HR Activities</h4>
                    </div>
                    <div class="hr-card-body">
                        <div class="recent-activity-list">
                            <?php foreach (array_slice($recentActivities, 0, 4) as $activity): ?>
                            <div class="recent-activity-item">
                                <span class="date"><?php echo htmlspecialchars($activity['date']); ?></span>
                                <span class="title"><?php echo htmlspecialchars($activity['title']); ?></span>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- DEPARTMENT PERFORMANCE SECTION -->
<section class="card">
    <div class="section-head">
        <div>
            <span class="tag">Department Performance</span>
            <h3>Department Overview & Performance Metrics</h3>
        </div>
    </div>

    <div class="dept-performance-grid">
        <?php foreach ($departmentPerformance as $dept): ?>
        <div class="dept-performance-card">
            <div class="dept-header">
                <h4><?php echo htmlspecialchars($dept['name']); ?></h4>
                <span class="dept-headcount"><?php echo $dept['headcount']; ?> staff</span>
            </div>
            <div class="dept-metrics">
                <div class="dept-metric">
                    <span class="metric-label">PDS Compliance</span>
                    <div class="metric-progress">
                        <div class="progress-bar">
                            <div class="progress-fill progress-fill--success" style="width: <?php echo $dept['pds_compliance']; ?>%;"></div>
                        </div>
                        <span class="metric-value"><?php echo $dept['pds_compliance']; ?>%</span>
                    </div>
                </div>
                <div class="dept-metric">
                    <span class="metric-label">SALN Compliance</span>
                    <div class="metric-progress">
                        <div class="progress-bar">
                            <div class="progress-fill progress-fill--info" style="width: <?php echo $dept['saln_compliance']; ?>%;"></div>
                        </div>
                        <span class="metric-value"><?php echo $dept['saln_compliance']; ?>%</span>
                    </div>
                </div>
                <div class="dept-metric">
                    <span class="metric-label">Avg Tenure</span>
                    <span class="metric-value"><?php echo $dept['avg_tenure_months']; ?> months</span>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</section>

</div>

<?php require_once 'includes/footer.php'; ?>
