import time
import tempfile
import shutil
from selenium import webdriver
from selenium.webdriver.common.by import By
from selenium.webdriver.chrome.service import Service
from selenium.webdriver.chrome.options import Options
from webdriver_manager.chrome import ChromeDriverManager


def test_login_success():
    # Tạo thư mục profile tạm riêng để tránh xung đột
    tmp_profile = tempfile.mkdtemp(prefix="chrome_selenium_")

    options = Options()
    options.add_argument(f"--user-data-dir={tmp_profile}")
    options.add_argument("--no-sandbox")
    options.add_argument("--disable-dev-shm-usage")

    # webdriver-manager tự tải ChromeDriver đúng version Chrome đang cài
    service = Service(ChromeDriverManager().install())
    driver = webdriver.Chrome(service=service, options=options)

    try:
        print("Mở trang Login...")
        driver.get("http://localhost/Capstone_Project_2/frontend/login.php")
        driver.maximize_window()
        time.sleep(2)

        print("Điền thông tin đăng nhập...")
        driver.find_element(By.NAME, "username").send_keys("pm_alex")
        driver.find_element(By.NAME, "password").send_keys("123456")
        time.sleep(1)

        print("Click nút Đăng nhập...")
        driver.find_element(By.CSS_SELECTOR, "button[type='submit']").click()
        time.sleep(2)

        current_url = driver.current_url
        print(f"URL hiện tại sau khi login: {current_url}")

        assert "dashboard" in current_url.lower(), \
            f"FAIL: Không chuyển được tới Dashboard. URL: {current_url}"
        print("✅ Test Case TC01 - Login Thành Công: PASS!")

    except AssertionError as e:
        print(f"❌ Test Case TC01 - Login Thành Công: FAIL\n   Lý do: {e}")
        driver.save_screenshot("screenshots/TC01_login_failed.png")
        raise

    except Exception as e:
        print(f"❌ Lỗi kỹ thuật: {e}")
        driver.save_screenshot("screenshots/TC01_error.png")
        raise

    finally:
        print("Đóng trình duyệt.")
        driver.quit()
        shutil.rmtree(tmp_profile, ignore_errors=True)


if __name__ == "__main__":
    import os
    os.makedirs("screenshots", exist_ok=True)
    test_login_success()
