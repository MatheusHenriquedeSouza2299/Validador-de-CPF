<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['file']) && $_FILES['file']['error'] === UPLOAD_ERR_OK) {
    $fileTmpPath = $_FILES['file']['tmp_name'];
    $fileType = $_FILES['file']['type'];

    // Verifica se o arquivo é CSV
    if ($fileType !== 'text/csv') {
        echo 'Por favor, envie um arquivo CSV.';
        exit;
    }

    // Função para validar o CPF
    function validarCPF($cpf) {
        $cpf = preg_replace('/\D/', '', $cpf);

        if (strlen($cpf) != 11) {
            return false;
        }

        $soma = 0;
        for ($i = 0; $i < 9; $i++) {
            $soma += $cpf[$i] * (10 - $i);
        }

        $resto = ($soma * 10) % 11;
        $resto = ($resto == 10 || $resto == 11) ? 0 : $resto;

        if ($resto != $cpf[9]) {
            return false;
        }

        $soma = 0;
        for ($i = 0; $i < 10; $i++) {
            $soma += $cpf[$i] * (11 - $i);
        }

        $resto = ($soma * 10) % 11;
        $resto = ($resto == 10 || $resto == 11) ? 0 : $resto;

        return $resto == $cpf[10];
    }

    $cpfsBase = [];
    $validCpfs = [];

    // Lê o arquivo CSV e processa
    if (($handle = fopen($fileTmpPath, 'r')) !== FALSE) {
        fgetcsv($handle); // Pular o cabeçalho, se houver

        while (($data = fgetcsv($handle, 1000, ',')) !== FALSE) {
            $cpfBase = $data[0];
            if (strlen($cpfBase) == 8) {
                $cpfsBase[] = $cpfBase;
                for ($i = 0; $i < 1000; $i++) {
                    $prefix = str_pad($i, 3, '0', STR_PAD_LEFT);
                    $cpf = $prefix . $cpfBase;

                    if (validarCPF($cpf)) {
                        $validCpfs[$cpfBase][] = $cpf;
                    }
                }
            }
        }
        fclose($handle);
    }

    // Gera o arquivo CSV com CPFs válidos
    $outputFile = 'cpfs_validos_' . date('YmdHis') . '.csv';
    $fp = fopen($outputFile, 'w');
    fputcsv($fp, ['CPF Base', 'CPF Válido']); // Cabeçalho
    foreach ($cpfsBase as $cpfBase) {
        if (isset($validCpfs[$cpfBase])) {
            foreach ($validCpfs[$cpfBase] as $cpf) {
                fputcsv($fp, [$cpfBase, $cpf]);
            }
        } else {
            fputcsv($fp, [$cpfBase, 'Nenhum CPF válido encontrado']);
        }
    }
    fclose($fp);

    // Exibe o resultado com link para download
    echo '<html><body>';
    echo '<h1>Resultados</h1>';

    foreach ($cpfsBase as $cpfBase) {
        echo '<h2>CPF Base: <strong>' . htmlspecialchars($cpfBase) . '</strong></h2>';
        if (isset($validCpfs[$cpfBase])) {
            echo '<ul>';
            foreach ($validCpfs[$cpfBase] as $cpf) {
                echo '<li>' . htmlspecialchars($cpf) . '</li>';
            }
            echo '</ul>';
        } else {
            echo '<p>Nenhum CPF válido encontrado.</p>';
        }
    }

    echo '<h3><a href="' . $outputFile . '" download>Baixar arquivo CSV com CPFs válidos</a></h3>';
    echo '</body></html>';
} else {
    echo 'Erro no upload do arquivo.';
}
?>
