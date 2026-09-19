Write-Host "=== Case-Sensitive Recursive Replace Tool ===" -ForegroundColor Cyan

# Default directory = current working directory
$defaultDir = $PWD.Path
$defaultName = Split-Path $defaultDir -Leaf

Write-Host "Default search directory: $defaultName ($defaultDir)" -ForegroundColor Yellow
Write-Host ""

# Ask user for directory
$inputDir = Read-Host "Where should we Search (default: $defaultName)"

# If user presses Enter, use default
if ([string]::IsNullOrWhiteSpace($inputDir)) {
    $dir = $defaultDir
} else {
    # Convert relative paths to absolute
    $dir = Resolve-Path $inputDir
}

Write-Host ""
Write-Host "Using directory: $dir" -ForegroundColor Yellow
Write-Host ""

# Ask for search term
$search = Read-Host "Enter the case-sensitive search term"

# Ask for replacement
$replace = Read-Host "Enter the replacement text"

Write-Host ""
Write-Host "Searching recursively in: $dir" -ForegroundColor Yellow
Write-Host "Search: $search"
Write-Host "Replace: $replace"
Write-Host ""

Get-ChildItem -Path $dir -Recurse -File | ForEach-Object {

    try {
        $content = Get-Content $_.FullName -Raw -ErrorAction Stop
    }
    catch {
        Write-Host "Skipping unreadable file: $($_.FullName)" -ForegroundColor DarkYellow
        return
    }

    if (-not $content) {
        Write-Host "Skipping empty/binary file: $($_.FullName)" -ForegroundColor DarkYellow
        return
    }

    if ($content.Contains($search)) {
        $newContent = $content.Replace($search, $replace)
        Set-Content $_.FullName $newContent
        Write-Host "Updated: $($_.FullName)" -ForegroundColor Green
    }
}

Write-Host ""
Write-Host "Done." -ForegroundColor Cyan
