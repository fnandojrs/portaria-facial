<h1 style="text-align:center">Registro de presença</h1>
<p style="text-align:center">Posicione o rosto na câmera para capturar automaticamente.</p>
<section class="card" style="text-align:center">
<div class="cam hidden" id="cam">
<video id="video" autoplay muted playsinline>
</video>
<div class="facebox">
</div>
</div>
<canvas id="canvas" class="hidden">
</canvas>
<button id="openCam" type="button">Abrir câmera</button>
<button id="capture" type="button" class="secondary hidden">Fotografar manualmente</button>
<p id="status" aria-live="polite">Aguardando câmera.</p>
<div id="result" class="hidden">
</div>
</section>
