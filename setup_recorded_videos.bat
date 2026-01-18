@echo off
REM Setup script for recorded videos feature

echo Creating directory for recorded videos...
if not exist "public\streams\videos" mkdir "public\streams\videos"
if not exist "public\streams" mkdir "public\streams"

echo Directories created successfully!
echo Location: public\streams\videos

echo.
echo Running migration...
php artisan migrate

echo.
echo Setup complete!
echo.
echo IMPORTANT: Make sure the public/streams and public/streams/videos directories have write permissions.
pause
