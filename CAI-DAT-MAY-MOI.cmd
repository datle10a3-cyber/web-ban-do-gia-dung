@echo off
setlocal
cd /d "%~dp0"
for %%I in ("%CD%\..\..") do set "SHOP_XAMPP=%%~fI"
if not exist "%SHOP_XAMPP%\php\php.exe" (
  echo Giai nen thu muc web-ban-do-gia-dung vao XAMPP\htdocs truoc.
  pause
  exit /b 1
)
"%SHOP_XAMPP%\php\php.exe" scripts\restore_transfer.php
if errorlevel 1 (
  echo Chua hoan tat. Xem thong bao phia tren.
) else (
  echo Da san sang: http://localhost/web-ban-do-gia-dung/
  start "" "http://localhost/web-ban-do-gia-dung/"
)
pause
