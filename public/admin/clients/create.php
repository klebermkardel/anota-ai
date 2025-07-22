<?php 
session_start();

require_once __DIR__ . '/../../../app/config/database.php';

if(!isset($_SESSION['user_id']) || $_SESSION['nivel_acesso'] != 'admin') {
    header('Location: ../../login.php?error=Acesso negado. Apenas administradores podem cadastrar clientes.');
    exit();
}

$message = '';
if (isset($_GET['success'])) {
    $message = '<div class="alert alert-success alert-dismissible fade show" role="alert">' . htmlspecialchars($_GET['success']) . '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>';
} elseif (isset($_GET['error'])) {
    $message = '<div class="alert alert-danger alert-dismissible fade show" role="alert">' . htmlspecialchars($_GET['error']) . '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>';
}

?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cadastrar Novo Cliente - Anota Aí</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-LN+7fdVzj6u52u30Kp6M/trliBMCMKTyK833zpbD+pXdCLuTusPj697FH4R/5mcr" crossorigin="anonymous">
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
            border-radius: 50rem !important; /* Makes it truly pill-shaped */
        }
    </style>
</head>
<body>
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
                        <a class="nav-link active" href="./create.php">Cadastrar Cliente</a> </li>
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

    <div class="container mt-5 mb-5">
        <div class="row justify-content-center">
            <div class="col-md-9">
                <div class="card form-card">
                    <div class="card-header bg-success text-white">
                        <h1 class="card-title mb-0">Cadastrar Novo Cliente</h1>
                    </div>
                    <div class="card-body">
                        <?php echo $message; // Exibe mensagens ?>

                        <form action="process_create.php" method="POST">
                            <h2 class="h5 mb-4 text-primary">Dados Pessoais do Cliente</h2>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="nome" class="form-label">Nome Completo:</label>
                                    <input type="text" class="form-control rounded-pill" id="nome" name="nome" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="telefone" class="form-label">Telefone:</label>
                                    <input type="text" class="form-control rounded-pill" id="telefone" name="telefone" required>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="email" class="form-label">E-mail:</label>
                                    <input type="email" class="form-control rounded-pill" id="email" name="email" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="empresa" class="form-label">Empresa:</label>
                                    <input type="text" class="form-control rounded-pill" id="empresa" name="empresa" required>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label for="setor" class="form-label">Setor:</label>
                                <input type="text" class="form-control rounded-pill" id="setor" name="setor" required>
                            </div>
                            <div class="mb-3">
                                <label for="observacoes" class="form-label">Observações:</label>
                                <textarea class="form-control rounded-3" id="observacoes" name="observacoes" rows="4"></textarea>
                            </div>

                            <hr class="my-5">

                            <h2 class="h5 mb-4 text-info">Dados de Acesso (Criar conta de usuário)</h2>
                            <div class="form-check form-switch mb-4">
                                <input class="form-check-input" type="checkbox" role="switch" name="criar_usuario" id="criar_usuario" value="1">
                                <label class="form-check-label" for="criar_usuario">
                                    Criar conta de usuário para este cliente?
                                </label>
                            </div>

                            <div id="user-account-fields" style="display: none;">
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="username" class="form-label">Nome de Usuário (Login):</label>
                                        <input type="text" class="form-control rounded-pill" id="username" name="username">
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="password" class="form-label">Senha:</label>
                                        <input type="password" class="form-control rounded-pill" id="password" name="password">
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <label for="confirm_password" class="form-label">Confirmar Senha:</label>
                                    <input type="password" class="form-control rounded-pill" id="confirm_password" name="confirm_password">
                                </div>
                            </div>

                            <div class="d-flex justify-content-between mt-5">
                                <button type="submit" class="btn btn-primary btn-lg rounded-pill px-4">Cadastrar Cliente</button>
                                <a href="../dashboard.php" class="btn btn-outline-secondary btn-lg rounded-pill px-4">Voltar para o Dashboard Admin</a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.8/dist/umd/popper.min.js" integrity="sha384-I7E8VVD/ismYTF4hNIPjVp/Zjvgyol6VFvRkX/vR+Vc4jQkC+hVqc2pM8ODewa9r" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/js/bootstrap.min.js" integrity="sha384-7qAoOXltbVP82dhxHAUje59V5r2YsVfBafyUDxEdApLPmcdhBPg1DKg1ERo0BZlK" crossorigin="anonymous"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const criarUsuarioCheckbox = document.getElementById('criar_usuario');
            const userAccountFields = document.getElementById('user-account-fields');

            // Ajuste para o switch do Bootstrap
            criarUsuarioCheckbox.classList.add('form-check-input');
            criarUsuarioCheckbox.setAttribute('role', 'switch');

            criarUsuarioCheckbox.addEventListener('change', function() {
                if (this.checked) {
                    userAccountFields.style.display = 'block';
                    userAccountFields.querySelectorAll('input').forEach(input => {
                        input.setAttribute('required', 'required');
                    });
                } else {
                    userAccountFields.style.display = 'none';
                    userAccountFields.querySelectorAll('input').forEach(input => {
                        input.removeAttribute('required');
                        input.value = '';
                    });
                }
            });
        });
    </script>
</body>
</html>