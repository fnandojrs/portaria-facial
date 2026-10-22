<h1>
<?= $pagina==='terminal'?'Terminal de presença':'Administração' ?>
</h1>
<section class="card">
<form id="login">
<input type="hidden" name="action" value="login">
<input type="hidden" name="perfil" value="<?= $pagina==='terminal'?'terminal':'admin' ?>">
<label>Senha de acesso</label>
<input type="password" name="senha" required>
<button>Entrar</button>
</form>
</section>
