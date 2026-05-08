<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Proyecto extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'tech_reference', 'cp_numero', 'dn_numero', 'año',
        'cliente_id', 'sublinea_id', 'usuario_final', 'sector',
        'estado', 'resumen_ejecutivo',
        'fecha_inicio_planeada', 'fecha_fin_planeada',
        'metodo_distribucion_plurianual',
        'monto_preliminar', 'moneda',
        'director_dn_id', 'gerente_proyectos_id', 'gerente_operaciones_id',
        'ingeniero_costos_id', 'ingeniero_proyectos_id', 'trainee_id',
        'notas',
    ];

    protected $casts = [
        'año' => 'integer',
        'fecha_inicio_planeada' => 'date',
        'fecha_fin_planeada' => 'date',
        'monto_preliminar' => 'decimal:2',
    ];

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class);
    }

    public function sublinea(): BelongsTo
    {
        return $this->belongsTo(Sublinea::class);
    }

    public function eventos(): HasMany
    {
        return $this->hasMany(ProyectoEvento::class)->latest();
    }

    public function cotizaciones(): HasMany
    {
        return $this->hasMany(Cotizacion::class)->orderByDesc('version');
    }

    public function cotizacionVigente()
    {
        return $this->cotizaciones()->whereIn('status', ['emitida', 'interno_aprobado', 'borrador'])->orderByDesc('version')->first();
    }

    public function minutaEntrega(): HasOne
    {
        return $this->hasOne(MinutaEntrega::class);
    }

    public function libro(): HasOne
    {
        return $this->hasOne(LibroProyecto::class);
    }

    public function koms(): HasMany
    {
        return $this->hasMany(KickOffMeeting::class)->orderByDesc('fecha');
    }

    public function cronogramas(): HasMany
    {
        return $this->hasMany(Cronograma::class)->orderByDesc('version');
    }

    public function cronogramaVigente()
    {
        return $this->cronogramas()->orderByDesc('version')->first();
    }

    public function directorDn(): BelongsTo
    {
        return $this->belongsTo(User::class, 'director_dn_id');
    }

    public function gerenteProyectos(): BelongsTo
    {
        return $this->belongsTo(User::class, 'gerente_proyectos_id');
    }

    public function gerenteOperaciones(): BelongsTo
    {
        return $this->belongsTo(User::class, 'gerente_operaciones_id');
    }

    public function ingenieroCostos(): BelongsTo
    {
        return $this->belongsTo(User::class, 'ingeniero_costos_id');
    }

    public function ingenieroProyectos(): BelongsTo
    {
        return $this->belongsTo(User::class, 'ingeniero_proyectos_id');
    }

    public function trainee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'trainee_id');
    }

    public function recordEvent(string $tipo, ?int $userId = null, array $payload = [], ?string $comentario = null, ?string $estadoAnterior = null): ProyectoEvento
    {
        return $this->eventos()->create([
            'user_id' => $userId,
            'tipo' => $tipo,
            'estado_anterior' => $estadoAnterior,
            'estado_nuevo' => $this->estado,
            'payload' => $payload ?: null,
            'comentario' => $comentario,
        ]);
    }
}
