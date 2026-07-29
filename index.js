const express = require('express');
const fileUpload = require('express-fileupload');
const path = require('path');
const fs = require('fs');
const { default: makeWASocket, useMultiFileAuthState, DisconnectReason } = require('@whiskeysockets/baileys');
const pino = require('pino');

const app = express();
const PORT = process.env.PORT || 3000;

app.use(express.static('public'));
app.use(fileUpload());

// Directory to store uploaded files
const storageDir = path.join(__dirname, 'hosted_files');
if (!fs.existsSync(storageDir)) {
  fs.mkdirSync(storageDir, { recursive: true });
}

const linkMap = {};

function generateNumericIP() {
  const p1 = Math.floor(Math.random() * 90) + 10;
  const p2 = Math.floor(Math.random() * 900) + 100;
  const p3 = Math.floor(Math.random() * 90) + 10;
  const p4 = Math.floor(Math.random() * 90) + 10;
  return `${p1}.${p2}.${p3}.${p4}`;
}

// File Upload Route
app.post('/api/upload', (req, res) => {
  if (!req.files || !req.files.file) {
    return res.status(400).json({ success: false, message: 'No file uploaded.' });
  }

  const uploadedFile = req.files.file;
  const customIp = req.body.ipLink ? req.body.ipLink.trim() : generateNumericIP();
  const numericId = customIp.length > 0 ? customIp : generateNumericIP();

  const fileExt = path.extname(uploadedFile.name);
  const savedFileName = `${Date.now()}_${uploadedFile.name}`;
  const savePath = path.join(storageDir, savedFileName);

  uploadedFile.mv(savePath, (err) => {
    if (err) return res.status(500).json({ success: false, message: err.message });

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

// Serve Hosted Site Output
app.get('/site/:numericId', (req, res) => {
  const id = req.params.numericId;
  const fileData = linkMap[id];

  if (!fileData || !fs.existsSync(fileData.filePath)) {
    return res.status(404).send('<h1>404 - Hosted IP Link Not Found</h1>');
  }

  if (fileData.ext === '.sh') {
    res.setHeader('Content-Type', 'text/plain');
    return res.sendFile(fileData.filePath);
  }

  res.sendFile(fileData.filePath);
});

// WhatsApp Bot Engine
async function startBot() {
  const { state, saveCreds } = await useMultiFileAuthState('./auth_info');
  const sock = makeWASocket({
    logger: pino({ level: 'silent' }),
    auth: state,
    browser: ['SA!R Bot', 'Chrome', '1.0.0']
  });

  sock.ev.on('creds.update', saveCreds);

  sock.ev.on('connection.update', (update) => {
    const { connection, lastDisconnect } = update;
    if (connection === 'close') {
      const shouldReconnect = (lastDisconnect?.error)?.output?.statusCode !== DisconnectReason.loggedOut;
      if (shouldReconnect) startBot();
    } else if (connection === 'open') {
      console.log('✅ WhatsApp Bot Connected!');
    }
  });

  sock.ev.on('messages.upsert', async (m) => {
    const msg = m.messages[0];
    if (!msg.message || msg.key.fromMe) return;

    const from = msg.key.remoteJid;
    const type = Object.keys(msg.message)[0];
    const body = (type === 'conversation') ? msg.message.conversation :
                 (type === 'extendedTextMessage') ? msg.message.extendedTextMessage.text : '';

    if (body === '!ping') {
      await sock.sendMessage(from, { text: '🏓 Pong! Bot is active.' }, { quoted: msg });
    } else if (body === '!menu') {
      await sock.sendMessage(from, { text: '🤖 SA!R BOT MENU\n\n• !ping\n• !alive\n• !info' }, { quoted: msg });
    }
  });
}

// Start Web Server & Bot
app.listen(PORT, () => {
  console.log(`Server running on port ${PORT}`);
  startBot().catch(err => console.log('Bot Error:', err));
});
    
