import com.kms.katalon.core.model.FailureHandling as FailureHandling
import com.kms.katalon.core.testobject.ConditionType as ConditionType
import com.kms.katalon.core.testobject.TestObject as TestObject
import com.kms.katalon.core.webui.keyword.WebUiBuiltInKeywords as WebUI

String BASE_URL = 'http://127.0.0.1:8000'
int TIMEOUT = 10

TestObject xpathObj(String name, String xpathExpr) {
    TestObject to = new TestObject(name)
    to.addProperty('xpath', ConditionType.EQUALS, xpathExpr)
    return to
}

void loginAs(String username, String password) {
    WebUI.navigateToUrl(BASE_URL + '/login')
    WebUI.waitForPageLoad(TIMEOUT)
    WebUI.setText(xpathObj('input_username', "//input[@name='username']"), username)
    WebUI.setText(xpathObj('input_password', "//input[@name='password']"), password)
    WebUI.click(xpathObj('btn_submit_login', "//button[@type='submit']"))
    WebUI.waitForPageLoad(TIMEOUT)
}

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
