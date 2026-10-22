<?php
declare(strict_types=1);
const DATA_DIR='/var/lib/portaria';
const DB_PATH=DATA_DIR.'/portaria.sqlite';
const PHOTO_DIR=DATA_DIR.'/fotos';
const PYTHON_BIN='/opt/portaria/venv/bin/python';
const RECOGNIZER='/opt/portaria/reconhecer.py';
const PY_TIMEOUT=35;
date_default_timezone_set('America/Sao_Paulo');

function db(): PDO {
    static $db=null;
    if ($db === null) {
        $db=new PDO('sqlite:'.DB_PATH);
        $db->setAttribute(PDO::ATTR_ERRMODE,PDO::ERRMODE_EXCEPTION);
        $db->exec('PRAGMA busy_timeout=8000; PRAGMA foreign_keys=ON');
    }
    return $db;
}

function initdb(): void {
    db()->exec("CREATE TABLE IF NOT EXISTS pessoas (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        nome TEXT NOT NULL,
        cpf TEXT NOT NULL UNIQUE CHECK(length(cpf)=11),
        telefone TEXT NOT NULL CHECK(length(telefone) IN (10,11)),
        empresa TEXT NOT NULL,
        matricula TEXT,
        ativo INTEGER NOT NULL DEFAULT 1,
        foto TEXT NOT NULL,
        criado_em TEXT NOT NULL
    )");
    db()->exec("CREATE TABLE IF NOT EXISTS presencas (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        pessoa_id INTEGER NOT NULL REFERENCES pessoas(id),
        tipo TEXT NOT NULL CHECK(tipo IN ('ENTRADA','SAIDA')),
        foto_captura TEXT NOT NULL,
        distancia REAL,
        criado_em TEXT NOT NULL
    )");
    db()->exec("CREATE TABLE IF NOT EXISTS tentativas (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        status TEXT NOT NULL,
        observacao TEXT,
        criado_em TEXT NOT NULL
    )");
    db()->exec('CREATE INDEX IF NOT EXISTS idx_presencas_pessoa_data ON presencas(pessoa_id, criado_em)');
}

function apenasDigitos(string $value): string {
    return preg_replace('/\D+/', '', $value) ?? '';
}

function cpfValido(string $cpf): bool {
    if (strlen($cpf)!==11 || preg_match('/^(\d)\1{10}$/', $cpf))return false;
    for($n=9;$n<=10;$n++) {
        $soma=0;
        for($i=0;$i<$n;$i++)$soma+=(int)$cpf[$i]*($n+1-$i);
        $digito=($soma*10)%11;
        if($digito===10)$digito=0;
        if($digito!==(int)$cpf[$n])return false;
    }
    return true;
}

function validarCadastro(array $data): array {
    $cpf=apenasDigitos((string)($data['cpf']??''));
    $telefone=apenasDigitos((string)($data['telefone']??''));
    $empresa=trim((string)($data['empresa']??''));
    $matricula=trim((string)($data['matricula']??''));
    if(!cpfValido($cpf))throw new RuntimeException('CPF inválido. Confira os números.');
    if(!in_array(strlen($telefone),[10,11],true))throw new RuntimeException('Telefone deve ter 10 ou 11 dígitos com DDD.');
    if($empresa===''||mb_strlen($empresa)>120)throw new RuntimeException('Informe uma empresa válida (até 120 caracteres).');
    if(mb_strlen($matricula)>40)throw new RuntimeException('Matrícula muito longa (máximo 40 caracteres).');
    return ['cpf'=>$cpf,'telefone'=>$telefone,'empresa'=>$empresa,'matricula'=>$matricula];
}

function now(): string {return date('Y-m-d H:i:s');}
function h(?string $s): string{return htmlspecialchars($s??'',ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8');}
function csrf(): string {
    if (empty($_SESSION['csrf'])) $_SESSION['csrf']=bin2hex(random_bytes(24));
    return $_SESSION['csrf'];
}
function checkcsrf(): void {if(!hash_equals(csrf(),(string)($_POST['csrf']??''))) throw new RuntimeException('Sessão inválida, atualize a página.');}
function imageFromData(string $str): string {
    if(!preg_match('~^data:image/jpeg;base64,([a-zA-Z0-9+/=]+)$~D',$str,$m)) throw new RuntimeException('Imagem JPEG inválida.');
    if(strlen($m[1])>5_000_000) throw new RuntimeException('Imagem muito grande.');
    $bytes=base64_decode($m[1],true);
    if($bytes===false || strlen($bytes)<1000 || strlen($bytes)>3_800_000) throw new RuntimeException('Tamanho da imagem inválido.');
    $type=(new finfo(FILEINFO_MIME_TYPE))->buffer($bytes);
    if($type!=='image/jpeg') throw new RuntimeException('O arquivo não é uma imagem JPEG.');
    $size=@getimagesizefromstring($bytes);
    if(!$size || $size[0]<150 || $size[1]<150 || $size[0]>4096 || $size[1]>4096) throw new RuntimeException('Dimensões de imagem inválidas.');
    return $bytes;
}
function saveImage(string $bytes):string {
    $name=bin2hex(random_bytes(16)).'.jpg';
    if(file_put_contents(PHOTO_DIR.'/'.$name,$bytes,LOCK_EX)===false) throw new RuntimeException('Erro ao gravar foto.');
    chmod(PHOTO_DIR.'/'.$name,0640);
    return $name;
}
function jsonout(array $value,int $status=200): never {http_response_code($status);header('Content-Type: application/json; charset=utf-8');header('Cache-Control: no-store');echo json_encode($value,JSON_UNESCAPED_UNICODE|JSON_INVALID_UTF8_SUBSTITUTE);exit;}
function runPython(array $arguments): array {
    $parts=array_merge([PYTHON_BIN,RECOGNIZER],$arguments);
    $cmd=implode(' ',array_map('escapeshellarg',$parts));
    $descriptors=[0=>['pipe','r'],1=>['pipe','w'],2=>['pipe','w']];
    $proc=proc_open($cmd,$descriptors,$pipes,null,['OMP_NUM_THREADS'=>'1','OPENBLAS_NUM_THREADS'=>'1']);
    if(!is_resource($proc))throw new RuntimeException('Serviço facial indisponível.');
    fclose($pipes[0]);
    stream_set_timeout($pipes[1],PY_TIMEOUT);stream_set_timeout($pipes[2],PY_TIMEOUT);
    $out=stream_get_contents($pipes[1]);$err=stream_get_contents($pipes[2]);
    fclose($pipes[1]);fclose($pipes[2]);$code=proc_close($proc);
    $result=json_decode($out,true);
    if(!is_array($result))throw new RuntimeException('Falha na análise facial: '.substr($err,0,120));
    return $result;
}
