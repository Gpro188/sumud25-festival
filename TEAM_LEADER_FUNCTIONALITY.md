# Team Leader Functionality for SUMUD'25 Arts Festival

## Overview
This document describes the team leader functionality that has been added to the SUMUD'25 Arts Festival management system. Team leaders can now log in to manage their specific team members, add new members, and upload photos for team members.

## Database Changes

### New Table: team_leaders
A new table has been added to the database schema to store team leader information:

```sql
CREATE TABLE team_leaders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    team_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (team_id) REFERENCES teams(id) ON DELETE CASCADE
);
```

### Updated Table: team_members
The team_members table has been updated to include a photo_path column:

```sql
ALTER TABLE team_members ADD COLUMN photo_path VARCHAR(255) DEFAULT NULL;
```

## New Files

### 1. Team Leader Login Page
- **File**: `admin/team_leader_login.php`
- **Access**: http://localhost/SUMUD25/admin/team_leader_login.php
- **Functionality**: 
  - Team leaders can log in with their username and password
  - Authentication is handled through the team_leaders table
  - Successful login redirects to the team leader dashboard

### 2. Team Leader Dashboard
- **File**: `admin/team_leader_dashboard.php`
- **Access**: Available after successful login
- **Functionality**:
  - View team information (name, leader, manager, color)
  - Add new team members with name, role, category, and chest number
  - Upload photos for team members
  - View all team members in a grid layout

### 3. Team Leader Logout
- **File**: `admin/team_leader_logout.php`
- **Access**: Available from the team leader dashboard
- **Functionality**: 
  - Destroys the session and logs the team leader out
  - Redirects to the login page

## Admin Panel Updates

### Teams Management Page
The existing `admin/teams.php` file has been updated to include team leader management:

- **Add Team Leaders**: Admins can create new team leader accounts
- **Reset Passwords**: Admins can reset team leader passwords
- **View Team Leaders**: List of all team leaders with their assigned teams

## Public Results Page Updates

### Photo Display
The public results page (`results.php`) and admin results page (`admin/results.php`) have been updated to display member photos:

- Photos are displayed next to winner names in results
- Photos are stored in the `assets/member_photos/` directory
- Only winners with uploaded photos will display images

## Security Features

### Password Security
- Team leader passwords are securely hashed using PHP's `password_hash()` function
- Authentication uses `password_verify()` for secure password checking

### Session Management
- Team leader sessions are properly managed with secure session handling
- Session variables include team leader ID, username, full name, and team ID
- Logout functionality properly destroys all session data

## Usage Instructions

### For Admins
1. Log in to the admin panel at `admin/login.php`
2. Navigate to the Teams page (`admin/teams.php`)
3. Scroll to the "Add Team Leader" section
4. Fill in the team leader details:
   - Username (must be unique)
   - Password
   - Full name
   - Assign to a team
5. Click "Add Team Leader"
6. To reset a password, find the team leader in the list and click "Reset Password"

### For Team Leaders
1. Navigate to the team leader login page at `admin/team_leader_login.php`
2. Enter your username and password
3. After successful login, you'll be redirected to your dashboard
4. From the dashboard, you can:
   - View your team information
   - Add new team members
   - Upload photos for team members
5. To log out, click the "Logout" link in the navigation

## File Structure
```
admin/
├── team_leader_login.php       # Team leader login page
├── team_leader_dashboard.php   # Team leader dashboard
├── team_leader_logout.php      # Team leader logout
└── teams.php                   # Updated admin teams management

assets/
└── member_photos/              # Directory for member photos (created automatically)

css/
└── styles.css                  # Updated with team leader styles

includes/
└── database.sql                # Updated database schema
```

## Default Credentials

### Team Leaders
- **Team Alpha Leader**: 
  - Username: `alpha_leader`
  - Password: `teamleader123`
- **Team Beta Leader**: 
  - Username: `beta_leader`
  - Password: `teamleader123`
- **Team Gamma Leader**: 
  - Username: `gamma_leader`
  - Password: `teamleader123`

## Implementation Notes

1. Photo uploads are automatically saved to the `assets/member_photos/` directory
2. Filenames are automatically generated to prevent conflicts
3. Supported photo formats: JPG, JPEG, PNG, GIF
4. Team leaders can only manage members of their assigned team
5. Admins have full control over all team leader accounts
6. All database operations use prepared statements to prevent SQL injection