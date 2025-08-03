<?php
// gustar_proxy.php : proxy sécurisé pour Gustar.io via RapidAPI
header('Content-Type: application/json');

// Récupère le paramètre 'text' depuis la requête GET
$text = isset($_GET['text']) ? urlencode($_GET['text']) : '';

if (empty($text)) {
    echo json_encode(['error' => 'Paramètre "text" manquant.']);
    http_response_code(400);
    exit;
}

$url = "https://gustar-io-deutsche-rezepte.p.rapidapi.com/search_api?text={$text}";

$curl = curl_init();
curl_setopt_array($curl, [
    CURLOPT_URL => $url,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_FOLLOWLOCATION => true,
    CURLOPT_HTTPHEADER => [
        "x-rapidapi-key: c0cfd85bc2mshed53825c3d09c4dp1bacc9jsn7f1dc36ec02b",
        "x-rapidapi-host: gustar-io-deutsche-rezepte.p.rapidapi.com",
    ],
]);

$response = curl_exec($curl);
$err = curl_error($curl);
curl_close($curl);

if ($err) {
    echo json_encode(['error' => "Erreur cURL : $err"]);
    http_response_code(500);
    exit;
}

// Retourne la réponse brute de l'API Gustar.io
http_response_code(200);
echo $response;
