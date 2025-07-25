<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Debug Streaming Test</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <style>
        body { font-family: Arial, sans-serif; padding: 20px; background: #f5f5f5; }
        .container { max-width: 800px; margin: 0 auto; }
        .section { background: white; padding: 20px; margin: 20px 0; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        .btn { background: #007bff; color: white; border: none; padding: 10px 20px; border-radius: 5px; cursor: pointer; margin: 5px; }
        .btn:hover { background: #0056b3; }
        .response { background: #f8f9fa; border: 1px solid #ddd; padding: 15px; margin: 10px 0; border-radius: 4px; white-space: pre-wrap; font-family: monospace; font-size: 12px; max-height: 300px; overflow-y: auto; }
        .error { background: #f8d7da; border-color: #f5c6cb; color: #721c24; }
        .success { background: #d4edda; border-color: #c3e6cb; color: #155724; }
        input { width: 300px; padding: 8px; margin: 5px; border: 1px solid #ddd; border-radius: 4px; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔧 Streaming API Debug Interface</h1>
        
        <div class="section">
            <h3>Step 1: Login Test</h3>
            <input type="text" id="email" value="admin@test.com" placeholder="Email">
            <input type="password" id="password" value="password123" placeholder="Password">
            <button class="btn" onclick="testLogin()">Test Login</button>
            <div id="login-status"></div>
        </div>

        <div class="section">
            <h3>Step 2: API Tests</h3>
            <button class="btn" onclick="testStreamCreate()">Test Create Stream</button>
            <button class="btn" onclick="testStreamList()">Test List Streams</button>
            <button class="btn" onclick="testStreamLatest()">Test Latest Streams</button>
        </div>

        <div class="section">
            <h3>API Response</h3>
            <div id="response" class="response">Ready for testing...</div>
        </div>

        <div class="section">
            <h3>Debug Info</h3>
            <button class="btn" onclick="checkCSRF()">Check CSRF</button>
            <button class="btn" onclick="checkRoutes()">Check Routes</button>
            <div id="debug" class="response">Debug info will appear here...</div>
        </div>
    </div>

    <script>
        let token = '';
        
        function showResponse(data, type = '') {
            const div = document.getElementById('response');
            div.textContent = typeof data === 'string' ? data : JSON.stringify(data, null, 2);
            div.className = 'response ' + type;
        }

        function showDebug(data) {
            const div = document.getElementById('debug');
            div.textContent = typeof data === 'string' ? data : JSON.stringify(data, null, 2);
        }

        function showLoginStatus(data, type = '') {
            const div = document.getElementById('login-status');
            div.innerHTML = `<div class="response ${type}">${typeof data === 'string' ? data : JSON.stringify(data, null, 2)}</div>`;
        }

        async function makeRequest(method, url, data = null) {
            const headers = {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
            };
            
            if (token) {
                headers['Authorization'] = `Bearer ${token}`;
            }
            
            const options = { method, headers };
            if (data) options.body = JSON.stringify(data);
            
            try {
                showResponse('Loading...', '');
                const response = await fetch(url, options);
                const result = await response.json();
                
                if (response.ok) {
                    showResponse(result, 'success');
                    return result;
                } else {
                    showResponse(result, 'error');
                    return null;
                }
            } catch (error) {
                showResponse('Error: ' + error.message, 'error');
                return null;
            }
        }

        async function testLogin() {
            const email = document.getElementById('email').value;
            const password = document.getElementById('password').value;
            
            showLoginStatus('Attempting login...', '');
            
            const result = await fetch('/api/v1/login', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
                },
                body: JSON.stringify({ email, password })
            });
            
            const data = await result.json();
            
            if (result.ok && data.success) {
                token = data.data.token;
                showLoginStatus(`✅ Login successful! Token: ${token.substring(0, 20)}...`, 'success');
            } else {
                showLoginStatus(`❌ Login failed: ${JSON.stringify(data)}`, 'error');
            }
        }

        async function testStreamCreate() {
            if (!token) {
                showResponse('Please login first!', 'error');
                return;
            }

            await makeRequest('POST', '/api/v1/streams', {
                title: 'Debug Test Stream',
                description: 'Testing stream creation',
                is_paid: false
            });
        }

        async function testStreamList() {
            if (!token) {
                showResponse('Please login first!', 'error');
                return;
            }

            await makeRequest('GET', '/api/v1/streams/my-streams');
        }

        async function testStreamLatest() {
            await makeRequest('GET', '/api/v1/streams/latest');
        }

        function checkCSRF() {
            const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
            showDebug(`CSRF Token: ${csrf || 'NOT FOUND'}`);
        }

        async function checkRoutes() {
            try {
                const response = await fetch('/api/v1/streams/latest');
                showDebug(`Route test - Status: ${response.status}, OK: ${response.ok}`);
            } catch (error) {
                showDebug(`Route test error: ${error.message}`);
            }
        }
    </script>
</body>
</html>
