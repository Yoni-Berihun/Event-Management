<?php
// Rate-limiter: IP+user sliding window backed by rate_limit_hits table.
declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';

function rate_limit_check(string $action, int $maxHits = RATE_LIMIT_MAX_ACTION, int $windowSeconds = RATE_LIMIT_WINDOW_SECONDS): bool
{
    $ip     = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $userId = isset($_SESSION['user_id']) ? (int) $_SESSION['user_id'] : 0;
    $key    = $action . '|' . $ip . '|' . $userId;

    try {
        $pdo = get_pdo();

        $pdo->prepare("DELETE FROM rate_limit_hits WHERE `key`=? AND hit_at < NOW() - INTERVAL ? SECOND")
            ->execute([$key, $windowSeconds]);

        $cntStmt = $pdo->prepare("SELECT COUNT(*) AS c FROM rate_limit_hits WHERE `key`=?");
        $cntStmt->execute([$key]);
        $cnt = (int) ($cntStmt->fetch()['c'] ?? 0);

        if ($cnt >= $maxHits) {
            return false;
        }

        $pdo->prepare("INSERT INTO rate_limit_hits (`key`) VALUES (?)")->execute([$key]);
        return true;
    } catch (Throwable) {
        return true; // fail open
    }
}
