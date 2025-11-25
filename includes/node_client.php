<?php
function callNodeApi($endpoint, $method = 'GET', $payload = null, $timeoutSeconds = 15) {
    $url = "http://localhost:3000" . $endpoint;
    $ch = curl_init();
    $headers = ['Accept: application/json'];

    if (in_array(strtoupper($method), ['POST','PUT','PATCH'])) {
        $headers[] = 'Content-Type: application/json';
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload ?? []));
    }

    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_CUSTOMREQUEST => $method,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => $headers,
        CURLOPT_CONNECTTIMEOUT => $timeoutSeconds,
        CURLOPT_TIMEOUT => $timeoutSeconds + 5,
    ]);

    $responseBody = curl_exec($ch);
    $curlErr     = curl_error($ch);
    $httpStatus  = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($curlErr) {
        return ['ok' => false, 'status' => 0, 'error' => "Network error: $curlErr"];
    }

    $decoded = json_decode($responseBody, true);
    if ($decoded === null && json_last_error() !== JSON_ERROR_NONE) {
        return ['ok' => false, 'status' => $httpStatus, 'error' => 'Invalid JSON from Node', 'raw' => $responseBody];
    }

    if ($httpStatus < 200 || $httpStatus >= 300) {
        return ['ok' => false, 'status' => $httpStatus, 'error' => ($decoded['message'] ?? 'Node API error'), 'data' => $decoded];
    }

    return ['ok' => true, 'status' => $httpStatus, 'data' => $decoded];
}
