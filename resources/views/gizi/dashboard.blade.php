@extends('layouts.app')

@section('title', 'Dashboard Ahli Gizi / Data Gizi')

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div>
        <h1 class="text-2xl font-bold text-white tracking-tight">Dashboard Ahli Gizi / Data Gizi</h1>
        <p class="text-xs text-slate-400 mt-1">
            Kelola master rujukan nilai gizi (TKPI), hitung komposisi menu, validasi AKG dan publikasikan ke portal publik.
        </p>
    </div>

    <!-- 3 Top Stat Cards -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
        <div class="bg-[#131f38] border border-[#1f3056] p-6 rounded-2xl">
            <div class="text-xs font-semibold text-slate-400">Menunggu perhitungan (Draft)</div>
            <div class="text-4xl font-extrabold text-white mt-2">{{ $menungguHitungCount }} menu</div>
            <div class="text-[11px] text-slate-400 mt-2">Disusun Admin &middot; bahan perlu diperiksa</div>
        </div>

        <div class="bg-[#131f38] border border-[#1f3056] p-6 rounded-2xl">
            <div class="text-xs font-semibold text-slate-400">Menunggu validasi</div>
            <div class="text-4xl font-extrabold text-white mt-2">{{ $menungguValidasiCount }} menu</div>
            <div class="text-[11px] text-slate-400 mt-2">Siap dihitung &amp; divalidasi standar AKG</div>
        </div>

        <div class="bg-[#131f38] border border-[#1f3056] p-6 rounded-2xl">
            <div class="text-xs font-semibold text-slate-400">Tervalidasi / Terpublikasi</div>
            <div class="text-4xl font-extrabold text-white mt-2">{{ $siapPublikasiCount }} / {{ $terpublikasiCount }}</div>
            <div class="text-[11px] text-slate-400 mt-2">Siap tayang / aktif di portal publik</div>
        </div>
    </div>

    <!-- Card 1: Antrean menu -->
    <div class="bg-[#131f38] border border-[#1f3056] p-6 rounded-2xl space-y-4">
        <h2 class="text-sm font-bold text-white">Antrean menu</h2>

        <div class="overflow-x-auto rounded-xl border border-[#1f3056]">
            <table class="w-full text-left text-xs border-collapse">
                <thead>
                    <tr class="bg-[#182645] text-slate-300 font-semibold border-b border-[#1f3056]">
                        <th class="py-3 px-4">Menu / tanggal</th>
                        <th class="py-3 px-4">Kelompok</th>
                        <th class="py-3 px-4">Status</th>
                        <th class="py-3 px-4">Tindakan Ahli Gizi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-[#1f3056]/60 text-slate-300">
                    @forelse($antreanMenus as $m)
                        @php
                            $stBadge = match($m->status) {
                                'terpublikasi' => 'badge-success',
                                'tervalidasi' => 'badge-info',
                                default => 'badge-warning',
                            };
                            $stText = match($m->status) {
                                'draft' => 'Draft',
                                'menunggu_validasi' => 'Menunggu validasi',
                                'tervalidasi' => 'Tervalidasi (belum publikasi)',
                                'terpublikasi' => 'Terpublikasi',
                                default => ucfirst($m->status),
                            };
                        @endphp
                        <tr class="hover:bg-[#182747]/40">
                            <td class="py-3.5 px-4 font-semibold text-white">
                                {{ $m->nama_menu }} &middot; {{ optional($m->tanggal_penyajian)->format('d M Y') }}
                            </td>
                            <td class="py-3.5 px-4">{{ $m->kelompokPenerima?->nama_kelompok ?? 'Semua kelompok' }}</td>
                            <td class="py-3.5 px-4">
                                <span class="badge {{ $stBadge }}">{{ $stText }}</span>
                            </td>
                            <td class="py-3.5 px-4">
                                <a href="{{ route('gizi.hitung.validasi', ['menu_id' => $m->id]) }}" class="text-sky-300 hover:text-white font-medium underline">
                                    {{ $m->status === 'tervalidasi' ? 'Tinjau & publikasikan' : ($m->status === 'terpublikasi' ? 'Lihat / Kelola Publikasi' : 'Hitung & validasi') }}
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="py-6 text-center text-slate-400">Belum ada menu dalam antrean.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div>
            <a href="{{ route('gizi.hitung.validasi') }}" class="inline-flex items-center gap-1.5 px-4 py-2.5 bg-[#192748] hover:bg-[#20335e] border border-[#2a3d69] text-xs font-semibold rounded-xl text-white transition">
                <span>Buka Hitung &amp; Validasi</span>
            </a>
        </div>
    </div>

    <!-- Card 2: Master data gizi bahan -->
    <div class="bg-[#131f38] border border-[#1f3056] p-6 rounded-2xl space-y-4">
        <h2 class="text-sm font-bold text-white">Master data gizi bahan &middot; CRUD khusus Ahli Gizi</h2>

        <form method="GET" action="{{ route('gizi.dashboard') }}" class="flex flex-col sm:flex-row sm:items-end justify-between gap-4">
            <div class="w-full sm:w-2/3 flex items-end gap-2">
                <div class="flex-1">
                    <label class="block text-xs font-semibold text-slate-300 mb-1.5">Cari bahan rujukan</label>
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="Ketik nama bahan (misal: Beras, Ayam, Tongkol)..."
                           class="w-full px-3.5 py-2.5 bg-[#192748] border border-[#2a3d69] rounded-xl text-xs text-white placeholder-slate-400 focus:outline-none">
                </div>
                <button type="submit" class="px-4 py-2.5 bg-[#192748] hover:bg-[#20335e] border border-[#2a3d69] text-xs font-semibold rounded-xl text-white transition cursor-pointer">
                    Cari
                </button>
                @if(request('search'))
                    <a href="{{ route('gizi.dashboard') }}" class="px-3.5 py-2.5 bg-[#192748] border border-[#2a3d69] text-xs rounded-xl text-slate-300 hover:text-white">Reset</a>
                @endif
            </div>
            <div>
                <a href="#form-gizi" onclick="resetGiziForm()" class="inline-flex items-center gap-1 px-4 py-2.5 bg-[#192748] hover:bg-[#20335e] border border-[#2a3d69] text-xs font-semibold rounded-xl text-white transition whitespace-nowrap">
                    <span>+ Tambah Data Gizi</span>
                </a>
            </div>
        </form>

        <div class="overflow-x-auto rounded-xl border border-[#1f3056]">
            <table class="w-full text-left text-xs border-collapse">
                <thead>
                    <tr class="bg-[#182645] text-slate-300 font-semibold border-b border-[#1f3056]">
                        <th class="py-3 px-4">Bahan / basis</th>
                        <th class="py-3 px-4">Energi</th>
                        <th class="py-3 px-4">Protein / lemak</th>
                        <th class="py-3 px-4">Karbo / serat</th>
                        <th class="py-3 px-4">Sumber / tindakan</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-[#1f3056]/60 text-slate-300">
                    @forelse($dataGiziList as $dg)
                        <tr class="hover:bg-[#182747]/40">
                            <td class="py-3.5 px-4 font-semibold text-white">
                                {{ $dg->nama_menu ?? $dg->nama_bahan }} &middot; per {{ $dg->basis_berat_gram }} g
                                @if($dg->vitamin_mineral)
                                    <div class="text-[11px] text-slate-400 font-normal">{{ $dg->vitamin_mineral }}</div>
                                @endif
                            </td>
                            <td class="py-3.5 px-4">{{ number_format($dg->energi_kkal, 1) }} kkal</td>
                            <td class="py-3.5 px-4">{{ number_format($dg->protein_g, 1) }} g / {{ number_format($dg->lemak_g, 1) }} g</td>
                            <td class="py-3.5 px-4">{{ number_format($dg->karbohidrat_g, 1) }} g / {{ number_format($dg->serat_g, 1) }} g</td>
                            <td class="py-3.5 px-4 text-xs">
                                <span class="text-slate-400">{{ $dg->sumber_catatan ?: 'TKPI' }}</span>
                                <div class="mt-0.5 space-x-1.5">
                                    <a href="#form-gizi" onclick="fillGiziEdit({{ json_encode($dg) }})" class="text-sky-300 hover:text-white underline">Ubah</a>
                                    <span class="text-slate-600">/</span>
                                    <form method="POST" action="{{ route('gizi.data.destroy', $dg->id) }}" class="inline" onsubmit="return mbgConfirmForm(event, 'Hapus data gizi {{ addslashes($dg->nama_bahan) }}?', 'Hapus Master Data Gizi?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-rose-400 hover:text-rose-300 bg-transparent border-0 p-0 cursor-pointer">Hapus</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="py-6 text-center text-slate-400">Belum ada data gizi bahan terdaftar.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Bottom Section: Form Data Gizi & Info Links -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start" id="form-gizi">
        <!-- Left Column: Tambah / Ubah data gizi -->
        <div class="lg:col-span-7 bg-[#131f38] border border-[#1f3056] p-6 rounded-2xl space-y-4">
            <h2 class="text-sm font-bold text-white" id="title-gizi-form">
                {{ $editingGizi ? 'Ubah data gizi · ' . $editingGizi->nama_bahan : 'Tambah / Ubah data gizi bahan' }}
            </h2>

            <form id="gizi-form" method="POST" action="{{ $editingGizi ? route('gizi.data.update', $editingGizi->id) : route('gizi.data.store') }}" class="space-y-4">
                @csrf
                <input type="hidden" id="gizi-edit-id" value="{{ $editingGizi?->id ?? '' }}">

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-semibold text-slate-300 mb-1.5">Nama bahan *</label>
                        <input type="text" name="nama_bahan" id="input-gizi-bahan" list="daftar-nama-bahan"
                               value="{{ old('nama_bahan', $editingGizi?->nama_bahan ?? '') }}" required placeholder="Contoh: Beras putih"
                               class="w-full px-3.5 py-2.5 bg-[#192748] border border-[#2a3d69] rounded-xl text-xs text-white focus:outline-none">
                        <datalist id="daftar-nama-bahan">
                            @foreach(\App\Models\BahanBaku::select('nama_bahan')->distinct()->get() as $bbOpt)
                                <option value="{{ $bbOpt->nama_bahan }}">
                            @endforeach
                        </datalist>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-300 mb-1.5">Basis berat (gram) *</label>
                        <input type="number" step="1" min="1" name="basis_berat_gram" id="input-gizi-basis"
                               value="{{ old('basis_berat_gram', $editingGizi?->basis_berat_gram ?? 100) }}" required
                               class="w-full px-3.5 py-2.5 bg-[#192748] border border-[#2a3d69] rounded-xl text-xs text-white focus:outline-none">
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-xs font-semibold text-slate-300 mb-1.5">Energi (kkal) *</label>
                        <input type="number" step="0.01" min="0" name="energi_kkal" id="input-gizi-energi"
                               value="{{ old('energi_kkal', $editingGizi?->energi_kkal ?? '') }}" required
                               class="w-full px-3.5 py-2.5 bg-[#192748] border border-[#2a3d69] rounded-xl text-xs text-white focus:outline-none">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-300 mb-1.5">Protein (g) *</label>
                        <input type="number" step="0.01" min="0" name="protein_g" id="input-gizi-protein"
                               value="{{ old('protein_g', $editingGizi?->protein_g ?? '') }}" required
                               class="w-full px-3.5 py-2.5 bg-[#192748] border border-[#2a3d69] rounded-xl text-xs text-white focus:outline-none">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-300 mb-1.5">Lemak (g) *</label>
                        <input type="number" step="0.01" min="0" name="lemak_g" id="input-gizi-lemak"
                               value="{{ old('lemak_g', $editingGizi?->lemak_g ?? '') }}" required
                               class="w-full px-3.5 py-2.5 bg-[#192748] border border-[#2a3d69] rounded-xl text-xs text-white focus:outline-none">
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-xs font-semibold text-slate-300 mb-1.5">Karbohidrat (g) *</label>
                        <input type="number" step="0.01" min="0" name="karbohidrat_g" id="input-gizi-karbo"
                               value="{{ old('karbohidrat_g', $editingGizi?->karbohidrat_g ?? '') }}" required
                               class="w-full px-3.5 py-2.5 bg-[#192748] border border-[#2a3d69] rounded-xl text-xs text-white focus:outline-none">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-300 mb-1.5">Serat (g)</label>
                        <input type="number" step="0.01" min="0" name="serat_g" id="input-gizi-serat"
                               value="{{ old('serat_g', $editingGizi?->serat_g ?? 0) }}"
                               class="w-full px-3.5 py-2.5 bg-[#192748] border border-[#2a3d69] rounded-xl text-xs text-white focus:outline-none">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-300 mb-1.5">Vitamin / mineral</label>
                        <input type="text" name="vitamin_mineral" id="input-gizi-vit"
                               value="{{ old('vitamin_mineral', $editingGizi?->vitamin_mineral ?? '') }}" placeholder="Contoh: Fe 1.8 mg, Ca 10 mg"
                               class="w-full px-3.5 py-2.5 bg-[#192748] border border-[#2a3d69] rounded-xl text-xs text-white focus:outline-none">
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-300 mb-1.5">Sumber / catatan rujukan</label>
                    <input type="text" name="sumber_catatan" id="input-gizi-sumber"
                           value="{{ old('sumber_catatan', $editingGizi?->sumber_catatan ?? 'Tabel Komposisi Pangan Indonesia (TKPI)') }}"
                           class="w-full px-3.5 py-2.5 bg-[#192748] border border-[#2a3d69] rounded-xl text-xs text-white focus:outline-none">
                </div>

                <div class="flex items-center gap-3 pt-2">
                    <button type="submit" class="px-5 py-2.5 bg-[#192748] hover:bg-[#20335e] border border-[#2a3d69] text-xs font-semibold rounded-xl text-white transition cursor-pointer">
                        Simpan Data Gizi
                    </button>
                    <button type="button" onclick="resetGiziForm()" class="px-5 py-2.5 bg-white hover:bg-slate-100 text-[#0b1329] text-xs font-bold rounded-xl transition cursor-pointer">
                        Batal
                    </button>
                </div>
            </form>
        </div>

        <!-- Right Column: 3 Cards -->
        <div class="lg:col-span-5 space-y-4">
            <!-- Card 1: Menu dan bahan · hanya lihat -->
            <div class="bg-[#131f38] border border-[#1f3056] p-5 rounded-2xl space-y-3">
                <h3 class="text-xs font-bold text-white">Menu dan bahan &middot; hanya lihat</h3>
                <p class="text-[11px] text-slate-300 leading-relaxed">
                    Bahan dan porsi diisi Admin SPPG. Jika komposisi belum lengkap atau perlu disesuaikan AKG, gunakan tombol "Minta Revisi" pada halaman Hitung &amp; Validasi.
                </p>
                <div>
                    <a href="{{ route('gizi.menu.lihat') }}" class="inline-block px-4 py-2 bg-[#192748] hover:bg-[#20335e] border border-[#2a3d69] text-xs font-semibold rounded-xl text-white transition">
                        Lihat Menu dan Bahan
                    </a>
                </div>
            </div>

            <!-- Card 2: Prediksi Risiko & KPI Mingguan · hanya lihat -->
            <div class="bg-[#131f38] border border-[#1f3056] p-5 rounded-2xl space-y-3">
                <h3 class="text-xs font-bold text-white">Prediksi Risiko &amp; KPI Mingguan &middot; hanya lihat</h3>
                <p class="text-[11px] text-slate-300 leading-relaxed">
                    Pantau prediksi kelayakan konsumsi Random Forest pra-distribusi per titik antar, serta rekapitulasi 7 indikator KPI Mingguan (1x seminggu) secara terpisah.
                </p>
                <div class="flex flex-wrap gap-2">
                    <a href="{{ route('gizi.prediksi.lihat') }}" class="inline-block px-4 py-2 bg-[#192748] hover:bg-[#20335e] border border-[#2a3d69] text-xs font-semibold rounded-xl text-white transition">
                        Prediksi Risiko
                    </a>
                    <a href="{{ route('gizi.kpi.lihat') }}" class="inline-block px-4 py-2 bg-[#192748] hover:bg-[#20335e] border border-[#2a3d69] text-xs font-semibold rounded-xl text-white transition">
                        KPI Mingguan
                    </a>
                </div>
            </div>

            <!-- Card 3: Total master gizi -->
            <div class="bg-[#131f38] border border-[#1f3056] p-5 rounded-2xl space-y-2">
                <h3 class="text-xs font-bold text-white">Cakupan tabel TKPI aktif</h3>
                <p class="text-[11px] text-slate-300 leading-relaxed">
                    Tersedia <strong>{{ $dataGiziList->count() }} bahan rujukan</strong> tersimpan. Pastikan nama bahan di Master Data Gizi cocok dengan nama Bahan Baku pada menu agar perhitungan otomatis berjalan akurat.
                </p>
            </div>
        </div>
    </div>
</div>

<script>
    function fillGiziEdit(item) {
        document.getElementById('gizi-edit-id').value = item.id;
        document.getElementById('input-gizi-bahan').value = item.nama_bahan;
        document.getElementById('input-gizi-basis').value = item.basis_berat_gram || 100;
        document.getElementById('input-gizi-energi').value = item.energi_kkal;
        document.getElementById('input-gizi-protein').value = item.protein_g;
        document.getElementById('input-gizi-lemak').value = item.lemak_g;
        document.getElementById('input-gizi-karbo').value = item.karbohidrat_g;
        document.getElementById('input-gizi-serat').value = item.serat_g ?? 0;
        document.getElementById('input-gizi-vit').value = item.vitamin_mineral || '';
        document.getElementById('input-gizi-sumber').value = item.sumber_catatan || '';
        document.getElementById('title-gizi-form').innerText = 'Ubah data gizi · ' + item.nama_bahan;
        document.getElementById('gizi-form').action = '/gizi/data-gizi/' + item.id + '/update';
    }

    function resetGiziForm() {
        document.getElementById('title-gizi-form').innerText = 'Tambah / Ubah data gizi bahan';
        document.getElementById('gizi-edit-id').value = '';
        document.getElementById('gizi-form').reset();
        document.getElementById('input-gizi-basis').value = 100;
        document.getElementById('input-gizi-sumber').value = 'Tabel Komposisi Pangan Indonesia (TKPI)';
        document.getElementById('gizi-form').action = "{{ route('gizi.data.store') }}";
    }
</script>
@endsection
