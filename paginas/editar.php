<div class="head">
<h1>Editar cadastros</h1>
<button class="secondary" id="logout">Sair</button>
</div>
<section class="card">
<label>Pesquisar nome, CPF ou telefone</label>
<input id="searchPerson" type="search" placeholder="Digite para pesquisar">
<div class="filter-grid">
<div><label for="filterEmpresa">Empresa</label><select id="filterEmpresa"><option value="">Todas</option></select></div>
<div><label for="filterAcesso">Tipo de acesso</label><select id="filterAcesso"><option value="">Todos</option><option value="Funcionario">Funcionário</option><option value="Aluno">Aluno</option><option value="Professor">Professor</option><option value="Visitante">Visitante</option><option value="Terceirizado">Terceirizado</option></select></div>
<div><label for="filterAtivo">Situação</label><select id="filterAtivo"><option value="">Todos</option><option value="1">Ativos</option><option value="0">Inativos</option></select></div>
<div><label for="orderPessoas">Ordenar por</label><select id="orderPessoas"><option value="nome">Nome</option><option value="criado_em">Data do cadastro</option><option value="id">Código</option></select></div>
<div><label for="dirPessoas">Ordem</label><select id="dirPessoas"><option value="asc">Crescente</option><option value="desc">Decrescente</option></select></div>
</div>
<p id="countPeople" class="small"></p>
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
<label for="tipo_acesso">Tipo de Acesso *</label>
<select id="personType" name="tipo_acesso" required>
<option value="">Selecione...</option>
<option value="Funcionario">Funcionário</option>
<option value="Aluno">Aluno</option>
<option value="Professor">Professor</option>
<option value="Visitante">Visitante</option>
<option value="Terceirizado">Terceirizado</option>
</select>
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
