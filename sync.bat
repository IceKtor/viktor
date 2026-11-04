@echo off
cd /d C:\projects\viktor
git pull || (echo git pull не удался, копирование отменено & pause & exit /b 1)
robocopy serge-site C:\OSPanel\domains\serge.local /E /XF config.local.php /XD uploads
pause
