<?php
/**
 * Title: Demo — Audio Visualizer
 * Slug: dariobimbi/demo-audio
 * Categories: dariobimbi
 * Description: Web Audio API + Canvas. Pick an oscillator (or pipe in your microphone) and watch the analyser data render as bars, waveform, or a circular spectrum. Source signal goes through an AnalyserNode; rAF loop reads its FFT data into Canvas.
 *
 * To use: parent Page "Demos" (slug `demos`), child Page "Audio Visualizer"
 * (slug `audio`, parent = Demos), pick the "Demo — Audio Visualizer"
 * template. Final URL: /demos/audio/.
 *
 * Note: AudioContext must be created on a user gesture (a click). The "Start"
 * button does that — autoplay would be silently blocked otherwise.
 */
?>
<!-- wp:group {"align":"full","className":"content-page demo-audio","style":{"spacing":{"padding":{"top":"var:preset|spacing|70","bottom":"var:preset|spacing|70","left":"var:preset|spacing|30","right":"var:preset|spacing|30"}}},"layout":{"type":"constrained"}} -->
<div class="wp-block-group alignfull content-page demo-audio" style="padding-top:var(--wp--preset--spacing--70);padding-right:var(--wp--preset--spacing--30);padding-bottom:var(--wp--preset--spacing--70);padding-left:var(--wp--preset--spacing--30)">

    <!-- wp:paragraph {"className":"eyebrow"} -->
    <p class="eyebrow">web audio + canvas</p>
    <!-- /wp:paragraph -->

    <!-- wp:heading {"level":1,"className":"has-gradient-text","style":{"spacing":{"margin":{"top":"var:preset|spacing|20","bottom":"var:preset|spacing|20"}}}} -->
    <h1 class="wp-block-heading has-gradient-text" style="margin-top:var(--wp--preset--spacing--20);margin-bottom:var(--wp--preset--spacing--20)">Audio Visualizer</h1>
    <!-- /wp:heading -->

    <!-- wp:paragraph {"textColor":"muted","style":{"typography":{"lineHeight":"1.55"},"spacing":{"margin":{"bottom":"var:preset|spacing|40"}}}} -->
    <p class="has-muted-color has-text-color" style="line-height:1.55;margin-bottom:var(--wp--preset--spacing--40)">A signal source (an oscillator or your microphone) goes into an <code>AnalyserNode</code>, and a <code>requestAnimationFrame</code> loop reads its FFT bins and paints them onto a <code>&lt;canvas&gt;</code>. Pick a waveform, drag the slider, and switch between the visualizations. <strong>I would suggest headphones</strong> if you turn the volume up.</p>
    <!-- /wp:paragraph -->

    <!-- wp:html -->
    <div class="av">
        <div class="av-stage">
            <canvas class="av-canvas" data-role="canvas" width="900" height="360" role="img" aria-label="Live visualization of the audio signal, drawn as bars, a waveform, or a ring."></canvas>
            <div class="av-stage-overlay" data-role="overlay">
                <div class="av-stage-msg">
                    <div class="av-stage-icon">🎛️</div>
                    <p>Press <strong>Start</strong> to fire up the AudioContext.</p>
                    <p class="av-stage-sub">(Browsers require a user click before audio can play.)</p>
                </div>
            </div>
            <p class="av-sr" role="status" aria-live="polite" data-role="live"></p>
        </div>

        <div class="av-controls">
            <div class="av-row">
                <button type="button" class="av-btn av-btn--primary" data-action="toggle">▶ Start</button>
                <div class="av-mode" role="group" aria-label="Visualization style">
                    <button type="button" class="av-mode-btn is-on" data-mode="bars" aria-pressed="true">Bars</button>
                    <button type="button" class="av-mode-btn"       data-mode="wave" aria-pressed="false">Wave</button>
                    <button type="button" class="av-mode-btn"       data-mode="ring" aria-pressed="false">Ring</button>
                </div>
            </div>

            <div class="av-row">
                <div class="av-source" role="radiogroup" aria-label="Audio source">
                    <label class="av-radio">
                        <input type="radio" name="av-src" value="osc" checked>
                        <span>Oscillator</span>
                    </label>
                    <label class="av-radio">
                        <input type="radio" name="av-src" value="mic">
                        <span>Microphone</span>
                    </label>
                </div>
                <p class="av-hint">Microphone audio stays in your browser. It isn't recorded or sent anywhere.</p>
            </div>

            <div class="av-row av-row--osc" data-role="osc-controls">
                <div class="av-knob">
                    <label for="av-osc-type">Wave</label>
                    <select id="av-osc-type" data-role="osc-type">
                        <option value="sine">sine</option>
                        <option value="square">square</option>
                        <option value="sawtooth">saw</option>
                        <option value="triangle">triangle</option>
                    </select>
                </div>
                <div class="av-knob av-knob--wide">
                    <label for="av-freq">Frequency <span data-role="freq-out">220</span> Hz</label>
                    <input id="av-freq" type="range" min="60" max="1200" step="1" value="220" data-role="freq">
                </div>
                <div class="av-knob av-knob--wide">
                    <label for="av-vol">Volume <span data-role="vol-out">0.30</span></label>
                    <input id="av-vol" type="range" min="0" max="1" step="0.01" value="0.30" data-role="vol">
                </div>
            </div>

            <p class="av-hint" data-role="hint">Tip: try a <strong>square</strong> wave around 110 Hz with the Bars view.</p>
        </div>
    </div>
    <!-- /wp:html -->

</div>
<!-- /wp:group -->

<!-- wp:html -->
<style>
.demo-audio {
    --av-bg: #12100c;
    --av-surface: #1b1712;
    --av-stage: #14110d;
    --av-accent: #f43f5e;
    --av-accent-2: #e11d48;
}

.demo-audio .av {
    margin-top: 1rem;
    background: var(--wp--preset--color--surface);
    border: 1px solid var(--wp--preset--color--subtle);
    border-radius: 16px;
    padding: 1.5rem;
}

.demo-audio .av-stage {
    position: relative;
    background: var(--av-stage);
    border: 1px solid var(--wp--preset--color--subtle);
    border-radius: 12px;
    overflow: hidden;
    aspect-ratio: 5 / 2;
}
.demo-audio .av-canvas {
    display: block;
    width: 100%;
    height: 100%;
}
.demo-audio .av-stage-overlay {
    position: absolute;
    inset: 0;
    display: flex;
    align-items: center;
    justify-content: center;
    background: color-mix(in srgb, var(--av-stage) 85%, transparent);
    -webkit-backdrop-filter: blur(2px);
    backdrop-filter: blur(2px);
    transition: opacity .3s ease;
    text-align: center;
    padding: 1rem;
}
.demo-audio .av-stage-overlay[hidden] { display: none; }
.demo-audio .av-stage-icon { font-size: 2.5rem; margin-bottom: 0.4rem; }
.demo-audio .av-stage-msg p { margin: 0.3rem 0; color: var(--wp--preset--color--foreground); }
.demo-audio .av-stage-sub { color: var(--wp--preset--color--muted) !important; font-size: 0.88rem; }

/* --- Controls --- */
.demo-audio .av-controls {
    margin-top: 1.25rem;
    display: flex;
    flex-direction: column;
    gap: 1rem;
}
.demo-audio .av-row {
    display: flex;
    align-items: center;
    gap: 0.85rem;
    flex-wrap: wrap;
}

.demo-audio .av-btn {
    cursor: pointer;
    border: 1px solid var(--wp--preset--color--subtle);
    background: var(--wp--preset--color--surface);
    color: var(--wp--preset--color--foreground);
    border-radius: 999px;
    padding: 0.7rem 1.4rem;
    font-weight: 700;
    font-size: 0.95rem;
    transition: filter .15s ease, border-color .15s ease;
}
.demo-audio .av-btn--primary {
    background: linear-gradient(135deg, var(--av-accent), var(--av-accent-2));
    border-color: transparent;
    color: #fff;
    min-width: 130px;
}
.demo-audio .av-btn:hover { filter: brightness(1.1); }
.demo-audio .av-btn:disabled { opacity: 0.6; cursor: progress; filter: none; }

.demo-audio .av-mode {
    display: inline-flex;
    background: var(--av-surface);
    border: 1px solid var(--wp--preset--color--subtle);
    border-radius: 999px;
    padding: 4px;
}
.demo-audio .av-mode-btn {
    cursor: pointer;
    background: transparent;
    border: none;
    color: var(--wp--preset--color--muted);
    padding: 0.5rem 1rem;
    border-radius: 999px;
    font-size: 0.88rem;
    font-weight: 600;
    transition: background-color .15s ease, color .15s ease;
}
.demo-audio .av-mode-btn.is-on {
    background: linear-gradient(135deg, color-mix(in srgb, var(--av-accent) 40%, transparent), color-mix(in srgb, var(--av-accent-2) 40%, transparent));
    color: var(--wp--preset--color--foreground);
}

.demo-audio .av-source {
    display: inline-flex;
    gap: 1.2rem;
    color: var(--wp--preset--color--muted);
    font-size: 0.92rem;
}
.demo-audio .av-radio {
    display: inline-flex;
    align-items: center;
    gap: 0.45rem;
    cursor: pointer;
}
.demo-audio .av-radio input { accent-color: var(--wp--preset--color--accent); }

.demo-audio .av-knob {
    display: flex;
    flex-direction: column;
    gap: 0.3rem;
    min-width: 130px;
}
.demo-audio .av-knob--wide { flex: 1 1 220px; }
.demo-audio .av-knob label {
    font-family: var(--wp--preset--font-family--mono);
    font-size: 0.78rem;
    text-transform: uppercase;
    letter-spacing: 0.06em;
    color: var(--wp--preset--color--muted);
}
.demo-audio .av-knob label span { color: var(--wp--preset--color--accent-soft); }
.demo-audio .av-knob select,
.demo-audio .av-knob input[type="range"] {
    background: var(--av-surface);
    border: 1px solid var(--wp--preset--color--subtle);
    border-radius: 8px;
    color: var(--wp--preset--color--foreground);
    padding: 0.5rem 0.6rem;
    font-family: inherit;
    font-size: 0.92rem;
}
.demo-audio .av-knob input[type="range"] {
    padding: 0;
    accent-color: var(--wp--preset--color--accent);
}

/* Visually hidden, still announced. Mirrors the status overlay for screen readers. */
.demo-audio .av-sr {
    position: absolute;
    width: 1px;
    height: 1px;
    margin: -1px;
    padding: 0;
    overflow: hidden;
    clip-path: inset(50%);
    white-space: nowrap;
    border: 0;
}

.demo-audio .av-hint {
    font-size: 0.85rem;
    color: var(--wp--preset--color--muted);
    margin: 0;
}
</style>

<script>
(function () {
    var root = document.querySelector('.demo-audio .av');
    if (!root) return;

    var canvas  = root.querySelector('[data-role="canvas"]');
    var ctx2d   = canvas.getContext('2d');
    var overlay = root.querySelector('[data-role="overlay"]');
    var toggle  = root.querySelector('[data-action="toggle"]');
    var modeBtns= root.querySelectorAll('.av-mode-btn');
    var srcRad  = root.querySelectorAll('input[name="av-src"]');
    var oscCtl  = root.querySelector('[data-role="osc-controls"]');
    var oscType = root.querySelector('[data-role="osc-type"]');
    var freq    = root.querySelector('[data-role="freq"]');
    var freqOut = root.querySelector('[data-role="freq-out"]');
    var vol     = root.querySelector('[data-role="vol"]');
    var volOut  = root.querySelector('[data-role="vol-out"]');
    var hint    = root.querySelector('[data-role="hint"]');
    var live    = root.querySelector('[data-role="live"]');

    var ctx, analyser, gain, osc, micStream, micNode;
    var freqData, timeData;
    var raf = 0;
    var mode = 'bars';
    var running = false;
    var starting = false;
    var source = 'osc';

    /* Canvas resize: redraw at the device pixel ratio so bars stay crisp on
       hi-DPI screens. Recompute on window resize. */
    function fitCanvas() {
        var dpr = window.devicePixelRatio || 1;
        var rect = canvas.getBoundingClientRect();
        canvas.width  = Math.round(rect.width  * dpr);
        canvas.height = Math.round(rect.height * dpr);
        ctx2d.setTransform(dpr, 0, 0, dpr, 0, 0);
    }
    fitCanvas();
    window.addEventListener('resize', fitCanvas);

    function ensureCtx() {
        if (ctx) return ctx;
        var AC = window.AudioContext || window.webkitAudioContext;
        if (!AC) throw new Error('Web Audio API not supported in this browser.');
        ctx = new AC();
        analyser = ctx.createAnalyser();
        analyser.fftSize = 1024;
        analyser.smoothingTimeConstant = 0.78;
        freqData = new Uint8Array(analyser.frequencyBinCount);
        timeData = new Uint8Array(analyser.frequencyBinCount);
        return ctx;
    }

    function startOscillator() {
        ensureCtx();
        gain = ctx.createGain();
        gain.gain.value = parseFloat(vol.value);
        osc = ctx.createOscillator();
        osc.type = oscType.value;
        osc.frequency.value = parseFloat(freq.value);
        osc.connect(gain).connect(analyser).connect(ctx.destination);
        osc.start();
    }
    function stopOscillator() {
        if (osc) { try { osc.stop(); } catch (e) {} osc.disconnect(); osc = null; }
        if (gain) { gain.disconnect(); gain = null; }
    }

    async function startMic() {
        ensureCtx();
        if (!navigator.mediaDevices) throw new Error('Your browser only offers the microphone on secure (https) pages, and this one is not.');
        if (!navigator.mediaDevices.getUserMedia) throw new Error('This browser does not support microphone input.');
        try {
            // Echo cancellation, noise suppression and auto gain are for phone calls.
            // They would flatten the whistles and claps this demo is meant to show.
            micStream = await navigator.mediaDevices.getUserMedia({
                audio: { echoCancellation: false, noiseSuppression: false, autoGainControl: false }
            });
        } catch (err) {
            throw new Error('Microphone access was denied or unavailable.');
        }
        micNode = ctx.createMediaStreamSource(micStream);
        // Critical: do NOT connect to ctx.destination — that would feed mic
        // straight back into the speakers and produce a feedback howl.
        micNode.connect(analyser);
    }
    function stopMic() {
        if (micNode) { micNode.disconnect(); micNode = null; }
        if (micStream) {
            micStream.getTracks().forEach(function (t) { t.stop(); });
            micStream = null;
        }
    }

    async function start() {
        // Starting takes a moment (the mic permission prompt can sit open),
        // and running is only true afterwards, so guard the gap.
        if (starting) return;
        if (running) return;
        starting = true;
        var hadFocus = document.activeElement === toggle;
        toggle.disabled = true;
        srcRad.forEach(function (r) { r.disabled = true; });
        try {
            ensureCtx();
            if (ctx.state === 'suspended') await ctx.resume();
            if (source === 'osc') startOscillator();
            else                   await startMic();
            overlay.hidden = true;
            toggle.textContent = '■ Stop';
            running = true;
            setLive('Audio started.');
            loop();
        } catch (err) {
            stopOscillator();
            stopMic();
            overlay.hidden = false;
            overlay.querySelector('.av-stage-msg').innerHTML =
                '<div class="av-stage-icon">⚠️</div><p>' + err.message + '</p>';
            setLive(err.message);
        }
        starting = false;
        toggle.disabled = false;
        srcRad.forEach(function (r) { r.disabled = false; });
        if (hadFocus) toggle.focus();
    }

    /* Mirror state changes into the hidden live region. Clearing first makes
       a repeated message announce again. */
    function setLive(msg) {
        live.textContent = '';
        live.textContent = msg;
    }

    function stop() {
        running = false;
        cancelAnimationFrame(raf);
        stopOscillator();
        stopMic();
        toggle.textContent = '▶ Start';
        overlay.hidden = false;
        overlay.querySelector('.av-stage-msg').innerHTML =
            '<div class="av-stage-icon">🎛️</div>'
            + '<p>Press <strong>Start</strong> to fire up the AudioContext.</p>'
            + '<p class="av-stage-sub">(Browsers require a user click before audio can play.)</p>';
        ctx2d.clearRect(0, 0, canvas.clientWidth, canvas.clientHeight);
        setLive('Audio stopped.');
    }

    /* --- Drawing routines, one per mode. All read from analyser fresh. --- */
    /* brand-fidelity — visualizer spectrum, warm ramp (deep pink to Sticky Yellow) intentional, do not tokenize */
    function drawBars() {
        analyser.getByteFrequencyData(freqData);
        var w = canvas.clientWidth, h = canvas.clientHeight;
        ctx2d.clearRect(0, 0, w, h);
        // Use ~half the FFT bins (roughly 0–11kHz at 44.1k) — gives nicer spread.
        var bins = Math.floor(freqData.length * 0.6);
        var bw = w / bins;
        // One gradient for the whole frame: loud bars reach the yellow end.
        var grad = ctx2d.createLinearGradient(0, h, 0, 0);
        grad.addColorStop(0,   '#e11d48');
        grad.addColorStop(0.6, '#f43f5e');
        grad.addColorStop(1,   '#facc15');
        ctx2d.fillStyle = grad;
        for (var i = 0; i < bins; i++) {
            var v = freqData[i] / 255;
            var bh = v * h;
            ctx2d.fillRect(i * bw + 1, h - bh, Math.max(1, bw - 1.5), bh);
        }
    }

    function drawWave() {
        analyser.getByteTimeDomainData(timeData);
        var w = canvas.clientWidth, h = canvas.clientHeight;
        ctx2d.clearRect(0, 0, w, h);
        ctx2d.lineWidth = 2;
        var grad = ctx2d.createLinearGradient(0, 0, w, 0);
        grad.addColorStop(0,   '#e11d48');
        grad.addColorStop(0.5, '#f43f5e');
        grad.addColorStop(1,   '#facc15');
        ctx2d.strokeStyle = grad;
        ctx2d.shadowColor = 'rgba(244,63,94,0.55)';
        ctx2d.shadowBlur  = 12;
        ctx2d.beginPath();
        var step = w / timeData.length;
        for (var i = 0; i < timeData.length; i++) {
            var x = i * step;
            var y = (timeData[i] / 255) * h;
            if (i === 0) ctx2d.moveTo(x, y); else ctx2d.lineTo(x, y);
        }
        ctx2d.stroke();
        ctx2d.shadowBlur = 0;
    }

    function drawRing() {
        analyser.getByteFrequencyData(freqData);
        var w = canvas.clientWidth, h = canvas.clientHeight;
        ctx2d.clearRect(0, 0, w, h);
        var cx = w / 2, cy = h / 2;
        var r0 = Math.min(w, h) * 0.18;
        var bins = 96;
        for (var i = 0; i < bins; i++) {
            var v = freqData[Math.floor(i * freqData.length / bins / 2)] / 255;
            var angle = (i / bins) * Math.PI * 2 - Math.PI / 2;
            var len = r0 + v * Math.min(w, h) * 0.32;
            var x1 = cx + Math.cos(angle) * r0;
            var y1 = cy + Math.sin(angle) * r0;
            var x2 = cx + Math.cos(angle) * len;
            var y2 = cy + Math.sin(angle) * len;
            ctx2d.lineWidth = 3;
            ctx2d.strokeStyle = 'hsl(' + ((350 + i * 0.65) % 360) + ', 90%, ' + (55 + v * 25) + '%)';
            ctx2d.beginPath();
            ctx2d.moveTo(x1, y1);
            ctx2d.lineTo(x2, y2);
            ctx2d.stroke();
        }
    }

    function loop() {
        if (!running) return;
        if      (mode === 'bars') drawBars();
        else if (mode === 'wave') drawWave();
        else                       drawRing();
        raf = requestAnimationFrame(loop);
    }

    /* --- Wire up controls --- */
    toggle.addEventListener('click', function () {
        if (running) stop(); else start();
    });

    modeBtns.forEach(function (b) {
        b.addEventListener('click', function () {
            modeBtns.forEach(function (x) { x.classList.remove('is-on'); });
            modeBtns.forEach(function (x) { x.setAttribute('aria-pressed', 'false'); });
            b.classList.add('is-on');
            b.setAttribute('aria-pressed', 'true');
            mode = b.dataset.mode;
        });
    });

    srcRad.forEach(function (r) {
        r.addEventListener('change', function () {
            source = r.value;
            oscCtl.style.display = source === 'osc' ? 'flex' : 'none';
            hint.innerHTML = source === 'osc'
                ? 'Tip: try a <strong>square</strong> wave around 110 Hz with the Bars view.'
                : '<strong>Mic mode:</strong> talk, clap, whistle. Mic input is never sent to your speakers. No feedback.';
            if (running) { stop(); start(); }
        });
    });

    oscType.addEventListener('change', function () {
        if (osc) osc.type = oscType.value;
    });
    freq.addEventListener('input', function () {
        freqOut.textContent = freq.value;
        if (osc) osc.frequency.setTargetAtTime(parseFloat(freq.value), ctx.currentTime, 0.01);
    });
    vol.addEventListener('input', function () {
        volOut.textContent = parseFloat(vol.value).toFixed(2);
        if (gain) gain.gain.setTargetAtTime(parseFloat(vol.value), ctx.currentTime, 0.01);
    });

    /* Stop the audio if the user navigates away — prevents a tone playing
       in a backgrounded tab forever. */
    // NOTE: avoid `&&` here — WordPress's content filter encodes consecutive
    // ampersands to `&#038;&#038;` even inside <script>, breaking the JS.
    document.addEventListener('visibilitychange', function () {
        if (document.hidden) { if (running) stop(); }
    });
})();
</script>
<!-- /wp:html -->
