@echo off
title Paradise Hotel Booking System Server
echo ====================================================
echo Starting Paradise Hotel Booking System...
echo ====================================================

:: Check if MariaDB / MySQL is already running
tasklist /FI "IMAGENAME eq mysqld.exe" 2>NUL | find /I /N "mysqld.exe">NUL
if "%ERRORLEVEL%"=="0" (
    echo [OK] MariaDB / MySQL is already running on port 3306.
) else (
    echo [..] Starting MariaDB / MySQL on port 3306...
    start "" /B "C:\xampp\mysql\bin\mysqld.exe" --datadir="%~dp0.mysql_data" --port=3306 --console
    timeout /t 2 >nul
)

:: Launch browser
start http://127.0.0.1:8000/index.php

:: Start PHP Server
echo [OK] Starting PHP server at http://127.0.0.1:8000 ...
echo [INFO] You can access the application at http://127.0.0.1:8000
echo [INFO] Press Ctrl+C in this window to stop the server.
"C:\xampp\php\php.exe" -S 127.0.0.1:8000 -t "%~dp0"
pause

