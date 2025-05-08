const express = require('express')
const cors = require('cors')
const dotenv = require('dotenv')
const sequelize = require('./config/db')

dotenv.config()

const app = express()
app.use(cors())
app.use(express.json())

app.get('/', (req, res) => {
    res.send('API do Anota Aí funcionando!')
})

sequelize.authenticate()
    .then(() => {
        console.timeLog('Conectado ao MySQL com sucesso!')
        return sequelize.sync()
    })
    .then(() => {
        app.listen(3000, () => console.log('Servidor rodando em http://localhost:3000'))
    })
    .catch(err => {
        console.error('Erro ao conectador no banco', err)
    })