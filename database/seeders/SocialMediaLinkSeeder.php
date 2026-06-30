<?php

namespace Database\Seeders;

use App\Models\SocialMediaLink;
use Illuminate\Database\Seeder;

class SocialMediaLinkSeeder extends Seeder
{
    public function run(): void
    {
        if (SocialMediaLink::query()->exists()) {
            return;
        }

        $links = [
            [
                'platform' => 'LinkedIn',
                'icon' => 'Linkedin',
                'url' => 'https://linkedin.com/company/sourcenest',
                'enabled' => true,
                'sort' => 1,
            ],
            [
                'platform' => 'X (Twitter)',
                'icon' => 'Twitter',
                'url' => 'https://twitter.com/sourcenest',
                'enabled' => true,
                'sort' => 2,
            ],
            [
                'platform' => 'Facebook',
                'icon' => 'Facebook',
                'url' => 'https://facebook.com/sourcenest',
                'enabled' => true,
                'sort' => 3,
            ],
            [
                'platform' => 'YouTube',
                'icon' => 'Youtube',
                'url' => 'https://youtube.com/@sourcenest',
                'enabled' => true,
                'sort' => 4,
            ],
            [
                'platform' => 'Instagram',
                'icon' => 'Instagram',
                'url' => 'https://www.instagram.com/sourcenest1/',
                'enabled' => true,
                'sort' => 5,
            ],
            [
                'platform' => 'TikTok',
                'icon' => 'Music2',
                'url' => 'https://tiktok.com/@sourcenest',
                'enabled' => false,
                'sort' => 6,
            ],
        ];

        foreach ($links as $link) {
            SocialMediaLink::query()->create($link);
        }
    }
}
