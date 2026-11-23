<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'MBG NutriView') - SPPG Ujung Lare Parepare</title>

    <!-- Google Font: Inter -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">

    <!-- CollabFlow TNC Blue Website Palette Design System -->
    <link rel="stylesheet" href="{{ asset('css/style.css') }}?v={{ filemtime(public_path('css/style.css')) }}">

    <!-- Tailwind CSS (Utility Layer) -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Inter', '-apple-system', 'BlinkMacSystemFont', 'Segoe UI', 'sans-serif'],
                    },
                    colors: {
                        palette: {
                            black: '#000000',
                            teal: '#164863',
                            indigo: '#5027EF',
                            gray: '#666666',
                            amber: '#FFBD17',
                            white: '#FFFFFF',
                        }
                    }
                }
            }
        }
    </script>
</head>
<body class="bg-[#F7F6F2] text-[#000000] min-h-screen antialiased font-sans">
    <div class="app-shell">
        @auth
        <!-- Mobile Top Navigation Header (< 1024px) -->
        <header class="mobile-topbar">
            <div class="flex items-center gap-3 min-w-0">
                <button type="button" id="mobile-menu-toggle" class="mobile-menu-btn" aria-label="Buka menu navigasi" aria-expanded="false">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16"/>
                    </svg>
                </button>
                <img src="{{ asset('images/logo-mbg.png') }}" alt="Logo Badan Gizi Nasional" class="w-9 h-9 object-contain shrink-0 rounded-xl bg-[#5027EF]/10 p-1">
                <div class="min-w-0">
                    <div class="font-extrabold text-sm text-[#000000] truncate">MBG NutriView</div>
                    <div class="text-[11px] text-[#666666] truncate">
                        {{ auth()->user()->isAdmin() ? 'Admin SPPG' : 'Ahli Gizi SPPG' }} &middot; Ujung Lare
                    </div>
                </div>
            </div>
            <a href="{{ route('public.index') }}" class="px-3.5 py-1.5 rounded-full bg-[#FFBD17] text-[11px] text-[#000000] font-extrabold shrink-0 hover:brightness-95 transition shadow-sm">
                Portal ↗
            </a>
        </header>

        <!-- Drawer Backdrop (Mobile/Tablet) -->
        <div id="sidebar-backdrop" class="sidebar-backdrop" onclick="toggleSidebar(false)"></div>

        <!-- APP SIDEBAR -->
        <aside id="app-sidebar" class="app-sidebar" aria-label="Menu Utama">
            <div>
                <!-- Header Brand & Seal -->
                <div class="flex items-start justify-between gap-2">
                    <div class="sidebar-brand">
                        <img src="{{ asset('images/logo-mbg.png') }}" alt="Logo Badan Gizi Nasional" class="w-10 h-10 object-contain shrink-0">
                        <div>
                            <div class="brand-title">MBG NutriView</div>
                            <div class="brand-sub">SPPG Ujung Lare</div>
                            <div class="brand-sub">Kota Parepare</div>
                        </div>
                    </div>
                    <button type="button" onclick="toggleSidebar(false)" class="lg:hidden p-1.5 rounded-xl text-white/80 hover:text-white hover:bg-white/10" aria-label="Tutup menu">
                        ✕
                    </button>
                </div>

                <!-- Role Pill (#FFBD17 Golden Pill with #000000 Text) -->
                <div>
                    @if(auth()->user()->isAdmin())
                        <span class="role-pill">
                            <span class="w-2 h-2 rounded-full bg-[#5027EF]"></span>
                            Akun Admin
                        </span>
                    @else
                        <span class="role-pill">
                            <span class="w-2 h-2 rounded-full bg-[#164863]"></span>
                            Akun Ahli Gizi
                        </span>
                    @endif
                </div>

                <!-- Nav Links -->
                <nav class="sidebar-nav">
                    @if(auth()->user()->isAdmin())
                        <a href="{{ route('admin.dashboard') }}" class="nav-item {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">
                            Dashboard
                        </a>
                        <a href="{{ route('admin.menu.index') }}" class="nav-item {{ request()->routeIs('admin.menu.*') ? 'active' : '' }}">
                            Data Menu
                        </a>
                        <a href="{{ route('admin.bahan.index') }}" class="nav-item {{ request()->routeIs('admin.bahan.*') ? 'active' : '' }}">
                            Bahan Baku
                        </a>
                        <a href="{{ route('admin.penerima.index') }}" class="nav-item {{ request()->routeIs('admin.penerima.*') ? 'active' : '' }}">
                            Penerima dan Porsi
                        </a>
                        <a href="{{ route('admin.jadwal.index') }}" class="nav-item {{ request()->routeIs('admin.jadwal.*') ? 'active' : '' }}">
                            Jadwal Produksi
                        </a>
                        <a href="{{ route('admin.tahap.index') }}" class="nav-item {{ request()->routeIs('admin.tahap.*') ? 'active' : '' }}">
                            Pencatatan Tahap
                        </a>
                        <a href="{{ route('admin.kpi.index') }}" class="nav-item {{ request()->routeIs('admin.kpi.*') ? 'active' : '' }}">
                            KPI dan Prediksi Risiko
                        </a>
                    @else
                        <a href="{{ route('gizi.dashboard') }}" class="nav-item {{ request()->routeIs('gizi.dashboard') ? 'active' : '' }}">
                            Dashboard / Data Gizi
                        </a>
                        <a href="{{ route('gizi.hitung.validasi') }}" class="nav-item {{ request()->routeIs('gizi.hitung.validasi') ? 'active' : '' }}">
                            Hitung &amp; Validasi
                        </a>
                        <a href="{{ route('gizi.menu.lihat') }}" class="nav-item {{ request()->routeIs('gizi.menu.lihat') ? 'active' : '' }}">
                            Menu dan Bahan (lihat)
                        </a>
                        <a href="{{ route('gizi.kpi.lihat') }}" class="nav-item {{ request()->routeIs('gizi.kpi.lihat') ? 'active' : '' }}">
                            KPI dan Prediksi Risiko
                        </a>
                    @endif
                </nav>
            </div>

            <!-- Sidebar Footer -->
            <div class="sidebar-footer">
                <div class="access-box">
                    <strong>Hak akses</strong>
                    @if(auth()->user()->isAdmin())
                        Kelola operasional. Validasi dan publikasi oleh Ahli Gizi.
                    @else
                        Kelola gizi, validasi dan publikasi. Operasional hanya baca.
                    @endif
                </div>

                <div class="pt-1 space-y-2.5">
                    <a href="{{ route('public.index') }}" class="footer-link">
                        <span>Portal publik</span>
                        <span>↗</span>
                    </a>

                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="text-xs text-white/80 hover:text-[#FFBD17] font-bold transition cursor-pointer bg-transparent border-0 p-0 flex items-center gap-1.5">
                            <span>Keluar dari akun</span>
                        </button>
                    </form>
                </div>
            </div>
        </aside>
        @endauth

        <!-- MAIN CONTENT -->
        <main class="main-content">
            <div class="main-inner">
                @if(session('success'))
                    <div class="alert-box alert-success flex items-start justify-between gap-3 mb-5">
                        <div class="flex items-start gap-2">
                            <span class="font-extrabold shrink-0">✓ Sukses:</span>
                            <span>{{ session('success') }}</span>
                        </div>
                        <button type="button" onclick="this.parentElement.remove()" class="opacity-70 hover:opacity-100 text-sm font-bold shrink-0" aria-label="Tutup">&times;</button>
                    </div>
                @endif

                @if(session('error'))
                    <div class="alert-box alert-danger flex items-start justify-between gap-3 mb-5">
                        <div class="flex items-start gap-2">
                            <span class="font-extrabold shrink-0">⚠️ Gagal:</span>
                            <span>{{ session('error') }}</span>
                        </div>
                        <button type="button" onclick="this.parentElement.remove()" class="opacity-70 hover:opacity-100 text-sm font-bold shrink-0" aria-label="Tutup">&times;</button>
                    </div>
                @endif

                @if(session('warning'))
                    <div class="alert-box alert-warning flex items-start justify-between gap-3 mb-5">
                        <div class="flex items-start gap-2">
                            <span class="font-extrabold shrink-0">⚡ Peringatan:</span>
                            <span>{{ session('warning') }}</span>
                        </div>
                        <button type="button" onclick="this.parentElement.remove()" class="opacity-70 hover:opacity-100 text-sm font-bold shrink-0" aria-label="Tutup">&times;</button>
                    </div>
                @endif

                @if($errors->any())
                    <div class="alert-box alert-danger mb-5">
                        <div class="font-extrabold mb-1">⚠️ Periksa isian berikut:</div>
                        <ul class="list-disc list-inside text-xs space-y-0.5">
                            @foreach($errors->all() as $err)
                                <li>{{ $err }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                @yield('content')
            </div>
        </main>
    </div>

    <script>
        function toggleSidebar(forceState) {
            const sidebar = document.getElementById('app-sidebar');
            const backdrop = document.getElementById('sidebar-backdrop');
            const btn = document.getElementById('mobile-menu-toggle');
            if (!sidebar || !backdrop) return;

            const isOpen = typeof forceState === 'boolean' ? forceState : !sidebar.classList.contains('open');
            sidebar.classList.toggle('open', isOpen);
            backdrop.classList.toggle('open', isOpen);
            if (btn) btn.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
            document.body.style.overflow = isOpen ? 'hidden' : '';
        }

        document.addEventListener('DOMContentLoaded', () => {
            const btn = document.getElementById('mobile-menu-toggle');
            if (btn) btn.addEventListener('click', () => toggleSidebar());
            document.addEventListener('keydown', (e) => {
                if (e.key === 'Escape') toggleSidebar(false);
            });
        });
    </script>

    @stack('scripts')
</body>
</html>
