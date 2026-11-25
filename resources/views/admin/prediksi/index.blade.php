@extends('layouts.app')

@section('title', 'Prediksi Risiko Random Forest')

@section('content')
@php
    $alurOlah = $alurs->get('pengolahan');
    $alurKemas = $alurs->get('pengemasan');
    $waktuMatang = $alurOlah?->waktu_matang_terakhir ?? $alurKemas?->waktu_matang_terakhir ?? $alurOlah?->waktu_selesai_aktual;
    $batas4Jam = $waktuMatang ? \Carbon\Carbon::parse($waktuMatang)->addHours(4) : null;
@endphp
<div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-white tracking-tight">Prediksi Risiko Random Forest</h1>
            <p class="text-xs text-slate-400 mt-1">
                Prediksi pra-distribusi per batch setelah pengemasan selesai untuk mengantisipasi keterlambatan melewati batas aman konsumsi 4 jam.
            </p>
        </div>

        <div class="flex flex-wrap items-center gap-2.5">
            <a href="{{ route('admin.kpi.index') }}" class="btn btn-secondary text-xs">
                Lihat KPI Mingguan
            </a>
            @if($activeMenu)
                <form method="POST" action="{{ route('admin.prediksi.hitung') }}">
                    @csrf
                    <input type="hidden" name="menu_id" value="{{ $activeMenu->id }}">
                    <input type="hidden" name="batch_no" value="{{ $batchNo }}">
                    <button type="submit" class="px-4 py-2.5 bg-[#192748] hover:bg-[#20335e] border border-[#2a3d69] text-xs font-semibold rounded-xl text-white transition cursor-pointer">
                        Jalankan Prediksi Random Forest
                    </button>
                </form>
            @endif
        </div>
    </div>

    <!-- Filter Menu & Batch -->
    <form method="GET" action="{{ route('admin.prediksi.index') }}" class="grid grid-cols-1 md:grid-cols-3 gap-4 items-end">
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
            <label class="block text-xs font-semibold text-slate-300 mb-1.5">Batch Produksi</label>
            <select name="batch_no" onchange="this.form.submit()" class="w-full px-3.5 py-2.5 bg-[#192748] border border-[#2a3d69] rounded-xl text-xs text-white focus:outline-none">
                @foreach(['Tahap 1', 'Tahap 2', 'Tahap 3'] as $bOption)
                    <option value="{{ $bOption }}" {{ $batchNo === $bOption ? 'selected' : '' }}>{{ $bOption }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="block text-xs font-semibold text-slate-300 mb-1.5">Kelompok Cakupan</label>
            <input type="text" readonly value="{{ $kelompoks->pluck('nama_kelompok')->map(fn($n) => trim(explode('/', $n)[0]))->implode(', ') ?: 'Semua titik' }}"
                   class="w-full px-3.5 py-2.5 bg-[#192748] border border-[#2a3d69] rounded-xl text-xs text-slate-300 focus:outline-none">
        </div>
    </form>

    @if($kurangData && (!$prediksi || !$prediksi->kelas_prediksi))
        <div class="p-5 bg-amber-950/30 border border-amber-700/60 rounded-2xl space-y-1">
            <h3 class="text-sm font-bold text-amber-300">Data Waktu Belum Lengkap untuk Prediksi</h3>
            <p class="text-xs text-amber-200 leading-relaxed">
                Tahap pengemasan dan waktu matang makanan untuk <strong>{{ $activeMenu?->nama_menu ?? 'menu terpilih' }} ({{ $batchNo }})</strong> belum tercatat selesai. Silakan catat waktu aktual di halaman <a href="{{ route('admin.tahap.index', ['menu_id' => $activeMenu?->id, 'batch_no' => $batchNo]) }}" class="underline text-white">Pencatatan Tahap</a>.
            </p>
        </div>
    @endif

    <!-- 3 Top Summary Cards -->
    <div class="grid-3">
        <div class="stat-card">
            <div class="stat-label">Status Prediksi Utama ({{ $batchNo }})</div>
            <div class="stat-value">{{ $prediksi?->kelas_prediksi ?? 'Menunggu' }}</div>
            <div class="stat-sub">
                {{ $activeMenu?->nama_menu ?? 'Belum ada menu terpilih' }}
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-label">Estimasi Probabilitas Risiko</div>
            <div class="stat-value">
                {{ $prediksi && $prediksi->probabilitas_risiko !== null ? number_format((float)$prediksi->probabilitas_risiko, 1, ',', '.') . '%' : '--' }}
            </div>
            <div class="stat-sub">Probabilitas keterlambatan melewati ambang aman</div>
        </div>

        <div class="stat-card">
            <div class="stat-label">Batas Konsumsi Aman (4 Jam)</div>
            <div class="stat-value">{{ $batas4Jam ? $batas4Jam->format('H.i') : '--' }}</div>
            <div class="stat-sub">
                {{ $waktuMatang ? '4 jam sejak waktu matang ' . \Carbon\Carbon::parse($waktuMatang)->format('H.i') . ' WITA' : 'Menunggu pencatatan jam matang' }}
            </div>
        </div>
    </div>

    <!-- Tabel Evaluasi Risiko per Titik Pengantaran -->
    <div class="bg-[#131f38] border border-[#1f3056] p-6 rounded-2xl space-y-4">
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-2">
            <div>
                <h2 class="text-sm font-bold text-white">Evaluasi Risiko Pra-Distribusi per Titik Pengantaran</h2>
                <p class="text-xs text-slate-400 mt-0.5">
                    Dijalankan otomatis setiap kali tahap pengemasan selesai dicatat, sebelum armada berangkat ke sekolah.
                </p>
            </div>
            @if($prediksi && $prediksi->kelas_prediksi)
                <span class="badge {{ $prediksi->kelas_prediksi === 'Layak' ? 'badge-success' : 'badge-danger' }}">
                    Prediksi Utama: {{ $prediksi->kelas_prediksi }} (Risiko {{ number_format((float)$prediksi->probabilitas_risiko, 1, ',', '.') }}%)
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

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 pt-1">
            <div class="bg-[#192748] border border-[#2a3d69] p-4 rounded-xl space-y-1">
                <h4 class="text-xs font-bold text-white">Pendukung keputusan; keputusan akhir pada petugas dapur</h4>
                <p class="text-[11px] text-slate-300 leading-relaxed">
                    {{ $prediksi?->keterangan_keputusan ?? 'Hasil rekomendasi merupakan bahan pertimbangan. Keputusan akhir kelayakan konsumsi makanan berada pada petugas yang berwenang.' }}
                </p>
            </div>

            <div class="bg-[#192748] border border-[#2a3d69] p-4 rounded-xl space-y-1">
                <h4 class="text-xs font-bold text-white">Transparansi model &amp; ambang batas cadangan</h4>
                <p class="text-[11px] text-slate-300 leading-relaxed">
                    Sistem menggunakan model Random Forest, dan otomatis beralih ke aturan ambang batas 4 jam (240 menit pasca matang) apabila layanan Python tidak merespons &le; 5 detik.
                </p>
            </div>
        </div>
    </div>

    <!-- Riwayat Prediksi Batch -->
    <div class="bg-[#131f38] border border-[#1f3056] p-6 rounded-2xl space-y-4">
        <h2 class="text-sm font-bold text-white">Riwayat Prediksi Random Forest per Batch</h2>

        <div class="overflow-x-auto rounded-xl border border-[#1f3056]">
            <table class="w-full text-left text-xs border-collapse">
                <thead>
                    <tr class="bg-[#182645] text-slate-300 font-semibold border-b border-[#1f3056]">
                        <th class="py-3 px-4">Tanggal / Menu</th>
                        <th class="py-3 px-4">Batch</th>
                        <th class="py-3 px-4">Kelas Prediksi</th>
                        <th class="py-3 px-4">Probabilitas Risiko</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-[#1f3056]/60 text-slate-300">
                    @forelse($prediksiHistory as $hist)
                        <tr class="hover:bg-[#182747]/40 cursor-pointer" onclick="window.location='{{ route('admin.prediksi.index', ['menu_id' => $hist->menu_id, 'batch_no' => $hist->batch_no]) }}'">
                            <td class="py-3.5 px-4 font-semibold text-white">
                                {{ optional($hist->tanggal)->format('d M Y') }} &middot; {{ $hist->menu?->nama_menu ?? '-' }}
                            </td>
                            <td class="py-3.5 px-4">{{ $hist->batch_no }}</td>
                            <td class="py-3.5 px-4 font-semibold {{ $hist->kelas_prediksi === 'Layak' ? 'text-emerald-400' : 'text-rose-400' }}">
                                {{ $hist->kelas_prediksi }}
                            </td>
                            <td class="py-3.5 px-4">
                                {{ $hist->probabilitas_risiko !== null ? number_format((float)$hist->probabilitas_risiko, 1, ',', '.') . '%' : '--' }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="py-6 text-center text-slate-400">Belum ada riwayat prediksi tersimpan.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection

