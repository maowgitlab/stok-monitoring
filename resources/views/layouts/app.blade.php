<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ config('app.name', 'Monitoring Stok') }}</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Google Fonts: Poppins -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">
    
    <!-- Custom CSS -->
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            min-height: 100vh;
            margin: 0;
            overflow-x: hidden;
            color: #2c3e50;
            transition: background 0.3s ease, color 0.3s ease;
        }

        .navbar {
            background: linear-gradient(90deg, #2c3e50, #34495e);
            box-shadow: 0 4px 12px rgba(0,0,0,0.2);
            position: sticky;
            top: 0;
            z-index: 1100;
        }

        .navbar-brand, .nav-link {
            color: #ecf0f1 !important;
            transition: all 0.3s ease;
        }

        .nav-link:hover {
            color: #3498db !important;
        }

        .nav-link.active {
            background: #3498db;
            border-radius: 5px;
        }

        .sidebar {
            background: #2c3e50;
            height: 100%;
            max-height: 100vh;
            padding: 30px 20px;
            box-shadow: 2px 0 10px rgba(0,0,0,0.3);
            position: fixed;
            top: 0;
            left: -250px;
            width: 250px;
            transition: all 0.3s ease;
            z-index: 1000;
            overflow-y: auto;
        }

        .sidebar.show {
            left: 0;
        }

        .sidebar .nav-link {
            color: #bdc3c7;
            padding: 12px 20px;
            margin: 5px 0;
            border-radius: 8px;
            transition: all 0.3s ease;
        }

        .sidebar .nav-link:hover {
            background: #34495e;
            color: #fff;
            transform: translateX(5px);
        }

        .sidebar .nav-link.active {
            background: #3498db;
            color: #fff;
        }

        .sidebar .sidebar-heading {
            color: #ecf0f1;
            font-size: 1.1rem;
            margin-top: 2rem;
        }

        .content-wrapper {
            padding: 2rem;
            margin-left: 0;
            transition: margin-left 0.3s ease;
            min-height: calc(100vh - 56px - 3rem);
            background: transparent;
            color: #2c3e50;
        }

        .content-wrapper .container-fluid {
            background: transparent;
        }

        .alert {
            border-radius: 10px;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
            animation: fadeIn 0.5s ease;
        }

        .bg-footer {
            background: #2c3e50;
            color: #ecf0f1;
            padding: 1.5rem 0;
            box-shadow: 0 -4px 12px rgba(0,0,0,0.2);
        }

        .overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            z-index: 900;
            display: none;
        }

        .overlay.show {
            display: block;
        }

        .card {
            background: #ffffff;
        }

        .sidebar::-webkit-scrollbar {
            width: 8px;
        }

        .sidebar::-webkit-scrollbar-track {
            background: #34495e;
        }

        .sidebar::-webkit-scrollbar-thumb {
            background: #3498db;
            border-radius: 4px;
        }

        .sidebar::-webkit-scrollbar-thumb:hover {
            background: #2980b9;
        }

        /* Responsive Adjustments */
        @media (min-width: 768px) {
            .sidebar {
                left: 0;
                top: 56px;
                max-height: calc(100vh - 56px);
            }
            .content-wrapper {
                margin-left: 250px;
            }
            .navbar .navbar-nav {
                display: flex !important;
            }
            .navbar-toggler {
                display: none;
            }
        }

        @media (max-width: 767px) {
            .navbar .navbar-toggler {
                display: block;
            }
            .content-wrapper {
                padding: 1rem;
            }
            .navbar .navbar-nav {
                display: none;
            }
            .sidebar {
                top: 56px;
                height: calc(100vh - 56px);
                max-height: calc(100vh - 56px);
            }
        }

        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
    </style>
    
    @stack('styles')
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container-fluid">
            <button class="navbar-toggler" type="button" aria-controls="sidebarMenu" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <a class="navbar-brand ms-2" href="{{ route('dashboard') }}">
                <i class="fas fa-boxes me-2"></i> Monitoring Stok
            </a>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}" href="{{ route('dashboard') }}">Dashboard</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('items.*') ? 'active' : '' }}" href="{{ route('items.index') }}">Daftar Item</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('imports.*') ? 'active' : '' }}" href="{{ route('imports.create') }}">Import Data</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Overlay untuk mobile -->
    <div class="overlay" id="overlay"></div>

    <!-- Sidebar -->
    <div class="sidebar" id="sidebarMenu">
        <ul class="nav flex-column">
            <li class="nav-item">
                <a class="nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}" href="{{ route('dashboard') }}">
                    <i class="fas fa-tachometer-alt"></i> Dashboard
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link {{ request()->routeIs('items.index') || request()->routeIs('items.archived') ? 'active' : '' }}" href="{{ route('items.index') }}">
                    <i class="fas fa-list"></i> Semua Item
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link {{ request()->routeIs('imports.create') ? 'active' : '' }}" href="{{ route('imports.create') }}">
                    <i class="fas fa-file-import"></i> Import Data
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link {{ request()->routeIs('stocks.paste') ? 'active' : '' }}" href="{{ route('stocks.paste') }}">
                    <i class="fas fa-file"></i> Stok In & Out
                </a>
            </li>
        </ul>
        <h6 class="sidebar-heading">Laporan</h6>
        <ul class="nav flex-column">
            <li class="nav-item">
                <a class="nav-link {{ request()->routeIs('stock-analysis.index') ? 'active' : '' }}" href="{{ route('stock-analysis.index') }}">
                    <i class="fas fa-chart-line"></i> Analisis Stok
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link {{ request()->routeIs('low-stock.index') || request()->routeIs('low-stock.whatsapp-report') ? 'active' : '' }}" href="{{ route('low-stock.index') }}">
                    <i class="fas fa-exclamation-triangle"></i> Stok Menipis
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link {{ request()->routeIs('items.whatsapp-report') ? 'active' : '' }}" href="{{ route('items.whatsapp-report') }}">
                    <i class="fas fa-paste"></i> Laporan WA
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link {{ request()->routeIs('stocks.compare') ? 'active' : '' }}" href="{{ route('stocks.compare') }}">
                    <i class="fas fa-paste"></i> Compare Stok
                </a>
            </li>
        </ul>
        <h6 class="sidebar-heading">Pengaturan</h6>
        <ul class="nav flex-column">
            <li class="nav-item">
                <a class="nav-link {{ request()->routeIs('settings.index') ? 'active' : '' }}" href="{{ route('settings.index') }}">
                    <i class="fas fa-cog"></i> Pengaturan
                </a>
            </li>
        </ul>
    </div>

    <!-- Main Content -->
    <main class="content-wrapper">
        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show mt-3" role="alert">
                {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif
        @if(session('error'))
            <div class="alert alert-danger alert-dismissible fade show mt-3" role="alert">
                {{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif
        @yield('content')
    </main>

    <!-- Footer -->
    <footer class="bg-footer text-center">
        <p class="mb-0">© {{ date('Y') }} Monitoring Stok. All rights reserved.</p>
    </footer>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Sidebar toggle
        const sidebar = document.getElementById('sidebarMenu');
        const overlay = document.getElementById('overlay');
        const toggler = document.querySelector('.navbar-toggler');

        toggler.addEventListener('click', function () {
            sidebar.classList.toggle('show');
            overlay.classList.toggle('show');
        });

        overlay.addEventListener('click', function () {
            sidebar.classList.remove('show');
            overlay.classList.remove('show');
        });

        window.addEventListener('resize', function () {
            if (window.innerWidth >= 768) {
                sidebar.classList.remove('show');
                overlay.classList.remove('show');
            }
        });
    </script>
    @stack('scripts')
</body>
</html>