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
                Catat waktu aktual operasional dapur tanpa mengubah jadwal rencana. Pemicu otomatis: selesai kemas &rarr; Prediksi Risiko; selesai distribusi &rarr; Hitung KPI.
            </p>
        </div>

        <a href="{{ route('admin.kpi.index', ['menu_id' => $activeMenu?->id, 'batch_no' => $batchNo]) }}" class="px-4 py-2.5 bg-white hover:bg-slate-100 text-[#0b1329] text-xs font-bold rounded-xl transition">
            Lihat KPI / Prediksi &rarr;
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

    <!-- Card 1: Ringkasan Waktu Mulai, Selesai dan Petugas -->
    <div class="bg-[#131f38] border border-[#1f3056] p-6 rounded-2xl space-y-4">
        <h2 class="text-sm font-bold text-white">Waktu mulai, selesai dan petugas ({{ $batchNo }})</h2>

        <div class="overflow-x-auto rounded-xl border border-[#1f3056]">
            <table class="w-full text-left text-xs border-collapse">
                <thead>
                    <tr class="bg-[#182645] text-slate-300 font-semibold border-b border-[#1f3056]">
                        <th class="py-3 px-4">Tahap</th>
                        <th class="py-3 px-4">Mulai aktual</th>
                        <th class="py-3 px-4">Selesai aktual</th>
                        <th class="py-3 px-4">Petugas &amp; porsi</th>
                        <th class="py-3 px-4">Status / Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-[#1f3056]/60 text-slate-300">
                    @foreach($tahapRows as $tr)
                        @php
                            $al = $tr['alur'];
                            $mulaiStr = $al?->waktu_mulai_aktual ? \Carbon\Carbon::parse($al->waktu_mulai_aktual)->format('H:i') : '';
                            $selesaiStr = $al?->waktu_selesai_aktual ? \Carbon\Carbon::parse($al->waktu_selesai_aktual)->format('H:i') : '';
                        @endphp
                        <tr class="hover:bg-[#182747]/40">
                            <td class="py-3.5 px-4 font-semibold text-white">{{ $tr['label'] }}</td>
                            <td class="py-3.5 px-4 font-mono">
                                {{ $mulaiStr ? str_replace(':', '.', $mulaiStr) : 'Belum dicatat' }}
                            </td>
                            <td class="py-3.5 px-4 font-mono">
                                {{ $selesaiStr ? str_replace(':', '.', $selesaiStr) : 'Belum dicatat' }}
                            </td>
                            <td class="py-3.5 px-4">
                                {{ $al?->petugas_aktual ?? '-' }}
                                @if($al?->porsi_terkemas)
                                    <span class="text-[11px] text-sky-300">({{ $al->porsi_terkemas }} porsi)</span>
                                @endif
                            </td>
                            <td class="py-3.5 px-4 text-xs space-x-1">
                                @php
                                    $rowStatus = $al?->status_tahap ?? 'belum_mulai';
                                    $statusTextClass = match($rowStatus) {
                                        'selesai' => 'text-emerald-400 font-semibold',
                                        'terlambat' => 'text-rose-400 font-semibold',
                                        'berlangsung' => 'text-sky-400 font-semibold',
                                        default => 'text-slate-400',
                                    };
                                @endphp
                                <span class="{{ $statusTextClass }}">
                                    {{ ucfirst(str_replace('_', ' ', $rowStatus)) }}
                                </span>
                                <span class="text-slate-600">/</span>
                                <a href="#form-tahap" onclick="pilihTahap('{{ $tr['key'] }}', '{{ $mulaiStr }}', '{{ $selesaiStr }}', '{{ addslashes($al?->petugas_aktual ?? '') }}', '{{ $al?->porsi_terkemas ?? ($activeMenu?->jumlah_porsi_rencana ?? 300) }}', '{{ addslashes($al?->catatan_kendala ?? '') }}')" class="text-sky-300 hover:text-white underline">
                                    {{ $al ? 'Ubah' : 'Isi' }}
                                </a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <p class="text-[11px] text-slate-400">
            Pilih "Isi" atau "Ubah" pada baris tahap untuk mempermudah pengisian formulir di bawah (urutan tahap FR-020: terima bahan &rarr; olah &rarr; kemas &rarr; distribusi).
        </p>
    </div>

    <!-- Main Form Wrapper covering Stage Entry + Component Maturity -->
    @if($activeMenu)
    <form method="POST" action="{{ route('admin.tahap.store') }}" id="form-tahap" class="space-y-6">
        @csrf
        <input type="hidden" name="menu_id" value="{{ $activeMenu->id }}">
        <input type="hidden" name="batch_no" value="{{ $batchNo }}">

        <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">
            <!-- Left: Form tahap terpilih -->
            <div class="lg:col-span-7 bg-[#131f38] border border-[#1f3056] p-6 rounded-2xl space-y-4">
                <h2 class="text-sm font-bold text-white" id="title-form-tahap">Form pencatatan tahap &middot; {{ $activeMenu->nama_menu }}</h2>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-semibold text-slate-300 mb-1.5">Tahap yang dicatat *</label>
                        <select name="tahap" id="select-tahap" class="w-full px-3.5 py-2.5 bg-[#192748] border border-[#2a3d69] rounded-xl text-xs text-white focus:outline-none">
                            <option value="terima_bahan" {{ $defaultTahap === 'terima_bahan' ? 'selected' : '' }}>1. Terima bahan</option>
                            <option value="pengolahan" {{ $defaultTahap === 'pengolahan' ? 'selected' : '' }}>2. Olah</option>
                            <option value="pengemasan" {{ $defaultTahap === 'pengemasan' ? 'selected' : '' }}>3. Kemas</option>
                            <option value="pengantaran" {{ $defaultTahap === 'pengantaran' ? 'selected' : '' }}>4. Distribusi</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-300 mb-1.5">Tanggal produksi *</label>
                        <input type="date" name="tanggal" value="{{ old('tanggal', $tglAktif) }}" required
                               class="w-full px-3.5 py-2.5 bg-[#192748] border border-[#2a3d69] rounded-xl text-xs text-white focus:outline-none">
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-semibold text-slate-300 mb-1.5">Mulai aktual (jam)</label>
                        <input type="time" name="waktu_mulai_aktual" id="input-mulai-aktual"
                               value="{{ old('waktu_mulai_aktual', $defaultAlur?->waktu_mulai_aktual ? \Carbon\Carbon::parse($defaultAlur->waktu_mulai_aktual)->format('H:i') : '05:45') }}"
                               class="w-full px-3.5 py-2.5 bg-[#192748] border border-[#2a3d69] rounded-xl text-xs text-white focus:outline-none font-mono">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-300 mb-1.5">Selesai aktual (jam)</label>
                        <input type="time" name="waktu_selesai_aktual" id="input-selesai-aktual"
                               value="{{ old('waktu_selesai_aktual', $defaultAlur?->waktu_selesai_aktual ? \Carbon\Carbon::parse($defaultAlur->waktu_selesai_aktual)->format('H:i') : '06:30') }}"
                               class="w-full px-3.5 py-2.5 bg-[#192748] border border-[#2a3d69] rounded-xl text-xs text-white focus:outline-none font-mono">
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-semibold text-slate-300 mb-1.5">Petugas pelaksana *</label>
                        <input type="text" name="petugas_aktual" id="input-petugas"
                               value="{{ old('petugas_aktual', $defaultAlur?->petugas_aktual ?? 'Sari · Tim kemas') }}" required
                               placeholder="Nama petugas & peran"
                               class="w-full px-3.5 py-2.5 bg-[#192748] border border-[#2a3d69] rounded-xl text-xs text-white focus:outline-none">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-300 mb-1.5">Jumlah porsi terkemas / ditangani</label>
                        <input type="number" min="0" name="porsi_terkemas" id="input-porsi-kemas"
                               value="{{ old('porsi_terkemas', $defaultAlur?->porsi_terkemas ?? $activeMenu->jumlah_porsi_rencana) }}"
                               class="w-full px-3.5 py-2.5 bg-[#192748] border border-[#2a3d69] rounded-xl text-xs text-white focus:outline-none">
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-300 mb-1.5">Catatan / kendala</label>
                    <textarea name="catatan_kendala" id="input-kendala" rows="3" placeholder="Catat kendala pemorsian, suhu, atau keterlambatan bila ada..."
                              class="w-full px-3.5 py-2.5 bg-[#192748] border border-[#2a3d69] rounded-xl text-xs text-white focus:outline-none">{{ old('catatan_kendala', $defaultAlur?->catatan_kendala) }}</textarea>
                </div>

                <div class="flex items-center gap-3 pt-2">
                    <button type="submit" class="px-5 py-2.5 bg-[#192748] hover:bg-[#20335e] border border-[#3b5791] text-xs font-semibold rounded-xl text-white transition cursor-pointer">
                        Simpan Catatan Tahap
                    </button>
                    <a href="{{ route('admin.tahap.index', ['menu_id' => $activeMenu->id, 'batch_no' => $batchNo]) }}" class="px-5 py-2.5 bg-white hover:bg-slate-100 text-[#0b1329] text-xs font-bold rounded-xl transition">
                        Reset
                    </a>
                </div>
            </div>

            <!-- Right: Waktu matang per komponen -->
            <div class="lg:col-span-5 bg-[#131f38] border border-[#1f3056] p-6 rounded-2xl space-y-4">
                <h2 class="text-sm font-bold text-white">Waktu matang per komponen</h2>

                @php
                    $mNasi = $alurOlah?->waktu_matang_nasi ? \Carbon\Carbon::parse($alurOlah->waktu_matang_nasi) : null;
                    $mLauk = $alurOlah?->waktu_matang_lauk ? \Carbon\Carbon::parse($alurOlah->waktu_matang_lauk) : null;
                    $mSayur = $alurOlah?->waktu_matang_sayur ? \Carbon\Carbon::parse($alurOlah->waktu_matang_sayur) : null;
                @endphp
                <div class="overflow-x-auto rounded-xl border border-[#1f3056]">
                    <table class="w-full text-left text-xs border-collapse">
                        <thead>
                            <tr class="bg-[#182645] text-slate-300 font-semibold border-b border-[#1f3056]">
                                <th class="py-3 px-4">Komponen</th>
                                <th class="py-3 px-4">Matang aktual</th>
                                <th class="py-3 px-4">Batas 4 jam</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-[#1f3056]/60 text-slate-300">
                            <tr>
                                <td class="py-3 px-4 font-semibold text-white">Nasi</td>
                                <td class="py-3 px-4 font-mono">{{ $mNasi ? $mNasi->format('H.i') : '--' }}</td>
                                <td class="py-3 px-4 text-emerald-400 font-semibold font-mono">{{ $mNasi ? $mNasi->copy()->addHours(4)->format('H.i') : '--' }}</td>
                            </tr>
                            <tr>
                                <td class="py-3 px-4 font-semibold text-white">Lauk utama &amp; nabati</td>
                                <td class="py-3 px-4 font-mono">{{ $mLauk ? $mLauk->format('H.i') : '--' }}</td>
                                <td class="py-3 px-4 text-emerald-400 font-semibold font-mono">{{ $mLauk ? $mLauk->copy()->addHours(4)->format('H.i') : '--' }}</td>
                            </tr>
                            <tr>
                                <td class="py-3 px-4 font-semibold text-white">Sayur</td>
                                <td class="py-3 px-4 font-mono">{{ $mSayur ? $mSayur->format('H.i') : '--' }}</td>
                                <td class="py-3 px-4 text-emerald-400 font-semibold font-mono">{{ $mSayur ? $mSayur->copy()->addHours(4)->format('H.i') : '--' }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Input Jam Matang Komponen (ikut tersimpan bersama submit tahap) -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-3 pt-1">
                    <div>
                        <label class="block text-[11px] font-semibold text-slate-300 mb-1">Matang Nasi</label>
                        <input type="time" name="waktu_matang_nasi" value="{{ $mNasi ? $mNasi->format('H:i') : '05:30' }}"
                               class="w-full px-2.5 py-2 bg-[#192748] border border-[#2a3d69] rounded-xl text-xs text-white font-mono">
                    </div>
                    <div>
                        <label class="block text-[11px] font-semibold text-slate-300 mb-1">Matang Lauk</label>
                        <input type="time" name="waktu_matang_lauk" value="{{ $mLauk ? $mLauk->format('H:i') : '05:30' }}"
                               class="w-full px-2.5 py-2 bg-[#192748] border border-[#2a3d69] rounded-xl text-xs text-white font-mono">
                    </div>
                    <div>
                        <label class="block text-[11px] font-semibold text-slate-300 mb-1">Matang Sayur</label>
                        <input type="time" name="waktu_matang_sayur" value="{{ $mSayur ? $mSayur->format('H:i') : '05:30' }}"
                               class="w-full px-2.5 py-2 bg-[#192748] border border-[#2a3d69] rounded-xl text-xs text-white font-mono">
                    </div>
                </div>

                <p class="text-[11px] text-slate-400 leading-relaxed">
                    Batas aman 4 jam dihitung otomatis dari waktu komponen matang terakhir hingga tiba di titik penerimaan.
                </p>
            </div>
        </div>
    </form>
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
                            <th class="py-3 px-4">Porsi dikirim</th>
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
                                    <td class="py-3 px-4">
                                        <input type="number" name="porsi_dikirim" value="{{ $t->porsi_dikirim }}" min="0"
                                               class="w-20 px-2 py-1.5 bg-[#192748] border border-[#2a3d69] rounded text-xs text-white">
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

<script>
    function pilihTahap(tahap, mulai, selesai, petugas, porsi, kendala) {
        document.getElementById('select-tahap').value = tahap;
        if (mulai) document.getElementById('input-mulai-aktual').value = mulai;
        if (selesai) document.getElementById('input-selesai-aktual').value = selesai;
        if (petugas) document.getElementById('input-petugas').value = petugas;
        if (porsi) document.getElementById('input-porsi-kemas').value = porsi;
        document.getElementById('input-kendala').value = kendala || '';

        let labelMap = {
            'terima_bahan': 'Terima bahan',
            'pengolahan': 'Olah',
            'pengemasan': 'Kemas',
            'pengantaran': 'Distribusi'
        };
        document.getElementById('title-form-tahap').innerText = 'Form pencatatan tahap · ' + (labelMap[tahap] || tahap);
    }
</script>
@endsection
