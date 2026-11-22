# Soko PHP Shop

A PHP 8 and MySQL/MariaDB shop for XAMPP.

## Requirements

- PHP 8.0 or later with PDO MySQL enabled
- MySQL or MariaDB (XAMPP's MariaDB is supported)
- Apache with PHP enabled

## Setup

1. Put the project in `C:\xampp\htdocs\swd\09-php-shop`.
2. Start Apache and MySQL from the XAMPP Control Panel.
3. Create and populate the shop database using one of these options:
   - Import the schema and run the CLI seeder:
     ```powershell
     & $env:ComSpec /c '"C:\xampp\mysql\bin\mysql.exe" -u root < schema.sql'
     C:\xampp\php\php.exe seed.php
     ```
   - Or create an empty database and import the schema-and-catalog export:
     ```powershell
     C:\xampp\mysql\bin\mysql.exe -u root -e "CREATE DATABASE swd_shop CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci"
     & $env:ComSpec /c '"C:\xampp\mysql\bin\mysql.exe" -u root swd_shop < database.sql'
     ```
     `database.sql` is an export snapshot and includes the seeded catalog. It does
     not contain customer orders.
4. The default local database connection is `127.0.0.1`, database `swd_shop`,
   user `root`, and an empty password. To use different credentials, set
   `DB_HOST`, `DB_NAME`, `DB_USER`, and `DB_PASS` in the Apache/PHP environment.
5. Configure the admin password:
   - Generate a password hash from PowerShell (replace the sample password):
     ```powershell
     @'
     <?php echo password_hash('replace-this-password', PASSWORD_DEFAULT), PHP_EOL;
     '@ | & C:\xampp\php\php.exe
     ```
   - Copy the resulting hash. In
     `C:\xampp\apache\conf\extra\httpd-xampp.conf`, add a `SetEnv` directive
     inside the active Apache configuration:
     ```apache
     SetEnv ADMIN_PASSWORD_HASH "$2y$..."
     ```
     The `...` is the complete hash produced by PHP. Do not put the plaintext
     password or hash in a public repository. Restart Apache after changing its
     configuration.
6. Visit `http://localhost/swd/09-php-shop/`.
   - Admin login: `http://localhost/swd/09-php-shop/admin/login.php`
   - Customer order lookup: `http://localhost/swd/09-php-shop/order-lookup.php`

Admin login remains unavailable until a valid `ADMIN_PASSWORD_HASH` is
configured. Passwords are checked with PHP's `password_verify()`; the application
does not ship with a default admin password.

## Order management

The admin dashboard reports today's paid, shipped, and delivered sales, order
counts by status, and the five best-selling products. Orders can be filtered by
status and date. Status changes follow `pending → paid → shipped → delivered`;
pending and paid orders can instead be cancelled. Cancelling an order restores
its item quantities to product stock in the same transaction as the status
change.

Customers can look up an order using its reference and the email address used at
checkout.

## Database export

To refresh the local schema-and-catalog snapshot from XAMPP:

```powershell
& $env:ComSpec /c '"C:\xampp\mysql\bin\mysqldump.exe" -u root swd_shop --no-tablespaces --skip-comments --skip-add-drop-table > database.sql'
```

Review the export before sharing it; a live database may contain customer
information. Do not commit credentials, password hashes, or customer data.
