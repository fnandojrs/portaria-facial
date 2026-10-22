<?php
// Ações executadas pelo dispatcher autenticado em actions.php.
        if($action==='setup') {
            if(is_file($adminHashFile)||is_file($terminalHashFile))throw new RuntimeException('Configuração já realizada.');
            $a=(string)($_POST['senha_admin']??'');
            $t=(string)($_POST['senha_terminal']??'');
            if(strlen($a)<10||strlen($t)<8||hash_equals($a,$t))throw new RuntimeException('Use senhas diferentes: administrador ≥10 e terminal ≥8 caracteres.');
            file_put_contents($adminHashFile,password_hash($a,PASSWORD_DEFAULT),LOCK_EX);
            file_put_contents($terminalHashFile,password_hash($t,PASSWORD_DEFAULT),LOCK_EX);
            chmod($adminHashFile,0640);
            chmod($terminalHashFile,0640);
            $_SESSION['admin']=true;
            jsonout(['ok'=>true]);
        }
        if($action==='login') {
            $perfil=(string)($_POST['perfil']??'terminal');
            $senha=(string)($_POST['senha']??'');
            $file=$perfil==='admin'?$adminHashFile:$terminalHashFile;
            if(!is_file($file)||!password_verify($senha,trim(file_get_contents($file))))throw new RuntimeException('Senha inválida.');
            session_regenerate_id(true);
            $_SESSION[$perfil==='admin'?'admin':'terminal']=true;
            jsonout(['ok'=>true]);
        }
        if($action==='logout') {
            $_SESSION=[];
            session_destroy();
            jsonout(['ok'=>true]);
        }
