<?php
if (!defined('AES_KEY_SECRET')) {
    die("Error: AES_KEY_SECRET belum didefinisikan. Include config.php sebelum crypto_utils.php.");
}

// 1. HASH / PASSWORD helpers
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
if (!function_exists('hash_password_pepper')) {
    function hash_password_pepper($password) {
        $pepper = defined('APP_PEPPER') ? APP_PEPPER : '';
        $peppered = hash_hmac('sha256', $password, $pepper);
        return password_hash($peppered, PASSWORD_BCRYPT);
    }
}
if (!function_exists('verify_password_pepper')) {
    function verify_password_pepper($password, $hash) {
        $pepper = defined('APP_PEPPER') ? APP_PEPPER : '';
        $peppered = hash_hmac('sha256', $password, $pepper);
        return password_verify($peppered, $hash);
    }
}
if (!function_exists('hash_password_secure')) {
    function hash_password_secure($password) { return hash_password_pepper($password); }
}
if (!function_exists('verify_password_secure')) {
    function verify_password_secure($password, $storedHash) { return verify_password_pepper($password, $storedHash); }
}

// 2. AES helpers (base64 output)
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

// 3. File encrypt/decrypt (AES, save base64 to file)
if (!function_exists('encrypt_file')) {
    function encrypt_file($source_path, $dest_path) {
        if (!file_exists($source_path)) return false;
        $dir = dirname($dest_path);
        if (!is_dir($dir)) mkdir($dir, 0755, true);
        $plaintext = file_get_contents($source_path);
        if ($plaintext === false) return false;
        $cipher_base64 = aes_encrypt($plaintext);
        if ($cipher_base64 === false) return false;
        return file_put_contents($dest_path, $cipher_base64) !== false;
    }
}
if (!function_exists('decrypt_file_to_browser')) {
    function decrypt_file_to_browser($source_path) {
        if (!file_exists($source_path)) return false;
        $cipher_base64 = file_get_contents($source_path);
        if ($cipher_base64 === false) return false;
        return aes_decrypt($cipher_base64);
    }
}

// 4. Utility: buat direk if not exists
if (!function_exists('ensure_upload_dirs')) {
    function ensure_upload_dirs() {
        $dirs = [
            __DIR__ . '/uploads/',
            __DIR__ . '/uploads/file_enc/',
            __DIR__ . '/uploads/stego_img/',
            __DIR__ . '/uploads/stego_txt/',
            __DIR__ . '/uploads/books/',
            __DIR__ . '/uploads/digital_books/',
        ];
        foreach ($dirs as $d) {
            if (!is_dir($d)) @mkdir($d, 0755, true);
        }
    }
}
ensure_upload_dirs();

// 5. Sanitasi nama file
if (!function_exists('sanitize_filename')) {
    function sanitize_filename($name) {
        $clean = preg_replace('/[^a-zA-Z0-9\.\-\_\s]/', '_', $name);
        $clean = preg_replace('/\s+/', ' ', $clean);
        $clean = trim($clean);
        return $clean;
    }
}

// 6. Steganografi (LSB)
if (!function_exists('lsb_embed_random_secure')) {
    function lsb_embed_random_secure($a, $b, $c = null, $d = null) {
        $input_path = $a;
        $password = null;
        $output_path = null;
        $message = null;

        $looks_like_path = (is_string($b) && (strpos($b, DIRECTORY_SEPARATOR) !== false || preg_match('/\.(png|jpg|jpeg)$/i', $b)));
        if ($looks_like_path) {
            $output_path = $b;
            $message = $c;
            $password = $d;
        } else {
            $message = $b;
            $output_path = $c;
            $password = $d;
        }

        if (!$input_path || !$output_path || $message === null || $password === null) {
            return ['status' => 'error', 'msg' => 'Parameter tidak lengkap (expected input, output, message, password).'];
        }

        if (!file_exists($input_path)) return ['status' => 'error', 'msg' => 'File input tidak ditemukan'];

        $outdir = dirname($output_path);
        if (!is_dir($outdir)) {
            if (!mkdir($outdir, 0755, true)) return ['status' => 'error', 'msg' => "Gagal membuat folder: $outdir"];
        }

        $marker = 'STEGv1::';
        $aes_base64 = aes_encrypt($marker . $message);
        if ($aes_base64 === false) return ['status' => 'error', 'msg' => 'Enkripsi AES gagal'];

        $payload = $aes_base64;
        $payload_len = strlen($payload);
        if ($payload_len <= 0) return ['status' => 'error', 'msg' => 'Payload kosong'];
        if ($payload_len > 2 * 1024 * 1024) return ['status' => 'error', 'msg' => 'Payload terlalu besar'];

        $header = pack('N', $payload_len);
        $full = $header . $payload;

        $bitstr = '';
        for ($i = 0; $i < strlen($full); $i++) {
            $bitstr .= str_pad(decbin(ord($full[$i])), 8, '0', STR_PAD_LEFT);
        }
        $required_bits = strlen($bitstr);

        $img_contents = file_get_contents($input_path);
        if ($img_contents === false) return ['status' => 'error', 'msg' => 'Gagal membaca file input'];

        $image = @imagecreatefromstring($img_contents);
        if (!$image) return ['status' => 'error', 'msg' => 'Gagal membuat image dari file'];

        if (!imageistruecolor($image)) {
            $w = imagesx($image);
            $h = imagesy($image);
            $true = imagecreatetruecolor($w, $h);
            imagecopy($true, $image, 0, 0, 0, 0, $w, $h);
            imagedestroy($image);
            $image = $true;
        }

        $width = imagesx($image);
        $height = imagesy($image);
        $total_pixels = $width * $height;
        $capacity = $total_pixels * 3;

        if ($required_bits > $capacity) {
            imagedestroy($image);
            return ['status' => 'error', 'msg' => "Pesan terlalu besar (butuh $required_bits bit, kapasitas $capacity bit)"];
        }

        mt_srand(crc32($password));
        $positions = range(0, $total_pixels - 1);
        shuffle($positions);

        $bit_index = 0;
        $bit_len = $required_bits;
        foreach ($positions as $pos) {
            if ($bit_index >= $bit_len) break;
            $x = $pos % $width;
            $y = intdiv($pos, $width);
            $rgb = imagecolorat($image, $x, $y);
            $r = ($rgb >> 16) & 0xFF;
            $g = ($rgb >> 8) & 0xFF;
            $b = $rgb & 0xFF;

            if ($bit_index < $bit_len) {
                $r = ($r & 0xFE) | intval($bitstr[$bit_index++]);
            }
            if ($bit_index < $bit_len) {
                $g = ($g & 0xFE) | intval($bitstr[$bit_index++]);
            }
            if ($bit_index < $bit_len) {
                $b = ($b & 0xFE) | intval($bitstr[$bit_index++]);
            }

            $color_int = ($r << 16) | ($g << 8) | $b;
            imagesetpixel($image, $x, $y, $color_int);
        }

        $out_ext = strtolower(pathinfo($output_path, PATHINFO_EXTENSION));
        if ($out_ext !== 'png') {
            $output_path = preg_replace('/\.[^\.]+$/', '.png', $output_path);
        }

        $ok = imagepng($image, $output_path);
        imagedestroy($image);

        if (!$ok) return ['status' => 'error', 'msg' => 'Gagal menyimpan file stego (imagepng gagal)'];

        clearstatcache();
        if (!file_exists($output_path) || filesize($output_path) < 1) {
            return ['status' => 'error', 'msg' => 'File hasil stego tidak ditemukan / kosong setelah menyimpan'];
        }

        return ['status' => 'ok', 'path' => $output_path];
    }
}

if (!function_exists('lsb_extract_random_secure')) {
    function lsb_extract_random_secure($image_path, $password) {
        if (!file_exists($image_path)) return "File tidak ditemukan";

        $img_contents = file_get_contents($image_path);
        if ($img_contents === false) return "Gagal membaca file gambar";

        $image = @imagecreatefromstring($img_contents);
        if (!$image) return "Gagal memproses gambar";

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
            $bits .= (($rgb >> 16) & 1) . (($rgb >> 8) & 1) . ($rgb & 1);
        }

        $data = '';
        for ($i = 0; $i + 8 <= strlen($bits); $i += 8) {
            $byte = substr($bits, $i, 8);
            $data .= chr(bindec($byte));
        }

        if (strlen($data) < 4) {
            imagedestroy($image);
            return "Data terlalu pendek (tidak ditemukan header panjang)";
        }

        $len_bytes = substr($data, 0, 4);
        $payload_len = unpack('N', $len_bytes)[1];

        if ($payload_len <= 0 || $payload_len > 2 * 1024 * 1024) {
            imagedestroy($image);
            return "Panjang payload tidak valid (mungkin password salah atau bukan gambar stego).";
        }

        $expected_total = 4 + $payload_len;
        if (strlen($data) < $expected_total) {
            imagedestroy($image);
            return "Data stego terpotong (ekstraksi mendapati " . strlen($data) . " byte, dibutuhkan $expected_total byte).";
        }

        $payload = substr($data, 4, $payload_len);

        imagedestroy($image);

        $decrypted = aes_decrypt($payload);
        if ($decrypted === false) return "Pesan tidak dapat didekripsi (mungkin key/IV salah).";

        $marker = 'STEGv1::';
        if (strpos($decrypted, $marker) !== 0) {
            return "Pesan tidak valid (marker tidak ditemukan).";
        }

        $message = substr($decrypted, strlen($marker));
        return trim($message);
    }
}

// 7. Compatibility demo wrapper
if (!function_exists('steganography_embed_demo')) {
    function steganography_embed_demo($file_array, $message_text) {
        if (!isset($file_array['tmp_name']) || !isset($file_array['name'])) {
            return false;
        }
        $safe_name = sanitize_filename($file_array['name']);
        $tmp = $file_array['tmp_name'];
        $out_dir = __DIR__ . '/uploads/stego_img/';
        if (!is_dir($out_dir)) @mkdir($out_dir, 0755, true);
        $output = $out_dir . 'stego_' . time() . '_' . $safe_name . '.png';
        $res = lsb_embed_random_secure($tmp, $output, $message_text, 'stegapass');
        if (isset($res['status']) && $res['status'] === 'ok') {
            $rel = str_replace(__DIR__ . '/', '', $res['path']);
            return ['success' => true, 'image_path' => $rel];
        }
        return false;
    }
}

?>
