<?php
// save_location.php

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $latitude  = filter_input(INPUT_POST, 'latitude', FILTER_VALIDATE_FLOAT);
    $longitude = filter_input(INPUT_POST, 'longitude', FILTER_VALIDATE_FLOAT);

    if ($latitude !== false && $longitude !== false) {
        // Log or process the coordinates securely
        $logData = sprintf("[%s] Lat: %f, Lon: %f\n", date('Y-m-d H:i:s'), $latitude, $longitude);
        file_put_contents('locations.log', $logData, FILE_APPEND);

        echo json_encode(['status' => 'success', 'message' => 'Coordinates stored']);
    } else {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'Invalid coordinates']);
    }
} else {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Method not allowed']);
}
?>
