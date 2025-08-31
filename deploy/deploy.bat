@echo off
echo Preparing Laravel Task Manager for deployment...
echo.

REM Create upload directory
if exist "upload" rmdir /s /q "upload"
mkdir upload

echo Copying application files...
xcopy "app" "upload\app\" /E /I /Y
xcopy "bootstrap" "upload\bootstrap\" /E /I /Y
xcopy "config" "upload\config\" /E /I /Y
xcopy "database" "upload\database\" /E /I /Y
xcopy "lang" "upload\lang\" /E /I /Y
xcopy "public" "upload\public\" /E /I /Y
xcopy "resources" "upload\resources\" /E /I /Y
xcopy "routes" "upload\routes\" /E /I /Y
xcopy "storage" "upload\storage\" /E /I /Y
xcopy "vendor" "upload\vendor\" /E /I /Y

echo Copying essential files...
copy "artisan" "upload\"
copy "composer.json" "upload\"

echo Creating production environment template...
echo APP_NAME="Task Manager" > "upload\.env.production.template"
echo APP_ENV=production >> "upload\.env.production.template"
echo APP_KEY= >> "upload\.env.production.template"
echo APP_DEBUG=false >> "upload\.env.production.template"
echo APP_URL=https://your-subdomain.yourdomain.com >> "upload\.env.production.template"
echo. >> "upload\.env.production.template"
echo DB_CONNECTION=mysql >> "upload\.env.production.template"
echo DB_HOST=127.0.0.1 >> "upload\.env.production.template"
echo DB_PORT=3306 >> "upload\.env.production.template"
echo DB_DATABASE=your_database_name >> "upload\.env.production.template"
echo DB_USERNAME=your_database_username >> "upload\.env.production.template"
echo DB_PASSWORD=your_database_password >> "upload\.env.production.template"

echo Copying .htaccess file...
copy "deploy\.htaccess" "upload\"

echo Copying deployment checklist...
copy "deploy\upload-checklist.txt" "upload\"

echo.
echo Deployment package created successfully!
echo.
echo Next steps:
echo 1. Upload the contents of the 'upload' folder to your subdomain
echo 2. Rename .env.production.template to .env and update database credentials
echo 3. Set proper file permissions
echo 4. Run the post-upload commands listed in upload-checklist.txt
echo.
echo Press any key to exit...
pause > nul



