@echo off
setlocal EnableExtensions
title PathForge AI startup

rem One-click startup for the recovered PathForge stack.
rem Does not change my.ini, .env, ports, or any database files.
rem
rem Do NOT use XAMPP Control Panel MySQL Start/Stop:
rem   Start uses C:\xampp\mysql\bin\my.ini (old datadir, innodb_force_recovery=3).
rem   Stop runs killprocess.bat and force-kills ALL mysqld.exe, including port 3308.

set "MYSQLD=C:\xampp\mysql\bin\mysqld.exe"
set "MYSQLADMIN=C:\xampp\mysql\bin\mysqladmin.exe"
set "MYSQL_INI=C:\xampp\mysql\restore-pathforge-data\my.ini"
set "PHP=C:\xampp\php\php.exe"
set "PROJECT=C:\xampp\htdocs\PathForge - AI"
set "APACHE_START=C:\xampp\apache_start.bat"

echo.
echo PathForge AI startup
echo ---------------------
echo Apache: XAMPP on port 80
echo Database: recovered MariaDB on 127.0.0.1:3308  (pathforge)
echo App: php artisan serve on http://127.0.0.1:8000
echo.
echo Leave XAMPP MySQL Stop unused while this database is running.
echo.

if not exist "%MYSQLD%" (
  echo ERROR: mysqld not found at %MYSQLD%
  goto fail
)
if not exist "%MYSQL_INI%" (
  echo ERROR: recovery my.ini not found at %MYSQL_INI%
  goto fail
)
if not exist "%PHP%" (
  echo ERROR: PHP not found at %PHP%
  goto fail
)

call :port_listening 3308
if not errorlevel 1 (
  echo Recovered MariaDB is already listening on 3308.
) else (
  echo Starting recovered MariaDB with restore-pathforge-data on port 3308...
  start "PathForge MariaDB (3308)" /MIN "%MYSQLD%" --defaults-file="%MYSQL_INI%"
)

set /a tries=0
:waitdb
"%MYSQLADMIN%" --protocol=tcp -h 127.0.0.1 -P 3308 -u root ping >nul 2>&1
if not errorlevel 1 goto dbok
set /a tries+=1
if %tries% GEQ 30 (
  echo ERROR: MariaDB on 3308 did not become ready within 30 seconds.
  echo Keep the "PathForge MariaDB (3308)" window open and check
  echo C:\xampp\mysql\restore-pathforge-data\restore_error.log
  goto fail
)
timeout /t 1 /nobreak >nul
goto waitdb

:dbok
echo MariaDB is ready on 127.0.0.1:3308.

call :port_listening 80
if not errorlevel 1 (
  echo Apache is already listening on port 80.
) else (
  if not exist "%APACHE_START%" (
    echo WARNING: Apache start script not found. Skipping Apache.
  ) else (
    echo Starting Apache...
    start "XAMPP Apache" /MIN "%APACHE_START%"
  )
)

call :port_listening 8000
if not errorlevel 1 (
  echo Laravel is already listening on port 8000.
) else (
  echo Starting Laravel on http://127.0.0.1:8000 ...
  start "PathForge Laravel" /D "%PROJECT%" "%PHP%" artisan serve --host=127.0.0.1 --port=8000
)

echo.
echo Open: http://127.0.0.1:8000
echo.
pause
exit /b 0

:fail
echo.
pause
exit /b 1

:port_listening
netstat -ano | findstr /C:":%~1" | findstr /I "LISTENING" >nul
exit /b %errorlevel%
