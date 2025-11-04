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
// 4. STEGANOGRAFI (Konsep untuk Demo) - KITA BALIKKAN KE INI
// =========================================================

function steganography_embed_demo($image_file, $message_text) {
    // Tentukan path
    $image_filename = time() . '_' . basename($image_file['name']);
    
    // VERSI PERBAIKAN: Buat nama file .txt cocok dengan nama gambar
    $image_basename_for_txt = pathinfo($image_filename, PATHINFO_FILENAME);
    $message_filename = $image_basename_for_txt . '.txt'; 

    $image_path = 'uploads/stego_img/' . $image_filename;
    $message_path = 'uploads/stego_txt/' . $message_filename;

    // 1. Pindahkan gambar
    if (!move_uploaded_file($image_file['tmp_name'], $image_path)) {
        return false;
    }
    
    // 2. Simpan pesan rahasia ke file teks
    if (file_put_contents($message_path, $message_text) === false) {
        return false;
    }

    return [
        'image_path' => $image_path,
        'message_path' => $message_path
    ];
}

function steganography_extract_demo($message_path) {
    // Cukup baca isi file teks rahasia
    if (file_exists($message_path)) {
        return file_get_contents($message_path);
    }
    return "Pesan rahasia tidak ditemukan.";
}

?>