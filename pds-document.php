<?php
$pageTitle = 'PDS Document View';
$activePage = 'pds.php';
require_once 'includes/auth.php';
require_roles(['employee', 'hr_officer', 'admin', 'superadmin']);
require_once 'includes/data.php';
require_once 'includes/pds-document-render.php';

$requestedEmployeeId = isset($_GET['employee_id']) ? (int) $_GET['employee_id'] : 0;
$year = isset($_GET['year']) ? (int) $_GET['year'] : (int) date('Y');

$currentUser = current_user();
$userRoles = get_user_roles($currentUser);
$currentUserEmployeeId = in_array('employee', $userRoles, true)
    ? ensure_employee_record_for_user((int) ($currentUser['id'] ?? 0))
    : pds_document_employee_id_from_user((int) ($currentUser['id'] ?? 0));
$employeeId = in_array('employee', $userRoles, true)
    ? (int) $currentUserEmployeeId
    : ($requestedEmployeeId > 0 ? $requestedEmployeeId : (int) $currentUserEmployeeId);

if ($employeeId <= 0) {
    http_response_code(400);
    exit('Missing employee ID.');
}

if (in_array('employee', $userRoles, true) && $currentUserEmployeeId !== $employeeId) {
    http_response_code(403);
    exit('You are not authorized to view this PDS document.');
}

$context = pds_document_get_context($employeeId, $year);
if ($context === null) {
    http_response_code(404);
    exit('No PDS record found for this employee and year.');
}

echo pds_document_render_html($context, false, false);
