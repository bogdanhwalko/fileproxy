<?php

namespace App\Services;

use App\Models\FileFolder;
use Illuminate\Support\Facades\Session;

/**
 * Tracks which password-protected folders the current session has unlocked,
 * with a 30-minute sliding window from last access.
 *
 * Session key: 'unlocked_folders' => [folder_id => unix_timestamp].
 */
class FolderUnlockService
{
    private const SESSION_KEY = 'unlocked_folders';
    private const TTL_SECONDS = 1800; // 30 minutes

    /**
     * Check whether the given folder is currently unlocked in the session.
     * Automatically refreshes the timestamp on hit (sliding window).
     */
    public function isUnlocked(int|FileFolder $folder): bool
    {
        $id = $folder instanceof FileFolder ? $folder->id : $folder;
        $store = Session::get(self::SESSION_KEY, []);
        $now = now()->timestamp;

        if (! isset($store[$id])) {
            return false;
        }

        if ($now - $store[$id] > self::TTL_SECONDS) {
            unset($store[$id]);
            Session::put(self::SESSION_KEY, $store);
            return false;
        }

        // Sliding window: refresh on every check
        $store[$id] = $now;
        Session::put(self::SESSION_KEY, $store);
        return true;
    }

    public function unlock(FileFolder $folder): void
    {
        $store = Session::get(self::SESSION_KEY, []);
        $store[$folder->id] = now()->timestamp;
        Session::put(self::SESSION_KEY, $store);
    }

    public function lock(FileFolder $folder): void
    {
        $store = Session::get(self::SESSION_KEY, []);
        unset($store[$folder->id]);
        Session::put(self::SESSION_KEY, $store);
    }

    /**
     * @return array<int,int> map of folder_id => last-access timestamp,
     *                       after pruning expired entries.
     */
    public function unlockedFolderIds(): array
    {
        $store = Session::get(self::SESSION_KEY, []);
        $now = now()->timestamp;
        $changed = false;

        foreach ($store as $id => $ts) {
            if ($now - $ts > self::TTL_SECONDS) {
                unset($store[$id]);
                $changed = true;
            }
        }

        if ($changed) {
            Session::put(self::SESSION_KEY, $store);
        }

        return $store;
    }
}
