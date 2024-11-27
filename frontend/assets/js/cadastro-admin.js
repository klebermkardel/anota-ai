document.getElementById('cadastroForm').addEventListener('submit', async function(event) {
    event.preventDefault();  // Evita o reload da página
    
    const nome = document.getElementById('nome').value;
    const username = document.getElementById('username').value;
    const email = document.getElementById('email').value;
    const password = document.getElementById('password').value;
    const msg = document.getElementById('msg');

    try {
        const response = await fetch('http://localhost:3000/cadastro-admin', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ nome, username, email, password })
        });

        const data = await response.json();
        if (response.ok) {
            msg.textContent = 'Administrador cadastrado com sucesso!';
            msg.style.color = 'green';
            document.getElementById('cadastroForm').reset();
        } else {
            msg.textContent = data.error || 'Erro ao cadastrar.';
            msg.style.color = 'red';
        }
    } catch (error) {
        msg.textContent = 'Erro de conexão com o servidor.';
        msg.style.color = 'red';
    }
});
