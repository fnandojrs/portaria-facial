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
// Compatibilidade com o link antigo: ambos abrem a tela unificada.
if($pagina==='editar')$pagina='cadastro';
if(isset($_GET['foto'])) {
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
$cadastros=$pagina==='cadastro'&&loggedAdmin()?db()->query('SELECT id,nome,cpf,telefone,empresa,matricula,ativo,foto,criado_em,tipo_acesso FROM pessoas ORDER BY nome COLLATE NOCASE')->fetchAll(PDO::FETCH_ASSOC):[];

$filtroInicio='';$filtroFim='';$filtroBusca='';$filtroEmpresa='';$filtroAcesso='';$filtroMov='';$ordenacao='data';$direcao='desc';$pessoas=[];
if($pagina==='movimentacoes' && loggedAdmin()) {
    $filtroInicio=(string)($_GET['data_inicio']??'');
    $filtroFim=(string)($_GET['data_fim']??'');
    if(!preg_match('/^\d{4}-\d{2}-\d{2}$/',$filtroInicio))$filtroInicio='';
    if(!preg_match('/^\d{4}-\d{2}-\d{2}$/',$filtroFim))$filtroFim='';
    $filtroBusca=mb_substr(trim((string)($_GET['busca']??'')),0,120);
    $filtroEmpresa=mb_substr(trim((string)($_GET['empresa']??'')),0,120);
    $filtroAcesso=(string)($_GET['tipo_acesso']??'');
    if(!in_array($filtroAcesso,['','Funcionario','Aluno','Professor','Visitante','Terceirizado'],true))$filtroAcesso='';
    $filtroMov=(string)($_GET['movimento']??'');
    if(!in_array($filtroMov,['','ENTRADA','SAIDA'],true))$filtroMov='';
    $ordenacao=(string)($_GET['ordenar']??'data');
    if(!in_array($ordenacao,['data','nome','tipo'],true))$ordenacao='data';
    $direcao=(string)($_GET['direcao']??'desc');
    if(!in_array($direcao,['asc','desc'],true))$direcao='desc';
    // Formar pares usando TODOS os eventos da pessoa no período; o filtro ENTRADA/SAIDA
    // é aplicado depois, para não separar entradas de suas saídas.
    require __DIR__.'/api/movimentacoes_pares.php';
}

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

<a href="?pagina=movimentacoes">Movimentações</a>

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

<p id="error" class="msg" role="alert"></p>
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
<script src="/portaria/assets/js/movimentacoes.js" defer></script>
<script>
if ('serviceWorker' in navigator) {
    window.addEventListener('load', () => navigator.serviceWorker.register('/portaria/sw.js').catch(console.warn));
}
</script>
</body>
</html>
