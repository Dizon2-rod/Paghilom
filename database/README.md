# Paghilom Café - Database Setup Guide

## Overview
This directory contains the complete database schema for **Paghilom Café Management System**.

All previous separate SQL files have been **consolidated into one unified file**: `paghilom_cafe_complete.sql`

---

## 📦 What's Included

The unified database schema includes **28 tables**:

### Core Tables
1. **users** - Admin, staff, and customer accounts
2. **clients** - Customer loyalty tracking with points balance
3. **categories** - Product categories
4. **products** - Menu items and products
5. **product_images** - Multiple images per product
6. **stores** - Store locations
7. **orders** - Customer orders
8. **order_items** - Order line items
9. **order_item_options** - Add-ons per order item

### Loyalty & Rewards System
10. **point_transactions** - Point earning and spending history
11. **loyalty_ledger** - Alternative points tracking
12. **reward_catalog** - Available rewards (primary)
13. **rewards** - Simplified rewards (alternative)
14. **redemptions** - User reward claims
15. **vouchers** - Issued voucher codes

### Customization & Options
16. **addons** - Extra options (espresso shots, sauces, etc.)
17. **milks** - Milk alternatives
18. **product_addons** - Which addons apply to which products
19. **product_milks** - Which milk options apply to which products

### Inventory Management
20. **ingredients** - Raw ingredients tracking
21. **product_recipes** - Recipe ingredients per product
22. **stock_movements** - Product stock history
23. **ingredient_movements** - Ingredient stock history

### Content & Settings
24. **gallery** - Photo gallery
25. **pages** - CMS pages (about, FAQ, contact)
26. **settings** - System configuration
27. **promos** - Promotional campaigns
28. **login_attempts** - Security audit log

---

## 🚀 Installation Instructions

### Method 1: Using phpMyAdmin (Recommended for XAMPP)

1. Open **phpMyAdmin** in your browser:
   ```
   http://localhost/phpmyadmin
   ```

2. Click on the **"Import"** tab

3. Click **"Choose File"** and select:
   ```
   C:\xampp\htdocs\paghilom_cafe\database\paghilom_cafe_complete.sql
   ```

4. Click **"Go"** at the bottom

5. Wait for the import to complete (should see success message)

6. The database `paghilom_cafe` will be created with all 28 tables and sample data

### Method 2: Using MySQL Command Line

1. Open Command Prompt or PowerShell

2. Navigate to the database directory:
   ```powershell
   cd C:\xampp\htdocs\paghilom_cafe\database
   ```

3. Run MySQL:
   ```powershell
   C:\xampp\mysql\bin\mysql.exe -u root -p
   ```

4. Execute the SQL file:
   ```sql
   SOURCE paghilom_cafe_complete.sql;
   ```

5. Verify installation:
   ```sql
   USE paghilom_cafe;
   SHOW TABLES;
   ```

---

## 🔑 Default Credentials

After installation, use these credentials to log in:

- **Email**: `admin@paghilom.cafe`
- **Password**: `Admin123!`
- **Role**: Owner (full access)

⚠️ **IMPORTANT**: Change the default password immediately after first login!

---

## 📊 Sample Data Included

The schema includes pre-populated data:

### Categories (6)
- Coffee
- Non-Coffee
- Matcha Series
- Pastries
- Snacks
- Add-ons

### Rewards (8)
- Free Espresso (30 points)
- ₱50 Discount (100 points)
- Free Pastry (40 points)
- ₱100 Discount (200 points)
- Free Iced Coffee (50 points)
- ₱200 Discount (400 points)
- Size Upgrade (25 points)
- Free Meal Combo (150 points)

### Add-ons (6)
- Espresso Shot
- Extra Sauce
- Extra Syrup
- Extra Milk
- Whipped Cream
- Chocolate Drizzle

### Milk Options (5)
- Oat Milk
- Almond Milk
- Soy Milk
- Coconut Milk
- Fresh Milk

### Default Store
- Paghilom Café - Main Branch
- Location: 4091 Sitio 2 Barangay Bagumbayan, Sta. Cruz, Laguna

---

## 🔧 Configuration Settings

The following settings are pre-configured:

| Setting | Value |
|---------|-------|
| Site Name | Paghilom Café |
| Tagline | Hayaang sarili ay MAGHILOM |
| Contact Phone | 09287197722 |
| Opening Hours | Mon-Sun 8:00 AM - 9:00 PM |
| Points System | ₱5 spent = 1 point earned |
| Facebook | [Link included] |

---

## ✅ Verification

After importing, verify the installation:

```sql
USE paghilom_cafe;

-- Check total tables
SELECT COUNT(*) as TotalTables 
FROM information_schema.TABLES 
WHERE TABLE_SCHEMA = 'paghilom_cafe';
-- Should return: 28

-- Check if admin exists
SELECT * FROM users WHERE role = 'owner';

-- Check rewards
SELECT name, points_cost FROM reward_catalog ORDER BY points_cost;

-- Check settings
SELECT `key`, `value` FROM settings WHERE `group` = 'loyalty';
```

---

## 🐛 Troubleshooting

### Error: "Table already exists"
The schema uses `CREATE TABLE IF NOT EXISTS`, so it's safe to run multiple times.

### Error: "Cannot create foreign key constraint"
Run the script again. The schema includes `SET FOREIGN_KEY_CHECKS = 0;` at the beginning.

### Missing tables
Check that your MySQL/MariaDB version is 5.7+ and supports InnoDB engine.

### Import timeout
If importing via phpMyAdmin times out, use Method 2 (command line) instead.

---

## 📝 Notes

- **Database Name**: `paghilom_cafe`
- **Character Set**: `utf8mb4_unicode_ci` (supports emojis and special characters)
- **Engine**: InnoDB (supports transactions and foreign keys)
- **Compatible**: MySQL 5.7+, MariaDB 10.2+

---

## 🔄 Updates & Maintenance

To update the schema:
1. Backup your database first
2. Run the new SQL file
3. Existing data will be preserved (uses `ON DUPLICATE KEY UPDATE`)

---

## 📞 Support

For issues or questions:
- Check the main project README
- Review config.php for database connection settings
- Ensure XAMPP MySQL service is running

---

**Last Updated**: October 26, 2025  
**Version**: 1.0 (Unified Schema)
