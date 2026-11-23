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
