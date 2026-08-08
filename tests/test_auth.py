import sys; sys.stdout.reconfigure(encoding='utf-8')
import time
import tempfile
import shutil
import os
from selenium import webdriver
from selenium.webdriver.common.by import By
from selenium.webdriver.chrome.service import Service
from selenium.webdriver.chrome.options import Options
from selenium.webdriver.support.ui import WebDriverWait
from selenium.webdriver.support import expected_conditions as EC
from webdriver_manager.chrome import ChromeDriverManager

BASE_URL = "http://localhost/Capstone_Project_2/frontend"
RESULTS = []

def make_driver():
    tmp = tempfile.mkdtemp(prefix="chrome_test_")
    opts = Options()
    opts.add_argument(f"--user-data-dir={tmp}")
    opts.add_argument("--no-sandbox")
    opts.add_argument("--disable-dev-shm-usage")
    svc = Service(ChromeDriverManager().install())
    driver = webdriver.Chrome(service=svc, options=opts)
    driver.implicitly_wait(5)
    return driver, tmp

def log(tc_id, desc, status, note=""):
    icon = "PASS" if status == "PASS" else "FAIL"
    print(f"  [{icon}] {tc_id}: {desc}" + (f" | {note}" if note else ""))
    RESULTS.append({"id": tc_id, "desc": desc, "status": status, "note": note})

def do_login(driver, username, password):
    driver.get(f"{BASE_URL}/login.php")
    time.sleep(1)
    driver.find_element(By.NAME, "USR_username").send_keys(username)
    pwd = driver.find_element(By.NAME, "USR_password_hash")
    pwd.clear()
    pwd.send_keys(password)
    driver.find_element(By.CSS_SELECTOR, "button[type='submit']").click()
    time.sleep(2)

# ─────────────────────────────────────────────
# TC_AUTH_01: Login thành công - pm_alex
# ─────────────────────────────────────────────
def test_auth_01():
    driver, tmp = make_driver()
    try:
        do_login(driver, "pm_alex", "123456")
        url = driver.current_url
        if "dashboard_production" in url:
            log("TC_AUTH_01", "Login thanh cong - pm_alex", "PASS")
        else:
            log("TC_AUTH_01", "Login thanh cong - pm_alex", "FAIL", f"URL: {url}")
    except Exception as e:
        log("TC_AUTH_01", "Login thanh cong - pm_alex", "FAIL", str(e))
        driver.save_screenshot("screenshots/TC_AUTH_01_error.png")
    finally:
        driver.quit(); shutil.rmtree(tmp, ignore_errors=True)

# ─────────────────────────────────────────────
# TC_AUTH_02: Login thất bại - sai mật khẩu
# ─────────────────────────────────────────────
def test_auth_02():
    driver, tmp = make_driver()
    try:
        do_login(driver, "pm_alex", "wrongpass")
        url = driver.current_url
        if "error=wrong_credentials" in url:
            log("TC_AUTH_02", "Login that bai - sai mat khau", "PASS")
        else:
            log("TC_AUTH_02", "Login that bai - sai mat khau", "FAIL", f"URL: {url}")
    except Exception as e:
        log("TC_AUTH_02", "Login that bai - sai mat khau", "FAIL", str(e))
        driver.save_screenshot("screenshots/TC_AUTH_02_error.png")
    finally:
        driver.quit(); shutil.rmtree(tmp, ignore_errors=True)

# ─────────────────────────────────────────────
# TC_AUTH_03: Login thất bại - username không tồn tại
# ─────────────────────────────────────────────
def test_auth_03():
    driver, tmp = make_driver()
    try:
        do_login(driver, "user_khong_ton_tai", "123456")
        url = driver.current_url
        if "error=wrong_credentials" in url:
            log("TC_AUTH_03", "Login that bai - username khong ton tai", "PASS")
        else:
            log("TC_AUTH_03", "Login that bai - username khong ton tai", "FAIL", f"URL: {url}")
    except Exception as e:
        log("TC_AUTH_03", "Login that bai - username khong ton tai", "FAIL", str(e))
    finally:
        driver.quit(); shutil.rmtree(tmp, ignore_errors=True)

# ─────────────────────────────────────────────
# TC_AUTH_04: Redirect đúng Role - qc_linh → qc_dashboard
# ─────────────────────────────────────────────
def test_auth_04():
    driver, tmp = make_driver()
    try:
        do_login(driver, "qc_linh", "123456")
        url = driver.current_url
        if "qc_dashboard" in url:
            log("TC_AUTH_04", "Redirect dung Role - qc_linh → qc_dashboard", "PASS")
        else:
            log("TC_AUTH_04", "Redirect dung Role - qc_linh → qc_dashboard", "FAIL", f"URL: {url}")
    except Exception as e:
        log("TC_AUTH_04", "Redirect dung Role - qc_linh → qc_dashboard", "FAIL", str(e))
    finally:
        driver.quit(); shutil.rmtree(tmp, ignore_errors=True)

# ─────────────────────────────────────────────
# TC_AUTH_05: Redirect đúng Role - wh_peter → dashboard_warehouse
# ─────────────────────────────────────────────
def test_auth_05():
    driver, tmp = make_driver()
    try:
        do_login(driver, "wh_peter", "123456")
        url = driver.current_url
        if "dashboard_warehouse" in url:
            log("TC_AUTH_05", "Redirect dung Role - wh_peter → dashboard_warehouse", "PASS")
        else:
            log("TC_AUTH_05", "Redirect dung Role - wh_peter → dashboard_warehouse", "FAIL", f"URL: {url}")
    except Exception as e:
        log("TC_AUTH_05", "Redirect dung Role - wh_peter → dashboard_warehouse", "FAIL", str(e))
    finally:
        driver.quit(); shutil.rmtree(tmp, ignore_errors=True)

# ─────────────────────────────────────────────
# TC_AUTH_06: Logout xóa session - không vào được dashboard
# ─────────────────────────────────────────────
def test_auth_06():
    driver, tmp = make_driver()
    try:
        # Login trước
        do_login(driver, "pm_alex", "123456")
        assert "dashboard" in driver.current_url, "Login failed"

        # Tìm link logout và click
        logout_link = driver.find_element(By.CSS_SELECTOR, "a[href*='logout']")
        logout_link.click()
        time.sleep(2)

        # Thử truy cập thẳng dashboard
        driver.get(f"{BASE_URL}/dashboard_production.php")
        time.sleep(2)
        url = driver.current_url

        if "login.php" in url:
            log("TC_AUTH_06", "Logout xoa session - redirect ve login", "PASS")
        else:
            log("TC_AUTH_06", "Logout xoa session - redirect ve login", "FAIL", f"Van o: {url}")
    except Exception as e:
        log("TC_AUTH_06", "Logout xoa session - redirect ve login", "FAIL", str(e))
        driver.save_screenshot("screenshots/TC_AUTH_06_error.png")
    finally:
        driver.quit(); shutil.rmtree(tmp, ignore_errors=True)

# ─────────────────────────────────────────────
# TC_AUTH_07: Truy cập trực tiếp dashboard khi chưa login
# ─────────────────────────────────────────────
def test_auth_07():
    driver, tmp = make_driver()
    try:
        driver.get(f"{BASE_URL}/dashboard_production.php")
        time.sleep(2)
        url = driver.current_url
        if "login.php" in url:
            log("TC_AUTH_07", "Bao ve session - truy cap dashboard chua login", "PASS")
        else:
            log("TC_AUTH_07", "Bao ve session - truy cap dashboard chua login", "FAIL", f"URL: {url}")
    except Exception as e:
        log("TC_AUTH_07", "Bao ve session - truy cap dashboard chua login", "FAIL", str(e))
    finally:
        driver.quit(); shutil.rmtree(tmp, ignore_errors=True)

# ─────────────────────────────────────────────
# MAIN - Chạy tất cả và in bảng tổng kết
# ─────────────────────────────────────────────
if __name__ == "__main__":
    os.makedirs("screenshots", exist_ok=True)
    print("\n" + "="*60)
    print("  MODULE AUTH - SELENIUM TEST RUNNER")
    print("="*60)

    tests = [
        test_auth_01,
        test_auth_02,
        test_auth_03,
        test_auth_04,
        test_auth_05,
        test_auth_06,
        test_auth_07,
    ]

    for t in tests:
        print(f"\nRunning {t.__name__}...")
        t()

    print("\n" + "="*60)
    print("  KET QUA TONG HOP")
    print("="*60)
    passed = sum(1 for r in RESULTS if r["status"] == "PASS")
    failed = sum(1 for r in RESULTS if r["status"] == "FAIL")
    for r in RESULTS:
        icon = "✓" if r["status"] == "PASS" else "✗"
        print(f"  {icon} {r['id']:15s} {r['status']:5s}  {r['desc']}")
    print(f"\n  PASS: {passed} | FAIL: {failed} | TOTAL: {len(RESULTS)}")
    print("="*60)
