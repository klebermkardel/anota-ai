<?php 
    /**
     * Script de Conexão com o Banco de Dados
     * anota_ai/includes/db_connect.php
     * 
     * Este script estabelece a conexão com o banco de dados MySQL
     * Ele inclui o arquivo de configuração para obter as credenciais 
    */

    // Inclui o arquivo de configuração com as credenciais do banco de dados
    // O caminho é relativo à localização deste arquivo (includes/)
    require_once __DIR__ . '/../config/config.php';

    // Tenta estabelecer a conexão com o banco de dados
    try {
        $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

        // Verifica se houve algum erro na conexão
        if($conn->connect_error) {
            // Em um ambiente de produção, você registraria o erro e mostraria uma mensagem genérica.
            // Para desenvolvimento, podemos mostrar o erro detalhado.
            throw new Exception("Falha na conexão com o banco de dados: " . $conn->connect_error);
        }

         // Define o conjunto de caracteres da conexão para evitar problemas com acentuação
        $conn->set_charset(DB_CHARSET);

        // Opcional: Mensagem de sucesso na conexão (apenas para depuração)
        // echo "Conexão com o banco de dados estabelecida com sucesso!";
    } catch(Exception $e) {
        // Captura a exceção e exibe uma mensagem de erro.
        // Em produção, você pode redirecionar para uma página de erro ou logar o problema.
        die("Erro crítico: Não foi possível conectar ao banco de dados. Por favor, tente novamente mais tarde. Detalhes: " . $e->getMessage());
    }

    // A variável $conn agora contém o objeto de conexão com o banco de dados.
    // Você pode usá-la em outros arquivos PHP que incluírem db_connect.php.
?>