<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Iniciar sesión · {{ config('app.name') }}</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700" rel="stylesheet" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-slate-50 font-sans">
    <main class="min-h-screen flex items-center justify-center px-4">
        <div class="w-full max-w-md">
            <div class="text-center mb-8">
                <span class="inline-flex h-12 w-12 items-center justify-center rounded-lg bg-gpt-600 text-white font-bold text-xl">G</span>
                <h1 class="mt-3 text-xl font-semibold text-slate-900">{{ config('app.name') }}</h1>
                <p class="text-sm text-slate-500">Ingresa con tu cuenta corporativa o externa autorizada.</p>
            </div>

            <div class="bg-white border border-slate-200 rounded-xl shadow-sm p-6 space-y-5">
                @if ($errors->any())
                    <div class="rounded-md border border-rose-200 bg-rose-50 px-3 py-2 text-rose-900 text-sm">
                        @foreach ($errors->all() as $err)<div>{{ $err }}</div>@endforeach
                    </div>
                @endif

                <a href="{{ route('auth.auth0.redirect') }}" class="flex items-center justify-center gap-2 w-full rounded-md bg-gpt-600 hover:bg-gpt-700 text-white font-medium py-2.5">
                    Continuar con Auth0 corporativo
                </a>

                <div class="grid grid-cols-3 gap-2">
                    <a href="{{ route('auth.social.redirect', 'google') }}" class="flex items-center justify-center rounded-md border border-slate-200 hover:bg-slate-50 py-2 text-sm font-medium">Google</a>
                    <a href="{{ route('auth.social.redirect', 'microsoft') }}" class="flex items-center justify-center rounded-md border border-slate-200 hover:bg-slate-50 py-2 text-sm font-medium">Microsoft</a>
                    <a href="{{ route('auth.social.redirect', 'apple') }}" class="flex items-center justify-center rounded-md border border-slate-200 hover:bg-slate-50 py-2 text-sm font-medium">Apple</a>
                </div>

                <div class="relative">
                    <hr class="border-slate-200">
                    <span class="absolute left-1/2 -translate-x-1/2 -top-2.5 bg-white px-2 text-xs text-slate-500 uppercase">o</span>
                </div>

                <form method="POST" action="{{ route('auth.email.store') }}" class="space-y-3">
                    @csrf
                    <div>
                        <label class="block text-xs font-medium text-slate-600 mb-1">Correo</label>
                        <input type="email" name="email" required value="{{ old('email') }}" class="w-full rounded-md border border-slate-200 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-gpt-500/30 focus:border-gpt-500">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-slate-600 mb-1">Contraseña</label>
                        <input type="password" name="password" required class="w-full rounded-md border border-slate-200 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-gpt-500/30 focus:border-gpt-500">
                    </div>
                    <label class="flex items-center gap-2 text-xs text-slate-600">
                        <input type="checkbox" name="remember" class="rounded border-slate-300 text-gpt-600 focus:ring-gpt-500">
                        Recordarme
                    </label>
                    <button type="submit" class="w-full rounded-md bg-slate-900 hover:bg-slate-800 text-white font-medium py-2.5">
                        Entrar con email y contraseña
                    </button>
                </form>
            </div>

            <p class="text-center text-xs text-slate-400 mt-6">© {{ date('Y') }} Tech Energy Control S.A. de C.V.</p>
        </div>
    </main>
</body>
</html>
