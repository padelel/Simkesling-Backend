<?php

require_once __DIR__ . '/vendor/autoload.php';

// Bootstrap Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Http\Controllers\DashboardLimbahPadatController;
use Illuminate\Http\Request;

try {
    $controller = new DashboardLimbahPadatController();
    $request = new Request();
    $request->merge(['tahun' => 2024]);
    
    echo "Testing Dashboard Limbah Padat Controller...\n";
    echo "Request tahun: " . $request->tahun . "\n\n";
    
    $result = $controller->dashboardLimbahPadatData($request);
    
    echo "Response Status: " . $result->getStatusCode() . "\n";
    
    $data = json_decode($result->getContent(), true);
    
    if (isset($data['data']['values'])) {
        $values = $data['data']['values'];
        
        echo "\n=== ALL AVAILABLE FIELDS ===\n";
        echo "Available fields: " . implode(", ", array_keys($values)) . "\n\n";
        
        echo "=== SUMMARY STATISTICS ===\n";
        echo "Total Puskesmas RS: " . ($values['total_puskesmas_rs'] ?? 'NOT_FOUND') . "\n";
        echo "Total Puskesmas RS Sudah Lapor: " . ($values['total_puskesmas_rs_sudah_lapor'] ?? 'NOT_FOUND') . "\n";
        echo "Total Puskesmas RS Belum Lapor: " . ($values['total_puskesmas_rs_belum_lapor'] ?? 'NOT_FOUND') . "\n";
        
        echo "\n=== CHART DATA ===\n";
        echo "Chart Sudah Lapor: ";
        if (isset($values['total_chart_puskesmas_rs_sudah_lapor'])) {
            print_r($values['total_chart_puskesmas_rs_sudah_lapor']);
        } else {
            echo "NOT_FOUND\n";
        }
    } else {
        echo "No data values found in response\n";
        echo "Full response: " . $result->getContent() . "\n";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}