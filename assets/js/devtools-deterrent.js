(() => {
  const blockedKeys = new Set(['F12']);
  const blockedCombos = [
    { ctrl: true, shift: true, key: 'I' },
    { ctrl: true, shift: true, key: 'J' },
    { ctrl: true, shift: true, key: 'C' },
    { ctrl: true, key: 'U' },
    { ctrl: true, key: 'S' },
  ];

  const showWarning = () => {
    if (document.getElementById('devtools-deterrent-warning')) {
      return;
    }

    const warning = document.createElement('div');
    warning.id = 'devtools-deterrent-warning';
    warning.setAttribute('role', 'alert');
    warning.style.position = 'fixed';
    warning.style.left = '16px';
    warning.style.bottom = '16px';
    warning.style.zIndex = '99999';
    warning.style.padding = '12px 16px';
    warning.style.borderRadius = '12px';
    warning.style.background = 'rgba(17, 38, 68, 0.94)';
    warning.style.color = '#fff';
    warning.style.fontSize = '14px';
    warning.style.lineHeight = '1.4';
    warning.style.boxShadow = '0 12px 30px rgba(0, 0, 0, 0.22)';
    warning.textContent = 'Content protection is enabled on this page.';

    document.body.appendChild(warning);
    window.setTimeout(() => {
      warning.remove();
    }, 2200);
  };

  document.addEventListener('contextmenu', (event) => {
    if (event.target.closest('img, video, canvas, pre, code')) {
      event.preventDefault();
      showWarning();
    }
  });

  document.addEventListener('dragstart', (event) => {
    if (event.target.closest('img')) {
      event.preventDefault();
    }
  });

  document.addEventListener('copy', (event) => {
    if (event.target.closest('pre, code')) {
      event.preventDefault();
      showWarning();
    }
  });

  document.addEventListener('keydown', (event) => {
    const key = String(event.key || '').toUpperCase();
    const isBlockedCombo = blockedCombos.some((combo) =>
      Boolean(combo.ctrl) === event.ctrlKey &&
      Boolean(combo.shift) === event.shiftKey &&
      Boolean(combo.alt) === event.altKey &&
      combo.key === key
    );

    if (blockedKeys.has(key) || isBlockedCombo) {
      event.preventDefault();
      event.stopPropagation();
      showWarning();
    }
  }, true);
})();
