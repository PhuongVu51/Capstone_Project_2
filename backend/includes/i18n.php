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
            static $zone_map_vi = [
                'Tools & Equipment Storage' => 'Kho CCDC',
                'Cans & Jars Warehouse' => 'Kho Lon/lọ',
                'Raw Materials Warehouse' => 'Kho NVL',
                'Finished Goods Warehouse' => 'Kho Thành phẩm',
                'Packaging Warehouse' => 'Kho bao bì',
                'Production Plant' => 'NM sản xuất',
                'Liquid Preparation Room' => 'Phòng Pha dịch',
                'Finished Goods Storage' => 'Thành phẩm',
                'Sugar Storage Area' => 'Đường',
                'Cold Storage' => 'Kho Lạnh',
                'Ambient Storage' => 'Kho Thường',
                'Semi-finished Goods Warehouse' => 'Kho Bán thành phẩm',
            ];
            return $zone_map_vi[$name] ?? $name;
        }

        static $zone_map = [
            'Default Zone' => 'Default Zone',
            'Kho CCDC' => 'Tools & Equipment Storage',
            'Kho Lon/lọ' => 'Cans & Jars Warehouse',
            'Kho lon/lo' => 'Cans & Jars Warehouse',
            'Kho NVL' => 'Raw Materials Warehouse',
            'Kho nvl' => 'Raw Materials Warehouse',
            'Kho Thành phẩm' => 'Finished Goods Warehouse',
            'Kho thành phẩm' => 'Finished Goods Warehouse',
            'Kho Nguyên liệu' => 'Raw Materials Warehouse',
            'Kho nguyên liệu' => 'Raw Materials Warehouse',
            'Kho Bán thành phẩm' => 'Semi-finished Goods Warehouse',
            'Kho bán thành phẩm' => 'Semi-finished Goods Warehouse',
            'Kho bao bì' => 'Packaging Warehouse',
            'Kho bao bi' => 'Packaging Warehouse',
            'NM sản xuất' => 'Production Plant',
            'NM san xuat' => 'Production Plant',
            'Phòng Pha dịch' => 'Liquid Preparation Room',
            'Phong Pha dich' => 'Liquid Preparation Room',
            'Thành phẩm' => 'Finished Goods Storage',
            'Thanh pham' => 'Finished Goods Storage',
            'Đường' => 'Sugar Storage Area',
            'Duong' => 'Sugar Storage Area',
            'Kho Lạnh' => 'Cold Storage',
            'Kho lạnh' => 'Cold Storage',
            'Kho Thường' => 'Ambient Storage',
            'Kho thường' => 'Ambient Storage',
        ];

        if (isset($zone_map[$name])) {
            return $zone_map[$name];
        }

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
        
        static $exact_map = [
            'Anh Thọ xe 29' => 'Mr. Tho Truck 29',
            'Dứa sơ chế' => 'Pre-processed Pineapple',
            'Thành phẩm' => 'Finished Goods',
            'Anh Hùng 05' => 'Mr. Hung 05',
            'Anh Thọ xe 30' => 'Mr. Tho Truck 30',
            'Anh Hùng 06' => 'Mr. Hung 06',
            'Tân Việt Anh' => 'Tan Viet Anh',
            'Thanh Bình' => 'Thanh Binh',
            'Anh Thọ xe 33' => 'Mr. Tho Truck 33',
            'Khoái Lạc Phúc' => 'Khoai Lac Phuc',
            'Mua lẻ' => 'Retail Supply',
            'Chị Thủy xe 22' => 'Ms. Thuy Truck 22',
            'Anh Thọ xe 34' => 'Mr. Tho Truck 34',
            'Anh Hùng 07' => 'Mr. Hung 07',
            'Hàng gửi xe' => 'Consigned Cargo',
            'Anh Lực' => 'Mr. Luc',
            'P.A.D' => 'P.A.D',
            'Chị Giang' => 'Ms. Giang',
            'Kiên Vương' => 'Kien Vuong',
            'Anh Hùng 10' => 'Mr. Hung 10',
            'Anh Hùng 11' => 'Mr. Hung 11',
            'Hưng Yên' => 'Hung Yen Supplier',
            'Hồng Dương' => 'Hong Duong',
            'Vinasam' => 'Vinasam',
            'Anh Mười BG' => 'Mr. Muoi BG',
            'Anh Lực HY' => 'Mr. Luc HY',
            'Vĩnh Nam Anh' => 'Vinh Nam Anh',
            'Gửi xe' => 'Consigned Cargo',
            'Trung Quốc' => 'China Import',
            'Anh Chiến' => 'Mr. Chien',
            'HLC' => 'HLC',
            '2 CONT' => '2 Containers',
            'VPHN' => 'VPHN',
            'ANh Tuyến' => 'Mr. Tuyen',
            'Anh Tuyến' => 'Mr. Tuyen',
            'Lọ tái chế' => 'Recycled Jars',
            'Lọ tái chế hàng hủy' => 'Recycled Scrap Jars',
            'Trang điểm gọt' => 'Peeling Preparation',
            'Hoàng Gửi' => 'Hoang Delivery',
            'Hàng TQ' => 'China Cargo',
            'Sơ chế dứa NCC Loan Tam Kỳ' => 'Pineapple Pre-processing (Loan Tam Ky)',
            'Sơ chế' => 'Pre-processing',
            'Nguyên vật liệu - Gia vị' => 'Raw Materials - Seasoning',
            'Nhập kho Hưng Yên chuyển FG1' => 'Hung Yen Import to FG1',
            'Nhập kho nhãn Ngô' => 'Sweetcorn Cans Stock-in',
            'Nhập kho NVL' => 'Raw Materials Stock-in',
            'NVL dây buộc cà' => 'Tomato Binding Wire Materials',
            'Nhâp NVL' => 'Raw Materials Stock-in',
            'Nhập NVL' => 'Raw Materials Stock-in',
            'Nhập NVL Dứa MD2-Dứa đồi FG xe 1' => 'MD2 & Hill Pineapple Import - FG Truck 1',
            'Nhập NVL- Trung Quốc' => 'Raw Materials Import - China',
            'Nhập NVL Dứa MD2-Dứa đồi FG xe 2' => 'MD2 & Hill Pineapple Import - FG Truck 2',
            'Nhập dứa sơ chế' => 'Pre-processed Pineapple Import',
            'Thành phẩm Hưng Yên' => 'Finished Goods (Hung Yen)',
            'NVL Hưng Yên' => 'Raw Materials (Hung Yen)',
            'Nhập NVL- Khoái Lạc Phúc' => 'Raw Materials Import - Khoai Lac Phuc',
            'Nhập NVL- Tân Việt Anh' => 'Raw Materials Import - Tan Viet Anh',
            'Nhập NVL Dứa MD2-Dứa đồi FG xe 3' => 'MD2 & Hill Pineapple Import - FG Truck 3',
            'Nhập NVL Dứa MD2-Dứa đồi FG xe 4' => 'MD2 & Hill Pineapple Import - FG Truck 4',
            'Nhập NVL Dứa MD2-Dứa đồi FG xe 5' => 'MD2 & Hill Pineapple Import - FG Truck 5',
            'Nhập NVL Dứa MD2- xe 95 Chú Tứ' => 'MD2 Pineapple Import - Truck 95 Mr. Tu',
            'Nhập NVL Dứa MD2-Xe 96 Chú Tứ' => 'MD2 Pineapple Import - Truck 96 Mr. Tu',
            'Nhập NVL Dứa Cayene-Xe 97 Chú Tứ' => 'Cayenne Pineapple Import - Truck 97 Mr. Tu',
            'Nhập thành phẩm + sơ chế' => 'Finished Goods + Pre-processing Import',
            'Nhập NVL Anh Khi - Dứa Cayenne Xe số 01' => 'Raw Materials Import Mr. Khi - Cayenne Pineapple Truck 01',
            'Chú Tứ -Xe 100' => 'Mr. Tu - Truck 100',
            'Anh Khi -Xe 03' => 'Mr. Khi - Truck 03',
        ];

        if (isset($exact_map[$name])) {
            return $exact_map[$name];
        }

        $res = $name;

        // Pattern transformations
        $patterns = [
            '/(Nhập|Nhâp)\s+kho\s+NVL/iu' => 'Raw Materials Stock-in',
            '/(Nhập|Nhâp)\s+NVL/iu' => 'Raw Materials Import',
            '/Nhập\s+kho/iu' => 'Warehouse Stock-in',
            '/Nhập\s+dứa\s+sơ\s+chế/iu' => 'Pre-processed Pineapple Import',
            '/Thành\s+phẩm/iu' => 'Finished Goods',
            '/Sơ\s+chế/iu' => 'Pre-processing',
            '/Nguyên\s+vật\s+liệu/iu' => 'Raw Materials',
            '/Dứa\s+sơ\s+chế/iu' => 'Pre-processed Pineapple',
            '/Dứa\s+MD2/iu' => 'MD2 Pineapple',
            '/Dứa\s+Cayenne|Dứa\s+Cayene/iu' => 'Cayenne Pineapple',
            '/Dứa/iu' => 'Pineapple',
            '/Khoái\s+Lạc\s+Phúc|Khái\s+Lạc\s+Phúc/iu' => 'Khoai Lac Phuc',
            '/Tân\s+Việt\s+Anh|Tân\s+Viêt\s+Anh/iu' => 'Tan Viet Anh',
            '/Thanh\s+Bình/iu' => 'Thanh Binh',
            '/Trung\s+Quốc/iu' => 'China Import',
            '/Hàng\s+TQ/iu' => 'China Cargo',
            '/Lọ\s+tái\s+chế\s+hàng\s+hủy/iu' => 'Recycled Scrap Jars',
            '/Lọ\s+tái\s+chế/iu' => 'Recycled Jars',
            '/Trang\s+điểm\s+gọt/iu' => 'Peeling Preparation',
            '/Gửi\s+xe/iu' => 'Consigned Cargo',
            '/Mua\s+lẻ/iu' => 'Retail Supply',
            '/Chú\s+Tứ/iu' => 'Mr. Tu',
            '/Anh\s+Tứ/iu' => 'Mr. Tu',
            '/Anh\s+Thọ/iu' => 'Mr. Tho',
            '/Anh\s+Hùng/iu' => 'Mr. Hung',
            '/Anh\s+Công/iu' => 'Mr. Cong',
            '/Anh\s+Nghị/iu' => 'Mr. Nghi',
            '/ANh\s+Tuyến|Anh\s+Tuyến/iu' => 'Mr. Tuyen',
            '/Anh\s+Đại/iu' => 'Mr. Dai',
            '/ANh\s+Dưỡng|Anh\s+Dưỡng/iu' => 'Mr. Duong',
            '/Anh\s+Lực/iu' => 'Mr. Luc',
            '/Anh\s+Chiến/iu' => 'Mr. Chien',
            '/Anh\s+Khi/iu' => 'Mr. Khi',
            '/Anh\s+Mười/iu' => 'Mr. Muoi',
            '/Chị\s+Thủy/iu' => 'Ms. Thuy',
            '/Chị\s+Ngát/iu' => 'Ms. Ngat',
            '/Chị\s+Giang/iu' => 'Ms. Giang',
            '/\bxe\s+số\s+/iu' => 'Truck ',
            '/\bxe\s+/iu' => 'Truck ',
            '/\bXe\s+số\s+/iu' => 'Truck ',
            '/\bXe\s+/iu' => 'Truck ',
            '/\b-xe\s*/iu' => '- Truck ',
            '/\b-Xe\s*/iu' => '- Truck ',
        ];

        foreach ($patterns as $p => $r) {
            $res = preg_replace($p, $r, $res);
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
            static $shift_map_vi = [
                'Morning' => 'Ca sáng',
                'Afternoon' => 'Ca chiều',
                'Overtime' => 'Ca tăng ca',
                'Night' => 'Ca tối',
            ];
            return $shift_map_vi[$name] ?? $name;
        }
        
        static $shift_map = [
            'Morning' => 'Morning Shift',
            'Afternoon' => 'Afternoon Shift',
            'Overtime' => 'Overtime Shift',
            'Night' => 'Night Shift',
            'Ca sáng' => 'Morning Shift',
            'Ca chiều' => 'Afternoon Shift',
            'Ca tối' => 'Night Shift',
            'Ca tăng ca' => 'Overtime Shift',
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
        
        if (isset($shift_map[$name])) {
            return $shift_map[$name];
        }

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
        $input = trim((string) $str);
        if ($input === '' || $input === 'N/A' || $input === 'None') return 'None';
        if ($input === 'Tất cả') return 'All Defects';
        if (in_array($input, ['No Defects', 'No defect', 'No defects'], true)) return 'No Defects';

        $current_lang = $_SESSION['lang'] ?? 'vi';
        if ($current_lang !== 'en') {
            return $input;
        }

        $r = mb_strtolower($input);
        $rNoAccents = mb_strtolower(remove_accents($input));

        $englishMap = [
            'khong dang ke' => 'No Defects',
            'khong đáng kể' => 'No Defects',
            'dập nát' => 'Minor Damage / Bruised',
            'dap nat' => 'Minor Damage / Bruised',
            'hư hỏng nhẹ' => 'Minor Damage / Bruised',
            'hu hong nhe' => 'Minor Damage / Bruised',
            'mốc / lên men' => 'Mold / Fermented',
            'moc / len men' => 'Mold / Fermented',
            'moc' => 'Mold / Fermented',
            'mốc' => 'Mold / Fermented',
            'sai quy cach' => 'Wrong Specification',
            'sau bệnh' => 'Bacterial Disease',
            'sau benh' => 'Bacterial Disease',
            'sâu bệnh' => 'Bacterial Disease',
            'corrosion' => 'Rust / Corrosion',
            'rust' => 'Rust / Corrosion',
            'damaged' => 'Damaged Goods',
            'bruise' => 'Minor Damage / Bruised',
            'bruised' => 'Minor Damage / Bruised',
        ];

        foreach ($englishMap as $key => $translated) {
            if (str_contains($r, $key) || str_contains($rNoAccents, $key)) {
                return $translated;
            }
        }

        if (str_contains($r, 'khong') || str_contains($rNoAccents, 'khong')) return 'No Defects';
        if (str_contains($r, 'dập') || str_contains($r, 'dap') || str_contains($r, 'nát') || str_contains($r, 'nat') || str_contains($r, 'hư') || str_contains($r, 'hu') || str_contains($r, 'hỏng') || str_contains($r, 'hong')) return 'Minor Damage / Bruised';
        if (str_contains($r, 'mốc') || str_contains($r, 'moc') || str_contains($r, 'lên') || str_contains($r, 'len') || str_contains($r, 'men')) return 'Mold / Fermented';
        if (str_contains($r, 'sai') || str_contains($r, 'quy') || str_contains($r, 'spec') || str_contains($r, 'wrong')) return 'Wrong Specification';
        if (str_contains($r, 'sâu') || str_contains($r, 'sau') || str_contains($r, 'benh') || str_contains($r, 'bệnh')) return 'Bacterial Disease';

        return $input;
    }
}

if (!function_exists('t_reason')) {
    function t_reason($str) {
        return translate_qc_reason($str);
    }
}


