<?php

namespace App\Services\Chat;

use App\Models\ChatCanal;
use App\Models\ChatLectura;
use App\Models\ChatMensaje;
use App\Models\Proyecto;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ChatService
{
    /**
     * Obtiene o crea el canal del proyecto. Por defecto agrega como miembros al
     * equipo del proyecto (director_dn, gp, go, ic, ip, trainee).
     */
    public function canalParaProyecto(Proyecto $proyecto): ChatCanal
    {
        $canal = ChatCanal::where('tipo', 'proyecto')
            ->where('contexto_id', $proyecto->id)
            ->first();

        if ($canal) {
            return $canal;
        }

        return DB::transaction(function () use ($proyecto) {
            $canal = ChatCanal::create([
                'tipo' => 'proyecto',
                'contexto_id' => $proyecto->id,
                'nombre' => '#'.($proyecto->cp_numero ?? "p-{$proyecto->id}"),
                'descripcion' => $proyecto->resumen_ejecutivo,
                'creado_por_id' => $proyecto->gerente_proyectos_id ?? $proyecto->director_dn_id,
            ]);

            $miembrosIds = collect([
                $proyecto->director_dn_id,
                $proyecto->gerente_proyectos_id,
                $proyecto->gerente_operaciones_id,
                $proyecto->ingeniero_costos_id,
                $proyecto->ingeniero_proyectos_id,
                $proyecto->trainee_id,
            ])->filter()->unique()->values();

            foreach ($miembrosIds as $userId) {
                $canal->miembros()->attach($userId, ['rol_en_canal' => 'miembro', 'joined_at' => now()]);
            }

            return $canal->fresh();
        });
    }

    public function enviarMensaje(ChatCanal $canal, int $userId, string $contenido, ?int $parentId = null): ChatMensaje
    {
        if (! $canal->miembros()->where('user_id', $userId)->exists()) {
            $canal->miembros()->attach($userId, ['rol_en_canal' => 'miembro', 'joined_at' => now()]);
        }

        return DB::transaction(function () use ($canal, $userId, $contenido, $parentId) {
            $mensaje = $canal->mensajes()->create([
                'user_id' => $userId,
                'parent_message_id' => $parentId,
                'contenido' => $contenido,
            ]);

            // Detectar @menciones por nombre o email (heurística simple)
            foreach ($this->extraerMenciones($contenido, $canal) as $usuarioMencionado) {
                if ($usuarioMencionado->id === $userId) {
                    continue;
                }
                $mensaje->menciones()->create([
                    'user_id' => $usuarioMencionado->id,
                ]);
            }

            // Auto-leído para el autor
            ChatLectura::updateOrCreate(
                ['canal_id' => $canal->id, 'user_id' => $userId],
                ['ultimo_mensaje_leido_id' => $mensaje->id],
            );

            return $mensaje->fresh('user', 'menciones.user');
        });
    }

    public function marcarLeido(ChatCanal $canal, int $userId): void
    {
        $ultimoMensaje = $canal->mensajes()->orderByDesc('id')->first();
        if (! $ultimoMensaje) {
            return;
        }

        ChatLectura::updateOrCreate(
            ['canal_id' => $canal->id, 'user_id' => $userId],
            ['ultimo_mensaje_leido_id' => $ultimoMensaje->id],
        );
    }

    public function canalesParaUsuario(int $userId): Collection
    {
        return ChatCanal::whereHas('miembros', fn ($q) => $q->where('user_id', $userId))
            ->withCount(['mensajes as ultimo_mensaje_id' => fn ($q) => $q->select(DB::raw('max(id)'))])
            ->orderByDesc('ultimo_mensaje_id')
            ->get();
    }

    /**
     * Extrae usuarios mencionados con @nombre.apellido o @email del contenido.
     * Búsqueda case-insensitive. Solo considera miembros del canal.
     *
     * @return Collection<int, User>
     */
    private function extraerMenciones(string $contenido, ChatCanal $canal): Collection
    {
        if (! preg_match_all('/@([a-zA-Z0-9._-]+)/u', $contenido, $matches)) {
            return collect();
        }

        $tokens = collect($matches[1])->unique()->values();
        if ($tokens->isEmpty()) {
            return collect();
        }

        // Buscar entre miembros del canal por email exacto o por nombre (substring) o por parte local del email.
        $miembros = $canal->miembros()->get();

        return $tokens->map(function ($token) use ($miembros) {
            $lower = mb_strtolower($token);

            return $miembros->first(function (User $u) use ($lower) {
                if (mb_strtolower($u->email) === $lower) {
                    return true;
                }
                $localPart = mb_strtolower(strstr($u->email, '@', true) ?: '');
                if ($localPart === $lower) {
                    return true;
                }
                $nombreNormalizado = mb_strtolower(str_replace(' ', '.', $u->name));

                return str_contains($nombreNormalizado, $lower);
            });
        })->filter()->unique('id')->values();
    }
}
