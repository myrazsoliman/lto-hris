<?php
$pageTitle = 'Superadmin Dashboard';
$activePage = 'superadmin-dashboard.php';
require_once 'includes/auth.php';
require_roles(['superadmin']);
require_once 'includes/header.php';
require_once 'includes/data.php';
?>

<section class="hero modern-hero">
    <div class="hero-content">
        <div class="hero-header">
            <div class="header-badge" style="background: linear-gradient(135deg, #8b0000, #dc143c); padding: 16px; border-radius: 12px; color: white; display: flex; align-items: center; justify-content: center; width: 60px; height: 60px;">
                <i class="fas fa-crown" style="font-size: 32px;"></i>
            </div>
            <div>
                <h2 style="font-size: 36px; font-weight: 700; color: var(--primary); margin: 0 0 8px 0; line-height: 1.2;">Superadmin Dashboard</h2>
                <p style="color: var(--muted); font-size: 15px; margin: 0;">Full System Control & Administration</p>
            </div>
        </div>

        <p style="color: var(--muted); line-height: 1.8; margin: 24px 0 28px 0; max-width: 650px; font-size: 15px;">
            Complete administrative control over the LTO HRIS. Manage system settings, user accounts, security configurations, and monitor all organizational operations from this central command center.
        </p>

        <div class="quick-actions">
            <a href="employees.php" class="quick-action-card quick-action-red">
                <div class="action-icon" style="background: #dc143c; color: white;"><i class="fas fa-users-cog"></i></div>
                <div class="action-content">
                    <h4>User Management</h4>
                    <p>Manage all system users & roles</p>
                </div>
                <div class="action-arrow">→</div>
            </a>
            <a href="employees.php" class="quick-action-card quick-action-dark">
                <div class="action-icon" style="background: #2c3e50; color: white;"><i class="fas fa-shield-alt"></i></div>
                <div class="action-content">
                    <h4>Security Center</h4>
                    <p>System security & permissions</p>
                </div>
                <div class="action-arrow">→</div>
            </a>
            <a href="reports.php" class="quick-action-card quick-action-purple">
                <div class="action-icon" style="background: #8e44ad; color: white;"><i class="fas fa-chart-line"></i></div>
                <div class="action-content">
                    <h4>System Analytics</h4>
                    <p>Advanced reports & insights</p>
                </div>
                <div class="action-arrow">→</div>
            </a>
            <a href="#" class="quick-action-card quick-action-orange">
                <div class="action-icon" style="background: #e67e22; color: white;"><i class="fas fa-cogs"></i></div>
                <div class="action-content">
                    <h4>System Settings</h4>
                    <p>Configure system parameters</p>
                </div>
                <div class="action-arrow">→</div>
            </a>
        </div>
    </div>

    <div class="hero-panel modern-panel">
        <div class="stat-widget" style="border-top: 4px solid #dc143c;">
            <div class="stat-header" style="background: linear-gradient(135deg, #dc143c, #8b0000);">
                <span class="stat-icon"><i class="fas fa-users"></i></span>
                <h4>Total Users</h4>
            </div>
            <div class="stat-body">
                <p class="stat-number" style="color: #dc143c;">156</p>
                <p class="stat-label">Across all system roles</p>
                <div style="margin-top: 12px; background: #ffebee; height: 4px; border-radius: 2px; overflow: hidden;">
                    <div style="width: 100%; height: 100%; background: linear-gradient(90deg, #dc143c, #ff6b6b); border-radius: 2px;"></div>
                </div>
            </div>
        </div>

        <div class="stat-widget" style="border-top: 4px solid #8e44ad;">
            <div class="stat-header" style="background: linear-gradient(135deg, #8e44ad, #6c1bc7);">
                <span class="stat-icon"><i class="fas fa-server"></i></span>
                <h4>System Health</h4>
            </div>
            <div class="stat-body">
                <p class="stat-number" style="color: #8e44ad;">98.5%</p>
                <p class="stat-label">Overall system performance</p>
                <div style="margin-top: 12px; padding: 8px 12px; background: #f3e5f5; border-radius: 6px; border-left: 3px solid #8e44ad; font-size: 12px; color: #6a1b9a; font-weight: 500;">
                    All systems operational
                </div>
            </div>
        </div>

        <div class="stat-widget" style="border-top: 4px solid #27ae60;">
            <div class="stat-header" style="background: linear-gradient(135deg, #27ae60, #229954);">
                <span class="stat-icon"><i class="fas fa-database"></i></span>
                <h4>Data Integrity</h4>
            </div>
            <div class="stat-body">
                <p class="stat-number" style="color: #27ae60;">100%</p>
                <p class="stat-label">Database consistency check</p>
                <div style="margin-top: 12px; background: #e8f5e9; height: 4px; border-radius: 2px; overflow: hidden;">
                    <div style="width: 100%; height: 100%; background: linear-gradient(90deg, #27ae60, #2ecc71); border-radius: 2px;"></div>
                </div>
            </div>
        </div>

        <div class="stat-widget" style="border-top: 4px solid #e74c3c;">
            <div class="stat-header" style="background: linear-gradient(135deg, #e74c3c, #c0392b);">
                <span class="stat-icon"><i class="fas fa-exclamation-triangle"></i></span>
                <h4>Critical Alerts</h4>
            </div>
            <div class="stat-body">
                <p class="stat-number" style="color: #e74c3c;">2</p>
                <p class="stat-label">Require immediate attention</p>
                <div style="margin-top: 12px; display: flex; gap: 6px;">
                    <span style="background: #ffcdd2; color: #c62828; padding: 4px 10px; border-radius: 12px; font-size: 11px; font-weight: 600;">Critical</span>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- SYSTEM ADMINISTRATION SECTION -->
<section class="activities-section">
    <div class="section-title">
        <h3><i class="fas fa-cogs"></i> System Administration</h3>
        <p>Manage system-wide settings and administrative functions</p>
    </div>

    <div class="activities-container">
        <div class="admin-grid">
            <!-- User Management -->
            <div class="admin-card">
                <div class="admin-card-header" style="background: linear-gradient(135deg, #dc143c, #8b0000);">
                    <i class="fas fa-users-cog"></i>
                    <h4>User Management</h4>
                </div>
                <div class="admin-card-body">
                    <div class="admin-stats">
                        <div class="admin-stat">
                            <span class="stat-number">12</span>
                            <span class="stat-label">Superadmins</span>
                        </div>
                        <div class="admin-stat">
                            <span class="stat-number">28</span>
                            <span class="stat-label">Administrators</span>
                        </div>
                        <div class="admin-stat">
                            <span class="stat-number">116</span>
                            <span class="stat-label">Employees</span>
                        </div>
                    </div>
                    <div class="admin-actions">
                        <button class="btn btn-primary">Manage Users</button>
                        <button class="btn btn-outline">Role Permissions</button>
                    </div>
                </div>
            </div>

            <!-- System Security -->
            <div class="admin-card">
                <div class="admin-card-header" style="background: linear-gradient(135deg, #2c3e50, #34495e);">
                    <i class="fas fa-shield-alt"></i>
                    <h4>Security Center</h4>
                </div>
                <div class="admin-card-body">
                    <div class="security-logs">
                        <div class="log-item">
                            <span class="log-time">2 hours ago</span>
                            <span class="log-action">Failed login attempt detected</span>
                        </div>
                        <div class="log-item">
                            <span class="log-time">5 hours ago</span>
                            <span class="log-action">Password policy updated</span>
                        </div>
                        <div class="log-item">
                            <span class="log-time">1 day ago</span>
                            <span class="log-action">Security audit completed</span>
                        </div>
                    </div>
                    <div class="admin-actions">
                        <button class="btn btn-warning">View Logs</button>
                        <button class="btn btn-outline">Security Settings</button>
                    </div>
                </div>
            </div>

            <!-- Database Management -->
            <div class="admin-card">
                <div class="admin-card-header" style="background: linear-gradient(135deg, #27ae60, #229954);">
                    <i class="fas fa-database"></i>
                    <h4>Database Management</h4>
                </div>
                <div class="admin-card-body">
                    <div class="db-stats">
                        <div class="db-stat">
                            <span class="stat-label">Total Records</span>
                            <span class="stat-value">48,527</span>
                        </div>
                        <div class="db-stat">
                            <span class="stat-label">Storage Used</span>
                            <span class="stat-value">2.4 GB</span>
                        </div>
                        <div class="db-stat">
                            <span class="stat-label">Last Backup</span>
                            <span class="stat-value">2 hours ago</span>
                        </div>
                    </div>
                    <div class="admin-actions">
                        <button class="btn btn-success">Backup Now</button>
                        <button class="btn btn-outline">Maintenance</button>
                    </div>
                </div>
            </div>

            <!-- Form Templates Management -->
            <div class="admin-card">
                <div class="admin-card-header" style="background: linear-gradient(135deg, #17a2b8, #138496);">
                    <i class="fas fa-file-alt"></i>
                    <h4>Form Templates</h4>
                </div>
                <div class="admin-card-body">
                    <div class="template-stats">
                        <div class="admin-stat">
                            <span class="stat-number">2</span>
                            <span class="stat-label">SALN Templates</span>
                        </div>
                        <div class="admin-stat">
                            <span class="stat-number">3</span>
                            <span class="stat-label">CSC Templates</span>
                        </div>
                        <div class="admin-stat">
                            <span class="stat-number">5</span>
                            <span class="stat-label">Total Templates</span>
                        </div>
                    </div>
                    <div class="admin-actions">
                        <a href="form-templates.php" class="btn btn-info">Manage Templates</a>
                        <button class="btn btn-outline">View History</button>
                    </div>
                </div>
            </div>

            <!-- System Configuration -->
            <div class="admin-card">
                <div class="admin-card-header" style="background: linear-gradient(135deg, #e67e22, #d35400);">
                    <i class="fas fa-cogs"></i>
                    <h4>System Configuration</h4>
                </div>
                <div class="admin-card-body">
                    <div class="config-items">
                        <div class="config-item">
                            <label>Email Notifications</label>
                            <div class="toggle-switch">
                                <input type="checkbox" checked>
                                <span class="slider"></span>
                            </div>
                        </div>
                        <div class="config-item">
                            <label>Auto Backup</label>
                            <div class="toggle-switch">
                                <input type="checkbox" checked>
                                <span class="slider"></span>
                            </div>
                        </div>
                        <div class="config-item">
                            <label>Maintenance Mode</label>
                            <div class="toggle-switch">
                                <input type="checkbox">
                                <span class="slider"></span>
                            </div>
                        </div>
                    </div>
                    <div class="admin-actions">
                        <button class="btn btn-warning">Advanced Settings</button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Right Side System Monitor -->
        <div class="activities-sidebar">
            <div class="sidebar-card">
                <h4><i class="fas fa-heartbeat"></i> System Monitor</h4>
                <div class="monitor-item">
                    <span class="monitor-label">CPU Usage</span>
                    <span class="monitor-value">24%</span>
                    <div class="progress-bar">
                        <div class="progress-fill" style="width: 24%; background: #27ae60;"></div>
                    </div>
                </div>
                <div class="monitor-item">
                    <span class="monitor-label">Memory Usage</span>
                    <span class="monitor-value">67%</span>
                    <div class="progress-bar">
                        <div class="progress-fill" style="width: 67%; background: #f39c12;"></div>
                    </div>
                </div>
                <div class="monitor-item">
                    <span class="monitor-label">Disk Space</span>
                    <span class="monitor-value">45%</span>
                    <div class="progress-bar">
                        <div class="progress-fill" style="width: 45%; background: #3498db;"></div>
                    </div>
                </div>
            </div>

            <div class="sidebar-card">
                <h4><i class="fas fa-bell"></i> System Alerts</h4>
                <div class="alert-item critical-alert">
                    <span class="alert-dot"></span>
                    <span>Database backup failed</span>
                </div>
                <div class="alert-item warning-alert">
                    <span class="alert-dot"></span>
                    <span>High memory usage detected</span>
                </div>
                <div class="alert-item info-alert">
                    <span class="alert-dot"></span>
                    <span>System update available</span>
                </div>
            </div>

            <div class="sidebar-card">
                <h4><i class="fas fa-clock"></i> Recent Activities</h4>
                <div class="activity-item">
                    <span class="activity-time">10 min ago</span>
                    <span class="activity-desc">User account created</span>
                </div>
                <div class="activity-item">
                    <span class="activity-time">1 hour ago</span>
                    <span class="activity-desc">System backup completed</span>
                </div>
                <div class="activity-item">
                    <span class="activity-time">3 hours ago</span>
                    <span class="activity-desc">Security scan performed</span>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ADVANCED SYSTEM METRICS -->
<section class="card">
    <div class="section-head">
        <div>
            <span class="tag">System Analytics</span>
            <h3>Advanced System Metrics</h3>
        </div>
    </div>

    <div class="metrics-grid">
        <div class="metric-card">
            <h4>User Activity</h4>
            <div class="metric-chart">
                <div class="chart-bar" style="height: 80%; background: #3498db;"></div>
                <div class="chart-bar" style="height: 65%; background: #3498db;"></div>
                <div class="chart-bar" style="height: 90%; background: #3498db;"></div>
                <div class="chart-bar" style="height: 75%; background: #3498db;"></div>
                <div class="chart-bar" style="height: 85%; background: #3498db;"></div>
            </div>
            <p>Daily active users trend</p>
        </div>
        <div class="metric-card">
            <h4>System Performance</h4>
            <div class="performance-indicator">
                <div class="indicator-circle" style="border-color: #27ae60;">
                    <span class="indicator-value">98.5%</span>
                </div>
            </div>
            <p>Overall system health score</p>
        </div>
        <div class="metric-card">
            <h4>Security Score</h4>
            <div class="security-metrics">
                <div class="security-item">
                    <span>Firewall</span>
                    <span class="status-ok">Active</span>
                </div>
                <div class="security-item">
                    <span>SSL Certificate</span>
                    <span class="status-ok">Valid</span>
                </div>
                <div class="security-item">
                    <span>Malware Scan</span>
                    <span class="status-warning">Pending</span>
                </div>
            </div>
            <p>Security system status</p>
        </div>
        <div class="metric-card">
            <h4>Data Growth</h4>
            <div class="data-stats">
                <div class="data-stat">
                    <span class="data-label">This Month</span>
                    <span class="data-value">+124 MB</span>
                </div>
                <div class="data-stat">
                    <span class="data-label">This Year</span>
                    <span class="data-value">+1.8 GB</span>
                </div>
            </div>
            <p>Database storage growth</p>
        </div>
    </div>
</section>

<?php require_once 'includes/footer.php'; ?>
