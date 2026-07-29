const express = require('express');
const os = require('os');
const https = require('https');

const app = express();
const PORT = 3000;

app.use(express.static('.'));

// Helper to get local Hotspot / Wi-Fi network interface IP
function getLocalNetworkIPs() {
  const interfaces = os.networkInterfaces();
  const addresses = [];
  for (const name of Object.keys(interfaces)) {
    for (const iface of interfaces[name]) {
      // Filter out loopback (127.0.0.1) and non-IPv4 addresses
      if (iface.family === 'IPv4' && !iface.internal) {
        addresses.push({ interface: name, ip: iface.address });
      }
    }
  }
  return addresses;
}

// Endpoint to return Mobile Public IP and Local Hotspot IP details
app.get('/api/network-info', (req, res) => {
  // Fetch Public WAN IP from external resolver
  https.get('https://api.ipify.org?format=json', (apiRes) => {
    let data = '';
    apiRes.on('data', chunk => data += chunk);
    apiRes.on('end', () => {
      const publicIp = JSON.parse(data).ip;
      
      // Get incoming client IP (your friend's IP on your hotspot network)
      const clientIp = req.headers['x-forwarded-for'] || req.socket.remoteAddress;

      res.json({
        publicMobileIp: publicIp,
        localHotspotGateway: getLocalNetworkIPs(),
        connectedFriendIp: clientIp
      });
    });
  }).on('error', (err) => {
    res.status(500).json({ error: 'Failed to resolve public IP' });
  });
});

app.listen(PORT, () => {
  console.log(`Hotspot Tracking Server running at http://localhost:${PORT}`);
});
