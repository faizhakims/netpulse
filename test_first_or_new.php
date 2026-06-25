<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

App\Models\AlertChannel::truncate(); 
$c = App\Models\AlertChannel::firstOrNew(['type' => 'telegram']); 
$c->config = ['token' => '123']; 
$c->save(); 
echo "OK\n";
