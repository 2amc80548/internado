<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Provincia;
use App\Models\Comunidad;

class ComunidadesCordilleraSeeder extends Seeder
{
    public function run(): void
    {
        $provincia = Provincia::where('nombre', 'Cordillera')->first();

        if ($provincia) {
            $comunidades = [
                'Camiri',
                'Charagua',
                'Cabezas',
                'Cuevo',
                'Boyuibe',
                'Lagunillas',
                'Gutiérrez',
                'Ipati',
                'Choreti',
                'Abapó',
                'Izozog',
                'Urundaiti',
                'Kaami',
                'Taputá',
                'Mora',
                'El Espino',
                'Securé',
                'Chani',
                'Saipurú'
            ];

            foreach ($comunidades as $nombre) {
                Comunidad::firstOrCreate([
                    'nombre' => $nombre,
                    'provincia_id' => $provincia->id
                ]);
            }
        }
    }
}
