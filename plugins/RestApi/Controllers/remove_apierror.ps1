$dir = $PSScriptRoot
$files = Get-ChildItem -Path $dir -Filter *.php -File | Where-Object { $_.Name -notmatch 'Rest_api_Controller|Api_settings|remove' }
foreach ($f in $files) {
    $content = Get-Content $f.FullName -Raw
    if ($content -match '@apiError') {
        $lines = Get-Content $f.FullName
        $out = @()
        foreach ($line in $lines) {
            $skip = $false
            if ($line -match '\s*\*\s*@apiError') { $skip = $true }
            elseif ($line -match '\s*\*\s*@apiErrorExample') { $skip = $true }
            elseif ($line -match '\s*\*\s*HTTP/1\.1 400 Bad Request') { $skip = $true }
            elseif ($line -match '\s*\*\s*\{\s*"' -and ($line -match '"error"' -or $line -match 'Invalid' -or $line -match ' fail')) { $skip = $true }
            if (-not $skip) { $out += $line }
        }
        $out | Set-Content $f.FullName -Encoding UTF8
    }
}
