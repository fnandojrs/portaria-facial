if(page==='movimentacoes'){
  const botao=document.getElementById('excluirPar');
  const aviso=document.getElementById('selecionadoAviso');
  if(botao){
    document.querySelectorAll('.selecionar-par').forEach(radio=>radio.addEventListener('change',()=>{
      botao.disabled=false;
      aviso.textContent=`Selecionado: ${radio.dataset.pessoa} (${radio.dataset.data})`;
    }));
    botao.addEventListener('click',async()=>{
      const radio=document.querySelector('.selecionar-par:checked');
      if(!radio)return;
      const quant=(Number(radio.dataset.entrada)>0?1:0)+(Number(radio.dataset.saida)>0?1:0);
      if(!confirm(`Excluir ${quant===2?'ENTRADA e SAÍDA':'este registro avulso'} de ${radio.dataset.pessoa} em ${radio.dataset.data}? Essa ação não pode ser desfeita.`))return;
      botao.disabled=true;
      try{
        await post({action:'excluir_par',entrada_id:radio.dataset.entrada,saida_id:radio.dataset.saida});
        location.reload();
      }catch(e){
        const erro=document.getElementById('error');if(erro)erro.textContent=e.message;
        botao.disabled=false;
      }
    });
  }
}
