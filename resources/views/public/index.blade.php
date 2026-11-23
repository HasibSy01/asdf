<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Portal Transparansi Menu &amp; Gizi MBG - SPPG Ujung Lare Parepare</title>
    <!-- Google Font: Inter -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <!-- CollabFlow TNC Blue Website Palette Design System -->
    <link rel="stylesheet" href="{{ asset('css/style.css') }}">
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="min-h-screen flex flex-col justify-between antialiased bg-[#F7F6F2] text-[#000000]">
    <!-- Floating Modern Navbar Inspired by CollabFlow TNC -->
    <header class="sticky top-0 z-50 px-3 sm:px-6 pt-3">
        <div class="max-w-6xl mx-auto bg-[#FFFFFF]/95 backdrop-blur-md border border-[#164863]/10 rounded-full px-4 sm:px-6 py-3 flex items-center justify-between gap-3 flex-wrap shadow-[0_10px_30px_-12px_rgba(22,72,99,0.12)]">
            <div class="flex items-center gap-3 min-w-0">
                <div class="w-10 h-10 rounded-2xl bg-[#5027EF] p-1.5 flex items-center justify-center shrink-0 shadow-md shadow-[#5027EF]/30">
                    <img src="{{ asset('images/logo-mbg.png') }}" alt="Logo Badan Gizi Nasional" class="w-full h-full object-contain">
                </div>
                <div class="min-w-0">
                    <h1 class="font-black text-sm sm:text-base text-[#000000] tracking-tight truncate">MBG NutriView</h1>
                    <p class="text-[11px] sm:text-xs text-[#666666] truncate">Portal Keterbukaan Informasi Gizi &middot; SPPG Ujung Lare</p>
                </div>
            </div>

            <nav class="flex items-center gap-2 sm:gap-3 text-xs w-full sm:w-auto justify-end">
                <a href="{{ route('public.alur', $menu ? ['menu_id' => $menu->id] : []) }}"
                   class="flex-1 sm:flex-none text-center px-4 py-2 rounded-full bg-[#164863]/10 hover:bg-[#164863] text-[#164863] hover:text-[#FFFFFF] font-bold transition whitespace-nowrap">
                    Alur Dapur
                </a>
                <a href="{{ route('login') }}"
                   class="flex-1 sm:flex-none text-center px-5 py-2 bg-[#FFBD17] hover:brightness-95 text-[#000000] font-extrabold rounded-full shadow-md shadow-[#FFBD17]/40 transition whitespace-nowrap">
                    Masuk Petugas
                </a>
            </nav>
        </div>
    </header>

    <!-- Main Content Container -->
    <main class="max-w-6xl mx-auto px-4 sm:px-6 py-6 sm:py-8 w-full flex-1 space-y-6">
        @if($publishedMenus->count() > 1)
            <div class="bg-[#FFFFFF] border border-[#164863]/10 px-5 py-3.5 rounded-2xl flex flex-col sm:flex-row sm:items-center justify-between gap-2.5 text-xs shadow-sm">
                <div class="flex items-center gap-2.5 text-[#000000] font-bold">
                    <span class="w-2.5 h-2.5 rounded-full bg-[#5027EF] shrink-0"></span>
                    <span>Pilih menu terpublikasi lain:</span>
                </div>
                <form method="GET" action="{{ route('public.index') }}" class="w-full sm:w-auto">
                    <select name="menu_id" onchange="this.form.submit()" aria-label="Pilih menu terpublikasi"
                            class="w-full sm:w-80 px-4 py-2 bg-[#F7F6F2] border border-[#164863]/20 rounded-full text-xs font-semibold text-[#000000] focus:outline-none focus:border-[#5027EF] transition">
                        @foreach($publishedMenus as $pm)
                            <option value="{{ $pm->id }}" {{ $menu && $menu->id === $pm->id ? 'selected' : '' }}>
                                {{ $pm->nama_menu }} &middot; {{ optional($pm->tanggal_penyajian)->format('d M Y') }}
                            </option>
                        @endforeach
                    </select>
                </form>
            </div>
        @endif

        @if(!$menu)
            <div class="bg-[#FFFFFF] border border-[#164863]/10 p-8 sm:p-12 rounded-3xl text-center space-y-4 shadow-sm">
                <div class="w-16 h-16 rounded-2xl bg-[#FFBD17]/20 border border-[#FFBD17] text-[#000000] text-3xl flex items-center justify-center mx-auto">
                    &#127857;
                </div>
                <h2 class="text-xl sm:text-2xl font-black text-[#000000]">Menu Hari Ini Belum Tersedia</h2>
                <p class="text-xs sm:text-sm text-[#666666] max-w-md mx-auto leading-relaxed">
                    Menu makanan bergizi untuk hari ini sedang dalam proses penyusunan atau validasi gizi oleh Satuan Pelayanan Pemenuhan Gizi (SPPG) Ujung Lare. Silakan periksa kembali beberapa saat lagi.
                </p>
            </div>
        @else
            @php
                $alurTerima = $alurs->get('terima_bahan');
                $alurOlah = $alurs->get('pengolahan');
                $alurKemas = $alurs->get('pengemasan');
                $alurDist = $alurs->get('pengantaran');
                $waktuMatang = $alurOlah?->waktu_matang_terakhir ?? $alurKemas?->waktu_matang_terakhir ?? $alurOlah?->waktu_selesai_aktual;
                $batasAman = $waktuMatang ? \Carbon\Carbon::parse($waktuMatang)->addHours(4) : null;
                $qrUrl = route('public.scan.qr', ['token' => 'mbg-menu-' . $menu->id]);
                $qrSvg = \App\Services\QrSvgService::renderDataUri($qrUrl);
            @endphp

            <!-- ASYMMETRIC SPLIT HERO CARD -->
            <div class="rounded-[28px] overflow-hidden shadow-[0_22px_50px_-16px_rgba(80,39,239,0.18)] border border-[#5027EF]/15 bg-[#FFFFFF] grid grid-cols-1 lg:grid-cols-12">
                <div class="lg:col-span-7 p-6 sm:p-8 lg:p-10 flex flex-col justify-between space-y-6 bg-gradient-to-br from-[#FFFFFF] via-[#FAF9FF] to-[#F3F0FF]">
                    <div class="space-y-4">
                        <div class="flex flex-wrap items-center gap-2">
                            <span class="inline-flex items-center gap-1.5 px-3.5 py-1 bg-[#5027EF] text-[#FFFFFF] text-[11px] font-bold rounded-full shadow-sm">
                                <span>✓</span> Menu Resmi Terpublikasi
                            </span>
                            <span class="inline-flex items-center gap-1 px-3 py-1 bg-[#FFBD17] text-[#000000] text-[11px] font-extrabold rounded-full">
                                Tervalidasi AKG
                            </span>
                        </div>

                        <h2 class="text-2xl sm:text-4xl font-black text-[#000000] tracking-tight leading-[1.15]">
                            {{ $menu->nama_menu }}
                        </h2>

                        <p class="text-xs sm:text-sm text-[#666666] leading-relaxed">
                            Disajikan pada <strong class="text-[#000000]">{{ $menu->tanggal_penyajian->translatedFormat('l, d F Y') }}</strong>
                            untuk sasaran <strong class="text-[#164863]">{{ $menu->kelompokPenerima ? $menu->kelompokPenerima->nama_kelompok : 'Siswa Sekolah' }}</strong>
                            ({{ $menu->jenis_makanan }}).
                        </p>
                    </div>

                    @if($menu->foto_menu && file_exists(public_path($menu->foto_menu)))
                        <div class="rounded-2xl overflow-hidden border border-[#164863]/15 shadow-sm">
                            <img src="{{ asset($menu->foto_menu) }}" alt="{{ $menu->nama_menu }}" class="w-full h-52 sm:h-60 object-cover">
                        </div>
                    @endif

                    <div class="grid grid-cols-2 sm:grid-cols-3 gap-3 pt-2">
                        <div class="p-3.5 rounded-2xl bg-[#FFFFFF] border border-[#164863]/12 shadow-sm">
                            <div class="text-[11px] text-[#666666] font-semibold">Takaran Saji</div>
                            <div class="text-lg sm:text-xl font-black text-[#000000] mt-0.5">&plusmn; {{ $menu->ukuran_porsi_gram }} g</div>
                        </div>
                        <div class="p-3.5 rounded-2xl bg-[#FFFFFF] border border-[#164863]/12 shadow-sm">
                            <div class="text-[11px] text-[#666666] font-semibold">Target Produksi</div>
                            <div class="text-lg sm:text-xl font-black text-[#5027EF] mt-0.5">{{ number_format($menu->jumlah_porsi_rencana, 0, ',', '.') }} porsi</div>
                        </div>
                        <div class="p-3.5 rounded-2xl bg-[#FFFFFF] border border-[#164863]/12 shadow-sm col-span-2 sm:col-span-1">
                            <div class="text-[11px] text-[#666666] font-semibold">Batas Konsumsi</div>
                            <div class="text-sm sm:text-base font-black text-[#164863] mt-1">
                                {{ $batasAman ? '≤ ' . $batasAman->format('H.i') . ' WITA' : 'Maks 4 Jam' }}
                            </div>
                        </div>
                    </div>
                </div>

                <div class="lg:col-span-5 bg-gradient-to-br from-[#5027EF] via-[#401ED1] to-[#164863] p-6 sm:p-8 flex flex-col justify-between text-[#FFFFFF] relative overflow-hidden">
                    <div class="flex items-center justify-between gap-2">
                        <span class="text-xs font-bold uppercase tracking-wider text-white/80">Ringkasan Kelaikan &amp; QR</span>
                        <a href="{{ route('public.alur', ['menu_id' => $menu->id]) }}"
                           class="px-3.5 py-1.5 rounded-full bg-[#FFBD17] text-[#000000] font-extrabold text-xs hover:brightness-95 transition shadow">
                            Pantau Dapur &rarr;
                        </a>
                    </div>

                    <div class="my-5 bg-[#FFFFFF] text-[#000000] rounded-2xl p-4 sm:p-5 shadow-xl border border-white/40 space-y-4">
                        <div class="flex items-center justify-between gap-3">
                            <div>
                                <div class="text-[11px] font-bold text-[#666666] uppercase">Indikator Gizi Seimbang</div>
                                <div class="text-sm font-black text-[#000000]">Standar BGN &amp; AKG Harian</div>
                            </div>
                            <div class="flex items-end gap-1.5 h-8" aria-hidden="true">
                                <span class="w-2 h-5 rounded-full bg-[#FFBD17]"></span>
                                <span class="w-2 h-8 rounded-full bg-[#5027EF]"></span>
                                <span class="w-2 h-6 rounded-full bg-[#164863]"></span>
                                <span class="w-2 h-4 rounded-full bg-[#FFBD17]"></span>
                                <span class="w-2 h-7 rounded-full bg-[#5027EF]"></span>
                            </div>
                        </div>

                        <div class="flex items-center gap-4 pt-2 border-t border-slate-100">
                            <div class="bg-[#F7F6F2] p-2 rounded-xl border border-slate-200 shrink-0">
                                <img src="{{ $qrSvg }}" alt="QR Menu Publik {{ $menu->nama_menu }}" class="w-20 h-20">
                            </div>
                            <div class="space-y-1">
                                <span class="inline-block px-2.5 py-0.5 rounded-full bg-[#5027EF]/10 text-[#5027EF] text-[10px] font-extrabold">
                                    QR Terverifikasi
                                </span>
                                <div class="text-xs font-extrabold text-[#000000]">Pindai QR Info Menu</div>
                                <p class="text-[11px] text-[#666666] leading-snug">
                                    Akses langsung rincian gizi &amp; ketertelusuran bahan baku melalui ponsel.
                                </p>
                            </div>
                        </div>
                    </div>

                    <div class="text-[11px] text-white/80 flex items-center justify-between flex-wrap gap-2">
                        <span>SPPG Ujung Lare &middot; Kota Parepare</span>
                        <span class="font-bold text-[#FFBD17]">● Data Real-Time</span>
                    </div>
                </div>
            </div>

            <!-- 5 Nutrisi Kunci Bento Cards -->
            @if($hasilGizi)
            <section class="bg-[#FFFFFF] border border-[#164863]/10 p-6 sm:p-7 rounded-[24px] shadow-sm space-y-4">
                <div class="flex items-center justify-between flex-wrap gap-2">
                    <div>
                        <h3 class="text-base sm:text-lg font-black text-[#000000]">Kandungan Gizi per 1 Sajian</h3>
                        <p class="text-xs text-[#666666]">Akumulasi makronutrisi berdasarkan gramasi bahan penyusun per porsi</p>
                    </div>
                    <span class="px-3 py-1 rounded-full bg-[#164863]/10 text-[#164863] text-xs font-bold">
                        Basis Rujukan 100g TKPI
                    </span>
                </div>

                <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-3.5 sm:gap-4">
                    <div class="hover-lift bg-[#F7F6F2] border border-[#5027EF]/20 p-4 rounded-2xl flex flex-col justify-between">
                        <div class="flex items-center justify-between">
                            <span class="text-[11px] text-[#666666] uppercase tracking-wider font-bold">Energi Total</span>
                            <span class="w-2.5 h-2.5 rounded-full bg-[#5027EF]"></span>
                        </div>
                        <div class="my-2">
                            <span class="text-2xl sm:text-3xl font-black text-[#5027EF] tabular-nums">{{ $hasilGizi['energi_kkal'] }}</span>
                            <span class="text-xs font-semibold text-[#666666] block">kkal / porsi</span>
                        </div>
                        <div class="w-full h-1.5 rounded-full bg-[#5027EF]/15 overflow-hidden">
                            <div class="h-full w-4/5 bg-[#5027EF] rounded-full"></div>
                        </div>
                    </div>

                    <div class="hover-lift bg-[#F7F6F2] border border-[#164863]/20 p-4 rounded-2xl flex flex-col justify-between">
                        <div class="flex items-center justify-between">
                            <span class="text-[11px] text-[#666666] uppercase tracking-wider font-bold">Protein</span>
                            <span class="w-2.5 h-2.5 rounded-full bg-[#164863]"></span>
                        </div>
                        <div class="my-2">
                            <span class="text-2xl sm:text-3xl font-black text-[#164863] tabular-nums">{{ $hasilGizi['protein_g'] }}</span>
                            <span class="text-xs font-semibold text-[#666666] block">gram</span>
                        </div>
                        <div class="w-full h-1.5 rounded-full bg-[#164863]/15 overflow-hidden">
                            <div class="h-full w-3/4 bg-[#164863] rounded-full"></div>
                        </div>
                    </div>

                    <div class="hover-lift bg-[#F7F6F2] border border-[#FFBD17] p-4 rounded-2xl flex flex-col justify-between">
                        <div class="flex items-center justify-between">
                            <span class="text-[11px] text-[#666666] uppercase tracking-wider font-bold">Lemak Sehat</span>
                            <span class="w-2.5 h-2.5 rounded-full bg-[#FFBD17]"></span>
                        </div>
                        <div class="my-2">
                            <span class="text-2xl sm:text-3xl font-black text-[#000000] tabular-nums">{{ $hasilGizi['lemak_g'] }}</span>
                            <span class="text-xs font-semibold text-[#666666] block">gram</span>
                        </div>
                        <div class="w-full h-1.5 rounded-full bg-[#FFBD17]/30 overflow-hidden">
                            <div class="h-full w-2/3 bg-[#FFBD17] rounded-full"></div>
                        </div>
                    </div>

                    <div class="hover-lift bg-[#F7F6F2] border border-[#000000]/15 p-4 rounded-2xl flex flex-col justify-between">
                        <div class="flex items-center justify-between">
                            <span class="text-[11px] text-[#666666] uppercase tracking-wider font-bold">Karbohidrat</span>
                            <span class="w-2.5 h-2.5 rounded-full bg-[#000000]"></span>
                        </div>
                        <div class="my-2">
                            <span class="text-2xl sm:text-3xl font-black text-[#000000] tabular-nums">{{ $hasilGizi['karbohidrat_g'] }}</span>
                            <span class="text-xs font-semibold text-[#666666] block">gram</span>
                        </div>
                        <div class="w-full h-1.5 rounded-full bg-black/10 overflow-hidden">
                            <div class="h-full w-4/5 bg-[#000000] rounded-full"></div>
                        </div>
                    </div>

                    <div class="hover-lift bg-[#F7F6F2] border border-[#164863]/20 p-4 rounded-2xl flex flex-col justify-between col-span-2 sm:col-span-1">
                        <div class="flex items-center justify-between">
                            <span class="text-[11px] text-[#666666] uppercase tracking-wider font-bold">Serat Pangan</span>
                            <span class="w-2.5 h-2.5 rounded-full bg-[#5027EF]"></span>
                        </div>
                        <div class="my-2">
                            <span class="text-2xl sm:text-3xl font-black text-[#164863] tabular-nums">{{ $hasilGizi['serat_g'] }}</span>
                            <span class="text-xs font-semibold text-[#666666] block">gram</span>
                        </div>
                        <div class="w-full h-1.5 rounded-full bg-[#164863]/15 overflow-hidden">
                            <div class="h-full w-1/2 bg-[#5027EF] rounded-full"></div>
                        </div>
                    </div>
                </div>
            </section>
            @endif

            <!-- Rincian Bahan Baku Terbuka -->
            <section class="bg-[#FFFFFF] border border-[#164863]/10 p-6 sm:p-7 rounded-[24px] shadow-sm space-y-4">
                <div class="flex items-center justify-between flex-wrap gap-2">
                    <div>
                        <h3 class="text-base sm:text-lg font-black text-[#000000]">Transparansi Bahan Baku Penyusun Makanan</h3>
                        <p class="text-xs text-[#666666]">Ketertelusuran lot bahan baku dan pemasok sesuai standar keamanan pangan</p>
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3.5">
                    @forelse($menu->menuBahans as $mb)
                        <div class="hover-lift p-4 bg-[#F7F6F2] border border-[#164863]/12 rounded-2xl text-xs flex flex-col justify-between gap-2.5">
                            <div class="flex items-start justify-between gap-2">
                                <div class="font-extrabold text-[#000000] text-sm leading-snug">{{ $mb->bahanBaku?->nama_bahan ?? 'Bahan baku' }}</div>
                                <span class="px-2.5 py-0.5 rounded-full bg-[#5027EF] text-[#FFFFFF] text-[10px] font-bold shrink-0">
                                    {{ $mb->komponen }}
                                </span>
                            </div>
                            <div class="text-xs text-[#666666]">
                                Takaran: <strong class="text-[#000000]">{{ $mb->berat_per_sajian_gram }} g/porsi</strong> &bull; Lot: <span class="font-mono font-semibold text-[#164863]">{{ $mb->bahanBaku?->kode_lot ?? '-' }}</span>
                            </div>
                            <div class="text-[11px] text-[#666666] border-t border-black/10 pt-2 flex items-center justify-between">
                                <span>Supplier: <strong class="text-[#000000]">{{ $mb->bahanBaku?->supplier ?? 'Penyedia Lokal' }}</strong></span>
                            </div>
                        </div>
                    @empty
                        <div class="p-4 bg-[#F7F6F2] border border-[#164863]/12 rounded-2xl text-xs text-[#666666] col-span-full">
                            Komposisi bahan baku sedang diperbarui oleh pengelola dapur.
                        </div>
                    @endforelse
                </div>
            </section>

            <!-- Status Pembuatan & Pengantaran Real-Time -->
            <section class="bg-[#164863] text-[#FFFFFF] p-6 sm:p-7 rounded-[24px] shadow-lg space-y-4">
                <div class="flex items-center justify-between flex-wrap gap-2">
                    <div>
                        <h3 class="text-sm sm:text-base font-extrabold text-[#FFFFFF] uppercase tracking-wider">Status Pembuatan &amp; Pengantaran Real-Time</h3>
                        <p class="text-xs text-white/75 mt-0.5">Rekam waktu aktual 4 tahap produksi hingga batas aman konsumsi</p>
                    </div>
                    <a href="{{ route('public.alur', ['menu_id' => $menu->id]) }}"
                       class="px-4 py-2 rounded-full bg-[#FFBD17] text-[#000000] text-xs font-extrabold inline-flex items-center gap-1.5 hover:brightness-95 transition shadow">
                        <span>Lihat rincian ketertelusuran alur</span>
                        <span>&rarr;</span>
                    </a>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-3 text-xs">
                    <div class="p-4 bg-[#FFFFFF] text-[#000000] rounded-2xl shadow-sm">
                        <div class="text-[11px] text-[#666666] font-bold">1. Terima Bahan</div>
                        @if($alurTerima && $alurTerima->waktu_selesai_aktual)
                            <div class="font-black text-[#164863] text-sm mt-1">✓ {{ \Carbon\Carbon::parse($alurTerima->waktu_selesai_aktual)->format('H.i') }} WITA</div>
                        @else
                            <div class="font-semibold text-[#666666] mt-1">Menunggu catatan</div>
                        @endif
                    </div>

                    <div class="p-4 bg-[#FFFFFF] text-[#000000] rounded-2xl shadow-sm">
                        <div class="text-[11px] text-[#666666] font-bold">2. Selesai Masak</div>
                        @if($alurOlah && $alurOlah->waktu_selesai_aktual)
                            <div class="font-black text-[#164863] text-sm mt-1">✓ {{ \Carbon\Carbon::parse($alurOlah->waktu_selesai_aktual)->format('H.i') }} WITA</div>
                        @else
                            <div class="font-semibold text-[#666666] mt-1">Belum selesai</div>
                        @endif
                    </div>

                    <div class="p-4 bg-[#FFFFFF] text-[#000000] rounded-2xl shadow-sm">
                        <div class="text-[11px] text-[#666666] font-bold">3. Selesai Kemas</div>
                        @if($alurKemas && $alurKemas->waktu_selesai_aktual)
                            <div class="font-black text-[#164863] text-sm mt-1">✓ {{ \Carbon\Carbon::parse($alurKemas->waktu_selesai_aktual)->format('H.i') }} WITA</div>
                        @else
                            <div class="font-semibold text-[#666666] mt-1">Belum selesai</div>
                        @endif
                    </div>

                    <div class="p-4 bg-[#FFBD17] text-[#000000] rounded-2xl shadow-sm">
                        <div class="text-[11px] text-[#000000]/75 font-extrabold">4. Batas Konsumsi Aman</div>
                        @if($batasAman)
                            <div class="font-black text-[#000000] text-sm mt-1">&le; {{ $batasAman->format('H.i') }} WITA</div>
                        @else
                            <div class="font-bold text-[#000000] mt-1">Maks 4 jam pasca matang</div>
                        @endif
                    </div>
                </div>
            </section>
        @endif
    </main>

    <!-- Footer Publik -->
    <footer class="border-t border-[#164863]/10 bg-[#FFFFFF] py-6 px-4 text-center text-xs text-[#666666]">
        <p class="font-bold text-[#000000]">Satuan Pelayanan Pemenuhan Gizi (SPPG) Ujung Lare &middot; Kota Parepare, Sulawesi Selatan</p>
        <p class="text-[11px] text-[#666666] mt-1">Dikelola sejalan dengan UU Keterbukaan Informasi Publik (UU No. 14 Tahun 2008) &amp; Petunjuk Teknis Badan Gizi Nasional.</p>
    </footer>
</body>
</html>
