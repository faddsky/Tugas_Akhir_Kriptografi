<?php
// crypto_utils.php
// ✅ Versi aman dan kompatibel dengan config.php (tanpa redeclare error)
// Pastikan file config.php sudah di-load sebelumnya
require_once 'config.php';

/*
|--------------------------------------------------------------------------
| CRYPTO UTILS (Versi Aman)
|--------------------------------------------------------------------------
| Fitur:
| - Hash password (bcrypt / sha256 / pepper)
| - Super Encrypt (Caesar + AES)
| - Enkripsi / Dekripsi file (AES-256-CBC)
| - Steganografi (AES + LSB)
| - Pembuatan folder upload otomatis
|--------------------------------------------------------------------------
*/

// ---------------------------------------------------------------------
// 1️⃣ HASH PASSWORD (dicek agar tidak duplikat)
// ---------------------------------------------------------------------

if (!function_exists('hash_password_bcrypt')) {
    function hash_password_bcrypt($password) {
        return password_hash($password, PASSWORD_BCRYPT);
    }
}
if (!function_exists('verify_password_bcrypt')) {
    function verify_password_bcrypt($password, $hash) {
        return password_verify($password, $hash);
    }
}

if (!function_exists('hash_password_sha256')) {
    function hash_password_sha256($password) {
        $salt = "garam_rahasia_toko_buku";
        return hash('sha256', $password . $salt);
    }
}
if (!function_exists('verify_password_sha256')) {
    function verify_password_sha256($password, $hash_from_db) {
        $hashed_password = hash_password_sha256($password);
        return hash_equals($hashed_password, $hash_from_db);
    }
}

if (!function_exists('hash_password_pepper')) {
    function hash_password_pepper($password) {
        $pepper = defined('APP_PEPPER') ? APP_PEPPER : '';
        $peppered = hash_hmac("sha256", $password, $pepper);
        return password_hash($peppered, PASSWORD_BCRYPT);
    }
}
if (!function_exists('verify_password_pepper')) {
    function verify_password_pepper($password, $hash) {
        $pepper = defined('APP_PEPPER') ? APP_PEPPER : '';
        $peppered = hash_hmac("sha256", $password, $pepper);
        return password_verify($peppered, $hash);
    }
}

// ⚙️ Gunakan fungsi unified jika belum ada di config.php
if (!function_exists('hash_password_secure')) {
    function hash_password_secure($password) {
        return hash_password_pepper($password);
    }
}
if (!function_exists('verify_password_secure')) {
    function verify_password_secure($password, $storedHash) {
        return verify_password_pepper($password, $storedHash);
    }
}

// ---------------------------------------------------------------------
// 2️⃣ AES + CAESAR (SUPER ENCRYPT)
// ---------------------------------------------------------------------
if (!function_exists('caesar_cipher')) {
    function caesar_cipher($text, $shift, $mode = 'encrypt') {
        $result = "";
        $shift = (int)$shift;
        if ($mode === 'decrypt') $shift = 26 - $shift;
        foreach (str_split($text) as $char) {
            $ascii = ord($char);
            if ($ascii >= 65 && $ascii <= 90) {
                $result .= chr((($ascii - 65 + $shift) % 26) + 65);
            } elseif ($ascii >= 97 && $ascii <= 122) {
                $result .= chr((($ascii - 97 + $shift) % 26) + 97);
            } else {
                $result .= $char;
            }
        }
        return $result;
    }
}

if (!function_exists('aes_encrypt')) {
    function aes_encrypt($plaintext) {
        $raw = openssl_encrypt($plaintext, 'aes-256-cbc', AES_KEY_SECRET, OPENSSL_RAW_DATA, AES_IV_SECRET);
        return $raw === false ? false : base64_encode($raw);
    }
}

if (!function_exists('aes_decrypt')) {
    function aes_decrypt($base64_ciphertext) {
        $raw = base64_decode($base64_ciphertext, true);
        if ($raw === false) return false;
        $plaintext = openssl_decrypt($raw, 'aes-256-cbc', AES_KEY_SECRET, OPENSSL_RAW_DATA, AES_IV_SECRET);
        return $plaintext === false ? false : $plaintext;
    }
}

if (!function_exists('super_encrypt')) {
    function super_encrypt($plaintext, $caesar_shift = 5) {
        $caesar = caesar_cipher($plaintext, $caesar_shift, 'encrypt');
        return aes_encrypt($caesar);
    }
}

if (!function_exists('super_decrypt')) {
    function super_decrypt($base64_ciphertext, $caesar_shift = 5) {
        $decaes = aes_decrypt($base64_ciphertext);
        if ($decaes === false) return false;
        return caesar_cipher($decaes, $caesar_shift, 'decrypt');
    }
}

// ---------------------------------------------------------------------
// 3️⃣ ENKRIPSI FILE (AES-256-CBC, hasil disimpan base64)
// ---------------------------------------------------------------------
if (!function_exists('encrypt_file')) {
    function encrypt_file($source_path, $dest_path) {
        if (!file_exists($source_path)) return false;
        $dir = dirname($dest_path);
        if (!is_dir($dir)) mkdir($dir, 0755, true);
        $plaintext = file_get_contents($source_path);
        $cipher_base64 = aes_encrypt($plaintext);
        return $cipher_base64 ? file_put_contents($dest_path, $cipher_base64) !== false : false;
    }
}

if (!function_exists('decrypt_file_to_browser')) {
    function decrypt_file_to_browser($source_path) {
        if (!file_exists($source_path)) return false;
        $cipher_base64 = file_get_contents($source_path);
        return aes_decrypt($cipher_base64);
    }
}

// ---------------------------------------------------------------------
// 4️⃣ STEGANOGRAFI (AES + LSB)
// ---------------------------------------------------------------------
if (!function_exists('steganography_embed_secure')) {
    function steganography_embed_secure($image_file, $message_text) {
        $target_dir = 'uploads/stego_img/';
        if (!is_dir($target_dir)) mkdir($target_dir, 0755, true);
        $image_filename = time() . '_' . basename($image_file['name']);
        $image_path = $target_dir . $image_filename;
        if (!move_uploaded_file($image_file['tmp_name'], $image_path)) {
            return ['status'=>'error','msg'=>'Upload gagal'];
        }

        $aes_base64 = aes_encrypt($message_text);
        if ($aes_base64 === false) return ['status'=>'error','msg'=>'Enkripsi gagal'];

        $binary_message = '';
        for ($i = 0; $i < strlen($aes_base64); $i++) {
            $binary_message .= str_pad(decbin(ord($aes_base64[$i])), 8, '0', STR_PAD_LEFT);
        }
        $binary_message .= '1111111100000000'; // EOF

        $info = getimagesize($image_path);
        $mime = $info['mime'];
        $img = ($mime === 'image/png') ? imagecreatefrompng($image_path) : imagecreatefromjpeg($image_path);
        if (!$img) return ['status'=>'error','msg'=>'Gagal membuka gambar'];

        $width = imagesx($img);
        $height = imagesy($img);
        $total_pixels = $width * $height;
        if (strlen($binary_message) > $total_pixels * 3) {
            imagedestroy($img);
            unlink($image_path);
            return ['status'=>'error','msg'=>'Pesan terlalu panjang'];
        }

        $pixels = range(0, $total_pixels - 1);
        shuffle($pixels);
        $bit_index = 0;
        $len = strlen($binary_message);

        foreach ($pixels as $i) {
            if ($bit_index >= $len) break;
            $x = $i % $width;
            $y = floor($i / $width);
            $rgb = imagecolorat($img, $x, $y);
            $r = ($rgb >> 16) & 0xFF;
            $g = ($rgb >> 8) & 0xFF;
            $b = $rgb & 0xFF;
            foreach (['r','g','b'] as $c) {
                if ($bit_index >= $len) break;
                ${$c} = (${$c} & 0xFE) | intval($binary_message[$bit_index]);
                $bit_index++;
            }
            $new_color = ($r << 16) | ($g << 8) | $b;
            imagesetpixel($img, $x, $y, $new_color);
        }

        $ok = ($mime === 'image/png') ? imagepng($img, $image_path) : imagejpeg($img, $image_path, 90);
        imagedestroy($img);
        return $ok ? ['status'=>'ok','path'=>$image_path] : ['status'=>'error','msg'=>'Gagal menyimpan'];
    }
}

if (!function_exists('steganography_extract_secure')) {
    function steganography_extract_secure($image_path) {
        if (!file_exists($image_path)) return "File tidak ditemukan";
        $info = getimagesize($image_path);
        $mime = $info['mime'];
        $img = ($mime === 'image/png') ? imagecreatefrompng($image_path) : imagecreatefromjpeg($image_path);
        if (!$img) return "Gagal memproses gambar";

        $width = imagesx($img);
        $height = imagesy($img);
        $bits = '';
        for ($y = 0; $y < $height; $y++) {
            for ($x = 0; $x < $width; $x++) {
                $rgb = imagecolorat($img, $x, $y);
                $r = ($rgb >> 16) & 1;
                $g = ($rgb >> 8) & 1;
                $b = $rgb & 1;
                $bits .= $r . $g . $b;
                if (substr($bits, -16) === '1111111100000000') break 2;
            }
        }
        imagedestroy($img);

        if (strlen($bits) < 16) return "Pesan tidak ditemukan";
        $message = '';
        for ($i = 0; $i < strlen($bits) - 16; $i += 8) {
            $byte = substr($bits, $i, 8);
            $message .= chr(bindec($byte));
        }
        $decrypted = aes_decrypt($message);
        return $decrypted === false ? "Gagal dekripsi pesan" : $decrypted;
    }
}

// ---------------------------------------------------------------------
// 5️⃣ Utility: Buat folder upload otomatis
// ---------------------------------------------------------------------
if (!function_exists('ensure_upload_dirs')) {
    function ensure_upload_dirs() {
        $dirs = [
            'uploads/',
            'uploads/file_enc/',
            'uploads/stego_img/',
            'uploads/stego_txt/',
            'uploads/books/',
            'uploads/digital_books/',
        ];
        foreach ($dirs as $d) {
            if (!is_dir($d)) @mkdir($d, 0755, true);
        }
    }
}

ensure_upload_dirs();

// ===================================================
// 6. DEMO WRAPPER UNTUK KOMPATIBILITAS
// ===================================================
function steganography_embed_demo($file_array, $message_text) {
    // Pastikan argumen sesuai struktur $_FILES
    if (!isset($file_array['tmp_name']) || !isset($file_array['name'])) {
        return false;
    }

    // Gunakan fungsi utama
    $result = steganography_embed_secure($file_array, $message_text);

    // Ubah hasil agar sesuai kebutuhan konfirmasi_pembayaran.php
    if (isset($result['status']) && $result['status'] === 'ok') {
        return [
            'success' => true,
            'image_path' => $result['path']
        ];
    } else {
        return false;
    }
}

// ============================================================
// 7️⃣ STEGANOGRAFI HYBRID (AES + LSB + RANDOM PIXEL POSITION)
// ============================================================
if (!function_exists('lsb_embed_random_secure')) {
    /**
     * Menyisipkan pesan terenkripsi AES ke gambar secara acak (random pixel)
     */
    function lsb_embed_random_secure($image_path, $message, $output_path, $password)
    {
        // 1. Buat key dari password
        $key = hash('sha256', $password, true);
        $iv = substr(hash('sha256', $password . '_iv'), 0, 16);

        // 2. Enkripsi pesan dengan AES-256-CBC
        $encrypted = openssl_encrypt($message, 'AES-256-CBC', $key, OPENSSL_RAW_DATA, $iv);
        if ($encrypted === false) return ['status' => 'error', 'msg' => 'Enkripsi AES gagal'];

        // 3. Ubah ciphertext jadi bit
        $binary = '';
        for ($i = 0; $i < strlen($encrypted); $i++) {
            $binary .= str_pad(decbin(ord($encrypted[$i])), 8, '0', STR_PAD_LEFT);
        }

        // 4. Buka gambar
        $image = imagecreatefromstring(file_get_contents($image_path));
        if (!$image) return ['status' => 'error', 'msg' => 'Gagal membaca gambar'];

        $width = imagesx($image);
        $height = imagesy($image);
        $total_pixels = $width * $height;

        if (strlen($binary) > $total_pixels * 3) {
            imagedestroy($image);
            return ['status' => 'error', 'msg' => 'Pesan terlalu panjang untuk gambar'];
        }

        // 5. Randomize urutan pixel (pakai seed dari password)
        mt_srand(crc32($password));
        $positions = range(0, $total_pixels - 1);
        shuffle($positions);

        // 6. Sisipkan bit ke pixel acak
        $bit_index = 0;
        $bit_length = strlen($binary);
        foreach ($positions as $pos) {
            if ($bit_index >= $bit_length) break;
            $x = $pos % $width;
            $y = intdiv($pos, $width);
            $rgb = imagecolorat($image, $x, $y);
            $r = ($rgb >> 16) & 0xFF;
            $g = ($rgb >> 8) & 0xFF;
            $b = $rgb & 0xFF;

            foreach (['r', 'g', 'b'] as $c) {
                if ($bit_index >= $bit_length) break;
                ${$c} = (${$c} & 0xFE) | intval($binary[$bit_index++]);
            }

            $new_color = imagecolorallocate($image, $r, $g, $b);
            imagesetpixel($image, $x, $y, $new_color);
        }

        // 7. Simpan hasil stego
        $dir = dirname($output_path);
        if (!is_dir($dir)) mkdir($dir, 0755, true);
        $ok = imagepng($image, $output_path);
        imagedestroy($image);

        return $ok ? ['status' => 'ok', 'path' => $output_path] : ['status' => 'error', 'msg' => 'Gagal menyimpan gambar'];
    }
}

if (!function_exists('lsb_extract_random_secure')) {
    /**
     * Mengekstrak pesan terenkripsi AES dari gambar acak
     */
    function lsb_extract_random_secure($image_path, $password)
    {
        if (!file_exists($image_path)) return "File tidak ditemukan";
        $image = imagecreatefromstring(file_get_contents($image_path));
        if (!$image) return "Gagal membaca gambar";

        $width = imagesx($image);
        $height = imagesy($image);
        $total_pixels = $width * $height;

        mt_srand(crc32($password));
        $positions = range(0, $total_pixels - 1);
        shuffle($positions);

        $bits = '';
        foreach ($positions as $pos) {
            $x = $pos % $width;
            $y = intdiv($pos, $width);
            $rgb = imagecolorat($image, $x, $y);
            $r = ($rgb >> 16) & 1;
            $g = ($rgb >> 8) & 1;
            $b = $rgb & 1;
            $bits .= $r . $g . $b;
        }

        // Konversi bit → byte
        $data = '';
        for ($i = 0; $i < strlen($bits); $i += 8) {
            $byte = substr($bits, $i, 8);
            if (strlen($byte) < 8) break;
            $data .= chr(bindec($byte));
        }

        // Dekripsi AES
        $key = hash('sha256', $password, true);
        $iv = substr(hash('sha256', $password . '_iv'), 0, 16);
        $decrypted = openssl_decrypt($data, 'AES-256-CBC', $key, OPENSSL_RAW_DATA, $iv);

        imagedestroy($image);
        return $decrypted === false ? "Pesan tidak dapat didekripsi." : trim($decrypted);
    }
}


?>

