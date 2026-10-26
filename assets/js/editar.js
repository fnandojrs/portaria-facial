if($('peopleList')) {
    const list=$('peopleList');
    const q=$('searchPerson');
    const empresas=[...new Set(persons.map(p=>p.empresa||'').filter(Boolean))].sort((a,b)=>a.localeCompare(b,'pt-BR'));
    for(const empresa of empresas){const opt=document.createElement('option');opt.value=empresa;opt.textContent=empresa;$('filterEmpresa').appendChild(opt)}
    function renderPeople() {
        const term=q.value.toLocaleLowerCase('pt-BR').trim();
        const empresa=$('filterEmpresa').value,acesso=$('filterAcesso').value,ativo=$('filterAtivo').value;
        const ordem=$('orderPessoas').value,dir=$('dirPessoas').value==='desc'?-1:1;
        const encontrados=persons.filter(p=>
            [p.nome,p.cpf,p.telefone].some(v=>(v||'').toLocaleLowerCase('pt-BR').includes(term))&&
            (!empresa||p.empresa===empresa)&&(!acesso||p.tipo_acesso===acesso)&&
            (ativo===''||String(Number(p.ativo))===ativo)
        ).sort((a,b)=>{
            if(ordem==='id')return (Number(a.id)-Number(b.id))*dir;
            return String(a[ordem]||'').localeCompare(String(b[ordem]||''),'pt-BR',{numeric:true})*dir;
        });
        list.replaceChildren();
        $('countPeople').textContent=encontrados.length+' cadastro(s) encontrado(s).';
        for(const p of encontrados) {
            const r=document.createElement('div');
            r.style.cssText='display:flex;align-items:center;justify-content:space-between;gap:12px;padding:10px 0;border-bottom:1px solid #39465c';
            const t=document.createElement('span');
            t.textContent=p.nome+' — '+formatCPF(p.cpf)+' — '+(p.tipo_acesso||'Funcionário')+(Number(p.ativo)?' (ativo)':' (inativo)');
            const b=document.createElement('button');b.textContent='Editar';b.type='button';b.onclick=()=>edit(p);
            r.append(t,b);list.appendChild(r);
        }
    }
    for(const id of ['filterEmpresa','filterAcesso','filterAtivo','orderPessoas','dirPessoas'])$(id).addEventListener('change',renderPeople);
    function edit(p) {
        $('listaPanel').classList.add('hidden');
        $('editPanel').classList.remove('hidden');
        $('personId').value=p.id;
        $('personName').value=p.nome;
        $('personCpf').value=formatCPF(p.cpf||'');
        $('personPhone').value=formatTelefone(p.telefone||'');
        $('personType').value=p.tipo_acesso||'Funcionario';
        $('personCompany').value=p.empresa||'';
        $('personActive').checked=Number(p.ativo)===1;
        $('personPhoto').src='?foto='+encodeURIComponent(p.foto);
        $('newPhoto').value='';
        $('editStatus').textContent='';
        $('editPanel').scrollIntoView({behavior:'smooth'})
    }
    q.oninput=renderPeople;
    renderPeople();
    $('cancelEdit').onclick=()=>{
        if(!confirm('Deseja cancelar a edição e voltar à lista?'))return;
        $('editPanel').classList.add('hidden');
        $('listaPanel').classList.remove('hidden');
    };
    $('deletePerson').onclick=async()=> {
        const id=$('personId').value;
        const nome=$('personName').value;
        if(!id)return;
        if(!confirm('Excluir definitivamente o cadastro de "'+nome+'"?\n\nPermitido somente se NAO houver entrada ou saida registrada.'))return;
        try {
            await post({action:'excluir_pessoa',id});
            $('editStatus').textContent='Cadastro excluido. Atualizando...';
            setTimeout(()=>location.reload(),550);
        }
        catch(e) {
            $('editStatus').textContent=e.message;
        }
    };
    $('editPerson').onsubmit=async ev=> {
        ev.preventDefault();
        const form=ev.target;
        const file=$('newPhoto').files[0];
        let img='';
        try {
            validarFormulario(form);
            if(file) {
                if(file.type!=='image/jpeg')throw new Error('Use uma foto JPEG.');
                if(file.size>3800000)throw new Error('Foto muito grande.');
                img=await new Promise((resolve,reject)=>{const rd=new FileReader();rd.onload=()=>resolve(rd.result);rd.onerror=()=>reject(new Error('Falha ao ler foto'));rd.readAsDataURL(file)});
            }
            await post({action:'editar_pessoa',id:$('personId').value,nome:$('personName').value,cpf:$('personCpf').value,telefone:$('personPhone').value,empresa:$('personCompany').value,tipo_acesso:$('personType').value,ativo:$('personActive').checked?'1':'0',foto:img});
            $('editStatus').textContent='Alterações salvas. Atualizando…';
            setTimeout(()=>location.reload(),600)
        }
        catch(e) {
            $('editStatus').textContent=e.message
        }
    };
}
