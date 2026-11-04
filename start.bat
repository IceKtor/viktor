@echo off
cd /d C:\projects\viktor
git pull
start http://localhost:8000
C:\OSPanel\modules\php\PHP_8.1\php.exe -S localhost:8000 -t serge-site