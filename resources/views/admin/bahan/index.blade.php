@extends('layouts.app')

@section('title', 'Bahan Baku')

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div>
        <h1 class="text-2xl font-bold text-white tracking-tight">Bahan Baku</h1>
        <p class="text-xs text-slate-400 mt-1">
            Kelola stok, masa simpan dan keterkaitan bahan dengan menu. Ringkasan aktif: {{ number_format($totalStokKg, 1, ',', '.') }} total unit stok &bull; {{ $kedaluwarsaCount }} kedaluwarsa &bull; {{ $mendekatiCount }} mendekati kedaluwarsa.
        </p>
    </div>

    <!-- Card: Daftar bahan -->
    <div class="bg-[#131f38] border border-[#1f3056] p-6 rounded-2xl space-y-5">
        <h2 class="text-sm font-bold text-white">Daftar bahan baku gudang</h2>

        <!-- Filters Row -->
        <form method="GET" action="{{ route('admin.bahan.index') }}" class="grid grid-cols-1 md:grid-cols-4 gap-4 items-end">
            <div>
                <label class="block text-xs font-semibold text-slate-300 mb-1.5">Cari bahan / lot</label>
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Nama bahan, lot, atau supplier..."
                       class="w-full px-3.5 py-2.5 bg-[#192748] border border-[#2a3d69] rounded-xl text-xs text-white placeholder-slate-400 focus:outline-none focus:border-blue-400">
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-300 mb-1.5">Masa simpan</label>
                <select name="status" onchange="this.form.submit()" class="w-full px-3.5 py-2.5 bg-[#192748] border border-[#2a3d69] rounded-xl text-xs text-white focus:outline-none">
                    <option value="">Semua status</option>
                    <option value="aman" {{ request('status') == 'aman' ? 'selected' : '' }}>Valid / Aman</option>
                    <option value="mendekati" {{ request('status') == 'mendekati' ? 'selected' : '' }}>Segera pakai (&le; 2 hari)</option>
                    <option value="kedaluwarsa" {{ request('status') == 'kedaluwarsa' ? 'selected' : '' }}>Kedaluwarsa</option>
                </select>
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-300 mb-1.5">Telusuri menu terkait</label>
                <select name="menu_id" onchange="this.form.submit()" class="w-full px-3.5 py-2.5 bg-[#192748] border border-[#2a3d69] rounded-xl text-xs text-white focus:outline-none">
                    @foreach($menus as $m)
                        <option value="{{ $m->id }}" {{ ($selectedTraceMenu && $selectedTraceMenu->id === $m->id) ? 'selected' : '' }}>
                            {{ $m->nama_menu }} &middot; {{ optional($m->tanggal_penyajian)->format('d M') }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="flex items-center gap-2">
                <button type="submit" class="px-4 py-2.5 bg-[#192748] hover:bg-[#20335e] border border-[#2a3d69] text-xs font-semibold rounded-xl text-white transition cursor-pointer">
                    Filter
                </button>
                @if(request('search') || request('status'))
                    <a href="{{ route('admin.bahan.index') }}" class="px-3.5 py-2.5 bg-[#192748] border border-[#2a3d69] text-xs rounded-xl text-slate-300 hover:text-white">
                        Reset
                    </a>
                @endif
            </div>
        </form>

        <!-- Table -->
        <div class="overflow-x-auto rounded-xl border border-[#1f3056]">
            <table class="w-full text-left text-xs border-collapse">
                <thead>
                    <tr class="bg-[#182645] text-slate-300 font-semibold border-b border-[#1f3056]">
                        <th class="py-3 px-4">Bahan / jumlah</th>
                        <th class="py-3 px-4">Bahan utama &amp; supplier</th>
                        <th class="py-3 px-4">Tanggal masuk</th>
                        <th class="py-3 px-4">Kedaluwarsa</th>
                        <th class="py-3 px-4">Status / tindakan</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-[#1f3056]/60">
                    @php $today = \Carbon\Carbon::today(); @endphp
                    @forelse($bahanList as $b)
                        @php
                            $isExpired = $b->tanggal_kedaluwarsa && $b->tanggal_kedaluwarsa->lt($today);
                            $hoursLeft = $b->tanggal_kedaluwarsa ? now()->diffInHours($b->tanggal_kedaluwarsa, false) : 999;
                        @endphp
                        <tr class="hover:bg-[#182747]/40 transition">
                            <td class="py-3.5 px-4 font-semibold text-white">
                                {{ $b->nama_bahan }} <span class="font-mono text-sky-300">{{ $b->kode_lot }}</span> &middot; {{ number_format($b->stok_kg, 1) }} {{ $b->satuan }}
                            </td>
                            <td class="py-3.5 px-4 text-slate-300">
                                {{ $b->is_utama ? 'Ya' : 'Tidak' }} &middot; {{ ucfirst($b->kategori) }}
                                <div class="text-[11px] text-slate-400">{{ $b->supplier }}</div>
                            </td>
                            <td class="py-3.5 px-4 text-slate-300 leading-tight">
                                <div>{{ optional($b->tanggal_masuk)->format('d M Y') }}</div>
                                <div class="text-[11px] text-slate-400">{{ optional($b->tanggal_masuk)->format('H.i') }}</div>
                            </td>
                            <td class="py-3.5 px-4 leading-tight">
                                <div class="{{ $isExpired ? 'text-rose-400 font-semibold' : ($hoursLeft <= 48 ? 'text-amber-400 font-semibold' : 'text-slate-300') }}">
                                    {{ optional($b->tanggal_kedaluwarsa)->format('d M Y') }}
                                </div>
                                <div class="text-[11px] text-slate-400">{{ optional($b->tanggal_kedaluwarsa)->format('H.i') }}</div>
                            </td>
                            <td class="py-3.5 px-4">
                                <div>
                                    @if($isExpired)
                                        <span class="text-rose-400 font-semibold">Kedaluwarsa</span>
                                    @elseif($hoursLeft <= 48)
                                        <span class="text-amber-400 font-semibold">Valid &middot; segera pakai</span>
                                    @else
                                        <span class="text-emerald-400 font-semibold">Valid</span>
                                    @endif
                                </div>
                                <div class="text-[11px] mt-0.5 space-x-1.5">
                                    <a href="#form-bahan" onclick="fillBahanEdit({{ json_encode($b) }})" class="text-slate-400 hover:text-white transition">Ubah</a>
                                    <span class="text-slate-600">/</span>
                                    <form method="POST" action="{{ route('admin.bahan.destroy', $b->id) }}" class="inline" onsubmit="return confirm('Hapus bahan {{ addslashes($b->nama_bahan) }}? Bahan terkait menu tetap akan diperiksa.')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-slate-400 hover:text-rose-400 transition bg-transparent border-0 p-0 cursor-pointer">Hapus</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="py-6 text-center text-slate-400">Belum ada data bahan baku sesuai filter.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($bahanList->hasPages())
            <div class="text-xs text-slate-300">{{ $bahanList->links() }}</div>
        @endif

        <div>
            <a href="#form-bahan" onclick="resetBahanForm()" class="inline-flex items-center gap-1.5 px-4 py-2.5 bg-[#192748] hover:bg-[#20335e] border border-[#2a3d69] text-xs font-semibold rounded-xl text-white transition">
                <span>+ Tambah Bahan</span>
            </a>
        </div>
    </div>

    <!-- 2 Columns: Tambah/Ubah Bahan & Right Panel -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start" id="form-bahan">
        <!-- Left: Form Tambah / Ubah Bahan -->
        <div class="lg:col-span-7 bg-[#131f38] border border-[#1f3056] p-6 rounded-2xl space-y-4">
            <h2 class="text-sm font-bold text-white" id="form-title">Tambah / Ubah bahan baku</h2>

            <form id="bahan-form" method="POST" action="{{ route('admin.bahan.store') }}" class="space-y-4">
                @csrf
                <input type="hidden" name="id" id="edit-id">

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-semibold text-slate-300 mb-1.5">Nama bahan *</label>
                        <input type="text" name="nama_bahan" id="input-nama" value="{{ old('nama_bahan') }}" required placeholder="Contoh: Bayam"
                               class="w-full px-3.5 py-2.5 bg-[#192748] border border-[#2a3d69] rounded-xl text-xs text-white focus:outline-none focus:border-blue-400">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-300 mb-1.5">Kategori komponen *</label>
                        <select name="kategori" id="input-kategori" class="w-full px-3.5 py-2.5 bg-[#192748] border border-[#2a3d69] rounded-xl text-xs text-white focus:outline-none">
                            <option value="nasi">Nasi / Karbohidrat</option>
                            <option value="lauk">Lauk hewani / nabati</option>
                            <option value="sayur" selected>Sayur</option>
                            <option value="buah">Buah</option>
                            <option value="pelengkap">Pelengkap</option>
                        </select>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-semibold text-slate-300 mb-1.5">Jumlah stok *</label>
                        <input type="number" step="0.1" min="0.1" name="stok_kg" id="input-stok" value="{{ old('stok_kg') }}" required placeholder="10"
                               class="w-full px-3.5 py-2.5 bg-[#192748] border border-[#2a3d69] rounded-xl text-xs text-white focus:outline-none focus:border-blue-400">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-300 mb-1.5">Satuan *</label>
                        <select name="satuan" id="input-satuan" class="w-full px-3.5 py-2.5 bg-[#192748] border border-[#2a3d69] rounded-xl text-xs text-white focus:outline-none">
                            <option value="kg">kg</option>
                            <option value="gram">gram</option>
                            <option value="liter">liter</option>
                            <option value="buah">buah</option>
                        </select>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-xs font-semibold text-slate-300 mb-1.5">Bahan utama *</label>
                        <select name="is_utama" id="input-utama" class="w-full px-3.5 py-2.5 bg-[#192748] border border-[#2a3d69] rounded-xl text-xs text-white focus:outline-none">
                            <option value="0">Tidak</option>
                            <option value="1">Ya</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-300 mb-1.5">Kode lot *</label>
                        <input type="text" name="kode_lot" id="input-lot" value="{{ old('kode_lot') }}" required placeholder="B-03"
                               class="w-full px-3.5 py-2.5 bg-[#192748] border border-[#2a3d69] rounded-xl text-xs text-white focus:outline-none focus:border-blue-400 font-mono">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-300 mb-1.5">Supplier *</label>
                        <input type="text" name="supplier" id="input-supplier" value="{{ old('supplier', 'UD Sumber Pangan') }}" required placeholder="UD Sumber Pangan"
                               class="w-full px-3.5 py-2.5 bg-[#192748] border border-[#2a3d69] rounded-xl text-xs text-white focus:outline-none focus:border-blue-400">
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-300 mb-1.5">Tanggal dan jam masuk *</label>
                    <input type="datetime-local" name="tanggal_masuk" id="input-masuk" value="{{ old('tanggal_masuk', date('Y-m-d\TH:i')) }}" required
                           class="w-full px-3.5 py-2.5 bg-[#192748] border border-[#2a3d69] rounded-xl text-xs text-white focus:outline-none focus:border-blue-400">
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-300 mb-1.5">Tanggal dan jam kedaluwarsa *</label>
                    <input type="datetime-local" name="tanggal_kedaluwarsa" id="input-kedaluwarsa" value="{{ old('tanggal_kedaluwarsa', date('Y-m-d\TH:i', strtotime('+3 days'))) }}" required
                           class="w-full px-3.5 py-2.5 bg-[#192748] border border-[#2a3d69] rounded-xl text-xs text-white focus:outline-none focus:border-blue-400">
                </div>

                <div class="flex items-center gap-3 pt-2">
                    <button type="submit" class="px-5 py-2.5 bg-[#192748] hover:bg-[#20335e] border border-[#2a3d69] text-xs font-semibold rounded-xl text-white transition cursor-pointer">
                        Simpan Bahan
                    </button>
                    <button type="button" onclick="resetBahanForm()" class="px-5 py-2.5 bg-white hover:bg-slate-100 text-[#0b1329] text-xs font-bold rounded-xl transition cursor-pointer">
                        Batal
                    </button>
                    <button type="button" onclick="confirmDeleteBahan()" class="px-5 py-2.5 bg-white hover:bg-slate-100 text-[#0b1329] text-xs font-bold rounded-xl transition cursor-pointer">
                        Hapus
                    </button>
                </div>
            </form>
        </div>

        <!-- Right: 3 Cards Stack -->
        <div class="lg:col-span-5 space-y-4">
            <!-- Card 1: Telusuri penggunaan bahan -->
            <div class="bg-[#131f38] border border-[#1f3056] p-5 rounded-2xl space-y-3">
                <h3 class="text-xs font-bold text-white">Telusuri penggunaan bahan</h3>
                @if($selectedTraceMenu)
                    <div class="text-xs font-bold text-white">
                        {{ $selectedTraceMenu->nama_menu }} ({{ optional($selectedTraceMenu->tanggal_penyajian)->format('d M Y') }})
                    </div>
                    <p class="text-[11px] text-slate-300 leading-relaxed">
                        Target: {{ $selectedTraceMenu->jumlah_porsi_rencana }} sajian. Bahan terkait:
                        @if($selectedTraceMenu->menuBahans->isNotEmpty())
                            {{ $selectedTraceMenu->menuBahans->map(fn($mb) => ($mb->bahanBaku?->nama_bahan ?? '-') . ' (' . ($mb->bahanBaku?->kode_lot ?? '-') . ': ' . $mb->jumlah_batch . ' ' . $mb->satuan . ')')->implode(', ') }}.
                        @else
                            Belum ada bahan baku ditautkan ke menu ini.
                        @endif
                    </p>
                    <div class="flex items-center gap-2.5 pt-1">
                        <a href="{{ route('admin.menu.index', ['edit' => $selectedTraceMenu->id]) }}#form-menu" class="px-3.5 py-2 bg-white hover:bg-slate-100 text-[#0b1329] text-xs font-bold rounded-xl transition">
                            Lihat Menu
                        </a>
                        <a href="{{ route('admin.tahap.index', ['menu_id' => $selectedTraceMenu->id]) }}" class="px-3.5 py-2 bg-white hover:bg-slate-100 text-[#0b1329] text-xs font-bold rounded-xl transition">
                            Lihat Catatan Tahap
                        </a>
                    </div>
                @else
                    <p class="text-[11px] text-slate-400">Belum ada data menu untuk ditelusuri.</p>
                @endif
            </div>

            <!-- Card 2: Kedaluwarsa bukan sekadar label -->
            <div class="bg-[#131f38] border border-[#1f3056] p-5 rounded-2xl space-y-2">
                <h3 class="text-xs font-bold text-white">Kedaluwarsa bukan sekadar label</h3>
                <p class="text-[11px] text-slate-300 leading-relaxed">
                    Bahan kedaluwarsa tidak boleh digunakan. Jika terpakai pada menu, KPI otomatis menurunkan skor Sx (&lt; 100) dan menampilkan peringatan terpisah, meskipun skor total tinggi.
                </p>
            </div>

            <!-- Card 3: Integritas Data -->
            <div class="bg-[#131f38] border border-[#1f3056] p-5 rounded-2xl space-y-2">
                <h3 class="text-xs font-bold text-white">Perlindungan riwayat &amp; kode lot</h3>
                <p class="text-[11px] text-slate-300 leading-relaxed">
                    Setiap kode lot bersifat unik. Perubahan atau penghapusan bahan baku langsung tercatat di Audit Log beserta pengguna dan waktunya.
                </p>
            </div>
        </div>
    </div>
</div>

<script>
    function fillBahanEdit(item) {
        document.getElementById('form-title').innerText = 'Ubah bahan · ' + item.nama_bahan;
        document.getElementById('edit-id').value = item.id;
        document.getElementById('input-nama').value = item.nama_bahan;
        document.getElementById('input-stok').value = item.stok_kg;
        document.getElementById('input-satuan').value = item.satuan;
        document.getElementById('input-utama').value = item.is_utama ? '1' : '0';
        document.getElementById('input-lot').value = item.kode_lot;
        if (item.kategori) document.getElementById('input-kategori').value = item.kategori;
        if (item.supplier) document.getElementById('input-supplier').value = item.supplier;

        if (item.tanggal_masuk) {
            document.getElementById('input-masuk').value = item.tanggal_masuk.replace(' ', 'T').substring(0, 16);
        }
        if (item.tanggal_kedaluwarsa) {
            document.getElementById('input-kedaluwarsa').value = item.tanggal_kedaluwarsa.replace(' ', 'T').substring(0, 16);
        }

        document.getElementById('bahan-form').action = '/admin/bahan-baku/' + item.id + '/update';
    }

    function resetBahanForm() {
        document.getElementById('form-title').innerText = 'Tambah / Ubah bahan baku';
        document.getElementById('edit-id').value = '';
        document.getElementById('bahan-form').reset();
        document.getElementById('bahan-form').action = "{{ route('admin.bahan.store') }}";
    }

    function confirmDeleteBahan() {
        let id = document.getElementById('edit-id').value;
        if (!id) {
            alert('Pilih bahan terlebih dahulu dari daftar untuk menghapus.');
            return;
        }
        if (confirm('Hapus bahan ini? Bahan yang terkait menu atau riwayat pemakaian tidak dihapus tanpa pemeriksaan.')) {
            let f = document.createElement('form');
            f.method = 'POST';
            f.action = '/admin/bahan-baku/' + id;
            f.innerHTML = `@csrf @method('DELETE')`;
            document.body.appendChild(f);
            f.submit();
        }
    }
</script>
@endsection
