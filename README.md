# SUMUD'25 Arts Festival Results Publishing System

A web application for publishing results of the SUMUD'25 arts festival with both public viewing and admin management capabilities.

## Features

### Public Viewing
- View competition results without login
- Filter results by category, program, or participant name
- See overall team standings and category-wise rankings
- Highlighted point system display
- Color-coded teams for easy identification

### Admin Panel
- Secure login system (admin/sumud25)
- Manage teams (add teams, leaders, managers, and members)
- Assign custom RGB colors to teams for visual identification
- Manage competition programs
- Update competition results with point calculation
- Dashboard with statistics overview

## Technology Stack
- **Frontend**: HTML, CSS
- **Backend**: PHP
- **Database**: MySQL (schema provided)
- **Authentication**: Session-based

## Project Structure
```
SUMUD'25/
├── admin/
│   ├── dashboard.php
│   ├── login.php
│   ├── logout.php
│   ├── programs.php
│   ├── results.php
│   └── teams.php
├── css/
│   └── styles.css
├── includes/
│   ├── config.php
│   └── database.sql
├── index.html
├── results.php
└── README.md
```

## Setup Instructions

1. **Web Server**: Deploy the files to a web server with PHP support (Apache, Nginx, etc.)

2. **Database Setup**:
   - Create a MySQL database
   - Execute the SQL script in `includes/database.sql`
   - Update database credentials in `includes/config.php`

3. **Configuration**:
   - Modify `includes/config.php` with your database credentials
   - Change the default admin password for production use

## Point System

### Individual Category
- 1st Place: 5 points
- 2nd Place: 3 points
- 3rd Place: 1 point

### Group Category
- 1st Place: 7 points
- 2nd Place: 5 points
- 3rd Place: 2 points

### General Category
- 1st Place: 10 points
- 2nd Place: 7 points
- 3rd Place: 5 points

### Grade Points (Added to position points)
- A Grade: 5 points
- B Grade: 3 points

## Access Points

### Public Pages
- `index.html` - Main landing page
- `results.php` - View and filter competition results

### Admin Panel
- `admin/login.php` - Admin login (unique link, not visible on public pages)
- `admin/dashboard.php` - Admin dashboard
- `admin/teams.php` - Team management (with RGB color selection)
- `admin/programs.php` - Program management
- `admin/results.php` - Results management

## Default Admin Credentials
- **Username**: admin
- **Password**: sumud25

> ⚠️ **Important**: Change the default admin password in production!

## Customization

The application can be easily customized for different festivals by:
1. Updating the categories in the database
2. Modifying the point system in `includes/config.php`
3. Adjusting the UI colors in `css/styles.css`

## License
This project is developed specifically for SUMUD'25 Arts Festival and is not licensed for redistribution.