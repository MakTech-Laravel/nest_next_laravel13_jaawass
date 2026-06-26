<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::table('orders')
            ->orderBy('id')
            ->chunkById(100, function ($orders): void {
                foreach ($orders as $order) {
                    if (DB::table('order_items')->where('order_id', $order->id)->exists()) {
                        continue;
                    }

                    $quantity = max((int) $order->quantity, 1);
                    $unitPrice = round((float) $order->total_amount / $quantity, 2);

                    DB::table('order_items')->insert([
                        'order_id' => $order->id,
                        'product_id' => $order->product_id,
                        'quantity' => $order->quantity,
                        'quantity_unit' => $order->quantity_unit ?? 'pieces',
                        'unit_price' => $unitPrice,
                        'line_total' => $order->total_amount,
                        'notes' => null,
                        'created_at' => $order->created_at ?? now(),
                        'updated_at' => $order->updated_at ?? now(),
                    ]);
                }
            });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('order_items')->truncate();
    }
};
