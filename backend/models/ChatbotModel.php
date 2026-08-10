<?php
// Đường dẫn: backend/models/ChatbotModel.php

require_once __DIR__ . '/../core/BaseModel.php';

class ChatbotModel extends BaseModel
{
    private $geminiApiKey;
    private $groqApiKey;
    private $openRouterApiKey;
    private $provider = 'gemini'; // 'groq', 'gemini', 'openrouter'
    private $workingModel = null;
    private $workingApiVersion = 'v1beta';
    private $lastError = null;

    public function __construct($pdoInstance = null)
    {
        parent::__construct($pdoInstance);
        
        // 1. Nạp các API Key từ $_ENV hoặc getenv hoặc file .env
        $this->geminiApiKey = trim($_ENV['GEMINI_API_KEY'] ?? getenv('GEMINI_API_KEY') ?: '');
        $this->groqApiKey = trim($_ENV['GROQ_API_KEY'] ?? getenv('GROQ_API_KEY') ?: '');
        $this->openRouterApiKey = trim($_ENV['OPENROUTER_API_KEY'] ?? getenv('OPENROUTER_API_KEY') ?: '');

        // Fallback đọc trực tiếp file .env
        $env_path = __DIR__ . '/../../.env';
        if (file_exists($env_path)) {
            $env_vars = @parse_ini_file($env_path);
            if (is_array($env_vars)) {
                if (empty($this->geminiApiKey) && !empty($env_vars['GEMINI_API_KEY'])) {
                    $this->geminiApiKey = trim($env_vars['GEMINI_API_KEY']);
                }
                if (empty($this->groqApiKey) && !empty($env_vars['GROQ_API_KEY'])) {
                    $this->groqApiKey = trim($env_vars['GROQ_API_KEY']);
                }
                if (empty($this->openRouterApiKey) && !empty($env_vars['OPENROUTER_API_KEY'])) {
                    $this->openRouterApiKey = trim($env_vars['OPENROUTER_API_KEY']);
                }
            }
        }

        // Tự động xác định Provider ưu tiên
        if (!empty($this->groqApiKey)) {
            $this->provider = 'groq';
        } elseif (!empty($this->openRouterApiKey)) {
            $this->provider = 'openrouter';
        } else {
            $this->provider = 'gemini';
        }
    }

    /**
     * Xử lý câu hỏi của người dùng và trả về kết quả
     */
    public function processQuery($userQuery, $userRole = 'Warehouse_Staff', $lang = 'vi')
    {
        $userQuery = trim($userQuery);
        if (empty($userQuery)) {
            return [
                'in_scope' => false,
                'answer' => ($lang === 'en') 
                    ? 'Please enter your question.' 
                    : 'Vui lòng nhập câu hỏi của bạn.'
            ];
        }

        // Kiểm tra xem đã dán ít nhất 1 API key chưa
        if (empty($this->geminiApiKey) && empty($this->groqApiKey) && empty($this->openRouterApiKey)) {
            return [
                'in_scope' => false,
                'answer' => ($lang === 'en')
                    ? 'AI API Key is missing. Please add GROQ_API_KEY or GEMINI_API_KEY in .env file.'
                    : 'Chưa cấu hình API Key. Vui lòng thêm GROQ_API_KEY hoặc GEMINI_API_KEY vào file .env.'
            ];
        }

        // Bước 1: Gọi AI để xác định in_scope và sinh câu lệnh SQL SELECT
        $intentResult = $this->determineIntentAndSql($userQuery, $userRole);

        // Nếu API lỗi -> Thông báo lỗi rõ ràng
        if (isset($intentResult['api_error']) && $intentResult['api_error']) {
            return [
                'in_scope' => false,
                'answer' => ($lang === 'en')
                    ? 'AI Service Error: ' . ($this->lastError ?? 'Connection failed.')
                    : 'Lỗi dịch vụ AI: ' . ($this->lastError ?? 'Kết nối thất bại.')
            ];
        }

        if (!$intentResult['in_scope'] || empty($intentResult['sql'])) {
            return [
                'in_scope' => false,
                'answer' => ($lang === 'en')
                    ? 'Sorry, I only support questions related to warehouse/production/QC data in the F&G Food system.'
                    : 'Xin lỗi, tôi chỉ hỗ trợ các câu hỏi liên quan đến dữ liệu kho/sản xuất/QC trong hệ thống F&G Food.'
            ];
        }

        // Bước 2: Thẩm định an toàn SQL (SQL Guardrails)
        $sql = $intentResult['sql'];
        $validation = $this->validateSql($sql, $userRole);
        if (!$validation['valid']) {
            return [
                'in_scope' => false,
                'answer' => ($lang === 'en')
                    ? 'Sorry, I only support questions related to warehouse/production/QC data in the F&G Food system.'
                    : 'Xin lỗi, tôi chỉ hỗ trợ các câu hỏi liên quan đến dữ liệu kho/sản xuất/QC trong hệ thống F&G Food.'
            ];
        }

        $safeSql = $validation['sql'];

        // Bước 3: Thực thi SQL an toàn trong DB
        try {
            $stmt = $this->pdo->prepare($safeSql);
            $stmt->execute();
            $dbResults = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            // Lỗi SQL execution -> Fallback an toàn
            return [
                'in_scope' => false,
                'answer' => ($lang === 'en')
                    ? 'Database query execution failed. Please rephrase your question.'
                    : 'Truy vấn dữ liệu không thành công. Vui lòng diễn đạt lại câu hỏi.'
            ];
        }

        // Bước 4: Tổng hợp câu trả lời bằng ngôn ngữ tự nhiên
        $naturalAnswer = $this->generateNaturalAnswer($userQuery, $safeSql, $dbResults, $lang);

        return [
            'in_scope' => true,
            'answer' => $naturalAnswer,
            'sql' => $safeSql
        ];
    }

    /**
     * Gọi AI Provider (Groq / Gemini / OpenRouter) để phân loại Scope & Tạo SQL
     */
    private function determineIntentAndSql($userQuery, $userRole)
    {
        $systemPrompt = $this->buildSystemPromptForSql($userRole);

        $response = $this->callAiApi($systemPrompt, $userQuery, true);
        if (!$response) {
            return ['in_scope' => false, 'sql' => null, 'api_error' => true];
        }

        try {
            // Xóa markdown codeblock (```json ... ```)
            $cleanJson = preg_replace('/^```(?:json)?\s*|\s*```$/i', '', trim($response));
            
            if (preg_match('/\{.*\}/s', $cleanJson, $matches)) {
                $cleanJson = $matches[0];
            }

            $data = json_decode($cleanJson, true);

            if (is_array($data) && isset($data['in_scope'])) {
                return [
                    'in_scope' => (bool)$data['in_scope'],
                    'sql' => !empty($data['sql']) ? trim($data['sql']) : null
                ];
            }
        } catch (Exception $e) {
            // Fallback
        }

        return ['in_scope' => false, 'sql' => null];
    }

    /**
     * Tổng hợp dữ liệu SQL thành câu trả lời tự nhiên
     */
    private function generateNaturalAnswer($userQuery, $sql, $dbResults, $lang)
    {
        $langInstruction = ($lang === 'en') ? 'Respond in English.' : 'Trả lời bằng Tiếng Việt.';
        $resultsJson = json_encode($dbResults, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        
        if (strlen($resultsJson) > 4000) {
            $resultsJson = substr($resultsJson, 0, 4000) . '... (truncated)';
        }

        $systemPrompt = "You are F&G Food's intelligent operational assistant.
Your job is to read the database query results and provide a clear, helpful, concise answer to the user's query.
Rules:
1. $langInstruction
2. Base your response STRICTLY on the provided data. Do not make up facts or extrapolate beyond the numbers provided.
3. Highlight key metrics (e.g. quantities in kg, dates, status, pass rates).
4. If the data is empty (no rows found / 0 kg), inform the user clearly that no matching operational records or stock were found in the system.
5. Do NOT include raw SQL in your main response.";

        $userPrompt = "User Query: \"$userQuery\"\n\nSQL Executed: `$sql` \n\nQuery Results:\n$resultsJson";

        $response = $this->callAiApi($systemPrompt, $userPrompt, false);
        if ($response) {
            return trim($response);
        }

        return $this->formatFallbackTable($dbResults, $lang);
    }

    /**
     * Lớp điều hướng chung tới Provider AI (Groq -> OpenRouter -> Gemini)
     */
    private function callAiApi($systemPrompt, $userPrompt, $isJsonMode = false)
    {
        // 1. Nếu có Groq Key -> Dùng Groq API (Siêu nhanh, 100% Free Llama-3.3-70b)
        if (!empty($this->groqApiKey)) {
            $res = $this->callGroqApi($systemPrompt, $userPrompt, $isJsonMode);
            if ($res !== null) return $res;
        }

        // 2. Nếu có OpenRouter Key -> Dùng OpenRouter Free AI Models
        if (!empty($this->openRouterApiKey)) {
            $res = $this->callOpenRouterApi($systemPrompt, $userPrompt, $isJsonMode);
            if ($res !== null) return $res;
        }

        // 3. Sử dụng Gemini API
        if (!empty($this->geminiApiKey)) {
            return $this->callGeminiApi($systemPrompt, $userPrompt, $isJsonMode);
        }

        return null;
    }

    /**
     * Gọi Groq API (Llama 3.3 70B - Tốc độ cực nhanh, Miễn phí 100%)
     */
    private function callGroqApi($systemPrompt, $userPrompt, $isJsonMode = false)
    {
        $url = 'https://api.groq.com/openai/v1/chat/completions';

        $payload = [
            'model' => 'llama-3.3-70b-versatile',
            'messages' => [
                ['role' => 'system', 'content' => $systemPrompt],
                ['role' => 'user', 'content' => $userPrompt]
            ],
            'temperature' => 0.2
        ];

        if ($isJsonMode) {
            $payload['response_format'] = ['type' => 'json_object'];
        }

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $this->groqApiKey
        ]);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
        curl_setopt($ch, CURLOPT_TIMEOUT, 15);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlErr = curl_error($ch);
        curl_close($ch);

        if ($curlErr) {
            $this->lastError = "Groq cURL Error: " . $curlErr;
            return null;
        }

        if ($httpCode === 200 && $response) {
            $data = json_decode($response, true);
            return $data['choices'][0]['message']['content'] ?? null;
        }

        $errData = json_decode($response, true);
        $errMsg = $errData['error']['message'] ?? "HTTP Status $httpCode";
        $this->lastError = "Groq API Error: " . $errMsg;
        return null;
    }

    /**
     * Gọi OpenRouter API (Miễn phí 100% với các dòng Model Free)
     */
    private function callOpenRouterApi($systemPrompt, $userPrompt, $isJsonMode = false)
    {
        $url = 'https://openrouter.ai/api/v1/chat/completions';

        $payload = [
            'model' => 'meta-llama/llama-3.3-70b-instruct:free',
            'messages' => [
                ['role' => 'system', 'content' => $systemPrompt],
                ['role' => 'user', 'content' => $userPrompt]
            ],
            'temperature' => 0.2
        ];

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $this->openRouterApiKey
        ]);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
        curl_setopt($ch, CURLOPT_TIMEOUT, 15);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlErr = curl_error($ch);
        curl_close($ch);

        if ($httpCode === 200 && $response) {
            $data = json_decode($response, true);
            return $data['choices'][0]['message']['content'] ?? null;
        }

        $errData = json_decode($response, true);
        $errMsg = $errData['error']['message'] ?? "HTTP Status $httpCode";
        $this->lastError = "OpenRouter API Error: " . $errMsg;
        return null;
    }

    /**
     * Gọi Gemini API (Google)
     */
    private function callGeminiApi($systemPrompt, $userPrompt, $isJsonMode = false)
    {
        $models = ['gemini-2.0-flash', 'gemini-1.5-flash-8b', 'gemini-2.0-flash-lite', 'gemini-1.5-flash'];
        $apiVersions = ['v1beta', 'v1'];

        $payload = [
            'system_instruction' => [
                'parts' => [['text' => $systemPrompt]]
            ],
            'contents' => [
                ['parts' => [['text' => $userPrompt]]]
            ],
            'generationConfig' => [
                'temperature' => 0.2
            ]
        ];

        foreach ($apiVersions as $ver) {
            foreach ($models as $model) {
                $url = "https://generativelanguage.googleapis.com/{$ver}/models/{$model}:generateContent?key=" . urlencode($this->geminiApiKey);

                $ch = curl_init($url);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_POST, true);
                curl_setopt($ch, CURLOPT_HTTPHEADER, [
                    'Content-Type: application/json',
                    'x-goog-api-key: ' . $this->geminiApiKey
                ]);
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
                curl_setopt($ch, CURLOPT_TIMEOUT, 15);
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

                $response = curl_exec($ch);
                $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                $curlErr = curl_error($ch);
                curl_close($ch);

                if ($httpCode === 200 && $response) {
                    $data = json_decode($response, true);
                    return $data['candidates'][0]['content']['parts'][0]['text'] ?? null;
                }

                $errData = json_decode($response, true);
                $errMsg = $errData['error']['message'] ?? "HTTP Status $httpCode";

                if (preg_match('/API_KEY_INVALID|API key not valid/i', $errMsg)) {
                    $this->lastError = "Gemini API Key không hợp lệ: " . $errMsg;
                    return null;
                }
            }
        }

        $this->lastError = "Gemini API Error: " . ($errMsg ?? 'Chưa bật Generative Language API trên Google Cloud Console');
        return null;
    }

    /**
     * Fallback hiển thị dạng danh sách nếu không thể tổng hợp AI
     */
    private function formatFallbackTable($dbResults, $lang)
    {
        if (empty($dbResults)) {
            return ($lang === 'en') ? 'No data records found.' : 'Không tìm thấy dữ liệu phù hợp trong hệ thống.';
        }

        $count = count($dbResults);
        $summary = ($lang === 'en') 
            ? "Found $count records:\n" 
            : "Tìm thấy $count bản ghi phù hợp:\n";

        $lines = [];
        foreach (array_slice($dbResults, 0, 5) as $i => $row) {
            $rowParts = [];
            foreach ($row as $k => $v) {
                $rowParts[] = "$k: $v";
            }
            $lines[] = ($i + 1) . ". " . implode(", ", $rowParts);
        }

        return $summary . implode("\n", $lines);
    }

    /**
     * Thẩm định SQL an toàn (SQL Safety Guardrails)
     */
    private function validateSql($sql, $userRole)
    {
        $sqlClean = trim($sql);
        $sqlClean = rtrim($sqlClean, ';');

        // 1. BẮT BUỘC chỉ bắt đầu bằng SELECT (case-insensitive)
        if (!preg_match('/^SELECT\s+/i', $sqlClean)) {
            return ['valid' => false, 'error' => 'Only SELECT queries allowed'];
        }

        // 2. CHẶN các từ khóa ghi / sửa / xóa / nguy hại
        $dangerousKeywords = [
            'INSERT', 'UPDATE', 'DELETE', 'DROP', 'ALTER', 'TRUNCATE', 
            'CREATE', 'REPLACE', 'EXEC', 'EXECUTE', 'GRANT', 'REVOKE', 
            'LOCK', 'UNLOCK', 'CALL', 'DECLARE', 'INTO OUTFILE', 'INTO DUMPFILE',
            'SYSTEM_AUDIT_LOGS', 'INFORMATION_SCHEMA', 'MYSQL.'
        ];

        foreach ($dangerousKeywords as $kw) {
            if (preg_match('/\b' . preg_quote($kw, '/') . '\b/i', $sqlClean)) {
                return ['valid' => false, 'error' => "Dangerous keyword detected: $kw"];
            }
        }

        // 3. Bảo vệ bảng USERS: Không cho phép SELECT các cột nhạy cảm như USR_password_hash
        if (preg_match('/\bUSERS\b/i', $sqlClean)) {
            if (preg_match('/USR_password_hash|USR_username/i', $sqlClean) || !preg_match('/USR_full_name|USR_user_id/i', $sqlClean)) {
                return ['valid' => false, 'error' => 'Unauthorized access to USERS table columns'];
            }
        }

        // 4. Giới hạn số dòng kết quả (LIMIT 50) nếu chưa có LIMIT
        if (!preg_match('/\bLIMIT\s+\d+/i', $sqlClean)) {
            $sqlClean .= ' LIMIT 50';
        }

        return ['valid' => true, 'sql' => $sqlClean];
    }

    /**
     * Xây dựng System Prompt cho bước phân loại Intent & sinh SQL
     */
    private function buildSystemPromptForSql($userRole)
    {
        $rolePermissions = [
            'Warehouse_Staff' => ['BATCHES', 'PRODUCTS', 'SUPPLIERS', 'STORAGE_ZONES', 'STOCK_MOVEMENTS', 'MATERIAL_REQUESTS', 'SHIFTS'],
            'QC' => ['QC_INSPECTIONS', 'BATCHES', 'PRODUCTS', 'SUPPLIERS', 'STORAGE_ZONES', 'SHIFTS'],
            'Production_Manager' => ['FINISHED_GOODS', 'MATERIAL_ALLOCATIONS', 'BATCHES', 'PRODUCTS', 'SUPPLIERS', 'STORAGE_ZONES', 'SHIFTS', 'QC_INSPECTIONS', 'MATERIAL_REQUESTS'],
            'Director' => ['FINISHED_GOODS', 'MATERIAL_ALLOCATIONS', 'BATCHES', 'PRODUCTS', 'SUPPLIERS', 'STORAGE_ZONES', 'SHIFTS', 'QC_INSPECTIONS', 'MATERIAL_REQUESTS', 'STOCK_MOVEMENTS']
        ];

        $allowedTables = $rolePermissions[$userRole] ?? $rolePermissions['Warehouse_Staff'];
        $allowedTablesStr = implode(', ', $allowedTables);

        return "You are an AI SQL Query Generator for the F&G Food Inventory & Production System.
Your job is to check if the user question is IN SCOPE for the system database, and if so, generate a safe MySQL SELECT query.

DATABASE SCHEMA:
- BATCHES (BCH_batch_id VARCHAR(50) PK, BCH_product_id INT, BCH_supplier_id INT, BCH_shift_id INT, BCH_zone_id INT, BCH_received_date DATETIME, BCH_expiry_date DATETIME, BCH_priority ENUM('LOW','NORMAL','HIGH','CRITICAL'), BCH_initial_volume_kg DECIMAL, BCH_available_stock_kg DECIMAL, BCH_current_stage VARCHAR(50), BCH_health_status ENUM('Good','Warning','Critical'))
- PRODUCTS (PRD_product_id INT PK, PRD_product_name VARCHAR(255), PRD_product_name_en VARCHAR(255), PRD_material_grade VARCHAR(50), PRD_unit_price DECIMAL, PRD_expected_yield DECIMAL, PRD_shelf_life_days INT)
- SUPPLIERS (SUP_supplier_id INT PK, SUP_supplier_name VARCHAR(255), SUP_supplier_name_en VARCHAR(255), SUP_contact_info VARCHAR(255), SUP_origin_facility VARCHAR(100))
- STORAGE_ZONES (STZ_zone_id INT PK, STZ_zone_name VARCHAR(100), STZ_zone_name_en VARCHAR(100), STZ_max_capacity_kg DECIMAL, STZ_current_load_kg DECIMAL, STZ_current_temp_c DECIMAL, STZ_current_humidity_pct DECIMAL)
- SHIFTS (SHF_shift_id INT PK, SHF_shift_date DATE, SHF_shift_type ENUM('Morning','Afternoon','Overtime'), SHF_worker_count INT, SHF_status ENUM('Open','Closed'))
- QC_INSPECTIONS (QCI_inspection_id INT PK, QCI_batch_id VARCHAR(50), QCI_user_id INT, QCI_rotten_weight_kg DECIMAL, QCI_natural_loss_weight_kg DECIMAL, QCI_usable_weight_kg DECIMAL, QCI_actual_yield_pct DECIMAL, QCI_rejection_reason VARCHAR(255), QCI_rejection_reason_en VARCHAR(255), QCI_inspector_comments TEXT, QCI_destination VARCHAR(100))
- FINISHED_GOODS (FGD_fg_id INT PK, FGD_batch_id VARCHAR(50), FGD_shift_id INT, FGD_produced_date DATE, FGD_total_cans INT, FGD_kg_per_can DECIMAL, FGD_actual_yield_rate DECIMAL, FGD_quarantine_end_date DATE, FGD_status ENUM('Quarantine','Ready_To_Export','Exported'))
- MATERIAL_ALLOCATIONS (ALC_allocation_id INT PK, ALC_batch_id VARCHAR(50), ALC_user_id INT, ALC_allocated_quantity_kg DECIMAL, ALC_production_line VARCHAR(100), ALC_allocation_time DATETIME)
- STOCK_MOVEMENTS (STM_movement_id INT PK, STM_reference_code VARCHAR(100), STM_batch_id VARCHAR(50), STM_shift_id INT, STM_movement_type ENUM('IN','OUT','ADJUSTMENT'), STM_quantity_kg DECIMAL, STM_timestamp DATETIME, STM_user_id INT)
- MATERIAL_REQUESTS (REQ_id INT PK, REQ_material_id VARCHAR(50), REQ_quantity DECIMAL, REQ_needed_date DATE, REQ_priority VARCHAR(20), REQ_notes TEXT, REQ_status VARCHAR(20), created_at TIMESTAMP)

ROLE & ACCESS PERMISSIONS:
Current User Role: $userRole
Allowed Tables for this Role: $allowedTablesStr

SCOPE CLASSIFICATION RULES:
1. ANY question asking about operational data, stock levels, inventory quantities, raw materials, fruit products (e.g. sầu riêng, dứa, vải, xoài, etc.), batches, QC defects, supplier performance, production output, or shifts IS IN SCOPE (set in_scope: true).
   - If searching for a fruit or product name in PRODUCTS or BATCHES, use `PRD_product_name LIKE '%keyword%'` or `PRD_material_grade LIKE '%keyword%'`.
   - If asking for total inventory of an item, query `SUM(b.BCH_available_stock_kg)` from `BATCHES b JOIN PRODUCTS p ON b.BCH_product_id = p.PRD_product_id`.
2. ONLY set in_scope=false IF:
   - The question is completely unrelated to factory operations or agricultural inventory (e.g., capitals of countries, weather, general news, sports, coding assistance, personal chat).
   - The user asks to INSERT, UPDATE, DELETE, or DROP data.
3. OUTPUT FORMAT:
   Return ONLY a valid JSON object with EXACTLY two fields:
   {
     \"in_scope\": true or false,
     \"sql\": \"SELECT ... LIMIT 50\" (or null if in_scope is false)
   }
4. SQL RULES:
   - Output valid MySQL standard query starting with SELECT.
   - Use table aliases and proper JOIN syntax when querying multiple tables.
   - Only query from allowed tables: $allowedTablesStr.
   - Keep queries optimized and include a LIMIT (maximum 50).
   - Do NOT include markdown code blocks in the output, return raw JSON string only.";
    }
}
?>
