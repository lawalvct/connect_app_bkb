<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Login & Push Test</title>
  <script src="https://cdn.jsdelivr.net/npm/uuid@9.0.0/dist/umd/uuid.min.js"></script>
  <script src="https://www.gstatic.com/firebasejs/9.23.0/firebase-app-compat.js"></script>
  <script src="https://www.gstatic.com/firebasejs/9.23.0/firebase-messaging-compat.js"></script>
  <style>
    body { font-family: Arial, sans-serif; background: #f7f7f7; }
    .container { max-width: 400px; margin: 40px auto; background: #fff; padding: 24px; border-radius: 8px; box-shadow: 0 2px 8px #0001; }
    input, button { width: 100%; margin: 8px 0; padding: 8px; }
    button { background: #007bff; color: #fff; border: none; border-radius: 4px; cursor: pointer; }
    .result { margin-top: 16px; }
  </style>
</head>
<body>
<div class="container">
  <h2>Login & Push Test</h2>
  <form id="loginForm">
    <input id="email" type="email" placeholder="Email" required>
    <input id="password" type="password" placeholder="Password" required>
    <input id="device_token" placeholder="Device Token (FCM)" required readonly>
    <input id="device_id" placeholder="Device ID (optional)">
    <input id="platform" placeholder="Platform (web/android/ios)" value="web">
    <input id="app_version" placeholder="App Version" value="1.0.0">
    <button type="submit">Login</button>
  </form>
  <div class="result" id="resultBox" style="display:none;">
    <pre id="result"></pre>
  </div>
</div>

<script>
  // --- Firebase config from .env (via Blade) ---
  const firebaseConfig = {
    apiKey: "AIzaSyCR4xsW8SRu599lMbA7yMYLNw8Q87H0GpE",
    authDomain: "connect-app-fbaca.firebaseapp.com",
    projectId: "connect-app-fbaca",
    messagingSenderId: "878521426508",
    appId: "1:878521426508:web:a6af7820b01cc146ad8ae9"
  };
  const vapidKey = "BCJJWDCGMJ6-qYX2lV7SFf4on6bgS9s69fmQtKPH_t6-FyAohWvxOqMXR3JDiUbuOKaN0L8aPvwyyjJWStYDAoY";

  // Initialize Firebase once
  firebase.initializeApp(firebaseConfig);
  const messaging = firebase.messaging();

  // Get or generate device_id
  function getDeviceId() {
    let id = localStorage.getItem('device_id');
    if (!id && window.uuid && typeof window.uuid.v4 === 'function') {
      id = window.uuid.v4();
      localStorage.setItem('device_id', id);
    }
    return id || '';
  }

  // Get FCM token
  async function getFcmToken() {
    try {
      // Ask for browser permission first
      const permission = await Notification.requestPermission();
      if (permission !== 'granted') {
        console.warn("Push permission not granted.");
        return '';
      }
      const token = await messaging.getToken({ vapidKey });
      console.log("FCM Token:", token);
      return token || '';
    } catch (e) {
      console.error("Error getting FCM token:", e);
      return '';
    }
  }

  // On page load, auto-fill device_id and device_token
  document.addEventListener('DOMContentLoaded', async function () {
    document.getElementById('device_id').value = getDeviceId();
    const token = await getFcmToken();
    document.getElementById('device_token').value = token;
  });

  // Handle login form submit
  document.getElementById('loginForm').addEventListener('submit', async function (e) {
    e.preventDefault();
    const payload = {
      email: document.getElementById('email').value,
      password: document.getElementById('password').value,
      remember_me: true,
      device_token: document.getElementById('device_token').value,
      device_id: document.getElementById('device_id').value,
      platform: document.getElementById('platform').value,
      app_version: document.getElementById('app_version').value
    };
    try {
      const res = await fetch('http://localhost:8000/api/v1/login', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(payload)
      });
      const data = await res.json();
      document.getElementById('result').textContent = JSON.stringify(data, null, 2);
      document.getElementById('resultBox').style.display = 'block';
    } catch (e) {
      document.getElementById('result').textContent = 'Error: ' + e;
      document.getElementById('resultBox').style.display = 'block';
    }
  });
</script>
</body>
</html>
