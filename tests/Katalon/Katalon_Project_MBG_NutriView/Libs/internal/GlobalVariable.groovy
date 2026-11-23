package internal

import com.kms.katalon.core.configuration.RunConfiguration
import com.kms.katalon.core.main.TestCaseMain


/**
 * This class is generated automatically by Katalon Studio and should not be modified or deleted.
 */
public class GlobalVariable {
     
    /**
     * <p>Profile default : Base URL Aplikasi MBG NutriView</p>
     */
    public static Object BASE_URL
     

    static {
        try {
            def selectedVariables = TestCaseMain.getGlobalVariables('default')
			selectedVariables += TestCaseMain.getGlobalVariables(RunConfiguration.getExecutionProfile())
    
            BASE_URL = selectedVariables['BASE_URL']
            
        } catch (Exception e) {
            TestCaseMain.logGlobalVariableError(e)
        }
    }
}
