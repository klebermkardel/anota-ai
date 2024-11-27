const express = require('express');
const mysql = require("mysql");
const bodyParser = require("body-parser");
const cors = require('cors');

const app = express();
const port = 3000;

// Middleware
app.use(cors());
app.use(bodyParser.json());

// Configuração do Banco de Dados
const db = mysql.createConnection({
    host: 'localhost',
    user: 'root',
    password: '@Ck2024kc',
    database: 'anota_ai',
});

db.connect(err => {
    if (err) throw err;
    console.log('Conectado ao banco de dados.');
});

// Rota básica de teste
app.get('/', (req, res) => {
    res.send('Servidor funcionando!');
});

app.listen(port, () => {
    console.log(`Servidor rodando em http://localhost:${port}`)
});