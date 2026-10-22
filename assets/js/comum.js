const $ = id => document.getElementById(id);

// Envia requisições ao PHP
async function post(data) {
    const form = new FormData();

    for (const [chave, valor] of Object.entries({
        ...data,
        csrf: token
    })) {
        form.append(chave, valor);
    }

    const resposta = await fetch(location.pathname, {
        method: 'POST',
        body: form,
        credentials: 'same-origin',
        cache: 'no-store'
    });

    const resultado = await resposta.json();

    if (!resultado.ok) {
        const erro = resultado.erro || 'Falha inesperada';

        // Se a sessão estiver desatualizada durante o login,
        // recarrega a página para obter um novo token CSRF.
        if (
            erro.includes('Sessão inválida') &&
            ['login', 'setup'].includes(data.action)
        ) {
            window.location.reload();
            throw new Error('Sessão expirada. Atualizando a página...');
        }

        throw new Error(erro);
    }

    return resultado;
}

// Login e configuração inicial
for (const id of ['setup', 'login']) {
    const formulario = $(id);

    if (!formulario) continue;

    formulario.addEventListener('submit', async event => {
        event.preventDefault();

        try {
            const dados = Object.fromEntries(
                new FormData(event.target)
            );

            await post(dados);

            location.reload();
        } catch (erro) {
            $('error').textContent = erro.message;
        }
    });
}

// Sair da administração
if ($('logout')) {
    $('logout').onclick = async () => {
        try {
            await post({ action: 'logout' });
            location.reload();
        } catch (erro) {
            $('error').textContent = erro.message;
        }
    };
}