@echo off
REM Adds Flutter to PATH for this window only, then drops you in this folder.
set "PATH=C:\src\flutter\bin;%PATH%"
cd /d "%~dp0"
echo Flutter: 
where flutter 2>nul || echo ERROR: C:\src\flutter\bin\flutter.bat not found. Install Flutter there or edit this script.
echo.
echo Try: flutter pub get
echo      flutter doctor
cmd /k
