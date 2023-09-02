//const http = require('http');
//const httpServer = require('http-server');
import http from 'http'
import httpServer from 'http-server'

// Configuration
const targetPort = 8000; // The port your application is running on
const customPort = 8090; // The port for the custom server
const allowOriginHeader = '*'; // Adjust this to the desired value

// Create the custom server
const customServer = http.createServer((req, res) => {
  // Set CORS headers
  res.setHeader('Access-Control-Allow-Origin', allowOriginHeader);
  // You can add more headers as needed

  // Handle requests to static assets
  if (req.url.startsWith('/FILING_USER')) {
    // Serve the asset using http-server
    httpServer.handle(req, res, { root: './', cors: true }); // Serve from the root directory
  } else {
    // Proxy other requests to the target port
    proxy.web(req, res, { target: `http://127.0.0.1:${targetPort}` });
  }
});

// Start the custom server
customServer.listen(customPort, () => {
  console.log(`Custom server is listening on port ${customPort}`);
});

// Start the static HTTP server (if needed)
httpServer.createServer({
  root: '.', // The directory containing your static files
}).listen(targetPort, () => {
  console.log(`Static server is listening on port ${targetPort}`);
});
