<?php

return array(
    'cartsrestore/<hash:[a-f0-9]{32}>/' => 'frontend/restore',
    'cartscancel/<hash:[a-f0-9]{32}>/' => 'frontend/cancel',
    'cartssave/' => 'frontend/save',
    'cartsheartbeat/' => 'frontend/heartbeat',
);