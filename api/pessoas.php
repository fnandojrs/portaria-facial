<?php
// Ações executadas pelo dispatcher autenticado em actions.php.
        if($action==='consultar_cpf') {
            if(!loggedAdmin())jsonout(['ok'=>false,'erro'=>'Acesso restrito'],403);
            $cpf=apenasDigitos((string)($_POST['cpf']??''));
            if(!cpfValido($cpf))throw new RuntimeException('CPF inválido.');
            $st=db()->prepare('SELECT id,nome,ativo FROM pessoas WHERE cpf=? LIMIT 1');
            $st->execute([$cpf]);
            $row=$st->fetch(PDO::FETCH_ASSOC);
            jsonout(['ok'=>true,'existente'=>(bool)$row,'ativo'=>$row?(bool)$row['ativo']:false,'id'=>$row?(int)$row['id']:null,'nome'=>$row?$row['nome']:null]);
        }
        if($action==='reativar_pessoa') {
            if(!loggedAdmin())jsonout(['ok'=>false,'erro'=>'Acesso restrito'],403);
            $cpf=apenasDigitos((string)($_POST['cpf']??''));
            if(!cpfValido($cpf))throw new RuntimeException('CPF inválido.');
            // Apenas reativa o registro existente. Não cria nova pessoa.
            $st=db()->prepare('UPDATE pessoas SET ativo=1 WHERE cpf=? AND ativo=0');
            $st->execute([$cpf]);
            if($st->rowCount()!==1)throw new RuntimeException('Cadastro não encontrado ou já ativo.');
            jsonout(['ok'=>true]);
        }
        if($action==='cadastrar') {
            if(!loggedAdmin())jsonout(['ok'=>false,'erro'=>'Acesso restrito'],403);
            $nome=trim((string)($_POST['nome']??''));
            if(mb_strlen($nome)<2||mb_strlen($nome)>120)throw new RuntimeException('Nome inválido.');
            $dados=validarCadastro($_POST);
            $verifica=db()->prepare('SELECT ativo FROM pessoas WHERE cpf=? LIMIT 1');
            $verifica->execute([$dados['cpf']]);
            $existente=$verifica->fetchColumn();
            if($existente!==false) {
                throw new RuntimeException((int)$existente===1 ? 'CLIENTE JA EXISTE' : 'Cliente já existe mas está desativado. Deseja liberar?');
            }
            $bytes=imageFromData((string)($_POST['foto']??''));
            $file=saveImage($bytes);
            try {
                $face=runPython(['validar',PHOTO_DIR.'/'.$file]);
                if(!($face['ok']??false))throw new RuntimeException((string)($face['erro']??'Não foi possível detectar um rosto único.'));
                $dados=validarCadastro($_POST);
                $st=db()->prepare('INSERT INTO pessoas(nome,cpf,telefone,empresa,matricula,tipo_acesso,foto,criado_em) VALUES (?,?,?,?,?,?,?,?)');
                try {
                    $st->execute([$nome,$dados['cpf'],$dados['telefone'],$dados['empresa'],$dados['matricula'],$dados['tipo_acesso'],$file,now()]);
                } catch (PDOException $e) {
                    if (str_contains($e->getMessage(),'UNIQUE constraint failed: pessoas.cpf')) throw new RuntimeException('CPF já cadastrado.');
                    throw $e;
                }
                jsonout(['ok'=>true,'id'=>(int)db()->lastInsertId()]);
            }
            catch(Throwable $e) {
                @unlink(PHOTO_DIR.'/'.$file);
                throw $e;
            }
        }
        if($action==='editar_pessoa') {
            if(!loggedAdmin())jsonout(['ok'=>false,'erro'=>'Acesso restrito'],403);
            $id=filter_var($_POST['id']??null,FILTER_VALIDATE_INT,['options'=>['min_range'=>1]]);
            if(!$id)throw new RuntimeException('Cadastro invalido.');
            $st=db()->prepare('SELECT id,foto FROM pessoas WHERE id=?');
            $st->execute([$id]);
            $current=$st->fetch(PDO::FETCH_ASSOC);
            if(!$current)throw new RuntimeException('Pessoa nao encontrada.');
            $nome=trim((string)($_POST['nome']??''));
            if(mb_strlen($nome)<2||mb_strlen($nome)>120)throw new RuntimeException('Nome invalido.');
            $dados=validarCadastro($_POST);
            $ativo=($_POST['ativo']??'0')==='1'?1:0;
            $newfile=null;
            try {
                if(!empty($_POST['foto'])) {
                    $newfile=saveImage(imageFromData((string)$_POST['foto']));
                    $result=runPython(['validar',PHOTO_DIR.'/'.$newfile]);
                    if(!($result['ok']??false))throw new RuntimeException((string)($result['erro']??'Foto sem rosto unico.'));
                }
                $st=db()->prepare('UPDATE pessoas SET nome=?,cpf=?,telefone=?,empresa=?,matricula=?,tipo_acesso=?,ativo=?,foto=? WHERE id=?');
                try {
                    $st->execute([$nome,$dados['cpf'],$dados['telefone'],$dados['empresa'],$dados['matricula'],$dados['tipo_acesso'],$ativo,$newfile??$current['foto'],$id]);
                } catch (PDOException $e) {
                    if (str_contains($e->getMessage(),'UNIQUE constraint failed: pessoas.cpf')) throw new RuntimeException('CPF já cadastrado.');
                    throw $e;
                }
                jsonout(['ok'=>true]);
            }
            catch(Throwable $e) {
                if($newfile)@unlink(PHOTO_DIR.'/'.$newfile);
                throw $e;
            }
        }
        if($action==='excluir_pessoa') {
            if(!loggedAdmin())jsonout(['ok'=>false,'erro'=>'Acesso restrito'],403);
            $id=filter_var($_POST['id']??null,FILTER_VALIDATE_INT,['options'=>['min_range'=>1]]);
            if(!$id)throw new RuntimeException('Cadastro invalido.');
            $db=db();
            $db->exec('BEGIN IMMEDIATE TRANSACTION');
            try {
                $st=$db->prepare('SELECT id,foto FROM pessoas WHERE id=?');
                $st->execute([$id]);
                $pessoa=$st->fetch(PDO::FETCH_ASSOC);
                if(!$pessoa)throw new RuntimeException('Pessoa nao encontrada.');
                $st=$db->prepare('SELECT COUNT(*) FROM presencas WHERE pessoa_id=?');
                $st->execute([$id]);
                $total=(int)$st->fetchColumn();
                if($total>0)throw new RuntimeException('Exclusao bloqueada: esta pessoa possui '.$total.' movimentacao(oes). Use Inativar.');
                $st=$db->prepare('DELETE FROM pessoas WHERE id=? AND NOT EXISTS (SELECT 1 FROM presencas WHERE pessoa_id=?)');
                $st->execute([$id,$id]);
                if($st->rowCount()!==1)throw new RuntimeException('Exclusao nao autorizada. Verifique movimentacoes.');
                $db->commit();
                if(isset($_SESSION['pending']['id']) && (int)$_SESSION['pending']['id']===$id)unset($_SESSION['pending']);
                // Somente remove a foto depois de confirmar a exclusao do cadastro.
                // Se estiver compartilhada com outro cadastro, preserva o arquivo.
                $check=$db->prepare('SELECT COUNT(*) FROM pessoas WHERE foto=?');
                $check->execute([$pessoa['foto']]);
                if((int)$check->fetchColumn()===0 && preg_match('/^[a-f0-9]{32}\.jpg$/D',(string)$pessoa['foto'])) {
                    @unlink(PHOTO_DIR.'/'.$pessoa['foto']);
                }
                jsonout(['ok'=>true,'mensagem'=>'Cadastro excluido.']);
            }
            catch(Throwable $e) {
                if($db->inTransaction())$db->rollBack();
                throw $e;
            }
        }
