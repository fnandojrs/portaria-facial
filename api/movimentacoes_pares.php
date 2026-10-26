<?php
// Exibição por pares, sem alterar a estrutura ou os registros originais do banco.
// Um par é uma ENTRADA seguida de SAIDA da mesma pessoa e na mesma data,
// em ordem de gravação (ID). Uma nova ENTRADA antes de uma SAIDA fica avulsa.
$where=[];
$params=[];
if($filtroInicio!==''){$where[]='substr(pr.criado_em,1,10)>=?';$params[]=$filtroInicio;}
if($filtroFim!==''){$where[]='substr(pr.criado_em,1,10)<=?';$params[]=$filtroFim;}
if($filtroBusca!==''){$where[]='(p.nome LIKE ? OR p.cpf LIKE ? OR p.telefone LIKE ?)';for($i=0;$i<3;$i++)$params[]='%'.$filtroBusca.'%';}
if($filtroEmpresa!==''){$where[]='p.empresa=?';$params[]=$filtroEmpresa;}
if($filtroAcesso!==''){$where[]='p.tipo_acesso=?';$params[]=$filtroAcesso;}
$whereSql=$where?' WHERE '.implode(' AND ',$where):'';
$sql='SELECT pr.id,pr.pessoa_id,pr.criado_em,pr.tipo,p.nome,p.empresa,p.tipo_acesso FROM presencas pr JOIN pessoas p ON p.id=pr.pessoa_id'.$whereSql.' ORDER BY pr.pessoa_id ASC, substr(pr.criado_em,1,10) ASC, pr.id ASC';
$stmt=db()->prepare($sql);
$stmt->execute($params);
$pares=[];
$pendente=null;
$grupoAnterior=null;
$adicionar=function($entrada,$saida)use(&$pares){
    $ref=$entrada??$saida;
    $pares[]=[
      'entrada_id'=>$entrada['id']??null,
      'saida_id'=>$saida['id']??null,
      'data'=>substr($ref['criado_em'],0,10),
      'entrada'=>isset($entrada['criado_em'])?substr($entrada['criado_em'],11,8):null,
      'saida'=>isset($saida['criado_em'])?substr($saida['criado_em'],11,8):null,
      'nome'=>$ref['nome'],'empresa'=>$ref['empresa'],'tipo_acesso'=>$ref['tipo_acesso'],
      'ordem_id'=>max((int)($entrada['id']??0),(int)($saida['id']??0)),
    ];
};
while($r=$stmt->fetch(PDO::FETCH_ASSOC)){
    $grupo=$r['pessoa_id'].'|'.substr($r['criado_em'],0,10);
    if($grupoAnterior!==$grupo){
        if($pendente!==null)$adicionar($pendente,null);
        $pendente=null;
        $grupoAnterior=$grupo;
    }
    if($r['tipo']==='ENTRADA'){
        if($pendente!==null)$adicionar($pendente,null);
        $pendente=$r;
    } elseif($r['tipo']==='SAIDA'){
        $adicionar($pendente,$r);
        $pendente=null;
    }
}
if($pendente!==null)$adicionar($pendente,null);
if($filtroMov==='ENTRADA')$pares=array_values(array_filter($pares,fn($p)=>$p['entrada_id']!==null));
if($filtroMov==='SAIDA')$pares=array_values(array_filter($pares,fn($p)=>$p['saida_id']!==null));
usort($pares,function($a,$b)use($ordenacao,$direcao){
    if($ordenacao==='nome')$c=strcasecmp($a['nome'],$b['nome']);
    elseif($ordenacao==='tipo')$c=strcmp(($a['entrada_id']&&$a['saida_id'])?'COMPLETO':($a['entrada_id']?'ENTRADA':'SAIDA'),($b['entrada_id']&&$b['saida_id'])?'COMPLETO':($b['entrada_id']?'ENTRADA':'SAIDA'));
    else $c=strcmp($a['data'],$b['data']);
    if($c===0)$c=$a['ordem_id']<=>$b['ordem_id'];
    return $direcao==='asc'?$c:-$c;
});
require __DIR__.'/exportacoes.php';
if((string)($_GET['exportar']??'')==='csv' && !$setup){
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="movimentacoes_portaria_'.date('Ymd_His').'.csv"');
    $out=fopen('php://output','w');fwrite($out,"\xEF\xBB\xBF");
    fputcsv($out,['Data','Pessoa','Empresa','Tipo de acesso','Entrada','Saida'],';');
    foreach($pares as $p) fputcsv($out,[$p['data'],$p['nome'],$p['empresa'],$p['tipo_acesso'],$p['entrada']??'',$p['saida']??''],';');
    fclose($out);exit;
}
$pessoas=array_slice($pares,0,500);
unset($pares);
$empresasMov=db()->query("SELECT DISTINCT empresa FROM pessoas WHERE empresa<>'' ORDER BY empresa COLLATE NOCASE")->fetchAll(PDO::FETCH_COLUMN);
