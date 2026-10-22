<div class="head">
<h1>Movimentações</h1>
<button class="secondary" id="logout">Sair</button>
</div>
<section class="card">
<table>
<thead>
<tr>
<th>Data</th>
<th>Pessoa</th>
<th>Movimento</th>
<th>Foto</th>
</tr>
</thead>
<tbody>
<?php foreach($pessoas as $row):?>
<tr>
<td>
<?=h($row['criado_em'])?>
</td>
<td>
<?=h($row['nome'])?>
<br>
<span class="small">
<?=h($row['empresa'])?>
</span>
</td>
<td>
<?=h($row['tipo'])?>
</td>
<td>
<a href="?foto=<?=rawurlencode($row['foto_captura'])?>" target="_blank" rel="noopener">Ver foto</a>
</td>
</tr>
<?php endforeach;?>
</tbody>
</table>
</section>
