<?php
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';

// Create super admin and admin accounts
function create_admin_accounts() {
    try {
        // First, ensure roles exist
        echo "Setting up roles...\n";
        
        $roles = [
            ['superadmin', 'Super administrator with full system access'],
            ['admin', 'System administrator'],
            ['hr_officer', 'HR officer with personnel management permissions'],
            ['employee', 'Regular employee with self-service access']
        ];
        
        foreach ($roles as $role) {
            $stmt = db()->prepare('SELECT id FROM roles WHERE name = ? LIMIT 1');
            $stmt->execute([$role[0]]);
            $existingRole = $stmt->fetch();
            
            if (!$existingRole) {
                $insertStmt = db()->prepare('INSERT INTO roles (name, description) VALUES (?, ?)');
                $insertStmt->execute($role);
                echo "✅ Created role: {$role[0]}\n";
            }
        }
        
        echo "\nCreating admin accounts...\n";
        // Super Admin account
        $superAdminEmail = 'superadmin@lto.gov.ph';
        $superAdminPassword = 'SuperAdmin123!';
        
        if (!user_exists($superAdminEmail)) {
            $stmt = db()->prepare(
                'INSERT INTO users (first_name, last_name, email, password, created_at)
                 VALUES (?, ?, ?, ?, NOW())'
            );
            $stmt->execute([
                'Super',
                'Admin',
                $superAdminEmail,
                password_hash($superAdminPassword, PASSWORD_DEFAULT)
            ]);
            
            $superAdminId = db()->lastInsertId();
            
            // Assign superadmin role
            $roleStmt = db()->prepare('SELECT id FROM roles WHERE name = ? LIMIT 1');
            $roleStmt->execute(['superadmin']);
            $superAdminRole = $roleStmt->fetch();
            
            if ($superAdminRole) {
                $userRoleStmt = db()->prepare('INSERT INTO user_roles (user_id, role_id) VALUES (?, ?)');
                $userRoleStmt->execute([$superAdminId, $superAdminRole['id']]);
                echo "✅ Super Admin account created successfully!\n";
                echo "   Email: $superAdminEmail\n";
                echo "   Password: $superAdminPassword\n\n";
            }
        } else {
            echo "ℹ️ Super Admin account already exists.\n\n";
        }
        
        // Admin account
        $adminEmail = 'admin@lto.gov.ph';
        $adminPassword = 'Admin123!';
        
        if (!user_exists($adminEmail)) {
            $stmt = db()->prepare(
                'INSERT INTO users (first_name, last_name, email, password, created_at)
                 VALUES (?, ?, ?, ?, NOW())'
            );
            $stmt->execute([
                'System',
                'Administrator',
                $adminEmail,
                password_hash($adminPassword, PASSWORD_DEFAULT)
            ]);
            
            $adminId = db()->lastInsertId();
            
            // Assign admin role
            $roleStmt = db()->prepare('SELECT id FROM roles WHERE name = ? LIMIT 1');
            $roleStmt->execute(['admin']);
            $adminRole = $roleStmt->fetch();
            
            if ($adminRole) {
                $userRoleStmt = db()->prepare('INSERT INTO user_roles (user_id, role_id) VALUES (?, ?)');
                $userRoleStmt->execute([$adminId, $adminRole['id']]);
                echo "✅ Admin account created successfully!\n";
                echo "   Email: $adminEmail\n";
                echo "   Password: $adminPassword\n\n";
            }
        } else {
            echo "ℹ️ Admin account already exists.\n\n";
        }
        
        echo "🎉 Account setup complete!\n";
        echo "You can now login with these credentials.\n";
        
    } catch (Exception $e) {
        echo "❌ Error creating accounts: " . $e->getMessage() . "\n";
    }
}

// Run the function
create_admin_accounts();
?>
