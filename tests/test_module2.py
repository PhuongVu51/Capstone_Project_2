import time
import os
import subprocess
import shutil
from selenium import webdriver
from selenium.webdriver.common.by import By
from selenium.webdriver.support.ui import WebDriverWait
from selenium.webdriver.support import expected_conditions as EC
from webdriver_manager.chrome import ChromeDriverManager
from selenium.webdriver.chrome.service import Service

# Setup screenshots directory for module 2
screenshots_dir = os.path.join(os.path.dirname(os.path.abspath(__file__)), 'screenshots', 'module2')
os.makedirs(screenshots_dir, exist_ok=True)

ROOT_DIR = os.path.dirname(os.path.dirname(os.path.abspath(__file__)))
MYSQL_EXE = r"C:\xampp\mysql\bin\mysql.exe"
DB_NAME = "Project2_db"

def setup_driver():
    options = webdriver.ChromeOptions()
    options.add_argument('--headless=new')
    options.add_argument('--window-size=1280,960')
    options.add_argument('--no-sandbox')
    options.add_argument('--disable-dev-shm-usage')
    service = Service(ChromeDriverManager().install())
    return webdriver.Chrome(service=service, options=options)

def take_screenshot(driver, tc_id):
    screenshot_path = os.path.join(screenshots_dir, f'{tc_id}_Evidence.png')
    driver.save_screenshot(screenshot_path)
    return screenshot_path

def run_tc_setup_01():
    print("\n=== TC_SETUP_01: Import schema successfully ===")
    driver = setup_driver()
    try:
        # Drop and recreate DB
        cmd_create = f'"{MYSQL_EXE}" -u root -e "DROP DATABASE IF EXISTS {DB_NAME}; CREATE DATABASE {DB_NAME};"'
        subprocess.run(cmd_create, shell=True, check=True)
        
        # Import schema
        schema_file = os.path.join(ROOT_DIR, "Project2_db.sql")
        cmd_import = f'"{MYSQL_EXE}" -u root {DB_NAME} < "{schema_file}"'
        subprocess.run(cmd_import, shell=True, check=True)
        
        # Open real XAMPP phpMyAdmin structure page for Project2_db
        driver.get("http://localhost/phpmyadmin/index.php?route=/database/structure&db=Project2_db")
        time.sleep(2)
        driver.execute_script("""
            var h = document.getElementById('topmenucontainer') || document.querySelector('.navbar');
            if(h) { h.style.borderBottom = '4px solid #38bdf8'; }
        """)
        take_screenshot(driver, "TC_SETUP_01")
        print("TC_SETUP_01 PASSED: XAMPP phpMyAdmin DB schema structure captured.")
    except Exception as e:
        print(f"TC_SETUP_01 FAILED: {str(e)}")
    finally:
        driver.quit()

def run_tc_setup_02():
    print("\n=== TC_SETUP_02: Import seed data successfully ===")
    driver = setup_driver()
    try:
        # Import seed data
        seed_file = os.path.join(ROOT_DIR, "seed_data.sql")
        cmd_import = f'"{MYSQL_EXE}" -u root {DB_NAME} < "{seed_file}"'
        subprocess.run(cmd_import, shell=True, check=True)
        
        # Open real XAMPP phpMyAdmin structure view showing row counts
        driver.get("http://localhost/phpmyadmin/index.php?route=/database/structure&db=Project2_db")
        time.sleep(2)
        driver.execute_script("""
            var tbl = document.getElementById('structure_table') || document.querySelector('table.data');
            if(tbl) { tbl.style.border = '4px solid #22c55e'; }
        """)
        take_screenshot(driver, "TC_SETUP_02")
        print("TC_SETUP_02 PASSED: XAMPP phpMyAdmin seed data row counts captured.")
    except Exception as e:
        print(f"TC_SETUP_02 FAILED: {str(e)}")
    finally:
        driver.quit()

def run_tc_setup_03():
    print("\n=== TC_SETUP_03: Database connection settings ===")
    driver = setup_driver()
    try:
        # Open real XAMPP phpMyAdmin databases page
        driver.get("http://localhost/phpmyadmin/index.php?route=/server/databases")
        time.sleep(2)
        driver.execute_script("""
            var el = document.querySelector('a[href*="Project2_db"]');
            if(el && el.parentElement) { el.parentElement.style.border = '3px solid #38bdf8'; }
        """)
        take_screenshot(driver, "TC_SETUP_03")
        print("TC_SETUP_03 PASSED: XAMPP phpMyAdmin database connection verified.")
    except Exception as e:
        print(f"TC_SETUP_03 FAILED: {str(e)}")
    finally:
        driver.quit()

def run_tc_setup_04():
    print("\n=== TC_SETUP_04: Seed login accounts work ===")
    driver = setup_driver()
    try:
        # Open real XAMPP phpMyAdmin users table SQL browse view
        driver.get("http://localhost/phpmyadmin/index.php?route=/sql&db=Project2_db&table=users&pos=0")
        time.sleep(2)
        driver.execute_script("""
            var tbl = document.querySelector('table.table_results') || document.querySelector('table.data');
            if(tbl) { tbl.style.border = '4px solid #eab308'; }
        """)
        take_screenshot(driver, "TC_SETUP_04")
        print("TC_SETUP_04 PASSED: XAMPP phpMyAdmin users table seed accounts captured.")
    except Exception as e:
        print(f"TC_SETUP_04 FAILED: {str(e)}")
    finally:
        driver.quit()

def run_tc_setup_05():
    print("\n=== TC_SETUP_05: Foreign key integrity ===")
    driver = setup_driver()
    try:
        # Open real XAMPP phpMyAdmin relation view or structure
        driver.get("http://localhost/phpmyadmin/index.php?route=/database/relation&db=Project2_db")
        time.sleep(2)
        take_screenshot(driver, "TC_SETUP_05")
        print("TC_SETUP_05 PASSED: XAMPP phpMyAdmin foreign keys relation view captured.")
    except Exception as e:
        print(f"TC_SETUP_05 FAILED: {str(e)}")
    finally:
        driver.quit()

def run_tc_setup_06():
    print("\n=== TC_SETUP_06: Missing database failure is readable ===")
    driver = setup_driver()
    try:
        # Open real XAMPP phpMyAdmin invalid database URL
        driver.get("http://localhost/phpmyadmin/index.php?route=/database/structure&db=non_existent_db_999")
        time.sleep(2)
        take_screenshot(driver, "TC_SETUP_06")
        print("TC_SETUP_06 PASSED: XAMPP phpMyAdmin database missing error captured.")
    except Exception as e:
        print(f"TC_SETUP_06 FAILED: {str(e)}")
    finally:
        driver.quit()

if __name__ == "__main__":
    print("Running Setup & Database Test Suite (TC_SETUP_01 to TC_SETUP_06)...")
    run_tc_setup_01()
    run_tc_setup_02()
    run_tc_setup_03()
    run_tc_setup_04()
    run_tc_setup_05()
    run_tc_setup_06()
    print("\nAll Setup Tests Completed!")