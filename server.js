const express = require('express');
const fileUpload = require('express-fileupload');
const path = require('path');
const fs = require('fs');
const localtunnel = require('localtunnel');

const app = express();
const MAIN_PORT = 3000;

app.use(express.static('public'));
app.use(fileUpload());

// Directory to store uploaded static sites
const uploadsDir = path.join(__dirname, 'hosted_sites');
if (!fs.existsSync(uploadsDir)) {
  fs.mkdirSync(uploadsDir);
}

app.post('/upload', async (req, res) => {
  if (!req.files || !req.files.websiteFile) {
    return res.status(400).json({ success: false, message: 'No file uploaded.' });
  }

  const uploadedFile = req.files.websiteFile;
  const ipLink = req.body.ipLink || 'default';
  
  // Create a unique port and path for this deployment
  const targetPort = 4000 + Math.floor(Math.random() * 1000);
  const userDir = path.join(uploadsDir, `site_${targetPort}`);
  fs.mkdirSync(userDir, { recursive: true });

  const filePath = path.join(userDir, 'index.html');

  // Save the uploaded file
  uploadedFile.mv(filePath, async (err) => {
    if (err) return res.status(500).json({ success: false, message: err.message });

    // Host the uploaded file locally on its assigned port
    const siteApp = express();
    siteApp.use(express.static(userDir));
    const server = siteApp.listen(targetPort, async () => {
      try {
        // Expose the local port globally via localtunnel
        const tunnel = await localtunnel({ port: targetPort });
        
        res.json({
          success: true,
          worldWideUrl: tunnel.url,
          fileName: uploadedFile.name,
          ipLink: ipLink
        });

        tunnel.on('close', () => {
          server.close();
        });
      } catch (tunnelErr) {
        res.status(500).json({ success: false, message: "Tunnel creation failed." });
      }
    });
  });
});

app.listen(MAIN_PORT, () => {
  console.log(`SA!R Hosting server is running on http://localhost:${MAIN_PORT}`);
});
    
