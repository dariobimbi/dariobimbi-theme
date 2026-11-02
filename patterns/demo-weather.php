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
            <label class="wx-label" for="wx-query">Search city, state, country</label>
            <div class="wx-row">
                <input type="text" id="wx-query" class="wx-input" data-role="input" placeholder="Saint Charles, IL, US" value="Saint Charles, IL, US" spellcheck="false" required>
                <button type="submit" class="wx-btn wx-btn--primary">
                    <span data-role="btn-label">Search</span>
                    <span class="wx-spin" data-role="spin" aria-hidden="true"></span>
                </button>
            </div>
            <p class="wx-hint">Use commas to narrow it down, e.g. <code>Springfield, MO, US</code>. State and country are optional.</p>
        </form>

        <div class="wx-suggest">
            <span class="wx-suggest-label">try:</span>
            <button type="button" class="wx-chip" data-suggest="Saint Charles, IL, US">Saint Charles, IL</button>
            <button type="button" class="wx-chip" data-suggest="Springfield, MO, US">Springfield, MO</button>
            <button type="button" class="wx-chip" data-suggest="Paris, FR">Paris, FR</button>
            <button type="button" class="wx-chip" data-suggest="London, ON, CA">London, ON</button>
            <button type="button" class="wx-chip" data-suggest="Tokyo, JP">Tokyo, JP</button>
            <button type="button" class="wx-chip" data-suggest="Reykjavík, IS">Reykjavík, IS</button>
            <button type="button" class="wx-chip wx-chip--geo" data-role="geo">Use my location</button>
        </div>

        <div class="wx-units" role="group" aria-label="Units">
            <button type="button" class="wx-unit is-on" data-role="unit" data-unit="imperial" aria-pressed="true">°F</button>
            <button type="button" class="wx-unit" data-role="unit" data-unit="metric" aria-pressed="false">°C</button>
        </div>

        <div class="wx-result" data-role="result" aria-live="polite">
            <div class="wx-empty">
                <div class="wx-empty-icon" aria-hidden="true">⛅</div>
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

.demo-weather {
    --wx-bg: #12100c;
    --wx-surface: #1b1712;
    --wx-code-bg: #14110d;
    --wx-fg: #efe9df;
    --wx-accent: #f43f5e;
    --wx-accent-2: #e11d48;
}

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
    flex-direction: column;
    gap: 0.5rem;
}
.demo-weather .wx-row {
    display: flex;
    gap: 0.75rem;
    align-items: stretch; /* input + button share one row at equal height */
    flex-wrap: wrap;
}
.demo-weather .wx-label {
    font-family: var(--wp--preset--font-family--mono);
    font-size: 0.78rem;
    text-transform: uppercase;
    letter-spacing: 0.06em;
    color: var(--wp--preset--color--muted);
}
.demo-weather .wx-input {
    background: var(--wx-bg);
    border: 1px solid var(--wp--preset--color--subtle);
    border-radius: 10px;
    padding: 0.85rem 1rem;
    font-size: max(1rem, 16px); /* avoid iOS zoom */
    font-family: var(--wp--preset--font-family--system);
    color: var(--wp--preset--color--foreground);
    transition: border-color .15s ease;
    flex: 1 1 220px; /* grow to fill the row; shrink before wrapping the button */
    min-width: 0;
    box-sizing: border-box;
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
    background: var(--wx-code-bg);
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
    justify-content: center;
    gap: 0.55rem;
    flex: 0 0 auto; /* keep natural width beside the growing input */
    white-space: nowrap;
    transition: background-color .15s ease, border-color .15s ease;
}
.demo-weather .wx-btn--primary {
    background: linear-gradient(135deg, var(--wx-accent), var(--wx-accent-2));
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
.demo-weather .wx-chip:focus-visible,
.demo-weather .wx-unit:focus-visible,
.demo-weather .wx-btn:focus-visible {
    outline: 2px solid var(--wp--preset--color--primary-soft);
    outline-offset: 2px;
}
.demo-weather .wx-chip[disabled] { opacity: 0.5; cursor: progress; }
.demo-weather .wx-chip--geo {
    border-style: dashed;
    color: var(--wp--preset--color--foreground);
}
.demo-weather .wx-sr {
    position: absolute;
    width: 1px;
    height: 1px;
    margin: -1px;
    padding: 0;
    overflow: hidden;
    clip: rect(0 0 0 0);
    white-space: nowrap;
    border: 0;
}
.demo-weather .wx-units {
    display: flex;
    justify-content: flex-end;
    margin-top: 0.85rem;
}
.demo-weather .wx-unit {
    cursor: pointer;
    border: 1px solid var(--wp--preset--color--subtle);
    background: var(--wp--preset--color--surface);
    color: var(--wp--preset--color--muted);
    padding: 0.35rem 0.9rem;
    font-weight: 700;
    font-size: 0.85rem;
    font-family: var(--wp--preset--font-family--mono);
    transition: background-color .15s ease, color .15s ease;
}
.demo-weather .wx-unit:first-child { border-radius: 999px 0 0 999px; }
.demo-weather .wx-unit:last-child  { border-radius: 0 999px 999px 0; border-left: none; }
.demo-weather .wx-unit.is-on {
    background: linear-gradient(135deg, var(--wx-accent), var(--wx-accent-2));
    border-color: transparent;
    color: #fff;
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

/* brand-fidelity — sky + JSON syntax colors, intentional */
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
    background: var(--wx-surface);
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
    background: var(--wx-surface);
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
    background: var(--wx-code-bg);
    border: 1px solid var(--wp--preset--color--subtle);
    border-radius: 8px;
    font-family: var(--wp--preset--font-family--mono);
    font-size: 0.78rem;
    color: var(--wx-fg);
    max-height: 320px;
    overflow: auto;
    white-space: pre-wrap;
    word-break: break-word;
}
/* brand-fidelity — sky + JSON syntax colors, intentional */
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

    var extraBtns = root.querySelectorAll('.wx-chip');   // suggestion chips + "Use my location"
    var geoBtn  = root.querySelector('[data-role="geo"]');
    var reqId   = 0;                // bumped per request so a slow older one can't overwrite a newer one

    var imperial = true;            // default Imperial (°F, mph)
    var lastPlace = null, lastForecast = null;

    function convTemp(c) {
        var t = imperial ? Math.round(c * 9 / 5 + 32) : Math.round(c);
        return t === 0 ? 0 : t; // Math.round(-0.4) is -0, which prints "-0°"
    }
    function tempUnit() { return imperial ? 'F' : 'C'; }
    function convWind(kmh) { return imperial ? (Math.round(kmh * 0.621371) + ' mph') : (Math.round(kmh) + ' km/h'); }

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
    function wmo(code, isDay) {
        var w = WMO[code] || ['🌡️','Unknown ('+code+')'];
        // Clear and mainly-clear get a moon after dark (is_day is 0 at night).
        if (isDay === 0) {
            if (code === 0) return ['🌙', w[1]];
            if (code === 1) return ['🌙', w[1]];
        }
        return w;
    }

    /* US state and Canadian province two-letter codes → full name. Open-Meteo
       returns admin1 as a full name ("Illinois"), but users typically type
       "IL" or "ON". This lets either work. Includes DC + the five inhabited
       US territories. No code is shared between the two countries. */
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
        gu:'guam', as:'american samoa', mp:'northern mariana islands',
        ab:'alberta', bc:'british columbia', mb:'manitoba', nb:'new brunswick',
        nl:'newfoundland and labrador', ns:'nova scotia', nt:'northwest territories',
        nu:'nunavut', on:'ontario', pe:'prince edward island', qc:'quebec',
        sk:'saskatchewan', yt:'yukon'
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

    /* Geocoder results whose admin1 / country agree with the user-supplied
       filters (either may be empty, meaning "any"). */
    function matchPlaces(results, region, country) {
        var stateFull = US_STATES[region] || region; // "il" -> "illinois", "on" -> "ontario"
        var hits = [];
        for (var i = 0; i < results.length; i++) {
            var r = results[i];
            if (region) {
                var a = (r.admin1 || '').toLowerCase();
                // accept either the typed value or its expanded state/province name
                if (a !== region) if (a !== stateFull) continue;
            }
            if (country) {
                var c1 = (r.country || '').toLowerCase();
                var c2 = (r.country_code || '').toLowerCase();
                if (c1 !== country) if (c2 !== country) continue;
            }
            hits.push(r);
        }
        return hits;
    }

    /* First populated place (feature code PPL*) in a list, or null. The
       geocoder also returns dams, parks and fields with the same name. */
    function firstTown(hits) {
        for (var i = 0; i < hits.length; i++) {
            if (/^PPL/.test(hits[i].feature_code || '')) return hits[i];
        }
        return null;
    }

    /* From a list of geocoder results, pick the one whose admin1 / country
       agrees with the user-supplied filters, preferring real towns. Falls back
       to a country-only match, then the top result, if nothing matches. */
    function pickResult(results, region, country) {
        if (!results) return null;
        if (!results.length) return null;
        if (!region) if (!country) return results[0];

        var hits = matchPlaces(results, region, country);
        if (hits.length) return firstTown(hits) || hits[0];
        if (country) {
            var byCountry = matchPlaces(results, '', country);
            if (byCountry.length) return firstTown(byCountry) || byCountry[0];
        }
        return results[0]; // graceful fallback
    }

    /* Tiny JSON syntax highlighter — wraps strings/numbers/booleans/keys/null
       in spans for the .jk/.js/.jn/.jb/.jl rules above. */
    function escapeHtml(s) {
        // NOTE: avoid a literal ampersand in source. WordPress's content filter
        // encodes ampersands inside <script> blocks to "#038;" entities, which would
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
        Array.prototype.forEach.call(extraBtns, function (b) { b.disabled = !!on; });
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
        lastPlace = place; lastForecast = fc;
        var c = fc.current;
        var cur = wmo(c.weather_code, c.is_day);
        var html = '<div class="wx-now">'
            + '<div class="wx-now-icon" aria-hidden="true">' + cur[0] + '</div>'
            + '<div class="wx-now-body">'
                + '<h3 class="wx-now-city">' + escapeHtml(place.name) + '</h3>'
                + '<p class="wx-now-region">' + escapeHtml([place.admin1, place.country].filter(Boolean).join(', ')) + '</p>'
                + '<div class="wx-now-temp">' + convTemp(c.temperature_2m) + '°<small>' + tempUnit() + '</small></div>'
                + '<p class="wx-now-summary">' + cur[1] + '</p>'
                + '<div class="wx-now-meta">'
                    + '<span>Feels like<strong>' + convTemp(c.apparent_temperature) + '°</strong></span>'
                    + '<span>Wind<strong>' + convWind(c.wind_speed_10m) + '</strong></span>'
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
                + '<div class="wx-day-icon" aria-hidden="true">' + dw[0] + '</div>'
                + '<span class="wx-sr">' + dw[1] + '</span>'
                + '<div class="wx-day-temps">'
                    + '<span class="hi">' + convTemp(d.temperature_2m_max[i]) + '°</span>'
                    + '<span class="lo">' + convTemp(d.temperature_2m_min[i]) + '°</span>'
                + '</div>'
            + '</div>';
        }
        html += '</div>';

        result.innerHTML = html;
    }

    /* fetch + JSON with a 10 s timeout. */
    async function getJson(url, label) {
        var ctl = new AbortController();
        var timer = setTimeout(function () { ctl.abort(); }, 10000);
        try {
            var res = await fetch(url, { signal: ctl.signal });
            if (!res.ok) throw new Error(label + ' returned ' + res.status);
            return await res.json();
        } finally {
            clearTimeout(timer);
        }
    }

    function friendlyError(err) {
        if (err) {
            if (err.name === 'AbortError') return 'The weather service took too long to answer. Give it another try in a moment.';
            if (err instanceof TypeError) return 'Could not reach the weather service. Check your connection and try again.';
            if (err.message) return err.message;
        }
        return 'Something went wrong fetching the forecast.';
    }

    function fail(err) {
        var msg = friendlyError(err);
        lastPlace = null; lastForecast = null; // so the unit toggle can't redraw an old forecast over the error
        showError(msg);
        jsonUrl.textContent = '·';
        jsonBody.innerHTML  = '// ' + escapeHtml(msg);
    }

    function beginRequest() {
        reqId++;
        setLoading(true);
        return reqId;
    }

    async function geocode(name) {
        var geoUrl = 'https://geocoding-api.open-meteo.com/v1/search?count=100&language=en&format=json&name=' + encodeURIComponent(name);
        var gJson = await getJson(geoUrl, 'Geocoding API');
        return gJson.results || [];
    }

    async function loadForecast(place, id) {
        var fxUrl = 'https://api.open-meteo.com/v1/forecast'
            + '?latitude='  + place.latitude
            + '&longitude=' + place.longitude
            + '&timezone=auto'
            + '&current=temperature_2m,relative_humidity_2m,apparent_temperature,is_day,weather_code,wind_speed_10m'
            + '&daily=weather_code,temperature_2m_max,temperature_2m_min';
        var fJson = await getJson(fxUrl, 'Forecast API');
        if (id !== reqId) return; // a newer request has taken over

        render(place, fJson);
        jsonUrl.textContent = fxUrl;
        jsonBody.innerHTML  = hilite(fJson);
    }

    async function search(query) {
        if (!query) return;
        var id = beginRequest();
        try {
            // 1. Parse + geocode. We send only the city to the geocoder
            //    (Open-Meteo's /search doesn't accept state or country params)
            //    then pick the best of up to 100 candidates client-side.
            //    (10 was too few: Illinois isn't in the first 10 "Saint Charles".)
            var q = parseQuery(query);
            if (!q.city) throw new Error('Type a city name to search.');
            var results = await geocode(q.city);
            if (id !== reqId) return;
            if (!results.length) throw new Error('No place matched "' + q.city + '". Try a different spelling?');
            var place = pickResult(results, q.region, q.country);
            if (!place) throw new Error('No match for "' + query + '".');

            // The geocoder lists Saint Charles, IL only as a dam and a ball
            // field, because the city itself is filed as "St. Charles".
            // If we landed on a non-town, retry with the abbreviation.
            if (!firstTown([place])) {
                if (/^saint\s/i.test(q.city)) {
                    var alt = await geocode('St. ' + q.city.replace(/^saint\s+/i, ''));
                    if (id !== reqId) return;
                    var altTown = firstTown(matchPlaces(alt, q.region, q.country));
                    if (altTown) place = altTown;
                }
            }

            // 2. Forecast
            await loadForecast(place, id);
        } catch (err) {
            if (id !== reqId) return;
            fail(err);
        } finally {
            if (id === reqId) setLoading(false);
        }
    }

    /* "Use my location": the browser hands us coordinates, so the forecast
       call needs no geocoding. Open-Meteo has no reverse geocoder, so the
       card is labeled "Your location" with the coordinates under it. */
    function useMyLocation() {
        if (!navigator.geolocation) {
            showError('This browser cannot share your location. Try searching for a city instead.');
            return;
        }
        if (!window.isSecureContext) {
            showError('Your browser only shares location on secure (https) pages. Try searching for a city instead.');
            return;
        }
        var id = beginRequest();
        navigator.geolocation.getCurrentPosition(function (pos) {
            if (id !== reqId) return;
            var lat = pos.coords.latitude, lon = pos.coords.longitude;
            var place = { name: 'Your location', admin1: '', country: lat.toFixed(2) + ', ' + lon.toFixed(2), latitude: lat, longitude: lon };
            loadForecast(place, id).catch(function (err) {
                if (id !== reqId) return;
                fail(err);
            }).then(function () {
                if (id === reqId) setLoading(false);
            });
        }, function (err) {
            if (id !== reqId) return;
            var msg = 'Could not get your location. Try searching for a city instead.';
            if (err.code === 1) msg = 'Location access is blocked. You can allow it in your browser settings, or search for a city instead.';
            if (err.code === 3) msg = 'Finding your location took too long. Try again, or search for a city instead.';
            fail(new Error(msg));
            setLoading(false);
        }, { timeout: 10000, maximumAge: 300000 });
    }

    var unitBtns = root.querySelectorAll('[data-role="unit"]');
    Array.prototype.forEach.call(unitBtns, function (b) {
        b.addEventListener('click', function () {
            var next = this.getAttribute('data-unit') === 'imperial';
            if (next === imperial) { return; }
            imperial = next;
            Array.prototype.forEach.call(unitBtns, function (x) {
                var on = x.getAttribute('data-unit') === (imperial ? 'imperial' : 'metric');
                x.classList.toggle('is-on', on);
                x.setAttribute('aria-pressed', on ? 'true' : 'false');
            });
            if (lastForecast) { render(lastPlace, lastForecast); }
        });
    });

    form.addEventListener('submit', function (e) {
        e.preventDefault();
        search(input.value.trim());
    });

    geoBtn.addEventListener('click', useMyLocation);

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
