<?php

namespace Database\Seeders;

use App\Models\VehicleBrand;
use App\Models\VehicleModel;
use Illuminate\Database\Seeder;

class VehicleModelSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get brand IDs
        $brands = VehicleBrand::all()->keyBy('name');

        $models = [
            'Toyota' => ['Camry', 'Corolla', 'RAV4', 'Highlander', 'Tacoma', 'Tundra', 'Sienna', 'Prius', '4Runner', 'Land Cruiser'],
            'Honda' => ['Civic', 'Accord', 'CR-V', 'Pilot', 'Odyssey', 'Fit', 'HR-V', 'Passport', 'Ridgeline'],
            'BMW' => ['3 Series', '5 Series', '7 Series', 'X3', 'X5', 'X7', 'M3', 'M5', 'i3', 'i8'],
            'Mercedes-Benz' => ['C-Class', 'E-Class', 'S-Class', 'GLA', 'GLC', 'GLE', 'GLS', 'A-Class', 'CLA', 'AMG GT'],
            'Audi' => ['A3', 'A4', 'A6', 'A8', 'Q3', 'Q5', 'Q7', 'Q8', 'TT', 'R8'],
            'Ford' => ['F-150', 'Mustang', 'Explorer', 'Escape', 'Edge', 'Ranger', 'Bronco', 'Focus', 'Fusion', 'Expedition'],
            'Nissan' => ['Altima', 'Maxima', 'Rogue', 'Pathfinder', 'Armada', 'Frontier', 'Titan', 'Sentra', 'Versa', 'Murano'],
            'Chevrolet' => ['Silverado', 'Camaro', 'Equinox', 'Traverse', 'Tahoe', 'Suburban', 'Colorado', 'Malibu', 'Impala', 'Blazer'],
            'Lexus' => ['ES', 'IS', 'LS', 'RX', 'NX', 'GX', 'LX', 'RC', 'LC', 'UX'],
            'Jeep' => ['Wrangler', 'Grand Cherokee', 'Cherokee', 'Compass', 'Renegade', 'Gladiator', 'Wagoneer', 'Grand Wagoneer'],
            'Land Rover' => ['Range Rover', 'Discovery', 'Defender', 'Evoque', 'Velar', 'Sport'],
            'Volkswagen' => ['Golf', 'Jetta', 'Passat', 'Tiguan', 'Atlas', 'Taos', 'ID.4', 'ID.Buzz'],
            'Mazda' => ['Mazda3', 'Mazda6', 'CX-5', 'CX-9', 'CX-30', 'MX-5 Miata'],
            'Subaru' => ['Outback', 'Forester', 'Crosstrek', 'Impreza', 'Legacy', 'Ascent', 'WRX'],
            'Hyundai' => ['Elantra', 'Sonata', 'Tucson', 'Santa Fe', 'Palisade', 'Kona', 'Ioniq', 'Venue'],
            'Kia' => ['Sportage', 'Sorento', 'Telluride', 'Forte', 'Optima', 'Stinger', 'Niro', 'Rio'],
            'Porsche' => ['911', 'Cayenne', 'Macan', 'Panamera', 'Taycan', 'Boxster', 'Cayman'],
            'Volvo' => ['XC90', 'XC60', 'XC40', 'S90', 'S60', 'V90', 'C40'],
            'Dodge' => ['Challenger', 'Charger', 'Durango', 'Journey', 'Grand Caravan', 'RAM'],
            'GMC' => ['Sierra', 'Yukon', 'Acadia', 'Terrain', 'Canyon'],
            'Mitsubishi' => ['Outlander', 'Eclipse Cross', 'Mirage', 'Pajero', 'Lancer'],
            'Suzuki' => ['Swift', 'Vitara', 'Jimny', 'S-Cross', 'Baleno'],
            'Ferrari' => ['296 GTB', 'SF90 Stradale', 'Roma', 'Portofino', 'Purosangue'],
            'Lamborghini' => ['Huracan', 'Aventador', 'Urus', 'Revuelto', 'Countach'],
            'Bugatti' => ['Chiron', 'Veyron', 'Divo', 'Bolide'],
            'Bentley' => ['Continental GT', 'Bentayga', 'Flying Spur', 'Mulsanne'],
            'Rolls-Royce' => ['Phantom', 'Cullinan', 'Ghost', 'Wraith', 'Dawn'],
            'McLaren' => ['Artura', '720S', 'GT', '765LT', 'Speedtail'],
            'Aston Martin' => ['DB11', 'Vantage', 'DBS', 'DBX', 'Valhalla'],
            'Maserati' => ['Ghibli', 'Levante', 'Quattroporte', 'MC20', 'Grecale'],
            'Jaguar' => ['F-PACE', 'E-PACE', 'XE', 'XF', 'F-TYPE', 'I-PACE'],
            'Alfa Romeo' => ['Giulia', 'Stelvio', 'Tonale', 'Quadrifoglio'],
            'Fiat' => ['500', '500X', '500L', 'Tipo', 'Panda'],
            'Peugeot' => ['208', '308', '508', '2008', '3008', '5008'],
            'Renault' => ['Clio', 'Megane', 'Captur', 'Kadjar', 'Arkana', 'Austral'],
            'Citroen' => ['C3', 'C4', 'C5', 'C3 Aircross', 'C5 Aircross'],
            'Daihatsu' => ['Gran Max', 'Terios', 'Xenia', 'Ayla', 'Sigra'],
            'Isuzu' => ['D-Max', 'MU-X'],
            'Lotus' => ['Evora', 'Emira', 'Evija', 'Elise', 'Exige'],
            'Koenigsegg' => ['Jesko', 'Regera', 'Gemera', 'CC850'],
        ];

        foreach ($models as $brandName => $modelNames) {
            $brand = $brands->get($brandName);
            if ($brand) {
                foreach ($modelNames as $modelName) {
                    VehicleModel::firstOrCreate([
                        'brand_id' => $brand->id,
                        'name' => $modelName,
                    ]);
                }
            }
        }

        $this->command->info('Vehicle models seeded successfully!');
    }
}