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
    const syncKey = 'langSync';
    if (storedLang && storedLang !== currentLang) {
        const syncing = sessionStorage.getItem(syncKey);
        if (syncing !== storedLang) {
            sessionStorage.setItem(syncKey, storedLang);
            fetch('/api/auth.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ action: 'lang', language: storedLang }),
            })
                .then((res) => res.json())
                .then((data) => {
                    if (data.language) {
                        localStorage.setItem('lang', data.language);
                    }
                    location.reload();
                });
        }
    } else {
        sessionStorage.removeItem(syncKey);
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
                    sessionStorage.removeItem(syncKey);
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
    const spinBtn = document.querySelector('.slot-spin');
    const autoBtn = document.querySelector('.slot-auto');
    const betInput = document.querySelector('.slot-bet');
    const resultText = document.querySelector('.slot-result-text');
    const canvas = document.querySelector('.slot-reels');
    const statusEl = document.querySelector('.slot-status');
    const winEl = document.querySelector('.slot-win');
    const theme = document.querySelector('[data-slot-theme]')?.dataset.slotTheme || 'aurora';
    const ctx = canvas?.getContext('2d');
    let spinning = false;
    let autoSpins = 0;

    const themes = {
        aurora: { bg: '#121225', accent: '#00f0ff', symbols: ['A', 'K', 'Q', 'J', '10', '9', '★', '✦'] },
        cosmic: { bg: '#0f0f20', accent: '#6ad3ff', symbols: ['A', 'K', 'Q', 'J', '10', '9', '✶', '✹'] },
        dragon: { bg: '#1a1220', accent: '#f5c542', symbols: ['A', 'K', 'Q', 'J', '10', '9', '🐉', '🔥'] },
        sky: { bg: '#101733', accent: '#6ad3ff', symbols: ['A', 'K', 'Q', 'J', '10', '9', '⚡', '☁'] },
        sugar: { bg: '#1b0f24', accent: '#ff7bd9', symbols: ['🍬', '🍭', '🍫', '🍒', '🍋', '🍇', '⭐', '💎'] },
    };

    const drawGrid = (grid, offsets = []) => {
        if (!ctx || !canvas) return;
        const cols = grid.length;
        const rows = grid[0]?.length || 0;
        const cellW = canvas.width / cols;
        const cellH = canvas.height / rows;
        const palette = themes[theme] || themes.aurora;
        const useOffsets = offsets.length ? offsets : Array(cols).fill(0);
        ctx.clearRect(0, 0, canvas.width, canvas.height);
        ctx.fillStyle = palette.bg;
        ctx.fillRect(0, 0, canvas.width, canvas.height);
        grid.forEach((col, x) => {
            col.forEach((symbol, y) => {
                const yPos = y * cellH + useOffsets[x];
                ctx.fillStyle = 'rgba(255,255,255,0.06)';
                ctx.fillRect(x * cellW + 8, yPos + 8, cellW - 16, cellH - 16);
                ctx.strokeStyle = palette.accent;
                ctx.lineWidth = 2;
                ctx.strokeRect(x * cellW + 8, yPos + 8, cellW - 16, cellH - 16);
                ctx.fillStyle = '#ffffff';
                ctx.font = '24px sans-serif';
                ctx.textAlign = 'center';
                ctx.textBaseline = 'middle';
                ctx.fillText(symbol, x * cellW + cellW / 2, yPos + cellH / 2);
            });
        });
    };

    const idleGrid = Array.from({ length: 6 }, () =>
        Array.from({ length: 5 }, () => themes[theme]?.symbols?.[0] || 'A')
    );
    drawGrid(idleGrid);

    const randomSymbol = () => {
        const symbols = themes[theme]?.symbols || themes.aurora.symbols;
        return symbols[Math.floor(Math.random() * symbols.length)];
    };

    const randomGrid = () =>
        Array.from({ length: 6 }, () => Array.from({ length: 5 }, randomSymbol));

    const animateSpin = (finalGrid, onComplete) => {
        if (!ctx || !canvas) return;
        const start = performance.now();
        const duration = 900;
        const baseOffsets = Array.from({ length: 6 }, (_, i) => 160 + i * 24);
        let tempGrid = randomGrid();
        const tick = (now) => {
            const t = Math.min((now - start) / duration, 1);
            const eased = 1 - Math.pow(1 - t, 3);
            const offsets = baseOffsets.map((base, i) => Math.max(0, base * (1 - eased) - i * 6));
            if (t < 1) {
                if (Math.floor(now / 80) % 2 === 0) {
                    tempGrid = randomGrid();
                }
                drawGrid(tempGrid, offsets);
                requestAnimationFrame(tick);
            } else {
                drawGrid(finalGrid, Array(6).fill(0));
                if (typeof onComplete === 'function') {
                    onComplete();
                }
            }
        };
        requestAnimationFrame(tick);
    };

    const runSpin = () => {
        if (spinning) return;
        spinning = true;
        statusEl.textContent = 'Спин...';
        const bet = Number(betInput?.value || 0);
        fetch('/api/game.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ game: slotPanel.dataset.slotGame, bet }),
        })
            .then((res) => res.json())
            .then((data) => {
                if (data.error) {
                    resultText.textContent = data.error;
                    statusEl.textContent = 'Ожидание';
                    spinning = false;
                    return;
                }
                const grid = data.grid;
                animateSpin(grid, () => {
                    const win = Number(data.win || 0);
                    winEl.textContent = `${win.toFixed(2)}₽`;
                    resultText.textContent = win > 0
                        ? `Выигрыш: ${win.toFixed(2)}₽ | Символ: ${data.symbol}`
                        : 'Комбо не собрано. Попробуйте еще раз!';
                    statusEl.textContent = win > 0 ? 'Выигрыш!' : 'Пустой спин';
                    spinning = false;
                    if (autoSpins > 0) {
                        autoSpins -= 1;
                        setTimeout(runSpin, 600);
                    }
                });
            })
            .catch(() => {
                resultText.textContent = 'Сервис временно недоступен.';
                statusEl.textContent = 'Ошибка';
                spinning = false;
            });
    };

    spinBtn?.addEventListener('click', runSpin);
    autoBtn?.addEventListener('click', () => {
        autoSpins = Number(autoBtn.dataset.auto || 0);
        runSpin();
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
