@extends('layouts.app')

@section('title', 'Pencatatan Tahap')

@section('content')
@php
    $alurTerima = $alurs->get('terima_bahan');
    $alurOlah = $alurs->get('pengolahan');
    $alurKemas = $alurs->get('pengemasan');
    $alurDist = $alurs->get('pengantaran');

    $tglAktif = optional($activeMenu?->tanggal_penyajian)->format('Y-m-d') ?? date('Y-m-d');
    $defaultTahap = old('tahap', 'pengemasan');
    $defaultAlur = $alurs->get($defaultTahap);

    $tahapRows = [
        ['key' => 'terima_bahan', 'label' => 'Terima bahan', 'alur' => $alurTerima],
        ['key' => 'pengolahan', 'label' => 'Olah', 'alur' => $alurOlah],
        ['key' => 'pengemasan', 'label' => 'Kemas', 'alur' => $alurKemas],
        ['key' => 'pengantaran', 'label' => 'Distribusi', 'alur' => $alurDist],
    ];
@endphp
<div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-white tracking-tight">Pencatatan Tahap</h1>
            <p class="text-xs text-slate-400 mt-1">
                Klik tombol <strong>Mulai</strong> atau <strong>Selesai Sekarang</strong> pada tabel untuk mencatat jam aktual &amp; menghitung durasi otomatis tanpa ketik manual.
            </p>
        </div>

        <a href="{{ route('admin.prediksi.index', ['menu_id' => $activeMenu?->id, 'batch_no' => $batchNo]) }}" class="px-4 py-2.5 bg-white hover:bg-slate-100 text-[#0b1329] text-xs font-bold rounded-xl transition">
            Lihat Prediksi Risiko &rarr;
        </a>
    </div>

    <!-- Filter Selector Menu & Batch -->
    <form method="GET" action="{{ route('admin.tahap.index') }}" class="grid grid-cols-1 md:grid-cols-3 gap-4 items-end">
        <div>
            <label class="block text-xs font-semibold text-slate-300 mb-1.5">Menu &amp; Tanggal</label>
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
                @foreach(['Tahap 1', 'Tahap 2', 'Tahap 3'] as $bOpt)
                    <option value="{{ $bOpt }}" {{ $batchNo === $bOpt ? 'selected' : '' }}>{{ $bOpt }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="block text-xs font-semibold text-slate-300 mb-1.5">Kelompok sasaran aktif</label>
            <input type="text" readonly value="{{ $kelompoks->pluck('nama_kelompok')->map(fn($n) => trim(explode('/', $n)[0]))->implode(', ') ?: 'Belum terdaftar' }}"
                   class="w-full px-3.5 py-2.5 bg-[#192748] border border-[#2a3d69] rounded-xl text-xs text-slate-300 focus:outline-none">
        </div>
    </form>

    <!-- Status Pills Row -->
    <div class="flex flex-wrap items-center gap-3">
        @foreach($tahapRows as $tr)
            @php
                $st = $tr['alur']?->status_tahap ?? 'belum_mulai';
                $stLabel = str_replace('_', ' ', $st);
                $pillClass = match($st) {
                    'selesai' => 'border-emerald-700/70 text-emerald-300',
                    'terlambat' => 'border-rose-700/70 text-rose-300',
                    'berlangsung' => 'border-sky-700/70 text-sky-300',
                    default => 'border-[#2a3d69] text-slate-300',
                };
            @endphp
            <span class="px-4 py-2 bg-[#192748] border {{ $pillClass }} text-xs font-semibold rounded-xl">
                {{ $tr['label'] }} &middot; {{ $stLabel }}
            </span>
        @endforeach
    </div>

    <!-- Card 1: Ringkasan Waktu Mulai, Selesai dan Aksi Cepat 1-Klik -->
    <div class="bg-[#131f38] border border-[#1f3056] p-6 rounded-2xl space-y-4">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2">
            <div>
                <h2 class="text-sm font-bold text-white">Pencatatan Cepat Waktu Tahapan ({{ $batchNo }})</h2>
                <p class="text-xs text-slate-400 mt-0.5">Tekan tombol aksi pada setiap tahapan agar sistem langsung merekam jam &amp; menghitung durasi otomatis</p>
            </div>
        </div>

        <div class="overflow-x-auto rounded-xl border border-[#1f3056]">
            <table class="w-full text-left text-xs border-collapse">
                <thead>
                    <tr class="bg-[#182645] text-slate-300 font-semibold border-b border-[#1f3056]">
                        <th class="py-3 px-4">Tahap</th>
                        <th class="py-3 px-4">Mulai aktual</th>
                        <th class="py-3 px-4">Selesai &amp; Durasi</th>
                        <th class="py-3 px-4">Petugas &amp; porsi</th>
                        <th class="py-3 px-4">Status / Tombol Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-[#1f3056]/60 text-slate-300">
                    @foreach($tahapRows as $tr)
                        @php
                            $al = $tr['alur'];
                            $mulaiCarbon = $al?->waktu_mulai_aktual ? \Carbon\Carbon::parse($al->waktu_mulai_aktual) : null;
                            $selesaiCarbon = $al?->waktu_selesai_aktual ? \Carbon\Carbon::parse($al->waktu_selesai_aktual) : null;
                            $mulaiStr = $mulaiCarbon ? $mulaiCarbon->format('H:i') : '';
                            $selesaiStr = $selesaiCarbon ? $selesaiCarbon->format('H:i') : '';
                            $durasiMenit = ($mulaiCarbon && $selesaiCarbon && $selesaiCarbon->gte($mulaiCarbon))
                                ? (int) round($mulaiCarbon->diffInMinutes($selesaiCarbon))
                                : null;
                            $rowStatus = $al?->status_tahap ?? 'belum_mulai';
                        @endphp
                        <tr class="hover:bg-[#182747]/40">
                            <td class="py-3.5 px-4 font-semibold text-white">{{ $tr['label'] }}</td>
                            <td class="py-3.5 px-4 font-mono">
                                {{ $mulaiStr ? str_replace(':', '.', $mulaiStr) . ' WITA' : 'Belum mulai' }}
                            </td>
                            <td class="py-3.5 px-4 font-mono">
                                @if($selesaiStr)
                                    <div class="font-semibold text-white">{{ str_replace(':', '.', $selesaiStr) }} WITA</div>
                                    @if($durasiMenit !== null)
                                        <span class="inline-block mt-0.5 px-2 py-0.5 rounded-full bg-emerald-950 text-[10px] font-bold">
                                            Durasi: {{ $durasiMenit }} menit
                                        </span>
                                    @endif
                                @elseif($rowStatus === 'berlangsung')
                                    <span class="text-sky-300 font-semibold">Sedang berjalan...</span>
                                @else
                                    <span>Belum selesai</span>
                                @endif
                            </td>
                            <td class="py-3.5 px-4">
                                {{ $al?->petugas_aktual ?? '-' }}
                                @if($al?->porsi_terkemas)
                                    <span class="text-[11px] text-sky-300">({{ $al->porsi_terkemas }} porsi)</span>
                                @endif
                            </td>
                            <td class="py-3.5 px-4">
                                <div class="flex flex-wrap items-center gap-2">
                                    @if($activeMenu)
                                        @if($rowStatus === 'belum_mulai')
                                            <form method="POST" action="{{ route('admin.tahap.quick') }}" class="inline">
                                                @csrf
                                                <input type="hidden" name="menu_id" value="{{ $activeMenu->id }}">
                                                <input type="hidden" name="batch_no" value="{{ $batchNo }}">
                                                <input type="hidden" name="tahap" value="{{ $tr['key'] }}">
                                                <input type="hidden" name="aksi" value="mulai">
                                                <button type="submit" class="btn btn-secondary btn-sm">
                                                    Mulai
                                                </button>
                                            </form>
                                            <form method="POST" action="{{ route('admin.tahap.quick') }}" class="inline">
                                                @csrf
                                                <input type="hidden" name="menu_id" value="{{ $activeMenu->id }}">
                                                <input type="hidden" name="batch_no" value="{{ $batchNo }}">
                                                <input type="hidden" name="tahap" value="{{ $tr['key'] }}">
                                                <input type="hidden" name="aksi" value="selesai">
                                                <button type="submit" class="btn btn-primary btn-sm">
                                                    Selesai Sekarang
                                                </button>
                                            </form>
                                        @elseif($rowStatus === 'berlangsung')
                                            <span class="badge badge-info">Berlangsung</span>
                                            <form method="POST" action="{{ route('admin.tahap.quick') }}" class="inline">
                                                @csrf
                                                <input type="hidden" name="menu_id" value="{{ $activeMenu->id }}">
                                                <input type="hidden" name="batch_no" value="{{ $batchNo }}">
                                                <input type="hidden" name="tahap" value="{{ $tr['key'] }}">
                                                <input type="hidden" name="aksi" value="selesai">
                                                <button type="submit" class="btn btn-primary btn-sm">
                                                    ✓ Selesai Sekarang
                                                </button>
                                            </form>
                                        @else
                                            <span class="badge {{ $rowStatus === 'terlambat' ? 'badge-danger' : 'badge-success' }}">
                                                {{ $rowStatus === 'terlambat' ? 'Terlambat' : '✓ Selesai' }}
                                            </span>
                                            <form method="POST" action="{{ route('admin.tahap.quick') }}" class="inline">
                                                @csrf
                                                <input type="hidden" name="menu_id" value="{{ $activeMenu->id }}">
                                                <input type="hidden" name="batch_no" value="{{ $batchNo }}">
                                                <input type="hidden" name="tahap" value="{{ $tr['key'] }}">
                                                <input type="hidden" name="aksi" value="mulai">
                                                <button type="submit" class="btn btn-secondary btn-sm">
                                                    Mulai Ulang
                                                </button>
                                            </form>
                                        @endif
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <p class="text-[11px] text-slate-400">
            Cukup klik <strong>Mulai</strong> saat tahapan dimulai lalu klik <strong>Selesai Sekarang</strong> saat rampung (atau langsung klik <strong>Selesai Sekarang</strong> untuk otomatis melanjutkan dari waktu selesai tahap sebelumnya).
        </p>
    </div>

    <!-- Card: Waktu matang per komponen (Otomatis & 1-Klik) -->
    @if($activeMenu)
        @php
            $mNasi = $alurOlah?->waktu_matang_nasi ? \Carbon\Carbon::parse($alurOlah->waktu_matang_nasi) : null;
            $mLauk = $alurOlah?->waktu_matang_lauk ? \Carbon\Carbon::parse($alurOlah->waktu_matang_lauk) : null;
            $mSayur = $alurOlah?->waktu_matang_sayur ? \Carbon\Carbon::parse($alurOlah->waktu_matang_sayur) : null;
            $komponenRows = [
                ['key' => 'nasi', 'label' => 'Nasi', 'dt' => $mNasi],
                ['key' => 'lauk', 'label' => 'Lauk utama & nabati', 'dt' => $mLauk],
                ['key' => 'sayur', 'label' => 'Sayur', 'dt' => $mSayur],
            ];
        @endphp
        <div class="bg-[#131f38] border border-[#1f3056] p-6 rounded-2xl space-y-4">
            <div>
                <h2 class="text-sm font-bold text-white">Waktu matang per komponen &amp; batas aman 4 jam</h2>
                <p class="text-xs text-slate-400 mt-0.5">Otomatis tercatat saat tahap <strong>Olah</strong> selesai, atau klik tombol per komponen saat masakan matang</p>
            </div>

            <div class="overflow-x-auto rounded-xl border border-[#1f3056]">
                <table class="w-full text-left text-xs border-collapse">
                    <thead>
                        <tr class="bg-[#182645] text-slate-300 font-semibold border-b border-[#1f3056]">
                            <th class="py-3 px-4">Komponen</th>
                            <th class="py-3 px-4">Matang aktual</th>
                            <th class="py-3 px-4">Batas konsumsi aman (4 jam)</th>
                            <th class="py-3 px-4">Aksi Cepat</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-[#1f3056]/60 text-slate-300">
                        @foreach($komponenRows as $kr)
                            <tr>
                                <td class="py-3 px-4 font-semibold text-white">{{ $kr['label'] }}</td>
                                <td class="py-3 px-4 font-mono">{{ $kr['dt'] ? $kr['dt']->format('H.i') . ' WITA' : 'Belum dicatat' }}</td>
                                <td class="py-3 px-4 text-emerald-400 font-semibold font-mono">{{ $kr['dt'] ? $kr['dt']->copy()->addHours(4)->format('H.i') . ' WITA' : '--' }}</td>
                                <td class="py-3 px-4">
                                    <form method="POST" action="{{ route('admin.tahap.quick') }}" class="inline">
                                        @csrf
                                        <input type="hidden" name="menu_id" value="{{ $activeMenu->id }}">
                                        <input type="hidden" name="batch_no" value="{{ $batchNo }}">
                                        <input type="hidden" name="tahap" value="pengolahan">
                                        <input type="hidden" name="aksi" value="komponen">
                                        <input type="hidden" name="komponen" value="{{ $kr['key'] }}">
                                        <button type="submit" class="btn btn-secondary btn-sm">
                                            {{ $kr['dt'] ? 'Perbarui Sekarang' : 'Matang Sekarang' }}
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    <!-- Card: Bahan Terkait Menu & Pemeriksaan Kedaluwarsa -->
    <div class="bg-[#131f38] border border-[#1f3056] p-6 rounded-2xl space-y-4">
        <div class="flex items-center justify-between flex-wrap gap-2">
            <h2 class="text-sm font-bold text-white">Pemeriksaan kedaluwarsa bahan terkait menu</h2>
            <a href="{{ route('admin.bahan.index') }}" class="text-xs text-sky-300 hover:underline">Kelola stok bahan &rarr;</a>
        </div>

        <div class="overflow-x-auto rounded-xl border border-[#1f3056]">
            <table class="w-full text-left text-xs border-collapse">
                <thead>
                    <tr class="bg-[#182645] text-slate-300 font-semibold border-b border-[#1f3056]">
                        <th class="py-3 px-4">Bahan / Kode Lot</th>
                        <th class="py-3 px-4">Diterima</th>
                        <th class="py-3 px-4">Pemakaian menu</th>
                        <th class="py-3 px-4">Kedaluwarsa</th>
                        <th class="py-3 px-4">Pemeriksaan</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-[#1f3056]/60 text-slate-300">
                    @php
                        $today = \Carbon\Carbon::today();
                        $menuBahans = $activeMenu?->menuBahans ?? collect();
                    @endphp
                    @forelse($menuBahans as $mb)
                        @php
                            $b = $mb->bahanBaku;
                            $exp = $b?->tanggal_kedaluwarsa;
                            $isExp = $exp && $exp->lt($today);
                        @endphp
                        <tr class="hover:bg-[#182747]/40">
                            <td class="py-3.5 px-4 font-semibold text-white">{{ $b?->nama_bahan ?? '-' }} <span class="font-mono text-sky-300">{{ $b?->kode_lot }}</span></td>
                            <td class="py-3.5 px-4">{{ $b?->stok_kg }} {{ $b?->satuan }} &middot; {{ optional($b?->tanggal_masuk)->format('d M H.i') }}</td>
                            <td class="py-3.5 px-4">{{ $mb->jumlah_batch }} {{ $mb->satuan }} ({{ $mb->komponen }})</td>
                            <td class="py-3.5 px-4">{{ optional($exp)->format('d M H.i') }}</td>
                            <td class="py-3.5 px-4 font-semibold {{ $isExp ? 'text-rose-400' : 'text-emerald-400' }}">
                                {{ $isExp ? 'Kedaluwarsa! Jangan dipakai' : 'Valid saat pakai' }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="py-5 text-center text-slate-400">Menu ini belum memiliki daftar bahan tertaut.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @php
            $expiredGudang = $bahanList->filter(fn($b) => $b->tanggal_kedaluwarsa && $b->tanggal_kedaluwarsa->lt($today));
        @endphp
        @if($expiredGudang->isNotEmpty())
            <div class="p-4 bg-[#182645] border border-rose-900/60 rounded-xl space-y-1">
                <h4 class="text-xs font-bold text-rose-400">Peringatan bahan kedaluwarsa di gudang ({{ $expiredGudang->count() }} lot)</h4>
                <p class="text-[11px] text-slate-300 leading-relaxed">
                    Terdeteksi lot kedaluwarsa: {{ $expiredGudang->map(fn($eb) => $eb->nama_bahan . ' ' . $eb->kode_lot)->implode(', ') }}. Jangan gunakan lot tersebut dan pisahkan dari jalur pengolahan.
                </p>
            </div>
        @endif
    </div>

    <!-- Card: Porsi dan penerimaan per titik antar -->
    <div class="bg-[#131f38] border border-[#1f3056] p-6 rounded-2xl space-y-4">
        <h2 class="text-sm font-bold text-white">Porsi dan penerimaan per titik antar</h2>

        @if($titiks->isNotEmpty())
            <div class="overflow-x-auto rounded-xl border border-[#1f3056]">
                <table class="w-full text-left text-xs border-collapse">
                    <thead>
                        <tr class="bg-[#182645] text-slate-300 font-semibold border-b border-[#1f3056]">
                            <th class="py-3 px-4">Titik / Armada</th>
                            <th class="py-3 px-4">Porsi Besar &amp; Kecil Dikirim</th>
                            <th class="py-3 px-4">Berangkat / Tiba aktual</th>
                            <th class="py-3 px-4">Penerima lokasi &amp; Status</th>
                            <th class="py-3 px-4">Simpan Titik</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-[#1f3056]/60 text-slate-300">
                        @foreach($titiks as $t)
                            <form method="POST" action="{{ route('admin.tahap.titik.update', $t->id) }}">
                                @csrf
                                <tr class="hover:bg-[#182747]/40">
                                    <td class="py-3 px-4 font-semibold text-white">
                                        {{ $t->kelompokPenerima?->nama_kelompok ?? 'Titik #' . $t->id }}
                                        <input type="text" name="petugas_antar" value="{{ $t->petugas_antar }}" placeholder="Petugas antar"
                                               class="mt-1 w-full px-2 py-1 bg-[#192748] border border-[#2a3d69] rounded text-[11px] text-white">
                                    </td>
                                    <td class="py-3 px-4 space-y-1">
                                        <div class="flex items-center gap-1.5">
                                            <span class="text-[10px] text-slate-400 w-10">Besar</span>
                                            <input type="number" name="porsi_besar_dikirim" value="{{ $t->porsi_besar_dikirim ?: $t->porsi_dikirim }}" min="0"
                                                   class="w-20 px-2 py-1 bg-[#192748] border border-[#2a3d69] rounded text-xs text-white">
                                        </div>
                                        <div class="flex items-center gap-1.5">
                                            <span class="text-[10px] text-slate-400 w-10">Kecil</span>
                                            <input type="number" name="porsi_kecil_dikirim" value="{{ $t->porsi_kecil_dikirim ?? 0 }}" min="0"
                                                   class="w-20 px-2 py-1 bg-[#192748] border border-[#2a3d69] rounded text-xs text-white">
                                        </div>
                                        <div class="text-[10px] text-slate-400">Total: {{ $t->porsi_dikirim }} porsi</div>
                                    </td>
                                    <td class="py-3 px-4 space-y-1">
                                        <div class="flex items-center gap-1">
                                            <span class="text-[10px] text-slate-400 w-10">Berangkat</span>
                                            <input type="time" name="waktu_berangkat_aktual"
                                                   value="{{ $t->waktu_berangkat_aktual ? \Carbon\Carbon::parse($t->waktu_berangkat_aktual)->format('H:i') : '' }}"
                                                   class="px-2 py-1 bg-[#192748] border border-[#2a3d69] rounded text-xs text-white font-mono">
                                        </div>
                                        <div class="flex items-center gap-1">
                                            <span class="text-[10px] text-slate-400 w-10">Tiba</span>
                                            <input type="time" name="waktu_tiba_aktual"
                                                   value="{{ $t->waktu_tiba_aktual ? \Carbon\Carbon::parse($t->waktu_tiba_aktual)->format('H:i') : '' }}"
                                                   class="px-2 py-1 bg-[#192748] border border-[#2a3d69] rounded text-xs text-white font-mono">
                                        </div>
                                    </td>
                                    <td class="py-3 px-4 space-y-1">
                                        <input type="text" name="petugas_penerima" value="{{ $t->petugas_penerima }}" placeholder="Nama penerima di sekolah"
                                               class="w-full px-2 py-1 bg-[#192748] border border-[#2a3d69] rounded text-[11px] text-white">
                                        <select name="status_antar" class="w-full px-2 py-1 bg-[#192748] border border-[#2a3d69] rounded text-[11px] text-white">
                                            @foreach(['Belum distribusi', 'Dikirim', 'Diterima'] as $sa)
                                                <option value="{{ $sa }}" {{ $t->status_antar === $sa ? 'selected' : '' }}>{{ $sa }}</option>
                                            @endforeach
                                        </select>
                                    </td>
                                    <td class="py-3 px-4">
                                        <button type="submit" class="px-3 py-1.5 bg-[#192748] hover:bg-[#20335e] border border-[#2a3d69] text-white text-[11px] font-semibold rounded-lg cursor-pointer">
                                            Simpan
                                        </button>
                                    </td>
                                </tr>
                            </form>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <p class="text-xs text-slate-400">
                Catat tahap <strong>4. Distribusi</strong> pada form di atas untuk menampilkan &amp; mengisi realisasi tiap titik pengantaran.
            </p>
        @endif
    </div>

    <!-- Card: Riwayat jadwal dibanding aktual -->
    <div class="bg-[#131f38] border border-[#1f3056] p-6 rounded-2xl space-y-4">
        <h2 class="text-sm font-bold text-white">Riwayat jadwal rencana dibanding aktual ({{ $batchNo }})</h2>

        <div class="overflow-x-auto rounded-xl border border-[#1f3056]">
            <table class="w-full text-left text-xs border-collapse">
                <thead>
                    <tr class="bg-[#182645] text-slate-300 font-semibold border-b border-[#1f3056]">
                        <th class="py-3 px-4">Peristiwa / Jadwal Rencana</th>
                        <th class="py-3 px-4">Rencana</th>
                        <th class="py-3 px-4">Aktual tercatat</th>
                        <th class="py-3 px-4">Keterangan</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-[#1f3056]/60 text-slate-300">
                    @forelse($jadwalList as $jdw)
                        @php
                            $tLower = strtolower($jdw->tahap);
                            $matchedAlur = str_contains($tLower, 'kemas') ? $alurKemas : (str_contains($tLower, 'distribusi') ? $alurDist : $alurOlah);
                        @endphp
                        <tr class="hover:bg-[#182747]/40">
                            <td class="py-3.5 px-4 font-semibold text-white">{{ $jdw->tahap }}</td>
                            <td class="py-3.5 px-4 font-mono">{{ substr($jdw->waktu_mulai_rencana, 0, 5) }} &ndash; {{ substr($jdw->waktu_selesai_rencana, 0, 5) }}</td>
                            <td class="py-3.5 px-4 font-mono">
                                @if($matchedAlur && ($matchedAlur->waktu_mulai_aktual || $matchedAlur->waktu_selesai_aktual))
                                    {{ $matchedAlur->waktu_mulai_aktual ? \Carbon\Carbon::parse($matchedAlur->waktu_mulai_aktual)->format('H:i') : '--' }}
                                    &ndash;
                                    {{ $matchedAlur->waktu_selesai_aktual ? \Carbon\Carbon::parse($matchedAlur->waktu_selesai_aktual)->format('H:i') : 'Proses' }}
                                @else
                                    <span class="text-slate-400">Belum dicatat</span>
                                @endif
                            </td>
                            <td class="py-3.5 px-4 text-slate-300">
                                {{ $matchedAlur?->catatan_kendala ?: ($matchedAlur?->status_tahap === 'selesai' ? 'Selesai · ' . ($matchedAlur->porsi_terkemas ?? $jdw->porsi_target) . ' porsi' : 'Menunggu pencatatan aktual') }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="py-5 text-center text-slate-400">Belum ada jadwal rencana untuk menu/batch ini.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
