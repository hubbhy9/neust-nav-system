<?php
$defaultLayoutFile = file_exists(__DIR__ . '/map.json') ? 'map.json' : 'map-layout.json';
$selectedLayoutFile = isset($_GET['file']) ? basename($_GET['file']) : $defaultLayoutFile;
$layoutPath = __DIR__ . '/' . $selectedLayoutFile;
$layout = ['nodes' => [], 'edges' => [], 'buildings' => [], 'lines' => []];

if (!preg_match('/\.json$/i', $selectedLayoutFile) || !file_exists($layoutPath)) {
  $selectedLayoutFile = $defaultLayoutFile;
  $layoutPath = __DIR__ . '/' . $selectedLayoutFile;
}

if (file_exists($layoutPath)) {
  $raw = file_get_contents($layoutPath);
  $decoded = json_decode($raw, true);
  if (is_array($decoded)) {
    $layout = $decoded;
  }
}

$availableLayouts = [];
foreach (glob(__DIR__ . '/*.json') ?: [] as $filePath) {
  $availableLayouts[] = basename($filePath);
}
sort($availableLayouts, SORT_STRING);

$nodes = $layout['nodes'] ?? [];
$edges = $layout['edges'] ?? [];
$buildings = $layout['buildings'] ?? [];
$lines = $layout['lines'] ?? [];

$nodeList = [];
foreach ($nodes as $node) {
  $nodeList[] = $node;
}

$buildingList = [];
foreach ($buildings as $building) {
  $buildingList[] = $building;
}

$lineList = [];
foreach ($lines as $line) {
  $lineList[] = $line;
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Campus Map from JSON</title>
  <style>
    :root {
      --panel-bg: #f4f7ff;
      --border: #d6dff7;
      --text: #1b2434;
      --muted: #5f7295;
      --primary: #2663eb;
      --danger: #dc2626;
      --success: #1ea97c;
    }

    * {
      box-sizing: border-box;
    }

    body {
      margin: 0;
      font-family: Arial, sans-serif;
      background: linear-gradient(135deg, #edf3ff 0%, #f7f9fd 100%);
      color: var(--text);
    }

    .app {
      display: flex;
      min-height: 100vh;
    }

    .sidebar {
      width: 320px;
      padding: 18px;
      background: rgba(255, 255, 255, 0.95);
      border-right: 1px solid var(--border);
    }

    .panel {
      background: var(--panel-bg);
      border: 1px solid var(--border);
      border-radius: 14px;
      padding: 12px;
      margin-bottom: 16px;
    }

    .panel h3 {
      margin: 0 0 10px;
      font-size: 12px;
      letter-spacing: 0.08em;
      text-transform: uppercase;
      color: var(--muted);
    }

    .field {
      display: flex;
      flex-direction: column;
      margin-bottom: 10px;
      font-size: 13px;
      color: var(--muted);
      font-weight: 700;
    }

    .field select,
    .field input {
      margin-top: 6px;
      padding: 9px 10px;
      border-radius: 9px;
      border: 1px solid var(--border);
      font: inherit;
    }

    button {
      cursor: pointer;
      border: 1px solid var(--border);
      background: white;
      border-radius: 10px;
      padding: 9px 12px;
      font: inherit;
      color: var(--text);
    }

    button.primary {
      background: var(--primary);
      border-color: var(--primary);
      color: white;
    }

    button.danger {
      background: rgba(220, 38, 38, 0.08);
      color: var(--danger);
      border-color: rgba(220, 38, 38, 0.14);
    }

    .stack {
      display: grid;
      gap: 8px;
    }

    .workspace {
      flex: 1;
      display: flex;
      flex-direction: column;
      min-height: 100vh;
      padding: 14px;
      background: linear-gradient(135deg, #edf3ff 0%, #f7f9fd 100%);
    }

    .canvas-wrap {
      flex: 1;
      border: none;
      border-radius: 16px;
      overflow: hidden;
      background: rgba(255,255,255,0.88);
      position: relative;
      box-shadow: none;
    }

    .canvas-wrap::before {
      display: none;
    }

    svg {
      width: 100%;
      height: 100%;
      display: block;
      background:
        linear-gradient(#f9fbff, #f5f8ff),
        repeating-linear-gradient(
          0deg,
          rgba(29, 78, 216, 0.025),
          rgba(29, 78, 216, 0.025) 1px,
          transparent 1px,
          transparent 24px
        ),
        repeating-linear-gradient(
          90deg,
          rgba(29, 78, 216, 0.025),
          rgba(29, 78, 216, 0.025) 1px,
          transparent 1px,
          transparent 24px
        );
      user-select: none;
      max-width: none;
      border: 2px solid rgba(38, 99, 235, 0.3);
    }

    .node text {
      font-size: 12px;
      fill: #000000;
      text-anchor: middle;
      dominant-baseline: middle;
      pointer-events: none;
    }

    .edge {
      stroke: rgba(68, 110, 180, 0.9);
      stroke-width: 3;
      fill: none;
      stroke-linecap: round;
    }

    .edge.route {
      stroke: var(--success);
      stroke-width: 6;
      stroke-dasharray: 10 8;
    }

    .edge.route.active {
      stroke-width: 8;
      stroke-dasharray: none;
      stroke-linecap: round;
      filter: drop-shadow(0 0 6px rgba(0, 0, 0, 0.25));
    }

    .edge.route.active.route-reveal {
      stroke-dasharray: 0 var(--route-length, 200);
      stroke-dashoffset: var(--route-length, 200);
      animation: routeReveal var(--route-duration, 2200ms) cubic-bezier(0.17, 0.84, 0.44, 1)
        var(--route-delay, 0ms) both;
    }

    .edge.route.active.route-stair {
      stroke-dasharray: 0 18;
      stroke-dashoffset: var(--route-length, 200);
      animation: routeRevealStair var(--route-duration, 2200ms) cubic-bezier(0.22, 1, 0.36, 1)
        var(--route-delay, 0ms) both;
    }

    @keyframes routeReveal {
      0% {
        stroke-dasharray: 0 var(--route-length, 200);
        stroke-dashoffset: var(--route-length, 200);
        opacity: 0.2;
      }

      35% {
        opacity: 1;
      }

      100% {
        stroke-dasharray: var(--route-length, 200) 0;
        stroke-dashoffset: 0;
        opacity: 1;
        filter: drop-shadow(0 0 7px rgba(22, 163, 124, 0.75));
      }
    }

    @keyframes routeRevealStair {
      0% {
        stroke-dasharray: 0 18;
        stroke-dashoffset: var(--route-length, 200);
        opacity: 0.2;
      }

      40% {
        opacity: 1;
      }

      70% {
        stroke-dasharray: calc(var(--route-length, 200) * 0.35) 12;
        stroke-dashoffset: calc(var(--route-length, 200) * 0.6);
      }

      100% {
        stroke-dasharray: var(--route-length, 200) 0;
        stroke-dashoffset: 0;
        opacity: 1;
      }
    }

    .edge.route.alt-active {
      stroke: #8d99ae;
      stroke-width: 5;
      stroke-dasharray: 12 10;
      opacity: 0.95;
    }

    .node-body {
      stroke: rgba(20, 52, 94, 0.75);
      stroke-width: 1.5;
      cursor: pointer;
    }

    .node.route-start .node-body {
      stroke: #0ea5e9;
      stroke-width: 3;
    }

    .node.route-end .node-body {
      stroke: #16a34a;
      stroke-width: 3;
    }

    .node.route-alt .node-body {
      stroke: #f59e0b;
      stroke-width: 3;
    }

    .node.selected .node-body {
      stroke: #f59e0b;
      stroke-width: 3;
    }

    .building-shell {
      fill: rgba(38, 99, 235, 0.08);
      stroke: rgba(38, 99, 235, 0.55);
      stroke-width: 1.5;
      stroke-dasharray: 8 8;
    }

    .status {
      font-size: 13px;
      color: var(--muted);
      margin-top: 8px;
    }

    .floor-toggle-row {
      display: flex;
      gap: 8px;
      flex-wrap: wrap;
      margin-top: 8px;
    }

    .floor-toggle-btn {
      flex: 1 1 0;
      min-width: 58px;
      padding: 8px 10px;
      border-radius: 10px;
      border: 1px solid rgba(38, 99, 235, 0.2);
      background: #edf3ff;
      color: var(--muted);
      font-weight: 700;
      transition: all 0.18s ease;
    }

    .floor-toggle-btn.active {
      background: linear-gradient(135deg, #2663eb, #4f8cff);
      border-color: #2663eb;
      color: white;
      box-shadow: 0 10px 18px rgba(38, 99, 235, 0.2);
    }

    .building-floor-popup,
    .building-toggle-pill {
      position: absolute;
      z-index: 20;
      pointer-events: auto;
      display: flex;
      align-items: center;
      gap: 8px;
      background: rgba(255, 255, 255, 0.96);
      border: 1px solid rgba(62, 91, 152, 0.18);
      border-radius: 999px;
      padding: 6px 10px;
      box-shadow: 0 12px 24px rgba(17, 24, 39, 0.12);
      transform: translate(-50%, -110%);
      white-space: nowrap;
    }

    .building-floor-popup.hidden,
    .building-toggle-pill.hidden {
      display: none;
    }

    .building-name-label {
      font-size: 12px;
      font-weight: 700;
      color: #1f2937;
      letter-spacing: 0.02em;
    }

    .btn-group {
      display: inline-flex;
      gap: 4px;
      align-items: center;
    }

    .btn {
      appearance: none;
      border: 1px solid rgba(38, 99, 235, 0.28);
      background: #ffffff;
      color: #1f2937;
      border-radius: 999px;
      padding: 4px 8px;
      font-size: 11px;
      font-weight: 700;
      line-height: 1.2;
      cursor: pointer;
      transition: all 0.18s ease;
    }

    .btn:hover {
      border-color: rgba(38, 99, 235, 0.52);
    }

    .btn.active,
    .btn-primary {
      background: linear-gradient(135deg, #2663eb, #4f8cff);
      border-color: #2663eb;
      color: white;
      box-shadow: 0 8px 18px rgba(38, 99, 235, 0.18);
    }

    .btn-outline-primary {
      background: white;
      color: #1f2937;
    }

    .btn-xs {
      padding: 3px 7px;
      font-size: 10px;
    }

    /* Only the selected route endpoints pulse; pass-through nodes remain neutral. */
    .node.selected-room .node-body {
      stroke-width: 4;
      animation: roomPulse 1.1s ease-in-out infinite;
    }

    .node.selected-room.route-start .node-body {
      stroke: #0057ff;
      --pulse-color: 0, 87, 255;
    }

    .node.selected-room.route-end .node-body {
      stroke: #ff1717;
      --pulse-color: 255, 23, 23;
    }

    .node.selected-room.route-alt .node-body {
      stroke: #8d99ae;
      --pulse-color: 141, 153, 174;
    }

    @keyframes roomPulse {
      0%,
      100% {
        stroke-width: 3;
        filter: drop-shadow(0 0 4px rgba(var(--pulse-color, 255, 0, 85), 0.55));
      }

      50% {
        stroke-width: 7;
        filter: drop-shadow(0 0 14px rgba(var(--pulse-color, 255, 0, 85), 1));
      }
    }

    @media (prefers-reduced-motion: reduce) {
      .node.selected-room .node-body {
        animation: none;
        filter: drop-shadow(0 0 8px rgba(var(--pulse-color, 255, 0, 85), 0.9));
      }
    }

    /* Floor toggle: floors the active route passes through */
    .floor-toggle-btn.has-route {
      position: relative;
    }

    .floor-toggle-btn.has-route::after {
      content: '';
      position: absolute;
      top: 5px;
      right: 6px;
      width: 8px;
      height: 8px;
      border-radius: 50%;
      background: var(--floor-dot, #16a34a);
      box-shadow: 0 0 0 2px #fff;
    }

    .floor-toggle-btn[data-floor="1"] { --floor-dot: #16a34a; }
    .floor-toggle-btn[data-floor="2"] { --floor-dot: #dc2626; }
    .floor-toggle-btn[data-floor="3"] { --floor-dot: #2563eb; }

    .floor-line-legend {
      display: grid;
      gap: 4px;
      margin-top: 10px;
      font-size: 12px;
      color: var(--muted);
    }

    .floor-line-item {
      display: flex;
      align-items: center;
      gap: 8px;
    }

    .floor-line-swatch {
      width: 28px;
      height: 4px;
      flex: 0 0 28px;
      border-radius: 999px;
    }

    .floor-line-ground { background: #16a34a; }
    .floor-line-second { background: #dc2626; }
    .floor-line-third { background: #2563eb; }
    .floor-line-alternative { height: 0; border-top: 4px dashed #8d99ae; }
  </style>
</head>

<body>
  <div class="app">
    <aside class="sidebar">
      <div class="panel">
        <h3>Route</h3>
        <div class="field">
          <label for="startSelect">From</label>
          <select id="startSelect"></select>
        </div>
        <div class="field">
          <label for="endSelect">To</label>
          <select id="endSelect"></select>
        </div>
        <div class="stack">
          <button class="primary" type="button" id="findPathBtn">Find Path</button>
          <button type="button" id="resetBtn">Reset</button>
        </div>
        <div id="status" class="status">Ready</div>
      </div>

      <div class="panel">
        <h3>Map data</h3>
        <div class="field">
          <label for="sourceFileSelect">Source file</label>
          <select id="sourceFileSelect">
            <?php foreach ($availableLayouts as $layoutFile): ?>
              <option value="<?php echo htmlspecialchars($layoutFile, ENT_QUOTES, 'UTF-8'); ?>"
                <?php echo ($layoutFile === $selectedLayoutFile) ? 'selected' : ''; ?>>
                <?php echo htmlspecialchars($layoutFile, ENT_QUOTES, 'UTF-8'); ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="stack">
          <button type="button" id="loadSourceBtn" class="primary">Load selected file</button>
          <button type="button" id="chooseLocalFileBtn">Choose local JSON file</button>
          <input id="localSourceFileInput" type="file" accept=".json,application/json" hidden />
        </div>
        <div class="field">
          <label>Nodes</label>
          <input type="text" value="<?php echo count($nodeList); ?>" readonly />
        </div>
        <div class="field">
          <label>Buildings</label>
          <input type="text" value="<?php echo count($buildingList); ?>" readonly />
        </div>
        <div class="field">
          <label>Lines</label>
          <input type="text" value="<?php echo count($lineList); ?>" readonly />
        </div>
      </div>
    </aside>

    <main class="workspace">
      <div class="canvas-wrap">
        <div id="buildingFloorPopup" class="building-floor-popup hidden" aria-live="polite"></div>
        <svg id="mapCanvas" preserveAspectRatio="xMidYMid meet">
          <g id="buildingLayer"></g>
          <g id="edgeLayer"></g>
          <g id="stairLayer"></g>
          <g id="fillerLayer"></g>
          <g id="routeLayer"></g>
          <g id="roomLayer"></g>
        </svg>
      </div>
    </main>
  </div>

  <script>
    function normalizeBuildingList(nodeMap, buildingList) {
      const buildingMap = new Map();
      (Array.isArray(buildingList) ? buildingList : []).forEach((building) => {
        if (!building || !building.id) return;
        buildingMap.set(String(building.id), { ...building });
      });

      const inferred = [];
      const seen = new Set();
      Object.values(nodeMap || {}).forEach((node) => {
        if (!node || !node.buildingId) return;
        const buildingId = String(node.buildingId);
        if (seen.has(buildingId)) return;
        seen.add(buildingId);

        const existing = buildingMap.get(buildingId) || {};
        inferred.push({
          ...existing,
          id: buildingId,
          name: existing.name || node.buildingName || buildingId,
          color: existing.color || node.color || '#4f9cff',
          x: Number(existing.x ?? node.x ?? 120) || 120,
          y: Number(existing.y ?? node.y ?? 120) || 120,
          width: Number(existing.width ?? 220) || 220,
          height: Number(existing.height ?? 180) || 180,
          floors: Number(existing.floors ?? Math.max(1, Number(node.floor) || 1)) || 1,
          nodeId: existing.nodeId || `${buildingId}-node`
        });
      });

      return [...inferred, ...([...buildingMap.values()].filter((building) => !seen.has(String(building.id))))];
    }

    let LAYOUT = <?php echo json_encode($layout, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES); ?>;
    let nodes = LAYOUT.nodes || {};
    let edges = LAYOUT.edges || [];
    let buildings = normalizeBuildingList(nodes, LAYOUT.buildings || []);
    let lines = LAYOUT.lines || [];
    const DEFAULT_CANVAS_SIZE = { width: 1920, height: 1080 };
    const IMPORTED_LAYOUT_STORAGE_KEY = 'render-map-imported-layout';
    const canvasSize = LAYOUT.canvasSize && Number(LAYOUT.canvasSize.width) && Number(LAYOUT.canvasSize.height)
      ? { width: Number(LAYOUT.canvasSize.width), height: Number(LAYOUT.canvasSize.height) }
      : DEFAULT_CANVAS_SIZE;

    const svg = document.getElementById('mapCanvas');
    const effectiveCanvasSize = { width: 1920, height: 1080 };
    svg.setAttribute('viewBox', `0 0 ${effectiveCanvasSize.width} ${effectiveCanvasSize.height}`);
    svg.setAttribute('width', '100%');
    svg.setAttribute('height', '100%');
    svg.style.width = '100%';
    svg.style.height = '100%';
    svg.style.maxWidth = '100%';
    svg.style.maxHeight = '100%';

    try {
      const storedImportedLayout = sessionStorage.getItem(IMPORTED_LAYOUT_STORAGE_KEY);
      if (storedImportedLayout) {
        const parsed = JSON.parse(storedImportedLayout);
        if (parsed && parsed.nodes) {
          LAYOUT = parsed;
          nodes = LAYOUT.nodes || {};
          edges = LAYOUT.edges || [];
          buildings = normalizeBuildingList(nodes, LAYOUT.buildings || []);
          lines = LAYOUT.lines || [];
        }
      }
    } catch (error) {
      console.warn('Could not restore saved imported layout:', error);
    }

    const edgeLayer = document.getElementById('edgeLayer');
    const stairLayer = document.getElementById('stairLayer');
    const fillerLayer = document.getElementById('fillerLayer');
    const routeLayer = document.getElementById('routeLayer');
    const roomLayer = document.getElementById('roomLayer');
    const buildingLayer = document.getElementById('buildingLayer');
    const nodeLayer = roomLayer;
    const startSelect = document.getElementById('startSelect');
    const endSelect = document.getElementById('endSelect');
    const sourceFileSelect = document.getElementById('sourceFileSelect');
    const loadSourceBtn = document.getElementById('loadSourceBtn');
    const chooseLocalFileBtn = document.getElementById('chooseLocalFileBtn');
    const localSourceFileInput = document.getElementById('localSourceFileInput');
    const statusEl = document.getElementById('status');

    const state = {
      route: [],
      primaryRoute: [],
      alternativeRoute: [],
      primaryStartId: null,
      primaryEndId: null,
      altEndId: null,
      activeFloor: 1,
      selectedNodeId: null,
      selectedBuildingId: null,
      buildingFloorState: {},
      zoom: 1
    };

    // Same palette as the campus map legend: ground green, 2F red, 3F blue.
    const FLOOR_COLORS = { 1: '#16a34a', 2: '#dc2626', 3: '#2563eb' };

    function getFloorColor(floor) {
      return FLOOR_COLORS[Number(floor) || 1] || FLOOR_COLORS[1];
    }

    // Floors the current primary/alternative route touches (e.g. {1, 2}).
    function getRouteFloors() {
      const floors = new Set();
      [...(state.primaryRoute || []), ...(state.alternativeRoute || [])].forEach((id) => {
        const node = getNodeData(id);
        if (node && node.type !== 'junction') floors.add(getNodeFloor(node));
      });
      // Junctions still carry a floor, so include them too for multi-floor paths.
      [...(state.primaryRoute || []), ...(state.alternativeRoute || [])].forEach((id) => {
        const node = getNodeData(id);
        if (node) floors.add(getNodeFloor(node));
      });
      return floors;
    }

    function getNodeFloor(node) {
      if (!node) return state.activeFloor;
      return Number(node.floor ?? node.level ?? 1) || 1;
    }

    function getBuildingFloorKey(buildingIdOrName) {
      if (!buildingIdOrName) return null;
      const value = String(buildingIdOrName).trim();
      if (!value) return null;
      const building = getBuildingById(value) || (buildings || []).find((item) => String(item && item.name || '') === value);
      return building && building.name ? String(building.name) : value;
    }

    // Nodes saved without a building (e.g. stairs/fillers added on an upper floor)
    // are matched to the smallest building whose rectangle contains them.
    function inferBuildingFromPosition(node) {
      if (!node || !Array.isArray(buildings)) return null;
      let best = null;
      let bestArea = Infinity;
      buildings.forEach((building) => {
        if (!building || !building.name) return;
        const bx = Number(building.x) || 0;
        const by = Number(building.y) || 0;
        const bw = Number(building.width) || 0;
        const bh = Number(building.height) || 0;
        if (node.x >= bx && node.x <= bx + bw && node.y >= by && node.y <= by + bh && bw * bh < bestArea) {
          best = building;
          bestArea = bw * bh;
        }
      });
      return best;
    }

    function getNodeBuildingId(node) {
      if (!node) return null;
      if (node.buildingId) return node.buildingId;
      const inferred = inferBuildingFromPosition(node);
      return inferred ? inferred.id : null;
    }

    function getNodeDisplayFloor(node) {
      if (!node) return 1;
      const inferred = (node.buildingName || node.buildingId) ? null : inferBuildingFromPosition(node);
      const buildingKey = node.buildingName
        || (node.buildingId ? getBuildingFloorKey(node.buildingId) : null)
        || (inferred ? String(inferred.name) : null);
      // Not in any building: always shown (never stuck on the wrong floor).
      if (!buildingKey) return getNodeFloor(node);
      if (Number.isFinite(Number(state.buildingFloorState[buildingKey])) && Number(state.buildingFloorState[buildingKey]) >= 1) {
        return Number(state.buildingFloorState[buildingKey]);
      }
      return 1;
    }

    // Buildings that currently have a floor-toggle popup (start + destination, or the selection).
    function getPopupBuildingIds() {
      const ids = [];
      const add = (id) => { if (id && !ids.includes(id)) ids.push(id); };
      const startId = state.primaryStartId || (startSelect && startSelect.value) || null;
      const endId = state.primaryEndId || (endSelect && endSelect.value) || null;
      add(getNodeBuildingId(startId ? getNodeData(startId) : null));
      add(getNodeBuildingId(endId ? getNodeData(endId) : null));
      const { buildingId } = resolveSelectedBuilding();
      add(buildingId);
      return ids
        .filter((id) => getBuildingFloorCount(id) > 1 && getBuildingHighestFloor(id) > 1)
        .slice(0, 2);
    }

    function getFocusedBuildingFloors() {
      const focus = new Map();
      let anyUpper = false;
      getPopupBuildingIds().forEach((id) => {
        const key = getBuildingFloorKey(id);
        const floor = Number(state.buildingFloorState[key] || 1);
        focus.set(key, floor);
        if (floor > 1) anyUpper = true;
      });
      return anyUpper ? focus : null;
    }

    function isDimmedByFocusedBuilding(node) {
      const focus = getFocusedBuildingFloors();
      if (!focus || !node) return false;
      const nodeBuilding = node.buildingName || (node.buildingId ? getBuildingFloorKey(node.buildingId) : null) || null;
      if (!nodeBuilding) return false;
      if (focus.has(nodeBuilding)) {
        return getNodeFloor(node) !== focus.get(nodeBuilding);
      }
      return true;
    }

    function nodeMatchesVisibleFloor(node) {
      if (!node) return false;
      return getNodeFloor(node) === getNodeDisplayFloor(node);
    }

    function updateFloorToggleButtons() {
      document.querySelectorAll('.floor-toggle-btn').forEach((button) => {
        const isActive = Number(button.dataset.floor) === state.activeFloor;
        button.classList.toggle('active', isActive);
      });

      const routeFloors = getRouteFloors();
      document.querySelectorAll('.floor-toggle-btn').forEach((button) => {
        button.classList.toggle('has-route', routeFloors.has(Number(button.dataset.floor)));
      });

      const floorStatusEl = document.getElementById('floorStatus');
      if (floorStatusEl) {
        const sorted = [...routeFloors].sort((a, b) => a - b);
        floorStatusEl.textContent = sorted.length > 1
          ? `Showing floor ${state.activeFloor} - route uses floors ${sorted.join(', ')}`
          : `Showing floor ${state.activeFloor}`;
      }
    }

    function setActiveFloor(floor) {
      const nextFloor = Number(floor) || 1;
      state.activeFloor = nextFloor;
      (buildings || []).forEach((building) => {
        if (!building || !building.name) return;
        state.buildingFloorState[String(building.name)] = Math.min(nextFloor, getBuildingFloorCount(building.id) || 1);
      });
      updateFloorToggleButtons();
      render();
    }

    function toggleBuildingFloor(buildingId, floor) {
      if (!buildingId) return;
      const building = getBuildingById(buildingId);
      const buildingKey = building && building.name ? building.name : String(buildingId || '');
      const nextFloor = Number(floor) || 1;
      state.buildingFloorState[buildingKey] = nextFloor;
      state.selectedBuildingId = buildingId;
      state.activeFloor = nextFloor;
      render();
    }

    function persistImportedLayout(layoutData) {
      if (!layoutData || typeof layoutData !== 'object') return;
      try {
        sessionStorage.setItem(IMPORTED_LAYOUT_STORAGE_KEY, JSON.stringify(layoutData));
      } catch (error) {
        console.warn('Could not persist imported layout:', error);
      }
    }

    function clearImportedLayoutOverride() {
      try {
        sessionStorage.removeItem(IMPORTED_LAYOUT_STORAGE_KEY);
      } catch (error) {
        console.warn('Could not clear imported layout override:', error);
      }
    }

    function setStatus(message) {
      statusEl.textContent = message;
    }

    function getBuildingById(buildingId) {
      if (!buildingId) return null;
      const id = String(buildingId);
      const fromLayout = (LAYOUT.buildings || []).find((building) => String(building.id) === id);
      if (fromLayout) return fromLayout;
      return (buildings || []).find((building) => String(building.id) === id) || null;
    }

    function getBuildingFloorCount(buildingId) {
      const building = getBuildingById(buildingId);
      if (building && Number.isFinite(Number(building.floors)) && Number(building.floors) >= 1) {
        return Math.max(1, Math.floor(Number(building.floors)));
      }

      const relatedNodes = Object.values(nodes || {}).filter((node) => String(node && node.buildingId || '') === String(buildingId || ''));
      if (!relatedNodes.length) {
        return 1;
      }

      const inferredFloor = relatedNodes.reduce((maxFloor, node) => {
        const value = Number(node && (node.floor ?? node.level ?? 1));
        if (Number.isFinite(value) && value >= 1) {
          return Math.max(maxFloor, Math.floor(value));
        }
        return maxFloor;
      }, 1);

      return Math.max(1, inferredFloor);
    }

    function getBuildingVisibleFloor(buildingId) {
      if (!buildingId) return 1;
      const floorLimit = getBuildingFloorCount(buildingId);
      const buildingKey = getBuildingFloorKey(buildingId);
      const savedFloor = Number(state.buildingFloorState[buildingKey]);
      const fallback = Number.isFinite(savedFloor) && savedFloor >= 1 ? savedFloor : 1;
      return Math.min(Math.max(fallback, 1), floorLimit || 1);
    }

    function getBuildingHighestFloor(buildingId) {
      if (!buildingId) return 1;
      const relatedNodes = Object.values(nodes || {}).filter((node) => String(node && node.buildingId || '') === String(buildingId || ''));
      if (!relatedNodes.length) return Math.max(1, Number(getBuildingById(buildingId)?.floors) || 1);
      return relatedNodes.reduce((maxFloor, node) => {
        const value = Number(getNodeFloor(node)) || 1;
        return Math.max(maxFloor, value);
      }, 1);
    }

    function getNodeData(nodeId) {
      return nodes[nodeId] || null;
    }

    function resolveSelectedBuilding() {
      const selectedNode = state.selectedNodeId ? getNodeData(state.selectedNodeId) : null;
      const buildingId = selectedNode && selectedNode.buildingId
        ? selectedNode.buildingId
        : (state.selectedBuildingId || null);

      const building = buildingId ? getBuildingById(buildingId) : null;
      const highestFloor = buildingId ? getBuildingHighestFloor(buildingId) : 1;
      return { selectedNode, buildingId, building, highestFloor };
    }

    function showBuildingPopupForSelection() {
      const { selectedNode, buildingId, building, highestFloor } = resolveSelectedBuilding();
      if (!buildingId || !building) return;
      const hasRouteContext = !!(state.primaryRoute && state.primaryRoute.length) || (!!startSelect.value && !!endSelect.value);
      if (!hasRouteContext) return;

      const selectedFloor = selectedNode
        ? (Number(getNodeFloor(selectedNode)) || 1)
        : (Number(state.buildingFloorState[buildingId]) || Number(state.activeFloor) || 1);

      if (selectedFloor <= 1 && highestFloor <= 1) return;

      const floorCount = getBuildingFloorCount(buildingId);
      if (floorCount <= 1) return;

      const buildingKey = getBuildingFloorKey(buildingId);
      if (!Number.isFinite(Number(state.buildingFloorState[buildingKey])) || Number(state.buildingFloorState[buildingKey]) <= 0) {
        state.buildingFloorState[buildingKey] = selectedFloor > 1 ? selectedFloor : highestFloor;
      }
      state.selectedBuildingId = buildingId;
      renderBuildingFloorPopup();
    }

    function getFloorPopupElement(slot) {
      if (slot === 0) return document.getElementById('buildingFloorPopup');
      const id = `buildingFloorPopup${slot + 1}`;
      let el = document.getElementById(id);
      if (!el) {
        const first = document.getElementById('buildingFloorPopup');
        if (!first || !first.parentNode) return null;
        el = document.createElement('div');
        el.id = id;
        el.className = 'building-floor-popup hidden';
        el.setAttribute('aria-live', 'polite');
        first.parentNode.appendChild(el);
      }
      return el;
    }

    function clearFloorPopup(popup) {
      if (!popup) return;
      popup.classList.add('hidden');
      popup.innerHTML = '';
    }

    function renderBuildingFloorPopup() {
      const hasRouteContext = !!(state.primaryRoute && state.primaryRoute.length) || (!!startSelect.value && !!endSelect.value);
      const popupBuildingIds = hasRouteContext ? getPopupBuildingIds() : [];
      const placed = [];

      [0, 1].forEach((slot) => {
        const popup = getFloorPopupElement(slot);
        if (!popup) return;

        const buildingId = popupBuildingIds[slot];
        const building = buildingId ? getBuildingById(buildingId) : null;
        if (!building) {
          clearFloorPopup(popup);
          return;
        }

        const floorCount = getBuildingFloorCount(buildingId);
        const buildingKey = getBuildingFloorKey(buildingId);
        if (!Number.isFinite(Number(state.buildingFloorState[buildingKey]))) {
          state.buildingFloorState[buildingKey] = getBuildingHighestFloor(buildingId);
        }

        const currentFloor = getBuildingVisibleFloor(buildingId);
        const safeId = String(buildingId || '').replace(/'/g, "\\'");
        const buttons = Array.from({ length: floorCount }, (_, index) => index + 1)
          .map((floor) => {
            const isActive = currentFloor === floor;
            const className = ['btn', 'btn-xs', isActive ? 'btn-primary active' : 'btn-outline-primary'].join(' ');
            return `
            <button
              type="button"
              class="${className}"
              data-building-floor-value="${floor}"
              onclick="toggleBuildingFloor('${safeId}', ${floor})"
              style="pointer-events: auto;"
            >
              ${floor}F
            </button>
          `;
          })
          .join('');

        const targetX = Number(building.x) || 0;
        const targetY = Number(building.y) || 0;
        const targetWidth = Number(building.width) || 220;
        const targetHeight = Number(building.height) || 180;
        const popupWidth = 170;
        const popupHeight = 42;
        const centerX = targetX + (targetWidth / 2);
        let centerY = targetY + (targetHeight / 2) - 16;

        // If the two popups would land on top of each other, nudge the second one down.
        placed.forEach((other) => {
          if (Math.abs(other.x - centerX) < popupWidth && Math.abs(other.y - centerY) < popupHeight + 6) {
            centerY = other.y + popupHeight + 8;
          }
        });

        const safeX = Math.min(Math.max(centerX, popupWidth / 2 + 12), effectiveCanvasSize.width - popupWidth / 2 - 12);
        const safeY = Math.min(Math.max(centerY, popupHeight / 2 + 12), effectiveCanvasSize.height - popupHeight / 2 - 12);
        placed.push({ x: safeX, y: safeY });

        popup.className = 'building-toggle-pill position-absolute';
        popup.dataset.group = buildingId;
        popup.style.left = `${(safeX / effectiveCanvasSize.width) * 100}%`;
        popup.style.top = `${(safeY / effectiveCanvasSize.height) * 100}%`;
        popup.style.transform = 'translate(-50%, -50%)';
        popup.style.display = 'flex';
        popup.innerHTML = `
        <span class="building-name-label">${building.name || building.id}</span>
        <div class="btn-group btn-group-sm" role="group" aria-label="${building.name || building.id} floor selection">
          ${buttons}
        </div>
      `;
        popup.classList.remove('hidden');
      });
    }

    function parseGroupValue(rawGroup) {
      if (typeof rawGroup !== 'string') return null;
      const trimmed = rawGroup.trim();
      if (!trimmed) return null;

      const match = trimmed.match(/^(.+?)\s*\((.*)\)$/i);
      if (!match) return null;

      const groupName = String(match[1]).trim().toLowerCase().replace(/\s+/g, '_');
      const labelText = String(match[2]).trim();
      if (!groupName || !labelText) return null;

      const routeLabel = labelText
        .replace(/\s+/g, ' ')
        .split(/\s+/)
        .filter(Boolean)
        .map((word) => word.charAt(0).toUpperCase() + word.slice(1).toLowerCase())
        .join(' ');

      return { groupName, routeLabel };
    }

    function resolveTargetNodes(targetVal) {
      if (!targetVal) return [];
      if (NODE_GROUPS[targetVal]) return [...NODE_GROUPS[targetVal]];
      return [targetVal];
    }

    function buildNodeGroups() {
      const grouped = {};
      Object.values(nodes).forEach((node) => {
        if (!node || node.type === 'junction' || !!node.disableDestination) return;

        const parsedGroup = parseGroupValue(node.group);
        if (!parsedGroup) return;

        if (!grouped[parsedGroup.groupName]) grouped[parsedGroup.groupName] = [];
        grouped[parsedGroup.groupName].push(node.id);
      });
      return grouped;
    }

    const NODE_GROUPS = buildNodeGroups();

    function buildRouteOptions() {
      const startOptions = [];
      const endOptions = [];
      const seenStart = new Set();
      const seenEnd = new Set();
      const groupLabels = {};

      Object.values(nodes).forEach((node) => {
        if (!node || node.type === 'junction' || !!node.disableDestination) return;
        const parsedGroup = parseGroupValue(node.group);
        if (parsedGroup) {
          groupLabels[parsedGroup.groupName] = parsedGroup.routeLabel;
        }
      });

      Object.entries(groupLabels).forEach(([groupName, routeLabel]) => {
        endOptions.push({ label: routeLabel, value: groupName });
      });

      Object.values(nodes)
        .filter((node) => !!node && node.type !== 'junction' && node.label)
        .sort((a, b) => String(a.label).localeCompare(String(b.label)))
        .forEach((node) => {
          const key = node.id;
          const parsedGroup = parseGroupValue(node.group);
          const isDestinationDisabled = !!node.disableDestination;

          if (isDestinationDisabled) {
            return;
          }

          if (!seenStart.has(key)) {
            startOptions.push({ label: String(node.label).replace(/\s*\r?\n\s*/g, ' '), value: node.id });
            seenStart.add(key);
          }

          if (parsedGroup) {
            return;
          }

          if (!seenEnd.has(key)) {
            endOptions.push({ label: String(node.label).replace(/\s*\r?\n\s*/g, ' '), value: node.id });
            seenEnd.add(key);
          }
        });

      return { startOptions, endOptions };
    }

    function escapeHtml(value) {
      return String(value).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
    }

    function populateSelectors() {
      const { startOptions, endOptions } = buildRouteOptions();
      startSelect.innerHTML = '<option value="">Choose starting point...</option>' + startOptions
        .map((option) => `<option value="${escapeHtml(option.value)}">${escapeHtml(option.label)}</option>`)
        .join('');
      endSelect.innerHTML = '<option value="">Choose destination...</option>' + endOptions
        .map((option) => `<option value="${escapeHtml(option.value)}">${escapeHtml(option.label)}</option>`)
        .join('');
      focusNodeByHash();
    }

    function getMapNodeColor(node) {
      const validHex = (value) => typeof value === 'string' && /^#[0-9a-fA-F]{3,6}$/.test(value.trim());
      const fallbackColors = {
        room: '#7bb7ff',
        junction: '#ff0000',
        office: '#c7a1ff',
        facility: '#7ee7a8',
        faculty: '#ff9d7a',
        stair: '#8b7bff',
        other: '#b5c7d6'
      };

      if (validHex(node && node.color)) return node.color;
      const type = (node && node.type) || 'other';
      return fallbackColors[type] || '#b5c7d6';
    }

    function normalizeStairDesign(value) {
      const normalized = String(value || '').trim().toLowerCase();
      return normalized === 'vertical' ? 'vertical' : 'horizontal';
    }

    function ensureStairPattern(stairDesign) {
      const design = normalizeStairDesign(stairDesign);
      const patternId = `stairs-pattern-${design}`;
      let defs = svg.querySelector('defs');
      if (!defs) {
        defs = document.createElementNS('http://www.w3.org/2000/svg', 'defs');
        svg.insertBefore(defs, svg.firstChild);
      }

      if (defs.querySelector(`#${patternId}`)) return patternId;

      const pattern = document.createElementNS('http://www.w3.org/2000/svg', 'pattern');
      pattern.setAttribute('id', patternId);
      pattern.setAttribute('patternUnits', 'userSpaceOnUse');
      pattern.setAttribute('width', design === 'vertical' ? '12' : '24');
      pattern.setAttribute('height', design === 'vertical' ? '24' : '12');

      const base = document.createElementNS('http://www.w3.org/2000/svg', 'rect');
      base.setAttribute('width', design === 'vertical' ? '12' : '24');
      base.setAttribute('height', design === 'vertical' ? '24' : '12');
      base.setAttribute('fill', '#c89b69');

      const stripe = document.createElementNS('http://www.w3.org/2000/svg', 'line');
      if (design === 'vertical') {
        stripe.setAttribute('x1', '4');
        stripe.setAttribute('y1', '0');
        stripe.setAttribute('x2', '4');
        stripe.setAttribute('y2', '24');
      } else {
        stripe.setAttribute('x1', '0');
        stripe.setAttribute('y1', '4');
        stripe.setAttribute('x2', '24');
        stripe.setAttribute('y2', '4');
      }
      stripe.setAttribute('stroke', '#734b1d');
      stripe.setAttribute('stroke-width', '2');

      pattern.appendChild(base);
      pattern.appendChild(stripe);
      defs.appendChild(pattern);
      return patternId;
    }

    function renderBuildings() {
      buildingLayer.innerHTML = '';
      buildings.forEach((building) => {
        const x = Number(building.x) || 80;
        const y = Number(building.y) || 80;
        const width = Number(building.width) || 220;
        const height = Number(building.height) || 180;

        const rect = document.createElementNS('http://www.w3.org/2000/svg', 'rect');
        rect.setAttribute('x', x);
        rect.setAttribute('y', y);
        rect.setAttribute('width', width);
        rect.setAttribute('height', height);
        rect.setAttribute('rx', 0);
        rect.setAttribute('ry', 0);
        rect.setAttribute('fill', 'transparent');
        rect.setAttribute('stroke', 'transparent');
        rect.setAttribute('stroke-width', '0');
        rect.setAttribute('pointer-events', 'none');
        rect.style.cursor = 'default';
        rect.style.pointerEvents = 'none';
        buildingLayer.appendChild(rect);
      });
    }

    function renderEdges() {
      edgeLayer.innerHTML = '';
      edges.forEach(([fromId, toId]) => {
        const from = getNodeData(fromId);
        const to = getNodeData(toId);
        if (!from || !to) return;
        if (from.type === 'junction' || to.type === 'junction') return;
        if (!nodeMatchesVisibleFloor(from) || !nodeMatchesVisibleFloor(to)) return;

        const line = document.createElementNS('http://www.w3.org/2000/svg', 'line');
        line.setAttribute('x1', from.x);
        line.setAttribute('y1', from.y);
        line.setAttribute('x2', to.x);
        line.setAttribute('y2', to.y);
        line.setAttribute('class', 'edge');
        line.setAttribute('stroke', getFloorColor(getNodeFloor(from)));
        line.setAttribute('stroke-width', '3');
        const dimmed = isDimmedByFocusedBuilding(from) || isDimmedByFocusedBuilding(to);
        line.style.opacity = dimmed ? '0.22' : '1';
        edgeLayer.appendChild(line);
      });

      lines.forEach((lineData) => {
        if (!lineData || typeof lineData !== 'object') return;
        const line = document.createElementNS('http://www.w3.org/2000/svg', 'line');
        const x1 = Number(lineData.x1) || 0;
        const y1 = Number(lineData.y1) || 0;
        const x2 = Number(lineData.x2) || 0;
        const y2 = Number(lineData.y2) || 0;
        const width = Number(lineData.lineThickness ?? lineData.strokeWidth ?? lineData.width ?? lineData.thickness ?? 2) || 2;
        const rotation = Number(lineData.rotation) || 0;
        const centerX = (x1 + x2) / 2;
        const centerY = (y1 + y2) / 2;
        const dashArray = Array.isArray(lineData.dashArray) && lineData.dashArray.length ? lineData.dashArray : [8, 6];
        const lineCap = String(lineData.lineCap || 'round');

        line.setAttribute('x1', x1);
        line.setAttribute('y1', y1);
        line.setAttribute('x2', x2);
        line.setAttribute('y2', y2);
        line.setAttribute('transform', `rotate(${rotation} ${centerX} ${centerY})`);
        line.setAttribute('class', 'edge free-line');
        line.setAttribute('stroke', String(lineData.color || getFloorColor(state.activeFloor)));
        line.setAttribute('stroke-width', String(width));
        line.setAttribute('stroke-linecap', lineCap);
        line.setAttribute('stroke-dasharray', dashArray.join(' '));
        edgeLayer.appendChild(line);
      });
    }

    function getNodeDisplayLabel(node) {
      if (!node) return 'Unknown';
      if (node.type === 'stair') return '';
      const rawLabel = String(node.label == null ? '' : node.label).replace(/\r\n?/g, '\n');
      if (!rawLabel.trim()) return node.id;
      const normalized = rawLabel.trim().toLowerCase();
      if (normalized.includes('window') || normalized.includes('filler')) return '';
      return rawLabel;
    }

    function createMultilineSvgText(value, x, y, node) {
      const SVG_NS = 'http://www.w3.org/2000/svg';
      const svgText = document.createElementNS(SVG_NS, 'text');
      const lines = String(value || '').split(/\r?\n/);
      const fontSize = Math.min(48, Math.max(8, Number(node.fontSize) || 14));
      const fontWeight = String(Number(node.fontWeight) || 500);
      const fontFamily = String(node.fontFamily || 'Arial, sans-serif');
      const anchor = String(node.textAnchor || '').toLowerCase();
      const textAnchor = ['start', 'middle', 'end'].includes(anchor) ? anchor : 'middle';
      const style = String(node.fontStyle || '').toLowerCase();
      const fontStyle = ['normal', 'italic', 'oblique'].includes(style) ? style : 'normal';
      const letterSpacing = Number(node.letterSpacing) || 0;

      svgText.setAttribute('x', String(x));
      svgText.setAttribute('y', String(y));
      svgText.setAttribute('dominant-baseline', 'middle');
      svgText.setAttributeNS('http://www.w3.org/XML/1998/namespace', 'xml:space', 'preserve');
      svgText.style.whiteSpace = 'pre';
      svgText.style.fontSize = `${fontSize}px`;
      svgText.style.fontWeight = fontWeight;
      svgText.style.fontFamily = fontFamily;
      svgText.style.fontStyle = fontStyle;
      svgText.style.letterSpacing = `${letterSpacing}px`;
      svgText.style.textAnchor = textAnchor;
      svgText.style.fill = '#000000';

      if (lines.length <= 1) {
        svgText.textContent = lines[0] || '';
        return svgText;
      }

      const lineHeight = fontSize * 1.2;
      lines.forEach((line, index) => {
        const tspan = document.createElementNS(SVG_NS, 'tspan');
        tspan.setAttribute('x', String(x));
        tspan.setAttribute('dy', String(index === 0 ? -((lines.length - 1) * lineHeight) / 2 : lineHeight));
        tspan.textContent = line === '' ? '\u00A0' : line;
        svgText.appendChild(tspan);
      });
      return svgText;
    }

    function navigateToNextMap(node) {
      if (!node || node.type !== 'stair') return;
      const nextMap = String(node.nextMap || node.nextJsonMap || node.targetMap || '').trim();
      const nextNodeId = String(node.nextMapNodeId || node.nextNodeId || node.targetNodeId || '').trim();
      if (!nextMap) {
        setStatus('This stair has no next map configured');
        return;
      }

      const nextUrl = new URL(window.location.href);
      nextUrl.searchParams.set('file', nextMap);
      nextUrl.hash = nextNodeId ? `node=${encodeURIComponent(nextNodeId)}` : '';
      window.location.href = nextUrl.toString();
    }

    function focusNodeByHash() {
      const hashValue = (window.location.hash || '').replace(/^#/, '').trim();
      if (!hashValue) return;
      const match = hashValue.match(/^node=(.+)$/i) || hashValue.match(/^node:(.+)$/i);
      const targetId = match ? decodeURIComponent(match[1]) : decodeURIComponent(hashValue);
      if (!targetId || !nodes[targetId]) return;
      startSelect.value = targetId;
      endSelect.value = '';
      setStatus(`Focused ${nodes[targetId].label || targetId}`);
    }

    function handleRouteSelectionChange(selectedValue, isStart) {
      if (!selectedValue || !nodes[selectedValue]) {
        if (isStart) {
          state.selectedNodeId = null;
          state.selectedBuildingId = null;
        }
        renderBuildingFloorPopup();
        return;
      }

      const selectedNode = nodes[selectedValue];
      state.selectedNodeId = selectedNode.id;
      state.selectedBuildingId = selectedNode.buildingId || null;
      render();
    }

    function applyRouteHighlighting() {
      [stairLayer, roomLayer].forEach((layer) => {
        if (!layer) return;
        layer.querySelectorAll('.node').forEach((nodeEl) => {
          nodeEl.classList.remove('route-start', 'route-end', 'route-alt', 'selected-room');
        });
      });

      const markPulse = (nodeId, cls) => {
        if (!nodeId) return;
        const target = [stairLayer, roomLayer].map((layer) => layer && layer.querySelector(`[data-node-id="${nodeId}"]`)).find(Boolean);
        if (target) target.classList.add('selected-room', cls);
      };

      if (state.selectedNodeId) {
        const selectedTarget = [stairLayer, roomLayer].map((layer) => layer && layer.querySelector(`[data-node-id="${state.selectedNodeId}"]`)).find(Boolean);
        if (selectedTarget) {
          selectedTarget.classList.add('selected-room');
        }
      }

      markPulse(state.altEndId, 'route-alt');
      markPulse(state.primaryStartId, 'route-start');
      markPulse(state.primaryEndId, 'route-end');
    }

    function renderNodes() {
      if (stairLayer) stairLayer.innerHTML = '';
      if (fillerLayer) fillerLayer.innerHTML = '';
      if (roomLayer) roomLayer.innerHTML = '';
      const highlightedIds = new Set([
        state.primaryStartId,
        state.primaryEndId,
        state.altEndId,
        ...(state.primaryRoute || []),
        ...(state.alternativeRoute || [])
      ].filter(Boolean));

      const drawQueue = [];
      const fillerQueue = [];

      Object.values(nodes).forEach((node) => {
        if (node.type === 'junction') return;
        // Nodes follow the floor toggle, so a multi-floor route is shown one floor at a time.
        if (!nodeMatchesVisibleFloor(node)) return;

        const group = document.createElementNS('http://www.w3.org/2000/svg', 'g');
        group.setAttribute('class', 'node');
        group.dataset.nodeId = node.id;
        group.dataset.zIndex = Number.isFinite(Number(node.zIndex)) ? String(Number(node.zIndex)) : '0';
        group.style.cursor = 'pointer';

        const isStair = node.type === 'stair';
        const isFillerNode = String(node.label || '').toLowerCase().includes('filler');
        const shape = document.createElementNS('http://www.w3.org/2000/svg', 'rect');
        shape.setAttribute('class', 'node-body');
        shape.setAttribute('stroke-linejoin', 'miter');
        shape.setAttribute('stroke-linecap', 'square');

        const w = Number(node.width) || (isStair ? 72 : 52);
        const h = Number(node.height) || (isStair ? 52 : 44);
        shape.setAttribute('x', node.x - w / 2);
        shape.setAttribute('y', node.y - h / 2);
        shape.setAttribute('width', w);
        shape.setAttribute('height', h);
        shape.setAttribute('rx', 0);
        shape.setAttribute('ry', 0);

        if (isStair) {
          const patternId = ensureStairPattern(node.stairDesign);
          shape.setAttribute('fill', `url(#${patternId})`);
          shape.setAttribute('stroke', '#734b1d');
          shape.setAttribute('stroke-width', '2');
        } else {
          shape.setAttribute('fill', getMapNodeColor(node));
          shape.setAttribute('stroke', (node.borderEnabled && Number(node.borderWidth) > 0) ? '#1f2937' : 'rgba(20, 52, 94, 0.75)');
          shape.setAttribute('stroke-width', (node.borderEnabled && Number(node.borderWidth) > 0) ? String(Number(node.borderWidth) || 1) : '1.5');
        }

        const dimmed = isDimmedByFocusedBuilding(node);
        if (dimmed) {
          shape.style.opacity = '0.22';
        }

        const label = createMultilineSvgText(getNodeDisplayLabel(node), node.x, node.y, node);
        if (isStair) label.setAttribute('display', 'none');
        if (dimmed) label.style.opacity = '0.22';

        group.addEventListener('click', () => {
          state.selectedNodeId = node.id;
          state.selectedBuildingId = node.buildingId || null;
          if (node.type === 'stair' && (node.nextMap || node.nextJsonMap || node.targetMap || node.nextMapNodeId || node.nextNodeId || node.targetNodeId)) {
            navigateToNextMap(node);
            return;
          }
          setStatus(`${node.label || node.id} selected`);
          showBuildingPopupForSelection();
          render();
        });

        group.appendChild(shape);
        group.appendChild(label);

        const queueEntry = { group, area: w * h };
        if (isFillerNode) {
          fillerQueue.push(queueEntry);
        } else {
          drawQueue.push(queueEntry);
        }
      });

      // Filler blocks stay under the route overlay, but regular rooms stay above the route.
      const sortByDepth = (entries) => entries.sort((a, b) => {
        const aZ = Number.isFinite(Number(a.group.dataset.zIndex)) ? Number(a.group.dataset.zIndex) : 0;
        const bZ = Number.isFinite(Number(b.group.dataset.zIndex)) ? Number(b.group.dataset.zIndex) : 0;
        if (aZ !== bZ) return aZ - bZ;
        return b.area - a.area;
      });

      sortByDepth(fillerQueue).forEach(({ group }) => { if (fillerLayer) fillerLayer.appendChild(group); });
      sortByDepth(drawQueue).forEach(({ group }) => { if (roomLayer) roomLayer.appendChild(group); });

      applyRouteHighlighting();
    }

    function drawRoutePath(path, kind) {
      if (!Array.isArray(path) || path.length < 2) return;
      const segments = [];
      for (let i = 0; i < path.length - 1; i++) {
        const from = getNodeData(path[i]);
        const to = getNodeData(path[i + 1]);
        if (!from || !to) continue;

        // A segment shows when either end is on its building's visible floor, so the two
        // popups (start / destination building) can sit on different floors at once.
        const fromVisible = nodeMatchesVisibleFloor(from);
        const toVisible = nodeMatchesVisibleFloor(to);
        if (!fromVisible && !toVisible) continue;

        const visibleNode = fromVisible ? from : to;
        const color = kind === 'alt' ? '#8d99ae' : getFloorColor(getNodeFloor(visibleNode));
        const dimmed = isDimmedByFocusedBuilding(from) || isDimmedByFocusedBuilding(to);
        segments.push({
          from,
          to,
          color,
          dimmed,
          stairStep: from.type === 'stair' || to.type === 'stair' || getNodeFloor(from) !== getNodeFloor(to) || /stair/i.test(from.id || '') || /stair/i.test(to.id || '')
        });
      }

      const totalLength = segments.reduce((sum, segment) => sum + Math.hypot(segment.to.x - segment.from.x, segment.to.y - segment.from.y), 0);
      const routeDuration = Math.min(2200, Math.max(800, totalLength * 0.65));
      let traversedLength = 0;

      segments.forEach((segment) => {
        const line = document.createElementNS('http://www.w3.org/2000/svg', 'line');
        line.setAttribute('x1', segment.from.x);
        line.setAttribute('y1', segment.from.y);
        line.setAttribute('x2', segment.to.x);
        line.setAttribute('y2', segment.to.y);
        line.setAttribute('class', kind === 'alt' ? 'edge route alt-active' : `edge route active${segment.stairStep ? ' route-stair' : ' route-reveal'}`);
        line.style.stroke = segment.color;
        line.setAttribute('stroke-width', kind === 'alt' ? '5' : '8');
        line.style.opacity = segment.dimmed ? '0.18' : '1';

        const segmentLength = Math.max(1, Math.hypot(segment.to.x - segment.from.x, segment.to.y - segment.from.y));
        if (kind !== 'alt' && totalLength > 0) {
          line.style.strokeLinecap = 'round';
          line.style.strokeLinejoin = 'round';
          line.style.setProperty('--route-length', `${Math.max(24, Math.ceil(segmentLength))}`);
          const segmentDuration = Math.max(1500, Math.min(3200, 1800 + (segmentLength / totalLength) * 1400));
          const segmentDelay = Math.min(1800, routeDuration * traversedLength / totalLength);
          line.style.setProperty('--route-duration', `${segmentDuration}ms`);
          line.style.setProperty('--route-delay', `${segmentDelay}ms`);
          line.style.animation = `${segment.stairStep ? 'routeRevealStair' : 'routeReveal'} ${segmentDuration}ms cubic-bezier(0.17, 0.84, 0.44, 1) ${segmentDelay}ms both`;
          traversedLength += segmentLength;
        }
        routeLayer.appendChild(line);
      });
    }

    function renderRoute() {
      routeLayer.innerHTML = '';
      drawRoutePath(state.alternativeRoute, 'alt');
      drawRoutePath(state.primaryRoute, 'primary');
    }

    const METERS_PER_PIXEL_X = 0.1;
    const METERS_PER_PIXEL_Y = 0.08182;

    function getNodeDistanceMeters(aId, bId) {
      const from = nodes[aId];
      const to = nodes[bId];
      if (!from || !to) return Number.POSITIVE_INFINITY;
      const dxPixels = Number(from.x) - Number(to.x);
      const dyPixels = Number(from.y) - Number(to.y);
      return Math.hypot(dxPixels * METERS_PER_PIXEL_X, dyPixels * METERS_PER_PIXEL_Y);
    }

    function canRouteBetweenNodes(aId, bId) {
      const firstNode = nodes[aId];
      const secondNode = nodes[bId];
      if (!firstNode || !secondNode || aId === bId) return false;

      if (firstNode.type === 'stair' && secondNode.type === 'stair') {
        return Math.abs(Number(firstNode.floor || 1) - Number(secondNode.floor || 1)) === 1;
      }

      if ((firstNode.type === 'stair' && secondNode.type === 'junction') ||
          (firstNode.type === 'junction' && secondNode.type === 'stair')) {
        return Math.abs(Number(firstNode.floor || 1) - Number(secondNode.floor || 1)) <= 1;
      }

      if (firstNode.type === 'junction' && secondNode.type === 'junction') {
        return Math.abs(Number(firstNode.floor || 1) - Number(secondNode.floor || 1)) <= 1;
      }

      if (firstNode.type === 'stair' || secondNode.type === 'stair') {
        return Math.abs(Number(firstNode.floor || 1) - Number(secondNode.floor || 1)) === 0;
      }

      return Number(firstNode.floor || 1) === Number(secondNode.floor || 1);
    }

    function buildAdjacency() {
      const adj = {};
      edges.forEach(([a, b]) => {
        if (!a || !b || a === b) return;
        if (!canRouteBetweenNodes(a, b)) return;
        if (!adj[a]) adj[a] = [];
        if (!adj[b]) adj[b] = [];
        const weight = getNodeDistanceMeters(a, b);
        if (!Number.isFinite(weight)) return;
        adj[a].push({ id: b, weight });
        adj[b].push({ id: a, weight });
      });
      return adj;
    }

    function findShortestPath(startId, endId) {
      if (!startId || !endId || startId === endId) {
        return { path: [startId].filter(Boolean), distance: 0 };
      }
      if (!nodes[startId] || !nodes[endId]) {
        return { path: [], distance: Number.POSITIVE_INFINITY };
      }

      const adjacency = buildAdjacency();
      if (!adjacency[startId] || !adjacency[endId]) {
        return { path: [], distance: Number.POSITIVE_INFINITY };
      }

      const distances = {};
      const previous = {};
      const visited = new Set();

      Object.keys(nodes).forEach((nodeId) => {
        distances[nodeId] = Number.POSITIVE_INFINITY;
        previous[nodeId] = null;
      });

      distances[startId] = 0;

      while (true) {
        let current = null;
        let bestDistance = Number.POSITIVE_INFINITY;
        Object.keys(nodes).forEach((nodeId) => {
          if (visited.has(nodeId)) return;
          if (distances[nodeId] < bestDistance) {
            current = nodeId;
            bestDistance = distances[nodeId];
          }
        });

        if (current === null || bestDistance === Number.POSITIVE_INFINITY) break;
        if (current === endId) break;

        visited.add(current);

        for (const neighbor of adjacency[current] || []) {
          const altDistance = bestDistance + neighbor.weight;
          if (altDistance < distances[neighbor.id]) {
            distances[neighbor.id] = altDistance;
            previous[neighbor.id] = current;
          }
        }
      }

      if (!Number.isFinite(distances[endId])) {
        return { path: [], distance: Number.POSITIVE_INFINITY };
      }

      const path = [];
      let cursor = endId;
      while (cursor) {
        path.unshift(cursor);
        cursor = previous[cursor];
      }

      return { path, distance: distances[endId] };
    }

    function computeGroupRoute(startVal, endVal) {
      const startNodes = resolveTargetNodes(startVal);
      const groupDestinationNodes = resolveTargetNodes(endVal);
      const isGroupDestination = !!(NODE_GROUPS[endVal] && NODE_GROUPS[endVal].length > 0);
      const primaryDestinationNodes = isGroupDestination ? [NODE_GROUPS[endVal][0]] : groupDestinationNodes;

      let bestMatch = null;
      for (const startNode of startNodes) {
        for (const destinationNode of primaryDestinationNodes) {
          if (!destinationNode || startNode === destinationNode) continue;
          const result = findShortestPath(startNode, destinationNode);
          if (!result || !result.path || !result.path.length || !Number.isFinite(result.distance)) continue;
          if (!bestMatch || result.distance < bestMatch.distance) {
            bestMatch = { distance: result.distance, path: result.path, startNode, endNode: destinationNode };
          }
        }
      }

      if (!bestMatch) return null;

      let altMatch = null;
      if (isGroupDestination && NODE_GROUPS[endVal].length > 1) {
        let closestGroupNode = null;
        let closestGroupDistance = Number.POSITIVE_INFINITY;
        for (const groupNode of NODE_GROUPS[endVal]) {
          for (const startNode of startNodes) {
            if (startNode === groupNode) {
              closestGroupDistance = 0;
              closestGroupNode = groupNode;
              break;
            }
            const route = findShortestPath(startNode, groupNode);
            if (!route || !route.path || !route.path.length || !Number.isFinite(route.distance)) continue;
            if (route.distance < closestGroupDistance) {
              closestGroupDistance = route.distance;
              closestGroupNode = groupNode;
            }
          }
        }

        if (bestMatch.endNode !== closestGroupNode) {
          const altCandidates = NODE_GROUPS[endVal].filter((nodeId) => nodeId !== bestMatch.endNode);
          for (const startNode of startNodes) {
            for (const altNode of altCandidates) {
              if (!altNode || startNode === altNode) continue;
              const result = findShortestPath(startNode, altNode);
              if (!result || !result.path || !result.path.length || !Number.isFinite(result.distance)) continue;
              if (!altMatch || result.distance < altMatch.distance) {
                altMatch = { distance: result.distance, path: result.path, startNode, endNode: altNode };
              }
            }
          }
        }
      }

      return {
        path: bestMatch.path,
        distance: bestMatch.distance,
        startNode: bestMatch.startNode,
        endNode: bestMatch.endNode,
        altPath: altMatch ? altMatch.path : []
      };
    }

    function doRoute() {
      const startVal = startSelect.value;
      const endVal = endSelect.value;

      if (!startVal || !endVal) {
        state.primaryRoute = [];
        state.alternativeRoute = [];
        state.primaryStartId = null;
        state.primaryEndId = null;
        state.altEndId = null;
        renderRoute();
        applyRouteHighlighting();
        setStatus('Select both a start and destination');
        return;
      }

      const routeMatch = computeGroupRoute(startVal, endVal);
      if (!routeMatch || !routeMatch.path.length) {
        state.primaryRoute = [];
        state.alternativeRoute = [];
        state.primaryStartId = null;
        state.primaryEndId = null;
        state.altEndId = null;
        renderRoute();
        applyRouteHighlighting();
        setStatus('No valid route found');
        return;
      }

      state.primaryRoute = routeMatch.path;
      state.alternativeRoute = routeMatch.altPath || [];
      state.primaryStartId = routeMatch.startNode;
      state.primaryEndId = routeMatch.endNode;
      state.altEndId = routeMatch.altPath && routeMatch.altPath.length ? routeMatch.altPath[routeMatch.altPath.length - 1] : null;

      setStatus(`Route found: ${routeMatch.path.length} nodes (${routeMatch.distance.toFixed(1)} m)`);

      // Seed a floor toggle for the start AND the destination building, each on the
      // floor of its own endpoint, so both popups work independently at the same time.
      [routeMatch.startNode, routeMatch.endNode].forEach((nodeId) => {
        const endpoint = getNodeData(nodeId);
        const endpointBuildingId = getNodeBuildingId(endpoint);
        if (!endpointBuildingId) return;
        state.buildingFloorState[getBuildingFloorKey(endpointBuildingId)] = Number(getNodeFloor(endpoint)) || 1;
      });

      const routeBuildingNode = [routeMatch.startNode, routeMatch.endNode]
        .map((nodeId) => getNodeData(nodeId))
        .find((node) => !!node && !!getNodeBuildingId(node) && Number(getNodeFloor(node)) > 1) || null;
      if (routeBuildingNode) {
        state.selectedNodeId = routeBuildingNode.id;
        state.selectedBuildingId = getNodeBuildingId(routeBuildingNode);
      }

      // Jump to the floor the route starts on; the floor buttons then show
      // which other floors the route passes through so the user can step through them.
      const startNode = getNodeData(routeMatch.startNode);
      if (startNode) state.activeFloor = getNodeFloor(startNode);
      render();
    }

    function applyLayoutData(layoutData) {
      if (!layoutData || typeof layoutData !== 'object') {
        setStatus('Invalid layout file');
        return;
      }

      LAYOUT = layoutData;
      nodes = LAYOUT.nodes || {};
      edges = LAYOUT.edges || [];
      buildings = normalizeBuildingList(nodes, LAYOUT.buildings || []);
      persistImportedLayout(layoutData);

      populateSelectors();
      render();
      setStatus('Layout loaded');
    }

    function render() {
      renderBuildings();
      renderEdges();
      renderRoute();
      renderNodes();
      updateFloorToggleButtons();
      renderBuildingFloorPopup();

      const orderedLayers = [buildingLayer, edgeLayer, stairLayer, fillerLayer, routeLayer, roomLayer].filter(Boolean);
      orderedLayers.forEach((layer) => {
        if (svg.contains(layer)) {
          svg.appendChild(layer);
        } else {
          svg.appendChild(layer);
        }
      });
    }

    document.querySelectorAll('.floor-toggle-btn').forEach((button) => {
      button.addEventListener('click', () => setActiveFloor(Number(button.dataset.floor) || 1));
    });

    startSelect.addEventListener('change', (event) => {
      handleRouteSelectionChange(event.target.value, true);
    });

    endSelect.addEventListener('change', (event) => {
      handleRouteSelectionChange(event.target.value, false);
    });

    chooseLocalFileBtn.addEventListener('click', () => localSourceFileInput.click());
    localSourceFileInput.addEventListener('change', async (event) => {
      const file = event.target.files && event.target.files[0];
      if (!file) return;

      try {
        const text = await file.text();
        const parsed = JSON.parse(text);
        applyLayoutData(parsed);
      } catch (error) {
        console.error('Could not read selected map file:', error);
        setStatus('Could not read the selected JSON file');
      }
    });

    document.getElementById('findPathBtn').addEventListener('click', doRoute);
    document.getElementById('resetBtn').addEventListener('click', () => {
      startSelect.value = '';
      endSelect.value = '';
      state.primaryRoute = [];
      state.alternativeRoute = [];
      state.primaryStartId = null;
      state.primaryEndId = null;
      state.altEndId = null;
      state.selectedNodeId = null;
      state.selectedBuildingId = null;
      renderRoute();
      applyRouteHighlighting();
      updateFloorToggleButtons();
      renderBuildingFloorPopup();
      setStatus('Ready');
    });

    loadSourceBtn.addEventListener('click', () => {
      clearImportedLayoutOverride();
      const selectedFile = sourceFileSelect && sourceFileSelect.value ? sourceFileSelect.value : 'map-layout.json';
      window.location.href = '?file=' + encodeURIComponent(selectedFile);
    });

    populateSelectors();
    render();
  </script>
</body>

</html>