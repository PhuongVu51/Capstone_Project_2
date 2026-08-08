import re
import csv
import json

log_path = 'test_results.log'
out_csv = 'test_execution_register.csv'

results = []

with open(log_path, 'r', encoding='utf-8') as f:
    lines = f.readlines()

current_tc = None
for line in lines:
    line = line.strip()
    
    # Catch TC headers like "=== TC_AUTH_01: Open login page ==="
    match_header = re.match(r'^===\s+(TC_[A-Z0-9_]+):', line)
    if match_header:
        current_tc = match_header.group(1)
        continue
        
    # Catch test outcomes like "TC_AUTH_01 PASSED: ..." or "TC_AUTH_01 FAILED [BUG]: ..."
    match_result = re.match(r'^(TC_[A-Z0-9_]+)\s+(PASSED|FAILED|SKIPPED)(?:\s+\(Info\))?(?:\s+\[BUG\])?:\s+(.*)', line)
    if match_result:
        tc_id = match_result.group(1)
        status = match_result.group(2)
        actual_result = match_result.group(3)
        
        bug = "Yes" if "FAILED" in status or "[BUG]" in line else ""
        
        results.append({
            'TC_ID': tc_id,
            'Actual Result': actual_result,
            'Status': status,
            'Bug': bug,
            'Evidence': f'{tc_id}_Evidence.png'
        })

# Write to CSV
with open(out_csv, 'w', newline='', encoding='utf-8') as csvfile:
    writer = csv.DictWriter(csvfile, fieldnames=['TC_ID', 'Actual Result', 'Status', 'Bug', 'Evidence'])
    writer.writeheader()
    writer.writerows(results)

print(f"Parsed {len(results)} test cases.")
with open('test_results_summary.json', 'w', encoding='utf-8') as jf:
    json.dump(results, jf, indent=2)
