<div class="head">
<h1>Movimentações</h1>
<button class="secondary" id="logout">Sair</button>
</div>
<section class="card">
<form method="get" class="mov-form">
<input type="hidden" name="pagina" value="movimentacoes">
<div class="filter-grid">
<div><label>Data inicial</label><input type="date" name="data_inicio" value="<?=h($filtroInicio)?>"></div>
<div><label>Data final</label><input type="date" name="data_fim" value="<?=h($filtroFim)?>"></div>
<div><label>Pesquisar nome, CPF ou telefone</label><input name="busca" value="<?=h($filtroBusca)?>" placeholder="Nome, CPF ou telefone"></div>
<div><label>Empresa</label><select name="empresa"><option value="">Todas</option><?php foreach($empresasMov as $emp):?><option value="<?=h($emp)?>" <?=$filtroEmpresa===$emp?'selected':''?>><?=h($emp)?></option><?php endforeach;?></select></div>
<div><label>Tipo de acesso</label><select name="tipo_acesso"><option value="">Todos</option><?php foreach(['Funcionario'=>'Funcionário','Aluno'=>'Aluno','Professor'=>'Professor','Visitante'=>'Visitante','Terceirizado'=>'Terceirizado'] as $ch=>$desc):?><option value="<?=h($ch)?>" <?=$filtroAcesso===$ch?'selected':''?>><?=h($desc)?></option><?php endforeach;?></select></div>
<div><label>Movimento</label><select name="movimento"><option value="">Todos</option><option value="ENTRADA" <?=$filtroMov==='ENTRADA'?'selected':''?>>ENTRADA</option><option value="SAIDA" <?=$filtroMov==='SAIDA'?'selected':''?>>SAÍDA</option></select></div>
<div><label>Ordenar por</label><select name="ordenar"><option value="data" <?=$ordenacao==='data'?'selected':''?>>Data</option><option value="nome" <?=$ordenacao==='nome'?'selected':''?>>Nome</option><option value="tipo" <?=$ordenacao==='tipo'?'selected':''?>>Movimento</option></select></div>
<div><label>Ordem</label><select name="direcao"><option value="desc" <?=$direcao==='desc'?'selected':''?>>Decrescente</option><option value="asc" <?=$direcao==='asc'?'selected':''?>>Crescente</option></select></div>
</div>
<div class="actions"><button type="submit">Filtrar</button><a class="btn secondary" href="?pagina=movimentacoes">Limpar</a><button type="submit" name="exportar" value="csv">Exportar CSV</button><button type="submit" name="exportar" value="pdf">Exportar PDF</button><button type="submit" name="exportar" value="xlsx">Exportar Excel</button></div>
</form>
<div class="actions" style="align-items:center;margin:14px 0">
<button type="button" class="secondary" id="excluirPar" disabled>Excluir selecionado</button>
<span class="small" id="selecionadoAviso">Selecione uma linha abaixo para excluir o par completo.</span>
</div>
<p class="small">Até 500 linhas na tela; CSV, PDF e Excel exportam todos os pares filtrados. Entradas sem saída permanecem em aberto. Fotos continuam guardadas, sem link nesta tela.</p>
<?php if(!$pessoas):?>
<p>Nenhuma movimentação encontrada para o filtro informado.</p>
<?php else:?>
<div style="overflow:auto">
<table>
<thead><tr><th>Selecionar</th><th>Data</th><th>Pessoa / Empresa</th><th>Tipo de acesso</th><th>Entrada</th><th>Saída</th></tr></thead>
<tbody>
<?php foreach($pessoas as $row):?>
<tr>
<td><input type="checkbox" name="par_selecionado" class="selecionar-par" aria-label="Selecionar movimentação de <?=h($row['nome'])?>" data-entrada="<?= (int)($row['entrada_id']??0) ?>" data-saida="<?= (int)($row['saida_id']??0) ?>" data-pessoa="<?=h($row['nome'])?>" data-data="<?=h($row['data'])?>"></td>
<td><?=h(date('d/m/Y',strtotime($row['data'])))?></td>
<td><?=h($row['nome'])?><br><span class="small"><?=h($row['empresa'])?></span></td>
<td><?=h($row['tipo_acesso'])?></td>
<td><?= $row['entrada']!==null?h($row['entrada']):'—' ?></td>
<td><?= $row['saida']!==null?h($row['saida']):'—' ?></td>
</tr>
<?php endforeach;?>
</tbody></table>
</div>
<?php endif;?>
</section>
