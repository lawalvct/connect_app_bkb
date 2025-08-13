<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Firebase Setup Helper - ConnectApp</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        'primary': '#A20030',
                    }
                }
            }
        }
    </script>
</head>
<body class="bg-gray-50 p-8">
    <div class="max-w-4xl mx-auto">
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-8">
            <h1 class="text-3xl font-bold text-gray-900 mb-6">🔧 Firebase Setup Helper</h1>

            <div class="bg-red-50 border border-red-200 rounded-lg p-4 mb-6">
                <div class="flex items-center">
                    <i class="fas fa-exclamation-triangle text-red-500 mr-3"></i>
                    <div>
                        <h3 class="text-lg font-semibold text-red-900">API Key Not Valid Error</h3>
                        <p class="text-red-700">Your .env file has placeholder values instead of real Firebase configuration.</p>
                    </div>
                </div>
            </div>

            <div class="space-y-6">
                <div class="bg-blue-50 border border-blue-200 rounded-lg p-6">
                    <h2 class="text-xl font-semibold text-blue-900 mb-4">Step 1: Go to Firebase Console</h2>
                    <div class="space-y-3">
                        <p class="text-blue-800">Visit the Firebase Console and select your project:</p>
                        <a href="https://console.firebase.google.com/project/connect-app-fbaca" target="_blank"
                           class="inline-block px-4 py-2 bg-primary text-white rounded-lg hover:bg-primary/90">
                            Open Firebase Console for connect-app-fbaca
                        </a>
                        <p class="text-sm text-blue-600">If the link doesn't work, go to https://console.firebase.google.com/ and select "connect-app-fbaca"</p>
                    </div>
                </div>

                <div class="bg-green-50 border border-green-200 rounded-lg p-6">
                    <h2 class="text-xl font-semibold text-green-900 mb-4">Step 2: Add Web App (if not exists)</h2>
                    <div class="space-y-3 text-green-800">
                        <p>1. In Firebase Console, click the <strong>gear icon</strong> → <strong>Project settings</strong></p>
                        <p>2. Scroll down to <strong>"Your apps"</strong> section</p>
                        <p>3. If you see a web app (🌐), click on it. If not, click <strong>"Add app"</strong> → <strong>"Web"</strong></p>
                        <p>4. Give it a name like "ConnectApp Web" and click <strong>"Register app"</strong></p>
                    </div>
                </div>

                <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-6">
                    <h2 class="text-xl font-semibold text-yellow-900 mb-4">Step 3: Copy Configuration</h2>
                    <div class="space-y-3 text-yellow-800">
                        <p>You'll see a configuration object like this:</p>
                        <div class="bg-gray-900 text-green-400 rounded p-4 font-mono text-sm">
const firebaseConfig = {<br>
&nbsp;&nbsp;apiKey: "AIzaSyB8X9Z4...",<br>
&nbsp;&nbsp;authDomain: "connect-app-fbaca.firebaseapp.com",<br>
&nbsp;&nbsp;projectId: "connect-app-fbaca",<br>
&nbsp;&nbsp;storageBucket: "connect-app-fbaca.appspot.com",<br>
&nbsp;&nbsp;messagingSenderId: "123456789012",<br>
&nbsp;&nbsp;appId: "1:123456789012:web:abcdef123456789"<br>
};
                        </div>
                    </div>
                </div>

                <div class="bg-purple-50 border border-purple-200 rounded-lg p-6">
                    <h2 class="text-xl font-semibold text-purple-900 mb-4">Step 4: Update Your .env File</h2>
                    <div class="space-y-3 text-purple-800">
                        <p>Replace these lines in your <code>.env</code> file with the real values:</p>
                        <div class="bg-gray-900 text-green-400 rounded p-4 font-mono text-sm">
FIREBASE_API_KEY=AIzaSyB8X9Z4... <span class="text-yellow-400"># Copy from Firebase console</span><br>
FIREBASE_AUTH_DOMAIN=connect-app-fbaca.firebaseapp.com<br>
FIREBASE_PROJECT_ID=connect-app-fbaca<br>
FIREBASE_STORAGE_BUCKET=connect-app-fbaca.appspot.com<br>
FIREBASE_MESSAGING_SENDER_ID=123456789012 <span class="text-yellow-400"># Copy from Firebase console</span><br>
FIREBASE_APP_ID=1:123456789012:web:abcdef123456789 <span class="text-yellow-400"># Copy from Firebase console</span>
                        </div>
                    </div>
                </div>

                <div class="bg-indigo-50 border border-indigo-200 rounded-lg p-6">
                    <h2 class="text-xl font-semibold text-indigo-900 mb-4">Step 5: Get VAPID Key for Push Notifications</h2>
                    <div class="space-y-3 text-indigo-800">
                        <p>1. In Firebase Console → <strong>Project settings</strong> → <strong>"Cloud Messaging"</strong> tab</p>
                        <p>2. Scroll to <strong>"Web Push certificates"</strong></p>
                        <p>3. Click <strong>"Generate key pair"</strong> if you don't have one</p>
                        <p>4. Copy the key and update:</p>
                        <div class="bg-gray-900 text-green-400 rounded p-4 font-mono text-sm">
FIREBASE_VAPID_KEY=BFx8f6tGx... <span class="text-yellow-400"># Your VAPID key</span>
                        </div>
                    </div>
                </div>

                <div class="bg-gray-100 border border-gray-300 rounded-lg p-6">
                    <h2 class="text-xl font-semibold text-gray-900 mb-4">Step 6: Clear Cache & Test</h2>
                    <div class="space-y-3 text-gray-800">
                        <p>After updating your .env file, run these commands:</p>
                        <div class="bg-gray-900 text-green-400 rounded p-4 font-mono text-sm space-y-1">
<div>php artisan config:clear</div>
<div>php artisan firebase:test-config</div>
                        </div>
                        <p class="text-sm">The test command should show real values, not placeholders.</p>
                    </div>
                </div>
            </div>

            <div class="mt-8 p-4 bg-gray-50 rounded-lg">
                <h3 class="font-semibold text-gray-900 mb-2">Quick Links:</h3>
                <div class="space-x-4">
                    <a href="https://console.firebase.google.com/" target="_blank"
                       class="text-blue-600 hover:text-blue-800">Firebase Console</a>
                    <a href="/firebase-test"
                       class="text-green-600 hover:text-green-800">Test Firebase Config</a>
                    <a href="/admin/notifications/subscription"
                       class="text-purple-600 hover:text-purple-800">Admin Subscription</a>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/js/all.min.js"></script>
</body>
</html>
