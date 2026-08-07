import os
import glob
import hashlib

screenshots_base = os.path.join(os.path.dirname(__file__), 'screenshots')

for m in sorted(os.listdir(screenshots_base)):
    mdir = os.path.join(screenshots_base, m)
    if os.path.isdir(mdir):
        files = glob.glob(os.path.join(mdir, '*.png'))
        print(f"=== {m}: {len(files)} files ===")
        hashes = {}
        for f in files:
            with open(f, 'rb') as fp:
                h = hashlib.md5(fp.read()).hexdigest()
            fname = os.path.basename(f)
            hashes.setdefault(h, []).append(fname)
        for h, fnames in hashes.items():
            if len(fnames) > 1:
                print(f"  [DUPLICATE ({len(fnames)}) - MD5 {h[:8]}]: {', '.join(fnames)}")
            else:
                print(f"  [UNIQUE]: {fnames[0]}")
