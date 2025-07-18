<?php 

session_start();

// Inclui o arquivo de configuração do banco de dados
require_once __DIR__ . '/../../../app/config/database.php';

// 1. Controle de Acesso: Verifica se o usuário está logado e se é um admin
if(!isset($_SESSION['user_id']) || $_SESSION['nivel_acesso'] !== 'admin') {
    header('Location: ../../login.php?error=Acesso negado. Apenas administradores podem processar cadastros.');
    exit();
}

// 2. Verifica se a requisição é POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: create.php?error=Acesso inválido. Formulário deve ser enviado via POST.');
    exit();
}

// Inicializa a conexão como null para o bloco finally
$conn = null;

try {
    // Conexão com o banco de dados    
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    if ($conn->connect_error) {
        throw new Exception("Erro de conexão com o banco de dados: " . $conn->connect_error);
    }
    if (!empty(DB_CHARSET)) {
        $conn->set_charset(DB_CHARSET);
    } 

    // Inicia uma transação. Isso garante que, se houver um erro ao inserir o usuário,
    // a inserção do cliente também será revertida, mantendo a integridade dos dados.
    $conn->begin_transaction();

    // 3. Coleta e sanitiza os dados do CLIENTE
    $nome = trim($_POST['nome'] ?? '');
    $telefone = trim($_POST['telefone'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $empresa = trim($_POST['empresa'] ?? '');
    $setor = trim($_POST['setor'] ?? '');
    $observacoes = trim($_POST['observacoes'] ?? '');

    // Validação básica dos dados do cliente (ex: nome é obrigatório)
    if (empty($nome) || empty($telefone) || empty($email) || empty($empresa) || empty($setor)) {
        $conn->rollback(); // Reverte a transação
        header('Location: create.php?error=Todos os campos (exceto observações) são obrigatórios.');
        exit();
    }

    // 4. Insere o cliente na tabela 'clientes'
    $sql_cliente = "INSERT INTO clientes (nome, telefone, email, empresa, setor, observacoes) VALUES (?, ?, ?, ?, ?, ?)";
    $stmt_cliente = $conn->prepare($sql_cliente);

    if ($stmt_cliente === false) {
        throw new Exception("Erro ao preparar a consulta de cliente: " . $conn->connect_error);
    }

    // Binda os parâmetros para a inserção do cliente
    $stmt_cliente->bind_param('ssssss', $nome, $telefone, $email, $empresa, $setor, $observacoes);

    if (!$stmt_cliente->execute()) {
        throw new Exception("Erro ao insetir cliente: " . $stmt_cliente->error);
    }

    $cliente_id = $conn->insert_id; // Obtém o ID do cliente recém-inserido

    $stmt_cliente->close();

    // 5. Verifica se uma conta de usuário deve ser criada para este cliente
    $criar_usuario = isset($_POST['criar_usuario']) && $_POST['criar_usuario'] === '1';

    if ($criar_usuario) {
        // Coleta e sanitiza os dados do USUÁRIO
        $username = trim(strtolower($_POST['username'] ?? ''));
        $password = $_POST['password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';

        // Validação dos dados do usuário
        if (empty($username) || empty($password) || empty($confirm_password)) {
            $conn->rollback(); // Reverte a transação
            header('Location: create.php?error=Todos os campos devem ser preenchidos.');
            exit();
        }

        if ($password !== $confirm_password) {
            $conn->rollback(); // Reverte a transação
            header('Location: create.php?error="As senhas não coincidem.');
            exit();
        }

        // Gera o hash da senha
        $password_hash = password_hash($password, PASSWORD_DEFAULT);

        // 6. Insere o usuário na tabela 'usuarios'
        // O nivel_acesso para clientes cadastrados aqui será sempre 'cliente'
        $nivel_acesso = 'cliente';
        $sql_usuario = "INSERT INTO usuarios (username, password_hash, nivel_acesso, cliente_id) VALUES (?, ?, ?, ?)";
        $stmt_usuario = $conn->prepare($sql_usuario);

        if ($stmt_usuario === false) {
            throw new Exception("Erro ao preparar a consulta de usuário: ". $conn->error);
        }

        // Binda os parâmetros para a inserção do usuário 
        $stmt_usuario->bind_param('sssi', $username, $password_hash, $nivel_acesso, $cliente_id);

        if (!$stmt_usuario->execute()) {
            // Verifica se o erro é por username duplicado
            if ($conn->errno == 1062) { // 1062 é o codigo de erro para chave duplicada (UNIQUE constraint)
                $conn->rollback();
                header('Location: create.php?error=Nome de usuário já existe. Por favor, escolha outro.');
                exit();
            }
            throw new Exception("Erro ao inserir usuário: " . $stmt_usuario->error);
        }

        $stmt_usuario->close();
    }

    // Se tudo deu certo, commita a transação
    $conn->commit();
    header('Location: create.php?sucess=Cliente e conta de usuário cadastrados com sucesso');
    exit();
} catch (Exception $e) {
    // Em caso de qualquer erro, reverte a transação
    if ($conn) {
        $conn->rollback();
    }
    // Loga o erro para depuração (em produção, não mostre ao usuário)
    error_log('Erro no cadastro de cliente/usuário: ' . $e->getMessage());
    header('Location: create.php?error=Ocorreu um erro inesperado: ' . $e->getMessage());
    exit();
} finally {
    // Garante que a conexão seja fechada
    if ($conn) {
        $conn->close();
    }
}

?>