#!/usr/bin/env python3
"""Portaria acadêmica: YuNet + SFace CPU; resultados exigem confirmação humana."""
import os, sys, json, hashlib
from pathlib import Path
import numpy as np
import cv2

BASE = Path('/opt/portaria/modelos')
YUNET = BASE / 'face_detection_yunet_2023mar.onnx'
SFACE = BASE / 'face_recognition_sface_2021dec.onnx'
CACHE = Path('/var/lib/portaria/cache_sface')
# Valores INICIAIS conservadores; exigem calibração com fotos reais de teste.
MIN_SCORE = float(os.getenv('PORTARIA_SFACE_MIN_SCORE', '0.50'))
MIN_GAP = float(os.getenv('PORTARIA_SFACE_MIN_GAP', '0.08'))


def reply(**kwargs):
    print(json.dumps(kwargs, ensure_ascii=False, allow_nan=False))
    sys.exit(0)


def models():
    if not YUNET.is_file() or not SFACE.is_file():
        reply(status='erro', ok=False, erro='Modelos YuNet/SFace ausentes. Execute o instalador.', motivo='Modelos ausentes')
    cv2.setNumThreads(1)
    detector = cv2.FaceDetectorYN_create(str(YUNET), '', (320,320), 0.85, 0.3, 5000)
    recognizer = cv2.FaceRecognizerSF_create(str(SFACE), '')
    return detector, recognizer


def embed(path, detector, recognizer):
    im = cv2.imread(str(path))
    if im is None:
        return None, 'Imagem inválida'
    h, w = im.shape[:2]
    # Evita tamanho extremo na CPU e preserva proporção
    if max(w,h)>1024:
        f=1024/max(w,h)
        im=cv2.resize(im,(max(1,int(w*f)),max(1,int(h*f))))
        h,w=im.shape[:2]
    detector.setInputSize((w,h))
    _, faces=detector.detect(im)
    if faces is None or len(faces)!=1:
        return None, 'É necessário exatamente um rosto visível'
    aligned=recognizer.alignCrop(im, faces[0])
    vector=recognizer.feature(aligned).ravel().astype(np.float32)
    norm=float(np.linalg.norm(vector))
    if norm<1e-9 or not np.isfinite(norm):
        return None, 'Não foi possível extrair o rosto'
    return vector/norm, None


def cached(path, detector, recognizer):
    # O caminho e o mtime invalidam o cache ao alterar a fotografia.
    p=Path(path)
    stat=p.stat()
    key=hashlib.sha256(f'{p}:{stat.st_size}:{stat.st_mtime_ns}'.encode()).hexdigest()
    try:
        CACHE.mkdir(mode=0o750,parents=True,exist_ok=True)
        f=CACHE/(key+'.npy')
        if f.is_file():
            v=np.load(f,allow_pickle=False)
            if v.shape==(128,) and np.isfinite(v).all():
                return v.astype(np.float32),None
        v,err=embed(p,detector,recognizer)
        if err:return None,err
        # grava cache atomicamente sem sobrescrever outros processos
        tmp=CACHE/(key+f'.{os.getpid()}.tmp')
        with open(tmp,'wb') as fp:np.save(fp,v,allow_pickle=False)
        os.replace(tmp,f)
        return v,None
    except (OSError,ValueError):
        return embed(p,detector,recognizer)


def main():
    if len(sys.argv)<3:reply(ok=False,status='erro',erro='Argumentos ausentes')
    action, path=sys.argv[1:3]
    det,rec=models()
    target,err=embed(path,det,rec)
    if action=='validar':
        reply(ok=err is None, **({'erro':err} if err else {}))
    if err:reply(status='inconclusivo',motivo=err)
    if action!='identificar' or len(sys.argv)<4:
        reply(status='erro',motivo='Ação inválida')
    persons=json.loads(sys.argv[3]);scores=[]
    for item in persons[:100]:
        try:
            v,why=cached(item['foto'],det,rec)
            if v is not None:scores.append((float(np.dot(target,v)),int(item['id'])))
        except (KeyError,ValueError,OSError):
            continue
    if not scores:reply(status='inconclusivo',motivo='Nenhum rosto válido cadastrado')
    scores.sort(reverse=True)
    score,identity=scores[0]
    second=scores[1][0] if len(scores)>1 else -1.0
    if score<MIN_SCORE:
        reply(status='sem_correspondencia',motivo='Nenhuma correspondência confiável')
    if len(scores)>1 and score-second<MIN_GAP:
        reply(status='inconclusivo',motivo='Rostos com resultados muito próximos; conferir manualmente')
    # PHP armazena campo distancia: agora ele contém DISTÂNCIA COSSENO (1 - similaridade).
    reply(status='correspondencia',id=identity,distancia=round(1-score,4),
          motivo='Correspondência experimental; confirmar identidade com operador')

if __name__=='__main__':
    try: main()
    except Exception as exc:reply(status='erro',motivo=f'Erro no motor SFace: {type(exc).__name__}')
