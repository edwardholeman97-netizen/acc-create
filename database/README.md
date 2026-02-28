# Database Setup

1. Configure DB credentials in `.env` (see below).
2. Run migration: `php database/migrate.php`
3. Create admin user: `php database/seed_admin.php admin@example.com yourpassword`

Configure DB credentials in `.env`:
```
DB_HOST=localhost
DB_NAME=cds_accounts
DB_USER=root
DB_PASSWORD=your_password
```
