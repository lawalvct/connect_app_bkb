# OBS Studio Requirements Check
Write-Host "=================================================" -ForegroundColor Green
Write-Host "   OBS Studio Requirements Check for Windows" -ForegroundColor Green
Write-Host "=================================================" -ForegroundColor Green
Write-Host ""

# Check Windows version
Write-Host "[1/6] Checking Windows Version..." -ForegroundColor Yellow
$os = Get-WmiObject -Class Win32_OperatingSystem
$version = $os.Version
if ($version -like "10.*" -or $version -like "11.*") {
    Write-Host "✅ Windows 10/11 detected - Compatible" -ForegroundColor Green
} else {
    Write-Host "❌ Windows 10/11 recommended for optimal OBS performance" -ForegroundColor Red
}
Write-Host ""

# Check system architecture
Write-Host "[2/6] Checking System Architecture..." -ForegroundColor Yellow
if ($env:PROCESSOR_ARCHITECTURE -eq "AMD64") {
    Write-Host "✅ 64-bit system detected - Compatible" -ForegroundColor Green
} else {
    Write-Host "❌ 64-bit system required" -ForegroundColor Red
}
Write-Host ""

# Check RAM
Write-Host "[3/6] Checking Available RAM..." -ForegroundColor Yellow
$ram = [math]::Round((Get-WmiObject -Class Win32_ComputerSystem).TotalPhysicalMemory / 1GB, 0)
if ($ram -ge 8) {
    Write-Host "✅ ${ram}GB RAM detected - Excellent for OBS" -ForegroundColor Green
} elseif ($ram -ge 4) {
    Write-Host "⚠️  ${ram}GB RAM detected - Minimum requirement met" -ForegroundColor Yellow
    Write-Host "   📝 Consider upgrading to 8GB+ for better performance" -ForegroundColor Yellow
} else {
    Write-Host "❌ ${ram}GB RAM detected - Below minimum requirement" -ForegroundColor Red
}
Write-Host ""

# Check cameras
Write-Host "[4/6] Checking Camera Devices..." -ForegroundColor Yellow
$cameras = Get-WmiObject -Class Win32_PnPEntity | Where-Object {$_.Name -like "*camera*" -or $_.Name -like "*webcam*" -or $_.Name -like "*imaging*"}
$cameraCount = $cameras.Count
if ($cameraCount -ge 2) {
    Write-Host "✅ $cameraCount camera devices detected - Perfect for multi-camera setup" -ForegroundColor Green
} elseif ($cameraCount -eq 1) {
    Write-Host "⚠️  1 camera device detected - You can add more cameras or use screen capture" -ForegroundColor Yellow
} else {
    Write-Host "❌ No camera devices detected - Please connect cameras" -ForegroundColor Red
}

# List detected cameras
if ($cameraCount -gt 0) {
    Write-Host "   Detected cameras:" -ForegroundColor Cyan
    foreach ($camera in $cameras) {
        Write-Host "   • $($camera.Name)" -ForegroundColor Cyan
    }
}
Write-Host ""

# Check disk space
Write-Host "[5/6] Checking Available Disk Space..." -ForegroundColor Yellow
$disk = Get-WmiObject -Class Win32_LogicalDisk -Filter "DeviceID='C:'"
$freeSpaceGB = [math]::Round($disk.FreeSpace / 1GB, 0)
if ($freeSpaceGB -ge 10) {
    Write-Host "✅ ${freeSpaceGB}GB free space - Sufficient for OBS and recordings" -ForegroundColor Green
} else {
    Write-Host "❌ ${freeSpaceGB}GB free space - Consider freeing up disk space" -ForegroundColor Red
}
Write-Host ""

# Check graphics capabilities
Write-Host "[6/6] Checking Graphics Support..." -ForegroundColor Yellow
$graphics = Get-WmiObject -Class Win32_VideoController | Where-Object {$_.Name -notlike "*Microsoft*"}
if ($graphics) {
    Write-Host "✅ Dedicated graphics detected: $($graphics[0].Name)" -ForegroundColor Green
} else {
    Write-Host "⚠️  Using integrated graphics - OBS will work but may impact performance" -ForegroundColor Yellow
}
Write-Host ""

# Summary
Write-Host "=================================================" -ForegroundColor Green
Write-Host "             RECOMMENDATION SUMMARY" -ForegroundColor Green
Write-Host "=================================================" -ForegroundColor Green
Write-Host ""

if ($ram -ge 4 -and $cameraCount -ge 1) {
    Write-Host "🎉 YOUR SYSTEM IS READY FOR OBS STUDIO!" -ForegroundColor Green
    Write-Host ""
    Write-Host "Next Steps:" -ForegroundColor White
    Write-Host "1. Download OBS Studio from: https://obsproject.com/download" -ForegroundColor White
    Write-Host "2. Follow the OBS_SETUP_GUIDE.md in your project" -ForegroundColor White
    Write-Host "3. Set up virtual camera for your Laravel streaming app" -ForegroundColor White
    Write-Host "4. Configure multiple scenes for camera switching" -ForegroundColor White
} elseif ($cameraCount -eq 0) {
    Write-Host "⚠️  System capable but needs cameras" -ForegroundColor Yellow
    Write-Host "Please connect camera devices before proceeding" -ForegroundColor Yellow
} else {
    Write-Host "❌ System does not meet minimum requirements" -ForegroundColor Red
    Write-Host "Consider upgrading RAM to at least 4GB" -ForegroundColor Red
}

Write-Host ""
Write-Host "Additional Recommendations:" -ForegroundColor Cyan
Write-Host "• Close unnecessary programs while streaming" -ForegroundColor White
Write-Host "• Use wired internet connection for stability" -ForegroundColor White
Write-Host "• Update graphics drivers for best performance" -ForegroundColor White
Write-Host "• Consider external microphone for better audio" -ForegroundColor White
Write-Host ""

Write-Host "Press any key to continue..." -ForegroundColor Gray
$null = $Host.UI.RawUI.ReadKey("NoEcho,IncludeKeyDown")
