
# Paghilom Cafe Website Management System

A complete, modern PHP-based website and admin panel for managing your cafe business. Features include product management, user authentication, order processing, inventory tracking, and more.

## Features

### 🎯 Core Features
- **Complete Authentication System** - Register, login, password reset, email verification
- **Admin Dashboard** - Comprehensive admin panel for managing all aspects of the business
- **Product Management** - Add, edit, delete products with images, categories, and inventory tracking
- **Category Management** - Organize products into categories with custom settings
- **User Management** - Customer accounts with role-based access control
- **Inventory Tracking** - Stock management with low-stock alerts
- **Order Management** - Process and track customer orders
- **Responsive Design** - Works perfectly on desktop, tablet, and mobile devices

### 🔐 Security Features
- **CSRF Protection** - All forms protected against CSRF attacks
- **Password Security** - Strong password requirements and secure hashing
- **Rate Limiting** - Login attempt limiting to prevent brute force attacks
- **Account Locking** - Temporary account locks after failed attempts
- **SQL Injection Protection** - Prepared statements throughout
- **XSS Protection** - Input sanitization and output escaping

### 📱 Modern UI/UX
- **Bootstrap 5** - Modern, responsive design framework
- **Font Awesome Icons** - Professional iconography
- **Clean Interface** - Intuitive admin panel design
- **Mobile-First** - Optimized for all device sizes

## Quick Start

### 1. Database Setup
1. Create a new MySQL database named `paghilom_cafe`
2. Import the unified schema:
   ```sql
   mysql -u root -p paghilom_cafe < database/schema_unified.sql
   ```

### 2. File Setup
1. Copy all files to your web server (PHP 8+ recommended)
2. Ensure the `uploads/` directory is writable:
   ```bash
   chmod 755 uploads/
   chmod 755 uploads/products/
   ```

### 3. Configuration
1. Update `config.php` with your database credentials:
   ```php
   define('DB_HOST', 'localhost');
   define('DB_USER', 'your_username');
   define('DB_PASS', 'your_password');
   define('DB_NAME', 'paghilom_cafe');
   ```

### 4. Default Login
- **Admin Login**: Visit `/admin/login.php`
- **Email**: admin@paghilom.local
- **Password**: ChangeMe123! (change immediately)

### 5. Customer Registration
- **Customer Registration**: Visit `/register.php`
- **Customer Login**: Visit `/login.php`

## File Structure

```
paghilom_cafe/
├── admin/                 # Admin panel files
│   ├── categories.php     # Category management
│   ├── category_edit.php  # Add/edit categories
│   ├── login.php          # Admin login
│   ├── products.php       # Product management
│   ├── product_edit.php   # Add/edit products
│   └── ...
├── assets/                # Static assets
│   ├── css/              # Stylesheets
│   ├── js/               # JavaScript files
│   └── img/              # Images and placeholders
├── database/              # Database files
│   └── schema_unified.sql # Complete database schema
├── uploads/               # User uploads
│   └── products/         # Product images
├── partials/              # Reusable components
│   ├── header.php         # Site header
│   └── footer.php         # Site footer
├── config.php             # Main configuration
├── login.php              # Customer login
├── register.php           # Customer registration
├── logout.php             # Logout handler
├── forgot_password.php    # Password reset request
├── reset_password.php     # Password reset form
├── verify_email.php       # Email verification
└── index.php              # Homepage
```

## Admin Features

### Product Management
- **Add Products**: Complete product information with images
- **Edit Products**: Update all product details
- **Delete Products**: Remove products with confirmation
- **Inventory Tracking**: Stock levels and low-stock alerts
- **Category Assignment**: Organize products by category
- **Featured Products**: Highlight products on homepage
- **Status Management**: Activate/deactivate products

### Category Management
- **Create Categories**: Add new product categories
- **Edit Categories**: Update category information
- **Delete Categories**: Remove categories (with product reassignment)
- **Sort Order**: Control category display order
- **Low Stock Thresholds**: Set default thresholds per category

### User Management
- **Customer Accounts**: View and manage customer accounts
- **Role Management**: Owner, admin, staff, and customer roles
- **Account Status**: Activate/deactivate user accounts
- **Login Tracking**: Monitor user login attempts

## Security Considerations

1. **Change Default Password**: Immediately change the default admin password
2. **Database Security**: Use strong database credentials
3. **File Permissions**: Ensure proper file permissions on uploads directory
4. **HTTPS**: Use HTTPS in production for secure data transmission
5. **Regular Updates**: Keep PHP and dependencies updated

## Customization

### Adding New Features
1. **Database**: Add new tables to `database/schema_unified.sql`
2. **Admin Panel**: Create new admin pages in the `admin/` directory
3. **Frontend**: Add new pages in the root directory
4. **Styling**: Modify CSS files in `assets/css/`

### Email Configuration
Update the email functions in `config.php` to use your preferred email service:
- `send_verification_email()`
- `send_password_reset_email()`

## Support

For support and customization requests, please refer to the code comments and documentation within the files.

## License

This project is provided as-is for educational and commercial use. Please ensure compliance with any third-party libraries used.

---

**Paghilom Cafe** - *Hayaang sarili ay MAGHILOM* (Let yourself heal)
