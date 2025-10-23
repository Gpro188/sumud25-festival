@echo off
echo SUMUD'25 Arts Festival Results System
echo =====================================
echo.
echo To run this application, you need to have PHP installed.
echo.
echo If PHP is installed, this script will start a local development server.
echo.
echo Press any key to attempt to start the server on http://localhost:8000
pause >nul

php -S localhost:8000

if %errorlevel% neq 0 (
    echo.
    echo PHP is not installed or not in your PATH.
    echo.
    echo Please install PHP from https://www.php.net/downloads.php
    echo After installation, make sure to add PHP to your system PATH.
    echo Then run this script again.
    echo.
    echo Alternatively, you can deploy these files to any PHP-enabled web server.
    echo.
    pause
)