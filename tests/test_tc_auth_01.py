import time
import os
from selenium import webdriver
from selenium.webdriver.common.by import By
from selenium.webdriver.support.ui import WebDriverWait
from selenium.webdriver.support import expected_conditions as EC
from webdriver_manager.chrome import ChromeDriverManager
from selenium.webdriver.chrome.service import Service

def run_tc_auth_01():
    print("=== STARTING TC_AUTH_01: Valid Login ===")
    
    # Create screenshots directory if it doesn't exist
    screenshots_dir = os.path.join(os.path.dirname(os.path.abspath(__file__)), 'screenshots')
    os.makedirs(screenshots_dir, exist_ok=True)
    
    # Setup Chrome options
    options = webdriver.ChromeOptions()
    options.add_argument('--start-maximized')
    
    # Setup WebDriver
    service = Service(ChromeDriverManager().install())
    driver = webdriver.Chrome(service=service, options=options)
    
    try:
        # Step 1: Navigate to login page
        login_url = "http://localhost/Capstone_Project_2/frontend/login.php"
        print(f"Navigating to {login_url}")
        driver.get(login_url)
        time.sleep(1)
        
        # Step 2: Enter valid credentials
        print("Entering valid credentials (pm_alex / 123456)...")
        # Clear fields before entering text
        username_field = driver.find_element(By.NAME, "USR_username")
        username_field.clear()
        username_field.send_keys("pm_alex")
        
        password_field = driver.find_element(By.NAME, "USR_password_hash")
        password_field.clear()
        password_field.send_keys("123456")
        
        # Step 3: Click login button
        print("Clicking login button...")
        login_button = driver.find_element(By.XPATH, "//button[@type='submit']")
        login_button.click()
        
        # Step 4: Wait for redirect to dashboard
        print("Waiting for dashboard to load...")
        WebDriverWait(driver, 5).until(
            EC.url_contains("dashboard.php")
        )
        
        # Take screenshot of successful login
        screenshot_path = os.path.join(screenshots_dir, 'TC_AUTH_01_Success.png')
        driver.save_screenshot(screenshot_path)
        print(f"Screenshot saved to: {screenshot_path}")
        
        print("✅ TC_AUTH_01 PASSED: Successfully logged in and redirected to dashboard.")
        
    except Exception as e:
        print(f"❌ TC_AUTH_01 FAILED: {str(e)}")
        # Take error screenshot
        error_screenshot = os.path.join(screenshots_dir, 'TC_AUTH_01_Error.png')
        driver.save_screenshot(error_screenshot)
        print(f"Error screenshot saved to: {error_screenshot}")
    finally:
        print("Closing browser...")
        driver.quit()
        print("=== END TC_AUTH_01 ===")

if __name__ == "__main__":
    run_tc_auth_01()
