@echo off
cd /d C:\projects\viktor
git pull
robocopy serge-site C:\OSPanel\domains\serge.local /MIR /XF config.local.php /XD uploads
pause