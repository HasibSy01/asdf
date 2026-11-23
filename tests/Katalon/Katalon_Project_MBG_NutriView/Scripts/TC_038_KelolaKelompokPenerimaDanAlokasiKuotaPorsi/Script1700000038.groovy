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
