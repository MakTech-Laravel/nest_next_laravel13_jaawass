<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('companies', function (Blueprint $table): void {
            $table->string('slug')->nullable()->after('company_name');
        });

        $usedSlugs = [];

        DB::table('companies')
            ->orderBy('id')
            ->get(['id', 'company_name'])
            ->each(function (object $company) use (&$usedSlugs): void {
                $baseSlug = Str::slug((string) $company->company_name);

                if ($baseSlug === '') {
                    $baseSlug = 'supplier-'.$company->id;
                }

                $slug = $baseSlug;
                $suffix = 2;

                while (in_array($slug, $usedSlugs, true)) {
                    $slug = $baseSlug.'-'.$suffix;
                    $suffix++;
                }

                $usedSlugs[] = $slug;

                DB::table('companies')->where('id', $company->id)->update(['slug' => $slug]);
            });

        Schema::table('companies', function (Blueprint $table): void {
            $table->unique('slug');
        });
    }

    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table): void {
            $table->dropUnique(['slug']);
            $table->dropColumn('slug');
        });
    }
};
