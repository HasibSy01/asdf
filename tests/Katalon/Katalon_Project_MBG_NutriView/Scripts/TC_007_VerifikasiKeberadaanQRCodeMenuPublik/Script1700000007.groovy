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

WebUI.comment('TC_007: Memastikan gambar QR Code SVG offline ter-render pada kartu menu publik')
WebUI.openBrowser('')
WebUI.navigateToUrl(BASE_URL + '/')
WebUI.waitForPageLoad(TIMEOUT)
TestObject qrImg = xpathObj('img_qr_menu', "//img[contains(@alt,'QR Menu Publik')]")
WebUI.verifyElementPresent(qrImg, TIMEOUT)
String srcAttr = WebUI.getAttribute(qrImg, 'src')
assert srcAttr.startsWith('data:image/svg+xml;base64,')
WebUI.closeBrowser()
