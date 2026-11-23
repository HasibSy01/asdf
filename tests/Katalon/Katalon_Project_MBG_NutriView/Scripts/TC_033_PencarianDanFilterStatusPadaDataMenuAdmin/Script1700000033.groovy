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
