document.addEventListener('DOMContentLoaded', () => {
    carregarDebitos(1);  // Carrega a primeira página ao iniciar
});

async function carregarDebitos(pagina) {
    try {
        const response = await fetch(`http://localhost:3000/debitos?page=${pagina}`);
        const data = await response.json();

        const tbody = document.querySelector('#tabela-debitos tbody');
        tbody.innerHTML = '';  // Limpa a tabela

        data.debitos.forEach(debito => {
            const tr = document.createElement('tr');
            tr.innerHTML = `
                <td>${debito.cliente}</td>
                <td>${debito.produto}</td>
                <td>R$ ${debito.valor}</td>
                <td>${new Date(debito.data).toLocaleDateString()}</td>
            `;
            tbody.appendChild(tr);
        });

        // Atualiza a paginação
        atualizarPaginacao(data.totalPaginas, pagina);
    } catch (error) {
        console.error('Erro ao carregar débitos:', error);
    }
}

function atualizarPaginacao(totalPaginas, paginaAtual) {
    const paginacao = document.getElementById('paginacao');
    paginacao.innerHTML = '';

    for (let i = 1; i <= totalPaginas; i++) {
        const botao = document.createElement('button');
        botao.innerText = i;
        botao.classList.add('pagina-btn');
        if (i === paginaAtual) botao.classList.add('ativo');
        botao.addEventListener('click', () => carregarDebitos(i));
        paginacao.appendChild(botao);
    }
}
