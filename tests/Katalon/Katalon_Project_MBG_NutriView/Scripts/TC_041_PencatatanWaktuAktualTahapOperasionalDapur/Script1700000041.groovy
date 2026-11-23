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
