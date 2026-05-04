<?php
/**
 * Title: Demo — Weather API Playground
 * Slug: dariobimbi/demo-weather
 * Categories: dariobimbi
 * Description: Live weather lookup using the Open-Meteo API (free, no auth, CORS-friendly). Shows a two-step API chain: geocode the city name, then fetch the forecast. Pretty-printed JSON viewer underneath so visitors can see the actual API response.
 *
 * To use: parent Page "Demos" (slug `demos`), child Page "Weather" (slug
 * `weather`, parent = Demos), pick the "Demo — Weather API Playground"
 * template. Final URL: /demos/weather/.
 *
 * APIs used:
 *   - https://geocoding-api.open-meteo.com/v1/search?name=...
 *   - https://api.open-meteo.com/v1/forecast?latitude=...&longitude=...&...
 * Both are free, keyless, and CORS-enabled — no proxy needed.
 */
?>
<!-- wp:group {"align":"full","className":"content-page demo-weather","style":{"spacing":{"padding":{"top":"var:preset|spacing|70","bottom":"var:preset|spacing|70","left":"var:preset|spacing|30","right":"var:preset|spacing|30"}}},"layout":{"type":"constrained"}} -->
<div class="wp-block-group alignfull content-page demo-weather" style="padding-top:var(--wp--preset--spacing--70);padding-right:var(--wp--preset--spacing--30);padding-bottom:var(--wp--preset--spacing--70);padding-left:var(--wp--preset--spacing--30)">

    <!-- wp:paragraph {"className":"eyebrow"} -->
    <p class="eyebrow">a real api, a real fetch chain</p>
    <!-- /wp:paragraph -->

    <!-- wp:heading {"level":1,"className":"has-gradient-text","style":{"spacing":{"margin":{"top":"var:preset|spacing|20","bottom":"var:preset|spacing|20"}}}} -->
    <h1 class="wp-block-heading has-gradient-text" style="margin-top:var(--wp--preset--spacing--20);margin-bottom:var(--wp--preset--spacing--20)">Weather API Playground</h1>
    <!-- /wp:heading -->

    <!-- wp:paragraph {"textColor":"muted","style":{"typography":{"lineHeight":"1.55"},"spacing":{"margin":{"bottom":"var:preset|spacing|40"}}}} -->
    <p class="has-muted-color has-text-color" style="line-height:1.55;margin-bottom:var(--wp--preset--spacing--40)">Type in a city, and add a state or country if you want to narrow it down (the format is <code>City, ST, CC</code>). The page asks <a href="https://open-meteo.com/" target="_blank" rel="noopener">Open-Meteo</a> to geocode the name, picks the right match from the results, and then asks the same API for a 7-day forecast. There is no backend, no library, and no API key needed. Just <code>fetch()</code> and <code>async/await</code>, with a small bit of code that handles errors so the page does not break when something goes wrong. The JSON pane below shows the raw response from the API.</p>
    <!-- /wp:paragraph -->

    <!-- wp:html -->
    <div class="wx" data-default-query="Saint Charles, IL, US">
        <form class="wx-form" data-role="form" autocomplete="off">
            <label class="wx-search">
                <span class="wx-label">Search city, state, country</span>
                <input type="text" class="wx-input" data-role="input" placeholder="Saint Charles, IL, US" value="Saint Charles, IL, US" spellcheck="false" required>
                <span class="wx-hint">Use commas to narrow it down, e.g. <code>Springfield, MO, US</code>. State and country are optional.</span>
            </label>
            <button type="submit" class="wx-btn wx-btn--primary">
                <span data-role="btn-label">Search</span>
                <span class="wx-spin" data-role="spin" aria-hidden="true"></span>
            </button>
        </form>

        <div class="wx-suggest">
            <span class="wx-suggest-label">try:</span>
            <button type="button" class="wx-chip" data-suggest="Saint Charles, IL, US">Saint Charles, IL</button>
            <button type="button" class="wx-chip" data-suggest="Springfield, MO, US">Springfield, MO</button>
            <button type="button" class="wx-chip" data-suggest="Paris, FR">Paris, FR</button>
            <button type="button" class="wx-chip" data-suggest="London, ON, CA">London, ON</button>
            <button type="button" class="wx-chip" data-suggest="Tokyo, JP">Tokyo, JP</button>
            <button type="button" class="wx-chip" data-suggest="Reykjavík, IS">Reykjavík, IS</button>
        </div>

        <div class="wx-result" data-role="result" aria-live="polite">
            <div class="wx-empty">
                <div class="wx-empty-icon">⛅</div>
                <p>Loading the default forecast…</p>
            </div>
        </div>

        <details class="wx-json">
            <summary>View raw API response (JSON)</summary>
            <div class="wx-json-meta">
                <span class="wx-json-label">Last call:</span>
                <code class="wx-json-url" data-role="json-url">·</code>
            </div>
            <pre class="wx-json-body"><code data-role="json-body">// Run a search to see the raw forecast JSON here.</code></pre>
        </details>
    </div>
    <!-- /wp:html -->

</div>
<!-- /wp:group -->

<!-- wp:html -->
<style>
/* All styles scoped to .demo-weather to avoid leaking into the rest of the site. */

.demo-weather .wx {
    margin-top: 1rem;
    background: var(--wp--preset--color--surface);
    border: 1px solid var(--wp--preset--color--subtle);
    border-radius: 16px;
    padding: 1.75rem;
}

/* --- Search form --- */
.demo-weather .wx-form {
    display: flex;
    gap: 0.75rem;
    align-items: flex-end;
    flex-wrap: wrap;
}
.demo-weather .wx-search {
    display: flex;
    flex-direction: column;
    gap: 0.4rem;
    flex: 1 1 280px;
}
.demo-weather .wx-label {
    font-family: var(--wp--preset--font-family--mono);
    font-size: 0.78rem;
    text-transform: uppercase;
    letter-spacing: 0.06em;
    color: var(--wp--preset--color--muted);
}
.demo-weather .wx-input {
    background: #0a0e1a;
    border: 1px solid var(--wp--preset--color--subtle);
    border-radius: 10px;
    padding: 0.85rem 1rem;
    font-size: max(1rem, 16px); /* avoid iOS zoom */
    font-family: var(--wp--preset--font-family--system);
    color: var(--wp--preset--color--foreground);
    transition: border-color .15s ease;
}
.demo-weather .wx-input:focus {
    outline: none;
    border-color: var(--wp--preset--color--primary);
}
.demo-weather .wx-hint {
    font-size: 0.78rem;
    color: var(--wp--preset--color--muted);
    margin-top: 0.15rem;
}
.demo-weather .wx-hint code {
    background: #050813;
    border: 1px solid var(--wp--preset--color--subtle);
    padding: 0 0.35rem;
    border-radius: 4px;
    font-size: 0.78rem;
}

.demo-weather .wx-btn {
    cursor: pointer;
    border: 1px solid var(--wp--preset--color--subtle);
    background: var(--wp--preset--color--surface);
    color: var(--wp--preset--color--foreground);
    border-radius: 999px;
    padding: 0.85rem 1.5rem;
    font-weight: 700;
    font-size: 0.95rem;
    display: inline-flex;
    align-items: center;
    gap: 0.55rem;
    transition: background-color .15s ease, border-color .15s ease;
}
.demo-weather .wx-btn--primary {
    background: linear-gradient(135deg, #3b82f6, #a855f7);
    border-color: transparent;
    color: #fff;
}
.demo-weather .wx-btn:hover { filter: brightness(1.1); }
.demo-weather .wx-btn[disabled] { opacity: 0.6; cursor: progress; }

.demo-weather .wx-spin {
    width: 14px;
    height: 14px;
    border-radius: 50%;
    border: 2px solid rgba(255,255,255,0.35);
    border-top-color: #fff;
    animation: wx-spin 0.7s linear infinite;
    display: none;
}
.demo-weather .wx-btn[data-loading="1"] .wx-spin { display: inline-block; }
@keyframes wx-spin { to { transform: rotate(360deg); } }

/* --- Suggestion chips --- */
.demo-weather .wx-suggest {
    margin-top: 0.85rem;
    display: flex;
    flex-wrap: wrap;
    gap: 0.4rem;
    align-items: center;
}
.demo-weather .wx-suggest-label {
    font-family: var(--wp--preset--font-family--mono);
    font-size: 0.78rem;
    text-transform: uppercase;
    letter-spacing: 0.06em;
    color: var(--wp--preset--color--muted);
    margin-right: 0.25rem;
}
.demo-weather .wx-chip {
    cursor: pointer;
    background: transparent;
    border: 1px solid var(--wp--preset--color--subtle);
    color: var(--wp--preset--color--muted);
    padding: 0.3rem 0.7rem;
    border-radius: 999px;
    font-size: 0.85rem;
    transition: border-color .15s ease, color .15s ease, background-color .15s ease;
}
.demo-weather .wx-chip:hover {
    border-color: var(--wp--preset--color--primary);
    color: var(--wp--preset--color--primary-soft);
}

/* --- Result area --- */
.demo-weather .wx-result {
    margin-top: 1.75rem;
    min-height: 220px;
}
.demo-weather .wx-empty {
    text-align: center;
    color: var(--wp--preset--color--muted);
    padding: 2.5rem 1rem;
    border: 1px dashed var(--wp--preset--color--subtle);
    border-radius: 12px;
}
.demo-weather .wx-empty-icon {
    font-size: 2.4rem;
    margin-bottom: 0.4rem;
}

.demo-weather .wx-error {
    text-align: center;
    color: #fda4af;
    background: rgba(244,63,94,0.08);
    border: 1px solid rgba(244,63,94,0.35);
    border-radius: 12px;
    padding: 1.25rem;
}

/* "Now" card — gradient background, hero-sized temp */
.demo-weather .wx-now {
    background: linear-gradient(135deg, rgba(59,130,246,0.18), rgba(168,85,247,0.18));
    border: 1px solid rgba(168,85,247,0.35);
    border-radius: 14px;
    padding: 1.5rem 1.75rem;
    display: flex;
    align-items: center;
    gap: 1.5rem;
    flex-wrap: wrap;
    animation: wx-fade .35s ease-out;
}
.demo-weather .wx-now-icon {
    font-size: 4.5rem;
    line-height: 1;
}
.demo-weather .wx-now-body { flex: 1; min-width: 200px; }
.demo-weather .wx-now-city {
    font-size: 1.35rem;
    font-weight: 700;
    margin: 0 0 0.15rem;
    color: var(--wp--preset--color--foreground);
}
.demo-weather .wx-now-region {
    font-size: 0.88rem;
    color: var(--wp--preset--color--muted);
    margin: 0 0 0.6rem;
}
.demo-weather .wx-now-temp {
    font-size: 3rem;
    font-weight: 800;
    line-height: 1;
    color: var(--wp--preset--color--foreground);
    letter-spacing: -0.02em;
}
.demo-weather .wx-now-temp small {
    font-size: 1rem;
    font-weight: 400;
    color: var(--wp--preset--color--muted);
    margin-left: 0.25rem;
}
.demo-weather .wx-now-summary {
    font-size: 1rem;
    color: var(--wp--preset--color--foreground);
    margin: 0.4rem 0 0;
}
.demo-weather .wx-now-meta {
    display: flex;
    gap: 1.2rem;
    flex-wrap: wrap;
    margin-top: 0.6rem;
    font-size: 0.85rem;
    color: var(--wp--preset--color--muted);
}
.demo-weather .wx-now-meta strong {
    color: var(--wp--preset--color--foreground);
    font-weight: 600;
    margin-left: 0.3rem;
}

/* 7-day forecast strip */
.demo-weather .wx-week {
    margin-top: 1.25rem;
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(110px, 1fr));
    gap: 0.6rem;
}
.demo-weather .wx-day {
    background: #0a0e1a;
    border: 1px solid var(--wp--preset--color--subtle);
    border-radius: 10px;
    padding: 0.85rem 0.5rem;
    text-align: center;
    transition: border-color .15s ease, transform .15s ease;
}
.demo-weather .wx-day:hover {
    border-color: var(--wp--preset--color--primary);
    transform: translateY(-2px);
}
.demo-weather .wx-day-name {
    font-family: var(--wp--preset--font-family--mono);
    font-size: 0.72rem;
    text-transform: uppercase;
    letter-spacing: 0.06em;
    color: var(--wp--preset--color--muted);
    margin: 0 0 0.4rem;
}
.demo-weather .wx-day-icon { font-size: 1.7rem; line-height: 1; }
.demo-weather .wx-day-temps {
    margin-top: 0.4rem;
    font-size: 0.92rem;
    color: var(--wp--preset--color--foreground);
}
.demo-weather .wx-day-temps .lo {
    color: var(--wp--preset--color--muted);
    margin-left: 0.3rem;
}

@keyframes wx-fade {
    from { opacity: 0; transform: translateY(8px); }
    to   { opacity: 1; transform: none; }
}

/* --- Raw JSON pane --- */
.demo-weather .wx-json {
    margin-top: 1.5rem;
    background: #0a0e1a;
    border: 1px solid var(--wp--preset--color--subtle);
    border-radius: 12px;
    padding: 0.9rem 1rem;
}
.demo-weather .wx-json summary {
    cursor: pointer;
    font-family: var(--wp--preset--font-family--mono);
    font-size: 0.85rem;
    color: var(--wp--preset--color--muted);
    list-style: none;
}
.demo-weather .wx-json summary::-webkit-details-marker { display: none; }
.demo-weather .wx-json summary::before {
    content: "▸ ";
    transition: transform .15s ease;
    display: inline-block;
}
.demo-weather .wx-json[open] summary::before { content: "▾ "; }
.demo-weather .wx-json-meta {
    margin-top: 0.65rem;
    font-family: var(--wp--preset--font-family--mono);
    font-size: 0.78rem;
    color: var(--wp--preset--color--muted);
    word-break: break-all;
}
.demo-weather .wx-json-label { color: var(--wp--preset--color--muted); margin-right: 0.4rem; }
.demo-weather .wx-json-url { color: var(--wp--preset--color--accent-soft); }
.demo-weather .wx-json-body {
    margin: 0.6rem 0 0;
    padding: 0.85rem 1rem;
    background: #050813;
    border: 1px solid var(--wp--preset--color--subtle);
    border-radius: 8px;
    font-family: var(--wp--preset--font-family--mono);
    font-size: 0.78rem;
    color: #e8ecf6;
    max-height: 320px;
    overflow: auto;
    white-space: pre-wrap;
    word-break: break-word;
}
.demo-weather .wx-json-body .jk { color: #61afef; }
.demo-weather .wx-json-body .js { color: #98c379; }
.demo-weather .wx-json-body .jn { color: #d19a66; }
.demo-weather .wx-json-body .jb { color: #c678dd; }
.demo-weather .wx-json-body .jl { color: #8a93a6; font-style: italic; }

@media (prefers-reduced-motion: reduce) {
    .demo-weather .wx-now { animation: none; }
    .demo-weather .wx-spin { animation-duration: 2s; }
}
</style>

<script>
(function () {
    var root = document.querySelector('.demo-weather .wx');
    if (!root) return;

    var form    = root.querySelector('[data-role="form"]');
    var input   = root.querySelector('[data-role="input"]');
    var btn     = form.querySelector('button');
    var btnLbl  = root.querySelector('[data-role="btn-label"]');
    var result  = root.querySelector('[data-role="result"]');
    var jsonUrl = root.querySelector('[data-role="json-url"]');
    var jsonBody= root.querySelector('[data-role="json-body"]');

    /* WMO weather code → emoji + short text. From the Open-Meteo docs. */
    var WMO = {
        0:  ['☀️','Clear sky'],
        1:  ['🌤️','Mostly clear'],
        2:  ['⛅','Partly cloudy'],
        3:  ['☁️','Overcast'],
        45: ['🌫️','Fog'],
        48: ['🌫️','Rime fog'],
        51: ['🌦️','Light drizzle'],
        53: ['🌦️','Drizzle'],
        55: ['🌧️','Heavy drizzle'],
        56: ['🌧️','Freezing drizzle'],
        57: ['🌧️','Heavy freezing drizzle'],
        61: ['🌧️','Light rain'],
        63: ['🌧️','Rain'],
        65: ['🌧️','Heavy rain'],
        66: ['🌧️','Freezing rain'],
        67: ['🌧️','Heavy freezing rain'],
        71: ['🌨️','Light snow'],
        73: ['🌨️','Snow'],
        75: ['❄️','Heavy snow'],
        77: ['🌨️','Snow grains'],
        80: ['🌦️','Rain showers'],
        81: ['🌧️','Heavy rain showers'],
        82: ['⛈️','Violent rain showers'],
        85: ['🌨️','Snow showers'],
        86: ['❄️','Heavy snow showers'],
        95: ['⛈️','Thunderstorm'],
        96: ['⛈️','Thunderstorm w/ hail'],
        99: ['⛈️','Severe thunderstorm']
    };
    function wmo(code) { return WMO[code] || ['🌡️','Unknown ('+code+')']; }

    /* US state two-letter codes → full name. Open-Meteo returns admin1 as a
       full name ("Illinois"), but users typically type "IL". This lets either
       work. Includes DC + the five inhabited US territories. */
    var US_STATES = {
        al:'alabama', ak:'alaska', az:'arizona', ar:'arkansas', ca:'california',
        co:'colorado', ct:'connecticut', de:'delaware', fl:'florida', ga:'georgia',
        hi:'hawaii', id:'idaho', il:'illinois', in:'indiana', ia:'iowa',
        ks:'kansas', ky:'kentucky', la:'louisiana', me:'maine', md:'maryland',
        ma:'massachusetts', mi:'michigan', mn:'minnesota', ms:'mississippi', mo:'missouri',
        mt:'montana', ne:'nebraska', nv:'nevada', nh:'new hampshire', nj:'new jersey',
        nm:'new mexico', ny:'new york', nc:'north carolina', nd:'north dakota', oh:'ohio',
        ok:'oklahoma', or:'oregon', pa:'pennsylvania', ri:'rhode island', sc:'south carolina',
        sd:'south dakota', tn:'tennessee', tx:'texas', ut:'utah', vt:'vermont',
        va:'virginia', wa:'washington', wv:'west virginia', wi:'wisconsin', wy:'wyoming',
        dc:'district of columbia', pr:'puerto rico', vi:'u.s. virgin islands',
        gu:'guam', as:'american samoa', mp:'northern mariana islands'
    };

    /* "City, ST, CC" → { city, region, country }. Any of the trailing parts
       can be omitted. We keep the user's casing for the city (display value)
       but normalize state/country to lowercase for matching. */
    function parseQuery(s) {
        var parts = s.split(',').map(function (p) { return p.trim(); }).filter(Boolean);
        return {
            city:    parts[0] || '',
            region:  (parts[1] || '').toLowerCase(),
            country: (parts[2] || '').toLowerCase()
        };
    }

    /* From a list of geocoder results, pick the one whose admin1 / country
       agrees with the user-supplied filters. Falls back to the top result
       if nothing matches — better than failing outright on a slight mismatch. */
    function pickResult(results, region, country) {
        if (!results) return null;
        if (!results.length) return null;
        if (!region) if (!country) return results[0];

        var stateFull = US_STATES[region] || region; // "il" -> "illinois"

        function matches(r) {
            if (region) {
                var a = (r.admin1 || '').toLowerCase();
                // accept either the typed value or its expanded US state name
                if (a !== region) if (a !== stateFull) return false;
            }
            if (country) {
                var c1 = (r.country || '').toLowerCase();
                var c2 = (r.country_code || '').toLowerCase();
                if (c1 !== country) if (c2 !== country) return false;
            }
            return true;
        }

        for (var i = 0; i < results.length; i++) {
            if (matches(results[i])) return results[i];
        }
        return results[0]; // graceful fallback
    }

    /* Tiny JSON syntax highlighter — wraps strings/numbers/booleans/keys/null
       in spans for the .jk/.js/.jn/.jb/.jl rules above. */
    function escapeHtml(s) {
        // NOTE: avoid literal "&" in source — WordPress's content filter
        // encodes ampersands inside <script> blocks to "&#038;" which would
        // corrupt the regex character class. & is the same code point
        // but no literal ampersand sits in the file.
        var AMP = '&';
        return s.replace(new RegExp('[' + AMP + '<>]', 'g'), function (c) {
            if (c === AMP) return AMP + 'amp;';
            if (c === '<') return AMP + 'lt;';
            return AMP + 'gt;';
        });
    }
    function hilite(json) {
        var pretty = JSON.stringify(json, null, 2);
        pretty = escapeHtml(pretty);
        return pretty.replace(
            /("(\\u[0-9a-fA-F]{4}|\\[^u]|[^\\"])*"(\s*:)?|\b(true|false|null)\b|-?\d+(?:\.\d+)?(?:[eE][+\-]?\d+)?)/g,
            function (m) {
                if (/^"/.test(m)) {
                    return /:$/.test(m)
                        ? '<span class="jk">' + m + '</span>'
                        : '<span class="js">' + m + '</span>';
                }
                if (/true|false/.test(m)) return '<span class="jb">' + m + '</span>';
                if (/null/.test(m))       return '<span class="jl">' + m + '</span>';
                return '<span class="jn">' + m + '</span>';
            }
        );
    }

    function setLoading(on) {
        btn.disabled = !!on;
        btn.dataset.loading = on ? '1' : '0';
        btnLbl.textContent = on ? 'Loading…' : 'Search';
    }

    function showError(msg) {
        result.innerHTML = '<div class="wx-error">' + escapeHtml(msg) + '</div>';
    }

    function fmtDay(iso) {
        var d = new Date(iso + 'T00:00:00');
        return d.toLocaleDateString(undefined, { weekday: 'short' });
    }

    function render(place, fc) {
        var c = fc.current;
        var cur = wmo(c.weather_code);
        var html = '<div class="wx-now">'
            + '<div class="wx-now-icon">' + cur[0] + '</div>'
            + '<div class="wx-now-body">'
                + '<h3 class="wx-now-city">' + escapeHtml(place.name) + '</h3>'
                + '<p class="wx-now-region">' + escapeHtml([place.admin1, place.country].filter(Boolean).join(', ')) + '</p>'
                + '<div class="wx-now-temp">' + Math.round(c.temperature_2m) + '°<small>C</small></div>'
                + '<p class="wx-now-summary">' + cur[1] + '</p>'
                + '<div class="wx-now-meta">'
                    + '<span>Feels like<strong>' + Math.round(c.apparent_temperature) + '°</strong></span>'
                    + '<span>Wind<strong>' + Math.round(c.wind_speed_10m) + ' km/h</strong></span>'
                    + '<span>Humidity<strong>' + Math.round(c.relative_humidity_2m) + '%</strong></span>'
                + '</div>'
            + '</div>'
        + '</div>';

        var d = fc.daily;
        html += '<div class="wx-week">';
        for (var i = 0; i < d.time.length; i++) {
            var dw = wmo(d.weather_code[i]);
            html += '<div class="wx-day">'
                + '<p class="wx-day-name">' + (i === 0 ? 'Today' : fmtDay(d.time[i])) + '</p>'
                + '<div class="wx-day-icon">' + dw[0] + '</div>'
                + '<div class="wx-day-temps">'
                    + '<span class="hi">' + Math.round(d.temperature_2m_max[i]) + '°</span>'
                    + '<span class="lo">' + Math.round(d.temperature_2m_min[i]) + '°</span>'
                + '</div>'
            + '</div>';
        }
        html += '</div>';

        result.innerHTML = html;
    }

    async function search(query) {
        if (!query) return;
        setLoading(true);
        try {
            // 1. Parse + geocode. We send only the city to the geocoder
            //    (Open-Meteo's /search doesn't accept state or country params)
            //    then pick the best of up to 10 candidates client-side.
            var q = parseQuery(query);
            if (!q.city) throw new Error('Type a city name to search.');
            var geoUrl = 'https://geocoding-api.open-meteo.com/v1/search?count=10&language=en&format=json&name=' + encodeURIComponent(q.city);
            var gRes = await fetch(geoUrl);
            if (!gRes.ok) throw new Error('Geocoding API returned ' + gRes.status);
            var gJson = await gRes.json();
            if (!gJson.results) throw new Error('No place matched "' + q.city + '". Try a different spelling?');
            if (!gJson.results.length) throw new Error('No place matched "' + q.city + '". Try a different spelling?');
            var place = pickResult(gJson.results, q.region, q.country);
            if (!place) throw new Error('No match for "' + query + '".');

            // 2. Forecast
            var fxUrl = 'https://api.open-meteo.com/v1/forecast'
                + '?latitude='  + place.latitude
                + '&longitude=' + place.longitude
                + '&timezone=auto'
                + '&current=temperature_2m,relative_humidity_2m,apparent_temperature,weather_code,wind_speed_10m'
                + '&daily=weather_code,temperature_2m_max,temperature_2m_min';
            var fRes = await fetch(fxUrl);
            if (!fRes.ok) throw new Error('Forecast API returned ' + fRes.status);
            var fJson = await fRes.json();

            render(place, fJson);
            jsonUrl.textContent = fxUrl;
            jsonBody.innerHTML  = hilite(fJson);
        } catch (err) {
            showError(err.message || 'Something went wrong fetching the forecast.');
            jsonUrl.textContent = '·';
            jsonBody.innerHTML  = '// ' + escapeHtml(err.message || 'error');
        } finally {
            setLoading(false);
        }
    }

    form.addEventListener('submit', function (e) {
        e.preventDefault();
        search(input.value.trim());
    });

    root.querySelectorAll('[data-suggest]').forEach(function (chip) {
        chip.addEventListener('click', function () {
            input.value = chip.dataset.suggest;
            search(input.value);
        });
    });

    // Auto-run the default search so visitors land on a real forecast,
    // not an empty card. The default value lives on the input itself.
    var initial = input.value.trim() || root.dataset.defaultQuery || '';
    if (initial) search(initial);
})();
</script>
<!-- /wp:html -->
