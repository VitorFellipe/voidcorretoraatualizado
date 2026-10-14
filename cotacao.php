<?php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
$contextOptions = [
    "ssl" => [
        "verify_peer" => false,
        "verify_peer_name" => false
    ],
 "http" => [
    "method" => "GET",
    "header" => "User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64)\r\n",
    "timeout" => 5
 ]
 ];

 $context = stream_context_create($contextOptions);

  $apiUrl = "https://economia.awesomeapi.com.br/json/last/BTC-BRL,BTC-USD";

 $response = @file_get_contents($apiUrl, false, $context);

 if ($response !== FALSE){
    $data = json_decode($response, true);

    if (isset($data['BTCBRL']) && isset($data['BTCUSD'])){
       echo json_encode([
        "status" => "sucess",
        "source" => "awesome_api",
        "bitcoin" => [
            "usd" => (float)$data['BTCUSD']['bid'],
            "brl" => (float)$data['BTCBRL']['bid'],
            "usd_24h_change" => (float)$data['BTCUSD']['pctChange']
        ]
       ]);
       exit;
    }
 }

 echo json_encode([
    "status" => "fallback",
    "source" => "backcup_data",
    "bitcoin" => [
        "usd" => 75000.00,
        "brl" => 412000.00,
        "usd_24h_change" => 1.85
    ]
 ]);
 ?>