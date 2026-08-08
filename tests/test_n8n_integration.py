import urllib.request
import json
import sys

def test_n8n_weekly_summary_api():
    url = "http://localhost/Capstone_Project_2/backend/api/n8n_weekly_summary.php"
    print(f"[TEST] Calling API: {url}")
    try:
        req = urllib.request.Request(url)
        with urllib.request.urlopen(req) as response:
            data = response.read().decode('utf-8')
            json_data = json.loads(data)
            print("[PASS] Received JSON response from n8n_weekly_summary.php:")
            print(json.dumps(json_data, indent=2, ensure_ascii=True))
            assert json_data.get('status') == 'success', "Status must be success"
            assert 'qc_metrics' in json_data, "qc_metrics missing"
            assert 'production_metrics' in json_data, "production_metrics missing"
            assert 'low_stock_alerts' in json_data, "low_stock_alerts missing"
            print("[SUCCESS] API Endpoint Verification Passed!\n")
            return True
    except Exception as e:
        print(f"[FAIL] API Error: {e}")
        return False

if __name__ == '__main__':
    success = test_n8n_weekly_summary_api()
    if not success:
        sys.exit(1)
