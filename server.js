const express = require('express');
const fileUpload = require('express-fileupload');
const path = require('path');
const fs = require('fs');

const app = express();
const PORT = process.env.PORT || 3000;

app.use(express.static('public'));
app.use(fileUpload());

// Directory to store uploaded files
const storageDir = path.join(__dirname, 'hosted_files');
if (!fs.existsSync(storageDir)) {
  fs.mkdirSync(storageDir, { recursive: true });
}

// Memory store for numeric links mapping
const linkMap = {};

// Helper function to generate numeric IP-style IDs (e.g. 62.833.88.32)
function generateNumericIP() {
  const p1 = Math.floor(Math.random() * 90) + 10;
  const p2 = Math.floor(Math.random() * 900) + 100;
  const p3 = Math.floor(Math.random() * 90) + 10;
  const p4 = Math.floor(Math.random() * 90) + 10;
  return `${p1}.${p2}.${p3}.${p4}`;
}

// Upload endpoint
app.post('/api/upload', (req, res) => {
  if (!req.files || !req.files.file) {
    return res.status(400).json({ success: false, message: 'No file was uploaded.' });
  }

  const uploadedFile = req.files.file;
  const customIp = req.body.ipLink ? req.body.ipLink.trim() : generateNumericIP();
  const numericId = customIp.length > 0 ? customIp : generateNumericIP();

  const fileExt = path.extname(uploadedFile.name);
  const savedFileName = `${Date.now()}_${uploadedFile.name}`;
  const savePath = path.join(storageDir, savedFileName);

  uploadedFile.mv(savePath, (err) => {
    if (err) {
      return res.status(500).json({ success: false, message: err.message });
    }

    // Save link mapping
    linkMap[numericId] = {
      filePath: savePath,
      fileName: uploadedFile.name,
      ext: fileExt
    };

    const protocol = req.protocol;
    const host = req.get('host');
    const fullUrl = `${protocol}://${host}/site/${numericId}`;

    res.json({
      success: true,
      url: fullUrl,
      numericId: numericId,
      fileName: uploadedFile.name
    });
  });
});

// Dynamic output endpoint (Serves uploaded HTML or Bash script globally)
app.get('/site/:numericId', (req, res) => {
  const id = req.params.numericId;
  const fileData = linkMap[id];

  if (!fileData || !fs.existsSync(fileData.filePath)) {
    return res.status(404).send('<h1>404 - Hosted IP Link Not Found</h1>');
  }

  // If Bash script, display text contents directly or serve as download
  if (fileData.ext === '.sh') {
    res.setHeader('Content-Type', 'text/plain');
    return res.sendFile(fileData.filePath);
  }

  // If HTML or web file, render it directly
  res.sendFile(fileData.filePath);
});

app.listen(PORT, () => {
  console.log(`==================================================`);
  console.log(`SA!R Hosting Server is running on port ${PORT}`);
  console.log(`Local Access: http://localhost:${PORT}`);
  console.log(`==================================================`);
});
      
