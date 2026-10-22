function Initialize-GiaDungSandboxTools {
    param(
        [Parameter(Mandatory=$true)][string]$ProjectRoot,
        [Parameter(Mandatory=$true)][string]$SandboxHome,
        [Parameter(Mandatory=$true)][string]$NgrokConfigHome
    )
    $expectedHash='082F0336D62544EB8A8E48FB5D48EA9B4028F90A4E20D22E71F16C542875BACA'
    $bundle=Join-Path $ProjectRoot 'transfer-private'
    $executable=Join-Path $SandboxHome 'bin/ngrok.exe'
    $config=Join-Path $NgrokConfigHome 'ngrok.yml'
    if (!(Test-Path -LiteralPath $executable -PathType Leaf)) {
        $source=Join-Path $bundle 'ngrok.exe'
        if (!(Test-Path -LiteralPath $source -PathType Leaf)) {
            throw 'Khong tim thay ngrok.exe. Giai nen goi SUA-HTTPS vao dung thu muc du an, chon Replace all, roi chay lai.'
        }
        if ((Get-FileHash -LiteralPath $source -Algorithm SHA256).Hash -ne $expectedHash) {
            throw 'File ngrok di kem khong khop checksum. Tai lai goi SUA-HTTPS; khong chay file nay.'
        }
        $null=New-Item -ItemType Directory -Path (Split-Path -Parent $executable) -Force
        Copy-Item -LiteralPath $source -Destination $executable
    }
    if (!(Test-Path -LiteralPath $config -PathType Leaf)) {
        $source=Join-Path $bundle 'ngrok.yml'
        if (!(Test-Path -LiteralPath $source -PathType Leaf)) {
            throw 'Thieu cau hinh ngrok.yml. Giai nen day du goi FULL hoac goi SUA-HTTPS vao du an.'
        }
        $null=New-Item -ItemType Directory -Path $NgrokConfigHome -Force
        Copy-Item -LiteralPath $source -Destination $config
    }
    return [PSCustomObject]@{Executable=$executable;Config=$config}
}
