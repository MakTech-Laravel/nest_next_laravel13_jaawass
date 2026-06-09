<?php

namespace Database\Seeders;

use App\Models\Industry;
use App\Models\SubCategory;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class SubCategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        
        $industries = Industry::all();

        $sub_categories = [];


        foreach($industries as $key => $value){
             $sub_categories [] = [
                'name' => 'sub category ' . $key,
                 'slug' => 'category-'.$key, 
                 'industry_id' => $value->id,
             ];
        }

        SubCategory::insert($sub_categories);
    }
}
