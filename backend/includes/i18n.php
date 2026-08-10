<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (isset($_GET['lang'])) {
    $lang = $_GET['lang'];
    if (in_array($lang, ['en', 'vi'])) {
        $_SESSION['lang'] = $lang;
    }
    $url = strtok($_SERVER["REQUEST_URI"], '?');
    $query = $_GET;
    unset($query['lang']);
    if (count($query) > 0) {
        $url .= '?' . http_build_query($query);
    }
    header("Location: " . $url);
    exit();
}

$current_lang = $_SESSION['lang'] ?? 'en';
$lang_file = __DIR__ . '/../lang/' . $current_lang . '.php';

if (file_exists($lang_file)) {
    global $translations;
    $translations = include $lang_file;
} else {
    $translations = [];
}

if (!function_exists('__')) {
    function __($key, $default = '') {
        global $translations;
        if (isset($translations[$key]) && $translations[$key] !== '') {
            return $translations[$key];
        }
        return $default !== '' ? $default : $key;
    }
}

if (!function_exists('remove_accents')) {
    function remove_accents($str) {
        if (!$str || !is_string($str)) return $str;
        $accents = [
            'a' => ['à', 'á', 'ạ', 'ả', 'ã', 'â', 'ầ', 'ấ', 'ậ', 'ẩ', 'ẫ', 'ă', 'ằ', 'ắ', 'ặ', 'ẳ', 'ẵ'],
            'e' => ['è', 'é', 'ẹ', 'ẻ', 'ẽ', 'ê', 'ề', 'ế', 'ệ', 'ể', 'ễ'],
            'i' => ['ì', 'í', 'ị', 'ỉ', 'ĩ'],
            'o' => ['ò', 'ó', 'ọ', 'ỏ', 'õ', 'ô', 'ồ', 'ố', 'ộ', 'ổ', 'ỗ', 'ơ', 'ờ', 'ớ', 'ợ', 'ở', 'ỡ'],
            'u' => ['ù', 'ú', 'ụ', 'ủ', 'ũ', 'ư', 'ừ', 'ứ', 'ự', 'ử', 'ữ'],
            'y' => ['ỳ', 'ý', 'ỵ', 'ỷ', 'ỹ'],
            'd' => ['đ'],
            'A' => ['À', 'Á', 'Ạ', 'Ả', 'Ã', 'Â', 'Ầ', 'Ấ', 'Ậ', 'Ẩ', 'Ẫ', 'Ă', 'Ằ', 'Ắ', 'Ặ', 'Ẳ', 'Ẵ'],
            'E' => ['È', 'É', 'Ẹ', 'Ẻ', 'Ẽ', 'Ê', 'Ề', 'Ế', 'Ệ', 'Ể', 'Ễ'],
            'I' => ['Ì', 'Í', 'Ị', 'Ỉ', 'Ĩ'],
            'O' => ['Ò', 'Ó', 'Ọ', 'Ỏ', 'Õ', 'Ô', 'Ồ', 'Ố', 'Ộ', 'Ổ', 'Ỗ', 'Ơ', 'Ờ', 'Ớ', 'Ợ', 'Ở', 'Ỡ'],
            'U' => ['Ù', 'Ú', 'Ụ', 'Ủ', 'Ũ', 'Ư', 'Ừ', 'Ứ', 'Ự', 'Ử', 'Ữ'],
            'Y' => ['Ỳ', 'Ý', 'Ỵ', 'Ỷ', 'Ỹ'],
            'D' => ['Đ']
        ];
        foreach ($accents as $non_accent => $accent_list) {
            $str = str_replace($accent_list, $non_accent, $str);
        }
        return $str;
    }
}

if (!function_exists('translate_product_name')) {
    function translate_product_name($name) {
        if (!$name) return '';
        $current_lang = $_SESSION['lang'] ?? 'en';
        if ($current_lang === 'vi') {
            return $name;
        }
        
        static $product_map = [
            'Dưa bao tử' => 'Baby Gherkins',
            'Dua bao tu' => 'Baby Gherkins',
            'Dưa chuột' => 'Cucumbers',
            'Dua chuot' => 'Cucumbers',
            'Dua thai lat' => 'Sliced Thai Cucumbers',
            'Dưa thái lát' => 'Sliced Thai Cucumbers',
            'Dua thai' => 'Sliced Cucumbers',
            'Dưa thái' => 'Sliced Cucumbers',
            'Dứa khối' => 'Pineapple Chunks',
            'Dua khoi' => 'Pineapple Chunks',
            'Dứa sơ chế' => 'Pre-processed Pineapple',
            'Dua so che' => 'Pre-processed Pineapple',
            'Dứa khoanh' => 'Pineapple Slices',
            'Dua khoanh' => 'Pineapple Slices',
            'Dứa mảnh' => 'Pineapple Tidbits',
            'Dua manh' => 'Pineapple Tidbits',
            'Dứa hộp' => 'Canned Pineapple',
            'Dua hop' => 'Canned Pineapple',
            'Dứa' => 'Pineapple',
            'Dua' => 'Cucumber',
            'Sốt cà chua' => 'Tomato Sauce',
            'Sot ca chua' => 'Tomato Sauce',
            'Cà chua' => 'Tomato',
            'Ca chua' => 'Tomato',
            'Ngô ngọt' => 'Sweet Corn',
            'Ngo ngot' => 'Sweet Corn',
            'Vải thiều' => 'Lychee',
            'Vai thieu' => 'Lychee',
            'Vải' => 'Lychee',
            'Vai' => 'Lychee',
            'Xoài' => 'Mango',
            'Xoai' => 'Mango',
            'mieng' => 'Slices',
            'miếng' => 'Slices',
            'kho mo' => 'Dry Open',
            'khô mở' => 'Dry Open',
            'duong thap' => 'Low Sugar',
            'đường thấp' => 'Low Sugar',
            'TL' => 'Net Wt.',
            'de' => 'Base',
            'đế' => 'Base',
            'Bàn gọt dứa' => 'Pineapple Peeling Table',
            'Bàn inox chân cao' => 'High Stainless Steel Table',
            'Băng dính trắng' => 'White Adhesive Tape',
            'Băng dính vàng' => 'Yellow Adhesive Tape',
            'Băng tải inox' => 'Stainless Steel Conveyor',
            'Bình tam giác' => 'Erlenmeyer Flask',
            'Bóng đèn bắt côn trùng' => 'Insect Killer Light Bulb',
            'Lọ tái chế' => 'Recycled Jars',
            'Lọ' => 'Jar',
            'lọ' => 'jar',
            'hàng lỗi' => 'defective',
            'hang loi' => 'defective',
        ];
        
        $res = $name;
        foreach ($product_map as $vi => $en) {
            if (mb_stripos($res, $vi) !== false) {
                $res = str_ireplace($vi, $en, $res);
            }
        }
        
        return remove_accents($res);
    }
}

if (!function_exists('t_product')) {
    function t_product($name) {
        return translate_product_name($name);
    }
}

if (!function_exists('translate_zone_name')) {
    function translate_zone_name($name) {
        if (!$name) return '';
        $current_lang = $_SESSION['lang'] ?? 'en';
        if ($current_lang === 'vi') {
            return $name;
        }
        static $zone_map = [
            'Kho Thành phẩm' => 'Finished Goods Warehouse',
            'Kho thành phẩm' => 'Finished Goods Warehouse',
            'Kho Nguyên liệu' => 'Raw Material Warehouse',
            'Kho nguyên liệu' => 'Raw Material Warehouse',
            'Kho Bán thành phẩm' => 'Semi-finished Goods Warehouse',
            'Kho Lạnh' => 'Cold Storage',
            'Kho Thường' => 'Ambient Storage',
            'Default Zone' => 'Default Zone',
        ];
        $res = $name;
        foreach ($zone_map as $vi => $en) {
            if (mb_stripos($res, $vi) !== false) {
                $res = str_ireplace($vi, $en, $res);
            }
        }
        return remove_accents($res);
    }
}

if (!function_exists('t_zone')) {
    function t_zone($name) {
        return translate_zone_name($name);
    }
}

if (!function_exists('translate_supplier_name')) {
    function translate_supplier_name($name) {
        if (!$name) return '';
        $current_lang = $_SESSION['lang'] ?? 'en';
        if ($current_lang === 'vi') {
            return $name;
        }
        
        static $supplier_map = [
            'Anh Thọ xe' => 'Mr. Tho Truck',
            'Anh Hùng xe' => 'Mr. Hung Truck',
            'Anh Hùng' => 'Mr. Hung',
            'Anh Công xe' => 'Mr. Cong Truck',
            'Anh Công' => 'Mr. Cong',
            'Anh Nghị xe' => 'Mr. Nghi Truck',
            'Anh Nghị' => 'Mr. Nghi',
            'Anh Tuyến xe' => 'Mr. Tuyen Truck',
            'Anh Tuyến' => 'Mr. Tuyen',
            'Anh Đại xe' => 'Mr. Dai Truck',
            'Anh Dưỡng xe' => 'Mr. Duong Truck',
            'Chị Thủy xe' => 'Ms. Thuy Truck',
            'Chị Thủy' => 'Ms. Thuy',
            'Chị Ngát xe' => 'Ms. Ngat Truck',
            'Chị Ngát Xe' => 'Ms. Ngat Truck',
            'Chị Ngát' => 'Ms. Ngat',
            'Chị Giang' => 'Ms. Giang',
            'Khoái Lạc Phúc xe' => 'Khoai Lac Phuc Truck',
            'Khái Lạc Phúc xe' => 'Khoai Lac Phuc Truck',
            'Khoái Lạc Phúc' => 'Khoai Lac Phuc',
            'Thành phẩm' => 'Finished Goods',
            'Dứa sơ chế' => 'Pre-processed Pineapple',
            'Thanh Bình xe' => 'Thanh Binh Truck',
            'Thanh Bình' => 'Thanh Binh',
            'Mua lẻ' => 'Retail Supply',
            'Hàng gửi xe' => 'Consigned Cargo',
            'Gửi xe' => 'Consigned Cargo',
            'Anh Lực HY' => 'Mr. Luc HY',
            'Anh Lực' => 'Mr. Luc',
            'Anh Mười BG' => 'Mr. Muoi BG',
            'Vĩnh Nam Anh' => 'Vinh Nam Anh',
            'Trung Quốc' => 'China Import',
            'Hàng TQ' => 'China Cargo',
            'Anh Chiến' => 'Mr. Chien',
            '2 CONT' => '2 Containers',
            'Lọ tái chế hàng hủy' => 'Recycled Scrap Jars',
            'Lọ tái chế' => 'Recycled Jars',
            'Trang điểm gọt' => 'Peeling Prep',
            'Hoàng Gửi' => 'Hoang Delivery',
            'Anh Tứ xe' => 'Mr. Tu Truck',
            'Tân Việt Anh' => 'Tan Viet Anh',
            'Tân Viêt Anh' => 'Tan Viet Anh',
            'Hưng Yên' => 'Hung Yen Supplier',
            'Hồng Dương' => 'Hong Duong',
            'Vinasam' => 'Vinasam',
            'P.A.D' => 'P.A.D Supplier',
            'Kiên Vương' => 'Kien Vuong',
        ];
        
        $res = $name;
        foreach ($supplier_map as $vi => $en) {
            if (mb_stripos($res, $vi) !== false) {
                $res = str_ireplace($vi, $en, $res);
            }
        }
        
        return remove_accents($res);
    }
}

if (!function_exists('t_supplier')) {
    function t_supplier($name) {
        return translate_supplier_name($name);
    }
}

if (!function_exists('translate_shift_name')) {
    function translate_shift_name($name) {
        if (!$name) return '';
        $current_lang = $_SESSION['lang'] ?? 'en';
        if ($current_lang === 'vi') {
            return $name;
        }
        
        static $shift_map = [
            'Ca sáng' => 'Morning Shift',
            'Ca chiều' => 'Afternoon Shift',
            'Ca tối' => 'Night Shift',
            'Ca kíp 1' => 'Shift 1',
            'Ca kíp 2' => 'Shift 2',
            'Ca kíp 3' => 'Shift 3',
            'Ca 1' => 'Shift 1',
            'Ca 2' => 'Shift 2',
            'Ca 3' => 'Shift 3',
            'Ca Alpha' => 'Shift Alpha',
            'Ca Beta' => 'Shift Beta',
            'Ca Gamma' => 'Shift Gamma',
            'Ca Hanh' => 'Shift Hanh',
        ];
        
        $res = $name;
        foreach ($shift_map as $vi => $en) {
            if (mb_stripos($res, $vi) !== false) {
                $res = str_ireplace($vi, $en, $res);
            }
        }
        
        return remove_accents($res);
    }
}

if (!function_exists('translate_qc_reason')) {
    function translate_qc_reason($str) {
        if (!$str || $str === 'N/A' || $str === 'None') return 'None';
        if ($str === 'Tất cả') return 'All Defects';
        
        $r = mb_strtolower($str);
        if (str_contains($r, 'han') || str_contains($r, 'gỉ') || str_contains($r, 'gi') || str_contains($r, 'corrosion')) {
            if (str_contains($r, 'ngoai') || str_contains($r, 'ngoài')) return 'External Rusty Cans (Internal Unverified)';
            if (str_contains($r, 'hong') || str_contains($r, 'hỏng')) return 'Rust & Damaged Cans';
            return 'Rust / Corrosion';
        }
        if (str_contains($r, '10') || str_contains($r, 'mop') || str_contains($r, 'móp')) {
            if (str_contains($r, '10')) return 'Dented Cans (10 Units)';
            if (str_contains($r, 'meo') || str_contains($r, 'méo')) return 'Dented & Deformed Cans';
            return 'Dented Cans';
        }
        if (str_contains($r, 'meo') || str_contains($r, 'méo')) return 'Deformed Cans';
        if (str_contains($r, 'cut') || str_contains($r, 'cắt')) return 'Miscut Specification Batch';
        if (str_contains($r, 'tibit')) return 'Tibit Defect';
        if (str_contains($r, '27') || str_contains($r, 'thung') || str_contains($r, 'thùng')) return '27 Cartons + 7 Cans (Severe Rust)';
        if (str_contains($r, 'hong yen') || str_contains($r, 'hồng yên') || str_contains($r, 'hung yen') || str_contains($r, 'hưng yên')) return 'Hong Yen Returned Batch';
        if (str_contains($r, 'tra') || str_contains($r, 'kh')) return 'Customer Return (Canceled Label)';
        if (str_contains($r, 'den') || str_contains($r, 'đen')) return 'Black Labeled Batch (VP)';
        if (str_contains($r, 'trang') || str_contains($r, 'trắng')) return 'White Labeled Batch (VP)';
        if (str_contains($r, 'damaged')) return 'Damaged Goods Seed Record';
        
        return remove_accents($str);
    }
}

if (!function_exists('t_reason')) {
    function t_reason($str) {
        return translate_qc_reason($str);
    }
}


