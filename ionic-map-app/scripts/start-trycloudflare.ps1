param(
    [string]$Url = "http://localhost:4200",
    [string]$LogPath = "$env:TEMP\trycloudflare.log",
    [string]$StaticUrl
)

function Get-TryCloudflareUrl {
    param([string]$Text)

    if ([string]::IsNullOrWhiteSpace($Text)) {
        return $null
    }

    $match = [regex]::Match($Text, 'https?://[a-z0-9-]+\.trycloudflare\.com')
    if ($match.Success) {
        return $match.Value
    }

    return $null
}

if (-not [string]::IsNullOrWhiteSpace($StaticUrl)) {
    $projectRoot = Split-Path -Parent $PSScriptRoot
    Set-Content -Path (Join-Path $projectRoot '.public-app-url') -Value $StaticUrl -NoNewline -Encoding Ascii
    $StaticUrl | Set-Clipboard
    Write-Host "TryCloudflare URL: $StaticUrl"
    Write-Host "Copied to clipboard."
    exit 0
}

$projectRoot = Split-Path -Parent $PSScriptRoot
Remove-Item (Join-Path $projectRoot '.public-app-url') -Force -ErrorAction SilentlyContinue
Remove-Item $LogPath -Force -ErrorAction SilentlyContinue

$process = Start-Process cloudflared -ArgumentList "tunnel", "--url", $Url -NoNewWindow -PassThru -RedirectStandardOutput $LogPath -RedirectStandardError $LogPath

$matchedUrl = $null
while ($true) {
    if (Test-Path $LogPath) {
        $content = Get-Content $LogPath -Raw -ErrorAction SilentlyContinue
        $matchedUrl = Get-TryCloudflareUrl -Text $content
        if ($matchedUrl) {
            break
        }
    }

    if ($process.HasExited) {
        break
    }

    Start-Sleep -Milliseconds 500
}

if ($matchedUrl) {
    $projectRoot = Split-Path -Parent $PSScriptRoot
    Set-Content -Path (Join-Path $projectRoot '.public-app-url') -Value $matchedUrl -NoNewline -Encoding Ascii
    $matchedUrl | Set-Clipboard
    Write-Host "TryCloudflare URL: $matchedUrl"
    Write-Host "Copied to clipboard."
    exit 0
}

Write-Host "No TryCloudflare URL detected yet. See output here:"
if (Test-Path $LogPath) {
    Get-Content $LogPath
}

exit 1
