<?php
$pageTitle = 'Admin Dashboard';
$activePage = 'admin-dashboard.php';
require_once 'includes/auth.php';
require_roles(['admin', 'hr_officer']);
require_once 'includes/header.php';
require_once 'includes/data.php';
?>

<section class="hero modern-hero">
    <div class="hero-content">
        <div class="hero-header">
            <div class="header-badge" style="background: linear-gradient(135deg, #0f4c81, #1768a7); padding: 16px; border-radius: 12px; color: white; display: flex; align-items: center; justify-content: center; width: 60px; height: 60px;">
                <i class="fas fa-user-tie" style="font-size: 32px;"></i>
            </div>
            <div>
                <h2 style="font-size: 36px; font-weight: 700; color: var(--primary); margin: 0 0 8px 0; line-height: 1.2;">Admin Dashboard</h2>
                <p style="color: var(--muted); font-size: 15px; margin: 0;">Human Resource Management System</p>
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

<!-- HR MANAGEMENT SECTION -->
<section class="activities-section">
    <div class="section-title">
        <h3><i class="fas fa-briefcase"></i> HR Management Overview</h3>
        <p>Comprehensive view of employee management and compliance status</p>
    </div>

    <div class="activities-container">
        <div class="hr-management-grid">
            <!-- Employee Status Overview -->
            <div class="hr-card">
                <div class="hr-card-header" style="background: linear-gradient(135deg, #4caf50, #45a049);">
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
                        <button class="btn btn-outline">Add New Employee</button>
                    </div>
                </div>
            </div>

            <!-- Department Distribution -->
            <div class="hr-card">
                <div class="hr-card-header" style="background: linear-gradient(135deg, #2196f3, #1976d2);">
                    <i class="fas fa-building"></i>
                    <h4>Department Distribution</h4>
                </div>
                <div class="hr-card-body">
                    <div class="dept-distribution">
                        <?php foreach ($dashboardMetrics['departments'] as $dept => $count): ?>
                        <div class="dept-item">
                            <span class="dept-name"><?php echo htmlspecialchars($dept); ?></span>
                            <span class="dept-count"><?php echo $count; ?></span>
                            <div class="dept-bar-container">
                                <div class="dept-bar-fill" style="width: <?php echo ($count / $dashboardMetrics['employee_status']['total']) * 100; ?>%; background: linear-gradient(90deg, #2196f3, #00bcd4);"></div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <div class="hr-actions">
                        <button class="btn btn-info">Department Reports</button>
                    </div>
                </div>
            </div>

            <!-- Compliance Tracking -->
            <div class="hr-card">
                <div class="hr-card-header" style="background: linear-gradient(135deg, #ff9800, #f57c00);">
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

            <!-- Leave Management -->
            <div class="hr-card">
                <div class="hr-card-header" style="background: linear-gradient(135deg, #9c27b0, #6a1b9a);">
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
                        <button class="btn btn-outline">Leave Calendar</button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Right Side HR Analytics -->
        <div class="activities-sidebar">
            <div class="sidebar-card">
                <h4><i class="fas fa-chart-pie"></i> HR Analytics</h4>
                <div class="analytics-item">
                    <span class="analytics-label">Hiring Rate</span>
                    <span class="analytics-value">+<?php echo $dashboardMetrics['hr_metrics']['recent_hires_30days']; ?> this month</span>
                </div>
                <div class="analytics-item">
                    <span class="analytics-label">Turnover Rate</span>
                    <span class="analytics-value"><?php echo $dashboardMetrics['hr_metrics']['turnover_rate']; ?>%</span>
                </div>
                <div class="analytics-item">
                    <span class="analytics-label">Open Positions</span>
                    <span class="analytics-value"><?php echo $dashboardMetrics['hr_metrics']['pending_job_openings']; ?></span>
                </div>
                <div class="analytics-item">
                    <span class="analytics-label">Applications</span>
                    <span class="analytics-value"><?php echo $dashboardMetrics['hr_metrics']['applications_pending']; ?> pending</span>
                </div>
            </div>

            <div class="sidebar-card">
                <h4><i class="fas fa-bell"></i> HR Alerts</h4>
                <?php foreach ($criticalAlerts as $alert): ?>
                <div class="alert-item <?php echo $alert['status']; ?>-alert">
                    <span class="alert-dot"></span>
                    <span><?php echo htmlspecialchars($alert['title']); ?> (<?php echo $alert['count']; ?>)</span>
                </div>
                <?php endforeach; ?>
            </div>

            <div class="sidebar-card">
                <h4><i class="fas fa-history"></i> Recent HR Activities</h4>
                <?php foreach (array_slice($recentActivities, 0, 4) as $activity): ?>
                <div class="activity-item">
                    <span class="activity-time"><?php echo htmlspecialchars($activity['date']); ?></span>
                    <span class="activity-desc"><?php echo htmlspecialchars($activity['title']); ?></span>
                </div>
                <?php endforeach; ?>
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
                            <div class="progress-fill" style="width: <?php echo $dept['pds_compliance']; ?>%; background: #4caf50;"></div>
                        </div>
                        <span class="metric-value"><?php echo $dept['pds_compliance']; ?>%</span>
                    </div>
                </div>
                <div class="dept-metric">
                    <span class="metric-label">SALN Compliance</span>
                    <div class="metric-progress">
                        <div class="progress-bar">
                            <div class="progress-fill" style="width: <?php echo $dept['saln_compliance']; ?>%; background: #2196f3;"></div>
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

<?php require_once 'includes/footer.php'; ?>
