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
        'default_resolution' => env('DEFAULT_STREAM_RESOLUTION', '1920x1080'),
        'default_bitrate' => env('DEFAULT_STREAM_BITRATE', 3000),
        'default_fps' => env('DEFAULT_STREAM_FPS', 30),
    ],

    'recommended_settings' => [
        'video' => [
            'resolution_options' => [
                '1920x1080' => '1080p (Full HD)',
                '1280x720' => '720p (HD)',
                '854x480' => '480p (SD)',
                '640x360' => '360p (Low)'
            ],
            'bitrate_ranges' => [
                '1080p' => ['min' => 2500, 'max' => 6000, 'recommended' => 4000],
                '720p' => ['min' => 1500, 'max' => 4000, 'recommended' => 2500],
                '480p' => ['min' => 800, 'max' => 2000, 'recommended' => 1200],
                '360p' => ['min' => 400, 'max' => 1000, 'recommended' => 600]
            ],
            'fps_options' => [30, 60],
            'keyframe_interval' => 2
        ],
        'audio' => [
            'bitrate' => 128, // kbps
            'sample_rate' => 44100, // Hz
            'channels' => 2 // Stereo
        ]
    ]
];
