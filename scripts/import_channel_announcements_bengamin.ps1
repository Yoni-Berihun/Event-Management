# Run chat import for organizer bengaminfish@gmail.com.
# Requires: database city_events exists, schema imported, organizer user exists.
#
# From project root (Event_tracking_system_IP folder):
#   .\scripts\import_channel_announcements_bengamin.ps1
# Dry-run:
#   .\scripts\import_channel_announcements_bengamin.ps1 -DryRun

param(
    [switch]$DryRun,
    [int]$DefaultCapacity = 120,
    [int]$Verified = 1
)

$ErrorActionPreference = 'Stop'
$organizerEmail = 'bengaminfish@gmail.com'
$root = Split-Path -Parent (Split-Path -Parent $MyInvocation.MyCommand.Path)
Set-Location $root

$phpArgs = @(
    'scripts/import_channel_announcements.php'
    "--organizer-email=$organizerEmail"
    "--default-capacity=$DefaultCapacity"
    "--verified=$Verified"
)
if ($DryRun) {
    $phpArgs += '--dry-run'
}

& php @phpArgs
