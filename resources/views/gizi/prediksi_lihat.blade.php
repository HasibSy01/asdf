@extends('layouts.app')

@section('title', 'Prediksi Risiko Random Forest (Ahli Gizi)')

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
            <h1 class="text-2xl font-bold text-white tracking-tight">Prediksi Risiko Random Forest</h1>
            <p class="text-xs text-slate-400 mt-1">
                Pemantauan risiko keterlambatan distribusi terhadap batas konsumsi aman 4 jam per batch. Mode hanya baca untuk Ahli Gizi.
            </p>
        </div>
        <div>
            <a href="{{ route('gizi.kpi.lihat') }}" class="btn btn-secondary text-xs">
                Lihat KPI Mingguan
            </a>
        </div>
    </div>

    <!-- Filter Selector -->
    <form method="GET" action="{{ route('gizi.prediksi.lihat') }}" class="grid grid-cols-1 md:grid-cols-3 gap-4">
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
        <div class="px-4 py-3 bg-[#131f38] border border-[#1f3056] rounded-xl text-xs flex items-center justify-between gap-2">
            <span class="text-slate-400">Status distribusi</span>
            <span class="font-bold text-white">{{ $alurDist && $alurDist->waktu_selesai_aktual ? 'Lengkap' : 'Pra-distribusi / Berjalan' }}</span>
        </div>
    </form>

    <!-- 3 Stat Cards -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
        <div class="bg-[#131f38] border border-[#1f3056] p-6 rounded-2xl">
            <div class="text-xs font-semibold text-slate-400">Kelas Prediksi ({{ $batchNo }})</div>
            <div class="text-4xl font-extrabold text-white mt-2">{{ $prediksi?->kelas_prediksi ?? 'Menunggu' }}</div>
            <div class="text-[11px] text-slate-400 mt-2">{{ $activeMenu?->nama_menu ?? '-' }}</div>
        </div>

        <div class="bg-[#131f38] border border-[#1f3056] p-6 rounded-2xl">
            <div class="text-xs font-semibold text-slate-400">Estimasi Risiko Telat</div>
            <div class="text-4xl font-extrabold text-white mt-2">
                {{ $prediksi && $prediksi->probabilitas_risiko !== null ? number_format((float)$prediksi->probabilitas_risiko, 1, ',', '.') . '%' : '--' }}
            </div>
            <div class="text-[11px] text-slate-400 mt-2">Inferensi Random Forest / ambang batas cadangan</div>
        </div>

        <div class="bg-[#131f38] border border-[#1f3056] p-6 rounded-2xl">
            <div class="text-xs font-semibold text-slate-400">Batas Konsumsi Aman (4 Jam)</div>
            <div class="text-4xl font-extrabold text-white mt-2">{{ $batas4Jam ? $batas4Jam->format('H.i') : '--' }}</div>
            <div class="text-[11px] text-slate-400 mt-2">
                {{ $waktuMatang ? '4 jam sejak matang ' . \Carbon\Carbon::parse($waktuMatang)->format('H.i') . ' WITA' : 'Menunggu pencatatan waktu matang' }}
            </div>
        </div>
    </div>

    <!-- Prediksi per Titik -->
    <div class="bg-[#131f38] border border-[#1f3056] p-6 rounded-2xl space-y-4">
        <div>
            <h2 class="text-sm font-bold text-white">Evaluasi Pra-Distribusi per Titik Pengantaran</h2>
            <p class="text-xs text-slate-400 mt-0.5">Random Forest / Ambang batas &bull; Setelah kemas selesai, sebelum armada berangkat</p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
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
                <div class="p-4 bg-[#192748] border border-[#2a3d69] rounded-xl text-xs text-slate-400 col-span-full">
                    Prediksi belum tersedia karena data kelompok atau tahap kemas belum lengkap.
                </div>
            @endforelse
        </div>

        <div class="pt-2 text-[11px] text-slate-400 leading-relaxed">
            Prediksi merupakan pendukung keputusan; keputusan akhir kelayakan konsumsi tetap berada pada petugas dapur berwenang.
        </div>
    </div>
</div>
@endsection

