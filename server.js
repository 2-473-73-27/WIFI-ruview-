const express = require('express');
const app = express();
const PORT = 3000;

app.use(express.json());
app.use(express.static('.'));

// In-memory key store for paired devices
const activeSessions = {};

// Register or join a 10-word key room
app.post('/api/connect-key', (req, res) => {
  const { key, memberId, location } = req.body;
  
  if (!activeSessions[key]) {
    activeSessions[key] = [];
  }
  
  if (activeSessions[key].length < 2) {
    activeSessions[key].push({ memberId, location });
  }

  const isConnected = activeSessions[key].length === 2;
  
  res.json({
    success: true,
    membersConnected: activeSessions[key].length,
    sessionReady: isConnected,
    members: activeSessions[key]
  });
});

app.listen(PORT, () => {
  console.log(`Tracking server active at http://localhost:${PORT}`);
});
