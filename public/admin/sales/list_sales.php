<?php
session_start();

// Inclui o arquivo de configuração do banco de dados
require_once __DIR__ . '/../../../app/config/database.php';

// 1. Controle de Acesso: Verifica se o usuário está logado e se é um admin
if (!isset($_SESSION['user_id']) || $_SESSION['nivel_acesso'] !== 'admin') {
    header('Location: ../../login.php?error=Acesso negado. Apenas administradores podem visualizar vendas.');
    exit();
}

$vendas = []; // Para armazenar a lista de vendas
$message = ''; // Para mensagens de feedback

try {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    if ($conn->connect_error) {
        throw new Exception("Erro de conexão com o banco de dados: " . $conn->connect_error);
    }
    if (!empty(DB_CHARSET)) {
        $conn->set_charset(DB_CHARSET);
    }

    // 2. Consulta SQL para buscar todas as vendas, incluindo o nome do cliente
    $sql_vendas = "
        SELECT
            v.id,
            v.cliente_id,
            c.nome AS nome_cliente,
            v.data_venda,
            v.valor_total,
            v.descricao_itens,
            v.status_venda,
            v.observacoes,
            v.data_registro
        FROM
            vendas v
        JOIN
            clientes c ON v.cliente_id = c.id
        ORDER BY
            v.data_venda DESC, v.id DESC
    ";

    $result_vendas = $conn->query($sql_vendas);

    if ($result_vendas) {
        $vendas = $result_vendas->fetch_all(MYSQLI_ASSOC);
        $result_vendas->free(); // Libera o resultado
    } else {
        throw new Exception("Erro ao buscar vendas: " . $conn->error);
    }

} catch (Exception $e) {
    error_log("Erro ao carregar lista de vendas: " . $e->getMessage());
    $message = '<div class="alert alert-danger alert-dismissible fade show mt-3" role="alert">Não foi possível carregar as vendas: ' . htmlspecialchars($e->getMessage()) . '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>';
} finally {
    if ($conn) {
        $conn->close();
    }
}

// Mensagens de feedback (ex: após sucesso/erro de criação, edição ou exclusão)
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
    <title>Lista de Vendas - Anota Aí (Admin)</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-LN+7fdVzj6u52u30Kp6M/trliBMCMKTyK833zpbD+pXdCLuTusPj697FH4R/5mcr" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        body { background-color: #f8f9fa; }
        .table-container {
            background-color: #ffffff;
            padding: 20px;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
        }
        .table thead th {
            background-color: #28a745; /* Cor verde do cabeçalho */
            color: white;
            vertical-align: middle;
        }
        .table tbody tr:hover {
            background-color: #e2f0d9; /* Um verde mais claro ao passar o mouse */
        }
        .btn-action {
            margin-right: 5px;
        }
        /* Estilos para status de venda */
        .badge-pendente { background-color: #ffc107; color: #343a40; } /* Amarelo */
        .badge-concluida { background-color: #28a745; color: white; } /* Verde */
        .badge-cancelada { background-color: #dc3545; color: white; } /* Vermelho */
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-success shadow-sm">
        <div class="container">
            <a class="navbar-brand" href="../dashboard.php">Anota Aí - Admin</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse justify-content-end" id="navbarNav">
                <ul class="navbar-nav">
                    <li class="nav-item">
                        <a class="nav-link" href="../dashboard.php">Dashboard</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="../clients/create.php">Cadastrar Cliente</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="../clients/list_clients.php">Listar Clientes</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="./create_sales.php">Registrar Venda</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" aria-current="page" href="./list_sales.php">Gerenciar Vendas</a> </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#">Gerenciar Pagamentos</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link btn btn-danger btn-sm ms-lg-3 px-3 rounded-pill" href="../../logout.php">Sair</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container mt-5 mb-5">
        <h1 class="mb-4 text-center text-success">Lista de Vendas</h1>

        <?php echo $message; // Exibe mensagens de feedback ?>

        <div class="d-flex justify-content-end mb-3">
            <a href="./create_sales.php" class="btn btn-success rounded-pill px-4"><i class="bi bi-plus-circle me-2"></i> Registrar Nova Venda</a>
        </div>

        <div class="table-container">
            <?php if (empty($vendas)): ?>
                <div class="alert alert-info text-center" role="alert">
                    Nenhuma venda registrada ainda.
                    <a href="./create_sales.php" class="alert-link">Clique aqui para registrar a primeira venda.</a>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover table-striped align-middle">
                        <thead>
                            <tr>
                                <th>Cliente</th>
                                <th>Data Venda</th>
                                <th>Valor Total</th>
                                <th>Descrição</th>
                                <th>Status</th>
                                <th>Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($vendas as $venda): ?>
                                <tr>
                                    <td>
                                            <?php echo htmlspecialchars($venda['nome_cliente']); ?>
                                    </td>
                                    <td><?php echo date('d/m/Y', strtotime($venda['data_venda'])); ?></td>
                                    <td>R$ <?php echo number_format($venda['valor_total'], 2, ',', '.'); ?></td>
                                    <td><?php echo nl2br(htmlspecialchars($venda['descricao_itens'])); ?></td>
                                    <td>
                                        <span class="badge rounded-pill badge-<?php echo htmlspecialchars($venda['status_venda']); ?>">
                                            <?php echo ucfirst(htmlspecialchars($venda['status_venda'])); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <a href="edit_sales.php?id=<?php echo htmlspecialchars($venda['id']); ?>" class="btn btn-sm btn-outline-primary btn-action" title="Editar Venda"><i class="bi bi-pencil-square"></i></a>
                                        <a href="delete_sales.php?id=<?php echo htmlspecialchars($venda['id']); ?>" class="btn btn-sm btn-outline-danger btn-action" title="Excluir Venda" onclick="return confirm('Tem certeza que deseja excluir esta venda? Esta ação também removerá todos os pagamentos associados.');"><i class="bi bi-trash"></i></a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/js/bootstrap.bundle.min.js" integrity="sha384-I7E8VVD/ismYTF4hNIPjVp/Zjvgyol6VFvRkX/vR+Vc4jQkC+hVqc2pM8ODewa9r" crossorigin="anonymous"></script>
</body>
</html>