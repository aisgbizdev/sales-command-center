const express = require('express');
const cors = require('cors');
const qrcode = require('qrcode');
const { Client, LocalAuth } = require('whatsapp-web.js');

process.on('unhandledRejection', (error) => {
  console.error('[wa-webjs] unhandledRejection:', error);
});

process.on('uncaughtException', (error) => {
  console.error('[wa-webjs] uncaughtException:', error);
});

const app = express();
app.use(cors());
app.use(express.json({ limit: '1mb' }));

const sessions = new Map();

function resolveUserId(req) {
  const header = String(req.headers['x-sgb-user-id'] || '').trim();
  const query = String(req.query.user_id || '').trim();
  const raw = header || query || 'global';
  return raw.replace(/[^\w.-]/g, '') || 'global';
}

function createSessionState(userId) {
  const state = {
    userId,
    ready: false,
    lastQr: null,
    initializing: false,
    client: null,
  };

  const client = new Client({
    authStrategy: new LocalAuth({
      dataPath: '.wwebjs_auth',
      clientId: `user-${userId}`,
    }),
    puppeteer: {
      headless: true,
      args: ['--no-sandbox', '--disable-setuid-sandbox'],
    },
  });

  state.client = client;

  client.on('qr', (qr) => {
    state.lastQr = qr;
    state.ready = false;
    console.log(`[wa-webjs] [${userId}] QR received.`);
  });

  client.on('ready', () => {
    state.ready = true;
    state.lastQr = null;
    console.log(`[wa-webjs] [${userId}] Client ready.`);
  });

  client.on('auth_failure', (msg) => {
    state.ready = false;
    console.error(`[wa-webjs] [${userId}] Auth failure:`, msg);
  });

  client.on('disconnected', (reason) => {
    state.ready = false;
    console.warn(`[wa-webjs] [${userId}] Disconnected:`, reason);
    tryInitialize(state);
  });

  return state;
}

function tryInitialize(state) {
  if (state.initializing) return;
  state.initializing = true;
  state.client
    .initialize()
    .catch((error) => {
      console.error(`[wa-webjs] [${state.userId}] initialize failed:`, error?.message || error);
      state.ready = false;
    })
    .finally(() => {
      state.initializing = false;
    });
}

function getSession(req) {
  const userId = resolveUserId(req);
  if (!sessions.has(userId)) {
    const state = createSessionState(userId);
    sessions.set(userId, state);
    tryInitialize(state);
  }
  return sessions.get(userId);
}

app.get('/health', (req, res) => {
  const session = getSession(req);
  res.json({
    ok: true,
    userId: session.userId,
    ready: session.ready,
    hasQr: !!session.lastQr,
  });
});

app.get('/qr', async (req, res) => {
  const session = getSession(req);
  if (!session.lastQr) {
    return res.json({ ready: session.ready, qr: null, userId: session.userId });
  }

  const dataUrl = await qrcode.toDataURL(session.lastQr, { width: 280, margin: 1 });
  return res.json({ ready: session.ready, qr: dataUrl, userId: session.userId });
});

app.get('/chats', async (req, res) => {
  const session = getSession(req);
  if (!session.ready) {
    return res.status(409).json({ message: 'Client not ready yet.' });
  }

  const chats = await session.client.getChats();
  const items = chats
    .filter((chat) => !chat.isGroup)
    .slice(0, 50)
    .map((chat) => ({
      id: chat.id._serialized,
      name: chat.name || chat.formattedTitle || chat.id.user,
      unreadCount: chat.unreadCount || 0,
      timestamp: chat.timestamp || null,
    }));

  res.json({ items });
});

app.get('/messages/:chatId', async (req, res) => {
  const session = getSession(req);
  if (!session.ready) {
    return res.status(409).json({ message: 'Client not ready yet.' });
  }

  const chat = await session.client.getChatById(req.params.chatId);
  const messages = await chat.fetchMessages({ limit: 50 });
  res.json({
    items: messages.map((msg) => ({
      id: msg.id._serialized,
      fromMe: msg.fromMe,
      body: msg.body,
      timestamp: msg.timestamp || null,
    })),
  });
});

app.post('/send', async (req, res) => {
  const session = getSession(req);
  if (!session.ready) {
    return res.status(409).json({ message: 'Client not ready yet.' });
  }

  const to = String(req.body.to || '').trim();
  const message = String(req.body.message || '').trim();

  if (!to || !message) {
    return res.status(422).json({ message: 'to and message are required.' });
  }

  const normalized = normalizeRecipient(to);
  const candidateIds = buildCandidateIds(normalized);

  let lastError = null;
  for (const candidate of candidateIds) {
    try {
      const result = await session.client.sendMessage(candidate, message);
      return res.json({
        success: true,
        id: result.id._serialized,
        to: candidate,
      });
    } catch (error) {
      lastError = error;
    }
  }

  return res.status(422).json({
    success: false,
    message: lastError?.message || 'Failed to send message.',
    to: candidateIds,
  });
});

function normalizeRecipient(value) {
  const raw = String(value || '').trim();
  if (raw.includes('@')) return raw;
  return `${raw.replace(/\D+/g, '')}@c.us`;
}

function buildCandidateIds(recipient) {
  const ids = [recipient];
  const user = recipient.split('@')[0].replace(/\D+/g, '');
  if (!user) return ids;

  if (!ids.includes(`${user}@c.us`)) ids.push(`${user}@c.us`);
  if (!ids.includes(`${user}@s.whatsapp.net`)) ids.push(`${user}@s.whatsapp.net`);

  return Array.from(new Set(ids));
}

const port = Number(process.env.WA_WEBJS_PORT || 3001);
app.listen(port, () => {
  console.log(`[wa-webjs] API server on http://127.0.0.1:${port}`);
});
