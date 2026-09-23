import Alpine from 'alpinejs';
import gsap from 'gsap';
window.Alpine = Alpine;
window.gsap = gsap;
Alpine.start();
(() => {
  const $ = (s, r=document) => r.querySelector(s);
  const $$ = (s, r=document) => [...r.querySelectorAll(s)];
  const toastEl = $('#toast');
  const toast = (msg) => {
    if (!toastEl) return;
    toastEl.textContent = msg;
    toastEl.classList.add('show');
    clearTimeout(window.__pmToast);
    window.__pmToast = setTimeout(() => toastEl.classList.remove('show'), 1700);
  };
  const csrf = () => document.querySelector('meta[name="csrf-token"]')?.content || '';
  const base = () => document.querySelector('meta[name="app-base-path"]')?.content || '';
  const urlFor = (path='') => /^https?:\/\//i.test(path) ? path : `${base()}${path.startsWith('/')?path:`/${path}`}`;
  const api = async (url, options={}) => {
    const headers={'Accept':'application/json','Content-Type':'application/json',...(options.headers||{})};
    if ((options.method||'GET').toUpperCase() !== 'GET') headers['X-CSRF-Token']=csrf();
    const res = await fetch(urlFor(url), {...options, headers});
    const json = await res.json().catch(()=>({data:null,errors:[{message:'Invalid response'}]}));
    if (!res.ok) throw new Error(json.errors?.[0]?.message || `Request failed (${res.status})`);
    return json.data;
  };
  const esc = (v='') => String(v).replace(/[&<>'"]/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;',"'":'&#39;','"':'&quot;'}[c]));

  document.addEventListener('click', async (e) => {
    const back = e.target.closest('[data-back]');
    if (back) { e.preventDefault(); history.back(); return; }

    const t = e.target.closest('[data-toast]');
    if (t) { e.preventDefault(); toast(t.dataset.toast || 'Done'); }

    const save = e.target.closest('[data-bookmark]');
    if (save) {
      e.preventDefault();
      try {
        const data = await api(`/api/v1/stories/${save.dataset.bookmark}/bookmark`, {method:'POST', body:'{}'});
        $$(`[data-bookmark="${save.dataset.bookmark}"]`).forEach(b => {
          b.classList.toggle('on', data.bookmarked);
          if (b.textContent.includes('Save')) b.innerHTML = `${data.bookmarked?'★':'☆'} Save`;
          else b.textContent = data.bookmarked ? '★' : '☆';
        });
        toast(data.bookmarked ? 'Saved to My Pune' : 'Removed from saved');
      } catch(err) { toast(err.message); }
    }

    const follow = e.target.closest('[data-follow]');
    if (follow) {
      e.preventDefault();
      try {
        const data = await api(`/api/v1/stories/${follow.dataset.follow}/follow`, {method:'POST', body:'{}'});
        $$(`[data-follow="${follow.dataset.follow}"]`).forEach(b => {
          b.classList.toggle('on', data.followed);
          b.textContent = data.followed ? 'Following' : (b.textContent.toLowerCase().includes('live') ? 'Follow live' : 'Follow story');
        });
        toast(data.followed ? 'You are following this story' : 'Story unfollowed');
      } catch(err) { toast(err.message); }
    }

    const channel = e.target.closest('[data-channel]');
    if (channel) {
      $$('.channels .chip').forEach(b=>b.classList.remove('active'));
      channel.classList.add('active');
      const q = channel.dataset.channel.replace('-', ' ');
      $$('#feed .story').forEach((card, i) => {
        card.style.display = (q === 'for you' || q === 'pune' || card.innerText.toLowerCase().includes(q) || i < 2) ? '' : 'none';
      });
      toast(`${channel.textContent.trim()} feed`);
    }

    const topic = e.target.closest('[data-topic]');
    if (topic) {
      e.preventDefault();
      const q = topic.dataset.topic;
      location.href = urlFor(`/explore?q=${encodeURIComponent(q)}`);
    }

    const area = e.target.closest('[data-area]');
    if (area) {
      e.preventDefault();
      location.href = urlFor(`/explore?q=${encodeURIComponent(area.dataset.area)}`);
    }

    const demo = e.target.closest('[data-live-demo]');
    if (demo) {
      e.preventDefault();
      try {
        const row = await api(`/api/v1/live/${demo.dataset.liveDemo}/demo-update`, {method:'POST', body:'{}'});
        insertLive(row, true);
      } catch(err) { toast(err.message); }
    }

    const jump = e.target.closest('[data-jump-latest]');
    if (jump) { document.querySelector('#developingTimeline .live-card')?.scrollIntoView({behavior:'smooth', block:'center'}); }
  });

  // Explore search: live API results without a full page refresh.
  const searchForm = $('[data-search-form]');
  if (searchForm) {
    const input = searchForm.querySelector('input[name=q]');
    const params = new URLSearchParams(location.search);
    if (params.get('q')) { input.value=params.get('q'); setTimeout(()=>searchForm.requestSubmit(),50); }
    searchForm.addEventListener('submit', async (e) => {
      e.preventDefault();
      const q = input.value.trim(); if (!q) return;
      const box = $('#searchResults'); box.hidden=false; box.innerHTML='<div class="skeleton" style="height:90px;border-radius:14px"></div>';
      try {
        const rows = await api(`/api/v1/search?q=${encodeURIComponent(q)}`);
        box.innerHTML = `<div class="secttitle"><h2>Search results</h2><span class="link">${rows.length}</span></div>` + (rows.length ? rows.map(s => {
          const m=s.media?.[0]?.url || '/media/home_rain.jpg';
          const path=s.type==='gallery'?`/gallery/${s.id}`:s.type==='developing'?`/developing/${s.id}`:s.type==='live'?`/live/${s.id}`:`/story/${s.id}`;
          return `<a class="saved-row" href="${urlFor(path)}"><img src="${esc(urlFor(m))}" alt=""><div><small>${esc(s.categories?.[0]?.name||'Pune')}</small><h3>${esc(s.headline)}</h3><small>⌖ ${esc(s.locations?.[0]?.name||'Pune')}</small></div></a>`;
        }).join('') : '<div class="empty">No matching stories in the local file index.</div>');
        box.scrollIntoView({behavior:'smooth',block:'start'});
      } catch(err) { box.innerHTML=`<div class="empty">${esc(err.message)}</div>`; }
    });
  }

  // Gallery horizontal swipe / drag.
  const gallery = $('[data-gallery]');
  if (gallery) {
    const track = $('.gallerytrack', gallery); const slides = $$('.gslide', gallery); const dots = $$('.gdot', gallery); const counter=$('[data-gallery-index]',gallery);
    let index=0, startX=0, delta=0, dragging=false;
    const set = (n, animate=true) => {
      index=Math.max(0,Math.min(slides.length-1,n));
      track.style.transition=animate?'transform .34s cubic-bezier(.2,.8,.2,1)':'none';
      track.style.transform=`translateX(${-index*100}%)`;
      dots.forEach((d,i)=>d.classList.toggle('active',i===index)); if(counter) counter.textContent=index+1;
    };
    gallery.addEventListener('pointerdown',e=>{dragging=true;startX=e.clientX;delta=0;gallery.setPointerCapture?.(e.pointerId);track.style.transition='none';});
    gallery.addEventListener('pointermove',e=>{if(!dragging)return;delta=e.clientX-startX;track.style.transform=`translateX(calc(${-index*100}% + ${delta}px))`;});
    const end=()=>{if(!dragging)return;dragging=false;if(Math.abs(delta)>45)set(index+(delta<0?1:-1));else set(index);};
    gallery.addEventListener('pointerup',end); gallery.addEventListener('pointercancel',end);
  }

  // Live updates: incremental polling fallback; SSE can be enabled server-side later.
  const live = $('#liveTimeline');
  function insertLive(row, announce=false) {
    if (!live || !row) return;
    if (live.querySelector(`[data-update-id="${row.id}"]`)) return;
    const article=document.createElement('article'); article.className='live-card'; article.dataset.updateId=row.id;
    const d=new Date(row.published_at); const time=Number.isNaN(d.getTime())?'NOW':d.toLocaleTimeString([], {hour:'2-digit',minute:'2-digit'});
    article.innerHTML=`<div class="live-time">${esc(time)} · ${esc(row.type||'Update')}</div><h3>${esc(row.headline||'Update')}</h3><p>${esc(row.body||'')}</p>`;
    live.prepend(article); live.dataset.last=row.published_at || live.dataset.last;
    if (window.gsap) window.gsap.from(article,{y:-16,opacity:0,duration:.35});
    if(announce) toast('New live update inserted');
  }
  if (live) {
    const liveId=live.dataset.liveId;
    setInterval(async()=>{
      try {
        const after=live.dataset.last||''; const rows=await api(`/api/v1/live/${liveId}/updates?after=${encodeURIComponent(after)}`);
        [...rows].reverse().forEach(r=>insertLive(r, true));
      } catch(_) {}
    },8000);
  }

  // Reels: native scroll snap; tap empty media area to toggle paused treatment.
  $$('.reel').forEach(reel=>reel.addEventListener('click',e=>{
    if(e.target.closest('a,button')) return;
    reel.classList.toggle('paused'); reel.querySelector('img').style.filter=reel.classList.contains('paused')?'brightness(.48)':'brightness(.76)';
    toast(reel.classList.contains('paused')?'Paused':'Playing');
  }));
})();

// M6 notification read interaction
(()=>{document.querySelectorAll('[data-notification-id]').forEach(row=>row.addEventListener('click',async()=>{if(!row.classList.contains('unread'))return;try{await fetch(`${document.querySelector('meta[name="app-base-path"]')?.content||''}/api/v1/notifications/${row.dataset.notificationId}/read`,{method:'POST',headers:{'Content-Type':'application/json','X-CSRF-Token':document.querySelector('meta[name="csrf-token"]')?.content||''},body:'{}'});row.classList.remove('unread');}catch(_){}}));})();
