<?php
session_start();

// Inclui o arquivo de configuração do banco de dados
require_once __DIR__ . '/../../../app/config/database.php';

// 1. Controle de Acesso: Verifica se o usuário está logado e se é um admin
if (!isset($_SESSION['user_id']) || $_SESSION['nivel_acesso'] !== 'admin') {
    header('Location: ../../login.php?error=Acesso negado. Apenas administradores podem visualizar clientes.');
    exit();
}

$clientes = []; // Para armazenar os clientes e seus saldos
$message = ''; // Para mensagens de feedback

try {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    if ($conn->connect_error) {
        throw new Exception("Erro de conexão com o banco de dados: " . $conn->connect_error);
    }
    if (!empty(DB_CHARSET)) {
        $conn->set_charset(DB_CHARSET);
    }

    // 2. Consulta SQL CORRIGIDA para buscar clientes e calcular o saldo devedor TOTAL
    // Usamos sub-consultas para somar vendas e pagamentos separadamente, evitando a multiplicação de linhas.
    $sql_clientes = "
        SELECT
            c.id,
            c.nome,
            c.telefone,
            c.email,
            c.empresa,
            c.setor,
            c.observacoes,
            COALESCE(SUM_VENDAS.total_devido, 0) AS total_devido,
            COALESCE(SUM_PAGAMENTOS.total_pago, 0) AS total_pago
        FROM
            clientes c
        LEFT JOIN (
            SELECT cliente_id, SUM(valor_total) AS total_devido
            FROM vendas
            GROUP BY cliente_id
        ) AS SUM_VENDAS ON c.id = SUM_VENDAS.cliente_id
        LEFT JOIN (
            SELECT cliente_id, SUM(valor_pago) AS total_pago
            FROM pagamentos
            GROUP BY cliente_id
        ) AS SUM_PAGAMENTOS ON c.id = SUM_PAGAMENTOS.cliente_id
        ORDER BY
            c.nome ASC
    ";

    $result_clientes = $conn->query($sql_clientes);

    if ($result_clientes) {
        while ($row = $result_clientes->fetch_assoc()) {
            // Calcula o saldo devedor final para exibição
            $row['saldo_devedor'] = $row['total_devido'] - $row['total_pago'];
            $clientes[] = $row;
        }
    } else {
        throw new Exception("Erro ao buscar clientes: " . $conn->error);
    }

} catch (Exception $e) {
    error_log("Erro ao carregar lista de clientes: " . $e->getMessage());
    $message = '<div class="alert alert-danger alert-dismissible fade show mt-3" role="alert">Não foi possível carregar os clientes: ' . htmlspecialchars($e->getMessage()) . '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>';
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
    <title>Listar Clientes - Anota Aí (Admin)</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-LN+7fdVzj6u52u30Kp6M/trliBMCMKTyK833zpbD+pXdCLuTusPj697FH4R/5mcr" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        body { 
            background-color: #f8f9fa;
            padding-top: 70px;
        }

        .table-container {
            background-color: #ffffff;
            padding: 20px;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
        }

        .navbar-brand {
            font-weight: bold;
        }

        .nav-link {
            font-weight: 500;
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

        .saldo-devedor-positivo {
            color: #dc3545; /* Vermelho para saldo devedor */
            font-weight: bold;
        }

        .saldo-devedor-zero {
            color: #28a745; /* Verde para saldo zero ou positivo */
            font-weight: bold;
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-success shadow-sm fixed-top">
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
                        <a class="nav-link" href="./create.php">Cadastrar Cliente</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" aria-current="page" href="./list_clients.php">Listar Clientes</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="../sales/create_sales.php">Registrar Venda</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="../sales/list_sales.php">Histórico de Vendas</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="../payments/create_payments.php">Gerenciar Pagamentos</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link btn btn-danger btn-sm ms-lg-3 px-3 rounded-pill" href="../../logout.php">Sair</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container mt-5 mb-5">
        <h1 class="mb-4 text-center text-success">Lista de Clientes</h1>

        <?php echo $message; // Exibe mensagens de feedback ?>

        <div class="d-flex justify-content-end mb-3">
            <a href="create.php" class="btn btn-success rounded-pill px-4"><i class="bi bi-person-plus me-2"></i> Cadastrar Novo Cliente</a>
        </div>

        <div class="table-container">
            <?php if (empty($clientes)): ?>
                <div class="alert alert-info text-center" role="alert">
                    Nenhum cliente cadastrado ainda.
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover table-striped align-middle">
                        <thead>
                            <tr>
                                <th>Nome</th>
                                <th>Telefone</th>
                                <th>Email</th>
                                <th>Empresa</th>
                                <th>Setor</th>
                                <th>Saldo Devedor</th>
                                <th class="text-center">Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($clientes as $cliente): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($cliente['nome']); ?></td>
                                    <td><?php echo htmlspecialchars($cliente['telefone']); ?></td>
                                    <td><?php echo htmlspecialchars($cliente['email']); ?></td>
                                    <td><?php echo htmlspecialchars($cliente['empresa']); ?></td>
                                    <td><?php echo htmlspecialchars($cliente['setor']); ?></td>
                                    <td>
                                        <span class="<?php echo ($cliente['saldo_devedor'] > 0) ? 'saldo-devedor-positivo' : 'saldo-devedor-zero'; ?>">
                                            R$ <?php echo number_format($cliente['saldo_devedor'], 2, ',', '.'); ?>
                                        </span>
                                    </td>
                                    <td class="text-center">
                                        <a href="edit.php?id=<?php echo htmlspecialchars($cliente['id']); ?>" class="btn btn-sm btn-outline-primary btn-action" title="Editar"><i class="bi bi-pencil"></i> Editar</a>
                                        <a href="delete_clients.php?id=<?php echo htmlspecialchars($cliente['id']); ?>" class="btn btn-sm btn-outline-danger btn-action" title="Excluir" onclick="return confirm('Tem certeza que deseja excluir este cliente e todas as suas vendas e pagamentos associados? Esta ação é irreversível!');"><i class="bi bi-trash"></i> Excluir</a>
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