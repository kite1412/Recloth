<?php
/**
 * RajaOngkir API proxy endpoint.
 * Called via AJAX from payment.php to fetch available shipping services.
 *
 * GET parameter: destination (zip_code of the user's selected address)
 * Returns JSON: { success: bool, data: [...] }
 *
 * Caches results per destination zip code for 1 hour to avoid redundant API calls.
 */

// Suppress any warnings/notices from corrupting JSON output
@ini_set('display_errors', '0');
error_reporting(0);

header('Content-Type: application/json');

$envPath = __DIR__ . '/../../.env';
$env = file_exists($envPath) ? parse_ini_file($envPath) : [];

$apiKey = $env['RAJAONGKIR_API_KEY'] ?? '';
$originZip = $env['RAJAONGKIR_ORIGIN_ZIP_CODE'] ?? '';
$destination = $_GET['destination'] ?? '';

if (empty($apiKey) || empty($originZip)) {
    echo json_encode(['success' => false, 'message' => 'API key atau origin zip code belum dikonfigurasi.']);
    exit;
}

if (empty($destination) || !ctype_digit($destination)) {
    echo json_encode(['success' => false, 'message' => 'Kode pos tujuan tidak valid.']);
    exit;
}

// --- Cache logic ---
$cacheDir = __DIR__ . '/../../cache/shipping';
if (!is_dir($cacheDir)) {
    @mkdir($cacheDir, 0755, true);
}
$cacheFile = $cacheDir . '/' . $destination . '.json';
$cacheTTL = 3600; // 1 hour

if (file_exists($cacheFile) && (time() - filemtime($cacheFile)) < $cacheTTL) {
    echo file_get_contents($cacheFile);
    exit;
}

// --- Fetch from API ---
$postFields = http_build_query([
    'origin'      => $originZip,
    'destination' => $destination,
    'weight'      => 1000,
    'courier'     => 'jne:sicepat:ide:sap:jnt:ninja:tiki:lion:anteraja:pos:ncs:rex:rpx:sentral:star:wahana:dse',
    'price'       => 'lowest',
]);

$ch = curl_init('https://rajaongkir.komerce.id/api/v1/calculate/domestic-cost');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $postFields);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'key: ' . $apiKey,
    'Content-Type: application/x-www-form-urlencoded',
]);
curl_setopt($ch, CURLOPT_TIMEOUT, 15);

$response = curl_exec($ch);
$curlError = curl_error($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

if ($curlError) {
    echo json_encode(['success' => false, 'message' => 'Gagal menghubungi layanan pengiriman: ' . $curlError]);
    exit;
}

$body = json_decode($response, true);

if ($body === null) {
    echo json_encode(['success' => false, 'message' => 'Respon API tidak valid (HTTP ' . $httpCode . ').']);
    exit;
}

if (isset($body['meta']['code']) && $body['meta']['code'] === 200 && isset($body['data'])) {
    $output = json_encode(['success' => true, 'data' => $body['data']]);
    // Write to cache
    @file_put_contents($cacheFile, $output);
    echo $output;
} else {
    $msg = $body['meta']['message'] ?? ('Gagal mendapatkan data ongkir (HTTP ' . $httpCode . ').');
    echo json_encode(['success' => false, 'message' => $msg]);
}
