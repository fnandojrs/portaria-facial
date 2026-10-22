<h1>Configuração inicial</h1>
<section class="card">
<p>Defina senhas diferentes para administração e terminal. Este cadastro deve ser realizado no computador do administrador.</p>
<form id="setup">
<input type="hidden" name="action" value="setup">
<label>Senha administrador</label>
<input type="password" name="senha_admin" minlength="10" required>
<label>Senha terminal</label>
<input type="password" name="senha_terminal" minlength="8" required>
<button>Configurar</button>
</form>
</section>
