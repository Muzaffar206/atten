<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Website</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            padding: 20px;
            text-align: center;
        }
        h1 {
            color: #333;
        }
        p {
            color: #666;
        }
        a {
            color: #0066cc;
            text-decoration: none;
        }
    </style>
</head>
<body>
    <h1>Welcome to My Website</h1>
    <p>This is the main page content.</p>

    <script>
    if ('serviceWorker' in navigator) {
        window.addEventListener('load', function() {
            navigator.serviceWorker.register('service-worker.js').then(function(registration) {
                console.log('Service Worker registered with scope:', registration.scope);
            }).catch(function(error) {
                console.log('Service Worker registration failed:', error);
            });
        });
    }
    </script>

    <script>
    const CACHE_NAME = 'offline-cache-v1';
    const OFFLINE_URL = 'offline.php';

    self.addEventListener('install', function(event) {
        event.waitUntil(
            caches.open(CACHE_NAME).then(function(cache) {
                return cache.addAll([OFFLINE_URL]);
            })
        );
    });

    self.addEventListener('fetch', function(event) {
        if (event.request.mode === 'navigate') {
            event.respondWith(
                fetch(event.request).catch(function() {
                    return caches.match(OFFLINE_URL);
                })
            );
        } else {
            event.respondWith(
                caches.match(event.request).then(function(response) {
                    return response || fetch(event.request);
                })
            );
        }
    });
    </script>
</body>
</html>
