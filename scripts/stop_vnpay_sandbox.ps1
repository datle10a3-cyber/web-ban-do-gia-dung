$ErrorActionPreference='Stop'
$projectRoot=[IO.Path]::GetFullPath((Join-Path $PSScriptRoot '..'))
$phpExecutable=[IO.Path]::GetFullPath((Join-Path $projectRoot '../../php/php.exe'))
$sandboxHome=Join-Path $env:LOCALAPPDATA 'GiaDungSandbox'
$runtimeFile=Join-Path $sandboxHome 'runtime.json'
if (!(Test-Path -LiteralPath $runtimeFile)) { Write-Output 'No managed sandbox tunnel is running.'; exit }
$runtime=Get-Content -LiteralPath $runtimeFile -Raw | ConvertFrom-Json
foreach($processId in @($runtime.ngrok_pid,$runtime.preview_pid)) {
    if($processId) {
        $runningProcess=Get-Process -Id $processId -ErrorAction SilentlyContinue
        $processInfo=Get-CimInstance Win32_Process -Filter "ProcessId=$([int]$processId)" -ErrorAction SilentlyContinue
        $ownedTunnel=$runningProcess -and $runningProcess.Path -eq (Join-Path $sandboxHome 'bin/ngrok.exe') -and $processInfo.CommandLine -like "*127.0.0.1:$($runtime.preview_port)*"
        $ownedPreview=$runningProcess -and $runningProcess.Path -eq $phpExecutable -and $processInfo.CommandLine -like '*sandbox_router.php*'
        if($ownedTunnel -or $ownedPreview) { Stop-Process -Id $processId }
    }
}
Write-Output 'Managed HTTPS tunnel stopped. XAMPP was left running.'
