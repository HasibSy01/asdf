@extends('layouts.app')

@section('title', 'Data Menu')

@section('content')
@php
    $isCreateMode = request()->boolean('create') || !$editingMenu;
    $activeFormMenu = $isCreateMode ? null : $editingMenu;
@endphp
<div class="space-y-6">
    <!-- Header -->
    <div class="page-header">
        <h1 class="page-title">Data Menu / Tambah atau Ubah Menu</h1>
        <p class="page-subtitle">
            Admin mengelola menu, komposisi dan porsi. Nilai gizi, validasi serta publikasi ditangani Ahli Gizi.
        </p>
    </div>

    @if($activeFormMenu && $activeFormMenu->catatan_revisi)
        <div class="alert-box alert-danger">
            <strong class="text-rose-300 block mb-1">Catatan Revisi dari Ahli Gizi ({{ $activeFormMenu->nama_menu }}):</strong>
            <span class="text-xs text-rose-200">{{ $activeFormMenu->catatan_revisi }}</span>
        </div>
    @endif

    <!-- Section 1: Daftar Menu -->
    <div class="card">
        <h2 class="card-title">Daftar menu</h2>

        <form method="GET" action="{{ route('admin.menu.index') }}" class="flex flex-col sm:flex-row sm:flex-wrap sm:items-center gap-3 mb-4">
            <div class="w-full sm:flex-1 sm:min-w-[200px]">
                <input type="text" name="search" value="{{ request('search') }}" class="form-input" placeholder="Cari nama menu...">
            </div>
            <div class="w-full sm:w-48">
                <select name="status" class="form-select">
                    <option value="">Semua status</option>
                    <option value="draft" {{ request('status') === 'draft' ? 'selected' : '' }}>Draft</option>
                    <option value="menunggu_validasi" {{ request('status') === 'menunggu_validasi' ? 'selected' : '' }}>Menunggu validasi</option>
                    <option value="tervalidasi" {{ request('status') === 'tervalidasi' ? 'selected' : '' }}>Tervalidasi</option>
                    <option value="terpublikasi" {{ request('status') === 'terpublikasi' ? 'selected' : '' }}>Terpublikasi</option>
                </select>
            </div>
            <div class="flex flex-wrap items-center gap-2.5">
                <button type="submit" class="btn btn-secondary">Cari</button>
                @if(request('search') || request('status'))
                    <a href="{{ route('admin.menu.index') }}" class="btn btn-secondary">Reset</a>
                @endif
                <a href="{{ route('admin.menu.index', ['create' => 1]) }}#form-menu" class="btn btn-primary">
                    + Tambah Menu
                </a>
            </div>
        </form>

        <div class="table-responsive">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Menu / tanggal</th>
                        <th>Kelompok</th>
                        <th>Status</th>
                        <th>Tindakan Admin</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($menus as $m)
                        @php
                            $statusLabel = match($m->status) {
                                'draft' => 'Draft',
                                'menunggu_validasi' => 'Menunggu validasi',
                                'tervalidasi' => 'Tervalidasi',
                                'terpublikasi' => 'Terpublikasi',
                                default => ucfirst($m->status),
                            };
                        @endphp
                        <tr>
                            <td class="font-medium text-white">
                                {{ $m->nama_menu }} &middot; {{ optional($m->tanggal_penyajian)->format('d M Y') }}
                                <div class="text-[11px] text-slate-400 font-normal">
                                    {{ $m->jenis_makanan }} &bull; Porsi Besar: {{ $m->porsi_besar_rencana }} ({{ $m->ukuran_porsi_besar_gram ?: $m->ukuran_porsi_gram }}g) &bull; Porsi Kecil: {{ $m->porsi_kecil_rencana }} ({{ $m->ukuran_porsi_kecil_gram ?: 300 }}g)
                                </div>
                            </td>
                            <td class="text-slate-300">{{ $m->kelompokPenerima?->nama_kelompok ?? 'Semua kelompok' }}</td>
                            <td class="text-slate-300">
                                <span class="badge {{ $m->status === 'terpublikasi' ? 'badge-success' : ($m->status === 'tervalidasi' ? 'badge-info' : 'badge-warning') }}">
                                    {{ $statusLabel }}
                                </span>
                            </td>
                            <td>
                                <div class="flex flex-wrap items-center gap-2 text-xs">
                                    <a href="{{ route('admin.menu.index', ['edit' => $m->id]) }}#form-menu" class="text-slate-300 hover:text-white underline">
                                        Lihat / Ubah
                                    </a>
                                    @if($m->status === 'draft')
                                        <span class="text-slate-600">/</span>
                                        <form method="POST" action="{{ route('admin.menu.kirim_validasi', $m->id) }}" style="display:inline;">
                                            @csrf
                                            <button type="submit" class="text-sky-300 hover:text-sky-200 underline bg-transparent border-0 cursor-pointer p-0 text-xs">
                                                Ajukan Validasi
                                            </button>
                                        </form>
                                    @endif
                                    @if($m->status !== 'terpublikasi')
                                        <span class="text-slate-600">/</span>
                                        <form method="POST" action="{{ route('admin.menu.destroy', $m->id) }}" style="display:inline;" onsubmit="return mbgConfirmForm(event, 'Hapus menu {{ addslashes($m->nama_menu) }}? Data riwayat yang terkait akan ikut diperiksa.', 'Hapus Data Menu?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="text-rose-400 hover:text-rose-300 bg-transparent border-0 cursor-pointer p-0 text-xs">
                                                Hapus
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="text-center text-slate-400 py-6">Tidak ada data menu ditemukan.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($menus->hasPages())
            <div class="mt-4 text-xs text-slate-300">
                {{ $menus->links() }}
            </div>
        @endif
    </div>

    <!-- Section 2: Tambah atau Ubah Menu -->
    <div class="card" id="form-menu">
        <div class="flex items-center justify-between flex-wrap gap-2 mb-4">
            <h2 class="card-title mb-0">
                @if($activeFormMenu)
                    Ubah menu &middot; {{ $activeFormMenu->nama_menu }}
                @else
                    Tambah menu baru
                @endif
            </h2>
            @if($activeFormMenu)
                <a href="{{ route('admin.menu.index', ['create' => 1]) }}#form-menu" class="text-xs text-sky-300 hover:underline">
                    + Buat menu baru saja
                </a>
            @endif
        </div>

        <form method="POST" action="{{ $activeFormMenu ? route('admin.menu.update', $activeFormMenu->id) : route('admin.menu.store') }}" enctype="multipart/form-data">
            @csrf

            <div class="grid-2">
                <!-- Left Form -->
                <div>
                    <div class="form-group">
                        <label class="form-label">Nama menu *</label>
                        <input type="text" name="nama_menu" value="{{ old('nama_menu', $activeFormMenu?->nama_menu ?? '') }}" required class="form-input" placeholder="Contoh: Paket Nasi Ayam Semur & Tempe">
                    </div>

                    <div class="grid-2 gap-4">
                        <div class="form-group">
                            <label class="form-label">Tanggal penyajian *</label>
                            <input type="date" name="tanggal_penyajian" value="{{ old('tanggal_penyajian', optional($activeFormMenu?->tanggal_penyajian)->format('Y-m-d') ?? date('Y-m-d')) }}" required class="form-input">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Jenis makanan *</label>
                            @php $jenisVal = old('jenis_makanan', $activeFormMenu?->jenis_makanan ?? 'Makanan lengkap'); @endphp
                            <select name="jenis_makanan" class="form-select">
                                <option value="Makanan lengkap" {{ $jenisVal === 'Makanan lengkap' ? 'selected' : '' }}>Makanan lengkap</option>
                                <option value="Kudapan bergizi" {{ $jenisVal === 'Kudapan bergizi' ? 'selected' : '' }}>Kudapan bergizi</option>
                            </select>
                        </div>
                    </div>

                    <div class="grid-2 gap-4">
                        <div class="form-group">
                            <label class="form-label">Ukuran Porsi Besar (gram) *</label>
                            <input type="number" name="ukuran_porsi_besar_gram" min="50" value="{{ old('ukuran_porsi_besar_gram', $activeFormMenu?->ukuran_porsi_besar_gram ?? $activeFormMenu?->ukuran_porsi_gram ?? 450) }}" required class="form-input" placeholder="450">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Ukuran Porsi Kecil (gram) *</label>
                            <input type="number" name="ukuran_porsi_kecil_gram" min="50" value="{{ old('ukuran_porsi_kecil_gram', $activeFormMenu?->ukuran_porsi_kecil_gram ?? 300) }}" required class="form-input" placeholder="300">
                        </div>
                    </div>

                    <div class="grid-2 gap-4">
                        <div class="form-group">
                            <label class="form-label">Target Porsi Besar *</label>
                            <input type="number" name="porsi_besar_rencana" min="0" value="{{ old('porsi_besar_rencana', $activeFormMenu?->porsi_besar_rencana ?? 190) }}" required class="form-input" placeholder="190">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Target Porsi Kecil *</label>
                            <input type="number" name="porsi_kecil_rencana" min="0" value="{{ old('porsi_kecil_rencana', $activeFormMenu?->porsi_kecil_rencana ?? 110) }}" required class="form-input" placeholder="110">
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Kelompok penerima *</label>
                        @php $kelVal = old('kelompok_penerima_id', $activeFormMenu?->kelompok_penerima_id); @endphp
                        <select name="kelompok_penerima_id" class="form-select">
                            <option value="">Semua kelompok / Tahap 1</option>
                            @foreach($kelompoks as $kel)
                                <option value="{{ $kel->id }}" {{ (string)$kelVal === (string)$kel->id ? 'selected' : '' }}>
                                    {{ $kel->kategori }} &middot; {{ $kel->nama_kelompok }} ({{ $kel->jumlah_penerima }} penerima)
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <!-- Right Box: Foto & Status -->
                <div class="flex flex-col justify-between">
                    <div>
                        <div id="foto-preview-box" class="w-full h-44 rounded-xl border border-dashed border-[#2b4170] bg-[#1a284a] flex flex-col items-center justify-center text-slate-400 text-xs mb-3 overflow-hidden">
                            @if($activeFormMenu && $activeFormMenu->foto_menu && file_exists(public_path($activeFormMenu->foto_menu)))
                                <img id="foto-preview-img" src="{{ asset($activeFormMenu->foto_menu) }}" alt="{{ $activeFormMenu->nama_menu }}" class="w-full h-full object-cover">
                            @else
                                <img id="foto-preview-img" src="" alt="" class="w-full h-full object-cover" style="display:none;">
                                <div id="foto-placeholder" class="flex flex-col items-center">
                                    <span class="text-2xl mb-1 text-slate-500">+</span>
                                    <span>Unggah foto menu (JPG/PNG, maks 2 MB)</span>
                                </div>
                            @endif
                        </div>
                        <input type="file" id="foto_menu_input" name="foto_menu" accept="image/jpeg,image/png,image/webp" class="hidden" onchange="previewMenuPhoto(this)">
                        <button type="button" onclick="document.getElementById('foto_menu_input').click()" class="btn btn-secondary text-xs mb-4">
                            Unggah / Ganti Foto
                        </button>
                    </div>

                    <div class="space-y-1">
                        @php $currStatus = $activeFormMenu?->status ?? 'draft'; @endphp
                        <span class="badge badge-amber mb-1">
                            Status: {{ ucfirst(str_replace('_', ' ', $currStatus)) }}
                        </span>
                        <div class="text-[11px] text-slate-400">
                            @if($currStatus === 'terpublikasi' || $currStatus === 'tervalidasi')
                                Peringatan FR-007: Mengubah komposisi bahan akan otomatis mengembalikan status ke <strong>Menunggu Validasi</strong> agar ditinjau ulang Ahli Gizi.
                            @else
                                Status diteruskan ke Ahli Gizi untuk validasi AKG, bukan dipublikasikan langsung oleh Admin.
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            <!-- Table: Bahan dan jumlah untuk sajian rencana -->
            <div class="pt-4 border-t border-[#213359] mt-6">
                <div class="flex items-center justify-between flex-wrap gap-2 mb-3">
                    <div class="text-xs font-bold text-white">
                        Bahan dan takaran untuk Porsi Besar ({{ $activeFormMenu?->porsi_besar_rencana ?? 190 }}) &amp; Porsi Kecil ({{ $activeFormMenu?->porsi_kecil_rencana ?? 110 }})
                    </div>
                    <button type="button" onclick="tambahBarisBahan()" class="btn btn-secondary text-xs">
                        + Tambah Bahan
                    </button>
                </div>

                <div class="table-responsive">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Bahan terkait stok gudang</th>
                                <th style="width: 100px;">Jumlah batch</th>
                                <th style="width: 80px;">Satuan</th>
                                <th style="width: 125px;">Komponen</th>
                                <th style="width: 115px;">Gram Porsi Besar</th>
                                <th style="width: 115px;">Gram Porsi Kecil</th>
                                <th style="width: 70px;">Aksi</th>
                            </tr>
                        </thead>
                        <tbody id="menu-bahan-rows">
                            @php
                                $existingBahans = $activeFormMenu?->menuBahans ?? collect();
                            @endphp
                            @if($existingBahans->isNotEmpty())
                                @foreach($existingBahans as $mb)
                                    <tr class="bahan-row">
                                        <td>
                                            <select name="bahan_ids[]" class="form-select text-xs">
                                                <option value="">-- Pilih bahan baku --</option>
                                                @foreach($bahanBakus as $b)
                                                    <option value="{{ $b->id }}" {{ (string)$mb->bahan_baku_id === (string)$b->id ? 'selected' : '' }}>
                                                        {{ $b->nama_bahan }} (lot {{ $b->kode_lot }}{{ $b->tanggal_kedaluwarsa && $b->tanggal_kedaluwarsa->lt(\Carbon\Carbon::today()) ? ' · KEDALUWARSA!' : '' }})
                                                    </option>
                                                @endforeach
                                            </select>
                                        </td>
                                        <td><input type="number" step="0.1" name="jumlah_batch[]" value="{{ $mb->jumlah_batch }}" class="form-input text-xs"></td>
                                        <td><input type="text" name="satuan[]" value="{{ $mb->satuan }}" class="form-input text-xs"></td>
                                        <td><input type="text" name="komponen[]" value="{{ $mb->komponen }}" class="form-input text-xs" placeholder="Nasi / Lauk / Sayur"></td>
                                        <td><input type="number" step="0.1" name="berat_porsi_besar[]" value="{{ $mb->berat_porsi_besar_gram > 0 ? $mb->berat_porsi_besar_gram : $mb->berat_per_sajian_gram }}" class="form-input text-xs"></td>
                                        <td><input type="number" step="0.1" name="berat_porsi_kecil[]" value="{{ $mb->berat_porsi_kecil_gram > 0 ? $mb->berat_porsi_kecil_gram : round($mb->berat_per_sajian_gram * 0.68, 1) }}" class="form-input text-xs"></td>
                                        <td>
                                            <button type="button" onclick="hapusBarisBahan(this)" class="text-rose-400 hover:text-rose-300 bg-transparent border-0 cursor-pointer text-xs">Hapus</button>
                                        </td>
                                    </tr>
                                @endforeach
                            @else
                                <tr class="bahan-row">
                                    <td>
                                        <select name="bahan_ids[]" class="form-select text-xs">
                                            <option value="">-- Pilih bahan baku --</option>
                                            @foreach($bahanBakus as $b)
                                                <option value="{{ $b->id }}">
                                                    {{ $b->nama_bahan }} (lot {{ $b->kode_lot }}{{ $b->tanggal_kedaluwarsa && $b->tanggal_kedaluwarsa->lt(\Carbon\Carbon::today()) ? ' · KEDALUWARSA!' : '' }})
                                                </option>
                                            @endforeach
                                        </select>
                                    </td>
                                    <td><input type="number" step="0.1" name="jumlah_batch[]" value="20" class="form-input text-xs"></td>
                                    <td><input type="text" name="satuan[]" value="kg" class="form-input text-xs"></td>
                                    <td><input type="text" name="komponen[]" value="Nasi" class="form-input text-xs"></td>
                                    <td><input type="number" step="0.1" name="berat_porsi_besar[]" value="80" class="form-input text-xs"></td>
                                    <td><input type="number" step="0.1" name="berat_porsi_kecil[]" value="55" class="form-input text-xs"></td>
                                    <td>
                                        <button type="button" onclick="hapusBarisBahan(this)" class="text-rose-400 hover:text-rose-300 bg-transparent border-0 cursor-pointer text-xs">Hapus</button>
                                    </td>
                                </tr>
                            @endif
                        </tbody>
                    </table>
                </div>

                <!-- Action Buttons Row -->
                <div class="flex flex-wrap items-center gap-3 mt-6">
                    <button type="submit" name="action_type" value="simpan" class="btn btn-secondary">
                        {{ $activeFormMenu ? 'Simpan Perubahan Menu' : 'Simpan sebagai Draft' }}
                    </button>
                    <button type="submit" name="action_type" value="kirim_validasi" class="btn btn-primary">
                        Simpan &amp; Ajukan Validasi Ahli Gizi
                    </button>
                    <a href="{{ route('admin.menu.index') }}" class="btn btn-secondary">
                        Batal
                    </a>
                </div>
            </div>
        </form>
    </div>

    <!-- Section 3: 2 Bottom Cards -->
    <div class="grid-2">
        <!-- Nilai Gizi · Hanya Baca -->
        <div class="card flex flex-col justify-between">
            <div>
                <h3 class="card-title">Nilai gizi &middot; hanya baca {{ $activeFormMenu ? '(' . $activeFormMenu->nama_menu . ')' : '' }}</h3>
                
                @if($activeFormMenu && $editingGizi)
                    <div class="space-y-2 mb-3 text-xs text-slate-300 leading-relaxed">
                        <div>
                            <span class="badge badge-info mb-1">Porsi Besar</span><br>
                            <strong class="text-white">Energi {{ number_format($editingGizi['energi_kkal'], 1) }} kkal</strong> &bull;
                            Protein {{ number_format($editingGizi['protein_g'], 1) }} g &bull;
                            Lemak {{ number_format($editingGizi['lemak_g'], 1) }} g &bull;
                            Karbo {{ number_format($editingGizi['karbohidrat_g'], 1) }} g &bull;
                            Serat {{ number_format($editingGizi['serat_g'], 1) }} g.
                        </div>
                        @if(!empty($editingGiziKecil))
                            <div class="pt-1 border-t border-[#213359]">
                                <span class="badge badge-amber mb-1">Porsi Kecil</span><br>
                                <strong class="text-white">Energi {{ number_format($editingGiziKecil['energi_kkal'], 1) }} kkal</strong> &bull;
                                Protein {{ number_format($editingGiziKecil['protein_g'], 1) }} g &bull;
                                Lemak {{ number_format($editingGiziKecil['lemak_g'], 1) }} g &bull;
                                Karbo {{ number_format($editingGiziKecil['karbohidrat_g'], 1) }} g &bull;
                                Serat {{ number_format($editingGiziKecil['serat_g'], 1) }} g.
                            </div>
                        @endif
                    </div>
                    @if(!$editingGizi['is_complete'])
                        <div class="p-3 rounded-lg bg-amber-950/40 border border-amber-800/50 text-xs text-amber-300 mb-3">
                            Data gizi belum lengkap untuk bahan: {{ implode(', ', $editingGizi['missing_data']) }}. Hubungi Ahli Gizi untuk melengkapi Data Gizi.
                        </div>
                    @endif
                @else
                    <p class="text-xs text-slate-300 leading-relaxed mb-3">
                        Tambahkan komposisi bahan per sajian agar sistem menghitung estimasi gizi otomatis untuk ditinjau Ahli Gizi.
                    </p>
                @endif

                <p class="text-xs text-slate-400 leading-relaxed mb-3">
                    Hanya Ahli Gizi dapat mengubah master nilai gizi, memvalidasi kelayakan AKG, dan mempublikasikan menu ke portal publik.
                </p>
            </div>

            <div class="text-[11px] text-slate-400 pt-3 border-t border-[#213359]">
                Alur status: Draft &rarr; Menunggu validasi &rarr; Tervalidasi &rarr; Terpublikasi.
            </div>
        </div>

        <!-- Catatan Integritas & Aturan -->
        <div class="card flex flex-col justify-between">
            <div>
                <h3 class="card-title">Aturan integritas data</h3>
                
                <p class="text-xs text-slate-300 leading-relaxed mb-4">
                    Menu yang sudah berstatus <strong>Tervalidasi</strong> atau <strong>Terpublikasi</strong> dilindungi dari penghapusan langsung dan tidak bisa berubah komposisinya tanpa validasi ulang Ahli Gizi.
                </p>
            </div>

            <div class="pt-3 border-t border-[#213359] text-[11px] text-slate-400">
                Data historis yang terkait dengan KPI dan penelusuran alur terlindungi dari penghapusan tidak sengaja. Setiap perubahan dicatat ke Audit Log.
            </div>
        </div>
    </div>
</div>

<template id="bahan-row-template">
    <tr class="bahan-row">
        <td>
            <select name="bahan_ids[]" class="form-select text-xs">
                <option value="">-- Pilih bahan baku --</option>
                @foreach($bahanBakus as $b)
                    <option value="{{ $b->id }}">
                        {{ $b->nama_bahan }} (lot {{ $b->kode_lot }}{{ $b->tanggal_kedaluwarsa && $b->tanggal_kedaluwarsa->lt(\Carbon\Carbon::today()) ? ' · KEDALUWARSA!' : '' }})
                    </option>
                @endforeach
            </select>
        </td>
        <td><input type="number" step="0.1" name="jumlah_batch[]" value="10" class="form-input text-xs"></td>
        <td><input type="text" name="satuan[]" value="kg" class="form-input text-xs"></td>
        <td><input type="text" name="komponen[]" value="Lauk" class="form-input text-xs"></td>
        <td><input type="number" step="0.1" name="berat_porsi_besar[]" value="50" class="form-input text-xs"></td>
        <td><input type="number" step="0.1" name="berat_porsi_kecil[]" value="35" class="form-input text-xs"></td>
        <td>
            <button type="button" onclick="hapusBarisBahan(this)" class="text-rose-400 hover:text-rose-300 bg-transparent border-0 cursor-pointer text-xs">Hapus</button>
        </td>
    </tr>
</template>

@push('scripts')
<script>
function tambahBarisBahan() {
    var tpl = document.getElementById('bahan-row-template');
    var tbody = document.getElementById('menu-bahan-rows');
    if (tpl && tbody) {
        tbody.appendChild(tpl.content.cloneNode(true));
    }
}
function hapusBarisBahan(btn) {
    var tbody = document.getElementById('menu-bahan-rows');
    if (tbody.querySelectorAll('.bahan-row').length > 1) {
        btn.closest('tr').remove();
    } else {
        mbgAlert('Minimal sisakan 1 baris atau kosongkan pilihan bahan.', 'Baris Minimal Bahan');
    }
}
function previewMenuPhoto(input) {
    if (input.files && input.files[0]) {
        var reader = new FileReader();
        reader.onload = function(e) {
            var img = document.getElementById('foto-preview-img');
            var ph = document.getElementById('foto-placeholder');
            img.src = e.target.result;
            img.style.display = 'block';
            if (ph) ph.style.display = 'none';
        };
        reader.readAsDataURL(input.files[0]);
    }
}
</script>
@endpush
@endsection
