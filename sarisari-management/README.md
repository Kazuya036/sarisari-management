# TindahanHub — Sari-Sari Store Management

A starter local web application using HTML, CSS, JavaScript, PHP 8+, PDO, and MySQL for XAMPP.

## Included modules
- Session-based login and roles (admin/cashier)
- Dashboard with daily/monthly sales, inventory value, low-stock alerts, and recent sales
- Product and stock management
- Point of Sale with cash payments, discounts, change, stock deduction, and receipt
- Sales history and admin-only void/reversal with inventory restoration
- Stock purchases and supplier references
- Expense tracking
- Reports, product ranking, and current inventory valuation
- Admin user management and basic audit logs
- CSRF tokens, PDO prepared statements, password hashing, output escaping

## Install on XAMPP
1. Copy the `sarisari-management` folder to `C:\xampp\htdocs\`.
2. Start Apache and MySQL in XAMPP Control Panel.
3. Open `http://localhost/phpmyadmin/`.
4. Import `database/database.sql`.
5. Check `config/database.php`; default XAMPP settings are host `127.0.0.1`, database `sarisari_db`, user `root`, blank password.
6. Visit `http://localhost/sarisari-management/setup.php` once to create the demo admin.
7. Sign in at `http://localhost/sarisari-management/login.php`.

Demo credentials:
- Username: `admin`
- Password: `Admin@12345`

**Important:** Delete `setup.php` after setup. Change the demo password before real use. This starter does not yet include a password-change screen.

## Notes
- This is intended for local development and learning. Before public deployment, configure HTTPS, backups, least-privilege database credentials, stronger operational controls, and security review.
- Chart.js and Google Fonts are loaded from CDNs, so those visual resources require internet access. Core pages can otherwise run locally.
- Customer/credit tables are included in the schema but a customer credit UI and collection workflow are not implemented in this starter.
- Supplier maintenance and category maintenance screens are not yet provided; purchases can use the seeded “General Supplier” or suppliers added directly to MySQL.
- Reports show estimated gross profit from the cost stored with each sale item; estimated net after expenses does not include all possible operating costs, taxes, shrinkage, or unpaid credit.
- The starter provides basic audit logging. For real-world use, review all authorization rules and add a proper password change/reset flow, backups, and operational testing.
