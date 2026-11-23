@extends('layouts.app')

@section('title', 'Dashboard Admin')

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="page-header">
        <h1 class="page-title">Dashboard Admin</h1>
        <p class="page-subtitle">
            Ringkasan operasional satu dapur SPPG Ujung Lare &bull; {{ \Carbon\Carbon::now()->translatedFormat('l, d F Y') }} &bull; Data Langsung
        </p>
    </div>

    <!-- 3 Top Stat Cards -->
    <div class="grid-3">
        <div class="stat-card">
            <div class="stat-label">Total penerima manfaat dapur</div>
            <div class="stat-value">{{ number_format($totalPenerimaDapur, 0, ',', '.') }}</div>
            <div class="stat-sub">Cakupan keseluruhan SPPG Ujung Lare, Kota Parepare</div>
        </div>

        <div class="stat-card">
            <div class="stat-label">Penerima batch Tahap 1</div>
            <div class="stat-value">{{ number_format($penerimaBatch1, 0, ',', '.') }}</div>
            <div class="stat-sub">
                {{ $kelompokList->isNotEmpty() ? $kelompokList->map(fn($k) => trim(explode('/', $k->nama_kelompok)[0]) . ' ' . $k->jumlah_penerima)->implode(' + ') : 'Belum ada kelompok penerima' }}
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-label">Porsi terkemas Tahap 1</div>
            <div class="stat-value">{{ number_format($porsiTerkemasBatch1, 0, ',', '.') }}</div>
            <div class="stat-sub">
                {{ $kelompokList->isNotEmpty() ? $kelompokList->map(fn($k) => trim(explode('/', $k->nama_kelompok)[0]) . ' ' . $k->porsi_terkemas)->implode(' + ') : 'Belum ada pencatatan porsi' }}
            </div>
        </div>
    </div>

    <!-- Status Batch Hari Ini -->
    @php
        $alurTerima = $alurs->get('terima_bahan');
        $alurOlah = $alurs->get('pengolahan');
        $alurKemas = $alurs->get('pengemasan');
        $alurDist = $alurs->get('pengantaran');

        $badgeBatch = 'Belum mulai';
        if ($alurDist && $alurDist->waktu_selesai_aktual) {
            $badgeBatch = 'Semua tahap selesai · KPI dihitung';
        } elseif ($alurKemas && $alurKemas->waktu_selesai_aktual) {
            $badgeBatch = 'Kemas selesai · Belum distribusi';
        } elseif ($alurOlah && $alurOlah->waktu_selesai_aktual) {
            $badgeBatch = 'Olah selesai · Menunggu kemas';
        } elseif ($alurTerima && $alurTerima->waktu_selesai_aktual) {
            $badgeBatch = 'Bahan diterima · Proses olah';
        }
    @endphp
    <div class="card">
        <div class="flex items-center justify-between flex-wrap gap-2 mb-4">
            <h2 class="card-title mb-0">Status batch hari ini</h2>
            <span class="badge badge-amber mb-0">
                {{ $badgeBatch }}
            </span>
        </div>

        <div class="text-sm font-bold text-[#000000] mb-3">
            @if($activeMenu)
                Tahap 1 &bull; {{ $activeMenu->nama_menu }}
                <span class="text-xs font-normal text-[#666666]">({{ $activeMenu->jumlah_porsi_rencana }} porsi · {{ ucfirst(str_replace('_', ' ', $activeMenu->status)) }})</span>
            @else
                Belum ada menu terdaftar untuk hari ini
            @endif
        </div>

        <!-- 4 Steps Stepper -->
        <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-3">
            <div class="step-card {{ $alurTerima && $alurTerima->waktu_selesai_aktual ? 'active' : '' }}">
                Terima bahan / {{ $alurTerima && $alurTerima->waktu_selesai_aktual ? 'Selesai ' . \Carbon\Carbon::parse($alurTerima->waktu_selesai_aktual)->format('H.i') : 'Belum mulai' }}
            </div>
            <div class="step-card {{ $alurOlah && $alurOlah->waktu_selesai_aktual ? 'active' : '' }}">
                &rarr; Olah / {{ $alurOlah && $alurOlah->waktu_selesai_aktual ? 'Selesai ' . \Carbon\Carbon::parse($alurOlah->waktu_selesai_aktual)->format('H.i') : 'Belum mulai' }}
            </div>
            <div class="step-card {{ $alurKemas && $alurKemas->waktu_selesai_aktual ? 'active' : '' }}">
                &rarr; Kemas / {{ $alurKemas && $alurKemas->waktu_selesai_aktual ? 'Selesai ' . \Carbon\Carbon::parse($alurKemas->waktu_selesai_aktual)->format('H.i') : 'Belum mulai' }}
            </div>
            <div class="step-card {{ $alurDist && $alurDist->waktu_selesai_aktual ? 'active' : '' }}">
                &rarr; Distribusi / {{ $alurDist && $alurDist->waktu_selesai_aktual ? 'Selesai ' . \Carbon\Carbon::parse($alurDist->waktu_selesai_aktual)->format('H.i') : ($alurDist && $alurDist->waktu_mulai_aktual ? 'Berlangsung' : 'Belum mulai') }}
            </div>
        </div>

        <p class="text-xs text-slate-400 mt-4 leading-relaxed">
            @if($kpi && $alurDist && $alurDist->waktu_selesai_aktual)
                Skor KPI aktif: <strong class="text-white">{{ number_format($kpi->skor_total, 1, ',', '.') }}% ({{ $kpi->kategori_kpi }})</strong> &bull; Prediksi risiko: <strong class="text-white">{{ $kpi->kelas_prediksi }}</strong> ({{ $kpi->probabilitas_risiko }}% risiko keterlambatan).
            @else
                Prediksi dijalankan otomatis setelah tahap kemas selesai. KPI final diperbarui otomatis setelah tahap distribusi selesai dicatat.
            @endif
        </p>
    </div>

    <!-- 2 Columns: Daftar Pekerjaan & Peringatan Bahan -->
    @php
        $pekerjaans = [];
        foreach ($kelompokList as $kel) {
            if ($kel->porsi_terkemas < $kel->jumlah_penerima) {
                $kurang = $kel->jumlah_penerima - $kel->porsi_terkemas;
                $pekerjaans[] = [
                    'judul' => 'Porsi ' . trim(explode('/', $kel->nama_kelompok)[0]) . ' kurang ' . $kurang,
                    'tindak' => 'Periksa kapasitas dan porsi terkemas (tercatat ' . $kel->porsi_terkemas . '/' . $kel->jumlah_penerima . ')',
                ];
            }
        }
        if (!$alurDist || !$alurDist->waktu_selesai_aktual) {
            $pekerjaans[] = [
                'judul' => 'Tahap distribusi belum selesai dicatat',
                'tindak' => 'Catat waktu berangkat dan tiba per titik pengantaran',
            ];
        }
        $menuDraftCount = \App\Models\Menu::whereIn('status', ['draft', 'menunggu_validasi'])->count();
        if ($menuDraftCount > 0) {
            $pekerjaans[] = [
                'judul' => $menuDraftCount . ' menu belum tervalidasi/terpublikasi',
                'tindak' => 'Lengkapi komposisi bahan di Data Menu agar Ahli Gizi dapat memvalidasi',
            ];
        }
    @endphp
    <div class="grid-2">
        <!-- Daftar Pekerjaan -->
        <div class="card flex flex-col justify-between">
            <div>
                <h2 class="card-title">Daftar pekerjaan</h2>

                <div class="table-responsive">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Pekerjaan</th>
                                <th>Tindak lanjut</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($pekerjaans as $pk)
                                <tr>
                                    <td class="font-medium text-white">{{ $pk['judul'] }}</td>
                                    <td class="text-slate-400">{{ $pk['tindak'] }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td class="font-medium text-emerald-300">Seluruh pekerjaan batch selesai</td>
                                    <td class="text-slate-400">Tidak ada kekurangan porsi atau tahap tertunda.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="pt-5 mt-4">
                <a href="{{ route('admin.tahap.index') }}" class="btn btn-secondary">
                    Buka Pencatatan Tahap
                </a>
            </div>
        </div>

        <!-- Peringatan Bahan -->
        <div class="card flex flex-col justify-between">
            <div>
                <h2 class="card-title">Peringatan bahan</h2>
                
                @forelse($bahanKedaluwarsa as $bk)
                    <div class="alert-box alert-danger mb-3">
                        <strong class="text-rose-300 block mb-1">Kedaluwarsa &middot; jangan digunakan</strong>
                        <p class="leading-relaxed text-xs">
                            {{ $bk->nama_bahan }} lot {{ $bk->kode_lot }} &bull; {{ number_format($bk->stok_kg, 0) }} {{ $bk->satuan }} &bull; kedaluwarsa {{ $bk->tanggal_kedaluwarsa->format('d M, H.i') }}. Periksa bahan terkait menu dan catatan pemakaian.
                        </p>
                    </div>
                @empty
                    <div class="p-3 rounded-lg bg-[#142845] border border-[#2b4170] text-xs text-emerald-300 mb-3">
                        Tidak ada bahan kedaluwarsa di gudang saat ini.
                    </div>
                @endforelse

                @if($bahanMendekati->isNotEmpty())
                    <p class="text-xs text-slate-300 leading-relaxed">
                        Mendekati kedaluwarsa:
                        {{ $bahanMendekati->map(fn($bm) => $bm->nama_bahan . ' lot ' . $bm->kode_lot . ' (' . $bm->tanggal_kedaluwarsa->format('d M, H.i') . ')')->implode(', ') }}.
                        Status masa simpan ditampilkan pada modul Bahan Baku.
                    </p>
                @else
                    <p class="text-xs text-slate-400 leading-relaxed">
                        Tidak ada bahan yang mendekati batas kedaluwarsa dalam 2 hari ke depan.
                    </p>
                @endif
            </div>

            <div class="pt-5 mt-4">
                <a href="{{ route('admin.bahan.index') }}" class="btn btn-secondary">
                    Lihat Bahan Baku
                </a>
            </div>
        </div>
    </div>

    <!-- Pintasan Modul Admin -->
    <div class="card">
        <h2 class="card-title">Pintasan modul Admin</h2>
        
        <div class="flex flex-wrap gap-3">
            <a href="{{ route('admin.menu.index') }}#form-menu" class="btn btn-secondary">
                Tambah Menu
            </a>
            <a href="{{ route('admin.bahan.index') }}" class="btn btn-secondary">
                Kelola Bahan Baku
            </a>
            <a href="{{ route('admin.penerima.index') }}" class="btn btn-secondary">
                Penerima dan Porsi
            </a>
            <a href="{{ route('admin.jadwal.index') }}" class="btn btn-secondary">
                Jadwal Produksi
            </a>
            <a href="{{ route('admin.kpi.index') }}" class="btn btn-secondary">
                KPI / Prediksi &middot; baca
            </a>
        </div>

        <p class="text-[11px] text-slate-400 mt-4">
            Nilai gizi, validasi dan publikasi merupakan kewenangan Ahli Gizi. Admin hanya melihat hasilnya.
        </p>
    </div>

    <!-- Aktivitas Audit Terkini -->
    <div class="card p-4" style="padding: 16px 20px;">
        <div class="text-xs font-bold text-white mb-2">Aktivitas sistem terkini (Audit Log)</div>
        @if($recentAudits->isNotEmpty())
            <div class="space-y-1 text-xs text-slate-400">
                @foreach($recentAudits->take(3) as $log)
                    <div>
                        <span class="text-slate-300 font-medium">{{ optional($log->created_at)->format('d M H.i') }}</span>
                        &bull; {{ $log->username }} ({{ $log->role }}) &mdash; <span class="text-white">{{ str_replace('_', ' ', $log->action) }}</span>
                        @if($log->model_type) pada {{ $log->model_type }} #{{ $log->model_id }} @endif
                    </div>
                @endforeach
            </div>
        @else
            <div class="text-xs text-slate-400">Belum ada catatan audit tercatat pada sesi ini.</div>
        @endif
    </div>
</div>
@endsection
