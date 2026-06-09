<?php

namespace Database\Seeders;


use App\Models\Industry;
use App\Models\Product;
use Illuminate\Database\Seeder;


class ProductSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = Industry::all();

        $categories->load('subCategories');



        foreach ($categories as $key => $cat) {

            $product =  Product::create([
                'industry_id' => $cat->id,
                'user_id' => 4, // Approved manufacturer user
                'name' => 'Product ' . $key,
                'slug' => "product-" . $key,
                'description' => 'Descripiton ' . $key,
                'sub_category_id' => $cat->subCategories()->first()->id,
                'status' => 'active',
                'is_approved' => true,

            ]);


            $product->pricingQuantities()->create([
                'min_price' => 100 + $key,
                'max_price' => 200 + $key,
                'minimum_order_quantity' => 10 + $key,
                'unit' => 'piece',
                'lead_time' => 7 + $key,
                'currency_id' => 1,
                'production_capacity' => 100 + $key,
                'production_duration' => 30 + $key,
                'production_unit' => 'Piece',
                'production_capacity' => 100 + $key,
            ]);


            $product->specifications()->create([
                'specification_title' => 'Specification ' . $key,
                'specification_value' => 'Value ' . $key,
            ]);


            $product->productKeyFeatures()->create([
                'key_feature' => 'Key Feature ' . $key,
            ]);

            $product->customizationOptions()->create([
                'option' => 'Custom Option ' . $key,
            ]);

            $product->shippingPackaging()->create([
                'packaging_type' => 'Packaging Type ' . $key,
                'port_of_loading' => 'Port of Loading ' . $key,
                'packaging_dimensions' => 'Packaging Dimensions ' . $key,
                'packaging_weight' => 'Packaging Weight ' . $key,
                'packaging_cost_per_unit' => 100 + $key,
                'packaging_description' => 'Packaging Description ' . $key,
            ]);

            $product->shippingMethods()->attach([1]);

            $product->load([
                'user',
                'currency',
                'category',
                'subCategory',
                'images',
                'pricingQuantities.currency',
                'specifications',
                'productKeyFeatures',
                'customizationOptions',
                'shippingPackaging',
                'availableOptions',
                'shippingMethods'
            ]);

        }
    }
}
