const http = require('http');
const fs = require('fs');
const path = require('path');
const zlib = require('zlib');

const projectRoot = path.join(__dirname, '..');
const buildRoot = path.join(projectRoot, 'www');
const portArgument = process.argv.indexOf('--port');
const port = Number(portArgument >= 0 ? process.argv[portArgument + 1] : 4200);
const contentTypes = {
  '.css': 'text/css; charset=utf-8',
  '.html': 'text/html; charset=utf-8',
  '.ico': 'image/x-icon',
  '.js': 'text/javascript; charset=utf-8',
  '.json': 'application/json; charset=utf-8',
  '.png': 'image/png',
  '.svg': 'image/svg+xml',
  '.webp': 'image/webp',
};

function isMobileOrTablet(request) {
  const userAgent = request.headers['user-agent'] || '';
  const platformHint = request.headers['sec-ch-ua-platform'] || '';
  const clientHint = request.headers['sec-ch-ua-mobile'];

  if (/Windows Phone|Windows Mobile/i.test(userAgent)) return true;
  if (/Android|iPad|iPhone|iPod|Tablet|PlayBook|Silk|Kindle/i.test(userAgent)) return true;
  if (/Windows NT|Win32|Win64/i.test(userAgent) || /Windows/i.test(platformHint)) return false;

  return clientHint === '?1' || /Mobile/i.test(userAgent);
}

function proxyPhp(request, response) {
  const isJsonMapRequest = /^\/capston\/map-editor\/map\.php(?:\?|$)/.test(request.url);
  const isCapsRequest = /^\/caps\//.test(request.url);
  const isAuthApiRequest = /^\/(?:capston|caps)\/api\/index\.php(?:\?|$)/.test(request.url);
  const forward = (requestPath, allowWorkspaceFallback) => {
    const upstream = http.request({
      hostname: '127.0.0.1',
      port: 80,
      method: request.method,
      path: requestPath,
      headers: {
        ...request.headers,
        host: '127.0.0.1',
        'x-forwarded-host': request.headers['x-forwarded-host'] || request.headers.host || '',
        'x-forwarded-proto': request.headers['x-forwarded-proto'] || 'https',
      },
    }, (upstreamResponse) => {
      if (allowWorkspaceFallback && isJsonMapRequest && upstreamResponse.statusCode === 404) {
        upstreamResponse.resume();
        const workspaceDirectory = path.basename(path.resolve(projectRoot, '..'));
        forward(request.url.replace(/^\/capston/, `/${workspaceDirectory}`), false);
        return;
      }

      response.writeHead(upstreamResponse.statusCode || 502, upstreamResponse.headers);
      upstreamResponse.pipe(response);
    });

    upstream.on('error', () => {
      if (!response.headersSent) response.writeHead(502, { 'Content-Type': 'text/plain; charset=utf-8' });
      response.end('PHP server is unavailable.');
    });
    request.pipe(upstream);
  };

  const phpPath = request.url.startsWith('/capston/')
    ? request.url.replace(/^\/capston/, '/checking')
    : request.url;
  forward(phpPath, !isAuthApiRequest);
}

http.createServer((request, response) => {
  if (request.url.startsWith('/capston/') || request.url.startsWith('/caps/') || /^\/checking\/(?:map\.php|map-editor\/map\.php)(?:\?|$)/.test(request.url) || /^\/navigation(?:\/|\?|$)/.test(request.url)) {
    proxyPhp(request, response);
    return;
  }

  let pathname;
  let parsedUrl;
  try {
    parsedUrl = new URL(request.url, 'http://localhost');
    pathname = decodeURIComponent(parsedUrl.pathname);
  } catch {
    response.writeHead(400).end();
    return;
  }

  if (pathname === '/' && !isMobileOrTablet(request)) {
    response.writeHead(302, {
      Location: `/navigation/index.php${parsedUrl.search}`,
      'Cache-Control': 'no-store',
      Vary: 'User-Agent, Sec-CH-UA-Mobile',
    });
    response.end();
    return;
  }

  const requestedPath = path.resolve(buildRoot, `.${pathname}`);
  if (requestedPath !== buildRoot && !requestedPath.startsWith(`${buildRoot}${path.sep}`)) {
    response.writeHead(403).end();
    return;
  }

  const filePath = fs.existsSync(requestedPath) && fs.statSync(requestedPath).isFile()
    ? requestedPath
    : path.join(buildRoot, 'index.html');
  const fileName = path.basename(filePath);
  const isFingerprintedAsset = /-[A-Z0-9_-]{8,}\.(?:js|css)$/i.test(fileName);
  const headers = {
    'Content-Type': contentTypes[path.extname(filePath)] || 'application/octet-stream',
    'Cache-Control': fileName === 'index.html' || !isFingerprintedAsset
      ? 'no-cache'
      : 'public, max-age=31536000, immutable',
  };
  if (pathname === '/') {
    headers['Cache-Control'] = 'private, no-store';
    headers.Vary = 'User-Agent, Sec-CH-UA-Mobile';
  }
  const stream = fs.createReadStream(filePath);

  if (/\.(?:js|css|svg)$/.test(filePath) && /\bgzip\b/.test(request.headers['accept-encoding'] || '')) {
    headers['Content-Encoding'] = 'gzip';
    headers.Vary = 'Accept-Encoding';
    response.writeHead(200, headers);
    stream.pipe(zlib.createGzip({ level: zlib.constants.Z_BEST_SPEED })).pipe(response);
  } else {
    response.writeHead(200, headers);
    stream.pipe(response);
  }
}).listen(port, '0.0.0.0', () => {
  console.log(`Optimized app server listening on http://localhost:${port}`);
});