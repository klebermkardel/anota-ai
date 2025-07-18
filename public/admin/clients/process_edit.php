<?php
session_start();

// Inclui o arquivo de configuração do banco de dados
require_once __DIR__ . '/../../../app/config/database.php';

// 1. Controle de Acesso: Verifica se o usuário está logado e se é um admin
if (!isset($_SESSION['user_id']) || $_SESSION['nivel_acesso'] !== 'admin') {
    header('Location: ../../login.php?error=Acesso negado. Apenas administradores podem processar edições.');
    exit();
}

// 2. Verifica se a requisição é POST e se o ID do cliente foi enviado
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['cliente_id']) || !is_numeric($_POST['cliente_id'])) {
    header('Location: list_clients.php?error=Requisição inválida ou ID do cliente não fornecido.');
    exit();
}

$cliente_id = (int)$_POST['cliente_id']; // Garante que é um inteiro
$conn = null; // Inicializa a conexão como null

try {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    if ($conn->connect_error) {
        throw new Exception("Erro de conexão com o banco de dados: " . $conn->connect_error);
    }
    if (!empty(DB_CHARSET)) {
        $conn->set_charset(DB_CHARSET);
    }

    // Inicia uma transação para garantir a atomicidade das operações
    $conn->begin_transaction();

    // 3. Coleta e sanitiza os dados do CLIENTE
    $nome = trim($_POST['nome'] ?? '');
    $telefone = trim($_POST['telefone'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $empresa = trim($_POST['empresa'] ?? '');
    $setor = trim($_POST['setor'] ?? '');
    $observacoes = trim($_POST['observacoes'] ?? '');

    // Validação básica dos dados do cliente (nome é obrigatório)
    if (empty($nome)) {
        throw new Exception("O nome do cliente é obrigatório.");
    }

    // 4. Atualiza o cliente na tabela 'clientes'
    $sql_update_cliente = "UPDATE clientes SET nome = ?, telefone = ?, email = ?, empresa = ?, setor = ?, observacoes = ? WHERE id = ?";
    $stmt_update_cliente = $conn->prepare($sql_update_cliente);
    if ($stmt_update_cliente === false) {
        throw new Exception("Erro ao preparar a consulta de atualização de cliente: " . $conn->error);
    }
    $stmt_update_cliente->bind_param('ssssssi', $nome, $telefone, $email, $empresa, $setor, $observacoes, $cliente_id);

    if (!$stmt_update_cliente->execute()) {
        throw new Exception("Erro ao atualizar cliente: " . $stmt_update_cliente->error);
    }
    $stmt_update_cliente->close();

    // 5. Lógica para gerenciar a conta de usuário associada
    $gerenciar_usuario = isset($_POST['gerenciar_usuario']) && $_POST['gerenciar_usuario'] === '1';
    $user_id_from_form = $_POST['user_id'] ?? null; // ID do usuário se já existia uma conta

    // Caso A: Checkbox "Gerenciar Usuário" marcado
    if ($gerenciar_usuario) {
        // Se já existe um user_id no formulário (usuário existente)
        if ($user_id_from_form) {
            // Atualizar senha do usuário existente (se uma nova senha foi fornecida)
            $new_password = $_POST['new_password'] ?? '';
            $confirm_new_password = $_POST['confirm_new_password'] ?? '';

            if (!empty($new_password)) { // Se uma nova senha foi digitada
                if ($new_password !== $confirm_new_password) {
                    throw new Exception("As novas senhas não coincidem.");
                }
                $new_password_hash = password_hash($new_password, PASSWORD_DEFAULT);

                $sql_update_user_pass = "UPDATE usuarios SET password_hash = ? WHERE id = ?";
                $stmt_update_user_pass = $conn->prepare($sql_update_user_pass);
                if ($stmt_update_user_pass === false) {
                    throw new Exception("Erro ao preparar a atualização de senha: " . $conn->error);
                }
                $stmt_update_user_pass->bind_param('si', $new_password_hash, $user_id_from_form);
                if (!$stmt_update_user_pass->execute()) {
                    throw new Exception("Erro ao atualizar senha do usuário: " . $stmt_update_user_pass->error);
                }
                $stmt_update_user_pass->close();
            }
            // else: Se new_password está vazio, não faz nada (não altera a senha)
        } else {
            // Criar um NOVO usuário para este cliente (se ainda não existia)
            $username = trim(strtolower($_POST['username'] ?? ''));
            $password = $_POST['new_password'] ?? ''; // Usamos new_password para o campo de senha inicial
            $confirm_password = $_POST['confirm_new_password'] ?? '';

            if (empty($username) || empty($password) || empty($confirm_password)) {
                throw new Exception("Todos os campos de usuário (usuário, nova senha, confirmar senha) são obrigatórios para criar uma nova conta.");
            }
            if ($password !== $confirm_password) {
                throw new Exception("As senhas não coincidem ao criar uma nova conta.");
            }

            $password_hash = password_hash($password, PASSWORD_DEFAULT);
            $nivel_acesso = 'cliente';

            $sql_insert_user = "INSERT INTO usuarios (username, password_hash, nivel_acesso, cliente_id) VALUES (?, ?, ?, ?)";
            $stmt_insert_user = $conn->prepare($sql_insert_user);
            if ($stmt_insert_user === false) {
                throw new Exception("Erro ao preparar a inserção de usuário: " . $conn->error);
            }
            $stmt_insert_user->bind_param('sssi', $username, $password_hash, $nivel_acesso, $cliente_id);

            if (!$stmt_insert_user->execute()) {
                if ($conn->errno == 1062) { // Erro de chave duplicada (username)
                    throw new Exception("Nome de usuário já existe. Por favor, escolha outro.");
                }
                throw new Exception("Erro ao inserir novo usuário: " . $stmt_insert_user->error);
            }
            $stmt_insert_user->close();
        }
    } else {
        // Caso B: Checkbox "Gerenciar Usuário" NÃO marcado
        // Se havia um usuário associado, devemos desvinculá-lo (definir cliente_id como NULL)
        // ou deletá-lo, dependendo da sua regra de negócio.
        // Por enquanto, vamos desvincular (definir cliente_id como NULL)
        if ($user_id_from_form) { // Se já existia um usuário para esse cliente
            $sql_unlink_user = "UPDATE usuarios SET cliente_id = NULL WHERE id = ?";
            $stmt_unlink_user = $conn->prepare($sql_unlink_user);
            if ($stmt_unlink_user === false) {
                throw new Exception("Erro ao preparar a desvinculação do usuário: " . $conn->error);
            }
            $stmt_unlink_user->bind_param('i', $user_id_from_form);
            if (!$stmt_unlink_user->execute()) {
                throw new Exception("Erro ao desvincular usuário do cliente: " . $stmt_unlink_user->error);
            }
            $stmt_unlink_user->close();
        }
    }

    // Se tudo deu certo, commita a transação
    $conn->commit();
    header('Location: list_clients.php?success=Cliente e dados de acesso atualizados com sucesso!');
    exit();

} catch (Exception $e) {
    // Em caso de qualquer erro, reverte a transação
    if ($conn) {
        $conn->rollback();
    }
    error_log('Erro na edição de cliente/usuário: ' . $e->getMessage()); // Log para depuração
    header('Location: edit.php?id=' . $cliente_id . '&error=' . urlencode($e->getMessage()));
    exit();
} finally {
    // Garante que a conexão seja fechada
    if ($conn) {
        $conn->close();
    }
}
?>