<?php
session_start();

function identificarEstadoPorCPF($cpf) {
    $regiao = substr($cpf, 8, 1);
    $estados = [
        '1' => 'DF, GO, MT, MS, TO',
        '2' => 'AC, AM, AP, PA, RO, RR',
        '3' => 'CE, MA, PI',
        '4' => 'AL, PB, PE, RN',
        '5' => 'BA, SE',
        '6' => 'MG',
        '7' => 'ES, RJ',
        '8' => 'SP',
        '9' => 'PR, SC',
        '0' => 'RS'
    ];
    return $estados[$regiao] ?? 'Desconhecido';
}

function calcularAnosDesdeRegistro($registrationDate) {
    $registro = DateTime::createFromFormat('d/m/Y', $registrationDate);
    if (!$registro) return 0;
    $hoje = new DateTime();
    $intervalo = $registro->diff($hoje);
    return $intervalo->y;
}

$info = null;
$anosRegistro = 0;
$erros = [];
$httpCode = 0;
$errorCurl = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $cpf = preg_replace('/[^0-9]/', '', $_POST['cpf']);
    $birthDateInput = $_POST['birthdate'];

    $date = DateTime::createFromFormat('Y-m-d', $birthDateInput);
    if (!$date) {
        $erros[] = "Data de nascimento inválida.";
    } else {
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
                "x-api-key: " . "65be66fdf9c1b2266a96102853af96b1a4595cb5dda5a74cc8bb89c7e2e2b9a5"
            ]
        ]);

        $response = curl_exec($curl);
        $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        $errorCurl = curl_error($curl);
        curl_close($curl);

        $data = json_decode($response, true);

        if ($httpCode == 200 && isset($data['success']) && $data['success'] == 1 && isset($data['data'])) {
            $info = $data['data'];
            $anosRegistro = calcularAnosDesdeRegistro($info['registrationDate']);

            if ($info['status'] !== 'Aprovado' || $info['situation'] !== 'REGULAR') {
                $erros[] = "CPF não está aprovado ou está com situação irregular.";
            }
            if ($info['birthDate'] !== $birthDate) {
                $erros[] = "Data de nascimento informada não confere com a data do CPF.";
            }
            if ($anosRegistro < 1) {
                $erros[] = "CPF foi registrado há menos de 1 ano. Verifique se é legítimo.";
            }

            if (isset($info['receipt']['controlCode'])) {
                $_SESSION['controlCode'] = $info['receipt']['controlCode'];
            }
        } else {
            if ($httpCode != 200) {
                $erros[] = "Erro na requisição: Código HTTP $httpCode";
            } else {
                $erros[] = "Não foi possível validar este CPF. Verifique os dados enviados.";
            }
        }
    }
}

// Definir classe de resultado para colorir conforme o estado do CPF
$classeResultado = 'valid';
if (!empty($erros)) {
    $classeResultado = 'invalid';
} elseif ($anosRegistro < 1) {
    $classeResultado = 'suspect';
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Validação de CPF</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f9fafb;
            margin: 0;
            padding: 20px;
        }
        h1 {
            text-align: center;
            color: #333;
        }
        .container {
            max-width: 600px;
            background: white;
            padding: 30px;
            margin: 30px auto;
            border-radius: 10px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        form label {
            display: block;
            margin-top: 15px;
            font-weight: bold;
            color: #555;
        }
        form input[type="text"],
        form input[type="date"] {
            width: 100%;
            padding: 10px;
            font-size: 1rem;
            margin-top: 5px;
            border: 1px solid #ccc;
            border-radius: 5px;
            box-sizing: border-box;
            transition: border-color 0.3s;
        }
        form input[type="text"]:focus,
        form input[type="date"]:focus {
            border-color: #007bff;
            outline: none;
        }
        .buttons {
            margin-top: 20px;
            display: flex;
            justify-content: space-between;
        }
        button {
            background-color: #007bff;
            color: white;
            border: none;
            padding: 12px 25px;
            font-size: 1rem;
            border-radius: 5px;
            cursor: pointer;
            transition: background-color 0.3s;
        }
        button:hover {
            background-color: #0056b3;
        }
        button:disabled {
            background-color: #999;
            cursor: not-allowed;
        }
        .message {
            margin-top: 20px;
            font-size: 1rem;
        }
        .error {
            color: #d9534f;
            font-weight: bold;
        }
        .success {
            color: #28a745;
            font-weight: bold;
        }
        .alert {
            color: #ffc107;
            font-weight: bold;
        }
        .info {
            color: #17a2b8;
            font-style: italic;
            margin-bottom: 10px;
        }
        .result-container {
            border-radius: 10px;
            padding: 25px;
            margin-top: 30px;
        }
        .result-container.valid {
            background-color: #e9f7ef;
            border: 2px solid #28a745;
            box-shadow: 0 0 15px rgba(40, 167, 69, 0.3);
        }
        .result-container.invalid {
            background-color: #f8d7da;
            border: 2px solid #dc3545;
            box-shadow: 0 0 15px rgba(220, 53, 69, 0.3);
        }
        .result-container.suspect {
            background-color: #fff3cd;
            border: 2px solid #ffc107;
            box-shadow: 0 0 15px rgba(255, 193, 7, 0.3);
        }
        .result-container h2 {
            text-align: center;
        }
        .result-item {
            margin: 10px 0;
            font-size: 1rem;
        }
        .loading {
            display: none;
            margin-top: 20px;
            text-align: center;
            font-style: italic;
            color: #555;
        }
        .spinner {
            border: 4px solid #f3f3f3;
            border-top: 4px solid #007bff;
            border-radius: 50%;
            width: 24px;
            height: 24px;
            animation: spin 1s linear infinite;
            display: inline-block;
            vertical-align: middle;
            margin-right: 8px;
        }
        @keyframes spin {
            0% { transform: rotate(0deg);}
            100% {transform: rotate(360deg);}
        }
    </style>
</head>
<body>

<div class="container">
    <h1>Consulta e Validação de CPF</h1>

    <p class="info">
        Preencha seu CPF (somente números) e a data de nascimento para validar os dados.<br>
        O código de controle é gerado a cada consulta para comprovar a veracidade.
    </p>

    <form id="cpfForm" method="POST" action="">
        <label for="cpf">CPF:</label>
        <input type="text" id="cpf" name="cpf" maxlength="11" placeholder="Ex: 12345678909" required pattern="\d{11}" title="Informe 11 números do CPF sem pontos ou traços" value="<?= htmlspecialchars($_POST['cpf'] ?? '') ?>">

        <label for="birthdate">Data de Nascimento:</label>
        <input type="date" id="birthdate" name="birthdate" required max="<?= date('Y-m-d') ?>" title="Informe uma data válida" value="<?= htmlspecialchars($_POST['birthdate'] ?? '') ?>">

        <div class="buttons">
            <button type="submit" id="submitBtn">Validar</button>
            <button type="button" id="clearBtn">Limpar</button>
        </div>

        <div class="loading" id="loadingIndicator">
            <span class="spinner"></span> Consultando...
        </div>
    </form>

    <?php if (!empty($erros)): ?>
        <div class="message error" role="alert">
            <?php foreach ($erros as $erro): ?>
                <p>⚠️ <?= htmlspecialchars($erro) ?></p>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <?php if ($info): ?>
        <div class="result-container <?= $classeResultado ?>" role="region" aria-live="polite">
            <h2>Resultado da Validação</h2>

            <?php if ($classeResultado === 'invalid'): ?>
                <p class="error">❌ CPF inválido ou com erros.</p>
            <?php elseif ($classeResultado === 'suspect'): ?>
                <p class="alert">⚠️ CPF válido, mas com indícios suspeitos (ex: registrado há menos de 1 ano).</p>
            <?php else: ?>
                <p class="success">✅ CPF válido e situação regular.</p>
            <?php endif; ?>

            <p class="result-item"><strong>Nome:</strong> <?= htmlspecialchars($info['name']) ?></p>
            <p class="result-item"><strong>CPF:</strong> <?= htmlspecialchars($info['cpfNumber']) ?></p>
            <p class="result-item"><strong>Data de Nascimento:</strong> <?= htmlspecialchars($info['birthDate']) ?></p>
            <p class="result-item"><strong>Estado provável (pela região do CPF):</strong> <?= identificarEstadoPorCPF($info['cpfNumber']) ?></p>
            <p class="result-item"><strong>Data de Registro:</strong> <?= htmlspecialchars($info['registrationDate']) ?> (<?= $anosRegistro ?> anos)</p>
            <p class="result-item"><strong>Situação:</strong> <?= htmlspecialchars($info['situation']) ?></p>
            <p class="result-item"><strong>Status:</strong> <?= htmlspecialchars($info['status']) ?></p>
            <p class="result-item"><strong>Código de Controle:</strong> <?= htmlspecialchars($_SESSION['controlCode'] ?? 'Não disponível') ?></p>

            <?php if (isset($info['receipt']['receiptCode'])): ?>
                <p class="result-item"><strong>Código de Recibo:</strong> <?= htmlspecialchars($info['receipt']['receiptCode']) ?></p>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>

<script>
    const form = document.getElementById('cpfForm');
    const loadingIndicator = document.getElementById('loadingIndicator');
    const submitBtn = document.getElementById('submitBtn');
    const clearBtn = document.getElementById('clearBtn');

    form.addEventListener('submit', () => {
        submitBtn.disabled = true;
        loadingIndicator.style.display = 'block';
    });

    clearBtn.addEventListener('click', () => {
        form.reset();
        submitBtn.disabled = false;
        loadingIndicator.style.display = 'none';
    });
</script>

</body>
</html>
