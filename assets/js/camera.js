let stream=null,faceDetector=null,loopId=null,busy=false,photo=null,stable=0;
function stopCam() {
    if(loopId)clearInterval(loopId);
    loopId=null;
    if(stream)stream.getTracks().forEach(t=>t.stop());
    stream=null
}
async function camera() {
    try {
        stopCam();
        stream=await navigator.mediaDevices.getUserMedia({video:{facingMode:{ideal:'user'},width:{ideal:640},height:{ideal:480}},audio:false});
        $('video').srcObject=stream;
        await $('video').play();
        if(page==='cadastro') {
            $('video').classList.remove('hidden');
            $('preview').classList.add('hidden');
        }
        if($('cam'))$('cam').classList.remove('hidden');
        $('capture').classList.remove('hidden');
        $('status').textContent='Câmera aberta. Enquadre o rosto.';
        if(page==='terminal')startAuto()
    }
    catch(e) {
        $('status').textContent='Não foi possível abrir a câmera: '+e.message+' (necessário HTTPS no celular).'
    }
}
if($('openCam'))$('openCam').onclick=camera;
function photoData() {
    const v=$('video'),c=$('canvas');
    if(!v.videoWidth)throw new Error('Câmera ainda não está pronta.');
    c.width=640;
    c.height=Math.round(640*v.videoHeight/v.videoWidth);
    c.getContext('2d').drawImage(v,0,0,c.width,c.height);
    return c.toDataURL('image/jpeg',.82)
}
if($('capture'))$('capture').onclick=()=>capture();
async function capture() {
    if(busy)return;
    busy=true;
    try {
        photo=photoData();
        stopCam();
        if(page==='terminal') {
            await identify(photo)
        }
        else {
            $('preview').src=photo;
            $('video').classList.add('hidden');
            $('preview').classList.remove('hidden');
            $('status').textContent='Foto capturada. Salve o cadastro.'
        }
    }
    catch(e) {
        $('status').textContent=e.message
    }
    finally {
        busy=false
    }
}
async function startAuto() {
    if(!('FaceDetector' in window)) {
        $('status').textContent='Detecção automática não disponível neste navegador. Use “Fotografar manualmente”.';
        return
    }
    try {
        faceDetector=new FaceDetector({fastMode:true,maxDetectedFaces:2})
    }
    catch(e) {
        $('status').textContent='Use o botão manual.';
        return
    }
    loopId=setInterval(async()=>{if(busy||!stream)return;try{const faces=await faceDetector.detect($('video'));if(faces.length===1){stable++;$('status').textContent='Rosto detectado: mantenha a posição…';if(stable>=3){stable=0;clearInterval(loopId);loopId=null;await capture()}}else{stable=0;$('status').textContent=faces.length>1?'Somente uma pessoa na imagem.':'Centralize o rosto na câmera.'}}catch(e){clearInterval(loopId);loopId=null;$('status').textContent='Detecção automática indisponível. Use o botão manual.'}},750)
}
// A pessoa só é registrada após confirmar a janela.
async function identify(img) {
    $('result').classList.add('hidden');
    $('status').textContent='Comparando o rosto com os cadastros…';
    try {
        const r=await post({action:'identificar',foto:img});
        if(r.status==='correspondencia') {
            $('status').textContent='Confirme a movimentação na janela.';
            mostrarConfirmacao(r);
        } else {
            $('status').textContent='Não identificado: '+(r.motivo||r.status);
            retryButton();
        }
    } catch(e) {
        $('status').textContent='Erro: '+e.message;
        retryButton();
    }
}

function retryButton() {
    const b=document.createElement('button');
    b.textContent='Tentar novamente';
    b.onclick=()=>location.reload();
    $('result').replaceChildren(b);
    $('result').classList.remove('hidden');
}

function mostrarConfirmacao(dados) {
    const overlay=$('confirmOverlay');
    $('confirmName').textContent=dados.pessoa.nome;
    $('confirmType').textContent=dados.tipo==='SAIDA'?'SAÍDA':'ENTRADA';
    $('confirmType').classList.toggle('saida',dados.tipo==='SAIDA');
    $('confirmError').textContent='';
    overlay.classList.remove('hidden');

    const confirmar=$('confirmPresence');
    const cancelar=$('cancelPresence');
    confirmar.disabled=false;
    cancelar.disabled=false;
    confirmar.focus();

    confirmar.onclick=async()=>{
        confirmar.disabled=true;
        cancelar.disabled=true;
        $('confirmError').textContent='Registrando…';
        try {
            await post({action:'presenca',token:dados.token,tipo:dados.tipo});
            // Limpa nome, foto e confirmação para o próximo atendimento.
            location.reload();
        } catch(e) {
            $('confirmError').textContent=e.message;
            confirmar.disabled=false;
            cancelar.disabled=false;
        }
    };

    cancelar.onclick=async()=>{
        confirmar.disabled=true;
        cancelar.disabled=true;
        try {
            await post({action:'cancelar_presenca',token:dados.token});
            location.reload();
        } catch(e) {
            $('confirmError').textContent=e.message;
            cancelar.disabled=false;
        }
    };
}
