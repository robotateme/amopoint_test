import { createServer } from 'node:http';
import { Server } from 'socket.io';

const host = process.env.SOCKET_IO_HOST || '127.0.0.1';
const port = Number(process.env.SOCKET_IO_PORT || 6001);
const internalToken = process.env.SOCKET_IO_INTERNAL_TOKEN || '';
const allowedOrigins = (process.env.SOCKET_IO_ALLOWED_ORIGINS || '*')
    .split(',')
    .map((origin) => origin.trim())
    .filter(Boolean);

const httpServer = createServer((request, response) => {
    if (request.method === 'GET' && request.url === '/health') {
        response.writeHead(200, { 'Content-Type': 'application/json' });
        response.end(JSON.stringify({ ok: true }));
        return;
    }

    if (request.method !== 'POST' || request.url !== '/emit') {
        response.writeHead(404, { 'Content-Type': 'application/json' });
        response.end(JSON.stringify({ message: 'Not found' }));
        return;
    }

    if (internalToken !== '' && request.headers['x-internal-token'] !== internalToken) {
        response.writeHead(401, { 'Content-Type': 'application/json' });
        response.end(JSON.stringify({ message: 'Unauthorized' }));
        return;
    }

    let body = '';

    request.on('data', (chunk) => {
        body += chunk;
    });

    request.on('end', () => {
        try {
            const payload = JSON.parse(body || '{}');
            const event = String(payload.event || '');

            if (event === '') {
                response.writeHead(422, { 'Content-Type': 'application/json' });
                response.end(JSON.stringify({ message: 'Missing event' }));
                return;
            }

            io.emit(event, payload.data || {});
            response.writeHead(202, { 'Content-Type': 'application/json' });
            response.end(JSON.stringify({ ok: true }));
        } catch {
            response.writeHead(400, { 'Content-Type': 'application/json' });
            response.end(JSON.stringify({ message: 'Invalid JSON' }));
        }
    });
});

const io = new Server(httpServer, {
    cors: {
        origin: allowedOrigins.includes('*') ? '*' : allowedOrigins,
        methods: ['GET', 'POST'],
    },
});

io.on('connection', (socket) => {
    socket.emit('visit-statistics:ready');
});

httpServer.listen(port, host, () => {
    console.log(`Socket.IO server listening on ${host}:${port}`);
});
