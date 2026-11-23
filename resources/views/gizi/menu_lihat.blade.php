@extends('layouts.app')

@section('title', 'Menu dan Bahan')

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div>
        <h1 class="text-2xl font-bold text-white tracking-tight">Menu dan Bahan</h1>
        <p class="text-xs text-slate-400 mt-1">
            Tinjau menu, komposisi bahan dari Admin, dan estimasi nilai gizi per sajian. &bull; Mode Hanya Baca
        </p>
    </div>

    <!-- Card 1: Daftar menu -->
    <div class="bg-[#131f38] border border-[#1f3056] p-6 rounded-2xl space-y-4">
        <h2 class="text-sm font-bold text-white">Daftar menu terdaftar</h2>

        <div class="overflow-x-auto rounded-xl border border-[#1f3056]">
            <table class="w-full text-left text-xs border-collapse">
                <thead>
                    <tr class="bg-[#182645] text-slate-300 font-semibold border-b border-[#1f3056]">
                        <th class="py-3 px-4">Menu / tanggal</th>
                        <th class="py-3 px-4">Kelompok penerima</th>
                        <th class="py-3 px-4">Status</th>
                        <th class="py-3 px-4">Detail</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-[#1f3056]/60 text-slate-300">
                    @forelse($menus as $m)
                        @php $isActive = $selectedMenu && $selectedMenu->id === $m->id; @endphp
                        <tr class="hover:bg-[#182747]/40 {{ $isActive ? 'bg-[#182645]/40' : '' }}">
                            <td class="py-3.5 px-4 font-semibold text-white">
                                {{ $m->nama_menu }} &middot; {{ optional($m->tanggal_penyajian)->format('d M Y') }}
                            </td>
                            <td class="py-3.5 px-4">{{ $m->kelompokPenerima?->nama_kelompok ?? 'Semua kelompok' }}</td>
                            <td class="py-3.5 px-4">
                                <span class="badge {{ $m->status === 'terpublikasi' ? 'badge-success' : ($m->status === 'tervalidasi' ? 'badge-info' : 'badge-warning') }}">
                                    {{ ucfirst(str_replace('_', ' ', $m->status)) }}
                                </span>
                            </td>
                            <td class="py-3.5 px-4">
                                <a href="{{ route('gizi.menu.lihat', ['menu_id' => $m->id]) }}" class="{{ $isActive ? 'text-white font-bold' : 'text-sky-300 hover:text-white' }} underline">
                                    {{ $isActive ? 'Sedang ditinjau' : 'Lihat detail' }}
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="py-6 text-center text-slate-400">Belum ada menu tersedia.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Card 2: Detail Menu Terpilih -->
    @if($selectedMenu)
        <div class="bg-[#131f38] border border-[#1f3056] p-6 rounded-2xl space-y-4">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 border-b border-[#1f3056] pb-3">
                <h2 class="text-sm font-bold text-white">{{ $selectedMenu->nama_menu }}</h2>
                <div class="text-xs text-slate-400">
                    {{ $selectedMenu->jumlah_porsi_rencana }} sajian rencana &middot; &plusmn;{{ $selectedMenu->ukuran_porsi_gram }} g / sajian
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">
                <!-- Left: Table of Ingredients -->
                <div class="lg:col-span-8 overflow-x-auto rounded-xl border border-[#1f3056]">
                    <table class="w-full text-left text-xs border-collapse">
                        <thead>
                            <tr class="bg-[#182645] text-slate-300 font-semibold border-b border-[#1f3056]">
                                <th class="py-3 px-4">Bahan / lot</th>
                                <th class="py-3 px-4">Jumlah batch</th>
                                <th class="py-3 px-4">Berat / sajian</th>
                                <th class="py-3 px-4">Komponen</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-[#1f3056]/60 text-slate-300">
                            @forelse($selectedMenu->menuBahans as $mb)
                                <tr class="hover:bg-[#182747]/40">
                                    <td class="py-3.5 px-4 font-semibold text-white">
                                        {{ $mb->bahanBaku?->nama_bahan ?? '-' }} &middot; {{ $mb->bahanBaku?->kode_lot ?? '-' }}
                                    </td>
                                    <td class="py-3.5 px-4">{{ $mb->jumlah_batch }} {{ $mb->satuan }}</td>
                                    <td class="py-3.5 px-4">{{ $mb->berat_per_sajian_gram }} g</td>
                                    <td class="py-3.5 px-4">{{ $mb->komponen }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="py-6 text-center text-slate-400">Menu ini belum memiliki rincian bahan.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <!-- Right: Gizi per sajian Card -->
                <div class="lg:col-span-4 bg-[#192748] border border-[#2a3d69] p-5 rounded-2xl space-y-3">
                    <h3 class="text-xs font-bold text-white">Gizi per sajian</h3>
                    @if($hasilHitung)
                        <div class="text-xs font-semibold text-slate-200">
                            {{ number_format($hasilHitung['energi_kkal'], 1) }} kkal &bull; Protein {{ number_format($hasilHitung['protein_g'], 1) }} g
                        </div>
                        <div class="text-xs text-slate-300">
                            Lemak {{ number_format($hasilHitung['lemak_g'], 1) }} g &bull; Karbohidrat {{ number_format($hasilHitung['karbohidrat_g'], 1) }} g
                        </div>
                        <div class="text-xs text-slate-300">Serat {{ number_format($hasilHitung['serat_g'], 1) }} g</div>
                    @endif

                    <div class="pt-2 border-t border-[#2a3d69] space-y-2 text-[11px] text-slate-400">
                        <div class="font-semibold text-slate-300">Status: {{ ucfirst(str_replace('_', ' ', $selectedMenu->status)) }}</div>
                        <div class="italic text-[10px] text-slate-500">Jumlah bahan dan porsi hanya dapat diubah Admin SPPG.</div>
                        <div>
                            <a href="{{ route('gizi.hitung.validasi', ['menu_id' => $selectedMenu->id]) }}" class="inline-block px-3.5 py-2 bg-white text-[#0b1329] font-bold rounded-xl text-xs">
                                Buka di Hitung &amp; Validasi &rarr;
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
@endsection
