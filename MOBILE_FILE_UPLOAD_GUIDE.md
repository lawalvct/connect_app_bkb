# Mobile App File Upload Guide

## ⚠️ Critical Issue

Mobile app is sending file **metadata only** (name, size) instead of actual file content:

```json
{
    "type": "audio",
    "message": "undefined",
    "file": {
        "name": "recording-1767911433506.m4a",
        "size": "124.878KB"
    }
}
```

**This will always fail!** The backend needs the actual file data, not just the filename and size.

## 🔧 Quick Fix for React Native

If you're currently sending something like this:

```javascript
// ❌ WRONG - Current broken code
const payload = {
    type: "image",
    file: {
        name: fileInfo.name,
        size: fileInfo.size,
    },
};
```

**Change it to this:**

```javascript
// ✅ CORRECT - Read file as base64
import * as FileSystem from "expo-file-system"; // or use react-native-fs

const base64 = await FileSystem.readAsStringAsync(fileUri, {
    encoding: FileSystem.EncodingType.Base64,
});

const payload = {
    type: "image",
    file: `data:image/jpeg;base64,${base64}`, // Send actual file content
    message: "Optional caption",
};
```

## ✅ Correct Implementation

You **MUST** send the actual file content using one of these methods:

### Method 1: Multipart/Form-Data Upload (Recommended for Binary Files)

```javascript
// React Native / JavaScript Example
const formData = new FormData();
formData.append("type", "audio");
formData.append("message", "Voice message caption"); // Optional
formData.append("file", {
    uri: fileUri, // Local file URI
    type: "audio/m4a",
    name: "recording-1767911433506.m4a",
});

fetch(`${API_URL}/api/v1/conversations/${conversationId}/messages`, {
    method: "POST",
    headers: {
        Authorization: `Bearer ${token}`,
        "Content-Type": "multipart/form-data",
    },
    body: formData,
});
```

### Method 2: Base64 Encoded Upload (For JSON API)

```javascript
// React Native / JavaScript Example
import RNFS from "react-native-fs";

// Read file as base64
const base64File = await RNFS.readFile(fileUri, "base64");

// Option A: Send with data URI prefix (backend will extract mime type)
const payload = {
    type: "audio",
    message: "Voice message caption", // Optional
    file: `data:audio/m4a;base64,${base64File}`,
    file_name: "recording-1767911433506.m4a", // Optional, for original filename
};

// Option B: Send pure base64 (backend will use default mime type for audio)
const payload = {
    type: "audio",
    message: "Voice message caption",
    file: base64File, // Pure base64 string without prefix
    file_name: "recording-1767911433506.m4a",
};

fetch(`${API_URL}/api/v1/conversations/${conversationId}/messages`, {
    method: "POST",
    headers: {
        Authorization: `Bearer ${token}`,
        "Content-Type": "application/json",
    },
    body: JSON.stringify(payload),
});
```

### Method 3: Flutter Example

```dart
// Using Dio package
import 'package:dio/dio.dart';
import 'dart:io';

// Multipart upload
FormData formData = FormData.fromMap({
  'type': 'audio',
  'message': 'Voice message caption',
  'file': await MultipartFile.fromFile(
    filePath,
    filename: 'recording.m4a',
    contentType: MediaType('audio', 'm4a'),
  ),
});

Response response = await dio.post(
  '/api/v1/conversations/$conversationId/messages',
  data: formData,
  options: Options(
    headers: {
      'Authorization': 'Bearer $token',
    },
  ),
);

// OR Base64 upload
import 'dart:convert';
import 'dart:io';

File file = File(filePath);
List<int> fileBytes = await file.readAsBytes();
String base64File = base64Encode(fileBytes);

Map<String, dynamic> payload = {
  'type': 'audio',
  'message': 'Voice message caption',
  'file': base64File,
  'file_name': 'recording.m4a',
};

Response response = await dio.post(
  '/api/v1/conversations/$conversationId/messages',
  data: payload,
  options: Options(
    headers: {
      'Authorization': 'Bearer $token',
      'Content-Type': 'application/json',
    },
  ),
);
```

## Supported File Types

### Audio (type: "audio")

-   mp3, wav, aac, ogg, m4a, webm
-   Max size: 50MB

### Image (type: "image")

-   jpg, jpeg, png, gif, webp
-   Max size: 50MB
-   Max dimensions: 8000x8000 (increased to support high-resolution mobile photos)

### Video (type: "video")

-   mp4, mov, avi, wmv, flv, webm
-   Max size: 50MB

### File (type: "file")

-   pdf, doc, docx, xls, xlsx, ppt, pptx, txt, zip, rar
-   Max size: 50MB

## Important Notes

1. **DO NOT** send file metadata as JSON object:

    ```json
    // ❌ WRONG - Will fail
    {
        "file": {
            "name": "recording.m4a",
            "size": "124KB"
        }
    }
    ```

2. **DO** send actual file content:

    ```json
    // ✅ CORRECT - Will work
    {
        "file": "data:audio/m4a;base64,AAAAHGZ0eXBpc29tAAACAGlzb21pc28y..."
    }
    ```

    OR use multipart/form-data with actual file binary

3. The `message` field is optional and can be used as a caption

4. The backend now supports both multipart and base64 uploads

## Testing with Postman/cURL

### Multipart Upload

```bash
curl -X POST "http://your-api.com/api/v1/conversations/1/messages" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -F "type=audio" \
  -F "message=Test voice note" \
  -F "file=@/path/to/recording.m4a"
```

### Base64 Upload

```bash
curl -X POST "http://your-api.com/api/v1/conversations/1/messages" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "type": "audio",
    "message": "Test voice note",
    "file": "data:audio/m4a;base64,YOUR_BASE64_STRING_HERE"
  }'
```

## Backend Changes Made

1. ✅ Added support for base64 file uploads
2. ✅ Added better validation for file types
3. ✅ Added helpful error messages when wrong format is used
4. ✅ Added logging to help debug upload issues
5. ✅ Supports both multipart/form-data and JSON base64 uploads
6. ✅ Increased image dimension limits to 8000x8000 for high-res mobile photos

## Common Errors & Solutions

### Error: "File content required"

**Cause:** You're sending `{"name": "file.jpg", "size": "500KB"}` instead of actual file content.
**Solution:** Read the file as base64 or use FormData with actual file binary.

### Error: "The file field has invalid image dimensions"

**Cause:** Image dimensions exceed 8000x8000 pixels, or you're sending invalid file data.
**Solution:** Resize image on mobile before upload, or compress it. Max supported: 8000x8000px.

### Error: "The file field must be a file of type: mp3, wav, aac..."

**Cause:** File has wrong extension or mime type.
**Solution:** Ensure file extension matches the type (e.g., .m4a for audio, .jpg for image).

### No error but file not received

**Cause:** File sent as metadata object instead of content.
**Solution:** Check backend logs for "Mobile app sent file metadata instead of actual file" message. Send base64 content instead.

## Need Help?

Check the backend logs for detailed error messages:

-   Laravel logs: `storage/logs/laravel.log`
-   Look for "MessageController store" entries
-   Error messages will include the exact data received and what's expected
