if($('cadastro'))$('cadastro').onsubmit=async ev=> {
    ev.preventDefault();
    if(!photo) {
        $('status').textContent='Capture a foto antes de salvar.';
        return
    }
    try {
        validarFormulario(ev.target);
        const values=Object.fromEntries(new FormData(ev.target));
        await post({...values,foto:photo});
        $('status').textContent='Cadastro salvo!';
        photo=null;
        ev.target.reset();
        $('preview').classList.add('hidden');
        $('video').classList.remove('hidden')
    }
    catch(e) {
        $('status').textContent='Falha ao cadastrar: '+e.message
    }
};
