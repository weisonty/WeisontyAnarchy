@echo off
title Weisonty Anarchy Server
color 0A

set PHP_BIN=bin\php\php.exe
set POCKETMINE_BIN=PocketMine-MP.phar

if not exist "%PHP_BIN%" (
    echo [WARNING] Local PHP not found at %PHP_BIN%
    echo Trying system PHP...
    set PHP_BIN=php
)

if not exist "%POCKETMINE_BIN%" (
    echo PocketMine-MP.phar not found.
    echo Please download it from: https://github.com/pmmp/PocketMine-MP/releases
    echo Place PocketMine-MP.phar in this folder, then run this script again.
    pause
    exit /b 1
)

echo ================================================
echo   Weisonty Anarchy - Starting Server...
echo   Type 'stop' in console to shut down safely.
echo ================================================
echo.

:start
%PHP_BIN% -dphar.readonly=0 %POCKETMINE_BIN% --no-wizard
if %ERRORLEVEL% == 0 (
    echo Server stopped cleanly.
    pause
    exit /b 0
)
echo Server crashed. Restarting in 5 seconds...
timeout /t 5 /nobreak >nul
goto start
