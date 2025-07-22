<?php
session_start();

// Inclui o arquivo de configuração do banco de dados
// Caminho do require_once:
// delete_sales.php (public/admin/sales/) -> ../ (public/admin/) -> ../ (public/) -> ../ (anota_ai/) -> app/config/
require_once __DIR__ . '/../../../app/config/database.php';

// 1. Controle de Acesso: Verifica se o usuário está logado e se é um admin
if (!isset($_SESSION['user_id']) || $_SESSION['nivel_acesso'] !== 'admin') {
    // Caminho para login.php: public/admin/sales/ -> ../../ (public/) -> login.php
    header('Location: ../../login.php?error=Acesso negado. Apenas administradores podem excluir vendas.');
    exit();
}

// 2. Verifica se a requisição é GET e se um ID de venda foi passado
// A exclusão geralmente é acionada por um link GET, com confirmação via JS no frontend.
if ($_SERVER['REQUEST_METHOD'] !== 'GET' || !isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: list_sales.php?error=Requisição inválida ou ID da venda não fornecido para exclusão.');
    exit();
}

$venda_id = (int)$_GET['id']; // Garante que é um inteiro
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

    // 3. Deleta a venda da tabela 'vendas'
    // A chave estrangeira em 'pagamentos' (ON DELETE CASCADE) fará com que os pagamentos associados sejam deletados.
    $sql_delete_venda = "DELETE FROM vendas WHERE id = ?";
    $stmt_delete_venda = $conn->prepare($sql_delete_venda);

    if ($stmt_delete_venda === false) {
        throw new Exception("Erro ao preparar a consulta de exclusão de venda: " . $conn->error);
    }

    $stmt_delete_venda->bind_param('i', $venda_id);

    if (!$stmt_delete_venda->execute()) {
        throw new Exception("Erro ao excluir venda: " . $stmt_delete_venda->error);
    }

    // Verifica se alguma linha foi afetada (se a venda realmente existia)
    if ($stmt_delete_venda->affected_rows > 0) {
        $conn->commit(); // Commita a transação se a exclusão foi bem-sucedida
        header('Location: list_sales.php?success=Venda excluída com sucesso. Pagamentos associados também foram removidos.');
        exit();
    } else {
        $conn->rollback(); // Reverte se nenhuma linha foi afetada (venda não encontrada)
        header('Location: list_sales.php?error=Venda com ID ' . $venda_id . ' não encontrada para exclusão.');
        exit();
    }

    $stmt_delete_venda->close();

} catch (Exception $e) {
    // Em caso de qualquer erro, reverte a transação
    if ($conn) {
        $conn->rollback();
    }
    error_log('Erro na exclusão de venda: ' . $e->getMessage()); // Log para depuração
    header('Location: list_sales.php?error=Ocorreu um erro inesperado ao excluir a venda: ' . urlencode($e->getMessage()));
    exit();
} finally {
    // Garante que a conexão seja fechada
    if ($conn) {
        $conn->close();
    }
}
?>