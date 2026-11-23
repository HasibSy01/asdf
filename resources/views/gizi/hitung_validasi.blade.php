@extends('layouts.app')

@section('title', 'Hitung, Validasi dan Publikasi Gizi')

@section('content')
@php
    $st = $selectedMenu?->status ?? 'draft';
    $shareUrl = $selectedMenu ? route('public.scan.qr', ['token' => 'mbg-menu-' . $selectedMenu->id]) : route('public.index');
    $qrSvg = $selectedMenu ? \App\Services\QrSvgService::renderDataUri($shareUrl) : null;
    $akgPctEnergi = ($hasilHitung && $targetAkg['energi_min'] > 0) ? round(($hasilHitung['energi_kkal'] / $targetAkg['energi_min']) * 100) : 0;
@endphp
<div class="space-y-6">
    <!-- Header + Menu Selector -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-white tracking-tight">Hitung, Validasi dan Publikasi Gizi</h1>
            <p class="text-xs text-slate-400 mt-1">
                Khusus Ahli Gizi &bull; {{ $selectedMenu ? $selectedMenu->nama_menu . ' · ' . optional($selectedMenu->tanggal_penyajian)->format('d F Y') : 'Belum ada menu' }}
            </p>
        </div>

        <form method="GET" action="{{ route('gizi.hitung.validasi') }}" class="flex items-center gap-2">
            <select name="menu_id" onchange="this.form.submit()" class="px-3.5 py-2.5 bg-[#192748] border border-[#2a3d69] rounded-xl text-xs text-white focus:outline-none">
                @foreach($menus as $m)
                    <option value="{{ $m->id }}" {{ $selectedMenu && $selectedMenu->id === $m->id ? 'selected' : '' }}>
                        {{ $m->nama_menu }} ({{ optional($m->tanggal_penyajian)->format('d M') }} &middot; {{ ucfirst(str_replace('_', ' ', $m->status)) }})
                    </option>
                @endforeach
            </select>
        </form>
    </div>

    <!-- Stepper Pills reflecting real status -->
    <div class="flex flex-wrap items-center gap-2 text-xs">
        <span class="px-3.5 py-1.5 bg-[#192748] border {{ $st === 'draft' ? 'border-sky-500 text-white font-bold' : 'border-[#2a3d69] text-slate-300 font-semibold' }} rounded-xl flex items-center gap-1.5">
            1. Draft <span>&rarr;</span>
        </span>
        <span class="px-3.5 py-1.5 bg-[#192748] border {{ $st === 'menunggu_validasi' ? 'border-sky-500 text-white font-bold' : 'border-[#2a3d69] text-slate-300 font-semibold' }} rounded-xl flex items-center gap-1.5">
            2. Dihitung / Menunggu Validasi <span>&rarr;</span>
        </span>
        <span class="px-3.5 py-1.5 bg-[#192748] border {{ $st === 'tervalidasi' ? 'border-sky-500 text-white font-bold' : 'border-[#2a3d69] text-slate-300 font-semibold' }} rounded-xl flex items-center gap-1.5">
            3. Tervalidasi <span>&rarr;</span>
        </span>
        <span class="px-3.5 py-1.5 bg-[#192748] border {{ $st === 'terpublikasi' ? 'border-emerald-500 text-emerald-300 font-bold' : 'border-[#2a3d69] text-slate-400 font-semibold' }} rounded-xl">
            4. Terpublikasi
        </span>
    </div>

    @if($selectedMenu)
    <!-- Card 1: Bahan dan nilai gizi per sajian -->
    <div class="bg-[#131f38] border border-[#1f3056] p-6 rounded-2xl space-y-4">
        <div>
            <h2 class="text-sm font-bold text-white">Bahan dan nilai gizi per sajian &middot; {{ $selectedMenu->nama_menu }}</h2>
            <p class="text-xs text-slate-400 mt-0.5">
                {{ $selectedMenu->jumlah_porsi_rencana }} sajian rencana &middot; ukuran saji &plusmn;{{ $selectedMenu->ukuran_porsi_gram }} g. Komposisi batch dikelola Admin; nilai gizi dihitung otomatis dari Master Data Gizi.
            </p>
        </div>

        <div class="overflow-x-auto rounded-xl border border-[#1f3056]">
            <table class="w-full text-left text-xs border-collapse">
                <thead>
                    <tr class="bg-[#182645] text-slate-300 font-semibold border-b border-[#1f3056]">
                        <th class="py-3 px-4">Komponen &middot; Berat saji</th>
                        <th class="py-3 px-4">Bahan batch</th>
                        <th class="py-3 px-4">Energi (kkal)</th>
                        <th class="py-3 px-4">Protein / lemak (g)</th>
                        <th class="py-3 px-4">Karbo / serat (g)</th>
                        <th class="py-3 px-4">Status rujukan</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-[#1f3056]/60 text-slate-300">
                    @forelse($rincianBahanGizi as $row)
                        <tr class="hover:bg-[#182747]/40">
                            <td class="py-3.5 px-4 font-semibold text-white">
                                {{ $row['menu_bahan']->komponen ?: $row['nama_bahan'] }} &middot; {{ $row['berat_sajian_gram'] }} g
                            </td>
                            <td class="py-3.5 px-4">
                                {{ $row['nama_bahan'] }} ({{ $row['menu_bahan']->jumlah_batch }} {{ $row['menu_bahan']->satuan }})
                            </td>
                            <td class="py-3.5 px-4 font-semibold text-white">{{ number_format($row['energi_kkal'], 1) }}</td>
                            <td class="py-3.5 px-4">{{ number_format($row['protein_g'], 1) }} / {{ number_format($row['lemak_g'], 1) }}</td>
                            <td class="py-3.5 px-4">{{ number_format($row['karbohidrat_g'], 1) }} / {{ number_format($row['serat_g'], 1) }}</td>
                            <td class="py-3.5 px-4 text-xs">
                                @if($row['gizi_found'])
                                    <span class="text-emerald-400 font-semibold">TKPI cocok</span>
                                @else
                                    <a href="{{ route('gizi.dashboard') }}#form-gizi" class="text-amber-400 underline font-semibold">+ Lengkapi Data Gizi</a>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="py-6 text-center text-slate-400">Menu ini belum memiliki komposisi bahan dari Admin.</td>
                        </tr>
                    @endforelse
                    @if($hasilHitung)
                        <tr class="bg-[#182645] font-bold border-t border-[#1f3056]">
                            <td class="py-3.5 px-4 text-white">Total per sajian</td>
                            <td class="py-3.5 px-4 text-white">&plusmn;{{ $selectedMenu->ukuran_porsi_gram }} g</td>
                            <td class="py-3.5 px-4 text-white">{{ number_format($hasilHitung['energi_kkal'], 1) }}</td>
                            <td class="py-3.5 px-4 text-white">{{ number_format($hasilHitung['protein_g'], 1) }} / {{ number_format($hasilHitung['lemak_g'], 1) }}</td>
                            <td class="py-3.5 px-4 text-white">{{ number_format($hasilHitung['karbohidrat_g'], 1) }} / {{ number_format($hasilHitung['serat_g'], 1) }}</td>
                            <td class="py-3.5 px-4 {{ $hasilHitung['is_complete'] ? 'text-emerald-400' : 'text-amber-400' }} font-semibold">
                                {{ $hasilHitung['is_complete'] ? 'Lengkap' : 'Data kurang' }}
                            </td>
                        </tr>
                    @endif
                </tbody>
            </table>
        </div>

        @if($hasilHitung && !$hasilHitung['is_complete'])
            <div class="p-4 bg-amber-950/30 border border-amber-800/60 rounded-xl text-xs text-amber-300">
                <strong>Data gizi belum lengkap untuk bahan:</strong> {{ implode(', ', $hasilHitung['missing_data']) }}.
                Tambahkan nilai gizi bahan tersebut di Master Data Gizi sebelum memvalidasi.
            </div>
        @endif

        <div class="flex items-center gap-3 pt-1">
            <a href="{{ route('gizi.dashboard') }}#form-gizi" class="px-4 py-2.5 bg-white hover:bg-slate-100 text-[#0b1329] text-xs font-bold rounded-xl transition">
                Kelola Master Data Gizi
            </a>
            <form method="GET" action="{{ route('gizi.hitung.validasi') }}" class="inline">
                <input type="hidden" name="menu_id" value="{{ $selectedMenu->id }}">
                <button type="submit" class="px-5 py-2.5 bg-[#192748] hover:bg-[#20335e] border border-[#2a3d69] text-xs font-semibold rounded-xl text-white transition cursor-pointer">
                    Hitung Ulang Gizi
                </button>
            </form>
        </div>
    </div>

    <!-- Middle 2 Columns: Acuan AKG & Ringkasan Validasi -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">
        <!-- Left: Validasi dan catatan revisi (approx 60%) -->
        <div class="lg:col-span-7 bg-[#131f38] border border-[#1f3056] p-6 rounded-2xl space-y-4">
            <h2 class="text-sm font-bold text-white">Validasi dan catatan revisi</h2>

            <form method="POST" action="{{ route('gizi.validasi.process', $selectedMenu->id) }}" id="form-validasi" class="space-y-4">
                @csrf
                <input type="hidden" name="status" id="validasi-status" value="tervalidasi">
                <div>
                    <label class="block text-xs font-semibold text-slate-300 mb-1.5">Catatan Ahli Gizi (wajib diisi apabila meminta revisi)</label>
                    <textarea name="catatan_revisi" rows="3"
                              placeholder="Contoh: Komposisi energi dan protein telah memenuhi porsi makan siang anak sekolah (Permenkes 28/2019)."
                              class="w-full px-3.5 py-2.5 bg-[#192748] border border-[#2a3d69] rounded-xl text-xs text-white focus:outline-none">{{ old('catatan_revisi', $selectedMenu->catatan_revisi) }}</textarea>
                </div>

                <div class="flex flex-wrap items-center gap-3">
                    <button type="submit"
                            class="px-5 py-2.5 bg-[#192748] hover:bg-[#20335e] border border-[#3b5791] text-xs font-semibold rounded-xl text-white transition cursor-pointer">
                        Validasi Menu (Sesuai AKG)
                    </button>
                    <button type="button" onclick="submitRevisi()" class="px-5 py-2.5 bg-white hover:bg-slate-100 text-[#0b1329] text-xs font-bold rounded-xl transition cursor-pointer">
                        Minta Revisi ke Admin
                    </button>
                </div>
            </form>

            <div class="p-4 bg-[#192748] border border-[#2a3d69] rounded-xl space-y-1">
                <h4 class="text-xs font-bold text-white">Alur kendali mutu</h4>
                <p class="text-[11px] text-slate-300 leading-relaxed">
                    Publikasi ke portal publik hanya diaktifkan setelah menu dinyatakan <strong>Tervalidasi</strong> oleh Ahli Gizi. Jika Admin mengubah bahan setelah tervalidasi/terpublikasi, status otomatis kembali ke <strong>Menunggu Validasi</strong>.
                </p>
            </div>
        </div>

        <!-- Right: Acuan AKG kelompok penerima (approx 40%) -->
        <div class="lg:col-span-5 bg-[#131f38] border border-[#1f3056] p-6 rounded-2xl space-y-4">
            <h2 class="text-sm font-bold text-white">Acuan AKG kelompok penerima</h2>

            <div class="px-3.5 py-2.5 bg-[#192748] border border-[#2a3d69] rounded-xl text-xs text-white font-semibold">
                Kelompok sasaran: {{ $selectedMenu->kelompokPenerima?->nama_kelompok ?? 'Siswa Sekolah (Makan Siang)' }}
            </div>

            <div class="overflow-x-auto rounded-xl border border-[#1f3056]">
                <table class="w-full text-left text-xs border-collapse">
                    <thead>
                        <tr class="bg-[#182645] text-slate-300 font-semibold border-b border-[#1f3056]">
                            <th class="py-3 px-4">Parameter</th>
                            <th class="py-3 px-4">Hasil menu</th>
                            <th class="py-3 px-4">Acuan makan siang</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-[#1f3056]/60 text-slate-300">
                        <tr>
                            <td class="py-2.5 px-4 font-semibold text-white">Energi</td>
                            <td class="py-2.5 px-4">{{ number_format($hasilHitung['energi_kkal'] ?? 0, 1) }} kkal</td>
                            <td class="py-2.5 px-4 text-slate-400">{{ $targetAkg['energi_min'] }}&ndash;{{ $targetAkg['energi_max'] }} kkal</td>
                        </tr>
                        <tr>
                            <td class="py-2.5 px-4 font-semibold text-white">Protein</td>
                            <td class="py-2.5 px-4">{{ number_format($hasilHitung['protein_g'] ?? 0, 1) }} g</td>
                            <td class="py-2.5 px-4 text-slate-400">{{ $targetAkg['protein_min'] }}&ndash;{{ $targetAkg['protein_max'] }} g</td>
                        </tr>
                        <tr>
                            <td class="py-2.5 px-4 font-semibold text-white">Lemak</td>
                            <td class="py-2.5 px-4">{{ number_format($hasilHitung['lemak_g'] ?? 0, 1) }} g</td>
                            <td class="py-2.5 px-4 text-slate-400">{{ $targetAkg['lemak_min'] }}&ndash;{{ $targetAkg['lemak_max'] }} g</td>
                        </tr>
                        <tr>
                            <td class="py-2.5 px-4 font-semibold text-white">Karbohidrat</td>
                            <td class="py-2.5 px-4">{{ number_format($hasilHitung['karbohidrat_g'] ?? 0, 1) }} g</td>
                            <td class="py-2.5 px-4 text-slate-400">{{ $targetAkg['karbohidrat_min'] }}&ndash;{{ $targetAkg['karbohidrat_max'] }} g</td>
                        </tr>
                        <tr>
                            <td class="py-2.5 px-4 font-semibold text-white">Serat</td>
                            <td class="py-2.5 px-4">{{ number_format($hasilHitung['serat_g'] ?? 0, 1) }} g</td>
                            <td class="py-2.5 px-4 text-slate-400">{{ $targetAkg['serat_min'] }}&ndash;{{ $targetAkg['serat_max'] }} g</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="p-4 bg-[#192748] border border-[#2a3d69] rounded-xl space-y-1">
                <h4 class="text-xs font-bold text-white">Kecukupan Energi Sajian ({{ $akgPctEnergi }}%)</h4>
                <p class="text-[11px] text-slate-300 leading-relaxed">
                    Acuan mengacu standar porsi makan siang sekolah (sekitar 30&ndash;35% AKG harian Permenkes No. 28/2019).
                </p>
            </div>
        </div>
    </div>

    <!-- Card: Publikasi dan preview portal + QR Code -->
    <div class="bg-[#131f38] border border-[#1f3056] p-6 rounded-2xl space-y-4">
        <h2 class="text-sm font-bold text-white">Publikasi portal &amp; Kode QR Transparansi Publik</h2>

        <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-center">
            <!-- Left: Foto Menu -->
            <div class="lg:col-span-4 bg-[#192748] border border-[#2a3d69] rounded-2xl p-5 text-center space-y-3 flex flex-col items-center justify-center min-h-[200px]">
                @if($selectedMenu->foto_menu && file_exists(public_path($selectedMenu->foto_menu)))
                    <img src="{{ asset($selectedMenu->foto_menu) }}" alt="{{ $selectedMenu->nama_menu }}" class="w-full h-36 object-cover rounded-xl">
                @else
                    <img src="{{ asset('images/logo-mbg.png') }}" alt="Logo BGN" class="w-16 h-16 object-contain">
                @endif
                <div class="text-xs font-bold text-white">{{ $selectedMenu->nama_menu }}</div>
                <p class="text-[11px] text-slate-300">
                    {{ number_format($hasilHitung['energi_kkal'] ?? 0, 0) }} kkal/sajian &bull; Status: <strong>{{ ucfirst(str_replace('_', ' ', $selectedMenu->status)) }}</strong>
                </p>
            </div>

            <!-- Middle: Status & Tombol Publikasi / Tarik Publikasi -->
            <div class="lg:col-span-5 space-y-3">
                <div class="text-xs font-bold text-white">Kendali Publikasi Portal Publik</div>

                <div class="flex flex-wrap items-center gap-2">
                    @if($selectedMenu->status === 'terpublikasi' && $selectedMenu->is_published)
                        <form method="POST" action="{{ route('gizi.tarik.publikasi', $selectedMenu->id) }}" class="inline">
                            @csrf
                            <button type="submit" class="px-5 py-2.5 bg-amber-600 hover:bg-amber-500 text-white text-xs font-semibold rounded-xl cursor-pointer border-0">
                                Tarik Publikasi
                            </button>
                        </form>
                    @else
                        <form method="POST" action="{{ route('gizi.publikasi.process', $selectedMenu->id) }}" class="inline">
                            @csrf
                            <button type="submit" {{ $selectedMenu->status === 'tervalidasi' ? '' : 'disabled' }}
                                    class="px-5 py-2.5 {{ $selectedMenu->status === 'tervalidasi' ? 'bg-blue-600 hover:bg-blue-500 text-white cursor-pointer' : 'bg-[#15223e] border border-[#2a3d69] text-slate-400 cursor-not-allowed' }} text-xs font-semibold rounded-xl border-0">
                                Publikasikan Sekarang
                            </button>
                        </form>
                    @endif

                    <a href="{{ route('public.index', ['menu_id' => $selectedMenu->id]) }}" class="px-4 py-2.5 bg-white hover:bg-slate-100 text-[#0b1329] text-xs font-bold rounded-xl transition">
                        Preview Portal
                    </a>
                </div>

                <div class="p-4 bg-[#192748] border border-[#2a3d69] rounded-xl space-y-1">
                    <h4 class="text-xs font-bold text-white">Status: {{ ucfirst(str_replace('_', ' ', $selectedMenu->status)) }}</h4>
                    <p class="text-[11px] text-slate-300 leading-relaxed">
                        @if($selectedMenu->status === 'terpublikasi')
                            Menu sedang tayang di portal publik dan dapat dipindai masyarakat melalui Kode QR di samping.
                        @elseif($selectedMenu->status === 'tervalidasi')
                            Menu sudah divalidasi dan siap dipublikasikan ke portal publik.
                        @else
                            Klik "Validasi Menu" terlebih dahulu sebelum tombol Publikasikan aktif.
                        @endif
                    </p>
                </div>

                <div class="flex items-center gap-2">
                    <input type="text" id="public-share-link" value="{{ $shareUrl }}" readonly
                           class="flex-1 px-3 py-2 bg-[#192748] border border-[#2a3d69] rounded-xl text-[11px] text-slate-300 font-mono">
                    <button type="button" onclick="copyShareLink()" class="px-3 py-2 bg-[#192748] hover:bg-[#20335e] border border-[#2a3d69] text-white text-[11px] font-semibold rounded-xl cursor-pointer">
                        Salin Tautan
                    </button>
                </div>
            </div>

            <!-- Right: Real Offline SVG QR Code -->
            <div class="lg:col-span-3 bg-[#192748] border border-[#2a3d69] rounded-2xl p-5 text-center space-y-2.5 flex flex-col items-center justify-center min-h-[200px]">
                @if($qrSvg)
                    <div class="bg-white p-2 rounded-xl inline-block">
                        <img src="{{ $qrSvg }}" alt="QR Portal Publik {{ $selectedMenu->nama_menu }}" class="w-32 h-32">
                    </div>
                @endif
                <div class="text-xs font-bold text-white">
                    {{ ($selectedMenu->status === 'terpublikasi' && $selectedMenu->is_published) ? 'QR Code Portal Aktif' : 'Pratinjau QR Portal' }}
                </div>
                <p class="text-[11px] text-slate-400">
                    Token: <span class="font-mono text-sky-300">mbg-menu-{{ $selectedMenu->id }}</span>
                </p>
            </div>
        </div>
    </div>
    @else
        <div class="bg-[#131f38] border border-[#1f3056] p-8 rounded-2xl text-center text-slate-400 text-xs">
            Belum ada menu terdaftar untuk dihitung dan divalidasi.
        </div>
    @endif
</div>

<script>
    function submitRevisi() {
        if (confirm('Minta revisi komposisi bahan menu ke Admin SPPG?')) {
            let f = document.getElementById('form-validasi');
            document.getElementById('validasi-status').value = 'revisi';
            f.submit();
        }
    }

    function copyShareLink() {
        var inp = document.getElementById('public-share-link');
        if (!inp) return;
        inp.select();
        navigator.clipboard?.writeText(inp.value);
        alert('Tautan QR portal publik disalin:\n' + inp.value);
    }
</script>
@endsection
