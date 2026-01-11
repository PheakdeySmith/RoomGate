// --- Toast UI (message box above the cat) ---
function showToast(type, title, message) {
    // remove any existing toast
    document.querySelectorAll('.cat-toast').forEach(el => el.remove());

    const wrap = document.createElement('div');
    wrap.className = `cat-toast ${type}`;

    const t = document.createElement('div');
    t.className = 'title';
    t.textContent = title;

    const m = document.createElement('div');
    m.className = 'msg';
    m.textContent = message;

    wrap.appendChild(t);
    wrap.appendChild(m);
    document.body.appendChild(wrap);

    setTimeout(() => wrap.remove(), 4200);
}

// --- Live2D cleanup ---
function clearAllLive2DElements() {
    const canvases = document.querySelectorAll('canvas');
    canvases.forEach(canvas => {
        if (canvas.id && (canvas.id.includes('live2d') || canvas.id.includes('L2D'))) {
            canvas.remove();
        }
    });

    const waifuElements = document.querySelectorAll('#waifu, .waifu, [id*="live2d"], [class*="live2d"]');
    waifuElements.forEach(element => element.remove());
}

// --- Init Live2D ---
function initializeCat() {
    console.log('🐱 Booting cat...');
    clearAllLive2DElements();

    setTimeout(() => {
        if (typeof L2Dwidget === 'undefined') {
            console.error('❌ L2Dwidget library not loaded!');
            showToast('error', 'Oops', 'Live2D library did not load. The cat is stuck in traffic.');
            return;
        }

        try {
            L2Dwidget.init({
                model: {
                    // Swap model here:
                    // Tororo (cat):  https://unpkg.com/live2d-widget-model-tororo@1.0.5/assets/tororo.model.json
                    // Wanko (dog):   https://unpkg.com/live2d-widget-model-wanko@1.0.5/assets/wanko.model.json
                    // Shizuku:       https://unpkg.com/live2d-widget-model-shizuku@1.0.5/assets/shizuku.model.json
                    jsonPath: "https://unpkg.com/live2d-widget-model-tororo@1.0.5/assets/tororo.model.json",
                    scale: 1
                },
                display: {
                    position: "right",
                    width: 150,
                    height: 200,
                    hOffset: 0,
                    vOffset: -20,
                    superSample: 2
                },
                mobile: {
                    show: false
                },
                react: {
                    opacity: 0.85
                },
                log: true
            });

            console.log('✅ Cat loaded.');
            showToast('success', 'Success', 'Cat online. Please do not feed it production bugs.');

            setTimeout(() => {
                setupCatMessages();
                setupMouseHover();
            }, 2000);

        } catch (error) {
            console.error('❌ Live2D init failed:', error);
            showToast('error', 'Error', 'Cat refused to load. Check DevTools console for the drama.');
        }
    }, 800);
}

// --- Funny message pool ---
function getRandom(arr) {
    return arr[Math.floor(Math.random() * arr.length)];
}

function setupCatMessages() {
    const timeBasedMessages = getTimeBasedMessages();
    const seasonalMessages = getSeasonalMessages();
    const basicMessages = [
        "Welcome! I’m the site’s emotional support cat.",
        "I’m not judging your code… I’m *observing* it.",
        "If you’re stuck, try turning it off and meowing again.",
        "This page is now under cat supervision.",
        "I saw that copy-paste. Respect.",
        "If it works, it’s not a bug. It’s a feature with whiskers."
    ];

    const messages = [...timeBasedMessages, ...seasonalMessages, ...basicMessages];


    // Copy celebration
    document.addEventListener('copy', function () {
        showToast('info', 'Clipboard', getRandom([
            "Nice. You copied something. The cat approves.",
            "Clipboard acquired. Cat is pleased.",
            "Copy successful. Now paste wisely, human."
        ]));
    });

    // Click canvas / bottom-left
    document.addEventListener('click', function (e) {
        if (e.target.tagName === 'CANVAS' ||
            (e.clientX > window.innerWidth - 200 && e.clientY > window.innerHeight - 250)) {
            showToast('info', 'Cat says', getRandom(messages));
            hideHoverMessage();
        }
    });

    // Welcome
    setTimeout(() => {
        showToast('success', 'Welcome', getWelcomeMessage());
    }, 900);
}

// --- Hover messages ---
function setupMouseHover() {
    const hoverMessages = {
        head: ["Pat detected. Morale increased +10.", "Careful… the ears are sensitive hardware."],
        face: ["Stop staring. I’m fluffy, not a log file.", "Hello there. I’m the UI now."],
        body: ["That’s the belly. It’s a trap.", "Belly rub request: denied (maybe later)."],
        tail: ["Tail is not a handle. This is not a suitcase.", "Touch tail = instant chaos mode."],
        paws: ["These beans are private property.", "Paw zone: highly ticklish."]
    };

    let hoverTimer;
    let isHovering = false;

    function setupCanvasHover() {
        const canvas = document.querySelector('#live2dcanvas') || document.querySelector('canvas');
        if (!canvas) {
            setTimeout(setupCanvasHover, 300);
            return;
        }

        canvas.addEventListener('mouseenter', () => {
            isHovering = true;
        });
        canvas.addEventListener('mouseleave', () => {
            isHovering = false;
            clearTimeout(hoverTimer);
            hideHoverMessage();
        });

        canvas.addEventListener('mousemove', (e) => {
            if (!isHovering) return;

            clearTimeout(hoverTimer);
            hoverTimer = setTimeout(() => {
                const rect = canvas.getBoundingClientRect();
                const x = e.clientX - rect.left;
                const y = e.clientY - rect.top;

                const part = detectBodyPart(x, y, rect.width, rect.height);
                const msg = getRandom(hoverMessages[part] || ["Meow."]);
                showHoverMessage(msg, e.clientX, e.clientY);
            }, 600);
        });
    }

    setupCanvasHover();
}

function detectBodyPart(x, y, width, height) {
    const rx = x / width;
    const ry = y / height;

    if (ry < 0.3) return (rx > 0.3 && rx < 0.7) ? 'face' : 'head';
    if (ry < 0.7) return 'body';
    return (rx < 0.3 || rx > 0.8) ? 'tail' : 'paws';
}

function showHoverMessage(text, mouseX, mouseY) {
    hideHoverMessage();

    const div = document.createElement('div');
    div.className = 'cat-hover-message';
    div.textContent = text;

    const left = Math.min(mouseX + 15, window.innerWidth - 260);
    const top = Math.max(mouseY - 50, 10);

    div.style.left = left + "px";
    div.style.top = top + "px";

    document.body.appendChild(div);
}

function hideHoverMessage() {
    const existing = document.querySelector('.cat-hover-message');
    if (existing) existing.remove();
}

function isMobile() {
    return window.innerWidth < 768 ||
        /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent);
}


// --- Time/season messages (funny English) ---
function getTimeBasedMessages() {
    const hour = new Date().getHours();
    if (hour >= 5 && hour < 12) return [
        "Morning! Time to ship code (and regrets) early.",
        "Coffee first. Then we debug reality.",
        "Fresh brain hours. Use them before meetings attack.",
        "Good morning. I demand snacks and clean commits."
    ];
    if (hour >= 12 && hour < 17) return [
        "Afternoon mode: 70% focus, 30% “where did my time go?”",
        "Lunch was great. Now we pretend we’re productive.",
        "Pro tip: hydrate. Your code is thirsty too.",
        "If you see a bug, blink twice. I’ll pretend I didn’t."
    ];
    if (hour >= 17 && hour < 22) return [
        "Evening! Perfect time to fix one small thing that becomes 12.",
        "Wrap-up time: commit, push, and flee gracefully.",
        "End-of-day energy: dangerously confident.",
        "Night shift? I’ll supervise from the couch."
    ];
    return [
        "It’s late. The bugs are stronger at night.",
        "Sleep is a feature. You should enable it.",
        "If you’re coding at 3AM, at least name variables poetically.",
        "Night mode: whisper your errors so they don’t multiply."
    ];
}

function getSeasonalMessages() {
    const month = new Date().getMonth() + 1;
    if (month >= 3 && month <= 5) return [
        "Spring vibes: new ideas, same old TODO list.",
        "It’s getting warm. So are the CPU temps.",
        "Spring cleaning? Start with unused branches.",
        "Fresh season, fresh bugs!"
    ];
    if (month >= 6 && month <= 8) return [
        "Summer mode: fewer clothes, more hotfixes.",
        "Stay cool. Your server won’t.",
        "If it’s too hot, blame the build pipeline.",
        "Ice water + keyboard = dangerous combo. Don’t."
    ];
    if (month >= 9 && month <= 11) return [
        "Autumn: leaves fall, so do production deployments.",
        "Cozy weather for cozy debugging.",
        "Hot tea, cold logs.",
        "Fall season: perfect time to refactor what you fear."
    ];
    return [
        "Winter: warm hoodie, cold stack traces.",
        "Holiday vibes. Deploy carefully, brave human.",
        "Winter plan: stay warm, write tests.",
        "If you break prod, at least bring cookies."
    ];
}

function getWelcomeMessage() {
    const all = [...getTimeBasedMessages(), ...getSeasonalMessages()];
    return getRandom(all);
}

// --- Buttons ---
function bindButtons() {
    const btnSuccess = document.getElementById('btnSuccess');
    const btnError = document.getElementById('btnError');
    const btnInfo = document.getElementById('btnInfo');

    if (btnSuccess) {
        btnSuccess.addEventListener('click', () => {
            showToast('success', 'Success', 'Everything is fine. Probably. ✅');
        });
    }

    if (btnError) {
        btnError.addEventListener('click', () => {
            showToast('error', 'Error', 'Something exploded… but in a *cute* way. ⛔️');
        });
    }

    if (btnInfo) {
        btnInfo.addEventListener('click', () => {
            showToast('info', 'Info', 'FYI: The cat is watching your commits. ℹ️');
        });
    }
}

// --- Boot ---
document.addEventListener('DOMContentLoaded', () => {
    bindButtons();
    if (!isMobile()) initializeCat();
    else showToast('info', 'Mobile', 'Live2D disabled on mobile. Cat is on vacation.');
});
