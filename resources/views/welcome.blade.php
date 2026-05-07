<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>{{ config('app.name', 'GPT Services') }}</title>

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700" rel="stylesheet" />

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="bg-slate-50 text-slate-800 antialiased">
        <main class="min-h-screen flex items-center justify-center px-4">
            <div class="max-w-2xl w-full bg-white border border-slate-200 rounded-xl shadow-sm p-10">
                <div class="flex items-center gap-3 mb-6">
                    <span class="inline-flex h-10 w-10 items-center justify-center rounded-lg bg-gpt-600 text-white font-bold">G</span>
                    <h1 class="text-2xl font-semibold text-slate-900">{{ config('app.name', 'GPT Services') }}</h1>
                </div>

                <p class="text-slate-600 leading-relaxed mb-6">
                    Plataforma interna de licitaciones, proyectos, finanzas y vista ejecutiva.
                    Módulo 0 (Fundamentos) listo. La autenticación y los módulos de negocio se incorporan en hitos posteriores.
                </p>

                <dl class="grid grid-cols-2 gap-4 text-sm">
                    <div class="rounded-lg bg-slate-50 border border-slate-200 p-4">
                        <dt class="text-slate-500">Versión Laravel</dt>
                        <dd class="text-slate-900 font-medium">{{ app()->version() }}</dd>
                    </div>
                    <div class="rounded-lg bg-slate-50 border border-slate-200 p-4">
                        <dt class="text-slate-500">Entorno</dt>
                        <dd class="text-slate-900 font-medium">{{ config('app.env') }}</dd>
                    </div>
                </dl>
            </div>
        </main>
    </body>
</html>
