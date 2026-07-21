<?php
// Đường dẫn: frontend/login.php
session_start();
// Tránh lỗi vòng lặp, nếu đã đăng nhập thì đá thẳng vào dashboard tương ứng
if (isset($_SESSION['role'])) {
    $role = $_SESSION['role'];
    if ($role == 'Production_Manager') header("Location: dashboard_production.php");
    elseif ($role == 'QC') header("Location: qc_dashboard.php"); // Sửa lại thành qc_dashboard.php theo MVC mới
    elseif ($role == 'Warehouse_Staff') header("Location: dashboard_warehouse.php");
    elseif ($role == 'Director') header("Location: dashboard_director.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ProSync Industrial - Login</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        :root { --bg-dark: #0a1118; --bg-card: #0f1722; --accent-green: #10b981; --border-color: #1f2937; }
        body { background-color: var(--bg-dark); font-family: 'Inter', sans-serif; }
    </style>
</head>
<body class="min-h-screen flex items-center justify-center text-gray-300">

    <div class="bg-[#0f1722] p-10 rounded-xl border border-[#1f2937] shadow-2xl shadow-emerald-900/10 w-full max-w-md">
        <div class="text-center mb-8">
            <h1 class="text-2xl font-bold text-white tracking-wide"><span class="text-[#10b981]">F&G</span> Food</h1>
            <p class="text-sm text-gray-500 mt-2">Precision Systems • Node Authentication</p>
        </div>

        <?php if (isset($_GET['error']) && $_GET['error'] == 'wrong_credentials'): ?>
            <div class="bg-red-900/30 border border-red-500/50 text-red-400 text-sm p-3 rounded mb-5 text-center">
                Authentication Failed. Invalid Operator ID or Passcode.
            </div>
        <?php endif; ?>

        <form action="../backend/connection/process_login.php" method="POST">
            
            <div class="mb-5 mt-2">
                <label for="username" class="block text-xs font-semibold text-gray-400 uppercase tracking-wider mb-2">Operator ID</label>
                <input type="text" id="username" name="USR_username" required
                    class="w-full bg-[#0a1118] border border-[#1f2937] text-gray-200 text-sm rounded px-4 py-3 focus:outline-none focus:border-[#10b981] focus:ring-1 focus:ring-[#10b981] transition-all placeholder-gray-600"
                    placeholder="e.g., pm_alex">
            </div>

            <div class="mb-8">
                <label for="password" class="block text-xs font-semibold text-gray-400 uppercase tracking-wider mb-2">Passcode</label>
                <input type="password" id="password" name="USR_password_hash" required value="123456"
                    class="w-full bg-[#0a1118] border border-[#1f2937] text-gray-200 text-sm rounded px-4 py-3 focus:outline-none focus:border-[#10b981] focus:ring-1 focus:ring-[#10b981] transition-all placeholder-gray-600">
            </div>

            <button type="submit" 
                class="w-full bg-[#10b981] hover:bg-[#059669] text-gray-900 font-bold py-3 px-4 rounded transition-colors duration-200">
                Authenticate Session
            </button>
            
            <div class="text-center mt-5">
                <p class="text-xs text-gray-600">Secure connection established. All access attempts are logged.</p>
            </div>
        </form>
    </div>
</body>
</html>