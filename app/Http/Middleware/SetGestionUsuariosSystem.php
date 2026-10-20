<?php

namespace App\Http\Middleware;

use App\Models\System;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Spatie\Permission\PermissionRegistrar;
use Symfony\Component\HttpFoundation\Response;

class SetGestionUsuariosSystem
{
    public function handle(Request $request, Closure $next): Response
    {
        $slug = 'gestion-usuarios';
        $system = System::where('slug', $slug)->first();

        abort_unless($system, 500, "No existe el sistema '{$slug}'.");

        // Filament/Livewire do not add this header automatically.
        // Set it server-side and establish the Spatie team context.
        $request->headers->set('X-System', $slug);
        $registrar = app(PermissionRegistrar::class);
        $registrar->setPermissionsTeamId($system->id);

        return $next($request);
    }
}
