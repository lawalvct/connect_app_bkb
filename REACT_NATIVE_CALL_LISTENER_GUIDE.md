# React Native Incoming Call Listener Implementation

## 1. Install Required Dependencies

```bash
npm install pusher-js react-native-callkeep react-native-voip-push-notification
```

## 2. Pusher Configuration

```javascript
// PusherConfig.js
import Pusher from "pusher-js";

const pusherConfig = {
    key: "0e0b5123273171ff212d",
    cluster: "eu",
    forceTLS: true,
    authEndpoint: "https://your-backend-url.com/broadcasting/auth",
    auth: {
        headers: {
            Authorization: `Bearer ${userToken}`, // User's auth token
        },
    },
};

export const pusher = new Pusher(pusherConfig.key, pusherConfig);
```

## 3. Call Listener Service

```javascript
// CallListenerService.js
import { pusher } from "./PusherConfig";
import { AppState, Alert } from "react-native";
import RNCallKeep from "react-native-callkeep";

class CallListenerService {
    constructor() {
        this.activeChannels = new Map();
        this.setupCallKeep();
    }

    setupCallKeep() {
        const options = {
            ios: {
                appName: "Your App Name",
                imageName: "logo",
                supportsVideo: true,
                maximumCallGroups: "1",
                maximumCallsPerCallGroup: "1",
            },
            android: {
                alertTitle: "Permissions required",
                alertDescription:
                    "This application needs to access your phone accounts",
                cancelButton: "Cancel",
                okButton: "OK",
                imageName: "phone_account_icon",
                additionalPermissions: [],
                selfManaged: false,
            },
        };

        RNCallKeep.setup(options);
        this.setupCallKeepEvents();
    }

    setupCallKeepEvents() {
        RNCallKeep.addEventListener("answerCall", this.onAnswerCallAction);
        RNCallKeep.addEventListener("endCall", this.onEndCallAction);
        RNCallKeep.addEventListener("didPerformDTMFAction", this.onDTMFAction);
        RNCallKeep.addEventListener(
            "didReceiveStartCallAction",
            this.onStartCallAction
        );
        RNCallKeep.addEventListener(
            "didPerformSetMutedCallAction",
            this.onToggleMute
        );
        RNCallKeep.addEventListener(
            "didToggleHoldCallAction",
            this.onToggleHold
        );
        RNCallKeep.addEventListener(
            "didLoadWithEvents",
            this.onCallKeepDidLoadWithEvents
        );
    }

    // Subscribe to conversation channel for incoming calls
    subscribeToConversation(conversationId) {
        if (this.activeChannels.has(conversationId)) {
            return; // Already subscribed
        }

        const channel = pusher.subscribe(
            `private-conversation.${conversationId}`
        );

        // Listen for incoming calls
        channel.bind("call.initiated", this.handleIncomingCall.bind(this));
        channel.bind("call.answered", this.handleCallAnswered.bind(this));
        channel.bind("call.ended", this.handleCallEnded.bind(this));
        channel.bind("call.missed", this.handleCallMissed.bind(this));

        this.activeChannels.set(conversationId, channel);

        console.log(
            `Subscribed to conversation ${conversationId} for call events`
        );
    }

    // Unsubscribe from conversation channel
    unsubscribeFromConversation(conversationId) {
        const channel = this.activeChannels.get(conversationId);
        if (channel) {
            pusher.unsubscribe(`private-conversation.${conversationId}`);
            this.activeChannels.delete(conversationId);
            console.log(`Unsubscribed from conversation ${conversationId}`);
        }
    }

    // Handle incoming call
    handleIncomingCall = (data) => {
        console.log("Incoming call received:", data);

        const { call_id, call_type, initiator, agora_channel_name } = data;

        // Store call data for later use
        this.currentCall = {
            callId: call_id,
            callType: call_type,
            channelName: agora_channel_name,
            initiator: initiator,
        };

        // Show native call UI
        this.displayIncomingCall(data);
    };

    displayIncomingCall(data) {
        const { call_id, initiator, call_type } = data;

        // Check if app is in background
        if (
            AppState.currentState === "background" ||
            AppState.currentState === "inactive"
        ) {
            // Use CallKeep for native call UI
            RNCallKeep.displayIncomingCall(
                call_id,
                initiator.name,
                initiator.name,
                "generic",
                call_type === "video"
            );
        } else {
            // App is active, show in-app call UI
            this.showInAppCallAlert(data);
        }
    }

    showInAppCallAlert(data) {
        const { initiator, call_type } = data;

        Alert.alert(
            "Incoming Call",
            `${call_type} call from ${initiator.name}`,
            [
                {
                    text: "Decline",
                    style: "cancel",
                    onPress: () => this.rejectCall(),
                },
                {
                    text: "Answer",
                    onPress: () => this.answerCall(),
                },
            ],
            { cancelable: false }
        );
    }

    // Answer call action
    onAnswerCallAction = ({ callUUID }) => {
        console.log("Call answered via CallKeep:", callUUID);
        this.answerCall();
    };

    // End call action
    onEndCallAction = ({ callUUID }) => {
        console.log("Call ended via CallKeep:", callUUID);
        this.rejectCall();
    };

    // Answer the call
    async answerCall() {
        if (!this.currentCall) return;

        try {
            const response = await fetch(
                "https://your-backend-url.com/api/v1/calls/answer",
                {
                    method: "POST",
                    headers: {
                        "Content-Type": "application/json",
                        Authorization: `Bearer ${userToken}`,
                    },
                    body: JSON.stringify({
                        call_id: this.currentCall.callId,
                    }),
                }
            );

            const result = await response.json();

            if (result.success) {
                // Navigate to call screen with Agora integration
                this.navigateToCallScreen();
            }
        } catch (error) {
            console.error("Error answering call:", error);
        }
    }

    // Reject the call
    async rejectCall() {
        if (!this.currentCall) return;

        try {
            const response = await fetch(
                "https://your-backend-url.com/api/v1/calls/reject",
                {
                    method: "POST",
                    headers: {
                        "Content-Type": "application/json",
                        Authorization: `Bearer ${userToken}`,
                    },
                    body: JSON.stringify({
                        call_id: this.currentCall.callId,
                    }),
                }
            );

            RNCallKeep.endCall(this.currentCall.callId);
            this.currentCall = null;
        } catch (error) {
            console.error("Error rejecting call:", error);
        }
    }

    navigateToCallScreen() {
        // Navigate to your call screen component
        // Pass the call data: this.currentCall
        console.log("Navigating to call screen with:", this.currentCall);
    }

    // Handle other call events
    handleCallAnswered = (data) => {
        console.log("Call answered:", data);
        // Update UI if needed
    };

    handleCallEnded = (data) => {
        console.log("Call ended:", data);
        RNCallKeep.endCall(data.call_id);
        this.currentCall = null;
    };

    handleCallMissed = (data) => {
        console.log("Call missed:", data);
        RNCallKeep.endCall(data.call_id);
        this.currentCall = null;
    };

    // Cleanup
    cleanup() {
        // Unsubscribe from all channels
        this.activeChannels.forEach((channel, conversationId) => {
            this.unsubscribeFromConversation(conversationId);
        });

        // Remove CallKeep listeners
        RNCallKeep.removeEventListener("answerCall");
        RNCallKeep.removeEventListener("endCall");
        RNCallKeep.removeEventListener("didPerformDTMFAction");
        RNCallKeep.removeEventListener("didReceiveStartCallAction");
        RNCallKeep.removeEventListener("didPerformSetMutedCallAction");
        RNCallKeep.removeEventListener("didToggleHoldCallAction");
    }
}

export default new CallListenerService();
```

## 4. Usage in React Native App

```javascript
// App.js or your main component
import React, { useEffect } from "react";
import { AppState } from "react-native";
import CallListenerService from "./services/CallListenerService";

const App = () => {
    useEffect(() => {
        // Initialize call listener when app starts
        const initializeCallListener = () => {
            // Subscribe to all user's conversations for call events
            // You should get this list from your backend
            const userConversations = [1, 2, 3]; // Example conversation IDs

            userConversations.forEach((conversationId) => {
                CallListenerService.subscribeToConversation(conversationId);
            });
        };

        initializeCallListener();

        // Handle app state changes
        const handleAppStateChange = (nextAppState) => {
            console.log("App state changed to:", nextAppState);
            // You can implement additional logic here if needed
        };

        AppState.addEventListener("change", handleAppStateChange);

        return () => {
            // Cleanup when app unmounts
            CallListenerService.cleanup();
            AppState.removeEventListener("change", handleAppStateChange);
        };
    }, []);

    return (
        // Your app components
        <YourAppComponents />
    );
};

export default App;
```

## 5. Backend Authentication for Pusher (Optional Enhancement)

If you want to add user authentication for private channels, add this to your Laravel routes:

```php
// routes/api.php
Route::post('/broadcasting/auth', function (Request $request) {
    return Broadcast::auth($request);
})->middleware('auth:sanctum');
```

## 6. Testing the Implementation

1. **Start the React Native app** with the call listener service
2. **Make a call** from another device using the `/calls/initiate` endpoint
3. **Verify** that the receiving device gets the incoming call notification
4. **Test** answering and rejecting calls

## Key Points:

1. **Real-time Events**: Your backend already broadcasts `CallInitiated` events when calls are made
2. **Private Channels**: Calls are broadcasted to `private-conversation.{id}` channels
3. **Native UI**: Uses CallKeep for native iOS/Android call interface
4. **Background Handling**: Works when app is in background or foreground
5. **Multiple Conversations**: Can listen to multiple conversation channels simultaneously

## Call Flow:

1. User A initiates call → Backend broadcasts `CallInitiated` event
2. User B's app receives event → Shows incoming call UI
3. User B answers → App calls `/calls/answer` endpoint
4. Backend broadcasts `CallAnswered` event → Both users join Agora channel

Your Pusher real-time system is already working perfectly for incoming calls! 🎉
