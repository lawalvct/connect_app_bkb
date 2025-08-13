// firebase-messaging-sw.js

// Import Firebase scripts
importScripts(
    "https://www.gstatic.com/firebasejs/9.0.0/firebase-app-compat.js"
);
importScripts(
    "https://www.gstatic.com/firebasejs/9.0.0/firebase-messaging-compat.js"
);

// Firebase configuration - these will be replaced by actual values
const firebaseConfig = {
    apiKey: "your-api-key",
    authDomain: "your-project.firebaseapp.com",
    projectId: "your-project-id",
    storageBucket: "your-project.appspot.com",
    messagingSenderId: "123456789",
    appId: "your-app-id",
};

// Initialize Firebase
firebase.initializeApp(firebaseConfig);

// Retrieve Firebase Messaging object
const messaging = firebase.messaging();

// Handle background messages
messaging.onBackgroundMessage(function (payload) {
    console.log(
        "[firebase-messaging-sw.js] Received background message ",
        payload
    );

    const notificationTitle =
        payload.notification?.title ||
        payload.data?.title ||
        "Admin Notification";
    const notificationOptions = {
        body:
            payload.notification?.body ||
            payload.data?.body ||
            "You have a new notification",
        icon: payload.notification?.icon || "/admin-assets/img/logo.png",
        badge: "/admin-assets/img/badge.png",
        tag: payload.data?.type || "admin-notification",
        requireInteraction: true,
        data: {
            url: payload.data?.url || "/admin/dashboard",
            type: payload.data?.type || "general",
            ...payload.data,
        },
        actions: [
            {
                action: "view",
                title: "View",
                icon: "/admin-assets/img/view-icon.png",
            },
            {
                action: "dismiss",
                title: "Dismiss",
                icon: "/admin-assets/img/dismiss-icon.png",
            },
        ],
    };

    self.registration.showNotification(notificationTitle, notificationOptions);
});

// Handle notification click
self.addEventListener("notificationclick", function (event) {
    console.log("[firebase-messaging-sw.js] Notification click received.");

    event.notification.close();

    if (event.action === "dismiss") {
        return; // Just close the notification
    }

    // Handle notification click
    const urlToOpen = event.notification.data?.url || "/admin/dashboard";

    event.waitUntil(
        clients
            .matchAll({
                type: "window",
            })
            .then(function (clientList) {
                // Check if there's already a window/tab open with the target URL
                for (let i = 0; i < clientList.length; i++) {
                    const client = clientList[i];
                    if (client.url === urlToOpen && "focus" in client) {
                        return client.focus();
                    }
                }

                // If no window/tab is open, open a new one
                if (clients.openWindow) {
                    return clients.openWindow(urlToOpen);
                }
            })
    );
});

// Handle notification close
self.addEventListener("notificationclose", function (event) {
    console.log("[firebase-messaging-sw.js] Notification closed.");

    // Track notification dismissal if needed
    // You can send analytics data here
});
