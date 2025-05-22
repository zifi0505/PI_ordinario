<?php
// azure/azure-translator.php

require_once __DIR__ . '/config.php';

function azureTranslate(string $texto, string $toLang = 'en', string $fromLang = 'es', bool $isHtml = false): string {
    $url = AZURE_TRANSLATOR_ENDPOINT
         . 'translate?api-version=3.0'
         . '&from='    . urlencode($fromLang)
         . '&to='      . urlencode($toLang)
         . ($isHtml ? '&textType=html' : '');

    $body = json_encode([ ['Text' => $texto] ], JSON_UNESCAPED_UNICODE);

    $headers = [
        'Ocp-Apim-Subscription-Key: '   . AZURE_TRANSLATOR_KEY,
        'Ocp-Apim-Subscription-Region: ' . AZURE_TRANSLATOR_REGION,
        'Content-Type: application/json; charset=UTF-8'
    ];

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => $body,
        CURLOPT_HTTPHEADER     => $headers,
        CURLOPT_SSL_VERIFYPEER => false,  // solo para debugging local
    ]);

    $resp = curl_exec($ch);
    if ($resp === false) {
        error_log("cURL ERROR: " . curl_error($ch));
        curl_close($ch);
        return $texto;
    }
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    if ($httpCode !== 200) {
        error_log("AZURE HTTP CODE: $httpCode -- RESPONSE: $resp");
        return $texto;
    }

    $data = json_decode($resp, true);
    if (!isset($data[0]['translations'][0]['text'])) {
        error_log("AZURE PARSE ERROR: " . print_r($data, true));
        return $texto;
    }

    return $data[0]['translations'][0]['text'];
}