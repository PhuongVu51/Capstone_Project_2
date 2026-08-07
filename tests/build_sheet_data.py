import json, os

with open('tests/test_results_summary.json', 'r', encoding='utf-8') as f:
    data = json.load(f)

def clean(s):
    if not s: return ''
    return str(s).replace('\t', ' ').replace('\n', ' ').strip()

def get_module(tc_id):
    if tc_id.startswith('TC_AUTH_') or tc_id.startswith('TC_SETUP_'): return 1
    if tc_id.startswith('TC_WH_'): return 2
    if tc_id.startswith('TC_INV_'): return 3
    if tc_id.startswith('TC_STOCK_'): return 4
    if tc_id.startswith('TC_QC_'): return 5
    if tc_id.startswith('TC_PROD_'): return 6
    if tc_id.startswith('TC_ANALYTICS_') or tc_id.startswith('TC_FG_'): return 7
    if tc_id.startswith('TC_REPORT_'): return 8
    if tc_id.startswith('TC_SEC_') or tc_id.startswith('TC_UI_'): return 9
    return 1

modules = {}
for item in data:
    m = get_module(item['TC_ID'])
    modules.setdefault(m, []).append(item)

for m, items in modules.items():
    with open(f'tests/module_{m}_data.tsv', 'w', encoding='utf-8') as f:
        for item in items:
            test_data = "Tài khoản / Dữ liệu chuẩn"
            expected = "Hệ thống hoạt động đúng thiết kế"
            actual = clean(item.get('Actual Result', ''))
            status = clean(item.get('Status', ''))
            bug = clean(item.get('Bug', ''))
            evidence = clean(item.get('Evidence', ''))
            line = f"{test_data}\t{expected}\t{actual}\t{status}\t{bug}\t{evidence}\n"
            f.write(line)

print("Generated TSV files for all 9 modules successfully.")
