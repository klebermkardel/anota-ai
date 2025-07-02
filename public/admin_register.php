<?php
/**
 * Página de Registro de Administrador
 * anota_ai/public/admin_register.php
 *
 * Permite que um novo usuário com nível de acesso 'admin' seja registrado no sistema.
 * Inclui o formulário HTML e a lógica PHP para processar o registro.
 */

// Inclui o script de conexão com o banco de dados
require_once __DIR__ . '/../includes/db_connect.php';

$message = ''; // Variável para armazenar mensagens de sucesso ou erro

// Verifica se o formulário foi submetido
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);
    $confirm_password = trim($_POST['confirm_password']);

    // Validação básica dos campos
    if (empty($username) || empty($password) || empty($confirm_password)) {
        $message = '<div class="alert alert-danger" role="alert">Por favor, preencha todos os campos.</div>';
    } elseif ($password !== $confirm_password) {
        $message = '<div class="alert alert-danger" role="alert">As senhas não coincidem.</div>';
    } elseif (strlen($password) < 6) {
        $message = '<div class="alert alert-danger" role="alert">A senha deve ter pelo menos 6 caracteres.</div>';
    } else {
        // Hash da senha para segurança
        $password_hash = password_hash($password, PASSWORD_DEFAULT);

        // Verifica se o nome de usuário já existe
        $stmt = $conn->prepare("SELECT id FROM usuarios WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            $message = '<div class="alert alert-danger" role="alert">Nome de usuário já existe. Por favor, escolha outro.</div>';
        } else {
            // Insere o novo usuário no banco de dados com nível de acesso 'admin'
            $stmt = $conn->prepare("INSERT INTO usuarios (username, password_hash, nivel_acesso) VALUES (?, ?, 'admin')");
            $stmt->bind_param("ss", $username, $password_hash);

            if ($stmt->execute()) {
                $message = '<div class="alert alert-success" role="alert">Administrador registrado com sucesso! Você pode fazer login agora.</div>';
                // Opcional: Redirecionar para a página de login após o registro bem-sucedido
                // header("Location: login.php");
                // exit();
            } else {
                $message = '<div class="alert alert-danger" role="alert">Erro ao registrar administrador: ' . $stmt->error . '</div>';
            }
        }
        $stmt->close();
    }
}

// Fecha a conexão com o banco de dados
// É uma boa prática fechar a conexão quando não for mais necessária,
// embora o PHP a feche automaticamente no final do script.
$conn->close();
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registrar Administrador - Anota Aí</title>
    <!-- Link para o Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" xintegrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <!-- Seu CSS personalizado -->
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background-color: #f8f9fa;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            margin: 0;
        }
        .register-container {
            background-color: #ffffff;
            padding: 40px;
            border-radius: 10px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            width: 100%;
            max-width: 400px;
        }
        .register-container h2 {
            margin-bottom: 30px;
            color: #0d6efd; /* Cor primária do Bootstrap */
            text-align: center;
        }
        .register-container h3{
            margin-bottom: 15px;
            text-align: center;
        }
        .form-control, .btn {
            border-radius: 5px;
        }
        .btn-primary {
            background-color: #0d6efd;
            border-color: #0d6efd;
        }
        .btn-primary:hover {
            background-color: #0b5ed7;
            border-color: #0a58ca;
        }
    </style>
</head>
<body>
    <div class="register-container">
        <h2>Anota Aí</h2>
        <h3>Cadastrar Administrador</h3>
        <?php echo $message; // Exibe mensagens de sucesso ou erro ?>
        <form action="admin_register.php" method="POST">
            <div class="mb-3">
                <label for="username" class="form-label">Nome de Usuário:</label>
                <input type="text" class="form-control" id="username" name="username" required>
            </div>
            <div class="mb-3">
                <label for="password" class="form-label">Senha:</label>
                <input type="password" class="form-control" id="password" name="password" required>
            </div>
            <div class="mb-3">
                <label for="confirm_password" class="form-label">Confirmar Senha:</label>
                <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
            </div>
            <button type="submit" class="btn btn-primary w-100">Registrar</button>
        </form>
        <p class="mt-3 text-center">
            Já tem uma conta? <a href="login.php">Faça Login aqui</a>
        </p>
    </div>

    <!-- Link para o Bootstrap JavaScript (Bundle com Popper) -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" xintegrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
</body>
</html>
