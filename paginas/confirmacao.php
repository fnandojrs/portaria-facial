<?php if($pagina==='terminal' && !$setup): ?>
<div id="confirmOverlay" class="confirm-overlay hidden" role="dialog" aria-modal="true" aria-labelledby="confirmHeading">
    <section class="confirm-box">
        <h2 id="confirmHeading">Confirmar movimentação</h2>
        <p>Pessoa identificada:</p>
        <div id="confirmName" class="confirm-name"></div>
        <p class="confirm-question">Registrar?</p>
        <div id="confirmType" class="confirm-type"></div>
        <div class="actions">
            <button type="button" class="secondary" id="cancelPresence">Cancelar</button>
            <button type="button" id="confirmPresence">Confirmar</button>
        </div>
        <p class="confirm-error" id="confirmError" role="alert"></p>
    </section>
</div>
<?php endif; ?>
