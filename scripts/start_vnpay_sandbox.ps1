param([int]$Port = 8097, [int]$ApachePort = 80, [switch]$CheckToolsOnly)
$ErrorActionPreference = 'Stop'
$projectRoot = [IO.Path]::GetFullPath((Join-Path $PSScriptRoot '..'))
$sandboxHome = Join-Path $env:LOCALAPPDATA 'GiaDungSandbox'
$runtimeFile = Join-Path $sandboxHome 'runtime.json'
. (Join-Path $PSScriptRoot 'ensure_sandbox_tools.ps1')
$tools=Initialize-GiaDungSandboxTools -ProjectRoot $projectRoot -SandboxHome $sandboxHome -NgrokConfigHome (Join-Path $env:LOCALAPPDATA 'ngrok')
$ngrokExecutable=$tools.Executable
$ngrokConfigFile=$tools.Config
if ($CheckToolsOnly) { Write-Output 'Ngrok executable and configuration are ready.'; return }
$phpExecutable = [IO.Path]::GetFullPath((Join-Path $projectRoot '../../php/php.exe'))
if (!(Test-Path -LiteralPath $phpExecutable)) { throw 'PHP runtime is unavailable.' }
$null = New-Item -ItemType Directory -Path $sandboxHome -Force
$localAddress = "http://127.0.0.1:$ApachePort/web-ban-do-gia-dung/"
if ((Invoke-WebRequest -Uri $localAddress -UseBasicParsing).StatusCode -ne 200) { throw 'The localhost shop is unavailable.' }
if (Test-Path -LiteralPath $runtimeFile) {
    $previous = Get-Content -LiteralPath $runtimeFile -Raw | ConvertFrom-Json
    foreach ($processId in @($previous.ngrok_pid, $previous.preview_pid)) {
        if ($processId) {
            $runningProcess = Get-Process -Id $processId -ErrorAction SilentlyContinue
            $processInfo = Get-CimInstance Win32_Process -Filter "ProcessId=$([int]$processId)" -ErrorAction SilentlyContinue
            $ownedTunnel = $runningProcess -and $runningProcess.Path -eq $ngrokExecutable -and $processInfo.CommandLine -like "*127.0.0.1:$($previous.preview_port)*"
            $ownedPreview = $runningProcess -and $runningProcess.Path -eq $phpExecutable -and $processInfo.CommandLine -like '*sandbox_router.php*' -and $processInfo.CommandLine -like "*$projectRoot*"
            if ($ownedTunnel -or $ownedPreview) {
                Stop-Process -Id $processId
            }
        }
    }
}
if (Get-NetTCPConnection -LocalPort $Port -State Listen -ErrorAction SilentlyContinue) { throw "Port $Port is already in use." }
$previewLog = Join-Path $sandboxHome 'preview.log'
$previewError = Join-Path $sandboxHome 'preview-errors.log'
$preview = Start-Process -FilePath $phpExecutable -ArgumentList @('-S',"127.0.0.1:$Port",'-t',"`"$projectRoot`"","`"$PSScriptRoot/sandbox_router.php`"") -WorkingDirectory $projectRoot -WindowStyle Hidden -RedirectStandardOutput $previewLog -RedirectStandardError $previewError -PassThru
try {
    $ngrokLog = Join-Path $sandboxHome 'ngrok.log'
    $ngrokError = Join-Path $sandboxHome 'ngrok-errors.log'
    $ngrokOutput = Join-Path $sandboxHome 'ngrok-output.log'
    $tunnel = Start-Process -FilePath $ngrokExecutable -ArgumentList @('http',"http://127.0.0.1:$Port",'--inspect=false',"--config=`"$ngrokConfigFile`"","--log=`"$ngrokLog`"",'--log-format=json') -WorkingDirectory $sandboxHome -WindowStyle Hidden -RedirectStandardOutput $ngrokOutput -RedirectStandardError $ngrokError -PassThru
    $publicOrigin = $null
    for ($attempt=0; $attempt -lt 40; $attempt++) {
        Start-Sleep -Milliseconds 500
        if ($tunnel.HasExited) {
            $logText=(@($ngrokLog,$ngrokError,$ngrokOutput) | ForEach-Object { if(Test-Path -LiteralPath $_){Get-Content -LiteralPath $_ -Raw} }) -join "`n"
            $match=[regex]::Match($logText,'ERR_NGROK_[0-9]+')
            $code=if($match.Success){$match.Value}else{'khong co ma loi'}
            throw "ngrok khong khoi dong duoc ($code). Gui anh thong bao nay de kiem tra."
        }
        try {
            $status = Invoke-RestMethod -Uri 'http://127.0.0.1:4040/api/tunnels' -TimeoutSec 2
            $entry = $status.tunnels | Where-Object { $_.proto -eq 'https' -and $_.config.addr -eq "http://127.0.0.1:$Port" } | Select-Object -First 1
            if ($entry) { $publicOrigin = $entry.public_url; break }
        } catch { }
    }
    if (!$publicOrigin -or $publicOrigin -notmatch '^https://[a-z0-9.-]+$') { throw 'No HTTPS endpoint was assigned.' }
    $website = "$publicOrigin/web-ban-do-gia-dung/"
    $response = Invoke-WebRequest -Uri $website -Headers @{'ngrok-skip-browser-warning'='1'} -UseBasicParsing -TimeoutSec 20
    if ($response.StatusCode -ne 200 -or $response.Content -notmatch 'Gia D.ng Shop') { throw 'The public preview failed its health check.' }
    $runtime = @{public_origin=$publicOrigin;website_url=$website;ipn_url="$publicOrigin/web-ban-do-gia-dung/cart/vnpay_ipn.php";local_url=$localAddress;preview_port=$Port;preview_pid=$preview.Id;ngrok_pid=$tunnel.Id;started_at=(Get-Date).ToString('o')}
    $runtime | ConvertTo-Json | Set-Content -LiteralPath $runtimeFile -Encoding UTF8
    & $phpExecutable "$PSScriptRoot/sync_oauth_runtime.php" $runtimeFile
    if ($LASTEXITCODE -ne 0) { throw 'Could not synchronize the private Facebook HTTPS runtime.' }
    $runtime | ConvertTo-Json
} catch {
    if ($tunnel -and !$tunnel.HasExited) { Stop-Process -Id $tunnel.Id }
    if ($preview -and !$preview.HasExited) { Stop-Process -Id $preview.Id }
    throw
}
