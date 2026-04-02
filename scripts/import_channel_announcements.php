<?php
// Import announcement-style channel posts into events table.

declare(strict_types=1);

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../models/UserModel.php';

/**
 * CLI options:
 * --organizer-email=...   Required. Existing organizer/admin user email.
 * --source-dir=...        Optional. Default: ChatExport_2026-03-11
 * --default-capacity=...  Optional. Default: 120
 * --verified=0|1          Optional. Default: 1
 * --dry-run               Optional. Parse without DB inserts/copies.
 */
$options = getopt('', [
    'organizer-email:',
    'source-dir::',
    'default-capacity::',
    'verified::',
    'dry-run',
]);

$organizerEmail = isset($options['organizer-email']) ? trim((string) $options['organizer-email']) : '';
if ($organizerEmail === '') {
    fwrite(STDERR, "Missing required option: --organizer-email\n");
    exit(1);
}

$sourceDir = isset($options['source-dir']) && trim((string) $options['source-dir']) !== ''
    ? trim((string) $options['source-dir'])
    : (__DIR__ . '/../ChatExport_2026-03-11');
$sourceDir = str_replace('\\', '/', $sourceDir);

$defaultCapacity = isset($options['default-capacity']) ? (int) $options['default-capacity'] : 120;
if ($defaultCapacity <= 0) {
    $defaultCapacity = 120;
}

$verified = isset($options['verified']) ? ((int) $options['verified'] === 1 ? 1 : 0) : 1;
$dryRun = array_key_exists('dry-run', $options);

if (!is_dir($sourceDir)) {
    fwrite(STDERR, "Source directory not found: {$sourceDir}\n");
    exit(1);
}

$user = user_find_by_email($organizerEmail);
if (!$user) {
    fwrite(STDERR, "Organizer user not found for email: {$organizerEmail}\n");
    exit(1);
}

if (!in_array($user['role'] ?? '', ['organizer', 'admin'], true)) {
    fwrite(STDERR, "User exists but role is not organizer/admin: {$organizerEmail}\n");
    exit(1);
}

$organizerId = (int) $user['id'];
$uploadsDir = __DIR__ . '/../public/uploads';
if (!$dryRun && !is_dir($uploadsDir) && !mkdir($uploadsDir, 0755, true) && !is_dir($uploadsDir)) {
    fwrite(STDERR, "Could not create uploads directory: {$uploadsDir}\n");
    exit(1);
}

$messageFiles = glob($sourceDir . '/messages*.html');
if (!$messageFiles) {
    fwrite(STDERR, "No messages*.html files found in: {$sourceDir}\n");
    exit(1);
}
sort($messageFiles);

$pdo = get_pdo();
$selectExistingStmt = $pdo->prepare(
    'SELECT id FROM events WHERE organizer_id = :organizer_id AND title = :title AND event_date = :event_date AND location = :location LIMIT 1'
);
$insertStmt = $pdo->prepare(
    'INSERT INTO events (organizer_id, title, description, image_path, location, event_date, capacity, is_verified, created_at)
     VALUES (:organizer_id, :title, :description, :image_path, :location, :event_date, :capacity, :is_verified, NOW())'
);

$parsed = 0;
$matchedAnnouncements = 0;
$inserted = 0;
$skippedExisting = 0;
$skippedInvalid = 0;

foreach ($messageFiles as $filePath) {
    $dom = new DOMDocument();
    libxml_use_internal_errors(true);
    $loaded = $dom->loadHTML((string) file_get_contents($filePath));
    libxml_clear_errors();

    if (!$loaded) {
        fwrite(STDERR, "Could not parse HTML file: {$filePath}\n");
        continue;
    }

    $xpath = new DOMXPath($dom);
    $messageNodes = $xpath->query('//div[contains(concat(" ", normalize-space(@class), " "), " message ") and contains(concat(" ", normalize-space(@class), " "), " default ")]');

    if ($messageNodes === false) {
        continue;
    }

    /** @var DOMElement $messageNode */
    foreach ($messageNodes as $messageNode) {
        $parsed++;

        $textNode = $xpath->query('.//div[contains(concat(" ", normalize-space(@class), " "), " text ")]', $messageNode);
        $rawText = $textNode !== false && $textNode->length > 0 ? normalize_text($textNode->item(0)?->textContent ?? '') : '';
        if ($rawText === '') {
            continue;
        }

        $postedAtTitleNode = $xpath->query('.//div[contains(concat(" ", normalize-space(@class), " "), " date ") and @title]', $messageNode);
        $postedAtTitleElement = $postedAtTitleNode !== false && $postedAtTitleNode->length > 0 && $postedAtTitleNode->item(0) instanceof DOMElement
            ? $postedAtTitleNode->item(0)
            : null;
        $postedAtTitle = $postedAtTitleElement instanceof DOMElement
            ? (string) $postedAtTitleElement->getAttribute('title')
            : '';
        $postedAt = parse_posted_at($postedAtTitle);

        $announcement = extract_announcement_fields($rawText, $postedAt);
        if ($announcement === null) {
            continue;
        }
        $matchedAnnouncements++;

        $photoHrefNode = $xpath->query('.//a[contains(concat(" ", normalize-space(@class), " "), " photo_wrap ")]', $messageNode);
        $photoHrefElement = $photoHrefNode !== false && $photoHrefNode->length > 0 && $photoHrefNode->item(0) instanceof DOMElement
            ? $photoHrefNode->item(0)
            : null;
        $photoHref = $photoHrefElement instanceof DOMElement
            ? trim((string) $photoHrefElement->getAttribute('href'))
            : '';

        $imagePath = null;
        if ($photoHref !== '') {
            $sourcePhotoPath = realpath($sourceDir . '/' . str_replace('\\', '/', $photoHref));
            if ($sourcePhotoPath !== false && is_file($sourcePhotoPath)) {
                $ext = strtolower(pathinfo($sourcePhotoPath, PATHINFO_EXTENSION));
                $safeExt = in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp'], true) ? $ext : 'jpg';
                $targetName = 'event_import_' . md5($sourcePhotoPath) . '.' . $safeExt;
                $targetPath = $uploadsDir . '/' . $targetName;

                if (!$dryRun && !is_file($targetPath)) {
                    copy($sourcePhotoPath, $targetPath);
                }
                $imagePath = 'uploads/' . $targetName;
            }
        }

        if ($announcement['location'] === '' || $announcement['event_datetime'] === null) {
            $skippedInvalid++;
            continue;
        }

        $eventDate = $announcement['event_datetime']->format('Y-m-d H:i:s');
        $selectExistingStmt->execute([
            ':organizer_id' => $organizerId,
            ':title' => $announcement['title'],
            ':event_date' => $eventDate,
            ':location' => $announcement['location'],
        ]);
        $existing = $selectExistingStmt->fetch();
        if ($existing !== false) {
            $skippedExisting++;
            continue;
        }

        if (!$dryRun) {
            $insertStmt->execute([
                ':organizer_id' => $organizerId,
                ':title' => $announcement['title'],
                ':description' => $announcement['description'],
                ':image_path' => $imagePath,
                ':location' => $announcement['location'],
                ':event_date' => $eventDate,
                ':capacity' => $defaultCapacity,
                ':is_verified' => $verified,
            ]);
        }
        $inserted++;
    }
}

echo "Import finished.\n";
echo "Parsed messages: {$parsed}\n";
echo "Matched announcements: {$matchedAnnouncements}\n";
echo "Inserted events: {$inserted}\n";
echo "Skipped existing: {$skippedExisting}\n";
echo "Skipped invalid: {$skippedInvalid}\n";
echo $dryRun ? "Mode: dry-run (no DB writes)\n" : "Mode: live import\n";

function normalize_text(string $text): string
{
    $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    $text = preg_replace('/\R+/u', "\n", $text) ?? $text;
    $text = preg_replace('/[ \t]+/u', ' ', $text) ?? $text;
    return trim($text);
}

function parse_posted_at(string $title): ?DateTimeImmutable
{
    // Example: "18.08.2022 18:22:19 UTC+03:00"
    if (preg_match('/^(\d{2})\.(\d{2})\.(\d{4})\s+(\d{2}):(\d{2}):(\d{2})/', $title, $m) !== 1) {
        return null;
    }

    $dt = DateTimeImmutable::createFromFormat('d.m.Y H:i:s', "{$m[1]}.{$m[2]}.{$m[3]} {$m[4]}:{$m[5]}:{$m[6]}");
    return $dt ?: null;
}

/**
 * Returns parsed event fields or null if message is not announcement-style.
 *
 * @return array{title:string, description:string, location:string, event_datetime:?DateTimeImmutable}|null
 */
function extract_announcement_fields(string $text, ?DateTimeImmutable $postedAt): ?array
{
    $hasDateCue = preg_match('/\b(monday|tuesday|wednesday|thursday|friday|saturday|sunday)\b|\b(january|february|march|april|may|june|july|august|september|october|november|december)\b|\b\d{1,2}[\/.-]\d{1,2}(?:[\/.-]\d{2,4})?\b/i', $text) === 1;
    $hasTimeCue = preg_match('/\b\d{1,2}:\d{2}\s*(am|pm)\b|\b\d{1,2}\s*(am|pm)\b/i', $text) === 1;
    $hasLocationCue = preg_match('/📌|📍|\b(venue|location|at)\b/i', $text) === 1;

    if (!($hasDateCue && $hasTimeCue && $hasLocationCue)) {
        return null;
    }

    $lines = array_values(array_filter(array_map('trim', preg_split('/\R/u', $text) ?: []), static fn($line) => $line !== ''));
    $title = $lines[0] ?? '';
    if ($title === '') {
        return null;
    }
    $title = preg_replace('/[#@].*/', '', $title) ?? $title;
    $title = trim($title);
    if (mb_strlen($title) > 150) {
        $title = trim(mb_substr($title, 0, 147)) . '...';
    }

    $location = extract_location($lines);
    $eventDatetime = extract_event_datetime($text, $postedAt);

    return [
        'title' => $title,
        'description' => $text,
        'location' => $location,
        'event_datetime' => $eventDatetime,
    ];
}

function extract_location(array $lines): string
{
    foreach ($lines as $line) {
        if (preg_match('/^(?:📌|📍)\s*(.+)$/u', $line, $m) === 1) {
            return trim($m[1]);
        }
    }

    foreach ($lines as $line) {
        if (preg_match('/\b(?:venue|location)\s*[:\-]\s*(.+)$/i', $line, $m) === 1) {
            return trim($m[1]);
        }
    }

    foreach ($lines as $line) {
        if (preg_match('/\bat\s+([A-Z][^.,;]{2,})/u', $line, $m) === 1) {
            return trim($m[1]);
        }
    }

    return '';
}

function extract_event_datetime(string $text, ?DateTimeImmutable $postedAt): ?DateTimeImmutable
{
    $time = null;
    if (preg_match('/\b(\d{1,2})(?::(\d{2}))?\s*(am|pm)\b/i', $text, $tm) === 1) {
        $hour = (int) $tm[1];
        $minute = isset($tm[2]) && $tm[2] !== '' ? (int) $tm[2] : 0;
        $meridian = strtolower($tm[3]);
        if ($meridian === 'pm' && $hour < 12) {
            $hour += 12;
        } elseif ($meridian === 'am' && $hour === 12) {
            $hour = 0;
        }
        $time = sprintf('%02d:%02d:00', $hour, $minute);
    }

    $year = $postedAt ? (int) $postedAt->format('Y') : (int) date('Y');

    if (preg_match('/\b(monday|tuesday|wednesday|thursday|friday|saturday|sunday)?\s*(january|february|march|april|may|june|july|august|september|october|november|december)\s+(\d{1,2})(?:,\s*(\d{4}))?/i', $text, $dm) === 1) {
        $month = $dm[2];
        $day = (int) $dm[3];
        $parsedYear = isset($dm[4]) && $dm[4] !== '' ? (int) $dm[4] : $year;
        $dateString = sprintf('%04d-%02d-%02d', $parsedYear, (int) date('n', strtotime($month . ' 1')), $day);
        $dateTimeString = $dateString . ' ' . ($time ?? '12:00:00');
        $dt = DateTimeImmutable::createFromFormat('Y-m-d H:i:s', $dateTimeString);
        return $dt ?: null;
    }

    if (preg_match('/\b(\d{1,2})[\/.-](\d{1,2})(?:[\/.-](\d{2,4}))?\b/', $text, $nm) === 1) {
        $day = (int) $nm[1];
        $month = (int) $nm[2];
        $parsedYear = isset($nm[3]) && $nm[3] !== '' ? (int) $nm[3] : $year;
        if ($parsedYear < 100) {
            $parsedYear += 2000;
        }
        $dateTimeString = sprintf('%04d-%02d-%02d %s', $parsedYear, $month, $day, $time ?? '12:00:00');
        $dt = DateTimeImmutable::createFromFormat('Y-m-d H:i:s', $dateTimeString);
        return $dt ?: null;
    }

    return null;
}

