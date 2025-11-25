@echo off
echo ========================================
echo Switching to SIMPLE checkout version
echo ========================================
echo.

REM Backup current version
if exist backend\checkout.php (
    echo Backing up current checkout.php...
    copy /Y backend\checkout.php backend\checkout_complex_backup.php
    echo Backup created: backend\checkout_complex_backup.php
    echo.
)

REM Copy simple version
if exist backend\checkout_simple.php (
    echo Installing simple version...
    copy /Y backend\checkout_simple.php backend\checkout.php
    echo.
    echo ========================================
    echo SUCCESS! Simple version installed.
    echo ========================================
    echo.
    echo This version has:
    echo   - Basic file upload
    echo   - File size validation
    echo   - File type validation
    echo   - NO complex security features
    echo.
    echo Try uploading a proof of payment now!
    echo.
) else (
    echo ERROR: checkout_simple.php not found!
    echo Please make sure the file exists.
)

pause
