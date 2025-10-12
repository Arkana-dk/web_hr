<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\TransportRoute;

class TransportRouteSeeder extends Seeder
{
    public function run(): void
    {
        $routes = ['Jakarta - Bekasi', 'Bekasi - Jakarta', 'Lainnya'];

        foreach ($routes as $route) {
            TransportRoute::create(['route_name' => $route]);
        }
    }
}
