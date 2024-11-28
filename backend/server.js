// 1. Importação de módulos
const express = require('express');
const mysql = require('mysql');
const bodyParser = require('body-parser');
const cors = require('cors');

// 2. Configuração do servidor
const app = express();
const port = 3000;

// 3. Configuração de middlewares
app.use(cors());
app.use(bodyParser.json());

// 4. Conexão com o banco de dados
const db = mysql.createConnection({
    host: 'localhost',
    user: 'root',       
    password: '@Ck2024kc',       
    database: 'anota_ai'
});

db.connect(err => {
    if (err) {
        console.error('Erro ao conectar no banco de dados:', err);
        return;
    }
    console.log('Conectado ao banco de dados.');
});

// 5. Rota básica de teste
app.get('/', (req, res) => {
    res.send('Servidor funcionando!');
});

// 6. Rota de cadastro de administrador
app.post('/cadastro-admin', (req, res) => {
    const { nome, username, email, password } = req.body;
    console.log('Dados recebidos:', { nome, username, email, password }); // Log dos dados recebidos

    // Validação dos dados
    if (!nome || !username || !email || !password) {
        console.log('Campos obrigatórios faltando'); // Log de erro
        return res.status(400).send({ error: 'Todos os campos são obrigatórios!' });
    }

    // Verifica se o usuário já existe
    const checkUserQuery = 'SELECT * FROM admins WHERE username = ?';
    db.query(checkUserQuery, [username], (err, results) => {
        if (err) {
            console.error('Erro na verificação do usuário:', err); // Log de erro no banco
            return res.status(500).send({ error: 'Erro no servidor ao verificar usuário.' });
        }

        if (results.length > 0) {
            console.log('Usuário já existe'); // Log para usuário duplicado
            return res.status(400).send({ error: 'Usuário já existe!' });
        }

        // Insere o novo administrador no banco de dados
        const insertQuery = 'INSERT INTO admins (nome, username, email, password) VALUES (?, ?, ?, ?)';
        db.query(insertQuery, [nome, username, email, password], (err) => {
            if (err) {
                console.error('Erro ao inserir administrador:', err); // Log de erro ao inserir
                res.status(500).send({ error: 'Erro ao cadastrar administrador.' });
            } else {
                console.log('Administrador cadastrado com sucesso'); // Log de sucesso
                res.status(201).send({ message: 'Administrador cadastrado com sucesso!' });
            }
        });
    });
});

// Rota de login de administrador
app.post('/login-admin', (req, res) => {
    const { username, password } = req.body;

    console.log('Dados de login recebidos:', { username, password });  // Adicione este log

    // Validação básica dos dados
    if (!username || !password) {
        return res.status(400).send({ error: 'Todos os campos são obrigatórios!' });
    }

    // Consulta ao banco para verificar o administrador
    const query = 'SELECT * FROM admins WHERE username = ? AND password = ?';
    db.query(query, [username, password], (err, results) => {
        if (err) {
            console.error('Erro ao verificar login:', err);  // Verifique se o erro está aqui
            return res.status(500).send({ error: 'Erro no servidor.' });
        }

        if (results.length > 0) {
            res.status(200).send({ message: 'Login bem-sucedido!' });
        } else {
            res.status(401).send({ error: 'Usuário ou senha incorretos!' });
        }
    });
});



// 7. Início do servidor
app.listen(port, () => {
    console.log(`Servidor rodando em http://localhost:${port}`);
});
