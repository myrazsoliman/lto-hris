document.addEventListener('DOMContentLoaded',function(){
  var btn = document.getElementById('toggleSidebar');
  var sidebar = document.getElementById('sidebar');
  if(btn && sidebar){
    btn.addEventListener('click',function(){
      sidebar.classList.toggle('collapsed');
    });
  }

  // Notifications and user menu
  function initNotifications(){
    var notifBtn = document.getElementById('notifBtn');
    var notifMenu = document.getElementById('notificationMenu');
    var notifDropdown = document.getElementById('notificationDropdown');
    var profileMenu = document.querySelector('.profile-menu');
    var notifCount = document.getElementById('notifCount');
    var notifList = document.getElementById('notificationList');
    var notifSummary = document.getElementById('notificationSummary');
    var markReadBtn = document.getElementById('markNotificationsRead');
    if(!notifBtn || !notifMenu || !notifDropdown || !notifList || !notifSummary) return;

    var open = false;
    var lastIds = '';

    function formatTime(value){
      var date = new Date(value.replace(' ', 'T'));
      if(Number.isNaN(date.getTime())) return '';
      return date.toLocaleString([], {
        month: 'short',
        day: '2-digit',
        hour: 'numeric',
        minute: '2-digit'
      });
    }

    function setOpen(next){
      open = !!next;
      notifBtn.setAttribute('aria-expanded', open ? 'true' : 'false');
      notifDropdown.hidden = !open;
    }

    function render(items, unreadCount){
      if(notifCount){
        notifCount.textContent = unreadCount > 99 ? '99+' : String(unreadCount);
        notifCount.classList.toggle('is-hidden', unreadCount <= 0);
      }

      notifSummary.textContent = unreadCount > 0
        ? 'You have ' + unreadCount + ' new notifications.'
        : 'No new notifications.';

      if(!items.length){
        notifList.innerHTML = '<div class="notification-empty">No notifications available.</div>';
        return;
      }

      notifList.innerHTML = items.map(function(item){
        var body = item.body ? '<div class="notification-item-text">' + escapeHtml(item.body) + '</div>' : '';
        var href = item.link ? item.link : '#';
        var unreadClass = item.is_read ? '' : ' is-unread';
        return '' +
          '<a class="notification-item' + unreadClass + '" href="' + escapeAttr(href) + '" data-notification-id="' + item.id + '">' +
            '<span class="notification-item-icon"><i class="' + escapeAttr(item.icon) + '" aria-hidden="true"></i></span>' +
            '<span class="notification-item-body">' +
              '<div class="notification-item-title">' + escapeHtml(item.title) + '</div>' +
              body +
              '<div class="notification-item-time">' + escapeHtml(formatTime(item.created_at)) + '</div>' +
            '</span>' +
          '</a>';
      }).join('');
    }

    function escapeHtml(value){
      return String(value)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#39;');
    }

    function escapeAttr(value){
      return escapeHtml(value);
    }

    function fetchNotifications(){
      fetch('notifications.php?action=list', { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
        .then(function(resp){ return resp.ok ? resp.json() : null; })
        .then(function(data){
          if(!data || !data.ok) return;
          render(data.items || [], data.unread_count || 0);
          var ids = (data.items || []).map(function(item){ return item.id; }).join(',');
          if(lastIds && ids !== lastIds && !open){
            notifBtn.classList.add('is-updated');
            window.setTimeout(function(){ notifBtn.classList.remove('is-updated'); }, 1600);
          }
          lastIds = ids;
        })
        .catch(function(){});
    }

    function markAllRead(){
      fetch('notifications.php?action=mark_read', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-Requested-With': 'XMLHttpRequest'
        },
        body: JSON.stringify({ ids: [] })
      }).then(function(){
        fetchNotifications();
      }).catch(function(){});
    }

    notifBtn.addEventListener('click', function(e){
      e.preventDefault();
      setOpen(!open);
      if(open){
        fetchNotifications();
      }
    });

    document.addEventListener('click', function(e){
      if(!notifMenu.contains(e.target)){
        setOpen(false);
      }
    });

    if(profileMenu){
      profileMenu.addEventListener('mouseenter', function(){
        setOpen(false);
      });
      profileMenu.addEventListener('focusin', function(){
        setOpen(false);
      });
    }

    notifList.addEventListener('click', function(e){
      var item = e.target.closest('[data-notification-id]');
      if(!item) return;
      markAllRead();
    });

    if(markReadBtn){
      markReadBtn.addEventListener('click', function(e){
        e.preventDefault();
        markAllRead();
      });
    }

    fetchNotifications();
    window.setInterval(fetchNotifications, 10000);
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

  // Topbar search (live results; do not filter sidebar navigation)
  function initHeaderSearch(){
    var input = document.getElementById('topbarSearch');
    if(!input) return;
    var form = input.closest('form');
    var dropdown = document.getElementById('topbarSearchDropdown');
    var results = document.getElementById('topbarSearchResults');
    if(!form || !dropdown || !results) return;

    var open = false;
    var lastQuery = '';
    var timer = null;

    function escapeHtml(value){
      return String(value)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#39;');
    }

    function formatTime(value){
      var date = new Date(String(value).replace(' ', 'T'));
      if(Number.isNaN(date.getTime())) return '';
      return date.toLocaleString([], {
        month: 'short',
        day: '2-digit',
        hour: 'numeric',
        minute: '2-digit'
      });
    }

    function setOpen(next){
      open = !!next;
      dropdown.hidden = !open;
      form.classList.toggle('is-open', open);
    }

    function render(data){
      var pages = (data && data.pages) ? data.pages : [];
      var notifs = (data && data.notifications) ? data.notifications : [];

      if(!pages.length && !notifs.length){
        results.innerHTML = '<div class="notification-empty">No results found.</div>';
        return;
      }

      var html = '';
      if(pages.length){
        html += '<div class="search-group">';
        html += '<div class="search-group-title">Pages</div>';
        pages.forEach(function(p){
          html += '' +
            '<a class="search-suggest" href="' + escapeHtml(p.href) + '">' +
              '<i class="fa-solid fa-arrow-right" aria-hidden="true"></i>' +
              '<span>' + escapeHtml(p.label) + '</span>' +
            '</a>';
        });
        html += '</div>';
      }
      if(notifs.length){
        html += '<div class="search-group">';
        html += '<div class="search-group-title">Notifications</div>';
        notifs.forEach(function(n){
          var body = n.body ? '<div class="search-suggest-sub">' + escapeHtml(n.body) + '</div>' : '';
          var href = n.link ? n.link : 'notification-center.php';
          html += '' +
            '<a class="search-suggest search-suggest--notif" href="' + escapeHtml(href) + '">' +
              '<span class="search-suggest-icon"><i class="' + escapeHtml(n.icon) + '" aria-hidden="true"></i></span>' +
              '<span class="search-suggest-body">' +
                '<div class="search-suggest-title">' + escapeHtml(n.title) + '</div>' +
                body +
                '<div class="search-suggest-meta">' + escapeHtml(formatTime(n.created_at)) + '</div>' +
              '</span>' +
            '</a>';
        });
        html += '</div>';
      }

      results.innerHTML = html;
    }

    function fetchResults(){
      var q = (input.value || '').trim();
      if(!q){
        results.innerHTML = '<div class="notification-empty">Type to search…</div>';
        setOpen(false);
        return;
      }
      if(q === lastQuery) return;
      lastQuery = q;

      fetch('search-api.php?q=' + encodeURIComponent(q), { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
        .then(function(resp){ return resp.ok ? resp.json() : null; })
        .then(function(data){
          if(!data || !data.ok) return;
          render(data);
          setOpen(true);
        })
        .catch(function(){});
    }

    function scheduleFetch(){
      if(timer) window.clearTimeout(timer);
      timer = window.setTimeout(fetchResults, 140);
    }

    input.addEventListener('input', function(){
      scheduleFetch();
    });

    input.addEventListener('focus', function(){
      if((input.value || '').trim()){
        fetchResults();
      }
    });

    input.addEventListener('keydown', function(e){
      if(e.key === 'Escape'){
        input.value = '';
        input.blur();
        lastQuery = '';
        results.innerHTML = '<div class="notification-empty">Type to search…</div>';
        setOpen(false);
      }
    });

    document.addEventListener('click', function(e){
      if(!form.contains(e.target)){
        setOpen(false);
      }
    });

  }

  // Password UI helpers (toggle, caps-lock, strength, confirm)
  function initPasswordUx(){
    // confirm before submitting sensitive forms
    document.querySelectorAll('form[data-confirm]').forEach(function(form){
      form.addEventListener('submit', function(e){
        var msg = form.getAttribute('data-confirm') || 'Continue?';
        if(!window.confirm(msg)){
          e.preventDefault();
        }
      });
    });

    // toggle show/hide
    document.querySelectorAll('[data-password-toggle]').forEach(function(btn){
      btn.addEventListener('click', function(){
        var targetId = btn.getAttribute('data-password-toggle');
        var input = document.getElementById(targetId);
        if(!input) return;
        var show = input.type === 'password';
        input.type = show ? 'text' : 'password';
        btn.setAttribute('aria-pressed', show ? 'true' : 'false');
        btn.textContent = show ? 'Hide' : 'Show';
        input.focus();
      });
    });

    // caps lock indicator
    document.querySelectorAll('input[type="password"][data-caps-indicator]').forEach(function(input){
      var indicatorId = input.getAttribute('data-caps-indicator');
      var indicator = indicatorId ? document.getElementById(indicatorId) : null;
      if(!indicator) return;

      function updateCaps(e){
        var on = false;
        try {
          on = !!(e && e.getModifierState && e.getModifierState('CapsLock'));
        } catch(err) {}
        indicator.hidden = !on;
      }

      input.addEventListener('keyup', updateCaps);
      input.addEventListener('keydown', updateCaps);
      input.addEventListener('blur', function(){ indicator.hidden = true; });
    });

    // strength meter
    var strengthInput = document.querySelector('input[data-strength-meter]');
    if(strengthInput){
      var meterId = strengthInput.getAttribute('data-strength-meter');
      var meter = meterId ? document.getElementById(meterId) : null;
      var fill = meter ? meter.querySelector('.strength-fill') : null;
      var label = meter ? meter.querySelector('.strength-label') : null;

      function scorePassword(pw){
        pw = pw || '';
        var length = pw.length;
        var hasLower = /[a-z]/.test(pw);
        var hasUpper = /[A-Z]/.test(pw);
        var hasNum = /\d/.test(pw);
        var hasSym = /[^a-zA-Z\d]/.test(pw);
        var categories = [hasLower, hasUpper, hasNum, hasSym].filter(Boolean).length;

        var score = 0;
        if(length >= 10) score++;
        if(length >= 14) score++;
        if(categories >= 2) score++;
        if(categories >= 3) score++;
        return Math.min(4, score);
      }

      function applyStrength(){
        if(!meter || !fill || !label) return;
        var s = scorePassword(strengthInput.value);
        meter.classList.remove('is-0','is-1','is-2','is-3','is-4');
        meter.classList.add('is-' + s);
        var pct = (s / 4) * 100;
        fill.style.width = pct + '%';
        var text = ['Very weak','Weak','Fair','Good','Strong'][s] || 'Weak';
        label.textContent = text;
      }

      strengthInput.addEventListener('input', applyStrength);
      applyStrength();
    }
  }

  function initLogoutConfirmModal(){
    var modal = document.getElementById('logoutConfirmModal');
    if(!modal) return;

    var noBtn = document.getElementById('logoutConfirmNo');
    var yesBtn = document.getElementById('logoutConfirmYes');
    var cancelTargets = modal.querySelectorAll('[data-logout-cancel]');
    var triggers = document.querySelectorAll('[data-logout-trigger="true"], a[href="logout.php"]');
    var lastFocus = null;

    function setOpen(next){
      var isOpen = !!next;
      modal.classList.toggle('is-open', isOpen);
      modal.setAttribute('aria-hidden', isOpen ? 'false' : 'true');
      document.body.classList.toggle('modal-open', isOpen);
      if(isOpen){
        if(noBtn){ noBtn.focus(); }
      } else if(lastFocus){
        lastFocus.focus();
      }
    }

    triggers.forEach(function(trigger){
      if(trigger.hasAttribute('data-logout-bound')) return;
      trigger.setAttribute('data-logout-bound', 'true');
      trigger.addEventListener('click', function(e){
        if(trigger.id === 'logoutConfirmYes') return;
        e.preventDefault();
        lastFocus = trigger;
        setOpen(true);
      });
    });

    cancelTargets.forEach(function(el){
      el.addEventListener('click', function(e){
        e.preventDefault();
        setOpen(false);
      });
    });

    document.addEventListener('keydown', function(e){
      if(e.key === 'Escape' && modal.classList.contains('is-open')){
        setOpen(false);
      }
    });

    if(yesBtn){
      yesBtn.addEventListener('click', function(){
        setOpen(false);
      });
    }
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

  initLogoutConfirmModal();
  initTabs();
  initExpandingCards();
  initTour();
  initCarousel();
  initBannerCarousel();
  initHeaderSearch();
  initPasswordUx();
  initNotifications();
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
