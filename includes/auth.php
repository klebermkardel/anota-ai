<?php
/**
 * Sistema de Autenticação e Sessão
 * anota_ai/includes/auth.php
 *
 * Contém funções para gerenciar a sessão do usuário, verificar status de login
 * e redirecionar usuários não autenticados.
 */

// Inicia a sessão se ainda não estiver iniciada
// É importante chamar session_start() no início de cada script que usa sessões.
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

/**
 * Verifica se o usuário está logado.
 * @return bool True se o usuário estiver logado, false caso contrário.
 */
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

/**
 * Verifica se o usuário logado tem um nível de acesso específico.
 * @param string $required_level O nível de acesso necessário (ex: 'admin', 'cliente').
 * @return bool True se o usuário tiver o nível de acesso necessário, false caso contrário.
 */
function hasAccess($required_level) {
    return isLoggedIn() && $_SESSION['nivel_acesso'] === $required_level;
}

/**
 * Redireciona o usuário para a página de login se não estiver logado.
 * Opcionalmente, pode verificar um nível de acesso específico.
 * @param string|null $required_level O nível de acesso necessário. Se null, apenas verifica se está logado.
 * @param string $redirect_url A URL para redirecionar se o acesso for negado.
 */
function requireLogin($required_level = null, $redirect_url = '/anota_ai/public/login.php') {
    if (!isLoggedIn()) {
        header("Location: " . $redirect_url);
        exit();
    }

    if ($required_level !== null && !hasAccess($required_level)) {
        // Se o usuário está logado mas não tem o nível de acesso necessário
        // Pode redirecionar para uma página de "acesso negado" ou para o dashboard principal
        header("Location: " . $redirect_url . "?access_denied=true");
        exit();
    }
}

/**
 * Realiza o logout do usuário, destruindo a sessão.
 */
function logout() {
    // Destrói todas as variáveis de sessão
    $_SESSION = array();

    // Se for preciso destruir completamente a sessão, também deleta o cookie de sessão.
    // Nota: Isso irá destruir a sessão, e não apenas os dados da sessão!
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
    header("Location: /anota_ai/public/login.php");
    exit();
}

?>
