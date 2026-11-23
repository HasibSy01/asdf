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

WebUI.comment('TC_029: Login dengan password salah ditolak dengan pesan generik (NFR-003)')
WebUI.openBrowser('')
loginAs('admin_uji', 'PasswordSalah123!')
WebUI.verifyTextPresent('Kombinasi username atau kata sandi tidak valid', false)
WebUI.closeBrowser()
