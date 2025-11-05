<?php

if (!defined('AES_KEY_SECRET')) {
    die("Error: AES_KEY_SECRET belum didefinisikan. Include config.php sebelum crypto_utils.php.");
}

//1. HASH / PASSWORD (keamanan login)
if (!function_exists('hash_password_secure')) {
    function hash_password_secure($password) {
        $pepper = defined('APP_PEPPER') ? APP_PEPPER : '';
        $peppered = hash_hmac('sha256', $password, $pepper);
        return password_hash($peppered, PASSWORD_BCRYPT);
    }
}
if (!function_exists('verify_password_secure')) {
    function verify_password_secure($password, $storedHash) {
        $pepper = defined('APP_PEPPER') ? APP_PEPPER : '';
        $peppered = hash_hmac('sha256', $password, $pepper);
        return password_verify($peppered, $storedHash);
    }
}


   //2. AES ENCRYPT / DECRYPT (inti super-enkripsi)
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

  // 3. FILE ENCRYPTION (gunakan AES untuk file)
if (!function_exists('encrypt_file')) {
    function encrypt_file($source_path, $dest_path) {
        if (!file_exists($source_path)) return false;
        $dir = dirname($dest_path);
        if (!is_dir($dir)) mkdir($dir, 0755, true);
        $plaintext = file_get_contents($source_path);
        $cipher_base64 = aes_encrypt($plaintext);
        return file_put_contents($dest_path, $cipher_base64) !== false;
    }
}
if (!function_exists('decrypt_file_to_browser')) {
    function decrypt_file_to_browser($source_path) {
        if (!file_exists($source_path)) return false;
        $cipher_base64 = file_get_contents($source_path);
        return aes_decrypt($cipher_base64);
    }
}

   //4. UTILITAS (folder & nama file)
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
        foreach ($dirs as $d) if (!is_dir($d)) @mkdir($d, 0755, true);
    }
}
ensure_upload_dirs();

if (!function_exists('sanitize_filename')) {
    function sanitize_filename($name) {
        $clean = preg_replace('/[^a-zA-Z0-9\.\-\_\s]/', '_', $name);
        return trim(preg_replace('/\s+/', ' ', $clean));
    }
}

  // 5. STEGANOGRAFI (LSB Random + AES)
if (!function_exists('lsb_embed_random_secure')) {
    function lsb_embed_random_secure($a, $b, $c = null, $d = null) {
        $input_path = $a;
        $looks_like_path = (is_string($b) && (strpos($b, DIRECTORY_SEPARATOR) !== false || preg_match('/\.(png|jpg|jpeg)$/i', $b)));
        if ($looks_like_path) {
            $output_path = $b; $message = $c; $password = $d;
        } else {
            $message = $b; $output_path = $c; $password = $d;
        }

        if (!$input_path || !$output_path || $message === null || $password === null)
            return ['status' => 'error', 'msg' => 'Parameter tidak lengkap'];

        if (!file_exists($input_path))
            return ['status' => 'error', 'msg' => 'File input tidak ditemukan'];

        $marker = 'STEGv1::';
        $aes_base64 = aes_encrypt($marker . $message);
        $payload_len = strlen($aes_base64);
        if ($payload_len <= 0) return ['status' => 'error', 'msg' => 'Payload kosong'];
        if ($payload_len > 2 * 1024 * 1024) return ['status' => 'error', 'msg' => 'Payload terlalu besar'];

        $header = pack('N', $payload_len);
        $full = $header . $aes_base64;
        $bitstr = '';
        for ($i = 0; $i < strlen($full); $i++) $bitstr .= str_pad(decbin(ord($full[$i])), 8, '0', STR_PAD_LEFT);
        $required_bits = strlen($bitstr);

        $image = @imagecreatefromstring(file_get_contents($input_path));
        if (!$image) return ['status' => 'error', 'msg' => 'Gagal membaca gambar'];
        if (!imageistruecolor($image)) {
            $w = imagesx($image); $h = imagesy($image);
            $true = imagecreatetruecolor($w, $h);
            imagecopy($true, $image, 0, 0, 0, 0, $w, $h);
            imagedestroy($image); $image = $true;
        }

        $width = imagesx($image); $height = imagesy($image);
        $total_pixels = $width * $height; $capacity = $total_pixels * 3;
        if ($required_bits > $capacity) { imagedestroy($image); return ['status'=>'error','msg'=>"Pesan terlalu besar"]; }

        mt_srand(crc32($password));
        $positions = range(0, $total_pixels - 1); shuffle($positions);

        $bit_index = 0;
        foreach ($positions as $pos) {
            if ($bit_index >= $required_bits) break;
            $x = $pos % $width; $y = intdiv($pos, $width);
            $rgb = imagecolorat($image, $x, $y);
            $r = ($rgb >> 16) & 0xFF; $g = ($rgb >> 8) & 0xFF; $b = $rgb & 0xFF;
            if ($bit_index < $required_bits) $r = ($r & 0xFE) | intval($bitstr[$bit_index++]);
            if ($bit_index < $required_bits) $g = ($g & 0xFE) | intval($bitstr[$bit_index++]);
            if ($bit_index < $required_bits) $b = ($b & 0xFE) | intval($bitstr[$bit_index++]);
            $color_int = ($r << 16) | ($g << 8) | $b;
            imagesetpixel($image, $x, $y, $color_int);
        }

        $output_path = preg_replace('/\.[^\.]+$/', '.png', $output_path);
        $ok = imagepng($image, $output_path); imagedestroy($image);
        if (!$ok) return ['status'=>'error','msg'=>'Gagal menyimpan file stego'];
        return ['status'=>'ok','path'=>$output_path];
    }
}

if (!function_exists('lsb_extract_random_secure')) {
    function lsb_extract_random_secure($image_path, $password) {
        if (!file_exists($image_path)) return "File tidak ditemukan";
        $image = @imagecreatefromstring(file_get_contents($image_path));
        if (!$image) return "Gagal memproses gambar";
        $width = imagesx($image); $height = imagesy($image);
        $total_pixels = $width * $height;
        mt_srand(crc32($password));
        $positions = range(0, $total_pixels - 1); shuffle($positions);
        $bits = '';
        foreach ($positions as $pos) {
            $x = $pos % $width; $y = intdiv($pos, $width);
            $rgb = imagecolorat($image, $x, $y);
            $bits .= (($rgb >> 16) & 1) . (($rgb >> 8) & 1) . ($rgb & 1);
        }
        $data = '';
        for ($i = 0; $i + 8 <= strlen($bits); $i += 8)
            $data .= chr(bindec(substr($bits, $i, 8)));
        if (strlen($data) < 4) return "Data tidak valid";
        $payload_len = unpack('N', substr($data, 0, 4))[1];
        $payload = substr($data, 4, $payload_len);
        $decrypted = aes_decrypt($payload);
        if ($decrypted === false) return "Gagal dekripsi";
        $marker = 'STEGv1::';
        if (strpos($decrypted, $marker) !== 0) return "Marker tidak ditemukan";
        return substr($decrypted, strlen($marker));
    }
}

   //6. SUPER ENCRYPTION UNTUK CHAT (Caesar + AES)
if (!function_exists('super_encrypt')) {
    function super_encrypt($plaintext, $shift = 5) {
        $shift = $shift % 26;
        $caesar_text = '';
        for ($i = 0; $i < strlen($plaintext); $i++) {
            $ch = $plaintext[$i];
            if (ctype_alpha($ch)) {
                $base = ctype_upper($ch) ? 65 : 97;
                $caesar_text .= chr((ord($ch) - $base + $shift) % 26 + $base);
            } else $caesar_text .= $ch;
        }
        return aes_encrypt($caesar_text);
    }
}
if (!function_exists('super_decrypt')) {
    function super_decrypt($ciphertext, $shift = 5) {
        $aes_decrypted = aes_decrypt($ciphertext);
        if ($aes_decrypted === false) return "[DECRYPT ERROR]";
        $shift = $shift % 26;
        $plain_text = '';
        for ($i = 0; $i < strlen($aes_decrypted); $i++) {
            $ch = $aes_decrypted[$i];
            if (ctype_alpha($ch)) {
                $base = ctype_upper($ch) ? 65 : 97;
                $plain_text .= chr((ord($ch) - $base - $shift + 26) % 26 + $base);
            } else $plain_text .= $ch;
        }
        return $plain_text;
    }
}

?>
