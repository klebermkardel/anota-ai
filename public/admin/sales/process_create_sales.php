<?php
session_start();

// Inclui o arquivo de configuração do banco de dados
// Caminho do require_once:
// process_create_sales.php (public/admin/sales/) -> ../ (public/admin/) -> ../ (public/) -> ../ (anota_ai/) -> app/config/
require_once __DIR__ . '/../../../app/config/database.php';

// 1. Controle de Acesso: Verifica se o usuário está logado e se é um admin
if (!isset($_SESSION['user_id']) || $_SESSION['nivel_acesso'] !== 'admin') {
    // Caminho para login.php: public/admin/sales/ -> ../../ (public/) -> login.php
    header('Location: ../../login.php?error=Acesso negado. Apenas administradores podem processar vendas.');
    exit();
}

// 2. Verifica se a requisição é POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    // Redireciona para o formulário de criação de vendas
    header('Location: create_sales.php?error=Acesso inválido. Formulário deve ser enviado via POST.');
    exit();
}

$conn = null; // Inicializa a conexão como null

try {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    if ($conn->connect_error) {
        throw new Exception("Erro de conexão com o banco de dados: " . $conn->connect_error);
    }
    if (!empty(DB_CHARSET)) {
        $conn->set_charset(DB_CHARSET);
    }

    // Inicia uma transação. Embora seja uma única inserção, é boa prática para operações com DB.
    $conn->begin_transaction();

    // 3. Coleta e sanitiza os dados do formulário de venda
    $cliente_id = (int)($_POST['cliente_id'] ?? 0); // Converte para inteiro, 0 se não existir
    $data_venda = trim($_POST['data_venda'] ?? '');
    $valor_total = (float)str_replace(',', '.', trim($_POST['valor_total'] ?? '0.00')); // Garante formato float (ponto decimal)
    $descricao = trim($_POST['descricao'] ?? '');
    $status_venda = trim($_POST['status_venda'] ?? '');

    // 4. Validação dos dados
    if (empty($cliente_id) || $cliente_id <= 0) {
        throw new Exception("Selecione um cliente válido.");
    }
    if (empty($data_venda) || !strtotime($data_venda)) { // Verifica se a data é válida
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


    // 5. Insere a nova venda na tabela 'vendas'
    $sql_insert_venda = "INSERT INTO vendas (cliente_id, data_venda, valor_total, descricao_itens, status_venda) VALUES (?, ?, ?, ?, ?)";
    $stmt_insert_venda = $conn->prepare($sql_insert_venda);

    if ($stmt_insert_venda === false) {
        throw new Exception("Erro ao preparar a consulta de inserção de venda: " . $conn->error);
    }

    // Binda os parâmetros: i=integer, s=string, d=double (para float), s=string, s=string
    $stmt_insert_venda->bind_param('isdss', $cliente_id, $data_venda, $valor_total, $descricao, $status_venda);

    if (!$stmt_insert_venda->execute()) {
        throw new Exception("Erro ao registrar venda: " . $stmt_insert_venda->error);
    }

    $venda_id = $conn->insert_id; // Pega o ID da venda recém-inserida
    $stmt_insert_venda->close();

    // Se tudo deu certo, commita a transação
    $conn->commit();
    header('Location: create_sales.php?success=Venda registrada com sucesso! ID da Venda: ' . $venda_id);
    exit();

} catch (Exception $e) {
    // Em caso de qualquer erro, reverte a transação
    if ($conn) {
        $conn->rollback();
    }
    // Loga o erro para depuração (em produção, não mostre ao usuário)
    error_log('Erro ao processar criação de venda: ' . $e->getMessage());
    header('Location: create_sales.php?error=Ocorreu um erro: ' . urlencode($e->getMessage()));
    exit();
} finally {
    // Garante que a conexão seja fechada
    if ($conn) {
        $conn->close();
    }
}
?>