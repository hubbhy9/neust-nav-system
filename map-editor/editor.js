const svg = document.getElementById('mapCanvas');
const edgeLayer = document.getElementById('edgeLayer');
const nodeLayer = document.getElementById('nodeLayer');
const outputBox = document.getElementById('outputBox');
const jsonFileInput = document.createElement('input');
jsonFileInput.type = 'file';
jsonFileInput.accept = 'application/json,.json';
jsonFileInput.hidden = true;
document.body.appendChild(jsonFileInput);
const toggleSidebarBtn = document.getElementById('toggleSidebarBtn');
const toggleTopNavBtn = document.getElementById('toggleTopNavBtn');
const sidebarRevealBtn = document.getElementById('sidebarRevealBtn');
const topRevealBtn = document.getElementById('topRevealBtn');
const appShell = document.querySelector('.app-shell');
const labelInput = document.getElementById('labelInput');
const labelSizeInput = document.getElementById('labelSizeInput');
const floorInput = document.getElementById('floorInput');
const groupInput = document.getElementById('groupInput');
const typeInput = document.getElementById('typeInput');
const nodeWidthInput = document.getElementById('nodeWidthInput');
const nodeHeightInput = document.getElementById('nodeHeightInput');
const buildingWidthInput = document.getElementById('buildingWidthInput');
const buildingHeightInput = document.getElementById('buildingHeightInput');
const junctionCoordinateGroup = document.getElementById('junctionCoordinateGroup');
const junctionXInput = document.getElementById('junctionXInput');
const junctionYInput = document.getElementById('junctionYInput');
const stairDesignInput = document.getElementById('stairDesignInput');
const lineThicknessInput = document.getElementById('lineThicknessInput');
const selectionLabel = document.getElementById('selectionLabel');
const zoomLabel = document.getElementById('zoomLabel');
const buildingNameInput = document.getElementById('buildingNameInput');
const buildingSelect = document.getElementById('buildingSelect');
const nodeBuildingSelect = document.getElementById('nodeBuildingSelect');
const buildingList = document.getElementById('buildingList');
const buildingsPanel = document.getElementById('buildingsPanel');
const buildingsCollapseBtn = document.getElementById('buildingsCollapseBtn');
const gridSnapToggle = document.getElementById('gridSnapToggle');
const toggleJunctionsBtn = document.getElementById('toggleJunctionsBtn');
const typeColorInput = document.getElementById('typeColorInput');
const fontColorInput = document.getElementById('fontColorInput');
const tagsInput = document.getElementById('tagsInput');
const buildingBorderToggle = document.getElementById('buildingBorderToggle');
const multiJunctionToggle = document.getElementById('multiJunctionToggle');
const disableDestinationToggle = document.getElementById('disableDestinationToggle');
const hideDisplayToggle = document.getElementById('hideDisplayToggle');
const floorConnectorToggle = document.getElementById('floorConnectorToggle');
const buildingBorderWidthInput = document.getElementById('buildingBorderWidthInput');
const nextMapInput = document.getElementById('nextMapInput');
const nextMapNodeInput = document.getElementById('nextMapNodeInput');
const globalFloorSelect = document.getElementById('globalFloorSelect');
const buildingFloorCountInput = document.getElementById('buildingFloorCountInput');
const mapWidthInput = document.getElementById('mapWidthInput');
const mapHeightInput = document.getElementById('mapHeightInput');

const STORAGE_KEY = 'campus-map-editor-layout';
const DEFAULT_CANVAS_SIZE = { width: 1920, height: 1080 };
let MAX_CANVAS = { ...DEFAULT_CANVAS_SIZE };
const GRID_SIZE = 25;
const LINE_SNAP_DISTANCE = 18;

const MAX_HISTORY = 20;

const INITIAL_TYPE_COLORS = {
  room: '#7bb7ff',
  junction: '#ff0000',
  office: '#c7a1ff',
  facility: '#7ee7a8',
  faculty: '#ff9d7a',
  stair: '#8b7bff',
  other: '#b5c7d6'
};

const state = {
  mode: 'select',
  selectedNodeId: null,
  selectedLineId: null,
  pendingConnectId: null,
  pendingLinePoint: null,
  pendingLineEnd: null,
  selectedBuildingId: null,
  lastSelectedBuildingId: null,
  selectedIds: [],
  selectionBox: null,
  zoom: 1,
  panX: 0,
  panY: 0,
  gridSnap: false,
  hideJunctionsAndConnections: false,
  activeFloor: 1,
  buildingFloorState: {},
  nodes: {},
  edges: [],
  lines: [],
  buildings: [],
  undoStack: [],
  redoStack: [],
  typeColors: { ...INITIAL_TYPE_COLORS }
};

let clipboardState = {
  nodes: [],
  buildings: [],
  lines: [],
  edges: []
};

let lastCtrlAKeyTime = 0;

function selectAllByType(typeName) {
  const matchingIds = [];

  if (typeName === 'junction') {
    Object.values(state.nodes).forEach((node) => {
      if (node && node.type === 'junction' && nodeMatchesVisibleFloor(node)) matchingIds.push(node.id);
    });
  } else if (typeName === 'room') {
    Object.values(state.nodes).forEach((node) => {
      if (node && isRoomLikeNode(node) && node.type !== 'stair' && nodeMatchesVisibleFloor(node)) matchingIds.push(node.id);
    });
  } else if (typeName === 'building') {
    state.buildings.forEach((building) => {
      if (building && building.id) matchingIds.push(building.id);
    });
  } else if (typeName === 'stair') {
    Object.values(state.nodes).forEach((node) => {
      if (node && node.type === 'stair' && nodeMatchesVisibleFloor(node)) matchingIds.push(node.id);
    });
  }

  if (!matchingIds.length) {
    setStatus(`No ${typeName}s to select`);
    return;
  }

  state.selectedNodeId = null;
  state.selectedBuildingId = null;
  state.selectedLineId = null;
  state.selectedIds = [...new Set(matchingIds)];
  syncSelectedIds();
  setStatus(`${state.selectedIds.length} ${typeName}${state.selectedIds.length === 1 ? '' : 's'} selected`);
  render();
}

function cloneLayoutState() {
  return JSON.parse(JSON.stringify({
    nodes: state.nodes,
    edges: state.edges,
    lines: state.lines,
    buildings: state.buildings,
    selectedNodeId: state.selectedNodeId,
    selectedLineId: state.selectedLineId,
    selectedBuildingId: state.selectedBuildingId,
    lastSelectedBuildingId: state.lastSelectedBuildingId,
    mode: state.mode,
    activeFloor: state.activeFloor,
    buildingFloorState: state.buildingFloorState,
    typeColors: state.typeColors
  }));
}

function pushHistory() {
  state.undoStack.push(cloneLayoutState());
  if (state.undoStack.length > MAX_HISTORY) {
    state.undoStack.shift();
  }
  state.redoStack = [];
}

function restoreFromSnapshot(snapshot) {
  if (!snapshot) return;
  state.nodes = snapshot.nodes;
  state.edges = snapshot.edges;
  state.lines = snapshot.lines || [];
  state.buildings = snapshot.buildings;
  state.selectedNodeId = snapshot.selectedNodeId;
  state.selectedLineId = snapshot.selectedLineId || null;
  state.selectedBuildingId = snapshot.selectedBuildingId;
  state.lastSelectedBuildingId = snapshot.lastSelectedBuildingId || snapshot.selectedBuildingId || null;
  state.mode = snapshot.mode || 'select';
  state.activeFloor = normalizeFloorValue(snapshot.activeFloor, 1);
  state.buildingFloorState = snapshot.buildingFloorState && typeof snapshot.buildingFloorState === 'object'
    ? Object.fromEntries(Object.entries(snapshot.buildingFloorState).map(([key, value]) => [key, normalizeFloorValue(value, 1)]))
    : {};
  state.typeColors = { ...INITIAL_TYPE_COLORS, ...(snapshot.typeColors || {}) };

  state.buildings.forEach((building) => {
    building.floors = normalizeFloorCount(building.floors, 1);
    ensureBuildingAnchorNode(building);
  });
}

function undoLastAction() {
  if (!state.undoStack.length) {
    setStatus('No actions to undo');
    return;
  }

  const current = cloneLayoutState();
  const previous = state.undoStack.pop();
  state.redoStack.push(current);
  if (state.redoStack.length > MAX_HISTORY) {
    state.redoStack.shift();
  }
  restoreFromSnapshot(previous);

  if (state.lastSelectedBuildingId && !getBuildingById(state.lastSelectedBuildingId)) {
    state.lastSelectedBuildingId = null;
  }

  render();
  saveLayoutToLocalStorage();
  setStatus('Undo applied');
}

function redoLastAction() {
  if (!state.redoStack.length) {
    setStatus('No actions to redo');
    return;
  }

  const current = cloneLayoutState();
  const next = state.redoStack.pop();
  state.undoStack.push(current);
  if (state.undoStack.length > MAX_HISTORY) {
    state.undoStack.shift();
  }
  restoreFromSnapshot(next);

  render();
  saveLayoutToLocalStorage();
  setStatus('Redo applied');
}

function setStatus(message) {
  // Status pill removed; status messages are intentionally ignored.
}

function showFeedbackToast(message, type = 'success') {
  const toast = document.createElement('div');
  toast.className = 'feedback-toast ' + (type === 'error' ? 'error' : type === 'warning' ? 'warning' : type === 'info' ? 'info' : 'success');
  toast.setAttribute('role', 'status');
  toast.setAttribute('aria-live', 'polite');

  const icon = document.createElement('i');
  icon.className = 'fa-solid ' + (type === 'error' ? 'fa-circle-exclamation' : type === 'warning' ? 'fa-triangle-exclamation' : type === 'info' ? 'fa-circle-info' : 'fa-circle-check');
  icon.style.marginTop = '2px';

  const text = document.createElement('span');
  text.textContent = message;

  const closeButton = document.createElement('button');
  closeButton.type = 'button';
  closeButton.setAttribute('aria-label', 'Close notification');
  closeButton.innerHTML = '&times;';
  closeButton.addEventListener('click', function () {
    if (toast && toast.parentNode) {
      toast.remove();
    }
  });

  toast.appendChild(icon);
  toast.appendChild(text);
  toast.appendChild(closeButton);
  document.body.appendChild(toast);

  window.setTimeout(function () {
    if (toast && toast.parentNode) {
      toast.remove();
    }
  }, 7000);
}

function updateJunctionVisibilityButtonState() {
  if (!toggleJunctionsBtn) return;

  toggleJunctionsBtn.textContent = state.hideJunctionsAndConnections
    ? 'Show junctions & connections'
    : 'Hide junctions & connections';
  toggleJunctionsBtn.classList.toggle('active', state.hideJunctionsAndConnections);
  toggleJunctionsBtn.setAttribute('aria-pressed', String(state.hideJunctionsAndConnections));
}

function toggleJunctionAndConnectionVisibility() {
  state.hideJunctionsAndConnections = !state.hideJunctionsAndConnections;
  updateJunctionVisibilityButtonState();
  render();
  setStatus(state.hideJunctionsAndConnections ? 'Junctions and connections hidden' : 'Junctions and connections visible');
}

function updateNavigationToggleButtons() {
  if (!appShell) return;

  const sidebarHidden = appShell.classList.contains('sidebar-hidden');
  const topbarHidden = appShell.classList.contains('topbar-hidden');

  if (toggleSidebarBtn) {
    toggleSidebarBtn.innerHTML = sidebarHidden
      ? '<i class="fa-solid fa-angles-right"></i>'
      : '<i class="fa-solid fa-angles-left"></i>';
    toggleSidebarBtn.setAttribute('aria-label', sidebarHidden ? 'Show side navigation' : 'Hide side navigation');
  }

  if (toggleTopNavBtn) {
    toggleTopNavBtn.innerHTML = topbarHidden
      ? '<i class="fa-solid fa-eye"></i>'
      : '<i class="fa-solid fa-eye-slash"></i>';
    toggleTopNavBtn.setAttribute('aria-label', topbarHidden ? 'Show top navigation' : 'Hide top navigation');
  }
}

function toggleSidebarNavigation() {
  if (!appShell) return;
  appShell.classList.toggle('sidebar-hidden');
  updateNavigationToggleButtons();
  updateEdgeRevealButtons();
}

function toggleTopNavigation() {
  if (!appShell) return;
  appShell.classList.toggle('topbar-hidden');
  updateNavigationToggleButtons();
  updateEdgeRevealButtons();
}

function updateEdgeRevealButtons() {
  if (!appShell) return;

  const sidebarHidden = appShell.classList.contains('sidebar-hidden');
  const topbarHidden = appShell.classList.contains('topbar-hidden');

  if (sidebarRevealBtn) {
    sidebarRevealBtn.classList.toggle('visible', sidebarHidden);
  }

  if (topRevealBtn) {
    topRevealBtn.classList.toggle('visible', topbarHidden);
  }
}

function handleEdgeRevealMouseMove(event) {
  if (!appShell) return;

  const sidebarHidden = appShell.classList.contains('sidebar-hidden');
  const topbarHidden = appShell.classList.contains('topbar-hidden');

  if (sidebarHidden && event.clientX <= 28) {
    if (sidebarRevealBtn) {
      sidebarRevealBtn.classList.add('visible');
      sidebarRevealBtn.style.top = `${Math.min(Math.max(event.clientY, 22), window.innerHeight - 22)}px`;
      sidebarRevealBtn.style.left = '12px';
    }
  } else if (sidebarRevealBtn) {
    sidebarRevealBtn.classList.remove('visible');
  }

  if (topbarHidden && event.clientY <= 28) {
    if (topRevealBtn) {
      topRevealBtn.classList.add('visible');
      topRevealBtn.style.left = `${Math.min(Math.max(event.clientX, 48), window.innerWidth - 48)}px`;
      topRevealBtn.style.top = '12px';
    }
  } else if (topRevealBtn) {
    topRevealBtn.classList.remove('visible');
  }
}

function syncSelectedIds() {
  const ids = new Set();
  if (state.selectedNodeId) {
    const node = getNodeById(state.selectedNodeId);
    if (node && nodeMatchesVisibleFloor(node)) {
      ids.add(state.selectedNodeId);
    }
  }
  if (state.selectedBuildingId) ids.add(state.selectedBuildingId);
  if (state.selectedLineId) ids.add(state.selectedLineId);
  if (Array.isArray(state.selectedIds)) {
    state.selectedIds.forEach((id) => {
      const node = getNodeById(id);
      if (!id) return;
      if (node && !nodeMatchesVisibleFloor(node)) return;
      ids.add(id);
    });
  }

  const lineIds = [...ids].filter((id) => !!getLineById(id));
  if (lineIds.length === 1 && ids.size === 1) {
    state.selectedLineId = lineIds[0];
    state.selectedNodeId = null;
    state.selectedBuildingId = null;
    state.selectedIds = [state.selectedLineId];
    return;
  }

  state.selectedLineId = null;
  state.selectedNodeId = [...ids].find((id) => !!getNodeById(id)) || null;
  state.selectedBuildingId = [...ids].find((id) => !!getBuildingById(id)) || null;
  state.selectedIds = [...ids].filter((id) => !getLineById(id));
}

function clearSelection() {
  state.selectedNodeId = null;
  state.selectedLineId = null;
  state.selectedBuildingId = null;
  state.selectedIds = [];
}

function rememberSelectedBuilding(buildingId) {
  if (!buildingId) return;
  const building = getBuildingById(buildingId);
  if (!building) return;
  state.lastSelectedBuildingId = building.id;
}

function getPreferredBuildingForNewNode() {
  if (state.selectedBuildingId && getBuildingById(state.selectedBuildingId)) {
    return state.selectedBuildingId;
  }
  if (state.lastSelectedBuildingId && getBuildingById(state.lastSelectedBuildingId)) {
    return state.lastSelectedBuildingId;
  }
  return state.buildings[0]?.id || null;
}

function selectLine(lineId) {
  if (!lineId) return;
  state.selectedNodeId = null;
  state.selectedBuildingId = null;
  state.selectedLineId = lineId;
  state.selectedIds = [lineId];
  syncSelectedIds();
}

function clamp(value, min, max) {
  return Math.min(Math.max(value, min), max);
}

function constrainBuildingToCanvas(building) {
  if (!building) return;

  const width = Math.max(80, Number(building.width) || 220);
  const height = Math.max(80, Number(building.height) || 180);
  const maxX = Math.max(20, MAX_CANVAS.width - width - 20);
  const maxY = Math.max(20, MAX_CANVAS.height - height - 20);

  building.x = clamp(Number(building.x) || 120, 20, maxX);
  building.y = clamp(Number(building.y) || 120, 20, maxY);

  building.width = clamp(width, 80, Math.max(80, MAX_CANVAS.width - building.x - 20));
  building.height = clamp(height, 80, Math.max(80, MAX_CANVAS.height - building.y - 20));

  if (building.x + building.width > MAX_CANVAS.width - 20) {
    building.x = MAX_CANVAS.width - building.width - 20;
  }
  if (building.y + building.height > MAX_CANVAS.height - 20) {
    building.y = MAX_CANVAS.height - building.height - 20;
  }
}

function generateId(prefix = 'wp') {
  const random = Math.random().toString(36).slice(2, 8);
  return `${prefix}-${Date.now().toString(36)}-${random}`;
}

function snapToGrid(value) {
  if (!state.gridSnap) return value;
  return Math.round(value / GRID_SIZE) * GRID_SIZE;
}

function projectPointOntoSegment(point, start, end) {
  const dx = end.x - start.x;
  const dy = end.y - start.y;
  const lengthSquared = dx * dx + dy * dy;
  if (lengthSquared === 0) {
    return { x: start.x, y: start.y, distance: Math.hypot(point.x - start.x, point.y - start.y) };
  }

  const t = Math.max(0, Math.min(1, ((point.x - start.x) * dx + (point.y - start.y) * dy) / lengthSquared));
  const x = start.x + t * dx;
  const y = start.y + t * dy;
  return { x, y, distance: Math.hypot(point.x - x, point.y - y) };
}

function snapLineEndpoint(point, ignoreLineId = null) {
  if (!point || !state.gridSnap) {
    return { x: Number(point?.x) || 0, y: Number(point?.y) || 0 };
  }

  const candidatePoints = [];

  state.lines.forEach((line) => {
    if (line && line.id !== ignoreLineId) {
      candidatePoints.push({ x: Number(line.x1) || 0, y: Number(line.y1) || 0 });
      candidatePoints.push({ x: Number(line.x2) || 0, y: Number(line.y2) || 0 });
    }
  });

  Object.values(state.nodes).forEach((node) => {
    if (!node || !isRoomLikeNode(node) || node.type === 'junction') return;

    const rect = getNodeRect(node);
    if (!rect) return;

    const edgeSegments = [
      { start: { x: rect.left, y: rect.top }, end: { x: rect.left, y: rect.bottom } },
      { start: { x: rect.right, y: rect.top }, end: { x: rect.right, y: rect.bottom } },
      { start: { x: rect.left, y: rect.top }, end: { x: rect.right, y: rect.top } },
      { start: { x: rect.left, y: rect.bottom }, end: { x: rect.right, y: rect.bottom } },
      { start: { x: rect.left, y: rect.top }, end: { x: rect.right, y: rect.bottom } },
      { start: { x: rect.right, y: rect.top }, end: { x: rect.left, y: rect.bottom } }
    ];

    edgeSegments.forEach(({ start, end }) => {
      const projected = projectPointOntoSegment(point, start, end);
      candidatePoints.push({ x: projected.x, y: projected.y, distance: projected.distance });
    });
  });

  let bestMatch = null;
  candidatePoints.forEach((candidate) => {
    const distance = Math.hypot(point.x - candidate.x, point.y - candidate.y);
    if (distance <= LINE_SNAP_DISTANCE && (!bestMatch || distance < bestMatch.distance)) {
      bestMatch = { x: candidate.x, y: candidate.y, distance };
    }
  });

  if (bestMatch) {
    return { x: clamp(bestMatch.x, 0, MAX_CANVAS.width), y: clamp(bestMatch.y, 0, MAX_CANVAS.height) };
  }

  return { x: clamp(snapToGrid(point.x), 0, MAX_CANVAS.width), y: clamp(snapToGrid(point.y), 0, MAX_CANVAS.height) };
}

function isRoomLikeNode(node) {
  return !!node && ['room', 'office', 'facility', 'faculty', 'stair', 'other'].includes(node.type);
}

function getNodeSeparationGroup(node) {
  if (!node || node.type === 'junction') return null;
  if (node.type === 'stair') return 'stair';
  if (['room', 'office', 'facility', 'faculty', 'other'].includes(node.type)) return 'room';
  return null;
}

function normalizeFontColor(value, fallbackColor = '#000000') {
  const normalized = String(value || '').trim().toLowerCase();
  if (normalized === 'white') return '#ffffff';
  if (normalized === 'black') return '#000000';

  const toFullHex = (raw) => '#' + (raw.length === 3 ? raw.split('').map((char) => char + char).join('') : raw);
  const direct = /^#?([0-9a-f]{3}|[0-9a-f]{6})$/i.exec(normalized);
  if (direct) return toFullHex(direct[1]);

  // No usable font colour: pick black/white for contrast against the background colour.
  const match = /^#?([0-9a-f]{3}|[0-9a-f]{6})$/i.exec(String(fallbackColor || '#000000').trim());
  if (!match) return '#000000';
  const fullHex = toFullHex(match[1]).slice(1);
  const red = parseInt(fullHex.slice(0, 2), 16);
  const green = parseInt(fullHex.slice(2, 4), 16);
  const blue = parseInt(fullHex.slice(4, 6), 16);
  const luminance = (0.299 * red + 0.587 * green + 0.114 * blue) / 255;
  return luminance > 0.6 ? '#000000' : '#ffffff';
}

function getFontColorForItem(item) {
  if (!item) return '#000000';
  return normalizeFontColor(item.fontColor, item.color || '#ffffff');
}

// Tags: "restroom, cr ,  comfort room" -> ['restroom', 'cr', 'comfort room'] (trimmed, no empties, no duplicates).
function normalizeTags(raw) {
  const parts = Array.isArray(raw) ? raw : String(raw || '').split(',');
  const seen = new Set();
  const result = [];
  parts.forEach((part) => {
    const tag = String(part || '').replace(/\s+/g, ' ').trim();
    const key = tag.toLowerCase();
    if (tag && !seen.has(key)) {
      seen.add(key);
      result.push(tag);
    }
  });
  return result;
}

function getAlignmentSnap(value, candidates) {
  for (const candidateValue of candidates) {
    if (Math.abs(value - candidateValue) <= 16) {
      return candidateValue;
    }
  }
  return value;
}

function getNodeRect(node) {
  if (!node) return null;
  const width = Number(node.width) || (node.type === 'junction' ? 5 : 52);
  const height = Number(node.height) || (node.type === 'junction' ? 5 : 44);
  return {
    left: node.x - width / 2,
    top: node.y - height / 2,
    right: node.x + width / 2,
    bottom: node.y + height / 2,
    width,
    height
  };
}

function doesNodeOverlap(nodeA, nodeB) {
  if (!nodeA || !nodeB || nodeA.id === nodeB.id || nodeA.type === 'junction' || nodeB.type === 'junction') {
    return false;
  }

  const rectA = getNodeRect(nodeA);
  const rectB = getNodeRect(nodeB);
  if (!rectA || !rectB) return false;

  return !(rectA.right < rectB.left || rectA.left > rectB.right || rectA.bottom < rectB.top || rectA.top > rectB.bottom);
}

function nodesShareFloor(nodeA, nodeB) {
  if (!nodeA || !nodeB) return false;

  const aBuildingId = nodeA.buildingId || null;
  const bBuildingId = nodeB.buildingId || null;
  if (aBuildingId && bBuildingId && aBuildingId !== bBuildingId) {
    return false;
  }

  return normalizeFloorValue(nodeA.floor, 1) === normalizeFloorValue(nodeB.floor, 1);
}

function keepRoomSeparated(node) {
  if (!node || !isRoomLikeNode(node) || node.type === 'junction') return;

  const currentRect = getNodeRect(node);
  if (!currentRect) return;

  const group = getNodeSeparationGroup(node);
  const candidates = Object.values(state.nodes).filter((other) => {
    if (other.id === node.id || other.type === 'junction') return false;
    return getNodeSeparationGroup(other) === group && nodesShareFloor(node, other);
  });

  for (const candidate of candidates) {
    if (!doesNodeOverlap(node, candidate)) continue;

    const candidateRect = getNodeRect(candidate);
    if (!candidateRect) continue;

    const dx = node.x - candidate.x;
    const dy = node.y - candidate.y;
    const separationX = (currentRect.width + candidateRect.width) / 2;
    const separationY = (currentRect.height + candidateRect.height) / 2;

    if (Math.abs(dx) >= Math.abs(dy)) {
      const direction = dx >= 0 ? 1 : -1;
      node.x = candidate.x + direction * separationX;
    } else {
      const direction = dy >= 0 ? 1 : -1;
      node.y = candidate.y + direction * separationY;
    }

    node.x = clamp(node.x, 20, MAX_CANVAS.width - 20);
    node.y = clamp(node.y, 20, MAX_CANVAS.height - 20);

    if (node.buildingId) {
      clampNodeToBuilding(node);
    }

    break;
  }
}

function alignNodeToNearbyNodes(node) {
  if (!node || !isRoomLikeNode(node)) return;

  const candidates = Object.values(state.nodes).filter((other) => {
    if (other.id === node.id || other.type === 'junction') return false;
    return isRoomLikeNode(other) && nodesShareFloor(node, other);
  });
  if (!candidates.length) return;

  const targetX = getAlignmentSnap(node.x, candidates.map((candidate) => candidate.x));
  const targetY = getAlignmentSnap(node.y, candidates.map((candidate) => candidate.y));

  node.x = targetX;
  node.y = targetY;
  keepRoomSeparated(node);
}

function snapNodePosition(node, x, y) {
  if (!state.gridSnap) {
    node.x = clamp(x, 10, MAX_CANVAS.width - 10);
    node.y = clamp(y, 10, MAX_CANVAS.height - 10);
  } else {
    node.x = clamp(snapToGrid(x), 10, MAX_CANVAS.width - 10);
    node.y = clamp(snapToGrid(y), 10, MAX_CANVAS.height - 10);
  }

  if (isRoomLikeNode(node)) {
    alignNodeToNearbyNodes(node);
    keepRoomSeparated(node);
  }
}

function snapLinePosition(line) {
  if (!line) return;

  const x1 = Number(line.x1) || 0;
  const y1 = Number(line.y1) || 0;
  const x2 = Number(line.x2) || 0;
  const y2 = Number(line.y2) || 0;

  const start = state.gridSnap ? snapLineEndpoint({ x: x1, y: y1 }, line.id) : { x: x1, y: y1 };
  const end = state.gridSnap ? snapLineEndpoint({ x: x2, y: y2 }, line.id) : { x: x2, y: y2 };

  line.x1 = clamp(start.x, 0, MAX_CANVAS.width);
  line.y1 = clamp(start.y, 0, MAX_CANVAS.height);
  line.x2 = clamp(end.x, 0, MAX_CANVAS.width);
  line.y2 = clamp(end.y, 0, MAX_CANVAS.height);
}

function isValidHexColor(value) {
  return /^#([0-9A-Fa-f]{3}|[0-9A-Fa-f]{6})$/.test(String(value || '').trim());
}

function normalizeNodeType(type) {
  const allowed = ['room', 'junction', 'office', 'facility', 'faculty', 'stair', 'other'];
  const normalized = String(type || 'room').toLowerCase();
  return allowed.includes(normalized) ? normalized : 'other';
}

function getTypeColor(type) {
  const normalized = normalizeNodeType(type);
  return state.typeColors[normalized] || INITIAL_TYPE_COLORS[normalized] || '#7bb7ff';
}

function getNodeById(id) {
  return state.nodes[id] || null;
}

// Position (X/Y) shown in the Properties panel for any selected item.
// Nodes (rooms, stairs, junctions) use their centre; buildings use their top-left corner;
// lines use the midpoint between their two ends.
function getItemPositionForPanel(node, building, line) {
  if (building) return { x: Number(building.x) || 0, y: Number(building.y) || 0 };
  if (line) {
    return {
      x: ((Number(line.x1) || 0) + (Number(line.x2) || 0)) / 2,
      y: ((Number(line.y1) || 0) + (Number(line.y2) || 0)) / 2
    };
  }
  if (node && node.type !== 'building') return { x: Number(node.x) || 0, y: Number(node.y) || 0 };
  return null;
}

function formatPositionValue(value) {
  return String(Math.round((Number(value) || 0) * 100) / 100);
}

function moveBuildingTo(building, nextX, nextY) {
  if (!building) return;
  const startX = Number(building.x) || 0;
  const startY = Number(building.y) || 0;
  building.x = Number.isFinite(nextX) ? nextX : startX;
  building.y = Number.isFinite(nextY) ? nextY : startY;
  constrainBuildingToCanvas(building);

  const dx = building.x - startX;
  const dy = building.y - startY;
  const anchor = ensureBuildingAnchorNode(building);
  if (anchor) {
    anchor.x = (Number(building.x) || 120) + ((Number(building.width) || 220) / 2);
    anchor.y = (Number(building.y) || 120) + ((Number(building.height) || 180) / 2);
  }
  if (!dx && !dy) return;

  // Everything inside the building moves with it, exactly like dragging it.
  Object.values(state.nodes).forEach((childNode) => {
    if (childNode.buildingId !== building.id || childNode.id === (anchor && anchor.id)) return;
    childNode.x = clamp(childNode.x + dx, 10, MAX_CANVAS.width - 10);
    childNode.y = clamp(childNode.y + dy, 10, MAX_CANVAS.height - 10);
    clampNodeToBuilding(childNode);
  });
  state.lines.forEach((childLine) => {
    if (childLine.buildingId !== building.id) return;
    childLine.x1 = clamp((Number(childLine.x1) || 0) + dx, 0, MAX_CANVAS.width);
    childLine.y1 = clamp((Number(childLine.y1) || 0) + dy, 0, MAX_CANVAS.height);
    childLine.x2 = clamp((Number(childLine.x2) || 0) + dx, 0, MAX_CANVAS.width);
    childLine.y2 = clamp((Number(childLine.y2) || 0) + dy, 0, MAX_CANVAS.height);
  });
}

function moveLineCenterTo(line, nextCenterX, nextCenterY) {
  if (!line) return;
  const x1 = Number(line.x1) || 0, y1 = Number(line.y1) || 0;
  const x2 = Number(line.x2) || 0, y2 = Number(line.y2) || 0;
  let dx = Number.isFinite(nextCenterX) ? nextCenterX - (x1 + x2) / 2 : 0;
  let dy = Number.isFinite(nextCenterY) ? nextCenterY - (y1 + y2) / 2 : 0;
  // Ignore rounding noise from the displayed value so "Apply" alone never nudges a line.
  if (Math.abs(dx) < 0.01) dx = 0;
  if (Math.abs(dy) < 0.01) dy = 0;
  // Keep the whole line on the canvas without changing its shape.
  dx = clamp(dx, -Math.min(x1, x2), MAX_CANVAS.width - Math.max(x1, x2));
  dy = clamp(dy, -Math.min(y1, y2), MAX_CANVAS.height - Math.max(y1, y2));
  line.x1 = x1 + dx; line.x2 = x2 + dx;
  line.y1 = y1 + dy; line.y2 = y2 + dy;
}

function getLineById(id) {
  return state.lines.find((line) => line.id === id) || null;
}

function getBuildingById(id) {
  return state.buildings.find((building) => building.id === id) || null;
}

function getBuildingBounds(building) {
  if (!building) return null;
  const x = Number(building.x) || 120;
  const y = Number(building.y) || 120;
  const width = Number(building.width) || 220;
  const height = Number(building.height) || 180;
  return { x, y, width, height, right: x + width, bottom: y + height };
}

function findBuildingForNode(node) {
  if (!node || node.type === 'junction') return null;
  const centerX = Number(node.x) || 0;
  const centerY = Number(node.y) || 0;
  for (const building of state.buildings) {
    const bounds = getBuildingBounds(building);
    if (!bounds) continue;
    if (centerX >= bounds.x && centerX <= bounds.right && centerY >= bounds.y && centerY <= bounds.bottom) {
      return building;
    }
  }
  return null;
}

function findBuildingForLine(line) {
  if (!line) return null;
  const lineCenterX = ((Number(line.x1) || 0) + (Number(line.x2) || 0)) / 2;
  const lineCenterY = ((Number(line.y1) || 0) + (Number(line.y2) || 0)) / 2;
  for (const building of state.buildings) {
    const bounds = getBuildingBounds(building);
    if (!bounds) continue;
    if (lineCenterX >= bounds.x && lineCenterX <= bounds.right && lineCenterY >= bounds.y && lineCenterY <= bounds.bottom) {
      return building;
    }
  }
  return null;
}

function assignNodeToBuilding(node, building) {
  if (!node || !building || node.type === 'junction') return;
  node.buildingId = building.id;
  node.buildingName = building.name;
  if (!isRoomLikeNode(node)) {
    node.group = '';
    return;
  }
  if (!node.group) {
    node.group = '';
  }
}

function clampNodeToBuilding(node) {
  if (!node || !node.buildingId || node.type === 'building' || node.type === 'junction') return;

  const building = getBuildingById(node.buildingId);
  if (!building) return;

  const bounds = getBuildingBounds(building);
  if (!bounds) return;

  const halfWidth = (Number(node.width) || (node.type === 'junction' ? 5 : 52)) / 2;
  const halfHeight = (Number(node.height) || (node.type === 'junction' ? 5 : 44)) / 2;

  node.x = clamp(node.x, bounds.x + halfWidth, bounds.right - halfWidth);
  node.y = clamp(node.y, bounds.y + halfHeight, bounds.bottom - halfHeight);
}

function normalizeName(value, fallback) {
  const cleaned = String(value || '').replace(/\s+$/g, '').trim();
  return cleaned || fallback;
}

function makeSequentialDuplicateName(baseName, existingNames = []) {
  const fallback = 'New Item';
  const base = normalizeName(baseName, fallback).trim();
  const root = String(base).replace(/\d+$/, '').replace(/\s+$/, '').trim() || fallback;
  let index = 2;
  let candidate = `${root}${index}`;

  while (existingNames.some((existingName) => normalizeName(existingName, '').toLowerCase() === candidate.toLowerCase())) {
    index += 1;
    candidate = `${root}${index}`;
  }

  return candidate;
}

function getUniqueBuildingName(baseName) {
  const candidate = normalizeName(baseName, 'New Building');
  const baseWithoutSuffix = candidate.replace(/\s+\d+$/, '').trim() || 'New Building';
  let nextName = candidate;
  let suffix = 1;

  while (state.buildings.some((building) => building.name.toLowerCase() === nextName.toLowerCase())) {
    nextName = `${baseWithoutSuffix} ${suffix}`;
    suffix += 1;
  }

  return nextName;
}

// Labels keep their exact formatting (leading/trailing/multiple spaces, blank lines).
// Only line endings are unified to "\n"; nothing is trimmed.
function normalizeLabelText(value, fallback) {
  const text = String(value == null ? '' : value).replace(/\r\n?/g, '\n');
  return text.trim() ? text : fallback;
}

function normalizeLineWidth(value, fallback = 2) {
  const numericValue = Number(value);
  if (!Number.isFinite(numericValue)) return fallback;
  return clamp(numericValue, 1, 50);
}

function normalizePendingText(value) {
  return String(value == null ? '' : value).replace(/\r\n?/g, '\n');
}

function capitalizeDestinationWords(value) {
  return String(value || '')
    .replace(/\s+/g, ' ')
    .trim()
    .split(/\s+/)
    .filter(Boolean)
    .map((word) => {
      if (!word) return word;
      return word.charAt(0).toUpperCase() + word.slice(1).toLowerCase();
    })
    .join(' ');
}

function normalizeGroupValue(value) {
  const rawText = String(value == null ? '' : value).trim();
  if (!rawText) return '';

  const normalized = rawText.toLowerCase();
  const match = normalized.match(/^(.+?)\s*\((.*)\)$/);
  if (!match) {
    return normalized.replace(/\s+/g, '_').replace(/_+/g, '_').replace(/^_+|_+$/g, '');
  }

  const groupName = String(match[1]).trim().replace(/\s+/g, '_').replace(/_+/g, '_').replace(/^_+|_+$/g, '');
  const pathLabel = capitalizeDestinationWords(match[2]);

  if (!groupName || !pathLabel) return '';
  return `${groupName} (${pathLabel})`;
}

function createMultilineSvgText({ value, x, y, fontSize, fill, fontWeight = '500', fontFamily = 'Arial, sans-serif', textAnchor = 'middle', fontStyle = 'normal', letterSpacing = 0 }) {
  const svgText = document.createElementNS('http://www.w3.org/2000/svg', 'text');
  const lines = String(value || '').split(/\r?\n/);
  const safeFontSize = Number(fontSize) || 14;
  const safeFontWeight = String(fontWeight || '500');
  const safeFontFamily = String(fontFamily || 'Arial, sans-serif');
  const safeTextAnchor = ['start', 'middle', 'end'].includes(String(textAnchor || '').toLowerCase()) ? String(textAnchor).toLowerCase() : 'middle';
  const safeFontStyle = ['normal', 'italic', 'oblique'].includes(String(fontStyle || '').toLowerCase()) ? String(fontStyle).toLowerCase() : 'normal';
  const safeLetterSpacing = Number(letterSpacing) || 0;

  svgText.setAttribute('x', String(x));
  svgText.setAttribute('y', String(y));
  svgText.setAttribute('font-size', String(safeFontSize));
  svgText.style.fontSize = `${safeFontSize}px`;
  svgText.setAttribute('font-weight', safeFontWeight);
  svgText.style.fontWeight = safeFontWeight;
  svgText.setAttribute('font-family', safeFontFamily);
  svgText.style.fontFamily = safeFontFamily;
  svgText.setAttribute('font-style', safeFontStyle);
  svgText.style.fontStyle = safeFontStyle;
  svgText.setAttribute('letter-spacing', String(safeLetterSpacing));
  svgText.style.letterSpacing = `${safeLetterSpacing}px`;
  svgText.setAttribute('fill', fill || '#1a2434');
  svgText.style.fill = fill || '#1a2434';
  svgText.setAttribute('text-anchor', safeTextAnchor);
  svgText.style.textAnchor = safeTextAnchor;
  svgText.setAttribute('dominant-baseline', 'middle');
  // Keep spaces and indentation exactly as typed (SVG collapses them by default).
  svgText.setAttributeNS('http://www.w3.org/XML/1998/namespace', 'xml:space', 'preserve');
  svgText.style.whiteSpace = 'pre';

  if (lines.length <= 1) {
    svgText.textContent = lines[0] || '';
    return svgText;
  }

  const lineHeight = safeFontSize * 1.2;
  lines.forEach((line, index) => {
    const tspan = document.createElementNS('http://www.w3.org/2000/svg', 'tspan');
    tspan.setAttribute('x', String(x));
    if (index === 0) {
      tspan.setAttribute('dy', String(-((lines.length - 1) * lineHeight) / 2));
    } else {
      tspan.setAttribute('dy', String(lineHeight));
    }
    // An empty tspan can drop its dy in some browsers, so use a no-break space for blank lines (display only).
    tspan.textContent = line === '' ? '\u00A0' : line;
    svgText.appendChild(tspan);
  });

  return svgText;
}

// Rooms, stairs, other room-like nodes and junctions can all be floor connectors.
function canBeFloorConnector(node) {
  return !!node && (node.type === 'junction' || isRoomLikeNode(node));
}

function normalizeBooleanFlag(value) {
  if (typeof value === 'string') {
    const text = value.trim().toLowerCase();
    return text === 'true' || text === '1' || text === 'yes';
  }
  return value === true || value === 1;
}

function normalizeStairDesign(value) {
  const normalized = String(value || 'horizontal').trim().toLowerCase();
  return normalized === 'vertical' ? 'vertical' : 'horizontal';
}

function normalizeFloorValue(value, fallback = 1) {
  const floorNumber = Number(value);
  if (!Number.isFinite(floorNumber) || floorNumber < 1) {
    return Number(fallback) || 1;
  }
  return Math.floor(floorNumber);
}

function normalizeFloorCount(value, fallback = 1) {
  const count = Number(value);
  if (!Number.isFinite(count) || count < 1) {
    return Number(fallback) || 1;
  }
  return Math.min(Math.floor(count), 20);
}

function getMaxBuildingFloorCount() {
  if (!state.buildings.length) {
    return 1;
  }
  return Math.max(1, ...state.buildings.map((building) => normalizeFloorCount(building.floors, 1)));
}

function getBuildingFloorLimit(buildingId) {
  const building = buildingId ? getBuildingById(buildingId) : null;
  if (building) {
    return normalizeFloorCount(building.floors, 1);
  }
  return getMaxBuildingFloorCount();
}

// Highest floor a node may be placed on: its building's floor count,
// or (for building-less nodes such as free junctions) the tallest building.
function getNodeFloorLimit(node) {
  if (node && node.buildingId && getBuildingById(node.buildingId)) {
    return getBuildingFloorLimit(node.buildingId);
  }
  return getMaxBuildingFloorCount();
}

function clampNodeFloorToLimit(node) {
  if (!node) return false;
  const limit = getNodeFloorLimit(node);
  const current = normalizeFloorValue(node.floor, 1);
  const next = clamp(current, 1, limit);
  if (next !== current || Number(node.floor) !== next) {
    node.floor = next;
    return next !== current;
  }
  return false;
}

// Rebuild the property-panel Floor dropdown so it only offers floors that
// exist for every selected item's building.
function updateFloorInputOptionsForSelection(selectedNodes) {
  if (!floorInput) return;
  const nodes = Array.isArray(selectedNodes) ? selectedNodes : [];
  const limit = nodes.length
    ? Math.min(...nodes.map((node) => getNodeFloorLimit(node)))
    : getMaxBuildingFloorCount();
  if (floorInput.dataset.floorLimit === String(limit) && floorInput.options.length === limit) {
    return;
  }
  if (document.activeElement === floorInput) {
    return;
  }
  floorInput.innerHTML = Array.from({ length: limit }, (_, index) => `<option value="${index + 1}">${index + 1}</option>`).join('');
  floorInput.dataset.floorLimit = String(limit);
}

function getBuildingVisibleFloor(buildingId) {
  if (!buildingId) return state.activeFloor;
  const maxFloor = getBuildingFloorLimit(buildingId);
  const saved = Number(state.buildingFloorState[buildingId]);
  const currentFloor = Number.isFinite(saved) && saved >= 1 ? saved : state.activeFloor;
  return clamp(currentFloor, 1, maxFloor);
}

function getFloorRangeForBuilding(building) {
  const maxFloor = normalizeFloorCount(building?.floors, 1);
  return Array.from({ length: maxFloor }, (_, index) => index + 1);
}

function getRelevantBuildingEndpointUsage() {
  const routeNodeIds = Array.isArray(window.activeRouteNodeIds) ? window.activeRouteNodeIds : [];
  const endpointIds = new Set([
    ...(state.selectedIds || []),
    ...(state.selectedNodeId ? [state.selectedNodeId] : []),
    ...routeNodeIds
  ].filter(Boolean));

  const usageByBuilding = new Map();

  endpointIds.forEach((nodeId) => {
    const node = getNodeById(nodeId);
    if (!node || !node.buildingId) return;

    const buildingId = node.buildingId;
    const floor = normalizeFloorValue(node.floor, 1);
    const current = usageByBuilding.get(buildingId) || { floors: new Set(), hasUpperFloor: false };

    current.floors.add(floor);
    current.hasUpperFloor = current.hasUpperFloor || floor > 1;
    usageByBuilding.set(buildingId, current);
  });

  return usageByBuilding;
}

function applyBuildingGroupVisibility() {
  // Every building loaded from the layout/JSON is always listed.
  // Floor buttons only appear for buildings that actually have more than one floor.
  const buildingRows = Array.from(document.querySelectorAll('.building-item'));

  buildingRows.forEach((row) => {
    const buildingId = row.dataset.buildItem;
    const building = buildingId ? getBuildingById(buildingId) : null;
    const hasMultipleFloors = !!building && normalizeFloorCount(building.floors, 1) > 1;

    row.style.display = '';

    row.querySelectorAll('[data-building-floor]').forEach((button) => {
      button.style.display = hasMultipleFloors ? '' : 'none';
      button.setAttribute('aria-hidden', String(!hasMultipleFloors));
    });
  });
}

function refreshFloorSelectOptions() {
  const maxFloor = getMaxBuildingFloorCount();
  const options = Array.from({ length: maxFloor }, (_, index) => index + 1).map((floor) => `<option value="${floor}">${floor}</option>`).join('');

  if (globalFloorSelect) {
    globalFloorSelect.innerHTML = options;
    if (state.activeFloor > maxFloor) {
      state.activeFloor = maxFloor;
    }
    globalFloorSelect.value = String(state.activeFloor || 1);
  }

  // The property-panel Floor dropdown is rebuilt per selection in syncProperties().
}

function nodeMatchesVisibleFloor(node) {
  if (!node) return false;
  const buildingFloor = node.buildingId ? getBuildingVisibleFloor(node.buildingId) : state.activeFloor;
  return normalizeFloorValue(node.floor, 1) === normalizeFloorValue(buildingFloor, 1);
}

function getBuildingColor(name) {
  const palette = ['#4f9cff', '#7ee7a8', '#f4b63d', '#ff7a59', '#9b8cff', '#2db5b6', '#ff8ac7'];
  const index = Math.abs(name.split('').reduce((sum, char) => sum + char.charCodeAt(0), 0)) % palette.length;
  return palette[index];
}

function getSelectionAccentColor() {
  const directBuilding = state.selectedBuildingId ? getBuildingById(state.selectedBuildingId) : null;
  if (directBuilding && directBuilding.color) {
    return directBuilding.color;
  }

  const activeNode = state.selectedNodeId ? getNodeById(state.selectedNodeId) : null;
  if (activeNode && activeNode.buildingId) {
    const building = getBuildingById(activeNode.buildingId);
    if (building && building.color) {
      return building.color;
    }
  }

  if (state.selectedIds && state.selectedIds.length) {
    const selectedNode = state.selectedIds
      .map((id) => getNodeById(id))
      .find((node) => node && node.buildingId);

    if (selectedNode && selectedNode.buildingId) {
      const building = getBuildingById(selectedNode.buildingId);
      if (building && building.color) {
        return building.color;
      }
    }
  }

  return '#000000';
}

function getDefaultBuildingSize() {
  const width = clamp(Math.round(MAX_CANVAS.width * 0.8), 200, MAX_CANVAS.width - 50);
  const height = clamp(Math.round(MAX_CANVAS.height * 0.3), 120, MAX_CANVAS.height - 50);
  return { width, height };
}

function getDefaultNodeSize(nodeType = 'room', buildingId = null) {
  const resolvedBuilding = buildingId ? getBuildingById(buildingId) : (state.selectedBuildingId ? getBuildingById(state.selectedBuildingId) : null);
  const buildingWidth = Number(resolvedBuilding?.width) || Math.round(MAX_CANVAS.width * 0.8);
  const buildingHeight = Number(resolvedBuilding?.height) || Math.round(MAX_CANVAS.height * 0.3);

  if (normalizeNodeType(nodeType) === 'junction') {
    return { width: 5, height: 5 };
  }

  if (normalizeNodeType(nodeType) === 'stair') {
    return {
      width: clamp(Math.max(52, Math.round(buildingWidth * 0.2)), 40, buildingWidth),
      height: clamp(Math.max(40, Math.round(buildingHeight * 0.8)), 30, buildingHeight)
    };
  }

  return {
    width: clamp(Math.round(buildingWidth * 0.2), 36, buildingWidth),
    height: clamp(Math.round(buildingHeight * 0.8), 32, buildingHeight)
  };
}

function getNodePositionFromEvent(evt) {
  const point = svg.createSVGPoint();
  point.x = evt.clientX;
  point.y = evt.clientY;
  const ctm = svg.getScreenCTM();
  const transformed = point.matrixTransform(ctm.inverse());
  return {
    x: clamp(transformed.x, 0, MAX_CANVAS.width),
    y: clamp(transformed.y, 0, MAX_CANVAS.height)
  };
}

function createDefaultBuildings() {
  return [
    { id: 'building-cict', name: 'CICT', color: '#4f9cff', fontColor: 'black', floors: 1, fontSize: 14, fontWeight: 700, fontFamily: 'Arial, sans-serif' },
    { id: 'building-coed', name: 'COED', color: '#7ee7a8', fontColor: 'black', floors: 1, fontSize: 14, fontWeight: 700, fontFamily: 'Arial, sans-serif' },
    { id: 'building-cmbt', name: 'CMBT', color: '#f4b63d', fontColor: 'black', floors: 1, fontSize: 14, fontWeight: 700, fontFamily: 'Arial, sans-serif' }
  ];
}

function createBlankLayout() {
  return {
    nodes: {},
    edges: [],
    lines: [],
    buildings: []
  };
}

function createDefaultLayout() {
  const buildings = [
    { id: 'building-cict', name: 'CICT', color: '#4f9cff', fontColor: 'black', floors: 1, nodeId: 'building-cict-node', x: 120, y: 300, width: 560, height: 700, fontSize: 14, fontWeight: 700, fontFamily: 'Arial, sans-serif' },
    { id: 'building-coed', name: 'COED', color: '#7ee7a8', fontColor: 'black', floors: 1, nodeId: 'building-coed-node', x: 820, y: 300, width: 300, height: 220, fontSize: 14, fontWeight: 700, fontFamily: 'Arial, sans-serif' },
    { id: 'building-cmbt', name: 'CMBT', color: '#f4b63d', fontColor: 'black', floors: 1, nodeId: 'building-cmbt-node', x: 820, y: 620, width: 300, height: 220, fontSize: 14, fontWeight: 700, fontFamily: 'Arial, sans-serif' }
  ];
  const nodes = {
    'wp-main-entrance': { id: 'wp-main-entrance', label: 'Main Entrance', x: 220, y: 870, floor: 1, type: 'facility', group: 'main', buildingId: 'building-cict', buildingName: 'CICT', width: 90, height: 56, fontSize: 14, fontWeight: 500, fontFamily: 'Arial, sans-serif', fontColor: 'black' },
    'wp-admin-office': { id: 'wp-admin-office', label: 'Admin Office', x: 440, y: 760, floor: 1, type: 'room', group: 'cict', buildingId: 'building-cict', buildingName: 'CICT', width: 120, height: 52, fontSize: 14, fontWeight: 500, fontFamily: 'Arial, sans-serif', fontColor: 'black' },
    'wp-cict-hall': { id: 'wp-cict-hall', label: 'CICT Hall', x: 690, y: 320, floor: 1, type: 'facility', group: 'cict', buildingId: 'building-cict', buildingName: 'CICT', width: 110, height: 58, fontSize: 14, fontWeight: 500, fontFamily: 'Arial, sans-serif', fontColor: 'black' },
    'wp-coed-room': { id: 'wp-coed-room', label: 'COED Room', x: 260, y: 420, floor: 1, type: 'room', group: 'coed', buildingId: 'building-coed', buildingName: 'COED', width: 120, height: 52, fontSize: 14, fontWeight: 500, fontFamily: 'Arial, sans-serif', fontColor: 'black' },
    'wp-lab-room': { id: 'wp-lab-room', label: 'Lab Room', x: 970, y: 560, floor: 1, type: 'room', group: 'draft', buildingId: 'building-cmbt', buildingName: 'CMBT', width: 120, height: 52, fontSize: 14, fontWeight: 500, fontFamily: 'Arial, sans-serif', fontColor: 'black' },
    'wp-junction-a': { id: 'wp-junction-a', label: 'Junction A', x: 510, y: 540, floor: 1, type: 'junction', group: 'corridor', buildingId: 'building-cict', buildingName: 'CICT', width: 5, height: 5, fontSize: 14, fontWeight: 600, fontFamily: 'Arial, sans-serif', fontColor: 'black' }
  };

  return {
    nodes,
    edges: [
      ['wp-main-entrance', 'wp-admin-office'],
      ['wp-admin-office', 'wp-junction-a'],
      ['wp-junction-a', 'wp-cict-hall'],
      ['wp-junction-a', 'wp-coed-room'],
      ['wp-junction-a', 'wp-lab-room']
    ],
    buildings
  };
}

function inferBuildingsFromNodes(nodeMap) {
  const inferred = [];
  const seen = new Set();
  Object.values(nodeMap).forEach((node) => {
    if (!node || !node.buildingId) return;
    if (seen.has(node.buildingId)) return;
    seen.add(node.buildingId);
    inferred.push({
      id: node.buildingId,
      name: normalizeName(node.buildingName || node.buildingId, 'Unnamed Building'),
      color: getBuildingColor(node.buildingId),
      floors: 1
    });
  });
  return inferred;
}

function mergeBuildingDefinitions(nodeMap, knownBuildings = []) {
  const byId = new Map();
  (knownBuildings || []).forEach((building) => {
    if (!building || !building.id) return;
    byId.set(String(building.id), { ...building });
  });

  const merged = [];
  const seen = new Set();

  [...inferBuildingsFromNodes(nodeMap), ...(knownBuildings || [])].forEach((building) => {
    if (!building || !building.id) return;
    const id = String(building.id);
    if (seen.has(id)) return;
    seen.add(id);

    const existing = byId.get(id) || {};
    merged.push({
      ...existing,
      ...building,
      id,
      name: normalizeName(existing.name || building.name || id, 'Unnamed Building'),
      color: existing.color || building.color || getBuildingColor(id),
      x: Number(existing.x ?? building.x ?? 120) || 120,
      y: Number(existing.y ?? building.y ?? 120) || 120,
      width: Number(existing.width ?? building.width ?? 220) || 220,
      height: Number(existing.height ?? building.height ?? 180) || 180,
      floors: normalizeFloorCount(existing.floors ?? building.floors ?? 1, 1),
      nodeId: String(existing.nodeId || building.nodeId || `${id}-node`),
      rotation: normalizeRotationValue(existing.rotation ?? building.rotation ?? 0),
      fontSize: clamp(Number(existing.fontSize ?? building.fontSize ?? 14), 8, 48),
      fontWeight: clamp(Number(existing.fontWeight ?? building.fontWeight ?? 700), 100, 900),
      fontFamily: String(existing.fontFamily || building.fontFamily || 'Arial, sans-serif'),
      fontStyle: ['normal', 'italic', 'oblique'].includes(String(existing.fontStyle || building.fontStyle || '').toLowerCase())
        ? String(existing.fontStyle || building.fontStyle || 'normal').toLowerCase()
        : 'normal',
      textAnchor: ['start', 'middle', 'end'].includes(String(existing.textAnchor || building.textAnchor || '').toLowerCase())
        ? String(existing.textAnchor || building.textAnchor || 'middle').toLowerCase()
        : 'middle',
      letterSpacing: clamp(Number(existing.letterSpacing ?? building.letterSpacing ?? 0), -20, 50),
      fontColor: normalizeFontColor(existing.fontColor ?? building.fontColor ?? 'black', existing.color || building.color || getBuildingColor(id)),
      borderEnabled: !!(existing.borderEnabled ?? building.borderEnabled ?? false),
      borderWidth: clamp(Number(existing.borderWidth ?? building.borderWidth ?? 0), 0, 12)
    });
  });

  return merged;
}

function normalizeLayout(data) {
  if (!data || typeof data !== 'object') return createDefaultLayout();

  const nodeMap = data.nodes || {};
  const safeNodes = {};
  const safeTypeColors = { ...INITIAL_TYPE_COLORS, ...(data.typeColors || {}) };
  Object.entries(nodeMap).forEach(([id, node], index) => {
    if (!node || typeof node !== 'object') return;
    const nodeType = normalizeNodeType(node.type || 'room');
    if (nodeType === 'building') return;
    const defaultSize = nodeType === 'junction' ? { width: 5, height: 5 } : { width: 52, height: 44 };
    const nextMapValue = String(node.nextMap || node.nextJsonMap || node.targetMap || node.mapLink || '').trim();
    const nextNodeValue = String(node.nextMapNodeId || node.nextNodeId || node.targetNodeId || node.mapNodeId || '').trim();

    const zIndexValue = Number(node.zIndex);
    const resolvedZIndex = Number.isFinite(zIndexValue) ? zIndexValue : index;
    safeNodes[id] = {
      id: String(node.id || id),
      label: (typeof node.label === 'string' && node.label !== '') ? node.label : String(node.label || id),
      x: Number(node.x) || 0,
      y: Number(node.y) || 0,
      floor: Number(node.floor) || 1,
      type: nodeType,
      group: isRoomLikeNode({ type: nodeType }) ? (node.group || '') : '',
      buildingId: node.buildingId || null,
      buildingName: node.buildingName || null,
      width: Number(node.width) || defaultSize.width,
      height: Number(node.height) || defaultSize.height,
      rotation: normalizeRotationValue(node.rotation),
      color: isValidHexColor(node.color) ? node.color : safeTypeColors[nodeType],
      fontSize: clamp(Number(node.fontSize) || 14, 8, 48),
      fontWeight: clamp(Number(node.fontWeight) || (nodeType === 'junction' ? 600 : 500), 100, 900),
      fontFamily: String(node.fontFamily || 'Arial, sans-serif'),
      fontStyle: ['normal', 'italic', 'oblique'].includes(String(node.fontStyle || '').toLowerCase()) ? String(node.fontStyle).toLowerCase() : 'normal',
      textAnchor: ['start', 'middle', 'end'].includes(String(node.textAnchor || '').toLowerCase()) ? String(node.textAnchor).toLowerCase() : 'middle',
      letterSpacing: clamp(Number(node.letterSpacing) || 0, -20, 50),
      allowMultipleJunctions: !!node.allowMultipleJunctions,
      disableDestination: !!node.disableDestination,
      tags: isRoomLikeNode({ type: nodeType }) ? normalizeTags(node.tags) : [],
      hideDisplay: nodeType === 'junction' ? false : !!node.hideDisplay,
      floorConnector: canBeFloorConnector({ type: nodeType }) ? normalizeBooleanFlag(node.floorConnector) : false,
      borderEnabled: !!node.borderEnabled,
      borderWidth: clamp(Number(node.borderWidth) || 0, 0, 12),
      stairDesign: nodeType === 'stair' ? normalizeStairDesign(node.stairDesign) : 'horizontal',
      fontColor: normalizeFontColor(node.fontColor, isValidHexColor(node.color) ? node.color : safeTypeColors[nodeType]),
      zIndex: resolvedZIndex,
      nextMap: nextMapValue,
      nextMapNodeId: nextNodeValue
    };
  });

  const rawBuildings = Array.isArray(data.buildings) && data.buildings.length ? data.buildings : inferBuildingsFromNodes(safeNodes);
  const safeBuildings = mergeBuildingDefinitions(safeNodes, rawBuildings).filter((building) => building && building.id).map((building) => ({
    id: String(building.id),
    name: normalizeLabelText(building.name, 'Unnamed Building'),
    color: building.color || getBuildingColor(building.name || building.id),
    nodeId: String(building.nodeId || `${building.id}-node`),
    x: Number(building.x) || 120,
    y: Number(building.y) || 120,
    width: Number(building.width) || 220,
    height: Number(building.height) || 180,
    floors: normalizeFloorCount(building.floors, 1),
    rotation: normalizeRotationValue(building.rotation),
    fontSize: clamp(Number(building.fontSize) || 14, 8, 48),
    fontWeight: clamp(Number(building.fontWeight) || 700, 100, 900),
    fontFamily: String(building.fontFamily || 'Arial, sans-serif'),
    fontStyle: ['normal', 'italic', 'oblique'].includes(String(building.fontStyle || '').toLowerCase()) ? String(building.fontStyle).toLowerCase() : 'normal',
    textAnchor: ['start', 'middle', 'end'].includes(String(building.textAnchor || '').toLowerCase()) ? String(building.textAnchor).toLowerCase() : 'middle',
    letterSpacing: clamp(Number(building.letterSpacing) || 0, -20, 50),
    fontColor: normalizeFontColor(building.fontColor, building.color || getBuildingColor(building.name || building.id)),
    borderEnabled: !!building.borderEnabled,
    borderWidth: clamp(Number(building.borderWidth) || 0, 0, 12)
  }));

  const safeEdges = Array.isArray(data.edges) ? data.edges : [];
  const normalizedEdges = safeEdges
    .filter(Array.isArray)
    .map(([from, to]) => [String(from), String(to)])
    .filter(([from, to]) => from && to && safeNodes[from] && safeNodes[to] && from !== to);

  // Older layouts have no floorConnector flag. Treat the ends of every edge that crosses floors
  // as floor connectors, but only when the flag is missing, so an explicit true/false is kept.
  normalizedEdges.forEach(([from, to]) => {
    if (safeNodes[from].floor === safeNodes[to].floor) return;
    [from, to].forEach((id) => {
      const rawNode = nodeMap[id];
      if (rawNode && rawNode.floorConnector === undefined && canBeFloorConnector(safeNodes[id])) {
        safeNodes[id].floorConnector = true;
      }
    });
  });

  const safeLines = Array.isArray(data.lines) ? data.lines : [];
  const normalizedLines = safeLines
    .filter((line) => line && typeof line === 'object')
    .map((line) => {
      const width = normalizeLineWidth(line.width ?? line.lineThickness ?? line.strokeWidth ?? line.thickness, 2);
      return {
        id: String(line.id || generateId('line')),
        x1: Number(line.x1) || 0,
        y1: Number(line.y1) || 0,
        x2: Number(line.x2) || 0,
        y2: Number(line.y2) || 0,
        rotation: normalizeRotationValue(line.rotation),
        color: '#000000',
        width,
        lineThickness: width,
        strokeWidth: width,
        lineCap: String(line.lineCap || 'round'),
        dashArray: []
      };
    });

  const buildingNames = new Map(safeBuildings.map((building) => [building.id, building.name]));
  Object.values(safeNodes).forEach((node) => {
    if (node.buildingId && buildingNames.has(node.buildingId)) {
      node.buildingName = buildingNames.get(node.buildingId);
    }
  });

  state.typeColors = { ...INITIAL_TYPE_COLORS, ...safeTypeColors };
  state.hideJunctionsAndConnections = !!data.hideJunctionsAndConnections;
  state.gridSnap = !!data.gridSnap;
  state.activeFloor = normalizeFloorValue(data.activeFloor, 1);
  state.buildingFloorState = data.buildingFloorState && typeof data.buildingFloorState === 'object'
    ? Object.fromEntries(Object.entries(data.buildingFloorState).map(([key, value]) => [key, normalizeFloorValue(value, state.activeFloor)]))
    : {};

  return {
    nodes: safeNodes,
    edges: normalizedEdges,
    lines: normalizedLines,
    buildings: safeBuildings,
    typeColors: state.typeColors,
    hideJunctionsAndConnections: state.hideJunctionsAndConnections,
    gridSnap: state.gridSnap,
    activeFloor: state.activeFloor,
    buildingFloorState: state.buildingFloorState
  };
}

function resetCanvasSizeToDefault() {
  MAX_CANVAS = { ...DEFAULT_CANVAS_SIZE };
  svg.setAttribute('viewBox', `0 0 ${MAX_CANVAS.width} ${MAX_CANVAS.height}`);
  svg.setAttribute('width', '100%');
  svg.setAttribute('height', '100%');
  svg.style.width = '100%';
  svg.style.height = '100%';
  mapWidthInput.value = String(MAX_CANVAS.width);
  mapHeightInput.value = String(MAX_CANVAS.height);
}

function getLayoutExportObject() {
  const exportLines = state.lines.map((line) => {
    const width = normalizeLineWidth(line.width ?? line.lineThickness ?? line.strokeWidth ?? line.thickness, 2);
    return {
      ...line,
      width,
      lineThickness: width,
      strokeWidth: width,
      lineCap: String(line.lineCap || 'round'),
      dashArray: []
    };
  });

  const exportBuildings = mergeBuildingDefinitions(state.nodes, state.buildings);

  const exportNodes = Object.fromEntries(Object.entries(state.nodes).map(([id, node], index) => {
    const normalNode = node && typeof node === 'object' ? { ...node } : {};
    const zIndexValue = Number(normalNode.zIndex);
    normalNode.zIndex = Number.isFinite(zIndexValue) ? zIndexValue : index;
    // Always written to the JSON so the flag survives export/import.
    normalNode.floorConnector = canBeFloorConnector(normalNode) ? normalizeBooleanFlag(normalNode.floorConnector) : false;
    if (!Object.prototype.hasOwnProperty.call(normalNode, 'fontColor')) {
      normalNode.fontColor = normalizeFontColor(normalNode.fontColor, normalNode.color || getTypeColor(normalNode.type || 'room'));
    } else {
      normalNode.fontColor = normalizeFontColor(normalNode.fontColor, normalNode.color || getTypeColor(normalNode.type || 'room'));
    }
    return [id, normalNode];
  }));

  return {
    nodes: exportNodes,
    edges: state.edges,
    lines: exportLines,
    buildings: exportBuildings,
    typeColors: state.typeColors,
    hideJunctionsAndConnections: !!state.hideJunctionsAndConnections,
    gridSnap: !!state.gridSnap,
    activeFloor: state.activeFloor,
    buildingFloorState: state.buildingFloorState,
    canvasSize: { ...MAX_CANVAS }
  };
}

function saveLayoutToLocalStorage() {
  const layout = getLayoutExportObject();
  localStorage.setItem(STORAGE_KEY, JSON.stringify(layout, null, 2));
  setStatus('Layout saved');
}

function loadLayoutFromLocalStorage() {
  const raw = localStorage.getItem(STORAGE_KEY);
  const blank = createBlankLayout();
  state.nodes = blank.nodes;
  state.edges = blank.edges;
  state.lines = blank.lines;
  state.buildings = blank.buildings;
  state.typeColors = { ...INITIAL_TYPE_COLORS };

  if (!raw) {
    resetCanvasSizeToDefault();
    return false;
  }

  try {
    const parsed = JSON.parse(raw);
    if (parsed && parsed.nodes) {
      const normalized = normalizeLayout(parsed);
      state.nodes = normalized.nodes;
      state.edges = normalized.edges;
      state.lines = normalized.lines || [];
      state.buildings = normalized.buildings;
      if (parsed.canvasSize && parsed.canvasSize.width && parsed.canvasSize.height) {
        MAX_CANVAS = {
          width: clamp(Number(parsed.canvasSize.width) || DEFAULT_CANVAS_SIZE.width, 300, 5000),
          height: clamp(Number(parsed.canvasSize.height) || DEFAULT_CANVAS_SIZE.height, 300, 5000)
        };
        svg.setAttribute('viewBox', `0 0 ${MAX_CANVAS.width} ${MAX_CANVAS.height}`);
        svg.setAttribute('width', '100%');
        svg.setAttribute('height', '100%');
        svg.style.width = '100%';
        svg.style.height = '100%';
        mapWidthInput.value = String(MAX_CANVAS.width);
        mapHeightInput.value = String(MAX_CANVAS.height);
      }
      if (normalized.typeColors) {
        state.typeColors = { ...INITIAL_TYPE_COLORS, ...normalized.typeColors };
      }
      return true;
    }
  } catch (error) {
    console.warn('Failed to parse saved layout:', error);
  }

  return false;
}

async function loadCurrentProjectLayout() {
  const blank = createBlankLayout();
  state.nodes = blank.nodes;
  state.edges = blank.edges;
  state.lines = blank.lines;
  state.buildings = blank.buildings;
  state.typeColors = { ...INITIAL_TYPE_COLORS };

  const candidateFiles = ['map.json', 'jsons/map-layout.json', 'jsons/trial.json'];

  for (const candidate of candidateFiles) {
    try {
      const response = await fetch(candidate, { cache: 'no-store' });
      if (!response.ok) continue;

      const parsed = await response.json();
      if (!parsed || typeof parsed !== 'object' || !parsed.nodes) continue;

      const normalized = normalizeLayout(parsed);
      state.nodes = normalized.nodes;
      state.edges = normalized.edges;
      state.lines = normalized.lines || [];
      state.buildings = normalized.buildings;

      if (parsed.canvasSize && parsed.canvasSize.width && parsed.canvasSize.height) {
        MAX_CANVAS = {
          width: clamp(Number(parsed.canvasSize.width) || DEFAULT_CANVAS_SIZE.width, 300, 5000),
          height: clamp(Number(parsed.canvasSize.height) || DEFAULT_CANVAS_SIZE.height, 300, 5000)
        };
      } else {
        resetCanvasSizeToDefault();
      }

      if (normalized.typeColors) {
        state.typeColors = { ...INITIAL_TYPE_COLORS, ...normalized.typeColors };
      }

      svg.setAttribute('viewBox', `0 0 ${MAX_CANVAS.width} ${MAX_CANVAS.height}`);
      svg.setAttribute('width', '100%');
      svg.setAttribute('height', '100%');
      svg.style.width = '100%';
      svg.style.height = '100%';
      if (mapWidthInput) mapWidthInput.value = String(MAX_CANVAS.width);
      if (mapHeightInput) mapHeightInput.value = String(MAX_CANVAS.height);
      return true;
    } catch (error) {
      console.warn(`Could not load campus layout from ${candidate}:`, error);
    }
  }

  if (typeof localStorage !== 'undefined' && loadLayoutFromLocalStorage()) {
    return true;
  }

  const defaultLayout = createDefaultLayout();
  state.nodes = defaultLayout.nodes;
  state.edges = defaultLayout.edges;
  state.lines = [];
  state.buildings = defaultLayout.buildings;
  resetCanvasSizeToDefault();
  return false;
}

function getNodeColor(type, floor, explicitColor) {
  const floorColors = {
    1: '#4f9cff',
    2: '#ff7a59',
    3: '#2db5b6'
  };

  if (isValidHexColor(explicitColor)) return explicitColor;

  const normalized = normalizeNodeType(type);
  if (isValidHexColor(state.typeColors[normalized])) return state.typeColors[normalized];
  if (normalized === 'room') return '#7bb7ff';
  if (normalized === 'junction') return '#f4b63d';
  if (normalized === 'office') return '#c7a1ff';
  if (normalized === 'facility') return '#7ee7a8';
  if (normalized === 'faculty') return '#ff9d7a';
  if (normalized === 'stair') return '#8b7bff';
  return floorColors[floor] || '#b5c7d6';
}

function getNodesInBuilding(buildingId) {
  return Object.values(state.nodes).filter((node) => node.buildingId === buildingId && node.type !== 'building');
}

function ensureBuildingAnchorNode(building) {
  if (!building) return null;
  const nodeId = building.nodeId || `${building.id}-node`;
  building.nodeId = nodeId;
  return null;
}

function renderAxisGuide() {
  const axisLayer = document.getElementById('axisLayer');
  if (!axisLayer) return;

  axisLayer.innerHTML = '';

  const centerX = MAX_CANVAS.width / 2;
  const centerY = MAX_CANVAS.height / 2;

  const mapBoundary = document.createElementNS('http://www.w3.org/2000/svg', 'rect');
  mapBoundary.setAttribute('x', 0);
  mapBoundary.setAttribute('y', 0);
  mapBoundary.setAttribute('width', MAX_CANVAS.width);
  mapBoundary.setAttribute('height', MAX_CANVAS.height);
  mapBoundary.setAttribute('class', 'map-boundary');
  mapBoundary.setAttribute('fill', 'none');
  mapBoundary.setAttribute('pointer-events', 'none');

  const isGroupSelection = !state.selectionBox && state.selectedIds.length > 1;
  const selectionRectData = state.selectionBox ? normalizeSelectionRect(state.selectionBox) : getSelectedGroupBounds();
  if (selectionRectData) {
    const selectionRect = document.createElementNS('http://www.w3.org/2000/svg', 'rect');
    const selectionColor = getSelectionAccentColor();
    selectionRect.setAttribute('x', selectionRectData.x);
    selectionRect.setAttribute('y', selectionRectData.y);
    selectionRect.setAttribute('width', selectionRectData.width);
    selectionRect.setAttribute('height', selectionRectData.height);
    selectionRect.setAttribute('class', 'selection-box');
    selectionRect.style.fill = selectionColor;
    selectionRect.style.fillOpacity = '0.12';
    selectionRect.style.stroke = selectionColor;
    selectionRect.style.strokeWidth = '1.5';
    selectionRect.style.strokeDasharray = '8 6';
    selectionRect.setAttribute('pointer-events', isGroupSelection ? 'all' : 'none');
    selectionRect.setAttribute('data-selection-group', isGroupSelection ? 'true' : 'false');
    axisLayer.appendChild(selectionRect);
  }

  const xLine = document.createElementNS('http://www.w3.org/2000/svg', 'line');
  xLine.setAttribute('x1', 0);
  xLine.setAttribute('y1', centerY);
  xLine.setAttribute('x2', MAX_CANVAS.width);
  xLine.setAttribute('y2', centerY);
  xLine.setAttribute('class', 'axis-guide');

  const yLine = document.createElementNS('http://www.w3.org/2000/svg', 'line');
  yLine.setAttribute('x1', centerX);
  yLine.setAttribute('y1', 0);
  yLine.setAttribute('x2', centerX);
  yLine.setAttribute('y2', MAX_CANVAS.height);
  yLine.setAttribute('class', 'axis-guide');

  const xLabel = document.createElementNS('http://www.w3.org/2000/svg', 'text');
  xLabel.setAttribute('x', MAX_CANVAS.width - 30);
  xLabel.setAttribute('y', centerY - 10);
  xLabel.setAttribute('class', 'axis-label');
  xLabel.textContent = 'X';

  const yLabel = document.createElementNS('http://www.w3.org/2000/svg', 'text');
  yLabel.setAttribute('x', centerX + 12);
  yLabel.setAttribute('y', 20);
  yLabel.setAttribute('class', 'axis-label');
  yLabel.textContent = 'Y';

  axisLayer.appendChild(mapBoundary);
  axisLayer.appendChild(xLine);
  axisLayer.appendChild(yLine);
  axisLayer.appendChild(xLabel);
  axisLayer.appendChild(yLabel);

  if (!state.gridSnap) return;

  for (let x = 0; x <= MAX_CANVAS.width; x += GRID_SIZE) {
    const guide = document.createElementNS('http://www.w3.org/2000/svg', 'line');
    guide.setAttribute('x1', x);
    guide.setAttribute('y1', 0);
    guide.setAttribute('x2', x);
    guide.setAttribute('y2', MAX_CANVAS.height);
    guide.setAttribute('class', 'axis-guide');
    guide.setAttribute('opacity', '0.45');
    axisLayer.appendChild(guide);
  }

  for (let y = 0; y <= MAX_CANVAS.height; y += GRID_SIZE) {
    const guide = document.createElementNS('http://www.w3.org/2000/svg', 'line');
    guide.setAttribute('x1', 0);
    guide.setAttribute('y1', y);
    guide.setAttribute('x2', MAX_CANVAS.width);
    guide.setAttribute('y2', y);
    guide.setAttribute('class', 'axis-guide');
    guide.setAttribute('opacity', '0.45');
    axisLayer.appendChild(guide);
  }
}

function normalizeRotationValue(value) {
  const rotation = Number(value) || 0;
  return clamp(rotation, -360, 360);
}

function normalizeSelectionRect(rect) {
  const x = rect.width >= 0 ? rect.x : rect.x + rect.width;
  const y = rect.height >= 0 ? rect.y : rect.y + rect.height;
  const width = Math.abs(rect.width);
  const height = Math.abs(rect.height);
  return { x, y, width, height };
}

function lineIntersectsRect(line, rect) {
  if (!line || !rect) return false;

  const left = rect.x;
  const top = rect.y;
  const right = rect.x + rect.width;
  const bottom = rect.y + rect.height;
  const x1 = Number(line.x1);
  const y1 = Number(line.y1);
  const x2 = Number(line.x2);
  const y2 = Number(line.y2);

  const minX = Math.min(x1, x2);
  const maxX = Math.max(x1, x2);
  const minY = Math.min(y1, y2);
  const maxY = Math.max(y1, y2);

  if (maxX < left || minX > right || maxY < top || minY > bottom) {
    return false;
  }

  return true;
}

function getSelectedGroupBounds() {
  if (!state.selectedIds || state.selectedIds.length <= 1) return null;

  const bounds = [];

  state.selectedIds.forEach((id) => {
    const node = getNodeById(id);
    if (node) {
      const width = Number(node.width) || (node.type === 'junction' ? 5 : 52);
      const height = Number(node.height) || (node.type === 'junction' ? 5 : 44);
      bounds.push({
        left: node.x - width / 2,
        top: node.y - height / 2,
        right: node.x + width / 2,
        bottom: node.y + height / 2
      });
      return;
    }

    const building = getBuildingById(id);
    if (building) {
      const width = Number(building.width) || 220;
      const height = Number(building.height) || 180;
      bounds.push({
        left: Number(building.x) || 120,
        top: Number(building.y) || 120,
        right: (Number(building.x) || 120) + width,
        bottom: (Number(building.y) || 120) + height
      });
      return;
    }

    const line = getLineById(id);
    if (line) {
      bounds.push({
        left: Math.min(line.x1, line.x2),
        top: Math.min(line.y1, line.y2),
        right: Math.max(line.x1, line.x2),
        bottom: Math.max(line.y1, line.y2)
      });
    }
  });

  if (!bounds.length) return null;

  const minX = Math.min(...bounds.map((item) => item.left));
  const minY = Math.min(...bounds.map((item) => item.top));
  const maxX = Math.max(...bounds.map((item) => item.right));
  const maxY = Math.max(...bounds.map((item) => item.bottom));

  return {
    x: minX,
    y: minY,
    width: Math.max(0, maxX - minX),
    height: Math.max(0, maxY - minY)
  };
}

function applySelectionFromBox(rect) {
  const normalized = normalizeSelectionRect(rect);
  const left = normalized.x;
  const top = normalized.y;
  const right = left + normalized.width;
  const bottom = top + normalized.height;

  const matchedNodeIds = [];
  Object.values(state.nodes).forEach((node) => {
    const xInside = node.x >= left && node.x <= right;
    const yInside = node.y >= top && node.y <= bottom;
    if (xInside && yInside) {
      matchedNodeIds.push(node.id);
    }
  });

  const matchedBuildingIds = [];
  state.buildings.forEach((building) => {
    const buildingX = Number(building.x) || 120;
    const buildingY = Number(building.y) || 120;
    const buildingRight = buildingX + (Number(building.width) || 220);
    const buildingBottom = buildingY + (Number(building.height) || 180);
    const hits = !(buildingRight < left || buildingX > right || buildingBottom < top || buildingY > bottom);
    if (hits) {
      matchedBuildingIds.push(building.id);
    }
  });

  const matchedLineIds = [];
  state.lines.forEach((line) => {
    if (lineIntersectsRect(line, normalized)) {
      matchedLineIds.push(line.id);
    }
  });

  if (!matchedNodeIds.length && !matchedBuildingIds.length && !matchedLineIds.length) {
    clearSelection();
    setStatus('No items selected');
    state.selectionBox = null;
    render();
    return;
  }

  if (matchedLineIds.length && !matchedNodeIds.length && !matchedBuildingIds.length) {
    state.selectedLineId = matchedLineIds[0];
    state.selectedNodeId = null;
    state.selectedBuildingId = null;
  } else {
    state.selectedLineId = null;
    if (matchedNodeIds.length) {
      state.selectedNodeId = matchedNodeIds[0];
    } else {
      state.selectedNodeId = null;
    }

    if (matchedBuildingIds.length) {
      state.selectedBuildingId = matchedBuildingIds[0];
      rememberSelectedBuilding(state.selectedBuildingId);
    } else {
      state.selectedBuildingId = null;
    }
  }

  state.selectedIds = [...new Set([...matchedNodeIds, ...matchedBuildingIds, ...matchedLineIds])];
  const total = state.selectedIds.length;
  setStatus(`${total} item${total > 1 ? 's' : ''} selected`);
  state.selectionBox = null;
  render();
}

function renderBuildings() {
  const buildingLayer = document.createElementNS('http://www.w3.org/2000/svg', 'g');
  buildingLayer.setAttribute('id', 'buildingLayer');

  state.buildings.forEach((building) => {
    ensureBuildingAnchorNode(building);
    const buildingX = Number(building.x) || 120;
    const buildingY = Number(building.y) || 120;
    const width = Number(building.width) || 220;
    const height = Number(building.height) || 180;
    const buildingColor = building.color || '#4f9cff';
    const rotation = Number(building.rotation) || 0;
    const centerX = buildingX + width / 2;
    const centerY = buildingY + height / 2;

    const buildingGroup = document.createElementNS('http://www.w3.org/2000/svg', 'g');
    buildingGroup.setAttribute('transform', `rotate(${rotation} ${centerX} ${centerY})`);
    buildingGroup.setAttribute('data-building-id', building.id);

    const rect = document.createElementNS('http://www.w3.org/2000/svg', 'rect');
    rect.setAttribute('x', buildingX);
    rect.setAttribute('y', buildingY);
    rect.setAttribute('width', width);
    rect.setAttribute('height', height);
    rect.setAttribute('rx', 0);
    rect.setAttribute('ry', 0);
    rect.setAttribute('class', 'building-shell');
    rect.setAttribute('data-building-id', building.id);
    rect.style.fill = buildingColor;
    rect.style.fillOpacity = '0.18';
    rect.style.stroke = buildingColor;
    rect.style.strokeWidth = !!building.borderEnabled && Number(building.borderWidth) > 0 ? String(Number(building.borderWidth) || 1) : '0';
    rect.style.cursor = 'move';
    rect.style.pointerEvents = 'all';

    const label = createMultilineSvgText({
      value: building.name,
      x: buildingX + 12,
      y: buildingY + 18,
      fontSize: Number(building.fontSize) || 14,
      fill: getFontColorForItem(building),
      fontWeight: Number(building.fontWeight) || 700,
      fontFamily: building.fontFamily || 'Arial, sans-serif',
      textAnchor: building.textAnchor || 'middle',
      fontStyle: building.fontStyle || 'normal',
      letterSpacing: Number(building.letterSpacing) || 0
    });

    buildingGroup.appendChild(rect);
    buildingGroup.appendChild(label);

    if (state.selectedBuildingId === building.id && state.mode !== 'connect' && !state.selectedLineId) {
      const handleNames = ['nw', 'n', 'ne', 'e', 'se', 's', 'sw', 'w'];
      const handleSize = 10;
      handleNames.forEach((anchor) => {
        const handle = document.createElementNS('http://www.w3.org/2000/svg', 'rect');
        const positions = {
          nw: { x: buildingX, y: buildingY },
          n: { x: buildingX + width / 2 - handleSize / 2, y: buildingY },
          ne: { x: buildingX + width - handleSize, y: buildingY },
          e: { x: buildingX + width - handleSize, y: buildingY + height / 2 - handleSize / 2 },
          se: { x: buildingX + width - handleSize, y: buildingY + height - handleSize },
          s: { x: buildingX + width / 2 - handleSize / 2, y: buildingY + height - handleSize },
          sw: { x: buildingX, y: buildingY + height - handleSize },
          w: { x: buildingX, y: buildingY + height / 2 - handleSize / 2 }
        };
        const pos = positions[anchor];
        handle.setAttribute('x', pos.x);
        handle.setAttribute('y', pos.y);
        handle.setAttribute('width', handleSize);
        handle.setAttribute('height', handleSize);
        handle.setAttribute('rx', 2);
        handle.setAttribute('fill', '#ffffff');
        handle.setAttribute('stroke', '#2663eb');
        handle.setAttribute('stroke-width', '2');
        handle.setAttribute('data-resize-handle', 'true');
        handle.setAttribute('data-resize-target-id', building.id);
        handle.setAttribute('data-resize-type', 'building');
        handle.setAttribute('data-resize-anchor', anchor);
        handle.style.cursor = {
          nw: 'nwse-resize',
          n: 'ns-resize',
          ne: 'nesw-resize',
          e: 'ew-resize',
          se: 'nwse-resize',
          s: 'ns-resize',
          sw: 'nesw-resize',
          w: 'ew-resize'
        }[anchor];
        handle.style.pointerEvents = 'all';
        buildingGroup.appendChild(handle);
      });
    }

    buildingLayer.appendChild(buildingGroup);
  });

  const existing = svg.querySelector('#buildingLayer');
  if (existing) existing.remove();
  svg.insertBefore(buildingLayer, edgeLayer);
}

function addFreeLine(start, end) {
  if (!start || !end) return;

  const line = {
    id: generateId('line'),
    x1: Number(start.x) || 0,
    y1: Number(start.y) || 0,
    x2: Number(end.x) || 0,
    y2: Number(end.y) || 0,
    rotation: 0,
    color: '#000000',
    width: 2,
    lineThickness: 2,
    strokeWidth: 2,
    lineCap: 'round',
    dashArray: [],
    buildingId: state.selectedBuildingId || null
  };

  snapLinePosition(line);

  if (!line.buildingId) {
    const matchingBuilding = findBuildingForLine(line);
    if (matchingBuilding) {
      line.buildingId = matchingBuilding.id;
    }
  }

  state.pendingLinePoint = null;
  state.pendingLineEnd = null;
  pushHistory();
  state.lines.push(line);
  render();
  setStatus('Free line added');
}

function renderEdges() {
  edgeLayer.innerHTML = '';

  const activeRouteNodeIds = Array.isArray(window.activeRouteNodeIds) ? window.activeRouteNodeIds : [];
  const routeIdSet = new Set(activeRouteNodeIds);

  if (state.mode === 'add-line' && state.pendingLinePoint) {
    const preview = document.createElementNS('http://www.w3.org/2000/svg', 'line');
    const previewEnd = state.pendingLineEnd || state.pendingLinePoint;
    preview.setAttribute('x1', state.pendingLinePoint.x);
    preview.setAttribute('y1', state.pendingLinePoint.y);
    preview.setAttribute('x2', previewEnd.x);
    preview.setAttribute('y2', previewEnd.y);
    preview.setAttribute('class', 'edge free-line selected');
    preview.setAttribute('stroke', '#000000');
    preview.setAttribute('stroke-width', '2');
    preview.setAttribute('stroke-linecap', 'round');
    preview.setAttribute('stroke-dasharray', 'none');
    preview.style.stroke = '#000000';
    preview.style.strokeDasharray = 'none';
    preview.style.strokeWidth = '2px';
    preview.style.pointerEvents = 'none';
    edgeLayer.appendChild(preview);
  }

  state.lines.forEach((line) => {
    const lineCap = String(line.lineCap || 'round');
    const lineWidth = normalizeLineWidth(line.width ?? line.lineThickness ?? line.strokeWidth ?? line.thickness, 2);
    line.color = '#000000';
    line.width = lineWidth;
    line.lineThickness = lineWidth;
    line.strokeWidth = lineWidth;

    const edge = document.createElementNS('http://www.w3.org/2000/svg', 'line');
    edge.setAttribute('x1', line.x1);
    edge.setAttribute('y1', line.y1);
    edge.setAttribute('x2', line.x2);
    edge.setAttribute('y2', line.y2);
    const centerX = (Number(line.x1) + Number(line.x2)) / 2;
    const centerY = (Number(line.y1) + Number(line.y2)) / 2;
    const rotation = Number(line.rotation) || 0;
    edge.setAttribute('transform', `rotate(${rotation} ${centerX} ${centerY})`);
    const isSelected = state.selectedLineId === line.id || state.selectedIds.includes(line.id);
    const isRouteLine = routeIdSet.has(line.id);
    edge.setAttribute('class', `edge free-line ${isSelected ? 'selected' : ''} ${isRouteLine ? 'route-path' : ''}`.trim());
    edge.setAttribute('data-line-id', line.id);
    edge.setAttribute('stroke', '#000000');
    edge.setAttribute('stroke-width', String(lineWidth));
    edge.style.strokeWidth = `${lineWidth}px`;
    edge.setAttribute('stroke-linecap', lineCap);
    edge.setAttribute('stroke-dasharray', isRouteLine ? '6 10' : 'none');
    // Inline styles beat the .edge CSS rule, so free lines are always solid black.
    edge.style.stroke = isRouteLine ? '#10b981' : '#000000';
    edge.style.strokeDasharray = isRouteLine ? '6 10' : 'none';
    edge.style.filter = isSelected ? 'drop-shadow(0 0 3px rgba(38,99,235,0.9))' : isRouteLine ? 'drop-shadow(0 0 7px rgba(16,185,129,0.9))' : 'none';
    edge.style.cursor = 'move';
    edge.style.pointerEvents = 'all';

    if (isSelected) {
      const handleRadius = 10;
      const startHandle = document.createElementNS('http://www.w3.org/2000/svg', 'circle');
      startHandle.setAttribute('cx', line.x1);
      startHandle.setAttribute('cy', line.y1);
      startHandle.setAttribute('r', String(handleRadius));
      startHandle.setAttribute('fill', '#ffffff');
      startHandle.setAttribute('stroke', '#2663eb');
      startHandle.setAttribute('stroke-width', '3');
      startHandle.setAttribute('data-resize-handle', 'true');
      startHandle.setAttribute('data-resize-target-id', line.id);
      startHandle.setAttribute('data-resize-type', 'line');
      startHandle.setAttribute('data-resize-anchor', 'start');
      startHandle.style.cursor = 'pointer';
      startHandle.style.pointerEvents = 'all';
      startHandle.style.filter = 'drop-shadow(0 0 3px rgba(38,99,235,0.35))';

      const endHandle = document.createElementNS('http://www.w3.org/2000/svg', 'circle');
      endHandle.setAttribute('cx', line.x2);
      endHandle.setAttribute('cy', line.y2);
      endHandle.setAttribute('r', String(handleRadius));
      endHandle.setAttribute('fill', '#ffffff');
      endHandle.setAttribute('stroke', '#2663eb');
      endHandle.setAttribute('stroke-width', '3');
      endHandle.setAttribute('data-resize-handle', 'true');
      endHandle.setAttribute('data-resize-target-id', line.id);
      endHandle.setAttribute('data-resize-type', 'line');
      endHandle.setAttribute('data-resize-anchor', 'end');
      endHandle.style.cursor = 'pointer';
      endHandle.style.pointerEvents = 'all';
      endHandle.style.filter = 'drop-shadow(0 0 3px rgba(38,99,235,0.35))';

      edgeLayer.appendChild(startHandle);
      edgeLayer.appendChild(endHandle);
    }

    edgeLayer.appendChild(edge);
  });

  if (state.hideJunctionsAndConnections) {
    return;
  }

  state.edges.forEach(([fromId, toId]) => {
    const fromNode = getNodeById(fromId);
    const toNode = getNodeById(toId);
    if (!fromNode || !toNode) return;

    const isSelected = state.selectedNodeId && (state.selectedNodeId === fromId || state.selectedNodeId === toId);
    const sameFloorVisible = nodeMatchesVisibleFloor(fromNode) && nodeMatchesVisibleFloor(toNode);
    if (!isSelected && !sameFloorVisible) {
      return;
    }

    const routeIds = Array.isArray(window.activeRouteNodeIds) ? window.activeRouteNodeIds : [];
    const routeIndexFrom = routeIds.indexOf(fromId);
    const routeIndexTo = routeIds.indexOf(toId);
    const isRouteEdge = routeIndexFrom !== -1 && routeIndexTo !== -1 && Math.abs(routeIndexFrom - routeIndexTo) === 1;

    const edge = document.createElementNS('http://www.w3.org/2000/svg', 'line');
    edge.setAttribute('x1', fromNode.x);
    edge.setAttribute('y1', fromNode.y);
    edge.setAttribute('x2', toNode.x);
    edge.setAttribute('y2', toNode.y);
    edge.setAttribute('class', `edge ${isRouteEdge ? 'route-path' : ''} ${isSelected ? 'selected' : ''}`.trim());
    if (isRouteEdge) {
      edge.style.stroke = '#10b981';
      edge.style.strokeDasharray = '6 10';
      edge.style.filter = 'drop-shadow(0 0 7px rgba(16, 185, 129, 0.9))';
    }
    edgeLayer.appendChild(edge);
  });
}

function getNodeZOrder(node) {
  if (!node || !Number.isFinite(Number(node.zIndex))) {
    return 0;
  }
  return Number(node.zIndex);
}

function moveSelectedNodesToFront() {
  const selectedNodes = state.selectedIds
    .map((id) => getNodeById(id))
    .filter(Boolean);

  if (!selectedNodes.length) return;

  const allNodes = Object.values(state.nodes).filter((node) => node && node.type !== 'building');
  const maxZ = Math.max(0, ...allNodes.map((node) => getNodeZOrder(node)));

  selectedNodes.forEach((node, index) => {
    node.zIndex = maxZ + index + 1;
  });

  render();
}

function moveSelectedNodesToBack() {
  const selectedNodes = state.selectedIds
    .map((id) => getNodeById(id))
    .filter(Boolean);

  if (!selectedNodes.length) return;

  const allNodes = Object.values(state.nodes).filter((node) => node && node.type !== 'building');
  const minZ = Math.min(0, ...allNodes.map((node) => getNodeZOrder(node)));

  selectedNodes.forEach((node, index) => {
    node.zIndex = minZ - (selectedNodes.length - index);
  });

  render();
}

function renderNodes() {
  nodeLayer.innerHTML = '';
  const orderedNodes = [...Object.values(state.nodes)].sort((a, b) => {
    const aZ = getNodeZOrder(a);
    const bZ = getNodeZOrder(b);
    if (aZ !== bZ) return aZ - bZ;

    const aIsStair = a && a.type === 'stair';
    const bIsStair = b && b.type === 'stair';
    if (aIsStair && !bIsStair) return -1;
    if (!aIsStair && bIsStair) return 1;
    return 0;
  });

  const totalSelectionCount = new Set([
    ...(Array.isArray(state.selectedIds) ? state.selectedIds : []),
    ...(state.selectedNodeId ? [state.selectedNodeId] : [])
  ]).size;

  orderedNodes.forEach((node) => {
    if (node.type === 'building') return;
    if (state.hideJunctionsAndConnections && node.type === 'junction') return;

    const floorMatches = nodeMatchesVisibleFloor(node);
    const isSelected = state.selectedNodeId === node.id || state.selectedIds.includes(node.id);
    if (!floorMatches && !isSelected) {
      return;
    }

    const group = document.createElementNS('http://www.w3.org/2000/svg', 'g');
    group.setAttribute('class', `node ${isSelected ? 'selected' : ''}`);
    group.dataset.id = node.id;
    group.dataset.multiSelect = String(isSelected && totalSelectionCount > 1);
    group.setAttribute('transform', `rotate(${Number(node.rotation) || 0} ${node.x} ${node.y})`);
    group.style.pointerEvents = 'all';

    const width = Number(node.width) || (node.type === 'junction' ? 5 : 52);
    const height = Number(node.height) || (node.type === 'junction' ? 5 : 44);
    const isPendingConnectTarget = state.mode === 'connect' && state.pendingConnectId === node.id;
    const isStair = node.type === 'stair';
    const isRouteNode = Array.isArray(window.activeRouteNodeIds) && window.activeRouteNodeIds.includes(node.id);

    const shape = document.createElementNS('http://www.w3.org/2000/svg', node.type === 'junction' ? 'circle' : 'rect');
    shape.setAttribute('class', `node-body ${isRouteNode ? 'route-node' : ''}`.trim());
    shape.setAttribute('fill', getNodeColor(node.type, node.floor, node.color));
    shape.setAttribute('data-id', node.id);
    shape.style.pointerEvents = 'all';
    if (isPendingConnectTarget) {
      shape.style.stroke = '#ef4444';
      shape.style.strokeWidth = '3';
      shape.style.filter = 'url(#softShadow)';
    } else if (node.type !== 'junction') {
      const hasBorder = !!node.borderEnabled && Number(node.borderWidth) > 0;
      const defaultStroke = hasBorder ? '#1f2937' : 'rgba(20, 52, 94, 0.75)';
      const defaultWidth = hasBorder ? String(Number(node.borderWidth) || 1) : '1.5';

      if (isSelected && totalSelectionCount > 1) {
        shape.setAttribute('stroke', '#000000');
        shape.setAttribute('stroke-width', '5');
        shape.style.setProperty('stroke', '#000000');
        shape.style.setProperty('stroke-width', '5');
        shape.style.setProperty('filter', 'none');
      } else {
        shape.style.stroke = defaultStroke;
        shape.style.strokeWidth = defaultWidth;
      }
    }

    if (node.type === 'junction') {
      shape.setAttribute('cx', node.x);
      shape.setAttribute('cy', node.y);
      shape.setAttribute('r', Math.max(12, Math.min(width, height) / 2));
    } else if (isStair) {
      const left = node.x - width / 2;
      const top = node.y - height / 2;
      const stairDesign = normalizeStairDesign(node.stairDesign);
      const patternId = `stairs-pattern-${stairDesign}`;

      const defs = document.querySelector('#mapCanvas defs');
      if (defs && !document.getElementById(patternId)) {
        const pattern = document.createElementNS('http://www.w3.org/2000/svg', 'pattern');
        pattern.setAttribute('id', patternId);
        pattern.setAttribute('patternUnits', 'userSpaceOnUse');
        pattern.setAttribute('width', stairDesign === 'vertical' ? '12' : '24');
        pattern.setAttribute('height', stairDesign === 'vertical' ? '24' : '12');

        const base = document.createElementNS('http://www.w3.org/2000/svg', 'rect');
        base.setAttribute('width', stairDesign === 'vertical' ? '12' : '24');
        base.setAttribute('height', stairDesign === 'vertical' ? '24' : '12');
        base.setAttribute('fill', '#c89b69');

        const stripe = document.createElementNS('http://www.w3.org/2000/svg', 'line');
        if (stairDesign === 'vertical') {
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
      }

      shape.setAttribute('x', left);
      shape.setAttribute('y', top);
      shape.setAttribute('width', width);
      shape.setAttribute('height', height);
      shape.setAttribute('rx', 0);
      shape.setAttribute('ry', 0);
      shape.setAttribute('fill', `url(#${patternId})`);
      shape.setAttribute('stroke', '#734b1d');
      shape.setAttribute('stroke-width', '2');
      shape.setAttribute('stroke-linejoin', 'miter');
      shape.style.filter = 'url(#softShadow)';
    } else {
      shape.setAttribute('x', node.x - width / 2);
      shape.setAttribute('y', node.y - height / 2);
      shape.setAttribute('width', width);
      shape.setAttribute('height', height);
      shape.setAttribute('rx', 0);
      shape.setAttribute('ry', 0);
    }

    const label = createMultilineSvgText({
      value: node.type === 'stair' ? 'STAIR' : (node.label || node.id),
      x: node.x,
      y: node.type === 'junction' ? node.y + 8 : node.y,
      fontSize: Number(node.fontSize) || 14,
      fill: getFontColorForItem(node),
      fontWeight: Number(node.fontWeight) || (node.type === 'junction' ? 600 : 500),
      fontFamily: node.fontFamily || 'Arial, sans-serif',
      textAnchor: node.textAnchor || 'middle',
      fontStyle: node.fontStyle || 'normal',
      letterSpacing: Number(node.letterSpacing) || 0
    });

    if (node.hideDisplay && node.type !== 'junction') {
      // Hidden on the public map, but still active: shown faded + dashed here so it can be found and edited.
      group.style.opacity = '0.35';
      shape.style.strokeDasharray = '6 4';
      group.setAttribute('data-hidden-display', 'true');
    }

    group.appendChild(shape);
    group.appendChild(label);

    if (node.floorConnector) {
      // Floor connector badge: an up triangle and a down triangle in the node's top-right corner.
      const isJunctionNode = node.type === 'junction';
      const badgeSize = isJunctionNode ? 18 : clamp(Math.min(width, height) * 0.45, 14, 24);
      // Junctions are drawn as a circle (radius 12+), so the badge sits just outside its top-right.
      const junctionRadius = Math.max(12, Math.min(width, height) / 2);
      const badgeX = isJunctionNode ? node.x + junctionRadius * 0.55 : node.x + width / 2 - badgeSize - 2;
      const badgeY = isJunctionNode ? node.y - junctionRadius * 0.55 - badgeSize : node.y - height / 2 + 2;
      const half = badgeSize / 2;
      const pad = badgeSize * 0.18;
      const badge = document.createElementNS('http://www.w3.org/2000/svg', 'g');
      badge.setAttribute('class', 'floor-connector-badge');
      badge.style.pointerEvents = 'none';

      const badgeBg = document.createElementNS('http://www.w3.org/2000/svg', 'rect');
      badgeBg.setAttribute('x', badgeX);
      badgeBg.setAttribute('y', badgeY);
      badgeBg.setAttribute('width', badgeSize);
      badgeBg.setAttribute('height', badgeSize);
      badgeBg.setAttribute('rx', 4);
      badgeBg.setAttribute('fill', '#ffffff');
      badgeBg.setAttribute('stroke', '#475569');
      badgeBg.setAttribute('stroke-width', '1');

      const upTriangle = document.createElementNS('http://www.w3.org/2000/svg', 'polygon');
      upTriangle.setAttribute('points', [
        `${badgeX + half},${badgeY + pad}`,
        `${badgeX + badgeSize - pad},${badgeY + half - 1}`,
        `${badgeX + pad},${badgeY + half - 1}`
      ].join(' '));
      upTriangle.setAttribute('fill', '#16a34a');

      const downTriangle = document.createElementNS('http://www.w3.org/2000/svg', 'polygon');
      downTriangle.setAttribute('points', [
        `${badgeX + pad},${badgeY + half + 1}`,
        `${badgeX + badgeSize - pad},${badgeY + half + 1}`,
        `${badgeX + half},${badgeY + badgeSize - pad}`
      ].join(' '));
      downTriangle.setAttribute('fill', '#dc2626');

      badge.appendChild(badgeBg);
      badge.appendChild(upTriangle);
      badge.appendChild(downTriangle);
      group.appendChild(badge);
    }

    if (state.selectedNodeId === node.id && state.mode !== 'connect' && node.type !== 'junction') {
      const handleNames = ['nw', 'n', 'ne', 'e', 'se', 's', 'sw', 'w'];
      const handleSize = 10;
      handleNames.forEach((anchor) => {
        const handle = document.createElementNS('http://www.w3.org/2000/svg', 'rect');
        const positions = {
          nw: { x: node.x - width / 2, y: node.y - height / 2 },
          n: { x: node.x - handleSize / 2, y: node.y - height / 2 },
          ne: { x: node.x + width / 2 - handleSize, y: node.y - height / 2 },
          e: { x: node.x + width / 2 - handleSize, y: node.y - handleSize / 2 },
          se: { x: node.x + width / 2 - handleSize, y: node.y + height / 2 - handleSize },
          s: { x: node.x - handleSize / 2, y: node.y + height / 2 - handleSize },
          sw: { x: node.x - width / 2, y: node.y + height / 2 - handleSize },
          w: { x: node.x - width / 2, y: node.y - handleSize / 2 }
        };
        const pos = positions[anchor];
        handle.setAttribute('x', pos.x);
        handle.setAttribute('y', pos.y);
        handle.setAttribute('width', handleSize);
        handle.setAttribute('height', handleSize);
        handle.setAttribute('rx', 2);
        handle.setAttribute('fill', '#ffffff');
        handle.setAttribute('stroke', '#2663eb');
        handle.setAttribute('stroke-width', '2');
        handle.setAttribute('data-resize-handle', 'true');
        handle.setAttribute('data-resize-target-id', node.id);
        handle.setAttribute('data-resize-type', 'node');
        handle.setAttribute('data-resize-anchor', anchor);
        handle.style.cursor = {
          nw: 'nwse-resize',
          n: 'ns-resize',
          ne: 'nesw-resize',
          e: 'ew-resize',
          se: 'nwse-resize',
          s: 'ns-resize',
          sw: 'nesw-resize',
          w: 'ew-resize'
        }[anchor];
        handle.style.pointerEvents = 'all';
        group.appendChild(handle);
      });
    }

    nodeLayer.appendChild(group);
  });
}

function refreshBuildingSelects() {
  const options = state.buildings.map((building) => `<option value="${building.id}">${building.name}</option>`).join('');
  const selectedNodeIds = [...new Set([
    ...(state.selectedNodeId ? [state.selectedNodeId] : []),
    ...(Array.isArray(state.selectedIds) ? state.selectedIds : [])
  ])].filter((id) => !!getNodeById(id));
  const selectedNodes = selectedNodeIds.map((id) => getNodeById(id)).filter(Boolean);
  const allowNoBuilding = selectedNodes.length > 0 && selectedNodes.every((node) => node.type === 'junction');

  if (buildingSelect) {
    buildingSelect.innerHTML = options || '<option value="">No buildings yet</option>';
  }
  nodeBuildingSelect.innerHTML = `${allowNoBuilding ? '<option value="">No building</option>' : ''}${options}`;

  if (!state.buildings.length) {
    if (buildingSelect) buildingSelect.disabled = true;
    if (nodeBuildingSelect) nodeBuildingSelect.disabled = true;
    if (buildingNameInput) buildingNameInput.value = '';
    return;
  }

  if (buildingSelect) buildingSelect.disabled = false;
  if (nodeBuildingSelect) nodeBuildingSelect.disabled = false;

  // Do not auto-select a building if a line is currently selected
  if (!state.selectedBuildingId && !state.selectedNodeId && !state.selectedLineId) {
    state.selectedBuildingId = state.buildings[0].id;
  }

  if (state.selectedBuildingId && buildingSelect) {
    buildingSelect.value = state.selectedBuildingId;
    const selectedBuilding = getBuildingById(state.selectedBuildingId);
    if (selectedBuilding && buildingNameInput) {
      buildingNameInput.value = selectedBuilding.name;
    }
  }

  const selected = getNodeById(state.selectedNodeId) || (selectedNodes[0] || null);
  if (selected && selected.buildingId) {
    nodeBuildingSelect.value = selected.buildingId;
  } else if (allowNoBuilding) {
    nodeBuildingSelect.value = '';
  } else if (state.buildings.length) {
    nodeBuildingSelect.value = state.buildings[0].id;
  } else {
    nodeBuildingSelect.value = '';
  }
}

function toggleBuildingsPanel(forceState) {
  if (!buildingsPanel || !buildingsCollapseBtn) return;

  const isCollapsed = typeof forceState === 'boolean' ? forceState : buildingsPanel.classList.contains('collapsed');
  const nextCollapsed = !isCollapsed;
  buildingsPanel.classList.toggle('collapsed', nextCollapsed);
  buildingsPanel.dataset.collapsed = String(nextCollapsed);

  const body = buildingsPanel.querySelector('.tool-group-body');
  if (body) {
    body.style.display = nextCollapsed ? 'none' : 'block';
  }

  buildingsCollapseBtn.setAttribute('aria-expanded', String(!nextCollapsed));
  buildingsCollapseBtn.setAttribute('aria-label', nextCollapsed ? 'Expand buildings panel' : 'Collapse buildings panel');
  buildingsCollapseBtn.title = nextCollapsed ? 'Expand buildings panel' : 'Collapse buildings panel';

  const icon = buildingsCollapseBtn.querySelector('i');
  if (icon) {
    icon.classList.toggle('fa-chevron-up', !nextCollapsed);
    icon.classList.toggle('fa-chevron-down', nextCollapsed);
  }
}

function renderBuildingList() {
  buildingList.innerHTML = '';
  if (!state.buildings.length) {
    buildingList.innerHTML = '<li class="building-item">No buildings defined yet</li>';
    return;
  }

  state.buildings.forEach((building) => {
    const item = document.createElement('li');
    item.className = 'building-item';
    item.dataset.buildItem = building.id;

    const visibleFloor = getBuildingVisibleFloor(building.id);
    const floorRange = getFloorRangeForBuilding(building);
    item.innerHTML = `
      <span class="building-chip">
        <input
          type="color"
          value="${building.color || '#4f9cff'}"
          data-build-color="${building.id}"
          aria-label="Change color for ${building.name}"
          title="Change color for ${building.name}"
          style="width:28px; height:24px; padding:0; border:1px solid #7a7f87; border-radius:4px; background:transparent; cursor:pointer; vertical-align:middle;"
        />
        <input
          type="text"
          value="${building.name}"
          data-build-name="${building.id}"
          aria-label="Edit building name"
          title="Edit building name"
          style="flex:1; min-width:0; border:none; background:transparent; font-weight:700; color:var(--text); padding:2px 4px;"
        />
      </span>
      <div class="building-floor-toggle-row" style="display:flex; gap:4px; margin-top:6px; flex-wrap:wrap;">
        ${floorRange.map((floorNumber) => `
          <button
            type="button"
            class="floor-view-btn ${visibleFloor === floorNumber ? 'active' : ''}"
            data-building-floor="${building.id}"
            data-building-floor-value="${floorNumber}"
            style="padding:3px 7px; border-radius:999px; font-size:11px; border:1px solid #7a7f87; background:${visibleFloor === floorNumber ? '#2d6cdf' : '#f3f5f8'}; color:${visibleFloor === floorNumber ? '#fff' : '#1f2937'}; cursor:pointer;"
          >${floorNumber}F</button>
        `).join('')}
      </div>
      <div>
        <button type="button" data-build-delete="${building.id}" class="danger building-delete-btn" aria-label="Delete ${building.name}" title="Delete building"><i class="fa-solid fa-trash"></i></button>
      </div>
    `;

    item.querySelectorAll('[data-building-floor]').forEach((button) => {
      button.addEventListener('click', () => {
        const targetBuildingId = button.getAttribute('data-building-floor');
        const nextFloor = normalizeFloorValue(button.getAttribute('data-building-floor-value'), 1);
        state.buildingFloorState[targetBuildingId] = nextFloor;
        state.activeFloor = nextFloor;
        render();
      });
    });

    buildingList.appendChild(item);
  });

  applyBuildingGroupVisibility();
}

function applyCanvasTransform() {
  svg.style.transform = `translate(${state.panX}px, ${state.panY}px) scale(${state.zoom})`;
  svg.style.transformOrigin = '0 0';
  svg.style.cursor = panState ? 'grabbing' : 'default';
}

function render() {
  updateJunctionVisibilityButtonState();
  refreshFloorSelectOptions();
  if (globalFloorSelect && Number(globalFloorSelect.value) !== state.activeFloor) {
    globalFloorSelect.value = String(state.activeFloor);
  }
  renderAxisGuide();
  renderBuildings();
  renderEdges();
  renderNodes();
  refreshBuildingSelects();
  renderBuildingList();
  syncProperties();
  updateOutputBox();
  updateZoomLabel();
  applyCanvasTransform();
}

function updateOutputBox() {
  const jsonText = JSON.stringify(getLayoutExportObject(), null, 2);
  if (outputBox) {
    outputBox.value = jsonText;
  }
  return jsonText;
}

function syncProperties() {
  const selectedNodeIds = [...new Set([...(state.selectedIds || []), ...(state.selectedNodeId ? [state.selectedNodeId] : [])])]
    .filter((id) => !!getNodeById(id))
    .map((id) => getNodeById(id));
  const selected = selectedNodeIds.length ? selectedNodeIds[0] : getNodeById(state.selectedNodeId);
  const selectedBuilding = state.selectedBuildingId ? getBuildingById(state.selectedBuildingId) : null;
  const selectedLine = state.selectedLineId ? getLineById(state.selectedLineId) : null;
  const activePropertyTarget = selectedBuilding || selected;
  const hasSelection = !!activePropertyTarget || !!selectedLine;
  let activeField = document.activeElement;

  // When the selection changes, throw away anything typed but not applied, so it can
  // never be shown for (or applied to) the newly selected item.
  const selectionKey = [state.selectedBuildingId, state.selectedNodeId, state.selectedLineId, (state.selectedIds || []).join(',')].join('|');
  if (selectionKey !== syncProperties.lastSelectionKey) {
    syncProperties.lastSelectionKey = selectionKey;
    if (activeField && activeField !== document.body && activeField.closest && activeField.closest('.property-panel')) {
      activeField.blur();
      activeField = document.body;
    }
  }

  const batchPropertyPopup = document.getElementById('batchPropertyPopup');
  if (batchPropertyPopup && batchPropertyPopup.dataset.explicitOpen === 'true') {
    return;
  }

  const updateField = (input, nextValue) => {
    if (!input || activeField === input) return;
    input.value = String(nextValue);
  };

  if (floorInput) {
    const hasFloorSelection = selectedNodeIds.length > 0;
    updateFloorInputOptionsForSelection(selectedNodeIds);
    floorInput.disabled = !hasFloorSelection;
    if (hasFloorSelection && activeField !== floorInput) {
      const floorLimit = Math.min(...selectedNodeIds.map((node) => getNodeFloorLimit(node)));
      floorInput.value = String(clamp(normalizeFloorValue(selected ? selected.floor : 1, 1), 1, floorLimit));
    }
    if (!hasFloorSelection && activeField !== floorInput) {
      floorInput.value = '1';
    }
  }

  if (buildingFloorCountInput) {
    buildingFloorCountInput.disabled = !selectedBuilding;
    if (selectedBuilding && activeField !== buildingFloorCountInput) {
      buildingFloorCountInput.value = String(normalizeFloorCount(selectedBuilding.floors, 1));
    }
    if (!selectedBuilding && activeField !== buildingFloorCountInput) {
      buildingFloorCountInput.value = '1';
    }
  }

  if (globalFloorSelect && activeField !== globalFloorSelect) {
    globalFloorSelect.value = String(state.activeFloor || 1);
  }

  if (stairDesignInput) stairDesignInput.disabled = !(selected && selected.type === 'stair');

  if (lineThicknessInput) {
    const lineSelected = !!selectedLine;
    lineThicknessInput.disabled = !lineSelected;
    if (!lineSelected && activeField !== lineThicknessInput) {
      lineThicknessInput.value = '2';
    }
  }

  if (!hasSelection) {
    if (labelInput && activeField !== labelInput) labelInput.value = '';
    if (groupInput && activeField !== groupInput) groupInput.value = '';
    if (groupInput) groupInput.disabled = true;
    if (tagsInput && activeField !== tagsInput) tagsInput.value = '';
    if (tagsInput) tagsInput.disabled = true;
    if (labelSizeInput && activeField !== labelSizeInput) labelSizeInput.value = '14';
    if (typeColorInput && activeField !== typeColorInput) typeColorInput.value = '#7bb7ff';
    if (fontColorInput && activeField !== fontColorInput) fontColorInput.value = '#000000';
    if (nodeBuildingSelect && activeField !== nodeBuildingSelect) nodeBuildingSelect.value = '';
    if (nodeBuildingSelect) nodeBuildingSelect.disabled = true;
    if (buildingWidthInput && activeField !== buildingWidthInput) buildingWidthInput.value = '220';
    if (buildingHeightInput && activeField !== buildingHeightInput) buildingHeightInput.value = '180';
    if (junctionCoordinateGroup) junctionCoordinateGroup.style.display = 'none';
    if (junctionXInput && activeField !== junctionXInput) junctionXInput.value = '0';
    if (junctionYInput && activeField !== junctionYInput) junctionYInput.value = '0';
    if (stairDesignInput && activeField !== stairDesignInput) stairDesignInput.value = 'horizontal';
    if (lineThicknessInput && activeField !== lineThicknessInput) lineThicknessInput.value = '2';
    if (lineThicknessInput) lineThicknessInput.disabled = true;
    if (buildingBorderToggle) buildingBorderToggle.checked = false;
    if (multiJunctionToggle) multiJunctionToggle.checked = false;
    if (multiJunctionToggle) multiJunctionToggle.disabled = true;
    if (disableDestinationToggle) disableDestinationToggle.checked = false;
    if (disableDestinationToggle) disableDestinationToggle.disabled = true;
    if (hideDisplayToggle) hideDisplayToggle.checked = false;
    if (hideDisplayToggle) hideDisplayToggle.disabled = true;
    if (floorConnectorToggle) floorConnectorToggle.checked = false;
    if (floorConnectorToggle) floorConnectorToggle.disabled = true;
    if (buildingBorderWidthInput && activeField !== buildingBorderWidthInput) buildingBorderWidthInput.value = '2';
    if (selectionLabel) selectionLabel.textContent = 'No selection';
    return;
  }

  if (selectedLine) {
    if (labelInput && activeField !== labelInput) labelInput.value = '';
    if (groupInput && activeField !== groupInput) groupInput.value = '';
    if (groupInput) groupInput.disabled = true;
    if (tagsInput && activeField !== tagsInput) tagsInput.value = '';
    if (tagsInput) tagsInput.disabled = true;
    if (labelSizeInput && activeField !== labelSizeInput) labelSizeInput.value = '14';
    if (typeColorInput && activeField !== typeColorInput) typeColorInput.value = '#7bb7ff';
    if (fontColorInput && activeField !== fontColorInput) fontColorInput.value = '#000000';
    if (nodeBuildingSelect && activeField !== nodeBuildingSelect) nodeBuildingSelect.value = '';
    if (nodeBuildingSelect) nodeBuildingSelect.disabled = true;
    if (buildingWidthInput && activeField !== buildingWidthInput) {
      buildingWidthInput.value = String(Math.round(Math.hypot(Number(selectedLine.x2) - Number(selectedLine.x1), Number(selectedLine.y2) - Number(selectedLine.y1))));
    }
    if (buildingHeightInput && activeField !== buildingHeightInput) buildingHeightInput.value = '180';
    const linePosition = getItemPositionForPanel(null, null, selectedLine);
    if (junctionCoordinateGroup) junctionCoordinateGroup.style.display = 'block';
    if (junctionXInput && activeField !== junctionXInput) junctionXInput.value = formatPositionValue(linePosition.x);
    if (junctionYInput && activeField !== junctionYInput) junctionYInput.value = formatPositionValue(linePosition.y);
    if (stairDesignInput && activeField !== stairDesignInput) stairDesignInput.value = 'horizontal';
    if (lineThicknessInput && activeField !== lineThicknessInput) lineThicknessInput.value = String(Number(selectedLine.width) || 2);
    if (lineThicknessInput) lineThicknessInput.disabled = false;
    if (buildingBorderToggle) buildingBorderToggle.checked = false;
    if (multiJunctionToggle) multiJunctionToggle.checked = false;
    if (multiJunctionToggle) multiJunctionToggle.disabled = true;
    if (disableDestinationToggle) disableDestinationToggle.checked = false;
    if (disableDestinationToggle) disableDestinationToggle.disabled = true;
    if (hideDisplayToggle) hideDisplayToggle.checked = false;
    if (hideDisplayToggle) hideDisplayToggle.disabled = true;
    if (floorConnectorToggle) floorConnectorToggle.checked = false;
    if (floorConnectorToggle) floorConnectorToggle.disabled = true;
    if (buildingBorderWidthInput && activeField !== buildingBorderWidthInput) buildingBorderWidthInput.value = '2';
    if (selectionLabel) selectionLabel.textContent = 'Line selected';
    return;
  }

  const buildingContext = selectedBuilding || (selected && selected.buildingId ? getBuildingById(selected.buildingId) : null);
  if (nodeBuildingSelect) nodeBuildingSelect.disabled = !!selectedBuilding;
  const defaultBuildingSize = getDefaultBuildingSize();
  const selectedNodeSize = selected ? {
    width: Number(selected.width) || getDefaultNodeSize(selected.type, selected.buildingId).width,
    height: Number(selected.height) || getDefaultNodeSize(selected.type, selected.buildingId).height
  } : null;

  if (labelInput && activeField !== labelInput) {
    labelInput.value = selected ? (selected.label || '') : (selectedBuilding ? selectedBuilding.name : '');
  }
  if (groupInput && activeField !== groupInput) {
    groupInput.value = selected && isRoomLikeNode(selected) ? (selected.group || '') : '';
  }
  if (groupInput) {
    groupInput.disabled = !selected || !isRoomLikeNode(selected);
  }
  if (tagsInput && activeField !== tagsInput) {
    tagsInput.value = selected && isRoomLikeNode(selected) ? normalizeTags(selected.tags).join(', ') : '';
  }
  if (tagsInput) {
    tagsInput.disabled = !selected || !isRoomLikeNode(selected);
  }
  if (disableDestinationToggle) {
    disableDestinationToggle.checked = !!(selected && isRoomLikeNode(selected) ? selected.disableDestination : false);
  }
  if (disableDestinationToggle) {
    disableDestinationToggle.disabled = !selected || !isRoomLikeNode(selected);
  }
  const canHideDisplay = !!selected && !selectedBuilding && selected.type !== 'junction';
  if (hideDisplayToggle) {
    hideDisplayToggle.checked = canHideDisplay ? !!selected.hideDisplay : false;
  }
  if (hideDisplayToggle) {
    hideDisplayToggle.disabled = !canHideDisplay;
  }
  const canSetFloorConnector = !!selected && !selectedBuilding && canBeFloorConnector(selected);
  if (floorConnectorToggle) {
    floorConnectorToggle.checked = canSetFloorConnector ? !!selected.floorConnector : false;
  }
  if (floorConnectorToggle) {
    floorConnectorToggle.disabled = !canSetFloorConnector;
  }

  const itemPosition = getItemPositionForPanel(selected, selectedBuilding, null);
  if (junctionCoordinateGroup) {
    junctionCoordinateGroup.style.display = itemPosition ? 'block' : 'none';
  }
  if (junctionXInput && activeField !== junctionXInput) {
    junctionXInput.value = itemPosition ? formatPositionValue(itemPosition.x) : '0';
  }
  if (junctionYInput && activeField !== junctionYInput) {
    junctionYInput.value = itemPosition ? formatPositionValue(itemPosition.y) : '0';
  }

  if (selected) {
    updateField(labelSizeInput, String(Number(selected.fontSize) || 14));
  } else if (selectedBuilding) {
    updateField(labelSizeInput, String(Number(selectedBuilding.fontSize) || 14));
  } else {
    updateField(labelSizeInput, '14');
  }

  if (nodeBuildingSelect && activeField !== nodeBuildingSelect) {
    nodeBuildingSelect.value = selected ? (selected.buildingId || '') : (selectedBuilding ? selectedBuilding.id : '');
  }
  if (selectedBuilding) {
    if (nodeBuildingSelect && activeField !== nodeBuildingSelect) nodeBuildingSelect.value = selectedBuilding.id;
  }
  if (typeColorInput && activeField !== typeColorInput) {
    typeColorInput.value = selectedBuilding
      ? (selectedBuilding.color || '#4f9cff')
      : (selected && selected.color) || (buildingContext ? (buildingContext.color || '#4f9cff') : '#4f9cff');
  }
  if (fontColorInput && activeField !== fontColorInput) {
    const fontColor = selectedBuilding
      ? normalizeFontColor(selectedBuilding.fontColor, selectedBuilding.color || '#4f9cff')
      : (selected ? normalizeFontColor(selected.fontColor, selected.color || getTypeColor(selected.type)) : '#000000');
    fontColorInput.value = fontColor;
  }

  const targetWidth = selectedBuilding
    ? (Number(selectedBuilding.width) || defaultBuildingSize.width)
    : (selected ? (Number(selected.width) || getDefaultNodeSize(selected.type, selected.buildingId).width) : defaultBuildingSize.width);
  const targetHeight = selectedBuilding
    ? (Number(selectedBuilding.height) || defaultBuildingSize.height)
    : (selected ? (Number(selected.height) || getDefaultNodeSize(selected.type, selected.buildingId).height) : defaultBuildingSize.height);

  updateField(buildingWidthInput, String(targetWidth));
  updateField(buildingHeightInput, String(targetHeight));
  if (stairDesignInput && selected && selected.type === 'stair' && activeField !== stairDesignInput) {
    stairDesignInput.value = normalizeStairDesign(selected.stairDesign);
  } else if (stairDesignInput && activeField !== stairDesignInput) {
    stairDesignInput.value = 'horizontal';
  }
  if (lineThicknessInput && activeField !== lineThicknessInput) {
    lineThicknessInput.value = String(selected ? Number(selected.width) || 2 : (selectedBuilding ? Number(selectedBuilding.width) || 2 : 2));
  }
  if (lineThicknessInput) lineThicknessInput.disabled = true;

  const activeBorderTarget = activePropertyTarget || buildingContext;
  if (buildingBorderToggle) {
    buildingBorderToggle.checked = !!(activeBorderTarget ? activeBorderTarget.borderEnabled : false);
  }
  if (multiJunctionToggle) {
    const roomSelected = !!selected && isRoomLikeNode(selected);
    multiJunctionToggle.checked = roomSelected ? !!selected.allowMultipleJunctions : false;
    multiJunctionToggle.disabled = !roomSelected;
  }
  updateField(buildingBorderWidthInput, String(activeBorderTarget ? Number(activeBorderTarget.borderWidth) || 2 : 2));

  if (selectionLabel) {
    selectionLabel.textContent = selectedBuilding ? `${selectedBuilding.name} building` : (selected ? (selected.label || 'Node') : 'No selection');
  }
}

const batchPropertyDefinitions = [
  { key: 'color', label: 'Color', type: 'color', applies: (item) => !!item && Object.prototype.hasOwnProperty.call(item, 'color') },
  { key: 'fontColor', label: 'Font color', type: 'color', applies: (item) => !!item && (Object.prototype.hasOwnProperty.call(item, 'fontColor') || Object.prototype.hasOwnProperty.call(item, 'label') || Object.prototype.hasOwnProperty.call(item, 'name') || Object.prototype.hasOwnProperty.call(item, 'buildingId') || Object.prototype.hasOwnProperty.call(item, 'type')) && !getLineById(item.id) },
  { key: 'fontSize', label: 'Text size', type: 'number', min: 8, max: 48, applies: (item) => !!item && Object.prototype.hasOwnProperty.call(item, 'fontSize') && Number.isFinite(Number(item.fontSize)) },
  { key: 'buildingId', label: 'Building', type: 'select', options: [], applies: (item) => !!item && Object.prototype.hasOwnProperty.call(item, 'buildingId') && !getLineById(item.id) },
  { key: 'floor', label: 'Floor', type: 'number', min: 1, max: 20, applies: (item) => !!item && Object.prototype.hasOwnProperty.call(item, 'floor') && Number.isFinite(Number(item.floor)) },
  { key: 'floors', label: 'Floors', type: 'number', min: 1, max: 20, applies: (item) => !!item && Object.prototype.hasOwnProperty.call(item, 'floors') && Number.isFinite(Number(item.floors)) },
  { key: 'width', label: 'Width', type: 'number', min: 1, max: 2000, applies: (item) => !!item && Object.prototype.hasOwnProperty.call(item, 'width') && Number.isFinite(Number(item.width)) },
  { key: 'height', label: 'Height', type: 'number', min: 1, max: 2000, applies: (item) => !!item && Object.prototype.hasOwnProperty.call(item, 'height') && Number.isFinite(Number(item.height)) },
  { key: 'label', label: 'Label', type: 'text', applies: (item) => !!item && Object.prototype.hasOwnProperty.call(item, 'label') },
  { key: 'group', label: 'Group', type: 'text', applies: (item) => !!item && isRoomLikeNode(item) },
  { key: 'borderEnabled', label: 'Border enabled', type: 'boolean', applies: (item) => !!item && Object.prototype.hasOwnProperty.call(item, 'borderEnabled') },
  { key: 'borderWidth', label: 'Border width', type: 'number', min: 0, max: 12, applies: (item) => !!item && Object.prototype.hasOwnProperty.call(item, 'borderWidth') },
  { key: 'disableDestination', label: 'Disable destination', type: 'boolean', applies: (item) => !!item && isRoomLikeNode(item) },
  { key: 'allowMultipleJunctions', label: 'Multi junction connection', type: 'boolean', applies: (item) => !!item && isRoomLikeNode(item) },
  { key: 'floorConnector', label: 'Floor connector', type: 'boolean', applies: (item) => !!item && canBeFloorConnector(item) && !getLineById(item.id) },
  { key: 'stairDesign', label: 'Stair design', type: 'select', options: ['horizontal', 'vertical'], applies: (item) => !!item && item.type === 'stair' },
  { key: 'lineThickness', label: 'Line width', type: 'number', min: 1, max: 50, applies: (item) => !!item && Object.prototype.hasOwnProperty.call(item, 'lineThickness') }
];

function getBatchSelectionItems() {
  const ids = Array.isArray(state.selectedIds) ? state.selectedIds.slice() : [];
  const items = ids
    .map((id) => getNodeById(id) || getBuildingById(id) || getLineById(id))
    .filter(Boolean);
  return [...new Map(items.map((item) => [item.id || item.nodeId || item.name || JSON.stringify(item), item])).values()];
}

function getAllowedBatchProperties(items) {
  if (!items || !items.length) return [];
  return batchPropertyDefinitions.filter((definition) => items.every((item) => definition.applies(item)));
}

function createBatchPropertyPopup() {
  let popup = document.getElementById('batchPropertyPopup');
  if (!popup) {
    popup = document.createElement('div');
    popup.id = 'batchPropertyPopup';
    popup.className = 'batch-property-popup hidden';
    popup.setAttribute('role', 'dialog');
    popup.setAttribute('aria-live', 'polite');
    popup.style.position = 'fixed';
    popup.style.zIndex = '2000';
    popup.style.background = '#fff';
    popup.style.border = '1px solid #cbd5e1';
    popup.style.borderRadius = '12px';
    popup.style.boxShadow = '0 18px 40px rgba(15, 23, 42, 0.18)';
    popup.style.padding = '12px';
    popup.style.minWidth = '260px';
    popup.style.display = 'none';
    popup.style.fontFamily = 'Arial, sans-serif';
    popup.style.color = '#1f2937';
    document.body.appendChild(popup);
  }

  if (!popup.querySelector('.batch-property-form')) {
    popup.innerHTML = `
      <div style="font-size:12px; letter-spacing:0.08em; text-transform:uppercase; color:#5f7295; font-weight:700; margin-bottom:8px;">Change:</div>
      <div class="batch-property-form" style="display:flex; flex-direction:column; gap:8px;">
        <select id="batchPropertySelect" style="padding:8px 10px; border:1px solid #dbe2f0; border-radius:8px; font:inherit;"></select>
        <div id="batchPropertyInputWrap" style="display:flex; flex-direction:column; gap:6px;"></div>
        <div style="display:flex; justify-content:flex-end; gap:8px; margin-top:4px;">
          <button type="button" id="batchPropertyCancelBtn" class="batch-cancel-btn" style="padding:8px 10px; border:1px solid #dbe2f0; border-radius:8px; background:#f8fafc; cursor:pointer;">Cancel</button>
          <button type="button" id="batchPropertySaveBtn" class="batch-save-btn" style="padding:8px 10px; border:none; border-radius:8px; background:#2663eb; color:#fff; cursor:pointer; font-weight:700;">Save</button>
        </div>
      </div>
    `;

    popup.querySelector('#batchPropertyCancelBtn').addEventListener('click', () => hideBatchPropertyPopup());
    popup.querySelector('#batchPropertySaveBtn').addEventListener('click', handleBatchPropertySave);
    popup.querySelector('#batchPropertySelect').addEventListener('change', () => updateBatchPropertyInputFromSelection());
  }

  return popup;
}

function showBatchPropertyPopup(event, items) {
  const popup = createBatchPropertyPopup();
  const propertySelect = popup.querySelector('#batchPropertySelect');
  const allowed = getAllowedBatchProperties(items);

  if (!allowed.length) {
    setStatus('No shared property is compatible with the current selection');
    return;
  }

  propertySelect.innerHTML = allowed.map((definition) => `<option value="${definition.key}">${definition.label}</option>`).join('');
  popup.dataset.explicitOpen = 'true';
  popup.style.display = 'block';
  popup.style.left = `${Math.min(window.innerWidth - 300, event.clientX + 14)}px`;
  popup.style.top = `${Math.min(window.innerHeight - 220, event.clientY + 14)}px`;
  updateBatchPropertyInputFromSelection();
}

function hideBatchPropertyPopup() {
  const popup = document.getElementById('batchPropertyPopup');
  if (!popup) return;
  popup.dataset.explicitOpen = 'false';
  popup.style.display = 'none';
}

function updateBatchPropertyInputFromSelection() {
  const popup = document.getElementById('batchPropertyPopup');
  if (!popup) return;

  const propertySelect = popup.querySelector('#batchPropertySelect');
  const inputWrap = popup.querySelector('#batchPropertyInputWrap');
  const items = getBatchSelectionItems();
  const allowed = getAllowedBatchProperties(items);
  const selectedDefinition = allowed.find((definition) => definition.key === propertySelect.value) || allowed[0];
  if (!selectedDefinition) return;

  const buildingOptions = selectedDefinition.key === 'buildingId'
    ? [{ value: '', label: 'No building' }, ...state.buildings.map((building) => ({ value: building.id, label: building.name }))]
    : (selectedDefinition.options || []).map((option) => ({ value: option, label: option }));

  const baseValue = items.find((item) => {
    const value = selectedDefinition.key === 'color' ? (item.color ?? '#7bb7ff') :
      selectedDefinition.key === 'label' ? (item.label || item.name || '') :
      selectedDefinition.key === 'group' ? (item.group || '') :
      selectedDefinition.key === 'buildingId' ? (item.buildingId ?? '') :
      selectedDefinition.key === 'floor' ? item.floor :
      selectedDefinition.key === 'floors' ? item.floors :
      selectedDefinition.key === 'width' ? item.width :
      selectedDefinition.key === 'height' ? item.height :
      selectedDefinition.key === 'rotation' ? item.rotation :
      selectedDefinition.key === 'borderEnabled' ? item.borderEnabled :
      selectedDefinition.key === 'borderWidth' ? item.borderWidth :
      selectedDefinition.key === 'disableDestination' ? item.disableDestination :
      selectedDefinition.key === 'allowMultipleJunctions' ? item.allowMultipleJunctions :
      selectedDefinition.key === 'stairDesign' ? item.stairDesign :
      selectedDefinition.key === 'lineThickness' ? item.lineThickness ?? item.width :
      item[selectedDefinition.key];
    return value !== undefined && value !== null;
  });

  const value = baseValue ? (
    selectedDefinition.key === 'color' ? (baseValue.color ?? '#7bb7ff') :
    selectedDefinition.key === 'fontColor' ? (baseValue.fontColor ?? '#000000') :
    selectedDefinition.key === 'fontSize' ? (baseValue.fontSize ?? 14) :
    selectedDefinition.key === 'label' ? (baseValue.label || baseValue.name || '') :
    selectedDefinition.key === 'group' ? (baseValue.group || '') :
    selectedDefinition.key === 'buildingId' ? (baseValue.buildingId ?? '') :
    selectedDefinition.key === 'floor' ? baseValue.floor :
    selectedDefinition.key === 'floors' ? baseValue.floors :
    selectedDefinition.key === 'width' ? baseValue.width :
    selectedDefinition.key === 'height' ? baseValue.height :
    selectedDefinition.key === 'borderEnabled' ? baseValue.borderEnabled :
    selectedDefinition.key === 'borderWidth' ? baseValue.borderWidth :
    selectedDefinition.key === 'disableDestination' ? baseValue.disableDestination :
    selectedDefinition.key === 'allowMultipleJunctions' ? baseValue.allowMultipleJunctions :
    selectedDefinition.key === 'stairDesign' ? baseValue.stairDesign :
    selectedDefinition.key === 'lineThickness' ? (baseValue.lineThickness ?? baseValue.width) :
    baseValue[selectedDefinition.key]
  ) : (selectedDefinition.type === 'boolean' ? false : selectedDefinition.type === 'color' ? '#4f9cff' : '');

  let inputHtml = '';
  if (selectedDefinition.type === 'color') {
    inputHtml = `<input id="batchPropertyValueInput" type="color" value="${(() => { const shown = selectedDefinition.key === 'fontColor' ? normalizeFontColor(value) : value; return isValidHexColor(shown) ? shown : '#4f9cff'; })()}" style="width:100%; height:38px; padding:0; border:1px solid #dbe2f0; border-radius:8px; background:#fff;" />`;
  } else if (selectedDefinition.type === 'boolean') {
    inputHtml = `<label style="display:flex; align-items:center; justify-content:space-between; gap:8px; padding:8px 10px; border:1px solid #dbe2f0; border-radius:8px; background:#f8fafc; font-size:14px;">
      <span>${selectedDefinition.label}</span>
      <input id="batchPropertyValueInput" type="checkbox" ${!!value ? 'checked' : ''} style="width:18px; height:18px;" />
    </label>`;
  } else if (selectedDefinition.type === 'select') {
    const options = selectedDefinition.key === 'buildingId' ? buildingOptions : (selectedDefinition.options || []).map((option) => ({ value: option, label: option }));
    inputHtml = `<select id="batchPropertyValueInput" style="padding:8px 10px; border:1px solid #dbe2f0; border-radius:8px; font:inherit;">
      ${options.map((option) => `<option value="${option.value}" ${String(value) === String(option.value) ? 'selected' : ''}>${option.label}</option>`).join('')}
    </select>`;
  } else {
    const numberType = selectedDefinition.type === 'number' ? 'number' : 'text';
    const minAttr = selectedDefinition.min !== undefined ? `min="${selectedDefinition.min}"` : '';
    const maxAttr = selectedDefinition.max !== undefined ? `max="${selectedDefinition.max}"` : '';
    inputHtml = `<input id="batchPropertyValueInput" type="${numberType}" value="${String(value || '').replace(/"/g, '&quot;')}" ${minAttr} ${maxAttr} style="padding:8px 10px; border:1px solid #dbe2f0; border-radius:8px; font:inherit;" />`;
  }

  inputWrap.innerHTML = inputHtml;
}

function handleBatchPropertySave() {
  const popup = document.getElementById('batchPropertyPopup');
  if (!popup) return;

  const propertySelect = popup.querySelector('#batchPropertySelect');
  const propertyInput = popup.querySelector('#batchPropertyValueInput');
  const propertyKey = propertySelect.value;
  const definition = batchPropertyDefinitions.find((entry) => entry.key === propertyKey);
  if (!definition || !propertyInput) return;

  const value = definition.type === 'boolean'
    ? propertyInput.checked
    : definition.type === 'number'
      ? Number(propertyInput.value)
      : definition.type === 'color'
        ? propertyInput.value
        : definition.type === 'select'
          ? propertyInput.value
          : String(propertyInput.value ?? '');

  const items = getBatchSelectionItems();
  if (!items.length) return;

  pushHistory();
  items.forEach((item) => {
    if (!item) return;

    if (propertyKey === 'color') {
      if (Object.prototype.hasOwnProperty.call(item, 'color')) item.color = isValidHexColor(value) ? value : item.color || '#4f9cff';
      return;
    }

    if (propertyKey === 'fontColor') {
      if (Object.prototype.hasOwnProperty.call(item, 'fontColor')) item.fontColor = normalizeFontColor(value);
      return;
    }

    if (propertyKey === 'fontSize') {
      if (Object.prototype.hasOwnProperty.call(item, 'fontSize')) {
        item.fontSize = clamp(Number(value) || 14, 8, 48);
      }
      return;
    }

    if (propertyKey === 'buildingId') {
      if (!Object.prototype.hasOwnProperty.call(item, 'buildingId')) return;
      const nextBuildingId = value || null;
      const node = getNodeById(item.id);
      if (!node) return;

      const hasNonJunctionSelection = items.some((candidate) => {
        const candidateNode = getNodeById(candidate.id);
        return !!candidateNode && candidateNode.type !== 'junction' && Object.prototype.hasOwnProperty.call(candidate, 'buildingId');
      });

      if (!nextBuildingId && hasNonJunctionSelection) {
        setStatus('Only junctions can be set to no building');
        return;
      }

      const building = nextBuildingId ? getBuildingById(nextBuildingId) : null;
      if (nextBuildingId && !building) {
        setStatus('Building not found');
        return;
      }

      rememberSelectedBuilding(building ? building.id : item.buildingId || state.lastSelectedBuildingId || null);
      node.buildingId = building ? building.id : null;
      node.buildingName = building ? building.name : null;

      if (!building) {
        node.group = '';
        return;
      }

      if (!isRoomLikeNode(node)) {
        node.group = '';
        clampNodeToBuilding(node);
        return;
      }

      if (!node.group) node.group = '';
      clampNodeToBuilding(node);
      return;
    }

    if (propertyKey === 'label') {
      if (Object.prototype.hasOwnProperty.call(item, 'label')) {
        item.label = normalizeLabelText(String(value || ''), item.label || item.name || item.id);
      }
      if (Object.prototype.hasOwnProperty.call(item, 'name')) {
        item.name = normalizeLabelText(String(value || ''), item.name || item.label || item.id);
      }
      return;
    }

    if (propertyKey === 'group') {
      if (item && isRoomLikeNode(item)) {
        const nextGroupValue = normalizeGroupValue(String(value || ''));
        item.group = nextGroupValue || '';
      }
      return;
    }

    if (propertyKey === 'floor') {
      if (Object.prototype.hasOwnProperty.call(item, 'floor')) {
        item.floor = normalizeFloorValue(value, 1);
        if (getNodeById(item.id)) clampNodeFloorToLimit(item);
      }
      return;
    }

    if (propertyKey === 'floors') {
      if (Object.prototype.hasOwnProperty.call(item, 'floors')) {
        item.floors = normalizeFloorCount(value, 1);
      }
      return;
    }

    if (propertyKey === 'width') {
      if (Object.prototype.hasOwnProperty.call(item, 'width')) {
        item.width = clamp(Number(value) || item.width || 220, 1, MAX_CANVAS.width - 40);
      }
      return;
    }

    if (propertyKey === 'height') {
      if (Object.prototype.hasOwnProperty.call(item, 'height')) {
        item.height = clamp(Number(value) || item.height || 180, 1, MAX_CANVAS.height - 40);
      }
      return;
    }

    if (propertyKey === 'borderEnabled') {
      if (Object.prototype.hasOwnProperty.call(item, 'borderEnabled')) {
        item.borderEnabled = !!value;
      }
      return;
    }

    if (propertyKey === 'borderWidth') {
      if (Object.prototype.hasOwnProperty.call(item, 'borderWidth')) {
        item.borderWidth = clamp(Number(value) || 0, 0, 12);
      }
      return;
    }

    if (propertyKey === 'disableDestination') {
      if (isRoomLikeNode(item)) {
        item.disableDestination = !!value;
      }
      return;
    }

    if (propertyKey === 'allowMultipleJunctions') {
      if (isRoomLikeNode(item)) {
        item.allowMultipleJunctions = !!value;
      }
      return;
    }

    if (propertyKey === 'floorConnector') {
      if (canBeFloorConnector(item)) {
        item.floorConnector = !!value;
      }
      return;
    }

    if (propertyKey === 'stairDesign') {
      if (item.type === 'stair') {
        item.stairDesign = normalizeStairDesign(String(value || 'horizontal'));
      }
      return;
    }

    if (propertyKey === 'lineThickness') {
      if (Object.prototype.hasOwnProperty.call(item, 'lineThickness')) {
        const nextWidth = clamp(Number(value) || 2, 1, 50);
        item.lineThickness = nextWidth;
        item.width = nextWidth;
        item.strokeWidth = nextWidth;
      }
      return;
    }
  });

  hideBatchPropertyPopup();
  render();
  saveLayoutToLocalStorage();
  setStatus('Batch property updated');
}

function setMode(mode) {
  state.mode = mode;
  document.querySelectorAll('.tool-btn[data-mode]').forEach((btn) => {
    btn.classList.toggle('active', btn.dataset.mode === mode);
  });
  state.pendingConnectId = null;
  state.pendingLinePoint = null;
  setStatus(mode.toUpperCase());
}

function saveInputOnEnter(event) {
  if (event.key !== 'Enter') return;

  const target = event.target;
  if (!target || !target.matches || !target.matches('input[type="text"], textarea, input[type="number"]')) {
    return;
  }

  if (target === labelInput && event.shiftKey) {
    event.preventDefault();
    const start = target.selectionStart;
    const end = target.selectionEnd;
    target.value = `${target.value.slice(0, start)}\n${target.value.slice(end)}`;
    target.selectionStart = start + 1;
    target.selectionEnd = start + 1;
    return;
  }

  event.preventDefault();

  if (target === mapWidthInput || target === mapHeightInput) {
    applyMapSize(mapWidthInput.value, mapHeightInput.value);
    target.blur();
    return;
  }

  if (
    target === labelInput ||
    target === groupInput ||
    target === tagsInput ||
    target === labelSizeInput ||
    target === buildingFloorCountInput ||
    target === buildingWidthInput ||
    target === buildingHeightInput ||
    target === junctionXInput ||
    target === junctionYInput ||
    target === buildingBorderWidthInput ||
    target === lineThicknessInput
  ) {
    if (target === labelInput && typeof target.value === 'string') {
      target.value = normalizePendingText(target.value);
    }
    applyNodeProperties();
    target.blur();
  }
}

function getActivePropertyChange() {
  const target = document.activeElement;
  if (!target) {
    return null;
  }

  if (target === labelInput) return 'label';
  if (target === groupInput) return 'group';
  if (target === tagsInput) return 'tags';
  if (target === labelSizeInput) return 'labelSize';
  if (target === typeColorInput) return 'color';
  if (target === fontColorInput) return 'fontColor';
  if (target === buildingFloorCountInput) return 'floors';
  if (target === buildingWidthInput) return 'width';
  if (target === buildingHeightInput) return 'height';
  if (target === junctionXInput) return 'x';
  if (target === junctionYInput) return 'y';
  if (target === floorInput) return 'floor';
  if (target === stairDesignInput) return 'stairDesign';
  if (target === lineThicknessInput) return 'lineThickness';
  if (target === buildingBorderToggle) return 'borderEnabled';
  if (target === multiJunctionToggle) return 'allowMultipleJunctions';
  if (target === buildingBorderWidthInput) return 'borderWidth';
  if (target === nodeBuildingSelect) return 'buildingId';
  if (target === disableDestinationToggle) return 'disableDestination';
  if (target === hideDisplayToggle) return 'hideDisplay';
  if (target === floorConnectorToggle) return 'floorConnector';

  return null;
}

function applyNodeProperties() {
  const selectedIds = Array.isArray(state.selectedIds) && state.selectedIds.length ? [...state.selectedIds] : (state.selectedNodeId ? [state.selectedNodeId] : []);
  const relevantLineIds = [...new Set([
    ...(state.selectedLineId ? [state.selectedLineId] : []),
    ...selectedIds.filter((id) => !!getLineById(id))
  ])];
  const relevantBuildingIds = selectedIds.filter((id) => !!getBuildingById(id));
  const relevantNodeIds = selectedIds.filter((id) => !!getNodeById(id));

  const changedProperty = getActivePropertyChange();
  const isFullApply = !changedProperty;

  const isEditingLiveLabel = document.activeElement === labelInput;
  const nextLabelValue = isEditingLiveLabel ? labelInput.value : normalizePendingText(labelInput.value);
  const nextBuildingName = isEditingLiveLabel ? labelInput.value : normalizePendingText(labelInput.value);
  const hasRelevantSelection = relevantLineIds.length || relevantBuildingIds.length || relevantNodeIds.length;

  if (hasRelevantSelection) {
    pushHistory();
  }

  if (relevantLineIds.length) {
    relevantLineIds.forEach((lineId) => {
      const line = getLineById(lineId);
      if (!line) return;

      if (isFullApply || changedProperty === 'lineThickness') {
        if (lineThicknessInput && !lineThicknessInput.disabled) {
          line.width = clamp(Number(lineThicknessInput.value) || 2, 1, 50);
        }
      }

      if ((isFullApply || changedProperty === 'x' || changedProperty === 'y') && selectedIds.length <= 1) {
        moveLineCenterTo(
          line,
          junctionXInput && (isFullApply || changedProperty === 'x') ? Number(junctionXInput.value) : NaN,
          junctionYInput && (isFullApply || changedProperty === 'y') ? Number(junctionYInput.value) : NaN
        );
      }
    });
  }

  if (relevantBuildingIds.length) {
    relevantBuildingIds.forEach((buildingId) => {
      const building = getBuildingById(buildingId);
      if (!building) return;

      if (isFullApply || changedProperty === 'label') {
        building.name = normalizeLabelText(nextBuildingName, building.name);
      }
      if (isFullApply || changedProperty === 'color') {
        building.color = isValidHexColor(typeColorInput.value) ? typeColorInput.value : building.color || '#4f9cff';
      }
      if (isFullApply || changedProperty === 'fontColor') {
        building.fontColor = normalizeFontColor(fontColorInput ? fontColorInput.value : building.fontColor, building.color || '#4f9cff');
      }
      if (isFullApply || changedProperty === 'labelSize') {
        building.fontSize = clamp(Number(labelSizeInput.value) || 14, 8, 48);
      }
      if (isFullApply || changedProperty === 'floors') {
        building.floors = normalizeFloorCount(buildingFloorCountInput ? buildingFloorCountInput.value : building.floors, 1);
        const maxAllowedFloor = normalizeFloorCount(building.floors, 1);
        const savedVisibleFloor = Number(state.buildingFloorState[building.id]);
        if (!Number.isFinite(savedVisibleFloor) || savedVisibleFloor > maxAllowedFloor) {
          state.buildingFloorState[building.id] = Math.min(Number(savedVisibleFloor) || state.activeFloor, maxAllowedFloor);
        }
        // Items that sat above the new top floor are moved down so none are left on a floor that no longer exists.
        let movedCount = 0;
        Object.values(state.nodes).forEach((childNode) => {
          if (childNode.buildingId === building.id && normalizeFloorValue(childNode.floor, 1) > maxAllowedFloor) {
            childNode.floor = maxAllowedFloor;
            movedCount += 1;
          }
        });
        if (movedCount) {
          setStatus(`${movedCount} item(s) moved to floor ${maxAllowedFloor}`);
        }
      }
      if (isFullApply || changedProperty === 'width') {
        building.width = clamp(Number(buildingWidthInput.value) || building.width || 220, 1, MAX_CANVAS.width - 40);
      }
      if ((isFullApply || changedProperty === 'x' || changedProperty === 'y') && selectedIds.length <= 1 && junctionXInput && junctionYInput) {
        const nextBuildingX = (isFullApply || changedProperty === 'x') && junctionXInput.value.trim() !== '' ? Number(junctionXInput.value) : NaN;
        const nextBuildingY = (isFullApply || changedProperty === 'y') && junctionYInput.value.trim() !== '' ? Number(junctionYInput.value) : NaN;
        moveBuildingTo(building, nextBuildingX, nextBuildingY);
      }
      if (isFullApply || changedProperty === 'height') {
        building.height = clamp(Number(buildingHeightInput.value) || building.height || 180, 1, MAX_CANVAS.height - 40);
      }
      if (buildingBorderToggle && (isFullApply || changedProperty === 'borderEnabled')) {
        building.borderEnabled = !!buildingBorderToggle.checked;
      }
      if (buildingBorderWidthInput && (isFullApply || changedProperty === 'borderWidth')) {
        building.borderWidth = clamp(Number(buildingBorderWidthInput.value) || 0, 0, 12);
        if (building.borderEnabled && building.borderWidth < 1) {
          building.borderWidth = 1;
        }
      }
      if (isFullApply || changedProperty === 'width' || changedProperty === 'height') {
        constrainBuildingToCanvas(building);
      }
    });
  }

  if (relevantNodeIds.length) {
    relevantNodeIds.forEach((nodeId) => {
      const node = getNodeById(nodeId);
      if (!node) return;

      // Junctions keep their old behaviour; rooms and stairs only take a position when one item is selected.
      // (Building anchor nodes follow their building, so they are never positioned directly.)
      const canSetNodePosition = node.type === 'junction' || (node.type !== 'building' && selectedIds.length <= 1);

      if (isFullApply || changedProperty === 'x') {
        if (canSetNodePosition && junctionXInput && junctionXInput.value.trim() !== '') {
          const nextX = Number(junctionXInput.value);
          if (!Number.isNaN(nextX)) {
            node.x = clamp(nextX, 0, MAX_CANVAS.width);
          }
        }
      }

      if (isFullApply || changedProperty === 'y') {
        if (canSetNodePosition && junctionYInput && junctionYInput.value.trim() !== '') {
          const nextY = Number(junctionYInput.value);
          if (!Number.isNaN(nextY)) {
            node.y = clamp(nextY, 0, MAX_CANVAS.height);
          }
        }
      }

      if (isFullApply || changedProperty === 'label') {
        node.label = normalizeLabelText(nextLabelValue, node.id);
      }
      if (isFullApply || changedProperty === 'group') {
        if (!isRoomLikeNode(node)) {
          node.group = '';
        } else {
          const rawGroupValue = groupInput ? groupInput.value : (node.group || '');
          const trimmedValue = String(rawGroupValue || '').trim();
          const nextGroupValue = normalizeGroupValue(rawGroupValue);
          const hasValidGroupSyntax = /^([a-z0-9_]+(?:\s*[a-z0-9_]+)*)\s*\([A-Za-z0-9][A-Za-z0-9\s-]*\)$/.test(trimmedValue.toLowerCase());

          if (trimmedValue && !hasValidGroupSyntax) {
            setStatus('Group format must be "group_name (Name On Destination)"');
          } else if (trimmedValue && nextGroupValue) {
            node.group = nextGroupValue;
          } else {
            node.group = '';
          }
        }
      }
      if (isFullApply || changedProperty === 'tags') {
        node.tags = isRoomLikeNode(node) ? normalizeTags(tagsInput ? tagsInput.value : node.tags) : [];
      }
      if (isFullApply || changedProperty === 'disableDestination') {
        if (!isRoomLikeNode(node)) {
          node.disableDestination = false;
        } else {
          node.disableDestination = !!(disableDestinationToggle ? disableDestinationToggle.checked : false);
        }
      }
      if (isFullApply || changedProperty === 'labelSize') {
        node.fontSize = clamp(Number(labelSizeInput.value) || 14, 8, 48);
      }
      if (isFullApply || changedProperty === 'color') {
        node.color = isValidHexColor(typeColorInput.value) ? typeColorInput.value : getTypeColor(node.type);
      }
      if (isFullApply || changedProperty === 'fontColor') {
        node.fontColor = normalizeFontColor(fontColorInput ? fontColorInput.value : node.fontColor, node.color || getTypeColor(node.type));
      }
      if (isFullApply || changedProperty === 'floor') {
        node.floor = normalizeFloorValue(floorInput ? floorInput.value : node.floor, 1);
      }
      if (hideDisplayToggle && (isFullApply || changedProperty === 'hideDisplay')) {
        node.hideDisplay = node.type === 'junction' ? false : !!hideDisplayToggle.checked;
      }
      if (floorConnectorToggle && (isFullApply || changedProperty === 'floorConnector')) {
        node.floorConnector = canBeFloorConnector(node) ? !!floorConnectorToggle.checked : false;
      }
      if (buildingBorderToggle && (isFullApply || changedProperty === 'borderEnabled')) {
        node.borderEnabled = !!buildingBorderToggle.checked;
      }
      if (isFullApply || changedProperty === 'allowMultipleJunctions') {
        node.allowMultipleJunctions = !!multiJunctionToggle.checked;
      }
      if (buildingBorderWidthInput && (isFullApply || changedProperty === 'borderWidth')) {
        node.borderWidth = clamp(Number(buildingBorderWidthInput.value) || 0, 0, 12);
      }
      if (isFullApply || changedProperty === 'stairDesign') {
        if (node.type === 'stair') {
          node.stairDesign = normalizeStairDesign(stairDesignInput ? stairDesignInput.value : node.stairDesign);
        }
      }

      if (isFullApply || changedProperty === 'borderEnabled' || changedProperty === 'borderWidth') {
        if (node.borderEnabled && node.borderWidth < 1) {
          node.borderWidth = 1;
        }
      }

      if (isFullApply || changedProperty === 'width' || changedProperty === 'height') {
        const fallbackNodeSize = getDefaultNodeSize(node.type, node.buildingId || state.selectedBuildingId);
        if (isFullApply || changedProperty === 'width') {
          node.width = clamp(Number(buildingWidthInput.value) || fallbackNodeSize.width, 1, Math.max(1, MAX_CANVAS.width));
        }
        if (isFullApply || changedProperty === 'height') {
          node.height = clamp(Number(buildingHeightInput.value) || fallbackNodeSize.height, 1, Math.max(1, MAX_CANVAS.height));
        }
      }

      if (isFullApply || changedProperty === 'buildingId') {
        if (nodeBuildingSelect && nodeBuildingSelect.value) {
          node.buildingId = nodeBuildingSelect.value || node.buildingId || null;
        }
      }

      if (node.buildingId) {
        const building = getBuildingById(node.buildingId);
        if (building) {
          if (isFullApply || changedProperty === 'x' || changedProperty === 'y' || changedProperty === 'width' || changedProperty === 'height') {
            clampNodeToBuilding(node);
          }
          node.buildingName = building.name;
        }
      }

      // A node can never sit above its building's top floor.
      if (isFullApply || changedProperty === 'floor' || changedProperty === 'buildingId') {
        clampNodeFloorToLimit(node);
      }
    });
  }

  if (!relevantLineIds.length && !relevantBuildingIds.length && !relevantNodeIds.length) {
    setStatus('Select a node or building first');
    return;
  }

  if (!isEditingLiveLabel) {
    labelInput.value = nextLabelValue;
  }
  render();
}

function updateSelectedBuilding() {
  if (!state.selectedBuildingId) {
    setStatus('Select a building first');
    return;
  }

  const building = getBuildingById(state.selectedBuildingId);
  if (!building) {
    setStatus('Building not found');
    return;
  }

  pushHistory();
  const nextName = normalizeLabelText(buildingNameInput ? buildingNameInput.value : '', building.name);

  building.name = nextName;

  const anchor = ensureBuildingAnchorNode(building);
  if (anchor) {
    anchor.label = building.name;
    anchor.buildingName = building.name;
  }

  Object.values(state.nodes).forEach((node) => {
    if (node.buildingId === building.id) {
      node.buildingName = building.name;
      if (node.type !== 'building' && node.id !== building.nodeId) {
        node.label = node.label || node.id;
      }
    }
  });

  setStatus(`Updated ${building.name}`);
  render();
}

function addBuilding(name) {
  const cleanName = getUniqueBuildingName(name);

  pushHistory();
  const defaultBuildingSize = getDefaultBuildingSize();
  const building = {
    id: `building-${generateId('bldg')}`,
    name: cleanName,
    color: getBuildingColor(cleanName),
    x: clamp(120 + (state.buildings.length * 40), 40, MAX_CANVAS.width - defaultBuildingSize.width - 40),
    y: clamp(120 + (state.buildings.length * 30), 40, MAX_CANVAS.height - defaultBuildingSize.height - 40),
    width: defaultBuildingSize.width,
    height: defaultBuildingSize.height,
    floors: 1,
    fontSize: 14,
    fontWeight: 700,
    fontFamily: 'Arial, sans-serif'
  };

  constrainBuildingToCanvas(building);
  state.buildings.push(building);
  ensureBuildingAnchorNode(building);
  state.selectedBuildingId = building.id;
  rememberSelectedBuilding(building.id);
  setStatus('Building added');
  render();
  return building.id;
}

function removeBuilding(buildingId) {
  if (!buildingId) return;
  pushHistory();
  const building = getBuildingById(buildingId);
  if (building && building.nodeId) {
    delete state.nodes[building.nodeId];
  }

  state.buildings = state.buildings.filter((buildingEntry) => buildingEntry.id !== buildingId);
  Object.values(state.nodes).forEach((node) => {
    if (node.buildingId === buildingId) {
      node.buildingId = null;
      node.buildingName = null;
    }
  });

  if (state.selectedBuildingId === buildingId) {
    state.selectedBuildingId = state.buildings[0]?.id || null;
  }
  if (state.lastSelectedBuildingId === buildingId) {
    state.lastSelectedBuildingId = state.buildings[0]?.id || null;
  }
  render();
}

function assignSelectedNodeToBuilding(buildingId) {
  const selectedNodeIds = [...new Set([
    ...(state.selectedNodeId ? [state.selectedNodeId] : []),
    ...(Array.isArray(state.selectedIds) ? state.selectedIds : [])
  ])].filter((id) => !!getNodeById(id));

  if (!selectedNodeIds.length) {
    setStatus('Select a node first');
    return;
  }

  const hasNonJunctionSelection = selectedNodeIds.some((id) => getNodeById(id)?.type !== 'junction');
  if (!buildingId && hasNonJunctionSelection) {
    setStatus('Only junctions can be set to no building');
    return;
  }

  const building = buildingId ? getBuildingById(buildingId) : null;
  if (buildingId && !building) {
    setStatus('Building not found');
    return;
  }

  pushHistory();

  selectedNodeIds.forEach((nodeId) => {
    const selected = getNodeById(nodeId);
    if (!selected) return;

    if (!building) {
      selected.buildingId = null;
      selected.buildingName = null;
      return;
    }

    rememberSelectedBuilding(building.id);
    selected.buildingId = building.id;
    selected.buildingName = building.name;
    clampNodeFloorToLimit(selected);
    if (!isRoomLikeNode(selected)) {
      selected.group = '';
      clampNodeToBuilding(selected);
      return;
    }
    if (!selected.group) {
      selected.group = '';
    }
    clampNodeToBuilding(selected);
  });

  render();
}

function getCanvasCenter() {
  return { x: MAX_CANVAS.width / 2, y: MAX_CANVAS.height / 2 };
}

function zoomCanvas(zoomDelta) {
  state.zoom = clamp(Number((state.zoom + zoomDelta).toFixed(2)), 0.5, 2.1);
  applyCanvasTransform();
  updateZoomLabel();
}

function handleScrollZoom(event) {
  event.preventDefault();
  const rect = svg.getBoundingClientRect();
  const pointerX = event.clientX - rect.left;
  const pointerY = event.clientY - rect.top;
  const prevZoom = state.zoom;
  const nextZoom = clamp(Number((state.zoom * (event.deltaY < 0 ? 1.12 : 0.9)).toFixed(2)), 0.5, 2.1);

  const worldX = (pointerX - state.panX) / prevZoom;
  const worldY = (pointerY - state.panY) / prevZoom;

  state.zoom = nextZoom;
  state.panX = pointerX - worldX * nextZoom;
  state.panY = pointerY - worldY * nextZoom;
  applyCanvasTransform();
  updateZoomLabel();
}

function parseZoomInput(rawValue) {
  const normalized = String(rawValue ?? '').replace(/%/g, '').trim();
  if (!normalized) {
    return null;
  }

  const parsedValue = Number.parseFloat(normalized);
  return Number.isFinite(parsedValue) ? parsedValue : null;
}

function applyZoomFromInput() {
  const percentValue = parseZoomInput(zoomLabel.value);
  if (percentValue === null) {
    updateZoomLabel();
    return;
  }

  const nextZoom = clamp(percentValue / 100, 0.5, 2.1);
  state.zoom = Number(nextZoom.toFixed(2));
  applyCanvasTransform();
  updateZoomLabel();
}

function updateZoomLabel() {
  zoomLabel.value = `${Math.round(state.zoom * 100)}%`;
}

function applyMapSize(width, height) {
  const nextWidth = clamp(Number(width) || MAX_CANVAS.width, 300, 5000);
  const nextHeight = clamp(Number(height) || MAX_CANVAS.height, 300, 5000);

  MAX_CANVAS = { width: nextWidth, height: nextHeight };
  svg.setAttribute('viewBox', `0 0 ${MAX_CANVAS.width} ${MAX_CANVAS.height}`);
  svg.setAttribute('width', '100%');
  svg.setAttribute('height', '100%');
  svg.style.width = '100%';
  svg.style.height = '100%';

  Object.values(state.nodes).forEach((node) => {
    node.x = clamp(node.x, 10, MAX_CANVAS.width - 10);
    node.y = clamp(node.y, 10, MAX_CANVAS.height - 10);
  });

  state.buildings.forEach((building) => {
    const safeWidth = Number(building.width) || 220;
    const safeHeight = Number(building.height) || 180;
    const maxX = Math.max(20, MAX_CANVAS.width - safeWidth - 20);
    const maxY = Math.max(20, MAX_CANVAS.height - safeHeight - 20);
    building.x = clamp(Number(building.x) || 120, 20, maxX);
    building.y = clamp(Number(building.y) || 120, 20, maxY);
    ensureBuildingAnchorNode(building);
  });

  mapWidthInput.value = String(MAX_CANVAS.width);
  mapHeightInput.value = String(MAX_CANVAS.height);
  render();
  saveLayoutToLocalStorage();
  setStatus('Map size updated');
}

function addNodeAt(x, y, options = {}) {
  pushHistory();
  const nodeId = options.id || generateId('wp');
  const nodeType = options.type || 'room';
  const selectedBuildingId = options.buildingId || state.selectedBuildingId || state.lastSelectedBuildingId || getPreferredBuildingForNewNode() || (state.buildings[0] && state.buildings[0].id) || null;
  const defaultSize = getDefaultNodeSize(nodeType, selectedBuildingId);
  const node = {
    id: nodeId,
    label: options.label || `New ${nodeType}`,
    x: clamp(x, 40, MAX_CANVAS.width - 40),
    y: clamp(y, 40, MAX_CANVAS.height - 40),
    floor: Number(options.floor) || 1,
    type: nodeType,
    group: isRoomLikeNode({ type: nodeType }) ? (options.group || '') : '',
    buildingId: selectedBuildingId,
    buildingName: options.buildingName || (selectedBuildingId ? getBuildingById(selectedBuildingId)?.name || null : null),
    width: Number(options.width) || defaultSize.width,
    height: Number(options.height) || defaultSize.height,
    fontSize: clamp(Number(options.fontSize) || 14, 8, 48),
    fontWeight: clamp(Number(options.fontWeight) || (nodeType === 'junction' ? 600 : 500), 100, 900),
    fontFamily: String(options.fontFamily || 'Arial, sans-serif'),
    allowMultipleJunctions: !!options.allowMultipleJunctions,
    disableDestination: !!options.disableDestination,
    tags: isRoomLikeNode({ type: nodeType }) ? normalizeTags(options.tags) : [],
    hideDisplay: nodeType === 'junction' ? false : !!options.hideDisplay,
    floorConnector: canBeFloorConnector({ type: nodeType }) ? normalizeBooleanFlag(options.floorConnector) : false,
    stairDesign: nodeType === 'stair' ? normalizeStairDesign(options.stairDesign) : 'horizontal',
    nextMap: options.nextMap || '',
    nextMapNodeId: options.nextMapNodeId || ''
  };

  if (node.buildingId) {
    clampNodeToBuilding(node);
  }
  clampNodeFloorToLimit(node);

  keepRoomSeparated(node);

  state.nodes[nodeId] = node;
  state.selectedBuildingId = null;
  state.selectedNodeId = nodeId;
  state.selectedIds = [nodeId];
  setStatus('Node added');
  render();
  return nodeId;
}

function ensureNodeWithinBuilding(node, buildingId = node?.buildingId) {
  if (!node || !buildingId) return node;
  const building = getBuildingById(buildingId);
  if (!building) return node;
  const bounds = getBuildingBounds(building);
  if (!bounds) return node;

  const halfWidth = (Number(node.width) || (node.type === 'junction' ? 5 : 52)) / 2;
  const halfHeight = (Number(node.height) || (node.type === 'junction' ? 5 : 44)) / 2;

  node.x = clamp(node.x, bounds.x + halfWidth, bounds.right - halfWidth);
  node.y = clamp(node.y, bounds.y + halfHeight, bounds.bottom - halfHeight);
  return node;
}

function createRoomAtCursor(evt) {
  const pos = getNodePositionFromEvent(evt);
  const buildingId = getPreferredBuildingForNewNode();
  const building = buildingId ? getBuildingById(buildingId) : null;
  const nextPos = { x: snapToGrid(pos.x), y: snapToGrid(pos.y) };

  if (building) {
    const bounds = getBuildingBounds(building);
    const defaultSize = getDefaultNodeSize('room', buildingId);
    const halfWidth = (Number(defaultSize.width) || 52) / 2;
    const halfHeight = (Number(defaultSize.height) || 44) / 2;
    nextPos.x = clamp(nextPos.x, bounds.x + halfWidth, bounds.right - halfWidth);
    nextPos.y = clamp(nextPos.y, bounds.y + halfHeight, bounds.bottom - halfHeight);
  }

  addNodeAt(nextPos.x, nextPos.y, {
    label: 'New room',
    type: 'room',
    floor: normalizeFloorValue(state.activeFloor, 1),
    group: '',
    buildingId,
    buildingName: building ? building.name : null
  });
  setMode('select');
}

function createJunctionAtCursor(evt) {
  const pos = getNodePositionFromEvent(evt);
  const nextPos = { x: snapToGrid(pos.x), y: snapToGrid(pos.y) };

  addNodeAt(nextPos.x, nextPos.y, {
    label: 'Junction',
    type: 'junction',
    floor: normalizeFloorValue(state.activeFloor, 1),
    group: '',
    buildingId: null,
    buildingName: null
  });
  setMode('select');
}

function createStairAtCursor(evt) {
  const pos = getNodePositionFromEvent(evt);
  const buildingId = getPreferredBuildingForNewNode();
  const building = buildingId ? getBuildingById(buildingId) : null;
  const nextPos = { x: snapToGrid(pos.x), y: snapToGrid(pos.y) };

  if (building) {
    const bounds = getBuildingBounds(building);
    const halfWidth = 32;
    const halfHeight = 28;
    nextPos.x = clamp(nextPos.x, bounds.x + halfWidth, bounds.right - halfWidth);
    nextPos.y = clamp(nextPos.y, bounds.y + halfHeight, bounds.bottom - halfHeight);
  }

  addNodeAt(nextPos.x, nextPos.y, {
    label: 'Stair',
    type: 'stair',
    floor: normalizeFloorValue(state.activeFloor, 1),
    group: '',
    buildingId,
    buildingName: building ? building.name : null,
    width: 72,
    height: 52,
    stairDesign: 'horizontal',
    nextMap: '',
    nextMapNodeId: ''
  });
  setMode('select');
}

function deleteSelectedItems() {
  const ids = new Set([
    ...(state.selectedIds || []),
    ...(state.selectedNodeId ? [state.selectedNodeId] : []),
    ...(state.selectedLineId ? [state.selectedLineId] : []),
    ...(state.selectedBuildingId ? [state.selectedBuildingId] : [])
  ]);

  if (!ids.size) {
    setStatus('No items selected');
    return;
  }

  pushHistory();

  const buildingIdsToDelete = [...ids].filter((id) => !!getBuildingById(id));
  const nodeIdsToDelete = [...ids].filter((id) => {
    const node = getNodeById(id);
    return !!node && node.type !== 'building';
  });
  const lineIdsToDelete = [...ids].filter((id) => !!getLineById(id));

  buildingIdsToDelete.forEach((buildingId) => {
    const building = getBuildingById(buildingId);
    if (building && building.nodeId) {
      delete state.nodes[building.nodeId];
    }
    state.buildings = state.buildings.filter((buildingEntry) => buildingEntry.id !== buildingId);
    Object.values(state.nodes).forEach((node) => {
      if (node.buildingId === buildingId) {
        node.buildingId = null;
        node.buildingName = null;
      }
    });
  });

  nodeIdsToDelete.forEach((nodeId) => {
    delete state.nodes[nodeId];
  });

  state.edges = state.edges.filter(([fromId, toId]) => !nodeIdsToDelete.includes(fromId) && !nodeIdsToDelete.includes(toId));
  state.lines = state.lines.filter((line) => !lineIdsToDelete.includes(line.id));
  clearSelection();
  render();
}

function deleteNode(id) {
  if (!id) return;
  const node = getNodeById(id);
  if (!node) return;

  pushHistory();

  if (node.type === 'building' && node.buildingId) {
    removeBuilding(node.buildingId);
    return;
  }

  delete state.nodes[id];
  state.edges = state.edges.filter(([fromId, toId]) => fromId !== id && toId !== id);
  if (state.selectedNodeId === id) {
    state.selectedNodeId = null;
  }
  syncSelectedIds();
  render();
}

function getSelectedEntityIds() {
  const ids = new Set([
    ...(state.selectedIds || []),
    ...(state.selectedNodeId ? [state.selectedNodeId] : []),
    ...(state.selectedLineId ? [state.selectedLineId] : []),
    ...(state.selectedBuildingId ? [state.selectedBuildingId] : [])
  ]);

  return [...ids].filter((id) => !!getNodeById(id) || !!getBuildingById(id) || !!getLineById(id));
}

function copySelectionToClipboard() {
  const ids = getSelectedEntityIds();
  if (!ids.length) {
    setStatus('Select an item to copy');
    return false;
  }

  const selectedNodeIds = new Set(ids.filter((id) => !!getNodeById(id)));
  const selectedBuildings = ids.filter((id) => !!getBuildingById(id)).map((id) => ({ ...getBuildingById(id) }));
  const selectedNodes = ids.filter((id) => !!getNodeById(id)).map((id) => ({ ...getNodeById(id) }));
  const selectedLines = ids.filter((id) => !!getLineById(id)).map((id) => ({ ...getLineById(id) }));

  clipboardState = {
    buildings: selectedBuildings,
    nodes: selectedNodes,
    lines: selectedLines,
    edges: state.edges.filter(([fromId, toId]) => selectedNodeIds.has(fromId) && selectedNodeIds.has(toId))
  };

  setStatus('Selection copied');
  return true;
}

function cutSelectionToClipboard() {
  if (!copySelectionToClipboard()) {
    return false;
  }

  pushHistory();
  deleteSelectedItems();
  setStatus('Selection cut');
  return true;
}

function pasteSelectionFromClipboard() {
  if (!clipboardState.nodes.length && !clipboardState.buildings.length && !clipboardState.lines.length) {
    setStatus('Nothing to paste');
    return false;
  }

  pushHistory();

  const buildingIdMap = new Map();
  const nodeIdMap = new Map();
  const lineIdMap = new Map();
  const xOffset = 40;
  const yOffset = 30;

  clipboardState.buildings.forEach((building) => {
    const duplicateBuilding = {
      ...building,
      id: generateId('building'),
      x: clamp((Number(building.x) || 120) + xOffset, 20, MAX_CANVAS.width - 80),
      y: clamp((Number(building.y) || 120) + yOffset, 20, MAX_CANVAS.height - 80)
    };

    buildingIdMap.set(building.id, duplicateBuilding.id);
    state.buildings.push(duplicateBuilding);
    ensureBuildingAnchorNode(duplicateBuilding);
  });

  clipboardState.nodes.forEach((node) => {
    const duplicate = {
      ...node,
      id: generateId('wp'),
      x: clamp((Number(node.x) || 120) + xOffset, 20, MAX_CANVAS.width - 40),
      y: clamp((Number(node.y) || 120) + yOffset, 20, MAX_CANVAS.height - 40),
      buildingId: buildingIdMap.has(node.buildingId) ? buildingIdMap.get(node.buildingId) : node.buildingId || null,
      buildingName: buildingIdMap.has(node.buildingId) ? getBuildingById(buildingIdMap.get(node.buildingId))?.name || null : node.buildingName || null
    };

    nodeIdMap.set(node.id, duplicate.id);
    state.nodes[duplicate.id] = duplicate;
  });

  clipboardState.lines.forEach((line) => {
    const duplicateLine = {
      ...line,
      id: generateId('line'),
      x1: clamp((Number(line.x1) || 0) + xOffset, 0, MAX_CANVAS.width),
      y1: clamp((Number(line.y1) || 0) + yOffset, 0, MAX_CANVAS.height),
      x2: clamp((Number(line.x2) || 0) + xOffset, 0, MAX_CANVAS.width),
      y2: clamp((Number(line.y2) || 0) + yOffset, 0, MAX_CANVAS.height)
    };

    lineIdMap.set(line.id, duplicateLine.id);
    state.lines.push(duplicateLine);
  });

  const pastedEdges = (clipboardState.edges || [])
    .filter(([fromId, toId]) => nodeIdMap.has(fromId) && nodeIdMap.has(toId))
    .map(([fromId, toId]) => [nodeIdMap.get(fromId), nodeIdMap.get(toId)]);

  state.edges.push(...pastedEdges);

  const pastedIds = [...buildingIdMap.values(), ...nodeIdMap.values(), ...lineIdMap.values()];
  state.selectedIds = pastedIds.filter((id) => !!getNodeById(id) || !!getBuildingById(id) || !!getLineById(id));
  state.selectedNodeId = pastedIds.find((id) => !!getNodeById(id)) || null;
  state.selectedBuildingId = pastedIds.find((id) => !!getBuildingById(id)) || null;
  state.selectedLineId = pastedIds.find((id) => !!getLineById(id)) || null;

  render();
  saveLayoutToLocalStorage();
  setStatus('Clipboard pasted');
  return true;
}

function duplicateSelectedNode() {
  const selectedIds = state.selectedIds.length ? [...state.selectedIds] : state.selectedNodeId ? [state.selectedNodeId] : [];
  if (!selectedIds.length) {
    setStatus('Select an item to duplicate');
    return;
  }

  pushHistory();

  const buildingMap = new Map();
  const copiedIds = [];

  const selectedBuildings = selectedIds.filter((id) => !!getBuildingById(id));
  selectedBuildings.forEach((buildingId) => {
    const building = getBuildingById(buildingId);
    if (!building) return;

    const duplicateBuildingName = makeSequentialDuplicateName(building.name, state.buildings.map((entry) => entry.name));
    const duplicateBuilding = {
      ...building,
      id: `building-${generateId('bldg')}`,
      name: duplicateBuildingName,
      x: clamp((Number(building.x) || 120) + 40, 20, MAX_CANVAS.width - 80),
      y: clamp((Number(building.y) || 120) + 30, 20, MAX_CANVAS.height - 80)
    };

    state.buildings.push(duplicateBuilding);
    buildingMap.set(buildingId, duplicateBuilding.id);
    copiedIds.push(duplicateBuilding.id);
    ensureBuildingAnchorNode(duplicateBuilding);
  });

  selectedIds.forEach((id) => {
    const node = getNodeById(id);
    if (node) {
      const duplicateLabel = makeSequentialDuplicateName(node.label || node.id, Object.values(state.nodes).map((entry) => entry.label));
      const duplicate = {
        ...node,
        id: generateId('wp'),
        label: duplicateLabel,
        x: clamp(node.x + 40, 20, MAX_CANVAS.width - 40),
        y: clamp(node.y + 30, 20, MAX_CANVAS.height - 40),
        width: Number(node.width) || 52,
        height: Number(node.height) || 44,
        buildingId: buildingMap.has(node.buildingId) ? buildingMap.get(node.buildingId) : node.buildingId || null,
        buildingName: buildingMap.has(node.buildingId) ? getBuildingById(buildingMap.get(node.buildingId))?.name || null : node.buildingName || null
      };

      state.nodes[duplicate.id] = duplicate;
      copiedIds.push(duplicate.id);
      return;
    }

    const line = getLineById(id);
    if (line) {
      const duplicateLine = {
        ...line,
        id: generateId('line'),
        x1: clamp((Number(line.x1) || 0) + 40, 0, MAX_CANVAS.width),
        y1: clamp((Number(line.y1) || 0) + 30, 0, MAX_CANVAS.height),
        x2: clamp((Number(line.x2) || 0) + 40, 0, MAX_CANVAS.width),
        y2: clamp((Number(line.y2) || 0) + 30, 0, MAX_CANVAS.height)
      };

      state.lines.push(duplicateLine);
      copiedIds.push(duplicateLine.id);
    }
  });

  state.selectedIds = copiedIds.filter((id) => !!getNodeById(id) || !!getBuildingById(id) || !!getLineById(id));
  state.selectedNodeId = copiedIds.find((id) => !!getNodeById(id)) || null;
  state.selectedBuildingId = copiedIds.find((id) => !!getBuildingById(id)) || null;
  state.selectedLineId = copiedIds.find((id) => !!getLineById(id)) || null;
  setStatus('Selection duplicated');
  render();
}

function canConnectNodes(firstId, secondId) {
  if (!firstId || !secondId || firstId === secondId) return false;

  const firstNode = getNodeById(firstId);
  const secondNode = getNodeById(secondId);
  if (!firstNode || !secondNode) return false;

  if (firstNode.type === 'building' || secondNode.type === 'building') {
    return false;
  }

  const firstIsRoom = isRoomLikeNode(firstNode);
  const secondIsRoom = isRoomLikeNode(secondNode);
  const firstIsJunction = firstNode.type === 'junction';
  const secondIsJunction = secondNode.type === 'junction';
  const exists = state.edges.some(([a, b]) =>
    (a === firstId && b === secondId) || (a === secondId && b === firstId)
  );

  if (exists) {
    return true;
  }

  const firstFloor = Number(firstNode.floor || 1);
  const secondFloor = Number(secondNode.floor || 1);

  if (firstNode.type === 'stair' && secondNode.type === 'stair') {
    return Math.abs(firstFloor - secondFloor) === 1;
  }

  if ((firstNode.type === 'stair' && secondNode.type === 'junction') ||
      (firstNode.type === 'junction' && secondNode.type === 'stair')) {
    return Math.abs(firstFloor - secondFloor) <= 1;
  }

  if (firstIsJunction && secondIsJunction) {
    return Math.abs(firstFloor - secondFloor) <= 1;
  }

  if (firstIsRoom && secondIsRoom) {
    return false;
  }

  const isRoomToJunction = (firstIsRoom && secondIsJunction) || (firstIsJunction && secondIsRoom);

  if (!isRoomToJunction) {
    return false;
  }

  const roomNode = firstIsRoom ? firstNode : secondNode;
  const roomAlreadyConnectedToJunction = state.edges.some(([a, b]) => {
    const otherId = a === roomNode.id ? b : (b === roomNode.id ? a : null);
    if (!otherId) return false;
    const otherNode = getNodeById(otherId);
    return !!otherNode && otherNode.type === 'junction';
  });

  if (roomNode && roomNode.allowMultipleJunctions) {
    return true;
  }

  return !roomAlreadyConnectedToJunction;
}

function addSimpleLineBetweenNodes(firstId, secondId) {
  if (!firstId || !secondId || firstId === secondId) return;

  const alreadyConnected = state.edges.some(([a, b]) =>
    (a === firstId && b === secondId) || (a === secondId && b === firstId)
  );

  if (alreadyConnected) {
    state.edges = state.edges.filter(([a, b]) => !(
      (a === firstId && b === secondId) || (a === secondId && b === firstId)
    ));
    setStatus('Line removed');
    render();
    return;
  }

  pushHistory();
  state.edges.push([firstId, secondId]);
  render();
  setStatus('Line added');
}

function connectNodes(firstId, secondId) {
  const firstNode = getNodeById(firstId);
  const secondNode = getNodeById(secondId);
  const alreadyConnected = state.edges.some(([a, b]) =>
    (a === firstId && b === secondId) || (a === secondId && b === firstId)
  );

  if (alreadyConnected) {
    state.edges = state.edges.filter(([a, b]) => !(
      (a === firstId && b === secondId) || (a === secondId && b === firstId)
    ));
    setStatus('Connection removed');
    render();
    return false;
  }

  if (!canConnectNodes(firstId, secondId)) {
    if (firstNode?.type === 'building' || secondNode?.type === 'building') {
      setStatus('Buildings cannot be connected directly');
    } else if (isRoomLikeNode(firstNode) && isRoomLikeNode(secondNode)) {
      setStatus('Room-to-room connections are not allowed');
    } else if ((firstNode && firstNode.type === 'junction') || (secondNode && secondNode.type === 'junction')) {
      const roomNode = isRoomLikeNode(firstNode) ? firstNode : secondNode;
      const roomHasJunction = state.edges.some(([a, b]) => {
        const otherId = a === roomNode.id ? b : (b === roomNode.id ? a : null);
        if (!otherId) return false;
        const otherNode = getNodeById(otherId);
        return !!otherNode && otherNode.type === 'junction';
      });
      if (roomHasJunction) {
        setStatus('A room can only connect to one junction');
      } else if (firstNode && firstNode.type === 'junction' && secondNode && secondNode.type === 'junction') {
        setStatus('Junction-to-junction connections are not allowed');
      } else {
        setStatus('This connection already exists');
      }
    } else if (firstNode && firstNode.type === 'junction' && secondNode && secondNode.type === 'junction') {
      setStatus('Junction-to-junction connections are not allowed');
    } else {
      setStatus('This connection already exists');
    }
    return false;
  }

  const roomNode = isRoomLikeNode(firstNode) ? firstNode : (isRoomLikeNode(secondNode) ? secondNode : null);
  const junctionNode = firstNode && firstNode.type === 'junction' ? firstNode : (secondNode && secondNode.type === 'junction' ? secondNode : null);

  if (roomNode && junctionNode) {
    const dx = Math.abs(junctionNode.x - roomNode.x);
    const dy = Math.abs(junctionNode.y - roomNode.y);

    if (dy >= dx) {
      // Junction is above or below the room -> assign room's x coordinate
      junctionNode.x = roomNode.x;
    } else {
      // Junction is to the right or left of the room -> assign room's y coordinate
      junctionNode.y = roomNode.y;
    }

    if (junctionNode.buildingId) {
      clampNodeToBuilding(junctionNode);
    }
  }

  pushHistory();
  state.edges.push([firstId, secondId]);
  render();
  return true;
}

function matchesSelectionMode(node) {
  if (!node) return false;
  if (state.mode === 'select' || state.mode === 'select-room' || state.mode === 'select-junction' || state.mode === 'select-building') {
    if (state.mode === 'select-room') return isRoomLikeNode(node);
    if (state.mode === 'select-junction') return node.type === 'junction';
    if (state.mode === 'select-building') return node.type === 'building';
    return true;
  }
  return true;
}

function handleNodeClick(nodeId, event = null) {
  const node = getNodeById(nodeId);
  if (!node) return;

  if (!nodeMatchesVisibleFloor(node)) {
    setStatus('That item is on a hidden floor');
    return;
  }

  if (!matchesSelectionMode(node)) {
    setStatus('That selection mode only accepts the matching element type');
    return;
  }

  const additiveSelection = !!(event && (event.ctrlKey || event.metaKey || event.shiftKey));

  if (state.selectedIds.length > 1 && state.selectedIds.includes(nodeId)) {
    state.selectedNodeId = nodeId;
    if (!additiveSelection) {
      state.selectedIds = [nodeId];
    }
    syncSelectedIds();
    setStatus(additiveSelection ? `${state.selectedIds.length} items selected` : 'Group selection active');
    render();
    return;
  }

  if (state.mode === 'delete') {
    state.selectedNodeId = nodeId;
    state.selectedIds = [nodeId];
    setStatus('Item selected for deletion');
    render();
    return;
  }

  if (state.mode === 'connect') {
    if (!state.pendingConnectId) {
      state.pendingConnectId = nodeId;
      state.selectedBuildingId = null;
      state.selectedNodeId = nodeId;
      state.selectedIds = [nodeId];
      setStatus('First node selected for connection');
      render();
      return;
    }

    if (state.pendingConnectId !== nodeId) {
      const connectionCreated = connectNodes(state.pendingConnectId, nodeId);
      const firstNode = getNodeById(state.pendingConnectId);
      const secondNode = getNodeById(nodeId);
      const junctionJoined = !!firstNode && firstNode.type === 'junction' && !!secondNode && secondNode.type === 'junction' && connectionCreated;

      state.pendingConnectId = junctionJoined ? nodeId : null;
      state.selectedNodeId = junctionJoined ? nodeId : null;
      state.selectedBuildingId = null;
      state.selectedIds = junctionJoined ? [nodeId] : [];
      setStatus(junctionJoined ? 'Junction ready for next connection' : 'Nodes connected');
    }
    return;
  }

  if (state.mode === 'add-line') {
    state.selectedBuildingId = null;
    state.selectedNodeId = nodeId;
    state.selectedIds = [nodeId];
    syncSelectedIds();
    setStatus('Line tool is free-drawing only');
    render();
    return;
  }

  if (additiveSelection) {
    const nextSelection = new Set(state.selectedIds || []);
    if (nextSelection.has(nodeId)) {
      nextSelection.delete(nodeId);
    } else {
      nextSelection.add(nodeId);
    }

    if (!nextSelection.size) {
      clearSelection();
      setStatus('Selection cleared');
      render();
      return;
    }

    state.selectedBuildingId = null;
    state.selectedNodeId = [...nextSelection].find((id) => !!getNodeById(id)) || null;
    state.selectedLineId = [...nextSelection].find((id) => !!getLineById(id)) || null;
    state.selectedIds = [...nextSelection];
    syncSelectedIds();
    setStatus(`${state.selectedIds.length} items selected`);
    render();
    return;
  }

  state.selectedBuildingId = null;
  state.selectedNodeId = nodeId;
  state.selectedIds = [nodeId];
  syncSelectedIds();
  setMode('select');
  setStatus('Node selected');
  render();
}

let dragState = null;
let panState = null;

function finalizeSelectionBox(event) {
  if (!dragState || dragState.kind !== 'selection-box') return;

  const current = getNodePositionFromEvent(event);
  const rect = {
    x: dragState.startX,
    y: dragState.startY,
    width: current.x - dragState.startX,
    height: current.y - dragState.startY
  };

  if (dragState.pointerId != null) {
    svg.releasePointerCapture?.(dragState.pointerId);
  }

  state.selectionBox = null;
  dragState = null;
  applySelectionFromBox(rect);
}

function startBuildingDrag(buildingId, event) {
  const building = getBuildingById(buildingId);
  if (!building) return;

  const startPos = getNodePositionFromEvent(event);
  const nodesInBuilding = getNodesInBuilding(buildingId);
  pushHistory();
  dragState = {
    kind: 'building',
    buildingId,
    startPos,
    nodeStartPositions: Object.fromEntries(nodesInBuilding.map((node) => [node.id, { x: node.x, y: node.y }])),
    buildingStart: { x: Number(building.x) || 120, y: Number(building.y) || 120 }
  };
  state.selectedBuildingId = buildingId;
  state.selectedNodeId = null;
  state.selectedIds = [buildingId];
  render();
}

function startMultiSelectionDrag(event) {
  const ids = new Set(state.selectedIds.length ? state.selectedIds : [state.selectedNodeId].filter(Boolean));
  if (!ids.size) return;

  const selectedBuildingIds = new Set([...ids].filter((id) => !!getBuildingById(id)));
  const startPos = getNodePositionFromEvent(event);
  const dragSnapshot = {};

  ids.forEach((id) => {
    const node = getNodeById(id);
    if (node) {
      if (node.buildingId && selectedBuildingIds.has(node.buildingId)) {
        return;
      }
      dragSnapshot[id] = { kind: 'node', x: node.x, y: node.y };
      return;
    }

    const building = getBuildingById(id);
    if (building) {
      dragSnapshot[id] = { kind: 'building', x: Number(building.x) || 120, y: Number(building.y) || 120 };
      return;
    }

    const line = getLineById(id);
    if (line) {
      dragSnapshot[id] = {
        kind: 'line',
        x1: Number(line.x1) || 0,
        y1: Number(line.y1) || 0,
        x2: Number(line.x2) || 0,
        y2: Number(line.y2) || 0
      };
    }
  });

  if (!Object.keys(dragSnapshot).length) return;

  pushHistory();
  dragState = {
    kind: 'multi-selection',
    startPos,
    snapshot: dragSnapshot
  };
}

function startResizeDrag(event, resizeTarget) {
  if (!resizeTarget) return;

  const itemId = resizeTarget.dataset.resizeTargetId;
  const itemType = resizeTarget.dataset.resizeType;
  const anchor = resizeTarget.dataset.resizeAnchor || '';
  if (!itemId || !itemType || !anchor) return;

  const startPos = getNodePositionFromEvent(event);

  if (itemType === 'line') {
    const line = getLineById(itemId);
    if (!line) return;

    dragState = {
      kind: 'resize-line',
      itemId,
      anchor,
      startPos,
      startLine: {
        x1: Number(line.x1) || 0,
        y1: Number(line.y1) || 0,
        x2: Number(line.x2) || 0,
        y2: Number(line.y2) || 0
      }
    };
    state.selectedLineId = itemId;
    state.selectedNodeId = null;
    state.selectedBuildingId = null;
    state.selectedIds = [itemId];
    setStatus('Line resizing');
    render();
    return;
  }

  const item = itemType === 'building' ? getBuildingById(itemId) : getNodeById(itemId);
  if (!item) return;

  if (itemType === 'building') {
    const left = Number(item.x) || 120;
    const top = Number(item.y) || 120;
    const right = left + (Number(item.width) || 220);
    const bottom = top + (Number(item.height) || 180);
    dragState = {
      kind: 'resize-building',
      itemId,
      startPos,
      startRect: { left, right, top, bottom },
      anchor
    };
    state.selectedBuildingId = itemId;
    rememberSelectedBuilding(itemId);
    state.selectedNodeId = null;
    state.selectedIds = [itemId];
  } else {
    const width = Number(item.width) || 52;
    const height = Number(item.height) || 44;
    const left = item.x - width / 2;
    const top = item.y - height / 2;
    const right = left + width;
    const bottom = top + height;
    dragState = {
      kind: 'resize-node',
      itemId,
      startPos,
      startRect: { left, right, top, bottom },
      anchor
    };
    state.selectedNodeId = itemId;
    state.selectedIds = [itemId];
    state.selectedBuildingId = null;
  }

  setStatus(`${itemType === 'building' ? 'Building' : 'Room'} resizing`);
  render();
}

svg.addEventListener('wheel', handleScrollZoom, { passive: false });

svg.addEventListener('pointerdown', (event) => {
  if (state.mode === 'connect' || state.mode === 'add-line') {
    const resizeHandle = event.target.closest('[data-resize-handle="true"]');
    if (resizeHandle) {
      event.preventDefault();
      return;
    }
  }

  const resizeHandle = event.target.closest('[data-resize-handle="true"]');
  if (resizeHandle) {
    event.preventDefault();
    startResizeDrag(event, resizeHandle);
    return;
  }

  if (event.target.closest('[data-selection-group="true"]')) {
    event.preventDefault();
    startMultiSelectionDrag(event);
    return;
  }

  if (event.button === 2) {
    const clickedNode = event.target.closest('.node');
    const clickedBuilding = event.target.closest('[data-building-id]');
    const clickedLine = event.target.closest('.free-line');
    const clickedId = clickedNode?.dataset.id || clickedBuilding?.getAttribute('data-building-id') || clickedLine?.dataset.lineId;

    if (clickedId && state.selectedIds.length > 1 && state.selectedIds.includes(clickedId)) {
      event.preventDefault();
      const selectedItems = getBatchSelectionItems();
      if (selectedItems.length > 1) {
        showBatchPropertyPopup(event, selectedItems);
        return;
      }
    }

    event.preventDefault();
    clearSelection();
    const start = getNodePositionFromEvent(event);
    state.selectionBox = {
      x: start.x,
      y: start.y,
      width: 0,
      height: 0
    };
    dragState = {
      kind: 'selection-box',
      startX: start.x,
      startY: start.y,
      pointerId: event.pointerId,
      active: true
    };
    svg.setPointerCapture?.(event.pointerId);
    render();
    return;
  }

  if (state.mode === 'add-line') {
    const point = getNodePositionFromEvent(event);
    const snappedPoint = state.gridSnap
      ? snapLineEndpoint(point)
      : { x: point.x, y: point.y };
    if (!state.pendingLinePoint) {
      state.pendingLinePoint = { ...snappedPoint };
      state.pendingLineEnd = { ...snappedPoint };
      setStatus('Line start selected');
      render();
      return;
    }

    addFreeLine(state.pendingLinePoint, snappedPoint);
    state.pendingLinePoint = null;
    state.pendingLineEnd = null;
    setMode('select');
    return;
  }

  if (state.mode === 'add-room') {
    createRoomAtCursor(event);
    return;
  }

  if (state.mode === 'add-junction') {
    createJunctionAtCursor(event);
    return;
  }

  if (state.mode === 'add-stair') {
    createStairAtCursor(event);
    return;
  }

  const lineTarget = event.target.closest('.free-line');
  if (lineTarget) {
    const lineId = lineTarget.dataset.lineId;
    const line = getLineById(lineId);
    if (!line) return;

    if (event.button === 1 && state.selectedIds.includes(lineId)) {
      event.preventDefault();
      startMultiSelectionDrag(event);
      return;
    }

    if (event.ctrlKey || event.metaKey) {
      const nextSelection = new Set(state.selectedIds || []);
      if (nextSelection.has(lineId)) {
        nextSelection.delete(lineId);
      } else {
        nextSelection.add(lineId);
      }

      if (!nextSelection.size) {
        clearSelection();
        setStatus('Selection cleared');
        render();
        return;
      }

      state.selectedNodeId = [...nextSelection].find((id) => !!getNodeById(id)) || null;
      state.selectedBuildingId = [...nextSelection].find((id) => !!getBuildingById(id)) || null;
      state.selectedIds = [...nextSelection];
      state.selectedLineId = nextSelection.size === 1 && nextSelection.has(lineId) ? lineId : null;
      syncSelectedIds();
      setStatus(`${state.selectedIds.length} items selected`);
      render();
      return;
    }

    if (event.shiftKey) {
      const nextSelection = new Set(state.selectedIds || []);
      if (nextSelection.has(lineId)) {
        nextSelection.delete(lineId);
      } else {
        nextSelection.add(lineId);
      }

      state.selectedNodeId = null;
      state.selectedBuildingId = null;
      state.selectedIds = [...nextSelection];
      state.selectedLineId = nextSelection.size === 1 && nextSelection.has(lineId) ? lineId : null;
      syncSelectedIds();
      setStatus(nextSelection.size > 1 ? `${nextSelection.size} items selected` : 'Line selected');
      render();
      return;
    }

    selectLine(lineId);
    pushHistory();
    dragState = {
      kind: 'line',
      lineId,
      startPos: getNodePositionFromEvent(event),
      startLine: { x1: line.x1, y1: line.y1, x2: line.x2, y2: line.y2 }
    };
    setStatus('Line selected');
    render();
    return;
  }

  const nodeTarget = event.target.closest('.node');
  if (nodeTarget) {
    const nodeId = nodeTarget.dataset.id;
    const node = getNodeById(nodeId);
    if (!node || !matchesSelectionMode(node)) return;

    if (event.button === 1 && state.selectedIds.includes(nodeId)) {
      event.preventDefault();
      startMultiSelectionDrag(event);
      return;
    }

    if (state.mode === 'select' || state.mode === 'select-room' || state.mode === 'select-junction' || state.mode === 'select-building') {
      if (event.ctrlKey || event.metaKey) {
        const nextSelection = new Set(state.selectedIds || []);
        if (nextSelection.has(nodeId)) {
          nextSelection.delete(nodeId);
        } else {
          nextSelection.add(nodeId);
        }

        if (!nextSelection.size) {
          clearSelection();
          setStatus('Selection cleared');
          render();
          return;
        }

        state.selectedBuildingId = null;
        state.selectedNodeId = [...nextSelection].find((id) => !!getNodeById(id)) || null;
        state.selectedLineId = [...nextSelection].find((id) => !!getLineById(id)) || null;
        state.selectedIds = [...nextSelection];
        syncSelectedIds();
        setStatus(`${state.selectedIds.length} items selected`);
        render();
        return;
      }

      pushHistory();
      dragState = {
        kind: 'node',
        nodeId,
        offsetX: event.clientX - nodeTarget.getBoundingClientRect().left,
        offsetY: event.clientY - nodeTarget.getBoundingClientRect().top
      };
      handleNodeClick(nodeId, event);
    } else {
      handleNodeClick(nodeId, event);
    }
    return;
  }

  const buildingTarget = event.target.closest('[data-building-id]');
  if (buildingTarget) {
    if (state.mode !== 'select' && state.mode !== 'select-building' && state.mode !== 'connect') {
      setMode('select');
    }
    if (state.mode !== 'select' && state.mode !== 'select-building' && state.mode !== 'connect') {
      return;
    }
    const buildingId = buildingTarget.getAttribute('data-building-id');
    if (event.button === 1 && state.selectedIds.includes(buildingId)) {
      event.preventDefault();
      startMultiSelectionDrag(event);
      return;
    }
    if (event.ctrlKey || event.metaKey) {
      const nextSelection = new Set(state.selectedIds || []);
      if (nextSelection.has(buildingId)) {
        nextSelection.delete(buildingId);
      } else {
        nextSelection.add(buildingId);
      }

      if (!nextSelection.size) {
        clearSelection();
        setStatus('Selection cleared');
        render();
        return;
      }

      state.selectedNodeId = [...nextSelection].find((id) => !!getNodeById(id)) || null;
      state.selectedBuildingId = [...nextSelection].find((id) => !!getBuildingById(id)) || null;
      state.selectedLineId = [...nextSelection].find((id) => !!getLineById(id)) || null;
      state.selectedIds = [...nextSelection];
      syncSelectedIds();
      setStatus(`${state.selectedIds.length} items selected`);
      render();
      return;
    }
    startBuildingDrag(buildingId, event);
    return;
  }

  const hasVisibleSelection = !!(state.selectedIds.length || state.selectedNodeId || state.selectedBuildingId);
  const clickedEmptyCanvas = !event.target.closest('.node, [data-building-id], [data-selection-group="true"]');

  if (state.mode === 'connect' && clickedEmptyCanvas) {
    state.pendingConnectId = null;
    clearSelection();
    render();
    return;
  }

  const shouldClearSelectionOnCanvasClick = state.mode === 'select' && hasVisibleSelection && clickedEmptyCanvas && !event.shiftKey && !event.altKey && event.button !== 1;

  if (shouldClearSelectionOnCanvasClick) {
    clearSelection();
    render();
    return;
  }

  const canPan = (state.mode === 'select' || state.mode === 'connect') && (event.button === 0 || event.button === 1 || event.shiftKey || event.altKey);
  if (canPan) {
    panState = {
      startX: event.clientX,
      startY: event.clientY,
      originPanX: state.panX,
      originPanY: state.panY
    };
    svg.setPointerCapture?.(event.pointerId);
    applyCanvasTransform();
    return;
  }

  if (state.mode === 'add-room') {
    createRoomAtCursor(event);
  } else if (state.mode === 'add-junction') {
    createJunctionAtCursor(event);
  } else if (state.mode === 'add-stair') {
    createStairAtCursor(event);
  } else if (state.mode === 'select') {
    clearSelection();
    render();
  }
});

svg.addEventListener('pointermove', (event) => {
  if (panState) {
    state.panX = panState.originPanX + (event.clientX - panState.startX);
    state.panY = panState.originPanY + (event.clientY - panState.startY);
    applyCanvasTransform();
    return;
  }

  if (dragState && dragState.kind === 'selection-box') {
    if (!dragState.active || event.buttons !== 2) {
      if (dragState.active) {
        finalizeSelectionBox(event);
      }
      return;
    }

    const current = getNodePositionFromEvent(event);
    state.selectionBox = {
      x: dragState.startX,
      y: dragState.startY,
      width: current.x - dragState.startX,
      height: current.y - dragState.startY
    };
    render();
    return;
  }

  if (state.mode === 'add-line' && state.pendingLinePoint) {
    const point = getNodePositionFromEvent(event);
    state.pendingLineEnd = state.gridSnap
      ? snapLineEndpoint(point)
      : point;
    render();
    return;
  }

  if (!dragState) return;

  if (dragState.kind === 'resize-line') {
    const currentPos = getNodePositionFromEvent(event);
    const line = getLineById(dragState.itemId);
    if (!line) return;

    const dx = currentPos.x - dragState.startPos.x;
    const dy = currentPos.y - dragState.startPos.y;

    if (dragState.anchor === 'start') {
      line.x1 = dragState.startLine.x1 + dx;
      line.y1 = dragState.startLine.y1 + dy;
      const snappedStart = snapLineEndpoint({ x: line.x1, y: line.y1 }, line.id);
      line.x1 = snappedStart.x;
      line.y1 = snappedStart.y;
    } else {
      line.x2 = dragState.startLine.x2 + dx;
      line.y2 = dragState.startLine.y2 + dy;
      const snappedEnd = snapLineEndpoint({ x: line.x2, y: line.y2 }, line.id);
      line.x2 = snappedEnd.x;
      line.y2 = snappedEnd.y;
    }

    render();
    return;
  }

  if (dragState.kind === 'resize-node') {
    const currentPos = getNodePositionFromEvent(event);
    const node = getNodeById(dragState.itemId);
    if (!node) return;

    const start = dragState.startRect;
    let left = start.left;
    let right = start.right;
    let top = start.top;
    let bottom = start.bottom;

    if (dragState.anchor.includes('e')) right = Math.max(currentPos.x, left + 20);
    if (dragState.anchor.includes('w')) left = Math.min(currentPos.x, right - 20);
    if (dragState.anchor.includes('s')) bottom = Math.max(currentPos.y, top + 20);
    if (dragState.anchor.includes('n')) top = Math.min(currentPos.y, bottom - 20);

    const width = clamp(Math.max(20, right - left), 20, MAX_CANVAS.width);
    const height = clamp(Math.max(20, bottom - top), 20, MAX_CANVAS.height);
    node.width = width;
    node.height = height;
    node.x = (left + right) / 2;
    node.y = (top + bottom) / 2;

    if (node.buildingId) {
      clampNodeToBuilding(node);
    }

    render();
    return;
  }

  if (dragState.kind === 'resize-building') {
    const currentPos = getNodePositionFromEvent(event);
    const building = getBuildingById(dragState.itemId);
    if (!building) return;

    const start = dragState.startRect;
    let left = start.left;
    let right = start.right;
    let top = start.top;
    let bottom = start.bottom;

    if (dragState.anchor.includes('e')) right = Math.max(currentPos.x, left + 80);
    if (dragState.anchor.includes('w')) left = Math.min(currentPos.x, right - 80);
    if (dragState.anchor.includes('s')) bottom = Math.max(currentPos.y, top + 80);
    if (dragState.anchor.includes('n')) top = Math.min(currentPos.y, bottom - 80);

    building.x = clamp(left, 20, MAX_CANVAS.width - 80);
    building.y = clamp(top, 20, MAX_CANVAS.height - 80);
    building.width = clamp(Math.max(80, right - left), 80, MAX_CANVAS.width - building.x - 20);
    building.height = clamp(Math.max(80, bottom - top), 80, MAX_CANVAS.height - building.y - 20);
    constrainBuildingToCanvas(building);

    const anchor = ensureBuildingAnchorNode(building);
    if (anchor) {
      anchor.x = (Number(building.x) || 120) + ((Number(building.width) || 220) / 2);
      anchor.y = (Number(building.y) || 120) + ((Number(building.height) || 180) / 2);
    }

    Object.values(state.nodes).forEach((node) => {
      if (node.buildingId === building.id) {
        clampNodeToBuilding(node);
      }
    });

    render();
    return;
  }

  if (dragState.kind === 'multi-selection') {
    const currentPos = getNodePositionFromEvent(event);
    const rawDx = currentPos.x - dragState.startPos.x;
    const rawDy = currentPos.y - dragState.startPos.y;
    const moveDx = state.gridSnap ? snapToGrid(rawDx) : rawDx;
    const moveDy = state.gridSnap ? snapToGrid(rawDy) : rawDy;

    Object.entries(dragState.snapshot).forEach(([id, start]) => {
      if (start.kind === 'node') {
        const node = getNodeById(id);
        if (!node) return;

        const buildingSelected = node.buildingId && dragState.snapshot[node.buildingId] && dragState.snapshot[node.buildingId].kind === 'building';
        if (buildingSelected) {
          return;
        }

        const targetX = start.x + moveDx;
        const targetY = start.y + moveDy;
        node.x = clamp(targetX, 10, MAX_CANVAS.width - 10);
        node.y = clamp(targetY, 10, MAX_CANVAS.height - 10);
        return;
      }

      if (start.kind === 'line') {
        const line = getLineById(id);
        if (!line) return;

        line.x1 = clamp(start.x1 + moveDx, 0, MAX_CANVAS.width);
        line.y1 = clamp(start.y1 + moveDy, 0, MAX_CANVAS.height);
        line.x2 = clamp(start.x2 + moveDx, 0, MAX_CANVAS.width);
        line.y2 = clamp(start.y2 + moveDy, 0, MAX_CANVAS.height);
        return;
      }

      const building = getBuildingById(id);
      if (!building) return;
      const proposedX = clamp(start.x + moveDx, 20, MAX_CANVAS.width - 80);
      const proposedY = clamp(start.y + moveDy, 20, MAX_CANVAS.height - 80);
      building.x = proposedX;
      building.y = proposedY;
      constrainBuildingToCanvas(building);
      const effectiveDx = building.x - start.x;
      const effectiveDy = building.y - start.y;
      const anchor = ensureBuildingAnchorNode(building);
      if (anchor) {
        anchor.x = (Number(building.x) || 120) + ((Number(building.width) || 220) / 2);
        anchor.y = (Number(building.y) || 120) + ((Number(building.height) || 180) / 2);
      }

      Object.values(state.nodes).forEach((node) => {
        if (node.buildingId === building.id) {
          const targetX = node.x + effectiveDx;
          const targetY = node.y + effectiveDy;
          node.x = clamp(targetX, 10, MAX_CANVAS.width - 10);
          node.y = clamp(targetY, 10, MAX_CANVAS.height - 10);
          clampNodeToBuilding(node);
        }
      });
    });

    render();
    return;
  }

  if (dragState.kind === 'line') {
    const currentPos = getNodePositionFromEvent(event);
    const line = getLineById(dragState.lineId);
    if (!line) return;

    const dx = currentPos.x - dragState.startPos.x;
    const dy = currentPos.y - dragState.startPos.y;
    line.x1 = dragState.startLine.x1 + dx;
    line.y1 = dragState.startLine.y1 + dy;
    line.x2 = dragState.startLine.x2 + dx;
    line.y2 = dragState.startLine.y2 + dy;

    if (state.gridSnap) {
      const snappedStart = snapLineEndpoint({ x: line.x1, y: line.y1 }, line.id);
      const snappedEnd = snapLineEndpoint({ x: line.x2, y: line.y2 }, line.id);
      line.x1 = snappedStart.x;
      line.y1 = snappedStart.y;
      line.x2 = snappedEnd.x;
      line.y2 = snappedEnd.y;
    }

    render();
    return;
  }

  if (dragState.kind === 'building') {
    const currentPos = getNodePositionFromEvent(event);
    const dx = currentPos.x - dragState.startPos.x;
    const dy = currentPos.y - dragState.startPos.y;

    const building = getBuildingById(dragState.buildingId);
    if (building) {
      const proposedX = clamp(snapToGrid(dragState.buildingStart.x + dx), 20, MAX_CANVAS.width - 80);
      const proposedY = clamp(snapToGrid(dragState.buildingStart.y + dy), 20, MAX_CANVAS.height - 80);
      building.x = proposedX;
      building.y = proposedY;
      constrainBuildingToCanvas(building);
      const effectiveDx = building.x - dragState.buildingStart.x;
      const effectiveDy = building.y - dragState.buildingStart.y;
      const anchor = ensureBuildingAnchorNode(building);
      if (anchor) {
        anchor.x = (Number(building.x) || 120) + ((Number(building.width) || 220) / 2);
        anchor.y = (Number(building.y) || 120) + ((Number(building.height) || 180) / 2);
      }

      Object.entries(dragState.nodeStartPositions).forEach(([nodeId, start]) => {
        const node = getNodeById(nodeId);
        if (!node) return;
        const targetX = start.x + effectiveDx;
        const targetY = start.y + effectiveDy;
        node.x = clamp(targetX, 10, MAX_CANVAS.width - 10);
        node.y = clamp(targetY, 10, MAX_CANVAS.height - 10);
        const matchingBuilding = findBuildingForNode(node);
        if (matchingBuilding) {
          assignNodeToBuilding(node, matchingBuilding);
        }
        if (node.buildingId) {
          clampNodeToBuilding(node);
        }
      });

      state.lines.forEach((line) => {
        if (line.buildingId !== building.id) return;
        line.x1 = clamp((Number(line.x1) || 0) + effectiveDx, 0, MAX_CANVAS.width);
        line.y1 = clamp((Number(line.y1) || 0) + effectiveDy, 0, MAX_CANVAS.height);
        line.x2 = clamp((Number(line.x2) || 0) + effectiveDx, 0, MAX_CANVAS.width);
        line.y2 = clamp((Number(line.y2) || 0) + effectiveDy, 0, MAX_CANVAS.height);
      });
    }

    render();
    return;
  }

  const node = getNodeById(dragState.nodeId);
  if (!node) return;

  const pos = getNodePositionFromEvent(event);
  snapNodePosition(node, pos.x, pos.y);
  const matchingBuilding = findBuildingForNode(node);
  if (matchingBuilding) {
    assignNodeToBuilding(node, matchingBuilding);
  }
  if (node.buildingId) {
    clampNodeToBuilding(node);
  }
  render();
});

svg.addEventListener('pointerup', (event) => {
  if (dragState && dragState.kind === 'selection-box') {
    finalizeSelectionBox(event);
    return;
  }

  if (dragState && (dragState.kind === 'resize-node' || dragState.kind === 'resize-building' || dragState.kind === 'resize-line' || dragState.kind === 'line')) {
    dragState = null;
    saveLayoutToLocalStorage();
    return;
  }

  dragState = null;
  panState = null;
  applyCanvasTransform();
  saveLayoutToLocalStorage();
});

window.addEventListener('pointerup', (event) => {
  if (dragState && dragState.kind === 'selection-box') {
    finalizeSelectionBox(event);
  }
});

svg.addEventListener('pointercancel', () => {
  if (dragState && dragState.kind === 'selection-box') {
    state.selectionBox = null;
    dragState = null;
    render();
  }
});

svg.addEventListener('contextmenu', (event) => {
  const clickedNode = event.target.closest('.node');
  const clickedBuilding = event.target.closest('[data-building-id]');
  const clickedLine = event.target.closest('.free-line');
  const clickedId = clickedNode?.dataset.id || clickedBuilding?.getAttribute('data-building-id') || clickedLine?.dataset.lineId;

  if (clickedId && state.selectedIds.length > 1 && state.selectedIds.includes(clickedId)) {
    const selectedItems = getBatchSelectionItems();
    if (selectedItems.length > 1) {
      event.preventDefault();
      showBatchPropertyPopup(event, selectedItems);
      return;
    }
  }

  event.preventDefault();
});

window.addEventListener('click', (event) => {
  const popup = document.getElementById('batchPropertyPopup');
  if (!popup || popup.style.display === 'none') return;
  const clickedInsidePopup = popup.contains(event.target);
  if (!clickedInsidePopup && !event.target.closest('[data-building-id]') && !event.target.closest('.node')) {
    hideBatchPropertyPopup();
  }
});

window.addEventListener('keydown', (event) => {
  if (event.key === 'Escape') {
    hideBatchPropertyPopup();
  }
});

svg.addEventListener('pointerleave', () => {
  if (dragState && dragState.kind === 'selection-box') {
    state.selectionBox = null;
    dragState = null;
    render();
    return;
  }

  dragState = null;
  panState = null;
  applyCanvasTransform();
  saveLayoutToLocalStorage();
});

function applyBorderSettingsFromControls() {
  const selectedNode = state.selectedNodeId ? getNodeById(state.selectedNodeId) : null;
  const activeTarget = selectedNode || (state.selectedBuildingId ? getBuildingById(state.selectedBuildingId) : null);

  if (!activeTarget) return;

  if (!buildingBorderToggle || !buildingBorderWidthInput) return;
  activeTarget.borderEnabled = !!buildingBorderToggle.checked;
  activeTarget.borderWidth = clamp(Number(buildingBorderWidthInput.value) || 0, 0, 12);
  if (activeTarget.borderEnabled && activeTarget.borderWidth < 1) {
    activeTarget.borderWidth = 1;
  }

  render();
}

document.getElementById('duplicateNodeBtn').addEventListener('click', duplicateSelectedNode);

// ---- Save to server: writes the current map to map.json, which map.php reads on every load. ----
const SERVER_SAVE_URL = 'save_map.php';
const serverSaveBtn = document.getElementById('serverSaveBtn');
let serverSaveInFlight = false;

async function saveLayoutToServer() {
  if (serverSaveInFlight) return;
  serverSaveInFlight = true;
  if (serverSaveBtn) serverSaveBtn.disabled = true;
  setStatus('Saving to server...');

  try {
    const layout = getLayoutExportObject();
    // Keep the browser copy in step as well, so a reload never shows older work.
    try { localStorage.setItem(STORAGE_KEY, JSON.stringify(layout, null, 2)); } catch (storageError) { /* optional */ }

    const response = await fetch(SERVER_SAVE_URL, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      cache: 'no-store',
      body: JSON.stringify(layout, null, 2)
    });

    let result = null;
    try { result = await response.json(); } catch (parseError) { /* handled below */ }
    if (!response.ok || !result || !result.ok) {
      throw new Error((result && result.error) || `Server responded with ${response.status}`);
    }
    showFeedbackToast('Current map is saved and in use.', 'success');
  } catch (error) {
    console.error('Save to server failed:', error);
    setStatus('Save failed');
    showFeedbackToast(`Could not save map.json. ${error.message}`, 'error');
    window.alert(`Could not save map.json.\n\n${error.message}\n\nOpen the editor through your PHP server (not as a file) and make sure save_map.php can write to its folder.`);
  } finally {
    serverSaveInFlight = false;
    if (serverSaveBtn) serverSaveBtn.disabled = false;
  }
}

if (serverSaveBtn) {
  serverSaveBtn.addEventListener('click', saveLayoutToServer);
}

document.addEventListener('keydown', (event) => {
  if ((event.ctrlKey || event.metaKey) && !event.shiftKey && !event.altKey && String(event.key).toLowerCase() === 's') {
    event.preventDefault();
    saveLayoutToServer();
  }
});
document.getElementById('undoBtn').addEventListener('click', undoLastAction);
document.getElementById('redoBtn').addEventListener('click', redoLastAction);
if (buildingBorderToggle) {
  buildingBorderToggle.addEventListener('change', applyBorderSettingsFromControls);
}
if (multiJunctionToggle) {
  multiJunctionToggle.addEventListener('change', () => {
    applyNodeProperties();
  });
}
if (disableDestinationToggle) {
  disableDestinationToggle.addEventListener('change', () => {
    applyNodeProperties();
  });
}
if (hideDisplayToggle) {
  hideDisplayToggle.addEventListener('change', () => {
    applyNodeProperties();
  });
}
if (floorConnectorToggle) {
  floorConnectorToggle.addEventListener('change', () => {
    applyNodeProperties();
  });
}
if (buildingBorderWidthInput) {
  buildingBorderWidthInput.addEventListener('change', applyBorderSettingsFromControls);
}
const applyPropsBtn = document.getElementById('applyPropsBtn');
if (applyPropsBtn) {
  applyPropsBtn.addEventListener('click', applyNodeProperties);
}

const moveToFrontBtn = document.getElementById('moveToFrontBtn');
if (moveToFrontBtn) {
  moveToFrontBtn.addEventListener('click', moveSelectedNodesToFront);
}

const moveToBackBtn = document.getElementById('moveToBackBtn');
if (moveToBackBtn) {
  moveToBackBtn.addEventListener('click', moveSelectedNodesToBack);
}

if (globalFloorSelect) {
  globalFloorSelect.addEventListener('change', () => {
    state.activeFloor = normalizeFloorValue(globalFloorSelect.value, 1);
    state.buildings.forEach((building) => {
      const maxFloor = normalizeFloorCount(building.floors, 1);
      state.buildingFloorState[building.id] = clamp(state.activeFloor, 1, maxFloor);
    });
    render();
  });
}

const groupHelpBtn = document.getElementById('groupHelpBtn');
if (groupHelpBtn) {
  const showGroupHelpTooltip = () => groupHelpBtn.setAttribute('data-visible', 'true');
  const hideGroupHelpTooltip = () => groupHelpBtn.removeAttribute('data-visible');

  groupHelpBtn.addEventListener('mouseenter', showGroupHelpTooltip);
  groupHelpBtn.addEventListener('mouseleave', hideGroupHelpTooltip);
  groupHelpBtn.addEventListener('focus', showGroupHelpTooltip);
  groupHelpBtn.addEventListener('blur', hideGroupHelpTooltip);
  groupHelpBtn.addEventListener('click', (event) => {
    event.preventDefault();
    event.stopPropagation();
    const isVisible = groupHelpBtn.getAttribute('data-visible') === 'true';
    if (isVisible) {
      hideGroupHelpTooltip();
    } else {
      showGroupHelpTooltip();
    }
  });
  document.addEventListener('click', (event) => {
    if (event.target !== groupHelpBtn && !groupHelpBtn.contains(event.target)) {
      hideGroupHelpTooltip();
    }
  });
}

function enableDecimalNumberInputs(inputs) {
  (inputs || []).forEach((input) => {
    if (!input || input.type !== 'number') return;
    input.setAttribute('step', 'any');
    input.setAttribute('inputmode', 'decimal');
  });
}

const propertyInputs = [
  mapWidthInput,
  mapHeightInput,
  labelInput,
  groupInput,
  tagsInput,
  labelSizeInput,
  typeColorInput,
  fontColorInput,
  buildingWidthInput,
  buildingHeightInput,
  junctionXInput,
  junctionYInput,
  stairDesignInput,
  lineThicknessInput,
  buildingBorderWidthInput,
  buildingNameInput
].filter(Boolean);
enableDecimalNumberInputs(propertyInputs);
// Property fields are only committed with the Apply button or the Enter key.
// They are deliberately NOT applied on input/change/blur: clicking another item
// changes the selection before the field blurs, which used to push half-typed
// edits onto the newly clicked item.
propertyInputs.forEach((input) => {
  input.addEventListener('keydown', saveInputOnEnter);
  if (input === groupInput) {
    input.addEventListener('input', () => {
      const currentValue = input.value.trim();
      const isCompleteGroupValue = /^([a-z0-9_]+(?:\s+[a-z0-9_]+)*)\s*\([A-Za-z0-9][A-Za-z0-9\s-]*\)$/.test(currentValue.toLowerCase());
      if (isCompleteGroupValue) {
        const normalized = normalizeGroupValue(currentValue);
        if (normalized && input.value !== normalized) {
          input.value = normalized;
        }
      }
    });
  }
});

document.getElementById('deleteSelectedBtn').addEventListener('click', () => {
  if (state.selectedIds.length) {
    deleteSelectedItems();
    return;
  }
  deleteNode(state.selectedNodeId);
});
zoomLabel.addEventListener('focus', () => zoomLabel.select());
zoomLabel.addEventListener('input', () => {
  zoomLabel.value = zoomLabel.value.replace(/[^\d.%]/g, '').replace(/(\..*)\./g, '$1');
  if (zoomLabel.value.includes('%') && zoomLabel.value.indexOf('%') !== zoomLabel.value.length - 1) {
    zoomLabel.value = zoomLabel.value.replace(/%/g, '');
  }
});
zoomLabel.addEventListener('keydown', (event) => {
  if (event.key === 'Enter') {
    event.preventDefault();
    applyZoomFromInput();
    zoomLabel.blur();
  }

  if (event.key === 'Escape') {
    updateZoomLabel();
    zoomLabel.blur();
  }
});
zoomLabel.addEventListener('blur', applyZoomFromInput);
if (toggleJunctionsBtn) {
  toggleJunctionsBtn.addEventListener('click', toggleJunctionAndConnectionVisibility);
}

document.getElementById('zoomInBtn').addEventListener('click', () => zoomCanvas(0.1));
document.getElementById('zoomOutBtn').addEventListener('click', () => zoomCanvas(-0.1));

document.getElementById('applyMapSizeBtn').addEventListener('click', () => {
  applyMapSize(mapWidthInput.value, mapHeightInput.value);
});

document.getElementById('resetBtn').addEventListener('click', () => {
  const layout = createBlankLayout();
  state.nodes = layout.nodes;
  state.edges = layout.edges;
  state.buildings = layout.buildings;
  state.selectedNodeId = null;
  state.pendingConnectId = null;
  state.selectedBuildingId = null;
  state.panX = 0;
  state.panY = 0;
  state.zoom = 1;
  resetCanvasSizeToDefault();
  render();
  saveLayoutToLocalStorage();
  setStatus('Canvas cleared');
});

document.getElementById('exportJsonBtn').addEventListener('click', () => {
  const jsonText = updateOutputBox();
  const blob = new Blob([jsonText], { type: 'application/json' });
  const url = URL.createObjectURL(blob);
  const link = document.createElement('a');
  link.href = url;
  link.download = `campus-map-layout-${Date.now()}.json`;
  document.body.appendChild(link);
  link.click();
  link.remove();
  URL.revokeObjectURL(url);
  setStatus('JSON exported');
});

function importLayoutFromJsonText(rawText) {
  const raw = String(rawText || '').trim();
  if (!raw) {
    setStatus('Nothing to import');
    return;
  }

  try {
    let importedData = null;
    if (raw.startsWith('{') || raw.startsWith('[')) {
      importedData = JSON.parse(raw);
    } else if (raw.includes('const NODES') || raw.includes('const EDGES')) {
      const matchNodes = raw.match(/const\s+NODES\s*=\s*({[\s\S]*?});?/);
      const matchEdges = raw.match(/const\s+EDGES\s*=\s*(\[[\s\S]*?\]);?/);
      if (matchNodes && matchEdges) {
        importedData = {
          nodes: Function(`return (${matchNodes[1]});`)(),
          edges: Function(`return (${matchEdges[1]});`)()
        };
      }
    }

    if (!importedData) {
      setStatus('Invalid JSON layout');
      return;
    }

    const normalized = normalizeLayout(importedData);
    state.nodes = normalized.nodes;
    state.edges = normalized.edges;
    state.lines = normalized.lines || [];
    state.buildings = normalized.buildings;
    state.typeColors = { ...INITIAL_TYPE_COLORS, ...(normalized.typeColors || {}) };
    state.selectedBuildingId = null;
    state.selectedNodeId = null;
    state.selectedLineId = null;
    state.selectedIds = [];
    if (importedData.canvasSize && importedData.canvasSize.width && importedData.canvasSize.height) {
      MAX_CANVAS = {
        width: clamp(Number(importedData.canvasSize.width) || DEFAULT_CANVAS_SIZE.width, 300, 5000),
        height: clamp(Number(importedData.canvasSize.height) || DEFAULT_CANVAS_SIZE.height, 300, 5000)
      };
      svg.setAttribute('viewBox', `0 0 ${MAX_CANVAS.width} ${MAX_CANVAS.height}`);
      svg.setAttribute('width', '100%');
      svg.setAttribute('height', '100%');
      svg.style.width = '100%';
      svg.style.height = '100%';
      mapWidthInput.value = String(MAX_CANVAS.width);
      mapHeightInput.value = String(MAX_CANVAS.height);
    }

    render();
    saveLayoutToLocalStorage();
    setStatus('Layout imported');
  } catch (error) {
    console.error('Failed to import layout:', error);
    setStatus('Import failed');
  }
}

document.getElementById('importJsonBtn').addEventListener('click', () => {
  jsonFileInput.value = '';
  jsonFileInput.click();
});

jsonFileInput.addEventListener('change', async (event) => {
  const file = event.target.files && event.target.files[0];
  if (!file) return;

  try {
    const raw = await file.text();
    importLayoutFromJsonText(raw);
  } catch (error) {
    console.error('Failed to read JSON file:', error);
    setStatus('Could not read selected file');
  }
});

if (buildingsCollapseBtn) {
  buildingsCollapseBtn.addEventListener('click', () => toggleBuildingsPanel());
}

const addBuildingBtn = document.getElementById('addBuildingBtn');
if (addBuildingBtn) {
  addBuildingBtn.addEventListener('click', () => {
    const name = buildingNameInput ? buildingNameInput.value.trim() : '';
    setMode('add-building');
    addBuilding(name);
    setMode('select');
    if (buildingNameInput) {
      buildingNameInput.value = '';
    }
  });
}

const updateBuildingBtn = document.getElementById('updateBuildingBtn');
if (updateBuildingBtn) {
  updateBuildingBtn.addEventListener('click', updateSelectedBuilding);
}

const deleteBuildingBtn = document.getElementById('deleteBuildingBtn');
if (deleteBuildingBtn) {
  deleteBuildingBtn.addEventListener('click', () => {
    if (!state.selectedBuildingId) {
      setStatus('Select a building first');
      return;
    }
    removeBuilding(state.selectedBuildingId);
  });
}

buildingList.addEventListener('click', (event) => {
  const deleteButton = event.target.closest('[data-build-delete]');
  if (deleteButton) {
    removeBuilding(deleteButton.dataset.buildDelete);
    return;
  }

  const colorInput = event.target.closest('[data-build-color]');
  if (colorInput) {
    state.selectedBuildingId = colorInput.dataset.buildColor;
    rememberSelectedBuilding(state.selectedBuildingId);
    return;
  }

  const nameInput = event.target.closest('[data-build-name]');
  if (nameInput) {
    state.selectedBuildingId = nameInput.dataset.buildName;
    rememberSelectedBuilding(state.selectedBuildingId);
    return;
  }

  const item = event.target.closest('[data-build-item]');
  if (item) {
    state.selectedNodeId = null;
    state.selectedLineId = null;
    state.selectedBuildingId = item.dataset.buildItem;
    rememberSelectedBuilding(state.selectedBuildingId);
    state.selectedIds = [state.selectedBuildingId];
    render();
  }
});

function handleBuildingColorChange(event) {
  const colorInput = event.target.closest('[data-build-color]');
  if (!colorInput) return;

  const buildingId = colorInput.dataset.buildColor;
  const building = getBuildingById(buildingId);
  if (!building) return;

  state.selectedNodeId = null;
  state.selectedLineId = null;
  state.selectedBuildingId = buildingId;
  rememberSelectedBuilding(buildingId);
  state.selectedIds = [buildingId];
  building.color = colorInput.value;

  const anchor = ensureBuildingAnchorNode(building);
  if (anchor) {
    anchor.buildingName = building.name;
    anchor.color = building.color;
  }

  if (event.type === 'change') {
    pushHistory();
    render();
  }
}

function handleBuildingNameChange(event) {
  const nameInput = event.target.closest('[data-build-name]');
  if (!nameInput) return;

  const buildingId = nameInput.dataset.buildName;
  const building = getBuildingById(buildingId);
  if (!building) return;

  state.selectedNodeId = null;
  state.selectedLineId = null;
  state.selectedBuildingId = buildingId;
  rememberSelectedBuilding(buildingId);
  state.selectedIds = [buildingId];
  building.name = normalizeLabelText(nameInput.value, building.name);

  const anchor = ensureBuildingAnchorNode(building);
  if (anchor) {
    anchor.label = building.name;
    anchor.buildingName = building.name;
  }

  if (event.type === 'change') {
    pushHistory();
    render();
  }
}

buildingList.addEventListener('input', handleBuildingColorChange);
buildingList.addEventListener('change', handleBuildingColorChange);
buildingList.addEventListener('input', handleBuildingNameChange);
buildingList.addEventListener('change', handleBuildingNameChange);

if (buildingSelect) {
  buildingSelect.addEventListener('change', (event) => {
    state.selectedNodeId = null;
    state.selectedLineId = null;
    state.selectedBuildingId = event.target.value || null;
    rememberSelectedBuilding(state.selectedBuildingId);
    state.selectedIds = state.selectedBuildingId ? [state.selectedBuildingId] : [];
    const building = getBuildingById(state.selectedBuildingId);
    if (building) {
      buildingNameInput.value = building.name;
    }
    render();
  });
}

nodeBuildingSelect.addEventListener('change', (event) => {
  const buildingId = event.target.value || null;
  const selectedNodeIds = [...new Set([
    ...(state.selectedNodeId ? [state.selectedNodeId] : []),
    ...(Array.isArray(state.selectedIds) ? state.selectedIds : [])
  ])].filter((id) => !!getNodeById(id));

  if (!selectedNodeIds.length) {
    return;
  }

  const hasNonJunctionSelection = selectedNodeIds.some((id) => getNodeById(id)?.type !== 'junction');
  if (!buildingId && hasNonJunctionSelection) {
    event.target.value = selectedNodeIds.map((id) => getNodeById(id)?.buildingId || '').find(Boolean) || '';
    setStatus('Only junctions can be set to no building');
    return;
  }

  assignSelectedNodeToBuilding(buildingId);
});

document.querySelectorAll('.tool-btn').forEach((btn) => {
  if (!btn.getAttribute('title')) {
    const buttonMap = {
      duplicateNodeBtn: 'Duplicate selected',
      centerViewBtn: 'Center view (Space)',
      exportJsonBtn: 'Export JSON',
      importJsonBtn: 'Import JSON',
      undoBtn: 'Undo (Ctrl+Z)',
      redoBtn: 'Redo (Ctrl+Y)'
    };

    if (buttonMap[btn.id]) {
      btn.title = buttonMap[btn.id];
    }
  }

  btn.addEventListener('click', () => {
    if (btn.dataset.mode === 'delete') {
      if (state.selectedIds.length || state.selectedNodeId || state.selectedBuildingId) {
        deleteSelectedItems();
        setMode('select');
        return;
      }
    }
    if (!btn.dataset.mode) {
      return;
    }
    setMode(btn.dataset.mode);
  });
});

gridSnapToggle.addEventListener('change', (event) => {
  state.gridSnap = event.target.checked;
  render();
});

if (toggleSidebarBtn) {
  toggleSidebarBtn.addEventListener('click', toggleSidebarNavigation);
}

if (toggleTopNavBtn) {
  toggleTopNavBtn.addEventListener('click', toggleTopNavigation);
}

if (sidebarRevealBtn) {
  sidebarRevealBtn.addEventListener('click', () => {
    appShell.classList.remove('sidebar-hidden');
    updateNavigationToggleButtons();
    updateEdgeRevealButtons();
  });
}

if (topRevealBtn) {
  topRevealBtn.addEventListener('click', () => {
    appShell.classList.remove('topbar-hidden');
    updateNavigationToggleButtons();
    updateEdgeRevealButtons();
  });
}

document.addEventListener('mousemove', handleEdgeRevealMouseMove);

window.addEventListener('load', async () => {
  updateNavigationToggleButtons();
  updateEdgeRevealButtons();

  const blank = createBlankLayout();
  state.nodes = blank.nodes;
  state.edges = blank.edges;
  state.lines = blank.lines;
  state.buildings = blank.buildings;
  state.selectedNodeId = null;
  state.pendingConnectId = null;
  state.selectedBuildingId = null;
  state.typeColors = { ...INITIAL_TYPE_COLORS };
  state.mode = 'select';
  if (gridSnapToggle) gridSnapToggle.checked = state.gridSnap;
  resetCanvasSizeToDefault();

  await loadCurrentProjectLayout();

  if (mapWidthInput) mapWidthInput.value = String(MAX_CANVAS.width);
  if (mapHeightInput) mapHeightInput.value = String(MAX_CANVAS.height);
  svg.setAttribute('viewBox', `0 0 ${MAX_CANVAS.width} ${MAX_CANVAS.height}`);
  svg.setAttribute('width', '100%');
  svg.setAttribute('height', '100%');
  svg.style.width = '100%';
  svg.style.height = '100%';
  updateNavigationToggleButtons();
  render();
  updateZoomLabel();
});

window.addEventListener('beforeunload', () => {
  saveLayoutToLocalStorage();
});

window.addEventListener('keydown', (event) => {
  const isEditingText = event.target && ['INPUT', 'TEXTAREA', 'SELECT'].includes(event.target.tagName);
  if (isEditingText) return;

  const key = String(event.key || '').toLowerCase();
  const modifierKey = event.ctrlKey || event.metaKey;

  if (modifierKey) {
    if (key === 'a') {
      const activeTag = document.activeElement && document.activeElement.tagName;
      if (!activeTag || !['INPUT', 'TEXTAREA', 'SELECT'].includes(activeTag)) {
        event.preventDefault();
        const ids = [
          ...Object.keys(state.nodes),
          ...state.buildings.map((building) => building.id),
          ...state.lines.map((line) => line.id)
        ].filter(Boolean);

        if (!ids.length) {
          setStatus('No items to select');
          return;
        }

        lastCtrlAKeyTime = Date.now();
        state.selectedNodeId = null;
        state.selectedBuildingId = null;
        state.selectedLineId = null;
        state.selectedIds = [...new Set(ids)];
        syncSelectedIds();
        setStatus(`${state.selectedIds.length} items selected`);
        render();
      }
      return;
    }

    if (['j', 'r', 'b', 's'].includes(key) && Date.now() - lastCtrlAKeyTime < 700) {
      event.preventDefault();
      const typeName = key === 'j' ? 'junction' : key === 'r' ? 'room' : key === 'b' ? 'building' : 'stair';
      selectAllByType(typeName);
      return;
    }

    if (key === 'z') {
      event.preventDefault();
      if (event.shiftKey) {
        redoLastAction();
      } else {
        undoLastAction();
      }
      return;
    }

    if (key === 'y') {
      event.preventDefault();
      redoLastAction();
      return;
    }

    if (key === 'c') {
      event.preventDefault();
      copySelectionToClipboard();
      return;
    }

    if (key === 'x') {
      event.preventDefault();
      cutSelectionToClipboard();
      return;
    }

    if (key === 'v') {
      event.preventDefault();
      pasteSelectionFromClipboard();
      return;
    }

    if (key === 'j') {
      event.preventDefault();
      duplicateSelectedNode();
      return;
    }
  }

  if (!modifierKey) {
    if (key === 's') {
      event.preventDefault();
      setMode('select');
      return;
    }

    if (key === 'b') {
      event.preventDefault();
      const name = buildingNameInput ? buildingNameInput.value.trim() : '';
      setMode('add-building');
      addBuilding(name);
      setMode('select');
      if (buildingNameInput) {
        buildingNameInput.value = '';
      }
      return;
    }

    if (key === 'r') {
      event.preventDefault();
      setMode('add-room');
      return;
    }

    if (key === 'j') {
      event.preventDefault();
      setMode('add-junction');
      return;
    }

    if (key === 'f') {
      event.preventDefault();
      setMode('add-stair');
      return;
    }

    if (key === 'l') {
      event.preventDefault();
      setMode('add-line');
      return;
    }

    if (key === 'c') {
      event.preventDefault();
      setMode('connect');
      return;
    }

  }

  const hasSelection = !!(state.selectedIds.length || state.selectedNodeId || state.selectedLineId || state.selectedBuildingId);
  if (!hasSelection) return;

  const selected = state.selectedNodeId ? getNodeById(state.selectedNodeId) : null;
  const selectedLine = state.selectedLineId ? getLineById(state.selectedLineId) : null;
  const selectedBuilding = state.selectedBuildingId ? getBuildingById(state.selectedBuildingId) : null;

  const isDeleteKey = event.key === 'Delete' || event.key === 'Del' || event.key === 'Backspace' || event.code === 'Delete' || event.keyCode === 46 || event.which === 46;

  if (isDeleteKey) {
    deleteSelectedItems();
    event.preventDefault();
    return;
  }

  if (selectedLine) {
    if (event.key === 'ArrowUp') {
      selectedLine.y1 = clamp(selectedLine.y1 - 10, 0, MAX_CANVAS.height);
      selectedLine.y2 = clamp(selectedLine.y2 - 10, 0, MAX_CANVAS.height);
      render();
    }

    if (event.key === 'ArrowDown') {
      selectedLine.y1 = clamp(selectedLine.y1 + 10, 0, MAX_CANVAS.height);
      selectedLine.y2 = clamp(selectedLine.y2 + 10, 0, MAX_CANVAS.height);
      render();
    }

    if (event.key === 'ArrowLeft') {
      selectedLine.x1 = clamp(selectedLine.x1 - 10, 0, MAX_CANVAS.width);
      selectedLine.x2 = clamp(selectedLine.x2 - 10, 0, MAX_CANVAS.width);
      render();
    }

    if (event.key === 'ArrowRight') {
      selectedLine.x1 = clamp(selectedLine.x1 + 10, 0, MAX_CANVAS.width);
      selectedLine.x2 = clamp(selectedLine.x2 + 10, 0, MAX_CANVAS.width);
      render();
    }
    return;
  }

  if (selectedBuilding) {
    const dx = (event.key === 'ArrowLeft' ? -10 : 0) + (event.key === 'ArrowRight' ? 10 : 0);
    const dy = (event.key === 'ArrowUp' ? -10 : 0) + (event.key === 'ArrowDown' ? 10 : 0);

    if (dx || dy) {
      const startX = Number(selectedBuilding.x) || 120;
      const startY = Number(selectedBuilding.y) || 120;
      selectedBuilding.x = clamp(snapToGrid(startX + dx), 20, MAX_CANVAS.width - 80);
      selectedBuilding.y = clamp(snapToGrid(startY + dy), 20, MAX_CANVAS.height - 80);
      constrainBuildingToCanvas(selectedBuilding);

      const anchor = ensureBuildingAnchorNode(selectedBuilding);
      if (anchor) {
        anchor.x = (Number(selectedBuilding.x) || 120) + ((Number(selectedBuilding.width) || 220) / 2);
        anchor.y = (Number(selectedBuilding.y) || 120) + ((Number(selectedBuilding.height) || 180) / 2);
      }

      Object.values(state.nodes).forEach((node) => {
        if (node.buildingId !== selectedBuilding.id) return;
        const targetX = node.x + dx;
        const targetY = node.y + dy;
        node.x = clamp(targetX, 10, MAX_CANVAS.width - 10);
        node.y = clamp(targetY, 10, MAX_CANVAS.height - 10);
        clampNodeToBuilding(node);
      });

      render();
      event.preventDefault();
    }
    return;
  }

  if (!selected) return;

  if (event.key === 'ArrowUp') {
    selected.y = clamp(selected.y - 10, 10, MAX_CANVAS.height - 10);
    render();
  }

  if (event.key === 'ArrowDown') {
    selected.y = clamp(selected.y + 10, 10, MAX_CANVAS.height - 10);
    render();
  }

  if (event.key === 'ArrowLeft') {
    selected.x = clamp(selected.x - 10, 10, MAX_CANVAS.width - 10);
    render();
  }

  if (event.key === 'ArrowRight') {
    selected.x = clamp(selected.x + 10, 10, MAX_CANVAS.width - 10);
    render();
  }
});