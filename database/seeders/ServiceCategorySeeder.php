<?php

namespace Database\Seeders;

use App\Models\ServiceCategory;
use Illuminate\Database\Seeder;

class ServiceCategorySeeder extends Seeder
{
    public function run()
    {
        $categories = [
            'Engine Repair',
            'Transmission',
            'Brakes',
            'Suspension',
            'Electrical',
            'Air Conditioning',
            'Heating & Cooling',
            'Exhaust',
            'Fuel System',
            'Steering',
            'Wheel Alignment',
            'Tires',
            'Battery',
            'Diagnostics',
            'Preventive Maintenance',
            'Oil Change',
            'Body Repair',
            'Painting',
            'Interior',
            'Glass & Windows',
            'Towing',
            'Roadside Assistance',
            'Car Wash',
            'Detailing',
        ];

        foreach ($categories as $category) {
            ServiceCategory::firstOrCreate([
                'name' => $category,
                'is_active' => true,
            ]);
        }
    }
}