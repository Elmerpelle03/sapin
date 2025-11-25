# ============================================
# Remove DEFINER Clauses from SQL Dump
# ============================================
# This script removes DEFINER clauses from SQL files
# making them compatible with Hostinger and other shared hosting

param(
    [Parameter(Mandatory=$false)]
    [string]$InputFile,
    
    [Parameter(Mandatory=$false)]
    [string]$OutputFile
)

Write-Host "========================================" -ForegroundColor Cyan
Write-Host "SQL DEFINER Remover for Hostinger" -ForegroundColor Cyan
Write-Host "========================================" -ForegroundColor Cyan
Write-Host ""

# If no input file specified, prompt user
if (-not $InputFile) {
    Write-Host "Please drag and drop your SQL file here and press Enter:" -ForegroundColor Yellow
    $InputFile = Read-Host
    $InputFile = $InputFile.Trim('"')
}

# Check if file exists
if (-not (Test-Path $InputFile)) {
    Write-Host "ERROR: File not found: $InputFile" -ForegroundColor Red
    Read-Host "Press Enter to exit"
    exit 1
}

# Set output file if not specified
if (-not $OutputFile) {
    $directory = Split-Path $InputFile -Parent
    $filename = [System.IO.Path]::GetFileNameWithoutExtension($InputFile)
    $extension = [System.IO.Path]::GetExtension($InputFile)
    $OutputFile = Join-Path $directory "$filename`_hostinger_ready$extension"
}

Write-Host "Input file:  $InputFile" -ForegroundColor White
Write-Host "Output file: $OutputFile" -ForegroundColor White
Write-Host ""
Write-Host "Processing..." -ForegroundColor Yellow

try {
    # Read the file
    $content = Get-Content $InputFile -Raw -Encoding UTF8
    
    # Count DEFINER occurrences before
    $beforeCount = ([regex]::Matches($content, "DEFINER")).Count
    
    # Remove DEFINER clauses - comprehensive patterns
    # Pattern 1: DEFINER=`user`@`host` (with backticks)
    $content = $content -replace "DEFINER\s*=\s*``[^``]+``\s*@\s*``[^``]+``\s+", ""
    # Pattern 2: DEFINER='user'@'host' (with single quotes)
    $content = $content -replace "DEFINER\s*=\s*'[^']+'@'[^']+'\s+", ""
    # Pattern 3: DEFINER=user@host (without quotes)
    $content = $content -replace "DEFINER\s*=\s*\w+@\w+\s+", ""
    # Pattern 4: Catch any remaining DEFINER fragments
    $content = $content -replace "DEFINER\s*=\s*[^\s]+\s+", ""
    
    # Clean up any leftover fragments like @`localhost` or @'localhost'
    $content = $content -replace "\s+@\s*``[^``]+``\s+", " "
    $content = $content -replace "\s+@\s*'[^']+'\s+", " "
    
    # Also remove SQL SECURITY DEFINER (safer for shared hosting)
    $content = $content -replace "SQL\s+SECURITY\s+DEFINER\s+", ""
    
    # Add foreign key check disable at the beginning (if not already present)
    if ($content -notmatch "SET FOREIGN_KEY_CHECKS\s*=\s*0") {
        $content = "SET FOREIGN_KEY_CHECKS=0;`n`n" + $content
    }
    
    # Add foreign key check enable at the end (if not already present)
    if ($content -notmatch "SET FOREIGN_KEY_CHECKS\s*=\s*1") {
        $content = $content + "`n`nSET FOREIGN_KEY_CHECKS=1;`n"
    }
    
    # Count DEFINER occurrences after
    $afterCount = ([regex]::Matches($content, "DEFINER")).Count
    
    # Save the modified content
    $content | Set-Content $OutputFile -Encoding UTF8 -NoNewline
    
    Write-Host ""
    Write-Host "========================================" -ForegroundColor Green
    Write-Host "SUCCESS!" -ForegroundColor Green
    Write-Host "========================================" -ForegroundColor Green
    Write-Host "Removed $beforeCount DEFINER clause(s)" -ForegroundColor Green
    Write-Host "Remaining: $afterCount" -ForegroundColor $(if ($afterCount -eq 0) { "Green" } else { "Yellow" })
    Write-Host ""
    Write-Host "Additional fixes applied:" -ForegroundColor Yellow
    Write-Host "  - Foreign key checks disabled/enabled" -ForegroundColor Green
    Write-Host "  - SQL SECURITY DEFINER removed" -ForegroundColor Green
    Write-Host "  - Leftover fragments cleaned" -ForegroundColor Green
    Write-Host ""
    Write-Host "Output saved to:" -ForegroundColor White
    Write-Host $OutputFile -ForegroundColor Cyan
    Write-Host ""
    Write-Host "You can now upload this file to Hostinger!" -ForegroundColor Green
    Write-Host ""
    
} catch {
    Write-Host ""
    Write-Host "ERROR: $($_.Exception.Message)" -ForegroundColor Red
    Write-Host ""
}

Read-Host "Press Enter to exit"
