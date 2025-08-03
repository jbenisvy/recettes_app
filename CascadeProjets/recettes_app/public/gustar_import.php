<?php
header('Content-Type: application/json');

if (!empty($_POST['url'])) {
    $data = ['text' => $_POST['url']];
    $ch = curl_init('https://gustar-io-deutsche-rezepte.p.rapidapi.com/generateRecipe');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'X-Rapidapi-Key: c0cfd85bc2mshed53825c3d09c4dp1bacc9jsn7f1dc36ec02b',
            'X-Rapidapi-Host: gustar-io-deutsche-rezepte.p.rapidapi.com'
        ],
        CURLOPT_POSTFIELDS => json_encode($data)
    ]);
    $response = curl_exec($ch);
    curl_close($ch);
    if (curl_errno($ch)) {
        echo json_encode(['error' => curl_error($ch)]);
        exit;
    }
    $responseData = json_decode($response, true);
    if (!function_exists('lungva_translate')) {
        function lungva_translate($text, $from = 'de', $to = 'fr') {
            $url = 'https://translate.mentality.rip/translate';
            $params = http_build_query([
                'source' => $from,
                'target' => $to,
                'text' => $text
            ]);
            $full_url = $url . '?' . $params;
            $response = @file_get_contents($full_url);
            $data = json_decode($response, true);
            return $data['translation'] ?? $text;
        }
    }
    if (isset($responseData['title'])) {
        // Mapping vers ton format avec traduction automatique
        $mapped = [
            'nom' => lungva_translate($responseData['title'], 'de', 'fr'),
            'description' => isset($responseData['description']) ? lungva_translate($responseData['description'], 'de', 'fr') : '',
            'ingredients' => array_map(function($ing) {
                return [
                    'nom' => lungva_translate($ing['name'], 'de', 'fr'),
                    'quantite' => $ing['amount'],
                    'unite' => lungva_translate($ing['unit'], 'de', 'fr')
                ];
            }, $responseData['ingredients'] ?? []),
            'etapes' => array_map(function($step) {
                return lungva_translate($step, 'de', 'fr');
            }, $responseData['instructions'] ?? []),
            'categorie' => '',
            'temps_preparation' => '',
            'temps_cuisson' => '',
            'difficulte' => '',
            'images' => [],
            'videos' => [],
            'tags' => [],
            'portions' => $responseData['portions'] ?? '',
            'temps_total' => $responseData['totalTime'] ?? ''
        ];
        echo json_encode($mapped, JSON_UNESCAPED_UNICODE);
        exit;
    } else {
        // Si pas de résultat reconnu
        echo $response;
        exit;
    }
}

echo json_encode(['error' => 'Aucune URL fournie']);
