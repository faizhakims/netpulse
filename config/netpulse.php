<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Main Router Name
    |--------------------------------------------------------------------------
    |
    | Nama perangkat yang dianggap sebagai core router / main router.
    | Digunakan untuk memisahkan metrik Core dan Edge pada grafik latensi.
    | Anda bisa menggantinya sesuai hostname perangkat utama Anda.
    |
    */
    'main_router' => env('MAIN_ROUTER', 'main-router'),
];