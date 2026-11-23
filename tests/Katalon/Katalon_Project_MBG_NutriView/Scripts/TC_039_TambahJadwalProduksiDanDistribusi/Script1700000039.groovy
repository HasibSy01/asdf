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
