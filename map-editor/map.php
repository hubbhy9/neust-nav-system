<?php
$sessionSavePath = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'navigation_sessions';
if (!is_dir($sessionSavePath)) {
    @mkdir($sessionSavePath, 0777, true);
}
if (!is_dir($sessionSavePath) || !is_writable($sessionSavePath)) {
    $sessionSavePath = sys_get_temp_dir();
}
if (session_status() === PHP_SESSION_NONE) {
    session_save_path($sessionSavePath);
    session_start();
}
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: 0');
$mapOnly = isset($_GET['map_only']) && $_GET['map_only'] === '1';
$ionicMap = isset($_GET['ionic']) && $_GET['ionic'] === '1';
$guestMap = (isset($_GET['guest']) && $_GET['guest'] === '1') || !empty($_SESSION['is_guest']);
$defaultLayoutFile = file_exists(__DIR__ . '/map.json') ? 'map.json' : 'map-layout.json';
$layoutPath = __DIR__ . '/' . $defaultLayoutFile;
$layout = ['nodes' => [], 'edges' => [], 'buildings' => [], 'lines' => []];

if (!preg_match('/\.json$/i', $defaultLayoutFile) || !file_exists($layoutPath)) {
  $defaultLayoutFile = 'map-layout.json';
  $layoutPath = __DIR__ . '/' . $defaultLayoutFile;
}

if (file_exists($layoutPath)) {
  $raw = file_get_contents($layoutPath);
  $decoded = json_decode($raw, true);
  if (is_array($decoded)) {
    $layout = $decoded;
  }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Campus Map from JSON</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" crossorigin="anonymous" referrerpolicy="no-referrer" />
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

    html,
    body {
      height: 100%;
      overflow: hidden;
    }

    .app {
      display: flex;
      height: 100vh;
      height: 100dvh;
      min-height: 0;
    }

    .sidebar {
      width: 320px;
      flex: 0 0 320px;
      max-height: 100%;
      overflow-y: auto;
      box-sizing: border-box;
      padding: 18px;
      background: rgba(255, 255, 255, 0.95);
      border-right: 1px solid var(--border);
    }

    .panel {
      background: linear-gradient(180deg, rgba(255,255,255,0.98), rgba(244,247,255,0.96));
      border: 1px solid rgba(38, 99, 235, 0.12);
      border-radius: 18px;
      padding: 14px 14px 12px;
      margin-bottom: 16px;
      box-shadow: 0 12px 28px rgba(20, 37, 80, 0.08);
    }

    /* ---- Room / tag search ---- */
    .search-input-wrap {
      position: relative;
      display: flex;
      align-items: center;
    }

    .search-input-wrap .search-icon {
      position: absolute;
      right: 12px;
      color: var(--muted);
      font-size: 12px;
      pointer-events: none;
      z-index: 1;
    }

    .search-input-wrap input {
      width: 100%;
      padding-left: 12px;
      padding-right: 34px;
    }

    .search-clear {
      position: absolute;
      right: 30px;
      width: 24px;
      height: 24px;
      padding: 0;
      border-radius: 50%;
      border: 0;
      background: rgba(38, 99, 235, 0.08);
      color: var(--muted);
      font-size: 14px;
      line-height: 1;
      display: none;
      align-items: center;
      justify-content: center;
      box-shadow: none;
    }

    .search-clear.visible {
      display: inline-flex;
    }

    .search-results {
      list-style: none;
      margin: 6px 0 0;
      padding: 4px;
      max-height: 280px;
      overflow-y: auto;
      background: #ffffff;
      border: 1px solid rgba(38, 99, 235, 0.16);
      border-radius: 12px;
      box-shadow: 0 10px 22px rgba(20, 37, 80, 0.1);
    }

    .search-results[hidden] {
      display: none;
    }

    .search-result {
      display: flex;
      flex-direction: column;
      gap: 2px;
      padding: 8px 10px;
      border-radius: 8px;
      cursor: pointer;
      color: var(--text);
      font-weight: 700;
      font-size: 13px;
    }

    .search-result.active,
    .search-result:hover {
      background: rgba(38, 99, 235, 0.1);
    }

    .search-result mark {
      background: transparent;
      color: var(--primary);
      font-weight: 900;
    }

    .search-result-meta {
      display: flex;
      flex-wrap: wrap;
      gap: 6px;
      align-items: center;
      font-size: 11px;
      font-weight: 600;
      color: var(--muted);
    }

    .search-via {
      padding: 1px 7px;
      border-radius: 999px;
      background: rgba(20, 184, 166, 0.12);
      color: #0d8b7c;
      font-weight: 700;
    }

    .search-empty {
      padding: 10px;
      font-size: 12px;
      color: var(--muted);
      font-weight: 600;
    }

    .search-hint {
      margin-top: 6px;
      font-size: 11px;
      line-height: 1.4;
      color: var(--muted);
      font-weight: 600;
      min-height: 0;
    }

    .search-hint:empty {
      display: none;
    }

    .route-panel-header {
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 8px;
      margin-bottom: 12px;
      padding-bottom: 10px;
      border-bottom: 1px solid rgba(38, 99, 235, 0.1);
    }

    .route-panel-header .title {
      margin: 0;
      font-size: 12px;
      letter-spacing: 0.12em;
      text-transform: uppercase;
      color: var(--muted);
      font-weight: 800;
    }

    .route-badge {
      display: inline-flex;
      align-items: center;
      justify-content: center;
      min-width: 26px;
      height: 26px;
      padding: 0 8px;
      border-radius: 999px;
      background: linear-gradient(135deg, rgba(38,99,235,0.12), rgba(33,150,243,0.08));
      border: 1px solid rgba(38,99,235,0.16);
      color: var(--primary);
      font-size: 10px;
      font-weight: 800;
      letter-spacing: 0.08em;
    }

    .field {
      display: flex;
      flex-direction: column;
      margin-bottom: 10px;
      font-size: 12px;
      color: var(--muted);
      font-weight: 700;
    }

    .field label {
      margin-bottom: 6px;
      letter-spacing: 0.02em;
    }

    .field select,
    .field input {
      margin-top: 0;
      padding: 10px 12px;
      border-radius: 12px;
      border: 1px solid rgba(38, 99, 235, 0.12);
      background: rgba(255, 255, 255, 0.82);
      font: inherit;
      color: var(--text);
      box-shadow: inset 0 1px 2px rgba(17,24,39,0.02);
      transition: border-color 0.18s ease, box-shadow 0.18s ease;
    }

    .field select:focus,
    .field input:focus {
      outline: none;
      border-color: rgba(38, 99, 235, 0.4);
      box-shadow: 0 0 0 4px rgba(38, 99, 235, 0.08);
    }

    .sort-panel {
      display: flex;
      align-items: stretch;
      gap: 8px;
      margin-top: 2px;
      width: 100%;
    }

    .sort-option {
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 8px;
      flex: 1 1 0;
      min-width: 0;
      padding: 8px 8px;
      background: linear-gradient(180deg, rgba(255,255,255,0.9), rgba(244,247,255,0.96));
      border: 1px solid rgba(38, 99, 235, 0.12);
      border-radius: 12px;
      color: var(--text);
      transition: all 0.18s ease;
      cursor: pointer;
      min-height: 42px;
    }

    .sort-option:hover {
      transform: translateY(-1px);
      border-color: rgba(38, 99, 235, 0.2);
      box-shadow: 0 8px 18px rgba(38, 99, 235, 0.08);
    }

    .sort-option.active {
      background: linear-gradient(135deg, rgba(38,99,235,0.13), rgba(79,141,255,0.09));
      border-color: rgba(38, 99, 235, 0.32);
      box-shadow: inset 0 0 0 1px rgba(38,99,235,0.08);
    }

    .sort-main {
      display: inline-flex;
      align-items: center;
      justify-content: center;
      flex: 1 1 auto;
      min-width: 0;
      font-size: 12px;
      font-weight: 800;
      letter-spacing: 0.02em;
    }

    .sort-icon {
      display: inline-flex;
      align-items: center;
      justify-content: center;
      width: 22px;
      height: 22px;
      border-radius: 8px;
      background: rgba(38, 99, 235, 0.08);
      color: var(--primary);
      font-size: 11px;
      font-weight: 900;
      line-height: 1;
    }

    .sort-arrows {
      display: inline-flex;
      flex-direction: column;
      gap: 2px;
    }

    .sort-arrow {
      width: 20px;
      height: 16px;
      border: 0;
      border-radius: 6px;
      background: rgba(38, 99, 235, 0.08);
      color: var(--muted);
      font-size: 10px;
      line-height: 1;
      padding: 0;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      cursor: pointer;
      transition: all 0.15s ease;
    }

    .sort-arrow:hover {
      background: rgba(38, 99, 235, 0.18);
      color: var(--primary);
    }

    .sort-arrow.active {
      background: linear-gradient(135deg, #2663eb, #4d8dff);
      color: white;
      box-shadow: 0 8px 14px rgba(38, 99, 235, 0.16);
    }

    .sort-arrow:focus-visible,
    .sort-option:focus-visible {
      outline: 2px solid rgba(38, 99, 235, 0.28);
      outline-offset: 2px;
    }

    button {
      cursor: pointer;
      border: 1px solid rgba(38, 99, 235, 0.12);
      background: linear-gradient(180deg, #ffffff, #f4f8ff);
      border-radius: 12px;
      padding: 10px 12px;
      font: inherit;
      color: var(--text);
      font-weight: 700;
      transition: transform 0.16s ease, box-shadow 0.16s ease, border-color 0.16s ease;
    }

    button:hover {
      transform: translateY(-1px);
      box-shadow: 0 8px 16px rgba(38, 99, 235, 0.08);
      border-color: rgba(38, 99, 235, 0.24);
    }

    button.primary {
      background: linear-gradient(135deg, #2663eb, #4d8dff);
      border-color: transparent;
      color: white;
      box-shadow: 0 12px 18px rgba(38, 99, 235, 0.22);
    }

    button.danger {
      background: rgba(220, 38, 38, 0.08);
      color: var(--danger);
      border-color: rgba(220, 38, 38, 0.14);
    }

    .stack {
      display: grid;
      gap: 8px;
      margin-top: 2px;
    }

    .workspace {
      flex: 1;
      display: flex;
      flex-direction: column;
      min-width: 0;
      min-height: 0;
      height: 100%;
      box-sizing: border-box;
      padding: 0;
      background: transparent;
    }

    .canvas-wrap {
      flex: 1 1 0;
      min-height: 0;
      border: none;
      border-radius: 0;
      overflow: hidden;
      background: transparent;
      position: relative;
      box-shadow: none;
    }

    .canvas-wrap::before {
      display: none;
    }

    .canvas-wrap > svg {
      position: absolute;
      inset: 0;
    }

    svg {
      width: 100%;
      height: 100%;
      display: block;
      background: transparent;
      user-select: none;
      max-width: none;
      border: none;
      outline: none;
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

    .edge.free-line {
      stroke: #000000;
      stroke-dasharray: none;
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

    .edge.route.active.route-reveal,
    .edge.route.active.route-stair {
      stroke-dasharray: 12 16;
      stroke-dashoffset: 0;
      animation: routeTrace 2800ms linear infinite;
    }

    .edge.route.active.route-stair {
      stroke-width: 5;
      filter: drop-shadow(0 0 5px currentColor);
    }

    #floorConnectorLayer,
    .floor-connector-marker {
      pointer-events: none;
    }

    .floor-connector-triangle {
      filter: drop-shadow(0 2px 3px rgba(0, 0, 0, 0.35));
    }

    .floor-connector-triangle.floor-connector-up {
      animation: floorConnectorBobUp 1.2s ease-in-out infinite;
    }

    .floor-connector-triangle.floor-connector-down {
      animation: floorConnectorBobDown 1.2s ease-in-out infinite;
    }

    @keyframes floorConnectorBobUp {
      0%, 100% { transform: translateY(2px); }
      50% { transform: translateY(-3px); }
    }

    @keyframes floorConnectorBobDown {
      0%, 100% { transform: translateY(-2px); }
      50% { transform: translateY(3px); }
    }

    .route-flow-halo,
    .route-flow-point,
    .route-flow-arrow {
      vector-effect: non-scaling-stroke;
      pointer-events: none;
    }

    .route-flow-halo {
      fill: rgba(255, 255, 255, 0.28);
      opacity: 0.7;
      filter: blur(1.5px);
    }

    .route-flow-point {
      fill: #ffffff;
      stroke: rgba(255, 255, 255, 0.9);
      stroke-width: 2;
      filter: drop-shadow(0 0 7px rgba(255, 255, 255, 0.8));
    }

    .route-flow-arrow {
      fill: #ffffff;
      stroke: rgba(255, 255, 255, 0.9);
      stroke-width: 1.5;
      filter: drop-shadow(0 0 8px rgba(255, 255, 255, 0.9));
    }

    @keyframes routeReveal {
      0% {
        stroke-dasharray: 12 16;
        stroke-dashoffset: 0;
        opacity: 0.45;
      }

      50% {
        opacity: 1;
      }

      100% {
        stroke-dasharray: 12 16;
        stroke-dashoffset: -200;
        opacity: 0.8;
      }
    }

    @keyframes routeTrace {
      0% {
        stroke-dashoffset: 0;
      }

      100% {
        stroke-dashoffset: -220;
      }
    }

    @keyframes routeFloorTransition {
      0%, 49.9% { stroke: var(--route-from-color); }
      50%, 100% { stroke: var(--route-to-color); }
    }

    .edge.route.alt-active {
      stroke: #8d99ae;
      stroke-width: 5;
      stroke-dasharray: 12 10;
      opacity: 0.95;
    }

    @media (prefers-reduced-motion: reduce) {
      .edge.route.active,
      .floor-connector-triangle.floor-connector-up,
      .floor-connector-triangle.floor-connector-down {
        animation: none !important;
      }
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
      z-index: 1200;
      pointer-events: auto;
      display: flex;
      align-items: center;
      gap: 8px;
      background: #ffffff;
      border: 1px solid rgba(35, 58, 102, 0.42);
      border-radius: 12px;
      padding: 6px 9px;
      box-shadow: 0 5px 16px rgba(17, 24, 39, 0.3);
      white-space: nowrap;
    }

    .building-floor-popup.hidden,
    .building-toggle-pill.hidden {
      display: none;
    }

    .building-name-label {
      font-size: 12px;
      font-weight: 700;
      color: #111827;
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
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 6px;
      margin-top: 12px;
      padding: 10px 10px 0;
      border-top: 1px solid rgba(38, 99, 235, 0.1);
      font-size: 11px;
      color: var(--muted);
      font-weight: 700;
      min-width: 0;
      letter-spacing: 0.02em;
    }

    .floor-line-item {
      display: inline-flex;
      align-items: center;
      gap: 5px;
      white-space: nowrap;
      min-width: 0;
    }

    .floor-line-swatch {
      width: 20px;
      height: 4px;
      flex: 0 0 20px;
      border-radius: 999px;
      display: inline-block;
      box-shadow: inset 0 0 0 1px rgba(255,255,255,0.24);
    }

    .floor-line-ground { background: linear-gradient(90deg, #2ecf7a, #16a34a); }
    .floor-line-second { background: linear-gradient(90deg, #f87171, #dc2626); }
    .floor-line-third { background: linear-gradient(90deg, #7aa7ff, #2563eb); }
    .floor-line-alternative { height: 0; border-top: 4px dashed #8d99ae; width: 20px; flex: 0 0 20px; }
    body.map-only .app { display: block; width: 100%; height: 100%; }
    body.map-only .sidebar { display: none !important; }
    body.map-only .workspace { width: 100%; height: 100%; min-width: 0; padding: 0; }
    body.map-only .canvas-wrap { width: 100%; height: 100%; min-height: 0; border: 0; border-radius: 0; box-shadow: none; }
    body.map-only #mapCanvas { height: 100% !important; max-height: 100% !important; }
    body.guest-map-mode .route-panel-header,
    body.guest-map-mode #findPathBtn,
    body.guest-map-mode #resetBtn,
    body.guest-map-mode .floor-line-legend { display: none !important; }

    body.guest-map-mode .sidebar { display: none !important; }
    body.guest-map-mode .map-zoom-controls {
      position: absolute;
      right: 18px;
      bottom: 18px;
      z-index: 30;
      display: flex;
      align-items: center;
      gap: 6px;
      padding: 6px;
      border: 1px solid rgba(20, 54, 156, 0.14);
      border-radius: 10px;
      background: rgba(255, 255, 255, 0.96);
      box-shadow: 0 8px 24px rgba(17, 37, 72, 0.16);
    }
    .map-zoom-controls[hidden] { display: none !important; }
    .map-zoom-controls button {
      display: grid;
      width: 36px;
      height: 36px;
      place-items: center;
      padding: 0;
      border: 1px solid var(--border);
      border-radius: 7px;
      background: #fff;
      color: #14369c;
      font-size: 20px;
      font-weight: 800;
      line-height: 1;
    }
    .map-zoom-controls button:hover { background: #f1f5ff; }
    .map-zoom-level { min-width: 48px; color: #334155; font-size: 12px; font-weight: 800; text-align: center; }
    @media (max-width: 600px) {
      body.guest-map-mode .map-zoom-controls { right: 10px; bottom: 10px; }
    }

    body.ionic-map,
    body.ionic-map .app {
      width: 100%;
      height: 100%;
      min-width: 0;
      min-height: 0;
      overflow: hidden;
    }

    body.ionic-map .app {
      position: relative;
      display: block;
    }

    body.ionic-map .workspace {
      position: absolute;
      inset: 0;
      width: 100%;
      height: 100%;
      min-width: 0;
      min-height: 0;
      padding: 0;
      border: 0;
      border-radius: 0;
      box-shadow: none;
    }

    body.ionic-map .canvas-wrap {
      position: absolute;
      inset: 0;
      width: 100%;
      height: auto;
      min-height: 0;
      padding: 0;
      border: 0;
      border-radius: 0;
      box-shadow: none;
    }

    body.ionic-map #mapCanvas {
      width: 100%;
      height: 100%;
      max-width: none;
      max-height: none;
      touch-action: none;
    }

    body.ionic-map .canvas-wrap { touch-action: none; }

    body.ionic-map .sidebar {
      position: absolute;
      z-index: 40;
      top: 12px;
      bottom: 12px;
      left: 12px;
      display: none;
      width: min(460px, calc(100% - 24px));
      max-height: none;
      padding: 12px;
      overflow-y: auto;
      border: 1px solid var(--border);
      border-radius: 14px;
      box-shadow: 0 8px 28px rgba(15, 27, 60, 0.28);
    }

    body.ionic-map.ionic-controls-open .sidebar {
      display: block;
      z-index: 1300;
    }

    body.ionic-map .ionic-controls-toggle {
      position: absolute;
      z-index: 1400;
      top: 8px;
      left: 10px;
      min-height: 42px;
      padding: 8px 14px;
      border-radius: 8px;
      background: #fff;
      box-shadow: 0 3px 12px rgba(15, 27, 60, 0.24);
    }

    body.ionic-map.ionic-controls-open .ionic-controls-toggle {
      display: none;
    }

    body.ionic-map .ionic-controls-close {
      display: none;
      width: auto;
      min-height: 38px;
      margin: 0 0 10px auto;
      padding: 7px 12px;
      border-radius: 8px;
      background: #fff;
      color: var(--text);
      box-shadow: 0 3px 12px rgba(15, 27, 60, 0.12);
    }

    body.ionic-map.ionic-controls-open .ionic-controls-close {
      display: inline-flex;
      align-items: center;
      justify-content: center;
    }

    body.ionic-map.guest-map-mode .sidebar,
    body.ionic-map.guest-map-mode .ionic-controls-toggle {
      display: none !important;
    }

    body.ionic-map.guest-map-mode .map-zoom-controls {
      right: 12px;
      bottom: 12px;
    }

    @media (max-width: 480px) {
      body.ionic-map .sidebar {
        top: 8px;
        bottom: 8px;
        left: 8px;
        width: min(460px, calc(100% - 16px));
        padding: 10px;
      }

    }
  </style>
</head>

<body class="<?= implode(' ', array_filter([$mapOnly ? 'map-only' : '', $ionicMap ? 'ionic-map' : '', $guestMap ? 'guest-map-mode' : ''])); ?>">
  <div class="app">
    <aside class="sidebar" id="mapControls">
      <?php if ($ionicMap && !$guestMap): ?>
        <button class="ionic-controls-close" id="ionicControlsClose" type="button" aria-label="Close route controls">Close route</button>
      <?php endif; ?>
      <div class="panel">
        <div class="route-panel-header">
          <h3 class="title">Search</h3>
        </div>
        <div class="field">
          <label for="roomSearchInput">Find a room, office or service</label>
          <div class="search-input-wrap">
            <i class="fa-solid fa-magnifying-glass search-icon" aria-hidden="true"></i>
            <input id="roomSearchInput" type="text" autocomplete="off" spellcheck="false" placeholder="e.g. library, books, clinic, 201"
              role="combobox" aria-autocomplete="list" aria-expanded="false" aria-controls="roomSearchResults" />
            <button id="roomSearchClear" class="search-clear" type="button" aria-label="Clear search">&times;</button>
          </div>
          <ul id="roomSearchResults" class="search-results" role="listbox" hidden></ul>
          <div id="roomSearchHint" class="search-hint" aria-live="polite"></div>
        </div>
      </div>

      <div class="panel">
        <div class="route-panel-header">
          <h3 class="title">Route</h3>
        </div>
        <div class="field">
          <label for="startSelect">From</label>
          <select id="startSelect"></select>
        </div>
        <div class="field">
          <label for="endSelect">To</label>
          <select id="endSelect"></select>
        </div>
        <div class="field">
          <label>Sort locations</label>
          <div class="sort-panel" aria-label="Sort locations">
            <div class="sort-option active" data-mode="label" tabindex="0">
              <span class="sort-main"><span class="sort-icon">A-Z</span></span>
              <span class="sort-arrows">
                <button class="sort-arrow active" data-direction="asc" type="button" aria-label="Sort A-Z ascending">▲</button>
                <button class="sort-arrow" data-direction="desc" type="button" aria-label="Sort A-Z descending">▼</button>
              </span>
            </div>
            <div class="sort-option" data-mode="floor" tabindex="0">
              <span class="sort-main"><span class="sort-icon">F</span></span>
              <span class="sort-arrows">
                <button class="sort-arrow active" data-direction="asc" type="button" aria-label="Sort floor ascending">▲</button>
                <button class="sort-arrow" data-direction="desc" type="button" aria-label="Sort floor descending">▼</button>
              </span>
            </div>
            <div class="sort-option" data-mode="building" tabindex="0">
              <span class="sort-main">
                <span class="sort-icon"><i class="fa-regular fa-building" aria-hidden="true"></i></span>
              </span>
              <span class="sort-arrows">
                <button class="sort-arrow active" data-direction="asc" type="button" aria-label="Sort building ascending">▲</button>
                <button class="sort-arrow" data-direction="desc" type="button" aria-label="Sort building descending">▼</button>
              </span>
            </div>
          </div>
          <select id="destinationSortSelect" hidden>
            <option value="label" selected>Alphabetical</option>
            <option value="building">Building</option>
            <option value="floor">Floor</option>
          </select>
        </div>
        <div class="stack">
          <button class="primary" type="button" id="findPathBtn">Find Path</button>
          <button type="button" id="resetBtn">Reset</button>
        </div>

        <div class="floor-line-legend" aria-label="Route legend">
          <div class="floor-line-item"><span class="floor-line-swatch floor-line-ground"></span><span>1F</span></div>
          <div class="floor-line-item"><span class="floor-line-swatch floor-line-second"></span><span>2F</span></div>
          <div class="floor-line-item"><span class="floor-line-swatch floor-line-third"></span><span>3F</span></div>
          <div class="floor-line-item"><span class="floor-line-swatch floor-line-alternative"></span><span>Alt</span></div>
        </div>
      </div>

    </aside>

    <main class="workspace">
      <?php if ($ionicMap && !$guestMap): ?>
        <button class="ionic-controls-toggle" id="ionicControlsToggle" type="button" aria-expanded="false" aria-controls="mapControls">Route</button>
      <?php endif; ?>
      <div class="canvas-wrap">
        <div id="buildingFloorPopup" class="building-floor-popup hidden" aria-live="polite"></div>
        <?php if ($guestMap): ?>
          <div class="map-zoom-controls" role="group" aria-label="Map zoom controls">
            <button id="mapZoomOut" type="button" aria-label="Zoom out" title="Zoom out">−</button>
            <span id="mapZoomLevel" class="map-zoom-level" aria-live="polite">100%</span>
            <button id="mapZoomIn" type="button" aria-label="Zoom in" title="Zoom in">+</button>
            <button id="mapZoomReset" type="button" aria-label="Reset zoom" title="Reset zoom">↺</button>
          </div>
        <?php endif; ?>
        <svg id="mapCanvas" preserveAspectRatio="xMidYMid meet">
          <g id="buildingLayer"></g>
          <g id="edgeLayer"></g>
          <g id="stairLayer"></g>
          <g id="fillerLayer"></g>
          <g id="routeLayer"></g>
          <g id="routeMarkerLayer"></g>
          <g id="roomLayer"></g>
          <g id="floorConnectorLayer"></g>
        </svg>
      </div>
    </main>
  </div>

  <script>
    const ionicControlsToggle = document.getElementById('ionicControlsToggle');
    const ionicControlsClose = document.getElementById('ionicControlsClose');
    const mapControls = document.getElementById('mapControls');

    function setIonicControlsOpen(isOpen) {
      document.body.classList.toggle('ionic-controls-open', isOpen);
      ionicControlsToggle?.setAttribute('aria-expanded', String(isOpen));
      ionicControlsClose?.setAttribute('aria-expanded', String(isOpen));
    }

    ionicControlsToggle?.addEventListener('click', () => setIonicControlsOpen(true));
    ionicControlsClose?.addEventListener('click', () => setIonicControlsOpen(false));

    document.addEventListener('pointerdown', (event) => {
      if (!document.body.classList.contains('ionic-map')) return;
      if (!document.body.classList.contains('ionic-controls-open')) return;

      const clickedInsideSidebar = mapControls && mapControls.contains(event.target);
      const clickedToggle = ionicControlsToggle && ionicControlsToggle.contains(event.target);
      const clickedCloseButton = ionicControlsClose && ionicControlsClose.contains(event.target);

      if (!clickedInsideSidebar && !clickedToggle && !clickedCloseButton) {
        setIonicControlsOpen(false);
      }
    });

    document.addEventListener('keydown', (event) => {
      if (event.key === 'Escape' && document.body.classList.contains('ionic-controls-open')) {
        setIonicControlsOpen(false);
      }
    });

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
    const canvasSize = LAYOUT.canvasSize && Number(LAYOUT.canvasSize.width) && Number(LAYOUT.canvasSize.height)
      ? { width: Number(LAYOUT.canvasSize.width), height: Number(LAYOUT.canvasSize.height) }
      : DEFAULT_CANVAS_SIZE;

    const svg = document.getElementById('mapCanvas');
    const effectiveCanvasSize = { width: 1920, height: 1080 };
    const baseViewBox = { x: 0, y: 0, width: effectiveCanvasSize.width, height: effectiveCanvasSize.height };
    svg.setAttribute('viewBox', `${baseViewBox.x} ${baseViewBox.y} ${baseViewBox.width} ${baseViewBox.height}`);
    svg.setAttribute('width', '100%');
    svg.setAttribute('height', '100%');
    svg.style.width = '100%';
    svg.style.height = '100%';
    svg.style.maxWidth = '100%';
    svg.style.maxHeight = '100%';

    const edgeLayer = document.getElementById('edgeLayer');
    const stairLayer = document.getElementById('stairLayer');
    const fillerLayer = document.getElementById('fillerLayer');
    const routeLayer = document.getElementById('routeLayer');
    const routeMarkerLayer = document.getElementById('routeMarkerLayer');
    const floorConnectorLayer = document.getElementById('floorConnectorLayer');
    const roomLayer = document.getElementById('roomLayer');
    const buildingLayer = document.getElementById('buildingLayer');
    const nodeLayer = roomLayer;
    const startSelect = document.getElementById('startSelect');
    const endSelect = document.getElementById('endSelect');
    const destinationSortSelect = document.getElementById('destinationSortSelect');
    const guestMap = <?= $guestMap ? 'true' : 'false'; ?>;
    const ionicMap = <?= $ionicMap ? 'true' : 'false'; ?>;

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
      zoom: 1,
      routeAnimationFrameId: null,
      routeAnimationSegments: []
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
      // Junctions are exempt from the active floor toggle. They always stay on the
      // floor they were assigned in the editor, even when a building's floor popup is shown.
      if (node.type === 'junction') return getNodeFloor(node);

      const inferred = (node.buildingName || node.buildingId) ? null : inferBuildingFromPosition(node);
      const buildingKey = node.buildingName
        || (node.buildingId ? getBuildingFloorKey(node.buildingId) : null)
        || (inferred ? String(inferred.name) : null);

      // Nodes that are not assigned to any building should never be hidden by the
      // active floor toggle. This includes path anchors and junctions that are not tied
      // to a building's floor state.
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
      if (guestMap) {
        const cict = buildings.find((building) => String(building && building.name || '').trim().toLowerCase() === 'cict');
        if (cict) add(cict.id);
        return ids.filter((id) => getBuildingFloorCount(id) > 1 && getBuildingHighestFloor(id) > 1);
      }
      const getSelectionBuildingId = (value, routedNodeId) => {
        if (!value) return getNodeBuildingId(routedNodeId ? getNodeData(routedNodeId) : null);
        const selectedNodeId = resolveTargetNodes(value)[0];
        return getNodeBuildingId(getNodeData(selectedNodeId))
          || getNodeBuildingId(routedNodeId ? getNodeData(routedNodeId) : null);
      };
      add(getSelectionBuildingId(startSelect && startSelect.value, state.primaryStartId));
      add(getSelectionBuildingId(endSelect && endSelect.value, state.primaryEndId));
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
      if (isGuestAdmissionNode(node)) return false;
      // Junctions are never dimmed, whether or not they belong to a building.
      if (node.type === 'junction') return false;
      const nodeBuilding = node.buildingName || (node.buildingId ? getBuildingFloorKey(node.buildingId) : null) || null;
      if (!nodeBuilding) return false;
      if (focus.has(nodeBuilding)) {
        return getNodeFloor(node) !== focus.get(nodeBuilding);
      }
      return true;
    }

    // Dim by building membership, not by a flat floor rule. A segment is dimmed when
    // one of its ends belongs to the currently active route building but is on another floor.
    function getNodeBuildingKey(node) {
      if (!node) return null;
      if (node.buildingName) return getBuildingFloorKey(node.buildingName);
      if (node.buildingId) return getBuildingFloorKey(node.buildingId);
      const inferred = inferBuildingFromPosition(node);
      return inferred && inferred.name ? getBuildingFloorKey(inferred.name) : null;
    }

    function getNodeContextFloor(node) {
      if (!node) return Number(state.activeFloor) || 1;
      const buildingKey = getNodeBuildingKey(node);
      if (!buildingKey) return Number(state.activeFloor) || 1;
      const savedFloor = Number(state.buildingFloorState[buildingKey]);
      if (Number.isFinite(savedFloor) && savedFloor >= 1) return savedFloor;
      return getNodeFloor(node);
    }

    // A route node belongs to the floor currently shown for its building when that building has a
    // floor toggle (start / destination building) and the node's floor matches the toggled floor.
    // Buildings without a toggle are not filtered, exactly like the route line itself.
    function isRouteNodeOnToggledFloor(node) {
      if (!node) return false;
      const activeBuildingKeys = new Set(
        getPopupBuildingIds().map((id) => getBuildingFloorKey(id)).filter(Boolean)
      );
      const buildingKey = getNodeBuildingKey(node);
      if (!buildingKey || !activeBuildingKeys.has(buildingKey)) return true;
      return getNodeFloor(node) === getNodeContextFloor(node);
    }

    // Floor shown for a route node: its building's toggled floor if it has one,
    // otherwise the globally active floor.
    function getRouteContextFloor(node) {
      const buildingKey = getNodeBuildingKey(node);
      if (buildingKey) {
        const savedFloor = Number(state.buildingFloorState[buildingKey]);
        if (Number.isFinite(savedFloor) && savedFloor >= 1) return savedFloor;
      }
      return Number(state.activeFloor) || 1;
    }

    // Floor rule for route paths (example with 3 floors):
    //   1F active -> 2F and 3F paths hidden
    //   2F active -> 1F path dimmed, 3F path hidden
    //   3F active -> 1F and 2F paths dimmed
    // Returns 'hide', 'dim' or 'normal'. A segment that touches the active floor stays normal.
    function getRouteSegmentFloorState(from, to) {
      if (!from || !to) return 'normal';
      if (isGuestAdmissionNode(from) || isGuestAdmissionNode(to)) return 'normal';
      const compare = (node) => {
        const floor = getNodeFloor(node);
        const activeFloor = getRouteContextFloor(node);
        return floor > activeFloor ? 1 : (floor < activeFloor ? -1 : 0);
      };
      const fromSide = compare(from);
      const toSide = compare(to);
      if (fromSide > 0 && toSide > 0) return 'hide';
      if (fromSide < 0 && toSide < 0) return 'dim';
      return 'normal';
    }

    function isRouteSegmentDimmed(from, to) {
      return getRouteSegmentFloorState(from, to) === 'dim' || isPathSegmentDimmed(from, to);
    }

    function isPathSegmentDimmed(from, to) {
      if (!from || !to) return false;

      const activeBuildingKeys = new Set(
        getPopupBuildingIds().map((id) => getBuildingFloorKey(id)).filter(Boolean)
      );

      const shouldDimNode = (node) => {
        const buildingKey = getNodeBuildingKey(node);
        if (!buildingKey || !activeBuildingKeys.has(buildingKey)) return false;
        return getNodeFloor(node) !== getNodeContextFloor(node);
      };

      return shouldDimNode(from) || shouldDimNode(to);
    }

    function nodeMatchesVisibleFloor(node) {
      if (!node) return false;
      if (isGuestAdmissionNode(node)) return true;

      const floor = getNodeFloor(node);
      if (node.type === 'junction' || floor === 1) return true;

      const inferred = (node.buildingName || node.buildingId) ? null : inferBuildingFromPosition(node);
      const buildingKey = node.buildingName
        || (node.buildingId ? getBuildingFloorKey(node.buildingId) : null)
        || (inferred ? String(inferred.name) : null);

      // If the node belongs to no building, it should not disappear behind the active
      // floor toggle. This keeps paths and junctions visible even when they are free-floating.
      if (!buildingKey) return true;
      return floor === getNodeDisplayFloor(node);
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

    function animateMapLayers(layers, duration = 320) {
      if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) return;
      layers.filter(Boolean).forEach((layer) => {
        if (typeof layer.animate !== 'function' || !layer.childElementCount) return;
        layer.animate(
          [
            { opacity: 0.08, transform: 'scale(0.985)', transformOrigin: 'center' },
            { opacity: 1, transform: 'scale(1)', transformOrigin: 'center' }
          ],
          { duration: Math.max(duration, 420), easing: 'cubic-bezier(0.2, 0.75, 0.25, 1)' },
        );
      });
    }

    function animateFloorSelection(buildingId) {
      const popup = [...document.querySelectorAll('.building-toggle-pill')]
        .find((element) => element.dataset.group === String(buildingId));
      const activeButton = popup?.querySelector('.btn.active');
      if (!activeButton || window.matchMedia('(prefers-reduced-motion: reduce)').matches) return;
      activeButton.animate(
        [
          { transform: 'scale(0.82)', boxShadow: '0 0 0 0 rgba(38, 99, 235, 0.4)' },
          { transform: 'scale(1.12)', boxShadow: '0 0 0 7px rgba(38, 99, 235, 0)' },
          { transform: 'scale(1)', boxShadow: '0 8px 18px rgba(38, 99, 235, 0.18)' }
        ],
        { duration: 420, easing: 'cubic-bezier(0.2, 0.75, 0.25, 1)' },
      );
    }

    function syncFloorControlToRouteSegment(segment, progress) {
      const currentNode = progress < 0.5 ? segment.from : segment.to;
      const buildingId = getNodeBuildingId(currentNode);
      if (!buildingId || !getPopupBuildingIds().includes(buildingId)) return;
      const building = getBuildingById(buildingId);
      const floor = getNodeFloor(currentNode);
      const floorKey = getBuildingFloorKey(buildingId);
      if (!building || getBuildingVisibleFloor(buildingId) === floor) return;

      state.buildingFloorState[floorKey] = floor;
      state.activeFloor = floor;
      renderNodes();
      updateFloorToggleButtons();
      renderBuildingFloorPopup();
      routeLayer.querySelectorAll('.edge.route').forEach((line) => {
        const fromNode = getNodeData(line.dataset.routeFrom);
        const toNode = getNodeData(line.dataset.routeTo);
        if (!fromNode || !toNode) return;
        line.style.display = getRouteSegmentFloorState(fromNode, toNode) === 'hide' ? 'none' : '';
        line.style.opacity = isRouteSegmentDimmed(fromNode, toNode) ? '0.52' : '1';
      });
      animateFloorSelection(buildingId);
      animateMapLayers([buildingLayer, edgeLayer, fillerLayer, stairLayer, roomLayer, floorConnectorLayer], 280);
    }

    function setActiveFloor(floor) {
      const nextFloor = Number(floor) || 1;
      if (nextFloor === state.activeFloor) return;
      state.activeFloor = nextFloor;
      (buildings || []).forEach((building) => {
        if (!building || !building.name) return;
        state.buildingFloorState[String(building.name)] = Math.min(nextFloor, getBuildingFloorCount(building.id) || 1);
      });
      updateFloorToggleButtons();
      render();
      animateMapLayers([buildingLayer, edgeLayer, fillerLayer, stairLayer, roomLayer, routeLayer, routeMarkerLayer, floorConnectorLayer], 280);
      animateFloorSelection(state.selectedBuildingId);
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
      animateMapLayers([buildingLayer, edgeLayer, fillerLayer, stairLayer, roomLayer, routeLayer, routeMarkerLayer, floorConnectorLayer], 280);
      animateFloorSelection(buildingId);
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

    function isGuestAdmissionNode(node) {
      return guestMap && /\badmission\b/i.test(String(node && node.label || ''));
    }

    function isGuestAllowedNode(node) {
      if (!guestMap) return true;
      const label = String(node && node.label || '').replace(/\s+/g, ' ').trim();
      const tags = Array.isArray(node && node.tags) ? node.tags.join(' ') : '';
      const searchableText = `${label} ${tags}`;
      const buildingName = String(node && node.buildingName || getBuildingById(node && node.buildingId)?.name || '').trim();
      const isInfoDesk = /^(?:info\b|information\s+desk\b)/i.test(label) || /\binfo_desk\b/i.test(String(node && node.group || ''));
      const isClinic = /\b(?:clinic|infirmary|health\s+(?:center|clinic))\b/i.test(searchableText);
      const isHiddenOffice = /\b(?:gender|ret|nstp|lhs\s+principal|osoad)\b/i.test(searchableText)
        || (/\b(?:guidance|counseling)\b/i.test(label) && /\boffice\b/i.test(label))
        || /\boffice of student affairs\b/i.test(label);
      if (isHiddenOffice) return false;
      return isClinic || (isInfoDesk && /^(?:cict|cmbt)$/i.test(buildingName)) || /\b(?:offices?|registrar|cashier|admission|entrance|exit|guard\s*house|restrooms?|toilets?|cr|faculty|canteen)\b/i.test(searchableText);
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
      if (guestMap && String(building.name || '').trim().toLowerCase() !== 'cict') return;
      const hasRouteContext = !!(state.primaryRoute && state.primaryRoute.length) || (!!startSelect.value && !!endSelect.value);
      if (!guestMap && !hasRouteContext) return;

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
      // renderBuildingFloorPopup() swaps the class and sets inline display/position,
      // so all of that must be undone or the pill stays on screen after Reset.
      popup.className = 'building-floor-popup hidden';
      popup.style.display = 'none';
      popup.style.left = '';
      popup.style.top = '';
      popup.style.transform = '';
      popup.removeAttribute('data-group');
      popup.innerHTML = '';
    }

    function renderBuildingFloorPopup() {
      const hasRouteContext = !!(state.primaryRoute && state.primaryRoute.length) || (!!startSelect.value && !!endSelect.value);
      const popupBuildingIds = guestMap || hasRouteContext ? getPopupBuildingIds() : [];
      const placed = [];

      const slotCount = guestMap ? popupBuildingIds.length : Math.max(popupBuildingIds.length, 2);
      Array.from({ length: slotCount }, (_, slot) => slot).forEach((slot) => {
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

        popup.className = 'building-toggle-pill position-absolute';
        popup.dataset.group = buildingId;
        popup.removeAttribute('data-edge-placement');
        popup.style.left = '0px';
        popup.style.top = '0px';
        popup.style.transform = 'none';
        popup.style.display = 'flex';
        popup.innerHTML = `
        <span class="building-name-label">${building.name || building.id}</span>
        <div class="btn-group btn-group-sm" role="group" aria-label="${building.name || building.id} floor selection">
          ${buttons}
        </div>
      `;
        popup.classList.remove('hidden');

      });
      positionBuildingFloorPopups();
    }

    function positionBuildingFloorPopups() {
      const canvasWrap = document.querySelector('.canvas-wrap');
      const ctm = svg.getScreenCTM();
      if (!canvasWrap || !ctm) return;
      const canvasRect = canvasWrap.getBoundingClientRect();
      const placed = [];
      const toCanvasPoint = (x, y) => {
        const point = svg.createSVGPoint();
        point.x = x;
        point.y = y;
        const screenPoint = point.matrixTransform(ctm);
        return { x: screenPoint.x - canvasRect.left, y: screenPoint.y - canvasRect.top };
      };
      const mapBuildingBounds = (buildings || []).map((building) => {
        const x = Number(building.x) || 0;
        const y = Number(building.y) || 0;
        const topLeft = toCanvasPoint(x, y);
        const bottomRight = toCanvasPoint(
          x + (Number(building.width) || 220),
          y + (Number(building.height) || 180),
        );
        return {
          left: Math.min(topLeft.x, bottomRight.x),
          top: Math.min(topLeft.y, bottomRight.y),
          right: Math.max(topLeft.x, bottomRight.x),
          bottom: Math.max(topLeft.y, bottomRight.y),
        };
      });
      const campusBounds = mapBuildingBounds.reduce((bounds, rect) => ({
        left: Math.min(bounds.left, rect.left),
        top: Math.min(bounds.top, rect.top),
        right: Math.max(bounds.right, rect.right),
        bottom: Math.max(bounds.bottom, rect.bottom),
      }), { left: Infinity, top: Infinity, right: -Infinity, bottom: -Infinity });

      getPopupBuildingIds().forEach((buildingId, slot) => {
        const popup = getFloorPopupElement(slot);
        const building = getBuildingById(buildingId);
        if (!popup || !building || popup.classList.contains('hidden')) return;

        const x = Number(building.x) || 0;
        const y = Number(building.y) || 0;
        const topLeft = toCanvasPoint(x, y);
        const bottomRight = toCanvasPoint(
          x + (Number(building.width) || 220),
          y + (Number(building.height) || 180),
        );
        const bounds = {
          left: Math.min(topLeft.x, bottomRight.x),
          top: Math.min(topLeft.y, bottomRight.y),
          right: Math.max(topLeft.x, bottomRight.x),
          bottom: Math.max(topLeft.y, bottomRight.y),
        };
        const width = popup.offsetWidth;
        const height = popup.offsetHeight;
        const gap = 8;
        const margin = 8;
        const centerY = (campusBounds.top + campusBounds.bottom - height) / 2;
        const buildingName = String(building.name || '').trim().toLowerCase();
        const preferredEdge = ({ cict: 'top', coed: 'left', lhs: 'right', cmbt: 'bottom' })[buildingName];
        const edgeX = preferredEdge === 'left'
          ? Math.max(margin, campusBounds.left - width - gap)
          : Math.min(canvasRect.width - width - margin, campusBounds.right + gap);
        const candidates = preferredEdge === 'top'
          ? [{ left: (campusBounds.left + campusBounds.right - width) / 2, top: campusBounds.top - height - gap }]
          : preferredEdge === 'bottom'
            ? [{ left: (campusBounds.left + campusBounds.right - width) / 2, top: campusBounds.bottom + gap }]
            : preferredEdge === 'left' || preferredEdge === 'right'
              ? Array.from({ length: Math.ceil(canvasRect.height / 8) + 1 }, (_, index) => {
                  const offset = index === 0 ? 0 : Math.ceil(index / 2) * 8 * (index % 2 === 1 ? -1 : 1);
                  return { left: edgeX, top: centerY + offset };
                })
              : [
              { left: bounds.right + gap, top: centerY },
              { left: bounds.left - width - gap, top: centerY },
              { left: (bounds.left + bounds.right - width) / 2, top: bounds.top - height - gap },
              { left: (bounds.left + bounds.right - width) / 2, top: bounds.bottom + gap },
            ];

        let best = null;
        let bestScore = Infinity;
        candidates.forEach((candidate, index) => {
          const candidateBounds = {
            left: candidate.left,
            top: candidate.top,
            right: candidate.left + width,
            bottom: candidate.top + height,
          };
          if (candidateBounds.left < margin || candidateBounds.right > canvasRect.width - margin ||
              candidateBounds.top < margin || candidateBounds.bottom > canvasRect.height - margin) return;
          const rect = candidateBounds;
          const controlOverlap = placed.reduce((area, other) => {
            const overlapWidth = Math.max(0, Math.min(rect.right, other.right) - Math.max(rect.left, other.left));
            const overlapHeight = Math.max(0, Math.min(rect.bottom, other.bottom) - Math.max(rect.top, other.top));
            return area + overlapWidth * overlapHeight;
          }, 0);
          const buildingOverlap = (preferredEdge === 'left' || preferredEdge === 'right')
            ? mapBuildingBounds.reduce((area, other) => {
                const overlapWidth = Math.max(0, Math.min(rect.right, other.right) - Math.max(rect.left, other.left));
                const overlapHeight = Math.max(0, Math.min(rect.bottom, other.bottom) - Math.max(rect.top, other.top));
                return area + overlapWidth * overlapHeight;
              }, 0)
            : 0;
          const score = buildingOverlap * 10000 + controlOverlap * 10000 + Math.abs(candidate.top - centerY) + index / 100;
          if (score < bestScore) {
            bestScore = score;
            best = rect;
          }
        });

        if (!best) {
          const fallback = candidates.map((candidate, index) => {
            const left = Math.max(margin, Math.min(canvasRect.width - width - margin, candidate.left));
            const top = Math.max(margin, Math.min(canvasRect.height - height - margin, candidate.top));
            const rect = { left, top, right: left + width, bottom: top + height };
            const overlap = placed.reduce((area, other) => {
              const overlapWidth = Math.max(0, Math.min(rect.right, other.right) - Math.max(rect.left, other.left));
              const overlapHeight = Math.max(0, Math.min(rect.bottom, other.bottom) - Math.max(rect.top, other.top));
              return area + overlapWidth * overlapHeight;
            }, 0);
            const buildingOverlap = (preferredEdge === 'left' || preferredEdge === 'right')
              ? mapBuildingBounds.reduce((area, other) => {
                  const overlapWidth = Math.max(0, Math.min(rect.right, other.right) - Math.max(rect.left, other.left));
                  const overlapHeight = Math.max(0, Math.min(rect.bottom, other.bottom) - Math.max(rect.top, other.top));
                  return area + overlapWidth * overlapHeight;
                }, 0)
              : 0;
            const clampDistance = Math.abs(left - candidate.left) + Math.abs(top - candidate.top);
            return { rect, score: (buildingOverlap + overlap) * 10000 + clampDistance + index };
          }).sort((a, b) => a.score - b.score)[0];
          best = fallback?.rect || null;
        }

        if (!best) return;
        popup.style.left = `${best.left}px`;
        popup.style.top = `${best.top}px`;
        popup.style.transform = 'none';
        if (preferredEdge) popup.dataset.edgePlacement = preferredEdge;
        placed.push(best);
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
        if (!isGuestAllowedNode(node)) return;

        const parsedGroup = parseGroupValue(node.group);
        if (!parsedGroup) return;

        if (!grouped[parsedGroup.groupName]) grouped[parsedGroup.groupName] = [];
        grouped[parsedGroup.groupName].push(node.id);
      });
      return grouped;
    }

    const NODE_GROUPS = buildNodeGroups();

    function getNodeBuildingName(node) {
      if (!node) return '';
      if (node.buildingName) return String(node.buildingName).trim();
      if (node.buildingId) {
        const building = (buildings || []).find((item) => String(item && item.id) === String(node.buildingId));
        if (building && building.name) return String(building.name).trim();
        return String(node.buildingId).trim();
      }
      return '';
    }

    function getRouteSortNode(option) {
      if (!option || !option.node) return null;
      return option.node;
    }

    const SORT_COLLATOR = new Intl.Collator(undefined, { sensitivity: 'base', numeric: true });
    const SHARED_SECTION = 'All buildings';

    // Buildings are ordered the way they appear in map.json, not alphabetically.
    function getBuildingSortIndex(buildingName) {
      if (!buildingName) return -1;
      const index = (buildings || []).findIndex((building) => String(building && building.name || '').trim() === buildingName);
      return index >= 0 ? index : 9999;
    }

    // Every node an option stands for (a group option stands for several nodes).
    function getOptionNodes(option) {
      if (option && Array.isArray(option.nodes) && option.nodes.length) return option.nodes;
      const node = getRouteSortNode(option);
      return node ? [node] : [];
    }

    function getOptionBuilding(option) {
      const names = new Set(getOptionNodes(option).map((node) => getNodeBuildingName(node)).filter(Boolean));
      if (names.size > 1) return SHARED_SECTION;
      return names.size === 1 ? [...names][0] : '';
    }

    function getOptionFloor(option) {
      const floors = getOptionNodes(option).map((node) => Number(node.floor ?? node.level ?? 1) || 1);
      return floors.length ? Math.min(...floors) : 1;
    }

    function getFloorOrdinalLabel(floor) {
      const n = Number(floor) || 1;
      const mod100 = n % 100;
      const suffix = (mod100 >= 11 && mod100 <= 13) ? 'th' : ({ 1: 'st', 2: 'nd', 3: 'rd' }[n % 10] || 'th');
      return `${n}${suffix} Floor`;
    }

    function getOptionSection(option, mode) {
      if (mode === 'building') return getOptionBuilding(option) || 'Outdoors';
      if (mode === 'floor') return getFloorOrdinalLabel(getOptionFloor(option));
      return '';
    }

    function sortDestinationOptions(options, mode = 'label', direction = 'asc') {
      const dir = direction === 'desc' ? -1 : 1;
      const byLabel = (a, b) => {
        const labelCompare = SORT_COLLATOR.compare(String(a && a.label || '').trim(), String(b && b.label || '').trim());
        if (labelCompare !== 0) return labelCompare * dir;
        return String(a && a.value !== undefined ? a.value : '').localeCompare(String(b && b.value !== undefined ? b.value : '')) * dir;
      };

      return [...options].sort((a, b) => {
        if (mode === 'building') {
          const aBuilding = getOptionBuilding(a);
          const bBuilding = getOptionBuilding(b);
          // Shared (multi-building) groups first, then buildings in map.json order.
          const aIndex = aBuilding === SHARED_SECTION ? -2 : getBuildingSortIndex(aBuilding);
          const bIndex = bBuilding === SHARED_SECTION ? -2 : getBuildingSortIndex(bBuilding);
          if (aIndex !== bIndex) return (aIndex - bIndex) * dir;
          return byLabel(a, b);
        }

        if (mode === 'floor') {
          const floorDiff = getOptionFloor(a) - getOptionFloor(b);
          if (floorDiff !== 0) return floorDiff * dir;
          return byLabel(a, b);
        }

        return byLabel(a, b);
      });
    }

    function buildRouteOptions(sortMode = 'label', sortDirection = 'asc') {
      const startOptions = [];
      const endOptions = [];
      const seenStart = new Set();
      const seenEnd = new Set();
      const groupLabels = {};
      const groupNodes = {};

      Object.values(nodes).forEach((node) => {
        if (!node || node.type === 'junction' || !!node.disableDestination) return;
        const parsedGroup = parseGroupValue(node.group);
        if (parsedGroup) {
          if (!groupNodes[parsedGroup.groupName]) groupNodes[parsedGroup.groupName] = [];
          groupNodes[parsedGroup.groupName].push(node);
          groupLabels[parsedGroup.groupName] = parsedGroup.routeLabel;
        }
      });

      Object.entries(groupLabels).forEach(([groupName, routeLabel]) => {
        const groupNode = (groupNodes[groupName] || []).find(Boolean) || null;
        endOptions.push({ label: routeLabel, value: groupName, node: groupNode, nodes: groupNodes[groupName] || [] });
      });

      Object.values(nodes)
        .filter((node) => !!node && node.type !== 'junction' && node.label && isGuestAllowedNode(node))
        .sort((a, b) => String(a.label).localeCompare(String(b.label)))
        .forEach((node) => {
          const key = node.id;
          const parsedGroup = parseGroupValue(node.group);
          const isDestinationDisabled = !!node.disableDestination;

          if (isDestinationDisabled) {
            return;
          }

          if (!seenStart.has(key)) {
            startOptions.push({ label: String(node.label).replace(/\s*\r?\n\s*/g, ' '), value: node.id, node });
            seenStart.add(key);
          }

          if (parsedGroup) {
            return;
          }

          if (!seenEnd.has(key)) {
            endOptions.push({
              label: String(node.label).replace(/\s*\r?\n\s*/g, ' '),
              value: node.id,
              node
            });
            seenEnd.add(key);
          }
        });

      const sortedEndOptions = sortDestinationOptions(endOptions, sortMode, sortDirection);
      const sortedStartOptions = sortDestinationOptions(startOptions, sortMode, sortDirection);

      return { startOptions: sortedStartOptions, endOptions: sortedEndOptions };
    }

    function escapeHtml(value) {
      return String(value).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
    }

    function renderSortedOptions(options, mode) {
      const optionHtml = (option) => `<option value="${escapeHtml(option.value)}">${escapeHtml(option.label)}</option>`;
      if (mode !== 'building' && mode !== 'floor') {
        return options.map(optionHtml).join('');
      }

      // Building / Floor sorting is shown as headed sections so the order is visible.
      let html = '';
      let currentSection = null;
      options.forEach((option) => {
        const section = getOptionSection(option, mode);
        if (section !== currentSection) {
          if (currentSection !== null) html += '</optgroup>';
          html += `<optgroup label="${escapeHtml(section)}">`;
          currentSection = section;
        }
        html += optionHtml(option);
      });
      if (currentSection !== null) html += '</optgroup>';
      return html;
    }

    function populateSelectors() {
      const selectedStartValue = startSelect.value || '';
      const selectedEndValue = endSelect.value || '';
      const sortMode = destinationSortSelect ? destinationSortSelect.value : 'label';
      const sortDirection = destinationSortSelect && destinationSortSelect.dataset.direction ? destinationSortSelect.dataset.direction : 'asc';
      const { startOptions, endOptions } = buildRouteOptions(sortMode, sortDirection);
      startSelect.innerHTML = '<option value="">Choose starting point...</option>' + renderSortedOptions(startOptions, sortMode);
      endSelect.innerHTML = '<option value="">Choose destination...</option>' + renderSortedOptions(endOptions, sortMode);
      if (selectedStartValue && startSelect.querySelector(`option[value="${CSS.escape(selectedStartValue)}"]`)) {
        startSelect.value = selectedStartValue;
      }
      if (selectedEndValue && endSelect.querySelector(`option[value="${CSS.escape(selectedEndValue)}"]`)) {
        endSelect.value = selectedEndValue;
      }
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
        const buildingColor = String(building.color || '#64748b');
        rect.setAttribute('fill', guestMap ? buildingColor : 'transparent');
        rect.setAttribute('fill-opacity', guestMap ? '0.06' : '0');
        rect.setAttribute('stroke', 'transparent');
        rect.setAttribute('stroke-opacity', '0');
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
        if (from.hideDisplay || to.hideDisplay) return;
        if (!nodeMatchesVisibleFloor(from) || !nodeMatchesVisibleFloor(to)) return;

        const line = document.createElementNS('http://www.w3.org/2000/svg', 'line');
        line.dataset.routeFrom = from.id;
        line.dataset.routeTo = to.id;
        line.setAttribute('x1', from.x);
        line.setAttribute('y1', from.y);
        line.setAttribute('x2', to.x);
        line.setAttribute('y2', to.y);
        line.setAttribute('class', 'edge');
        line.setAttribute('stroke', getFloorColor(getNodeFloor(from)));
        line.setAttribute('stroke-width', '3');
        const dimmed = isPathSegmentDimmed(from, to);
        line.style.opacity = dimmed ? '0.52' : '1';
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
        const lineCap = String(lineData.lineCap || 'round');

        line.setAttribute('x1', x1);
        line.setAttribute('y1', y1);
        line.setAttribute('x2', x2);
        line.setAttribute('y2', y2);
        line.setAttribute('transform', `rotate(${rotation} ${centerX} ${centerY})`);
        line.setAttribute('class', 'edge free-line');
        // Free lines are always solid and black.
        line.setAttribute('stroke', '#000000');
        line.setAttribute('stroke-width', String(width));
        line.setAttribute('stroke-linecap', lineCap);
        line.setAttribute('stroke-dasharray', 'none');
        line.style.stroke = '#000000';
        line.style.strokeDasharray = 'none';
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
      const fillColor = String(node.fontColor || '#1a2434');

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
      svgText.setAttribute('fill', fillColor);
      svgText.style.fill = fillColor;

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
      (state.highlightTargetIds || []).forEach((nodeId) => markPulse(nodeId, 'route-end'));
    }

    // ---- Quick destinations (dashboard Home tab) -------------------------------
    // The dashboard sends short target ids; each one points at the node(s) with these labels.
    const MAP_TARGET_ALIASES = {
      'wp-clinic': ['Clinic'],
      'wp-registrar': ['Registrar window'],
      'wp-cashier': ['Cashier Window'],
      'wp-osoad': ['OSOAD'],
      'wp-bmgs': ['BMGS'],
      'wp-lhs-principal': ['LHS Principal'],
      'wp-coed-office': ['COED Office']
    };

    function normalizeTargetText(value) {
      return String(value || '').toLowerCase().replace(/\s+/g, '');
    }

    function resolveMapTargetNodes(target) {
      const raw = String(target || '').trim();
      if (!raw) return [];
      if (nodes[raw] && nodes[raw].type !== 'junction' && isGuestAllowedNode(nodes[raw])) return [nodes[raw]];

      const labels = MAP_TARGET_ALIASES[raw.toLowerCase()] || [raw.replace(/^wp-/i, '').replace(/-/g, ' ')];
      const wanted = labels.map(normalizeTargetText);
      return Object.values(nodes).filter((node) => (
        node && node.type !== 'junction' && isGuestAllowedNode(node) && wanted.includes(normalizeTargetText(node.label))
      ));
    }

    window.highlightMapTargets = function (targets) {
      const list = Array.isArray(targets) ? targets : [targets];
      const found = [];
      list.forEach((target) => {
        resolveMapTargetNodes(target).forEach((node) => {
          if (!found.some((entry) => entry.id === node.id)) found.push(node);
        });
      });

      if (!found.length) {
        return false;
      }

      // Drop any route so only the chosen location is highlighted.
      startSelect.value = '';
      endSelect.value = '';
      state.primaryRoute = [];
      state.alternativeRoute = [];
      state.primaryStartId = null;
      state.primaryEndId = null;
      state.altEndId = null;
      state.selectedNodeId = null;
      state.selectedBuildingId = null;
      state.highlightTargetIds = found.map((node) => node.id);

      // Show the floor each highlighted node is on.
      found.forEach((node) => {
        const buildingId = getNodeBuildingId(node);
        if (buildingId) {
          state.buildingFloorState[getBuildingFloorKey(buildingId)] = getNodeFloor(node);
        }
      });
      state.activeFloor = getNodeFloor(found[0]);

      render();
      return true;
    };

    window.addEventListener('message', (event) => {
      const data = event.data || {};
      if (data.type === 'highlight-map-target') {
        window.highlightMapTargets(data.targets || (data.target ? [data.target] : []));
      }
    });

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
      const stairQueue = [];

      Object.values(nodes).forEach((node) => {
        if (node.type === 'junction') return;
        const guestLabelAllowed = isGuestAllowedNode(node);
        // "Remove display": the node stays active (routing, destinations) but is not drawn.
        if (node.hideDisplay && !(guestMap && guestLabelAllowed)) return;
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
          shape.style.opacity = '0.58';
        }

        const label = createMultilineSvgText(getNodeDisplayLabel(node), node.x, node.y, node);
        if (isStair || (guestMap && !guestLabelAllowed)) label.setAttribute('display', 'none');
        if (dimmed) label.style.opacity = '0.58';

        group.addEventListener('click', () => {
          if (guestMap && !isGuestAllowedNode(node)) return;
          state.selectedNodeId = node.id;
          state.selectedBuildingId = node.buildingId || null;
          if (node.type === 'stair' && (node.nextMap || node.nextJsonMap || node.targetMap || node.nextMapNodeId || node.nextNodeId || node.targetNodeId)) {
            navigateToNextMap(node);
            return;
          }
          showBuildingPopupForSelection();
          render();
        });

        group.appendChild(shape);
        group.appendChild(label);

        const queueEntry = { group, area: w * h };
        if (isStair) {
          stairQueue.push(queueEntry);
        } else if (isFillerNode) {
          fillerQueue.push(queueEntry);
        } else {
          drawQueue.push(queueEntry);
        }
      });

      // Filler blocks stay under the route overlay, stairs are in their own layer, and regular rooms stay above the route.
      const sortByDepth = (entries) => entries.sort((a, b) => {
        const aZ = Number.isFinite(Number(a.group.dataset.zIndex)) ? Number(a.group.dataset.zIndex) : 0;
        const bZ = Number.isFinite(Number(b.group.dataset.zIndex)) ? Number(b.group.dataset.zIndex) : 0;
        if (aZ !== bZ) return aZ - bZ;
        return b.area - a.area;
      });

      sortByDepth(stairQueue).forEach(({ group }) => { if (stairLayer) stairLayer.appendChild(group); });
      sortByDepth(fillerQueue).forEach(({ group }) => { if (fillerLayer) fillerLayer.appendChild(group); });
      sortByDepth(drawQueue).forEach(({ group }) => { if (roomLayer) roomLayer.appendChild(group); });

      applyRouteHighlighting();
    }

    function getRouteAnimationStartIndex(path) {
      if (!Array.isArray(path) || path.length < 2) return 0;
      let startIndex = 0;
      while (startIndex < path.length - 1) {
        const node = getNodeData(path[startIndex]);
        if (!node || node.type !== 'junction') break;
        startIndex += 1;
      }
      return startIndex;
    }

    function drawRoutePath(path, kind) {
      if (!Array.isArray(path) || path.length < 2) return;

      const routeSegments = [];
      const connectorMarkers = [];
      const animationStartIndex = kind === 'primary' ? getRouteAnimationStartIndex(path) : 0;
      for (let i = animationStartIndex; i < path.length - 1; i++) {
        const from = getNodeData(path[i]);
        const to = getNodeData(path[i + 1]);
        if (!from || !to) continue;

        // A segment shows when either end is on its building's visible floor, so the two
        // popups (start / destination building) can sit on different floors at once.
        const fromVisible = nodeMatchesVisibleFloor(from);
        const toVisible = nodeMatchesVisibleFloor(to);
        // Paths on floors above the active floor are hidden; paths below it are dimmed (see getRouteSegmentFloorState).
        if (getRouteSegmentFloorState(from, to) === 'hide') continue;

        const fromFloor = getNodeFloor(from);
        const toFloor = getNodeFloor(to);
        const fromContextFloor = getNodeContextFloor(from);
        const toContextFloor = getNodeContextFloor(to);

        const preferredActiveFloorNode = (fromFloor === fromContextFloor || toFloor === toContextFloor)
          ? (fromFloor === fromContextFloor ? from : to)
          : (fromVisible ? from : to);
        const visibleNode = preferredActiveFloorNode || (fromVisible ? from : to);
        const baseColor = kind === 'alt' ? '#8d99ae' : getFloorColor(getNodeFloor(visibleNode));
        const stairStep = from.type === 'stair' || to.type === 'stair' || fromFloor !== toFloor;

        const segmentLength = Math.max(1, Math.hypot(to.x - from.x, to.y - from.y));
        const segmentDuration = stairStep ? 1500 : Math.max(1400, Math.min(3600, segmentLength / 0.12));
        const segmentData = {
          from,
          to,
          stairStep,
          color: baseColor,
          length: segmentLength,
          duration: segmentDuration,
          dimmed: isRouteSegmentDimmed(from, to),
          fromFloor,
          toFloor,
          line: null
        };

        // Floor connectors (stairs, lifts, etc.) get an up/down triangle where the route changes floors.
        if (kind === 'primary' && fromFloor !== toFloor) {
          const direction = toFloor > fromFloor ? 'up' : 'down';
          [from, to].forEach((connectorNode) => {
            if (!connectorNode || connectorNode.floorConnector !== true) return;
            // Follow the floor toggle: only the connector on the floor currently shown gets its triangle.
            if (!nodeMatchesVisibleFloor(connectorNode) || !isRouteNodeOnToggledFloor(connectorNode)) return;
            connectorMarkers.push({ node: connectorNode, direction, targetFloor: toFloor });
          });
        }

        const line = document.createElementNS('http://www.w3.org/2000/svg', 'line');
        line.dataset.routeFrom = from.id;
        line.dataset.routeTo = to.id;
        line.setAttribute('x1', from.x);
        line.setAttribute('y1', from.y);
        line.setAttribute('x2', to.x);
        line.setAttribute('y2', to.y);
        line.setAttribute('class', kind === 'alt' ? 'edge route alt-active' : `edge route active${stairStep ? ' route-stair' : ' route-reveal'}`);
        line.setAttribute('stroke', kind === 'alt' ? '#8d99ae' : baseColor);
        line.setAttribute('stroke-width', kind === 'alt' ? '5' : (stairStep ? '5' : '8'));
        line.style.stroke = kind === 'alt' ? '#8d99ae' : baseColor;
        if (fromFloor !== toFloor) {
          line.style.setProperty('--route-from-color', getFloorColor(fromFloor));
          line.style.setProperty('--route-to-color', getFloorColor(toFloor));
        }

        line.style.setProperty('--route-length', `${Math.max(24, Math.ceil(segmentLength))}`);
        line.style.setProperty('--route-duration', `${segmentDuration}ms`);
        line.style.setProperty('--route-delay', '0ms');

        line.style.opacity = kind === 'alt' ? '0.18' : (segmentData.dimmed ? '0.52' : '1');
        line.style.strokeLinecap = 'round';
        line.style.strokeLinejoin = 'round';
        line.style.animation = 'none';

        if (kind === 'primary') {
          const guide = line.cloneNode();
          guide.setAttribute('class', 'edge route route-guide');
          guide.setAttribute('stroke-width', stairStep ? '3' : '4');
          guide.style.strokeDasharray = 'none';
          guide.style.opacity = '0.14';
          guide.style.filter = 'none';
          routeLayer.appendChild(guide);

          segmentData.line = line;
          line.style.strokeDasharray = `0 ${segmentLength}`;
          routeSegments.push(segmentData);
        }

        routeLayer.appendChild(line);
      }

      if (kind === 'primary' && routeSegments.length) {
        state.routeAnimationSegments = routeSegments;
      }

      if (kind === 'primary') {
        renderFloorConnectorMarkers(connectorMarkers);
      }
    }

    function renderFloorConnectorMarkers(markers) {
      if (!floorConnectorLayer) return;
      floorConnectorLayer.innerHTML = '';

      // One triangle per direction per node (a node can be both arrival and departure).
      // Keyed by position, so linked connectors stacked on the same spot (e.g. a 1F and 2F junction) draw once.
      const byNode = new Map();
      markers.forEach((marker) => {
        const key = `${Math.round(Number(marker.node.x))}|${Math.round(Number(marker.node.y))}`;
        const list = byNode.get(key) || [];
        if (!list.some((entry) => entry.direction === marker.direction)) list.push(marker);
        byNode.set(key, list);
      });

      byNode.forEach((list) => {
        list.forEach((marker, index) => {
          const node = marker.node;
          const w = Number(node.width) || 52;
          const h = Number(node.height) || 44;
          const size = Math.max(16, Math.min(30, Math.min(w, h) * 0.55));
          const half = size / 2;
          const offsetX = list.length > 1 ? (index === 0 ? -1 : 1) * size * 0.6 : 0;

          const holder = document.createElementNS('http://www.w3.org/2000/svg', 'g');
          holder.setAttribute('class', 'floor-connector-marker');
          holder.setAttribute('transform', `translate(${Number(node.x) + offsetX} ${Number(node.y)})`);
          holder.dataset.nodeId = node.id;
          holder.dataset.direction = marker.direction;

          const triangle = document.createElementNS('http://www.w3.org/2000/svg', 'polygon');
          triangle.setAttribute('class', `floor-connector-triangle floor-connector-${marker.direction}`);
          triangle.setAttribute(
            'points',
            marker.direction === 'up'
              ? `0,${-half} ${half},${half} ${-half},${half}`
              : `${-half},${-half} ${half},${-half} 0,${half}`
          );
          triangle.setAttribute('fill', getFloorColor(marker.targetFloor));
          triangle.setAttribute('stroke', '#ffffff');
          triangle.setAttribute('stroke-width', '3');
          triangle.setAttribute('stroke-linejoin', 'round');
          triangle.setAttribute('paint-order', 'stroke');

          holder.appendChild(triangle);
          floorConnectorLayer.appendChild(holder);
        });
      });
    }

    function animateRouteDot() {
      if (!routeMarkerLayer || !state.routeAnimationSegments.length) return;

      if (state.routeAnimationFrameId !== null) {
        cancelAnimationFrame(state.routeAnimationFrameId);
        state.routeAnimationFrameId = null;
      }

      routeMarkerLayer.innerHTML = '';
      const halo = document.createElementNS('http://www.w3.org/2000/svg', 'circle');
      halo.setAttribute('class', 'route-flow-halo');
      halo.setAttribute('r', '16');
      halo.setAttribute('cx', state.routeAnimationSegments[0].from.x);
      halo.setAttribute('cy', state.routeAnimationSegments[0].from.y);
      halo.style.fill = state.routeAnimationSegments[0].color;

      const dot = document.createElementNS('http://www.w3.org/2000/svg', 'circle');
      dot.setAttribute('class', 'route-flow-point');
      dot.setAttribute('r', '7');
      dot.setAttribute('cx', state.routeAnimationSegments[0].from.x);
      dot.setAttribute('cy', state.routeAnimationSegments[0].from.y);
      dot.style.fill = state.routeAnimationSegments[0].color;

      const arrow = document.createElementNS('http://www.w3.org/2000/svg', 'polygon');
      arrow.setAttribute('class', 'route-flow-arrow');
      arrow.setAttribute('points', '16,0 0,8 5,0 0,-8');
      arrow.setAttribute('transform', `translate(${state.routeAnimationSegments[0].from.x} ${state.routeAnimationSegments[0].from.y}) rotate(0)`);
      arrow.style.fill = state.routeAnimationSegments[0].color;

      routeMarkerLayer.appendChild(halo);
      routeMarkerLayer.appendChild(dot);
      routeMarkerLayer.appendChild(arrow);

      const totalPathLength = state.routeAnimationSegments.reduce((sum, segment) => sum + Math.max(1, segment.length), 0);
      const baseDuration = 1800;
      const dynamicDuration = Math.max(baseDuration, totalPathLength * 4);
      const startAt = performance.now();

      function tick(now) {
        const elapsed = (now - startAt) % dynamicDuration;
        const progress = elapsed / dynamicDuration;
        const targetDistance = progress * totalPathLength;

        let travelled = 0;
        let activeSegment = state.routeAnimationSegments[state.routeAnimationSegments.length - 1];
        let segmentProgress = 1;

        for (const segment of state.routeAnimationSegments) {
          const segmentLength = Math.max(1, segment.length);
          const segmentEnd = travelled + segmentLength;
          if (targetDistance <= segmentEnd) {
            activeSegment = segment;
            segmentProgress = segmentLength > 0 ? (targetDistance - travelled) / segmentLength : 1;
            break;
          }
          travelled += segmentLength;
        }

        travelled = 0;
        for (const segment of state.routeAnimationSegments) {
          const segmentLength = Math.max(1, segment.length);
          const segmentEnd = travelled + segmentLength;
          const revealedLength = Math.max(0, Math.min(segmentLength, targetDistance - travelled));
          segment.line.style.strokeDasharray = revealedLength >= segmentLength
            ? 'none'
            : `${revealedLength} ${segmentLength * 2}`;
          const isCurrentSegment = targetDistance >= travelled && targetDistance < segmentEnd;
          segment.line.style.opacity = isCurrentSegment
            ? (segment.dimmed ? '0.52' : '1')
            : revealedLength > 0 ? '0.16' : '0';
          travelled = segmentEnd;
        }

        const x = activeSegment.from.x + (activeSegment.to.x - activeSegment.from.x) * segmentProgress;
        const y = activeSegment.from.y + (activeSegment.to.y - activeSegment.from.y) * segmentProgress;
        const dx = activeSegment.to.x - activeSegment.from.x;
        const dy = activeSegment.to.y - activeSegment.from.y;
        const angle = Math.atan2(dy, dx) * (180 / Math.PI);

        halo.setAttribute('cx', x);
        halo.setAttribute('cy', y);
        dot.setAttribute('cx', x);
        dot.setAttribute('cy', y);
        arrow.setAttribute('transform', `translate(${x} ${y}) rotate(${angle})`);
        halo.style.fill = activeSegment.color;
        dot.style.fill = activeSegment.color;
        arrow.style.fill = activeSegment.color;
        syncFloorControlToRouteSegment(activeSegment, segmentProgress);

        state.routeAnimationFrameId = requestAnimationFrame(tick);
      }

      state.routeAnimationFrameId = requestAnimationFrame(tick);
    }

    function renderRoute() {
      routeLayer.innerHTML = '';
      if (routeMarkerLayer) routeMarkerLayer.innerHTML = '';
      if (floorConnectorLayer) floorConnectorLayer.innerHTML = '';
      state.routeAnimationSegments = [];
      if (state.routeAnimationFrameId !== null) {
        cancelAnimationFrame(state.routeAnimationFrameId);
        state.routeAnimationFrameId = null;
      }
      drawRoutePath(state.alternativeRoute, 'alt');
      drawRoutePath(state.primaryRoute, 'primary');
      animateRouteDot();
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
      const primaryDestinationNodes = isGroupDestination ? [...NODE_GROUPS[endVal]] : groupDestinationNodes;

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
        const altCandidates = NODE_GROUPS[endVal].filter((nodeId) => nodeId !== bestMatch.endNode);
        for (const startNode of startNodes) {
          for (const altNode of altCandidates) {
            if (!altNode || startNode === altNode) continue;
            const result = findShortestPath(startNode, altNode);
            if (!result || !result.path || !result.path.length || !Number.isFinite(result.distance)) continue;
            if (!altMatch || result.distance > altMatch.distance) {
              altMatch = { distance: result.distance, path: result.path, startNode, endNode: altNode };
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
      if (guestMap) return;
      const startVal = startSelect.value;
      const endVal = endSelect.value;
      state.highlightTargetIds = [];

      if (!startVal || !endVal) {
        state.primaryRoute = [];
        state.alternativeRoute = [];
        state.primaryStartId = null;
        state.primaryEndId = null;
        state.altEndId = null;
        renderRoute();
        applyRouteHighlighting();
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
        return;
      }

      state.primaryRoute = routeMatch.path;
      state.alternativeRoute = routeMatch.altPath || [];
      state.primaryStartId = routeMatch.startNode;
      state.primaryEndId = routeMatch.endNode;
      state.altEndId = routeMatch.altPath && routeMatch.altPath.length ? routeMatch.altPath[routeMatch.altPath.length - 1] : null;

      if (ionicMap && window.parent !== window) {
        const startNode = getNodeData(routeMatch.startNode);
        const destinationNode = getNodeData(routeMatch.endNode);
        window.parent.postMessage({
          type: 'ionic-audit',
          event: 'MAP_ROUTE',
          start: getSearchDisplayName(startNode),
          destination: getSearchDisplayName(destinationNode)
        }, window.location.origin);
      }

      // Seed a floor toggle for the start AND the destination building, each on the
      // floor of its own endpoint, so both popups work independently at the same time.
      [routeMatch.startNode, routeMatch.endNode].forEach((nodeId) => {
        const endpoint = getNodeData(nodeId);
        const endpointBuildingId = getNodeBuildingId(endpoint);
        if (!endpointBuildingId) return;
        state.buildingFloorState[getBuildingFloorKey(endpointBuildingId)] = Number(getNodeFloor(endpoint)) || 1;
      });

      const routeBuildingNode = getNodeData(routeMatch.endNode);
      state.selectedNodeId = routeBuildingNode?.id || null;
      state.selectedBuildingId = getNodeBuildingId(routeBuildingNode);

      // Prefer the highest floor in the computed route so destinations on upper floors
      // automatically reveal their path instead of leaving the route hidden behind the
      // starting floor's view.
      const routeFloorValues = (routeMatch.path || []).map((nodeId) => {
        const node = getNodeData(nodeId);
        return Number(getNodeFloor(node)) || 1;
      });
      if (routeFloorValues.length) {
        state.activeFloor = Math.max(...routeFloorValues);
      }
      render();
      animateMapLayers([routeLayer, routeMarkerLayer, floorConnectorLayer], 420);
      if (ionicMap && document.body.classList.contains('ionic-controls-open')) {
        document.body.classList.remove('ionic-controls-open');
        ionicControlsToggle?.setAttribute('aria-expanded', 'false');
        ionicControlsClose?.setAttribute('aria-expanded', 'false');
      }
    }

    function reorderMapLayers() {
      const orderedLayers = [
        buildingLayer,
        edgeLayer,
        fillerLayer,
        stairLayer,
        routeLayer,
        roomLayer,
        routeMarkerLayer,
        floorConnectorLayer
      ].filter(Boolean);

      orderedLayers.forEach((layer) => {
        if (!layer || layer.parentNode !== svg) return;
        svg.removeChild(layer);
      });

      orderedLayers.forEach((layer) => {
        if (layer && layer.parentNode !== svg) {
          svg.appendChild(layer);
        }
      });

      orderedLayers.forEach((layer) => {
        if (layer && layer.parentNode === svg) {
          svg.appendChild(layer);
        }
      });
    }

    function render() {
      renderBuildings();
      renderEdges();
      renderRoute();
      renderNodes();
      updateFloorToggleButtons();
      renderBuildingFloorPopup();
      reorderMapLayers();
    }

    let currentViewBox = { ...baseViewBox };

    function setMapView(nextZoom, nextX, nextY) {
      const zoom = Math.min(3, Math.max(0.65, Number(nextZoom) || 1));
      const width = baseViewBox.width / zoom;
      const height = baseViewBox.height / zoom;
      const minX = zoom >= 1 ? 0 : (baseViewBox.width - width) / 2;
      const minY = zoom >= 1 ? 0 : (baseViewBox.height - height) / 2;
      const maxX = zoom >= 1 ? baseViewBox.width - width : minX;
      const maxY = zoom >= 1 ? baseViewBox.height - height : minY;
      const x = Math.min(maxX, Math.max(minX, Number(nextX) || 0));
      const y = Math.min(maxY, Math.max(minY, Number(nextY) || 0));
      state.zoom = zoom;
      currentViewBox = { x, y, width, height };
      svg.setAttribute('viewBox', `${x} ${y} ${width} ${height}`);
      positionBuildingFloorPopups();
      const level = document.getElementById('mapZoomLevel');
      if (level) level.textContent = `${Math.round(zoom * 100)}%`;
    }

    function setMapZoom(nextZoom) {
      const width = baseViewBox.width / Math.min(3, Math.max(0.65, Number(nextZoom) || 1));
      const height = baseViewBox.height / Math.min(3, Math.max(0.65, Number(nextZoom) || 1));
      setMapView(nextZoom, currentViewBox.x + (currentViewBox.width - width) / 2, currentViewBox.y + (currentViewBox.height - height) / 2);
    }

    window.addEventListener('resize', () => requestAnimationFrame(positionBuildingFloorPopups));

    if (ionicMap) {
      const canvasWrap = document.querySelector('.canvas-wrap');
      const pointers = new Map();
      let gestureStart = null;

      function beginGesture() {
        const active = [...pointers.values()];
        if (!active.length) return;
        const midpoint = active.length > 1
          ? { x: (active[0].x + active[1].x) / 2, y: (active[0].y + active[1].y) / 2 }
          : active[0];
        gestureStart = {
          zoom: state.zoom,
          viewBox: { ...currentViewBox },
          midpoint,
          distance: active.length > 1 ? Math.hypot(active[1].x - active[0].x, active[1].y - active[0].y) : 0,
        };
      }

      canvasWrap?.addEventListener('pointerdown', (event) => {
        if (event.pointerType !== 'touch') return;
        event.preventDefault();
        pointers.set(event.pointerId, { x: event.clientX, y: event.clientY });
        beginGesture();
      });

      canvasWrap?.addEventListener('pointermove', (event) => {
        if (!pointers.has(event.pointerId) || !gestureStart) return;
        event.preventDefault();
        pointers.set(event.pointerId, { x: event.clientX, y: event.clientY });
        const active = [...pointers.values()];
        const rect = canvasWrap.getBoundingClientRect();
        if (active.length > 1 && gestureStart.distance > 0) {
          const midpoint = { x: (active[0].x + active[1].x) / 2, y: (active[0].y + active[1].y) / 2 };
          const distance = Math.hypot(active[1].x - active[0].x, active[1].y - active[0].y);
          const zoom = gestureStart.zoom * distance / gestureStart.distance;
          const boundedZoom = Math.min(3, Math.max(0.65, zoom));
          const width = baseViewBox.width / boundedZoom;
          const height = baseViewBox.height / boundedZoom;
          const focalX = gestureStart.viewBox.x + ((gestureStart.midpoint.x - rect.left) / rect.width) * gestureStart.viewBox.width;
          const focalY = gestureStart.viewBox.y + ((gestureStart.midpoint.y - rect.top) / rect.height) * gestureStart.viewBox.height;
          const x = focalX - ((midpoint.x - rect.left) / rect.width) * width;
          const y = focalY - ((midpoint.y - rect.top) / rect.height) * height;
          setMapView(zoom, x, y);
          return;
        }

        const pointer = active[0];
        const deltaX = pointer.x - gestureStart.midpoint.x;
        const deltaY = pointer.y - gestureStart.midpoint.y;
        setMapView(
          gestureStart.zoom,
          gestureStart.viewBox.x - deltaX / rect.width * gestureStart.viewBox.width,
          gestureStart.viewBox.y - deltaY / rect.height * gestureStart.viewBox.height,
        );
      });

      function endGesture(event) {
        if (!pointers.delete(event.pointerId)) return;
        if (pointers.size) beginGesture();
        else gestureStart = null;
      }

      canvasWrap?.addEventListener('pointerup', endGesture);
      canvasWrap?.addEventListener('pointercancel', endGesture);
      canvasWrap?.addEventListener('wheel', (event) => {
        event.preventDefault();
        setMapZoom(state.zoom * (event.deltaY < 0 ? 1.1 : 1 / 1.1));
      }, { passive: false });

      document.getElementById('mapZoomIn')?.addEventListener('click', () => setMapZoom(state.zoom * 1.2));
      document.getElementById('mapZoomOut')?.addEventListener('click', () => setMapZoom(state.zoom / 1.2));
      document.getElementById('mapZoomReset')?.addEventListener('click', () => setMapZoom(1));
    }

    document.querySelectorAll('.floor-toggle-btn').forEach((button) => {
      button.addEventListener('click', () => setActiveFloor(Number(button.dataset.floor) || 1));
    });

    function syncSortButtons() {
      const sortMode = destinationSortSelect ? destinationSortSelect.value : 'label';
      const sortDirection = destinationSortSelect && destinationSortSelect.dataset.direction ? destinationSortSelect.dataset.direction : 'asc';

      document.querySelectorAll('.sort-option').forEach((option) => {
        option.classList.toggle('active', option.dataset.mode === sortMode);
      });

      document.querySelectorAll('.sort-arrow').forEach((button) => {
        const option = button.closest('.sort-option');
        const isActive = option && option.dataset.mode === sortMode && button.dataset.direction === sortDirection;
        button.classList.toggle('active', !!isActive);
      });
    }

    if (destinationSortSelect) {
      destinationSortSelect.addEventListener('change', () => {
        if (!destinationSortSelect.dataset.direction) {
          destinationSortSelect.dataset.direction = 'asc';
        }
        syncSortButtons();
        populateSelectors();
      });
    }

    document.querySelectorAll('.sort-option').forEach((option) => {
      option.addEventListener('click', (event) => {
        if (event.target.closest('.sort-arrow')) return;
        const mode = option.dataset.mode || 'label';
        destinationSortSelect.value = mode;
        if (!destinationSortSelect.dataset.direction) {
          destinationSortSelect.dataset.direction = 'asc';
        }
        syncSortButtons();
        populateSelectors();
      });

      option.addEventListener('keydown', (event) => {
        if (event.key === 'Enter' || event.key === ' ') {
          event.preventDefault();
          const mode = option.dataset.mode || 'label';
          destinationSortSelect.value = mode;
          if (!destinationSortSelect.dataset.direction) {
            destinationSortSelect.dataset.direction = 'asc';
          }
          syncSortButtons();
          populateSelectors();
        }
      });
    });

    document.querySelectorAll('.sort-arrow').forEach((button) => {
      button.addEventListener('click', (event) => {
        event.stopPropagation();
        const option = button.closest('.sort-option');
        const mode = option ? option.dataset.mode : 'label';
        const direction = button.dataset.direction || 'asc';
        destinationSortSelect.value = mode;
        destinationSortSelect.dataset.direction = direction;
        syncSortButtons();
        populateSelectors();
      });
    });

    if (destinationSortSelect) {
      destinationSortSelect.dataset.direction = 'asc';
      destinationSortSelect.value = 'label';
    }

    syncSortButtons();

    startSelect.addEventListener('change', (event) => {
      handleRouteSelectionChange(event.target.value, true);
    });

    endSelect.addEventListener('change', (event) => {
      handleRouteSelectionChange(event.target.value, false);
    });

    document.getElementById('findPathBtn').addEventListener('click', doRoute);
    document.getElementById('resetBtn').addEventListener('click', () => {
      startSelect.value = '';
      endSelect.value = '';
      state.route = [];
      state.primaryRoute = [];
      state.alternativeRoute = [];
      state.routeAnimationSegments = [];
      state.primaryStartId = null;
      state.primaryEndId = null;
      state.altEndId = null;
      state.selectedNodeId = null;
      state.selectedBuildingId = null;
      state.highlightTargetIds = [];
      state.activeFloor = 1;
      state.buildingFloorState = {};
      if (typeof clearSearchBox === 'function') clearSearchBox();

      [0, 1].forEach((slot) => {
        const popup = getFloorPopupElement(slot);
        if (popup) clearFloorPopup(popup);
      });

      renderRoute();
      applyRouteHighlighting();
      updateFloorToggleButtons();
      render();
    });

    // ---- Room / tag search ------------------------------------------------------
    // Matches the start of any word in a room's name or in any of its tags, so typing
    // "b", "bo", "boo" or "book" all surface the Library (tag: books) as you type.
    const searchInput = document.getElementById('roomSearchInput');
    const searchResultsEl = document.getElementById('roomSearchResults');
    const searchHintEl = document.getElementById('roomSearchHint');
    const searchClearBtn = document.getElementById('roomSearchClear');
    const SEARCH_LIMIT = 10;
    let searchIndex = [];
    let searchCurrent = [];
    let searchActiveIndex = -1;

    function tokenizeSearchText(text) {
      return String(text || '').toLowerCase().split(/[^a-z0-9]+/).filter(Boolean);
    }

    function getSearchDisplayName(node) {
      const parts = String(node.label == null ? '' : node.label)
        .split(/\r?\n/)
        .map((part) => part.trim())
        .filter(Boolean);
      // Vertical lettering such as C / A / N / T / E / E / N reads as a single word.
      if (parts.length > 1 && parts.every((part) => part.length === 1)) return parts.join('');
      return parts.join(' ');
    }

    function buildSearchIndex() {
      searchIndex = [];
      Object.values(nodes).forEach((node) => {
        if (!node || node.type === 'junction' || node.type === 'stair' || node.disableDestination) return;
        const name = getSearchDisplayName(node);
        if (!name || /filler/i.test(name)) return;

        const seenTags = new Set();
        const tags = [];
        (Array.isArray(node.tags) ? node.tags : []).forEach((tag) => {
          const text = String(tag || '').trim();
          const lower = text.toLowerCase();
          if (!text || seenTags.has(lower)) return;
          seenTags.add(lower);
          tags.push({ text, lower, words: tokenizeSearchText(text) });
        });

        searchIndex.push({
          node,
          name,
          nameLower: name.toLowerCase(),
          nameWords: tokenizeSearchText(name),
          tags,
          building: getNodeBuildingName(node),
          floor: getNodeFloor(node)
        });
      });
    }

    // Score one query word against one entry. 0 means no match.
    function scoreSearchToken(entry, query) {
      let nameScore = 0;
      if (entry.nameLower.startsWith(query)) nameScore = 100;
      else if (entry.nameWords.some((word) => word.startsWith(query))) nameScore = 90;

      let tagScore = 0;
      let tagText = null;
      entry.tags.forEach((tag) => {
        let score = 0;
        if (tag.lower === query) score = 75;
        else if (tag.lower.startsWith(query)) score = 65;
        else if (tag.words.some((word) => word.startsWith(query))) score = 55;
        if (score > tagScore) { tagScore = score; tagText = tag.text; }
      });

      // Mid-word matches only count for 3+ letters and when nothing matched at a word start.
      if (!nameScore && !tagScore && query.length >= 3) {
        if (entry.nameLower.includes(query)) nameScore = 30;
        entry.tags.forEach((tag) => {
          if (tag.lower.includes(query) && 25 > tagScore) { tagScore = 25; tagText = tag.text; }
        });
      }

      const score = Math.max(nameScore, tagScore);
      // Only report the tag when the name itself did not match.
      return { score, via: nameScore ? null : tagText };
    }

    function runSearch(rawQuery) {
      const queryWords = tokenizeSearchText(rawQuery);
      if (!queryWords.length) return [];

      const results = [];
      searchIndex.forEach((entry) => {
        let total = 0;
        const via = [];
        for (const word of queryWords) {
          const result = scoreSearchToken(entry, word);
          if (!result.score) return;
          total += result.score;
          if (result.via && !via.includes(result.via)) via.push(result.via);
        }
        results.push({ entry, score: total, via });
      });

      results.sort((a, b) => (
        (b.score - a.score)
        || SORT_COLLATOR.compare(a.entry.name, b.entry.name)
        || (a.entry.floor - b.entry.floor)
      ));
      return results.slice(0, SEARCH_LIMIT);
    }

    // Bold the part of each word the user has typed so far.
    function highlightSearchMatch(text, queryWords) {
      return String(text).split(/([A-Za-z0-9]+)/).map((part) => {
        if (!/^[A-Za-z0-9]+$/.test(part)) return escapeHtml(part);
        const lower = part.toLowerCase();
        const hit = queryWords.find((q) => lower.startsWith(q));
        if (!hit) return escapeHtml(part);
        return `<mark>${escapeHtml(part.slice(0, hit.length))}</mark>${escapeHtml(part.slice(hit.length))}`;
      }).join('');
    }

    function setSearchActive(index) {
      const items = searchResultsEl.querySelectorAll('.search-result');
      if (!items.length) { searchActiveIndex = -1; return; }
      searchActiveIndex = (index + items.length) % items.length;
      items.forEach((item, i) => {
        const active = i === searchActiveIndex;
        item.classList.toggle('active', active);
        item.setAttribute('aria-selected', active ? 'true' : 'false');
        if (active) item.scrollIntoView({ block: 'nearest' });
      });
    }

    function closeSearchResults() {
      searchResultsEl.hidden = true;
      searchInput.setAttribute('aria-expanded', 'false');
      searchActiveIndex = -1;
    }

    function renderSearchResults() {
      const value = searchInput.value;
      searchClearBtn.classList.toggle('visible', !!value);
      const queryWords = tokenizeSearchText(value);
      if (!queryWords.length) {
        searchCurrent = [];
        searchResultsEl.innerHTML = '';
        closeSearchResults();
        return;
      }

      searchCurrent = runSearch(value);
      if (!searchCurrent.length) {
        searchResultsEl.innerHTML = `<li class="search-empty" role="presentation">No rooms match &ldquo;${escapeHtml(value.trim())}&rdquo;</li>`;
      } else {
        searchResultsEl.innerHTML = searchCurrent.map((result, index) => {
          const { entry, via } = result;
          const where = `${escapeHtml(entry.building || 'Outdoors')} &middot; ${entry.floor}F`;
          const viaHtml = via.length ? `<span class="search-via">${escapeHtml(via.join(', '))}</span>` : '';
          return `<li class="search-result" role="option" id="roomSearchOption${index}" data-index="${index}" aria-selected="false">`
            + `<span>${highlightSearchMatch(entry.name, queryWords)}</span>`
            + `<span class="search-result-meta"><span>${where}</span>${viaHtml}</span></li>`;
        }).join('');
      }
      searchResultsEl.hidden = false;
      searchInput.setAttribute('aria-expanded', 'true');
      setSearchActive(searchCurrent.length ? 0 : -1);
    }

    function pickSearchResult(index) {
      const result = searchCurrent[index];
      if (!result) return;
      const { node } = result.entry;

      if (ionicMap && window.parent !== window) {
        window.parent.postMessage({
          type: 'ionic-audit',
          event: 'MAP_DESTINATION',
          destination: getSearchDisplayName(node)
        }, window.location.origin);
      }

      // Show the room on the map (switches to its floor)...
      window.highlightMapTargets([node.id]);

      // ...and make it the destination, so only a starting point is left to choose.
      const parsedGroup = parseGroupValue(node.group);
      const candidates = [parsedGroup && parsedGroup.groupName, node.id].filter(Boolean);
      const destinationValue = candidates.find((value) => endSelect.querySelector(`option[value="${CSS.escape(value)}"]`));
      if (destinationValue) {
        endSelect.value = destinationValue;
        handleRouteSelectionChange(destinationValue, false);
      }

      searchInput.value = result.entry.name;
      searchClearBtn.classList.add('visible');
      closeSearchResults();
      searchHintEl.textContent = destinationValue
        ? `${result.entry.name} is set as your destination. Pick a starting point, then press Find Path.`
        : `Showing ${result.entry.name} on the map.`;
    }

    function clearSearchBox() {
      searchInput.value = '';
      searchCurrent = [];
      searchResultsEl.innerHTML = '';
      searchHintEl.textContent = '';
      searchClearBtn.classList.remove('visible');
      closeSearchResults();
    }

    searchInput.addEventListener('input', () => {
      searchHintEl.textContent = '';
      renderSearchResults();
    });

    searchInput.addEventListener('focus', () => {
      if (searchInput.value.trim()) renderSearchResults();
    });

    searchInput.addEventListener('keydown', (event) => {
      if (event.key === 'ArrowDown') {
        event.preventDefault();
        if (searchResultsEl.hidden) renderSearchResults();
        else setSearchActive(searchActiveIndex + 1);
      } else if (event.key === 'ArrowUp') {
        event.preventDefault();
        setSearchActive(searchActiveIndex - 1);
      } else if (event.key === 'Enter') {
        if (!searchResultsEl.hidden && searchActiveIndex >= 0) {
          event.preventDefault();
          pickSearchResult(searchActiveIndex);
        }
      } else if (event.key === 'Escape') {
        if (!searchResultsEl.hidden) {
          event.preventDefault();
          closeSearchResults();
        } else if (searchInput.value) {
          clearSearchBox();
        }
      }
    });

    // mousedown (not click) so the input keeps focus and the list does not close first.
    searchResultsEl.addEventListener('mousedown', (event) => {
      const item = event.target.closest('.search-result');
      if (!item) return;
      event.preventDefault();
      pickSearchResult(Number(item.dataset.index));
    });

    searchClearBtn.addEventListener('click', () => {
      clearSearchBox();
      searchInput.focus();
    });

    document.addEventListener('mousedown', (event) => {
      if (!event.target.closest('.search-input-wrap') && !event.target.closest('#roomSearchResults')) {
        closeSearchResults();
      }
    });

    buildSearchIndex();

    populateSelectors();
    if (guestMap) {
      const guestBuilding = buildings.find((building) => String(building && building.name || '').trim().toLowerCase() === 'cict');
      if (guestBuilding) {
        const buildingKey = getBuildingFloorKey(guestBuilding.id);
        state.buildingFloorState[buildingKey] = 1;
        state.activeFloor = state.buildingFloorState[buildingKey];
      }
    }
    render();
  </script>
</body>

</html>