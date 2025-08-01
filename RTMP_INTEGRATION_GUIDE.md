# RTMP Integration Guide for ManyCam & SplitCam

## Overview

This guide shows how to integrate RTMP streaming software (ManyCam, SplitCam, XSplit, etc.) with your Laravel streaming project. This approach provides professional multi-camera switching capabilities while maintaining your existing Agora RTC viewer experience.

## Architecture Options

### Option 1: RTMP Server + Agora Bridge (Recommended)

```
ManyCam/SplitCam → RTMP Server → Stream Bridge → Agora RTC → Viewers
```

### Option 2: Dual Streaming (Parallel)

```
ManyCam/SplitCam → RTMP Server → HLS/WebRTC → Viewers
                ↘ Virtual Camera → Browser → Agora RTC → Viewers
```

## Implementation Steps

### Step 1: Add RTMP Configuration

Add RTMP settings to your Laravel configuration:

**config/streaming.php** (create this file):

```php
<?php

return [
    'rtmp' => [
        'server_url' => env('RTMP_SERVER_URL', 'rtmp://localhost/live'),
        'server_key' => env('RTMP_SERVER_KEY', ''),
        'nginx_rtmp_enabled' => env('NGINX_RTMP_ENABLED', false),
        'stream_bridge_enabled' => env('STREAM_BRIDGE_ENABLED', true),
    ],

    'agora' => [
        'app_id' => env('AGORA_APP_ID'),
        'app_certificate' => env('AGORA_APP_CERTIFICATE'),
        'rtmp_bridge' => env('AGORA_RTMP_BRIDGE', false),
    ],

    'streaming_software' => [
        'supported' => ['manycam', 'splitcam', 'obs', 'xsplit'],
        'default_resolution' => '1920x1080',
        'default_bitrate' => 3000,
        'default_fps' => 30,
    ]
];
```

### Step 2: Environment Variables

Add to your `.env` file:

```env
# RTMP Streaming Configuration
RTMP_SERVER_URL=rtmp://your-server.com/live
RTMP_SERVER_KEY=your-stream-key
NGINX_RTMP_ENABLED=true
STREAM_BRIDGE_ENABLED=true
AGORA_RTMP_BRIDGE=true

# Streaming Software Settings
DEFAULT_STREAM_RESOLUTION=1920x1080
DEFAULT_STREAM_BITRATE=3000
DEFAULT_STREAM_FPS=30
```

### Step 3: Create RTMP Stream Model

**app/Models/RtmpStream.php**:

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RtmpStream extends Model
{
    protected $fillable = [
        'stream_id',
        'rtmp_url',
        'stream_key',
        'software_type',
        'resolution',
        'bitrate',
        'fps',
        'is_active',
        'last_heartbeat',
        'metadata'
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'last_heartbeat' => 'datetime',
        'metadata' => 'array'
    ];

    public function stream(): BelongsTo
    {
        return $this->belongsTo(Stream::class);
    }

    public function generateStreamKey(): string
    {
        return $this->stream_id . '_' . bin2hex(random_bytes(16));
    }

    public function getFullRtmpUrl(): string
    {
        return rtrim(config('streaming.rtmp.server_url'), '/') . '/' . $this->stream_key;
    }
}
```

### Step 4: Update Stream Model

Add RTMP relationship to your existing **app/Models/Stream.php**:

```php
// Add this method to your existing Stream model
public function rtmpStream(): HasOne
{
    return $this->hasOne(RtmpStream::class);
}

public function createRtmpStream($softwareType = 'manycam'): RtmpStream
{
    return $this->rtmpStream()->create([
        'rtmp_url' => config('streaming.rtmp.server_url'),
        'stream_key' => $this->id . '_' . bin2hex(random_bytes(16)),
        'software_type' => $softwareType,
        'resolution' => config('streaming.streaming_software.default_resolution'),
        'bitrate' => config('streaming.streaming_software.default_bitrate'),
        'fps' => config('streaming.streaming_software.default_fps'),
        'is_active' => false
    ]);
}
```

### Step 5: Create Migration

```bash
php artisan make:migration create_rtmp_streams_table
```

**Migration content**:

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('rtmp_streams', function (Blueprint $table) {
            $table->id();
            $table->foreignId('stream_id')->constrained()->onDelete('cascade');
            $table->string('rtmp_url');
            $table->string('stream_key')->unique();
            $table->enum('software_type', ['manycam', 'splitcam', 'obs', 'xsplit', 'other'])->default('manycam');
            $table->string('resolution')->default('1920x1080');
            $table->integer('bitrate')->default(3000);
            $table->integer('fps')->default(30);
            $table->boolean('is_active')->default(false);
            $table->timestamp('last_heartbeat')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('rtmp_streams');
    }
};
```

## ManyCam & SplitCam Integration

### ManyCam Setup Instructions

1. **Open ManyCam**
2. **Go to Settings → Streaming**
3. **Select "Custom RTMP"**
4. **Enter your RTMP details**:
    - **RTMP URL**: `rtmp://your-domain.com/live`
    - **Stream Key**: Get from your Laravel app (generated per stream)
    - **Resolution**: 1920x1080 (or your preferred)
    - **Bitrate**: 3000 kbps
    - **FPS**: 30

### SplitCam Setup Instructions

1. **Open SplitCam**
2. **Click "Share & Record" → "Media Server"**
3. **Select "Custom RTMP Server"**
4. **Configure**:
    - **Server**: `rtmp://your-domain.com/live`
    - **Stream Key**: From your Laravel app
    - **Video Quality**: High (1080p)
    - **Audio Quality**: High

### Integration Flow

```
┌─────────────┐    RTMP Stream    ┌─────────────┐    Bridge    ┌─────────────┐
│ ManyCam/    │ ───────────────► │   RTMP      │ ──────────► │   Agora     │
│ SplitCam    │                  │   Server    │             │   RTC       │
└─────────────┘                  └─────────────┘             └─────────────┘
       ▲                               │                           │
       │                               ▼                           ▼
┌─────────────┐                  ┌─────────────┐           ┌─────────────┐
│  Multiple   │                  │  Laravel    │           │   Web       │
│  Cameras    │                  │  Backend    │           │  Viewers    │
└─────────────┘                  └─────────────┘           └─────────────┘
```

## Next Steps

1. Set up NGINX with RTMP module (or use a cloud RTMP service)
2. Create API endpoints for RTMP stream management
3. Update broadcast interface to show RTMP connection status
4. Implement stream bridge between RTMP and Agora RTC

Would you like me to continue with any specific part of this implementation?
