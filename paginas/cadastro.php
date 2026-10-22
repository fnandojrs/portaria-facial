<div class="head">
<h1>Cadastro de pessoas</h1>
<button class="secondary" id="logout">Sair</button>
</div>
<section class="card">
<form id="cadastro">
<input type="hidden" name="action" value="cadastrar">
<label>Nome</label>
<input name="nome" maxlength="120" required>
<label>CPF *</label>
<input name="cpf" class="cpf-input" inputmode="numeric" maxlength="14" placeholder="000.000.000-00" required>
<label>Telefone *</label>
<input name="telefone" class="telefone-input" inputmode="tel" maxlength="15" placeholder="(00) 00000-0000" required>
<label>Empresa *</label>
<input name="empresa" maxlength="120" required>
<label>Matrícula / Código (opcional)</label>
<input name="matricula" maxlength="40">
<label>Foto de referência</label>
<div class="cam camera-cadastro" aria-label="Área de captura da foto">
<video id="video" autoplay playsinline muted></video>
<img id="preview" class="preview hidden" alt="Foto capturada">
<div class="guia-rosto" id="guiaRosto" aria-hidden="true"></div>
</div>
<canvas id="canvas" class="hidden"></canvas>
<div class="actions">
<button type="button" id="openCam">Abrir câmera</button>
<button type="button" id="capture" class="secondary">Capturar foto</button>
</div>
<p id="status" aria-live="polite">Capture uma foto bem iluminada com apenas um rosto.</p>
<button type="submit">Salvar cadastro</button>
</form>
</section>
