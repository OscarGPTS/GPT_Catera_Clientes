<?php

namespace Database\Seeders;

use App\Models\Cliente;
use App\Models\Sublinea;
use Illuminate\Database\Seeder;

class ComercialCatalogosSeeder extends Seeder
{
    public function run(): void
    {
        $sublineas = [
            ['codigo' => 'HTP', 'nombre' => 'Hot Tapping', 'descripcion' => 'Servicios de Hot Tap y Plug'],
            ['codigo' => 'LSP', 'nombre' => 'Line Stop', 'descripcion' => 'Servicios de Line Stop'],
            ['codigo' => 'VLV', 'nombre' => 'Válvulas', 'descripcion' => 'Suministro y servicio de válvulas'],
            ['codigo' => 'SOL', 'nombre' => 'Soldadura', 'descripcion' => 'Servicios de soldadura'],
            ['codigo' => 'SG', 'nombre' => 'Servicios Generales', 'descripcion' => 'Otros servicios'],
        ];

        foreach ($sublineas as $row) {
            Sublinea::updateOrCreate(['codigo' => $row['codigo']], $row);
        }

        $clientes = [
            ['razon_social' => 'Secretaría de la Defensa Nacional', 'alias_3letras' => 'SDN', 'sector' => 'Gobierno'],
            ['razon_social' => 'IGASAMEX', 'alias_3letras' => 'IGA', 'sector' => 'Energía'],
            ['razon_social' => 'PROTEXA', 'alias_3letras' => 'PTX', 'sector' => 'Energía'],
            ['razon_social' => 'ESENTIA', 'alias_3letras' => 'FER', 'sector' => 'Energía'],
            ['razon_social' => 'ENGIE México', 'alias_3letras' => 'ENG', 'sector' => 'Energía'],
            ['razon_social' => 'CENAGAS', 'alias_3letras' => 'CGS', 'sector' => 'Gobierno'],
            ['razon_social' => 'PEMEX', 'alias_3letras' => 'PMX', 'sector' => 'Gobierno'],
            ['razon_social' => 'CFE', 'alias_3letras' => 'CFE', 'sector' => 'Gobierno'],
            ['razon_social' => 'IEnova', 'alias_3letras' => 'IEN', 'sector' => 'Energía'],
            ['razon_social' => 'Fermaca', 'alias_3letras' => 'FRM', 'sector' => 'Energía'],
        ];

        foreach ($clientes as $row) {
            Cliente::updateOrCreate(['alias_3letras' => $row['alias_3letras']], $row + ['activo' => true]);
        }
    }
}
