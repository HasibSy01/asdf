<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Menu;
use App\Models\BahanBaku;
use App\Models\KelompokPenerima;
use App\Models\JadwalProduksi;
use App\Models\AlurOperasional;
use App\Models\PengantaranTitik;
use App\Models\KpiPrediksi;
use App\Models\AuditLog;
use App\Services\KpiService;
use App\Services\PredictionService;
use Carbon\Carbon;

class AdminController extends Controller
{
    protected KpiService $kpiService;
    protected PredictionService $predictionService;

    public function __construct(KpiService $kpiService, PredictionService $predictionService)
    {
        $this->kpiService = $kpiService;
        $this->predictionService = $predictionService;
    }

    public function dashboard()
    {
        $today = Carbon::today();
        $kelompokList = KelompokPenerima::all();
        $penerimaBatch1 = $kelompokList->sum('jumlah_penerima');
        $porsiTerkemasBatch1 = $kelompokList->sum('porsi_terkemas');
        $totalPenerimaDapur = max(2911, $penerimaBatch1); // Kapasitas cakupan SPPG Ujung Lare

        $activeMenu = Menu::with(['menuBahans.bahanBaku', 'kelompokPenerima'])
            ->whereDate('tanggal_penyajian', $today)
            ->first() ?? Menu::with(['menuBahans.bahanBaku', 'kelompokPenerima'])->latest()->first();

        $alurs = collect();
        if ($activeMenu) {
            $alurs = AlurOperasional::where('menu_id', $activeMenu->id)
                ->where('batch_no', 'Tahap 1')
                ->get()
                ->keyBy('tahap');
        }

        // Peringatan bahan kedaluwarsa (berdasarkan tanggal)
        $bahanKedaluwarsa = BahanBaku::where('tanggal_kedaluwarsa', '<', $today)->get();
        $bahanMendekati = BahanBaku::whereBetween('tanggal_kedaluwarsa', [$today, $today->copy()->addDays(2)])->get();

        $kpi = null;
        if ($activeMenu) {
            $kpi = KpiPrediksi::where('menu_id', $activeMenu->id)->where('batch_no', 'Tahap 1')->first();
        }

        $jadwalHariIni = $activeMenu
            ? JadwalProduksi::where('menu_id', $activeMenu->id)->orderBy('waktu_mulai_rencana')->get()
            : collect();

        $recentAudits = AuditLog::orderByDesc('created_at')->take(5)->get();

        return view('admin.dashboard', compact(
            'totalPenerimaDapur',
            'kelompokList',
            'penerimaBatch1',
            'porsiTerkemasBatch1',
            'activeMenu',
            'alurs',
            'bahanKedaluwarsa',
            'bahanMendekati',
            'kpi',
            'jadwalHariIni',
            'recentAudits'
        ));
    }

    // --- DATA MENU ---
    public function dataMenu(Request $request)
    {
        $query = Menu::with(['kelompokPenerima', 'menuBahans.bahanBaku']);

        if ($request->filled('search')) {
            $query->where('nama_menu', 'LIKE', '%' . $request->search . '%');
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $menus = $query->orderBy('tanggal_penyajian', 'desc')->paginate(10)->appends($request->query());
        $kelompoks = KelompokPenerima::all();
        $bahanBakus = BahanBaku::orderBy('nama_bahan')->get();

        $editingMenu = $request->filled('edit')
            ? Menu::with(['kelompokPenerima', 'menuBahans.bahanBaku'])->find($request->edit)
            : $menus->first();
        $editingGizi = $editingMenu ? $editingMenu->hitungGiziPerSajian() : null;

        return view('admin.menu.index', compact('menus', 'kelompoks', 'bahanBakus', 'editingMenu', 'editingGizi'));
    }

    public function storeMenu(Request $request)
    {
        $validated = $request->validate([
            'nama_menu' => 'required|string|max:255',
            'tanggal_penyajian' => 'required|date',
            'jenis_makanan' => 'required|string',
            'ukuran_porsi_gram' => 'required|numeric|min:50',
            'jumlah_porsi_rencana' => 'required|numeric|min:1',
            'kelompok_penerima_id' => 'nullable|exists:kelompok_penerimas,id',
            'foto_menu' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
            'bahan_ids' => 'nullable|array',
            'jumlah_batch' => 'nullable|array',
            'satuan' => 'nullable|array',
            'komponen' => 'nullable|array',
            'berat_per_sajian' => 'nullable|array',
        ], [
            'nama_menu.required' => 'Nama menu wajib diisi.',
            'tanggal_penyajian.required' => 'Tanggal penyajian wajib diisi.',
            'ukuran_porsi_gram.required' => 'Ukuran porsi wajib diisi.',
            'jumlah_porsi_rencana.required' => 'Jumlah porsi rencana wajib diisi.',
        ]);

        $menuData = collect($validated)->only([
            'nama_menu',
            'tanggal_penyajian',
            'jenis_makanan',
            'ukuran_porsi_gram',
            'jumlah_porsi_rencana',
            'kelompok_penerima_id',
        ])->toArray();

        $menuData['status'] = $request->input('action_type') === 'kirim_validasi' ? 'menunggu_validasi' : 'draft';
        if ($fotoPath = $this->handleFotoMenuUpload($request)) {
            $menuData['foto_menu'] = $fotoPath;
        }

        $menu = DB::transaction(function () use ($menuData, $request) {
            $created = Menu::create($menuData);
            $this->syncMenuBahans($created, $request);
            AuditLog::log('tambah_menu', 'menu', (string)$created->id, null, $created->toArray());

            return $created;
        });

        return redirect()->route('admin.menu.index', ['edit' => $menu->id])
            ->with('success', $menu->status === 'menunggu_validasi' ? 'Menu berhasil ditambahkan dan diajukan untuk validasi gizi.' : 'Menu berhasil ditambahkan sebagai Draft.');
    }

    public function updateMenu(Request $request, Menu $menu)
    {
        $validated = $request->validate([
            'nama_menu' => 'required|string|max:255',
            'tanggal_penyajian' => 'required|date',
            'jenis_makanan' => 'required|string',
            'ukuran_porsi_gram' => 'required|numeric|min:50',
            'jumlah_porsi_rencana' => 'required|numeric|min:1',
            'kelompok_penerima_id' => 'nullable|exists:kelompok_penerimas,id',
            'foto_menu' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
            'bahan_ids' => 'nullable|array',
            'jumlah_batch' => 'nullable|array',
            'satuan' => 'nullable|array',
            'komponen' => 'nullable|array',
            'berat_per_sajian' => 'nullable|array',
        ]);

        $oldValues = $menu->toArray();
        $oldStatus = $menu->status;

        $menuData = collect($validated)->only([
            'nama_menu',
            'tanggal_penyajian',
            'jenis_makanan',
            'ukuran_porsi_gram',
            'jumlah_porsi_rencana',
            'kelompok_penerima_id',
        ])->toArray();

        if ($fotoPath = $this->handleFotoMenuUpload($request)) {
            $menuData['foto_menu'] = $fotoPath;
        }

        // FR-007: Jika menu sudah terpublikasi/tervalidasi dan bahan baku diubah, kembalikan ke menunggu_validasi
        $isBahanChanged = $request->has('bahan_ids');

        if (($oldStatus === 'terpublikasi' || $oldStatus === 'tervalidasi') && $isBahanChanged) {
            $menuData['status'] = 'menunggu_validasi';
            $menuData['is_published'] = false; // sembunyikan sementara dari portal publik (FR-007)
        } elseif ($request->input('action_type') === 'kirim_validasi' && $oldStatus === 'draft') {
            $menuData['status'] = 'menunggu_validasi';
        }

        DB::transaction(function () use ($menu, $menuData, $request, $oldValues) {
            $menu->update($menuData);
            $this->syncMenuBahans($menu, $request);
            AuditLog::log('ubah_menu', 'menu', (string)$menu->id, $oldValues, $menu->fresh()->toArray());
        });

        $msg = 'Menu berhasil diperbarui.';
        if (($oldStatus === 'terpublikasi' || $oldStatus === 'tervalidasi') && $isBahanChanged) {
            $msg .= ' Karena bahan diubah, status dikembalikan ke "Menunggu Validasi" untuk diverifikasi ulang oleh Ahli Gizi (FR-007).';
        } elseif ($request->input('action_type') === 'kirim_validasi') {
            $msg .= ' Menu berhasil diajukan untuk validasi Ahli Gizi.';
        }

        return redirect()->route('admin.menu.index', ['edit' => $menu->id])->with('success', $msg);
    }

    public function kirimValidasi(Menu $menu)
    {
        $old = $menu->toArray();
        $menu->status = 'menunggu_validasi';
        $menu->is_published = false;
        $menu->save();

        AuditLog::log('kirim_validasi', 'menu', (string)$menu->id, $old, $menu->fresh()->toArray());

        return redirect()->route('admin.menu.index', ['edit' => $menu->id])
            ->with('success', 'Menu "' . $menu->nama_menu . '" berhasil dikirim ke Ahli Gizi untuk validasi.');
    }

    public function destroyMenu(Menu $menu)
    {
        $old = $menu->toArray();
        $menu->delete();
        AuditLog::log('hapus_menu', 'menu', (string)$old['id'], $old, null);
        return redirect()->route('admin.menu.index')->with('success', 'Menu berhasil dihapus.');
    }

    // --- BAHAN BAKU ---
    public function bahanBaku(Request $request)
    {
        $today = Carbon::today();
        $query = BahanBaku::query();

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('nama_bahan', 'LIKE', '%' . $search . '%')
                  ->orWhere('kode_lot', 'LIKE', '%' . $search . '%')
                  ->orWhere('supplier', 'LIKE', '%' . $search . '%');
            });
        }

        if ($request->filled('status')) {
            if ($request->status === 'kedaluwarsa') {
                $query->where('tanggal_kedaluwarsa', '<', $today);
            } elseif ($request->status === 'mendekati') {
                $query->whereBetween('tanggal_kedaluwarsa', [$today, $today->copy()->addDays(2)]);
            } elseif ($request->status === 'aman') {
                $query->where('tanggal_kedaluwarsa', '>', $today->copy()->addDays(2));
            }
        }

        $bahanList = $query->orderBy('tanggal_kedaluwarsa', 'asc')->paginate(10)->appends($request->query());
        $menus = Menu::with('menuBahans.bahanBaku')->orderByDesc('tanggal_penyajian')->get();
        $selectedTraceMenu = $request->filled('menu_id')
            ? $menus->firstWhere('id', (int) $request->menu_id)
            : $menus->first();

        $allBahan = BahanBaku::all();
        $totalStokKg = $allBahan->sum('stok_kg');
        $kedaluwarsaCount = $allBahan->filter(fn($b) => $b->tanggal_kedaluwarsa && $b->tanggal_kedaluwarsa->lt($today))->count();
        $mendekatiCount = $allBahan->filter(fn($b) => $b->tanggal_kedaluwarsa && $b->tanggal_kedaluwarsa->gte($today) && $b->tanggal_kedaluwarsa->lte($today->copy()->addDays(2)))->count();
        $editingBahan = $request->filled('edit') ? BahanBaku::find($request->edit) : null;

        return view('admin.bahan.index', compact(
            'bahanList',
            'menus',
            'selectedTraceMenu',
            'totalStokKg',
            'kedaluwarsaCount',
            'mendekatiCount',
            'editingBahan'
        ));
    }

    public function storeBahan(Request $request)
    {
        $validated = $this->validateBahanRequest($request);

        $validated['is_utama'] = $request->boolean('is_utama');
        $bahan = BahanBaku::create($validated);

        AuditLog::log('tambah_bahan', 'bahan_baku', (string)$bahan->id, null, $bahan->toArray());

        return redirect()->route('admin.bahan.index')->with('success', 'Bahan baku berhasil disimpan.');
    }

    public function updateBahan(Request $request, BahanBaku $bahan)
    {
        $validated = $this->validateBahanRequest($request, $bahan->id);

        $validated['is_utama'] = $request->boolean('is_utama');
        $old = $bahan->toArray();
        $bahan->update($validated);

        AuditLog::log('ubah_bahan', 'bahan_baku', (string)$bahan->id, $old, $bahan->fresh()->toArray());

        return redirect()->route('admin.bahan.index')->with('success', 'Data bahan baku berhasil diperbarui.');
    }

    public function destroyBahan(BahanBaku $bahan)
    {
        $old = $bahan->toArray();
        $bahan->delete();
        AuditLog::log('hapus_bahan', 'bahan_baku', (string)$old['id'], $old, null);
        return redirect()->route('admin.bahan.index')->with('success', 'Bahan baku berhasil dihapus.');
    }

    // --- PENERIMA DAN PORSI ---
    public function penerimaPorsi(Request $request)
    {
        $kelompokList = KelompokPenerima::orderBy('id')->get();
        $totalPenerima = $kelompokList->sum('jumlah_penerima');
        $totalTerkemas = $kelompokList->sum('porsi_terkemas');
        $editingKelompok = $request->filled('edit') ? KelompokPenerima::find($request->edit) : null;

        return view('admin.penerima.index', compact('kelompokList', 'totalPenerima', 'totalTerkemas', 'editingKelompok'));
    }

    public function storeKelompok(Request $request)
    {
        $validated = $request->validate([
            'nama_kelompok' => 'required|string',
            'kategori' => 'required|string',
            'jumlah_penerima' => 'required|integer|min:1',
            'porsi_terkemas' => 'required|integer|min:0',
            'titik_antar' => 'required|string',
            'estimasi_waktu_tempuh_menit' => 'required|integer|min:1',
        ]);

        $kelompok = KelompokPenerima::create($validated);
        AuditLog::log('tambah_kelompok_penerima', 'kelompok_penerima', (string)$kelompok->id, null, $kelompok->toArray());

        return redirect()->route('admin.penerima.index')->with('success', 'Kelompok penerima berhasil ditambahkan.');
    }

    public function updateKelompok(Request $request, KelompokPenerima $kelompok)
    {
        $validated = $request->validate([
            'nama_kelompok' => 'required|string',
            'kategori' => 'required|string',
            'jumlah_penerima' => 'required|integer|min:1',
            'porsi_terkemas' => 'required|integer|min:0',
            'titik_antar' => 'required|string',
            'estimasi_waktu_tempuh_menit' => 'required|integer|min:1',
        ]);

        $old = $kelompok->toArray();
        $kelompok->update($validated);
        AuditLog::log('ubah_kelompok_penerima', 'kelompok_penerima', (string)$kelompok->id, $old, $kelompok->fresh()->toArray());

        return redirect()->route('admin.penerima.index')->with('success', 'Data kelompok penerima berhasil diperbarui.');
    }

    public function destroyKelompok(KelompokPenerima $kelompok)
    {
        $old = $kelompok->toArray();
        $kelompok->delete();
        AuditLog::log('hapus_kelompok_penerima', 'kelompok_penerima', (string)$old['id'], $old, null);
        return redirect()->route('admin.penerima.index')->with('success', 'Kelompok penerima berhasil dihapus.');
    }

    // --- JADWAL PRODUKSI ---
    public function jadwalProduksi(Request $request)
    {
        $query = JadwalProduksi::with(['menu', 'kelompokPenerima']);
        if ($request->filled('tanggal')) {
            $query->whereDate('tanggal', $request->tanggal);
        }
        if ($request->filled('batch_no')) {
            $query->where('batch_no', $request->batch_no);
        }

        $jadwalList = $query->orderBy('tanggal', 'desc')->orderBy('waktu_mulai_rencana')->get();
        $menus = Menu::orderByDesc('tanggal_penyajian')->get();
        $kelompoks = KelompokPenerima::all();
        $editingJadwal = $request->filled('edit') ? JadwalProduksi::find($request->edit) : null;

        // Deteksi ID jadwal yang beririsan untuk petugas sama
        $bentrokIds = [];
        foreach ($jadwalList as $i => $j1) {
            foreach ($jadwalList as $k => $j2) {
                if ($i >= $k) {
                    continue;
                }
                $sameDay = optional($j1->tanggal)->format('Y-m-d') === optional($j2->tanggal)->format('Y-m-d');
                if ($sameDay && $j1->petugas_rencana === $j2->petugas_rencana
                    && $j1->waktu_mulai_rencana < $j2->waktu_selesai_rencana
                    && $j1->waktu_selesai_rencana > $j2->waktu_mulai_rencana) {
                    $bentrokIds[] = $j1->id;
                    $bentrokIds[] = $j2->id;
                }
            }
        }
        $bentrokIds = array_unique($bentrokIds);

        return view('admin.jadwal.index', compact('jadwalList', 'menus', 'kelompoks', 'editingJadwal', 'bentrokIds'));
    }

    public function storeJadwal(Request $request)
    {
        $validated = $request->validate([
            'menu_id' => 'required|exists:menus,id',
            'kelompok_penerima_id' => 'required|exists:kelompok_penerimas,id',
            'batch_no' => 'required|string',
            'tanggal' => 'required|date',
            'tahap' => 'required|string',
            'waktu_mulai_rencana' => 'required',
            'waktu_selesai_rencana' => 'required',
            'petugas_rencana' => 'required|string',
            'porsi_target' => 'required|integer|min:1',
            'target_tiba' => 'nullable',
        ]);

        $newMulai = strlen($validated['waktu_mulai_rencana']) === 5 ? $validated['waktu_mulai_rencana'] . ':00' : $validated['waktu_mulai_rencana'];
        $newSelesai = strlen($validated['waktu_selesai_rencana']) === 5 ? $validated['waktu_selesai_rencana'] . ':00' : $validated['waktu_selesai_rencana'];

        // FR-022: Deteksi jadwal bentrok untuk petugas yang sama
        if ($bentrok = $this->findJadwalBentrok($validated['tanggal'], $validated['petugas_rencana'], $newMulai, $newSelesai)) {
            return back()->withInput()->with('error', "Jadwal bentrok: Petugas '{$validated['petugas_rencana']}' sudah dialokasikan pada tahap '{$bentrok->tahap}' ({$bentrok->waktu_mulai_rencana} - {$bentrok->waktu_selesai_rencana}).");
        }

        $validated['waktu_mulai_rencana'] = $newMulai;
        $validated['waktu_selesai_rencana'] = $newSelesai;
        if (!empty($validated['target_tiba']) && strlen($validated['target_tiba']) === 5) {
            $validated['target_tiba'] .= ':00';
        }
        $validated['status'] = $request->input('status', 'Direncanakan');

        DB::transaction(function () use ($validated) {
            $jadwal = JadwalProduksi::create($validated);
            AuditLog::log('tambah_jadwal', 'jadwal_produksi', (string)$jadwal->id, null, $jadwal->toArray());
        });

        return redirect()->route('admin.jadwal.index')->with('success', 'Jadwal produksi berhasil disimpan.');
    }

    public function updateJadwal(Request $request, JadwalProduksi $jadwal)
    {
        $validated = $request->validate([
            'menu_id' => 'required|exists:menus,id',
            'kelompok_penerima_id' => 'required|exists:kelompok_penerimas,id',
            'batch_no' => 'required|string',
            'tanggal' => 'required|date',
            'tahap' => 'required|string',
            'waktu_mulai_rencana' => 'required',
            'waktu_selesai_rencana' => 'required',
            'petugas_rencana' => 'required|string',
            'porsi_target' => 'required|integer|min:1',
            'target_tiba' => 'nullable',
            'status' => 'nullable|string',
        ]);

        $newMulai = strlen($validated['waktu_mulai_rencana']) === 5 ? $validated['waktu_mulai_rencana'] . ':00' : $validated['waktu_mulai_rencana'];
        $newSelesai = strlen($validated['waktu_selesai_rencana']) === 5 ? $validated['waktu_selesai_rencana'] . ':00' : $validated['waktu_selesai_rencana'];

        if ($bentrok = $this->findJadwalBentrok($validated['tanggal'], $validated['petugas_rencana'], $newMulai, $newSelesai, $jadwal->id)) {
            return back()->withInput()->with('error', "Jadwal bentrok: Petugas '{$validated['petugas_rencana']}' sudah dialokasikan pada tahap '{$bentrok->tahap}' ({$bentrok->waktu_mulai_rencana} - {$bentrok->waktu_selesai_rencana}).");
        }

        $validated['waktu_mulai_rencana'] = $newMulai;
        $validated['waktu_selesai_rencana'] = $newSelesai;
        if (!empty($validated['target_tiba']) && strlen($validated['target_tiba']) === 5) {
            $validated['target_tiba'] .= ':00';
        }

        $old = $jadwal->toArray();
        DB::transaction(function () use ($jadwal, $validated, $old) {
            $jadwal->update($validated);
            AuditLog::log('ubah_jadwal', 'jadwal_produksi', (string)$jadwal->id, $old, $jadwal->fresh()->toArray());
        });

        return redirect()->route('admin.jadwal.index')->with('success', 'Jadwal produksi berhasil diperbarui.');
    }

    public function destroyJadwal(JadwalProduksi $jadwal)
    {
        $old = $jadwal->toArray();
        $jadwal->delete();
        AuditLog::log('hapus_jadwal', 'jadwal_produksi', (string)$old['id'], $old, null);
        return redirect()->route('admin.jadwal.index')->with('success', 'Jadwal produksi berhasil dihapus.');
    }

    // --- PENCATATAN TAHAP ---
    public function pencatatanTahap(Request $request)
    {
        $today = Carbon::today();
        $menus = Menu::with('kelompokPenerima')->orderByDesc('tanggal_penyajian')->get();
        $kelompoks = KelompokPenerima::all();

        $activeMenu = $request->filled('menu_id')
            ? Menu::with(['menuBahans.bahanBaku', 'kelompokPenerima'])->find($request->menu_id)
            : (Menu::with(['menuBahans.bahanBaku', 'kelompokPenerima'])->whereDate('tanggal_penyajian', $today)->first()
                ?? Menu::with(['menuBahans.bahanBaku', 'kelompokPenerima'])->latest()->first());

        $batchNo = $request->input('batch_no', 'Tahap 1');
        $alurs = collect();
        $titiks = collect();
        $jadwalList = collect();

        if ($activeMenu) {
            $alurs = AlurOperasional::where('menu_id', $activeMenu->id)
                ->where('batch_no', $batchNo)
                ->get()
                ->keyBy('tahap');
            $alurDist = $alurs->get('pengantaran');
            if ($alurDist) {
                $titiks = PengantaranTitik::where('alur_operasional_id', $alurDist->id)->with('kelompokPenerima')->get();
            }
            $jadwalList = JadwalProduksi::where('menu_id', $activeMenu->id)
                ->where('batch_no', $batchNo)
                ->orderBy('waktu_mulai_rencana')
                ->get();
        }

        $bahanList = BahanBaku::orderBy('tanggal_kedaluwarsa')->get();

        return view('admin.tahap.index', compact(
            'menus',
            'kelompoks',
            'activeMenu',
            'batchNo',
            'alurs',
            'titiks',
            'jadwalList',
            'bahanList'
        ));
    }

    public function storeTahap(Request $request)
    {
        // FR-019: Waktu mulai, waktu selesai, dan petugas aktual wajib diisi agar pencatatan tahap sah
        $validated = $request->validate([
            'menu_id' => 'required|exists:menus,id',
            'batch_no' => 'required|string',
            'tanggal' => 'required|date',
            'tahap' => 'required|in:terima_bahan,pengolahan,pengemasan,pengantaran',
            'waktu_mulai_aktual' => 'required|string',
            'waktu_selesai_aktual' => 'required|string',
            'petugas_aktual' => 'required|string|max:255',
            'porsi_terkemas' => 'nullable|integer|min:0',
            'catatan_kendala' => 'nullable|string',
            'waktu_matang_nasi' => 'nullable|string',
            'waktu_matang_lauk' => 'nullable|string',
            'waktu_matang_sayur' => 'nullable|string',
            'waktu_matang_terakhir' => 'nullable|string',
        ], [
            'waktu_mulai_aktual.required' => 'Waktu mulai aktual wajib diisi (FR-019).',
            'waktu_selesai_aktual.required' => 'Waktu selesai aktual wajib diisi (FR-019).',
            'petugas_aktual.required' => 'Nama petugas penanggung jawab tahap wajib diisi (FR-019).',
        ]);

        $tanggal = $validated['tanggal'];
        $mulaiAt = $this->parseStageDateTime($tanggal, $request->waktu_mulai_aktual);
        $selesaiAt = $this->parseStageDateTime($tanggal, $request->waktu_selesai_aktual);

        if (!$mulaiAt || !$selesaiAt) {
            return back()->withInput()->withErrors([
                'waktu_selesai_aktual' => 'Format waktu mulai dan waktu selesai aktual tidak valid (FR-019).',
            ]);
        }

        if ($selesaiAt->lt($mulaiAt)) {
            return back()->withInput()->withErrors([
                'waktu_selesai_aktual' => 'Waktu selesai aktual tidak boleh lebih awal daripada waktu mulai aktual.',
            ]);
        }

        $matangNasi = $this->parseStageDateTime($tanggal, $request->waktu_matang_nasi);
        $matangLauk = $this->parseStageDateTime($tanggal, $request->waktu_matang_lauk);
        $matangSayur = $this->parseStageDateTime($tanggal, $request->waktu_matang_sayur);
        $matangTerakhir = $this->parseStageDateTime($tanggal, $request->waktu_matang_terakhir);

        if (!$matangTerakhir) {
            $matangCandidates = array_filter([$matangNasi, $matangLauk, $matangSayur]);
            if (!empty($matangCandidates)) {
                $matangTerakhir = collect($matangCandidates)->sortByDesc(fn($dt) => $dt->timestamp)->first();
            }
        }

        // FR-020: Urutan tahap (terima_bahan -> pengolahan -> pengemasan -> pengantaran)
        $urutan = ['terima_bahan' => 1, 'pengolahan' => 2, 'pengemasan' => 3, 'pengantaran' => 4];
        $currentOrder = $urutan[$validated['tahap']];

        if ($currentOrder > 1) {
            $prevTahapName = array_search($currentOrder - 1, $urutan);
            $prevRecorded = AlurOperasional::where('menu_id', $validated['menu_id'])
                ->where('batch_no', $validated['batch_no'])
                ->where('tahap', $prevTahapName)
                ->whereNotNull('waktu_selesai_aktual')
                ->first();

            if (!$prevRecorded) {
                session()->flash('warning', "Peringatan urutan tahap (FR-020): Tahap sebelumnya ({$prevTahapName}) belum tercatat selesai.");
            }
        }

        // FR-026: Tandai tahap terlambat jika melewati target jadwal + toleransi T
        $isTerlambat = $this->isTahapTerlambat(
            (int) $validated['menu_id'],
            $validated['batch_no'],
            $tanggal,
            $validated['tahap'],
            $selesaiAt
        );

        $payload = [
            'tanggal' => $tanggal,
            'waktu_mulai_aktual' => $mulaiAt,
            'waktu_selesai_aktual' => $selesaiAt,
            'petugas_aktual' => $validated['petugas_aktual'],
            'porsi_terkemas' => $validated['porsi_terkemas'] ?? null,
            'catatan_kendala' => $validated['catatan_kendala'] ?? null,
            'status_tahap' => $isTerlambat ? 'terlambat' : 'selesai',
        ];

        if ($matangNasi) $payload['waktu_matang_nasi'] = $matangNasi;
        if ($matangLauk) $payload['waktu_matang_lauk'] = $matangLauk;
        if ($matangSayur) $payload['waktu_matang_sayur'] = $matangSayur;
        if ($matangTerakhir) $payload['waktu_matang_terakhir'] = $matangTerakhir;

        $alur = DB::transaction(function () use ($validated, $payload, $mulaiAt, $selesaiAt) {
            $record = AlurOperasional::updateOrCreate(
                [
                    'menu_id' => $validated['menu_id'],
                    'batch_no' => $validated['batch_no'],
                    'tahap' => $validated['tahap'],
                ],
                $payload
            );

            // Pastikan titik pengantaran tersedia ketika mencatat pengantaran
            if ($validated['tahap'] === 'pengantaran' && $record->pengantaranTitiks()->count() === 0) {
                foreach (KelompokPenerima::all() as $kel) {
                    PengantaranTitik::create([
                        'alur_operasional_id' => $record->id,
                        'kelompok_penerima_id' => $kel->id,
                        'petugas_antar' => $validated['petugas_aktual'],
                        'porsi_dikirim' => $kel->porsi_terkemas ?: $kel->jumlah_penerima,
                        'waktu_berangkat_aktual' => $mulaiAt,
                        'waktu_tiba_aktual' => $selesaiAt,
                        'status_antar' => 'Diterima',
                    ]);
                }
            }

            AuditLog::log('catat_tahap', 'alur_operasional', (string)$record->id, null, $record->toArray());

            return $record;
        });

        $menu = Menu::find($validated['menu_id']);

        // Pemicu otomatis (FR-028 & FR-025):
        if ($validated['tahap'] === 'pengemasan' && $selesaiAt) {
            $this->predictionService->jalankanPrediksi($menu, $validated['batch_no']);
        }

        if ($validated['tahap'] === 'pengantaran' && $selesaiAt) {
            $this->kpiService->hitungKpi($menu, $validated['batch_no']);
            $this->predictionService->jalankanPrediksi($menu, $validated['batch_no']);
        }

        $msg = "Tahap {$validated['tahap']} berhasil dicatat.";
        if ($isTerlambat) {
            $msg .= ' Status ditandai TERLAMBAT karena melewati target batas waktu jadwal + toleransi (FR-026).';
        }

        return redirect()->route('admin.tahap.index', ['menu_id' => $validated['menu_id'], 'batch_no' => $validated['batch_no']])
            ->with('success', $msg);
    }

    public function updatePengantaranTitik(Request $request, PengantaranTitik $titik)
    {
        $validated = $request->validate([
            'petugas_antar' => 'nullable|string',
            'porsi_dikirim' => 'required|integer|min:0',
            'waktu_berangkat_aktual' => 'nullable|string',
            'waktu_tiba_aktual' => 'nullable|string',
            'petugas_penerima' => 'nullable|string',
            'status_antar' => 'required|string',
        ]);

        $alur = $titik->alurOperasional;
        $tanggal = $alur?->tanggal ? Carbon::parse($alur->tanggal)->format('Y-m-d') : Carbon::today()->format('Y-m-d');

        $old = $titik->toArray();
        DB::transaction(function () use ($titik, $validated, $tanggal, $request, $alur, $old) {
            $titik->update([
                'petugas_antar' => $validated['petugas_antar'] ?? $titik->petugas_antar,
                'porsi_dikirim' => $validated['porsi_dikirim'],
                'waktu_berangkat_aktual' => $this->parseStageDateTime($tanggal, $request->waktu_berangkat_aktual),
                'waktu_tiba_aktual' => $this->parseStageDateTime($tanggal, $request->waktu_tiba_aktual),
                'petugas_penerima' => $validated['petugas_penerima'] ?? null,
                'status_antar' => $validated['status_antar'],
            ]);

            if ($alur && $alur->menu) {
                $this->kpiService->hitungKpi($alur->menu, $alur->batch_no);
            }

            AuditLog::log('update_pengantaran_titik', 'pengantaran_titik', (string)$titik->id, $old, $titik->fresh()->toArray());
        });

        return back()->with('success', 'Realisasi pengantaran titik berhasil diperbarui.');
    }

    // --- KPI & PREDIKSI RISIKO ---
    public function kpiPrediksi(Request $request)
    {
        $today = Carbon::today();
        $menus = Menu::orderByDesc('tanggal_penyajian')->get();
        $activeMenu = $request->filled('menu_id')
            ? Menu::with('menuBahans.bahanBaku')->find($request->menu_id)
            : (Menu::with('menuBahans.bahanBaku')->whereDate('tanggal_penyajian', $today)->first()
                ?? Menu::with('menuBahans.bahanBaku')->latest()->first());

        $batchNo = $request->input('batch_no', 'Tahap 1');
        $kpi = null;
        $alurs = collect();
        $titikEvaluasi = [];
        $kelompoks = KelompokPenerima::all();

        if ($activeMenu) {
            $kpi = KpiPrediksi::where('menu_id', $activeMenu->id)->where('batch_no', $batchNo)->first();
            $alurs = AlurOperasional::where('menu_id', $activeMenu->id)->where('batch_no', $batchNo)->get()->keyBy('tahap');
            $alurOlah = $alurs->get('pengolahan');
            $alurKemas = $alurs->get('pengemasan');
            $waktuMatang = $alurOlah?->waktu_matang_terakhir ?? $alurKemas?->waktu_matang_terakhir ?? $alurOlah?->waktu_selesai_aktual;

            foreach ($kelompoks as $kel) {
                $titikEvaluasi[] = [
                    'kelompok' => $kel,
                    'evaluasi' => $this->predictionService->evaluasiRisikoTitik(
                        $waktuMatang,
                        $alurKemas?->waktu_selesai_aktual,
                        (int) $kel->estimasi_waktu_tempuh_menit
                    ),
                ];
            }
        }

        $kpiHistory = KpiPrediksi::with('menu')->orderByDesc('tanggal')->orderByDesc('id')->get();
        $kurangData = !$alurs->has('pengemasan');

        return view('admin.kpi.index', compact(
            'menus',
            'activeMenu',
            'batchNo',
            'kpi',
            'alurs',
            'kelompoks',
            'titikEvaluasi',
            'kpiHistory',
            'kurangData'
        ));
    }

    public function hitungUlangKpi(Request $request)
    {
        $request->validate([
            'menu_id' => 'required|exists:menus,id',
            'batch_no' => 'required|string',
        ]);

        $menu = Menu::with('menuBahans.bahanBaku')->findOrFail($request->menu_id);
        $kpi = $this->kpiService->hitungKpi($menu, $request->batch_no);
        $prediksi = $this->predictionService->jalankanPrediksi($menu, $request->batch_no);

        if (!$kpi || ($prediksi['status'] ?? '') === 'data_tidak_lengkap') {
            return redirect()->route('admin.kpi.index', ['menu_id' => $menu->id, 'batch_no' => $request->batch_no])
                ->with('error', 'Data tahap pengemasan atau waktu matang belum lengkap untuk menghitung KPI dan prediksi (FR-027/FR-030).');
        }

        return redirect()->route('admin.kpi.index', ['menu_id' => $menu->id, 'batch_no' => $request->batch_no])
            ->with('success', 'Skor KPI & prediksi kelayakan konsumsi berhasil diperbarui.');
    }

    // --- HELPER PRIVAT ---
    private function handleFotoMenuUpload(Request $request): ?string
    {
        if (!$request->hasFile('foto_menu')) {
            return null;
        }
        $destDir = public_path('uploads/menus');
        if (!is_dir($destDir)) {
            mkdir($destDir, 0755, true);
        }
        $file = $request->file('foto_menu');
        $filename = 'menu-' . time() . '-' . uniqid() . '.' . $file->getClientOriginalExtension();
        $file->move($destDir, $filename);
        return 'uploads/menus/' . $filename;
    }

    private function syncMenuBahans(Menu $menu, Request $request): void
    {
        if (!$request->has('bahan_ids')) {
            return;
        }
        $menu->menuBahans()->delete();
        foreach ($request->bahan_ids as $index => $bahanId) {
            if ($bahanId) {
                $menu->menuBahans()->create([
                    'bahan_baku_id' => $bahanId,
                    'jumlah_batch' => $request->jumlah_batch[$index] ?? 1,
                    'satuan' => $request->satuan[$index] ?? 'kg',
                    'komponen' => $request->komponen[$index] ?? 'Lauk',
                    'berat_per_sajian_gram' => $request->berat_per_sajian[$index] ?? 0,
                ]);
            }
        }
    }

    private function validateBahanRequest(Request $request, ?int $ignoreId = null): array
    {
        $uniqueRule = 'required|string|unique:bahan_bakus,kode_lot' . ($ignoreId ? ',' . $ignoreId : '');

        return $request->validate([
            'nama_bahan' => 'required|string',
            'kode_lot' => $uniqueRule,
            'kategori' => 'required|string',
            'stok_kg' => 'required|numeric|min:0.01',
            'satuan' => 'required|string',
            'tanggal_masuk' => 'required|date',
            'tanggal_kedaluwarsa' => 'required|date|after:tanggal_masuk',
            'supplier' => 'required|string',
        ], [
            'kode_lot.unique' => 'Kode lot sudah terdaftar.',
            'stok_kg.min' => 'Stok bahan minimal harus lebih dari 0 (FR-006).',
            'tanggal_kedaluwarsa.after' => 'Tanggal kedaluwarsa harus setelah tanggal masuk (FR-006).',
            'supplier.required' => 'Nama supplier wajib diisi.',
        ]);
    }

    private function findJadwalBentrok(string $tanggal, string $petugas, string $mulai, string $selesai, ?int $excludeId = null): ?JadwalProduksi
    {
        return JadwalProduksi::when($excludeId, fn($q) => $q->where('id', '!=', $excludeId))
            ->whereDate('tanggal', $tanggal)
            ->where('petugas_rencana', $petugas)
            ->where('waktu_mulai_rencana', '<', $selesai)
            ->where('waktu_selesai_rencana', '>', $mulai)
            ->first();
    }

    private function isTahapTerlambat(int $menuId, string $batchNo, string $tanggal, string $tahap, Carbon $selesaiAt): bool
    {
        $jadwal = JadwalProduksi::where('menu_id', $menuId)
            ->where('batch_no', $batchNo)
            ->get()
            ->first(function (JadwalProduksi $item) use ($tahap) {
                $normalized = strtolower(str_replace([' ', '-'], '_', trim($item->tahap)));
                return $normalized === $tahap || str_contains($normalized, str_replace('_', ' ', $tahap));
            });

        $targetTime = $jadwal?->waktu_selesai_rencana ?? config("mbg.operasional.batas_tahap.{$tahap}");
        if (!$targetTime) {
            return false;
        }

        $toleransiMenit = (int) config('mbg.operasional.toleransi_terlambat_menit', 15);
        $deadline = Carbon::parse($tanggal . ' ' . $targetTime)->addMinutes($toleransiMenit);

        return $selesaiAt->gt($deadline);
    }

    private function parseStageDateTime(string $tanggal, ?string $value): ?Carbon
    {
        if (!$value || trim($value) === '') {
            return null;
        }
        $trimmed = trim($value);
        if (preg_match('/^\d{1,2}:\d{2}(:\d{2})?$/', $trimmed)) {
            return Carbon::parse($tanggal . ' ' . $trimmed);
        }
        return Carbon::parse($trimmed);
    }
}
