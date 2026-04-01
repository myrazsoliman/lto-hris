# Form Templates Management System

## Overview

This system allows super admins to upload and manage dynamic templates for SALN and CSC forms. When new template formats are uploaded, they automatically become available to all user roles while maintaining auto-fill functionality.

## Features

### For Super Admins
- **Upload New Templates**: Upload PDF or Word document templates for SALN and CSC forms
- **Version Management**: Track multiple versions of templates with automatic activation
- **Template Management**: View, activate/deactivate templates
- **Access Control**: Only super admins can access template management

### For All Users
- **Dynamic Forms**: Forms automatically use the latest active template
- **Auto-fill**: Employee information is automatically populated
- **Consistent Experience**: All users see the same form format

## Database Schema

### form_templates Table
- `id`: Primary key
- `form_type`: ENUM('saln', 'csc')
- `template_name`: Human-readable name
- `file_path`: Path to uploaded file
- `version`: Version identifier
- `is_active`: Boolean flag for active template
- `uploaded_by`: User ID of uploader
- `uploaded_at`: Timestamp

## File Structure

```
├── form-templates.php          # Super admin template management interface
├── includes/
│   ├── template-helper.php     # Template generation and helper functions
│   └── data.php               # Employee data function for auto-fill
├── saln.php                   # Updated SALN page with dynamic forms
├── csc-forms.php              # Updated CSC forms page with dynamic forms
└── uploads/form_templates/     # Storage for uploaded template files
```

## Usage

### Super Admin: Managing Templates

1. **Access**: Navigate to "Form Templates" from super admin dashboard
2. **Upload New Template**:
   - Select form type (SALN or CSC)
   - Enter template name and version
   - Choose file (PDF or Word)
   - Click "Upload Template"
3. **View Templates**: See all uploaded templates with active/inactive status
4. **Automatic Activation**: New uploads automatically deactivate old templates

### All Users: Using Forms

1. **SALN Forms**: Go to SALN page → Click "Create SALN" → Form auto-fills with employee data
2. **CSC Forms**: Go to CSC Forms page → Click "Generate Form" → Form auto-fills with employee data
3. **Dynamic Updates**: When templates are updated, all users immediately see the new format

## Auto-fill Functionality

The system automatically populates:
- Employee Name
- Employee Number
- Position
- Department
- Birth Date
- Gender
- Civil Status

Data is pulled from the `employees` table based on the logged-in user's employee ID.

## Security Features

- **Role-based Access**: Only super admins can upload templates
- **File Validation**: Only PDF and Word documents allowed
- **Secure Upload**: Files stored in dedicated directory with validation
- **Session Protection**: All pages require authentication

## Technical Implementation

### Template Helper Functions

- `getActiveTemplate($form_type)`: Retrieves the current active template
- `getAllTemplates($form_type)`: Gets all templates for a form type
- `generateFormWithAutofill($form_type, $employee_data)`: Generates form with auto-filled data

### Form Generation

The system generates HTML forms dynamically based on the active template, including:
- SALN forms with assets, liabilities, and net worth calculations
- CSC forms with approval workflows
- JavaScript for dynamic field addition and calculations

## Installation

1. Run the database schema updates to create the `form_templates` table
2. Ensure the `uploads/form_templates/` directory exists and is writable
3. The system is ready to use for super admin template management

## Future Enhancements

- PDF generation for form downloads
- Template preview functionality
- Bulk template operations
- Template version rollback
- Email notifications for template updates
