if($('peopleList')) {
    const list=$('peopleList');
    const q=$('searchPerson');
    function renderPeople() {
        const term=q.value.toLocaleLowerCase('pt-BR').trim();
        list.replaceChildren();
        for(const p of persons.filter(p=>[p.nome,p.cpf,p.telefone].some(v=>(v||'').toLocaleLowerCase('pt-BR').includes(term)))) {
            const r=document.createElement('div');
            r.style.cssText='display:flex;align-items:center;justify-content:space-between;gap:12px;padding:10px 0;border-bottom:1px solid #39465c';
            const t=document.createElement('span');
            t.textContent=p.nome+' — '+(formatCPF(p.cpf)||p.telefone||'Sem CPF')+(Number(p.ativo)?' (ativo)':' (inativo)');
            const b=document.createElement('button');
            b.textContent='Editar';
            b.type='button';
            b.onclick=()=>edit(p);
            r.append(t,b);
            list.appendChild(r)
        }
    }
    function edit(p) {
        $('editPanel').classList.remove('hidden');
        $('personId').value=p.id;
        $('personName').value=p.nome;
        $('personCpf').value=formatCPF(p.cpf||'');
        $('personPhone').value=formatTelefone(p.telefone||'');
        $('personMatricula').value=p.matricula||'';
        $('personCompany').value=p.empresa||'';
        $('personActive').checked=Number(p.ativo)===1;
        $('personPhoto').src='?foto='+encodeURIComponent(p.foto);
        $('newPhoto').value='';
        $('editStatus').textContent='';
        $('editPanel').scrollIntoView({behavior:'smooth'})
    }
    q.oninput=renderPeople;
    renderPeople();
    $('cancelEdit').onclick=()=>$('editPanel').classList.add('hidden');
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
            await post({action:'editar_pessoa',id:$('personId').value,nome:$('personName').value,cpf:$('personCpf').value,telefone:$('personPhone').value,empresa:$('personCompany').value,matricula:$('personMatricula').value,ativo:$('personActive').checked?'1':'0',foto:img});
            $('editStatus').textContent='Alterações salvas. Atualizando…';
            setTimeout(()=>location.reload(),600)
        }
        catch(e) {
            $('editStatus').textContent=e.message
        }
    };
}
