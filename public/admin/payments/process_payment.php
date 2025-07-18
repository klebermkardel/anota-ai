<?php
session_start();

require_once __DIR__ . '/../../../app/config/database.php';

// Controle de Acesso: Verifica se o usuário está logado e se é um admin
if (!isset($_SESSION['user_id']) || $_SESSION['nivel_acesso'] !== 'admin') {
    header('Location: ../../login.php?error=Acesso negado. Apenas administradores podem registrar pagamentos.');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $cliente_id = $_POST['cliente_id'] ?? null; // Agora recebemos cliente_id
    $valor_pago = $_POST['valor_pago'] ?? null;
    $data_pagamento = $_POST['data_pagamento'] ?? null;
    $observacoes = $_POST['observacoes'] ?? '';

    // Validação básica
    if (empty($cliente_id) || empty($valor_pago) || empty($data_pagamento)) {
        header('Location: create_payment.php?error=Todos os campos obrigatórios devem ser preenchidos.');
        exit();
    }

    // Garante que valor_pago é um float e que o cliente_id é um inteiro válido
    $valor_pago = floatval(str_replace(',', '.', $valor_pago));
    $cliente_id = (int)$cliente_id;

    if ($cliente_id <= 0) {
        header('Location: create_payment.php?error=Cliente inválido selecionado.');
        exit();
    }

    try {
        $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
        if ($conn->connect_error) {
            throw new Exception("Erro de conexão com o banco de dados: " . $conn->connect_error);
        }
        if (!empty(DB_CHARSET)) {
            $conn->set_charset(DB_CHARSET);
        }

        // Inicia uma transação (boa prática sempre que há alterações no DB)
        $conn->begin_transaction();

        // Opcional: Verificar se o cliente_id realmente existe na tabela clientes
        $stmt_check_cliente = $conn->prepare("SELECT id FROM clientes WHERE id = ?");
        $stmt_check_cliente->bind_param('i', $cliente_id);
        $stmt_check_cliente->execute();
        $result_check_cliente = $stmt_check_cliente->get_result();
        if ($result_check_cliente->num_rows === 0) {
            throw new Exception("O cliente selecionado não existe.");
        }
        $stmt_check_cliente->close();

        // Inserir o pagamento na tabela 'pagamentos'
        // Agora, venda_id será NULL, pois o pagamento é geral ao cliente
        $venda_id_null = null; // Definindo explicitamente como NULL
        $stmt_insert_payment = $conn->prepare("INSERT INTO pagamentos (cliente_id, venda_id, valor_pago, data_pagamento, observacoes) VALUES (?, ?, ?, ?, ?)");
        
        // O tipo 'i' para cliente_id, 'i' para venda_id (mesmo que nulo), 'd' para float, 's' para string, 's' para string
        // Para passar NULL em um bind_param, você deve usar null para o valor e o tipo 'i' ou 's' para o placeholder
        $stmt_insert_payment->bind_param("iisss", $cliente_id, $venda_id_null, $valor_pago, $data_pagamento, $observacoes);
        
        if (!$stmt_insert_payment->execute()) {
            throw new Exception("Erro ao registrar pagamento: " . $stmt_insert_payment->error);
        }
        $stmt_insert_payment->close();

        // A lógica de atualizar o status da venda para 'concluida' não se aplica mais aqui,
        // já que o pagamento é geral ao cliente e não a uma venda específica.
        // Se desejar atualizar o status de vendas pendentes, uma lógica mais complexa
        // precisaria ser implementada para alocar o pagamento às vendas mais antigas, etc.

        $conn->commit(); // Confirma a transação
        header('Location: create_payment.php?success=Pagamento registrado com sucesso para o cliente!');
        exit();

    } catch (Exception $e) {
        if ($conn) {
            $conn->rollback(); // Reverte a transação em caso de erro
        }
        error_log("Erro ao processar pagamento: " . $e->getMessage());
        header('Location: create_payment.php?error=Erro ao registrar pagamento: ' . htmlspecialchars($e->getMessage()));
        exit();
    } finally {
        if ($conn) {
            $conn->close();
        }
    }
} else {
    // Se a requisição não for POST, redireciona para o formulário
    header('Location: create_payment.php');
    exit();
}