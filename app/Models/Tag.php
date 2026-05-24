<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Tag extends Model
{
    use HasFactory;

    protected $fillable = ['user_id', 'name'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function files(): BelongsToMany
    {
        return $this->belongsToMany(ManagedFile::class, 'managed_file_tag')->withTimestamps();
    }

    /**
     * Normalize tag input: trim, lowercase, replace whitespace/separators with single space.
     * Empty after normalization → null (caller should skip).
     */
    public static function normalizeName(string $raw): ?string
    {
        $raw = trim(mb_strtolower($raw));
        $raw = preg_replace('/\s+/u', ' ', $raw) ?? '';
        $raw = preg_replace('/[\x00-\x1F]/u', '', $raw) ?? '';
        $raw = mb_substr($raw, 0, 64);

        return $raw === '' ? null : $raw;
    }

    /**
     * Parse a free-text "tag1, tag2, ..." input into normalized unique names.
     *
     * @return array<int,string>
     */
    public static function parseInput(?string $rawInput): array
    {
        if ($rawInput === null) {
            return [];
        }

        $parts = preg_split('/[,;\n\r]+/u', (string) $rawInput) ?: [];
        $names = [];
        foreach ($parts as $part) {
            $normalized = self::normalizeName((string) $part);
            if ($normalized !== null) {
                $names[$normalized] = true; // dedupe
            }
        }

        return array_keys($names);
    }
}
