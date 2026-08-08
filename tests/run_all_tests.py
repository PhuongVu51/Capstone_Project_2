"""
MASTER TEST RUNNER — Runs all 9 modules (81 test cases total)
Each module is imported and executed sequentially.
Results are aggregated and a summary is printed at the end.
"""
import sys
import os
import time
import importlib

# Add tests directory to path
sys.path.insert(0, os.path.dirname(os.path.abspath(__file__)))

MODULES = [
    ("test_module1", "Module 1: Authentication & Authorisation", 10),
    ("test_module2", "Module 2: Environment & Data Setup", 9),
    ("test_module3", "Module 3: Warehouse Dashboard", 6),
    ("test_module4", "Module 4: Inventory Ledger", 10),
    ("test_module5", "Module 5: Stock-In, Update, Delete & Movement", 11),
    ("test_module6", "Module 6: QC Dashboard, Queue & Inspection", 13),
    ("test_module7", "Module 7: Production Dashboard & FEFO", 8),
    ("test_module8", "Module 8: Finished Goods Declaration", 6),
    ("test_module9", "Module 9: Material Request & Reports", 9),
    ("test_module10", "Module 10: Performance & Stress Testing", 5),
]

def run_all():
    total_cases = sum(m[2] for m in MODULES)
    print("=" * 70)
    print(f"  CAPSTONE PROJECT 2 — FULL TEST SUITE ({total_cases} Test Cases)")
    print("=" * 70)
    
    start_time = time.time()
    
    for mod_name, description, count in MODULES:
        print(f"\n{'='*70}")
        print(f"  {description} ({count} test cases)")
        print(f"{'='*70}")
        try:
            module = importlib.import_module(mod_name)
            # Find and run all run_tc_* functions in order
            funcs = sorted([f for f in dir(module) if f.startswith("run_tc_")])
            for func_name in funcs:
                try:
                    getattr(module, func_name)()
                except Exception as e:
                    print(f"  ERROR in {func_name}: {e}")
        except Exception as e:
            print(f"  FATAL ERROR importing {mod_name}: {e}")
    
    elapsed = time.time() - start_time
    print(f"\n{'='*70}")
    print(f"  ALL TESTS COMPLETE — {total_cases} cases across 10 modules")
    print(f"  Total time: {elapsed:.1f}s")
    print(f"  Screenshots saved in: tests/screenshots/module1..module10/")
    print(f"{'='*70}")


if __name__ == "__main__":
    run_all()
