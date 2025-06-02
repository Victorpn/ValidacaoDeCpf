<?php
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Limpa o CPF, deixando apenas números
    $cpf = preg_replace('/[^0-9]/', '', $_POST['cpf']);
    $birthDate = $_POST['birthdate']; // Formato: DD/MM/YYYY

    // Validação básica
    if (strlen($cpf) != 11 || !preg_match('/^\d{2}\/\d{2}\/\d{4}$/', $birthDate)) {
        echo "<h2>CPF ou data de nascimento inválidos.</h2>";
        echo '<a href="index.html">Voltar</a>';
        exit;
    }

    // Inicializa cURL
    $curl = curl_init();

    // Define a URL da API
    $url = "https://api.cpfhub.io/api/cpf";

    // Dados a serem enviados no corpo da requisição
    $data = json_encode([
        "cpf" => $_POST['cpf'],
        "birthDate" => $birthDate
    ]);

    // Configurações do cURL
    curl_setopt_array($curl, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => [
            "Content-Type: application/json",
            "x-api-key: 65be66fdf9c1b2266a96102853af96b1a4595cb5dda5a74cc8bb89c7e2e2b9a5"
        ],
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => $data
    ]);

    // Executa a requisição
    $response = curl_exec($curl);
    $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    curl_close($curl);

    // Verifica o código de status HTTP
    if ($httpCode == 200) {
        $data = json_decode($response, true);
        if ($data['success']) {
            echo "<h2>Resultado da Validação:</h2>";
            echo "<pre>" . print_r($data['data'], true) . "</pre>";
        } else {
            echo "<h2>Erro na consulta: " . $data['message'] . "</h2>";
        }
    } else {
        echo "<h2>Erro ao consultar API. Código HTTP: $httpCode</h2>";
    }

    echo '<a href="index.html">Voltar</a>';
}
?>
