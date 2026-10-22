const $=id=>document.getElementById(id);
async function post(data) {
    const form=new FormData();
    for(const [k,v] of Object.entries({...data,csrf:token}))form.append(k,v);
    const res=await fetch(location.pathname,{method:'POST',body:form,credentials:'same-origin'});
    const r=await res.json();
    if(!r.ok)throw new Error(r.erro||'Falha inesperada');
    return r
}
for(const id of ['setup','login'])if($(id))$(id).addEventListener('submit',async ev=>{ev.preventDefault();try{await post(Object.fromEntries(new FormData(ev.target)));location.reload()}catch(e){$('error').textContent=e.message}});
if($('logout'))$('logout').onclick=async()=> {
    await post({action:'logout'});
    location.reload()
};
