<?php
$pageTitle = 'My SALN';
$activePage = 'saln.php';
require_once 'includes/auth.php';
require_login();

// Get current user information
$currentUser = current_user();
$employee_id = $currentUser['id'] ?? null;

require_once 'includes/header.php';
require_once 'includes/template-helper.php';
require_once 'includes/data.php';

// Get employee data for auto-fill
$employee_data = [];
if ($employee_id) {
    $employee_data = getEmployeeData($employee_id);
}

// Get active SALN template
$saln_template = getActiveTemplate('saln');
?>

<section class="hero modern-hero">
    <div class="hero-content">
        <div class="hero-header">
            <div class="header-badge" style="background: linear-gradient(135deg, #f39c12, #e67e22); padding: 16px; border-radius: 12px; color: white; display: flex; align-items: center; justify-content: center; width: 60px; height: 60px;">
                <i class="fas fa-balance-scale" style="font-size: 32px;"></i>
            </div>
            <div>
                <h2 style="font-size: 36px; font-weight: 700; color: var(--primary); margin: 0 0 8px 0; line-height: 1.2;">My SALN</h2>
                <p style="color: var(--muted); font-size: 15px; margin: 0;">Statement of Assets, Liabilities, and Net Worth</p>
            </div>
        </div>

        <p style="color: var(--muted); line-height: 1.8; margin: 24px 0 28px 0; max-width: 650px; font-size: 15px;">
            File your annual SALN declaration with auto-filled personal information and comprehensive asset tracking.
        </p>

        <?php if ($saln_template): ?>
            <div class="template-info" style="background: #fff3cd; border: 1px solid #ffeaa7; border-radius: 8px; padding: 15px; margin-bottom: 20px;">
                <div style="display: flex; align-items: center; gap: 10px;">
                    <i class="fas fa-info-circle" style="color: #f39c12; font-size: 20px;"></i>
                    <div>
                        <strong>Active Template:</strong> <?php echo htmlspecialchars($saln_template['template_name']); ?> 
                        <span style="color: #666;">(Version: <?php echo htmlspecialchars($saln_template['version']); ?>)</span>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <div class="template-warning" style="background: #f8d7da; border: 1px solid #f5c6cb; border-radius: 8px; padding: 15px; margin-bottom: 20px;">
                <div style="display: flex; align-items: center; gap: 10px;">
                    <i class="fas fa-exclamation-triangle" style="color: #e74c3c; font-size: 20px;"></i>
                    <div>
                        <strong>No Active Template:</strong> Please contact HR to activate a SALN template.
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
</section>

<?php if ($saln_template): ?>
    <?php echo generateFormWithAutofill('saln', $employee_data); ?>
<?php else: ?>
    <section class="card">
        <div class="section-head">
            <div>
                <span class="tag">SALN Unavailable</span>
                <h3>Template Not Available</h3>
            </div>
        </div>
        <div class="alert alert-warning">
            <i class="fas fa-exclamation-triangle"></i>
            <p>No active SALN template is currently available. Please contact the HR department to activate a template for SALN filing.</p>
        </div>
    </section>
<?php endif; ?>

<script>
function downloadForm() {
    // Show print dialog for immediate printing
    window.print();
}

function saveSALN(formData) {
    // Show loading state
    const submitBtn = document.querySelector('button[type="submit"]');
    const originalText = submitBtn.innerHTML;
    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving...';
    submitBtn.disabled = true;

    // Simulate save process (replace with actual AJAX call)
    setTimeout(() => {
        submitBtn.innerHTML = '<i class="fas fa-check"></i> Saved Successfully!';
        submitBtn.style.background = '#27ae60';
        
        setTimeout(() => {
            submitBtn.innerHTML = originalText;
            submitBtn.disabled = false;
            submitBtn.style.background = '';
        }, 2000);
    }, 1500);
}

// Handle form submission
document.addEventListener('DOMContentLoaded', function() {
    const salnForm = document.getElementById('salnForm');
    if (salnForm) {
        salnForm.addEventListener('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            saveSALN(formData);
        });
    }

    // Auto-calculate net worth on input changes
    const numberInputs = document.querySelectorAll('input[type="number"]');
    numberInputs.forEach(input => {
        input.addEventListener('input', calculateNetWorth);
    });

    // Initial calculation
    calculateNetWorth();
});

function calculateNetWorth() {
    let totalAssets = 0;
    let totalLiabilities = 0;
    
    // Calculate total assets from real properties
    document.querySelectorAll('input[name="real_property_value[]"]').forEach(input => {
        totalAssets += parseFloat(input.value) || 0;
    });
    
    // Calculate total assets from personal properties
    document.querySelectorAll('input[name="personal_property_value[]"]').forEach(input => {
        totalAssets += parseFloat(input.value) || 0;
    });
    
    // Calculate total liabilities
    document.querySelectorAll('input[name="liability_balance[]"]').forEach(input => {
        totalLiabilities += parseFloat(input.value) || 0;
    });
    
    // Update calculated fields
    const totalAssetsField = document.getElementById('totalAssets');
    const totalLiabilitiesField = document.getElementById('totalLiabilities');
    const netWorthField = document.getElementById('netWorth');
    
    if (totalAssetsField) totalAssetsField.value = totalAssets.toFixed(2);
    if (totalLiabilitiesField) totalLiabilitiesField.value = totalLiabilities.toFixed(2);
    if (netWorthField) netWorthField.value = (totalAssets - totalLiabilities).toFixed(2);
}

function addRealPropertyRow() {
    const tbody = document.querySelector('table.asset-table tbody');
    const newRow = document.createElement('tr');
    newRow.innerHTML = `
        <td><input type="text" name="real_property_desc[]" placeholder="Property description"></td>
        <td><input type="text" name="real_property_loc[]" placeholder="Location"></td>
        <td><input type="number" name="real_property_year[]" placeholder="Year" min="1900" max="<?php echo date('Y'); ?>"></td>
        <td><input type="number" step="0.01" name="real_property_cost[]" placeholder="0.00"></td>
        <td><input type="number" step="0.01" name="real_property_value[]" placeholder="0.00"></td>
        <td><button type="button" class="btn btn-sm btn-danger" onclick="removeRow(this)">×</button></td>
    `;
    tbody.appendChild(newRow);
    
    // Add event listener to new inputs
    newRow.querySelectorAll('input[type="number"]').forEach(input => {
        input.addEventListener('input', calculateNetWorth);
    });
}

function addPersonalPropertyRow() {
    const tbody = document.querySelectorAll('table.asset-table tbody')[1];
    const newRow = document.createElement('tr');
    newRow.innerHTML = `
        <td><input type="text" name="personal_property_desc[]" placeholder="Property description"></td>
        <td><input type="number" name="personal_property_year[]" placeholder="Year" min="1900" max="<?php echo date('Y'); ?>"></td>
        <td><input type="number" step="0.01" name="personal_property_cost[]" placeholder="0.00"></td>
        <td><input type="number" step="0.01" name="personal_property_value[]" placeholder="0.00"></td>
        <td><button type="button" class="btn btn-sm btn-danger" onclick="removeRow(this)">×</button></td>
    `;
    tbody.appendChild(newRow);
    
    // Add event listener to new inputs
    newRow.querySelectorAll('input[type="number"]').forEach(input => {
        input.addEventListener('input', calculateNetWorth);
    });
}

function addLiabilityRow() {
    const tbody = document.querySelector('table.liability-table tbody');
    const newRow = document.createElement('tr');
    newRow.innerHTML = `
        <td><input type="text" name="creditor[]" placeholder="Creditor name"></td>
        <td><input type="text" name="liability_nature[]" placeholder="Nature of liability"></td>
        <td><input type="number" step="0.01" name="liability_balance[]" placeholder="0.00"></td>
        <td><button type="button" class="btn btn-sm btn-danger" onclick="removeRow(this)">×</button></td>
    `;
    tbody.appendChild(newRow);
    
    // Add event listener to new inputs
    newRow.querySelectorAll('input[type="number"]').forEach(input => {
        input.addEventListener('input', calculateNetWorth);
    });
}

function removeRow(button) {
    const row = button.closest('tr');
    row.remove();
    calculateNetWorth();
}
</script>

<style>
@media print {
    .hero, .form-actions, .template-info, button {
        display: none !important;
    }
    
    .saln-form-container {
        box-shadow: none;
        border: none;
        margin: 0;
        padding: 0;
    }
    
    .form-group input {
        border: 1px solid #000 !important;
        background: white !important;
    }
    
    .asset-table, .liability-table {
        border-collapse: collapse;
    }
    
    .asset-table th, .asset-table td,
    .liability-table th, .liability-table td {
        border: 1px solid #000 !important;
    }
}

.saln-form-container {
    max-width: 1000px;
    margin: 0 auto;
    padding: 30px;
    background: white;
    border: 1px solid #ddd;
    border-radius: 12px;
    box-shadow: 0 4px 6px rgba(0,0,0,0.1);
}

.form-header {
    text-align: center;
    margin-bottom: 40px;
    border-bottom: 3px solid #2c3e50;
    padding-bottom: 20px;
}

.form-header h3 {
    color: #2c3e50;
    font-size: 28px;
    font-weight: 700;
    margin: 0 0 10px 0;
}

.form-version {
    color: #7f8c8d;
    font-size: 14px;
    margin: 0;
}

.form-section {
    margin-bottom: 35px;
}

.form-section h4 {
    color: #2c3e50;
    font-size: 18px;
    font-weight: 600;
    border-bottom: 2px solid #3498db;
    padding-bottom: 8px;
    margin-bottom: 20px;
}

.form-section h5 {
    color: #34495e;
    font-size: 16px;
    font-weight: 600;
    margin: 20px 0 15px 0;
}

.form-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 20px;
    margin-bottom: 20px;
}

.form-group {
    display: flex;
    flex-direction: column;
}

.form-group label {
    font-weight: 600;
    margin-bottom: 8px;
    color: #2c3e50;
    font-size: 14px;
}

.form-group input,
.form-group select {
    padding: 12px 16px;
    border: 2px solid #ecf0f1;
    border-radius: 8px;
    font-size: 14px;
    transition: border-color 0.3s ease;
}

.form-group input:focus,
.form-group select:focus {
    outline: none;
    border-color: #3498db;
    box-shadow: 0 0 0 3px rgba(52, 152, 219, 0.1);
}

.form-group input[readonly] {
    background: #f8f9fa;
    color: #6c757d;
}

.asset-table, .liability-table {
    width: 100%;
    border-collapse: collapse;
    margin-bottom: 15px;
    background: white;
    border-radius: 8px;
    overflow: hidden;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.asset-table th, .asset-table td,
.liability-table th, .liability-table td {
    border: 1px solid #dee2e6;
    padding: 12px 8px;
    text-align: left;
}

.asset-table th, .liability-table th {
    background: linear-gradient(135deg, #3498db, #2980b9);
    color: white;
    font-weight: 600;
    font-size: 13px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.asset-table input, .liability-table input {
    width: 100%;
    border: none;
    padding: 8px;
    font-size: 13px;
    background: transparent;
}

.asset-table input:focus, .liability-table input:focus {
    background: #f8f9fa;
    outline: 2px solid #3498db;
    outline-offset: -2px;
    border-radius: 4px;
}

.calculation-row {
    display: grid;
    grid-template-columns: 250px 1fr;
    gap: 20px;
    margin-bottom: 15px;
    align-items: center;
}

.calculation-row label {
    font-weight: 600;
    color: #2c3e50;
    font-size: 15px;
}

.calculation-row input {
    padding: 12px 16px;
    border: 2px solid #ecf0f1;
    border-radius: 8px;
    font-size: 16px;
    font-weight: 600;
    background: #f8f9fa;
    color: #2c3e50;
}

.declaration-text {
    background: linear-gradient(135deg, #f8f9fa, #e9ecef);
    padding: 20px;
    border-left: 5px solid #3498db;
    border-radius: 8px;
    margin-bottom: 20px;
}

.declaration-text p {
    margin: 0;
    font-style: italic;
    color: #495057;
}

.signature-section {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 20px;
}

.form-actions {
    display: flex;
    gap: 15px;
    justify-content: center;
    margin-top: 40px;
    padding-top: 30px;
    border-top: 2px solid #dee2e6;
}

.btn {
    padding: 12px 24px;
    border: none;
    border-radius: 8px;
    font-size: 14px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 8px;
}

.btn-primary {
    background: linear-gradient(135deg, #3498db, #2980b9);
    color: white;
}

.btn-primary:hover {
    background: linear-gradient(135deg, #2980b9, #21618c);
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(52, 152, 219, 0.3);
}

.btn-outline {
    background: white;
    color: #3498db;
    border: 2px solid #3498db;
}

.btn-outline:hover {
    background: #3498db;
    color: white;
    transform: translateY(-2px);
}

.btn-sm {
    padding: 6px 12px;
    font-size: 12px;
}

.btn-danger {
    background: #e74c3c;
    color: white;
    border-radius: 50%;
    width: 30px;
    height: 30px;
    padding: 0;
    display: flex;
    align-items: center;
    justify-content: center;
}

.btn-danger:hover {
    background: #c0392b;
}

.asset-category {
    margin-bottom: 30px;
}

.template-info,
.template-warning {
    border-radius: 8px;
    padding: 15px;
    margin-bottom: 20px;
}

@media (max-width: 768px) {
    .form-row {
        grid-template-columns: 1fr;
        gap: 15px;
    }
    
    .signature-section {
        grid-template-columns: 1fr;
        gap: 15px;
    }
    
    .calculation-row {
        grid-template-columns: 1fr;
        gap: 10px;
    }
    
    .form-actions {
        flex-direction: column;
    }
    
    .saln-form-container {
        padding: 20px;
    }
}
</style>

<?php require_once 'includes/footer.php'; ?>
