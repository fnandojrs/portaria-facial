<?php
declare(strict_types=1);

require __DIR__ . '/config.php';

ini_set('display_errors', '0');
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: no-referrer');
header('Cache-Control: no-store');

$secure = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
session_set_cookie_params([
    'httponly' => true,
    'samesite' => 'Strict',
    'secure' => $secure,
]);
session_start();

// A página não é listada no menu; acesso restrito ao administrador autenticado.
if (empty($_SESSION['admin'])) {
    http_response_code(403);
    exit('Acesso restrito. Entre como administrador na Portaria antes de abrir esta página.');
}

if (empty($_SESSION['limpar_mov_csrf'])) {
    $_SESSION['limpar_mov_csrf'] = bin2hex(random_bytes(32));
}

$mensagem = '';
$erro = '';
$total = 0;

try {
    $banco = db();
    $total = (int) $banco->query('SELECT COUNT(*) FROM presencas')->fetchColumn();
} catch (Throwable $e) {
    http_response_code(500);
    exit('Erro ao consultar as movimentações. Confira a configuração do SQLite.');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $tokenEnviado = (string) ($_POST['csrf'] ?? '');
        if (!hash_equals((string) $_SESSION['limpar_mov_csrf'], $tokenEnviado)) {
            throw new RuntimeException('Sessão inválida. Atualize a página e tente novamente.');
        }

        if ((string) ($_POST['confirmacao'] ?? '') !== 'ZERAR MOVIMENTACOES') {
            throw new RuntimeException('Digite exatamente ZERAR MOVIMENTACOES para confirmar.');
        }

        $banco->exec('BEGIN IMMEDIATE TRANSACTION');
        try {
            $apagados = (int) $banco->query('SELECT COUNT(*) FROM presencas')->fetchColumn();
            $banco->exec('DELETE FROM presencas');
            $banco->commit();
        } catch (Throwable $e) {
            if ($banco->inTransaction()) {
                $banco->rollBack();
            }
            throw $e;
        }

        $_SESSION['limpar_mov_csrf'] = bin2hex(random_bytes(32));
        $total = 0;
        $mensagem = "Movimentações apagadas com sucesso: {$apagados} registro(s). Cadastros preservados.";
    } catch (RuntimeException $e) {
        $erro = $e->getMessage();
    } catch (Throwable $e) {
        $erro = 'Não foi possível limpar as movimentações. Confira os logs do servidor.';
    }
}

function h_local(string $valor): string
{
    return htmlspecialchars($valor, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}
?>
<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow, noarchive">
    <title>Comandos administrativos — Portaria Facial</title>
    <style>
        :root { color-scheme: dark; font-family: system-ui, Arial, sans-serif; }
        * { box-sizing: border-box; }
        body { background: #0d1321; color: #f1f5fc; margin: 0; min-height: 100vh; }
        main { max-width: 720px; margin: 5vh auto; padding: 20px; }
        .nav { display: flex; gap: 10px; flex-wrap: wrap; margin: 0 0 22px; }
        .nav a {
            display: inline-block; text-decoration: none; font-size: .95rem;
            background: #2a3952; color: #d5e6ff; padding: 11px 13px;
            border-radius: 9px; border: 1px solid #34445d;
        }
        .nav a:hover, .nav a:focus-visible { background: #365278; color: #fff; }
        .nav a.current { background: #315d94; border-color: #4773a6; color: #fff; }
        .card { background: #192337; border: 1px solid #34445d; border-radius: 16px; padding: 26px; }
        h1 { margin: 0 0 8px; font-size: 1.7rem; }
        p { color: #bac9dc; line-height: 1.5; }
        .total { font-size: 2.2rem; font-weight: bold; margin: 10px 0 20px; }
        label { display: block; margin: 16px 0 8px; }
        input { width: 100%; padding: 14px; border: 1px solid #566a86; background: #0e182a; color: #fff; border-radius: 9px; font-size: 1rem; }
        button { width: 100%; background: #bd333d; border: 0; color: white; border-radius: 10px; padding: 15px; margin-top: 18px; font-size: 1rem; font-weight: 700; cursor: pointer; }
        button:disabled { opacity: .45; cursor: not-allowed; }
        .aviso { border-left: 4px solid #f0b45e; padding: 10px 14px; background: #302a25; border-radius: 6px; }
        .ok { color: #8af0ac; }
        .erro { color: #ff9a9a; }
        a { color: #9bcaff; }
        @media (max-width: 480px) {
            main { margin: 12px auto; padding: 12px; }
            .card { padding: 18px; }
            .nav { gap: 7px; }
            .nav a { flex: 1 1 auto; text-align: center; }
        }
    </style>
</head>
<body>
<main>
    <nav class="nav" aria-label="Navegação da Portaria">
        <a href="index.php?pagina=cadastro">Cadastro</a>
        <a href="index.php?pagina=editar">Editar cadastros</a>
        <a href="index.php?pagina=movimentacoes">Movimentações</a>
        <a href="index.php?pagina=terminal">Terminal</a>
        <a href="comandos_ocultos.php" class="current" aria-current="page">Comandos</a>
    </nav>

    <section class="card">
        <h1>Comandos administrativos</h1>
        <p>Limpeza do histórico de entradas e saídas.</p>
        <p>Movimentações existentes:</p>
        <div class="total"><?= number_format($total, 0, ',', '.') ?></div>

        <?php if ($mensagem !== ''): ?>
            <p class="ok" role="status"><?= h_local($mensagem) ?></p>
        <?php endif; ?>
        <?php if ($erro !== ''): ?>
            <p class="erro" role="alert"><?= h_local($erro) ?></p>
        <?php endif; ?>

        <p class="aviso"><strong>Atenção:</strong> essa operação é irreversível e exclui todas as linhas de <code>presencas</code>. Os cadastros das pessoas e suas fotos de referência são mantidos. Fotos de capturas antigas podem continuar no disco.</p>

        <form method="post" autocomplete="off" id="clearForm">
            <input type="hidden" name="csrf" value="<?= h_local((string) $_SESSION['limpar_mov_csrf']) ?>">
            <label for="confirmacao">Digite <strong>ZERAR MOVIMENTACOES</strong> para confirmar:</label>
            <input id="confirmacao" name="confirmacao" required spellcheck="false" autocomplete="off">
            <button type="submit" id="deleteBtn" disabled>Apagar todas as movimentações</button>
        </form>
        <p><a href="index.php?pagina=movimentacoes">Voltar às movimentações</a></p>
    </section>
</main>
<script>
    const campo = document.getElementById('confirmacao');
    const botao = document.getElementById('deleteBtn');
    campo.addEventListener('input', () => {
        botao.disabled = campo.value !== 'ZERAR MOVIMENTACOES';
    });
    document.getElementById('clearForm').addEventListener('submit', (event) => {
        if (!confirm('Confirma a exclusão definitiva de TODAS as movimentações?')) {
            event.preventDefault();
        }
    });
</script>
</body>
</html>
