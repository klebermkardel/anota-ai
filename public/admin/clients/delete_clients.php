<?php
session_start();

// Inclui o arquivo de configuração do banco de dados
require_once __DIR__ . '/../../../app/config/database.php';

// 1. Controle de Acesso: Verifica se o usuário está logado e se é um admin
if (!isset($_SESSION['user_id']) || $_SESSION['nivel_acesso'] !== 'admin') {
    header('Location: ../../login.php?error=Acesso negado. Apenas administradores podem excluir clientes.');
    exit();
}

// 2. Verifica se a requisição é GET e se um ID de cliente foi passado
// A exclusão geralmente é acionada por um link GET, com confirmação via JS no frontend.
if ($_SERVER['REQUEST_METHOD'] !== 'GET' || !isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: list_clients.php?error=Requisição inválida ou ID do cliente não fornecido para exclusão.');
    exit();
}

$cliente_id = (int)$_GET['id']; // Garante que é um inteiro
$conn = null; // Inicializa a conexão como null

try {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    if ($conn->connect_error) {
        throw new Exception("Erro de conexão com o banco de dados: " . $conn->connect_error);
    }
    if (!empty(DB_CHARSET)) {
        $conn->set_charset(DB_CHARSET);
    }

    // Inicia uma transação (boa prática mesmo para DELETE único, por segurança)
    $conn->begin_transaction();

    // 3. Deleta o cliente da tabela 'clientes'
    // As chaves estrangeiras (ON DELETE CASCADE, ON DELETE SET NULL) farão o resto
    $sql_delete_cliente = "DELETE FROM clientes WHERE id = ?";
    $stmt_delete_cliente = $conn->prepare($sql_delete_cliente);

    if ($stmt_delete_cliente === false) {
        throw new Exception("Erro ao preparar a consulta de exclusão de cliente: " . $conn->error);
    }

    $stmt_delete_cliente->bind_param('i', $cliente_id);

    if (!$stmt_delete_cliente->execute()) {
        throw new Exception("Erro ao excluir cliente: " . $stmt_delete_cliente->error);
    }

    // Verifica se alguma linha foi afetada (se o cliente realmente existia)
    if ($stmt_delete_cliente->affected_rows > 0) {
        $conn->commit(); // Commita a transação se a exclusão foi bem-sucedida
        header('Location: list_clients.php?success=Cliente excluído com sucesso. Vendas e pagamentos associados foram removidos.');
        exit();
    } else {
        $conn->rollback(); // Reverte se nenhuma linha foi afetada (cliente não encontrado)
        header('Location: list_clients.php?error=Cliente com ID ' . $cliente_id . ' não encontrado para exclusão.');
        exit();
    }

    $stmt_delete_cliente->close();

} catch (Exception $e) {
    // Em caso de qualquer erro, reverte a transação
    if ($conn) {
        $conn->rollback();
    }
    error_log('Erro na exclusão de cliente: ' . $e->getMessage()); // Log para depuração
    header('Location: list_clients.php?error=Ocorreu um erro inesperado ao excluir o cliente: ' . urlencode($e->getMessage()));
    exit();
} finally {
    // Garante que a conexão seja fechada
    if ($conn) {
        $conn->close();
    }
}
?>