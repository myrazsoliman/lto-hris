<?php
$systemName = 'LTO HRIS';
$systemOffice = 'LTO Pila, Laguna';

$navItems = [
    'index.php' => 'Dashboard',
    'employees.php' => 'Employees',
    'pds.php' => 'PDS',
    'csc-forms.php' => 'CSC Forms',
    'saln.php' => 'SALN',
    'reports.php' => 'Reports'
];

$stats = [
    ['label' => 'Total Employees', 'value' => '48', 'note' => 'Active personnel records'],
    ['label' => 'PDS Records', 'value' => '48', 'note' => 'Complete and searchable'],
    ['label' => 'CSC Forms', 'value' => '126', 'note' => 'Tracked submissions'],
    ['label' => 'SALN Compliance', 'value' => '92%', 'note' => 'Current annual filing']
];

// Enhanced HRIS Dashboard Metrics
$dashboardMetrics = [
    // Employee Status Breakdown
    'employee_status' => [
        'active' => 42,
        'on_leave' => 3,
        'probationary' => 2,
        'inactive' => 1,
        'total' => 48
    ],

    // Department Distribution
    'departments' => [
        'Admin' => 12,
        'Operations' => 15,
        'Records' => 8,
        'ICT' => 7,
        'HR Unit' => 6
    ],

    // Compliance Status
    'compliance' => [
        'pds_filed' => 48,
        'pds_pending' => 0,
        'saln_filed_current_year' => 44,
        'saln_outstanding' => 4,
        'saln_compliance_rate' => 92,
        'csc_forms_completed' => 126,
        'csc_forms_pending' => 8,
        'medical_clearance_valid' => 46,
        'medical_clearance_expired' => 2
    ],

    // Leave Management
    'leave_summary' => [
        'pending_requests' => 3,
        'approved_this_month' => 7,
        'on_leave_today' => 2,
        'leave_balance_critical' => 1
    ],

    // Document Expiry Tracking
    'expiring_documents' => [
        'contracts_expiring_30days' => 2,
        'certifications_expiring_30days' => 1,
        'clearances_expired' => 2
    ],

    // Recruitment & Separation
    'hr_metrics' => [
        'recent_hires_30days' => 3,
        'turnover_rate' => 4.2,
        'pending_job_openings' => 6,
        'applications_pending' => 12
    ],

    // Payroll Status
    'payroll' => [
        'processed_this_month' => 48,
        'pending_adjustments' => 2,
        'tax_filing_status_current' => true
    ]
];

$employees = [
    ['id' => 'EMP-001', 'name' => 'Maria Santos', 'position' => 'HR Officer', 'department' => 'HR Unit', 'status' => 'Active'],
    ['id' => 'EMP-002', 'name' => 'John Reyes', 'position' => 'Administrative Aide', 'department' => 'Admin', 'status' => 'Active'],
    ['id' => 'EMP-003', 'name' => 'Angela Cruz', 'position' => 'Records Officer', 'department' => 'Records', 'status' => 'On Leave'],
    ['id' => 'EMP-004', 'name' => 'Kevin Dela Rosa', 'position' => 'IT Support Staff', 'department' => 'ICT', 'status' => 'Active'],
    ['id' => 'EMP-005', 'name' => 'Samantha Lim', 'position' => 'Clerk', 'department' => 'Operations', 'status' => 'Probationary'],
];

$pdsRecords = [
    ['employee' => 'Maria Santos', 'last_updated' => 'March 18, 2026', 'status' => 'Complete'],
    ['employee' => 'John Reyes', 'last_updated' => 'March 15, 2026', 'status' => 'For Review'],
    ['employee' => 'Angela Cruz', 'last_updated' => 'March 10, 2026', 'status' => 'Complete'],
];

$cscForms = [
    ['form' => 'CSC Form 212', 'employee' => 'Maria Santos', 'date' => 'March 12, 2026', 'status' => 'Submitted'],
    ['form' => 'Leave Form', 'employee' => 'Angela Cruz', 'date' => 'March 14, 2026', 'status' => 'Pending'],
    ['form' => 'Service Record Request', 'employee' => 'John Reyes', 'date' => 'March 16, 2026', 'status' => 'Approved'],
];

$salnRecords = [
    ['employee' => 'Maria Santos', 'year' => '2025', 'status' => 'Filed'],
    ['employee' => 'John Reyes', 'year' => '2025', 'status' => 'Filed'],
    ['employee' => 'Angela Cruz', 'year' => '2025', 'status' => 'Missing'],
];

$reports = [
    ['title' => 'Employee Masterlist', 'type' => 'PDF', 'updated' => 'March 20, 2026'],
    ['title' => 'SALN Compliance Summary', 'type' => 'Excel', 'updated' => 'March 19, 2026'],
    ['title' => 'CSC Forms Monitoring', 'type' => 'PDF', 'updated' => 'March 18, 2026'],
];

// Recent HR Activities & Alerts
$recentActivities = [
    ['type' => 'alert', 'title' => 'SALN Deadline Approaching', 'message' => '4 employees need to file SALN before April 15', 'date' => 'March 21, 2026', 'severity' => 'high'],
    ['type' => 'update', 'title' => 'Medical Certificate Expired', 'message' => 'Kevin Dela Rosa medical clearance expired', 'date' => 'March 21, 2026', 'severity' => 'medium'],
    ['type' => 'info', 'title' => 'Leave Applied', 'message' => 'Angela Cruz applied for 5-day vacation leave', 'date' => 'March 20, 2026', 'severity' => 'low'],
    ['type' => 'success', 'title' => 'PDS Updated', 'message' => 'Maria Santos completed PDS Form 2026', 'date' => 'March 20, 2026', 'severity' => 'low'],
    ['type' => 'alert', 'title' => 'Contract Expiration Notice', 'message' => '2 contract employees expiring in 30 days', 'date' => 'March 19, 2026', 'severity' => 'high'],
];

// Top Alerts for HR Dashboard
$criticalAlerts = [
    ['icon' => '⚠️', 'title' => 'Outstanding SALN Filings', 'count' => 4, 'action' => 'Review SALN Status', 'status' => 'critical'],
    ['icon' => '🔴', 'title' => 'Expired Medical Certificates', 'count' => 2, 'action' => 'Schedule Medical Exam', 'status' => 'critical'],
    ['icon' => '📋', 'title' => 'Pending Leave Approvals', 'count' => 3, 'action' => 'Process Requests', 'status' => 'warning'],
    ['icon' => '📑', 'title' => 'CSC Forms Pending', 'count' => 8, 'action' => 'Follow-up Required', 'status' => 'warning'],
];

// Department Performance Snapshot
$departmentPerformance = [
    [
        'name' => 'Admin',
        'headcount' => 12,
        'pds_compliance' => 100,
        'saln_compliance' => 100,
        'turnover_rate' => 0,
        'avg_tenure_months' => 48
    ],
    [
        'name' => 'Operations',
        'headcount' => 15,
        'pds_compliance' => 100,
        'saln_compliance' => 87,
        'turnover_rate' => 6.7,
        'avg_tenure_months' => 36
    ],
    [
        'name' => 'Records',
        'headcount' => 8,
        'pds_compliance' => 100,
        'saln_compliance' => 100,
        'turnover_rate' => 0,
        'avg_tenure_months' => 60
    ],
    [
        'name' => 'ICT',
        'headcount' => 7,
        'pds_compliance' => 100,
        'saln_compliance' => 86,
        'turnover_rate' => 14.3,
        'avg_tenure_months' => 24
    ],
    [
        'name' => 'HR Unit',
        'headcount' => 6,
        'pds_compliance' => 100,
        'saln_compliance' => 100,
        'turnover_rate' => 0,
        'avg_tenure_months' => 72
    ]
];

// Monthly Hiring & Separation Trends
$hrTrends = [
    'hiring_this_year' => [
        'january' => 3,
        'february' => 4,
        'march' => 2
    ],
    'separations_this_year' => [
        'january' => 0,
        'february' => 1,
        'march' => 0
    ]
];
