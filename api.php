<?php
// api.php - Server Backend API
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST');
header('Access-Control-Allow-Headers: Content-Type');

$dataFile = __DIR__ . '/devices.json';

// Ensure data storage file exists
if (!file_exists($dataFile)) {
    file_put_contents($dataFile, json_encode([], JSON_PRETTY_PRINT));
}

$requestMethod = $_SERVER['REQUEST_METHOD'];

// Handle GET Request: Fetch all connected device locations
if ($requestMethod === 'GET') {
    $content = file_get_contents($dataFile);
    echo $content ? $content : json_encode([]);
    exit;
}

// Handle POST Request: Receive location payload from client
if ($requestMethod === 'POST') {
    $rawInput = file_get_contents('php://input');
    $payload = json_decode($rawInput, true);

    // Validate payload fields
    if (!$payload || !isset($payload['deviceId'], $payload['lat'], $payload['lng'])) {
        http_response_code(400);
        echo json_encode([
            'status' => 'error',
            'message' => 'Missing required fields: deviceId, lat, or lng'
        ]);
        exit;
    }

    // Read existing database
    $devices = json_decode(file_get_contents($dataFile), true) ?? [];

    // Sanitize and update device record
    $deviceId = preg_replace('/[^a-zA-Z0-9_\-]/', '', $payload['deviceId']);
    $devices[$deviceId] = [
        'deviceId'   => $deviceId,
        'name'       => filter_var($payload['name'] ?? 'Device ' . substr($deviceId, -4), FILTER_SANITIZE_SPECIAL_CHARS),
        'lat'        => filter_var($payload['lat'], FILTER_VALIDATE_FLOAT),
        'lng'        => filter_var($payload['lng'], FILTER_VALIDATE_FLOAT),
        'signal'     => filter_var($payload['signalPercentage'] ?? 100, FILTER_VALIDATE_INT),
        'lastActive' => date('Y-m-d H:i:s')
    ];

    // Save back to JSON file
    file_put_contents($dataFile, json_encode($devices, JSON_PRETTY_PRINT));

    echo json_encode([
        'status'  => 'success',
        'message' => 'Location recorded',
        'device'  => $devices[$deviceId]
    ]);
    exit;
}

// Return 405 for unsupported HTTP methods
http_response_code(405);
echo json_encode(['status' => 'error', 'message' => 'Method Not Allowed']);
