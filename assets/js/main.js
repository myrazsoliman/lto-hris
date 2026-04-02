document.addEventListener('DOMContentLoaded',function(){
  var btn = document.getElementById('toggleSidebar');
  var sidebar = document.getElementById('sidebar');
  if(btn && sidebar){
    btn.addEventListener('click',function(){
      sidebar.classList.toggle('collapsed');
    });
  }

  // Notifications and user menu
  var notifBtn = document.getElementById('notifBtn');
  var userBtn = document.getElementById('userBtn');
  var userDropdown = document.getElementById('userDropdown');
  if(userBtn && userDropdown){
    userBtn.addEventListener('click', function(e){
      var expanded = userBtn.getAttribute('aria-expanded') === 'true';
      userBtn.setAttribute('aria-expanded', !expanded);
      userDropdown.hidden = expanded;
    });
    // close on outside click
    document.addEventListener('click', function(e){
      if(!userBtn.contains(e.target) && !userDropdown.contains(e.target)){
        userDropdown.hidden = true;
        userBtn.setAttribute('aria-expanded', 'false');
      }
    });
  }
  if(notifBtn){
    notifBtn.addEventListener('click', function(){
      // simple placeholder: clear badge
      var b = document.getElementById('notifCount'); if(b){ b.style.display='none'; }
      notifBtn.setAttribute('aria-expanded', 'false');
    });
  }

  // Spotlight effect for cards
  function attachSpotlight(selector){
    document.querySelectorAll(selector).forEach(function(el){
      el.addEventListener('mousemove', function(e){
        var rect = el.getBoundingClientRect();
        var x = ((e.clientX - rect.left) / rect.width) * 100 + '%';
        var y = ((e.clientY - rect.top) / rect.height) * 100 + '%';
        el.style.setProperty('--x', x);
        el.style.setProperty('--y', y);
        el.classList.add('is-hover');
      });
      el.addEventListener('mouseleave', function(){
        el.classList.remove('is-hover');
      });
      // touch fallback: show on touchstart
      el.addEventListener('touchstart', function(ev){
        var touch = ev.touches[0];
        var rect = el.getBoundingClientRect();
        var x = ((touch.clientX - rect.left) / rect.width) * 100 + '%';
        var y = ((touch.clientY - rect.top) / rect.height) * 100 + '%';
        el.style.setProperty('--x', x);
        el.style.setProperty('--y', y);
        el.classList.add('is-hover');
      });
    });
  }

  attachSpotlight('.spotlight-card');
  attachSpotlight('.request-item.focus-hover');

  // Topbar search (filters sidebar navigation)
  function initHeaderSearch(){
    var input = document.getElementById('topbarSearch');
    if(!input) return;

    var form = input.closest('form');
    if(form){
      form.addEventListener('submit', function(e){ e.preventDefault(); });
    }

    var links = Array.from(document.querySelectorAll('.nav-link'));
    if(!links.length) return;

    var getLabel = function(link){
      var span = link.querySelector('span');
      return (span ? span.textContent : link.textContent || '').toLowerCase().trim();
    };

    function apply(){
      var q = (input.value || '').toLowerCase().trim();
      links.forEach(function(link){
        if(!q){
          link.classList.remove('is-hidden');
          return;
        }
        var match = getLabel(link).indexOf(q) !== -1;
        var keep = match || link.classList.contains('active');
        link.classList.toggle('is-hidden', !keep);
      });
    }

    input.addEventListener('input', apply);
    input.addEventListener('keydown', function(e){
      if(e.key === 'Escape'){
        input.value = '';
        apply();
        input.blur();
      }
    });

    apply();
  }

  // Tabs switcher
  function initTabs(){
    var buttons = document.querySelectorAll('.tab-button');
    buttons.forEach(function(btn){
      btn.addEventListener('click', function(){
        var target = btn.getAttribute('data-tab');
        document.querySelectorAll('.tab-button').forEach(b=>b.classList.remove('active'));
        btn.classList.add('active');
        document.querySelectorAll('.tab-panel').forEach(function(panel){
          panel.style.display = (panel.id === 'tab-' + target) ? '' : 'none';
        });
      });
    });
  }

  // Expanding cards
  function initExpandingCards(){
    var AUTO_CLOSE_MS = 8000; // milliseconds

    function clearAutoClose(targetCard){
      if(targetCard._autoClose){ clearTimeout(targetCard._autoClose); targetCard._autoClose = null; }
    }

    function collapseCard(targetCard){
      clearAutoClose(targetCard);
      var b = targetCard.querySelector('.card-body');
      if(!b) { targetCard.classList.remove('open'); return; }
      // set explicit height then collapse to 0
      b.style.maxHeight = b.scrollHeight + 'px';
      requestAnimationFrame(function(){ b.style.maxHeight = '0px'; });
      targetCard.classList.remove('open');
      b.addEventListener('transitionend', function te(){ b.style.maxHeight = ''; b.removeEventListener('transitionend', te); }, { once: true });
    }

    function setAutoClose(targetCard){
      clearAutoClose(targetCard);
      targetCard._autoClose = setTimeout(function(){
        // ensure card still exists and is open
        if(document.body.contains(targetCard) && targetCard.classList.contains('open')){
          collapseCard(targetCard);
        }
      }, AUTO_CLOSE_MS);
    }

    document.querySelectorAll('.expanding-card').forEach(function(card){
      card.addEventListener('click', function(e){
        var body = card.querySelector('.card-body');
        if(!body) { card.classList.toggle('open'); return; }
        var isOpen = card.classList.contains('open');
        if(isOpen){
          collapseCard(card);
        } else {
          // collapse any other open cards first
          document.querySelectorAll('.expanding-card.open').forEach(function(other){ if(other !== card){ collapseCard(other); } });
          // expand this card
          card.classList.add('open');
          var sh = body.scrollHeight;
          body.style.maxHeight = '0px';
          requestAnimationFrame(function(){ body.style.maxHeight = sh + 'px'; });
          body.addEventListener('transitionend', function te(){ body.style.maxHeight = ''; body.removeEventListener('transitionend', te); }, { once: true });
          // start auto-close timer
          setAutoClose(card);
        }
      });
      card.addEventListener('keydown', function(e){
        if(e.key === 'Enter' || e.key === ' ') { e.preventDefault(); card.click(); }
      });
      // if user hovers or focuses, keep it open by resetting timer
      card.addEventListener('mouseenter', function(){ if(card.classList.contains('open')) setAutoClose(card); });
      card.addEventListener('focusin', function(){ if(card.classList.contains('open')) setAutoClose(card); });
      card.addEventListener('mouseleave', function(){ /* allow auto-close to continue */ });
    });
  }

  // Simple guided tour
  function initTour(){
    var steps = [
      { el: '.topbar h2', title: 'Dashboard', text: 'This is the LTO HRIS dashboard where key metrics are summarized.' },
      { el: '.vision-mission', title: 'Vision & Mission', text: 'Quick view of the agency vision and mission placed here for context.' },
      { el: '.stats-grid', title: 'Statistics', text: 'Overview cards show counts and KPIs at a glance.' },
      { el: '.tab-button[data-tab="employees"]', title: 'Employees', text: 'Open the Employees tab to see the staff directory as expandable cards.' }
    ];

    var overlay = null;
    var current = 0;

    function showStep(i){
      current = i;
      var s = steps[i];
      var target = document.querySelector(s.el);
      document.querySelectorAll('.tour-highlight').forEach(el=>el.classList.remove('tour-highlight'));
      if(target){
        target.classList.add('tour-highlight');
        target.scrollIntoView({behavior:'smooth', block:'center'});
      }
      var content = overlay.querySelector('.tour-step');
      content.innerHTML = '<h4>'+s.title+'</h4><p style="margin-top:8px">'+s.text+'</p>';
      var controls = document.createElement('div'); controls.className='tour-controls';
      var prev = document.createElement('button'); prev.className='btn btn--ghost'; prev.textContent='Prev';
      var next = document.createElement('button'); next.className='btn btn--primary'; next.textContent = (i === steps.length-1) ? 'Finish' : 'Next';
      var close = document.createElement('button'); close.className='btn btn--ghost'; close.textContent='Close';
      prev.disabled = (i===0);
      prev.addEventListener('click', function(){ showStep(Math.max(0, current-1)); });
      next.addEventListener('click', function(){ if(current === steps.length-1){ stopTour(); } else { showStep(current+1); }});
      close.addEventListener('click', stopTour);
      controls.appendChild(prev); controls.appendChild(next); controls.appendChild(close);
      content.appendChild(controls);
    }

    function startTour(){
      if(overlay) return;
      overlay = document.createElement('div'); overlay.className='tour-overlay';
      var stepBox = document.createElement('div'); stepBox.className = 'tour-step';
      overlay.appendChild(stepBox);
      document.body.appendChild(overlay);
      showStep(0);
    }

    function stopTour(){
      if(!overlay) return;
      document.body.removeChild(overlay);
      overlay = null;
      document.querySelectorAll('.tour-highlight').forEach(el=>el.classList.remove('tour-highlight'));
    }

    var tourBtn = document.getElementById('startTourBtn');
    if(tourBtn) tourBtn.addEventListener('click', function(){ startTour(); });
  }

  initTabs();
  initExpandingCards();
  initTour();
  initCarousel();
  initBannerCarousel();
  initHeaderSearch();
});

// Simple carousel initializer for announcements
function initCarousel(){
  var carousel = document.getElementById('annCarousel');
  if(!carousel) return;
  var track = carousel.querySelector('.carousel-track');
  var slides = Array.from(carousel.querySelectorAll('.carousel-slide'));
  var dots = Array.from(carousel.querySelectorAll('.dots li'));
  var idx = 0;
  var interval = 4000;
  var timer = null;

  function goTo(i){
    idx = (i + slides.length) % slides.length;
    track.style.transform = 'translateX(-' + (idx * 100) + '%)';
    dots.forEach(function(d){ d.classList.remove('on'); });
    if(dots[idx]) dots[idx].classList.add('on');
  }

  function start(){ timer = setInterval(function(){ goTo(idx+1); }, interval); }
  function stop(){ if(timer){ clearInterval(timer); timer = null; } }

  // dot clicks
  dots.forEach(function(d){ d.addEventListener('click', function(){ goTo(parseInt(d.getAttribute('data-index'))); stop(); start(); }); });

  // pause on hover
  carousel.addEventListener('mouseenter', stop);
  carousel.addEventListener('mouseleave', start);

  // init
  goTo(0);
  start();
}

// Banner image carousel (full-width banners with counter and pause)
function initBannerCarousel(){
  var el = document.getElementById('bannerCarousel');
  if(!el) return;
  var track = el.querySelector('.banner-track');
  var slides = Array.from(el.querySelectorAll('.banner-slide'));
  var dots = Array.from(el.querySelectorAll('.banner-dots li'));
  var curSpan = el.querySelector('.slide-counter .current');
  var totalSpan = el.querySelector('.slide-counter .total');
  var pauseBtn = el.querySelector('.banner-pause');
  var idx = 0; var timer = null; var interval = 4000; var paused = false;

  totalSpan.textContent = slides.length;

  function update(){
    track.style.transform = 'translateX(-' + (idx * 100) + '%)';
    if(curSpan) curSpan.textContent = (idx + 1);
    dots.forEach(d=>d.classList.remove('on'));
    if(dots[idx]) dots[idx].classList.add('on');
  }

  function start(){ if(timer) clearInterval(timer); timer = setInterval(function(){ idx = (idx+1) % slides.length; update(); }, interval); }
  function stop(){ if(timer){ clearInterval(timer); timer = null; } }

  // dot click
  dots.forEach(function(d){ d.addEventListener('click', function(){ idx = parseInt(d.getAttribute('data-index')) || 0; update(); stop(); start(); }); });

  // pause button toggle
  if(pauseBtn){ pauseBtn.addEventListener('click', function(){ paused = !paused; if(paused){ stop(); pauseBtn.textContent = '\u25BA'; } else { start(); pauseBtn.textContent = '||'; } }); }

  // pause on hover
  el.addEventListener('mouseenter', function(){ stop(); });
  el.addEventListener('mouseleave', function(){ if(!paused) start(); });

  update(); start();
}
