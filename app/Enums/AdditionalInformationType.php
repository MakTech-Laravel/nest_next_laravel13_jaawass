<?php

namespace App\Enums;

enum AdditionalInformationType: string
{
    case Text = 'text';
    case Image = 'image';
    case Video = 'video';
    case Audio = 'audio';
    case Document = 'document';

    public function label(): string
    {
        return match ($this) {
            self::Text => 'Text message',
            self::Image => 'Image',
            self::Video => 'Video',
            self::Audio => 'Audio',
            self::Document => 'Document',
        };
    }

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
