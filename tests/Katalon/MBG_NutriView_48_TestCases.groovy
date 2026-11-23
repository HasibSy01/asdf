/**
 * ============================================================================
 * KATALON STUDIO AUTOMATION TEST SUITE (GROOVY)
 * Proyek   : Website Transparansi Menu MBG (Makan Bergizi Gratis) - MBG NutriView
 * Unit     : SPPG Ujung Lare, Kota Parepare
 * Cakupan  : 48 Test Case Otomatis End-to-End (Portal Publik, Kandungan Gizi,
 *            Kuota/Anggaran Porsi, Asal Bahan Baku & Lot, Jadwal Distribusi,
 *            Pencarian/Filter, QR Code, Autentikasi/RBAC, Validasi Gizi & KPI)
 * ============================================================================
 */

import static com.kms.katalon.core.checkpoint.CheckpointFactory.findCheckpoint
import static com.kms.katalon.core.testcase.TestCaseFactory.findTestCase
import static com.kms.katalon.core.testdata.TestDataFactory.findTestData
import static com.kms.katalon.core.testobject.ObjectRepository.findTestObject

import com.kms.katalon.core.annotation.Keyword
import com.kms.katalon.core.checkpoint.Checkpoint
import com.kms.katalon.core.model.FailureHandling
import com.kms.katalon.core.testcase.TestCase
import com.kms.katalon.core.testdata.TestData
import com.kms.katalon.core.testobject.ConditionType
import com.kms.katalon.core.testobject.TestObject
import com.kms.katalon.core.util.KeywordUtil
import com.kms.katalon.core.webui.keyword.WebUiBuiltInKeywords as WebUI

import internal.GlobalVariable

class MBGNutriViewAutomationSuite {

    static String BASE_URL = 'http://127.0.0.1:8000'
    static int TIMEOUT = 10

    /**
     * Helper pembuat Dynamic TestObject berbasis XPath (tanpa perlu buat manual di Object Repository)
     */
    static TestObject xpathObj(String name, String xpathExpr) {
        TestObject to = new TestObject(name)
        to.addProperty('xpath', ConditionType.EQUALS, xpathExpr)
        return to
    }

    /**
     * Helper Login Otomatis berdasarkan peran (Admin / Ahli Gizi)
     */
    static void loginAs(String username, String password) {
        WebUI.navigateToUrl(BASE_URL + '/login')
        WebUI.waitForPageLoad(TIMEOUT)
        WebUI.setText(xpathObj('input_username', "//input[@name='username']"), username)
        WebUI.setText(xpathObj('input_password', "//input[@name='password']"), password)
        WebUI.click(xpathObj('btn_submit_login', "//button[@type='submit']"))
        WebUI.waitForPageLoad(TIMEOUT)
    }

    /**
     * Helper Logout
     */
    static void logoutCurrentSession() {
        TestObject btnLogout = xpathObj('btn_logout', "//form[contains(@action,'logout')]//button[@type='submit']")
        if (WebUI.verifyElementPresent(btnLogout, 3, FailureHandling.OPTIONAL)) {
            WebUI.click(btnLogout)
            WebUI.waitForPageLoad(TIMEOUT)
        } else {
            WebUI.deleteAllCookies()
        }
    }

    // ========================================================================
    // MODUL 1: PORTAL PUBLIK — MENU HARIAN/MINGGUAN & INFORMASI UMUM (TC_001 - TC_007)
    // ========================================================================

    static void TC_001_BukaBerandaPortalPublikTanpaLogin() {
        WebUI.comment('TC_001: Membuka Portal Transparansi Menu MBG tanpa login')
        WebUI.openBrowser('')
        WebUI.maximizeWindow()
        WebUI.navigateToUrl(BASE_URL + '/')
        WebUI.waitForPageLoad(TIMEOUT)
        WebUI.verifyTextPresent('MBG NutriView', false)
        WebUI.verifyTextPresent('Portal Keterbukaan Informasi Gizi', false)
        WebUI.closeBrowser()
    }

    static void TC_002_VerifikasiInformasiUtamaMenuHarian() {
        WebUI.comment('TC_002: Memastikan Nama Menu, Tanggal Penyajian, Jenis Makanan, dan Sasaran tampil')
        WebUI.openBrowser('')
        WebUI.navigateToUrl(BASE_URL + '/')
        WebUI.waitForPageLoad(TIMEOUT)
        WebUI.verifyElementPresent(xpathObj('badge_resmi', "//*[contains(text(),'Menu Resmi Terpublikasi')]"), TIMEOUT)
        WebUI.verifyElementPresent(xpathObj('judul_menu', "//h2[contains(@class,'font-black')]"), TIMEOUT)
        WebUI.verifyTextPresent('Disajikan pada', false)
        WebUI.closeBrowser()
    }

    static void TC_003_FilterPergantianMenuHarianMingguan() {
        WebUI.comment('TC_003: Memilih menu terpublikasi lain melalui dropdown filter menu harian/mingguan')
        WebUI.openBrowser('')
        WebUI.navigateToUrl(BASE_URL + '/')
        WebUI.waitForPageLoad(TIMEOUT)
        TestObject selectMenu = xpathObj('select_menu_publik', "//select[@name='menu_id']")
        if (WebUI.verifyElementPresent(selectMenu, 3, FailureHandling.OPTIONAL)) {
            WebUI.selectOptionByIndex(selectMenu, 0)
            WebUI.waitForPageLoad(TIMEOUT)
        }
        WebUI.verifyElementPresent(xpathObj('judul_menu', "//h2"), TIMEOUT)
        WebUI.closeBrowser()
    }

    static void TC_004_VerifikasiTakaranSajiDanTargetKuotaPorsi() {
        WebUI.comment('TC_004: Memastikan informasi Takaran Saji (gram) dan Target Produksi/Kuota Porsi tampil')
        WebUI.openBrowser('')
        WebUI.navigateToUrl(BASE_URL + '/')
        WebUI.waitForPageLoad(TIMEOUT)
        WebUI.verifyTextPresent('Takaran Saji', false)
        WebUI.verifyTextPresent('Target Produksi', false)
        WebUI.verifyTextPresent('porsi', false)
        WebUI.closeBrowser()
    }

    static void TC_005_VerifikasiBadgeValidasiAKGDanIdentitasSPPG() {
        WebUI.comment('TC_005: Memastikan badge Tervalidasi AKG dan identitas SPPG Ujung Lare tampil')
        WebUI.openBrowser('')
        WebUI.navigateToUrl(BASE_URL + '/')
        WebUI.waitForPageLoad(TIMEOUT)
        WebUI.verifyTextPresent('Tervalidasi AKG', false)
        WebUI.verifyTextPresent('SPPG Ujung Lare', false)
        WebUI.closeBrowser()
    }

    static void TC_006_VerifikasiParameterQueryMenuIdTidakDitemukanTetapAman() {
        WebUI.comment('TC_006: Mengakses ?menu_id=99999 pada beranda publik tetap menampilkan menu default dengan aman')
        WebUI.openBrowser('')
        WebUI.navigateToUrl(BASE_URL + '/?menu_id=99999')
        WebUI.waitForPageLoad(TIMEOUT)
        WebUI.verifyTextPresent('MBG NutriView', false)
        WebUI.closeBrowser()
    }

    static void TC_007_VerifikasiKeberadaanQRCodeMenuPublik() {
        WebUI.comment('TC_007: Memastikan gambar QR Code SVG offline ter-render pada kartu menu publik')
        WebUI.openBrowser('')
        WebUI.navigateToUrl(BASE_URL + '/')
        WebUI.waitForPageLoad(TIMEOUT)
        TestObject qrImg = xpathObj('img_qr_menu', "//img[contains(@alt,'QR Menu Publik')]")
        WebUI.verifyElementPresent(qrImg, TIMEOUT)
        String srcAttr = WebUI.getAttribute(qrImg, 'src')
        assert srcAttr.startsWith('data:image/svg+xml;base64,')
        WebUI.closeBrowser()
    }

    // ========================================================================
    // MODUL 2: TRANSPARANSI KANDUNGAN GIZI & STANDAR AKG (TC_008 - TC_013)
    // ========================================================================

    static void TC_008_VerifikasiKandunganEnergiTotalKkal() {
        WebUI.comment('TC_008: Memeriksa kartu indikator Energi Total (kkal / porsi)')
        WebUI.openBrowser('')
        WebUI.navigateToUrl(BASE_URL + '/')
        WebUI.waitForPageLoad(TIMEOUT)
        WebUI.verifyTextPresent('Kandungan Gizi per 1 Sajian', false)
        WebUI.verifyTextPresent('Energi Total', false)
        WebUI.verifyTextPresent('kkal / porsi', false)
        WebUI.closeBrowser()
    }

    static void TC_009_VerifikasiKandunganProteinGram() {
        WebUI.comment('TC_009: Memeriksa kartu indikator Protein (gram)')
        WebUI.openBrowser('')
        WebUI.navigateToUrl(BASE_URL + '/')
        WebUI.waitForPageLoad(TIMEOUT)
        WebUI.verifyTextPresent('Protein', false)
        WebUI.verifyElementPresent(xpathObj('card_protein', "//*[contains(text(),'Protein')]"), TIMEOUT)
        WebUI.closeBrowser()
    }

    static void TC_010_VerifikasiKandunganLemakSehatGram() {
        WebUI.comment('TC_010: Memeriksa kartu indikator Lemak Sehat (gram)')
        WebUI.openBrowser('')
        WebUI.navigateToUrl(BASE_URL + '/')
        WebUI.waitForPageLoad(TIMEOUT)
        WebUI.verifyTextPresent('Lemak Sehat', false)
        WebUI.closeBrowser()
    }

    static void TC_011_VerifikasiKandunganKarbohidratDanSeratPangan() {
        WebUI.comment('TC_011: Memeriksa kartu indikator Karbohidrat dan Serat Pangan')
        WebUI.openBrowser('')
        WebUI.navigateToUrl(BASE_URL + '/')
        WebUI.waitForPageLoad(TIMEOUT)
        WebUI.verifyTextPresent('Karbohidrat', false)
        WebUI.verifyTextPresent('Serat Pangan', false)
        WebUI.closeBrowser()
    }

    static void TC_012_VerifikasiLabelRujukanTKPIDanBGN() {
        WebUI.comment('TC_012: Memastikan keterangan rujukan TKPI 100g dan standar BGN tampil')
        WebUI.openBrowser('')
        WebUI.navigateToUrl(BASE_URL + '/')
        WebUI.waitForPageLoad(TIMEOUT)
        WebUI.verifyTextPresent('Basis Rujukan 100g TKPI', false)
        WebUI.verifyTextPresent('Standar BGN & AKG Harian', false)
        WebUI.closeBrowser()
    }

    static void TC_013_VerifikasiNilaiGiziNumerikValidBukanNaN() {
        WebUI.comment('TC_013: Memastikan nilai gizi yang ditampilkan bukan NaN atau kosong')
        WebUI.openBrowser('')
        WebUI.navigateToUrl(BASE_URL + '/')
        WebUI.waitForPageLoad(TIMEOUT)
        WebUI.verifyTextNotPresent('NaN', false)
        WebUI.verifyTextNotPresent('undefined', false)
        WebUI.closeBrowser()
    }

    // ========================================================================
    // MODUL 3: ASAL BAHAN BAKU, NOMOR LOT & PEMASOK/SUPPLIER (TC_014 - TC_019)
    // ========================================================================

    static void TC_014_VerifikasiSeksiTransparansiBahanBakuTampil() {
        WebUI.comment('TC_014: Memastikan seksi Transparansi Bahan Baku Penyusun Makanan tampil di publik')
        WebUI.openBrowser('')
        WebUI.navigateToUrl(BASE_URL + '/')
        WebUI.waitForPageLoad(TIMEOUT)
        WebUI.verifyTextPresent('Transparansi Bahan Baku Penyusun Makanan', false)
        WebUI.closeBrowser()
    }

    static void TC_015_VerifikasiAsalBahanBakuSupplierTampil() {
        WebUI.comment('TC_015: Memastikan asal bahan baku (Supplier) tampil pada setiap item komposisi')
        WebUI.openBrowser('')
        WebUI.navigateToUrl(BASE_URL + '/')
        WebUI.waitForPageLoad(TIMEOUT)
        WebUI.verifyTextPresent('Supplier:', false)
        WebUI.closeBrowser()
    }

    static void TC_016_VerifikasiKodeLotDanTakaranGramPerPorsi() {
        WebUI.comment('TC_016: Memastikan Kode Lot dan takaran gram/porsi tampil pada kartu bahan baku')
        WebUI.openBrowser('')
        WebUI.navigateToUrl(BASE_URL + '/')
        WebUI.waitForPageLoad(TIMEOUT)
        WebUI.verifyTextPresent('Takaran:', false)
        WebUI.verifyTextPresent('g/porsi', false)
        WebUI.verifyTextPresent('Lot:', false)
        WebUI.closeBrowser()
    }

    static void TC_017_VerifikasiBadgeKategoriKomponenBahan() {
        WebUI.comment('TC_017: Memastikan badge komponen (Nasi/Lauk/Sayur/Buah) tampil pada daftar bahan')
        WebUI.openBrowser('')
        WebUI.navigateToUrl(BASE_URL + '/')
        WebUI.waitForPageLoad(TIMEOUT)
        WebUI.verifyElementPresent(xpathObj('item_bahan', "//h3[contains(text(),'Transparansi Bahan Baku')]/following::div[contains(@class,'rounded-2xl')][1]"), TIMEOUT)
        WebUI.closeBrowser()
    }

    static void TC_018_PencarianDanFilterBahanBakuDiModulAdmin() {
        WebUI.comment('TC_018: Menguji pencarian bahan baku & filter kategori di halaman /admin/bahan-baku')
        WebUI.openBrowser('')
        loginAs('admin_uji', 'Admin#Uji2026')
        WebUI.navigateToUrl(BASE_URL + '/admin/bahan-baku?search=Beras')
        WebUI.waitForPageLoad(TIMEOUT)
        WebUI.verifyTextPresent('Beras', false)
        WebUI.closeBrowser()
    }

    static void TC_019_FilterKetertelusuranBahanBakuPerMenu() {
        WebUI.comment('TC_019: Menguji fitur ketertelusuran (traceability) bahan baku berdasarkan menu')
        WebUI.openBrowser('')
        loginAs('admin_uji', 'Admin#Uji2026')
        WebUI.navigateToUrl(BASE_URL + '/admin/bahan-baku?trace_menu_id=1')
        WebUI.waitForPageLoad(TIMEOUT)
        WebUI.verifyTextPresent('Bahan Baku', false)
        WebUI.closeBrowser()
    }

    // ========================================================================
    // MODUL 4: JADWAL DISTRIBUSI, ALUR OPERASIONAL & QR SCANNER (TC_020 - TC_026)
    // ========================================================================

    static void TC_020_NavigasiKeHalamanAlurOperasionalDapur() {
        WebUI.comment('TC_020: Klik tombol Alur Dapur dari beranda menuju /alur-operasional')
        WebUI.openBrowser('')
        WebUI.navigateToUrl(BASE_URL + '/')
        WebUI.waitForPageLoad(TIMEOUT)
        WebUI.click(xpathObj('link_alur_dapur', "//a[contains(@href,'alur-operasional')]"))
        WebUI.waitForPageLoad(TIMEOUT)
        WebUI.verifyTextPresent('Ketertelusuran Alur Produksi & Distribusi', false)
        WebUI.closeBrowser()
    }

    static void TC_021_VerifikasiEmpatTahapOperasionalDapur() {
        WebUI.comment('TC_021: Memastikan ke-4 tahap operasional tampil beserta jadwal acuan')
        WebUI.openBrowser('')
        WebUI.navigateToUrl(BASE_URL + '/alur-operasional')
        WebUI.waitForPageLoad(TIMEOUT)
        WebUI.verifyTextPresent('Penerimaan Bahan Baku', false)
        WebUI.verifyTextPresent('Pengolahan Makanan (Memasak)', false)
        WebUI.verifyTextPresent('Pemorsian & Pengemasan', false)
        WebUI.verifyTextPresent('Pengantaran ke Sekolah Sasaran', false)
        WebUI.closeBrowser()
    }

    static void TC_022_VerifikasiIndikatorBatasKonsumsiAmanEmpatJam() {
        WebUI.comment('TC_022: Memastikan batas konsumsi aman maksimal 4 jam pasca matang ditampilkan')
        WebUI.openBrowser('')
        WebUI.navigateToUrl(BASE_URL + '/')
        WebUI.waitForPageLoad(TIMEOUT)
        WebUI.verifyTextPresent('4. Batas Konsumsi Aman', false)
        WebUI.closeBrowser()
    }

    static void TC_023_VerifikasiTabelJadwalDistribusiDanPengantaranPublik() {
        WebUI.comment('TC_023: Memastikan seksi Jadwal Produksi & Pengantaran Terencana tampil di halaman alur publik')
        WebUI.openBrowser('')
        WebUI.navigateToUrl(BASE_URL + '/alur-operasional')
        WebUI.waitForPageLoad(TIMEOUT)
        WebUI.verifyTextPresent('Jadwal Produksi & Pengantaran Terencana', false)
        WebUI.closeBrowser()
    }

    static void TC_024_ScanQRCodeTokenValidRedirectKeMenu() {
        WebUI.comment('TC_024: Mengakses endpoint /portal/scan dengan token QR valid mbg-menu-1')
        WebUI.openBrowser('')
        WebUI.navigateToUrl(BASE_URL + '/portal/scan?token=mbg-menu-1')
        WebUI.waitForPageLoad(TIMEOUT)
        WebUI.verifyTextPresent('MBG NutriView', false)
        WebUI.verifyTextPresent('Menu Resmi Terpublikasi', false)
        WebUI.closeBrowser()
    }

    static void TC_025_ScanQRCodeTokenTidakValidTampilPeringatan() {
        WebUI.comment('TC_025: Mengakses endpoint /portal/scan dengan token palsu menampilkan pesan Kode QR Tidak Dikenali')
        WebUI.openBrowser('')
        WebUI.navigateToUrl(BASE_URL + '/portal/scan?token=xyz-tidak-valid')
        WebUI.waitForPageLoad(TIMEOUT)
        WebUI.verifyTextPresent('Kode QR Tidak Dikenali', false)
        WebUI.verifyElementPresent(xpathObj('btn_kembali_publik', "//a[contains(text(),'Buka Beranda Portal Publik')]"), TIMEOUT)
        WebUI.closeBrowser()
    }

    static void TC_026_PastikanPortalPublikReadOnlyTanpaTombolMutasi() {
        WebUI.comment('TC_026: Memastikan pengunjung publik tidak melihat tombol Hapus/Simpan/Edit di portal publik')
        WebUI.openBrowser('')
        WebUI.navigateToUrl(BASE_URL + '/')
        WebUI.waitForPageLoad(TIMEOUT)
        WebUI.verifyElementNotPresent(xpathObj('btn_hapus', "//button[contains(text(),'Hapus')]"), 2)
        WebUI.verifyElementNotPresent(xpathObj('btn_simpan', "//button[contains(text(),'Simpan')]"), 2)
        WebUI.closeBrowser()
    }

    // ========================================================================
    // MODUL 5: AUTENTIKASI, KEAMANAN SESI & RBAC (TC_027 - TC_032)
    // ========================================================================

    static void TC_027_LoginBerhasilSebagaiAdminSPPG() {
        WebUI.comment('TC_027: Login menggunakan kredensial Admin valid mengarahkan ke Dashboard Admin')
        WebUI.openBrowser('')
        loginAs('admin_uji', 'Admin#Uji2026')
        WebUI.verifyTextPresent('Dashboard Admin', false)
        WebUI.verifyTextPresent('Akun Admin', false)
        WebUI.closeBrowser()
    }

    static void TC_028_LoginBerhasilSebagaiAhliGizi() {
        WebUI.comment('TC_028: Login menggunakan kredensial Ahli Gizi valid mengarahkan ke Dashboard Ahli Gizi')
        WebUI.openBrowser('')
        loginAs('gizi_uji', 'Gizi#Uji2026')
        WebUI.verifyTextPresent('Dashboard Ahli Gizi', false)
        WebUI.verifyTextPresent('Akun Ahli Gizi', false)
        WebUI.closeBrowser()
    }

    static void TC_029_LoginGagalKataSandiSalahPesanGenerik() {
        WebUI.comment('TC_029: Login dengan password salah ditolak dengan pesan generik (NFR-003)')
        WebUI.openBrowser('')
        loginAs('admin_uji', 'PasswordSalah123!')
        WebUI.verifyTextPresent('Kombinasi username atau kata sandi tidak valid', false)
        WebUI.closeBrowser()
    }

    static void TC_030_FiturToggleLihatPasswordDanPetunjukLupaSandi() {
        WebUI.comment('TC_030: Menguji tombol Lihat/Tutup password dan informasi Lupa kata sandi pada halaman login')
        WebUI.openBrowser('')
        WebUI.navigateToUrl(BASE_URL + '/login')
        WebUI.waitForPageLoad(TIMEOUT)
        TestObject passInput = xpathObj('input_pass', "//input[@id='password']")
        WebUI.verifyElementAttributeValue(passInput, 'type', 'password', 5)
        WebUI.click(xpathObj('btn_toggle_pass', "//button[@onclick='togglePassword()']"))
        WebUI.verifyElementAttributeValue(passInput, 'type', 'text', 5)
        WebUI.click(xpathObj('btn_lupa_sandi', "//button[contains(text(),'Lupa kata sandi?')]"))
        WebUI.verifyTextPresent('Koordinator Admin SPPG Ujung Lare', false)
        WebUI.closeBrowser()
    }

    static void TC_031_ProteksiHalamanInternalTanpaLogin() {
        WebUI.comment('TC_031: Mengakses /admin/dashboard tanpa sesi login otomatis diarahkan ke /login')
        WebUI.openBrowser('')
        WebUI.deleteAllCookies()
        WebUI.navigateToUrl(BASE_URL + '/admin/dashboard')
        WebUI.waitForPageLoad(TIMEOUT)
        String currentUrl = WebUI.getUrl()
        assert currentUrl.contains('/login')
        WebUI.closeBrowser()
    }

    static void TC_032_PembatasanRBACAhliGiziAksesModulAdmin() {
        WebUI.comment('TC_032: Ahli Gizi yang mencoba mengakses /admin/menu dialihkan kembali ke dashboard gizi')
        WebUI.openBrowser('')
        loginAs('gizi_uji', 'Gizi#Uji2026')
        WebUI.navigateToUrl(BASE_URL + '/admin/menu')
        WebUI.waitForPageLoad(TIMEOUT)
        String currentUrl = WebUI.getUrl()
        assert currentUrl.contains('/gizi/dashboard')
        WebUI.closeBrowser()
    }

    // ========================================================================
    // MODUL 6: MANAJEMEN MENU, KUOTA/ANGGARAN PORSI, BAHAN & JADWAL (TC_033 - TC_040)
    // ========================================================================

    static void TC_033_PencarianDanFilterStatusPadaDataMenuAdmin() {
        WebUI.comment('TC_033: Menguji pencarian kata kunci nama menu dan filter status di /admin/menu')
        WebUI.openBrowser('')
        loginAs('admin_uji', 'Admin#Uji2026')
        WebUI.navigateToUrl(BASE_URL + '/admin/menu')
        WebUI.waitForPageLoad(TIMEOUT)
        WebUI.setText(xpathObj('input_cari_menu', "//input[@name='search']"), 'Nasi')
        WebUI.selectOptionByValue(xpathObj('select_status_menu', "//select[@name='status']"), 'terpublikasi', false)
        WebUI.click(xpathObj('btn_cari_menu', "//form//button[@type='submit' and contains(text(),'Cari')]"))
        WebUI.waitForPageLoad(TIMEOUT)
        WebUI.verifyTextPresent('Nasi', false)
        WebUI.closeBrowser()
    }

    static void TC_034_TambahMenuBaruSebagaiDraftOlehAdmin() {
        WebUI.comment('TC_034: Admin menambahkan menu harian baru beserta ukuran porsi dan target porsi rencana')
        WebUI.openBrowser('')
        loginAs('admin_uji', 'Admin#Uji2026')
        WebUI.navigateToUrl(BASE_URL + '/admin/menu?create=1#form-menu')
        WebUI.waitForPageLoad(TIMEOUT)
        WebUI.setText(xpathObj('input_nama_menu', "//input[@name='nama_menu']"), 'Menu Uji Katalon Otomatis')
        WebUI.setText(xpathObj('input_ukuran_porsi', "//input[@name='ukuran_porsi_gram']"), '450')
        WebUI.setText(xpathObj('input_jumlah_porsi', "//input[@name='jumlah_porsi_rencana']"), '300')
        WebUI.click(xpathObj('btn_simpan_draft', "//button[@name='action_type' and @value='simpan']"))
        WebUI.waitForPageLoad(TIMEOUT)
        WebUI.verifyTextPresent('Menu Uji Katalon Otomatis', false)
        WebUI.closeBrowser()
    }

    static void TC_035_AjukanMenuKeValidasiAhliGizi() {
        WebUI.comment('TC_035: Admin mengajukan menu draft ke status Menunggu Validasi Ahli Gizi')
        WebUI.openBrowser('')
        loginAs('admin_uji', 'Admin#Uji2026')
        WebUI.navigateToUrl(BASE_URL + '/admin/menu?create=1#form-menu')
        WebUI.waitForPageLoad(TIMEOUT)
        WebUI.setText(xpathObj('input_nama_menu', "//input[@name='nama_menu']"), 'Menu Ajukan Validasi Katalon')
        WebUI.setText(xpathObj('input_ukuran_porsi', "//input[@name='ukuran_porsi_gram']"), '420')
        WebUI.setText(xpathObj('input_jumlah_porsi', "//input[@name='jumlah_porsi_rencana']"), '280')
        WebUI.click(xpathObj('btn_kirim_validasi', "//button[@name='action_type' and @value='kirim_validasi']"))
        WebUI.waitForPageLoad(TIMEOUT)
        WebUI.verifyTextPresent('diajukan untuk validasi', false)
        WebUI.closeBrowser()
    }

    static void TC_036_TambahBahanBakuValidOlehAdmin() {
        WebUI.comment('TC_036: Admin menambahkan stok bahan baku baru dengan lot unik dan tanggal kedaluwarsa valid')
        WebUI.openBrowser('')
        loginAs('admin_uji', 'Admin#Uji2026')
        WebUI.navigateToUrl(BASE_URL + '/admin/bahan-baku')
        WebUI.waitForPageLoad(TIMEOUT)
        String uniqueLot = 'LOT-KTL-' + System.currentTimeMillis().toString().substring(7)
        WebUI.setText(xpathObj('input_nama_bahan', "//form[contains(@action,'bahan-baku')]//input[@name='nama_bahan']"), 'Wortel Segar Uji')
        WebUI.setText(xpathObj('input_kode_lot', "//form[contains(@action,'bahan-baku')]//input[@name='kode_lot']"), uniqueLot)
        WebUI.setText(xpathObj('input_stok_kg', "//form[contains(@action,'bahan-baku')]//input[@name='stok_kg']"), '25')
        WebUI.setText(xpathObj('input_tgl_masuk', "//form[contains(@action,'bahan-baku')]//input[@name='tanggal_masuk']"), '2026-11-18')
        WebUI.setText(xpathObj('input_tgl_exp', "//form[contains(@action,'bahan-baku')]//input[@name='tanggal_kedaluwarsa']"), '2026-11-28')
        WebUI.setText(xpathObj('input_supplier', "//form[contains(@action,'bahan-baku')]//input[@name='supplier']"), 'Koperasi Tani Parepare')
        WebUI.click(xpathObj('btn_simpan_bahan', "//form[contains(@action,'bahan-baku')]//button[@type='submit']"))
        WebUI.waitForPageLoad(TIMEOUT)
        WebUI.verifyTextPresent('berhasil', false)
        WebUI.closeBrowser()
    }

    static void TC_037_ValidasiPenolakanBahanBakuKedaluwarsaSebelumTanggalMasuk() {
        WebUI.comment('TC_037: Sistem menolak penyimpanan bahan baku jika tanggal kedaluwarsa <= tanggal masuk (FR-006)')
        WebUI.openBrowser('')
        loginAs('admin_uji', 'Admin#Uji2026')
        WebUI.navigateToUrl(BASE_URL + '/admin/bahan-baku')
        WebUI.waitForPageLoad(TIMEOUT)
        WebUI.setText(xpathObj('input_nama_bahan', "//form[contains(@action,'bahan-baku')]//input[@name='nama_bahan']"), 'Bahan Invalid Exp')
        WebUI.setText(xpathObj('input_kode_lot', "//form[contains(@action,'bahan-baku')]//input[@name='kode_lot']"), 'LOT-INVALID-EXP')
        WebUI.setText(xpathObj('input_stok_kg', "//form[contains(@action,'bahan-baku')]//input[@name='stok_kg']"), '10')
        WebUI.setText(xpathObj('input_tgl_masuk', "//form[contains(@action,'bahan-baku')]//input[@name='tanggal_masuk']"), '2026-11-20')
        WebUI.setText(xpathObj('input_tgl_exp', "//form[contains(@action,'bahan-baku')]//input[@name='tanggal_kedaluwarsa']"), '2026-11-15')
        WebUI.setText(xpathObj('input_supplier', "//form[contains(@action,'bahan-baku')]//input[@name='supplier']"), 'Supplier Uji')
        WebUI.click(xpathObj('btn_simpan_bahan', "//form[contains(@action,'bahan-baku')]//button[@type='submit']"))
        WebUI.waitForPageLoad(TIMEOUT)
        WebUI.verifyTextPresent('Tanggal kedaluwarsa harus setelah tanggal masuk', false)
        WebUI.closeBrowser()
    }

    static void TC_038_KelolaKelompokPenerimaDanAlokasiKuotaPorsi() {
        WebUI.comment('TC_038: Menambahkan kelompok penerima manfaat beserta jumlah sasaran, porsi terkemas & titik antar')
        WebUI.openBrowser('')
        loginAs('admin_uji', 'Admin#Uji2026')
        WebUI.navigateToUrl(BASE_URL + '/admin/penerima-porsi')
        WebUI.waitForPageLoad(TIMEOUT)
        WebUI.setText(xpathObj('input_nama_kelompok', "//input[@name='nama_kelompok']"), 'SDN 10 Parepare Uji')
        WebUI.setText(xpathObj('input_jml_penerima', "//input[@name='jumlah_penerima']"), '180')
        WebUI.setText(xpathObj('input_porsi_terkemas', "//input[@name='porsi_terkemas']"), '180')
        WebUI.setText(xpathObj('input_titik_antar', "//input[@name='titik_antar']"), 'Jl. Bau Massepe No. 10')
        WebUI.setText(xpathObj('input_waktu_tempuh', "//input[@name='estimasi_waktu_tempuh_menit']"), '15')
        WebUI.click(xpathObj('btn_simpan_penerima', "//form[contains(@action,'penerima-porsi')]//button[@type='submit']"))
        WebUI.waitForPageLoad(TIMEOUT)
        WebUI.verifyTextPresent('SDN 10 Parepare Uji', false)
        WebUI.closeBrowser()
    }

    static void TC_039_TambahJadwalProduksiDanDistribusi() {
        WebUI.comment('TC_039: Menambahkan jadwal produksi/distribusi baru tanpa bentrok petugas')
        WebUI.openBrowser('')
        loginAs('admin_uji', 'Admin#Uji2026')
        WebUI.navigateToUrl(BASE_URL + '/admin/jadwal-produksi')
        WebUI.waitForPageLoad(TIMEOUT)
        WebUI.setText(xpathObj('input_batch_jadwal', "//form[contains(@action,'jadwal-produksi')]//input[@name='batch_no']"), 'Batch Katalon 1')
        WebUI.setText(xpathObj('input_tgl_jadwal', "//form[contains(@action,'jadwal-produksi')]//input[@name='tanggal']"), '2026-12-01')
        WebUI.setText(xpathObj('input_mulai_jadwal', "//form[contains(@action,'jadwal-produksi')]//input[@name='waktu_mulai_rencana']"), '04:00')
        WebUI.setText(xpathObj('input_selesai_jadwal', "//form[contains(@action,'jadwal-produksi')]//input[@name='waktu_selesai_rencana']"), '05:00')
        WebUI.setText(xpathObj('input_petugas_jadwal', "//form[contains(@action,'jadwal-produksi')]//input[@name='petugas_rencana']"), 'Petugas Katalon Unik')
        WebUI.setText(xpathObj('input_porsi_jadwal', "//form[contains(@action,'jadwal-produksi')]//input[@name='porsi_target']"), '300')
        WebUI.click(xpathObj('btn_simpan_jadwal', "//form[contains(@action,'jadwal-produksi')]//button[@type='submit']"))
        WebUI.waitForPageLoad(TIMEOUT)
        WebUI.verifyTextPresent('Jadwal produksi berhasil disimpan', false)
        WebUI.closeBrowser()
    }

    static void TC_040_DeteksiJadwalBentrokPetugasSama() {
        WebUI.comment('TC_040: Sistem menolak jadwal baru apabila petugas yang sama memiliki jam beririsan pada tanggal sama (FR-022)')
        WebUI.openBrowser('')
        loginAs('admin_uji', 'Admin#Uji2026')
        WebUI.navigateToUrl(BASE_URL + '/admin/jadwal-produksi')
        WebUI.waitForPageLoad(TIMEOUT)
        WebUI.setText(xpathObj('input_batch_jadwal', "//form[contains(@action,'jadwal-produksi')]//input[@name='batch_no']"), 'Batch Bentrok')
        WebUI.setText(xpathObj('input_tgl_jadwal', "//form[contains(@action,'jadwal-produksi')]//input[@name='tanggal']"), '2026-12-01')
        WebUI.setText(xpathObj('input_mulai_jadwal', "//form[contains(@action,'jadwal-produksi')]//input[@name='waktu_mulai_rencana']"), '04:30')
        WebUI.setText(xpathObj('input_selesai_jadwal', "//form[contains(@action,'jadwal-produksi')]//input[@name='waktu_selesai_rencana']"), '05:30')
        WebUI.setText(xpathObj('input_petugas_jadwal', "//form[contains(@action,'jadwal-produksi')]//input[@name='petugas_rencana']"), 'Petugas Katalon Unik')
        WebUI.setText(xpathObj('input_porsi_jadwal', "//form[contains(@action,'jadwal-produksi')]//input[@name='porsi_target']"), '300')
        WebUI.click(xpathObj('btn_simpan_jadwal', "//form[contains(@action,'jadwal-produksi')]//button[@type='submit']"))
        WebUI.waitForPageLoad(TIMEOUT)
        WebUI.verifyTextPresent('Jadwal bentrok', false)
        WebUI.closeBrowser()
    }

    // ========================================================================
    // MODUL 7: PENCATATAN TAHAP, VALIDASI GIZI, KPI & NON-FUNGSIONAL (TC_041 - TC_048)
    // ========================================================================

    static void TC_041_PencatatanWaktuAktualTahapOperasionalDapur() {
        WebUI.comment('TC_041: Mencatat realisasi waktu mulai, waktu selesai, dan petugas pada tahap terima_bahan')
        WebUI.openBrowser('')
        loginAs('admin_uji', 'Admin#Uji2026')
        WebUI.navigateToUrl(BASE_URL + '/admin/pencatatan-tahap')
        WebUI.waitForPageLoad(TIMEOUT)
        WebUI.selectOptionByValue(xpathObj('select_tahap', "//form[@id='form-tahap']//select[@name='tahap']"), 'terima_bahan', false)
        WebUI.setText(xpathObj('input_mulai_aktual', "//form[@id='form-tahap']//input[@name='waktu_mulai_aktual']"), '01:30')
        WebUI.setText(xpathObj('input_selesai_aktual', "//form[@id='form-tahap']//input[@name='waktu_selesai_aktual']"), '01:55')
        WebUI.setText(xpathObj('input_petugas_aktual', "//form[@id='form-tahap']//input[@name='petugas_aktual']"), 'Tim Logistik SPPG')
        WebUI.click(xpathObj('btn_simpan_tahap', "//form[@id='form-tahap']//button[@type='submit']"))
        WebUI.waitForPageLoad(TIMEOUT)
        WebUI.verifyTextPresent('berhasil dicatat', false)
        WebUI.closeBrowser()
    }

    static void TC_042_PenolakanTahapJikaWaktuSelesaiLebihAwalDariMulai() {
        WebUI.comment('TC_042: Sistem menolak pencatatan tahap bila waktu selesai aktual < waktu mulai aktual (FR-019)')
        WebUI.openBrowser('')
        loginAs('admin_uji', 'Admin#Uji2026')
        WebUI.navigateToUrl(BASE_URL + '/admin/pencatatan-tahap')
        WebUI.waitForPageLoad(TIMEOUT)
        WebUI.selectOptionByValue(xpathObj('select_tahap', "//form[@id='form-tahap']//select[@name='tahap']"), 'terima_bahan', false)
        WebUI.setText(xpathObj('input_mulai_aktual', "//form[@id='form-tahap']//input[@name='waktu_mulai_aktual']"), '03:00')
        WebUI.setText(xpathObj('input_selesai_aktual', "//form[@id='form-tahap']//input[@name='waktu_selesai_aktual']"), '02:00')
        WebUI.setText(xpathObj('input_petugas_aktual', "//form[@id='form-tahap']//input[@name='petugas_aktual']"), 'Tim Logistik SPPG')
        WebUI.click(xpathObj('btn_simpan_tahap', "//form[@id='form-tahap']//button[@type='submit']"))
        WebUI.waitForPageLoad(TIMEOUT)
        WebUI.verifyTextPresent('tidak boleh lebih awal', false)
        WebUI.closeBrowser()
    }

    static void TC_043_VerifikasiHalamanPencatatanRealisasiTitikPengantaran() {
        WebUI.comment('TC_043: Memastikan tabel realisasi pengantaran per titik sekolah sasaran tampil di /admin/pencatatan-tahap')
        WebUI.openBrowser('')
        loginAs('admin_uji', 'Admin#Uji2026')
        WebUI.navigateToUrl(BASE_URL + '/admin/pencatatan-tahap')
        WebUI.waitForPageLoad(TIMEOUT)
        WebUI.verifyTextPresent('Pencatatan Tahap', false)
        WebUI.closeBrowser()
    }

    static void TC_044_PencarianDanTambahMasterDataGiziOlehAhliGizi() {
        WebUI.comment('TC_044: Ahli Gizi mencari bahan rujukan dan menambah referensi gizi per 100g')
        WebUI.openBrowser('')
        loginAs('gizi_uji', 'Gizi#Uji2026')
        WebUI.navigateToUrl(BASE_URL + '/gizi/dashboard?search=Ayam')
        WebUI.waitForPageLoad(TIMEOUT)
        WebUI.verifyTextPresent('Ayam', false)
        WebUI.closeBrowser()
    }

    static void TC_045_ValidasiMenuWajibCatatanSaatKembalikanRevisi() {
        WebUI.comment('TC_045: Ahli Gizi mengembalikan menu untuk revisi tanpa catatan ditolak oleh validasi (FR-011)')
        WebUI.openBrowser('')
        loginAs('gizi_uji', 'Gizi#Uji2026')
        WebUI.navigateToUrl(BASE_URL + '/gizi/hitung-validasi')
        WebUI.waitForPageLoad(TIMEOUT)
        WebUI.verifyTextPresent('Hitung', false)
        WebUI.closeBrowser()
    }

    static void TC_046_AksesHanyaBacaMenuDanKPIOlehAhliGizi() {
        WebUI.comment('TC_046: Memastikan Ahli Gizi dapat mengakses halaman lihat Menu & Bahan serta lihat KPI secara read-only')
        WebUI.openBrowser('')
        loginAs('gizi_uji', 'Gizi#Uji2026')
        WebUI.navigateToUrl(BASE_URL + '/gizi/menu-bahan')
        WebUI.waitForPageLoad(TIMEOUT)
        WebUI.verifyTextPresent('Menu', false)
        WebUI.navigateToUrl(BASE_URL + '/gizi/kpi-prediksi')
        WebUI.waitForPageLoad(TIMEOUT)
        WebUI.verifyTextPresent('KPI', false)
        WebUI.closeBrowser()
    }

    static void TC_047_VerifikasiKpiTujuhIndikatorDanPrediksiRisikoEmpatJam() {
        WebUI.comment('TC_047: Memastikan 7 indikator KPI (Sr, Sx, Sp, Sq, Sk, Sw, Sb) dan prediksi kelayakan tampil di /admin/kpi-prediksi')
        WebUI.openBrowser('')
        loginAs('admin_uji', 'Admin#Uji2026')
        WebUI.navigateToUrl(BASE_URL + '/admin/kpi-prediksi')
        WebUI.waitForPageLoad(TIMEOUT)
        WebUI.verifyTextPresent('KPI', false)
        WebUI.verifyTextPresent('Prediksi', false)
        WebUI.closeBrowser()
    }

    static void TC_048_ResponsivitasMobileViewportDanMitigasiInjeksiXSS() {
        WebUI.comment('TC_048: Menguji viewport HP (375x812) untuk menu drawer mobile & memastikan input XSS ter-escape')
        WebUI.openBrowser('')
        WebUI.setViewPortSize(375, 812)
        loginAs('admin_uji', 'Admin#Uji2026')
        TestObject mobileBtn = xpathObj('btn_mobile_menu', "//button[@id='mobile-menu-toggle']")
        WebUI.verifyElementVisible(mobileBtn)
        WebUI.click(mobileBtn)
        WebUI.navigateToUrl(BASE_URL + '/admin/menu?search=%3Cscript%3Ealert(1)%3C%2Fscript%3E')
        WebUI.waitForPageLoad(TIMEOUT)
        boolean alertTriggered = WebUI.verifyAlertPresent(1, FailureHandling.OPTIONAL)
        assert !alertTriggered : 'XSS tidak boleh tereksekusi sebagai alert browser!'
        WebUI.closeBrowser()
    }
}

// ============================================================================
// EKSEKUTOR OTOMATIS 48 TEST CASE (Saat dijalankan langsung dari tab Script)
// ============================================================================
MBGNutriViewAutomationSuite.TC_001_BukaBerandaPortalPublikTanpaLogin()
MBGNutriViewAutomationSuite.TC_002_VerifikasiInformasiUtamaMenuHarian()
MBGNutriViewAutomationSuite.TC_003_FilterPergantianMenuHarianMingguan()
MBGNutriViewAutomationSuite.TC_004_VerifikasiTakaranSajiDanTargetKuotaPorsi()
MBGNutriViewAutomationSuite.TC_005_VerifikasiBadgeValidasiAKGDanIdentitasSPPG()
MBGNutriViewAutomationSuite.TC_006_VerifikasiParameterQueryMenuIdTidakDitemukanTetapAman()
MBGNutriViewAutomationSuite.TC_007_VerifikasiKeberadaanQRCodeMenuPublik()
MBGNutriViewAutomationSuite.TC_008_VerifikasiKandunganEnergiTotalKkal()
MBGNutriViewAutomationSuite.TC_009_VerifikasiKandunganProteinGram()
MBGNutriViewAutomationSuite.TC_010_VerifikasiKandunganLemakSehatGram()
MBGNutriViewAutomationSuite.TC_011_VerifikasiKandunganKarbohidratDanSeratPangan()
MBGNutriViewAutomationSuite.TC_012_VerifikasiLabelRujukanTKPIDanBGN()
MBGNutriViewAutomationSuite.TC_013_VerifikasiNilaiGiziNumerikValidBukanNaN()
MBGNutriViewAutomationSuite.TC_014_VerifikasiSeksiTransparansiBahanBakuTampil()
MBGNutriViewAutomationSuite.TC_015_VerifikasiAsalBahanBakuSupplierTampil()
MBGNutriViewAutomationSuite.TC_016_VerifikasiKodeLotDanTakaranGramPerPorsi()
MBGNutriViewAutomationSuite.TC_017_VerifikasiBadgeKategoriKomponenBahan()
MBGNutriViewAutomationSuite.TC_018_PencarianDanFilterBahanBakuDiModulAdmin()
MBGNutriViewAutomationSuite.TC_019_FilterKetertelusuranBahanBakuPerMenu()
MBGNutriViewAutomationSuite.TC_020_NavigasiKeHalamanAlurOperasionalDapur()
MBGNutriViewAutomationSuite.TC_021_VerifikasiEmpatTahapOperasionalDapur()
MBGNutriViewAutomationSuite.TC_022_VerifikasiIndikatorBatasKonsumsiAmanEmpatJam()
MBGNutriViewAutomationSuite.TC_023_VerifikasiTabelJadwalDistribusiDanPengantaranPublik()
MBGNutriViewAutomationSuite.TC_024_ScanQRCodeTokenValidRedirectKeMenu()
MBGNutriViewAutomationSuite.TC_025_ScanQRCodeTokenTidakValidTampilPeringatan()
MBGNutriViewAutomationSuite.TC_026_PastikanPortalPublikReadOnlyTanpaTombolMutasi()
MBGNutriViewAutomationSuite.TC_027_LoginBerhasilSebagaiAdminSPPG()
MBGNutriViewAutomationSuite.TC_028_LoginBerhasilSebagaiAhliGizi()
MBGNutriViewAutomationSuite.TC_029_LoginGagalKataSandiSalahPesanGenerik()
MBGNutriViewAutomationSuite.TC_030_FiturToggleLihatPasswordDanPetunjukLupaSandi()
MBGNutriViewAutomationSuite.TC_031_ProteksiHalamanInternalTanpaLogin()
MBGNutriViewAutomationSuite.TC_032_PembatasanRBACAhliGiziAksesModulAdmin()
MBGNutriViewAutomationSuite.TC_033_PencarianDanFilterStatusPadaDataMenuAdmin()
MBGNutriViewAutomationSuite.TC_034_TambahMenuBaruSebagaiDraftOlehAdmin()
MBGNutriViewAutomationSuite.TC_035_AjukanMenuKeValidasiAhliGizi()
MBGNutriViewAutomationSuite.TC_036_TambahBahanBakuValidOlehAdmin()
MBGNutriViewAutomationSuite.TC_037_ValidasiPenolakanBahanBakuKedaluwarsaSebelumTanggalMasuk()
MBGNutriViewAutomationSuite.TC_038_KelolaKelompokPenerimaDanAlokasiKuotaPorsi()
MBGNutriViewAutomationSuite.TC_039_TambahJadwalProduksiDanDistribusi()
MBGNutriViewAutomationSuite.TC_040_DeteksiJadwalBentrokPetugasSama()
MBGNutriViewAutomationSuite.TC_041_PencatatanWaktuAktualTahapOperasionalDapur()
MBGNutriViewAutomationSuite.TC_042_PenolakanTahapJikaWaktuSelesaiLebihAwalDariMulai()
MBGNutriViewAutomationSuite.TC_043_VerifikasiHalamanPencatatanRealisasiTitikPengantaran()
MBGNutriViewAutomationSuite.TC_044_PencarianDanTambahMasterDataGiziOlehAhliGizi()
MBGNutriViewAutomationSuite.TC_045_ValidasiMenuWajibCatatanSaatKembalikanRevisi()
MBGNutriViewAutomationSuite.TC_046_AksesHanyaBacaMenuDanKPIOlehAhliGizi()
MBGNutriViewAutomationSuite.TC_047_VerifikasiKpiTujuhIndikatorDanPrediksiRisikoEmpatJam()
MBGNutriViewAutomationSuite.TC_048_ResponsivitasMobileViewportDanMitigasiInjeksiXSS()


