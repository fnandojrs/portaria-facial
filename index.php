<?php

require __DIR__.'/config.php';
ini_set('display_errors','0');
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: no-referrer');
header('Cache-Control: no-store');
$secure=!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS']!=='off';
session_set_cookie_params(['httponly'=>true,'samesite'=>'Strict','secure'=>$secure]);
session_start();
try {
    initdb();
}
catch(Throwable $e) {
    http_response_code(500);
    exit('Erro de banco: confira permissões em /var/lib/portaria.');
}
$pagina=(string)($_GET['pagina']??'terminal');
if(!in_array($pagina,['terminal','cadastro','editar','movimentacoes'],true))$pagina='terminal';
if(isset($_GET['foto'])) {
    // Restricted to authenticated session; access control for demo only
    $name=(string)$_GET['foto'];
    if(!preg_match('/^[a-f0-9]{32}\.jpg$/D',$name)) {
        http_response_code(404);
        exit;
    }
    if(!isset($_SESSION['admin']) && !isset($_SESSION['terminal'])) {
        http_response_code(403);
        exit;
    }
    $p=PHOTO_DIR.'/'.$name;
    if(!is_file($p)) {
        http_response_code(404);
        exit;
    }
    header('Content-Type: image/jpeg');
    readfile($p);
    exit;
}
// Educational prototype: separate terminal password from admin password.
function loggedAdmin():bool {
    return !empty($_SESSION['admin']);
}
function loggedTerminal():bool {
    return true;
}
$adminHashFile=DATA_DIR.'/admin.hash';
$terminalHashFile=DATA_DIR.'/terminal.hash';
if ($_SERVER['REQUEST_METHOD']==='POST') {
    require __DIR__.'/api/actions.php';
}
$setup=!is_file($adminHashFile)||!is_file($terminalHashFile);
$authorized=$pagina==='terminal'?loggedTerminal():loggedAdmin();
$cadastros=$pagina==='editar'&&loggedAdmin()?db()->query('SELECT id,nome,cpf,telefone,empresa,matricula,ativo,foto FROM pessoas ORDER BY nome COLLATE NOCASE')->fetchAll(PDO::FETCH_ASSOC):[];
$pessoas=$pagina==='movimentacoes'&&loggedAdmin()?db()->query('SELECT pr.criado_em,pr.tipo,p.nome,p.empresa,pr.foto_captura FROM presencas pr JOIN pessoas p ON p.id=pr.pessoa_id ORDER BY pr.id DESC LIMIT 100')->fetchAll(PDO::FETCH_ASSOC):[];
?>
<!doctype html>
<html lang="pt-BR">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Portaria Facial</title>
<link rel="stylesheet" href="/portaria/assets/css/style.css">
<link rel="manifest" href="/portaria/manifest.webmanifest">
<meta name="theme-color" content="#101820">
<link rel="apple-touch-icon" href="/portaria/icon-192.png">
</head>
<body>
<main class="wrap <?= $pagina==='terminal'?'kiosk':'' ?>">
<?php if(!$setup&&$authorized&&$pagina!=='terminal'):?>
<nav class="nav">
<a href="?pagina=cadastro">Cadastro</a>
<a href="?pagina=editar">Editar cadastros</a>
<a href="?pagina=movimentacoes">Movimentações</a>
<a href="?pagina=terminal">Terminal</a>
</nav>
<?php endif;?>
<?php
if ($setup) {
    require __DIR__.'/paginas/configuracao_inicial.php';
} elseif (!$authorized) {
    require __DIR__.'/paginas/login.php';
} else {
    require __DIR__.'/paginas/'.$pagina.'.php';
}
?>

<p id="error" class="msg" role="alert">
</p>
<?php require __DIR__.'/paginas/confirmacao.php'; ?>
</main>
<script>
const token=<?=json_encode(csrf())?>;
const page=<?=json_encode($pagina)?>;
const persons=<?=json_encode($cadastros,JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT|JSON_INVALID_UTF8_SUBSTITUTE)?>;
</script>
<script src="/portaria/assets/js/comum.js" defer></script>
<script src="/portaria/assets/js/camera.js" defer></script>
<script src="/portaria/assets/js/validacoes.js" defer></script>
<script src="/portaria/assets/js/editar.js" defer></script>
<script src="/portaria/assets/js/cadastro.js" defer></script>
<script>
if ('serviceWorker' in navigator) {
    window.addEventListener('load', () => navigator.serviceWorker.register('/portaria/sw.js').catch(console.warn));
}
</script>
</body>
</html>
