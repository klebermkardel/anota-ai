<?php
session_start(); // Inicia a sessão

// Destrói todas as variáveis de sessão
$_SESSION = array();

// Se a sessão for controlada por cookies, exclui também o cookie de sessão.
// Nota: Isso apagará o cookie, mas não a sessão no servidor até que ela expire.
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Finalmente, destrói a sessão
session_destroy();

// Redireciona para a página de login
header('Location: login.php?message=Você foi desconectado.');
exit();
?>