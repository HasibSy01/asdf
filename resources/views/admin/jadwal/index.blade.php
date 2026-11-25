@extends('layouts.app')

@section('title', 'Jadwal Produksi')

@section('content')
@php
    $firstMenu = $menus->first();
    $totPenerima = $kelompoks->sum('jumlah_penerima');
    $totKemas = $kelompoks->sum('porsi_terkemas');
@endphp
<div class="space-y-6">
    <!-- Header -->
    <div>
        <h1 class="text-2xl font-bold text-white tracking-tight">Jadwal Produksi</h1>
        <p class="text-xs text-slate-400 mt-1">
            Susun rencana pengolahan, pengemasan dan distribusi. Sistem otomatis memeriksa bentrok petugas pada jam yang beririsan (FR-022).
        </p>
    </div>

    <!-- Filter Bar -->
    <form method="GET" action="{{ route('admin.jadwal.index') }}" class="grid grid-cols-1 md:grid-cols-4 gap-4 items-end">
        <div>
            <label class="block text-xs font-semibold text-slate-300 mb-1.5">Filter tanggal</label>
            <input type="date" name="tanggal" value="{{ request('tanggal') }}" class="w-full px-3.5 py-2.5 bg-[#192748] border border-[#2a3d69] rounded-xl text-xs text-white focus:outline-none">
        </div>
        <div>
            <label class="block text-xs font-semibold text-slate-300 mb-1.5">Batch</label>
            <select name="batch_no" class="w-full px-3.5 py-2.5 bg-[#192748] border border-[#2a3d69] rounded-xl text-xs text-white focus:outline-none">
                <option value="">Semua tahap/batch</option>
                <option value="Tahap 1" {{ request('batch_no') === 'Tahap 1' ? 'selected' : '' }}>Tahap 1</option>
                <option value="Tahap 2" {{ request('batch_no') === 'Tahap 2' ? 'selected' : '' }}>Tahap 2</option>
                <option value="Tahap 3" {{ request('batch_no') === 'Tahap 3' ? 'selected' : '' }}>Tahap 3</option>
            </select>
        </div>
        <div class="flex items-center gap-2">
            <button type="submit" class="px-4 py-2.5 bg-[#192748] hover:bg-[#20335e] border border-[#2a3d69] text-xs font-semibold rounded-xl text-white transition cursor-pointer">
                Terapkan Filter
            </button>
            @if(request('tanggal') || request('batch_no'))
                <a href="{{ route('admin.jadwal.index') }}" class="px-3.5 py-2.5 bg-[#192748] border border-[#2a3d69] text-xs rounded-xl text-slate-300 hover:text-white">Reset</a>
            @endif
        </div>
        <div class="text-right">
            <a href="#form-jadwal" onclick="resetJadwalForm()" class="inline-flex items-center gap-1.5 px-4 py-2.5 bg-[#192748] hover:bg-[#20335e] border border-[#2a3d69] text-xs font-semibold rounded-xl text-white transition">
                + Tambah Jadwal
            </a>
        </div>
    </form>

    <!-- Card 1: Ringkasan rencana aktif -->
    <div class="bg-[#131f38] border border-[#1f3056] p-6 rounded-2xl space-y-2">
        <h2 class="text-sm font-bold text-white">Ringkasan rencana produksi aktif</h2>
        <p class="text-xs text-slate-300 leading-relaxed">
            Menu utama: <strong class="text-white">{{ $firstMenu?->nama_menu ?? 'Belum ada menu' }}</strong>
            &bull; Penerima batch aktif: <strong class="text-white">{{ number_format($totPenerima, 0, ',', '.') }} penerima</strong>
            ({{ number_format($totKemas, 0, ',', '.') }} porsi terkemas tercatat).
            Cakupan keseluruhan dapur SPPG Ujung Lare: 2.911 penerima.
        </p>
        @if(!empty($bentrokIds))
            <div class="p-3 bg-rose-950/40 border border-rose-800/60 rounded-xl text-xs text-rose-300">
                Terdeteksi {{ count($bentrokIds) }} jadwal dengan potensi bentrok petugas di hari &amp; rentang jam yang beririsan (FR-022). Mohon sesuaikan jadwal yang ditandai.
            </div>
        @endif
    </div>

    <!-- Card 2: Daftar jadwal olah, kemas dan distribusi -->
    <div class="bg-[#131f38] border border-[#1f3056] p-6 rounded-2xl space-y-4">
        <h2 class="text-sm font-bold text-white">Rencana olah, kemas dan distribusi</h2>

        <div class="overflow-x-auto rounded-xl border border-[#1f3056]">
            <table class="w-full text-left text-xs border-collapse">
                <thead>
                    <tr class="bg-[#182645] text-slate-300 font-semibold border-b border-[#1f3056]">
                        <th class="py-3 px-4">Tanggal / Batch</th>
                        <th class="py-3 px-4">Tahap &amp; Menu</th>
                        <th class="py-3 px-4">Jam Rencana</th>
                        <th class="py-3 px-4">Petugas &amp; Sasaran</th>
                        <th class="py-3 px-4">Status / Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-[#1f3056]/60 text-slate-300">
                    @forelse($jadwalList as $j)
                        @php $isBentrok = in_array($j->id, $bentrokIds); @endphp
                        <tr class="hover:bg-[#182747]/40 {{ $isBentrok ? 'bg-rose-950/20' : '' }}">
                            <td class="py-3.5 px-4">
                                <div class="font-semibold text-white">{{ optional($j->tanggal)->format('d M Y') }}</div>
                                <div class="text-[11px] text-sky-300">{{ $j->batch_no }}</div>
                            </td>
                            <td class="py-3.5 px-4">
                                <div class="font-semibold text-white">{{ $j->tahap }}</div>
                                <div class="text-[11px] text-slate-400">{{ $j->menu?->nama_menu ?? '-' }}</div>
                            </td>
                            <td class="py-3.5 px-4 font-mono">
                                {{ substr($j->waktu_mulai_rencana, 0, 5) }} &ndash; {{ substr($j->waktu_selesai_rencana, 0, 5) }}
                                @if($j->target_tiba)
                                    <div class="text-[11px] text-slate-400">Target tiba: {{ substr($j->target_tiba, 0, 5) }}</div>
                                @endif
                            </td>
                            <td class="py-3.5 px-4">
                                <div class="text-white">{{ $j->petugas_rencana }}</div>
                                <div class="text-[11px] text-slate-400">{{ $j->kelompokPenerima?->nama_kelompok ?? '-' }} ({{ $j->porsi_target }} porsi)</div>
                                @if($isBentrok)
                                    <span class="badge badge-danger mt-1">Bentrok petugas</span>
                                @endif
                            </td>
                            <td class="py-3.5 px-4 space-y-1">
                                <div>
                                    <span class="badge {{ $j->status === 'Selesai' ? 'badge-success' : 'badge-info' }}">{{ $j->status }}</span>
                                </div>
                                <div class="text-[11px] space-x-1.5">
                                    <a href="#form-jadwal" onclick="fillJadwalEdit({{ json_encode($j) }})" class="text-slate-300 hover:text-white underline">Ubah</a>
                                    <span class="text-slate-600">/</span>
                                    <form method="POST" action="{{ route('admin.jadwal.destroy', $j->id) }}" class="inline" onsubmit="return mbgConfirmForm(event, 'Hapus baris jadwal {{ addslashes($j->tahap) }}?', 'Hapus Jadwal Produksi?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-rose-400 hover:text-rose-300 bg-transparent border-0 p-0 cursor-pointer">Hapus</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="py-6 text-center text-slate-400">Belum ada jadwal produksi terdaftar.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <p class="text-[11px] text-slate-400">
            Waktu rencana dapat diubah pada form di bawah. Waktu aktual dicatat terpisah pada modul Pencatatan Tahap.
        </p>
    </div>

    <!-- Card 3: Sasaran distribusi terdaftar -->
    <div class="bg-[#131f38] border border-[#1f3056] p-6 rounded-2xl space-y-4">
        <div class="flex items-center justify-between">
            <h2 class="text-sm font-bold text-white">Sasaran distribusi terdaftar</h2>
            <a href="{{ route('admin.penerima.index') }}" class="text-xs text-sky-300 hover:underline">Kelola penerima &amp; porsi &rarr;</a>
        </div>

        <div class="overflow-x-auto rounded-xl border border-[#1f3056]">
            <table class="w-full text-left text-xs border-collapse">
                <thead>
                    <tr class="bg-[#182645] text-slate-300 font-semibold border-b border-[#1f3056]">
                        <th class="py-3 px-4">Titik antar</th>
                        <th class="py-3 px-4">Penerima / terkemas</th>
                        <th class="py-3 px-4">Estimasi tempuh</th>
                        <th class="py-3 px-4">Rekomendasi berangkat</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-[#1f3056]/60 text-slate-300">
                    @foreach($kelompoks as $kel)
                        <tr class="hover:bg-[#182747]/40">
                            <td class="py-3.5 px-4 font-semibold text-white">
                                {{ $kel->nama_kelompok }}
                                <div class="text-[11px] text-slate-400 font-normal">{{ $kel->titik_antar }}</div>
                            </td>
                            <td class="py-3.5 px-4">{{ $kel->jumlah_penerima }} / {{ $kel->porsi_terkemas }}</td>
                            <td class="py-3.5 px-4">{{ $kel->estimasi_waktu_tempuh_menit }} menit</td>
                            <td class="py-3.5 px-4 text-slate-300">Max tiba &le; 4 jam pasca matang</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <!-- Card 4: Tambah / Ubah baris rencana jadwal -->
    <div class="bg-[#131f38] border border-[#1f3056] p-6 rounded-2xl space-y-4" id="form-jadwal">
        <h2 class="text-sm font-bold text-white" id="jadwal-form-title">Tambah / Ubah baris rencana jadwal</h2>

        <form id="jadwal-form" method="POST" action="{{ route('admin.jadwal.store') }}" class="space-y-4">
            @csrf

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-slate-300 mb-1.5">Menu *</label>
                    <select name="menu_id" id="input-jadwal-menu" required class="w-full px-3.5 py-2.5 bg-[#192748] border border-[#2a3d69] rounded-xl text-xs text-white focus:outline-none">
                        @foreach($menus as $m)
                            <option value="{{ $m->id }}">{{ $m->nama_menu }} ({{ optional($m->tanggal_penyajian)->format('d M') }})</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-300 mb-1.5">Kelompok sasaran *</label>
                    <select name="kelompok_penerima_id" id="input-jadwal-kel" required class="w-full px-3.5 py-2.5 bg-[#192748] border border-[#2a3d69] rounded-xl text-xs text-white focus:outline-none">
                        @foreach($kelompoks as $kel)
                            <option value="{{ $kel->id }}">{{ $kel->nama_kelompok }} ({{ $kel->jumlah_penerima }} porsi)</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-300 mb-1.5">Batch / Tahap *</label>
                    <select name="batch_no" id="input-jadwal-batch" class="w-full px-3.5 py-2.5 bg-[#192748] border border-[#2a3d69] rounded-xl text-xs text-white focus:outline-none">
                        <option value="Tahap 1">Tahap 1</option>
                        <option value="Tahap 2">Tahap 2</option>
                        <option value="Tahap 3">Tahap 3</option>
                    </select>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-slate-300 mb-1.5">Tanggal *</label>
                    <input type="date" name="tanggal" id="input-jadwal-tanggal" value="{{ old('tanggal', date('Y-m-d')) }}" required
                           class="w-full px-3.5 py-2.5 bg-[#192748] border border-[#2a3d69] rounded-xl text-xs text-white focus:outline-none">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-300 mb-1.5">Tahap / komponen *</label>
                    <input type="text" name="tahap" id="input-tahap" value="{{ old('tahap', 'Olah · Nasi') }}" required placeholder="Contoh: Olah · Sayur"
                           class="w-full px-3.5 py-2.5 bg-[#192748] border border-[#2a3d69] rounded-xl text-xs text-white focus:outline-none">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-300 mb-1.5">Petugas rencana *</label>
                    <input type="text" name="petugas_rencana" id="input-petugas" value="{{ old('petugas_rencana', 'Tim Olah Sayur') }}" required
                           class="w-full px-3.5 py-2.5 bg-[#192748] border border-[#2a3d69] rounded-xl text-xs text-white focus:outline-none">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-300 mb-1.5">Porsi target *</label>
                    <input type="number" min="1" name="porsi_target" id="input-porsi-target" value="{{ old('porsi_target', 300) }}" required
                           class="w-full px-3.5 py-2.5 bg-[#192748] border border-[#2a3d69] rounded-xl text-xs text-white focus:outline-none">
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-slate-300 mb-1.5">Waktu mulai *</label>
                    <input type="time" name="waktu_mulai_rencana" id="input-mulai" value="{{ old('waktu_mulai_rencana', '04:00') }}" required
                           class="w-full px-3.5 py-2.5 bg-[#192748] border border-[#2a3d69] rounded-xl text-xs text-white focus:outline-none font-mono">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-300 mb-1.5">Waktu selesai *</label>
                    <input type="time" name="waktu_selesai_rencana" id="input-selesai" value="{{ old('waktu_selesai_rencana', '05:30') }}" required
                           class="w-full px-3.5 py-2.5 bg-[#192748] border border-[#2a3d69] rounded-xl text-xs text-white focus:outline-none font-mono">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-300 mb-1.5">Target tiba (opsional)</label>
                    <input type="time" name="target_tiba" id="input-target-tiba" value="{{ old('target_tiba', '07:45') }}"
                           class="w-full px-3.5 py-2.5 bg-[#192748] border border-[#2a3d69] rounded-xl text-xs text-white focus:outline-none font-mono">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-300 mb-1.5">Status jadwal</label>
                    <select name="status" id="input-jadwal-status" class="w-full px-3.5 py-2.5 bg-[#192748] border border-[#2a3d69] rounded-xl text-xs text-white focus:outline-none">
                        <option value="Direncanakan">Direncanakan</option>
                        <option value="Berlangsung">Berlangsung</option>
                        <option value="Selesai">Selesai</option>
                    </select>
                </div>
            </div>

            <div class="flex items-center gap-3 pt-2">
                <button type="submit" class="px-5 py-2.5 bg-[#192748] hover:bg-[#20335e] border border-[#2a3d69] text-xs font-semibold rounded-xl text-white transition cursor-pointer">
                    Simpan Jadwal
                </button>
                <button type="button" onclick="resetJadwalForm()" class="px-5 py-2.5 bg-white hover:bg-slate-100 text-[#0b1329] text-xs font-bold rounded-xl transition cursor-pointer">
                    Batal / Reset
                </button>
            </div>
        </form>

        <!-- Nested Note: Aturan waktu -->
        <div class="p-4 bg-[#182645] border border-[#2a3d69] rounded-xl space-y-1">
            <h4 class="text-xs font-bold text-white">Aturan waktu</h4>
            <p class="text-[11px] text-slate-300 leading-relaxed">
                Batas konsumsi dihitung 4 jam sejak makanan matang, bukan dari jam berangkat. Petugas yang sama tidak dapat dialokasikan pada rentang waktu yang saling beririsan pada hari yang sama.
            </p>
        </div>
    </div>
</div>

<script>
    function fillJadwalEdit(item) {
        document.getElementById('jadwal-form-title').innerText = 'Ubah jadwal · ' + item.tahap;
        document.getElementById('input-jadwal-menu').value = item.menu_id;
        document.getElementById('input-jadwal-kel').value = item.kelompok_penerima_id;
        document.getElementById('input-jadwal-batch').value = item.batch_no || 'Tahap 1';
        if (item.tanggal) document.getElementById('input-jadwal-tanggal').value = item.tanggal.substring(0, 10);
        document.getElementById('input-tahap').value = item.tahap;
        document.getElementById('input-mulai').value = (item.waktu_mulai_rencana || '').substring(0, 5);
        document.getElementById('input-selesai').value = (item.waktu_selesai_rencana || '').substring(0, 5);
        document.getElementById('input-target-tiba').value = item.target_tiba ? item.target_tiba.substring(0, 5) : '';
        document.getElementById('input-petugas').value = item.petugas_rencana;
        document.getElementById('input-porsi-target').value = item.porsi_target;
        document.getElementById('input-jadwal-status').value = item.status || 'Direncanakan';
        document.getElementById('jadwal-form').action = '/admin/jadwal-produksi/' + item.id + '/update';
    }

    function resetJadwalForm() {
        document.getElementById('jadwal-form-title').innerText = 'Tambah / Ubah baris rencana jadwal';
        document.getElementById('jadwal-form').reset();
        document.getElementById('jadwal-form').action = "{{ route('admin.jadwal.store') }}";
    }
</script>
@endsection
