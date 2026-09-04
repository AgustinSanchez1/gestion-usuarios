<?php

namespace App\Http\Middleware;

use App\Models\System;
use Closure;
use Illuminate\Http\Request;
use Spatie\Permission\PermissionRegistrar;
use Symfony\Component\HttpFoundation\Response;

class SetSystemTeam
{
    /**
     * Handle an incoming request.
     *
     * Lee el header X-System y setea el team_id correspondiente
     * en el PermissionRegistrar de Spatie para que todos los
     * chequeos de roles/permisos se hagan dentro del sistema correcto.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $slug = $request->header('X-System');

        if ($slug) {
            $system = System::where('slug', $slug)->first();

            if ($system) {
                app(PermissionRegistrar::class)->setPermissionsTeamId($system->id);
            }
        }
        // Si no hay header o no se encontró el sistema,
        // el team_id queda en null (comportamiento legacy).

        return $next($request);
    }
}