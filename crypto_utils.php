<?php
require_once 'config.php';

// =========================================================
// 1. LOGIN (Hashing)
// =========================================================

function hash_password_bcrypt($password) {
    return password_hash($password, PASSWORD_BCRYPT);
}

function verify_password_bcrypt($password, $hash) {
    return password_verify($password, $hash);
}

function hash_password_sha256($password) {
    $salt = "garam_rahasia_toko_buku"; 
    return hash('sha256', $password . $salt);
}

function verify_password_sha256($password, $hash_from_db) {
    $hashed_password = hash_password_sha256($password);
    return hash_equals($hashed_password, $hash_from_db);
}

// =========================================================
// 🔒 BCRYPT + PEPPER (HMAC SHA-256)
// =========================================================

function hash_password_pepper($password) {
    // Gunakan kunci rahasia dari config.php
    $pepper = AES_KEY_SECRET; 
    // Hash tambahan dengan HMAC
    $peppered = hash_hmac("sha256", $password, $pepper);
    // Hash hasil HMAC-nya dengan bcrypt
    return password_hash($peppered, PASSWORD_BCRYPT);
}

function verify_password_pepper($password, $hash) {
    $pepper = AES_KEY_SECRET; 
    $peppered = hash_hmac("sha256", $password, $pepper);
    return password_verify($peppered, $hash);
}


// =========================================================
// 2. SUPER ENKRIPSI (AES + Caesar Cipher)
// =========================================================

function caesar_cipher($text, $shift, $mode = 'encrypt') {
    $result = "";
    $shift = (int)$shift;
    if ($mode == 'decrypt') {
        $shift = 26 - $shift;
    }
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

function aes_encrypt($plaintext) {
    return openssl_encrypt(
        $plaintext, 
        'aes-256-cbc', 
        AES_KEY_SECRET, 
        0, 
        AES_IV_SECRET
    );
}

function aes_decrypt($ciphertext) {
    return openssl_decrypt(
        $ciphertext, 
        'aes-256-cbc', 
        AES_KEY_SECRET, 
        0, 
        AES_IV_SECRET
    );
}

function super_encrypt($plaintext, $caesar_shift = 5) {
    $caesar_encrypted = caesar_cipher($plaintext, $caesar_shift, 'encrypt');
    $aes_encrypted = aes_encrypt($caesar_encrypted);
    return base64_encode($aes_encrypted);
}

function super_decrypt($base64_ciphertext, $caesar_shift = 5) {
    $aes_encrypted = base64_decode($base64_ciphertext);
    $caesar_encrypted = aes_decrypt($aes_encrypted);
    $plaintext = caesar_cipher($caesar_encrypted, $caesar_shift, 'decrypt');
    return $plaintext;
}


// =========================================================
// 3. ENKRIPSI FILE (Menggunakan AES)
// =========================================================
function encrypt_file($source_path, $dest_path) {
    $plaintext = file_get_contents($source_path);
    if ($plaintext === false) return false;
    $ciphertext = openssl_encrypt(
        $plaintext, 
        'aes-256-cbc', 
        AES_KEY_SECRET, 
        0, 
        AES_IV_SECRET
    );
    if ($ciphertext === false) return false;
    file_put_contents($dest_path, $ciphertext);
    return true;
}

function decrypt_file_to_browser($source_path) {
    $ciphertext = file_get_contents($source_path);
    if ($ciphertext === false) return false;
    $plaintext = openssl_decrypt(
        $ciphertext, 
        'aes-256-cbc', 
        AES_KEY_SECRET, 
        0, 
        AES_IV_SECRET
    );
    if ($plaintext === false) return false;
    return $plaintext;
}


// =========================================================
// 4. STEGANOGRAFI (AES + LSB + RANDOM PIXEL)
// =========================================================

function steganography_embed_secure($image_file, $message_text) {
    $image_filename = time() . '_' . basename($image_file['name']);
    $image_path = 'uploads/stego_img/' . $image_filename;

    // Pindahkan file ke folder tujuan
    if (!move_uploaded_file($image_file['tmp_name'], $image_path)) {
        return false;
    }

    // 🔐 Enkripsi pesan dengan AES
    $encrypted_message = aes_encrypt($message_text);

    // 🔢 Konversi pesan terenkripsi ke biner
    $binary_message = '';
    for ($i = 0; $i < strlen($encrypted_message); $i++) {
        $binary_message .= str_pad(decbin(ord($encrypted_message[$i])), 8, '0', STR_PAD_LEFT);
    }

    // Tambahkan penanda akhir pesan agar bisa diekstrak nanti
    $binary_message .= str_repeat('0', 8) . '11111111'; // penanda EOF

    // 🧩 Buka gambar
    $image_info = getimagesize($image_path);
    if ($image_info === false) return false;

    $mime = $image_info['mime'];
    switch ($mime) {
        case 'image/png':
            $img = imagecreatefrompng($image_path);
            break;
        case 'image/jpeg':
            $img = imagecreatefromjpeg($image_path);
            break;
        default:
            return false;
    }

    $width = imagesx($img);
    $height = imagesy($img);
    $total_pixels = $width * $height;

    // 🧮 Pastikan pesan muat di gambar
    if (strlen($binary_message) > $total_pixels * 3) {
        return false; // pesan terlalu besar
    }

    // 🎲 Random urutan pixel untuk disisipkan
    $pixel_order = range(0, $total_pixels - 1);
    shuffle($pixel_order);

    $bit_index = 0;
    for ($i = 0; $i < $total_pixels && $bit_index < strlen($binary_message); $i++) {
        $x = $pixel_order[$i] % $width;
        $y = floor($pixel_order[$i] / $width);

        $rgb = imagecolorat($img, $x, $y);
        $r = ($rgb >> 16) & 0xFF;
        $g = ($rgb >> 8) & 0xFF;
        $b = $rgb & 0xFF;

        // Simpan 3 bit sekaligus ke RGB
        foreach (['r', 'g', 'b'] as $color) {
            if ($bit_index >= strlen($binary_message)) break;
            $$color = ($$color & 0xFE) | $binary_message[$bit_index];
            $bit_index++;
        }

        $new_color = imagecolorallocate($img, $r, $g, $b);
        imagesetpixel($img, $x, $y, $new_color);
    }

    // 💾 Simpan hasil gambar stego
    if ($mime == 'image/png') {
        imagepng($img, $image_path);
    } else {
        imagejpeg($img, $image_path, 90);
    }

    imagedestroy($img);

    return [
        'image_path' => $image_path,
        'message_length' => strlen($message_text),
        'encrypted_length' => strlen($encrypted_message)
    ];
}

function steganography_extract_secure($image_path) {
    $image_info = getimagesize($image_path);
    if ($image_info === false) return "Gagal membuka gambar.";

    $mime = $image_info['mime'];
    switch ($mime) {
        case 'image/png':
            $img = imagecreatefrompng($image_path);
            break;
        case 'image/jpeg':
            $img = imagecreatefromjpeg($image_path);
            break;
        default:
            return "Format gambar tidak didukung.";
    }

    $width = imagesx($img);
    $height = imagesy($img);
    $total_pixels = $width * $height;

    $bits = '';
    for ($y = 0; $y < $height; $y++) {
        for ($x = 0; $x < $width; $x++) {
            $rgb = imagecolorat($img, $x, $y);
            $r = ($rgb >> 16) & 0xFF;
            $g = ($rgb >> 8) & 0xFF;
            $b = $rgb & 0xFF;

            $bits .= ($r & 1);
            $bits .= ($g & 1);
            $bits .= ($b & 1);

            // Jika ketemu EOF marker
            if (substr($bits, -8) === '11111111') {
                break 2;
            }
        }
    }

    imagedestroy($img);

    // Konversi bit ke karakter
    $message = '';
    for ($i = 0; $i < strlen($bits) - 8; $i += 8) {
        $byte = substr($bits, $i, 8);
        $message .= chr(bindec($byte));
    }

    // 🔐 Dekripsi pesan
    $decrypted = aes_decrypt($message);
    if ($decrypted === false || $decrypted === '') {
        return "Gagal mendekripsi pesan.";
    }

    return $decrypted;
}

?>
