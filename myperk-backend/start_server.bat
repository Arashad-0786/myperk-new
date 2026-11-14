@echo off
cd D:\MyPerk-New\myperk-backend
php -S 0.0.0.0:8080 standalone-minimal.exe > server.log 2>&1
timeout 2
echo Server started