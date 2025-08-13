// Import the functions you need from the SDKs
import { initializeApp } from "firebase/app";
import { getMessaging, getToken, onMessage } from "firebase/messaging";

// Firebase configuration
const firebaseConfig = {
    apiKey: "{{ config('services.firebase.api_key') }}",
    authDomain: "{{ config('services.firebase.auth_domain') }}",
    projectId: "{{ config('services.firebase.project_id') }}",
    storageBucket: "{{ config('services.firebase.storage_bucket') }}",
    messagingSenderId: "{{ config('services.firebase.messaging_sender_id') }}",
    appId: "{{ config('services.firebase.app_id') }}",
};

// Initialize Firebase
const app = initializeApp(firebaseConfig);

// Initialize Firebase Cloud Messaging and get a reference to the service
const messaging = getMessaging(app);

// Function to request notification permission and get token
export async function requestNotificationPermission() {
    try {
        const permission = await Notification.requestPermission();
        if (permission === "granted") {
            console.log("Notification permission granted.");

            const token = await getToken(messaging, {
                vapidKey: "{{ config('services.firebase.vapid_key') }}",
            });

            if (token) {
                console.log("FCM Token:", token);
                return token;
            } else {
                console.log("No registration token available.");
                return null;
            }
        } else {
            console.log("Unable to get permission to notify.");
            return null;
        }
    } catch (error) {
        console.error("An error occurred while retrieving token. ", error);
        return null;
    }
}

// Handle foreground messages
export function setupMessageListener() {
    onMessage(messaging, (payload) => {
        console.log("Message received in foreground: ", payload);

        // Customize notification here
        const notificationTitle = payload.notification.title;
        const notificationOptions = {
            body: payload.notification.body,
            icon: payload.notification.icon || "/admin-assets/img/logo.png",
            badge: "/admin-assets/img/badge.png",
            tag: payload.data?.type || "admin-notification",
            requireInteraction: true,
            data: payload.data,
        };

        // Show notification
        if (Notification.permission === "granted") {
            const notification = new Notification(
                notificationTitle,
                notificationOptions
            );

            notification.onclick = function (event) {
                event.preventDefault();
                window.focus();

                // Handle notification click based on type
                if (payload.data?.url) {
                    window.location.href = payload.data.url;
                }

                notification.close();
            };

            // Auto-close after 5 seconds
            setTimeout(() => {
                notification.close();
            }, 5000);
        }
    });
}

export { messaging };
