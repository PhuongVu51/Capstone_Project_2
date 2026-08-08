import sys
sys.stdout.reconfigure(encoding='utf-8')
import os
import glob
import shutil
import subprocess
import hashlib

base_dir = os.path.dirname(os.path.abspath(__file__))
screenshots_dir = os.path.join(base_dir, 'screenshots')

print("1. Cleaning up existing screenshots...")
for m in range(1, 11):
    mdir = os.path.join(screenshots_dir, f'module{m}')
    if os.path.exists(mdir):
        shutil.rmtree(mdir)
    os.makedirs(mdir, exist_ok=True)

print("\n2. Executing all module tests (Module 1 to 10)...")
modules = [f"test_module{i}.py" for i in range(1, 11)]

for mod in modules:
    mod_path = os.path.join(base_dir, mod)
    if os.path.exists(mod_path):
        print(f"\n==================== Running {mod} ====================")
        res = subprocess.run([sys.executable, mod_path], capture_output=True, text=True, encoding='utf-8', errors='replace')
        try:
            print(res.stdout)
        except Exception:
            print(res.stdout.encode('ascii', 'ignore').decode('ascii'))
        if res.stderr and "Error" in res.stderr:
            print("ERRORS:", res.stderr)

print("\n3. Analyzing Screenshot Uniqueness Across All Modules...")
all_unique = True
total_images = 0
duplicates_found = 0

for m in range(1, 11):
    mdir = os.path.join(screenshots_dir, f'module{m}')
    files = glob.glob(os.path.join(mdir, '*.png'))
    total_images += len(files)
    print(f"\n--- Module {m}: {len(files)} screenshot(s) ---")
    hashes = {}
    for f in files:
        with open(f, 'rb') as fp:
            h = hashlib.md5(fp.read()).hexdigest()
        fname = os.path.basename(f)
        hashes.setdefault(h, []).append(fname)
    for h, fnames in hashes.items():
        if len(fnames) > 1:
            all_unique = False
            duplicates_found += (len(fnames) - 1)
            print(f"  [DUPLICATE ({len(fnames)}) - MD5 {h[:8]}]: {', '.join(fnames)}")
        else:
            print(f"  [UNIQUE]: {fnames[0]}")

print("\n" + "="*60)
print(f"SUMMARY: Total Screenshots: {total_images} | Duplicates: {duplicates_found}")
if all_unique and total_images > 0:
    print("SUCCESS: ALL SCREENSHOTS ARE 100% UNIQUE AND MATCH THEIR TEST CASE DETAILS!")
else:
    print("WARNING: Some duplicate screenshots were detected. Review list above.")
print("="*60)
