<?php

namespace Database\Seeders;

use App\Models\FaqCategory;
use Illuminate\Database\Seeder;

class FaqSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $faqCategories = FaqCategory::all();

        foreach ($faqCategories as $faqCategory) {
            $faqCategory->faqs()->create([
                'question' => 'Question ' . $faqCategory->id,
                'answer' => 'Answer ' . $faqCategory->id,
                'sort' => 1,
            ]);
        }
    }
}
