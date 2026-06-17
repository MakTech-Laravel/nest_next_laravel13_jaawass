<?php

namespace Database\Seeders;

use App\Enums\UserManuFactureStatus;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\Industry;
use App\Models\User;
use App\Services\Company\CompanySlugService;
use Illuminate\Database\Seeder;

class ManufacturerCompanySeeder extends Seeder
{
    /**
     * Create company profiles for manufacturers that do not have one yet.
     * Public /api/v1/suppliers requires an approved manufacturer with a company_name.
     */
    public function run(): void
    {
        $slugService = app(CompanySlugService::class);
        $industryIds = Industry::query()->orderBy('id')->limit(2)->pluck('id')->all();

        $profiles = [
            'manufacturer@dev.com' => [
                'company_name' => 'TechVision Electronics Co., Ltd.',
                'short_description' => 'Leading electronics manufacturer specializing in consumer devices and IoT products.',
                'long_description' => 'TechVision Electronics has been manufacturing high-quality electronic products since 2008. We serve buyers worldwide with OEM and ODM services.',
                'company_type' => 'manufacturer',
                'company_established' => '2008',
                'company_size' => '500-1000',
                'revenue' => '$10M - $50M',
                'country' => 'China',
                'city' => 'Shenzhen',
                'street_address' => '88 Nanshan Tech Park, Nanshan District',
                'phone' => '+86 755 1234 5678',
                'zip_code' => '518000',
                'minimum_order_value' => 5000,
                'company_website' => 'https://techvision-electronics.example.com',
                'capabilities' => json_encode(['OEM', 'ODM', 'Custom packaging', 'Product design']),
                'certifications' => json_encode(['ISO9001', 'CE', 'FCC', 'RoHS']),
                'export_markets' => json_encode(['North America', 'Europe', 'Southeast Asia']),
                'language_spoken' => json_encode(['English', 'Chinese']),
                'payments_term' => json_encode(['T/T', 'L/C', 'PayPal']),
            ],
            'meheduvau@gmail.com' => [
                'company_name' => 'Global Textile Industries Ltd.',
                'short_description' => 'Premium textile and apparel manufacturing for global brands.',
                'long_description' => 'Global Textile Industries provides fabric sourcing, garment production, and private-label manufacturing for international buyers.',
                'company_type' => 'manufacturer',
                'company_established' => '2012',
                'company_size' => '200-500',
                'revenue' => '$5M - $10M',
                'country' => 'Bangladesh',
                'city' => 'Dhaka',
                'street_address' => '12 Industrial Area, Gazipur',
                'phone' => '+880 1712 345678',
                'zip_code' => '1700',
                'minimum_order_value' => 3000,
                'company_website' => 'https://global-textile.example.com',
                'capabilities' => json_encode(['Cut & sew', 'Embroidery', 'Private label']),
                'certifications' => json_encode(['ISO9001', 'OEKO-TEX', 'BSCI']),
                'export_markets' => json_encode(['Europe', 'North America', 'Middle East']),
                'language_spoken' => json_encode(['English', 'Bengali']),
                'payments_term' => json_encode(['T/T', 'L/C']),
            ],
        ];

        User::query()
            ->where('role', UserRole::MANUFACTURER->value)
            ->where('status', UserStatus::ACTIVE->value)
            ->whereDoesntHave('company')
            ->get()
            ->each(function (User $manufacturer) use ($profiles, $slugService, $industryIds): void {
                $payload = $profiles[$manufacturer->email] ?? $this->defaultProfile($manufacturer);

                $company = $manufacturer->company()->create($payload);
                $slugService->syncSlug($company, $payload['company_name']);

                if ($industryIds !== [] && $manufacturer->manufacture_status === UserManuFactureStatus::APPROVED) {
                    $company->industries()->sync(
                        $manufacturer->email === 'manufacturer@dev.com'
                            ? array_slice($industryIds, 0, 1)
                            : array_slice($industryIds, -1)
                    );
                }
            });
    }

    /**
     * @return array<string, mixed>
     */
    private function defaultProfile(User $manufacturer): array
    {
        $name = trim("{$manufacturer->first_name} {$manufacturer->last_name} Manufacturing");

        return [
            'company_name' => $name !== 'Manufacturing' ? $name : "Manufacturer #{$manufacturer->id}",
            'short_description' => 'Manufacturing company profile.',
            'long_description' => 'Company profile seeded for development and testing.',
            'company_type' => 'manufacturer',
            'company_established' => '2015',
            'company_size' => '50-200',
            'revenue' => '$1M - $5M',
            'country' => 'China',
            'city' => 'Guangzhou',
            'street_address' => '100 Factory Road',
            'phone' => '+86 20 0000 0000',
            'zip_code' => '510000',
            'minimum_order_value' => 1000,
            'capabilities' => json_encode(['OEM']),
            'certifications' => json_encode(['ISO9001']),
            'export_markets' => json_encode(['Asia']),
            'language_spoken' => json_encode(['English']),
            'payments_term' => json_encode(['T/T']),
        ];
    }
}
