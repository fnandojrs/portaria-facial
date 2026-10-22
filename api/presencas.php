<?php
// Ações executadas pelo dispatcher autenticado em actions.php.
        if($action==='identificar') {
            if(!loggedTerminal())jsonout(['ok'=>false,'erro'=>'Acesso restrito'],403);
            $bytes=imageFromData((string)($_POST['foto']??''));
            $file=saveImage($bytes);
            $st=db()->query('SELECT id,foto FROM pessoas WHERE ativo=1 ORDER BY id');
            $arr=[];
            foreach($st as $p)$arr[]=['id'=>(int)$p['id'],'foto'=>PHOTO_DIR.'/'.$p['foto']];
            $result=runPython(['identificar',PHOTO_DIR.'/'.$file,json_encode($arr)]);
            $status=(string)($result['status']??'erro');
            db()->prepare('INSERT INTO tentativas(status,observacao,criado_em) VALUES (?,?,?)')->execute([$status,mb_substr((string)($result['motivo']??''),0,255),now()]);
            if($status==='correspondencia') {
                $person=db()->prepare('SELECT id,nome,empresa,foto FROM pessoas WHERE id=? AND ativo=1');
                $person->execute([(int)$result['id']]);
                $row=$person->fetch(PDO::FETCH_ASSOC);
                if($row) {
                    $token=bin2hex(random_bytes(20));
                    $_SESSION['pending']=['token'=>$token,'id'=>(int)$row['id'],'capture'=>$file,'distancia'=>(float)$result['distancia'],'at'=>time()];

                    // Consulta a última movimentação da pessoa no dia.
                    $consulta=db()->prepare('SELECT tipo FROM presencas WHERE pessoa_id=? AND substr(criado_em,1,10)=? ORDER BY id DESC LIMIT 1');
                    $consulta->execute([(int)$row['id'],substr(now(),0,10)]);
                    $proximoTipo=$consulta->fetchColumn()==='ENTRADA'?'SAIDA':'ENTRADA';

                    jsonout(['ok'=>true,'status'=>$status,'pessoa'=>$row,'token'=>$token,'tipo'=>$proximoTipo,'distancia'=>$result['distancia']]);
                }
            }
            @unlink(PHOTO_DIR.'/'.$file);
            jsonout(['ok'=>true,'status'=>$status,'motivo'=>$result['motivo']??'Não identificado']);
        }
        if($action==='cancelar_presenca') {
            if(!loggedTerminal())jsonout(['ok'=>false,'erro'=>'Acesso restrito'],403);
            $p=$_SESSION['pending']??null;
            if($p && isset($p['token']) && hash_equals($p['token'],(string)($_POST['token']??''))) {
                if(!empty($p['capture'])) @unlink(PHOTO_DIR.'/'.$p['capture']);
                unset($_SESSION['pending']);
            }
            jsonout(['ok'=>true]);
        }
        if($action==='presenca') {
            if(!loggedTerminal())jsonout(['ok'=>false,'erro'=>'Acesso restrito'],403);
            $p=$_SESSION['pending']??null;
            if(!$p||!hash_equals($p['token'],(string)($_POST['token']??''))||time()-$p['at']>120)throw new RuntimeException('Identificação expirada. Faça nova foto.');
            $database=db();
            $agora=now();
            $database->exec('BEGIN IMMEDIATE TRANSACTION');
            try {
                $st=$database->prepare('SELECT tipo FROM presencas WHERE pessoa_id=? AND substr(criado_em,1,10)=? ORDER BY id DESC LIMIT 1');
                $st->execute([$p['id'],substr($agora,0,10)]);
                $tipo=$st->fetchColumn()==='ENTRADA'?'SAIDA':'ENTRADA';
                // Evita confirmar uma movimentação diferente da exibida no pop-up.
                if($tipo!==(string)($_POST['tipo']??'')) {
                    throw new RuntimeException('A movimentação mudou. Identifique a pessoa novamente.');
                }
                $st=$database->prepare('INSERT INTO presencas(pessoa_id,tipo,foto_captura,distancia,criado_em) VALUES(?,?,?,?,?)');
                $st->execute([$p['id'],$tipo,$p['capture'],$p['distancia'],$agora]);
                $database->commit();
            } catch(Throwable $e) {
                if($database->inTransaction())$database->rollBack();
                throw $e;
            }
            unset($_SESSION['pending']);
            jsonout(['ok'=>true,'tipo'=>$tipo]);
        }
