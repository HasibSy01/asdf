@extends('layouts.app')

@section('title', 'KPI dan Prediksi Risiko')

@section('content')
@php
    $alurOlah = $alurs->get('pengolahan');
    $alurKemas = $alurs->get('pengemasan');
    $alurDist = $alurs->get('pengantaran');
    $waktuMatang = $alurOlah?->waktu_matang_terakhir ?? $alurKemas?->waktu_matang_terakhir ?? $alurOlah?->waktu_selesai_aktual;
    $batas4Jam = $waktuMatang ? \Carbon\Carbon::parse($waktuMatang)->addHours(4) : null;
@endphp
<div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-white tracking-tight">KPI dan Prediksi Risiko</h1>
            <p class="text-xs text-slate-400 mt-1">
                Pemantauan 7 indikator KPI dan hasil inferensi Random Forest pra-distribusi (hanya baca, dengan pemicu hitung ulang manual).
            </p>
        </div>

        @if($activeMenu)
            <form method="POST" action="{{ route('admin.kpi.hitung') }}">
                @csrf
                <input type="hidden" name="menu_id" value="{{ $activeMenu->id }}">
                <input type="hidden" name="batch_no" value="{{ $batchNo }}">
                <button type="submit" class="px-4 py-2.5 bg-[#192748] hover:bg-[#20335e] border border-[#2a3d69] text-xs font-semibold rounded-xl text-white transition cursor-pointer">
                    Hitung Ulang KPI &amp; Prediksi
                </button>
            </form>
        @endif
    </div>

    <!-- Filter Selector -->
    <form method="GET" action="{{ route('admin.kpi.index') }}" class="grid grid-cols-1 md:grid-cols-3 gap-4 items-end">
        <div>
            <label class="block text-xs font-semibold text-slate-300 mb-1.5">Menu &amp; Tanggal Penyajian</label>
            <select name="menu_id" onchange="this.form.submit()" class="w-full px-3.5 py-2.5 bg-[#192748] border border-[#2a3d69] rounded-xl text-xs text-white focus:outline-none">
                @foreach($menus as $m)
                    <option value="{{ $m->id }}" {{ $activeMenu && $activeMenu->id === $m->id ? 'selected' : '' }}>
                        {{ $m->nama_menu }} &middot; {{ optional($m->tanggal_penyajian)->format('d M Y') }}
                    </option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="block text-xs font-semibold text-slate-300 mb-1.5">Batch</label>
            <select name="batch_no" onchange="this.form.submit()" class="w-full px-3.5 py-2.5 bg-[#192748] border border-[#2a3d69] rounded-xl text-xs text-white focus:outline-none">
                @foreach(['Tahap 1', 'Tahap 2', 'Tahap 3'] as $bOption)
                    <option value="{{ $bOption }}" {{ $batchNo === $bOption ? 'selected' : '' }}>{{ $bOption }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="block text-xs font-semibold text-slate-300 mb-1.5">Kelompok cakupan</label>
            <input type="text" readonly value="{{ $kelompoks->pluck('nama_kelompok')->map(fn($n) => trim(explode('/', $n)[0]))->implode(', ') ?: 'Semua titik' }}"
                   class="w-full px-3.5 py-2.5 bg-[#192748] border border-[#2a3d69] rounded-xl text-xs text-slate-300 focus:outline-none">
        </div>
    </form>

    <!-- Notice Card -->
    <div class="p-4 bg-[#131f38] border border-[#1f3056] rounded-2xl space-y-1">
        <h3 class="text-xs font-bold text-white">Dua jendela evaluasi berbeda (FR-025 s/d FR-032)</h3>
        <p class="text-[11px] text-slate-300 leading-relaxed">
            Panel KPI mengevaluasi ketepatan siklus produksi setelah distribusi selesai. Panel Prediksi Random Forest dijalankan setelah kemas selesai sebelum pengantaran berangkat untuk membantu petugas mengantisipasi keterlambatan melewati batas aman 4 jam.
        </p>
    </div>

    @if($kurangData && !$kpi)
        <div class="p-5 bg-amber-950/30 border border-amber-700/60 rounded-2xl space-y-1">
            <h3 class="text-sm font-bold text-amber-300">Data Belum Cukup untuk Prediksi &amp; KPI (FR-027 / FR-030)</h3>
            <p class="text-xs text-amber-200 leading-relaxed">
                Tahap pengemasan dan waktu matang makanan untuk <strong>{{ $activeMenu?->nama_menu ?? 'menu terpilih' }} ({{ $batchNo }})</strong> belum tercatat selesai. Silakan lengkapi catatan aktual di menu <a href="{{ route('admin.tahap.index', ['menu_id' => $activeMenu?->id, 'batch_no' => $batchNo]) }}" class="underline text-white">Pencatatan Tahap</a>.
            </p>
        </div>
    @endif

    <!-- Section A: KPI batch -->
    <div class="bg-[#131f38] border border-[#1f3056] p-6 rounded-2xl space-y-5">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 border-b border-[#1f3056] pb-3">
            <div>
                <h2 class="text-sm font-bold text-white">A. Evaluasi KPI ({{ $batchNo }})</h2>
                <div class="text-xs text-slate-400 mt-0.5">
                    Menu: {{ $activeMenu?->nama_menu ?? '-' }} &middot;
                    @if($alurDist && $alurDist->waktu_selesai_aktual)
                        Distribusi selesai dicatat ({{ \Carbon\Carbon::parse($alurDist->waktu_selesai_aktual)->format('H.i') }})
                    @else
                        Menunggu penyelesaian aktual distribusi
                    @endif
                </div>
            </div>
            <div>
                <span class="px-3 py-1 bg-[#192748] border border-[#2a3d69] text-slate-300 text-[11px] font-semibold rounded-lg">
                    Hasil otomatis &middot; hanya baca
                </span>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">
            <!-- Left: Table of 7 Indicators -->
            <div class="lg:col-span-8 overflow-x-auto rounded-xl border border-[#1f3056]">
                <table class="w-full text-left text-xs border-collapse">
                    <thead>
                        <tr class="bg-[#182645] text-slate-300 font-semibold border-b border-[#1f3056]">
                            <th class="py-3 px-4">Indikator</th>
                            <th class="py-3 px-4">Makna indikator</th>
                            <th class="py-3 px-4">Skor tercatat</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-[#1f3056]/60 text-slate-300">
                        @php
                            $rows = [
                                ['kode' => 'Sr', 'nama' => 'Ketepatan penerimaan bahan (10%)', 'val' => $kpi?->skor_sr],
                                ['kode' => 'Sx', 'nama' => 'Kepatuhan masa kedaluwarsa bahan (15%)', 'val' => $kpi?->skor_sx],
                                ['kode' => 'Sp', 'nama' => 'Ketepatan mulai pengolahan (10%)', 'val' => $kpi?->skor_sp],
                                ['kode' => 'Sq', 'nama' => 'Ketepatan pemorsian / pengemasan (15%)', 'val' => $kpi?->skor_sq],
                                ['kode' => 'Sk', 'nama' => 'Ketercapaian kapasitas porsi (25%)', 'val' => $kpi?->skor_sk],
                                ['kode' => 'Sw', 'nama' => 'Ketepatan waktu tiba pengantaran (15%)', 'val' => $kpi?->skor_sw],
                                ['kode' => 'Sb', 'nama' => 'Kepatuhan batas konsumsi 4 jam (10%)', 'val' => $kpi?->skor_sb],
                            ];
                        @endphp
                        @foreach($rows as $r)
                            <tr class="hover:bg-[#182747]/40">
                                <td class="py-3 px-4 font-bold text-white">{{ $r['kode'] }}</td>
                                <td class="py-3 px-4">{{ $r['nama'] }}</td>
                                <td class="py-3 px-4 font-semibold {{ ($r['val'] !== null && $r['val'] < 75) ? 'text-amber-400' : 'text-white' }}">
                                    {{ $r['val'] !== null ? number_format((float)$r['val'], 1, ',', '.') . '%' : 'Belum dinilai' }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <!-- Right: Total KPI Card & Kategori Skor -->
            <div class="lg:col-span-4 space-y-4">
                <div class="bg-[#192748] border border-[#2a3d69] p-5 rounded-2xl">
                    <div class="text-xs font-semibold text-slate-400">Total KPI &middot; {{ $batchNo }}</div>
                    <div class="text-4xl font-extrabold text-white mt-1">
                        {{ $kpi ? number_format((float)$kpi->skor_total, 1, ',', '.') . '%' : '--' }}
                    </div>
                    <div class="text-xs {{ ($kpi?->kategori_kpi === 'Baik') ? 'text-emerald-400' : (($kpi?->kategori_kpi === 'Prioritas perbaikan') ? 'text-rose-400' : 'text-amber-300') }} mt-1 font-semibold">
                        {{ $kpi?->kategori_kpi ?? 'Belum tersedia' }}
                    </div>
                </div>

                <div class="bg-[#192748] border border-[#2a3d69] p-5 rounded-2xl space-y-2">
                    <div class="text-xs font-bold text-white">Kategori skor</div>
                    <div class="text-[11px] text-slate-300">
                        &ge;90%: Baik &bull; 75&ndash;&lt;90%: Perlu perbaikan &bull; &lt;75%: Prioritas perbaikan.
                    </div>
                    <p class="text-[11px] text-slate-400 leading-relaxed pt-1">
                        Skor total adalah agregasi kinerja operasional batch, dan tidak menggantikan verifikasi keamanan pangan per bahan.
                    </p>
                </div>
            </div>
        </div>

        <!-- Warning Bahan Kedaluwarsa Terpisah (FR-026) -->
        @if($kpi && ($kpi->is_warning_kedaluwarsa || ($kpi->skor_sx !== null && $kpi->skor_sx < 100)))
            <div class="p-4 bg-[#192748] border border-rose-900/60 rounded-xl space-y-1">
                <h4 class="text-xs font-bold text-rose-400">PERINGATAN TERPISAH &middot; Pemeriksaan Kedaluwarsa Bahan (Sx &lt; 100)</h4>
                <p class="text-[11px] text-slate-300 leading-relaxed">
                    Terdeteksi bahan terkait menu atau gudang batch ini yang mendekati/melewati masa kedaluwarsa (Skor Sx: {{ number_format((float)$kpi->skor_sx, 1, ',', '.') }}%). Wajib ditinjau ulang petugas dapur meskipun total KPI tergolong tinggi.
                </p>
            </div>
        @endif
    </div>

    <!-- Section B: Prediksi pra-distribusi · Random Forest -->
    <div class="bg-[#131f38] border border-[#1f3056] p-6 rounded-2xl space-y-4">
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-2">
            <div>
                <h2 class="text-sm font-bold text-white">B. Prediksi pra-distribusi &middot; Random Forest</h2>
                <p class="text-xs text-slate-400 mt-0.5">
                    @if($waktuMatang)
                        Waktu matang acuan: {{ \Carbon\Carbon::parse($waktuMatang)->format('H.i') }} WITA &rarr; Batas konsumsi aman 4 jam: <strong class="text-white">{{ $batas4Jam->format('H.i') }} WITA</strong>.
                    @else
                        Waktu makanan matang belum dicatat pada Pencatatan Tahap.
                    @endif
                </p>
            </div>
            @if($kpi && $kpi->kelas_prediksi)
                <span class="badge {{ $kpi->kelas_prediksi === 'Layak' ? 'badge-success' : 'badge-danger' }}">
                    Prediksi Utama: {{ $kpi->kelas_prediksi }} (Risiko {{ number_format((float)$kpi->probabilitas_risiko, 1, ',', '.') }}%)
                </span>
            @endif
        </div>

        <div class="overflow-x-auto rounded-xl border border-[#1f3056]">
            <table class="w-full text-left text-xs border-collapse">
                <thead>
                    <tr class="bg-[#182645] text-slate-300 font-semibold border-b border-[#1f3056]">
                        <th class="py-3 px-4">Titik antar</th>
                        <th class="py-3 px-4">Prediksi kelayakan waktu</th>
                        <th class="py-3 px-4">Estimasi risiko telat</th>
                        <th class="py-3 px-4">Konteks tempuh &amp; sisa waktu</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-[#1f3056]/60 text-slate-300">
                    @forelse($titikEvaluasi as $te)
                        @php
                            $kel = $te['kelompok'];
                            $ev = $te['evaluasi'];
                        @endphp
                        <tr class="hover:bg-[#182747]/40">
                            <td class="py-3.5 px-4 font-semibold text-white">
                                {{ $kel->nama_kelompok }} &middot; {{ $kel->porsi_terkemas ?: $kel->jumlah_penerima }} porsi
                            </td>
                            <td class="py-3.5 px-4 font-semibold {{ $ev['status_risiko'] === 'Aman' ? 'text-emerald-400' : ($ev['status_risiko'] === 'Waspada' ? 'text-amber-400' : ($ev['status_risiko'] === 'Risiko tinggi' ? 'text-rose-400' : 'text-slate-400')) }}">
                                {{ $ev['status_risiko'] }} ({{ $ev['kelas'] }})
                            </td>
                            <td class="py-3.5 px-4 font-semibold text-white">
                                {{ $ev['probabilitas'] !== null ? number_format((float)$ev['probabilitas'], 1, ',', '.') . '%' : '--' }}
                            </td>
                            <td class="py-3.5 px-4">
                                Estimasi tempuh {{ $kel->estimasi_waktu_tempuh_menit }} menit
                                @if($ev['sisa_menit_aman'] !== null)
                                    &bull; Sisa jendela aman: {{ $ev['sisa_menit_aman'] }} menit
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="py-6 text-center text-slate-400">Belum ada kelompok pengantaran untuk dievaluasi.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- 2 Boxes below -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 pt-1">
            <div class="bg-[#192748] border border-[#2a3d69] p-4 rounded-xl space-y-1">
                <h4 class="text-xs font-bold text-white">Pendukung keputusan; keputusan akhir pada petugas dapur</h4>
                <p class="text-[11px] text-slate-300 leading-relaxed">
                    {{ $kpi?->keterangan_keputusan ?? 'Hasil rekomendasi merupakan bahan pertimbangan. Keputusan akhir kelayakan konsumsi makanan berada pada petugas yang berwenang.' }}
                </p>
            </div>

            <div class="bg-[#192748] border border-[#2a3d69] p-4 rounded-xl space-y-1">
                <h4 class="text-xs font-bold text-white">Transparansi model &amp; ambang batas</h4>
                <p class="text-[11px] text-slate-300 leading-relaxed">
                    Sistem menggunakan model Random Forest, dan otomatis beralih ke aturan ambang batas 4 jam (240 menit pasca matang) apabila layanan Python tidak merespons &le; 5 detik.
                </p>
            </div>
        </div>
    </div>

    <!-- Bottom Card: Riwayat KPI -->
    <div class="bg-[#131f38] border border-[#1f3056] p-6 rounded-2xl space-y-4">
        <h2 class="text-sm font-bold text-white">Riwayat KPI &amp; Prediksi Seluruh Batch</h2>

        <div class="overflow-x-auto rounded-xl border border-[#1f3056]">
            <table class="w-full text-left text-xs border-collapse">
                <thead>
                    <tr class="bg-[#182645] text-slate-300 font-semibold border-b border-[#1f3056]">
                        <th class="py-3 px-4">Tanggal / Menu</th>
                        <th class="py-3 px-4">Batch</th>
                        <th class="py-3 px-4">Total KPI</th>
                        <th class="py-3 px-4">Prediksi</th>
                        <th class="py-3 px-4">Kategori</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-[#1f3056]/60 text-slate-300">
                    @forelse($kpiHistory as $hist)
                        <tr class="hover:bg-[#182747]/40 cursor-pointer" onclick="window.location='{{ route('admin.kpi.index', ['menu_id' => $hist->menu_id, 'batch_no' => $hist->batch_no]) }}'">
                            <td class="py-3.5 px-4 font-semibold text-white">
                                {{ optional($hist->tanggal)->format('d M Y') }} &middot; {{ $hist->menu?->nama_menu ?? '-' }}
                            </td>
                            <td class="py-3.5 px-4">{{ $hist->batch_no }}</td>
                            <td class="py-3.5 px-4 font-semibold {{ ($hist->skor_total ?? 0) >= 90 ? 'text-emerald-400' : (($hist->skor_total ?? 0) >= 75 ? 'text-amber-400' : 'text-rose-400') }}">
                                {{ $hist->skor_total !== null ? number_format((float)$hist->skor_total, 1, ',', '.') . '%' : '--' }}
                            </td>
                            <td class="py-3.5 px-4">{{ $hist->kelas_prediksi ?? '--' }}</td>
                            <td class="py-3.5 px-4">{{ $hist->kategori_kpi ?? 'Menunggu' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="py-6 text-center text-slate-400">Belum ada riwayat perhitungan KPI tersimpan.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
