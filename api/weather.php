<?php
// api/weather.php — AJAX: devuelve datos del tiempo de una ciudad
// Usa la API gratuita de Open-Meteo (sin clave API)
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: max-age=1800'); // Cache 30 min

// Coordenadas de Alicante (zona costera típica de hotel)
$lat = 38.3452;
$lon = -0.4815;

$url = "https://api.open-meteo.com/v1/forecast?latitude={$lat}&longitude={$lon}&current_weather=true&hourly=relativehumidity_2m&timezone=Europe%2FMadrid";

$ctx = stream_context_create(['http' => ['timeout' => 5]]);
$data = @file_get_contents($url, false, $ctx);

if ($data) {
    $json = json_decode($data, true);
    $cw   = $json['current_weather'] ?? [];
    $codes = [0=>'Despejado',1=>'Mayormente despejado',2=>'Parcialmente nublado',3=>'Nublado',
              45=>'Niebla',48=>'Niebla con escarcha',51=>'Llovizna ligera',61=>'Lluvia ligera',
              71=>'Nieve ligera',80=>'Lluvia moderada',95=>'Tormenta'];
    $desc = $codes[$cw['weathercode'] ?? 0] ?? 'Variable';
    echo json_encode([
        'temp'     => $cw['temperature'] ?? '--',
        'windspeed'=> $cw['windspeed'] ?? '--',
        'desc'     => $desc,
        'city'     => 'Alicante',
    ]);
} else {
    echo json_encode(['temp'=>'--','windspeed'=>'--','desc'=>'No disponible','city'=>'Alicante']);
}
