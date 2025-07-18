<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Anota Aí - Login</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-LN+7fdVzj6u52u30Kp6M/trliBMCMKTyK833zpbD+pXdCLuTusPj697FH4R/5mcr" crossorigin="anonymous">
    <style>
        body {
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            background-color: #e9ecef; /* Um cinza claro suave */
        }
        .login-card {
            max-width: 420px;
            padding: 35px;
            border-radius: 12px; /* Bordas mais arredondadas */
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08); /* Sombra mais pronunciada */
            background-color: #ffffff;
            border: none; /* Remover borda padrão do card */
        }
        .form-label {
            font-weight: 500; /* Mais destaque para os labels */
            color: #495057;
        }
        .btn-primary {
            background-color: #007bff; /* Azul padrão do Bootstrap */
            border-color: #007bff;
            transition: background-color 0.3s ease, border-color 0.3s ease;
        }
        .btn-primary:hover {
            background-color: #0056b3; /* Tom mais escuro no hover */
            border-color: #0056b3;
        }
    </style>
</head>
<body>
    <div class="card login-card">
        <div class="card-body">
            <h2 class="card-title text-center mb-4 fw-bold">Anota Aí</h2>
            <p class="card-text text-center text-secondary mb-4">Faça login para continuar</p>
            
            <form action="processa_login.php" method="POST">
                <div class="mb-3">
                    <label for="username" class="form-label">Usuário:</label>
                    <input type="text" class="form-control form-control-lg rounded-pill" id="username" name="username" required>
                </div>
                <div class="mb-4">
                    <label for="password" class="form-label">Senha:</label>
                    <input type="password" class="form-control form-control-lg rounded-pill" id="password" name="password" required>
                </div>
                <button type="submit" class="btn btn-primary btn-lg w-100 rounded-pill">Entrar</button>
            </form>

            <?php 
            // Exibe mensagens de erro ou sucesso, se houver
            if(isset($_GET['error'])) {
                echo '<div class="alert alert-danger mt-4 text-center" role="alert">' . htmlspecialchars($_GET['error']) . '</div>';
            }
            ?>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.8/dist/umd/popper.min.js" integrity="sha384-I7E8VVD/ismYTF4hNIPjVp/Zjvgyol6VFvRkX/vR+Vc4jQkC+hVqc2pM8ODewa9r" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/js/bootstrap.min.js" integrity="sha384-7qAoOXltbVP82dhxHAUje59V5r2YsVfBafyUDxEdApLPmcdhBPg1DKg1ERo0BZlK" crossorigin="anonymous"></script>

</body>
</html>