<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CuentaBancaria extends Model
{
    protected $table = 'cuentas_bancarias';

    protected $fillable = ['banco', 'alias', 'numero_cuenta_enmascarado', 'clabe_enmascarada', 'moneda', 'activa'];

    protected $casts = ['activa' => 'boolean'];

    public function estadosCuenta(): HasMany
    {
        return $this->hasMany(EstadoCuenta::class, 'cuenta_id');
    }
}
