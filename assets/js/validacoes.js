// Máscaras e validação também ocorrem no servidor, em config.php.
const onlyDigits=value=>String(value||'').replace(/\D/g,'');
function formatCPF(value) {
    const d=onlyDigits(value).slice(0,11);
    return d.replace(/^(\d{3})(\d)/,'$1.$2').replace(/^(\d{3})\.(\d{3})(\d)/,'$1.$2.$3').replace(/(\d{3})(\d{1,2})$/,'$1-$2');
}
function formatTelefone(value) {
    const d=onlyDigits(value).slice(0,11);
    if(d.length<=2)return d.length?'('+d:'';
    if(d.length<=6)return '('+d.slice(0,2)+') '+d.slice(2);
    const corte=d.length===11?7:6;
    return '('+d.slice(0,2)+') '+d.slice(2,corte)+'-'+d.slice(corte);
}
function cpfValido(value) {
    const d=onlyDigits(value);
    if(d.length!==11||/^(\d)\1{10}$/.test(d))return false;
    for(let n=9;n<=10;n++) {
        let sum=0;
        for(let i=0;i<n;i++)sum+=Number(d[i])*(n+1-i);
        let x=(sum*10)%11;if(x===10)x=0;
        if(x!==Number(d[n]))return false;
    }
    return true;
}
function validarFormulario(form) {
    const cpf=form.querySelector('[name="cpf"]');
    const tel=form.querySelector('[name="telefone"]');
    if(!cpfValido(cpf.value))throw new Error('CPF inválido. Confira os números.');
    if(![10,11].includes(onlyDigits(tel.value).length))throw new Error('Telefone deve ter 10 ou 11 dígitos com DDD.');
    if(!form.querySelector('[name="empresa"]').value.trim())throw new Error('Informe a empresa.');
}
for(const input of document.querySelectorAll('.cpf-input'))input.addEventListener('input',()=>input.value=formatCPF(input.value));
for(const input of document.querySelectorAll('.telefone-input'))input.addEventListener('input',()=>input.value=formatTelefone(input.value));
