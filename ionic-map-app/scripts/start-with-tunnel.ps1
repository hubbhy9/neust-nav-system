param(
    [string]$Url = 'http://localhost:4200',
    [string]$MockUrl
)

function Get-FreePort {
    param([int]$StartPort = 4200)

    for ($port = $StartPort; $port -lt ($StartPort + 100); $port++) {
        if (Get-NetTCPConnection -State Listen -LocalPort $port -ErrorAction SilentlyContinue) {
            continue
        }

        $listener = [System.Net.Sockets.TcpListener]::new([System.Net.IPAddress]::Any, $port)
        $listener.Server.ExclusiveAddressUse = $true
        try {
            $listener.Start()
            return $port
        }
        catch {
            continue
        }
        finally {
            $listener.Stop()
        }
    }

    return $StartPort
}

$projectRoot = Split-Path -Parent $PSScriptRoot
$serverScript = Join-Path $PSScriptRoot 'serve-production.js'
$ngCli = Join-Path $projectRoot 'node_modules\@angular\cli\bin\ng'
$logPath = Join-Path $env:TEMP 'capston-trycloudflare.log'
$errLogPath = Join-Path $env:TEMP 'capston-trycloudflare-error.log'
$Port = Get-FreePort
$Url = "http://localhost:$Port"
$publicUrlPath = Join-Path $projectRoot '.public-app-url'
$publicWebUrlPath = Join-Path $projectRoot '.public-web-url'
Remove-Item $publicUrlPath -ErrorAction SilentlyContinue
Remove-Item $publicWebUrlPath -ErrorAction SilentlyContinue
Remove-Item $logPath -ErrorAction SilentlyContinue
Remove-Item $errLogPath -ErrorAction SilentlyContinue

Write-Host 'Building optimized Ionic app...'
& node $ngCli build --configuration production
if ($LASTEXITCODE -ne 0) {
    exit $LASTEXITCODE
}

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

Write-Host 'Starting one Cloudflare tunnel for phones, tablets, and computers...'

if (-not [string]::IsNullOrWhiteSpace($MockUrl)) {
    $publicUrl = $MockUrl
    $tunnelProcess = $null
}
else {
    $tunnelProcess = Start-Process -FilePath 'cloudflared' -ArgumentList @('tunnel', '--url', $Url) -PassThru -NoNewWindow -RedirectStandardOutput $logPath -RedirectStandardError $errLogPath
}

$publicUrl = if ($MockUrl) { $MockUrl } else { $null }
$sw = [System.Diagnostics.Stopwatch]::StartNew()
while ([string]::IsNullOrWhiteSpace($publicUrl) -and $sw.ElapsedMilliseconds -lt 60000) {
    $content = Get-Content -Path $logPath -Raw -ErrorAction SilentlyContinue
    $publicUrl = Get-TryCloudflareUrl -Text $content

    if (-not $publicUrl) {
        $errContent = Get-Content -Path $errLogPath -Raw -ErrorAction SilentlyContinue
        $publicUrl = Get-TryCloudflareUrl -Text $errContent
    }

    if ($tunnelProcess -and $tunnelProcess.HasExited) {
        break
    }

    if (-not $publicUrl) { Start-Sleep -Milliseconds 500 }
}

if ([string]::IsNullOrWhiteSpace($publicUrl)) {
    Write-Host 'Could not detect the TryCloudflare URL.'
    Get-Content -Path $logPath, $errLogPath -ErrorAction SilentlyContinue
    if ($tunnelProcess -and -not $tunnelProcess.HasExited) { Stop-Process -Id $tunnelProcess.Id -Force }
    exit 1
}

Set-Content -Path $publicUrlPath -Value $publicUrl -NoNewline -Encoding Ascii
$publicUrl | Set-Clipboard
Write-Host "Shared website URL: $publicUrl"
Write-Host 'Phones/tablets open Ionic; desktop/laptop browsers open the PHP website.'
Write-Host 'Shared URL copied to clipboard.'

Write-Host "Starting compressed app server on http://localhost:$Port..."
& node $serverScript --port $Port
