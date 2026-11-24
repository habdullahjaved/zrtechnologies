<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum VideoSource: string implements HasLabel
{
    case Upload = 'upload';
    case YouTube = 'youtube';
    case TikTok = 'tiktok';
    case Instagram = 'instagram';

    public function getLabel(): string
    {
        return match ($this) {
            self::Upload => 'Uploaded video file',
            self::YouTube => 'YouTube Shorts',
            self::TikTok => 'TikTok',
            self::Instagram => 'Instagram Reel',
        };
    }

    /**
     * Platforms whose players are embedded on the page. TikTok and Instagram
     * open on their own site instead, because their embed scripts are heavy.
     */
    public function isEmbeddable(): bool
    {
        return in_array($this, [self::Upload, self::YouTube], true);
    }
}
