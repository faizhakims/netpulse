<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$req = Illuminate\Http\Request::create('/alert/channel/save', 'POST', [
    'type' => 'telegram', 
    'config' => ['token' => '123', 'chat_id' => '123'], 
    'is_active' => true
]);

$user = App\Models\User::first();
auth()->login($user);

$res = app(App\Http\Controllers\AlertController::class)->saveChannel($req);
echo $res->getContent();
