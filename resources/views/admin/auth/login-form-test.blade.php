<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Login Form Test with CSRF</title>
    <style>
        body { font-family: Arial, sans-serif; padding: 20px; max-width: 600px; margin: 0 auto; }
        .form-group { margin-bottom: 15px; }
        label { display: block; margin-bottom: 5px; font-weight: bold; }
        input { padding: 10px; width: 100%; border: 1px solid #ccc; border-radius: 4px; }
        button { padding: 12px 24px; background: #007cba; color: white; border: none; cursor: pointer; border-radius: 4px; }
        button:disabled { opacity: 0.6; cursor: not-allowed; }
        .status { margin-top: 15px; padding: 10px; border-radius: 4px; }
        .success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .error { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        .info { background: #e2e3e5; color: #383d41; border: 1px solid #d6d8db; }
    </style>
</head>
<body>
    <h1>🧪 Login Form Test with CSRF</h1>

    <div class="info status">
        <strong>Test Credentials:</strong><br>
        Email: admin@connectapp.com<br>
        Password: admin123
    </div>

    <form id="loginForm" action="{{ route('admin.auth.login.post') }}" method="POST">
        @csrf

        <div class="form-group">
            <label for="email">Email Address:</label>
            <input type="email" id="email" name="email" value="admin@connectapp.com" required>
        </div>

        <div class="form-group">
            <label for="password">Password:</label>
            <input type="password" id="password" name="password" value="admin123" required>
        </div>

        <button type="submit" id="submitBtn">Test Login Submission</button>
    </form>

    <div id="status" class="status info">Ready to test... Click the button to submit the form.</div>

    <script>
        const form = document.getElementById('loginForm');
        const statusDiv = document.getElementById('status');
        const submitBtn = document.getElementById('submitBtn');

        form.addEventListener('submit', function(e) {
            // Don't prevent default - let it submit normally
            statusDiv.innerHTML = '⏳ Form submitting to: ' + form.action;
            statusDiv.className = 'status info';

            submitBtn.disabled = true;
            submitBtn.textContent = 'Submitting...';

            console.log('Form submitted to:', form.action);
            console.log('Form method:', form.method);
            console.log('CSRF token present:', !!document.querySelector('input[name="_token"]'));
        });

        // Log form details on page load
        console.log('Login test form loaded');
        console.log('Action URL:', form.action);
        console.log('Method:', form.method);
        console.log('CSRF token:', document.querySelector('input[name="_token"]')?.value);
    </script>
</body>
</html>
