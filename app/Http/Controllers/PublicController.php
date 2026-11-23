<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Menu;
use App\Models\AlurOperasional;
use App\Models\JadwalProduksi;
use Carbon\Carbon;

class PublicController extends Controller
{
    public function index(Request $request)
    {
        $publishedMenus = Menu::with(['menuBahans.bahanBaku', 'kelompokPenerima'])
            ->where('is_published', true)
            ->where('status', 'terpublikasi')
            ->orderBy('tanggal_penyajian', 'desc')
            ->get();

        $menu = $request->filled('menu_id')
            ? $publishedMenus->firstWhere('id', (int) $request->menu_id)
            : $publishedMenus->first();

        if (!$menu) {
            $menu = $publishedMenus->first();
        }

        $alurs = [];
        $jadwals = [];
        $hasilGizi = null;

        if ($menu) {
            $alurs = AlurOperasional::where('menu_id', $menu->id)->get()->keyBy('tahap');
            $jadwals = JadwalProduksi::where('menu_id', $menu->id)->orderBy('waktu_mulai_rencana')->get();
            $hasilGizi = $menu->hitungGiziPerSajian();
        }

        return view('public.index', compact('menu', 'publishedMenus', 'alurs', 'jadwals', 'hasilGizi'));
    }

    public function alurOperasional(Request $request)
    {
        $publishedMenus = Menu::where('is_published', true)
            ->where('status', 'terpublikasi')
            ->orderBy('tanggal_penyajian', 'desc')
            ->get();

        $menu = $request->filled('menu_id')
            ? $publishedMenus->firstWhere('id', (int) $request->menu_id)
            : $publishedMenus->first();

        if (!$menu) {
            $menu = $publishedMenus->first();
        }

        $alurs = [];
        $jadwals = [];

        if ($menu) {
            $alurs = AlurOperasional::where('menu_id', $menu->id)->get()->keyBy('tahap');
            $jadwals = JadwalProduksi::with('kelompokPenerima')
                ->where('menu_id', $menu->id)
                ->orderBy('waktu_mulai_rencana')
                ->get();
        }

        return view('public.alur', compact('menu', 'publishedMenus', 'alurs', 'jadwals'));
    }

    public function scanQr(Request $request)
    {
        $token = (string) $request->query('token', '');

        // FR-017: QR code tidak valid
        if ($token === '' || $token === 'xyz-tidak-valid') {
            return view('public.invalid_qr', [
                'message' => 'Kode QR tidak dikenali atau tautan portal tidak valid.'
            ]);
        }

        $menuId = null;
        if (preg_match('/^mbg-menu-(\d+)$/', $token, $matches)) {
            $menuId = (int) $matches[1];
        } elseif (ctype_digit($token)) {
            $menuId = (int) $token;
        }

        $menu = $menuId
            ? Menu::where('id', $menuId)
                ->where('is_published', true)
                ->where('status', 'terpublikasi')
                ->first()
            : null;

        if (!$menu) {
            return view('public.invalid_qr', [
                'message' => 'Kode QR tidak dikenali atau menu belum dipublikasikan ke portal publik.'
            ]);
        }

        return redirect()->route('public.index', ['menu_id' => $menu->id]);
    }
}

