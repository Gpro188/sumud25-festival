# Installation Guide: Team Leader Photo Management

This guide explains how to implement the team leader photo management functionality in your SUMUD'25 Festival system.

## Prerequisites

1. PHP 7.0 or higher
2. MySQL 5.6 or higher
3. Web server (Apache, Nginx, etc.)
4. Existing SUMUD'25 Festival system installation

## Installation Steps

### 1. Update Database Schema

Run the following SQL command to add the photo_path column to the team_leaders table:

```sql
ALTER TABLE team_leaders ADD COLUMN photo_path VARCHAR(255);
```

Alternatively, you can recreate the table using the updated schema in `updated_database.sql`.

### 2. Add Photo Handler Class

Copy the `team_leader_photo_handler.php` file to your `includes/` directory:

```
cp includes/team_leader_photo_handler.php /path/to/your/project/includes/
```

### 3. Update Admin Teams Management Page

Replace your existing `admin/teams.php` file with the updated version that includes photo management functionality.

Key features added:
- Photo upload modal for each team leader
- File validation (type and size)
- Photo display in the team leaders table
- Integration with existing team leader management actions

### 4. Update Results Display Pages

Update your `results.php` and `admin/results.php` files to display team leader photos:

- Overall team standings now show team leader photos
- Program results display winning team leader photos
- Fallback icons for team leaders without photos

### 5. Create Uploads Directory

Create an uploads directory in your project root with appropriate permissions:

```bash
mkdir uploads
chmod 755 uploads
```

This directory will store all uploaded team leader photos.

## Configuration

### File Upload Settings

The photo handler is configured with the following defaults:
- Maximum file size: 5MB
- Allowed file types: JPG, PNG, GIF
- Upload directory: `../uploads/`

To modify these settings, edit the `TeamLeaderPhotoHandler` class in `includes/team_leader_photo_handler.php`.

## Usage

### For Administrators

1. Log in to the admin panel
2. Navigate to the "Manage Teams" section
3. For each team leader:
   - Click the "Photo" button in the actions column
   - Select a photo file (JPG, PNG, or GIF)
   - Click "Update Photo" to save

### For Public Display

Team leader photos will automatically appear in:
- Overall team standings on the results page
- Program results showing winning teams

## Troubleshooting

### Upload Errors

If you encounter upload errors:
1. Check that the `uploads/` directory exists and has write permissions
2. Verify file size is under 5MB
3. Ensure file type is JPG, PNG, or GIF
4. Check PHP upload limits in `php.ini`:
   - `upload_max_filesize`
   - `post_max_size`

### Photos Not Displaying

If uploaded photos aren't displaying:
1. Verify the `photo_path` column was added to the database
2. Check that uploaded files exist in the `uploads/` directory
3. Confirm file permissions allow web server to read the files

## Support

For issues with the photo management functionality, please check:
1. PHP error logs
2. Browser developer console for JavaScript errors
3. Database connection and permissions

## Version Information

This implementation is for SUMUD'25 Festival system version 1.0 with team leader photo management.