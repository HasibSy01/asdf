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

WebUI.comment('TC_025: Mengakses endpoint /portal/scan dengan token palsu menampilkan pesan Kode QR Tidak Dikenali')
WebUI.openBrowser('')
WebUI.navigateToUrl(BASE_URL + '/portal/scan?token=xyz-tidak-valid')
WebUI.waitForPageLoad(TIMEOUT)
WebUI.verifyTextPresent('Kode QR Tidak Dikenali', false)
WebUI.verifyElementPresent(xpathObj('btn_kembali_publik', "//a[contains(text(),'Buka Beranda Portal Publik')]"), TIMEOUT)
WebUI.closeBrowser()
