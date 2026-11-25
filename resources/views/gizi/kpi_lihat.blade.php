@extends('layouts.app')

@section('title', 'KPI Mingguan (Ahli Gizi)')

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-white tracking-tight">KPI Mingguan</h1>
            <p class="text-xs text-slate-400 mt-1">
                Evaluasi kinerja operasional dapur per minggu (1x seminggu). Mode hanya baca untuk Ahli Gizi.
            </p>
        </div>
        <div>
            <a href="{{ route('gizi.prediksi.lihat') }}" class="btn btn-secondary text-xs">
                Buka Prediksi Random Forest &rarr;
            </a>
        </div>
    </div>

    <!-- Filter Minggu Evaluasi -->
    <form method="GET" action="{{ route('gizi.kpi.lihat') }}" class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div>
            <select name="week_start" onchange="this.form.submit()" class="w-full px-4 py-3 bg-[#131f38] border border-[#1f3056] rounded-xl text-xs text-white focus:outline-none">
                @foreach($daftarMinggu as $wm)
                    <option value="{{ $wm['week_start'] }}" {{ $rekapMinggu['week_start'] === $wm['week_start'] ? 'selected' : '' }}>
                        {{ $wm['label'] }}
                    </option>
                @endforeach
            </select>
        </div>
        <div class="px-4 py-3 bg-[#131f38] border border-[#1f3056] rounded-xl text-xs flex items-center justify-between gap-2">
            <span class="text-slate-400">Siklus evaluasi</span>
            <span class="font-bold text-white">1x Seminggu</span>
        </div>
        <div class="px-4 py-3 bg-[#131f38] border border-[#1f3056] rounded-xl text-xs flex items-center justify-between gap-2">
            <span class="text-slate-400">Cakupan minggu ini</span>
            <span class="font-bold text-white">{{ $rekapMinggu['jumlah_batch'] }} batch produksi</span>
        </div>
    </form>

    <!-- 3 Stat Cards -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
        <div class="bg-[#131f38] border border-[#1f3056] p-6 rounded-2xl">
            <div class="text-xs font-semibold text-slate-400">Skor KPI Mingguan</div>
            <div class="text-4xl font-extrabold text-white mt-2">
                {{ $rekapMinggu['skor_total'] !== null ? number_format((float)$rekapMinggu['skor_total'], 1, ',', '.') . '%' : '--' }}
            </div>
            <div class="text-[11px] text-slate-400 mt-2">{{ $rekapMinggu['kategori_kpi'] ?? 'Belum direkap Admin' }}</div>
        </div>

        <div class="bg-[#131f38] border border-[#1f3056] p-6 rounded-2xl">
            <div class="text-xs font-semibold text-slate-400">Rata-rata Kapasitas Porsi (Sk)</div>
            <div class="text-4xl font-extrabold text-white mt-2">
                {{ $rekapMinggu['skor_sk'] !== null ? number_format((float)$rekapMinggu['skor_sk'], 1, ',', '.') . '%' : '--' }}
            </div>
            <div class="text-[11px] text-slate-400 mt-2">Ketercapaian porsi selama {{ $rekapMinggu['label_minggu'] }}</div>
        </div>

        <div class="bg-[#131f38] border border-[#1f3056] p-6 rounded-2xl">
            <div class="text-xs font-semibold text-slate-400">Kepatuhan Masa Simpan (Sx)</div>
            <div class="text-4xl font-extrabold text-white mt-2">
                {{ $rekapMinggu['skor_sx'] !== null ? number_format((float)$rekapMinggu['skor_sx'], 1, ',', '.') . '%' : '--' }}
            </div>
            <div class="text-[11px] text-slate-400 mt-2">{{ $rekapMinggu['is_warning_kedaluwarsa'] ? 'Perlu perhatian bahan kedaluwarsa' : 'Bahan terpantau aman' }}</div>
        </div>
    </div>

    @if($rekapMinggu['is_warning_kedaluwarsa'])
        <div class="p-4 bg-[#131f38] border border-amber-900/60 rounded-2xl space-y-1">
            <h4 class="text-xs font-bold text-amber-300">Peringatan keamanan pangan mingguan &middot; Sx {{ number_format((float)$rekapMinggu['skor_sx'], 1, ',', '.') }}%</h4>
            <p class="text-[11px] text-slate-300 leading-relaxed">
                Terdapat batch pada minggu ini yang memiliki catatan bahan mendekati atau melewati masa kedaluwarsa.
            </p>
        </div>
    @endif

    <!-- Tabel 7 Indikator & Riwayat Mingguan -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 items-start">
        <div class="bg-[#131f38] border border-[#1f3056] p-6 rounded-2xl space-y-4">
            <div>
                <h2 class="text-sm font-bold text-white">Rincian 7 Indikator KPI ({{ $rekapMinggu['label_minggu'] }})</h2>
                <p class="text-xs text-slate-400 mt-0.5">Rata-rata mingguan hasil rekapitulasi Admin SPPG</p>
            </div>

            <div class="overflow-x-auto rounded-xl border border-[#1f3056]">
                <table class="w-full text-left text-xs border-collapse">
                    <thead>
                        <tr class="bg-[#182645] text-slate-300 font-semibold border-b border-[#1f3056]">
                            <th class="py-3 px-4">Kode</th>
                            <th class="py-3 px-4">Indikator</th>
                            <th class="py-3 px-4">Rata-rata</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-[#1f3056]/60 text-slate-300">
                        @php
                            $rows = [
                                ['kode' => 'Sr', 'nama' => 'Penerimaan bahan', 'val' => $rekapMinggu['skor_sr']],
                                ['kode' => 'Sx', 'nama' => 'Kedaluwarsa bahan', 'val' => $rekapMinggu['skor_sx']],
                                ['kode' => 'Sp', 'nama' => 'Mulai pengolahan', 'val' => $rekapMinggu['skor_sp']],
                                ['kode' => 'Sq', 'nama' => 'Pemorsian / pengemasan', 'val' => $rekapMinggu['skor_sq']],
                                ['kode' => 'Sk', 'nama' => 'Kapasitas porsi', 'val' => $rekapMinggu['skor_sk']],
                                ['kode' => 'Sw', 'nama' => 'Waktu tiba pengantaran', 'val' => $rekapMinggu['skor_sw']],
                                ['kode' => 'Sb', 'nama' => 'Batas konsumsi 4 jam', 'val' => $rekapMinggu['skor_sb']],
                            ];
                        @endphp
                        @foreach($rows as $r)
                            <tr class="hover:bg-[#182747]/40">
                                <td class="py-3 px-4 font-bold text-white">{{ $r['kode'] }}</td>
                                <td class="py-3 px-4">{{ $r['nama'] }}</td>
                                <td class="py-3 px-4 font-semibold text-white">
                                    {{ $r['val'] !== null ? number_format((float)$r['val'], 1, ',', '.') . '%' : '--' }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <div class="bg-[#131f38] border border-[#1f3056] p-6 rounded-2xl space-y-4">
            <div>
                <h2 class="text-sm font-bold text-white">Riwayat KPI 6 Minggu Terakhir</h2>
                <p class="text-xs text-slate-400 mt-0.5">Klik baris minggu untuk meninjau rincian indikator</p>
            </div>

            <div class="overflow-x-auto rounded-xl border border-[#1f3056]">
                <table class="w-full text-left text-xs border-collapse">
                    <thead>
                        <tr class="bg-[#182645] text-slate-300 font-semibold border-b border-[#1f3056]">
                            <th class="py-3 px-4">Periode</th>
                            <th class="py-3 px-4">Batch</th>
                            <th class="py-3 px-4">Skor KPI</th>
                            <th class="py-3 px-4">Kategori</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-[#1f3056]/60 text-slate-300">
                        @foreach($riwayatMingguan as $rw)
                            <tr class="hover:bg-[#182747]/40 cursor-pointer" onclick="window.location='{{ route('gizi.kpi.lihat', ['week_start' => $rw['week_start']]) }}'">
                                <td class="py-3 px-4 font-semibold text-white">{{ $rw['label_minggu'] }}</td>
                                <td class="py-3 px-4">{{ $rw['jumlah_batch'] }}</td>
                                <td class="py-3 px-4 font-semibold text-white">
                                    {{ $rw['skor_total'] !== null ? number_format((float)$rw['skor_total'], 1, ',', '.') . '%' : '--' }}
                                </td>
                                <td class="py-3 px-4">{{ $rw['kategori_kpi'] ?? 'Menunggu' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="pt-1 text-[11px] text-slate-400 leading-relaxed">
                Kategori KPI: &ge;90% Baik &bull; 75&ndash;&lt;90% Perlu perbaikan &bull; &lt;75% Prioritas perbaikan.
            </div>
        </div>
    </div>
</div>
@endsection
