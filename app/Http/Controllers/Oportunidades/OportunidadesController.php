<?php

namespace App\Http\Controllers\Oportunidades;

use App\Http\Controllers\Controller;
use App\Models\Cliente;
use App\Models\Proyecto;
use App\Models\Sublinea;
use App\Models\User;
use App\Notifications\CpAprobadoNotification;
use App\Services\Proyectos\FichaProyectoPdfGenerator;
use App\Services\Proyectos\SecuenciasService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class OportunidadesController extends Controller
{
    public function index(Request $request)
    {
        $query = Proyecto::query()
            ->with(['cliente:id,razon_social,alias_3letras', 'sublinea:id,codigo,nombre', 'gerenteProyectos:id,name'])
            ->when($request->filled('año'), fn ($q) => $q->where('año', $request->integer('año')))
            ->when($request->filled('sublinea'), fn ($q) => $q->whereHas('sublinea', fn ($s) => $s->where('codigo', $request->string('sublinea'))))
            ->when($request->filled('cliente'), fn ($q) => $q->where('cliente_id', $request->integer('cliente')))
            ->when($request->filled('estado'), fn ($q) => $q->where('estado', $request->string('estado')))
            ->when($request->filled('gerente'), fn ($q) => $q->where('gerente_proyectos_id', $request->integer('gerente')))
            ->when($request->filled('q'), fn ($q) => $q->where(function ($w) use ($request) {
                $term = '%'.$request->string('q').'%';
                $w->where('cp_numero', 'like', $term)
                    ->orWhere('dn_numero', 'like', $term)
                    ->orWhere('tech_reference', 'like', $term)
                    ->orWhere('usuario_final', 'like', $term);
            }))
            ->orderByDesc('created_at');

        $proyectos = $query->paginate(20)->withQueryString();

        $stats = [
            'total' => Proyecto::count(),
            'pipeline' => Proyecto::whereIn('estado', ['cotizando', 'cotizado', 'presentado', 'adjudicado_pendiente'])->count(),
            'adjudicados' => Proyecto::whereIn('estado', ['adjudicado_firmado', 'en_ejecucion'])->count(),
            'cerrados' => Proyecto::where('estado', 'cerrado')->count(),
        ];

        return view('oportunidades.index', [
            'proyectos' => $proyectos,
            'stats' => $stats,
            'clientes' => Cliente::where('activo', true)->orderBy('razon_social')->get(['id', 'razon_social', 'alias_3letras']),
            'sublineas' => Sublinea::orderBy('codigo')->get(),
            'gerentes' => User::role('gerente_proyectos')->orderBy('name')->get(['id', 'name']),
            'estados' => $this->estadosLabels(),
        ]);
    }

    public function create()
    {
        return view('oportunidades.create', [
            'clientes' => Cliente::where('activo', true)->orderBy('razon_social')->get(),
            'sublineas' => Sublinea::orderBy('codigo')->get(),
            'directoresDn' => User::role(['director_dn', 'direccion_general'])->orderBy('name')->get(['id', 'name']),
            'sectores' => ['Energía', 'Gobierno', 'Industria', 'Manufactura', 'Otro'],
        ]);
    }

    public function store(Request $request, SecuenciasService $secuencias): RedirectResponse
    {
        $data = $request->validate([
            'cliente_id' => ['required', 'exists:clientes,id'],
            'sublinea_id' => ['required', 'exists:sublineas,id'],
            'usuario_final' => ['nullable', 'string', 'max:255'],
            'sector' => ['nullable', 'string', 'max:100'],
            'resumen_ejecutivo' => ['required', 'string', 'min:20'],
            'fecha_inicio_planeada' => ['nullable', 'date'],
            'fecha_fin_planeada' => ['nullable', 'date', 'after_or_equal:fecha_inicio_planeada'],
            'metodo_distribucion_plurianual' => ['required', 'in:dias_naturales,hitos'],
            'monto_preliminar' => ['nullable', 'numeric', 'min:0'],
            'moneda' => ['required', 'in:USD,MXN,EUR'],
            'director_dn_id' => ['required', 'exists:users,id'],
        ]);

        $año = (int) (now()->year);

        $proyecto = DB::transaction(function () use ($data, $año, $secuencias, $request) {
            $cpNumero = $secuencias->asignarCp($año);

            $proyecto = Proyecto::create($data + [
                'año' => $año,
                'cp_numero' => $cpNumero,
                'estado' => 'en_revision',
            ]);

            $proyecto->recordEvent('cp_asignado', $request->user()->id, [
                'cp_numero' => $cpNumero,
            ]);

            return $proyecto;
        });

        return redirect()
            ->route('oportunidades.show', $proyecto)
            ->with('status', "Oportunidad creada con {$proyecto->cp_numero}.");
    }

    public function show(Proyecto $proyecto)
    {
        $proyecto->load([
            'cliente', 'sublinea',
            'directorDn', 'gerenteProyectos', 'gerenteOperaciones',
            'ingenieroCostos', 'ingenieroProyectos', 'trainee',
            'eventos.user', 'cotizaciones', 'minutaEntrega',
            'koms', 'cronogramas:id,proyecto_id,version,fecha_inicio,fecha_fin',
            'bomBoeItems:id,proyecto_id', 'listadoSuministros', 'solicitudesInternas',
            'libro', 'bitacoras:id,proyecto_id,fecha,firmado_at', 'reportesSemanales:id,proyecto_id,semana_inicio',
            'solicitudesViaticos:id,proyecto_id,status',
            'cartaFiniquito', 'postMortem',
        ]);

        return view('oportunidades.show', [
            'proyecto' => $proyecto,
            'estados' => $this->estadosLabels(),
            'gerentes' => User::role('gerente_proyectos')->orderBy('name')->get(['id', 'name']),
            'ingenieros' => User::role(['ingeniero_costos', 'ingeniero_proyectos', 'trainee_proyectos'])
                ->orderBy('name')
                ->get(['id', 'name'])
                ->groupBy(fn ($u) => $u->getRoleNames()->first() ?? 'otro'),
        ]);
    }

    public function aprobarCp(Request $request, Proyecto $proyecto): RedirectResponse
    {
        $data = $request->validate([
            'gerente_proyectos_id' => ['required', 'exists:users,id'],
            'comentario' => ['nullable', 'string'],
            'decision' => ['required', 'in:aprobar,rechazar'],
        ]);

        $estadoAnterior = $proyecto->estado;

        if ($data['decision'] === 'aprobar') {
            $proyecto->update([
                'estado' => 'cotizando',
                'gerente_proyectos_id' => $data['gerente_proyectos_id'],
            ]);

            $proyecto->recordEvent(
                tipo: 'cp_aprobado',
                userId: $request->user()->id,
                payload: ['gerente_proyectos_id' => $data['gerente_proyectos_id']],
                comentario: $data['comentario'],
                estadoAnterior: $estadoAnterior,
            );

            // M9 · Notificar al gerente de proyectos asignado
            $gerente = User::find($data['gerente_proyectos_id']);
            $gerente?->notify(new CpAprobadoNotification($proyecto->fresh()));

            return back()->with('status', "CP {$proyecto->cp_numero} aprobado y asignado.");
        }

        $proyecto->update(['estado' => 'cancelado']);
        $proyecto->recordEvent(
            tipo: 'cp_rechazado',
            userId: $request->user()->id,
            comentario: $data['comentario'],
            estadoAnterior: $estadoAnterior,
        );

        return back()->with('status', "CP {$proyecto->cp_numero} rechazado por el comité.");
    }

    public function asignarEquipo(Request $request, Proyecto $proyecto): RedirectResponse
    {
        $data = $request->validate([
            'ingeniero_costos_id' => ['nullable', 'exists:users,id'],
            'ingeniero_proyectos_id' => ['nullable', 'exists:users,id'],
            'trainee_id' => ['nullable', 'exists:users,id'],
        ]);

        $proyecto->update($data);

        $proyecto->recordEvent(
            tipo: 'equipo_asignado',
            userId: $request->user()->id,
            payload: array_filter($data),
        );

        return back()->with('status', 'Equipo asignado al CP.');
    }

    public function ficha(Proyecto $proyecto, FichaProyectoPdfGenerator $generator): StreamedResponse
    {
        $path = $generator->generar($proyecto);

        return Storage::disk('local')->download(
            $path,
            'Ficha-'.str_replace(['/', ' '], '-', $proyecto->cp_numero ?? "p{$proyecto->id}").'.pdf',
        );
    }

    /** @return array<string, string> */
    private function estadosLabels(): array
    {
        return [
            'en_revision' => 'En revisión (comité)',
            'cotizando' => 'Cotizando',
            'cotizado' => 'Cotizado',
            'presentado' => 'Presentado al cliente',
            'adjudicado_pendiente' => 'Adjudicado (pendiente firma)',
            'adjudicado_firmado' => 'Adjudicado firmado',
            'en_ejecucion' => 'En ejecución',
            'en_cierre' => 'En cierre',
            'cerrado' => 'Cerrado',
            'cancelado' => 'Cancelado',
            'perdido' => 'Perdido',
            'archivado' => 'Archivado',
        ];
    }
}
