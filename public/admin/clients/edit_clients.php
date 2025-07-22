<?php
session_start();

// Inclui o arquivo de configuração do banco de dados
require_once __DIR__ . '/../../../app/config/database.php';

// 1. Controle de Acesso: Verifica se o usuário está logado e se é um admin
if (!isset($_SESSION['user_id']) || $_SESSION['nivel_acesso'] !== 'admin') {
    header('Location: ../../login.php?error=Acesso negado. Apenas administradores podem editar clientes.');
    exit();
}

// 2. Verifica se um ID de cliente foi passado via GET
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: list_clients.php?error=ID do cliente inválido ou não fornecido.');
    exit();
}

$cliente_id = $_GET['id'];
$cliente = null; // Variável para armazenar os dados do cliente
$usuario_associado = null; // Variável para armazenar os dados do usuário associado (se houver)
$message = ''; // Para mensagens de sucesso/erro

try {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    if ($conn->connect_error) {
        throw new Exception("Erro de conexão com o banco de dados: " . $conn->connect_error);
    }
    if (!empty(DB_CHARSET)) {
        $conn->set_charset(DB_CHARSET);
    }

    // 3. Busca os dados do cliente
    $sql_cliente = "SELECT id, nome, telefone, email, empresa, setor, observacoes FROM clientes WHERE id = ?";
    $stmt_cliente = $conn->prepare($sql_cliente);
    if ($stmt_cliente === false) {
        throw new Exception("Erro ao preparar consulta de cliente: " . $conn->error);
    }
    $stmt_cliente->bind_param('i', $cliente_id);
    $stmt_cliente->execute();
    $result_cliente = $stmt_cliente->get_result();

    if ($result_cliente->num_rows === 1) {
        $cliente = $result_cliente->fetch_assoc();
    } else {
        throw new Exception("Cliente não encontrado.");
    }
    $stmt_cliente->close();

    // 4. Busca os dados do usuário associado, se existir
    // Usamos LEFT JOIN para garantir que o cliente seja exibido mesmo se não tiver um usuário associado
    $sql_usuario = "SELECT id as user_id, username FROM usuarios WHERE cliente_id = ?";
    $stmt_usuario = $conn->prepare($sql_usuario);
    if ($stmt_usuario === false) {
        throw new Exception("Erro ao preparar consulta de usuário: " . $conn->error);
    }
    $stmt_usuario->bind_param('i', $cliente_id);
    $stmt_usuario->execute();
    $result_usuario = $stmt_usuario->get_result();

    if ($result_usuario->num_rows === 1) {
        $usuario_associado = $result_usuario->fetch_assoc();
    }
    $stmt_usuario->close();

} catch (Exception $e) {
    error_log("Erro ao carregar dados do cliente para edição: " . $e->getMessage());
    header('Location: list_clients.php?error=Não foi possível carregar os dados do cliente: ' . urlencode($e->getMessage()));
    exit();
} finally {
    if ($conn) {
        $conn->close();
    }
}

// Mensagens de feedback (ex: após falha na atualização)
if (isset($_GET['success'])) {
    $message = '<div class="alert alert-success alert-dismissible fade show mt-3" role="alert">' . htmlspecialchars($_GET['success']) . '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>';
} elseif (isset($_GET['error'])) {
    $message = '<div class="alert alert-danger alert-dismissible fade show mt-3" role="alert">' . htmlspecialchars($_GET['error']) . '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>';
}

?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Cliente - Anota Aí (Admin)</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-LN+7fdVzj6u52u30Kp6M/trliBMCMKTyK833zpbD+pXdCLuTusPj697FH4R/5mcr" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        body { 
            background-color: #f8f9fa;
            padding-top: 70px;
        }

        .navbar-brand {
            font-weight: bold;
        }

        .nav-link {
            font-weight: 500;
        }

        .form-card {
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
            border: none;
        }

        .form-label { 
            font-weight: 500; 
            color: #495057; 
        }
        
        .form-control.rounded-pill {
            border-radius: 50rem !important; 
        }
    </style>
</head>
<body>
    <div class="container mt-5 mb-5">
    <nav class="navbar navbar-expand-lg navbar-dark bg-success shadow-sm fixed-top">
        <div class="container">
            <a class="navbar-brand" href="../dashboard.php">Anota Aí - Admin</a> <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse justify-content-end" id="navbarNav">
                <ul class="navbar-nav">
                    <li class="nav-item">
                        <a class="nav-link" aria-current="page" href="../dashboard.php">Dashboard</a> </li>
                    <li class="nav-item">
                        <a class="nav-link" href="./create.php">Cadastrar Cliente</a> </li>
                    <li class="nav-item">
                        <a class="nav-link" href="./list_clients.php">Listar Clientes</a> </li>
                    <li class="nav-item">
                        <a class="nav-link" href="../sales/create_sales.php">Gerenciar Vendas</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="../sales/list_sales.php">Histórico de Vendas</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="../payments/create_payments.php">Gerenciar Pagamentos</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link btn btn-danger btn-sm ms-lg-3 px-3 rounded-pill" href="../../logout.php">Sair</a> </li>
                </ul>
            </div>
        </div>
    </nav>
    
        <div class="row justify-content-center">
            <div class="col-md-9">
                <div class="card form-card">
                    <div class="card-header bg-primary text-white">
                        <h1 class="card-title mb-0">Editar Cliente: <?php echo htmlspecialchars($cliente['nome']); ?></h1>
                    </div>
                    <div class="card-body">
                        <?php echo $message; // Exibe mensagens ?>

                        <form action="process_edit.php" method="POST">
                            <input type="hidden" name="cliente_id" value="<?php echo htmlspecialchars($cliente['id']); ?>">
                            
                            <h2 class="h5 mb-4 text-primary">Dados Pessoais do Cliente</h2>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="nome" class="form-label">Nome Completo:</label>
                                    <input type="text" class="form-control rounded-pill" id="nome" name="nome" value="<?php echo htmlspecialchars($cliente['nome']); ?>" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="telefone" class="form-label">Telefone:</label>
                                    <input type="text" class="form-control rounded-pill" id="telefone" name="telefone" value="<?php echo htmlspecialchars($cliente['telefone']); ?>" required>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="email" class="form-label">E-mail:</label>
                                    <input type="email" class="form-control rounded-pill" id="email" name="email" value="<?php echo htmlspecialchars($cliente['email']); ?>" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="empresa" class="form-label">Empresa:</label>
                                    <input type="text" class="form-control rounded-pill" id="empresa" name="empresa" value="<?php echo htmlspecialchars($cliente['empresa']); ?>" required>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label for="setor" class="form-label">Setor:</label>
                                <input type="text" class="form-control rounded-pill" id="setor" name="setor" value="<?php echo htmlspecialchars($cliente['setor']); ?>" required>
                            </div>
                            <div class="mb-3">
                                <label for="observacoes" class="form-label">Observações:</label>
                                <textarea class="form-control rounded-3" id="observacoes" name="observacoes" rows="4"><?php echo htmlspecialchars($cliente['observacoes']); ?></textarea>
                            </div>

                            <div class="d-flex justify-content-between mt-5">
                                <button type="submit" class="btn btn-primary btn-lg rounded-pill px-4"><i class="bi bi-save me-2"></i> Salvar Alterações</button>
                                <a href="list_clients.php" class="btn btn-outline-secondary btn-lg rounded-pill px-4"><i class="bi bi-arrow-left-circle me-2"></i> Voltar para a Lista</a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/js/bootstrap.bundle.min.js" integrity="sha384-I7E8VVD/ismYTF4hNIPjVp/Zjvgyol6VFvRkX/vR+Vc4jQkC+hVqc2pM8ODewa9r" crossorigin="anonymous"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const gerenciarUsuarioCheckbox = document.getElementById('gerenciar_usuario');
            const userAccountFields = document.getElementById('user-account-fields');
            const usernameInput = document.getElementById('username');
            const newPasswordInput = document.getElementById('new_password');
            const confirmNewPasswordInput = document.getElementById('confirm_new_password');

            // Função para ajustar os atributos 'required'
            function toggleRequiredAttributes() {
                // Se a caixa estiver marcada, os campos de senha (nova e confirmação) se tornam obrigatórios
                // APENAS se estiverem sendo preenchidos (se não forem deixar em branco)
                // O campo username só é obrigatório se não existir um usuário e o campo for editável
                if (gerenciarUsuarioCheckbox.checked) {
                    if (!usernameInput.readOnly) { // Se o username pode ser editado (novo usuário)
                        usernameInput.setAttribute('required', 'required');
                    }
                } else {
                    usernameInput.removeAttribute('required');
                    newPasswordInput.removeAttribute('required');
                    confirmNewPasswordInput.removeAttribute('required');
                    // Limpar valores para evitar envio acidental de senhas antigas/incompletas
                    newPasswordInput.value = '';
                    confirmNewPasswordInput.value = '';
                }
            }

            // Chama a função ao carregar a página e ao mudar o checkbox
            toggleRequiredAttributes(); // Para o estado inicial
            gerenciarUsuarioCheckbox.addEventListener('change', toggleRequiredAttributes);

            // Ajuste específico: A nova senha só é obrigatória se preenchida
            newPasswordInput.addEventListener('input', function() {
                if (this.value.length > 0) {
                    confirmNewPasswordInput.setAttribute('required', 'required');
                } else {
                    confirmNewPasswordInput.removeAttribute('required');
                }
            });
            confirmNewPasswordInput.addEventListener('input', function() {
                if (this.value.length > 0) {
                    newPasswordInput.setAttribute('required', 'required');
                } else {
                    newPasswordInput.removeAttribute('required');
                }
            });
        });
    </script>
</body>
</html>