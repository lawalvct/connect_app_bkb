@echo off
echo =================================================
echo    OBS Studio Requirements Check for Windows
echo =================================================
echo.

REM Check Windows version
echo [1/6] Checking Windows Version...
ver | findstr /i "10\|11" >nul
if %errorlevel%==0 (
    echo ✅ Windows 10/11 detected - Compatible
) else (
    echo ❌ Windows 10/11 required for optimal OBS performance
)
echo.

REM Check system architecture
echo [2/6] Checking System Architecture...
if "%PROCESSOR_ARCHITECTURE%"=="AMD64" (
    echo ✅ 64-bit system detected - Compatible
) else (
    echo ❌ 64-bit system required
)
echo.

REM Check available RAM
echo [3/6] Checking Available RAM...
for /f "tokens=2 delims==" %%a in ('wmic computersystem get TotalPhysicalMemory /value') do set /a ram=%%a/1024/1024/1024
if %ram% GEQ 8 (
    echo ✅ %ram%GB RAM detected - Excellent for OBS
) else if %ram% GEQ 4 (
    echo ⚠️  %ram%GB RAM detected - Minimum requirement met
    echo    📝 Consider upgrading to 8GB+ for better performance
) else (
    echo ❌ %ram%GB RAM detected - Below minimum requirement
)
echo.

REM Check DirectX version
echo [4/6] Checking DirectX Support...
dxdiag /t dxdiag_temp.txt 2>nul
if exist dxdiag_temp.txt (
    findstr /i "DirectX 11\|DirectX 12" dxdiag_temp.txt >nul
    if %errorlevel%==0 (
        echo ✅ DirectX 11/12 support detected - Compatible
    ) else (
        echo ❌ DirectX 11+ required for OBS
    )
    del dxdiag_temp.txt
) else (
    echo ⚠️  Unable to detect DirectX version
)
echo.

REM Check for cameras
echo [5/6] Checking Camera Devices...
powershell "Get-WmiObject -Class Win32_PnPEntity | Where-Object {$_.Name -like '*camera*' -or $_.Name -like '*webcam*'} | Measure-Object | Select-Object -ExpandProperty Count" > temp_camera_count.txt
set /p camera_count=<temp_camera_count.txt
del temp_camera_count.txt

if %camera_count% GEQ 2 (
    echo ✅ %camera_count% camera devices detected - Perfect for multi-camera setup
) else if %camera_count% EQU 1 (
    echo ⚠️  1 camera device detected - You can add more cameras or use screen capture
) else (
    echo ❌ No camera devices detected - Please connect cameras
)
echo.

REM Check disk space
echo [6/6] Checking Available Disk Space...
for /f "tokens=3" %%a in ('dir C:\ ^| findstr /i "bytes free"') do set freespace=%%a
set /a freespace_gb=%freespace:,=%/1024/1024/1024
if %freespace_gb% GEQ 10 (
    echo ✅ %freespace_gb%GB free space - Sufficient for OBS and recordings
) else (
    echo ❌ %freespace_gb%GB free space - Consider freeing up disk space
)
echo.

echo =================================================
echo              RECOMMENDATION SUMMARY
echo =================================================
echo.

REM Overall recommendation
if %ram% GEQ 4 (
    if %camera_count% GEQ 1 (
        echo 🎉 YOUR SYSTEM IS READY FOR OBS STUDIO!
        echo.
        echo Next Steps:
        echo 1. Download OBS Studio from: https://obsproject.com/download
        echo 2. Follow the OBS_SETUP_GUIDE.md in your project
        echo 3. Set up virtual camera for your Laravel streaming app
        echo 4. Configure multiple scenes for camera switching
    ) else (
        echo ⚠️  System capable but needs cameras
        echo Please connect camera devices before proceeding
    )
) else (
    echo ❌ System does not meet minimum requirements
    echo Consider upgrading RAM to at least 4GB
)

echo.
echo Additional Recommendations:
echo • Close unnecessary programs while streaming
echo • Use wired internet connection for stability
echo • Update graphics drivers for best performance
echo • Consider external microphone for better audio
echo.

pause
