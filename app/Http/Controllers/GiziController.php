<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Menu;
use App\Models\DataGizi;
use App\Models\BahanBaku;
use App\Models\KelompokPenerima;
use App\Models\AlurOperasional;
use App\Models\PengantaranTitik;
use App\Models\KpiPrediksi;
use App\Models\AuditLog;
use App\Services\PredictionService;
use Carbon\Carbon;

class GiziController extends Controller
{
    public function dashboard(Request $request)
    {
        $today = Carbon::today();
        
        $menungguHitungCount = Menu::where('status', 'draft')->count();
        $menungguValidasiCount = Menu::where('status', 'menunggu_validasi')->count();
        $siapPublikasiCount = Menu::where('status', 'tervalidasi')->count();
        $terpublikasiCount = Menu::where('is_published', true)->where('status', 'terpublikasi')->count();

        $antreanMenus = Menu::with(['kelompokPenerima', 'menuBahans.bahanBaku'])
            ->orderBy('tanggal_penyajian', 'desc')
            ->get();

        $giziQuery = DataGizi::orderBy('nama_bahan');
        if ($request->filled('search')) {
            $giziQuery->where('nama_bahan', 'LIKE', '%' . $request->search . '%');
        }
        $dataGiziList = $giziQuery->get();
        $editingGizi = $request->filled('edit_gizi') ? DataGizi::find($request->edit_gizi) : null;

        return view('gizi.dashboard', compact(
            'menungguHitungCount',
            'menungguValidasiCount',
            'siapPublikasiCount',
            'terpublikasiCount',
            'antreanMenus',
            'dataGiziList',
            'editingGizi'
        ));
    }

    // --- MASTER DATA GIZI ---
    public function storeGizi(Request $request)
    {
        $validated = $request->validate([
            'nama_bahan' => 'required|string|unique:data_gizis,nama_bahan',
            'basis_berat_gram' => 'required|numeric|min:1',
            'energi_kkal' => 'required|numeric|min:0',
            'protein_g' => 'required|numeric|min:0',
            'lemak_g' => 'required|numeric|min:0',
            'karbohidrat_g' => 'required|numeric|min:0',
            'serat_g' => 'nullable|numeric|min:0',
            'vitamin_mineral' => 'nullable|string',
            'sumber_catatan' => 'nullable|string',
        ]);

        $gizi = DataGizi::create($validated);
        AuditLog::log('tambah_data_gizi', 'data_gizi', (string)$gizi->id, null, $gizi->toArray());

        return redirect()->route('gizi.dashboard')->with('success', 'Data gizi bahan berhasil disimpan.');
    }

    public function updateGizi(Request $request, DataGizi $gizi)
    {
        $validated = $request->validate([
            'nama_bahan' => 'required|string|unique:data_gizis,nama_bahan,' . $gizi->id,
            'basis_berat_gram' => 'required|numeric|min:1',
            'energi_kkal' => 'required|numeric|min:0',
            'protein_g' => 'required|numeric|min:0',
            'lemak_g' => 'required|numeric|min:0',
            'karbohidrat_g' => 'required|numeric|min:0',
            'serat_g' => 'nullable|numeric|min:0',
            'vitamin_mineral' => 'nullable|string',
            'sumber_catatan' => 'nullable|string',
        ]);

        $old = $gizi->toArray();
        $gizi->update($validated);
        AuditLog::log('ubah_data_gizi', 'data_gizi', (string)$gizi->id, $old, $gizi->fresh()->toArray());

        return redirect()->route('gizi.dashboard')->with('success', 'Data gizi berhasil diperbarui.');
    }

    public function destroyGizi(DataGizi $gizi)
    {
        $old = $gizi->toArray();
        $gizi->delete();
        AuditLog::log('hapus_data_gizi', 'data_gizi', (string)$old['id'], $old, null);

        return redirect()->route('gizi.dashboard')->with('success', 'Data gizi berhasil dihapus.');
    }

    // --- HITUNG DAN VALIDASI GIZI ---
    public function hitungValidasi(Request $request)
    {
        $menus = Menu::with(['menuBahans.bahanBaku', 'kelompokPenerima'])
            ->orderBy('tanggal_penyajian', 'desc')
            ->get();

        $selectedMenu = $request->filled('menu_id')
            ? Menu::with(['menuBahans.bahanBaku', 'kelompokPenerima'])->find($request->menu_id)
            : $menus->first();

        if (!$selectedMenu) {
            $selectedMenu = $menus->first();
        }

        $hasilHitung = null;
        $rincianBahanGizi = [];
        $alurs = collect();

        if ($selectedMenu) {
            $hasilHitung = $selectedMenu->hitungGiziPerSajian();
            $alurs = AlurOperasional::where('menu_id', $selectedMenu->id)->get()->keyBy('tahap');

            foreach ($selectedMenu->menuBahans as $mb) {
                $namaBahan = $mb->bahanBaku?->nama_bahan ?? 'Bahan tidak diketahui';
                $giziRef = DataGizi::where('nama_bahan', 'LIKE', '%' . $namaBahan . '%')->first();
                $gram = (float) $mb->berat_per_sajian_gram;
                if ($gram <= 0 && $selectedMenu->jumlah_porsi_rencana > 0) {
                    $gram = ($mb->jumlah_batch * 1000) / $selectedMenu->jumlah_porsi_rencana;
                }
                $factor = $giziRef ? ($gram / ($giziRef->basis_berat_gram ?: 100)) : 0;

                $rincianBahanGizi[] = [
                    'menu_bahan' => $mb,
                    'nama_bahan' => $namaBahan,
                    'berat_sajian_gram' => round($gram, 1),
                    'gizi_found' => (bool) $giziRef,
                    'energi_kkal' => $giziRef ? round($giziRef->energi_kkal * $factor, 1) : 0,
                    'protein_g' => $giziRef ? round($giziRef->protein_g * $factor, 1) : 0,
                    'lemak_g' => $giziRef ? round($giziRef->lemak_g * $factor, 1) : 0,
                    'karbohidrat_g' => $giziRef ? round($giziRef->karbohidrat_g * $factor, 1) : 0,
                    'serat_g' => $giziRef ? round($giziRef->serat_g * $factor, 1) : 0,
                ];
            }
        }

        // Acuan AKG anak sekolah / MBG per sajian makan siang (Permenkes No. 28/2019)
        $targetAkg = [
            'energi_min' => 550,
            'energi_max' => 700,
            'protein_min' => 18,
            'protein_max' => 25,
            'lemak_min' => 15,
            'lemak_max' => 25,
            'karbohidrat_min' => 70,
            'karbohidrat_max' => 100,
            'serat_min' => 6,
            'serat_max' => 10,
        ];

        $allGizi = DataGizi::all();

        return view('gizi.hitung_validasi', compact(
            'menus',
            'selectedMenu',
            'hasilHitung',
            'rincianBahanGizi',
            'alurs',
            'targetAkg',
            'allGizi'
        ));
    }

    public function validasiMenu(Request $request, Menu $menu)
    {
        $validated = $request->validate([
            'status' => 'required|in:tervalidasi,revisi',
            'catatan_revisi' => 'nullable|string|max:1000',
        ]);

        $menu->loadMissing('menuBahans.bahanBaku');
        $old = $menu->toArray();

        if ($validated['status'] === 'tervalidasi') {
            $gizi = $menu->hitungGiziPerSajian();
            if ($menu->menuBahans->isEmpty() || !$gizi['is_complete']) {
                $missingList = !empty($gizi['missing_data'])
                    ? implode(', ', $gizi['missing_data'])
                    : 'belum ada komponen bahan baku pada menu ini';

                return redirect()->route('gizi.hitung.validasi', ['menu_id' => $menu->id])
                    ->with('error', "Menu tidak dapat divalidasi (FR-010): Data gizi per 100g belum lengkap ({$missingList}). Lengkapi referensi gizi terlebih dahulu.");
            }

            $menu->status = 'tervalidasi';
            $menu->catatan_revisi = null;
            $msg = 'Menu telah divalidasi dan disetujui sesuai standar AKG.';
        } else {
            // FR-011: Catatan revisi wajib diisi saat mengembalikan menu untuk perbaikan
            $catatan = trim((string) ($validated['catatan_revisi'] ?? ''));
            if ($catatan === '') {
                return back()->withInput()->withErrors([
                    'catatan_revisi' => 'Catatan revisi wajib diisi saat mengembalikan menu ke Admin (FR-011).',
                ]);
            }

            $menu->status = 'menunggu_validasi';
            $menu->catatan_revisi = $catatan;
            $msg = 'Catatan revisi berhasil disimpan. Menu dikembalikan ke Admin untuk perbaikan.';
        }

        $menu->save();
        AuditLog::log('validasi_menu', 'menu', (string)$menu->id, $old, $menu->fresh()->toArray());

        return redirect()->route('gizi.hitung.validasi', ['menu_id' => $menu->id])->with('success', $msg);
    }

    public function publikasikanMenu(Request $request, Menu $menu)
    {
        // FR-013: Tolak publikasi menu jika belum tervalidasi
        if ($menu->status !== 'tervalidasi') {
            return back()->with('error', 'Validasi gizi diperlukan sebelum menu dapat dipublikasikan (FR-013).');
        }

        $old = $menu->toArray();
        $menu->status = 'terpublikasi';
        $menu->is_published = true;
        $menu->save();

        AuditLog::log('publikasi_menu', 'menu', (string)$menu->id, $old, $menu->fresh()->toArray());

        return back()->with('success', 'Menu berhasil dipublikasikan ke portal publik (FR-012).');
    }

    public function tarikPublikasi(Menu $menu)
    {
        // FR-014: Tarik publikasi menu
        $old = $menu->toArray();
        $menu->status = 'tervalidasi';
        $menu->is_published = false;
        $menu->save();

        AuditLog::log('tarik_publikasi_menu', 'menu', (string)$menu->id, $old, $menu->fresh()->toArray());

        return back()->with('success', 'Menu ditarik dari portal publik (FR-014).');
    }

    // --- MENU DAN BAHAN (HANYA BACA) ---
    public function menuBahanLihat(Request $request)
    {
        $menus = Menu::with(['menuBahans.bahanBaku', 'kelompokPenerima'])
            ->orderBy('tanggal_penyajian', 'desc')
            ->get();

        $selectedMenu = $request->filled('menu_id')
            ? $menus->firstWhere('id', (int) $request->menu_id)
            : $menus->first();

        if (!$selectedMenu) {
            $selectedMenu = $menus->first();
        }

        $hasilHitung = $selectedMenu ? $selectedMenu->hitungGiziPerSajian() : null;
        $jadwalMenu = $selectedMenu
            ? \App\Models\JadwalProduksi::where('menu_id', $selectedMenu->id)->orderBy('waktu_mulai_rencana')->get()
            : collect();

        return view('gizi.menu_lihat', compact('menus', 'selectedMenu', 'hasilHitung', 'jadwalMenu'));
    }

    // --- KPI & PREDIKSI (HANYA BACA) ---
    public function kpiPrediksi(Request $request, PredictionService $predictionService)
    {
        $menus = Menu::orderBy('tanggal_penyajian', 'desc')->get();
        $activeMenu = $request->filled('menu_id')
            ? Menu::find($request->menu_id)
            : (Menu::whereDate('tanggal_penyajian', Carbon::today())->first() ?? Menu::latest()->first());
        $batchNo = $request->input('batch_no', 'Tahap 1');

        $kpi = null;
        $alurs = collect();
        $titikEvaluasi = [];
        if ($activeMenu) {
            $kpi = KpiPrediksi::where('menu_id', $activeMenu->id)->where('batch_no', $batchNo)->first();
            $alurs = AlurOperasional::where('menu_id', $activeMenu->id)->where('batch_no', $batchNo)->get()->keyBy('tahap');

            $alurOlah = $alurs->get('pengolahan');
            $alurKemas = $alurs->get('pengemasan');
            $waktuMatang = $alurOlah?->waktu_matang_terakhir ?? $alurKemas?->waktu_matang_terakhir ?? $alurOlah?->waktu_selesai_aktual;

            foreach (KelompokPenerima::all() as $kel) {
                $eval = $predictionService->evaluasiRisikoTitik($waktuMatang, $alurKemas?->waktu_selesai_aktual, (int) $kel->estimasi_waktu_tempuh_menit);
                $titikEvaluasi[] = [
                    'kelompok' => $kel,
                    'evaluasi' => $eval,
                ];
            }
        }

        $kpiHistory = KpiPrediksi::with('menu')->orderBy('tanggal', 'desc')->orderBy('id', 'desc')->get();
        $kurangData = !$alurs->has('pengemasan');

        return view('gizi.kpi_lihat', compact('menus', 'activeMenu', 'batchNo', 'kpi', 'alurs', 'titikEvaluasi', 'kpiHistory', 'kurangData'));
    }
}

