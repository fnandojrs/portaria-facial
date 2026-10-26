if($('cadastro')) {
    const formulario=$('cadastro');
    const lista=$('listaPanel');
    const panel=$('cadastroPanel');
    const edicao=$('editPanel');
    const status=$('status');

    function voltarLista() {
        stopCam();
        panel.classList.add('hidden');
        edicao.classList.add('hidden');
        lista.classList.remove('hidden');
        window.scrollTo({top:0,behavior:'smooth'});
    }
    function limparCampos() {
        stopCam();
        photo=null;
        formulario.reset();
        $('preview').removeAttribute('src');
        $('preview').classList.add('hidden');
        $('video').classList.remove('hidden');
        status.textContent='Campos limpos. Nenhum cadastro foi apagado.';
    }
    $('novoCadastro').onclick=()=>{
        limparCampos();
        lista.classList.add('hidden');
        edicao.classList.add('hidden');
        panel.classList.remove('hidden');
        panel.scrollIntoView({behavior:'smooth'});
    };
    $('clearCadastro').onclick=limparCampos;
    $('cancelCadastro').onclick=()=>{
        if(confirm('Deseja cancelar e voltar à lista de pessoas?')) {
            limparCampos();
            voltarLista();
        }
    };
    formulario.onsubmit=async ev=>{
        ev.preventDefault();
        try {
            validarFormulario(formulario);
            const cpf=formulario.querySelector('[name="cpf"]').value;
            const busca=await post({action:'consultar_cpf',cpf});
            if(busca.existente) {
                if(busca.ativo) {
                    status.textContent='CLIENTE JA EXISTE';
                    alert('CLIENTE JA EXISTE');
                } else if(confirm('Cliente já existe mas está desativado. Deseja liberar?')) {
                    await post({action:'reativar_pessoa',cpf});
                    alert('Cadastro reativado com sucesso!');
                    location.reload();
                }
                return;
            }
            if(!photo)throw new Error('Capture a foto antes de salvar.');
            const values=Object.fromEntries(new FormData(formulario));
            await post({...values,foto:photo});
            alert('Cadastro salvo com sucesso!');
            location.reload();
        } catch(e) {
            status.textContent='Falha ao cadastrar: '+e.message;
        }
    };
}
