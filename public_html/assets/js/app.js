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
        aurora: {
            bg: '#121225',
            accent: '#00f0ff',
            symbols: [
                { id: 'aurora_crystal', label: 'Кристалл', shape: 'diamond', colors: ['#7efcff', '#3b63ff'] },
                { id: 'aurora_star', label: 'Звезда', shape: 'star', colors: ['#ffe179', '#ff8ad1'] },
                { id: 'aurora_comet', label: 'Комета', shape: 'wave', colors: ['#99a8ff', '#6af5ff'] },
                { id: 'aurora_ring', label: 'Кольцо', shape: 'ring', colors: ['#7bf0ff', '#7b6bff'] },
                { id: 'aurora_orb', label: 'Сфера', shape: 'orb', colors: ['#8ef0ff', '#3fc7ff'] },
                { id: 'aurora_shard', label: 'Осколок', shape: 'prism', colors: ['#84f7ff', '#5c79ff'] },
                { id: 'aurora_wave', label: 'Волна', shape: 'wave', colors: ['#79f7ff', '#48d2ff'] },
                { id: 'aurora_prism', label: 'Призма', shape: 'hex', colors: ['#8fd3ff', '#7a5bff'] },
            ],
        },
        cosmic: {
            bg: '#0f0f20',
            accent: '#6ad3ff',
            symbols: [
                { id: 'cosmic_planet', label: 'Планета', shape: 'planet', colors: ['#7bd7ff', '#4b6cff'] },
                { id: 'cosmic_moon', label: 'Луна', shape: 'orb', colors: ['#f0f7ff', '#8aa7ff'] },
                { id: 'cosmic_nova', label: 'Нова', shape: 'star', colors: ['#ffd36a', '#ff7acb'] },
                { id: 'cosmic_saturn', label: 'Сатурн', shape: 'planet', colors: ['#ffb676', '#ff7a3c'] },
                { id: 'cosmic_void', label: 'Вихрь', shape: 'ring', colors: ['#7b7bff', '#00f0ff'] },
                { id: 'cosmic_ray', label: 'Луч', shape: 'bolt', colors: ['#6ad3ff', '#9b6bff'] },
                { id: 'cosmic_asteroid', label: 'Астероид', shape: 'diamond', colors: ['#b0c2ff', '#5873ff'] },
                { id: 'cosmic_pulse', label: 'Импульс', shape: 'wave', colors: ['#6af5ff', '#3fc7ff'] },
            ],
        },
        dragon: {
            bg: '#1a1220',
            accent: '#f5c542',
            symbols: [
                { id: 'dragon_scale', label: 'Чешуя', shape: 'hex', colors: ['#f5c542', '#ff8f3d'] },
                { id: 'dragon_claw', label: 'Коготь', shape: 'bolt', colors: ['#ffb347', '#ff6a3d'] },
                { id: 'dragon_orb', label: 'Сфера', shape: 'orb', colors: ['#ff9f6b', '#ff6a3d'] },
                { id: 'dragon_ember', label: 'Искра', shape: 'flame', colors: ['#ff9b42', '#ff5f3d'] },
                { id: 'dragon_flame', label: 'Пламя', shape: 'flame', colors: ['#ff5f3d', '#ff9b42'] },
                { id: 'dragon_banner', label: 'Штандарт', shape: 'wave', colors: ['#ffb347', '#f5c542'] },
                { id: 'dragon_horn', label: 'Рог', shape: 'prism', colors: ['#ffb347', '#ff6a3d'] },
                { id: 'dragon_eye', label: 'Око', shape: 'orb', colors: ['#ff7a3c', '#ffd36a'] },
            ],
        },
        sky: {
            bg: '#101733',
            accent: '#6ad3ff',
            symbols: [
                { id: 'sky_bolt', label: 'Молния', shape: 'bolt', colors: ['#6ad3ff', '#2b3cff'] },
                { id: 'sky_cloud', label: 'Облако', shape: 'orb', colors: ['#e9f1ff', '#8bbcff'] },
                { id: 'sky_wing', label: 'Крыло', shape: 'wave', colors: ['#d2f0ff', '#6ad3ff'] },
                { id: 'sky_sun', label: 'Солнце', shape: 'star', colors: ['#ffd36a', '#ff9f6b'] },
                { id: 'sky_rain', label: 'Дождь', shape: 'drop', colors: ['#8bd3ff', '#4b8bff'] },
                { id: 'sky_titan', label: 'Титан', shape: 'shield', colors: ['#6ad3ff', '#8b6bff'] },
                { id: 'sky_gale', label: 'Шквал', shape: 'wave', colors: ['#6af5ff', '#48d2ff'] },
                { id: 'sky_crown', label: 'Корона', shape: 'crown', colors: ['#ffe07a', '#ff9f6b'] },
            ],
        },
        sugar: {
            bg: '#1b0f24',
            accent: '#ff7bd9',
            symbols: [
                { id: 'sugar_macaron', label: 'Макарон', shape: 'orb', colors: ['#ff9ad5', '#ff6ad1'] },
                { id: 'sugar_candy', label: 'Конфета', shape: 'candy', colors: ['#ff7bd9', '#ffb6f1'] },
                { id: 'sugar_lolli', label: 'Леденец', shape: 'ring', colors: ['#ff9ad5', '#ff6ad1'] },
                { id: 'sugar_jelly', label: 'Желе', shape: 'diamond', colors: ['#ffb6f1', '#ff6ad1'] },
                { id: 'sugar_cupcake', label: 'Кекс', shape: 'prism', colors: ['#ff9ad5', '#ffc2a8'] },
                { id: 'sugar_sprinkle', label: 'Посыпка', shape: 'star', colors: ['#ffe179', '#ff7bd9'] },
                { id: 'sugar_heart', label: 'Сердце', shape: 'heart', colors: ['#ff7bd9', '#ff4fb0'] },
                { id: 'sugar_star', label: 'Звезда', shape: 'star', colors: ['#ffe179', '#ff9ad5'] },
            ],
        },
        zenith: {
            bg: '#17122a',
            accent: '#9b6bff',
            symbols: [
                { id: 'zenith_gem', label: 'Гем', shape: 'diamond', colors: ['#9b6bff', '#4b3cff'] },
                { id: 'zenith_prism', label: 'Призма', shape: 'prism', colors: ['#b09bff', '#6a7bff'] },
                { id: 'zenith_triangle', label: 'Треугольник', shape: 'prism', colors: ['#9b6bff', '#6ad3ff'] },
                { id: 'zenith_hex', label: 'Гекса', shape: 'hex', colors: ['#8b8bff', '#6a7bff'] },
                { id: 'zenith_orb', label: 'Сфера', shape: 'orb', colors: ['#9b6bff', '#6ad3ff'] },
                { id: 'zenith_shard', label: 'Осколок', shape: 'diamond', colors: ['#b09bff', '#7a5bff'] },
                { id: 'zenith_beam', label: 'Луч', shape: 'bolt', colors: ['#6ad3ff', '#9b6bff'] },
                { id: 'zenith_crown', label: 'Корона', shape: 'crown', colors: ['#ffd36a', '#9b6bff'] },
            ],
        },
        orbit: {
            bg: '#1a150f',
            accent: '#ff9f6b',
            symbols: [
                { id: 'orbit_planet', label: 'Планета', shape: 'planet', colors: ['#ff9f6b', '#ff6a3d'] },
                { id: 'orbit_ring', label: 'Кольцо', shape: 'ring', colors: ['#ffb347', '#ff9f6b'] },
                { id: 'orbit_meteor', label: 'Метеор', shape: 'bolt', colors: ['#ffd36a', '#ff7a3c'] },
                { id: 'orbit_star', label: 'Звезда', shape: 'star', colors: ['#ffd36a', '#ff9f6b'] },
                { id: 'orbit_core', label: 'Ядро', shape: 'orb', colors: ['#ff9f6b', '#ff6a3d'] },
                { id: 'orbit_satellite', label: 'Спутник', shape: 'hex', colors: ['#ffb347', '#ff7a3c'] },
                { id: 'orbit_comet', label: 'Комета', shape: 'wave', colors: ['#ffd36a', '#ff7a3c'] },
                { id: 'orbit_wave', label: 'Орбита', shape: 'wave', colors: ['#ff9f6b', '#ff7a3c'] },
            ],
        },
    };

    const getTheme = () => themes[theme] || themes.aurora;
    let symbolMap = Object.fromEntries(getTheme().symbols.map((symbol) => [symbol.id, symbol]));
    const toKey = (x, y) => `${x}-${y}`;
    const drawRoundedRect = (x, y, width, height, radius) => {
        if (!ctx) return;
        ctx.beginPath();
        ctx.moveTo(x + radius, y);
        ctx.lineTo(x + width - radius, y);
        ctx.quadraticCurveTo(x + width, y, x + width, y + radius);
        ctx.lineTo(x + width, y + height - radius);
        ctx.quadraticCurveTo(x + width, y + height, x + width - radius, y + height);
        ctx.lineTo(x + radius, y + height);
        ctx.quadraticCurveTo(x, y + height, x, y + height - radius);
        ctx.lineTo(x, y + radius);
        ctx.quadraticCurveTo(x, y, x + radius, y);
        ctx.closePath();
    };

    const drawStarShape = (cx, cy, spikes, outerRadius, innerRadius) => {
        if (!ctx) return;
        let rotation = (Math.PI / 2) * 3;
        const step = Math.PI / spikes;
        ctx.beginPath();
        ctx.moveTo(cx, cy - outerRadius);
        for (let i = 0; i < spikes; i++) {
            ctx.lineTo(cx + Math.cos(rotation) * outerRadius, cy + Math.sin(rotation) * outerRadius);
            rotation += step;
            ctx.lineTo(cx + Math.cos(rotation) * innerRadius, cy + Math.sin(rotation) * innerRadius);
            rotation += step;
        }
        ctx.closePath();
    };

    const drawSymbolShape = (symbol, x, y, width, height) => {
        if (!ctx || !symbol) return;
        const cx = x + width / 2;
        const cy = y + height / 2;
        const size = Math.min(width, height) * 0.38;
        switch (symbol.shape) {
            case 'diamond':
                ctx.beginPath();
                ctx.moveTo(cx, cy - size);
                ctx.lineTo(cx + size, cy);
                ctx.lineTo(cx, cy + size);
                ctx.lineTo(cx - size, cy);
                ctx.closePath();
                break;
            case 'star':
                drawStarShape(cx, cy, 5, size, size * 0.45);
                break;
            case 'orb':
                ctx.beginPath();
                ctx.arc(cx, cy, size * 0.8, 0, Math.PI * 2);
                break;
            case 'flame':
                ctx.beginPath();
                ctx.moveTo(cx, cy + size);
                ctx.bezierCurveTo(cx + size * 0.8, cy + size * 0.2, cx + size * 0.6, cy - size * 0.8, cx, cy - size);
                ctx.bezierCurveTo(cx - size * 0.6, cy - size * 0.8, cx - size * 0.8, cy + size * 0.2, cx, cy + size);
                ctx.closePath();
                break;
            case 'bolt':
                ctx.beginPath();
                ctx.moveTo(cx - size * 0.2, cy - size);
                ctx.lineTo(cx + size * 0.2, cy - size * 0.2);
                ctx.lineTo(cx + size * 0.05, cy - size * 0.2);
                ctx.lineTo(cx + size * 0.5, cy + size);
                ctx.lineTo(cx - size * 0.3, cy + size * 0.2);
                ctx.lineTo(cx - size * 0.05, cy + size * 0.2);
                ctx.closePath();
                break;
            case 'leaf':
                ctx.beginPath();
                ctx.ellipse(cx, cy, size * 0.7, size, Math.PI / 6, 0, Math.PI * 2);
                break;
            case 'candy':
                drawRoundedRect(cx - size, cy - size * 0.6, size * 2, size * 1.2, size * 0.4);
                break;
            case 'planet':
                ctx.beginPath();
                ctx.arc(cx, cy, size * 0.75, 0, Math.PI * 2);
                break;
            case 'crown':
                ctx.beginPath();
                ctx.moveTo(cx - size, cy + size * 0.6);
                ctx.lineTo(cx - size * 0.7, cy - size * 0.2);
                ctx.lineTo(cx, cy - size * 0.8);
                ctx.lineTo(cx + size * 0.7, cy - size * 0.2);
                ctx.lineTo(cx + size, cy + size * 0.6);
                ctx.closePath();
                break;
            case 'hex':
                ctx.beginPath();
                for (let i = 0; i < 6; i++) {
                    const angle = (Math.PI / 3) * i;
                    const px = cx + size * 0.85 * Math.cos(angle);
                    const py = cy + size * 0.85 * Math.sin(angle);
                    if (i === 0) {
                        ctx.moveTo(px, py);
                    } else {
                        ctx.lineTo(px, py);
                    }
                }
                ctx.closePath();
                break;
            case 'ring':
                ctx.beginPath();
                ctx.arc(cx, cy, size * 0.85, 0, Math.PI * 2);
                break;
            case 'wave':
                ctx.beginPath();
                ctx.moveTo(cx - size, cy);
                ctx.bezierCurveTo(cx - size * 0.5, cy - size * 0.8, cx + size * 0.2, cy + size * 0.8, cx + size, cy);
                ctx.lineTo(cx + size * 0.8, cy + size * 0.6);
                ctx.bezierCurveTo(cx + size * 0.2, cy + size * 1.1, cx - size * 0.4, cy - size * 0.1, cx - size, cy + size * 0.6);
                ctx.closePath();
                break;
            case 'prism':
                ctx.beginPath();
                ctx.moveTo(cx, cy - size);
                ctx.lineTo(cx + size, cy + size);
                ctx.lineTo(cx - size, cy + size);
                ctx.closePath();
                break;
            case 'heart':
                ctx.beginPath();
                ctx.moveTo(cx, cy + size * 0.8);
                ctx.bezierCurveTo(cx + size, cy + size * 0.2, cx + size, cy - size * 0.4, cx, cy - size * 0.1);
                ctx.bezierCurveTo(cx - size, cy - size * 0.4, cx - size, cy + size * 0.2, cx, cy + size * 0.8);
                ctx.closePath();
                break;
            case 'drop':
                ctx.beginPath();
                ctx.moveTo(cx, cy - size);
                ctx.bezierCurveTo(cx + size * 0.7, cy - size * 0.2, cx + size * 0.4, cy + size * 0.7, cx, cy + size);
                ctx.bezierCurveTo(cx - size * 0.4, cy + size * 0.7, cx - size * 0.7, cy - size * 0.2, cx, cy - size);
                ctx.closePath();
                break;
            case 'shield':
                ctx.beginPath();
                ctx.moveTo(cx - size * 0.7, cy - size * 0.9);
                ctx.lineTo(cx + size * 0.7, cy - size * 0.9);
                ctx.lineTo(cx + size * 0.8, cy + size * 0.1);
                ctx.lineTo(cx, cy + size);
                ctx.lineTo(cx - size * 0.8, cy + size * 0.1);
                ctx.closePath();
                break;
            default:
                ctx.beginPath();
                ctx.arc(cx, cy, size * 0.75, 0, Math.PI * 2);
                break;
        }
    };

    const drawSymbol = (symbol, x, y, width, height) => {
        if (!ctx || !symbol) return;
        const baseRadius = Math.min(width, height) * 0.2;
        const gradient = ctx.createLinearGradient(x, y, x + width, y + height);
        gradient.addColorStop(0, symbol.colors?.[0] || '#ffffff');
        gradient.addColorStop(1, symbol.colors?.[1] || '#cccccc');
        drawRoundedRect(x, y, width, height, baseRadius);
        ctx.fillStyle = 'rgba(0,0,0,0.35)';
        ctx.fill();
        ctx.strokeStyle = 'rgba(255,255,255,0.2)';
        ctx.lineWidth = 2;
        ctx.stroke();
        drawRoundedRect(x + 4, y + 4, width - 8, height - 8, baseRadius * 0.8);
        ctx.fillStyle = gradient;
        ctx.fill();
        ctx.save();
        ctx.shadowColor = symbol.colors?.[0] || '#ffffff';
        ctx.shadowBlur = 10;
        drawSymbolShape(symbol, x, y, width, height);
        ctx.fillStyle = '#0b0b14';
        ctx.fill();
        ctx.restore();
        ctx.save();
        ctx.globalAlpha = 0.85;
        drawSymbolShape(symbol, x, y, width, height);
        ctx.fillStyle = '#ffffff';
        ctx.fill();
        ctx.strokeStyle = 'rgba(0,0,0,0.6)';
        ctx.lineWidth = 2;
        ctx.stroke();
        ctx.restore();
        if (symbol.shape === 'planet') {
            ctx.save();
            ctx.strokeStyle = 'rgba(255,255,255,0.8)';
            ctx.lineWidth = 3;
            ctx.beginPath();
            ctx.ellipse(x + width / 2, y + height / 2, width * 0.35, height * 0.12, -0.4, 0, Math.PI * 2);
            ctx.stroke();
            ctx.restore();
        }
        if (symbol.shape === 'ring') {
            ctx.save();
            ctx.strokeStyle = 'rgba(255,255,255,0.9)';
            ctx.lineWidth = 3;
            ctx.beginPath();
            ctx.arc(x + width / 2, y + height / 2, Math.min(width, height) * 0.26, 0, Math.PI * 2);
            ctx.stroke();
            ctx.restore();
        }
        if (symbol.shape === 'candy') {
            ctx.save();
            ctx.strokeStyle = 'rgba(255,255,255,0.8)';
            ctx.lineWidth = 3;
            ctx.beginPath();
            ctx.moveTo(x + width * 0.2, y + height * 0.2);
            ctx.lineTo(x + width * 0.8, y + height * 0.8);
            ctx.stroke();
            ctx.beginPath();
            ctx.moveTo(x + width * 0.2, y + height * 0.5);
            ctx.lineTo(x + width * 0.8, y + height * 0.5);
            ctx.stroke();
            ctx.restore();
        }
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

    const drawGrid = (grid, offsets = [], highlight = new Set()) => {
        if (!ctx || !canvas) return;
        const cols = grid.length;
        const rows = grid[0]?.length || 0;
        const width = canvas.clientWidth || canvas.width;
        const height = canvas.clientHeight || canvas.height;
        const cellW = width / cols;
        const cellH = height / rows;
        const palette = getTheme();
        const useOffsets = offsets.length ? offsets : Array(cols).fill(0);
        ctx.clearRect(0, 0, width, height);
        ctx.fillStyle = palette.bg;
        ctx.fillRect(0, 0, width, height);
        grid.forEach((col, x) => {
            col.forEach((symbol, y) => {
                const yPos = y * cellH + useOffsets[x];
                const symbolData = symbolMap[symbol] || {
                    id: symbol,
                    label: symbol,
                    shape: 'orb',
                    colors: [palette.accent, '#ffffff'],
                };
                if (highlight.has(toKey(x, y))) {
                    ctx.save();
                    ctx.shadowColor = palette.accent;
                    ctx.shadowBlur = 18;
                    ctx.strokeStyle = palette.accent;
                    ctx.lineWidth = 3;
                    ctx.strokeRect(x * cellW + 6, yPos + 6, cellW - 12, cellH - 12);
                    ctx.restore();
                }
                drawSymbol(symbolData, x * cellW + 10, yPos + 10, cellW - 20, cellH - 20);
            });
        });
    };

    const gridCols = Number(slotPanel.dataset.cols || 6);
    const gridRows = Number(slotPanel.dataset.rows || 5);
    const idleGrid = Array.from({ length: gridCols }, () =>
        Array.from({ length: gridRows }, () => getTheme().symbols?.[0]?.id || 'fallback')
    );
    const updatePayoutHints = () => {
        const bet = Number(betInput?.value || 0);
        document.querySelectorAll('.slot-hints li[data-multiplier]').forEach((item) => {
            const multiplier = Number(item.dataset.multiplier || 0);
            const win = bet * multiplier;
            const winEl = item.querySelector('.slot-hint-win');
            if (winEl) {
                winEl.textContent = `${win.toFixed(2)}₽`;
            }
        });
    };
    resizeCanvas();
    drawGrid(idleGrid);
    updatePayoutHints();
    betInput?.addEventListener('input', updatePayoutHints);
    window.addEventListener('resize', () => {
        resizeCanvas();
        drawGrid(idleGrid);
    });

    const randomSymbol = () => {
        const symbols = getTheme().symbols || themes.aurora.symbols;
        return symbols[Math.floor(Math.random() * symbols.length)].id;
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
                    const winCells = Array.isArray(data.win_cells) ? data.win_cells : [];
                    const highlight = new Set(winCells.map(([x, y]) => toKey(x, y)));
                    drawGrid(grid, Array(grid.length).fill(0), highlight);
                    winEl.textContent = `${win.toFixed(2)}₽`;
                    const symbolLabel = symbolMap[data.symbol]?.label || data.symbol;
                    const multiplier = data.multiplier ? ` x${Number(data.multiplier).toFixed(2)}` : '';
                    const feature = data.feature ? ` • ${data.feature}` : '';
                    resultText.textContent = win > 0
                        ? `Выигрыш: ${win.toFixed(2)}₽${multiplier} • Символ: ${symbolLabel}${feature}`
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
            const slotsEl = wrapper?.querySelector('[data-plinko-slots]');
            const difficultyInputs = wrapper?.querySelectorAll('input[name="plinko_difficulty"]');
            const ctx = canvas?.getContext('2d');
            const plinkoConfigs = {
                easy: { rows: 6, multipliers: [0.2, 0.5, 0.8, 1, 1.2, 1, 0.8, 0.5, 0.2] },
                medium: { rows: 8, multipliers: [0, 0.2, 0.5, 0.8, 1, 1.5, 2, 1.5, 1, 0.8, 0.5, 0.2, 0] },
                hard: { rows: 10, multipliers: [0, 0.1, 0.2, 0.5, 0.8, 1, 1.5, 2, 3, 2, 1.5, 1, 0.8, 0.5, 0.2, 0.1, 0] },
            };
            let pegLayout = [];
            let slotLayout = [];
            const getDifficulty = () =>
                wrapper?.querySelector('input[name="plinko_difficulty"]:checked')?.value || 'easy';
            const renderSlots = (multipliers, activeIndex = null) => {
                if (!slotsEl) return;
                slotsEl.innerHTML = '';
                multipliers.forEach((value, index) => {
                    const slot = document.createElement('span');
                    slot.className = 'plinko-slot';
                    slot.textContent = `x${value}`;
                    if (activeIndex === index) {
                        slot.classList.add('active');
                    }
                    slotsEl.appendChild(slot);
                });
            };
            const buildLayout = (rows, width, height) => {
                const pegs = [];
                const topOffset = 70;
                const usableHeight = height - topOffset - 60;
                const rowGap = usableHeight / rows;
                for (let row = 0; row < rows; row++) {
                    const count = row + 1;
                    const gap = width / (count + 1);
                    for (let col = 0; col < count; col++) {
                        pegs.push({
                            x: gap * (col + 1),
                            y: topOffset + rowGap * row,
                        });
                    }
                }
                const slots = [];
                const slotCount = rows + 1;
                const slotGap = width / slotCount;
                for (let i = 0; i < slotCount; i++) {
                    slots.push({
                        x: slotGap * i,
                        width: slotGap,
                        center: slotGap * i + slotGap / 2,
                    });
                }
                return { pegs, slots };
            };
            const drawBoard = (activeIndex = null) => {
                if (!ctx || !canvas) return;
                const width = canvas.clientWidth || canvas.width;
                const height = canvas.clientHeight || canvas.height;
                ctx.clearRect(0, 0, width, height);
                ctx.fillStyle = '#0f0f22';
                ctx.fillRect(0, 0, width, height);
                ctx.fillStyle = '#f5c542';
                pegLayout.forEach((peg) => {
                    ctx.beginPath();
                    ctx.arc(peg.x, peg.y, 6, 0, Math.PI * 2);
                    ctx.fill();
                });
                ctx.strokeStyle = 'rgba(255,255,255,0.08)';
                slotLayout.forEach((slot, index) => {
                    ctx.lineWidth = activeIndex === index ? 2 : 1;
                    ctx.strokeStyle = activeIndex === index ? '#f5c542' : 'rgba(255,255,255,0.08)';
                    ctx.strokeRect(slot.x + 4, height - 46, slot.width - 8, 36);
                });
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
            const updateBoard = () => {
                const config = plinkoConfigs[getDifficulty()] || plinkoConfigs.easy;
                const width = canvas?.clientWidth || canvas?.width || 420;
                const height = canvas?.clientHeight || canvas?.height || 520;
                const layout = buildLayout(config.rows, width, height);
                pegLayout = layout.pegs;
                slotLayout = layout.slots;
                renderSlots(config.multipliers);
                drawBoard();
            };
            updateBoard();
            difficultyInputs?.forEach((input) => {
                input.addEventListener('change', () => {
                    updateBoard();
                });
            });
            fetch('/api/minigames.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ game: 'plinko', bet, difficulty: getDifficulty() }),
            })
                .then((res) => res.json())
                .then((data) => {
                    if (data.error) {
                        resultEl.textContent = data.error;
                        return;
                    }
                    const multipliers = data.meta?.multipliers || [];
                    const targetIndex = Number(data.meta?.index ?? 0);
                    const slotIndex = Number.isNaN(targetIndex) ? 0 : targetIndex;
                    const targetSlot = slotLayout[slotIndex];
                    if (multipliers.length) {
                        renderSlots(multipliers);
                    } else {
                        renderSlots(plinkoConfigs[getDifficulty()]?.multipliers || plinkoConfigs.easy.multipliers);
                    }
                    let x = (canvas?.clientWidth || canvas.width) / 2;
                    let y = 30;
                    let vx = (Math.random() - 0.5) * 2;
                    let vy = 0;
                    const ballRadius = 10;
                    const pegRadius = 6;
                    const gravity = 0.45;
                    const bounce = 0.75;
                    const targetX = targetSlot?.center ?? x;
                    const animate = () => {
                        if (!ctx || !canvas) return;
                        vy += gravity;
                        x += vx;
                        y += vy;
                        if (x < ballRadius || x > canvas.width - ballRadius) {
                            vx *= -0.6;
                            x = Math.min(Math.max(x, ballRadius), canvas.width - ballRadius);
                        }
                        pegLayout.forEach((peg) => {
                            const dx = x - peg.x;
                            const dy = y - peg.y;
                            const dist = Math.hypot(dx, dy);
                            if (dist > 0 && dist < ballRadius + pegRadius) {
                                const nx = dx / dist;
                                const ny = dy / dist;
                                const overlap = ballRadius + pegRadius - dist;
                                x += nx * overlap;
                                y += ny * overlap;
                                const dot = vx * nx + vy * ny;
                                vx -= 2 * dot * nx;
                                vy -= 2 * dot * ny;
                                vx *= bounce;
                                vy *= bounce;
                            }
                        });
                        if (y > canvas.height - 140) {
                            vx += (targetX - x) * 0.002;
                        }
                        drawBoard();
                        ctx.fillStyle = '#00f0ff';
                        ctx.beginPath();
                        ctx.arc(x, y, 10, 0, Math.PI * 2);
                        ctx.fill();
                        if (y < canvas.height - 60) {
                            requestAnimationFrame(animate);
                        } else {
                            const multiplier = data.meta?.multiplier ?? 0;
                            const win = Number(data.win || 0);
                            resultEl.textContent = `${resultLabel} x${multiplier} • Выигрыш ${win}₽`;
                            const fallbackMultipliers = plinkoConfigs[getDifficulty()]?.multipliers || plinkoConfigs.easy.multipliers;
                            drawBoard(slotIndex);
                            renderSlots(multipliers.length ? multipliers : fallbackMultipliers, slotIndex);
                        }
                    };
                    animate();
                });
        }
        if (game === 'dice') {
            const diceDisplay = wrapper?.querySelector('[data-dice-display]');
            const diceResult = wrapper?.querySelector('[data-dice-result]');
            const pick = Number(wrapper?.querySelector('.minigame-dice-pick')?.value || 1);
            const winLabel = wrapper?.dataset.diceWin || 'Угадали';
            const loseLabel = wrapper?.dataset.diceLose || 'Не угадали';
            fetch('/api/minigames.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ game: 'dice', bet, pick }),
            })
                .then((res) => res.json())
                .then((data) => {
                    if (data.error) {
                        diceResult.textContent = data.error;
                        return;
                    }
                    const roll = Number(data.meta?.roll || 1);
                    diceDisplay?.setAttribute('data-value', String(roll));
                    const win = Number(data.win || 0);
                    diceResult.textContent = win > 0 ? `${winLabel}: ${win}₽ (🎲 ${roll})` : `${loseLabel} (🎲 ${roll})`;
                });
        }
        if (game === 'highlow') {
            const cardDisplay = wrapper?.querySelector('[data-card-display]');
            const resultEl = wrapper?.querySelector('[data-highlow-result]');
            const pick = wrapper?.querySelector('.minigame-highlow-pick')?.value || 'high';
            const winLabel = wrapper?.dataset.highlowWin || 'Выигрыш';
            const loseLabel = wrapper?.dataset.highlowLose || 'Проигрыш';
            const pushLabel = wrapper?.dataset.highlowPush || 'Возврат ставки';
            const suitMap = { hearts: '♥', diamonds: '♦', clubs: '♣', spades: '♠' };
            const valueMap = {
                1: 'A',
                11: 'J',
                12: 'Q',
                13: 'K',
            };
            const updateCard = (value, suit) => {
                if (!cardDisplay) return;
                const face = valueMap[value] || String(value);
                cardDisplay.setAttribute('data-value', face);
                cardDisplay.setAttribute('data-suit', suit);
                cardDisplay.querySelectorAll('.card-value').forEach((el) => {
                    el.textContent = face;
                });
                const suitSymbol = suitMap[suit] || '♠';
                cardDisplay.querySelectorAll('.card-suit, .card-center').forEach((el) => {
                    el.textContent = suitSymbol;
                });
            };
            fetch('/api/minigames.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ game: 'highlow', bet, pick }),
            })
                .then((res) => res.json())
                .then((data) => {
                    if (data.error) {
                        resultEl.textContent = data.error;
                        return;
                    }
                    const value = Number(data.meta?.value || 7);
                    const suit = data.meta?.suit || 'hearts';
                    updateCard(value, suit);
                    const win = Number(data.win || 0);
                    const outcome = data.meta?.outcome;
                    if (outcome === 'push') {
                        resultEl.textContent = `${pushLabel}: ${win}₽`;
                    } else if (win > 0) {
                        resultEl.textContent = `${winLabel}: ${win}₽`;
                    } else {
                        resultEl.textContent = `${loseLabel}`;
                    }
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
