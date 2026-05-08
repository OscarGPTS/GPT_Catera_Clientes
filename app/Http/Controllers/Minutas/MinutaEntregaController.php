<?php

namespace App\Http\Controllers\Minutas;

use App\Http\Controllers\Controller;
use App\Models\Proyecto;
use App\Models\User;
use App\Services\Minutas\MinutaEntregaPdfGenerator;
use App\Services\Minutas\MinutaEntregaService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Symfony\Component\HttpFoundation\StreamedResponse;

class MinutaEntregaController extends Controller
{
    public function show(Proyecto $proyecto, MinutaEntregaService $service): View|RedirectResponse
    {
        if (! in_array($proyecto->estado, ['adjudicado_pendiente', 'adjudicado_firmado', 'en_ejecucion', 'en_cierre', 'cerrado'], true)) {
            return redirect()->route('oportunidades.show', $proyecto)
                ->withErrors(['minuta' => 'La minuta CP→DN se levanta tras la adjudicación.']);
        }

        $minuta = $proyecto->minutaEntrega ?? $service->crearOSeleccionar($proyecto, request()->user()->id);
        $minuta->load(['participantes.user']);

        $proyecto->load(['cliente', 'sublinea', 'directorDn', 'gerenteProyectos', 'gerenteOperaciones', 'ingenieroProyectos']);

        $candidatos = User::where(function ($q) {
            $q->whereHas('roles', fn ($r) => $r->whereIn('name', [
                'direccion_general', 'director_dn', 'gerente_proyectos',
                'gerente_operaciones', 'ingeniero_proyectos', 'ingeniero_costos',
            ]));
        })->orderBy('name')->get(['id', 'name']);

        return view('minutas.show', [
            'proyecto' => $proyecto,
            'minuta' => $minuta,
            'candidatos' => $candidatos,
        ]);
    }

    public function update(Request $request, Proyecto $proyecto, MinutaEntregaService $service): RedirectResponse
    {
        $minuta = $proyecto->minutaEntrega;
        abort_if(! $minuta, 404);

        $data = $request->validate([
            'fecha_reunion' => ['required', 'date'],
            'hora_inicio' => ['nullable', 'date_format:H:i'],
            'hora_fin' => ['nullable', 'date_format:H:i', 'after_or_equal:hora_inicio'],
            'modalidad' => ['required', 'in:presencial,virtual,mixta'],
            'orden_del_dia' => ['nullable', 'array'],
            'orden_del_dia.*' => ['nullable', 'string', 'max:500'],
            'acuerdos' => ['nullable', 'array'],
            'acuerdos.*.texto' => ['nullable', 'string', 'max:500'],
            'acuerdos.*.responsable' => ['nullable', 'string', 'max:120'],
            'acuerdos.*.fecha' => ['nullable', 'date'],
        ]);

        // Limpiar entries vacíos
        $data['orden_del_dia'] = array_values(array_filter($data['orden_del_dia'] ?? [], fn ($t) => filled($t)));
        $data['acuerdos'] = array_values(array_filter($data['acuerdos'] ?? [], fn ($a) => filled($a['texto'] ?? null)));

        try {
            $service->actualizar($minuta, $data);
        } catch (RuntimeException $e) {
            return back()->withErrors(['minuta' => $e->getMessage()]);
        }

        return back()->with('status', 'Minuta actualizada.');
    }

    public function addParticipante(Request $request, Proyecto $proyecto, MinutaEntregaService $service): RedirectResponse
    {
        $minuta = $proyecto->minutaEntrega;
        abort_if(! $minuta, 404);

        $data = $request->validate([
            'user_id' => ['required', 'exists:users,id'],
            'rol_en_minuta' => ['nullable', 'string', 'max:120'],
        ]);

        try {
            $service->agregarParticipante($minuta, $data['user_id'], $data['rol_en_minuta'] ?? null);
        } catch (RuntimeException $e) {
            return back()->withErrors(['minuta' => $e->getMessage()]);
        }

        return back()->with('status', 'Participante agregado.');
    }

    public function removeParticipante(Proyecto $proyecto, int $userId, MinutaEntregaService $service): RedirectResponse
    {
        $minuta = $proyecto->minutaEntrega;
        abort_if(! $minuta, 404);

        try {
            $service->quitarParticipante($minuta, $userId);
        } catch (RuntimeException $e) {
            return back()->withErrors(['minuta' => $e->getMessage()]);
        }

        return back()->with('status', 'Participante removido.');
    }

    public function firmar(Request $request, Proyecto $proyecto, MinutaEntregaService $service, MinutaEntregaPdfGenerator $pdf): RedirectResponse
    {
        $minuta = $proyecto->minutaEntrega;
        abort_if(! $minuta, 404);

        try {
            $minuta = $service->firmarPorUsuario($minuta, $request->user()->id);
        } catch (RuntimeException $e) {
            return back()->withErrors(['firma' => $e->getMessage()]);
        }

        if ($minuta->status === 'firmada') {
            $pdf->generar($minuta);

            return back()->with('status', 'Minuta firmada por todos los participantes. PDF generado.');
        }

        return back()->with('status', 'Tu firma fue registrada. Faltan otros participantes.');
    }

    public function pdf(Proyecto $proyecto, MinutaEntregaPdfGenerator $generator): StreamedResponse
    {
        $minuta = $proyecto->minutaEntrega;
        abort_if(! $minuta, 404);

        if (! $minuta->pdf_path || ! Storage::disk('local')->exists($minuta->pdf_path)) {
            $generator->generar($minuta);
            $minuta->refresh();
        }

        return Storage::disk('local')->download(
            $minuta->pdf_path,
            "MinutaEntrega-{$proyecto->cp_numero}.pdf",
        );
    }
}
