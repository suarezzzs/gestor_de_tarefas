<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>TaskFlow - Transforme sua Gestão de TI</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    
    <!-- Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">

    <!-- Styles -->
    @vite('resources/css/app.css')
</head>
<body class="bg-slate-900 text-slate-300 antialiased">
    
    {{-- Background com gradiente animado --}}
    <div class="background-gradient"></div>

    {{-- Navegação --}}
    <header class="fixed top-0 left-0 w-full z-50">
        <nav class="container mx-auto px-6 py-4 flex justify-between items-center">
            <a href="/" class="text-2xl font-bold text-white flex items-center gap-2">
                <i class="fa-solid fa-rocket text-violet-400"></i>
                <span>TaskFlow</span>
            </a>
            <div class="hidden md:flex items-center space-x-8">
                <a href="#home" class="nav-link">Home</a>
                <a href="#features" class="nav-link">Recursos</a>
                <a href="#contact" class="nav-link">Contato</a>
            </div>
            <div class="flex items-center gap-4">
                <a href="{{ route('login') }}" class="text-sm font-medium hover:text-violet-400 transition-colors">Login</a>
                <a href="{{ route('register') }}" class="bg-violet-600 hover:bg-violet-700 text-white text-sm font-medium py-2 px-4 rounded-lg transition-colors">
                    Começar Agora
                </a>
            </div>
        </nav>
    </header>

    {{-- Conteúdo da Página --}}
    <main>
        @yield('content')
    </main>

    {{-- Rodapé --}}
    <footer class="border-t border-slate-800">
        <div class="container mx-auto px-6 py-8 text-center text-slate-500">
            <p>&copy; {{ date('Y') }} TaskFlow. Todos os direitos reservados.</p>
            <p class="text-sm mt-2">Feito com <i class="fa-solid fa-heart text-red-500"></i> e Laravel.</p>
        </div>
    </footer>

</body>
</html>