<?php

namespace Database\Seeders;

use App\Models\Industry;
use Illuminate\Database\Seeder;

class IndustrySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $industries = [
            [
                'name' => 'Manufacturing',
                'slug' => 'manufacturing',
                'icon' => 'Layers',
                'color' => '#ff0000',
                'title_color' => '#f8c4c4',
                'desc_color' => null,
                'btn_color' => null,
                'supplier_color' => null,
                'icon_color' => null,
                'sort_order' => 1,
                'featured' => false,
            ],
            [
                'name' => 'Construction',
                'slug' => 'construction',
                'icon' => 'Factory',
                'color' => '#864141',
                'title_color' => '#ffffff',
                'desc_color' => '#ededed',
                'btn_color' => null,
                'supplier_color' => '#d1d1d1',
                'icon_color' => null,
                'sort_order' => 1,
                'featured' => false,
            ],
            [
                'name' => 'Agriculture',
                'slug' => 'agriculture',
                'icon' => 'Heart',
                'color' => '#ff0000',
                'title_color' => null,
                'desc_color' => null,
                'btn_color' => null,
                'supplier_color' => null,
                'icon_color' => null,
                'sort_order' => 1,
                'featured' => false,
            ],
            [
                'name' => 'Mining',
                'slug' => 'mining',
                'icon' => 'Gem',
                'color' => '#893e3e',
                'title_color' => null,
                'desc_color' => null,
                'btn_color' => null,
                'supplier_color' => '#006aff',
                'icon_color' => null,
                'sort_order' => 1,
                'featured' => false,
            ],
            [
                'name' => 'Energy',
                'slug' => 'energy',
                'icon' => 'Hammer',
                'color' => '#ff0000',
                'title_color' => '#a36666',
                'desc_color' => '#a76262',
                'btn_color' => '#ffffff',
                'supplier_color' => '#006aff',
                'icon_color' => null,
                'sort_order' => 1,
                'featured' => false,
            ],
            [
                'name' => 'Transportation',
                'slug' => 'transportation',
                'icon' => null,
                'color' => null,
                'title_color' => null,
                'desc_color' => null,
                'btn_color' => null,
                'supplier_color' => null,
                'icon_color' => null,
                'sort_order' => 1,
                'featured' => false,
            ],
            [
                'name' => 'Healthcare',
                'slug' => 'healthcare',
                'icon' => null,
                'color' => null,
                'title_color' => null,
                'desc_color' => null,
                'btn_color' => null,
                'supplier_color' => null,
                'icon_color' => null,
                'sort_order' => 1,
                'featured' => false,
            ],
            [
                'name' => 'Education',
                'slug' => 'education',
                'icon' => 'Medal',
                'color' => '#a56f6f',
                'title_color' => '#ffbdbd',
                'desc_color' => '#000000',
                'btn_color' => '#0a0a0b',
                'supplier_color' => '#006aff',
                'icon_color' => null,
                'sort_order' => 1,
                'featured' => false,
            ],
        ];

        foreach ($industries as $industry) {
            Industry::query()->updateOrCreate(
                ['slug' => $industry['slug']],
                $industry,
            );
        }
    }
}
