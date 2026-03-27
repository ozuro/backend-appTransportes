<?php

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $roles = [
            [
                'code' => 'owner',
                'name' => 'Dueño',
                'description' => 'Acceso total a la empresa y configuración comercial.',
            ],
            [
                'code' => 'admin',
                'name' => 'Administrador',
                'description' => 'Gestiona operaciones, usuarios y catálogo operativo.',
            ],
            [
                'code' => 'dispatcher',
                'name' => 'Despachador',
                'description' => 'Programa servicios, asigna choferes y monitorea viajes.',
            ],
            [
                'code' => 'accountant',
                'name' => 'Contador',
                'description' => 'Accede a facturación, cobranzas y reportes financieros.',
            ],
            [
                'code' => 'driver',
                'name' => 'Chofer',
                'description' => 'Ve servicios asignados, reporta ubicación y evidencias.',
            ],
            [
                'code' => 'assistant',
                'name' => 'Ayudante',
                'description' => 'Participa en servicios y registra evidencias permitidas.',
            ],
        ];

        foreach ($roles as $role) {
            Role::updateOrCreate(
                ['code' => $role['code']],
                $role
            );
        }
    }
}
