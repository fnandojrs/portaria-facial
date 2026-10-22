<?php
// Rotas POST. A sessão e config.php são carregados pelo index.php.
try {
    checkcsrf();
    $action=(string)($_POST['action']??'');
    require __DIR__.'/autenticacao.php';
    require __DIR__.'/pessoas.php';
    require __DIR__.'/presencas.php';
    throw new RuntimeException('Ação inválida.');
} catch (Throwable $e) {
    jsonout(['ok'=>false,'erro'=>$e->getMessage()],400);
}
