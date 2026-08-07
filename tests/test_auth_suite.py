import time
import os
from selenium import webdriver
from selenium.webdriver.common.by import By
from selenium.webdriver.support.ui import WebDriverWait
from selenium.webdriver.support import expected_conditions as EC
from webdriver_manager.chrome import ChromeDriverManager
from selenium.webdriver.chrome.service import Service

# Setup screenshots directory
screenshots_dir = os.path.join(os.path.dirname(os.path.abspath(__file__)), 'screenshots')
os.makedirs(screenshots_dir, exist_ok=True)

def setup_driver():
    options = webdriver.ChromeOptions()
    options.add_argument('--start-maximized')
    service = Service(ChromeDriverManager().install())
    return webdriver.Chrome(service=service, options=options)

def take_screenshot(driver, tc_id):
    screenshot_path = os.path.join(screenshots_dir, f'{tc_id}_Evidence.png')
    driver.save_screenshot(screenshot_path)
    return screenshot_path

def login(driver, username, password):
    driver.get("http://localhost/Capstone_Project_2/frontend/login.php")
    time.sleep(1)
    driver.find_element(By.NAME, "USR_username").clear()
    driver.find_element(By.NAME, "USR_username").send_keys(username)
    driver.find_element(By.NAME, "USR_password_hash").clear()
    driver.find_element(By.NAME, "USR_password_hash").send_keys(password)
    driver.find_element(By.XPATH, "//button[@type='submit']").click()

def run_tc_auth_01():
    print("\n=== TC_AUTH_01: Open login page ===")
    driver = setup_driver()
    try:
        driver.get("http://localhost/Capstone_Project_2/frontend/login.php")
        WebDriverWait(driver, 5).until(EC.presence_of_element_located((By.NAME, "USR_username")))
        take_screenshot(driver, "TC_AUTH_01")
        print("TC_AUTH_01 PASSED")
    except Exception as e:
        print(f"TC_AUTH_01 FAILED: {str(e)}")
    finally:
        driver.quit()

def run_tc_auth_02():
    print("\n=== TC_AUTH_02: Login as Production Manager ===")
    driver = setup_driver()
    try:
        login(driver, "pm_alex", "123456")
        WebDriverWait(driver, 5).until(EC.url_contains("dashboard_production.php"))
        take_screenshot(driver, "TC_AUTH_02")
        print("TC_AUTH_02 PASSED")
    except Exception as e:
        print(f"TC_AUTH_02 FAILED: {str(e)}")
    finally:
        driver.quit()

def run_tc_auth_03():
    print("\n=== TC_AUTH_03: Login as QC user ===")
    driver = setup_driver()
    try:
        login(driver, "nhung_thuy", "123456")
        WebDriverWait(driver, 5).until(EC.url_contains("qc_dashboard.php"))
        take_screenshot(driver, "TC_AUTH_03")
        print("TC_AUTH_03 PASSED")
    except Exception as e:
        print(f"TC_AUTH_03 FAILED: {str(e)}")
    finally:
        driver.quit()

def run_tc_auth_04():
    print("\n=== TC_AUTH_04: Login as Warehouse Staff ===")
    driver = setup_driver()
    try:
        login(driver, "wh_admin04", "123456")
        WebDriverWait(driver, 5).until(EC.url_contains("dashboard_warehouse.php"))
        take_screenshot(driver, "TC_AUTH_04")
        print("TC_AUTH_04 PASSED")
    except Exception as e:
        print(f"TC_AUTH_04 FAILED: {str(e)}")
    finally:
        driver.quit()

def run_tc_auth_05():
    print("\n=== TC_AUTH_05: Invalid password is rejected ===")
    driver = setup_driver()
    try:
        login(driver, "pm_alex", "wrongpassword123")
        WebDriverWait(driver, 5).until(EC.url_contains("error=wrong_credentials"))
        take_screenshot(driver, "TC_AUTH_05")
        print("TC_AUTH_05 PASSED")
    except Exception as e:
        print(f"TC_AUTH_05 FAILED: {str(e)}")
    finally:
        driver.quit()

def run_tc_auth_06():
    print("\n=== TC_AUTH_06: Inactive or unknown user is rejected ===")
    driver = setup_driver()
    try:
        login(driver, "unknown_user_999", "123456")
        WebDriverWait(driver, 5).until(EC.url_contains("error=wrong_credentials"))
        take_screenshot(driver, "TC_AUTH_06")
        print("TC_AUTH_06 PASSED")
    except Exception as e:
        print(f"TC_AUTH_06 FAILED: {str(e)}")
    finally:
        driver.quit()

def run_tc_auth_07():
    print("\n=== TC_AUTH_07: Session redirects already logged-in user ===")
    driver = setup_driver()
    try:
        login(driver, "pm_alex", "123456")
        WebDriverWait(driver, 5).until(EC.url_contains("dashboard_production.php"))
        # Navigate to login again
        driver.get("http://localhost/Capstone_Project_2/frontend/login.php")
        WebDriverWait(driver, 5).until(EC.url_contains("dashboard_production.php"))
        take_screenshot(driver, "TC_AUTH_07")
        print("TC_AUTH_07 PASSED")
    except Exception as e:
        print(f"TC_AUTH_07 FAILED: {str(e)}")
    finally:
        driver.quit()

def run_tc_auth_08():
    print("\n=== TC_AUTH_08: Director login route ===")
    driver = setup_driver()
    try:
        login(driver, "director_demo", "123456")
        WebDriverWait(driver, 5).until(EC.url_contains("dashboard_director.php"))
        take_screenshot(driver, "TC_AUTH_08")
        print("TC_AUTH_08 PASSED")
    except Exception as e:
        print(f"TC_AUTH_08 FAILED: {str(e)}")
    finally:
        driver.quit()

def run_tc_auth_09():
    print("\n=== TC_AUTH_09: Protected page without session ===")
    driver = setup_driver()
    try:
        # Directly go to protected page without login
        driver.get("http://localhost/Capstone_Project_2/frontend/dashboard_production.php")
        WebDriverWait(driver, 5).until(EC.url_contains("login.php"))
        take_screenshot(driver, "TC_AUTH_09")
        print("TC_AUTH_09 PASSED")
    except Exception as e:
        print(f"TC_AUTH_09 FAILED: {str(e)}")
    finally:
        driver.quit()

def run_tc_auth_10():
    print("\n=== TC_AUTH_10: Wrong role receives 403 ===")
    driver = setup_driver()
    try:
        # Login as QC
        login(driver, "nhung_thuy", "123456")
        WebDriverWait(driver, 5).until(EC.url_contains("qc_dashboard.php"))
        # Try to access Production Manager dashboard
        driver.get("http://localhost/Capstone_Project_2/frontend/dashboard_production.php")
        WebDriverWait(driver, 5).until(EC.url_contains("403.php"))
        take_screenshot(driver, "TC_AUTH_10")
        print("TC_AUTH_10 PASSED")
    except Exception as e:
        print(f"TC_AUTH_10 FAILED: {str(e)}")
    finally:
        driver.quit()

if __name__ == "__main__":
    print("Running Authentication Test Suite (TC_AUTH_01 to TC_AUTH_10)...")
    run_tc_auth_01()
    run_tc_auth_02()
    run_tc_auth_03()
    run_tc_auth_04()
    run_tc_auth_05()
    run_tc_auth_06()
    run_tc_auth_07()
    run_tc_auth_08()
    run_tc_auth_09()
    run_tc_auth_10()
    print("\nAll Auth Tests Completed!")
