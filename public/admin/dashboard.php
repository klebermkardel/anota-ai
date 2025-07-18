<?php
session_start();

// Verifica se o usuário está logado e se é um admin
// O caminho para login.php agora é '../login.php' porque dashboard.php está em public/admin/
// e login.php está em public/
if (!isset($_SESSION['user_id']) || $_SESSION['nivel_acesso'] !== 'admin') {
    header('Location: ../login.php?error=Acesso negado.');
    exit();
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Admin - Anota Aí</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-LN+7fdVzj6u52u30Kp6M/trliBMCMKTyK833zpbD+pXdCLuTusPj697FH4R/5mcr" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        body {
            background-color: #f8f9fa;
        }
        .main-card {
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
            border: none;
        }
        .navbar-brand {
            font-weight: bold;
        }
        .nav-link {
            font-weight: 500;
        }
        .list-group-item-action:hover {
            background-color: #e9ecef; /* Light gray on hover */
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-success shadow-sm">
        <div class="container">
            <a class="navbar-brand" href="./dashboard.php">Anota Aí - Admin</a> <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse justify-content-end" id="navbarNav">
                <ul class="navbar-nav">
                    <li class="nav-item">
                        <a class="nav-link active" aria-current="page" href="./dashboard.php">Dashboard</a> </li>
                    <li class="nav-item">
                        <a class="nav-link" href="./clients/create.php">Cadastrar Cliente</a> </li>
                    <li class="nav-item">
                        <a class="nav-link" href="./clients/list_clients.php">Listar Clientes</a> </li>
                    <li class="nav-item">
                        <a class="nav-link" href="./sales/create_sales.php">Gerenciar Vendas</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="./sales/list_sales.php">Histórico de Vendas</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="./payments/create_payments.php">Gerenciar Pagamentos</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link btn btn-danger btn-sm ms-lg-3 px-3 rounded-pill" href="../logout.php">Sair</a> </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-9">
                <div class="card main-card">
                    <div class="card-body">
                        <h1 class="card-title mb-4 text-success">Bem-vindo, Administrador <?php echo htmlspecialchars($_SESSION['username']); ?>!</h1>
                        <p class="card-text fs-5 text-secondary">Esta é a sua área administrativa. Gerencie clientes, vendas e pagamentos do food truck.</p>
                        
                        <h2 class="mt-5 mb-3 text-dark">Ações Rápidas</h2>
                        <div class="list-group list-group-flush">
                            <a href="./clients/create.php" class="list-group-item list-group-item-action py-3 d-flex justify-content-between align-items-center">
                                <i class="bi bi-person-plus-fill me-2"></i> Cadastrar Novo Cliente
                                <span class="badge bg-primary rounded-pill">Novo</span>
                            </a>
                            <a href="./clients/list_clients.php" class="list-group-item list-group-item-action py-3 d-flex justify-content-between align-items-center">
                                <i class="bi bi-people-fill me-2"></i> Ver e Gerenciar Clientes
                                <span class="badge bg-info rounded-pill">Acessar</span>
                            </a>
                            <a href="./sales/create_sales.php" class="list-group-item list-group-item-action py-3 d-flex justify-content-between align-items-center">
                                <i class="bi bi-cart-fill me-2"></i> Registrar Venda
                                <span class="badge bg-success rounded-pill">Rápido</span>
                            </a>
                            <a href="../admin/payments/create_payments.php" class="list-group-item list-group-item-action py-3 d-flex justify-content-between align-items-center">
                                <i class="bi bi-currency-dollar me-2"></i> Registrar Pagamento
                                <span class="badge bg-warning text-dark rounded-pill">Ação</span>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/js/bootstrap.bundle.min.js" integrity="sha384-I7E8VVD/ismYTF4hNIPjVp/Zjvgyol6VFvRkX/vR+Vc4jQkC+hVqc2pM8ODewa9r" crossorigin="anonymous"></script>
</body>
</html>