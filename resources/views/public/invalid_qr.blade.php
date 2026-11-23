<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kode QR Tidak Valid - MBG NutriView</title>
    <!-- Google Font: Inter -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <!-- CollabFlow TNC Blue Website Palette Design System -->
    <link rel="stylesheet" href="{{ asset('css/style.css') }}">
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="min-h-screen flex flex-col justify-between items-center p-4 sm:p-6 bg-[#F7F6F2] text-[#000000]">
    <div class="w-full max-w-md my-auto space-y-5">
        <div class="flex items-center justify-center gap-3">
            <div class="w-10 h-10 rounded-2xl bg-[#5027EF] p-1.5 flex items-center justify-center shadow-md shadow-[#5027EF]/30">
                <img src="{{ asset('images/logo-mbg.png') }}" alt="Logo Badan Gizi Nasional" class="w-full h-full object-contain">
            </div>
            <div class="text-left">
                <div class="font-black text-sm text-[#000000]">MBG NutriView</div>
                <div class="text-[11px] text-[#666666]">SPPG Ujung Lare Parepare</div>
            </div>
        </div>
        <div class="bg-[#FFFFFF] border border-[#164863]/12 rounded-[28px] p-7 sm:p-9 text-center space-y-5 shadow-[0_20px_50px_-15px_rgba(22,72,99,0.14)]">
            <div class="w-16 h-16 rounded-2xl bg-rose-50 border border-rose-300 text-rose-600 text-3xl font-bold flex items-center justify-center mx-auto">
                ✕
            </div>

            <div>
                <h1 class="text-xl font-black text-[#000000]">Kode QR Tidak Dikenali</h1>
                <p class="text-xs font-semibold text-rose-700 mt-2 leading-relaxed">
                    {{ $message ?? 'Kode QR tidak dikenali atau tautan portal tidak valid.' }}
                </p>
                <p class="text-xs text-[#666666] mt-2 leading-relaxed">
                    Pastikan Anda memindai kode QR resmi yang tertera pada kotak makanan atau lembar distribusi SPPG Ujung Lare.
                </p>
            </div>

            <div class="pt-2">
                <a href="{{ route('public.index') }}" class="inline-block px-6 py-3 bg-[#5027EF] hover:bg-[#3F19D6] text-[#FFFFFF] font-extrabold text-xs rounded-full shadow-lg shadow-[#5027EF]/30 transition">
                    Buka Beranda Portal Publik
                </a>
            </div>
        </div>
    </div>

    <div class="text-xs text-[#666666] py-4 text-center font-medium">
        Satuan Pelayanan Pemenuhan Gizi (SPPG) Ujung Lare &middot; Parepare
    </div>
</body>
</html>
