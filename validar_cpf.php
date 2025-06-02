<?php
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $cpf = preg_replace('/[^0-9]/', '', $_POST['cpf']);

    // Converte data de nascimento para DD/MM/YYYY
    $birthDateInput = $_POST['birthdate']; // formato YYYY-MM-DD
    $date = DateTime::createFromFormat('Y-m-d', $birthDateInput);
    if (!$date) {
        echo "Data de nascimento inválida";
        exit;
    }
    $birthDate = $date->format('d/m/Y');

    $payload = json_encode([
        "cpf" => $cpf,
        "birthDate" => $birthDate
    ]);

    $curl = curl_init();
    curl_setopt_array($curl, [
        CURLOPT_URL => "https://api.cpfhub.io/api/cpf",
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => $payload,
        CURLOPT_HTTPHEADER => [
            "Content-Type: application/json",
            "x-api-key: 65be66fdf9c1b2266a96102853af96b1a4595cb5dda5a74cc8bb89c7e2e2b9a5"
        ]
    ]);

    $response = curl_exec($curl);
    $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    $error = curl_error($curl);
    curl_close($curl);

    if ($httpCode == 200) {
        $data = json_decode($response, true);
        echo "<pre>" . print_r($data, true) . "</pre>";
    } else {
        echo "<h2>Erro ao consultar API</h2>";
        echo "Código HTTP: $httpCode<br>";
        echo "Resposta da API: $response<br>";
        echo "Erro cURL: $error";
    }
}
?>
