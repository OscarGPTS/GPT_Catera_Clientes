<?php

namespace App\Services\Finanzas;

use App\Models\FinanzasAudit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Request;

/**
 * Auditoría de acciones financieras sensibles. Registra usuario, IP, payload.
 */
class FinanzasAuditService
{
    public function registrar(string $action, ?Model $subject = null, array $payload = [], ?int $userId = null): FinanzasAudit
    {
        return FinanzasAudit::create([
            'user_id' => $userId ?? auth()->id(),
            'action' => $action,
            'subject_type' => $subject ? $subject::class : null,
            'subject_id' => $subject?->getKey(),
            'payload' => $payload ?: null,
            'ip' => Request::ip(),
            'user_agent' => Request::userAgent(),
        ]);
    }
}
