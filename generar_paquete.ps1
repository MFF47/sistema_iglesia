<#
.SYNOPSIS
    Genera un .zip listo para subir al hosting (cPanel), excluyendo archivos
    de desarrollo, pruebas y credenciales reales.

.USO
    Ejecutar desde la raíz del proyecto:
        powershell -ExecutionPolicy Bypass -File generar_paquete.ps1

    El .zip se genera UN NIVEL ARRIBA del proyecto (no dentro), para no
    incluirse a sí mismo por accidente.
#>

$ErrorActionPreference = 'Stop'

$proyecto = $PSScriptRoot
$carpetaPadre = Split-Path $proyecto -Parent
$staging = Join-Path $env:TEMP 'sistema_iglesia_deploy_stage'
$zipDestino = Join-Path $carpetaPadre 'sistema_iglesia_deploy.zip'

Write-Host "Proyecto: $proyecto"
Write-Host "Paquete destino: $zipDestino"

# ----------------------------------------------------------------------------
# 1. Copiar el proyecto a una carpeta temporal, saltando carpetas completas
#    que no deben ir al servidor.
# ----------------------------------------------------------------------------
if (Test-Path $staging) { Remove-Item -Recurse -Force $staging }
New-Item -ItemType Directory -Path $staging | Out-Null

$carpetasExcluir = @('.git', '.claude', 'docs')

Get-ChildItem -Path $proyecto -Force | Where-Object {
    $carpetasExcluir -notcontains $_.Name
} | ForEach-Object {
    Write-Host "Copiando: $($_.Name)"
    Copy-Item -Path $_.FullName -Destination (Join-Path $staging $_.Name) -Recurse -Force
}

# ----------------------------------------------------------------------------
# 2. Quitar archivos sueltos de desarrollo / credenciales reales.
# ----------------------------------------------------------------------------
$archivosExcluir = @(
    'CLAUDE.md',
    'README.md',
    '.gitignore',
    'generar_paquete.ps1',
    'tmp_create_admin.php',
    'tmp_create_test_users.php',
    'tmp_db_test.php',
    'tmp_db_test_ports.php',
    'config\database.php',
    'config\app.php',
    'config\mail.php'
)
foreach ($rel in $archivosExcluir) {
    $p = Join-Path $staging $rel
    if (Test-Path $p) {
        Remove-Item -Force $p
        Write-Host "Excluido: $rel"
    }
}

# ----------------------------------------------------------------------------
# 3. Vaciar comprobantes y logs de prueba, pero conservar las carpetas y su
#    protección (.htaccess) — deben llegar vacías pero presentes y protegidas.
# ----------------------------------------------------------------------------
$carpetasVaciar = @('storage\adjuntos', 'storage\facturas', 'storage\logs')
foreach ($rel in $carpetasVaciar) {
    $p = Join-Path $staging $rel
    if (Test-Path $p) {
        Get-ChildItem -Path $p -Force | ForEach-Object { Remove-Item -Recurse -Force $_.FullName }
        # Marcador para que la carpeta vacía sí quede dentro del .zip.
        New-Item -ItemType File -Path (Join-Path $p '.gitkeep') -Force | Out-Null
        Write-Host "Vaciado (conservando carpeta): $rel"
    }
}

# ----------------------------------------------------------------------------
# 4. Comprimir.
# ----------------------------------------------------------------------------
if (Test-Path $zipDestino) { Remove-Item -Force $zipDestino }
Compress-Archive -Path (Join-Path $staging '*') -DestinationPath $zipDestino -CompressionLevel Optimal

Remove-Item -Recurse -Force $staging

$tamanoMB = [Math]::Round((Get-Item $zipDestino).Length / 1MB, 1)
Write-Host ""
Write-Host "Paquete generado: $zipDestino ($tamanoMB MB)"
Write-Host "Recuerda: en el servidor debes crear config/database.php, config/app.php y config/mail.php"
Write-Host "a partir de sus .example.php, con los datos reales de produccion."
