# app.py - Python Flask Backend API
from flask import Flask, request, jsonify
import json
import os
from datetime import datetime

app = Flask(__name__)
DATA_FILE = "devices.json"

def read_data():
    if not os.path.exists(DATA_FILE):
        return {}
    with open(DATA_FILE, "r") as f:
        try:
            return json.load(f)
        except json.JSONDecodeError:
            return {}

def write_data(data):
    with open(DATA_FILE, "w") as f:
        json.dump(data, f, indent=4)

@app.route('/api', methods=['GET'])
def get_devices():
    """Returns all stored device locations."""
    data = read_data()
    return jsonify(data), 200

@app.route('/api', methods=['POST'])
def update_location():
    """Receives and stores device location coordinates."""
    payload = request.get_json()

    if not payload or 'deviceId' not in payload or 'lat' not in payload or 'lng' not in payload:
        return jsonify({'status': 'error', 'message': 'Invalid payload'}), 400

    device_id = str(payload['deviceId'])
    devices = read_data()

    devices[device_id] = {
        'deviceId': device_id,
        'name': payload.get('name', f"Device {device_id[-4:]}"),
        'lat': float(payload['lat']),
        'lng': float(payload['lng']),
        'signal': int(payload.get('signalPercentage', 100)),
        'lastActive': datetime.now().strftime('%Y-%m-%d %H:%M:%S')
    }

    write_data(devices)
    return jsonify({'status': 'success', 'device': devices[device_id]}), 200

if __name__ == '__main__':
    # Run server on port 5000
    app.run(host='0.0.0.0', port=5000, debug=True)
    
