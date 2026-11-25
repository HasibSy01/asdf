@extends('layouts.app')

@section('title', 'KPI Mingguan')

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-white tracking-tight">KPI Mingguan</h1>
            <p class="text-xs text-slate-400 mt-1">
                Evaluasi kinerja operasional dapur dilakukan cukup <strong>1 kali dalam seminggu</strong> (akumulasi periode Senin s/d Minggu), terpisah dari modul Prediksi Risiko Random Forest.
            </p>
        </div>

        <div class="flex flex-wrap items-center gap-2.5">
            <a href="{{ route('admin.prediksi.index') }}" class="btn btn-secondary text-xs">
                Buka Prediksi Random Forest &rarr;
            </a>
            <form method="POST" action="{{ route('admin.kpi.hitung') }}">
                @csrf
                <input type="hidden" name="week_start" value="{{ $rekapMinggu['week_start'] }}">
                <button type="submit" class="px-4 py-2.5 bg-[#192748] hover:bg-[#20335e] border border-[#2a3d69] text-xs font-semibold rounded-xl text-white transition cursor-pointer">
                    Hitung / Rekap KPI Minggu Ini
                </button>
            </form>
        </div>
    </div>

    <!-- Filter Minggu Evaluasi -->
    <form method="GET" action="{{ route('admin.kpi.index') }}" class="grid grid-cols-1 md:grid-cols-3 gap-4 items-end">
        <div>
            <label class="block text-xs font-semibold text-slate-300 mb-1.5">Periode Minggu Evaluasi (Senin &ndash; Minggu)</label>
            <select name="week_start" onchange="this.form.submit()" class="w-full px-3.5 py-2.5 bg-[#192748] border border-[#2a3d69] rounded-xl text-xs text-white focus:outline-none">
                @foreach($daftarMinggu as $wm)
                    <option value="{{ $wm['week_start'] }}" {{ $rekapMinggu['week_start'] === $wm['week_start'] ? 'selected' : '' }}>
                        {{ $wm['label'] }}
                    </option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="block text-xs font-semibold text-slate-300 mb-1.5">Frekuensi Evaluasi</label>
            <input type="text" readonly value="1x Seminggu (Rekap Mingguan)"
                   class="w-full px-3.5 py-2.5 bg-[#192748] border border-[#2a3d69] rounded-xl text-xs text-slate-300 focus:outline-none">
        </div>
        <div>
            <label class="block text-xs font-semibold text-slate-300 mb-1.5">Cakupan Batch Terevaluasi</label>
            <input type="text" readonly value="{{ $rekapMinggu['jumlah_batch'] }} batch produksi tercatat"
                   class="w-full px-3.5 py-2.5 bg-[#192748] border border-[#2a3d69] rounded-xl text-xs text-slate-300 focus:outline-none">
        </div>
    </form>

    <!-- Notice Card -->
    <div class="p-4 bg-[#131f38] border border-[#1f3056] rounded-2xl space-y-1">
        <h3 class="text-xs font-bold text-white">Siklus Evaluasi KPI Mingguan (1x Seminggu)</h3>
        <p class="text-[11px] text-slate-300 leading-relaxed">
            Skor KPI tidak lagi dicampur dengan inferensi harian Random Forest. Petugas cukup menekan tombol <strong>Hitung / Rekap KPI Minggu Ini</strong> satu kali setiap akhir pekan operasional untuk merekap rata-rata 7 indikator kinerja selama satu minggu penuh.
        </p>
    </div>

    @if($rekapMinggu['jumlah_batch'] === 0)
        <div class="p-5 bg-amber-950/30 border border-amber-700/60 rounded-2xl space-y-1">
            <h3 class="text-sm font-bold text-amber-300">Rekap KPI Minggu Ini Belum Dihitung</h3>
            <p class="text-xs text-amber-200 leading-relaxed">
                Belum ada rekap KPI tersimpan untuk periode <strong>{{ $rekapMinggu['label_minggu'] }}</strong>. Jika pencatatan tahapan minggu ini sudah berjalan, klik tombol <strong>Hitung / Rekap KPI Minggu Ini</strong> di kanan atas.
            </p>
        </div>
    @endif

    <!-- Evaluasi 7 Indikator KPI Mingguan -->
    <div class="bg-[#131f38] border border-[#1f3056] p-6 rounded-2xl space-y-5">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 border-b border-[#1f3056] pb-3">
            <div>
                <h2 class="text-sm font-bold text-white">Rekap 7 Indikator KPI &middot; Periode {{ $rekapMinggu['label_minggu'] }}</h2>
                <div class="text-xs text-slate-400 mt-0.5">
                    @if($rekapMinggu['terakhir_dihitung'])
                        Terakhir direkap: {{ \Carbon\Carbon::parse($rekapMinggu['terakhir_dihitung'])->translatedFormat('d M Y, H.i') }} WITA ({{ $rekapMinggu['jumlah_batch'] }} batch)
                    @else
                        Menunggu rekapitulasi mingguan
                    @endif
                </div>
            </div>
            <div>
                <span class="badge badge-info">Evaluasi Mingguan</span>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">
            <!-- Left: Table of 7 Indicators -->
            <div class="lg:col-span-8 overflow-x-auto rounded-xl border border-[#1f3056]">
                <table class="w-full text-left text-xs border-collapse">
                    <thead>
                        <tr class="bg-[#182645] text-slate-300 font-semibold border-b border-[#1f3056]">
                            <th class="py-3 px-4">Kode</th>
                            <th class="py-3 px-4">Indikator &amp; Bobot</th>
                            <th class="py-3 px-4">Rata-rata Minggu Ini</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-[#1f3056]/60 text-slate-300">
                        @php
                            $rows = [
                                ['kode' => 'Sr', 'nama' => 'Ketepatan penerimaan bahan (10%)', 'val' => $rekapMinggu['skor_sr']],
                                ['kode' => 'Sx', 'nama' => 'Kepatuhan masa kedaluwarsa bahan (15%)', 'val' => $rekapMinggu['skor_sx']],
                                ['kode' => 'Sp', 'nama' => 'Ketepatan mulai pengolahan (10%)', 'val' => $rekapMinggu['skor_sp']],
                                ['kode' => 'Sq', 'nama' => 'Ketepatan pemorsian / pengemasan (15%)', 'val' => $rekapMinggu['skor_sq']],
                                ['kode' => 'Sk', 'nama' => 'Ketercapaian kapasitas porsi (25%)', 'val' => $rekapMinggu['skor_sk']],
                                ['kode' => 'Sw', 'nama' => 'Ketepatan waktu tiba pengantaran (15%)', 'val' => $rekapMinggu['skor_sw']],
                                ['kode' => 'Sb', 'nama' => 'Kepatuhan batas konsumsi 4 jam (10%)', 'val' => $rekapMinggu['skor_sb']],
                            ];
                        @endphp
                        @foreach($rows as $r)
                            <tr class="hover:bg-[#182747]/40">
                                <td class="py-3 px-4 font-bold text-white">{{ $r['kode'] }}</td>
                                <td class="py-3 px-4">{{ $r['nama'] }}</td>
                                <td class="py-3 px-4 font-semibold {{ ($r['val'] !== null && $r['val'] < 75) ? 'text-amber-400' : 'text-white' }}">
                                    {{ $r['val'] !== null ? number_format((float)$r['val'], 1, ',', '.') . '%' : 'Belum direkap' }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <!-- Right: Total KPI Mingguan -->
            <div class="lg:col-span-4 space-y-4">
                <div class="bg-[#192748] border border-[#2a3d69] p-5 rounded-2xl">
                    <div class="text-xs font-semibold text-slate-400">Skor KPI Mingguan</div>
                    <div class="text-4xl font-extrabold text-white mt-1">
                        {{ $rekapMinggu['skor_total'] !== null ? number_format((float)$rekapMinggu['skor_total'], 1, ',', '.') . '%' : '--' }}
                    </div>
                    <div class="text-xs {{ ($rekapMinggu['kategori_kpi'] === 'Baik') ? 'text-emerald-400' : (($rekapMinggu['kategori_kpi'] === 'Prioritas perbaikan') ? 'text-rose-400' : 'text-amber-300') }} mt-1 font-semibold">
                        {{ $rekapMinggu['kategori_kpi'] ?? 'Belum tersedia' }}
                    </div>
                </div>

                <div class="bg-[#192748] border border-[#2a3d69] p-5 rounded-2xl space-y-2">
                    <div class="text-xs font-bold text-white">Ambang Kategori Mingguan</div>
                    <div class="text-[11px] text-slate-300">
                        &ge;90%: Baik &bull; 75&ndash;&lt;90%: Perlu perbaikan &bull; &lt;75%: Prioritas perbaikan.
                    </div>
                    <p class="text-[11px] text-slate-400 leading-relaxed pt-1">
                        Rekap mingguan menggabungkan seluruh batch yang telah tercatat pada rentang Senin hingga Minggu.
                    </p>
                </div>
            </div>
        </div>

        @if($rekapMinggu['is_warning_kedaluwarsa'])
            <div class="p-4 bg-[#192748] border border-rose-900/60 rounded-xl space-y-1">
                <h4 class="text-xs font-bold text-rose-400">PERINGATAN TERPISAH &middot; Pemeriksaan Kedaluwarsa Bahan Minggu Ini (Sx &lt; 100)</h4>
                <p class="text-[11px] text-slate-300 leading-relaxed">
                    Terdapat batch pada minggu ini yang memakai atau memiliki catatan bahan mendekati/melewati masa kedaluwarsa (rata-rata Sx: {{ number_format((float)$rekapMinggu['skor_sx'], 1, ',', '.') }}%). Wajib ditinjau ulang petugas gudang meskipun skor KPI mingguan tinggi.
                </p>
            </div>
        @endif
    </div>

    <!-- Rincian Batch Penyusun Minggu Ini -->
    @if($rekapMinggu['batch_records']->isNotEmpty())
        <div class="bg-[#131f38] border border-[#1f3056] p-6 rounded-2xl space-y-4">
            <h2 class="text-sm font-bold text-white">Rincian Batch Produksi Penyusun Minggu Ini ({{ $rekapMinggu['label_minggu'] }})</h2>

            <div class="overflow-x-auto rounded-xl border border-[#1f3056]">
                <table class="w-full text-left text-xs border-collapse">
                    <thead>
                        <tr class="bg-[#182645] text-slate-300 font-semibold border-b border-[#1f3056]">
                            <th class="py-3 px-4">Tanggal &amp; Menu</th>
                            <th class="py-3 px-4">Batch</th>
                            <th class="py-3 px-4">Skor Total</th>
                            <th class="py-3 px-4">Kategori</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-[#1f3056]/60 text-slate-300">
                        @foreach($rekapMinggu['batch_records'] as $rec)
                            <tr class="hover:bg-[#182747]/40">
                                <td class="py-3.5 px-4 font-semibold text-white">
                                    {{ optional($rec->tanggal)->format('d M Y') }} &middot; {{ $rec->menu?->nama_menu ?? '-' }}
                                </td>
                                <td class="py-3.5 px-4">{{ $rec->batch_no }}</td>
                                <td class="py-3.5 px-4 font-semibold text-white">
                                    {{ number_format((float)$rec->skor_total, 1, ',', '.') }}%
                                </td>
                                <td class="py-3.5 px-4">{{ $rec->kategori_kpi }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    <!-- Riwayat KPI per Minggu -->
    <div class="bg-[#131f38] border border-[#1f3056] p-6 rounded-2xl space-y-4">
        <h2 class="text-sm font-bold text-white">Riwayat Evaluasi KPI Mingguan (6 Minggu Terakhir)</h2>

        <div class="overflow-x-auto rounded-xl border border-[#1f3056]">
            <table class="w-full text-left text-xs border-collapse">
                <thead>
                    <tr class="bg-[#182645] text-slate-300 font-semibold border-b border-[#1f3056]">
                        <th class="py-3 px-4">Periode Minggu</th>
                        <th class="py-3 px-4">Jumlah Batch</th>
                        <th class="py-3 px-4">Rata-rata KPI</th>
                        <th class="py-3 px-4">Kategori</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-[#1f3056]/60 text-slate-300">
                    @foreach($riwayatMingguan as $rw)
                        <tr class="hover:bg-[#182747]/40 cursor-pointer" onclick="window.location='{{ route('admin.kpi.index', ['week_start' => $rw['week_start']]) }}'">
                            <td class="py-3.5 px-4 font-semibold text-white">{{ $rw['label_minggu'] }}</td>
                            <td class="py-3.5 px-4">{{ $rw['jumlah_batch'] }} batch</td>
                            <td class="py-3.5 px-4 font-semibold {{ ($rw['skor_total'] ?? 0) >= 90 ? 'text-emerald-400' : (($rw['skor_total'] !== null && $rw['skor_total'] >= 75) ? 'text-amber-400' : 'text-slate-400') }}">
                                {{ $rw['skor_total'] !== null ? number_format((float)$rw['skor_total'], 1, ',', '.') . '%' : 'Belum direkap' }}
                            </td>
                            <td class="py-3.5 px-4">{{ $rw['kategori_kpi'] ?? 'Menunggu rekap' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
