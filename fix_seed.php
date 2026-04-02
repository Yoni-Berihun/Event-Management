<?php
// Fix seed passwords and create placeholder images without GD.
require_once __DIR__ . '/config/db.php';

$pdo = get_pdo();

// 1. Reset all user passwords to 'password'
$hash = password_hash('password', PASSWORD_BCRYPT);
$stmt = $pdo->prepare("UPDATE users SET password_hash = ?");
$stmt->execute([$hash]);
echo "Updated " . $stmt->rowCount() . " user passwords to 'password'.\n";

// 2. Create minimal 1x1 JPEG placeholders for missing Rotaract images.
// These are tiny but valid JEPGs so the <img> tags don't 404.
$images = [
    'event_coffee_nov22.jpg',
    'event_coffee_nov29.jpg',
    'event_culture_day_dec06.jpg',
    'event_trivia_karaoke_jan04.jpg',
];

$uploadsDir = __DIR__ . '/public/uploads';
if (!is_dir($uploadsDir)) {
    mkdir($uploadsDir, 0755, true);
}

// Minimal valid JPEG (1x1 grey pixel) — 107 bytes
$minJpeg = base64_decode(
    '/9j/4AAQSkZJRgABAQAAAQABAAD/2wBDAAMCAgMCAgMDAwMEAwMEBQgFBQQE' .
    'BQoHBwYIDAoMCwsKCwsICQ4SDA0OEAsLDhEOEhETExITCxAVEhMSExAREhP/' .
    '2wBDAQMEBAUEBQkFBQkTDQsNExMTExMTExMTExMTExMTExMTExMTExMTExMT' .
    'ExMTExMTExMTExMTExMTExMTExMTExP/wAARCAABAAEDASIAAhEBAxEB/8QA' .
    'HwAAAQUBAQEBAQEAAAAAAAAAAAECAwQFBgcICQoL/8QAtRAAAgEDAwIEAwUF' .
    'BAQAAAF9AQIDAAQRBRIhMUEGE1FhByJxFDKBkaEII0KxwRVS0fAkM2JyggkK' .
    'FhcYGRolJicoKSo0NTY3ODk6Q0RFRkdISUpTVFVWV1hZWmNkZWZnaGlqc3R1' .
    'dnd4eXqDhIWGh4iJipKTlJWWl5iZmqKjpKWmp6ipqrKztLW2t7i5usLDxMXG' .
    'x8jJytLT1NXW19jZ2uHi4+Tl5ufo6erx8vP09fb3+Pn6/8QAHwEAAwEBAQEB' .
    'AQEBAQAAAAAAAAECAwQFBgcICQoL/8QAtREAAgECBAQDBAcFBAQAAQJ3AAEC' .
    'AxEEBSExBhJBUQdhcRMiMoEIFEKRobHBCSMzUvAVYnLRChYkNOEl8RcYI4Q/' .
    'RFhHRlczTldKJYk5DSo2QlY3a0RXfGJ4ejlMc3R5fI2Oj5CRkpOUlZaXmJma' .
    'oqOkpaanqKmqsrO0tba3uLm6wsPExcbHyMnK0tPU1dbX2Nna4ePk5ebn6Onq' .
    '8vP09fb3+Pn6/9oADAMBAAIRAxEAPwD9U6KKKAf/2Q=='
);

foreach ($images as $filename) {
    $path = $uploadsDir . '/' . $filename;
    if (file_exists($path)) {
        echo "Image already exists: $filename\n";
        continue;
    }
    file_put_contents($path, $minJpeg);
    echo "Created placeholder: $filename\n";
}

// 3. Also clear image_path for Rotaract events if placeholders look bad —
//    Actually let's keep them so the img tags work (no 404).

echo "\nDone! Login with any seed account using password: 'password'\n";
