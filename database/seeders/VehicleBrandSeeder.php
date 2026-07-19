<?php

namespace Database\Seeders;

use App\Models\VehicleBrand;
use Illuminate\Database\Seeder;

class VehicleBrandSeeder extends Seeder
{
    public function run()
    {
        $brands = [
            'Toyota',
            'Honda',
            'Ford',
            'Chevrolet',
            'Nissan',
            'BMW',
            'Mercedes-Benz',
            'Audi',
            'Volkswagen',
            'Hyundai',
            'Kia',
            'Mazda',
            'Subaru',
            'Lexus',
            'Jeep',
            'Dodge',
            'Chrysler',
            'Daihatsu',
            'Mitsubishi',
            'Suzuki',
            'Isuzu',
            'Peugeot',
            'Citroen',
            'Renault',
            'Fiat',
            'Volvo',
            'Jaguar',
            'Land Rover',
            'Porsche',
            'Ferrari',
            'Lamborghini',
            'Bentley',
            'Rolls-Royce',
            'Aston Martin',
            'Maserati',
            'Alfa Romeo',
            'Lotus',
            'McLaren',
            'Bugatti',
            'Koenigsegg',
        ];

        foreach ($brands as $brand) {
            VehicleBrand::firstOrCreate([
                'name' => $brand,
                'is_active' => true,
            ]);
        }
    }
}