# Installation Guide for SUMUD'25 Arts Festival Results System

## System Requirements

To run this application, you need:
1. A web server with PHP support (Apache, Nginx, etc.)
2. PHP 7.0 or higher
3. MySQL or MariaDB database

## Installation Options

### Option 1: Using XAMPP (Recommended for Windows)

1. Download and install XAMPP from https://www.apachefriends.org/index.html
2. Start the Apache and MySQL services from the XAMPP Control Panel
3. Copy all files from this folder to `C:\xampp\htdocs\sumud25\`
4. Open phpMyAdmin at http://localhost/phpmyadmin/
5. Create a new database named `sumud25_festival`
6. Import the database schema from `includes/database.sql`
7. Access the application at http://localhost/sumud25/

### Option 2: Using Built-in PHP Server (For Development)

1. Install PHP on your system
2. Navigate to this directory in the command prompt
3. Run the command: `php -S localhost:8000`
4. Access the application at http://localhost:8000

### Option 3: Deploying to a Web Host

1. Upload all files to your web hosting account
2. Create a MySQL database through your hosting control panel
3. Import the database schema from `includes/database.sql`
4. Update database credentials in `includes/config.php`
5. Access the application through your domain

## Database Configuration

1. Database Name: `sumud25_festival`
2. Default Admin Credentials:
   - Username: `admin`
   - Password: `sumud25`

## Security Considerations

Before deploying to production:

1. Change the default admin password
2. Update database credentials in `includes/config.php`
3. Ensure proper file permissions
4. Consider adding SSL for secure communication

## File Structure

```
sumud25/
├── admin/              # Admin panel files
├── css/                # Stylesheets
├── includes/           # Configuration and database files
├── js/                 # JavaScript files (currently empty)
├── assets/             # Media files (currently empty)
├── index.html          # Public homepage
├── results.php         # Public results page
├── README.md           # Project overview
└── INSTALLATION.md     # This file
```

## Access Points

### Public Pages
- Homepage: `/index.html`
- Results: `/results.php`

### Admin Panel
- Login: `/admin/login.php`
- Dashboard: `/admin/dashboard.php`

## Support

For any issues or questions, please contact the development team.