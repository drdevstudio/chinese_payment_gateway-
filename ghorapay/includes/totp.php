<?php
// RFC 6238 TOTP Implementation for Ghora Pay

class TOTP {
    private static $base32Chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';

    public static function generateSecret($length = 16) {
        $secret = '';
        $validChars = self::$base32Chars;
        for ($i = 0; $i < $length; $i++) {
            $secret .= $validChars[random_int(0, strlen($validChars) - 1)];
        }
        return $secret;
    }

    private static function base32Decode($secret) {
        $secret = strtoupper($secret);
        $secret = preg_replace('/[^A-Z2-7]/', '', $secret);
        $binary = '';
        $buffer = 0;
        $bitsLeft = 0;

        for ($i = 0; $i < strlen($secret); $i++) {
            $pos = strpos(self::$base32Chars, $secret[$i]);
            if ($pos === false) continue;
            $buffer = ($buffer << 5) | $pos;
            $bitsLeft += 5;
            if ($bitsLeft >= 8) {
                $bitsLeft -= 8;
                $binary .= chr(($buffer >> $bitsLeft) & 0xFF);
            }
        }
        return $binary;
    }

    public static function getCode($secret, $timeSlice = null) {
        if ($timeSlice === null) $timeSlice = floor(time() / 30);
        $secretKey = self::base32Decode($secret);
        $time = chr(0) . chr(0) . chr(0) . chr(0) . pack('N*', $timeSlice);
        $hm = hash_hmac('sha1', $time, $secretKey, true);
        $offset = ord($hm[19]) & 0xF;
        $code = (
            ((ord($hm[$offset]) & 0x7F) << 24) |
            ((ord($hm[$offset + 1]) & 0xFF) << 16) |
            ((ord($hm[$offset + 2]) & 0xFF) << 8) |
            (ord($hm[$offset + 3]) & 0xFF)
        ) % 1000000;
        return str_pad($code, 6, '0', STR_PAD_LEFT);
    }

    public static function verify($secret, $code, $discrepancy = 1) {
        $code = trim($code);
        if (strlen($code) !== 6 || !ctype_digit($code)) return false;
        $currentSlice = floor(time() / 30);
        for ($i = -$discrepancy; $i <= $discrepancy; $i++) {
            if (self::getCode($secret, $currentSlice + $i) === $code) return true;
        }
        return false;
    }

    public static function getQRUrl($secret, $email, $issuer = 'Ghora Pay') {
        $issuerEnc = urlencode($issuer);
        $emailEnc = urlencode($email);
        $data = "otpauth://totp/{$issuerEnc}:{$emailEnc}?secret={$secret}&issuer={$issuerEnc}&algorithm=SHA1&digits=6&period=30";
        return 'https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=' . urlencode($data);
    }
}
