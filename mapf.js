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


function createBuildingTranslateHandler(buildingId) {
    return function (event) {
        const dx = Number(event?.detail?.dx || 0);
        const dy = Number(event?.detail?.dy || 0);
        moveBuildingGroup(buildingId, dx, dy);
    };
}

window.createBuildingTranslateHandler = createBuildingTranslateHandler;

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
    ['wp-cr-staff', 'wp-junction7'],
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
    const allSelected = [...starts, ...ends];
    allSelected.forEach(id => {
        if (!id) return;
        const wpEl = document.getElementById(id);
        if (wpEl) wpEl.classList.add('selected-waypoint');

        const matchingEl = document.querySelector(`[data-target="${id}"]`);
        if (matchingEl) matchingEl.classList.add('selected-room');
    });
}

// RESOLVE DESTINATION GROUPS ONLY
//
// Group logic is intentionally used ONLY for destinations.
// Starting points remain exactly as selected.
function resolveDestinationTargets(targetVal) {

    if (NODE_GROUPS[targetVal]) {
        return NODE_GROUPS[targetVal];
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
    // STARTING POINT
    // =========================================================
    //
    // IMPORTANT:
    // Starting points are ALWAYS exact.
    //
    // We do NOT resolve NODE_GROUPS here.

    const startNode = startVal;


    // =========================================================
    // DESTINATION
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
        resolveDestinationTargets(endVal);


    // =========================================================
    // FIND SHORTEST VALID DESTINATION
    // =========================================================

    let bestDistance = Infinity;
    let bestDestination = null;
    let bestPath = null;


    for (const destinationNode of destinationNodes) {

        // Don't route a node to itself.
        if (startNode === destinationNode) {
            continue;
        }


        const result =
            dijkstra(startNode, destinationNode);


        // Ignore destinations that cannot be reached.
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
            bestDestination = destinationNode;
            bestPath = result.path;

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
    // HIGHLIGHT ONLY THE ACTUAL DESTINATION
    // =========================================================

    highlightSelectedRooms(
        [startNode],
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
    const sortedOptions = [...dropdownOptions];


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


    // Rebuild both dropdowns using the new order
    rebuildDropdowns(sortedOptions);

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
sortDropdowns();