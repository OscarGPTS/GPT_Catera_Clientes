<?php

namespace App\Notifications;

use App\Models\Cotizacion;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class CotizacionEmitidaNotification extends Notification
{
    use Queueable;

    public function __construct(public Cotizacion $cotizacion) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        $proyecto = $this->cotizacion->proyecto;

        return [
            'tipo' => 'cotizacion_emitida',
            'proyecto_id' => $proyecto?->id,
            'cotizacion_id' => $this->cotizacion->id,
            'cp_numero' => $proyecto?->cp_numero,
            'version' => $this->cotizacion->version,
            'precio_venta' => (float) $this->cotizacion->precio_venta_final,
            'moneda' => $this->cotizacion->moneda,
            'titulo' => "Cotización v{$this->cotizacion->version} emitida · {$proyecto?->cp_numero}",
            'mensaje' => 'Listo para presentar al cliente. Precio: $'.number_format((float) $this->cotizacion->precio_venta_final, 2)." {$this->cotizacion->moneda}.",
            'url' => $proyecto ? route('cotizaciones.show', [$proyecto, $this->cotizacion]) : null,
        ];
    }
}
