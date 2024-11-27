document.getElementById('loginForm').addEventListener('submit', async function(event) {
    event.preventDefault();

    const username = document.getElementById('username').value;
    const password = document.getElementById('password').value;

    try {
        const response = await fetch('http://localhost:3000/login-admin', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ username, password })
        });

        const data = await response.json();

        if (response.ok) {
            alert('Login bem-sucedido!');
            // Redirecionar ou armazenar o token/sessão aqui
            // Exemplo: localStorage.setItem('token', data.token);
        } else {
            alert(data.error);  // Exibir mensagem de erro
        }
    } catch (error) {
        console.error('Erro:', error);
        alert('Erro ao tentar fazer login.');
    }
});
