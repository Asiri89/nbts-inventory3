# NBTS Inventory Management System

A comprehensive web-based inventory management system designed specifically for the National Blood Transfusion Service (NBTS) Sri Lanka. Built with pure PHP, MySQL, and Bootstrap 5 for easy maintenance and deployment.

## Features

### 🔐 User Authentication & Roles
- **Multi-role system**: Admin, Inventory Manager, View-Only, Auditor
- **Branch-level access control**: Users restricted to their assigned branches
- **Session management**: Automatic timeout and security features
- **Activity logging**: Complete audit trail of all user actions

### 📊 Master Data Management
- **Branches**: Manage blood bank locations with contact details
- **Items**: Complete item catalog with categories, suppliers, and specifications
- **Categories**: Medical and non-medical equipment classification
- **Suppliers**: Vendor database with contact and tax information

### 📦 Inventory Operations
- **Stock Management**: Add, transfer, adjust, and track inventory
- **Multi-branch Support**: Transfer items between locations
- **Serial/Batch Tracking**: Individual item identification
- **Status Management**: Active, maintenance, repair, disposed states

### 🔧 Asset Lifecycle Tracking
- **Installation Tracking**: Location and staff assignment
- **Warranty Management**: Automatic expiry alerts
- **Maintenance Scheduling**: Preventive and corrective maintenance
- **Service History**: Complete maintenance and repair logs

### 📈 Reports & Dashboard
- **Real-time Dashboard**: Key metrics and alerts
- **Comprehensive Reports**: Stock, movements, maintenance, warranty
- **Export Functionality**: CSV export for all reports
- **Visual Analytics**: Charts and graphs for data visualization

### 📱 Modern Features
- **QR Code Support**: Generate and print asset labels
- **Mobile Responsive**: Works on all devices
- **File Management**: Upload warranties, manuals, photos
- **Email Notifications**: Automated alerts for critical events

## Technical Specifications

### Requirements
- **PHP**: 7.4 or higher
- **MySQL**: 5.7 or higher
- **Web Server**: Apache with mod_rewrite
- **Extensions**: MySQLi, GD (for QR codes), JSON

### Architecture
- **Backend**: Pure PHP with procedural and minimal OOP
- **Database**: MySQL with MySQLi prepared statements
- **Frontend**: HTML5, CSS3, JavaScript, Bootstrap 5
- **Security**: SQL injection prevention, XSS protection, CSRF tokens

### Folder Structure
```
nbts-inventory/
├── api/                    # API endpoints
├── config/                 # Configuration files
├── includes/              # Common includes and functions
├── pages/                 # Application pages
│   ├── auth/             # Authentication pages
│   ├── dashboard.php     # Main dashboard
│   ├── items/            # Item management
│   ├── inventory/        # Inventory operations
│   ├── categories/       # Category management
│   ├── suppliers/        # Supplier management
│   ├── branches/         # Branch management
│   ├── users/            # User management
│   ├── maintenance/      # Maintenance scheduling
│   ├── reports/          # Report generation
│   └── logs/             # Activity logs
├── uploads/              # File uploads
│   ├── documents/        # Document storage
│   ├── qr_codes/         # Generated QR codes
│   └── reports/          # Generated reports
├── database/             # Database schema
├── .htaccess            # Apache configuration
└── index.php           # Application entry point
```

## Installation

### 1. Database Setup
```sql
-- Import the database schema
mysql -u username -p database_name < database/schema.sql
```

### 2. Configuration
Edit `config/database.php`:
```php
define('DB_HOST', 'localhost');
define('DB_USERNAME', 'your_username');
define('DB_PASSWORD', 'your_password');
define('DB_NAME', 'nbts_inventory');
```

### 3. File Permissions
```bash
chmod 755 uploads/
chmod 755 uploads/documents/
chmod 755 uploads/qr_codes/
chmod 755 uploads/reports/
```

### 4. Web Server Configuration
Ensure Apache mod_rewrite is enabled and .htaccess files are allowed.

## Default Login Credentials

| Role | Username | Password | Access Level |
|------|----------|----------|--------------|
| Admin | admin | password | Full system access |
| Inventory Manager | inv_manager_nbc | password | NBC branch management |
| View Only | viewer_galle | password | Galle branch read-only |
| Auditor | auditor | password | All branches read-only |

**⚠️ Important**: Change default passwords immediately after installation.

## Usage Guide

### Dashboard
- **Statistics Overview**: Total inventory value, low stock alerts, warranty expiry
- **Quick Actions**: Direct access to common operations
- **Recent Activities**: Latest system activities and movements
- **Visual Charts**: Inventory distribution by category

### Inventory Management
1. **Add Stock**: Register new inventory with supplier and warranty details
2. **Transfer Stock**: Move items between branches with approval workflow
3. **Adjust Stock**: Handle corrections, damages, and losses
4. **Track Assets**: Monitor location, assignment, and status

### Maintenance Scheduling
1. **Schedule Maintenance**: Set up preventive and corrective maintenance
2. **Track Due Dates**: Automatic alerts for upcoming maintenance
3. **Record History**: Complete maintenance and repair logs
4. **Cost Tracking**: Monitor maintenance expenses

### Reporting
- **Stock Summary**: Current inventory levels by branch/category
- **Low Stock Report**: Items below reorder levels
- **Warranty Expiry**: Items with expiring warranties
- **Movement History**: Complete audit trail of stock movements
- **Maintenance Reports**: Scheduled and completed maintenance

## Security Features

### Data Protection
- **SQL Injection Prevention**: All queries use prepared statements
- **XSS Protection**: Input sanitization and output encoding
- **Session Security**: Timeout, regeneration, and secure cookies
- **File Upload Security**: Type validation and secure storage

### Access Control
- **Role-based Permissions**: Granular access control by user role
- **Branch Restrictions**: Users limited to assigned branches
- **Activity Logging**: Complete audit trail of all actions
- **Password Security**: Hashed passwords with strong requirements

### System Security
- **HTTPS Support**: SSL/TLS encryption ready
- **Security Headers**: XSS, clickjacking, and content-type protection
- **File Access Control**: Sensitive files protected from direct access
- **Error Handling**: Secure error messages without information disclosure

## Customization

### Adding New Roles
1. Update the `users` table enum for the `role` column
2. Modify `Auth::getPermissions()` in `includes/auth.php`
3. Update navigation and access controls in templates

### Custom Reports
1. Create new report file in `pages/reports/`
2. Add database queries for required data
3. Implement export functionality
4. Add navigation links

### Additional Features
- **Email Integration**: Configure SMTP settings in `config/config.php`
- **Barcode Support**: Extend QR code functionality for barcodes
- **API Integration**: Add external system integrations
- **Mobile App**: Use existing API endpoints for mobile development

## Maintenance

### Regular Tasks
- **Database Backup**: Regular automated backups
- **Log Rotation**: Archive old activity logs
- **File Cleanup**: Remove old temporary files
- **Security Updates**: Keep PHP and MySQL updated

### Monitoring
- **Error Logs**: Monitor PHP and application error logs
- **Performance**: Track database query performance
- **Storage**: Monitor file upload storage usage
- **User Activity**: Review activity logs for suspicious behavior

## Support

### Documentation
- **User Manual**: Detailed user guide available
- **API Documentation**: Complete API reference
- **Database Schema**: ERD and table documentation
- **Deployment Guide**: Production deployment instructions

### Troubleshooting
- **Common Issues**: FAQ and solutions
- **Error Codes**: Complete error reference
- **Performance Tuning**: Optimization guidelines
- **Backup/Recovery**: Disaster recovery procedures

## License

This system is developed specifically for the National Blood Transfusion Service (NBTS) Sri Lanka. All rights reserved.

## Version History

- **v1.0.0** (2024): Initial release with core functionality
- Complete inventory management system
- Multi-role user authentication
- Comprehensive reporting
- Mobile-responsive design
- QR code integration

---

**Developed for NBTS Sri Lanka** - A comprehensive solution for medical equipment and inventory management in healthcare facilities.