// music_player.js — ثابت عبر الصفحات + يحفظ/يستعيد نفس الأغنية والوقت بدقة
(function(){
  const ENDPOINT = 'music_playlist.php';
  const BTN_ID   = 'music-btn';
  const LS_KEY   = 'music_state_v1';

  let PLAYLIST = [];
  let order = [];
  let current = 0;       // موضع العنصر داخل order
  let shuffled = false;

  // عناصر عامة
  let audio, mini, mpTitle, mpBar, btnMiniPlay, btnMiniPrev, btnMiniNext, btnMiniOpen;

  // عناصر نافذة المشغّل
  let modal, backdrop, container, playlistEl, titleEl, curTimeEl, durEl, progressWrap, progressBar;
  let btnPlay, btnNext, btnBack, btnShuffle, chkAutoplay, volumeEl, btnClose;

  // ===== Helpers =====
  const qs = (s, r=document)=>r.querySelector(s);
  const ce = (t, c)=>{ const e=document.createElement(t); if(c) e.className=c; return e; };
  const fmt= s=>{ if(!isFinite(s)) return '0:00'; s=Math.max(0,Math.floor(s)); const m=Math.floor(s/60),r=s%60; return m+':'+String(r).padStart(2,'0'); };
  function shuffleArray(a){ for(let i=a.length-1;i>0;i--){ const j=Math.floor(Math.random()*(i+1)); [a[i],a[j]]=[a[j],a[i]]; } }
  function normalizeSrc(src){
    try { const u = new URL(src, window.location.href); return u.pathname.replace(/^\/+/, ''); }
    catch { return String(src||'').replace(/^\/+/, ''); }
  }

  // ===== حفظ/استرجاع الحالة =====
  function saveState(extra = {}){
    try{
      const realIndex = (order && typeof current === 'number') ? order[current] : 0;
      const curTrack  = PLAYLIST[realIndex] || {};
      const srcNow    = audio?.src || '';
      const state = {
        shuffled,
        order,
        current,                        // موضع داخل order (للمعلومة)
        trackFile: curTrack.file || '', // مرجع موثوق
        srcRel: normalizeSrc(srcNow),   // مسار نسبي موحّد
        time: audio?.currentTime || 0,
        volume: audio?.volume ?? 1,
        autoplay: chkAutoplay ? chkAutoplay.checked : true,
        playing: audio ? !audio.paused : false,
        ...extra
      };
      localStorage.setItem(LS_KEY, JSON.stringify(state));
    }catch{}
  }
  function loadState(){ try{ return JSON.parse(localStorage.getItem(LS_KEY)||'{}'); }catch{ return {}; } }

  // ===== القشرة الثابتة (زر/ميني بلير/عنصر Audio) =====
  function ensureShell(){
    // زر Music إن لم يوجد
    if (!document.getElementById(BTN_ID)) {
      const nav = document.querySelector('nav, .navbar, .dashboard-nav') || document.body;
      const a = ce('a'); a.id=BTN_ID; a.href='#'; a.innerHTML='<i class="fas fa-music"></i> <span>Music</span>';
      a.style.marginInlineStart='8px';
      nav.appendChild(a);
    }

    // عنصر الصوت العالمي
    audio = qs('#global-audio');
    if (!audio){ audio = ce('audio'); audio.id='global-audio'; document.body.appendChild(audio); }

    // الميني بلير
    mini = qs('#mini-player');
    if (!mini){
      mini = ce('div','mini-player'); mini.id='mini-player';
      mini.innerHTML = `
        <div class="mp-body">
          <button class="mp-btn" id="mp-prev" title="Previous"><i class="fas fa-backward"></i></button>
          <button class="mp-btn" id="mp-play" title="Play/Pause"><i class="fas fa-play"></i></button>
          <button class="mp-btn" id="mp-next" title="Next"><i class="fas fa-forward"></i></button>
          <div class="mp-title" id="mp-title">No track</div>
          <button class="mp-open" id="mp-open" title="Open player"><i class="fas fa-external-link-alt"></i></button>
        </div>
        <div class="mp-progress"><div class="mp-bar" id="mp-bar"></div></div>`;
      document.body.appendChild(mini);
    }

    // ربط أزرار الميني
    btnMiniPrev = qs('#mp-prev', mini);
    btnMiniPlay = qs('#mp-play', mini);
    btnMiniNext = qs('#mp-next', mini);
    btnMiniOpen = qs('#mp-open', mini);
    mpTitle     = qs('#mp-title', mini);
    mpBar       = qs('#mp-bar', mini);

    btnMiniPrev.onclick=()=>back();
    btnMiniNext.onclick=()=>next();
    btnMiniPlay.onclick=()=>togglePlay();
    btnMiniOpen.onclick=()=>openModal();

    // تقدّم الشريط + حفظ الزمن
    audio.addEventListener('timeupdate', ()=>{
      const p = (audio.currentTime/(audio.duration||1))*100;
      mpBar.style.width = p+'%';
      saveState();
    });
    // انتهاء التراك
    audio.addEventListener('ended', ()=>{ if (chkAutoplay?.checked ?? true) next(); saveState(); });

    // زر فتح المودال
    document.getElementById(BTN_ID).addEventListener('click', (e)=>{ e.preventDefault(); openModal(); });
  }

  // ===== إنشاء نافذة المشغّل =====
  function buildModal(){
    if (modal) return;
    modal = ce('div'); modal.id='music-modal'; modal.classList.add('hidden');
    backdrop = ce('div','music-backdrop');
    container= ce('div','music-container');

    // Header
    const header = ce('div','music-header');
    const h2 = ce('h2'); h2.id='music-title'; h2.innerHTML='<i class="fas fa-music"></i> Music Player';
    btnClose = ce('button','music-close'); btnClose.id='music-close'; btnClose.title='Close'; btnClose.innerHTML='<i class="fas fa-times"></i>';
    header.append(h2, btnClose);

    // Now playing
    const now = ce('div','music-nowplaying');
    const meta= ce('div','track-meta');
    titleEl = ce('div','track-title'); titleEl.id='track-title'; titleEl.textContent='No track';
    const timeWrap = ce('div','track-time'); timeWrap.innerHTML='<span id="current-time">0:00</span> / <span id="duration">0:00</span>';
    now.append(meta, ce('div','progress-wrap'));
    now.firstChild.append(titleEl, timeWrap);
    progressWrap = now.lastChild; progressWrap.id='progress-wrap';
    progressBar   = ce('div','progress-bar'); progressBar.id='progress-bar';
    progressWrap.append(progressBar);

    // Controls
    const ctrls = ce('div','music-controls');
    btnBack = ce('button'); btnBack.id='btn-back'; btnBack.title='Previous'; btnBack.innerHTML='<i class="fas fa-backward"></i>';
    btnPlay = ce('button'); btnPlay.id='btn-play'; btnPlay.title='Play/Pause'; btnPlay.innerHTML='<i class="fas fa-play"></i>';
    btnNext = ce('button'); btnNext.id='btn-next'; btnNext.title='Next'; btnNext.innerHTML='<i class="fas fa-forward"></i>';
    btnShuffle = ce('button'); btnShuffle.id='btn-shuffle'; btnShuffle.title='Shuffle (Off)'; btnShuffle.innerHTML='<i class="fas fa-random"></i>';

    const autoplay = ce('label','autoplay'); autoplay.innerHTML='<input type="checkbox" id="chk-autoplay" checked> Auto-play';
    chkAutoplay = ce('input'); chkAutoplay.type='checkbox'; chkAutoplay.id='chk-autoplay'; chkAutoplay.checked=true;
    autoplay.firstChild.replaceWith(chkAutoplay);

    const volume = ce('div','volume');
    volume.innerHTML='<i class="fas fa-volume-up"></i>';
    volumeEl = ce('input'); volumeEl.type='range'; volumeEl.id='volume'; volumeEl.min='0'; volumeEl.max='1'; volumeEl.step='0.01'; volumeEl.value='1';
    volume.append(volumeEl);

    ctrls.append(btnBack, btnPlay, btnNext, btnShuffle, autoplay, volume);

    // List
    const body = ce('div','music-body');
    playlistEl = ce('ul','playlist'); playlistEl.id='playlist';
    body.append(playlistEl);

    // Compose
    container.append(header, now, ctrls, body);
    modal.append(backdrop, container);
    document.body.appendChild(modal);

    // Bind
    curTimeEl = qs('#current-time', container);
    durEl     = qs('#duration', container);

    progressWrap.onclick=(e)=>{ const r=progressWrap.getBoundingClientRect(); const p=(e.clientX-r.left)/r.width; audio.currentTime=p*(audio.duration||0); };
    btnPlay.onclick   = ()=>togglePlay();
    btnNext.onclick   = ()=>next();
    btnBack.onclick   = ()=>back();
    btnShuffle.onclick= ()=>{ applyShuffle(!shuffled); buildList(); highlightActive(); saveState(); };
    chkAutoplay.onchange= ()=>saveState();
    volumeEl.oninput  = ()=>{ audio.volume=Number(volumeEl.value); saveState(); };

    audio.addEventListener('timeupdate', ()=>{
      curTimeEl.textContent=fmt(audio.currentTime);
      durEl.textContent=fmt(audio.duration);
      const p=(audio.currentTime/(audio.duration||1))*100;
      progressBar.style.width=p+'%';
    });

    btnClose.onclick = ()=>closeModal();
    backdrop.onclick = ()=>closeModal();
    document.addEventListener('keydown', (e)=>{
      if (modal.classList.contains('hidden')) return;
      if (e.key==='Escape') closeModal();
      if (e.key===' ') { e.preventDefault(); togglePlay(); }
      if (e.key==='ArrowRight') next();
      if (e.key==='ArrowLeft')  back();
    });
  }

  // ===== فتح/إغلاق =====
  function openModal(){
    buildModal();
    modal.classList.remove('hidden'); modal.setAttribute('aria-hidden','false');
    document.body.style.overflow='hidden';

    if (!PLAYLIST.length) {
      fetch(ENDPOINT).then(r=>r.json()).then(data=>{
        PLAYLIST = Array.isArray(data) ? data : [];
        if (!PLAYLIST.length){ alert('No audio files found in /music'); closeModal(); return; }
        order = [...Array(PLAYLIST.length).keys()];
        const s = loadState();
        if (Array.isArray(s.order) && s.order.length===PLAYLIST.length) order = s.order;
        buildList();

        // حدد التراك الحقيقي عند الفتح
        const realIndex = pickRealIndexFromState(s);
        current = Math.max(0, order.indexOf(realIndex));
        setTrack(realIndex, s.time||0, s.playing===true);
        if (typeof s.volume==='number') audio.volume=s.volume;
        if (typeof s.autoplay==='boolean') chkAutoplay.checked=s.autoplay;
        if (typeof s.shuffle==='boolean') applyShuffle(s.shuffle);
      }).catch(()=>{ alert('Failed to load playlist'); closeModal(); });
    } else {
      // القائمة محمّلة مسبقًا
      const s = loadState();
      const realIndex = pickRealIndexFromState(s);
      current = Math.max(0, order.indexOf(realIndex));
      buildList(); highlightActive();
    }
  }
  function closeModal(){
    // الصوت يستمر
    modal.classList.add('hidden'); modal.setAttribute('aria-hidden','true');
    document.body.style.overflow=''; saveState();
  }

  // ===== قائمة التشغيل =====
  function buildList(){
    playlistEl.innerHTML='';
    order.forEach((idx, ordPos)=>{
      const li = ce('li');
      li.dataset.ord = ordPos;
      li.innerHTML = `<span class="name">${PLAYLIST[idx].title || PLAYLIST[idx].file}</span><span class="badge">${PLAYLIST[idx].file}</span>`;
      li.onclick = ()=>{ current = ordPos; setTrack(order[current], 0, true); };
      playlistEl.appendChild(li);
    });
    highlightActive();
  }
  function highlightActive(){ if (!playlistEl) return; [...playlistEl.children].forEach((li,i)=> li.classList.toggle('active', i===current)); }

  // ===== اختيار/تشغيل =====
  function setTrack(realIndex, startAt=0, autoPlay=false){
    const track = PLAYLIST[realIndex];
    if (!track) return;

    audio.src = track.src;
    audio.currentTime = startAt || 0;

    // عناوين
    if (!mini) return;
    mpTitle.textContent = track.title || track.file;
    if (titleEl) titleEl.textContent = mpTitle.textContent;

    mini.style.display = 'block';

    if (autoPlay) play(); else updatePlayIcon();

    saveState({
      current,
      order,
      trackFile: track.file || '',
      srcRel: normalizeSrc(track.src)
    });
    highlightActive();
  }

  function updatePlayIcon(){
    const paused = audio.paused;
    if (btnPlay)     btnPlay.innerHTML    = paused?'<i class="fas fa-play"></i>':'<i class="fas fa-pause"></i>';
    if (btnMiniPlay) btnMiniPlay.innerHTML= paused?'<i class="fas fa-play"></i>':'<i class="fas fa-pause"></i>';
  }
  function play(){ audio.play().catch(()=>{}); updatePlayIcon(); }
  function pause(){ audio.pause(); updatePlayIcon(); }
  function togglePlay(){ audio.paused ? play() : pause(); }
  function next(){ if (!PLAYLIST.length) return; current = (current + 1) % order.length; setTrack(order[current], 0, true); }
  function back(){ if (!PLAYLIST.length) return; current = (current - 1 + order.length) % order.length; setTrack(order[current], 0, true); }
  function applyShuffle(on){
    shuffled = on;
    if (btnShuffle){ btnShuffle.classList.toggle('active', on); btnShuffle.title = on ? 'Shuffle (On)' : 'Shuffle (Off)'; }
    order = [...Array(PLAYLIST.length).keys()];
    if (on) shuffleArray(order);
  }

  // ===== اختيار التراك الحقيقي من الحالة المحفوظة =====
  function pickRealIndexFromState(s){
    if (!s || !PLAYLIST.length) return 0;

    // 1) المطابقة باسم الملف
    if (s.trackFile) {
      const idx = PLAYLIST.findIndex(t => t.file === s.trackFile);
      if (idx >= 0) return idx;
    }

    // 2) المطابقة بالمسار النسبي الموّحد
    if (s.srcRel) {
      const target = s.srcRel;
      const idx = PLAYLIST.findIndex(t => normalizeSrc(t.src) === target);
      if (idx >= 0) return idx;
    }

    // 3) فشل: أول عنصر
    return 0;
  }

  // ===== استئناف تلقائي عند فتح أي صفحة =====
  function tryResume(){
    const s = loadState();
    if (!s || (!s.trackFile && !s.srcRel)) return;

    fetch(ENDPOINT).then(r=>r.json()).then(data=>{
      PLAYLIST = Array.isArray(data) ? data : [];
      if (!PLAYLIST.length) return;

      order = [...Array(PLAYLIST.length).keys()];
      if (Array.isArray(s.order) && s.order.length === PLAYLIST.length) order = s.order;

      ensureShell();

      const realIndex = pickRealIndexFromState(s);
      current = Math.max(0, order.indexOf(realIndex));

      setTrack(realIndex, s.time || 0, s.playing === true);
      if (typeof s.volume === 'number')  audio.volume = s.volume;
      if (typeof s.shuffle === 'boolean') applyShuffle(s.shuffle);
    }).catch(()=>{});
  }

  // ===== تهيئة =====
  window.addEventListener('DOMContentLoaded', ()=>{
    // ضمّن الستايل تلقائياً إن نُسي
    if (!qs('link[href$="music_player.css"]')) {
      const l=ce('link'); l.rel='stylesheet'; l.href='music_player.css'; document.head.appendChild(l);
    }
    ensureShell();
    tryResume();
    ['play','pause'].forEach(ev => audio.addEventListener(ev, updatePlayIcon));
    window.addEventListener('beforeunload', ()=> saveState());
  });
})();
