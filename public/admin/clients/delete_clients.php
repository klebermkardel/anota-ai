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

    // Inicia uma transação para garantir que ambas as exclusões (usuário e cliente) sejam atômicas
    $conn->begin_transaction();

    // PRIMEIRA ETAPA: Deletar o usuário associado (se existir)
    // Buscamos o ID do usuário para o cliente_id
    $sql_get_user_id = "SELECT id FROM usuarios WHERE cliente_id = ?";
    $stmt_get_user_id = $conn->prepare($sql_get_user_id);
    if ($stmt_get_user_id === false) {
        throw new Exception("Erro ao preparar busca de usuário: " . $conn->error);
    }
    $stmt_get_user_id->bind_param('i', $cliente_id);
    $stmt_get_user_id->execute();
    $result_user_id = $stmt_get_user_id->get_result();

    if ($result_user_id->num_rows > 0) {
        $user_data = $result_user_id->fetch_assoc();
        $user_id_to_delete = $user_data['id'];

        $sql_delete_user = "DELETE FROM usuarios WHERE id = ?";
        $stmt_delete_user = $conn->prepare($sql_delete_user);
        if ($stmt_delete_user === false) {
            throw new Exception("Erro ao preparar exclusão de usuário: " . $conn->error);
        }
        $stmt_delete_user->bind_param('i', $user_id_to_delete);
        if (!$stmt_delete_user->execute()) {
            throw new Exception("Erro ao excluir usuário associado: " . $stmt_delete_user->error);
        }
        $stmt_delete_user->close();
    }
    $stmt_get_user_id->close();


    // SEGUNDA ETAPA: Deletar o cliente
    // As chaves estrangeiras em 'vendas' e 'pagamentos' (ON DELETE CASCADE) farão o resto
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
        $conn->commit(); // Commita a transação se ambas as exclusões foram bem-sucedidas
        header('Location: list_clients.php?success=Cliente e conta de usuário associada excluídos com sucesso. Vendas e pagamentos do cliente também foram removidos.');
        exit();
    } else {
        $conn->rollback(); // Reverte se nenhuma linha de cliente foi afetada (cliente não encontrado)
        header('Location: list_clients.php?error=Cliente com ID ' . $cliente_id . ' não encontrado para exclusão.');
        exit();
    }

} catch (Exception $e) {
    // Em caso de qualquer erro, reverte a transação
    if ($conn) {
        $conn->rollback();
    }
    error_log('Erro na exclusão de cliente e usuário: ' . $e->getMessage()); // Log para depuração
    header('Location: list_clients.php?error=Ocorreu um erro inesperado ao excluir o cliente e seu usuário: ' . urlencode($e->getMessage()));
    exit();
} finally {
    // Garante que a conexão seja fechada
    if ($conn) {
        $conn->close();
    }
}
?>