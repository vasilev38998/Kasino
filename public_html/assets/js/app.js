const toast = document.getElementById('toast');

function showToast(message) {
    if (!toast) return;
    toast.textContent = message;
    toast.classList.add('show');
    setTimeout(() => toast.classList.remove('show'), 3000);
}

const langSwitch = document.querySelector('.lang-switch');
if (langSwitch) {
    const currentLang = langSwitch.dataset.lang;
    const storedLang = localStorage.getItem('lang');
    if (storedLang && storedLang !== currentLang) {
        fetch('/api/auth.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'lang', language: storedLang }),
        }).then(() => location.reload());
    }
    langSwitch.querySelectorAll('button').forEach((btn) => {
        if (btn.dataset.langBtn === currentLang) {
            btn.classList.add('active');
        }
        btn.addEventListener('click', () => {
            fetch('/api/auth.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ action: 'lang', language: btn.dataset.langBtn }),
            })
                .then((res) => res.json())
                .then((data) => {
                    if (data.message) {
                        showToast(data.message);
                    }
                    localStorage.setItem('lang', btn.dataset.langBtn);
                    setTimeout(() => location.reload(), 500);
                });
        });
    });
}

const balanceEl = document.querySelector('[data-balance]');
if (balanceEl) {
    fetch('/api/balance.php')
        .then((res) => res.json())
        .then((data) => {
            if (!data.balance) return;
            const start = 0;
            const end = Number(data.balance);
            const duration = 1000;
            const startTime = performance.now();
            function animate(time) {
                const progress = Math.min((time - startTime) / duration, 1);
                const value = Math.floor(start + (end - start) * progress);
                balanceEl.textContent = `${value.toFixed(0)}₽`;
                if (progress < 1) requestAnimationFrame(animate);
            }
            requestAnimationFrame(animate);
        });
}

const slotPanel = document.querySelector('[data-slot-game]');
if (slotPanel) {
    const spinBtn = slotPanel.querySelector('.slot-spin');
    const betInput = slotPanel.querySelector('.slot-bet');
    const resultEl = document.querySelector('.slot-result');
    spinBtn?.addEventListener('click', () => {
        const bet = Number(betInput?.value || 0);
        fetch('/api/game.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ game: slotPanel.dataset.slotGame, bet }),
        })
            .then((res) => res.json())
            .then((data) => {
                if (data.error) {
                    resultEl.textContent = data.error;
                    return;
                }
                resultEl.textContent = `Выигрыш: ${data.win}₽ | Комбо: ${data.combo}`;
            })
            .catch(() => {
                resultEl.textContent = 'Сервис временно недоступен.';
            });
    });
}

document.querySelectorAll('.social-bind').forEach((form) => {
    form.addEventListener('submit', (event) => {
        event.preventDefault();
        const provider = form.dataset.provider;
        const input = form.querySelector('input[name="provider_id"]');
        if (!input?.value) return;
        fetch('/api/auth.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'social_bind', provider, provider_id: input.value }),
        }).then(() => location.reload());
    });
});

if ('serviceWorker' in navigator) {
    window.addEventListener('load', () => {
        navigator.serviceWorker.register('/sw.js');
    });
}

const installBtn = document.querySelector('[data-install]');
let deferredPrompt;
window.addEventListener('beforeinstallprompt', (event) => {
    event.preventDefault();
    deferredPrompt = event;
    if (installBtn) {
        installBtn.hidden = false;
        installBtn.addEventListener('click', () => {
            deferredPrompt.prompt();
        });
    }
});

const liveFeed = document.querySelector('[data-live-feed]');
if (liveFeed) {
    fetch('/api/notifications.php?type=live')
        .then((res) => res.json())
        .then((data) => {
            if (!Array.isArray(data.items)) return;
            liveFeed.innerHTML = '';
            data.items.forEach((item) => {
                const row = document.createElement('div');
                row.className = 'card';
                row.textContent = `${item.player} • ${item.slot} • ${item.amount}₽`;
                liveFeed.appendChild(row);
            });
        });
}

if (document.querySelector('[data-logout]')) {
    const clear = [];
    if ('caches' in window) {
        clear.push(caches.keys().then((keys) => Promise.all(keys.map((key) => caches.delete(key)))));
    }
    if ('serviceWorker' in navigator) {
        clear.push(navigator.serviceWorker.getRegistrations().then((regs) => regs.forEach((reg) => reg.unregister())));
    }
    Promise.all(clear).finally(() => {
        window.location.href = '/index.php';
    });
}
