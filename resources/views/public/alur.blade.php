<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Penelusuran Alur Operasional Dapur - MBG NutriView</title>
    <!-- Google Font: Inter -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <!-- CollabFlow TNC Blue Website Palette Design System -->
    <link rel="stylesheet" href="{{ asset('css/style.css') }}">
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="min-h-screen flex flex-col justify-between antialiased bg-[#F7F6F2] text-[#000000]">
    <!-- Floating Navbar -->
    <header class="sticky top-0 z-50 px-3 sm:px-6 pt-3">
        <div class="max-w-6xl mx-auto bg-[#FFFFFF]/95 backdrop-blur-md border border-[#164863]/10 rounded-full px-4 sm:px-6 py-3 flex items-center justify-between gap-3 flex-wrap shadow-[0_10px_30px_-12px_rgba(22,72,99,0.12)]">
            <div class="flex items-center gap-3 min-w-0">
                <a href="{{ route('public.index') }}" class="w-10 h-10 rounded-2xl bg-[#5027EF] p-1.5 flex items-center justify-center shrink-0 shadow-md shadow-[#5027EF]/30">
                    <img src="{{ asset('images/logo-mbg.png') }}" alt="Logo Badan Gizi Nasional" class="w-full h-full object-contain">
                </a>
                <div class="min-w-0">
                    <h1 class="font-black text-sm sm:text-base text-[#000000] tracking-tight truncate">MBG NutriView</h1>
                    <p class="text-[11px] sm:text-xs text-[#666666] truncate">Penelusuran Alur Operasional Dapur SPPG Ujung Lare</p>
                </div>
            </div>

            <a href="{{ route('public.index', $menu ? ['menu_id' => $menu->id] : []) }}"
               class="px-4 py-2 rounded-full bg-[#FFBD17] hover:brightness-95 text-xs text-[#000000] font-extrabold inline-flex items-center gap-1.5 transition shrink-0 shadow-sm">
                <span>&larr;</span>
                <span>Kembali ke Menu</span>
            </a>
        </div>
    </header>

    <main class="max-w-6xl mx-auto px-4 sm:px-6 py-6 sm:py-8 w-full flex-1 space-y-6">
        <div class="flex flex-col sm:flex-row sm:items-end justify-between gap-3">
            <div>
                <span class="inline-block px-3 py-1 rounded-full bg-[#5027EF]/10 text-[#5027EF] text-[11px] font-extrabold mb-2">
                    Transparansi Operasional SPPG
                </span>
                <h2 class="text-2xl sm:text-3xl font-black text-[#000000] tracking-tight">Ketertelusuran Alur Produksi &amp; Distribusi</h2>
                <p class="text-xs sm:text-sm text-[#666666] mt-1 leading-relaxed">
                    Informasi terbuka mengenai rekam jejak waktu masak, pengemasan, hingga armada pengantaran tiba di sekolah.
                </p>
            </div>
        </div>

        @if(!$menu)
            <div class="bg-[#FFFFFF] border border-[#164863]/10 p-8 sm:p-12 rounded-3xl text-center space-y-3 shadow-sm">
                <p class="text-xs sm:text-sm text-[#666666]">Alur operasional belum tersedia karena belum ada menu yang dipublikasikan.</p>
            </div>
        @else
            @php
                $tahapList = [
                    'terima_bahan' => ['1', 'Penerimaan Bahan Baku', 'Memeriksa kualitas bahan, lot supplier, dan masa simpan.', '01:30 - 02:00 WITA'],
                    'pengolahan' => ['2', 'Pengolahan Makanan (Memasak)', 'Proses memasak nasi, lauk pauk, dan sayur hingga matang sempurna.', '02:15 - 05:30 WITA'],
                    'pengemasan' => ['3', 'Pemorsian & Pengemasan', 'Makanan dimasukkan ke wadah higienis tertutup sesuai gramasi AKG.', '05:45 - 06:30 WITA'],
                    'pengantaran' => ['4', 'Pengantaran ke Sekolah Sasaran', 'Armada mengantar ke sekolah dengan batas konsumsi maksimal 4 jam.', '07:30 - 08:00 WITA'],
                ];
                $completedCount = collect($tahapList)->keys()->filter(function ($k) use ($alurs) {
                    $st = $alurs->get($k)?->status_tahap;
                    return in_array($st, ['selesai', 'terlambat'], true);
                })->count();
                $progressPct = round(($completedCount / 4) * 100);
            @endphp

            <!-- Jadwal vs Realisasi Alur -->
            <div class="bg-[#FFFFFF] border border-[#164863]/10 p-5 sm:p-7 rounded-[26px] shadow-sm space-y-5">
                <div class="border-b border-slate-200 pb-4 flex justify-between items-start sm:items-center flex-wrap gap-3">
                    <div>
                        <h3 class="text-base sm:text-lg font-black text-[#000000]">{{ $menu->nama_menu }}</h3>
                        <p class="text-xs text-[#666666] mt-0.5">Batch Produksi: Tahap 1 &middot; Tanggal: {{ $menu->tanggal_penyajian->format('d M Y') }}</p>
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="px-3.5 py-1 bg-[#5027EF] text-[#FFFFFF] text-[11px] font-bold rounded-full shadow-sm">
                            Status: Dipantau Real-Time ({{ $completedCount }}/4)
                        </span>
                    </div>
                </div>

                <!-- Progress Capaian Tahap -->
                <div class="space-y-1.5">
                    <div class="flex justify-between text-xs font-bold text-[#666666]">
                        <span>Progres Pelaksanaan Dapur</span>
                        <span class="font-mono font-extrabold text-[#5027EF]">{{ $progressPct }}%</span>
                    </div>
                    <div class="w-full h-2.5 bg-[#F7F6F2] rounded-full overflow-hidden border border-slate-200">
                        <div class="h-full bg-gradient-to-r from-[#5027EF] via-[#164863] to-[#FFBD17] rounded-full transition-all duration-300" style="width: {{ $progressPct }}%;"></div>
                    </div>
                </div>

                <!-- 4 Tahapan Detail -->
                <div class="space-y-3.5">
                    @foreach($tahapList as $tKey => $tInfo)
                        @php
                            $alurItem = $alurs->get($tKey);
                            $st = $alurItem?->status_tahap ?? 'belum_mulai';
                            $circleClass = match($st) {
                                'selesai' => 'bg-[#5027EF] text-[#FFFFFF]',
                                'terlambat' => 'bg-rose-600 text-[#FFFFFF]',
                                'berlangsung' => 'bg-[#164863] text-[#FFFFFF]',
                                default => 'bg-[#FFBD17] text-[#000000]',
                            };
                        @endphp
                        <div class="hover-lift p-4 sm:p-5 bg-[#F7F6F2] border border-[#164863]/12 rounded-2xl flex flex-col md:flex-row md:items-center justify-between gap-4">
                            <div class="flex items-start gap-3.5">
                                <div class="w-9 h-9 rounded-2xl {{ $circleClass }} flex items-center justify-center font-black text-xs shrink-0 mt-0.5 shadow-sm">
                                    {{ $tInfo[0] }}
                                </div>
                                <div class="space-y-1">
                                    <div class="font-extrabold text-[#000000] text-sm sm:text-base">{{ $tInfo[1] }}</div>
                                    <div class="text-xs text-[#666666] leading-relaxed">{{ $tInfo[2] }}</div>
                                    <div class="text-[11px] text-[#666666]">Rencana Jadwal Acuan: <span class="font-mono font-bold text-[#164863]">{{ $tInfo[3] }}</span></div>
                                </div>
                            </div>

                            <div class="md:text-right pl-12 md:pl-0 shrink-0">
                                @if($alurItem && $alurItem->status_tahap === 'selesai')
                                    <span class="px-3 py-1 bg-emerald-100 text-emerald-800 border border-emerald-300 rounded-full font-bold text-[11px] inline-block mb-1">
                                        ✓ Selesai Dicatat
                                    </span>
                                    <div class="text-xs text-[#000000] font-mono font-bold">
                                        {{ $alurItem->waktu_mulai_aktual ? $alurItem->waktu_mulai_aktual->format('H:i') : '-' }} -
                                        {{ $alurItem->waktu_selesai_aktual ? $alurItem->waktu_selesai_aktual->format('H:i') : '-' }} WITA
                                    </div>
                                    <div class="text-[11px] text-[#666666] mt-0.5">Petugas: {{ $alurItem->petugas_aktual ?: 'Tim Dapur' }}</div>
                                @elseif($alurItem && $alurItem->status_tahap === 'terlambat')
                                    <span class="px-3 py-1 bg-rose-100 text-rose-800 border border-rose-300 rounded-full font-bold text-[11px] inline-block mb-1">
                                        &#9888; Selesai Terlambat
                                    </span>
                                    <div class="text-xs text-[#000000] font-mono font-bold">
                                        {{ $alurItem->waktu_mulai_aktual ? $alurItem->waktu_mulai_aktual->format('H:i') : '-' }} -
                                        {{ $alurItem->waktu_selesai_aktual ? $alurItem->waktu_selesai_aktual->format('H:i') : '-' }} WITA
                                    </div>
                                    <div class="text-[11px] text-[#666666] mt-0.5">Petugas: {{ $alurItem->petugas_aktual ?: 'Tim Dapur' }}</div>
                                @elseif($alurItem && $alurItem->status_tahap === 'berlangsung')
                                    <span class="px-3 py-1 bg-[#5027EF]/15 text-[#5027EF] border border-[#5027EF]/30 rounded-full font-bold text-[11px] inline-block mb-1">
                                        &#9203; Sedang Berlangsung
                                    </span>
                                    <div class="text-xs text-[#000000] font-mono font-bold">
                                        Mulai: {{ $alurItem->waktu_mulai_aktual ? $alurItem->waktu_mulai_aktual->format('H:i') : '-' }} WITA
                                    </div>
                                @else
                                    <span class="px-3 py-1 bg-[#FFBD17]/30 text-[#000000] border border-[#FFBD17] rounded-full font-bold text-[11px] inline-block mb-1">
                                        &#9203; Menunggu Pelaksanaan
                                    </span>
                                    <div class="text-xs text-[#666666]">Alur belum selesai dicatat</div>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            <!-- Jadwal Produksi & Rencana Pengantaran Terkait -->
            <div class="bg-[#FFFFFF] border border-[#164863]/10 rounded-[26px] p-5 sm:p-7 shadow-sm space-y-4">
                <div>
                    <h2 class="text-base sm:text-lg font-black text-[#000000]">Jadwal Produksi &amp; Pengantaran Terencana</h2>
                    <p class="text-xs text-[#666666] mt-0.5 leading-relaxed">Informasi read-only alokasi waktu produksi, target pengantaran sekolah sasaran, dan petugas penanggung jawab.</p>
                </div>

                @if(collect($jadwals)->isEmpty())
                    <div class="p-4 bg-[#F7F6F2] border border-slate-200 rounded-2xl text-xs text-[#666666]">
                        Belum ada jadwal produksi terinci untuk menu ini.
                    </div>
                @else
                    <div class="grid grid-cols-1 gap-3 sm:hidden">
                        @foreach($jadwals as $jdw)
                            <div class="p-4 bg-[#F7F6F2] border border-[#164863]/12 rounded-2xl space-y-2 text-xs">
                                <div class="flex items-start justify-between gap-2">
                                    <div>
                                        <div class="font-extrabold text-[#000000]">{{ $jdw->tahap }}</div>
                                        <div class="text-[11px] text-[#666666]">{{ $jdw->batch_no }} &middot; {{ $jdw->kelompokPenerima?->nama_kelompok ?? 'Umum SPPG' }}</div>
                                    </div>
                                    <span class="px-2.5 py-0.5 rounded-full bg-[#5027EF] text-[#FFFFFF] text-[10px] font-bold shrink-0">
                                        {{ $jdw->status }}
                                    </span>
                                </div>
                                <div class="grid grid-cols-2 gap-2 pt-2 border-t border-black/10 text-[11px]">
                                    <div>
                                        <span class="text-[#666666] block">Jam Rencana</span>
                                        <span class="font-mono font-bold text-[#000000]">{{ substr((string) $jdw->waktu_mulai_rencana, 0, 5) }} - {{ substr((string) $jdw->waktu_selesai_rencana, 0, 5) }}</span>
                                    </div>
                                    <div>
                                        <span class="text-[#666666] block">Target Tiba</span>
                                        <span class="font-mono font-bold text-[#164863]">{{ $jdw->target_tiba ? substr((string) $jdw->target_tiba, 0, 5) . ' WITA' : '-' }}</span>
                                    </div>
                                    <div class="col-span-2">
                                        <span class="text-[#666666]">Petugas:</span>
                                        <strong class="text-[#000000]">{{ $jdw->petugas_rencana }}</strong>
                                        <span class="text-[#5027EF] font-bold">({{ $jdw->porsi_target }} porsi)</span>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>

                    <div class="hidden sm:block table-responsive">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Tahap &amp; Batch</th>
                                    <th>Kelompok Penerima</th>
                                    <th>Jam Rencana</th>
                                    <th>Target Tiba</th>
                                    <th>Petugas &amp; Porsi</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($jadwals as $jdw)
                                    <tr>
                                        <td>
                                            <span class="font-extrabold text-[#000000] block">{{ $jdw->tahap }}</span>
                                            <span class="text-[11px] text-[#666666]">{{ $jdw->batch_no }}</span>
                                        </td>
                                        <td>{{ $jdw->kelompokPenerima?->nama_kelompok ?? 'Umum SPPG' }}</td>
                                        <td class="font-mono font-semibold whitespace-nowrap">
                                            {{ substr((string) $jdw->waktu_mulai_rencana, 0, 5) }} - {{ substr((string) $jdw->waktu_selesai_rencana, 0, 5) }} WITA
                                        </td>
                                        <td class="font-mono font-semibold whitespace-nowrap">
                                            {{ $jdw->target_tiba ? substr((string) $jdw->target_tiba, 0, 5) . ' WITA' : '-' }}
                                        </td>
                                        <td>
                                            <span class="text-[#000000] font-bold">{{ $jdw->petugas_rencana }}</span>
                                            <span class="text-[#5027EF] font-bold text-[11px]">({{ $jdw->porsi_target }} porsi)</span>
                                        </td>
                                        <td>
                                            <span class="px-3 py-1 rounded-full bg-[#FFBD17]/30 border border-[#FFBD17] text-[11px] text-[#000000] font-bold whitespace-nowrap">
                                                {{ $jdw->status }}
                                            </span>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        @endif
    </main>

    <!-- Footer -->
    <footer class="border-t border-[#164863]/10 bg-[#FFFFFF] py-6 px-4 text-center text-xs text-[#666666]">
        <p class="font-bold text-[#000000]">Satuan Pelayanan Pemenuhan Gizi (SPPG) Ujung Lare &middot; Kota Parepare</p>
    </footer>
</body>
</html>
