
    // FACILITY GROUPS FOR AUTOMATIC NEAREST SELECTION (Updated to mirror Info Desk multi-node routing logic)
    const NODE_GROUPS = {
      'GROUP_CANTEEN': ['wp-canteen', 'wp-canteen2'],
      'GROUP_CR_FEMALE-CMBT': ['wp-crFem', 'wp-cr-female'],
      'GROUP_CR_FEMALE-GYM': ['wp-cr-female', 'wp-crFem'],
      'GROUP_CR_MALE-CMBT': ['wp-cr-male-j','wp-cr-male'],
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
      'wp-infoDesk': { x: 690, y: 175},
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
      'wp-cr-male': { x: 230, y: 600},
      'wp-cr-female': { x: 230, y: 375},
      'wp-cr-male-j': { x: 110, y: 870 },

      /* JUNCTIONS & WAYPOINTS */
      'wp-info-front': { x: 465, y: 1000},
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
      'wp-1stair-cmbt-mid': { x: 413, y: 1000 },
      'wp-open-court': { x: 345, y: 685 },
      'wp-1stair-draft': { x: 260, y: 685 },
      'wp-chempath': { x: 200, y: 685 },
      'wp-1stair-coed1': { x: 167, y: 845 },
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
      'wp-junction37': {x:200, y:375},
      'wp-junction38':{x:200, y:515},
      'wp-junction39':{x:200, y:600}
    };

    const FLOOR_2F_NODES = {
      'wp-room221': { x: 800, y: 175, label: 'Room 221' },
      'wp-guidance': { x: 690, y: 125, label: 'Guidance' },
      'wp-room222': { x: 840, y: 175, label: 'Room 222' },
      'wp-room220': { x: 625, y: 175, label: 'Room 220' },
      'wp-room219': { x: 530, y: 175, label: 'Room 219' },
      'wp-room218': { x: 408, y: 175, label: 'Room 218' },
      'wp-coed-faculty': { x: 340, y: 175, label: 'COED Faculty' },
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
      'wp-2stair-right-cmbt': { x: 935, y: 1000 },
      'wp-2j-cmbt-stair': { x: 115, y: 1000 },
      'wp-2stair-cmbt-mid': { x: 470, y: 1000 },
      'wp-2stair-lhs': { x: 990, y: 570 },
      'wp-2stair-coed1': { x: 167, y: 830 },
      'wp-2stair-coed2': { x: 167, y: 170 },
      'wp-2stair-cict': { x: 690, y: 175 },
      'wp-2stair-cict2': { x: 270, y: 175 },
      'wp-2stair-draft': { x: 260, y: 685 }
    };

    const FLOOR_2F_NODE_IDS = new Set(Object.keys(FLOOR_2F_NODES));
    Object.assign(NODES, FLOOR_2F_NODES);

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
      ['wp-cr-staff', 'wp-junction7'],
      ['wp-cr-staff', 'wp-1stair-cict2'],
      ['wp-bsiestock', 'wp-junction7'],
      ['wp-bsiestock', 'wp-canteen-hall'],
      ['wp-junction7', 'wp-canteen-hall'],
      ['wp-canteen-hall', 'wp-canteen'],
      ['wp-canteen-hall', 'wp-junction33'],
      ['wp-canteen-hall', 'wp-gender'],
      ['wp-gender', 'wp-ret'],
      ['wp-ret', 'wp-junction31'],
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
      ['wp-room218', 'wp-coed-faculty'],
      ['wp-coed-faculty', 'wp-2stair-cict2'],
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

    const FLOOR_CONNECTIONS = [
      ['wp-1stair-coed1', 'wp-2stair-coed1'],
      ['wp-1stair-coed2', 'wp-2stair-coed2'],
      ['wp-1stair-cict', 'wp-2stair-cict'],
      ['wp-1stair-cict2', 'wp-2stair-cict2'],
      ['wp-1stair-lhs', 'wp-2stair-lhs'],
      ['wp-1stair-draft', 'wp-2stair-draft'],
      ['wp-1stair-right-cmbt', 'wp-2stair-right-cmbt'],
      ['wp-1j-cmbt-stair', 'wp-2j-cmbt-stair'],
      ['wp-1stair-cmbt-mid', 'wp-2stair-cmbt-mid']
    ];

    EDGES.push(...EDGES_2F, ...FLOOR_CONNECTIONS);

    // BUILD GRAPH
    const graph = {};
    for (const id in NODES) {
      graph[id] = {};
    }

    function distance(a, b) {
      return Math.hypot(
        NODES[a].x - NODES[b].x,
        NODES[a].y - NODES[b].y
      );
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
        line.setAttribute('class', 'edge');

        const both2f = FLOOR_2F_NODE_IDS.has(a) && FLOOR_2F_NODE_IDS.has(b);
        const targetLayer = both2f ? layer2 : layer1;
        const edgeId = `edge-${a.replace('wp-', '')}-${b.replace('wp-', '')}` + (both2f ? '-2f' : '');
        line.setAttribute('id', edgeId);

        if (targetLayer) {
          targetLayer.appendChild(line);
        }
      });
    }

    renderEdges();

    // DIJKSTRA PATHFINDING
    function dijkstra(start, end) {
      const dist = {};
      const prev = {};
      const unvisited = new Set(Object.keys(graph));

      for (const node of unvisited) {
        dist[node] = Infinity;
        prev[node] = null;
      }

      dist[start] = 0;

      while (unvisited.size) {
        let current = [...unvisited].reduce((a, b) => dist[a] < dist[b] ? a : b);
        if (current === end || dist[current] === Infinity) break;

        unvisited.delete(current);

        for (const nb in graph[current]) {
          const alt = dist[current] + graph[current][nb];
          if (alt < dist[nb]) {
            dist[nb] = alt;
            prev[nb] = current;
          }
        }
      }

      const path = [];
      let u = end;
      while (u) {
        path.unshift(u);
        u = prev[u];
      }
      return { path, distance: dist[end] };
    }

    // HELPERS
    function clearPath(clearHighlights = false) {
      document.querySelectorAll('.edge')
        .forEach(e => e.classList.remove('active', 'alt-active'));

      document.querySelectorAll('.waypoint')
        .forEach(w => w.classList.remove('start', 'end', 'alt-end', 'on-path'));

      if (clearHighlights) {
        document.querySelectorAll('.waypoint')
          .forEach(w => w.classList.remove('selected-waypoint'));

        document.querySelectorAll('.bordering')
          .forEach(div => div.classList.remove('selected-room'));
      }
    }

    function getEdge(a, b) {
      const baseAB = `edge-${a.replace('wp-', '')}-${b.replace('wp-', '')}`;
      const baseBA = `edge-${b.replace('wp-', '')}-${a.replace('wp-', '')}`;
      return document.getElementById(baseAB) ||
        document.getElementById(baseBA) ||
        document.getElementById(baseAB + '-2f') ||
        document.getElementById(baseBA + '-2f');
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

    // Ensures selected nodes and room divs remain highlighted
    function highlightSelectedRooms(starts, ends) {
      const allSelected = [...starts, ...ends];
      allSelected.forEach(id => {
        if (!id) return;
        const wpEl = document.getElementById(id);
        if (wpEl) wpEl.classList.add('selected-waypoint');

        const matchingEl = document.querySelector(`[data-target="${id}"]`);
        if (matchingEl) matchingEl.classList.add('selected-room');
      });
    }

    // RESOLVE GROUPS OR SINGLE NODES
    function resolveTargets(targetVal) {
      if (NODE_GROUPS[targetVal]) {
        return NODE_GROUPS[targetVal];
      }
      return [targetVal];
    }

    // CLICK HANDLERS
    let routeStart = null;

    function onWaypointClick(target) {
      const selFrom = document.getElementById('sel-from');
      const selTo = document.getElementById('sel-to');

      let resolvedId = target;

      if (NODE_GROUPS[target]) {
        resolvedId = NODE_GROUPS[target][0];
      }

      const wpEl = document.getElementById(resolvedId);
      if (wpEl) wpEl.classList.add('selected-waypoint');

      const matchingEl = document.querySelector(`[data-target="${target}"]`);
      if (matchingEl) matchingEl.classList.add('selected-room');

      // If starting a new route selection
      if (!routeStart || (selFrom.value && selTo.value)) {
        routeStart = resolvedId;
        clearPath(true);

        selFrom.value = target;
        selTo.value = '';

        if (wpEl) wpEl.classList.add('start', 'selected-waypoint');
        if (matchingEl) matchingEl.classList.add('selected-room');

        document.getElementById('msg').textContent =
          'Starting point selected. Choose destination.';
        return;
      }

      // Otherwise set destination and draw route
      selTo.value = target;
      findPath();
    }

    // FIND PATH EXECUTION
    function findPath() {
      const startVal = document.getElementById('sel-from').value;
      const endVal = document.getElementById('sel-to').value;

      if (!startVal || !endVal) {
        alert('Please select start and destination.');
        return;
      }

      let starts = resolveTargets(startVal);
      if (routeStart && (starts.includes(routeStart) || startVal === routeStart)) {
        starts = [routeStart];
      }

      const ends = resolveTargets(endVal);

      let minDistance = Infinity;
      let primaryStart = null;
      let primaryEnd = null;
      let primaryPath = null;

      starts.forEach(s => {
        ends.forEach(e => {
          if (s === e) return;
          const res = dijkstra(s, e);
          if (res.distance < minDistance) {
            minDistance = res.distance;
            primaryPath = res.path;
            primaryStart = s;
            primaryEnd = e;
          }
        });
      });

      clearPath(false);
      highlightSelectedRooms(starts, ends);

      const floor2Route = primaryPath.some(id => FLOOR_2F_NODE_IDS.has(id));
      setFloor2LayerVisible(floor2Route);

      if (!primaryPath || primaryPath.length === 0) {
        document.getElementById('msg').textContent = 'No route found.';
        return;
      }

      // 1. Draw Alternative/Secondary Paths (Gray, Dashed Line)
      starts.forEach(s => {
        ends.forEach(e => {
          if (s === e) return;
          const altRes = dijkstra(s, e);
          if (altRes.path && altRes.path.length > 0) {
            const altElStart = document.getElementById(s);
            if (altElStart && s !== primaryStart) altElStart.classList.add('start');

            const altElEnd = document.getElementById(e);
            if (altElEnd && e !== primaryEnd) altElEnd.classList.add('alt-end');

            for (let i = 0; i < altRes.path.length - 1; i++) {
              const edge = getEdge(altRes.path[i], altRes.path[i + 1]);
              if (edge && !edge.classList.contains('active')) {
                edge.classList.add('alt-active');
              }
            }
          }
        });
      });

      // 2. Draw Primary Shortest Path (Green, Solid Line)
      primaryPath.forEach((node, index) => {
        const el = document.getElementById(node);
        if (!el) return;

        if (index === 0) el.classList.add('start');
        else if (index === primaryPath.length - 1) el.classList.add('end');
      });

      for (let i = 0; i < primaryPath.length - 1; i++) {
        const edge = getEdge(primaryPath[i], primaryPath[i + 1]);
        if (edge) {
          edge.classList.remove('alt-active');
          edge.classList.add('active');
        }
      }

      const labels = primaryPath
        .filter(id => NODES[id] && NODES[id].label)
        .map(id => NODES[id].label);

      document.getElementById('msg').textContent =
        'Quickest Route: ' + labels.join(' → ');

      routeStart = null;
    }

    // RESET FUNCTION
    function resetNav() {
      clearPath(true);
      routeStart = null;

      document.getElementById('sel-from').value = '';
      document.getElementById('sel-to').value = '';
      document.getElementById('msg').textContent =
        'Click two locations on the map, or use the dropdowns above.';
    }

    document.addEventListener('DOMContentLoaded', () => {
      // Bind click listeners securely using explicit data-target attributes
      document.querySelectorAll('.bordering[data-target]').forEach(el => {
        el.addEventListener('click', (e) => {
          const targetValue = el.getAttribute('data-target');
          if (targetValue) {
            onWaypointClick(targetValue);
          }
        });
      });
    });

    const selFrom = document.getElementById('sel-from');
    const selTo = document.getElementById('sel-to');

    // COLLECT ALL OPTIONS IN AN ARRAY FOR SORTING
    let dropdownOptions = [
      { label: 'Info Desk (CICT)', value: 'GROUP_INFO_CICT' },
      { label: 'Info Desk (Front)', value: 'GROUP_INFO_FRONT' },
      { label: 'Guard House / Entrance (Closest)', value: 'GROUP_GUARD' },
      { label: 'Canteen (Closest)', value: 'GROUP_CANTEEN' },
      { label: 'CR Female', value: 'GROUP_CR_FEMALE' },
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

    // POPULATE THE DROPDOWNS
    dropdownOptions.forEach(opt => {
      selFrom.appendChild(new Option(opt.label, opt.value));
      selTo.appendChild(new Option(opt.label, opt.value));
    });
  