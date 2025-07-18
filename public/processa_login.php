<?php 

// Inicia a sessão PHP. Essencial para armazenar informações do usuário logado.
session_start();

// Inclui o arquivo de configuração do banco de dados
require_once __DIR__ . '/../app/config/database.php';

// Redireciona para a página de login se a requisição não for POST (acesso direto)
if($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: login.php?error=Acesso inválido.');
    exit();
}

// 1. Coleta e sanitiza os dados do formulário
$username = $_POST['username'] ?? '';
$password = $_POST['password'] ?? '';

// Remove espaços em branco do início e fim e converte para minúsculas (opcional, mas bom para usernames)
$username = trim(strtolower($username));
$password = trim($password);

// 2. Validação básica (campos vazios)
if (empty($username) || empty($password)) {
    header('Location: login.php?error=Preencha todos os campos. ');
    exit();
}

try {
    // 3. Conexão com o banco de dados usando MySQLi
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

    if($conn->connect_error) {
        throw new Exception("Erro de conexão: " . $conn->connect_error);
    }

    // Opcional: Define o charset da conexão para evitar problemas com caracteres especiais
    if (!empty(DB_CHARSET)) {
        $conn->set_charset(DB_CHARSET);
    }

    // 4. Prepara a consulta SQL para buscar o usuário
    // Usamos prepared statements para segurança contra SQL Injection
    $sql = "SELECT id, username, password_hash, nivel_acesso, cliente_id FROM usuarios WHERE username = ?";
    $stmt = $conn->prepare($sql);

    if ($stmt === false) {
        throw new Exception("Erro ao preparar a consulta: " . $conn->error);
    }

    // Binda o parâmetro (s - string) e executa a consulta
    $stmt->bind_param('s', $username);
    $stmt->execute();
    $result = $stmt->get_result();

    // 5. Verifica se o usuário existe
    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();

        // 6. Verifica a senha usando password_verify()
        // password_verify() compara a senha fornecida com o hash armazenado
        if (password_verify($password, $user['password_hash'])) {
            // Senha correta: Inicia a sessão do usuário
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['nivel_acesso'] = $user['nivel_acesso'];
            $_SESSION['cliente_id'] = $user['cliente_id']; // Pode ser null para admins

            // Redireciona para uma página de dashboard ou área restrita
            if ($user['nivel_acesso'] === 'admin') {
                header('Location: admin/dashboard.php'); // Página para administradores
            } else {
                header('Location: client_dashboard.php'); // Página para clientes
            }
            exit();

        } else {
            // Senha incorreta
            header('Location: login.php?error=Usuário ou senha inválidos.');
            exit();
        }
    } else {
        // Usuário não encontrado
        header('Location: login.php?error=Usuário ou senha inválidos.');
        exit();
    }
} catch (Exception $e) {
    // Em caso de erro na conexão ou query, redireciona com mensagem de erro
    // Em produção, você logaria o erro detalhado e mostraria uma mensagem genérica
    error_log('Erro no login: ' . $e->getMessage()); // Registra o erro no log do servidor
    header('Location: login.php?error=Ocorreu um erro inesperado. Tente novamente mais tarde.');
    exit();
} finally {
    // Garante que a conexão seja fechada, se estiver aberta
    if (isset($conn) && $conn instanceof mysqli) {
        $conn->close();
    }
}
?>