<?php
session_start();

// Inclui o arquivo de configuração do banco de dados
// Caminho do require_once:
// process_edit_sales.php (public/admin/sales/) -> ../ (public/admin/) -> ../ (public/) -> ../ (anota_ai/) -> app/config/
require_once __DIR__ . '/../../../app/config/database.php';

// 1. Controle de Acesso: Verifica se o usuário está logado e se é um admin
if (!isset($_SESSION['user_id']) || $_SESSION['nivel_acesso'] !== 'admin') {
    // Caminho para login.php: public/admin/sales/ -> ../../ (public/) -> login.php
    header('Location: ../../login.php?error=Acesso negado. Apenas administradores podem processar edições de vendas.');
    exit();
}

// 2. Verifica se a requisição é POST e se o ID da venda foi enviado
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['venda_id']) || !is_numeric($_POST['venda_id'])) {
    header('Location: list_sales.php?error=Requisição inválida ou ID da venda não fornecido para edição.');
    exit();
}

$venda_id = (int)$_POST['venda_id']; // Garante que é um inteiro
$conn = null; // Inicializa a conexão como null

try {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    if ($conn->connect_error) {
        throw new Exception("Erro de conexão com o banco de dados: " . $conn->connect_error);
    }
    if (!empty(DB_CHARSET)) {
        $conn->set_charset(DB_CHARSET);
    }

    // Inicia uma transação para garantir a atomicidade da operação
    $conn->begin_transaction();

    // 3. Coleta e sanitiza os dados do formulário de venda
    $cliente_id = (int)($_POST['cliente_id'] ?? 0);
    $data_venda = trim($_POST['data_venda'] ?? '');
    $valor_total = (float)str_replace(',', '.', trim($_POST['valor_total'] ?? '0.00'));
    $descricao_itens = trim($_POST['descricao_itens'] ?? ''); // O nome do campo é descricao_itens
    $status_venda = trim($_POST['status_venda'] ?? '');
    $observacoes = trim($_POST['observacoes'] ?? ''); // Adicionado campo de observações, se estiver no form

    // 4. Validação dos dados
    if (empty($cliente_id) || $cliente_id <= 0) {
        throw new Exception("Selecione um cliente válido.");
    }
    if (empty($data_venda) || !strtotime($data_venda)) {
        throw new Exception("A data da venda é obrigatória e deve ser válida.");
    }
    if (empty($valor_total) || $valor_total <= 0) {
        throw new Exception("O valor total da venda deve ser maior que zero.");
    }
    if (!in_array($status_venda, ['pendente', 'concluida', 'cancelada'])) {
        throw new Exception("Status de venda inválido.");
    }

    // Opcional: Verificar se o cliente_id realmente existe na tabela clientes
    $sql_check_cliente = "SELECT id FROM clientes WHERE id = ?";
    $stmt_check_cliente = $conn->prepare($sql_check_cliente);
    if ($stmt_check_cliente === false) {
        throw new Exception("Erro ao preparar verificação de cliente: " . $conn->error);
    }
    $stmt_check_cliente->bind_param('i', $cliente_id);
    $stmt_check_cliente->execute();
    $result_check_cliente = $stmt_check_cliente->get_result();
    if ($result_check_cliente->num_rows === 0) {
        throw new Exception("O cliente selecionado não existe.");
    }
    $stmt_check_cliente->close();

    // 5. Atualiza a venda na tabela 'vendas'
    $sql_update_venda = "UPDATE vendas SET cliente_id = ?, data_venda = ?, valor_total = ?, descricao_itens = ?, status_venda = ?, observacoes = ? WHERE id = ?";
    $stmt_update_venda = $conn->prepare($sql_update_venda);

    if ($stmt_update_venda === false) {
        throw new Exception("Erro ao preparar a consulta de atualização de venda: " . $conn->error);
    }

    // Binda os parâmetros: i=integer, s=string, d=double (para float), s=string, s=string, s=string, i=integer
    $stmt_update_venda->bind_param('isdsssi', $cliente_id, $data_venda, $valor_total, $descricao_itens, $status_venda, $observacoes, $venda_id);

    if (!$stmt_update_venda->execute()) {
        throw new Exception("Erro ao atualizar venda: " . $stmt_update_venda->error);
    }

    // Verifica se alguma linha foi afetada (se a venda realmente existia e foi atualizada)
    if ($stmt_update_venda->affected_rows > 0) {
        $conn->commit(); // Commita a transação se a atualização foi bem-sucedida
        header('Location: list_sales.php?success=Venda ID ' . $venda_id . ' atualizada com sucesso!');
        exit();
    } else {
        $conn->rollback(); // Reverte se nenhuma linha foi afetada (venda não encontrada ou nenhum dado alterado)
        header('Location: edit_sales.php?id=' . $venda_id . '&error=Nenhuma alteração detectada ou venda não encontrada.');
        exit();
    }

    $stmt_update_venda->close();

} catch (Exception $e) {
    // Em caso de qualquer erro, reverte a transação
    if ($conn) {
        $conn->rollback();
    }
    error_log('Erro ao processar edição de venda: ' . $e->getMessage());
    header('Location: edit_sales.php?id=' . $venda_id . '&error=Ocorreu um erro: ' . urlencode($e->getMessage()));
    exit();
} finally {
    // Garante que a conexão seja fechada
    if ($conn) {
        $conn->close();
    }
}
?>