#!/usr/bin/env python3
"""Academic demonstrator: Haar detection + LBPH verification, NOT production biometrics."""
import json, sys, cv2, numpy as np

def reply(**kwargs):
    print(json.dumps(kwargs, ensure_ascii=False));sys.exit(0)

def face(path):
    img=cv2.imread(path)
    if img is None:return None,'Imagem inválida'
    gray=cv2.cvtColor(img,cv2.COLOR_BGR2GRAY)
    cascade=cv2.CascadeClassifier(cv2.data.haarcascades+'haarcascade_frontalface_default.xml')
    if cascade.empty():return None,'Detector Haar não encontrado'
    boxes=cascade.detectMultiScale(gray,scaleFactor=1.1,minNeighbors=5,minSize=(65,65))
    if len(boxes)!=1:return None,'É necessário exatamente um rosto visível'
    x,y,w,h=boxes[0];roi=gray[y:y+h,x:x+w]
    return cv2.equalizeHist(cv2.resize(roi,(120,120))),None

def main():
    if len(sys.argv)<3:reply(ok=False,erro='Argumentos ausentes')
    action,path=sys.argv[1:3]
    target,err=face(path)
    if err:
        if action=='validar':reply(ok=False,erro=err)
        reply(status='inconclusivo',motivo=err)
    if action=='validar':reply(ok=True)
    if action!='identificar' or len(sys.argv)<4:reply(status='erro',motivo='Ação inválida')
    people=json.loads(sys.argv[3]);images=[];labels=[]
    for item in people[:100]:
        ref,problem=face(item['foto'])
        if ref is not None:images.append(ref);labels.append(int(item['id']))
    if not images:reply(status='inconclusivo',motivo='Nenhum rosto válido foi cadastrado')
    if not hasattr(cv2,'face'):reply(status='erro',motivo='OpenCV instalado sem módulo cv2.face')
    rec=cv2.face.LBPHFaceRecognizer_create(radius=1,neighbors=8,grid_x=8,grid_y=8)
    rec.train(images,np.asarray(labels,dtype=np.int32))
    identity,distance=rec.predict(target)
    # A provisional conservative threshold; adjust only after local tests.
    if not np.isfinite(distance) or distance>48:
        reply(status='sem_correspondencia',motivo='Sem correspondência suficientemente próxima')
    # Small data sets and LBPH can yield ambiguous identifications; recommend confirmation.
    reply(status='correspondencia',id=int(identity),distancia=round(float(distance),2),motivo='Resultado experimental: confirmar com operador')

if __name__=='__main__':
    try:main()
    except Exception as exc:reply(status='erro',motivo=f'Falha no reconhecimento: {type(exc).__name__}')
