<?php
session_start();

// Inclui o arquivo de configuração do banco de dados
require_once __DIR__ . '/../../../app/config/database.php';

// 1. Controle de Acesso: Verifica se o usuário está logado e se é um admin
if (!isset($_SESSION['user_id']) || $_SESSION['nivel_acesso'] !== 'admin') {
    header('Location: ../../login.php?error=Acesso negado. Apenas administradores podem editar vendas.');
    exit();
}

// 2. Verifica se um ID de venda foi passado via GET
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: list_sales.php?error=ID da venda inválido ou não fornecido.');
    exit();
}

$venda_id = (int)$_GET['id'];
$venda = null; // Variável para armazenar os dados da venda
$clientes = []; // Para armazenar a lista de clientes para o dropdown
$message = ''; // Para mensagens de sucesso/erro

try {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    if ($conn->connect_error) {
        throw new Exception("Erro de conexão com o banco de dados: " . $conn->connect_error);
    }
    if (!empty(DB_CHARSET)) {
        $conn->set_charset(DB_CHARSET);
    }

    // 3. Busca os dados da venda específica
    $sql_venda = "SELECT id, cliente_id, data_venda, valor_total, descricao_itens, status_venda, observacoes FROM vendas WHERE id = ?";
    $stmt_venda = $conn->prepare($sql_venda);
    if ($stmt_venda === false) {
        throw new Exception("Erro ao preparar consulta de venda: " . $conn->error);
    }
    $stmt_venda->bind_param('i', $venda_id);
    $stmt_venda->execute();
    $result_venda = $stmt_venda->get_result();

    if ($result_venda->num_rows === 1) {
        $venda = $result_venda->fetch_assoc();
    } else {
        throw new Exception("Venda não encontrada.");
    }
    $stmt_venda->close();

    // 4. Busca a lista de clientes para o dropdown (para poder mudar o cliente da venda)
    $sql_clientes = "SELECT id, nome FROM clientes ORDER BY nome ASC";
    $result_clientes = $conn->query($sql_clientes);

    if ($result_clientes) {
        while ($row = $result_clientes->fetch_assoc()) {
            $clientes[] = $row;
        }
    } else {
        throw new Exception("Erro ao buscar clientes para dropdown: " . $conn->error);
    }

} catch (Exception $e) {
    error_log("Erro ao carregar dados da venda para edição: " . $e->getMessage());
    header('Location: list_sales.php?error=Não foi possível carregar os dados da venda: ' . urlencode($e->getMessage()));
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
    <title>Editar Venda - Anota Aí (Admin)</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-LN+7fdVzj6u52u30Kp6M/trliBMCMKTyK833zpbD+pXdCLuTusPj697FH4R/5mcr" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        body { 
            background-color: #f8f9fa;
            padding-top: 70px; /* Para compensar o navbar fixo */
        }
        .navbar-brand { font-weight: bold; }
        .nav-link { font-weight: 500; }
        .form-card {
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
            border: none;
        }
        .form-label { font-weight: 500; color: #495057; }
        .form-control.rounded-pill, .form-select.rounded-pill { border-radius: 50rem !important; }
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
                        <a class="nav-link" href="./create_sales.php">Registrar Venda</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" aria-current="page" href="./list_sales.php">Histórico de Vendas</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="../payments/create_payment.php">Registrar Pagamento</a>
                    </li>
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
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card form-card">
                    <div class="card-header bg-primary text-white">
                        <h1 class="card-title mb-0">Editar Venda #<?php echo htmlspecialchars($venda['id']); ?></h1>
                    </div>
                    <div class="card-body">
                        <?php echo $message; // Exibe mensagens ?>

                        <form action="process_edit_sales.php" method="POST">
                            <input type="hidden" name="venda_id" value="<?php echo htmlspecialchars($venda['id']); ?>">
                            
                            <div class="mb-3">
                                <label for="cliente_id" class="form-label">Cliente:</label>
                                <select class="form-select rounded-pill" id="cliente_id" name="cliente_id" required>
                                    <option value="">Selecione um cliente</option>
                                    <?php foreach ($clientes as $cliente): ?>
                                        <option value="<?php echo htmlspecialchars($cliente['id']); ?>" <?php echo ($cliente['id'] == $venda['cliente_id']) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($cliente['nome']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="data_venda" class="form-label">Data da Venda:</label>
                                    <input type="date" class="form-control rounded-pill" id="data_venda" name="data_venda" value="<?php echo htmlspecialchars($venda['data_venda']); ?>" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="valor_total" class="form-label">Valor Total:</label>
                                    <div class="input-group rounded-pill">
                                        <span class="input-group-text rounded-start-pill">R$</span>
                                        <input type="number" step="0.01" class="form-control rounded-end-pill" id="valor_total" name="valor_total" placeholder="0.00" value="<?php echo htmlspecialchars($venda['valor_total']); ?>" required min="0.01">
                                    </div>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="descricao_itens" class="form-label">Descrição da Venda:</label>
                                <textarea class="form-control rounded-3" id="descricao_itens" name="descricao_itens" rows="3" placeholder="Ex: X-Burguer, Refrigerante, Batata Frita"><?php echo htmlspecialchars($venda['descricao_itens']); ?></textarea>
                            </div>

                            <div class="mb-4">
                                <label for="status_venda" class="form-label">Status da Venda:</label>
                                <select class="form-select rounded-pill" id="status_venda" name="status_venda" required>
                                    <option value="pendente" <?php echo ($venda['status_venda'] == 'pendente') ? 'selected' : ''; ?>>Pendente</option>
                                    <option value="concluida" <?php echo ($venda['status_venda'] == 'concluida') ? 'selected' : ''; ?>>Concluída</option>
                                    <option value="cancelada" <?php echo ($venda['status_venda'] == 'cancelada') ? 'selected' : ''; ?>>Cancelada</option>
                                </select>
                            </div>

                            <div class="d-flex justify-content-between mt-4">
                                <button type="submit" class="btn btn-primary btn-lg rounded-pill px-4"><i class="bi bi-save me-2"></i> Salvar Alterações</button>
                                <a href="list_sales.php" class="btn btn-outline-secondary btn-lg rounded-pill px-4"><i class="bi bi-arrow-left-circle me-2"></i> Voltar para o Histórico</a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/js/bootstrap.bundle.min.js" integrity="sha384-I7E8VVD/ismYTF4hNIPjVp/Zjvgyol6VFvRkX/vR+Vc4jQkC+hVqc2pM8ODewa9r" crossorigin="anonymous"></script>
</body>
</html>