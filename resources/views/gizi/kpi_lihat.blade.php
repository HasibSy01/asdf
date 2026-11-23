@extends('layouts.app')

@section('title', 'KPI dan Prediksi Risiko (Ahli Gizi)')

@section('content')
@php
    $alurOlah = $alurs->get('pengolahan');
    $alurKemas = $alurs->get('pengemasan');
    $alurDist = $alurs->get('pengantaran');
    $waktuMatang = $alurOlah?->waktu_matang_terakhir ?? $alurKemas?->waktu_matang_terakhir ?? $alurOlah?->waktu_selesai_aktual;
    $batas4Jam = $waktuMatang ? \Carbon\Carbon::parse($waktuMatang)->addHours(4) : null;
    $porsiTerkemas = $alurKemas?->porsi_terkemas ?? ($alurOlah?->porsi_terkemas ?? 0);
    $porsiRencana = $activeMenu?->jumlah_porsi_rencana ?: 300;
@endphp
<div class="space-y-6">
    <!-- Header -->
    <div>
        <h1 class="text-2xl font-bold text-white tracking-tight">KPI dan Prediksi Risiko</h1>
        <p class="text-xs text-slate-400 mt-1">
            Evaluasi kinerja operasional dapur dan risiko batas konsumsi. Hanya baca untuk Ahli Gizi.
        </p>
    </div>

    <!-- Filter Selector -->
    <form method="GET" action="{{ route('gizi.kpi.lihat') }}" class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div>
            <select name="menu_id" onchange="this.form.submit()" class="w-full px-4 py-3 bg-[#131f38] border border-[#1f3056] rounded-xl text-xs text-white focus:outline-none">
                @foreach($menus as $m)
                    <option value="{{ $m->id }}" {{ $activeMenu && $activeMenu->id === $m->id ? 'selected' : '' }}>
                        {{ $m->nama_menu }} &middot; {{ optional($m->tanggal_penyajian)->format('d M Y') }}
                    </option>
                @endforeach
            </select>
        </div>
        <div>
            <select name="batch_no" onchange="this.form.submit()" class="w-full px-4 py-3 bg-[#131f38] border border-[#1f3056] rounded-xl text-xs text-white focus:outline-none">
                @foreach(['Tahap 1', 'Tahap 2', 'Tahap 3'] as $bOpt)
                    <option value="{{ $bOpt }}" {{ $batchNo === $bOpt ? 'selected' : '' }}>{{ $bOpt }}</option>
                @endforeach
            </select>
        </div>
        <div class="px-4 py-3 bg-[#131f38] border border-[#1f3056] rounded-xl text-xs flex items-center gap-2">
            <span class="text-slate-400">Status distribusi</span>
            <span class="font-bold text-white">{{ $alurDist && $alurDist->waktu_selesai_aktual ? 'Lengkap' : 'Berjalan / Belum selesai' }}</span>
        </div>
    </form>

    <!-- 3 Stat Cards on Top -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
        <div class="bg-[#131f38] border border-[#1f3056] p-6 rounded-2xl">
            <div class="text-xs font-semibold text-slate-400">Total KPI ({{ $batchNo }})</div>
            <div class="text-4xl font-extrabold text-white mt-2">
                {{ $kpi && $kpi->skor_total !== null ? number_format((float)$kpi->skor_total, 1, ',', '.') . '%' : '--' }}
            </div>
            <div class="text-[11px] text-slate-400 mt-2">{{ $kpi?->kategori_kpi ?? 'Belum dihitung final' }}</div>
        </div>

        <div class="bg-[#131f38] border border-[#1f3056] p-6 rounded-2xl">
            <div class="text-xs font-semibold text-slate-400">Kapasitas porsi</div>
            <div class="text-4xl font-extrabold text-white mt-2">{{ $porsiTerkemas }} / {{ $porsiRencana }}</div>
            <div class="text-[11px] text-slate-400 mt-2">
                {{ $porsiRencana > 0 ? number_format(($porsiTerkemas / $porsiRencana) * 100, 1, ',', '.') : 0 }}% ketercapaian rencana batch
            </div>
        </div>

        <div class="bg-[#131f38] border border-[#1f3056] p-6 rounded-2xl">
            <div class="text-xs font-semibold text-slate-400">Batas konsumsi aman (4 jam)</div>
            <div class="text-4xl font-extrabold text-white mt-2">{{ $batas4Jam ? $batas4Jam->format('H.i') : '--' }}</div>
            <div class="text-[11px] text-slate-400 mt-2">
                {{ $waktuMatang ? '4 jam sejak matang ' . \Carbon\Carbon::parse($waktuMatang)->format('H.i') : 'Menunggu pencatatan waktu matang' }}
            </div>
        </div>
    </div>

    <!-- Alert Box: Peringatan keamanan pangan -->
    @if($kpi && ($kpi->is_warning_kedaluwarsa || ($kpi->skor_sx !== null && $kpi->skor_sx < 100)))
        <div class="p-4 bg-[#131f38] border border-amber-900/60 rounded-2xl space-y-1">
            <h4 class="text-xs font-bold text-amber-300">Peringatan keamanan pangan &middot; Sx {{ number_format((float)$kpi->skor_sx, 1, ',', '.') }}% &mdash; bahan kedaluwarsa perlu perhatian</h4>
            <p class="text-[11px] text-slate-300 leading-relaxed">
                Terdapat bahan terkait batch/gudang yang mendekati atau melewati masa kedaluwarsa. Peringatan tetap ditampilkan terpisah meskipun skor KPI keseluruhan tinggi.
            </p>
        </div>
    @endif

    <!-- 2 Columns Middle: A. KPI & B. Prediksi pra-distribusi -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 items-start">
        <!-- Left: A. KPI -->
        <div class="bg-[#131f38] border border-[#1f3056] p-6 rounded-2xl space-y-4">
            <div>
                <h2 class="text-sm font-bold text-white">A. Rincian 7 Indikator KPI ({{ $batchNo }})</h2>
                <p class="text-xs text-slate-400 mt-0.5">Data operasional hasil pencatatan Admin SPPG</p>
            </div>

            <div class="overflow-x-auto rounded-xl border border-[#1f3056]">
                <table class="w-full text-left text-xs border-collapse">
                    <thead>
                        <tr class="bg-[#182645] text-slate-300 font-semibold border-b border-[#1f3056]">
                            <th class="py-3 px-4">Kode</th>
                            <th class="py-3 px-4">Indikator</th>
                            <th class="py-3 px-4">Skor</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-[#1f3056]/60 text-slate-300">
                        @php
                            $rows = [
                                ['kode' => 'Sr', 'nama' => 'Penerimaan bahan', 'val' => $kpi?->skor_sr],
                                ['kode' => 'Sx', 'nama' => 'Kedaluwarsa bahan', 'val' => $kpi?->skor_sx],
                                ['kode' => 'Sp', 'nama' => 'Mulai pengolahan', 'val' => $kpi?->skor_sp],
                                ['kode' => 'Sq', 'nama' => 'Pemorsian / pengemasan', 'val' => $kpi?->skor_sq],
                                ['kode' => 'Sk', 'nama' => 'Kapasitas porsi', 'val' => $kpi?->skor_sk],
                                ['kode' => 'Sw', 'nama' => 'Waktu tiba pengantaran', 'val' => $kpi?->skor_sw],
                                ['kode' => 'Sb', 'nama' => 'Batas konsumsi 4 jam', 'val' => $kpi?->skor_sb],
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

        <!-- Right: B. Prediksi pra-distribusi -->
        <div class="bg-[#131f38] border border-[#1f3056] p-6 rounded-2xl space-y-4">
            <div>
                <h2 class="text-sm font-bold text-white">B. Prediksi pra-distribusi per titik</h2>
                <p class="text-xs text-slate-400 mt-0.5">Random Forest / Ambang batas &bull; Setelah kemas, sebelum berangkat</p>
            </div>

            @forelse($titikEvaluasi as $te)
                @php
                    $kel = $te['kelompok'];
                    $ev = $te['evaluasi'];
                @endphp
                <div class="bg-[#192748] border border-[#2a3d69] p-4 rounded-xl space-y-1">
                    <div class="text-xs text-slate-400">{{ $kel->nama_kelompok }} &middot; {{ $kel->porsi_terkemas ?: $kel->jumlah_penerima }} porsi</div>
                    <div class="text-sm font-bold {{ $ev['status_risiko'] === 'Aman' ? 'text-emerald-400' : ($ev['status_risiko'] === 'Waspada' ? 'text-amber-400' : ($ev['status_risiko'] === 'Risiko tinggi' ? 'text-rose-400' : 'text-slate-300')) }}">
                        {{ $ev['status_risiko'] }} ({{ $ev['kelas'] }})
                    </div>
                    <div class="text-[11px] text-slate-300">
                        Estimasi risiko telat: {{ $ev['probabilitas'] !== null ? number_format((float)$ev['probabilitas'], 1, ',', '.') . '%' : '--' }} &bull; Estimasi tempuh {{ $kel->estimasi_waktu_tempuh_menit }} menit
                    </div>
                </div>
            @empty
                <div class="p-4 bg-[#192748] border border-[#2a3d69] rounded-xl text-xs text-slate-400">
                    Prediksi belum tersedia karena data kelompok atau tahap kemas belum lengkap.
                </div>
            @endforelse

            <div class="space-y-1 pt-2 text-[11px] text-slate-400 leading-relaxed">
                <div>Kategori KPI: &ge;90% Baik &bull; 75&ndash;&lt;90% Perlu perbaikan &bull; &lt;75% Prioritas perbaikan.</div>
                <div>Prediksi merupakan pendukung keputusan; keputusan akhir tetap pada petugas dapur berwenang.</div>
            </div>
        </div>
    </div>
</div>
@endsection
