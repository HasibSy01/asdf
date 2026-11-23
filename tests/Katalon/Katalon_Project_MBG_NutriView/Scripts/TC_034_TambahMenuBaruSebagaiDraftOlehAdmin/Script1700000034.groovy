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
