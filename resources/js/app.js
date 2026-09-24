import gsap from 'gsap';
window.gsap = gsap;
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
  const reducedMotion = window.matchMedia?.('(prefers-reduced-motion: reduce)')?.matches ?? false;
  const scrollBehavior = reducedMotion ? 'auto' : 'smooth';
  const analyticsTrack=(event,properties={})=>fetch(urlFor('/api/v1/analytics/events'),{
    method:'POST',
    headers:{'Accept':'application/json','Content-Type':'application/json','X-CSRF-Token':csrf()},
    body:JSON.stringify({event,properties}),
    keepalive:true
  }).catch(()=>{});
  analyticsTrack('page_view',{path:location.pathname,referrer:document.referrer||''});

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
          b.setAttribute('aria-label', data.bookmarked ? 'Remove bookmark' : 'Save story');
          const label = b.querySelector('span');
          if (label) label.textContent = data.bookmarked ? 'Saved' : 'Save';
        });
        analyticsTrack('bookmark',{story_id:save.dataset.bookmark,active:!!data.bookmarked});
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
          const label=b.querySelector('span');
          if(label) label.textContent=data.followed?'Following':(b.dataset.followLabel||'Follow');
        });
        analyticsTrack('follow',{story_id:follow.dataset.follow,active:!!data.followed});
        toast(data.followed ? 'You are following this story' : 'Story unfollowed');
      } catch(err) { toast(err.message); }
    }

    const channel = e.target.closest('[data-channel]');
    if (channel) {
      $$('.channels .chip').forEach(b=>b.classList.remove('active'));
      channel.classList.add('active');
      const q = channel.dataset.channel.replaceAll('-', ' ');
      $$('#feed .story').forEach((card) => {
        const haystack=(card.dataset.filter||'').toLowerCase();
        card.style.display = (q === 'for you' || q === 'pune' || haystack.includes(q)) ? '' : 'none';
      });
      toast(`${channel.textContent.trim()} feed`);
    }

    const share = e.target.closest('[data-share-url]');
    if (share) {
      e.preventDefault();
      const absolute = new URL(share.dataset.shareUrl || '/', location.origin).href;
      const title = share.dataset.shareTitle || document.title;
      analyticsTrack('share',{path:share.dataset.shareUrl||location.pathname});
      try {
        if (navigator.share) {
          await navigator.share({title, url:absolute});
        } else if (navigator.clipboard?.writeText) {
          await navigator.clipboard.writeText(absolute);
          toast('Link copied');
        } else {
          toast('Share unavailable');
        }
      } catch(err) {
        if (err?.name !== 'AbortError') toast('Could not share');
      }
      return;
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
    if (jump) { document.querySelector('#developingTimeline .live-card')?.scrollIntoView({behavior:scrollBehavior, block:'center'}); }
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
      analyticsTrack('search',{q});
      const box = $('#searchResults'); box.hidden=false; box.innerHTML='<div class="skeleton" style="height:90px;border-radius:14px"></div>';
      try {
        const rows = await api(`/api/v1/search?q=${encodeURIComponent(q)}`);
        box.innerHTML = `<div class="secttitle"><h2>Search results</h2><span class="link">${rows.length}</span></div>` + (rows.length ? rows.map(s => {
          const m=s.media?.[0]?.url || '/media/home_rain.jpg';
          const path=s.path || `/story/${s.id}`;
          return `<a class="saved-row" href="${urlFor(path)}"><img src="${esc(urlFor(m))}" alt=""><div><small>${esc(s.categories?.[0]?.name||'Pune')}</small><h3>${esc(s.headline)}</h3><small>⌖ ${esc(s.locations?.[0]?.name||'Pune')}</small></div></a>`;
        }).join('') : '<div class="empty">No matching stories in the local file index.</div>');
        box.scrollIntoView({behavior:scrollBehavior,block:'start'});
      } catch(err) { box.innerHTML=`<div class="empty">${esc(err.message)}</div>`; }
    });
  }

  const storyCards=$$('[data-story-id]');
  if('IntersectionObserver' in window && storyCards.length){
    const seen=new Set();
    const io=new IntersectionObserver(entries=>entries.forEach(entry=>{
      if(!entry.isIntersecting||entry.intersectionRatio<.6)return;
      const id=entry.target.dataset.storyId;if(!id||seen.has(id))return;
      seen.add(id);analyticsTrack('story_impression',{story_id:id,path:location.pathname});io.unobserve(entry.target);
    }),{threshold:[.6]});
    storyCards.forEach(card=>io.observe(card));
  }

  const prefForm=$('[data-preferences]');
  if(prefForm){
    prefForm.addEventListener('submit',async(e)=>{
      e.preventDefault();
      const areas=$('input[name="areas"]:checked',prefForm).map(x=>x.value);
      const channels=$('input[name="channels"]:checked',prefForm).map(x=>x.value);
      const status=$('[data-preference-status]',prefForm);
      if(status)status.textContent='Saving…';
      try{
        await api('/api/v1/me/preferences',{method:'PATCH',body:JSON.stringify({areas,channels})});
        if(status)status.textContent='Saved';
        toast('My Pune updated');
      }catch(err){
        if(status)status.textContent='Could not save';
        toast(err.message);
      }
    });
  }

  const storyId=$('meta[name="pm-story-id"]')?.content||'';
  if(storyId){
    analyticsTrack('story_open',{story_id:storyId,path:location.pathname});
    const sent=new Set();
    const onRead=()=>{
      const doc=document.documentElement;
      const max=Math.max(1,doc.scrollHeight-window.innerHeight);
      const pct=Math.min(100,Math.round((window.scrollY/max)*100));
      for(const threshold of [25,50,90]){
        if(pct>=threshold&&!sent.has(threshold)){
          sent.add(threshold);
          analyticsTrack(`story_read_${threshold}`,{story_id:storyId,path:location.pathname});
        }
      }
    };
    addEventListener('scroll',onRead,{passive:true});
    onRead();
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
      if(slides.length>1&&index===slides.length-1)analyticsTrack('gallery_complete',{slides:slides.length,path:location.pathname});
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
    if (window.gsap && !reducedMotion) window.gsap.from(article,{y:-16,opacity:0,duration:.35});
    if(announce) toast('New live update inserted');
  }
  if (live) {
    const liveId=live.dataset.liveId;
    setInterval(async()=>{
      if (document.hidden) return;
      try {
        const after=live.dataset.last||''; const rows=await api(`/api/v1/live/${liveId}/updates?after=${encodeURIComponent(after)}`);
        [...rows].reverse().forEach(r=>insertLive(r, true));
      } catch(_) {}
    },12000);
  }

  // Reels: native scroll snap; tap empty media area to toggle paused treatment.
  $$('.reel').forEach((reel,i)=>{
    if('IntersectionObserver' in window){
      const io=new IntersectionObserver(entries=>entries.forEach(entry=>{if(entry.isIntersecting&&entry.intersectionRatio>.7){analyticsTrack('reel_view',{index:i,path:location.pathname});io.disconnect();}}),{threshold:[.7]});
      io.observe(reel);
    }
    reel.addEventListener('click',e=>{
    if(e.target.closest('a,button')) return;
    reel.classList.toggle('paused'); reel.querySelector('img').style.filter=reel.classList.contains('paused')?'brightness(.48)':'brightness(.76)';
    toast(reel.classList.contains('paused')?'Paused':'Playing');
  });
  });
})();

// M6 notification read interaction
(()=>{document.querySelectorAll('[data-notification-id]').forEach(row=>row.addEventListener('click',async()=>{if(!row.classList.contains('unread'))return;try{await fetch(`${document.querySelector('meta[name="app-base-path"]')?.content||''}/api/v1/notifications/${row.dataset.notificationId}/read`,{method:'POST',headers:{'Content-Type':'application/json','X-CSRF-Token':document.querySelector('meta[name="csrf-token"]')?.content||''},body:'{}'});row.classList.remove('unread');}catch(_){}}));})();
