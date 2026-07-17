<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$export = new App\Exports\AchievementReportExport('2026-07-17', '2026-07-17', null, null);
$collection = $export->collection();
echo 'Count: ' . $collection->count() . "\n";
foreach ($collection as $row) {
    echo json_encode($row) . "\n";
}
