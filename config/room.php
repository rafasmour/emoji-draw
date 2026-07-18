<?php

return [
    'disconnect_grace_seconds' => (int) env('ROOM_DISCONNECT_GRACE_SECONDS', 30),
    'heartbeat_interval_seconds' => (int) env('ROOM_HEARTBEAT_INTERVAL_SECONDS', 10),
];
