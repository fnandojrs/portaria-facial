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
        if($action==='excluir_movimento') {
            if(!loggedAdmin())jsonout(['ok'=>false,'erro'=>'Acesso restrito'],403);
            $id=(int)($_POST['id']??0);
            if($id<=0)throw new RuntimeException('Movimentação inválida.');
            $database=db();
            $st=$database->prepare('SELECT foto_captura FROM presencas WHERE id=?');
            $st->execute([$id]);
            $foto=$st->fetchColumn();
            if($foto===false)throw new RuntimeException('Movimentação não encontrada.');
            $database->exec('BEGIN IMMEDIATE TRANSACTION');
            try {
                $st=$database->prepare('DELETE FROM presencas WHERE id=?');
                $st->execute([$id]);
                $database->commit();
            } catch(Throwable $e) {
                if($database->inTransaction())$database->rollBack();
                throw $e;
            }
            if(is_string($foto) && preg_match('/^[a-f0-9]{32}\.jpg$/D',$foto)) {
                $st=$database->prepare('SELECT COUNT(*) FROM presencas WHERE foto_captura=?');
                $st->execute([$foto]);
                if((int)$st->fetchColumn()===0) {
                    @unlink(PHOTO_DIR.'/'.$foto);
                }
            }
            jsonout(['ok'=>true,'id'=>$id]);
        }

        if($action==='excluir_par') {
            if(!loggedAdmin())jsonout(['ok'=>false,'erro'=>'Acesso restrito'],403);
            $entradaId=(int)($_POST['entrada_id']??0);
            $saidaId=(int)($_POST['saida_id']??0);
            if($entradaId<=0 && $saidaId<=0)throw new RuntimeException('Selecione uma movimentação.');
            if($entradaId>0 && $entradaId===$saidaId)throw new RuntimeException('IDs inválidos.');
            $db=db();
            $db->exec('BEGIN IMMEDIATE TRANSACTION');
            try {
                $refId=$entradaId>0?$entradaId:$saidaId;
                $st=$db->prepare('SELECT id,pessoa_id,tipo,criado_em FROM presencas WHERE id=?');
                $st->execute([$refId]);
                $ref=$st->fetch(PDO::FETCH_ASSOC);
                if(!$ref)throw new RuntimeException('Registro não encontrado. Atualize a lista.');
                $dia=substr($ref['criado_em'],0,10);
                // Reconstrói TODOS os pares deste usuário e dia no banco, sem confiar nos IDs enviados.
                $st=$db->prepare('SELECT id,tipo,foto_captura FROM presencas WHERE pessoa_id=? AND substr(criado_em,1,10)=? ORDER BY id');
                $st->execute([$ref['pessoa_id'],$dia]);
                $pendente=null;$parEncontrado=null;
                $avaliar=function($e,$s)use($entradaId,$saidaId,&$parEncontrado){
                    if(($e['id']??0)===$entradaId && ($s['id']??0)===$saidaId)$parEncontrado=[$e,$s];
                };
                while($r=$st->fetch(PDO::FETCH_ASSOC)){
                    $r['id']=(int)$r['id'];
                    if($r['tipo']==='ENTRADA'){
                        if($pendente!==null)$avaliar($pendente,null);
                        $pendente=$r;
                    }elseif($r['tipo']==='SAIDA'){
                        $avaliar($pendente,$r);
                        $pendente=null;
                    }
                }
                if($pendente!==null)$avaliar($pendente,null);
                if($parEncontrado===null)throw new RuntimeException('Este par foi alterado. Atualize a lista e selecione novamente.');
                $fotos=[];$ids=[];
                foreach($parEncontrado as $item){if($item!==null){$ids[]=$item['id'];$fotos[]=$item['foto_captura'];}}
                $del=$db->prepare('DELETE FROM presencas WHERE id=?');
                foreach($ids as $id)$del->execute([$id]);
                $db->commit();
            }catch(Throwable $e){if($db->inTransaction())$db->rollBack();throw $e;}
            // Fotos de captura permanecem armazenadas, conforme solicitado.
            jsonout(['ok'=>true,'excluidos'=>count($ids)]);
        }
