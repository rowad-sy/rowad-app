<?php

namespace App\Services;

use App\Models\AuditLog;
use Illuminate\Support\Facades\File;

class AuditAnchorService
{
    protected static string $anchorDir = 'private/audit-anchors';

    public static function getAnchorDir(): string
    {
        $path = storage_path(self::$anchorDir);
        if (!File::isDirectory($path)) {
            File::makeDirectory($path, 0755, true);
        }
        return $path;
    }

    public static function createAnchor(): array
    {
        $lastLog = AuditLog::latest('id')->first();

        if (!$lastLog) {
            return ['created' => false, 'reason' => 'no_logs'];
        }

        $count = AuditLog::count();
        $lastHash = $lastLog->hash;
        $previousAnchorHash = self::getPreviousAnchorHash();

        $anchorPayload = json_encode([
            'audit_log_id' => $lastLog->id,
            'count' => $count,
            'last_hash' => $lastHash,
            'prev_anchor_hash' => $previousAnchorHash,
            'created_at' => now()->toDateTimeString(),
        ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

        $anchorHash = hash('sha256', $anchorPayload);

        $filename = now()->format('Y-m-d_His') . '_' . substr($anchorHash, 0, 12) . '.json';

        $fullPayload = json_encode([
            'version' => 1,
            'anchor_hash' => $anchorHash,
            'data' => json_decode($anchorPayload, true),
        ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

        File::put(self::getAnchorDir() . '/' . $filename, $fullPayload);

        return [
            'created' => true,
            'filename' => $filename,
            'anchor_hash' => $anchorHash,
            'audit_log_id' => $lastLog->id,
            'count' => $count,
        ];
    }

    public static function getLatestAnchor(): ?array
    {
        $dir = self::getAnchorDir();
        $files = collect(File::files($dir))
            ->filter(fn($f) => $f->getExtension() === 'json')
            ->sort(fn($a, $b) => $b->getFilename() <=> $a->getFilename());

        if ($files->isEmpty()) {
            return null;
        }

        $latest = $files->first();
        $data = json_decode(File::get($latest->getPathname()), true);

        return $data['data'] ?? null;
    }

    public static function getAllAnchors(): array
    {
        $dir = self::getAnchorDir();
        $files = collect(File::files($dir))
            ->filter(fn($f) => $f->getExtension() === 'json')
            ->sort(fn($a, $b) => $a->getFilename() <=> $b->getFilename());

        $anchors = [];
        foreach ($files as $file) {
            $data = json_decode(File::get($file->getPathname()), true);
            $anchors[] = array_merge($data['data'] ?? [], [
                '_filename' => $file->getFilename(),
                '_anchor_hash' => $data['anchor_hash'] ?? null,
            ]);
        }

        return $anchors;
    }

    protected static function getPreviousAnchorHash(): ?string
    {
        $latest = self::getLatestAnchor();
        return $latest['_anchor_hash'] ?? null;
    }

    public static function verifyAllAnchors(): array
    {
        $anchors = self::getAllAnchors();

        if (empty($anchors)) {
            return ['valid' => true, 'message' => 'no_anchors', 'count' => 0];
        }

        $previousAnchorHash = null;
        $brokenAt = null;

        foreach ($anchors as $anchor) {
            $expectedHash = hash('sha256', json_encode($anchor['data'] ?? $anchor, JSON_UNESCAPED_UNICODE));

            if (isset($anchor['_anchor_hash']) && $expectedHash !== $anchor['_anchor_hash']) {
                $brokenAt = $anchor['_filename'] ?? 'unknown';
                break;
            }

            $previousAnchorHash = $anchor['_anchor_hash'] ?? $expectedHash;
        }

        return [
            'valid' => $brokenAt === null,
            'total_anchors' => count($anchors),
            'broken_at' => $brokenAt,
        ];
    }
}
