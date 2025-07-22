<?php
session_start();

// Inclui o arquivo de configuração do banco de dados
require_once __DIR__ . '/../../../app/config/database.php';

// Controle de Acesso: Verifica se o usuário está logado e se é um admin
if (!isset($_SESSION['user_id']) || $_SESSION['nivel_acesso'] !== 'admin') {
    header('Location: ../../login.php?error=Acesso negado. Apenas administradores podem registrar pagamentos.');
    exit();
}

$clientes_com_saldo = []; // Para armazenar os clientes e seus saldos para seleção
$message = ''; // Para mensagens de feedback

try {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    if ($conn->connect_error) {
        throw new Exception("Erro de conexão com o banco de dados: " . $conn->connect_error);
    }
    if (!empty(DB_CHARSET)) {
        $conn->set_charset(DB_CHARSET);
    }

    // Consulta SQL CORRIGIDA para buscar clientes e calcular o saldo devedor total de cada um
    // Usamos sub-consultas para somar vendas e pagamentos separadamente, evitando a multiplicação de linhas.
    $sql_clientes_com_saldo = "
        SELECT
            c.id,
            c.nome,
            -- Calculamos o total de vendas usando uma subconsulta
            COALESCE(SUM_VENDAS.total_devido, 0) AS total_vendas,
            -- Calculamos o total de pagamentos usando uma subconsulta
            COALESCE(SUM_PAGAMENTOS.total_pago, 0) AS total_pagamentos,
            -- Calculamos o saldo devedor final
            (COALESCE(SUM_VENDAS.total_devido, 0) - COALESCE(SUM_PAGAMENTOS.total_pago, 0)) AS saldo_devedor_cliente
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
        -- Opcional: Filtra para exibir apenas clientes com saldo devedor positivo ou que já tiveram vendas/pagamentos
        -- Removi a cláusula WHERE/HAVING do PHP para deixar o filtro aqui no SQL, mais eficiente.
        WHERE
            (COALESCE(SUM_VENDAS.total_devido, 0) - COALESCE(SUM_PAGAMENTOS.total_pago, 0)) > 0 OR COALESCE(SUM_VENDAS.total_devido, 0) > 0
        ORDER BY
            c.nome ASC
    ";

    $result_clientes_com_saldo = $conn->query($sql_clientes_com_saldo);

    if ($result_clientes_com_saldo) {
        while ($row = $result_clientes_com_saldo->fetch_assoc()) {
            // A lógica de filtragem foi movida para o SQL (cláusula WHERE/HAVING)
            $clientes_com_saldo[] = $row;
        }
    } else {
        throw new Exception("Erro ao buscar clientes com saldo: " . $conn->error);
    }

} catch (Exception $e) {
    error_log("Erro ao carregar clientes para registro de pagamento: " . $e->getMessage());
    $message = '<div class="alert alert-danger alert-dismissible fade show mt-3" role="alert">Não foi possível carregar os clientes: ' . htmlspecialchars($e->getMessage()) . '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>';
} finally {
    if ($conn) {
        $conn->close();
    }
}

// Mensagens de feedback (ex: após sucesso/erro de registro de pagamento)
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
    <title>Registrar Pagamento - Anota Aí (Admin)</title>
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

        .form-container {
            background-color: #ffffff;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
        }
        
        .header-bg {
            background-color: #28a745; /* Verde */
            color: white;
            padding: 15px 20px;
            border-radius: 12px 12px 0 0;
            margin: -30px -30px 30px -30px; /* Ajusta para preencher o topo do container */
            text-align: center;
        }

        .input-group-text {
            background-color: #e9ecef;
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
                        <a class="nav-link" href="../clients/create.php">Cadastrar Cliente</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="../clients/list_clients.php">Listar Clientes</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="../sales/create_sales.php">Registrar Venda</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="../sales/list_sales.php">Histórico de Vendas</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" aria-current="page" href="./create_payments.php">Gerenciar Pagamentos</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link btn btn-danger btn-sm ms-lg-3 px-3 rounded-pill" href="../../logout.php">Sair</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container mt-5 mb-5">
        <div class="form-container">
            <div class="header-bg">
                <h2 class="mb-0">Registrar Novo Pagamento</h2>
            </div>

            <?php echo $message; // Exibe mensagens de feedback ?>

            <?php if (empty($clientes_com_saldo)): ?>
                <div class="alert alert-info text-center" role="alert">
                    Não há clientes com saldo devedor ou vendas registradas para registrar pagamentos.
                    <a href="../clients/create.php" class="alert-link">Clique aqui para cadastrar um novo cliente</a> ou
                    <a href="../sales/create_sales.php" class="alert-link">registrar uma nova venda.</a>
                </div>
            <?php else: ?>
                <form action="process_payment.php" method="POST">
                    <div class="mb-3">
                        <label for="cliente_id" class="form-label">Cliente:</label>
                        <select class="form-select" id="cliente_id" name="cliente_id" required>
                            <option value="">Selecione um cliente</option>
                            <?php foreach ($clientes_com_saldo as $cliente): ?>
                                <option value="<?php echo htmlspecialchars($cliente['id']); ?>">
                                    <?php echo htmlspecialchars($cliente['nome']); ?>
                                    <?php if ($cliente['saldo_devedor_cliente'] > 0): ?>
                                        (Saldo Devedor: R$ <?php echo number_format($cliente['saldo_devedor_cliente'], 2, ',', '.'); ?>)
                                    <?php endif; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label for="valor_pago" class="form-label">Valor Pago:</label>
                        <div class="input-group">
                            <span class="input-group-text">R$</span>
                            <input type="number" class="form-control" id="valor_pago" name="valor_pago" step="0.01" min="0.01" required placeholder="Ex: 150.75">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="data_pagamento" class="form-label">Data do Pagamento:</label>
                        <input type="date" class="form-control" id="data_pagamento" name="data_pagamento" value="<?php echo date('Y-m-d'); ?>" required>
                    </div>

                    <div class="mb-4">
                        <label for="observacoes" class="form-label">Observações (Opcional):</label>
                        <textarea class="form-control" id="observacoes" name="observacoes" rows="3" placeholder="Ex: Pagamento via PIX, Referente à entrada..."></textarea>
                    </div>

                    <div class="d-flex justify-content-between mt-5">
                        <button type="submit" class="btn btn-success btn-lg rounded-pill px-4"><i class="bi bi-cash-coin me-2"></i> Registrar Pagamento</button>
                    </div>
                </form>
            <?php endif; ?>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/js/bootstrap.bundle.min.js" integrity="sha384-I7E8VVD/ismYTF4hNIPjVp/Zjvgyol6VFvRkX/vR+Vc4jQkC+hVqc2pM8ODewa9r" crossorigin="anonymous"></script>
</body>
</html>