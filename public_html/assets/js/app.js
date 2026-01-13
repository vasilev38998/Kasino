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
        zenith: { bg: '#17122a', accent: '#9b6bff', symbols: ['🔷', '🔶', '🔺', '🔸', '💎', '✨', 'A', 'K'] },
        orbit: { bg: '#1a150f', accent: '#ff9f6b', symbols: ['🪐', '🌙', '⭐', '💠', 'A', 'K', 'Q', 'J'] },
    };

    const resizeCanvas = () => {
        if (!canvas || !slotPanel) return;
        const rect = slotPanel.getBoundingClientRect();
        const ratio = window.devicePixelRatio || 1;
        const width = Math.max(320, Math.floor(rect.width));
        const height = Math.max(280, Math.floor(rect.height));
        canvas.width = width * ratio;
        canvas.height = height * ratio;
        canvas.style.width = `${width}px`;
        canvas.style.height = `${height}px`;
        if (ctx) {
            ctx.setTransform(ratio, 0, 0, ratio, 0, 0);
        }
    };

    const drawGrid = (grid, offsets = []) => {
        if (!ctx || !canvas) return;
        const cols = grid.length;
        const rows = grid[0]?.length || 0;
        const width = canvas.clientWidth || canvas.width;
        const height = canvas.clientHeight || canvas.height;
        const cellW = width / cols;
        const cellH = height / rows;
        const palette = themes[theme] || themes.aurora;
        const useOffsets = offsets.length ? offsets : Array(cols).fill(0);
        ctx.clearRect(0, 0, width, height);
        ctx.fillStyle = palette.bg;
        ctx.fillRect(0, 0, width, height);
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

    const gridCols = Number(slotPanel.dataset.cols || 6);
    const gridRows = Number(slotPanel.dataset.rows || 5);
    const idleGrid = Array.from({ length: gridCols }, () =>
        Array.from({ length: gridRows }, () => themes[theme]?.symbols?.[0] || 'A')
    );
    resizeCanvas();
    drawGrid(idleGrid);
    window.addEventListener('resize', () => {
        resizeCanvas();
        drawGrid(idleGrid);
    });

    const randomSymbol = () => {
        const symbols = themes[theme]?.symbols || themes.aurora.symbols;
        return symbols[Math.floor(Math.random() * symbols.length)];
    };

    const randomGrid = () =>
        Array.from({ length: idleGrid.length }, () => Array.from({ length: idleGrid[0].length }, randomSymbol));

    const animateSpin = (finalGrid, onComplete) => {
        if (!ctx || !canvas) return;
        const start = performance.now();
        const duration = 900;
        const baseOffsets = Array.from({ length: finalGrid.length }, (_, i) => 160 + i * 24);
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
                drawGrid(finalGrid, Array(finalGrid.length).fill(0));
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
                slotPanel.classList.add('spinning');
                animateSpin(grid, () => {
                    const win = Number(data.win || 0);
                    winEl.textContent = `${win.toFixed(2)}₽`;
                    const multiplier = data.multiplier ? ` x${Number(data.multiplier).toFixed(2)}` : '';
                    const feature = data.feature ? ` • ${data.feature}` : '';
                    resultText.textContent = win > 0
                        ? `Выигрыш: ${win.toFixed(2)}₽${multiplier} • Символ: ${data.symbol}${feature}`
                        : 'Комбо не собрано. Попробуйте еще раз!';
                    statusEl.textContent = win > 0 ? 'Выигрыш!' : 'Пустой спин';
                    slotPanel.classList.remove('spinning');
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
                slotPanel.classList.remove('spinning');
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

const minigameButtons = document.querySelectorAll('.minigame-play');
minigameButtons.forEach((btn) => {
    btn.addEventListener('click', () => {
        const wrapper = btn.closest('.minigame-layout');
        const betInput = wrapper?.querySelector('.minigame-bet');
        const bet = Number(betInput?.value || 0);
        const game = btn.dataset.minigame;
        if (game === 'coin') {
            const choice = wrapper?.querySelector('.minigame-side')?.value || 'heads';
            const coinDisplay = wrapper?.querySelector('[data-coin-display]');
            const coinResult = wrapper?.querySelector('[data-coin-result]');
            const winLabel = wrapper?.dataset.coinWin || 'Победа';
            const loseLabel = wrapper?.dataset.coinLose || 'Проигрыш';
            const headsLabel = wrapper?.dataset.heads || 'Орёл';
            const tailsLabel = wrapper?.dataset.tails || 'Решка';
            coinDisplay?.classList.remove('flip');
            fetch('/api/minigames.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ game: 'coin', bet, choice }),
            })
                .then((res) => res.json())
                .then((data) => {
                    if (data.error) {
                        coinResult.textContent = data.error;
                        return;
                    }
                    coinDisplay?.classList.add('flip');
                    const win = Number(data.win || 0);
                    const result = data.meta?.result === 'heads' ? headsLabel : tailsLabel;
                    coinResult.textContent = win > 0 ? `${winLabel}: ${win}₽ (${result})` : `${loseLabel} (${result})`;
                });
        }
        if (game === 'plinko') {
            const canvas = wrapper?.querySelector('.plinko-canvas');
            const resultEl = wrapper?.querySelector('[data-plinko-result]');
            const resultLabel = wrapper?.dataset.plinkoLabel || 'Множитель';
            const ctx = canvas?.getContext('2d');
            const drawBoard = () => {
                if (!ctx || !canvas) return;
                const width = canvas.clientWidth || canvas.width;
                const height = canvas.clientHeight || canvas.height;
                ctx.clearRect(0, 0, width, height);
                ctx.fillStyle = '#0f0f22';
                ctx.fillRect(0, 0, width, height);
                ctx.fillStyle = '#f5c542';
                const rowGap = height / 8;
                const colGap = width / 8;
                for (let row = 0; row < 6; row++) {
                    for (let col = 0; col <= row; col++) {
                        const x = width / 2 - row * colGap * 0.5 + col * colGap;
                        const y = 60 + row * rowGap;
                        ctx.beginPath();
                        ctx.arc(x, y, 6, 0, Math.PI * 2);
                        ctx.fill();
                    }
                }
            };
            const ensureSize = () => {
                if (!canvas) return;
                const rect = canvas.getBoundingClientRect();
                const ratio = window.devicePixelRatio || 1;
                canvas.width = rect.width * ratio;
                canvas.height = rect.height * ratio;
                if (ctx) {
                    ctx.setTransform(ratio, 0, 0, ratio, 0, 0);
                }
            };
            ensureSize();
            drawBoard();
            fetch('/api/minigames.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ game: 'plinko', bet }),
            })
                .then((res) => res.json())
                .then((data) => {
                    if (data.error) {
                        resultEl.textContent = data.error;
                        return;
                    }
                    let y = 40;
                    let x = (canvas?.clientWidth || canvas.width) / 2;
                    const target = x + (Math.random() - 0.5) * 180;
                    const animate = () => {
                        if (!ctx || !canvas) return;
                        drawBoard();
                        y += 12;
                        x += (target - x) * 0.08;
                        ctx.fillStyle = '#00f0ff';
                        ctx.beginPath();
                        ctx.arc(x, y, 10, 0, Math.PI * 2);
                        ctx.fill();
                        if (y < canvas.height - 40) {
                            requestAnimationFrame(animate);
                        } else {
                            const multiplier = data.meta?.multiplier ?? 0;
                            const win = Number(data.win || 0);
                            resultEl.textContent = `${resultLabel} x${multiplier} • Выигрыш ${win}₽`;
                        }
                    };
                    animate();
                });
        }
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
