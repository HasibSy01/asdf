@extends('layouts.app')

@section('title', 'Penerima dan Porsi')

@section('content')
@php
    $selisihBatch = $totalPenerima - $totalTerkemas;
    $catCounts = [];
    foreach ($kelompokList as $kp) {
        $key = strtolower(trim($kp->kategori));
        $catCounts[$key] = ($catCounts[$key] ?? 0) + $kp->jumlah_penerima;
    }
@endphp
<div class="space-y-6">
    <!-- Header -->
    <div>
        <h1 class="text-2xl font-bold text-white tracking-tight">Penerima dan Porsi</h1>
        <p class="text-xs text-slate-400 mt-1">
            Pisahkan cakupan seluruh dapur SPPG Ujung Lare dari kelompok pengantaran batch aktif.
        </p>
    </div>

    <!-- 3 Top Stat Cards -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
        <div class="bg-[#131f38] border border-[#1f3056] p-6 rounded-2xl">
            <div class="text-xs font-semibold text-slate-400">Total dapur &middot; SPPG Ujung Lare</div>
            <div class="text-4xl font-extrabold text-white mt-2">{{ number_format(max(2911, $totalPenerima), 0, ',', '.') }}</div>
            <div class="text-[11px] text-slate-400 mt-2">Total cakupan penerima manfaat keseluruhan SPPG</div>
        </div>

        <div class="bg-[#131f38] border border-[#1f3056] p-6 rounded-2xl">
            <div class="text-xs font-semibold text-slate-400">Total penerima batch terdaftar</div>
            <div class="text-4xl font-extrabold text-white mt-2">{{ number_format($totalPenerima, 0, ',', '.') }} penerima</div>
            <div class="text-[11px] text-slate-400 mt-2">Akumulasi seluruh kelompok pengantaran aktif ({{ $kelompokList->count() }} titik)</div>
        </div>

        <div class="bg-[#131f38] border border-[#1f3056] p-6 rounded-2xl">
            <div class="text-xs font-semibold text-slate-400">Total porsi terkemas</div>
            <div class="text-4xl font-extrabold text-white mt-2">{{ number_format($totalTerkemas, 0, ',', '.') }} porsi</div>
            <div class="text-[11px] {{ $selisihBatch > 0 ? 'text-amber-400 font-semibold' : 'text-emerald-400' }} mt-2">
                @if($selisihBatch > 0)
                    Selisih {{ $selisihBatch }} porsi kurang dari total penerima batch
                @elseif($selisihBatch < 0)
                    Cadangan berlebih {{ abs($selisihBatch) }} porsi dari target penerima
                @else
                    100% sesuai target penerima batch
                @endif
            </div>
        </div>
    </div>

    <!-- Middle 2 Columns: Kategori Penerima & Kelompok Pengantaran -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">
        <!-- Left: Kategori Penerima -->
        <div class="lg:col-span-4 bg-[#131f38] border border-[#1f3056] p-6 rounded-2xl space-y-4">
            <h2 class="text-sm font-bold text-white">Kategori penerima</h2>

            <div class="overflow-x-auto rounded-xl border border-[#1f3056]">
                <table class="w-full text-left text-xs border-collapse">
                    <thead>
                        <tr class="bg-[#182645] text-slate-300 font-semibold border-b border-[#1f3056]">
                            <th class="py-3 px-4">Kategori</th>
                            <th class="py-3 px-4">Jumlah aktif</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-[#1f3056]/60 text-slate-300">
                        @foreach(['Siswa', 'Batita', 'Balita', 'Ibu hamil', 'Ibu menyusui'] as $catLabel)
                            @php $cnt = $catCounts[strtolower($catLabel)] ?? 0; @endphp
                            <tr class="hover:bg-[#182747]/40">
                                <td class="py-3 px-4 {{ $cnt > 0 ? 'font-semibold text-white' : '' }}">{{ $catLabel }}</td>
                                <td class="py-3 px-4 {{ $cnt > 0 ? 'text-white font-semibold' : 'text-slate-400' }}">
                                    {{ $cnt > 0 ? number_format($cnt, 0, ',', '.') : 'Tidak dipilih' }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <p class="text-[11px] text-slate-400 leading-relaxed">
                Total agregat dihitung langsung dari daftar kelompok pengantaran di sebelah kanan.
            </p>
        </div>

        <!-- Right: Kelompok pengantaran -->
        <div class="lg:col-span-8 bg-[#131f38] border border-[#1f3056] p-6 rounded-2xl space-y-4">
            <h2 class="text-sm font-bold text-white">Kelompok pengantaran &middot; Tahap aktif</h2>

            <!-- Table -->
            <div class="overflow-x-auto rounded-xl border border-[#1f3056]">
                <table class="w-full text-left text-xs border-collapse">
                    <thead>
                        <tr class="bg-[#182645] text-slate-300 font-semibold border-b border-[#1f3056]">
                            <th class="py-3 px-4">Kelompok / titik antar</th>
                            <th class="py-3 px-4">Kategori</th>
                            <th class="py-3 px-4">Penerima</th>
                            <th class="py-3 px-4">Porsi terkemas</th>
                            <th class="py-3 px-4">Estimasi tempuh</th>
                            <th class="py-3 px-4">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-[#1f3056]/60 text-slate-300">
                        @forelse($kelompokList as $kp)
                            <tr class="hover:bg-[#182747]/40 transition">
                                <td class="py-3.5 px-4 font-semibold text-white">
                                    {{ $kp->nama_kelompok }}
                                    <div class="text-[11px] text-slate-400 font-normal">{{ $kp->titik_antar }}</div>
                                </td>
                                <td class="py-3.5 px-4">{{ $kp->kategori }}</td>
                                <td class="py-3.5 px-4 font-semibold text-white">{{ $kp->jumlah_penerima }}</td>
                                <td class="py-3.5 px-4 {{ $kp->porsi_terkemas < $kp->jumlah_penerima ? 'text-amber-400 font-semibold' : 'text-emerald-400 font-semibold' }}">
                                    {{ $kp->porsi_terkemas }}
                                </td>
                                <td class="py-3.5 px-4">{{ $kp->estimasi_waktu_tempuh_menit }} menit</td>
                                <td class="py-3.5 px-4 text-xs space-x-1.5">
                                    <a href="#form-kelompok" onclick="fillKelompokEdit({{ json_encode($kp) }})" class="text-slate-300 hover:text-white transition underline">Ubah</a>
                                    <span class="text-slate-600">/</span>
                                    <form method="POST" action="{{ route('admin.penerima.destroy', $kp->id) }}" class="inline" onsubmit="return confirm('Hapus kelompok {{ addslashes($kp->nama_kelompok) }}?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-rose-400 hover:text-rose-300 transition bg-transparent border-0 p-0 cursor-pointer">Hapus</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="py-6 text-center text-slate-400">Belum ada kelompok pengantaran.</td>
                            </tr>
                        @endforelse
                        <!-- Summary Row -->
                        <tr class="bg-[#182645]/50 font-bold border-t border-[#1f3056]">
                            <td class="py-3 px-4 text-white" colspan="2">Total batch terdaftar</td>
                            <td class="py-3 px-4 text-white">{{ $totalPenerima }}</td>
                            <td class="py-3 px-4 text-white">{{ $totalTerkemas }}</td>
                            <td class="py-3 px-4 text-slate-400">&mdash;</td>
                            <td class="py-3 px-4 text-slate-400">&mdash;</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div>
                <a href="#form-kelompok" onclick="resetKelompokForm()" class="inline-flex items-center gap-1.5 px-4 py-2.5 bg-[#192748] hover:bg-[#20335e] border border-[#2a3d69] text-xs font-semibold rounded-xl text-white transition">
                    <span>+ Tambah Kelompok</span>
                </a>
            </div>
        </div>
    </div>

    <!-- Card: Kelola kelompok -->
    <div class="bg-[#131f38] border border-[#1f3056] p-6 rounded-2xl space-y-4" id="form-kelompok">
        <h2 class="text-sm font-bold text-white" id="kelompok-form-title">Tambah / Ubah kelompok penerima</h2>

        <form id="kelompok-form" method="POST" action="{{ route('admin.penerima.store') }}" class="space-y-4">
            @csrf
            <input type="hidden" name="id" id="kelompok-id">

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-slate-300 mb-1.5">Nama kelompok *</label>
                    <input type="text" name="nama_kelompok" id="input-nama-kelompok" value="{{ old('nama_kelompok') }}" required placeholder="Contoh: SD Negeri C Parepare"
                           class="w-full px-3.5 py-2.5 bg-[#192748] border border-[#2a3d69] rounded-xl text-xs text-white focus:outline-none focus:border-blue-400">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-300 mb-1.5">Kategori penerima *</label>
                    <select name="kategori" id="input-kategori-kelompok" class="w-full px-3.5 py-2.5 bg-[#192748] border border-[#2a3d69] rounded-xl text-xs text-white focus:outline-none">
                        <option value="Siswa">Siswa</option>
                        <option value="Batita">Batita</option>
                        <option value="Balita">Balita</option>
                        <option value="Ibu hamil">Ibu hamil</option>
                        <option value="Ibu menyusui">Ibu menyusui</option>
                    </select>
                </div>
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-300 mb-1.5">Titik antar *</label>
                <input type="text" name="titik_antar" id="input-titik-antar" value="{{ old('titik_antar') }}" required placeholder="Alamat lengkap / titik serah terima di lokasi"
                       class="w-full px-3.5 py-2.5 bg-[#192748] border border-[#2a3d69] rounded-xl text-xs text-white focus:outline-none focus:border-blue-400">
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-slate-300 mb-1.5">Jumlah penerima *</label>
                    <input type="number" min="1" name="jumlah_penerima" id="input-jml-penerima" value="{{ old('jumlah_penerima', 100) }}" required
                           class="w-full px-3.5 py-2.5 bg-[#192748] border border-[#2a3d69] rounded-xl text-xs text-white focus:outline-none focus:border-blue-400">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-300 mb-1.5">Jumlah porsi terkemas *</label>
                    <input type="number" min="0" name="porsi_terkemas" id="input-jml-terkemas" value="{{ old('porsi_terkemas', 100) }}" required
                           class="w-full px-3.5 py-2.5 bg-[#192748] border border-[#2a3d69] rounded-xl text-xs text-white focus:outline-none focus:border-blue-400">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-300 mb-1.5">Estimasi waktu tempuh (menit) *</label>
                    <input type="number" min="1" name="estimasi_waktu_tempuh_menit" id="input-estimasi" value="{{ old('estimasi_waktu_tempuh_menit', 20) }}" required
                           class="w-full px-3.5 py-2.5 bg-[#192748] border border-[#2a3d69] rounded-xl text-xs text-white focus:outline-none focus:border-blue-400">
                </div>
            </div>

            <div class="flex items-center gap-3 pt-2">
                <button type="submit" class="px-5 py-2.5 bg-[#192748] hover:bg-[#20335e] border border-[#2a3d69] text-xs font-semibold rounded-xl text-white transition cursor-pointer">
                    Simpan Kelompok
                </button>
                <button type="button" onclick="resetKelompokForm()" class="px-5 py-2.5 bg-white hover:bg-slate-100 text-[#0b1329] text-xs font-bold rounded-xl transition cursor-pointer">
                    Batal
                </button>
                <button type="button" onclick="confirmDeleteKelompok()" class="px-5 py-2.5 bg-white hover:bg-slate-100 text-[#0b1329] text-xs font-bold rounded-xl transition cursor-pointer">
                    Hapus Kelompok
                </button>
            </div>
        </form>
    </div>

    <!-- Card: Pemeriksaan porsi otomatis -->
    @php
        $kurangGroups = $kelompokList->filter(fn($k) => $k->porsi_terkemas < $k->jumlah_penerima);
    @endphp
    <div class="bg-[#131f38] border border-[#1f3056] p-5 rounded-2xl space-y-2">
        <h3 class="text-xs font-bold text-white">Pemeriksaan kecukupan porsi batch</h3>
        @if($kurangGroups->isNotEmpty())
            <p class="text-xs text-amber-300 leading-relaxed">
                @foreach($kurangGroups as $kg)
                    <span class="block">
                        &bull; <strong>{{ $kg->nama_kelompok }}</strong>: {{ $kg->jumlah_penerima }} penerima, {{ $kg->porsi_terkemas }} porsi terkemas &rarr; kekurangan {{ $kg->jumlah_penerima - $kg->porsi_terkemas }} porsi. Periksa sebelum distribusi.
                    </span>
                @endforeach
            </p>
        @else
            <p class="text-xs text-emerald-300 leading-relaxed">
                Seluruh kelompok pengantaran telah memenuhi kecukupan porsi kemasan (100% sesuai penerima).
            </p>
        @endif
    </div>
</div>

<script>
    function fillKelompokEdit(item) {
        document.getElementById('kelompok-form-title').innerText = 'Ubah kelompok · ' + item.nama_kelompok;
        document.getElementById('kelompok-id').value = item.id;
        document.getElementById('input-nama-kelompok').value = item.nama_kelompok;
        document.getElementById('input-kategori-kelompok').value = item.kategori || 'Siswa';
        document.getElementById('input-titik-antar').value = item.titik_antar;
        document.getElementById('input-jml-penerima').value = item.jumlah_penerima;
        document.getElementById('input-jml-terkemas').value = item.porsi_terkemas;
        document.getElementById('input-estimasi').value = item.estimasi_waktu_tempuh_menit;

        document.getElementById('kelompok-form').action = '/admin/penerima-porsi/' + item.id + '/update';
    }

    function resetKelompokForm() {
        document.getElementById('kelompok-form-title').innerText = 'Tambah / Ubah kelompok penerima';
        document.getElementById('kelompok-id').value = '';
        document.getElementById('kelompok-form').reset();
        document.getElementById('kelompok-form').action = "{{ route('admin.penerima.store') }}";
    }

    function confirmDeleteKelompok() {
        let id = document.getElementById('kelompok-id').value;
        if (!id) {
            alert('Pilih kelompok terlebih dahulu dari daftar untuk menghapus.');
            return;
        }
        if (confirm('Hapus kelompok ini? Kelompok yang terhubung jadwal harus diperiksa dahulu.')) {
            let f = document.createElement('form');
            f.method = 'POST';
            f.action = '/admin/penerima-porsi/' + id;
            f.innerHTML = `@csrf @method('DELETE')`;
            document.body.appendChild(f);
            f.submit();
        }
    }
</script>
@endsection
