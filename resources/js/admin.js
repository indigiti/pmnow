(()=>{
  const q=(s,r=document)=>r.querySelector(s), qa=(s,r=document)=>[...r.querySelectorAll(s)];
  const csrf=()=>q('meta[name="csrf-token"]')?.content||'';
  const base=()=>q('meta[name="app-base-path"]')?.content||''; const urlFor=(path='')=>/^https?:\/\//i.test(path)?path:`${base()}${path.startsWith('/')?path:`/${path}`}`;
  const api=async(url,opt={})=>{const headers={'Content-Type':'application/json',...(opt.headers||{})};if((opt.method||'GET').toUpperCase()!=='GET')headers['X-CSRF-Token']=csrf();const r=await fetch(urlFor(url),{...opt,headers});const j=await r.json().catch(()=>({}));if(!r.ok)throw new Error(j.errors?.[0]?.message||`HTTP ${r.status}`);return j.data;};
  const toast=(m)=>{let x=document.createElement('div');x.className='admin-toast';x.textContent=m;document.body.appendChild(x);setTimeout(()=>x.remove(),2200);};
  q('[data-admin-login]')?.addEventListener('submit',async e=>{e.preventDefault();const f=new FormData(e.currentTarget);try{await api('/api/admin/session',{method:'POST',body:JSON.stringify(Object.fromEntries(f))});location.href=urlFor('/admin');}catch(err){q('[data-login-error]').textContent=err.message;}});
  q('[data-admin-logout]')?.addEventListener('click',async()=>{await api('/api/admin/logout',{method:'POST',body:'{}'}).catch(()=>{});location.href=urlFor('/admin/login');});
  qa('[data-editorial-action]').forEach(b=>b.addEventListener('click',async()=>{b.disabled=true;try{await api(`/api/admin/editorial/${b.dataset.contentId}/action`,{method:'POST',body:JSON.stringify({action:b.dataset.editorialAction})});toast(`Content ${b.dataset.editorialAction}d`);location.reload();}catch(e){toast(e.message);}finally{b.disabled=false;}}));
  const updateBulk=()=>{const n=qa('[data-content-select]:checked').length;const bar=q('[data-bulkbar]');if(bar){bar.hidden=n===0;q('[data-selected-count]',bar).textContent=n;}};
  q('[data-select-all]')?.addEventListener('change',e=>{qa('[data-content-select]').forEach(x=>x.checked=e.target.checked);updateBulk();});
  qa('[data-content-select]').forEach(x=>x.addEventListener('change',updateBulk));
  qa('[data-bulk-action]').forEach(b=>b.addEventListener('click',async()=>{const ids=qa('[data-content-select]:checked').map(x=>x.value);if(!ids.length)return;try{await api('/api/admin/editorial/bulk',{method:'POST',body:JSON.stringify({content_ids:ids,action:b.dataset.bulkAction})});location.reload();}catch(e){toast(e.message);}}));
  qa('[data-inbox-filter]').forEach(b=>b.addEventListener('click',()=>{qa('[data-inbox-filter]').forEach(x=>x.classList.remove('active'));b.classList.add('active');const f=b.dataset.inboxFilter;qa('[data-content-row]').forEach(r=>r.hidden=f!=='all'&&r.dataset.status!==f);}));
  qa('[data-source-test]').forEach(b=>b.addEventListener('click',async()=>{b.disabled=true;try{await api(`/api/admin/sources/${b.dataset.sourceTest}/test`,{method:'POST',body:'{}'});toast('Source test completed');location.reload();}catch(e){toast(e.message);}finally{b.disabled=false;}}));
  qa('[data-source-sync]').forEach(b=>b.addEventListener('click',async()=>{b.disabled=true;try{const x=await api(`/api/admin/sources/${b.dataset.sourceSync}/sync`,{method:'POST',body:JSON.stringify({limit:10})});toast(`Synced ${x.count||0} item(s)`);location.reload();}catch(e){toast(e.message);}finally{b.disabled=false;}}));
  q('[data-scheduler-tick]')?.addEventListener('click',async()=>{try{const x=await api('/api/admin/jobs/scheduler-tick',{method:'POST',body:'{}'});toast(`Queued ${x.length} job(s)`);location.reload();}catch(e){toast(e.message);}});
  q('[data-worker-run]')?.addEventListener('click',async()=>{try{const x=await api('/api/admin/jobs/run-next',{method:'POST',body:'{}'});toast(x?'Worker completed one cycle':'No queued job');location.reload();}catch(e){toast(e.message);}});
  q('[data-rebuild-search]')?.addEventListener('click',async()=>{try{const x=await api('/api/admin/search/rebuild',{method:'POST',body:'{}'});toast(`Indexed ${x.documents} stories`);}catch(e){toast(e.message);}});
  const dialog=q('[data-source-dialog]'); q('[data-open-source-form]')?.addEventListener('click',()=>dialog?.showModal());
  q('[data-create-source]')?.addEventListener('click',async e=>{e.preventDefault();const f=q('[data-source-form]');const data=Object.fromEntries(new FormData(f));data.sync_enabled=!!q('[name=sync_enabled]',f).checked;data.sync_interval=Number(data.sync_interval||300);data.settings={};if(data.site_url){data.settings.site_url=data.site_url;delete data.site_url;}if(data.quota_soft_limit){data.settings.quota_soft_limit=Number(data.quota_soft_limit);delete data.quota_soft_limit;}try{await api('/api/admin/sources',{method:'POST',body:JSON.stringify(data)});dialog.close();location.reload();}catch(err){toast(err.message);}});
  const credentialDialog=q('[data-credential-dialog]'); qa('[data-open-credential]').forEach(b=>b.addEventListener('click',()=>{q('[name=source_id]',credentialDialog).value=b.dataset.openCredential;credentialDialog?.showModal();})); q('[data-save-credential]')?.addEventListener('click',async e=>{e.preventDefault();const f=q('[data-credential-form]');const d=Object.fromEntries(new FormData(f));try{await api(`/api/admin/sources/${d.source_id}/credentials`,{method:'POST',body:JSON.stringify({credentials:{[d.credential_name]:d.credential_value}})});credentialDialog.close();toast('Credential encrypted and stored');location.reload();}catch(err){toast(err.message);}});
  qa('[data-resolve-error]').forEach(b=>b.addEventListener('click',async()=>{try{await api(`/api/admin/errors/${b.dataset.resolveError}/resolve`,{method:'POST',body:'{}'});location.reload();}catch(e){toast(e.message);}}));
  q('[data-refresh-health]')?.addEventListener('click',async()=>{try{await api('/api/admin/system/health');location.reload();}catch(e){toast(e.message);}});
  q('[data-create-utility-entity]')?.addEventListener('submit',async e=>{
    e.preventDefault();const f=e.currentTarget;const data=Object.fromEntries(new FormData(f));
    data.areas=String(data.areas||'').split(',').map(x=>x.trim()).filter(Boolean);
    try{await api('/api/admin/utility/entities',{method:'POST',body:JSON.stringify(data)});toast('Utility entity created');location.reload();}catch(err){toast(err.message);}
  });
  q('[data-create-utility-update]')?.addEventListener('submit',async e=>{
    e.preventDefault();const f=e.currentTarget;const data=Object.fromEntries(new FormData(f));
    try{const result=await api('/api/admin/utility/updates',{method:'POST',body:JSON.stringify(data)});toast(`Utility update published · ${result.follower_notifications||0} follower alert(s)`);location.reload();}catch(err){toast(err.message);}
  });
  qa('[data-resolve-utility-update]').forEach(b=>b.addEventListener('click',async()=>{
    b.disabled=true;try{await api(`/api/admin/utility/updates/${b.dataset.resolveUtilityUpdate}/resolve`,{method:'POST',body:'{}'});toast('Utility update resolved');location.reload();}catch(err){toast(err.message);}finally{b.disabled=false;}
  }));
  qa('[data-distribute-story]').forEach(b=>b.addEventListener('click',async()=>{
    b.disabled=true;
    try{
      const result=await api('/api/admin/distribution/alerts',{method:'POST',body:JSON.stringify({story_id:b.dataset.distributeStory,kind:b.dataset.distributionKind})});
      toast(`Sent ${result.sent||0} alert(s) to ${result.matched||0} eligible reader(s)`);
    }catch(e){toast(e.message);}finally{b.disabled=false;}
  }));
})();
