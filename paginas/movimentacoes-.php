<div class="head">
<h1>Movimentações</h1>
<button class="secondary" id="logout">Sair</button>
</div>
<section class="card">
<form method="get" class="actions" style="align-items:end;margin-bottom:18px">
<input type="hidden" name="pagina" value="movimentacoes">
<div style="flex:1;min-width:180px">
<label for="data_inicio">Data inicial</label>
<input type="date" id="data_inicio" name="data_inicio" value="<?=h($filtroInicio)?>">
</div>
<div style="flex:1;min-width:180px">
<label for="data_fim">Data final</label>
<input type="date" id="data_fim" name="data_fim" value="<?=h($filtroFim)?>">
</div>
<div class="actions" style="margin-bottom:14px">
<button type="submit">Filtrar</button>
<a class="btn secondary" href="?pagina=movimentacoes">Limpar</a>
<a class="btn" href="?pagina=movimentacoes<?= $filtroInicio!==''?'&data_inicio='.rawurlencode($filtroInicio):'' ?><?= $filtroFim!==''?'&data_fim='.rawurlencode($filtroFim):'' ?>&exportar=csv">Exportar CSV</a>
</div>
</form>

<?php if(!$pessoas):?>
<p>Nenhuma movimentação encontrada para o filtro informado.</p>
<?php else:?>
<div style="overflow:auto">
<table>
<thead>
<tr>
<th>ID</th>
<th>Data</th>
<th>Pessoa</th>
<th>Movimento</th>
<th>Foto</th>
<th>Ações</th>
</tr>
</thead>
<tbody>
<?php foreach($pessoas as $row):?>
<tr id="mov-row-<?= (int)$row['id'] ?>">
<td><?= (int)$row['id'] ?></td>
<td><?=h($row['criado_em'])?></td>
<td>
<?=h($row['nome'])?><br>
<span class="small"><?=h($row['empresa'])?></span>
</td>
<td><?=h($row['tipo'])?></td>
<td><a href="?foto=<?=rawurlencode($row['foto_captura'])?>" target="_blank" rel="noopener">Ver foto</a></td>
<td>
<button type="button" class="secondary delete-mov-btn" data-id="<?= (int)$row['id'] ?>">Excluir</button>
</td>
</tr>
<?php endforeach;?>
</tbody>
</table>
</div>
<?php endif;?>
</section>
