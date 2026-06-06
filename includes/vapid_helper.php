<?php

function base64urlEncode($data) {
    return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
}

function base64urlDecode($data) {
    return base64_decode(strtr($data, '-_', '+/'));
}

function generateVapidJwt($endpoint, $publicKey, $privateKey) {
    $header = ['typ' => 'JWT', 'alg' => 'ES256'];

    $now = time();
    $payload = [
        'aud' => parse_url($endpoint, PHP_URL_SCHEME) . '://' . parse_url($endpoint, PHP_URL_HOST),
        'exp' => $now + 86400,
        'sub' => defined('VAPID_SUBJECT') ? VAPID_SUBJECT : 'mailto:admin@recurlog.com',
    ];

    $headerEncoded = base64urlEncode(json_encode($header));
    $payloadEncoded = base64urlEncode(json_encode($payload));

    $input = $headerEncoded . '.' . $payloadEncoded;

    $privateKeyPem = ecPrivateKeyToPem($privateKey);
    $signature = null;
    openssl_sign($input, $signature, $privateKeyPem, 'sha256');

    $rawSignature = derToRawSignature($signature, 64);

    $signatureEncoded = base64urlEncode($rawSignature);

    return $headerEncoded . '.' . $payloadEncoded . '.' . $signatureEncoded;
}

function ecPrivateKeyToPem($privateKeyRaw) {
    $pubKey = ecPublicKeyFromPrivate($privateKeyRaw);
    $x = substr($pubKey, 1, 32);
    $y = substr($pubKey, 33, 32);
    $d = $privateKeyRaw;

    $oid = hex2bin('2a8648ce3d030107');
    $curveOid = chr(6) . chr(strlen($oid)) . $oid;

    $dEnc = chr(2) . chr(strlen($d)) . $d;
    if (ord($d[0]) & 0x80) $dEnc = chr(2) . chr(strlen($d) + 1) . chr(0) . $d;

    $xEnc = chr(2) . chr(strlen($x)) . $x;
    if (ord($x[0]) & 0x80) $xEnc = chr(2) . chr(strlen($x) + 1) . chr(0) . $x;

    $yEnc = chr(2) . chr(strlen($y)) . $y;
    if (ord($y[0]) & 0x80) $yEnc = chr(2) . chr(strlen($y) + 1) . chr(0) . $y;

    $pubKeySeq = chr(0xa1) . chr(3 + strlen($xEnc) + strlen($yEnc)) . chr(3) . chr(strlen($xEnc) + strlen($yEnc)) . $xEnc . $yEnc;

    $innerSeqContent = chr(1) . chr(1) . chr(0) . $curveOid . $pubKeySeq . $dEnc;
    $innerSeq = chr(0x30) . chr(strlen($innerSeqContent)) . $innerSeqContent;

    $outerSeq = chr(0x30) . chr(strlen($innerSeq)) . $innerSeq;
    return "-----BEGIN EC PRIVATE KEY-----\n" . chunk_split(base64_encode($outerSeq), 64, "\n") . "-----END EC PRIVATE KEY-----";
}

function ecPublicKeyToPem($publicKeyRaw) {
    $x = substr($publicKeyRaw, 1, 32);
    $y = substr($publicKeyRaw, 33, 32);

    $xEnc = chr(2) . chr(strlen($x)) . $x;
    if (ord($x[0]) & 0x80) $xEnc = chr(2) . chr(strlen($x) + 1) . chr(0) . $x;

    $yEnc = chr(2) . chr(strlen($y)) . $y;
    if (ord($y[0]) & 0x80) $yEnc = chr(2) . chr(strlen($y) + 1) . chr(0) . $y;

    $pubKeySeqContent = $xEnc . $yEnc;
    $pubKeySeq = chr(0x30) . chr(strlen($pubKeySeqContent)) . $pubKeySeqContent;
    $bitString = chr(3) . chr(1 + strlen($publicKeyRaw)) . chr(0) . $publicKeyRaw;

    $oid = hex2bin('2a8648ce3d030107');
    $curveOid = chr(6) . chr(strlen($oid)) . $oid;

    $algSeqContent = $curveOid;
    $algSeq = chr(0x30) . chr(strlen($algSeqContent)) . $algSeqContent;

    $spkiContent = $algSeq . $bitString;
    $spki = chr(0x30) . chr(strlen($spkiContent)) . $spkiContent;

    return "-----BEGIN PUBLIC KEY-----\n" . chunk_split(base64_encode($spki), 64, "\n") . "-----END PUBLIC KEY-----";
}

function ecPublicKeyFromPrivate($privateKeyRaw) {
    $pem = ecPrivateKeyToPem($privateKeyRaw);
    $key = openssl_pkey_get_private($pem);
    $details = openssl_pkey_get_details($key);
    return chr(4) . $details['ec']['x'] . $details['ec']['y'];
}

function derToRawSignature($der, $expectedLen) {
    $rLen = ord($der[3]);
    $r = substr($der, 4, $rLen);
    $sLen = ord($der[5 + $rLen]);
    $s = substr($der, 6 + $rLen, $sLen);
    $r = str_pad(ltrim($r, "\x00"), $expectedLen / 2, "\x00", STR_PAD_LEFT);
    $s = str_pad(ltrim($s, "\x00"), $expectedLen / 2, "\x00", STR_PAD_LEFT);
    return $r . $s;
}

function hkdf($salt, $ikm, $info, $length) {
    $prk = hash_hmac('sha256', $ikm, $salt, true);
    $t = '';
    $result = '';
    for ($i = 1; strlen($result) < $length; $i++) {
        $t = hash_hmac('sha256', $t . $info . chr($i), $prk, true);
        $result .= $t;
    }
    return substr($result, 0, $length);
}

function encryptWebPushPayload($payload, $clientPublicKey, $authSecret) {
    $localKey = openssl_pkey_new([
        'curve_name' => 'prime256v1',
        'private_key_type' => OPENSSL_KEYTYPE_EC,
    ]);
    $localDetails = openssl_pkey_get_details($localKey);
    $localPublicKey = chr(4) . $localDetails['ec']['x'] . $localDetails['ec']['y'];
    $localPrivateKey = $localDetails['ec']['d'];

    $salt = openssl_random_pseudo_bytes(16);
    $recordSize = 4096;

    $clientPem = ecPublicKeyToPem(chr(4) . $clientPublicKey);
    $serverPem = ecPrivateKeyToPem($localPrivateKey);

    $serverRes = openssl_pkey_get_private($serverPem);
    $clientRes = openssl_pkey_get_public($clientPem);
    $sharedSecret = '';
    openssl_pkey_derive($clientRes, $serverRes, $sharedSecret, 256);

    $prkInfo = "Content-Encoding: auth\0";
    $prk = hkdf($authSecret, $sharedSecret, $prkInfo, 32);

    $cekInfo = "Content-Encoding: aes128gcm\0";
    $contentEncKey = hkdf($salt, $prk, $cekInfo, 16);

    $nonceInfo = "Content-Encoding: nonce\0";
    $nonce = hkdf($salt, $prk, $nonceInfo, 12);

    $padding = str_repeat(chr(0), 0);
    $plaintext = $payload . chr(2) . $padding;

    $tag = '';
    $ciphertext = openssl_encrypt($plaintext, 'aes-128-gcm', $contentEncKey, OPENSSL_RAW_DATA, $nonce, $tag);

    $record = pack('N', $recordSize) . chr(strlen($localPublicKey)) . $localPublicKey . $ciphertext . $tag;
    return $salt . $record;
}

function sendWebPushNotification($endpoint, $clientPublicKey, $clientAuth, $payload, $vapidPublicKey, $vapidPrivateKey) {
    $vapidJwt = generateVapidJwt($endpoint, $vapidPublicKey, $vapidPrivateKey);
    $vapidAuthHeader = 'vapid t=' . $vapidJwt . ', k=' . base64urlEncode(hex2bin('04') . $vapidPublicKey);

    $encryptedPayload = encryptWebPushPayload(json_encode($payload), base64urlDecode($clientPublicKey), base64urlDecode($clientAuth));

    $ch = curl_init($endpoint);
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/octet-stream',
            'Content-Encoding: aes128gcm',
            'Authorization: ' . $vapidAuthHeader,
            'TTL: 2419200',
        ],
        CURLOPT_POSTFIELDS => $encryptedPayload,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 10,
    ]);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    return [
        'success' => $httpCode >= 200 && $httpCode < 500,
        'statusCode' => $httpCode,
        'expired' => $httpCode === 410,
        'response' => $response,
    ];
}
