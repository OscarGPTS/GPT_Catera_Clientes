<?php

namespace App\Http\Controllers\Ejecutivo;

use App\Http\Controllers\Controller;
use App\Services\Ejecutivo\KpisEjecutivoService;
use Illuminate\Http\Request;

class EjecutivoController extends Controller
{
    public function __invoke(Request $request, KpisEjecutivoService $svc)
    {
        $año = (int) $request->input('año', now()->year);

        return view('ejecutivo.index', [
            'año' => $año,
            'kpis' => $svc->snapshot($año),
        ]);
    }
}
