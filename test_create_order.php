<?php
require_once __DIR__ . '/includes/node_client.php';
$sampleFile = __DIR__ . '/courier/logistic_provider_api-main/api/jnt/sample_data.json';
header('Content-Type: application/json');
if (!file_exists($sampleFile)) {
    echo json_encode(['ok'=>false,'error'=>'sample_data.json not found at '.$sampleFile]);
    exit;
}
$logistics = json_decode(file_get_contents($sampleFile), true);
$payload = ['data' => ['logistics_interface' => $logistics]];
$result = callNodeApi('/api/j&t/createOrder', 'POST', $payload);
echo json_encode($result, JSON_PRETTY_PRINT);
