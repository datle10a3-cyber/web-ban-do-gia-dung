param([string]$NgrokPath=(Join-Path $env:LOCALAPPDATA 'GiaDungSandbox/bin/ngrok.exe'))
$ErrorActionPreference='Stop'
. (Join-Path $PSScriptRoot 'ensure_sandbox_tools.ps1')
if (!(Test-Path -LiteralPath $NgrokPath -PathType Leaf)) { throw 'Pass the path of the official bundled ngrok.exe.' }
$tempBase=[IO.Path]::GetFullPath([IO.Path]::GetTempPath())
$testRoot=Join-Path $tempBase ('gia-dung-tools-'+[guid]::NewGuid().ToString('N'))
$null=New-Item -ItemType Directory -Path $testRoot
$savedLocal=$env:LOCALAPPDATA
function Assert-Tools($condition,[string]$label) { if(!$condition){throw $label};Write-Output ('PASS '+$label) }
try {
    $project=Join-Path $testRoot 'XAMPP with spaces/htdocs/web-ban-do-gia-dung'
    $bundle=Join-Path $project 'transfer-private';$scripts=Join-Path $project 'scripts'
    $null=New-Item -ItemType Directory -Path $bundle,$scripts -Force
    Copy-Item -LiteralPath $NgrokPath -Destination (Join-Path $bundle 'ngrok.exe')
    [IO.File]::WriteAllText((Join-Path $bundle 'ngrok.yml'),"version: '3'`nagent:`n  authtoken: test-fixture-not-real`n")
    foreach($file in @('ensure_sandbox_tools.ps1','start_vnpay_sandbox.ps1')){Copy-Item -LiteralPath (Join-Path $PSScriptRoot $file) -Destination (Join-Path $scripts $file)}
    $env:LOCALAPPDATA=Join-Path $testRoot 'Fresh Windows profile/AppData/Local'
    $output=& powershell.exe -NoProfile -File (Join-Path $scripts 'start_vnpay_sandbox.ps1') -CheckToolsOnly
    Assert-Tools ($LASTEXITCODE -eq 0 -and "$output" -match 'are ready') 'PowerShell 5 bootstrap works before the installer has provisioned any tools'
    $installed=Join-Path $env:LOCALAPPDATA 'GiaDungSandbox/bin/ngrok.exe'
    $config=Join-Path $env:LOCALAPPDATA 'ngrok/ngrok.yml'
    Assert-Tools ((Get-FileHash -LiteralPath $installed).Hash -eq (Get-FileHash -LiteralPath $NgrokPath).Hash) 'official binary is copied intact from the project bundle'
    Assert-Tools ((Get-Content -LiteralPath $config -Raw) -match 'test-fixture-not-real') 'ngrok config is copied to the current Windows profile'
    $version=& $installed version
    Assert-Tools ("$version" -match '^ngrok version ') 'bundled executable runs correctly from a path containing spaces'
    [IO.File]::WriteAllText($config,"version: '3'`nagent:`n  authtoken: existing-profile-fixture`n")
    Remove-Item -LiteralPath (Join-Path $bundle 'ngrok.exe')
    $tools=Initialize-GiaDungSandboxTools -ProjectRoot $project -SandboxHome (Join-Path $env:LOCALAPPDATA 'GiaDungSandbox') -NgrokConfigHome (Join-Path $env:LOCALAPPDATA 'ngrok')
    Assert-Tools ((Get-Content -LiteralPath $config -Raw) -match 'existing-profile-fixture') 'existing ngrok settings are preserved on repeated runs'
    Assert-Tools (Test-Path -LiteralPath $tools.Executable) 'an existing install remains usable if its bundled copy is absent'
    $failed=$false
    try { $null=Initialize-GiaDungSandboxTools -ProjectRoot $project -SandboxHome (Join-Path $testRoot 'missing-runtime') -NgrokConfigHome (Join-Path $testRoot 'missing-config') } catch { $failed=$_.Exception.Message -match 'SUA-HTTPS' }
    Assert-Tools $failed 'missing binary has a clear repair message'
    [IO.File]::WriteAllText((Join-Path $bundle 'ngrok.exe'),'tampered binary fixture')
    $failed=$false
    try { $null=Initialize-GiaDungSandboxTools -ProjectRoot $project -SandboxHome (Join-Path $testRoot 'tampered-runtime') -NgrokConfigHome (Join-Path $testRoot 'tampered-config') } catch { $failed=$_.Exception.Message -match 'checksum' }
    Assert-Tools $failed 'a changed bundled executable is rejected before it is installed'
    Assert-Tools (!(Test-Path -LiteralPath (Join-Path $testRoot 'tampered-runtime/bin/ngrok.exe'))) 'a rejected executable is never copied into the runtime'
    $tokens=$null;$errors=$null
    $null=[System.Management.Automation.Language.Parser]::ParseFile((Join-Path $PSScriptRoot 'start_vnpay_sandbox.ps1'),[ref]$tokens,[ref]$errors)
    Assert-Tools ($errors.Count -eq 0) 'full HTTPS runner parses correctly under Windows PowerShell 5'
} finally {
    $env:LOCALAPPDATA=$savedLocal
    $resolved=[IO.Path]::GetFullPath($testRoot)
    if(!$resolved.StartsWith($tempBase,[StringComparison]::OrdinalIgnoreCase) -or !(Split-Path -Leaf $resolved).StartsWith('gia-dung-tools-')) { throw 'Refusing cleanup outside the temporary test directory.' }
    Remove-Item -LiteralPath $resolved -Recurse -Force
}
