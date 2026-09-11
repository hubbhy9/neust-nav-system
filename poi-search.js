(function () {

    // POI tags mapping: user-provided tags for each room id
    const POI_TAGS = {
        'wp-cict': ['cict', 'faculty', 'teachers', 'professors'],
        'wp-clinic': ['clinic', 'health', 'medical', 'nurse', 'medicine', 'firstaid'],
        'wp-conf': ['conference', 'meeting', 'seminar'],
        'wp-dean': ['dean', 'office', 'administration'],
        'wp-room113': ['113', 'classroom'],
        'wp-room114': ['114', 'classroom'],
        'wp-supplyroom': ['stock', 'supplies', 'storage'],
        'wp-room222': ['222', 'classroom', 'mis', 'management', 'information', 'system'],
        'wp-room221': ['221', 'classroom'],
        'wp-room220': ['220', 'classroom'],
        'wp-room219': ['219', 'classroom'],
        'wp-room218': ['218', 'classroom'],
        'wp-guidance': ['guidance', 'counselor', 'counseling'],
        'wp-cict-coed-faculty': ['coed', 'faculty', 'teachers', 'professors'],
        'wp-cr-staff': ['cr', 'comfort', 'restroom', 'toilet'],
        'wp-bsiestock': ['bsie', 'stockroom', 'storage', 'supplies'],
        'wp-gender': ['gender', 'gad', 'gender'],
        'wp-ret': ['ret', 'office', 'education'],
        'wp-nstp': ['nstp', 'office', 'training', 'requirements', 'documents'],
        'wp-room110': ['110', 'classroom'],
        'wp-room109': ['109', 'classroom'],
        'wp-mapeh': ['mapeh', 'music', 'arts', 'physical', 'health'],
        'wp-bped': ['bped', 'physical', 'education'],
        'wp-room108': ['108'],
        'wp-coed-office': ['coed', 'office', 'education'],
        'wp-ed-tech': ['ed', 'tech', 'technology'],
        'wp-room215': ['215', 'classroom'],
        'wp-room214': ['214', 'classroom'],
        'wp-room213': ['213', 'classroom'],
        'wp-room212': ['212', 'classroom'],
        'wp-field-study-office': ['field', 'study', 'office', 'requirements', 'documents'],
        'wp-room211': ['211', 'classroom'],
        'wp-lhs-principal': ['lhs', 'principal', 'office', 'highschool'],
        'wp-room107': ['107', 'classroom'],
        'wp-cashier': ['cashier', 'payment', 'tuition', 'fees', 'receipt'],
        'wp-registrar': ['registrar', 'records', 'transcript', 'tor', 'certification', 'certificate', 'clearance', 'form', 'document'],
        'wp-osoad': ['osoad', 'affairs', 'services', 'student'],
        'wp-bmgs': ['bmgs', 'business', 'management'],
        'wp-room207': ['207', 'classroom'],
        'wp-room208': ['208', 'classroom'],
        'wp-room209': ['209', 'classroom'],
        'wp-room206': ['206', 'classroom'],
        'wp-accreditation': ['accreditation', 'quality'],
        'wp-anihan': ['anihan', 'organization'],
        'wp-room301': ['301', 'classroom'],
        'wp-room302': ['302', 'classroom'],
        'wp-room303': ['303', 'classroom'],
        'wp-library': ['library', 'books', 'reading', 'study', 'research'],
        'wp-room104': ['104', 'highschool', 'classroom', 'student'],
        'wp-room103': ['103', 'highschool', 'classroom', 'student'],
        'wp-room102': ['102', 'highschool', 'classroom', 'student'],
        'wp-room101': ['101', 'highschool', 'classroom', 'student'],
        'wp-room204': ['204', 'comlab', 'computer', 'laboratory'],
        'wp-room203': ['203', 'comlab', 'computer', 'laboratory'],
        'wp-room202': ['202', 'comlab', 'computer', 'laboratory'],
        'wp-room201': ['201', 'comlab', 'computer', 'laboratory'],
        'wp-lab': ['laboratory', 'lab', 'computer', 'science', 'practical'],
        'wp-drafting': ['drafting', 'drawing', 'technical']
    };

    let searchInput = null;
    let suggestions = null;

    function cssEscapeSafe(s) {
        if (typeof CSS !== 'undefined' && typeof CSS.escape === 'function') return CSS.escape(s);
        return String(s).replace(/(["'\\\\])/g, "\\$1");
    }

    function buildIndex() {

        const list = [];

        // POI_TAGS is the ONLY basis for predictions
        for (const id in POI_TAGS) {

            if (!Object.prototype.hasOwnProperty.call(POI_TAGS, id)) {
                continue;
            }

            const tags = POI_TAGS[id];

            // Use the first tag as a fallback display name
            let label = id;

            if (Array.isArray(tags) && tags.length > 0) {
                label = tags[0];
            }

            list.push({
                label: label,
                value: id,
                tags: tags.map(tag =>
                    String(tag).toLowerCase().trim()
                )
            });
        }

        return list;
    }

    let index = [];

    function scoreForQuery(tags, q) {
        let best = 0;

        for (const tag of tags) {
            const t = String(tag).toLowerCase();

            // 1. EXACT MATCH
            // Example: "library" === "library"
            if (t === q) {
                best = Math.max(best, 400);
            }

            // 2. PREFIX MATCH: lib*
            // Example: "library"
            else if (t.startsWith(q)) {
                best = Math.max(best, 300);
            }

            // 3. WORD PREFIX MATCH
            // Example: "main library"
            // Typing "lib" finds the word "library"
            else if (
                t.split(/[\s\-_]+/).some(word => word.startsWith(q))
            ) {
                best = Math.max(best, 200);
            }

            // 4. CONTAINS MATCH: *lib*
            // Example: "mainlibrary"
            else if (t.includes(q)) {
                best = Math.max(best, 100);
            }

            // 5. SUFFIX MATCH: *lib
            // Example: "mylib"
            else if (t.endsWith(q)) {
                best = Math.max(best, 50);
            }
        }

        return best;
    }

    let lastQuery = '';
    let activeIndex = -1;

    function onInput(e) {
        const q = String(e.target.value || '').trim().toLowerCase();
        lastQuery = q;
        activeIndex = -1;

        if (!q) {
            suggestions.style.display = 'none';
            if (typeof filterDropdowns === 'function') filterDropdowns();
            return;
        }

        const results = [];
        for (const item of index) {
            const s = scoreForQuery(item.tags, q);
            if (s > 0) results.push({ item, score: s });
        }

        try { console.debug && console.debug('poi-search: query', q, 'candidates', index.length, 'matches', results.length); } catch (e) { }

        const tokens = q.split(/\s+/).filter(Boolean);
        for (const r of results) {
            const combined = r.item.tags.join(' ');
            const allTokens = tokens.every(tok => combined.includes(tok));
            if (allTokens) r.score += 10;
        }

        results.sort((a, b) => b.score - a.score || a.item.label.localeCompare(b.item.label));
        renderSuggestions(results.map(r => r.item).slice(0, 12), q);
    }

    function renderSuggestions(list, q) {
        suggestions.innerHTML = '';
        if (!list.length) { suggestions.style.display = 'none'; return; }

        list.forEach((item, idx) => {
            const btn = document.createElement('button');
            btn.type = 'button';
            btn.className = 'list-group-item list-group-item-action';

            const label = String(item.label || '');
            const labelLower = label.toLowerCase();
            let renderedLabel = escapeHtml(label);
            if (q) {
                const pos = labelLower.indexOf(q);
                if (pos >= 0) {
                    renderedLabel = escapeHtml(label.slice(0, pos)) + '<mark>' + escapeHtml(label.slice(pos, pos + q.length)) + '</mark>' + escapeHtml(label.slice(pos + q.length));
                }
            }

            btn.innerHTML = `<div><strong>${renderedLabel}</strong></div>`;
            btn.addEventListener('click', () => selectSuggestion(item));
            btn.addEventListener('mouseenter', () => setActiveSuggestion(idx));
            suggestions.appendChild(btn);
        });

        suggestions.style.display = 'block';
    }

    function setActiveSuggestion(i) {
        const children = Array.from(suggestions.children);
        if (!children.length) return;
        children.forEach((ch, idx) => ch.classList.toggle('active', idx === i));
        activeIndex = i;
        if (i >= 0 && children[i]) children[i].scrollIntoView({ block: 'nearest' });
    }

    function clearActiveSuggestion() {
        setActiveSuggestion(-1);
    }

    function selectSuggestion(item) {
        const selFrom = document.getElementById('sel-from');
        if (selFrom) {
            selFrom.value = item.value;
            selFrom.dispatchEvent(new Event('change', { bubbles: true }));
        }

        // Highlight node and matching room element
        const nodeEl = document.getElementById(item.value);
        if (nodeEl) nodeEl.classList.add('selected-waypoint');
        const matchingEl = document.querySelector('[data-target="' + cssEscapeSafe(item.value) + '"]');
        if (matchingEl) matchingEl.classList.add('selected-room');

        if (typeof applyBuildingGroupVisibility === 'function') applyBuildingGroupVisibility();

        // set search input to the label for a predictive feel
        try { searchInput.value = item.label; } catch (e) { }

        suggestions.style.display = 'none';
        // Trigger existing map behavior for waypoint selection
        try {
            if (typeof onWaypointClick === 'function') onWaypointClick(item.value);
        } catch (e) { }
    }

    // Utility to escape HTML in inserted strings
    function escapeHtml(str) {
        return String(str).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
    }

    // Rebuild index if dropdownOptions are populated later
    window.poiSearch = {
        POI_TAGS,
        rebuildIndex: function () { index = buildIndex(); return index.length; }
    };

    function initPoiSearch() {
        searchInput = document.getElementById('sel-search');
        suggestions = document.getElementById('poi-suggestions');
        if (!searchInput || !suggestions) return;

        // Build predictions directly from POI_TAGS
        index = buildIndex();

        console.log(
            'POI predictive search ready:',
            index.length,
            'locations loaded'
        );

        searchInput.addEventListener('input', onInput);
        // Rebuild index on focus if it wasn't available earlier
        searchInput.addEventListener('focus', function () {
            if (!index || !index.length) {
                index = buildIndex();
                try { console.debug('poi-search: rebuilt index on focus', index.length); } catch (e) { }
            }
        });
        try { console.debug('poi-search: initialized'); } catch (e) { }

        searchInput.addEventListener('keydown', function (e) {
            const children = Array.from(suggestions.children);
            if (!children.length) return;

            if (e.key === 'ArrowDown') {
                e.preventDefault();
                const next = (activeIndex + 1) % children.length;
                setActiveSuggestion(next);
                return;
            }

            if (e.key === 'ArrowUp') {
                e.preventDefault();
                const prev = (activeIndex - 1 + children.length) % children.length;
                setActiveSuggestion(prev);
                return;
            }

            if (e.key === 'Enter') {
                if (activeIndex >= 0 && children[activeIndex]) {
                    e.preventDefault();
                    children[activeIndex].click();
                }
            }

            if (e.key === 'Escape') {
                suggestions.style.display = 'none';
                clearActiveSuggestion();
            }
        });

        // Close suggestions when clicking outside
        document.addEventListener('click', function (e) {
            if (!suggestions.contains(e.target) && e.target !== searchInput) {
                suggestions.style.display = 'none';
            }
        });
    }

    if (document.readyState !== 'loading') initPoiSearch();
    else document.addEventListener('DOMContentLoaded', initPoiSearch);

})();

// FACILITY GROUPS FOR AUTOMATIC NEAREST SELECTION (Updated to mirror Info Desk multi-node routing logic)
const NODE_GROUPS = {
    'GROUP_CANTEEN': ['wp-canteen', 'wp-canteen2'],
    'GROUP_CR_FEMALE': ['wp-crFem', 'wp-cr-female'],
    'GROUP_CR_FEMALE-CMBT': ['wp-crFem', 'wp-cr-female'],
    'GROUP_CR_FEMALE-GYM': ['wp-cr-female', 'wp-crFem'],
    'GROUP_CR_MALE': ['wp-cr-male-j', 'wp-cr-male'],
    'GROUP_CR_MALE-CMBT': ['wp-cr-male-j', 'wp-cr-male'],
    'GROUP_CR_MALE-GYM': ['wp-cr-male', 'wp-cr-male-j'],
    'GROUP_INFO_CICT': ['wp-infoDesk', 'wp-info-front'],
    'GROUP_INFO_FRONT': ['wp-info-front', 'wp-infoDesk'],
    'GROUP_GUARD': ['wp-guard']
};

// MAPPING HTML DIVS DIRECTLY TO TARGET ID ATTRIBUTES TO AVOID CONFLICTS
const BUILDING_MAP_1f = {
    'cict-faculty': 'wp-cict',
    'clinic': 'wp-clinic',
    'info-cict': 'GROUP_INFO_CICT',
    'conference-room': 'wp-conf',
    'director-office': 'wp-dean',
    'rm113': 'wp-room113',
    'rm114': 'wp-room114',
    'supply-room': 'wp-supplyroom',
    'cr-staff': 'wp-cr-staff',
    'bsie-stockroom': 'wp-bsiestock',
    'canteen': 'GROUP_CANTEEN',
    'gender': 'wp-gender',
    'nstp': 'wp-nstp',
    'rm110': 'wp-room110',
    'rm109': 'wp-room109',
    'rmmapeh': 'wp-mapeh',
    'bped': 'wp-bped',
    'rm108': 'wp-room108',
    'laboratory': 'wp-lab',
    'cr-female': 'GROUP_CR_FEMALE',
    'guarde': 'GROUP_GUARD',
    'registrar': 'wp-registrar',
    'cashier': 'wp-cashier',
    'osoad': 'wp-osoad',
    'bmgs': 'wp-bmgs',
    'rm101': 'wp-room101',
    'rm102': 'wp-room102',
    'rm103': 'wp-room103',
    'rm104': 'wp-room104',
    'gym': 'wp-gym',
    'rm107': 'wp-room107',
    'lhs': 'wp-lhs-principal',
    'ret': 'wp-ret',
    'cr-male': 'GROUP_CR_MALE',
    'info-front': 'GROUP_INFO_FRONT',
    'cr-gym-female': 'GROUP_CR_FEMALE-GYM',
    'cr-gym-male': 'GROUP_CR_MALE-GYM'
};

// NODES DEFINITION
const NODES = {
    'wp-clinic': { x: 800, y: 175, label: 'Clinic' },
    'wp-cict': { x: 840, y: 175, label: 'CICT Faculty' },
    'wp-infoDesk': { x: 690, y: 175 },
    'wp-conf': { x: 620, y: 175, label: 'Conference Room' },
    'wp-dean': { x: 505, y: 175, label: 'Dean Office' },
    'wp-room113': { x: 408, y: 175, label: 'Room 113' },
    'wp-room114': { x: 333, y: 175, label: 'Room 114' },
    'wp-supplyroom': { x: 167, y: 147, label: 'Stock Room' },
    'wp-cr-staff': { x: 167, y: 200, label: 'CR Staff' },
    'wp-bsiestock': { x: 167, y: 250, label: 'BSIE Stockroom' },
    'wp-canteen': { x: 83, y: 297, label: 'Canteen 1 (North)' },
    'wp-gender': { x: 167, y: 357, label: 'Gender Office' },
    'wp-nstp': { x: 167, y: 465, label: 'NSTP' },
    'wp-room110': { x: 167, y: 525, label: 'Room 110' },
    'wp-room109': { x: 167, y: 580, label: 'Room 109' },
    'wp-canteen2': { x: 50, y: 690, label: 'Canteen 2 (South)' },
    'wp-mapeh': { x: 167, y: 635, label: 'MAPEH Room' },
    'wp-bped': { x: 167, y: 693, label: 'BPED/PE Room' },
    'wp-room108': { x: 167, y: 750, label: 'Room 108' },
    'wp-lab': { x: 200, y: 750, label: 'Laboratory' },
    'wp-crFem': { x: 167, y: 798 },
    'wp-entrance': { x: 503, y: 1120, label: 'Guard House / Entrance' },
    'wp-registrar': { x: 535, y: 1000, label: 'Office of Registrar' },
    'wp-cashier': { x: 345, y: 1000, label: 'Cashier Office' },
    'wp-osoad': { x: 800, y: 1000, label: 'OSOAD' },
    'wp-bmgs': { x: 915, y: 1000, label: 'BMGS' },
    'wp-room101': { x: 990, y: 380, label: 'Room 101' },
    'wp-room102': { x: 990, y: 490, label: 'Room 102' },
    'wp-room103': { x: 990, y: 715, label: 'Room 103' },
    'wp-room104': { x: 990, y: 825, label: 'Room 104' },
    'wp-gym': { x: 430, y: 485, label: 'Gym' },
    'wp-room107': { x: 188, y: 1000, label: 'Room 107' },
    'wp-lhs-principal': { x: 260, y: 1000, label: 'LHS Principal' },
    'wp-reg-win': { x: 535, y: 860, label: 'Registrar Window (R. Win)' },
    'wp-cashier-win': { x: 345, y: 860, label: 'Cashier Window (C. Win)' },
    'wp-ret': { x: 167, y: 410, label: 'RET Office' },
    'wp-cr-male': { x: 230, y: 600 },
    'wp-cr-female': { x: 230, y: 375 },
    'wp-cr-male-j': { x: 110, y: 870 },

    /* JUNCTIONS & WAYPOINTS */
    'wp-info-front': { x: 465, y: 1000 },
    'wp-guard': { x: 465, y: 1030 },
    'wp-hallmid-a': { x: 640, y: 1000 },
    'wp-1stair-right-cmbt': { x: 948, y: 1000 },
    'wp-lhs-hall': { x: 990, y: 1000 },
    'wp-1stair-lhs': { x: 990, y: 600 },
    'wp-exit': { x: 950, y: 265 },
    'wp-j-exit': { x: 835, y: 265 },
    'wp-hallmid-b': { x: 640, y: 860 },
    'wp-old-reg': { x: 835, y: 860 },
    'wp-front-gym': { x: 835, y: 485 },
    'wp-1stair-cict': { x: 730, y: 175 },
    'wp-dean-win': { x: 540, y: 175 },
    'wp-1stair-cict2': { x: 238, y: 175 },
    'wp-stockroom': { x: 280, y: 175 },
    'wp-j-cmbt-left': { x: 50, y: 870 },
    'wp-j-cmbt-cr-stair': { x: 50, y: 1000 },
    'wp-1j-cmbt-stair': { x: 95, y: 1000 },
    'wp-1stair-cmbt-mid': { x: 450, y: 1000 },
    'wp-open-court': { x: 345, y: 685 },
    'wp-1stair-draft': { x: 260, y: 685 },
    'wp-chempath': { x: 200, y: 685 },
    'wp-1stair-coed1': { x: 167, y: 845 },
    'wp-12stair-coed1': { x: 117, y: 845 },
    'wp-12stair-coed2': { x: 117, y: 147 },
    'wp-12stair-draft': { x: 260, y: 685 },
    'wp-12j-cmbt-stair': { x: 95, y: 930 },
    'wp-12stair-right-cmbt': { x: 948, y: 930 },
    'wp-12stair-cmbt-mid': { x: 450, y: 930 },
    'wp-12stair-cict': { x: 730, y: 125 },
    'wp-12stair-cict2': { x: 238, y: 125 },
    'wp-12stair-lhs': { x: 890, y: 600 },
    'wp-canteen-hall': { x: 167, y: 297 },

    /* JUNCTIONS 1 - 36 */
    'wp-junction1': { x: 640, y: 485 },
    'wp-junction2': { x: 640, y: 685 },
    'wp-junction3': { x: 640, y: 265 },
    'wp-junction4': { x: 408, y: 265 },
    'wp-junction5': { x: 505, y: 265 },
    'wp-junction6': { x: 333, y: 265 },
    'wp-junction7': { x: 238, y: 265 },
    'wp-junction8': { x: 580, y: 265 },
    'wp-junction9': { x: 700, y: 265 },
    'wp-junction10': { x: 760, y: 265 },
    'wp-junction11': { x: 700, y: 375 },
    'wp-junction12': { x: 700, y: 600 },
    'wp-junction13': { x: 700, y: 485 },
    'wp-junction14': { x: 700, y: 685 },
    'wp-junction15': { x: 700, y: 785 },
    'wp-junction16': { x: 760, y: 375 },
    'wp-junction17': { x: 760, y: 600 },
    'wp-junction18': { x: 760, y: 485 },
    'wp-junction19': { x: 760, y: 685 },
    'wp-junction20': { x: 760, y: 785 },
    'wp-junction21': { x: 640, y: 375 },
    'wp-junction22': { x: 640, y: 600 },
    'wp-junction23': { x: 640, y: 785 },
    'wp-junction24': { x: 580, y: 685 },
    'wp-junction25': { x: 580, y: 785 },
    'wp-junction26': { x: 505, y: 685 },
    'wp-junction27': { x: 505, y: 785 },
    'wp-junction28': { x: 408, y: 685 },
    'wp-junction29': { x: 408, y: 785 },
    'wp-junction30': { x: 345, y: 785 },
    'wp-junction31': { x: 72, y: 410 },
    'wp-junction32': { x: 72, y: 635 },
    'wp-junction33': { x: 72, y: 297 },
    'wp-junction34': { x: 50, y: 635 },
    'wp-junction35': { x: 167, y: 870 },
    'wp-junction36': { x: 238, y: 330 },
    'wp-junction37': { x: 200, y: 330 },
    'wp-junction38': { x: 200, y: 515 },
    'wp-junction39': { x: 200, y: 600 }
};

const FLOOR_2F_NODES = {
    'wp-room221': { x: 800, y: 175, label: 'Room 221' },
    'wp-guidance': { x: 690, y: 125, label: 'Guidance' },
    'wp-room222': { x: 840, y: 175, label: 'Room 222' },
    'wp-room220': { x: 625, y: 175, label: 'Room 220' },
    'wp-room219': { x: 530, y: 175, label: 'Room 219' },
    'wp-room218': { x: 408, y: 175, label: 'Room 218' },
    'wp-cict-coed-faculty': { x: 340, y: 175, label: 'COED Faculty' },
    'wp-coed-office': { x: 167, y: 147, label: 'COED Office' },
    'wp-ed-tech': { x: 167, y: 230, label: 'Ed Tech' },
    'wp-room215': { x: 167, y: 320, label: 'Room 215' },
    'wp-room214': { x: 167, y: 410, label: 'Room 214' },
    'wp-room213': { x: 167, y: 500, label: 'Room 213' },
    'wp-room212': { x: 167, y: 590, label: 'Room 212' },
    'wp-field-study-office': { x: 167, y: 680, label: 'Field Study Office' },
    'wp-room211': { x: 167, y: 770, label: 'Room 211' },
    'wp-room209': { x: 200, y: 1000, label: 'Room 209' },
    'wp-room208': { x: 290, y: 1000, label: 'Room 208' },
    'wp-room207': { x: 380, y: 1000, label: 'Room 207' },
    'wp-room206': { x: 545, y: 1000, label: 'Room 206' },
    'wp-anihan': { x: 685, y: 1000, label: 'Anihan' },
    'wp-accreditation': { x: 825, y: 1000, label: 'Accreditation' },
    'wp-drafting': { x: 300, y: 685, label: 'Drafting Room' },
    'wp-room201': { x: 990, y: 380, label: 'Room 201' },
    'wp-room202': { x: 990, y: 490, label: 'Room 202' },
    'wp-room203': { x: 990, y: 715, label: 'Room 203' },
    'wp-room204': { x: 990, y: 825, label: 'Room 204' },
    'wp-2stair-right-cmbt': { x: 948, y: 1000 },
    'wp-2j-cmbt-stair': { x: 115, y: 1000 },
    'wp-2stair-cmbt-mid': { x: 470, y: 1000 },
    'wp-2stair-lhs': { x: 990, y: 570 },
    'wp-2stair-coed1': { x: 167, y: 830 },
    'wp-2stair-coed2': { x: 167, y: 170 },
    'wp-2stair-cict': { x: 690, y: 175 },
    'wp-2stair-cict2': { x: 270, y: 175 },
    'wp-2stair-draft': { x: 260, y: 685 },
    'wp-21stair-right-cmbt': { x: 948, y: 930 },
    'wp-21j-cmbt-stair': { x: 115, y: 930 },
    'wp-21stair-cmbt-mid': { x: 470, y: 930 },
    'wp-21stair-lhs': { x: 890, y: 570 },
    'wp-21stair-coed1': { x: 117, y: 830 },
    'wp-21stair-coed2': { x: 117, y: 170 },
    'wp-21stair-cict': { x: 690, y: 125 },
    'wp-21stair-cict2': { x: 270, y: 125 },
    'wp-21stair-draft': { x: 260, y: 685 }
};

// 3RD FLOOR (CMBT) NODES DEFINITION
const FLOOR_3F_NODES = {
    'wp-room301': { x: 200, y: 1000, label: 'Room 301' },
    'wp-room302': { x: 380, y: 1000, label: 'Room 302' },
    'wp-room303': { x: 290, y: 1000, label: 'Room 303' },
    'wp-library': { x: 655, y: 1000, label: 'Library' },
    'wp-3stair-right-cmbt': { x: 948, y: 1000 },
    'wp-3j-cmbt-stair': { x: 115, y: 1000 },
    'wp-23j-cmbt-stair': { x: 95, y: 930 },
    'wp-23stair-right-cmbt': { x: 948, y: 930 }
};

const FLOOR_3F_NODE_IDS = new Set(Object.keys(FLOOR_3F_NODES));
Object.assign(NODES, FLOOR_3F_NODES);

const FLOOR_2F_NODE_IDS = new Set(Object.keys(FLOOR_2F_NODES));
Object.assign(NODES, FLOOR_2F_NODES);

const FLOOR_2F_BUILDING_MAP = {
    'wp-room221': 'cict',
    'wp-guidance': 'cict',
    'wp-room222': 'cict',
    'wp-room220': 'cict',
    'wp-room219': 'cict',
    'wp-room218': 'cict',
    'wp-2stair-cict': 'cict',
    'wp-2stair-cict2': 'cict',

    'wp-cict-coed-faculty': 'coed',
    'wp-coed-office': 'coed',
    'wp-ed-tech': 'coed',
    'wp-room215': 'coed',
    'wp-room214': 'coed',
    'wp-room213': 'coed',
    'wp-room212': 'coed',
    'wp-field-study-office': 'coed',
    'wp-room211': 'coed',
    'wp-2stair-coed1': 'coed',
    'wp-2stair-coed2': 'coed',

    'wp-room209': 'cmbt',
    'wp-room208': 'cmbt',
    'wp-room207': 'cmbt',
    'wp-room206': 'cmbt',
    'wp-anihan': 'cmbt',
    'wp-accreditation': 'cmbt',
    'wp-2stair-right-cmbt': 'cmbt',
    'wp-2j-cmbt-stair': 'cmbt',
    'wp-2stair-cmbt-mid': 'cmbt',

    'wp-room204': 'lhs',
    'wp-room203': 'lhs',
    'wp-room202': 'lhs',
    'wp-room201': 'lhs',
    'wp-drafting': 'lhs',
    'wp-2stair-lhs': 'lhs',
    'wp-2stair-draft': 'lhs',
    'wp-21stair-right-cmbt': 'cmbt',
    'wp-21j-cmbt-stair': 'cmbt',
    'wp-21stair-cmbt-mid': 'cmbt',
    'wp-21stair-lhs': 'lhs',
    'wp-21stair-coed1': 'coed',
    'wp-21stair-coed2': 'coed',
    'wp-21stair-cict': 'cict',
    'wp-21stair-cict2': 'cict',
    'wp-21stair-draft': 'lhs'
};

const FLOOR_2F_BUILDING_KEYS = ['cict', 'coed', 'cmbt', 'lhs'];

const FLOOR_2F_BUILDING_GROUPS = FLOOR_2F_BUILDING_KEYS.reduce((groups, key) => {
    groups[key] = {
        building: key,
        domSelector: `#floor2-layer .building-${key}`,
        svgLineSelector: `#floor2-layer svg.map-lines-overlay .inner-wall.building-${key}`,
        nodeIds: Object.keys(FLOOR_2F_BUILDING_MAP).filter(id => FLOOR_2F_BUILDING_MAP[id] === key)
    };
    return groups;
}, {});


// EDGES DEFINITION
const EDGES = [
    ['wp-j-cmbt-left', 'wp-canteen2'],
    ['wp-j-cmbt-left', 'wp-j-cmbt-cr-stair'],
    ['wp-gym', 'wp-cr-female'],
    ['wp-gym', 'wp-junction1'],
    ['wp-gym', 'wp-cr-male'],
    ['wp-cr-female', 'wp-junction36'],
    ['wp-cr-female', 'wp-junction37'],
    ['wp-junction37', 'wp-junction36'],
    ['wp-junction37', 'wp-junction38'],
    ['wp-junction38', 'wp-junction39'],
    ['wp-cr-male', 'wp-junction39'],
    ['wp-chempath', 'wp-junction39'],
    ['wp-cr-male', 'wp-room110'],
    ['wp-guard', 'wp-info-front'],
    ['wp-guard', 'wp-entrance'],
    ['wp-entrance', 'wp-info-front'],
    ['wp-info-front', 'wp-1stair-cmbt-mid'],
    ['wp-info-front', 'wp-registrar'],
    ['wp-1stair-cmbt-mid', 'wp-cashier'],
    ['wp-cashier', 'wp-lhs-principal'],
    ['wp-lhs-principal', 'wp-room107'],
    ['wp-room107', 'wp-1j-cmbt-stair'],
    ['wp-j-cmbt-cr-stair', 'wp-1j-cmbt-stair'],
    ['wp-registrar', 'wp-hallmid-a'],
    ['wp-hallmid-a', 'wp-osoad'],
    ['wp-hallmid-a', 'wp-hallmid-b'],
    ['wp-osoad', 'wp-bmgs'],
    ['wp-bmgs', 'wp-1stair-right-cmbt'],
    ['wp-1stair-right-cmbt', 'wp-lhs-hall'],
    ['wp-lhs-hall', 'wp-room104'],
    ['wp-room104', 'wp-room103'],
    ['wp-room103', 'wp-1stair-lhs'],
    ['wp-1stair-lhs', 'wp-room102'],
    ['wp-room102', 'wp-room101'],
    ['wp-room101', 'wp-exit'],
    ['wp-exit', 'wp-j-exit'],
    ['wp-j-exit', 'wp-1stair-cict'],
    ['wp-j-exit', 'wp-junction10'],
    ['wp-1stair-cict', 'wp-clinic'],
    ['wp-1stair-cict', 'wp-infoDesk'],
    ['wp-clinic', 'wp-infoDesk'],
    ['wp-cict', 'wp-1stair-cict'],
    ['wp-cict', 'wp-clinic'],
    ['wp-infoDesk', 'wp-conf'],
    ['wp-infoDesk', 'wp-junction10'],
    ['wp-infoDesk', 'wp-junction3'],
    ['wp-infoDesk', 'wp-junction9'],
    ['wp-conf', 'wp-dean-win'],
    ['wp-conf', 'wp-junction9'],
    ['wp-conf', 'wp-junction3'],
    ['wp-conf', 'wp-junction8'],
    ['wp-dean-win', 'wp-dean'],
    ['wp-dean', 'wp-room113'],
    ['wp-dean', 'wp-junction8'],
    ['wp-dean', 'wp-junction5'],
    ['wp-dean', 'wp-junction4'],
    ['wp-room113', 'wp-room114'],
    ['wp-room114', 'wp-stockroom'],
    ['wp-stockroom', 'wp-1stair-cict2'],
    ['wp-1stair-cict2', 'wp-supplyroom'],

    ['wp-supplyroom', 'wp-cr-staff'],
    ['wp-cr-staff', 'wp-bsiestock'],
    ['wp-cr-staff', 'wp-supplyroom'],
    ['wp-cr-staff', 'wp-junction7'],
    ['wp-supplyroom', 'wp-12stair-coed2'],
    ['wp-cr-staff', 'wp-1stair-cict2'],
    ['wp-bsiestock', 'wp-junction7'],
    ['wp-bsiestock', 'wp-canteen-hall'],
    ['wp-junction7', 'wp-canteen-hall'],
    ['wp-canteen-hall', 'wp-canteen'],
    ['wp-canteen-hall', 'wp-junction33'],
    ['wp-canteen-hall', 'wp-gender'],
    ['wp-gender', 'wp-ret'],
    ['wp-ret', 'wp-nstp'],
    ['wp-nstp', 'wp-room110'],
    ['wp-room110', 'wp-room109'],
    ['wp-room109', 'wp-mapeh'],
    ['wp-mapeh', 'wp-bped'],
    ['wp-mapeh', 'wp-chempath'],
    ['wp-mapeh', 'wp-cr-male'],
    ['wp-bped', 'wp-room108'],
    ['wp-bped', 'wp-chempath'],
    ['wp-room108', 'wp-lab'],
    ['wp-room108', 'wp-crFem'],
    ['wp-lab', 'wp-crFem'],
    ['wp-lab', 'wp-chempath'],
    ['wp-crFem', 'wp-1stair-coed1'],
    ['wp-1stair-coed1', 'wp-junction35'],
    ['wp-junction35', 'wp-cr-male-j'],
    ['wp-cr-male-j', 'wp-j-cmbt-left'],
    ['wp-j-cmbt-cr-stair', 'wp-j-cmbt-stair'],
    ['wp-hallmid-b', 'wp-reg-win'],
    ['wp-hallmid-b', 'wp-old-reg'],
    ['wp-reg-win', 'wp-cashier-win'],
    ['wp-reg-win', 'wp-junction27'],
    ['wp-reg-win', 'wp-junction25'],
    ['wp-cashier-win', 'wp-open-court'],
    ['wp-cashier-win', 'wp-junction30'],
    ['wp-cashier-win', 'wp-junction29'],
    ['wp-open-court', 'wp-1stair-draft'],
    ['wp-1stair-draft', 'wp-chempath'],
    ['wp-chempath', 'wp-cr-male'],
    ['wp-cr-male', 'wp-1stair-draft'],
    ['wp-old-reg', 'wp-front-gym'],
    ['wp-front-gym', 'wp-gym'],
    ['wp-front-gym', 'wp-j-exit'],
    ['wp-junction1', 'wp-junction22'],
    ['wp-junction2', 'wp-junction23'],
    ['wp-junction2', 'wp-junction24'],
    ['wp-junction3', 'wp-junction8'],
    ['wp-junction3', 'wp-junction21'],
    ['wp-junction4', 'wp-junction6'],
    ['wp-junction4', 'wp-room113'],
    ['wp-junction5', 'wp-junction4'],
    ['wp-junction5', 'wp-dean'],
    ['wp-junction6', 'wp-junction7'],
    ['wp-junction7', 'wp-1stair-cict2'],
    ['wp-junction8', 'wp-junction5'],
    ['wp-junction9', 'wp-junction3'],
    ['wp-junction9', 'wp-junction11'],
    ['wp-junction10', 'wp-junction9'],
    ['wp-junction10', 'wp-junction16'],
    ['wp-junction11', 'wp-junction13'],
    ['wp-junction12', 'wp-junction14'],
    ['wp-junction13', 'wp-junction12'],
    ['wp-junction14', 'wp-junction15'],
    ['wp-junction16', 'wp-junction18'],
    ['wp-junction17', 'wp-junction19'],
    ['wp-junction18', 'wp-junction17'],
    ['wp-junction19', 'wp-junction20'],
    ['wp-junction21', 'wp-junction1'],
    ['wp-junction22', 'wp-junction2'],
    ['wp-junction23', 'wp-hallmid-b'],
    ['wp-junction23', 'wp-junction25'],
    ['wp-junction24', 'wp-junction26'],
    ['wp-junction25', 'wp-junction27'],
    ['wp-junction26', 'wp-junction28'],
    ['wp-junction27', 'wp-junction29'],
    ['wp-junction28', 'wp-open-court'],
    ['wp-junction29', 'wp-junction30'],
    ['wp-junction30', 'wp-cashier-win'],
    ['wp-junction31', 'wp-junction32'],
    ['wp-junction32', 'wp-junction34'],
    ['wp-junction33', 'wp-canteen'],
    ['wp-junction33', 'wp-junction31'],
    ['wp-junction34', 'wp-canteen2'],
    ['wp-junction36', 'wp-junction7']
];

const EDGES_2F = [
    ['wp-room222', 'wp-room221'],
    ['wp-room221', 'wp-2stair-cict'],
    ['wp-2stair-cict', 'wp-guidance'],
    ['wp-2stair-cict', 'wp-room220'],
    ['wp-room220', 'wp-room219'],
    ['wp-room219', 'wp-room218'],
    ['wp-room218', 'wp-cict-coed-faculty'],
    ['wp-cict-coed-faculty', 'wp-2stair-cict2'],
    ['wp-2stair-cict2', 'wp-2stair-coed2'],
    ['wp-coed-office', 'wp-2stair-coed2'],
    ['wp-2stair-coed2', 'wp-ed-tech'],
    ['wp-ed-tech', 'wp-room215'],
    ['wp-room215', 'wp-room214'],
    ['wp-room214', 'wp-room213'],
    ['wp-room213', 'wp-room212'],
    ['wp-room212', 'wp-field-study-office'],
    ['wp-field-study-office', 'wp-room211'],
    ['wp-room211', 'wp-2stair-coed1'],
    ['wp-room209', 'wp-room208'],
    ['wp-room208', 'wp-room207'],
    ['wp-room207', 'wp-2stair-cmbt-mid'],
    ['wp-2stair-cmbt-mid', 'wp-room206'],
    ['wp-room206', 'wp-anihan'],
    ['wp-anihan', 'wp-accreditation'],
    ['wp-accreditation', 'wp-2stair-right-cmbt'],
    ['wp-room204', 'wp-room203'],
    ['wp-room203', 'wp-2stair-lhs'],
    ['wp-2stair-lhs', 'wp-room202'],
    ['wp-room202', 'wp-room201'],
    ['wp-drafting', 'wp-2stair-draft']
];

// 3RD FLOOR EDGES
const EDGES_3F = [
    ['wp-23j-cmbt-stair', 'wp-3j-cmbt-stair'],
    ['wp-3j-cmbt-stair', 'wp-room301'],
    ['wp-room301', 'wp-room303'],
    ['wp-room303', 'wp-room302'],
    ['wp-room302', 'wp-library'],
    ['wp-library', 'wp-3stair-right-cmbt'],
    ['wp-3stair-right-cmbt', 'wp-23stair-right-cmbt']
];

const FLOOR_CONNECTIONS = [
    ['wp-1stair-coed1', 'wp-12stair-coed1'],
    ['wp-12stair-coed1', 'wp-21stair-coed1'],
    ['wp-21stair-coed1', 'wp-2stair-coed1'],

    ['wp-1stair-coed2', 'wp-12stair-coed2'],
    ['wp-12stair-coed2', 'wp-21stair-coed2'],
    ['wp-21stair-coed2', 'wp-2stair-coed2'],

    ['wp-1stair-cict', 'wp-12stair-cict'],
    ['wp-12stair-cict', 'wp-21stair-cict'],
    ['wp-21stair-cict', 'wp-2stair-cict'],

    ['wp-1stair-cict2', 'wp-12stair-cict2'],
    ['wp-12stair-cict2', 'wp-21stair-cict2'],
    ['wp-21stair-cict2', 'wp-2stair-cict2'],

    ['wp-1stair-lhs', 'wp-12stair-lhs'],
    ['wp-12stair-lhs', 'wp-21stair-lhs'],
    ['wp-21stair-lhs', 'wp-2stair-lhs'],

    ['wp-1stair-draft', 'wp-12stair-draft'],
    ['wp-12stair-draft', 'wp-21stair-draft'],
    ['wp-21stair-draft', 'wp-2stair-draft'],

    ['wp-1stair-right-cmbt', 'wp-12stair-right-cmbt'],
    ['wp-12stair-right-cmbt', 'wp-21stair-right-cmbt'],
    ['wp-21stair-right-cmbt', 'wp-2stair-right-cmbt'],

    ['wp-1j-cmbt-stair', 'wp-12j-cmbt-stair'],
    ['wp-12j-cmbt-stair', 'wp-21j-cmbt-stair'],
    ['wp-21j-cmbt-stair', 'wp-2j-cmbt-stair'],

    ['wp-1stair-cmbt-mid', 'wp-12stair-cmbt-mid'],
    ['wp-12stair-cmbt-mid', 'wp-21stair-cmbt-mid'],
    ['wp-21stair-cmbt-mid', 'wp-2stair-cmbt-mid'],

    // CMBT 2F → 3F
    ['wp-2j-cmbt-stair', 'wp-23j-cmbt-stair'],
    ['wp-23j-cmbt-stair', 'wp-3j-cmbt-stair'],

    ['wp-2stair-right-cmbt', 'wp-23stair-right-cmbt'],
    ['wp-23stair-right-cmbt', 'wp-3stair-right-cmbt']
];

// Append 2F, 3F, and floor connection edges to global EDGES array
EDGES.push(
    ...EDGES_2F,
    ...FLOOR_CONNECTIONS,
    ...EDGES_3F
);

// BUILD GRAPH
const graph = {};
for (const id in NODES) {
    graph[id] = {};
}

// REAL-WORLD MAP SCALE
// X = 0.1 meters per pixel
// Y = 0.08182 meters per pixel

const METERS_PER_PIXEL_X = 0.1;
const METERS_PER_PIXEL_Y = 0.08182;

function distance(a, b) {
    const dxPixels = NODES[a].x - NODES[b].x;
    const dyPixels = NODES[a].y - NODES[b].y;

    const dxMeters = dxPixels * METERS_PER_PIXEL_X;
    const dyMeters = dyPixels * METERS_PER_PIXEL_Y;

    return Math.hypot(dxMeters, dyMeters);
}

for (const [a, b] of EDGES) {
    if (!NODES[a] || !NODES[b]) continue;
    const w = distance(a, b);
    graph[a][b] = w;
    graph[b][a] = w;
}

// RENDER SVG EDGES
function renderEdges() {
    const layer1 = document.getElementById('edge-layer');
    const layer2 = document.getElementById('edge-layer-2f');
    const preservedClasses = {};

    [layer1, layer2].forEach(layer => {
        if (!layer) return;
        layer.querySelectorAll('.edge').forEach(edge => {
            if (edge.id) {
                preservedClasses[edge.id] = edge.getAttribute('class');
            }
        });
    });

    if (layer1) layer1.innerHTML = '';
    if (layer2) layer2.innerHTML = '';

    EDGES.forEach(([a, b]) => {
        if (!NODES[a] || !NODES[b]) return;

        const line = document.createElementNS(
            "http://www.w3.org/2000/svg",
            "line"
        );
        line.setAttribute('x1', NODES[a].x);
        line.setAttribute('y1', NODES[a].y);
        line.setAttribute('x2', NODES[b].x);
        line.setAttribute('y2', NODES[b].y);
        const both2f =
            FLOOR_2F_NODE_IDS.has(a) &&
            FLOOR_2F_NODE_IDS.has(b);

        const both3f =
            FLOOR_3F_NODE_IDS.has(a) &&
            FLOOR_3F_NODE_IDS.has(b);

        const edgeId =
            `edge-${a.replace('wp-', '')}-${b.replace('wp-', '')}` +
            (both2f ? '-2f' : '') +
            (both3f ? '-3f' : '');

        line.setAttribute('id', edgeId);
        line.setAttribute('class', preservedClasses[edgeId] || 'edge');

        // Add building class for 2F
        if (both2f) {
            const buildingA = FLOOR_2F_BUILDING_MAP[a];
            const buildingB = FLOOR_2F_BUILDING_MAP[b];

            if (buildingA && buildingA === buildingB) {
                line.classList.add(`building-${buildingA}`);
            }
        }

        // 3F is CMBT
        if (both3f) {
            line.classList.add('building-cmbt');
        }

        // Put the edge in the correct SVG layer
        let targetLayer = layer1;

        if (both2f) {
            targetLayer = layer2;
        } else if (both3f) {
            targetLayer = document.getElementById('edge-layer-3f');
        }

        if (targetLayer) {
            targetLayer.appendChild(line);
        }
    });
}


renderEdges();

// DIJKSTRA PATHFINDING// =========================================================
// DIJKSTRA PATHFINDING
// =========================================================
function dijkstra(start, end) {

    // Start/end must be real nodes.
    // Group IDs are NOT real nodes.
    if (!graph[start] || !graph[end]) {
        return {
            path: [],
            distance: Infinity
        };
    }


    const dist = {};
    const prev = {};

    const unvisited = new Set(Object.keys(graph));


    // Start everything at infinity
    for (const node of unvisited) {
        dist[node] = Infinity;
        prev[node] = null;
    }


    // Starting node is free
    dist[start] = 0;


    while (unvisited.size) {

        // Find the closest unvisited node
        let current = [...unvisited].reduce(
            (a, b) => dist[a] < dist[b] ? a : b
        );


        // No route exists
        if (dist[current] === Infinity) {
            break;
        }


        // We reached the destination
        if (current === end) {
            break;
        }


        unvisited.delete(current);


        // Check neighboring nodes
        for (const nb in graph[current]) {

            const alt =
                dist[current] + graph[current][nb];


            if (alt < dist[nb]) {

                dist[nb] = alt;
                prev[nb] = current;

            }
        }
    }


    // Destination cannot be reached
    if (dist[end] === Infinity) {
        return {
            path: [],
            distance: Infinity
        };
    }


    // Reconstruct the route
    const path = [];

    let u = end;

    while (u) {

        path.unshift(u);
        u = prev[u];

    }


    return {
        path,
        distance: dist[end]
    };
}

// HELPERS
function clearPath(clearHighlights = false) {
    if (clearHighlights) {
        activeRouteGroups.clear();
    }

    document.querySelectorAll('.edge')
        .forEach(e => e.classList.remove('active', 'alt-active'));

    document.querySelectorAll('.waypoint')
        .forEach(w => w.classList.remove('start', 'end', 'alt-end', 'on-path'));

    if (clearHighlights) {
        document.querySelectorAll('.waypoint')
            .forEach(w => w.classList.remove('selected-waypoint'));

        document.querySelectorAll('.bordering')
            .forEach(div => div.classList.remove('selected-room'));

        document.body.classList.remove('route-visible');
        document.body.classList.remove('floor2-exclusive');
    }

    const floor2Layer = document.getElementById('floor2-layer');
    if (floor2Layer) {
        FLOOR_2F_BUILDING_KEYS.forEach(building => {
            floor2Layer.classList.remove(`active-building-${building}`);
            floor2Layer.classList.remove(`hovered-building-${building}`);
        });
    }

    const floor3Layer = document.getElementById('floor3-layer');

    if (floor3Layer) {
        floor3Layer.classList.remove('active-building-cmbt');
        floor3Layer.classList.remove('hovered-building-cmbt');
    }
}

function getEdge(a, b) {
    const baseAB = `edge-${a.replace('wp-', '')}-${b.replace('wp-', '')}`;
    const baseBA = `edge-${b.replace('wp-', '')}-${a.replace('wp-', '')}`;

    return document.getElementById(baseAB) ||
        document.getElementById(baseBA) ||
        document.getElementById(baseAB + '-2f') ||
        document.getElementById(baseBA + '-2f') ||
        document.getElementById(baseAB + '-3f') ||
        document.getElementById(baseBA + '-3f');
}

function setFloor2LayerVisible(visible) {
    const layer = document.getElementById('floor2-layer');
    if (!layer) return;
    if (visible) {
        layer.classList.remove('hidden');
        layer.classList.add('visible');
    } else {
        layer.classList.remove('visible');
        layer.classList.add('hidden');
    }
}

function getExclusiveFloor2Building(path) {
    const buildingSet = new Set();
    path.forEach(id => {
        if (FLOOR_2F_BUILDING_MAP[id]) {
            buildingSet.add(FLOOR_2F_BUILDING_MAP[id]);
        }
    });
    return buildingSet.size === 1 ? [...buildingSet][0] : null;
}

function isExclusiveFloor2Path(path) {
    if (!path || path.length === 0) return false;
    return path.every(id => FLOOR_2F_NODE_IDS.has(id));
}

function setFloor2ActiveBuilding(path) {
    const layer = document.getElementById('floor2-layer');
    if (!layer) return;

    FLOOR_2F_BUILDING_KEYS.forEach(b => layer.classList.remove(`active-building-${b}`));
    const building = path ? getExclusiveFloor2Building(path) : null;
    if (building) {
        layer.classList.add(`active-building-${building}`);
    }

    if (building && isExclusiveFloor2Path(path)) {
        document.body.classList.add('floor2-exclusive');
    } else {
        document.body.classList.remove('floor2-exclusive');
    }
}

const BUILDING_GROUPS = {
    cict: {
        name: 'CICT',
        targets: [
            'wp-room222', 'wp-room221', 'wp-room220', 'wp-room219', 'wp-room218', 'wp-guidance',
            'wp-cict', 'wp-clinic', 'wp-conf', 'wp-dean', 'wp-room113', 'wp-cict-coed-faculty', 'wp-room114', 'wp-supplyroom', 'GROUP_INFO_CICT'
        ],
        classes: ['building-cict', 'cict-faculty', 'cict-coed-faculty', 'clinic', 'stairs-cict1', 'info-cict', 'conference-room', 'director-office', 'rm113', 'rm114', 'stairs-cict2', 'supply-room', 'rm222', 'rm221', 'rm220', 'rm219', 'rm218', 'guidance']
    },
    coed: {
        name: 'COED',
        targets: [
            'wp-coed-office', 'wp-ed-tech', 'wp-room215', 'wp-room214', 'wp-room213', 'wp-room212',
            'wp-field-study-office', 'wp-room211', 'wp-cr-staff', 'wp-bsiestock',
            'wp-gender', 'wp-ret', 'wp-nstp', 'wp-room110', 'wp-room109', 'wp-mapeh', 'wp-bped', 'wp-room108'
        ],
        classes: ['building-coed', 'coed-office', 'stairs-educ1', 'cr-staff', 'bsie-stockroom', 'gender', 'ret', 'nstp', 'rm110', 'rm109', 'rmmapeh', 'bped', 'rm108', 'stairs-educ2', 'ed-tech', 'rm215', 'rm214', 'rm213', 'rm212', 'field-study', 'rm211']
    },
    cmbt: {
        name: 'CMBT',
        targets: [
            'wp-room207', 'wp-room208', 'wp-room209', 'wp-room206', 'wp-accreditation', 'wp-anihan',
            'wp-cashier', 'wp-registrar', 'wp-osoad', 'wp-bmgs', 'wp-room107',
            // 3rd Floor Targets
            'wp-room301', 'wp-room302', 'wp-room303', 'wp-library'
        ],
        classes: [
            'building-cmbt', 'stairs-cmbt22', 'rm207', 'rm208', 'rm209', 'stairs-cmbt2mid', 'rm206',
            'accreditation', 'anihan', 'stairs-cmbt21', 'cashier', 'registrar', 'osoad', 'bmgs', 'rm107',
            // 3rd Floor Classes
            'rm301', 'rm302', 'rm303', 'library'
        ]
    },
    lhs: {
        name: 'LHS',
        targets: [
            'wp-room204', 'wp-room203', 'wp-room202', 'wp-room201', 'wp-lhs-principal',
            'wp-room104', 'wp-room103', 'wp-room102', 'wp-room101'
        ],
        classes: ['building-lhs', 'rm204', 'rm203', 'stairs-lhs2', 'rm202', 'rm201', 'lhs', 'rm104', 'rm103', 'stairs-lhs', 'rm102', 'rm101']
    },
    draft: {
        name: 'Drafting',
        targets: ['wp-drafting', 'wp-lab'],
        classes: ['building-draft', 'drafting', 'stairs-lab2', 'stairs-lab']
    }
};


function getGroupForTarget(targetId) {
    if (!targetId) return null;
    for (const [groupKey, groupData] of Object.entries(BUILDING_GROUPS)) {
        if (groupData.targets.includes(targetId)) return groupKey;
    }
    return null;
}

// Ensures selected nodes and room divs remain highlighted
function highlightSelectedRooms(starts, ends) {
    document.querySelectorAll('.waypoint.selected-waypoint')
        .forEach(el => el.classList.remove('selected-waypoint'));

    document.querySelectorAll('.bordering.selected-room')
        .forEach(el => el.classList.remove('selected-room'));

    const allSelected = [...starts, ...ends];
    allSelected.forEach(id => {
        if (!id) return;
        const wpEl = document.getElementById(id);
        if (wpEl) wpEl.classList.add('selected-waypoint');

        const matchingEl = document.querySelector(`[data-target="${id}"]`);
        if (matchingEl) matchingEl.classList.add('selected-room');
    });
}

// Resolve a single waypoint or a multi-node group to the actual graph nodes.
// This must work for both the starting point and the destination, because
// dropdown selections such as “Info Desk (CICT)” are valid group values.
function resolveTargetNodes(targetVal) {

    if (NODE_GROUPS[targetVal]) {
        return [...NODE_GROUPS[targetVal]];
    }

    return [targetVal];
}

// CLICK HANDLERS
let routeStart = null;
let activeRouteGroups = new Set();

// Stores the actual route currently being displayed.
// This lets the floor-toggle system know if the
// route passes through Floor 2 or Floor 3.
let activeRoutePath = [];

function onWaypointClick(target) {

    const selFrom = document.getElementById('sel-from');
    const selTo = document.getElementById('sel-to');


    // =========================================================
    // BOTH ALREADY HAVE VALUES
    // =========================================================
    //
    // Start a completely new route.
    if (selFrom.value && selTo.value) {

        clearPath(true);

        routeStart = target;

        selFrom.value = target;
        selTo.value = '';

        const wpEl = document.getElementById(target);
        const matchingEl =
            document.querySelector(`[data-target="${target}"]`);

        if (wpEl) {
            wpEl.classList.add(
                'start',
                'selected-waypoint'
            );
        }

        if (matchingEl) {
            matchingEl.classList.add('selected-room');
        }

        applyBuildingGroupVisibility();

        document.getElementById('msg').textContent =
            'Starting point selected. Choose destination.';

        return;
    }


    // =========================================================
    // START IS EMPTY
    // =========================================================
    //
    // The first room clicked becomes the starting point.
    if (!selFrom.value) {

        clearPath(true);

        routeStart = target;

        selFrom.value = target;
        selTo.value = '';

        const wpEl = document.getElementById(target);
        const matchingEl =
            document.querySelector(`[data-target="${target}"]`);

        if (wpEl) {
            wpEl.classList.add(
                'start',
                'selected-waypoint'
            );
        }

        if (matchingEl) {
            matchingEl.classList.add('selected-room');
        }

        applyBuildingGroupVisibility();

        document.getElementById('msg').textContent =
            'Starting point selected. Choose destination.';

        return;
    }


    // =========================================================
    // START EXISTS, DESTINATION IS EMPTY
    // =========================================================
    //
    // The clicked room becomes the destination.
    selTo.value = target;

    applyBuildingGroupVisibility();

    findPath();
}

// FIND PATH EXECUTION
function findPath() {

    const selFrom = document.getElementById('sel-from');
    const selTo = document.getElementById('sel-to');

    const startVal = selFrom.value;
    const endVal = selTo.value;


    // =========================================================
    // VALIDATION
    // =========================================================

    if (!startVal || !endVal) {

        activeRouteGroups.clear();
        applyBuildingGroupVisibility();

        document.getElementById('msg').textContent =
            'Please select a starting point and destination.';

        return;
    }


    // =========================================================
    // STARTING POINT(S)
    // =========================================================
    //
    // A group start (for example Info Desk (CICT)) resolves to all actual
    // waypoint nodes in that group, and the shortest valid route among them
    // is chosen automatically.

    const startNodes = resolveTargetNodes(startVal);


    // =========================================================
    // DESTINATION(S)
    // =========================================================
    //
    // A normal destination gives one node.
    //
    // A group destination gives multiple nodes.
    //
    // Example:
    //
    // GROUP_CR_MALE-CMBT
    //       ↓
    // wp-cr-male-j
    // wp-cr-male

    const destinationNodes =
        resolveTargetNodes(endVal);


    // =========================================================
    // FIND SHORTEST VALID ROUTE ACROSS ALL START/END CANDIDATES
    // =========================================================
    // If the destination is a node group, keep the designated primary node
    // (the first node in the group) as the main target, while the nearest
    // alternate node in the same group is rendered as a dashed alternative.

    let bestDistance = Infinity;
    let bestStart = null;
    let bestDestination = null;
    let bestPath = null;

    const primaryDestinationNodes =
        NODE_GROUPS[endVal] && NODE_GROUPS[endVal].length > 0
            ? [NODE_GROUPS[endVal][0]]
            : destinationNodes;


    for (const startNode of startNodes) {
        for (const destinationNode of primaryDestinationNodes) {

            // Don't route a node to itself.
            if (startNode === destinationNode) {
                continue;
            }

            const result =
                dijkstra(startNode, destinationNode);

            // Ignore routes that cannot be reached.
            if (
                !result ||
                !result.path ||
                result.path.length === 0 ||
                !Number.isFinite(result.distance)
            ) {
                continue;
            }

            // Keep the shortest route.
            if (result.distance < bestDistance) {
                bestDistance = result.distance;
                bestStart = startNode;
                bestDestination = destinationNode;
                bestPath = result.path;
            }
        }
    }


    // =========================================================
    // REMOVE OLD ROUTE
    // =========================================================

    clearPath(false);


    // =========================================================
    // NO ROUTE
    // =========================================================

    if (!bestPath || !bestDestination) {

        activeRouteGroups.clear();

        // Forget the previous route
        activeRoutePath = [];

        setFloor2LayerVisible(false);

        const floor3Layer =
            document.getElementById('floor3-layer');

        if (floor3Layer) {
            floor3Layer.classList.remove('visible');
            floor3Layer.classList.add('hidden');
        }

        applyBuildingGroupVisibility();

        document.getElementById('msg').textContent =
            'No route found.';

        return;
    }


    // =========================================================
    // HIGHLIGHT ONLY THE ACTUAL START/DESTINATION NODES USED
    // =========================================================

    highlightSelectedRooms(
        [bestStart],
        [bestDestination]
    );


    // =========================================================
    // FLOOR 2
    // =========================================================

    const floor2Route =
        bestPath.some(
            id => FLOOR_2F_NODE_IDS.has(id)
        );

    setFloor2LayerVisible(floor2Route);
    setFloor2ActiveBuilding(bestPath);


    // =========================================================
    // FLOOR 3
    // =========================================================

    const floor3Route =
        bestPath.some(
            id => FLOOR_3F_NODE_IDS.has(id)
        );

    const floor3Layer =
        document.getElementById('floor3-layer');


    if (floor3Layer) {

        if (floor3Route) {

            floor3Layer.classList.remove('hidden');
            floor3Layer.classList.add('visible');

        } else {

            floor3Layer.classList.remove('visible');
            floor3Layer.classList.add('hidden');

        }
    }

    // =========================================================
    // REMEMBER CURRENT ROUTE
    // =========================================================

    activeRoutePath = [...bestPath];

    const groupedDestination = NODE_GROUPS[endVal];
    const altGroupNodes = groupedDestination
        ? groupedDestination.filter(node => node !== bestDestination)
        : [];

    if (groupedDestination && altGroupNodes.length > 0) {
        let closestGroupNode = null;
        let closestGroupDistance = Infinity;

        for (const groupNode of groupedDestination) {
            for (const startNode of startNodes) {
                if (startNode === groupNode) {
                    closestGroupDistance = 0;
                    closestGroupNode = groupNode;
                    break;
                }

                const route = dijkstra(startNode, groupNode);
                if (!route || !route.path || route.path.length === 0) continue;
                if (route.distance < closestGroupDistance) {
                    closestGroupDistance = route.distance;
                    closestGroupNode = groupNode;
                }
            }
        }

        // If the selected group target is already the closest member in its group,
        // do not render an alternate route for the other members.
        if (bestDestination !== closestGroupNode) {
            const primaryEdgeSet = new Set();
            for (let i = 0; i < bestPath.length - 1; i++) {
                const a = bestPath[i];
                const b = bestPath[i + 1];
                primaryEdgeSet.add([a, b].slice().sort().join('|'));
            }

            let closestAlt = null;

            for (const altNode of altGroupNodes) {
                for (const startNode of startNodes) {
                    if (startNode === altNode) continue;

                    const altRes = dijkstra(startNode, altNode);
                    if (!altRes || !altRes.path || altRes.path.length === 0) continue;

                    if (!closestAlt || altRes.distance < closestAlt.distance) {
                        closestAlt = {
                            ...altRes,
                            start: startNode,
                            end: altNode
                        };
                    }
                }
            }

            if (closestAlt) {
                for (let i = 0; i < closestAlt.path.length - 1; i++) {
                    const a = closestAlt.path[i];
                    const b = closestAlt.path[i + 1];
                    const edge = getEdge(a, b);
                    const edgeKey = [a, b].slice().sort().join('|');

                    if (edge && !primaryEdgeSet.has(edgeKey)) {
                        edge.classList.add('alt-active');
                    }
                }

                const altStartEl = document.getElementById(closestAlt.start);
                const altEndEl = document.getElementById(closestAlt.end);
                if (altStartEl) altStartEl.classList.add('start');
                if (altEndEl) altEndEl.classList.add('alt-end');
            }
        }
    }

    // =========================================================
    // DRAW THE PRIMARY ROUTE
    // =========================================================

    bestPath.forEach((node, index) => {

        const el = document.getElementById(node);

        if (!el) return;


        if (index === 0) {

            el.classList.add('start');

        }
        else if (index === bestPath.length - 1) {

            el.classList.add('end');

        }
        else {

            el.classList.add('on-path');

        }
    });


    // Draw route edges
    for (
        let i = 0;
        i < bestPath.length - 1;
        i++
    ) {

        const edge =
            getEdge(
                bestPath[i],
                bestPath[i + 1]
            );

        if (edge) {

            edge.classList.remove('alt-active');
            edge.classList.add('active');

        }
    }


    // =========================================================
    // ROUTE TEXT
    // =========================================================

    const labels =
        bestPath
            .map(id => {

                if (
                    NODES[id] &&
                    NODES[id].label
                ) {
                    return NODES[id].label;
                }

                return id
                    .replace(/^wp-/, '')
                    .toUpperCase();
            })
            .filter(Boolean);


    document.getElementById('msg').textContent =
        'Quickest Route: ' +
        labels.join(' → ');


    // =========================================================
    // ACTIVE BUILDINGS
    // =========================================================

    activeRouteGroups = new Set(
        bestPath
            .map(
                id => ROOM_BUILDING_MAP[id]?.group
            )
            .filter(Boolean)
    );


    document.body.classList.add('route-visible');

    routeStart = null;

    applyBuildingGroupVisibility();
}

// RESET FUNCTION
function resetNav() {

    clearPath(true);

    activeRouteGroups.clear();

    routeStart = null;

    // Forget the previous route
    activeRoutePath = [];
    // Forget manual floor choices when resetting navigation
    Object.keys(manualFloorOverride).forEach(group => {
        manualFloorOverride[group] = false;
    });

    setFloor2LayerVisible(false);

    const floor3Layer = document.getElementById('floor3-layer');
    if (floor3Layer) {
        floor3Layer.classList.remove('visible');
        floor3Layer.classList.add('hidden');
    }

    document.getElementById('sel-from').value = '';
    document.getElementById('sel-to').value = '';
    document.getElementById('msg').textContent =
        'Click two locations on the map, or use the dropdowns above.';
    applyBuildingGroupVisibility();
}


const selFrom = document.getElementById('sel-from');
const selTo = document.getElementById('sel-to');

// COLLECT ALL OPTIONS IN AN ARRAY FOR SORTING
let dropdownOptions = [
    { label: 'Info Desk (CICT)', value: 'GROUP_INFO_CICT' },
    { label: 'Info Desk (Front)', value: 'GROUP_INFO_FRONT' },
    { label: 'Guard House / Entrance (Closest)', value: 'GROUP_GUARD' },
    { label: 'Canteen (Closest)', value: 'GROUP_CANTEEN' },
    { label: 'CR Male (CMBT)', value: 'GROUP_CR_MALE-CMBT' },
    { label: 'CR Female (COED)', value: 'GROUP_CR_FEMALE-CMBT' },
    { label: 'CR Female (Gym)', value: 'GROUP_CR_FEMALE-GYM' },
    { label: 'CR Male (Gym)', value: 'GROUP_CR_MALE-GYM' }
];

for (const [id, node] of Object.entries(NODES)) {
    const el = document.getElementById(id);
    if (!node.label || !el) continue;
    if (!el.classList.contains('room')) continue;

    dropdownOptions.push({ label: node.label, value: id });
}


// SORT OPTIONS ALPHABETICALLY BY LABEL
dropdownOptions.sort((a, b) => a.label.localeCompare(b.label));


// FUNCTION TO REBUILD THE DROPDOWNS
function rebuildDropdowns(options) {

    // Remember the currently selected locations
    const currentFrom = selFrom.value;
    const currentTo = selTo.value;

    // Remove all existing options
    selFrom.innerHTML = '<option value="">Choose starting point...</option>';
    selTo.innerHTML = '<option value="">Choose destination...</option>';


    // =========================================================
    // CREATE GROUP HEADERS
    // =========================================================

    const sortType = document.getElementById('sel-sort').value;


    // ---------------------------------------------------------
    // A-Z
    // ---------------------------------------------------------
    if (sortType === 'alpha') {

        options.forEach(opt => {

            selFrom.appendChild(
                new Option(opt.label, opt.value)
            );

            selTo.appendChild(
                new Option(opt.label, opt.value)
            );

        });
    }


    // ---------------------------------------------------------
    // BUILDING
    // ---------------------------------------------------------
    else if (sortType === 'building') {

        const buildingGroups = {};

        options.forEach(opt => {

            const data = ROOM_BUILDING_MAP[opt.value];

            // Items without building information
            // go into "Other"
            const building = data
                ? data.group.toUpperCase()
                : 'OTHER';

            if (!buildingGroups[building]) {
                buildingGroups[building] = [];
            }

            buildingGroups[building].push(opt);
        });


        // Building order
        const buildingOrder = [
            'CICT',
            'COED',
            'CMBT',
            'LHS',
            'DRAFT',
            'OTHER'
        ];


        buildingOrder.forEach(building => {

            if (!buildingGroups[building]) return;


            // Create the building header
            const fromGroup = document.createElement('optgroup');
            fromGroup.label = building;

            const toGroup = document.createElement('optgroup');
            toGroup.label = building;


            // Add rooms under the building
            buildingGroups[building].forEach(opt => {

                fromGroup.appendChild(
                    new Option(opt.label, opt.value)
                );

                toGroup.appendChild(
                    new Option(opt.label, opt.value)
                );

            });


            selFrom.appendChild(fromGroup);
            selTo.appendChild(toGroup);

        });
    }


    // ---------------------------------------------------------
    // FLOOR
    // ---------------------------------------------------------
    else if (sortType === 'floor') {

        const floorGroups = {};

        options.forEach(opt => {

            const data = ROOM_BUILDING_MAP[opt.value];

            // Items without floor information
            // go into "Other"
            const floor = data
                ? data.floor
                : 'OTHER';

            if (!floorGroups[floor]) {
                floorGroups[floor] = [];
            }

            floorGroups[floor].push(opt);
        });


        // Floor order
        const floorOrder = [
            1,
            2,
            3,
            'OTHER'
        ];


        floorOrder.forEach(floor => {

            if (!floorGroups[floor]) return;


            // Header text
            let floorLabel;

            if (floor === 1) {
                floorLabel = '1st Floor';
            }
            else if (floor === 2) {
                floorLabel = '2nd Floor';
            }
            else if (floor === 3) {
                floorLabel = '3rd Floor';
            }
            else {
                floorLabel = 'Other';
            }


            // Create the floor header
            const fromGroup = document.createElement('optgroup');
            fromGroup.label = floorLabel;

            const toGroup = document.createElement('optgroup');
            toGroup.label = floorLabel;


            // Add locations under the floor
            floorGroups[floor].forEach(opt => {

                fromGroup.appendChild(
                    new Option(opt.label, opt.value)
                );

                toGroup.appendChild(
                    new Option(opt.label, opt.value)
                );

            });


            selFrom.appendChild(fromGroup);
            selTo.appendChild(toGroup);

        });
    }


    // =========================================================
    // RESTORE PREVIOUS SELECTIONS
    // =========================================================

    if (options.some(opt => opt.value === currentFrom)) {
        selFrom.value = currentFrom;
    }

    if (options.some(opt => opt.value === currentTo)) {
        selTo.value = currentTo;
    }
}


// SORT DROPDOWNS
function sortDropdowns() {

    const sortType = document.getElementById('sel-sort').value;

    // Make a copy so we don't destroy the original list
    let sortedOptions = [...dropdownOptions];


    // ==========================================
    // A-Z
    // ==========================================
    if (sortType === 'alpha') {

        sortedOptions.sort((a, b) =>
            a.label.localeCompare(b.label)
        );
    }


    // ==========================================
    // BUILDING
    // ==========================================
    else if (sortType === 'building') {

        sortedOptions.sort((a, b) => {

            const dataA = ROOM_BUILDING_MAP[a.value];
            const dataB = ROOM_BUILDING_MAP[b.value];

            // If an option is a group such as Canteen,
            // Info Desk, etc., put it at the end.
            if (!dataA && !dataB) {
                return a.label.localeCompare(b.label);
            }

            if (!dataA) return 1;
            if (!dataB) return -1;

            // First sort by building
            const buildingCompare =
                dataA.group.localeCompare(dataB.group);

            if (buildingCompare !== 0) {
                return buildingCompare;
            }

            // Then sort by floor
            if (dataA.floor !== dataB.floor) {
                return dataA.floor - dataB.floor;
            }

            // Finally sort alphabetically
            return a.label.localeCompare(b.label);
        });
    }


    // ==========================================
    // FLOOR
    // ==========================================
    else if (sortType === 'floor') {

        sortedOptions.sort((a, b) => {

            const dataA = ROOM_BUILDING_MAP[a.value];
            const dataB = ROOM_BUILDING_MAP[b.value];

            // Options without floor information go last
            if (!dataA && !dataB) {
                return a.label.localeCompare(b.label);
            }

            if (!dataA) return 1;
            if (!dataB) return -1;

            // First sort by floor
            if (dataA.floor !== dataB.floor) {
                return dataA.floor - dataB.floor;
            }

            // Then sort by building
            const buildingCompare =
                dataA.group.localeCompare(dataB.group);

            if (buildingCompare !== 0) {
                return buildingCompare;
            }

            // Finally sort alphabetically
            return a.label.localeCompare(b.label);
        });
    }


    // If a search query is active, apply filter to the sorted list
    const searchQ = document.getElementById('sel-search')?.value.trim().toLowerCase();
    if (searchQ) {
        sortedOptions = sortedOptions.filter(o => o.label.toLowerCase().includes(searchQ));
    }

    // Rebuild both dropdowns using the new order
    rebuildDropdowns(sortedOptions);



    // FILTER DROPDOWNS BY SEARCH INPUT
    function filterDropdowns() {
        // Re-use the existing sort logic which now respects the search field
        sortDropdowns();
    }
    // Keep the building/floor display synchronized
    syncTogglesFromSelection();
}

// Map rooms to their respective Building Group and Floor Level
const ROOM_BUILDING_MAP = {
    // CICT
    'wp-cict': { group: 'cict', floor: 1 },
    'wp-clinic': { group: 'cict', floor: 1 },
    'wp-conf': { group: 'cict', floor: 1 },
    'wp-dean': { group: 'cict', floor: 1 },
    'wp-room113': { group: 'cict', floor: 1 },
    'wp-room114': { group: 'cict', floor: 1 },
    'wp-supplyroom': { group: 'cict', floor: 1 },
    'wp-room222': { group: 'cict', floor: 2 },
    'wp-room221': { group: 'cict', floor: 2 },
    'wp-room220': { group: 'cict', floor: 2 },
    'wp-room219': { group: 'cict', floor: 2 },
    'wp-room218': { group: 'cict', floor: 2 },
    'wp-guidance': { group: 'cict', floor: 2 },
    'wp-cict-coed-faculty': { group: 'cict', floor: 2 },

    // COED
    'wp-cr-staff': { group: 'coed', floor: 1 },
    'wp-bsiestock': { group: 'coed', floor: 1 },
    'wp-gender': { group: 'coed', floor: 1 },
    'wp-ret': { group: 'coed', floor: 1 },
    'wp-nstp': { group: 'coed', floor: 1 },
    'wp-room110': { group: 'coed', floor: 1 },
    'wp-room109': { group: 'coed', floor: 1 },
    'wp-mapeh': { group: 'coed', floor: 1 },
    'wp-bped': { group: 'coed', floor: 1 },
    'wp-room108': { group: 'coed', floor: 1 },
    'wp-coed-office': { group: 'coed', floor: 2 },
    'wp-ed-tech': { group: 'coed', floor: 2 },
    'wp-room215': { group: 'coed', floor: 2 },
    'wp-room214': { group: 'coed', floor: 2 },
    'wp-room213': { group: 'coed', floor: 2 },
    'wp-room212': { group: 'coed', floor: 2 },
    'wp-field-study-office': { group: 'coed', floor: 2 },
    'wp-room211': { group: 'coed', floor: 2 },

    // CMBT
    'wp-room107': { group: 'cmbt', floor: 1 },
    'wp-cashier': { group: 'cmbt', floor: 1 },
    'wp-registrar': { group: 'cmbt', floor: 1 },
    'wp-osoad': { group: 'cmbt', floor: 1 },
    'wp-bmgs': { group: 'cmbt', floor: 1 },
    'wp-room207': { group: 'cmbt', floor: 2 },
    'wp-room208': { group: 'cmbt', floor: 2 },
    'wp-room209': { group: 'cmbt', floor: 2 },
    'wp-room206': { group: 'cmbt', floor: 2 },
    'wp-accreditation': { group: 'cmbt', floor: 2 },
    'wp-anihan': { group: 'cmbt', floor: 2 },
    // 3rd Floor Nodes
    // Add inside ROOM_BUILDING_MAP under CMBT
    'wp-room301': { group: 'cmbt', floor: 3 },
    'wp-room302': { group: 'cmbt', floor: 3 },
    'wp-room303': { group: 'cmbt', floor: 3 },
    'wp-library': { group: 'cmbt', floor: 3 },

    // LHS
    'wp-lhs-principal': { group: 'lhs', floor: 1 },
    'wp-room104': { group: 'lhs', floor: 1 },
    'wp-room103': { group: 'lhs', floor: 1 },
    'wp-room102': { group: 'lhs', floor: 1 },
    'wp-room101': { group: 'lhs', floor: 1 },
    'wp-room204': { group: 'lhs', floor: 2 },
    'wp-room203': { group: 'lhs', floor: 2 },
    'wp-room202': { group: 'lhs', floor: 2 },
    'wp-room201': { group: 'lhs', floor: 2 },

    // DRAFTING / LAB
    'wp-lab': { group: 'draft', floor: 1 },
    'wp-drafting': { group: 'draft', floor: 2 }
};

// Current floor status for each building group
const buildingFloors = {
    cict: 1,
    coed: 1,
    cmbt: 1,
    lhs: 1,
    draft: 1
};

// Remember that the user manually clicked a floor button.
// This prevents the route logic from immediately overriding the click.
const manualFloorOverride = {
    cict: false,
    coed: false,
    cmbt: false,
    lhs: false,
    draft: false
};

sortDropdowns();


function toggleBuildingFloor(group, floor) {
    // Defensive init: ensure globals exist
    if (typeof buildingFloors === 'undefined') {
        window.buildingFloors = { cict: 1, coed: 1, cmbt: 1, lhs: 1, draft: 1 };
    }
    if (typeof manualFloorOverride === 'undefined') {
        window.manualFloorOverride = { cict: false, coed: false, cmbt: false, lhs: false, draft: false };
    }

    const floorNum = Number(floor);

    console.debug && console.debug('toggleBuildingFloor called', group, floorNum);

    if (
        !Object.prototype.hasOwnProperty.call(buildingFloors, group) ||
        !Number.isInteger(floorNum) ||
        floorNum < 1 ||
        floorNum > 3
    ) {
        return;
    }

    // The user manually selected this building/floor.
    // Multiple independent building toggles may stay active at the same time,
    // except for the explicit CICT + COED paired case.
    manualFloorOverride[group] = true;
    buildingFloors[group] = floorNum;

    updateToggleUI(group, floorNum);

    // IMPORTANT:
    // A manual floor choice must override the
    // route's "floor2-exclusive" display mode.
    document.body.classList.remove('floor2-exclusive');

    applyBuildingGroupVisibility();
}


function toggleCictCoedFloor(floor) {
    // Keep CICT and COED independent. A combined toggle should not
    // overwrite the other building's floor selection.
    return;
}
function updateCombinedToggleUI(floor) {
    const pill = document.querySelector(
        '.building-toggle-pill[data-group="cict-coed"]'
    );

    if (!pill) return;

    const btns = pill.querySelectorAll('.btn');

    btns.forEach(btn => {
        const is1F = btn.textContent.trim() === '1F';

        if (
            (floor === 1 && is1F) ||
            (floor === 2 && !is1F)
        ) {
            btn.classList.add('active', 'btn-primary');
            btn.classList.remove('btn-outline-primary');
        } else {
            btn.classList.remove('active', 'btn-primary');
            btn.classList.add('btn-outline-primary');
        }
    });
}
// Update UI active state on pill buttons
function updateToggleUI(group, floor) {
    const pill = document.querySelector(
        `.building-toggle-pill[data-group="${group}"]`
    );

    if (!pill) return;

    const btns = pill.querySelectorAll('.btn');

    btns.forEach(btn => {
        const buttonFloor = Number(
            btn.textContent.trim().replace('F', '')
        );

        if (buttonFloor === floor) {
            btn.classList.add('active', 'btn-primary');
            btn.classList.remove('btn-outline-primary');
        } else {
            btn.classList.remove('active', 'btn-primary');
            btn.classList.add('btn-outline-primary');
        }
    });
}

// Core Function: Apply 2F/1F filtering based on selections and toggles
function applyBuildingGroupVisibility() {

    // Defensive init: ensure floor state exists if script executed out-of-order
    if (typeof buildingFloors === 'undefined') {
        window.buildingFloors = {
            cict: 1,
            coed: 1,
            cmbt: 1,
            lhs: 1,
            draft: 1
        };
    }

    if (typeof manualFloorOverride === 'undefined') {
        window.manualFloorOverride = {
            cict: false,
            coed: false,
            cmbt: false,
            lhs: false,
            draft: false
        };
    }

    const allGroups = [
        'cict',
        'coed',
        'cmbt',
        'lhs',
        'draft'
    ];


    const fromVal =
        document.getElementById('sel-from')?.value;

    const toVal =
        document.getElementById('sel-to')?.value;


    // =========================================================
    // GET REAL FLOOR DATA FOR A NORMAL ROOM OR A GROUP
    // =========================================================

    function getSelectionFloorData(value) {

        if (!value) {
            return [];
        }


        // Normal room/waypoint
        if (ROOM_BUILDING_MAP[value]) {
            return [
                ROOM_BUILDING_MAP[value]
            ];
        }


        // Group destination
        if (NODE_GROUPS[value]) {

            return NODE_GROUPS[value]
                .map(id => ROOM_BUILDING_MAP[id])
                .filter(Boolean);

        }


        return [];
    }


    const fromData =
        getSelectionFloorData(fromVal);

    const toData =
        getSelectionFloorData(toVal);


    // =========================================================
    // ACTUAL ROUTE FLOOR USAGE
    // =========================================================

    const routeUsesFloor2 =
        activeRoutePath.some(
            id => FLOOR_2F_NODE_IDS.has(id)
        );


    const routeUsesFloor3 =
        activeRoutePath.some(
            id => FLOOR_3F_NODE_IDS.has(id)
        );


    // =========================================================
    // ENDPOINT FLOOR USAGE
    // =========================================================

    const endpointUsesFloor2 =
        [...fromData, ...toData].some(
            data => data.floor === 2
        );


    const endpointUsesFloor3 =
        [...fromData, ...toData].some(
            data => data.floor === 3
        );


    // =========================================================
    // FINAL ANSWER
    //
    // Show the floor if:
    //
    // 1. The starting point is there
    // OR
    // 2. The destination is there
    // OR
    // 3. The actual route passes there
    // =========================================================

    const usesFloor2 =
        routeUsesFloor2 ||
        endpointUsesFloor2;


    const usesFloor3 =
        routeUsesFloor3 ||
        endpointUsesFloor3;

    /*
     * ============================================================
     * CICT + COED SPECIAL CASE
     * ============================================================
     *
     * Combined toggle appears ONLY when:
     *
     *   FROM = CICT
     *   TO   = COED
     *
     * OR:
     *
     *   FROM = COED
     *   TO   = CICT
     *
     * Being somewhere along the route does NOT count.
     */
    // =========================================================
    // CICT + COED SPECIAL CASE
    // =========================================================

    const fromCict2F =
        fromData.some(
            data =>
                data.group === 'cict' &&
                data.floor === 2
        );

    const fromCoed2F =
        fromData.some(
            data =>
                data.group === 'coed' &&
                data.floor === 2
        );

    const toCict2F =
        toData.some(
            data =>
                data.group === 'cict' &&
                data.floor === 2
        );

    const toCoed2F =
        toData.some(
            data =>
                data.group === 'coed' &&
                data.floor === 2
        );


    const isCictCoedPair =
        (
            (fromCict2F && toCoed2F) ||
            (fromCoed2F && toCict2F)
        );

    const combinedPill = document.querySelector(
        '.building-toggle-pill[data-group="cict-coed"]'
    );

    if (combinedPill) {
        combinedPill.style.display = 'none';
    }

    /*
     * ============================================================
     * SOLO TOGGLES
     * ============================================================
     *
     * If CICT + COED are the two endpoints:
     *     hide BOTH solo toggles.
     *
     * Otherwise:
     *     keep the normal solo behavior.
     */
    allGroups.forEach(group => {
        const pill = document.querySelector(
            `.building-toggle-pill[data-group="${group}"]`
        );

        if (!pill) return;

        let shouldShowSolo = false;

        /*
         * Only a higher-floor endpoint in this building group should show
         * the toggle. A 1F room selection should never display it.
         */
        const endpointData = [
            ...fromData,
            ...toData
        ].filter(
            data => data && data.group === group
        );

        if (endpointData.length > 0) {
            shouldShowSolo = endpointData.some(
                data => data.floor > 1
            );
        }

        pill.style.display = shouldShowSolo ? 'flex' : 'none';
    });

    /*
     * ============================================================
     * FLOOR 2 VISIBILITY
     * ============================================================
     */
    const floor2Layer = document.getElementById('floor2-layer');

    // Show Floor 2 when:
    // 1. A building is manually set to 2F
    // OR
    // 2. The route uses Floor 2 AND the user has NOT manually
    //    turned off the relevant building's 2F view.
    const anyGroup2FVisible = allGroups.some(group => {
        const groupFloor = buildingFloors[group];

        if (groupFloor !== 2) return false;

        if (manualFloorOverride[group]) {
            return true;
        }

        const groupFromUses = fromData.some(
            data => data && data.group === group
        );

        const groupToUses = toData.some(
            data => data && data.group === group
        );

        return groupFromUses || groupToUses;
    });

    if (floor2Layer) {
        if (anyGroup2FVisible) {
            floor2Layer.classList.remove('hidden');
            floor2Layer.classList.add('visible');
        } else {
            floor2Layer.classList.remove('visible');
            floor2Layer.classList.add('hidden');
        }
    }

    /*
 * ============================================================
 * SHOW/HIDE 2F BUILDING ELEMENTS
 * ============================================================
 */

    allGroups.forEach(group => {

        const groupFloor = buildingFloors[group];

        const group2FElements = document.querySelectorAll(
            `#floor2-layer .building-${group}`
        );

        // Does the selected START point belong to this building?
        const fromUsesThisGroup = fromData.some(
            data => data && data.group === group
        );

        // Does the selected DESTINATION belong to this building?
        const toUsesThisGroup = toData.some(
            data => data && data.group === group
        );

        group2FElements.forEach(el => {

            /*
             * ====================================================
             * CICT + COED COMBINED MODE
             * ====================================================
             */

            if (
                isCictCoedPair &&
                (group === 'cict' || group === 'coed')
            ) {

                if (groupFloor === 2) {
                    el.classList.remove('building-group-hidden');
                } else {
                    el.classList.add('building-group-hidden');
                }

                return;
            }


            /*
             * ====================================================
             * NORMAL BUILDING MODE
             * ====================================================
             *
             * The building is visible on 2F when:
             *
             * 1. The toggle is currently on 2F
             *
             * AND
             *
             * 2. The selected start OR destination belongs
             *    to this building.
             */

            // Show this building's 2F only when it is relevant to its own group.
            // Each toggle is independent: a 1F click on one building should not
            // suppress the 2F of another building, and multiple 2F groups may stay
            // active at the same time.
            if (manualFloorOverride[group] && groupFloor === 1) {
                el.classList.add('building-group-hidden');
                return;
            }

            const manualThisGroup2FActive =
                manualFloorOverride[group] && groupFloor === 2;

            const show2F =
                groupFloor === 2 && (
                    manualThisGroup2FActive ||
                    (!manualFloorOverride[group] && (fromUsesThisGroup || toUsesThisGroup))
                );

            if (show2F) {
                el.classList.remove('building-group-hidden');
            } else {
                el.classList.add('building-group-hidden');
            }

        });
    });
    /*
* ============================================================
* FLOOR 3 VISIBILITY
* ============================================================
*/
    const floor3Layer = document.getElementById('floor3-layer');

    // CMBT 3F is visible when:
    // 1. CMBT was manually switched to 3F
    // OR
    // 2. The route uses 3F and the user has not manually
    //    switched CMBT away from 3F.
    const manual3FActive =
        buildingFloors.cmbt === 3 &&
        manualFloorOverride.cmbt;

    const automatic3FActive =
        usesFloor3 &&
        !(
            manualFloorOverride.cmbt &&
            buildingFloors.cmbt !== 3
        );

    const isFloor3Active =
        manual3FActive || automatic3FActive;

    if (floor3Layer) {
        if (isFloor3Active) {
            floor3Layer.classList.remove('hidden');
            floor3Layer.classList.add('visible');
        } else {
            floor3Layer.classList.remove('visible');
            floor3Layer.classList.add('hidden');
        }
    }

    /*
     * ============================================================
     * SHOW/HIDE 3F CMBT ELEMENTS
     * ============================================================
     */

    const floor3Elements = document.querySelectorAll(
        '#floor3-layer .building-cmbt'
    );

    floor3Elements.forEach(el => {
        if (buildingFloors.cmbt === 3) {
            el.classList.remove('building-group-hidden');
        } else {
            el.classList.add('building-group-hidden');
        }
    });
}

// Auto-sync building floor toggles when dropdown selection changes
function syncTogglesFromSelection() {
    // Defensive init: prevent TDZ ReferenceError if variables aren't declared yet
    if (typeof buildingFloors === 'undefined') {
        window.buildingFloors = { cict: 1, coed: 1, cmbt: 1, lhs: 1, draft: 1 };
    }

    if (typeof manualFloorOverride === 'undefined') {
        window.manualFloorOverride = { cict: false, coed: false, cmbt: false, lhs: false, draft: false };
    }
    const fromVal = document.getElementById('sel-from')?.value;
    const toVal = document.getElementById('sel-to')?.value;

    // Normalize selection into an array of building/floor data objects.
    function selectionFloorData(value) {
        if (!value) return [];
        // Exact room
        if (ROOM_BUILDING_MAP[value]) return [ROOM_BUILDING_MAP[value]];
        // Group destination (e.g. NODE_GROUPS)
        if (NODE_GROUPS[value]) {
            return NODE_GROUPS[value]
                .map(id => ROOM_BUILDING_MAP[id])
                .filter(Boolean);
        }
        return [];
    }

    const fromData = selectionFloorData(fromVal);
    const toData = selectionFloorData(toVal);

    // Combined CICT + COED mode ONLY when:
    // CICT 2F <-> COED 2F
    // =========================================================
    // CICT + COED SPECIAL CASE
    // =========================================================

    const fromCict2F = fromData.some(data => data.group === 'cict' && data.floor === 2);
    const fromCoed2F = fromData.some(data => data.group === 'coed' && data.floor === 2);
    const toCict2F = toData.some(data => data.group === 'cict' && data.floor === 2);
    const toCoed2F = toData.some(data => data.group === 'coed' && data.floor === 2);


    const isCictCoedPair =
        (
            (fromCict2F && toCoed2F) ||
            (fromCoed2F && toCict2F)
        );

    // Keep each building group independent. Do not force the COED
    // toggle to follow the CICT toggle or vice versa.
    [fromData, toData].forEach(roomData => {

        if (!roomData) return;

        // If the user manually selected a floor,
        // DO NOT overwrite it with the endpoint's floor.
        if (manualFloorOverride[roomData.group]) {
            return;
        }

        buildingFloors[roomData.group] = roomData.floor;

        updateToggleUI(
            roomData.group,
            roomData.floor
        );
    });

    updateCombinedToggleUI(1);

    applyBuildingGroupVisibility();
}


// button for debugging
function toggleDebugNodes() {
    document.body.classList.toggle('show-all-nodes');
}

// Instrument toggle buttons to help debugging click delivery
(function attachToggleDebuggers() {
    const container = document.getElementById('building-floor-toggles');
    if (!container) return;
    const btns = container.querySelectorAll('.building-toggle-pill button');
    btns.forEach(btn => {
        btn.addEventListener('click', (e) => {
            const pill = btn.closest('.building-toggle-pill');
            const group = pill?.dataset?.group || 'unknown';
            console.debug && console.debug('toggle button clicked', group, btn.textContent.trim());
        });
    });
})();