<div class="head">
<h1>Editar cadastros</h1>
<button class="secondary" id="logout">Sair</button>
</div>
<section class="card">
<label>Pesquisar nome, CPF ou telefone</label>
<input id="searchPerson" type="search" placeholder="Digite para pesquisar">
<div id="peopleList">
</div>
</section>
<section class="card hidden" id="editPanel">
<h2>Editar pessoa</h2>
<form id="editPerson">
<input type="hidden" name="action" value="editar_pessoa">
<input type="hidden" name="id" id="personId">
<label>Nome</label>
<input name="nome" id="personName" maxlength="120" required>
<label>CPF *</label>
<input name="cpf" id="personCpf" class="cpf-input" inputmode="numeric" maxlength="14" required>
<label>Telefone *</label>
<input name="telefone" id="personPhone" class="telefone-input" inputmode="tel" maxlength="15" required>
<label>Empresa *</label>
<input name="empresa" id="personCompany" maxlength="120" required>
<label>Matrícula / Código (opcional)</label>
<input name="matricula" id="personMatricula" maxlength="40">
<label>
<input type="checkbox" id="personActive" name="ativo" value="1" style="width:auto"> Cadastro ativo</label>
<p>Foto cadastrada</p>
<img id="personPhoto" class="preview" style="max-width:230px" alt="Foto do cadastro">
<p>Para substituir a foto, selecione uma imagem JPEG nítida com um único rosto. Não é obrigatório.</p>
<input id="newPhoto" type="file" accept="image/jpeg" capture="user">
<div class="actions">
<button type="submit">Salvar alterações</button>
<button type="button" class="secondary" id="cancelEdit">Cancelar</button>
<button type="button" id="deletePerson" style="background:#b32632">Excluir cadastro</button>
</div>
</form>
<p id="editStatus" aria-live="polite">
</p>
</section>
