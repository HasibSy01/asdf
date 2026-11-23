<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MBG NutriView - LOGIN</title>
    <!-- Google Font: Inter -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <!-- CollabFlow TNC Blue Website Palette Design System -->
    <link rel="stylesheet" href="{{ asset('css/style.css') }}">
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="p-4 sm:p-8 min-h-screen flex flex-col justify-between bg-[#F7F6F2] text-[#000000]">

    <!-- Center Split / Floating Login Card -->
    <div class="flex-1 flex items-center justify-center py-4 sm:py-8">
        <div class="w-full max-w-[450px] bg-[#FFFFFF] border border-[#164863]/12 rounded-[28px] overflow-hidden shadow-[0_24px_60px_-15px_rgba(80,39,239,0.18)]">
            <!-- Top Brand Header Banner (#5027EF to #164863) -->
            <div class="bg-gradient-to-br from-[#5027EF] via-[#3F1CD6] to-[#164863] p-6 sm:p-8 text-center text-[#FFFFFF] relative">
                <div class="w-14 h-14 rounded-2xl bg-white/15 backdrop-blur-md p-2 mx-auto mb-3 flex items-center justify-center shadow-lg">
                    <img src="{{ asset('images/logo-mbg.png') }}" alt="Logo Badan Gizi Nasional" class="w-full h-full object-contain">
                </div>
                <span class="inline-block px-3 py-0.5 rounded-full bg-[#FFBD17] text-[#000000] text-[10px] font-extrabold uppercase tracking-wider mb-1.5">
                    Portal Internal SPPG
                </span>
                <div class="text-xl sm:text-2xl font-black tracking-tight text-[#FFFFFF]">MBG NutriView</div>
                <p class="text-xs text-white/80 mt-0.5">Satuan Pelayanan Pemenuhan Gizi Ujung Lare</p>
            </div>

            <div class="p-6 sm:p-8">
                <!-- Error Notification -->
                @if(session('error'))
                    <div class="mb-5 p-3.5 bg-rose-50 border border-rose-300 text-rose-800 text-xs font-semibold rounded-2xl flex items-start gap-2">
                        <span class="shrink-0">⚠️</span>
                        <span>{{ session('error') }}</span>
                    </div>
                @endif

                @if(session('info'))
                    <div class="mb-5 p-3.5 bg-[#5027EF]/10 border border-[#5027EF]/25 text-[#5027EF] text-xs font-semibold rounded-2xl flex items-start gap-2">
                        <span class="shrink-0">ℹ️</span>
                        <span>{{ session('info') }}</span>
                    </div>
                @endif

                <!-- Form -->
                <form method="POST" action="{{ route('login.process') }}" class="space-y-4">
                    @csrf
                    <div>
                        <label for="username" class="block text-xs font-bold text-[#000000] mb-1.5">Username</label>
                        <input id="username" type="text" name="username" value="{{ old('username') }}" required autofocus
                               placeholder="Masukkan username petugas"
                               class="w-full px-4 py-3 rounded-xl bg-[#F7F6F2] border border-[#164863]/20 text-[#000000] text-sm font-medium placeholder-[#666666]/60 focus:outline-none focus:bg-[#FFFFFF] focus:border-[#5027EF] transition">
                    </div>

                    <div>
                        <label for="password" class="block text-xs font-bold text-[#000000] mb-1.5">Kata sandi</label>
                        <div class="relative">
                            <input id="password" type="password" name="password" required
                                   placeholder="••••••••••••"
                                   class="w-full px-4 py-3 pr-14 rounded-xl bg-[#F7F6F2] border border-[#164863]/20 text-[#000000] text-sm font-medium placeholder-[#666666]/60 focus:outline-none focus:bg-[#FFFFFF] focus:border-[#5027EF] transition">
                            <button type="button" onclick="togglePassword()" aria-label="Tampilkan atau sembunyikan kata sandi"
                                    class="absolute inset-y-0 right-0 px-3.5 text-xs font-bold text-[#5027EF] hover:text-[#164863] transition cursor-pointer">
                                <span id="eye-label">Lihat</span>
                            </button>
                        </div>
                    </div>

                    <div class="flex justify-end pt-0.5">
                        <button type="button" onclick="document.getElementById('reset-hint').classList.toggle('hidden')"
                                class="text-xs font-semibold text-[#666666] underline hover:text-[#5027EF] bg-transparent border-0 cursor-pointer p-0">
                            Lupa kata sandi?
                        </button>
                    </div>

                    <div id="reset-hint" class="hidden p-3 rounded-xl bg-[#F7F6F2] border border-[#164863]/15 text-[11px] text-[#666666]">
                        Untuk keamanan akun SPPG, hubungi Koordinator Admin SPPG Ujung Lare untuk pengaturan ulang kata sandi.
                    </div>

                    <div class="pt-1">
                        <button type="submit"
                                class="w-full py-3.5 bg-[#5027EF] hover:bg-[#3F19D6] active:scale-[0.99] text-[#FFFFFF] font-extrabold text-sm rounded-full shadow-lg shadow-[#5027EF]/35 transition cursor-pointer">
                            Masuk ke Sistem
                        </button>
                    </div>
                </form>

                <div class="mt-5 text-center space-y-2">
                    <div class="text-xs text-[#666666]">Akses mengikuti peran akun</div>
                    <div>
                        <a href="{{ route('public.index') }}" class="inline-flex items-center gap-1.5 px-4 py-1.5 rounded-full bg-[#FFBD17]/30 hover:bg-[#FFBD17] text-xs text-[#000000] font-bold transition">
                            <span>Buka portal publik tanpa login</span>
                            <span>↗</span>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bottom Left Official Seal & Brand -->
    <div class="max-w-[450px] sm:max-w-none w-full mx-auto flex items-center justify-center sm:justify-start gap-3 pt-4 border-t border-[#164863]/12">
        <img src="{{ asset('images/logo-mbg.png') }}" alt="Logo Badan Gizi Nasional" class="w-8 h-8 object-contain">
        <div class="font-black text-xs sm:text-sm tracking-widest text-[#164863] uppercase">
            BADAN GIZI NASIONAL
        </div>
    </div>

    <script>
        function togglePassword() {
            const input = document.getElementById('password');
            const label = document.getElementById('eye-label');
            if (!input || !label) return;
            const show = input.type === 'password';
            input.type = show ? 'text' : 'password';
            label.textContent = show ? 'Tutup' : 'Lihat';
        }
    </script>
</body>
</html>
