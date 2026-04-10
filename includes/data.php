<?php
$systemName = 'LTO HRIS';
$systemOffice = 'LTO Pila, Laguna';

// Role-based navigation items
function get_nav_items($userRoles = []) {
    $roles = array_values(array_filter((array) $userRoles, 'is_string'));

    // Pick a single "primary" role so the sidebar is distinct per role.
    // Priority: superadmin > admin > hr_officer > employee
    $primaryRole = 'employee';
    foreach (['superadmin', 'admin', 'hr_officer', 'employee'] as $role) {
        if (in_array($role, $roles, true)) {
            $primaryRole = $role;
            break;
        }
    }

    if ($primaryRole === 'superadmin') {
        return [
            'superadmin-dashboard.php' => 'Dashboard',
            'account.php' => 'My Account',
            'admin-accounts.php' => 'Admin Accounts',
            'employees.php' => 'User Management',
            'pds.php' => 'PDS',
            'csc-forms.php' => 'CSC Forms',
            'saln.php' => 'SALN Monitoring',
            'form-templates.php' => 'Form Templates',
            'reports.php' => 'Reports',
            'activity-logs.php' => 'Activity Logs',
            'system-settings.php' => 'Settings',
        ];
    }

    if ($primaryRole === 'admin' || $primaryRole === 'hr_officer') {
        return [
            'admin-dashboard.php' => 'Dashboard',
            'account.php' => 'My Account',
            'employees.php' => 'Employees',
            'leave-request.php' => 'Leave Requests',
            'pds-template-admin.php' => 'PDS Template',
            'csc-forms.php' => 'CSC Forms',
            'saln.php' => 'SALN Monitoring',
            'reports.php' => 'Reports',
            'documents.php' => 'Employee Files',
            'activity-logs.php' => 'Activity Logs',
        ];
    }

    // Default: employee (and also used when roles are missing/empty)
    return [
        'employee-dashboard.php' => 'Dashboard',
        'account.php' => 'My Account',
        'pds.php' => 'My PDS',
        'saln.php' => 'My SALN',
        'csc-forms.php' => 'Forms',
        'leave-request.php' => 'Leave Request',
        'documents.php' => 'My Documents',
        'help.php' => 'Help',
    ];
}

// Default navigation for non-logged in users
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

// Function to get employee data for auto-fill
function getEmployeeData($employee_id) {
    global $pdo;
    
    $sql = "SELECT 
                e.employee_number, 
                CONCAT(e.first_name, ' ', COALESCE(e.middle_name, ''), ' ', e.last_name) as full_name,
                e.position, 
                e.department,
                e.birthdate,
                e.gender,
                e.civil_status
            FROM employees e 
            WHERE e.id = ?";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$employee_id]);
    
    return $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
}

function resolve_employee_id_for_user($userId) {
    global $pdo;

    $userId = (int) $userId;
    if ($userId <= 0) {
        return 0;
    }

    try {
        $stmt = $pdo->prepare("SELECT id FROM employees WHERE id = ? LIMIT 1");
        $stmt->execute([$userId]);
        $directMatch = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($directMatch) {
            return (int) $directMatch['id'];
        }

        $stmt = $pdo->prepare("
            SELECT
                e.id,
                CASE
                    WHEN e.first_name = u.first_name AND e.last_name = u.last_name THEN 1
                    WHEN TRIM(CONCAT_WS(' ', e.first_name, e.middle_name, e.last_name)) = TRIM(CONCAT_WS(' ', u.first_name, u.middle_name, u.last_name)) THEN 2
                    WHEN TRIM(CONCAT_WS(' ', e.first_name, e.last_name)) = TRIM(CONCAT_WS(' ', u.first_name, u.last_name)) THEN 3
                    WHEN TRIM(CONCAT_WS(' ', e.first_name, COALESCE(e.middle_name, ''))) = TRIM(u.first_name) AND e.last_name = u.last_name THEN 4
                    ELSE 9
                END AS match_rank
            FROM users u
            JOIN employees e
              ON (
                    (e.first_name = u.first_name AND e.last_name = u.last_name)
                 OR TRIM(CONCAT_WS(' ', e.first_name, e.middle_name, e.last_name)) = TRIM(CONCAT_WS(' ', u.first_name, u.middle_name, u.last_name))
                 OR TRIM(CONCAT_WS(' ', e.first_name, e.last_name)) = TRIM(CONCAT_WS(' ', u.first_name, u.last_name))
                 OR (TRIM(CONCAT_WS(' ', e.first_name, COALESCE(e.middle_name, ''))) = TRIM(u.first_name) AND e.last_name = u.last_name)
              )
            WHERE u.id = ?
            ORDER BY match_rank, e.id
            LIMIT 1
        ");
        $stmt->execute([$userId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row) {
            return (int) $row['id'];
        }

        return $userId;
    } catch (PDOException $e) {
        error_log('Employee resolution failed: ' . $e->getMessage());
        return $userId;
    }
}

function ensure_employee_record_for_user($userId) {
    global $pdo;

    $resolvedId = resolve_employee_id_for_user($userId);
    if ($resolvedId > 0) {
        $stmt = $pdo->prepare("SELECT id FROM employees WHERE id = ? LIMIT 1");
        $stmt->execute([$resolvedId]);
        $existing = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($existing) {
            return (int) $existing['id'];
        }
    }

    $userId = (int) $userId;
    if ($userId <= 0) {
        return 0;
    }

    try {
        $hasMiddleName = false;
        try {
            $checkStmt = $pdo->query("SHOW COLUMNS FROM users LIKE 'middle_name'");
            $hasMiddleName = (bool) $checkStmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            $hasMiddleName = false;
        }

        $userSelect = $hasMiddleName
            ? "SELECT id, first_name, middle_name, last_name FROM users WHERE id = ? LIMIT 1"
            : "SELECT id, first_name, NULL AS middle_name, last_name FROM users WHERE id = ? LIMIT 1";

        $stmt = $pdo->prepare($userSelect);
        $stmt->execute([$userId]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$user) {
            return $resolvedId > 0 ? $resolvedId : 0;
        }

        $employeeNumber = 'EMP-' . str_pad((string) $userId, 4, '0', STR_PAD_LEFT);
        $stmt = $pdo->prepare("
            INSERT INTO employees (
                id, employee_number, first_name, middle_name, last_name, status
            ) VALUES (?, ?, ?, ?, ?, 'Active')
        ");
        $stmt->execute([
            $userId,
            $employeeNumber,
            $user['first_name'] ?? 'Employee',
            $user['middle_name'] ?? null,
            $user['last_name'] ?? 'User',
        ]);

        return $userId;
    } catch (PDOException $e) {
        error_log('Employee auto-create failed: ' . $e->getMessage());
        return $resolvedId > 0 ? $resolvedId : 0;
    }
}

// PDS Functions
function get_pds_record($employeeId, $year = null) {
    global $pdo;
    
    if ($year === null) {
        $year = date('Y');
    }
    
    try {
        $stmt = $pdo->prepare("
            SELECT * FROM pds_records 
            WHERE employee_id = ? AND year = ?
        ");
        $stmt->execute([$employeeId, $year]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Error getting PDS record: " . $e->getMessage());
        return false;
    }
}

function get_pds_personal_info($pdsId) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("
            SELECT * FROM pds_personal_info 
            WHERE pds_id = ?
        ");
        $stmt->execute([$pdsId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Error getting PDS personal info: " . $e->getMessage());
        return false;
    }
}

function get_pds_family_background($pdsId) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("
            SELECT * FROM pds_family_background 
            WHERE pds_id = ?
        ");
        $stmt->execute([$pdsId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Error getting PDS family background: " . $e->getMessage());
        return false;
    }
}

function get_pds_children($pdsId) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("
            SELECT * FROM pds_children 
            WHERE pds_id = ? 
            ORDER BY date_of_birth
        ");
        $stmt->execute([$pdsId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Error getting PDS children: " . $e->getMessage());
        return [];
    }
}

function get_pds_education($pdsId) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("
            SELECT * FROM pds_education 
            WHERE pds_id = ? 
            ORDER BY FIELD(level, 'Elementary', 'High School', 'College', 'Vocational/Trade Course', 'Graduate Studies'), period_from
        ");
        $stmt->execute([$pdsId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Error getting PDS education: " . $e->getMessage());
        return [];
    }
}

function get_pds_civil_service_eligibility($pdsId) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("
            SELECT * FROM pds_civil_service_eligibility 
            WHERE pds_id = ?
        ");
        $stmt->execute([$pdsId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Error getting PDS civil service eligibility: " . $e->getMessage());
        return [];
    }
}

function get_pds_work_experience($pdsId) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("
            SELECT * FROM pds_work_experience 
            WHERE pds_id = ? 
            ORDER BY inclusive_dates_from DESC
        ");
        $stmt->execute([$pdsId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Error getting PDS work experience: " . $e->getMessage());
        return [];
    }
}

function get_pds_voluntary_work($pdsId) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("
            SELECT * FROM pds_voluntary_work 
            WHERE pds_id = ?
        ");
        $stmt->execute([$pdsId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Error getting PDS voluntary work: " . $e->getMessage());
        return [];
    }
}

function get_pds_training_programs($pdsId) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("
            SELECT * FROM pds_training_programs 
            WHERE pds_id = ?
        ");
        $stmt->execute([$pdsId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Error getting PDS training programs: " . $e->getMessage());
        return [];
    }
}

function get_pds_other_information($pdsId) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("
            SELECT * FROM pds_other_information 
            WHERE pds_id = ?
        ");
        $stmt->execute([$pdsId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Error getting PDS other information: " . $e->getMessage());
        return false;
    }
}

function get_pds_questions($pdsId) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("
            SELECT * FROM pds_questions 
            WHERE pds_id = ?
        ");
        $stmt->execute([$pdsId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Error getting PDS questions: " . $e->getMessage());
        return false;
    }
}

function get_pds_references($pdsId) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("
            SELECT * FROM pds_references 
            WHERE pds_id = ? 
            ORDER BY reference_number
        ");
        $stmt->execute([$pdsId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Error getting PDS references: " . $e->getMessage());
        return [];
    }
}

function get_pds_government_id($pdsId) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("
            SELECT * FROM pds_government_id 
            WHERE pds_id = ?
        ");
        $stmt->execute([$pdsId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Error getting PDS government ID: " . $e->getMessage());
        return false;
    }
}

function get_pds_signature($pdsId) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("
            SELECT * FROM pds_signature 
            WHERE pds_id = ?
        ");
        $stmt->execute([$pdsId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Error getting PDS signature: " . $e->getMessage());
        return false;
    }
}

function create_pds_record($employeeId, $year = null) {
    global $pdo;
    
    if ($year === null) {
        $year = date('Y');
    }
    
    try {
        $pdo->beginTransaction();
        
        // Create main PDS record
        $stmt = $pdo->prepare("
            INSERT INTO pds_records (employee_id, year, status) 
            VALUES (?, ?, 'draft')
        ");
        $stmt->execute([$employeeId, $year]);
        $pdsId = $pdo->lastInsertId();
        
        // Initialize related records with empty data
        $tables = [
            'pds_personal_info' => [],
            'pds_family_background' => [],
            'pds_other_information' => [],
            'pds_questions' => [],
            'pds_signature' => []
        ];
        
        foreach ($tables as $table => $data) {
            $stmt = $pdo->prepare("INSERT INTO $table (pds_id) VALUES (?)");
            $stmt->execute([$pdsId]);
        }
        
        // Create empty references
        for ($i = 1; $i <= 3; $i++) {
            $stmt = $pdo->prepare("
                INSERT INTO pds_references (pds_id, reference_number) 
                VALUES (?, ?)
            ");
            $stmt->execute([$pdsId, $i]);
        }
        
        $pdo->commit();
        return $pdsId;
        
    } catch (PDOException $e) {
        $pdo->rollback();
        error_log("Error creating PDS record: " . $e->getMessage());
        return false;
    }
}

function update_pds_personal_info($pdsId, $data) {
    global $pdo;
    
    try {
        // Check if record exists
        $stmt = $pdo->prepare("SELECT id FROM pds_personal_info WHERE pds_id = ?");
        $stmt->execute([$pdsId]);
        
        if ($stmt->fetch()) {
            // Update existing record
            $fields = [];
            $values = [];
            foreach ($data as $key => $value) {
                if ($value !== null && $value !== '') {
                    $fields[] = "$key = ?";
                    $values[] = $value;
                }
            }
            $values[] = $pdsId;
            
            $sql = "UPDATE pds_personal_info SET " . implode(', ', $fields) . " WHERE pds_id = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute($values);
        } else {
            // Insert new record
            $fields = array_keys($data);
            $placeholders = str_repeat('?,', count($fields) - 1) . '?';
            $values = array_values($data);
            $values[] = $pdsId;
            
            $sql = "INSERT INTO pds_personal_info (" . implode(', ', $fields) . ", pds_id) VALUES ($placeholders, ?)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute($values);
        }
        
        return true;
    } catch (PDOException $e) {
        error_log("Error updating PDS personal info: " . $e->getMessage());
        return false;
    }
}

function update_pds_family_background($pdsId, $data) {
    global $pdo;
    
    try {
        // Similar logic as personal info
        $stmt = $pdo->prepare("SELECT id FROM pds_family_background WHERE pds_id = ?");
        $stmt->execute([$pdsId]);
        
        if ($stmt->fetch()) {
            $fields = [];
            $values = [];
            foreach ($data as $key => $value) {
                if ($value !== null && $value !== '') {
                    $fields[] = "$key = ?";
                    $values[] = $value;
                }
            }
            $values[] = $pdsId;
            
            $sql = "UPDATE pds_family_background SET " . implode(', ', $fields) . " WHERE pds_id = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute($values);
        } else {
            $fields = array_keys($data);
            $placeholders = str_repeat('?,', count($fields) - 1) . '?';
            $values = array_values($data);
            $values[] = $pdsId;
            
            $sql = "INSERT INTO pds_family_background (" . implode(', ', $fields) . ", pds_id) VALUES ($placeholders, ?)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute($values);
        }
        
        return true;
    } catch (PDOException $e) {
        error_log("Error updating PDS family background: " . $e->getMessage());
        return false;
    }
}

function update_pds_children($pdsId, $children) {
    global $pdo;
    
    try {
        $pdo->beginTransaction();
        
        // Delete existing children
        $stmt = $pdo->prepare("DELETE FROM pds_children WHERE pds_id = ?");
        $stmt->execute([$pdsId]);
        
        // Insert new children
        foreach ($children as $child) {
            if (!empty($child['name'])) {
                $stmt = $pdo->prepare("
                    INSERT INTO pds_children (pds_id, child_name, date_of_birth) 
                    VALUES (?, ?, ?)
                ");
                $stmt->execute([$pdsId, $child['name'], $child['birthdate']]);
            }
        }
        
        $pdo->commit();
        return true;
    } catch (PDOException $e) {
        $pdo->rollback();
        error_log("Error updating PDS children: " . $e->getMessage());
        return false;
    }
}

function update_pds_status($pdsId, $status, $approvedBy = null) {
    global $pdo;
    
    try {
        $sql = "UPDATE pds_records SET status = ?";
        $values = [$status];
        
        if ($status === 'approved' && $approvedBy) {
            $sql .= ", approved_at = NOW(), approved_by = ?";
            $values[] = $approvedBy;
        } elseif ($status === 'submitted') {
            $sql .= ", submitted_at = NOW()";
        }
        
        $sql .= " WHERE id = ?";
        $values[] = $pdsId;
        
        $stmt = $pdo->prepare($sql);
        return $stmt->execute($values);
        
    } catch (PDOException $e) {
        error_log("Error updating PDS status: " . $e->getMessage());
        return false;
    }
}

function get_employee_pds_list($employeeId) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("
            SELECT pr.*, e.first_name, e.last_name 
            FROM pds_records pr
            JOIN employees e ON pr.employee_id = e.id
            WHERE pr.employee_id = ?
            ORDER BY pr.year DESC
        ");
        $stmt->execute([$employeeId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Error getting employee PDS list: " . $e->getMessage());
        return [];
    }
}

function get_all_pds_records() {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("
            SELECT pr.*, e.first_name, e.last_name, e.employee_number 
            FROM pds_records pr
            JOIN employees e ON pr.employee_id = e.id
            ORDER BY pr.year DESC, e.last_name, e.first_name
        ");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Error getting all PDS records: " . $e->getMessage());
        return [];
    }
}
