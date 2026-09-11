<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Campus Navigation</title>

  <!-- Bootstrap 5 CSS & FontAwesome -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

  <style>
    :root {
      --primary-color: #1d3698;
      --accent-color: #f8f9fa;
    }

    body.map-body {
      font-family: 'Inter', sans-serif;
      background-color: #f4f7f6;
      overflow-x: hidden;
    }

    .map-wrapper {
      background: #ffffff;
      box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
      padding: 1.5rem;
      border-radius: 12px;
    }

    .fixed-canvas {
      width: 100%;
      max-width: 1000px;
      aspect-ratio: 1000 / 800;
      min-height: 300px;
      position: relative;
      background-color: #eef2f5;
      overflow: hidden;
      margin: 0 auto;
    }

    /* Color Palette */
    .bg-room {
      background-color: #0dcaf0;
    }

    .bg-help {
      background-color: #7bff74;
    }

    .bg-clinic {
      background-color: #ffffff;
      border: 1px solid #ced4da;
    }

    .bg-male {
      background-color: #5AC1F2;
    }

    .bg-female {
      background-color: #F576AB;
    }

    .bg-gym {
      background-color: #434243
    }

    /* Custom Stairs Style with Tread Pattern */
    .bg-stairs {
      background-color: #B28859;
      background-image: repeating-linear-gradient(0deg,
          rgba(0, 0, 0, 0.25) 0px,
          rgba(0, 0, 0, 0.25) 2px,
          transparent 2px,
          transparent 6px);
      box-shadow: inset 0 0 0 1px rgba(0, 0, 0, 0.2);
    }

    /* Vertical stairs pattern for tall narrow staircases */
    .stairs-educ1,
    .stairs-educ2,
    .stairs-lab,
    .stairs-lhs {
      background-image: repeating-linear-gradient(90deg,
          rgba(0, 0, 0, 0.25) 0px,
          rgba(0, 0, 0, 0.25) 2px,
          transparent 2px,
          transparent 6px);
    }

    /* Base Stair Styling */
    .bg-stairs {
      background-color: #b28859 !important;
      position: absolute;
      overflow: hidden;
      box-shadow: inset 0 0 0 1px rgba(0, 0, 0, 0.3);
    }

    /* 1. Horizontal Staircases */
    .stairs-cict1,
    .stairs-cict2,
    .stairs-cmbt1,
    .stairs-cmbt2,
    .stairs-cmbtmid {
      background-image: repeating-linear-gradient(0deg,
          rgba(0, 0, 0, 0.35) 0px,
          rgba(0, 0, 0, 0.35) 2px,
          transparent 2px,
          transparent 7px) !important;
    }

    /* Center vertical dividing line for horizontal stairs */
    .stairs-cict1::before,
    .stairs-cict2::before,
    .stairs-cmbt1::before,
    .stairs-cmbt2::before,
    .stairs-cmbtmid::before {
      content: '';
      position: absolute;
      top: 0;
      bottom: 0;
      left: 50%;
      width: 2px;
      background-color: rgba(0, 0, 0, 0.6);
      transform: translateX(-50%);
      z-index: 1;
    }

    /* 2. Vertical Staircases */
    .stairs-educ1,
    .stairs-educ2,
    .stairs-lhs {
      background-image: repeating-linear-gradient(90deg,
          rgba(0, 0, 0, 0.35) 0px,
          rgba(0, 0, 0, 0.35) 2px,
          transparent 2px,
          transparent 7px) !important;
    }

    /* Center horizontal dividing line for vertical stairs */
    .stairs-educ1::before,
    .stairs-educ2::before,
    .stairs-lhs::before {
      content: '';
      position: absolute;
      left: 0;
      right: 0;
      top: 50%;
      height: 2px;
      background-color: rgba(0, 0, 0, 0.6);
      transform: translateY(-50%);
      z-index: 1;
    }

    .bg-offices {
      background-color: #ffc107;
      color: #212529 !important;
    }

    .bg-facility {
      background-color: #dc3545;
    }

    /* Room Styling */
    .bordering {
      border: 1px solid rgba(0, 0, 0, 0.25) !important;
      border-radius: 0 !important;
      cursor: pointer;
      user-select: none;
      z-index: 2;
      box-sizing: border-box;
      transition: all 0.2s ease;
    }

    .bordering:hover {
      border-color: rgba(255, 0, 85, 0.75) !important;
      box-shadow: 0 8px 18px rgba(255, 0, 85, 0.14);
      transform: translateY(-1px);
    }

    .bordering.selected-room {
      border: 2px solid #ff0055 !important;
      outline: 3px solid rgba(255, 0, 85, 0.9) !important;
      box-shadow: 0 0 18px rgba(255, 0, 85, 0.75);
      z-index: 12;
    }

    /* Building-specific hover styling for floor 2 */
    #floor2-layer .building-cict.bordering:hover,
    #floor2-layer .building-coed.bordering:hover,
    #floor2-layer .building-cmbt.bordering:hover,
    #floor2-layer .building-lhs.bordering:hover {
      outline: 2px solid #000;
      z-index: 10 !important;
    }

    #floor2-layer.hovered-building-cict .building-cict.bordering:not(.bg-stairs, .filler, .filler2, .filler3),
    #floor2-layer.hovered-building-coed .building-coed.bordering:not(.bg-stairs, .filler, .filler2, .filler3),
    #floor2-layer.hovered-building-cmbt .building-cmbt.bordering:not(.bg-stairs, .filler, .filler2, .filler3),
    #floor2-layer.hovered-building-lhs .building-lhs.bordering:not(.bg-stairs, .filler, .filler2, .filler3),
    #floor2-layer.active-building-cict .building-cict.bordering:not(.bg-stairs, .filler, .filler2, .filler3),
    #floor2-layer.active-building-coed .building-coed.bordering:not(.bg-stairs, .filler, .filler2, .filler3),
    #floor2-layer.active-building-cmbt .building-cmbt.bordering:not(.bg-stairs, .filler, .filler2, .filler3),
    #floor2-layer.active-building-lhs .building-lhs.bordering:not(.bg-stairs, .filler, .filler2, .filler3) {
      outline: 2px solid #000;
      z-index: 10 !important;
    }

    #floor2-layer.active-building-cict .building-coed,
    #floor2-layer.active-building-cict .building-cmbt,
    #floor2-layer.active-building-cict .building-lhs,
    #floor2-layer.active-building-coed .building-cict,
    #floor2-layer.active-building-coed .building-cmbt,
    #floor2-layer.active-building-coed .building-lhs,
    #floor2-layer.active-building-cmbt .building-cict,
    #floor2-layer.active-building-cmbt .building-coed,
    #floor2-layer.active-building-cmbt .building-lhs,
    #floor2-layer.active-building-lhs .building-cict,
    #floor2-layer.active-building-lhs .building-coed,
    #floor2-layer.active-building-lhs .building-cmbt,
    #floor2-layer.active-building-cict #edge-layer-2f .edge:not(.building-cict),
    #floor2-layer.active-building-coed #edge-layer-2f .edge:not(.building-coed),
    #floor2-layer.active-building-cmbt #edge-layer-2f .edge:not(.building-cmbt),
    #floor2-layer.active-building-lhs #edge-layer-2f .edge:not(.building-lhs),
    #floor2-layer.active-building-cict #nav-overlay-2f .waypoint:not(.building-cict),
    #floor2-layer.active-building-coed #nav-overlay-2f .waypoint:not(.building-coed),
    #floor2-layer.active-building-cmbt #nav-overlay-2f .waypoint:not(.building-cmbt),
    #floor2-layer.active-building-lhs #nav-overlay-2f .waypoint:not(.building-lhs) {
      opacity: 0;
      visibility: hidden;
      pointer-events: none;
    }

    body.floor2-exclusive .map-wrapper>.border>svg.map-lines-overlay {
      display: none !important;
    }

    body.floor2-exclusive .map-wrapper>.border>.bordering {
      display: none !important;
    }

    body.floor2-exclusive #nav-overlay {
      display: none !important;
    }

    body.floor2-exclusive #floor2-layer {
      opacity: 1;
      visibility: visible;
    }

    /* DYNAMIC CLICK STYLING FOR ROOM DIVS */
    #floor2-layer .building-cict.bordering.selected-room,
    #floor2-layer .building-coed.bordering.selected-room,
    #floor2-layer .building-cmbt.bordering.selected-room,
    #floor2-layer .building-lhs.bordering.selected-room,
    #floor2-layer .bordering.selected-room {
      outline: 3px solid #ff0055 !important;
      box-shadow: 0 0 10px rgba(255, 0, 85, 0.8);
      z-index: 15 !important;
    }

    /* SVG Overlay Layer */
    .map-lines-overlay,
    #nav-overlay,
    #nav-overlay-2f {
      position: absolute;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
    }

    .map-lines-overlay {
      pointer-events: none;
      z-index: 1;
    }

    #nav-overlay,
    #nav-overlay-2f {
      z-index: 20;
      pointer-events: none;
    }

    #nav-overlay .waypoint,
    #nav-overlay-2f .waypoint {
      pointer-events: auto;
      cursor: pointer;
    }

    .bordering[data-target] {
      cursor: pointer;
    }

    /* Wall Stroke Properties */
    .outer-wall {
      fill: none;
      stroke: #1a1a1a;
      stroke-width: 3px;
      stroke-linejoin: square;
      stroke-linecap: butt;
      vector-effect: non-scaling-stroke;
    }

    .inner-wall {
      fill: none;
      stroke: #495057;
      stroke-width: 2px;
      stroke-linejoin: square;
      stroke-linecap: butt;
      vector-effect: non-scaling-stroke;
    }

    /* Navigation Edges & Waypoints */
    .edge {
      stroke: rgba(40, 100, 230, 0.0);
      stroke-width: 8;
      stroke-linecap: round;
      transition: .25s;
      fill: none;
    }

    .edge.active {
      stroke: #00d25a;
      stroke-width: 10;
      filter: drop-shadow(0 0 18px rgba(0, 210, 90, 0.5));
      transition: stroke 0.25s, stroke-width 0.25s, filter 0.25s;
    }

    /* Alternative Path Styling (Gray & Broken/Dashed Line) */
    .edge.alt-active {
      stroke: #8d99ae;
      stroke-width: 6;
      stroke-dasharray: 8, 8;
      filter: drop-shadow(0 0 12px rgba(141, 153, 174, 0.4));
      z-index: 1;
    }

    .waypoint {
      display: none !important;
    }

    .floor-layer {
      position: absolute;
      inset: 0;
      transition: transform 0.35s ease, opacity 0.35s ease;
      z-index: 30;
      pointer-events: none;
    }

    .floor-layer.hidden {
      transform: translateY(105%);
      opacity: 0;
      pointer-events: none;
    }

    .floor-layer.visible {
      transform: translateY(0);
      opacity: 1;
      pointer-events: auto;
    }


    #wp-reg-win,
    #wp-cashier-win {
      display: inline !important;
    }

    .route-visible .waypoint.start,
    .route-visible .waypoint.end,
    .route-visible .waypoint.alt-end,
    .route-visible .waypoint.selected-waypoint {
      display: inline !important;
    }

    .waypoint.on-path,
    .waypoint.junction {
      display: none !important;
    }

    .waypoint.room circle {
      fill: #2d6cdf;
      stroke: white;
      stroke-width: 2;
      cursor: pointer;
      transition: .2s;
      paint-order: stroke fill;
    }

    /* DYNAMIC CLICK STYLING FOR SVG WAYPOINTS */
    .waypoint.selected-waypoint circle {
      fill: #ff0055 !important;
      r: 16 !important;
      stroke: #ffffff !important;
      stroke-width: 3 !important;
    }

    .waypoint.room text {
      fill: #111;
      font-size: 10px;
      font-weight: bold;
      text-anchor: middle;
      dominant-baseline: middle;
      pointer-events: none;
    }

    .waypoint.start circle {
      fill: #00d25a !important;
      filter: drop-shadow(0 0 12px rgba(0, 210, 90, 0.7));
      transition: filter 0.25s ease;
    }

    .waypoint.end circle {
      fill: #dc3545 !important;
      filter: drop-shadow(0 0 12px rgba(220, 53, 69, 0.6));
      transition: filter 0.25s ease;
    }

    .waypoint.alt-end circle {
      fill: #8d99ae !important;
      filter: drop-shadow(0 0 10px rgba(141, 153, 174, 0.5));
      transition: filter 0.25s ease;
    }

    .tracking-wider {
      letter-spacing: 0.05em;
    }

    .form-select:focus {
      border-color: #0d6efd;
      box-shadow: none;
    }

    .input-group-text {
      color: #6c757d;
    }

    .btn-primary {
      box-shadow: 0 4px 6px rgba(13, 110, 253, 0.15);
    }

    .btn:hover {
      transform: translateY(-1px);
      transition: 0.2s;
    }

    /* Absolute Room Layout Coordinates */
    .cict-faculty {
      width: 11%;
      height: 9.5%;
      top: 7.3%;
      right: 10.58%;
    }

    .clinic {
      width: 9.75%;
      height: 6.63%;
      top: 7.3%;
      right: 21.58%;
    }

    .stairs-cict1 {
      width: 6.45%;
      height: 6.63%;
      top: 7.3%;
      right: 31.33%;
    }

    .info-cict {
      width: 3.4%;
      height: 3.6%;
      top: 10.3%;
      right: 34.33%;
    }

    .conference-room {
      width: 10.51%;
      height: 6.63%;
      top: 7.3%;
      right: 37.75%;
    }

    .director-office {
      width: 11.51%;
      height: 6.63%;
      top: 7.3%;
      right: 47.6%;
    }

    .rm113 {
      width: 7.19%;
      height: 6.6%;
      top: 7.3%;
      right: 58.77%;
    }

    .rm114 {
      width: 7.19%;
      height: 6.6%;
      top: 7.3%;
      right: 65.96%;
    }

    .stairs-cict2 {
      width: 6.45%;
      height: 6.63%;
      top: 7.3%;
      right: 73.15%;
    }

    .supply-room {
      width: 10.32%;
      height: 4.7%;
      top: 7.3%;
      right: 82.36%;
    }

    .stairs-educ1 {
      width: 6.82%;
      height: 4.05%;
      top: 11.88%;
      right: 85.86%;
    }

    .cr-staff {
      width: 6.82%;
      height: 3.35%;
      top: 15.93%;
      right: 85.86%;
    }

    .bsie-stockroom {
      width: 6.82%;
      height: 5.02%;
      top: 19.28%;
      right: 85.86%;
    }

    .gender {
      width: 6.82%;
      height: 5.1%;
      top: 28.26%;
      right: 85.86%;
    }

    .ret {
      width: 6.82%;
      height: 5.02%;
      top: 33.3%;
      right: 85.86%;
    }

    .nstp {
      width: 6.82%;
      height: 5.1%;
      top: 38.3%;
      right: 85.86%;
    }

    .rm110 {
      width: 6.82%;
      height: 5.02%;
      top: 43.4%;
      right: 85.86%;
    }

    .rm109 {
      width: 6.82%;
      height: 5.02%;
      top: 48.34%;
      right: 85.86%;
    }

    .rmmapeh {
      width: 6.82%;
      height: 5.02%;
      top: 53.36%;
      right: 85.86%;
    }

    .bped {
      width: 6.82%;
      height: 5.02%;
      top: 58.38%;
      right: 85.86%;
    }

    .rm108 {
      width: 6.82%;
      height: 5.02%;
      top: 63.4%;
      right: 85.86%;
    }

    .cr-female {
      width: 6.82%;
      height: 3.35%;
      top: 68.42%;
      right: 85.86%;
    }

    .stairs-educ2 {
      width: 6.82%;
      height: 4.05%;
      top: 71.77%;
      right: 85.86%;
    }

    .cr-male {
      width: 6.19%;
      height: 3.35%;
      top: 77.58%;
      right: 86.43%;
    }

    .stairs-cmbt2 {
      width: 3.9%;
      height: 5.46%;
      top: 80.93%;
      right: 88.7%;
    }

    .rm107 {
      width: 6.83%;
      height: 8.81%;
      top: 77.58%;
      right: 79.6%;
    }

    .lhs {
      width: 7.28%;
      height: 12.77%;
      top: 73.62%;
      right: 72.31%;
    }

    .cashier {
      width: 11.40%;
      height: 8.81%;
      top: 77.58%;
      right: 60.91%;
    }

    .stairs-cmbtmid {
      width: 5.96%;
      height: 8.81%;
      top: 77.58%;
      right: 54.95%;
    }

    .info-front {
      width: 4%;
      height: 2%;
      top: 82.628%;
      right: 54.95%;
    }

    .registrar {
      width: 11.15%;
      height: 8.81%;
      top: 77.58%;
      right: 43.95%;
    }

    .osoad {
      width: 20.58%;
      height: 8.81%;
      top: 77.58%;
      right: 17.29%;
    }

    .filler {
      width: 4%;
      height: 2.72%;
      top: 77.58%;
      right: 10.5%;
    }

    .bmgs {
      width: 2.82%;
      height: 8.81%;
      top: 77.6%;
      right: 14.47%;
    }

    .stairs-cmbt1 {
      width: 4%;
      height: 6.09%;
      top: 80.2%;
      right: 10.5%;
    }

    .rm104 {
      width: 10.60%;
      height: 10.23%;
      top: 67.4%;
      right: 10.5%;
    }

    .rm103 {
      width: 10.60%;
      height: 10.23%;
      top: 57.4%;
      right: 10.5%;
    }

    .stairs-lhs {
      width: 7.4%;
      height: 9.4%;
      top: 48%;
      right: 13.7%;
    }

    .rm102 {
      width: 10.60%;
      height: 10.23%;
      top: 38%;
      right: 10.5%;
    }

    .rm101 {
      width: 10.60%;
      height: 10.23%;
      top: 28.26%;
      right: 10.5%;
    }

    .gym {
      width: 32.5%;
      height: 28.5%;
      top: 28.26%;
      right: 43.95%;
    }

    .laboratory {
      width: 9.40%;
      height: 12.5%;
      top: 61.4%;
      right: 71.31%;
      background-color: #ffffa7;
    }

    .stairs-lab {
      width: 9.40%;
      height: 2%;
      top: 59.4%;
      right: 71.31%;
    }

    .cr-gym-female {
      width: 5%;
      height: 8.25%;
      top: 34.75%;
      right: 76.45%;
    }

    .cr-gym-male {
      width: 5%;
      height: 8.25%;
      top: 43%;
      right: 76.45%;
    }

    .canteen {
      width: 3%;
      height: 35%;
      top: 19%;
      right: 94%;
    }

    .guarde {
      width: 10%;
      height: 5%;
      top: 92.4%;
      right: 54.95%;
    }

    /* FLOOR 2 ROOM LAYOUT COORDINATES */
    #floor2-layer .rm222 {
      width: 11%;
      height: 9.5%;
      top: 7.3%;
      right: 10.58%;
    }

    #floor2-layer .rm221 {
      width: 9.75%;
      height: 6.63%;
      top: 7.3%;
      right: 21.58%;
    }

    #floor2-layer .stairs-cict21 {
      width: 6.45%;
      height: 6.63%;
      top: 7.3%;
      right: 31.33%;
    }

    #floor2-layer .rm220 {
      width: 8.85%;
      height: 6.63%;
      top: 7.3%;
      right: 37.75%;
    }

    #floor2-layer .rm219 {
      width: 8.85%;
      height: 6.63%;
      top: 7.3%;
      right: 46.6%;
    }

    #floor2-layer .rm218 {
      width: 8.85%;
      height: 6.63%;
      top: 7.3%;
      right: 55.45%;
    }

    #floor2-layer .coed-faculty {
      width: 8.85%;
      height: 6.63%;
      top: 7.3%;
      right: 64.3%;
    }

    #floor2-layer .stairs-cict22 {
      width: 6.45%;
      height: 6.63%;
      top: 7.3%;
      right: 73.15%;
    }

    #floor2-layer .filler3 {
      width: 6.45%;
      height: 2%;
      top: 7.3%;
      right: 73.15%;
    }

    #floor2-layer .guidance {
      width: 6.45%;
      height: 2%;
      top: 7.3%;
      right: 31.33%;
    }

    #floor2-layer .coed-office {
      width: 10.32%;
      height: 4.7%;
      top: 7.3%;
      right: 82.36%;
    }

    #floor2-layer .stairs-educ21 {
      width: 6.82%;
      height: 4.05%;
      top: 11.88%;
      right: 85.86%;
    }

    #floor2-layer .ed-tech {
      width: 6.82%;
      height: 7.9771428571428%;
      top: 15.93%;
      right: 85.86%;
    }

    #floor2-layer .rm215 {
      width: 6.82%;
      height: 7.9771428571428%;
      top: 23.9071428571428%;
      right: 85.86%;
    }

    #floor2-layer .rm214 {
      width: 6.82%;
      height: 7.9771428571428%;
      top: 31.8842857142856%;
      right: 85.86%;
    }

    #floor2-layer .rm213 {
      width: 6.82%;
      height: 7.9771428571428%;
      top: 39.8614285714284%;
      right: 85.86%;
    }

    #floor2-layer .rm212 {
      width: 6.82%;
      height: 7.9771428571428%;
      top: 47.8385714285712%;
      right: 85.86%;
    }

    #floor2-layer .field-study {
      width: 6.82%;
      height: 7.9771428571428%;
      top: 55.815714285714%;
      right: 85.86%;
    }

    #floor2-layer .rm211 {
      width: 6.82%;
      height: 7.9771428571428%;
      top: 63.7928571428568%;
      right: 85.86%;
    }

    #floor2-layer .stairs-educ22 {
      width: 6.82%;
      height: 4.05%;
      top: 71.77%;
      right: 85.86%;
    }

    #floor2-layer .filler2 {
      width: 6.92%;
      height: 8.81%;
      top: 77.58%;
      right: 85.7%;
      z-index: 1;
    }

    #floor2-layer .stairs-cmbt22 {
      width: 4%;
      height: 6.09%;
      top: 80.3%;
      right: 88.6%;
      z-index: 3;
      z-index: 3;
    }

    #floor2-layer .rm209 {
      width: 8.29%;
      height: 8.81%;
      top: 77.58%;
      right: 77.49%;
    }

    #floor2-layer .rm208 {
      width: 8.29%;
      height: 8.81%;
      top: 77.58%;
      right: 69.2%;
    }

    #floor2-layer .rm207 {
      width: 8.29%;
      height: 8.81%;
      top: 77.58%;
      right: 60.91%;
    }

    #floor2-layer .stairs-cmbt2mid {
      width: 5.96%;
      height: 8.81%;
      top: 77.58%;
      right: 54.95%;
    }

    #floor2-layer .rm206 {
      width: 12%;
      height: 8.81%;
      top: 77.58%;
      right: 43.5%;
    }

    #floor2-layer .accreditation {
      width: 13.5%;
      height: 8.81%;
      top: 77.58%;
      right: 17%;
    }

    #floor2-layer .anihan {
      width: 13.5%;
      height: 8.81%;
      top: 77.58%;
      right: 30%;
    }

    #floor2-layer .filler {
      width: 6.82%;
      height: 8.81%;
      top: 77.6%;
      right: 10.5%;
    }

    #floor2-layer .stairs-cmbt21 {
      width: 4%;
      height: 6.09%;
      top: 80.3%;
      right: 10.5%;
    }

    #floor2-layer .rm204 {
      width: 10.60%;
      height: 10.23%;
      top: 67.4%;
      right: 10.5%;
    }

    #floor2-layer .rm203 {
      width: 10.60%;
      height: 10.23%;
      top: 57.4%;
      right: 10.5%;
    }

    #floor2-layer .stairs-lhs2 {
      width: 7.4%;
      height: 9.4%;
      top: 48%;
      right: 13.7%;
    }

    #floor2-layer .rm202 {
      width: 10.60%;
      height: 10.23%;
      top: 38%;
      right: 10.5%;
    }

    #floor2-layer .rm201 {
      width: 10.60%;
      height: 10.23%;
      top: 28.26%;
      right: 10.5%;
    }

    #floor2-layer .gym {
      width: 32.5%;
      height: 28.5%;
      top: 28.26%;
      right: 43.95%;
    }

    #floor2-layer .drafting {
      width: 9.40%;
      height: 12.5%;
      top: 61.4%;
      right: 71.31%;
      background-color: #ffffa7;
    }

    #floor2-layer .stairs-lab2 {
      width: 9.40%;
      height: 2%;
      top: 59.4%;
      right: 71.31%;
    }

    .group-hidden {
      display: none !important;
      opacity: 0 !important;
      pointer-events: none !important;
    }

    .building-floor-toggle {
      position: absolute;
      z-index: 100;
      display: flex;
      align-items: center;
      gap: 4px;
      background: rgba(255, 255, 255, 0.95);
      border: 1px solid #1d3698;
      border-radius: 20px;
      padding: 2px 6px;
      box-shadow: 0 4px 10px rgba(0, 0, 0, 0.15);
      font-size: 0.75rem;
      font-weight: bold;
      transition: opacity 0.25s ease;
      pointer-events: auto;
    }

    .building-floor-toggle .btn-floor {
      border: none;
      background: transparent;
      padding: 2px 8px;
      border-radius: 12px;
      color: #495057;
      cursor: pointer;
      font-size: 0.7rem;
      font-weight: 700;
      pointer-events: auto;
    }

    .building-floor-toggle .btn-floor.active {
      background-color: #1d3698;
      color: #ffffff;
    }

    #toggle-badge-cict {
      top: 3%;
      right: 30%;
    }

    #toggle-badge-coed {
      top: 40%;
      right: 86%;
    }

    #toggle-badge-cmbt {
      top: 86%;
      right: 45%;
    }

    #toggle-badge-lhs {
      top: 45%;
      right: 8%;
    }

    #toggle-badge-draft {
      top: 55%;
      right: 70%;
    }

    /* Building Floor Toggle Pills Overlay */
    .building-toggle-pill {
      pointer-events: auto;
      background: rgba(255, 255, 255, 0.95);
      padding: 3px 6px;
      border-radius: 20px;
      box-shadow: 0 2px 8px rgba(0, 0, 0, 0.25);
      display: none;
      align-items: center;
      gap: 4px;
      z-index: 45;
      border: 1px solid #ced4da;
    }

    .btn-xs {
      padding: 1px 6px;
      font-size: 10px;
      font-weight: bold;
      line-height: 1.2;
    }

    /* Dynamic Visibility Helper Classes */
    #floor2-layer .building-group-hidden,
    .building-f1-hidden {
      display: none !important;
      opacity: 0 !important;
      pointer-events: none !important;
    }

    #building-floor-toggles .building-toggle-pill,
    #building-floor-toggles .building-toggle-pill button {
      pointer-events: auto !important;
      position: relative;
      z-index: 1000;
    }
  </style>
</head>

<body class="map-body">

  <div class="container-fluid py-3">
    <div class="container-fluid py-4 bg-light">
      <!-- Header Section -->
      <div class="text-center mb-4">
        <h4 class="fw-bold text-dark mb-1">
          <i class="fa-solid fa-map-location-dot me-2 text-primary"></i>Campus Navigation
        </h4>
        <p id="msg" class="small text-muted">
          Select your origin and destination to find the quickest route.
        </p>
      </div>

      <!-- Navigation Command Center -->
      <div class="card shadow-sm border-0 rounded-4 mb-4 mx-auto" style="max-width: 1100px;">
        <div class="card-body p-4">
          <div class="row g-3 align-items-end">
            <!-- Dropdowns -->
            <div class="col-md-4">
              <label class="form-label fw-semibold small text-uppercase tracking-wider text-muted">From</label>
              <div class="input-group">
                <span class="input-group-text bg-white border-end-0"><i
                    class="fa fa-street-view text-primary"></i></span>
                <select id="sel-from" class="form-select border-start-0 ps-0">
                  <option value="">Choose starting point...</option>
                </select>
              </div>
            </div>

            <div class="col-md-4">
              <label class="form-label fw-semibold small text-uppercase tracking-wider text-muted">To</label>
              <div class="input-group">
                <span class="input-group-text bg-white border-end-0"><i
                    class="fa fa-location-dot text-danger"></i></span>
                <select id="sel-to" class="form-select border-start-0 ps-0">
                  <option value="">Choose destination...</option>
                </select>
              </div>
            </div>

            <!-- Action Buttons -->
            <div class="col-md-4">
              <div class="d-flex gap-2">
                <button class="btn btn-primary w-100 fw-bold" onclick="findPath()">
                  <i class="fa fa-route me-2"></i>Find Path
                </button>
                <button class="btn btn-outline-secondary" onclick="resetNav()" title="Reset Map">
                  <i class="fa fa-rotate-left"></i>
                </button>
                <a href="functions/logout.php" class="btn btn-outline-danger" title="Logout">
                  <i class="fa fa-right-from-bracket"></i>
                </a>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- MAP WRAPPER -->
    <div class="map-wrapper mx-auto mt-4" style="max-width: 1050px;">
      <div class="border position-relative fixed-canvas shadow-sm">
        <div id="building-floor-toggles-container"
          class="position-absolute w-100 h-100 top-0 start-0 pointer-events-none" style="z-index: 99;"></div>
        <!-- Floating Per-Building Floor Toggle Buttons -->
        <div id="building-floor-toggles" class="position-absolute w-100 h-100 pointer-events-none"
          style="z-index: 999; top: 0; left: 0;  pointer-events: none;">
          <!-- CICT Toggle -->
          <div class="building-toggle-pill position-absolute" data-group="cict" style="top: 2%; right: 40%;">
            <span class="badge bg-primary me-1">CICT</span>
            <div class="btn-group btn-group-sm" role="group">
              <button type="button" class="btn btn-xs btn-primary active"
                onclick="toggleBuildingFloor('cict', 1)">1F</button>
              <button type="button" class="btn btn-xs btn-outline-primary"
                onclick="toggleBuildingFloor('cict', 2)">2F</button>
            </div>
          </div>

          <!-- COED Toggle -->
          <div class="building-toggle-pill position-absolute" data-group="coed" style="top: 10%; right: 88%;">
            <span class="badge bg-primary me-1">COED</span>
            <div class="btn-group btn-group-sm" role="group">
              <button type="button" class="btn btn-xs btn-primary active"
                onclick="toggleBuildingFloor('coed', 1)">1F</button>
              <button type="button" class="btn btn-xs btn-outline-primary"
                onclick="toggleBuildingFloor('coed', 2)">2F</button>
            </div>
          </div>
          <!-- CICT + COED Combined Toggle -->
          <div class="building-toggle-pill position-absolute" data-group="cict-coed"
            style="top: 2%; right: 40%; display: none;">

            <span class="badge bg-primary me-1">CICT + COED</span>

            <div class="btn-group btn-group-sm" role="group">
              <button type="button" class="btn btn-xs btn-primary active" onclick="toggleCictCoedFloor(1)">
                1F
              </button>

              <button type="button" class="btn btn-xs btn-outline-primary" onclick="toggleCictCoedFloor(2)">
                2F
              </button>
            </div>
          </div>
          <!-- CMBT Toggle -->
          <div class="building-toggle-pill position-absolute" data-group="cmbt" style="bottom: 2%; right: 45%;">
            <span class="badge bg-primary me-1">CMBT</span>
            <div class="btn-group btn-group-sm" role="group">
              <button type="button" class="btn btn-xs btn-primary active"
                onclick="toggleBuildingFloor('cmbt', 1)">1F</button>
              <button type="button" class="btn btn-xs btn-outline-primary"
                onclick="toggleBuildingFloor('cmbt', 2)">2F</button>
            </div>
          </div>

          <!-- LHS Toggle -->
          <div class="building-toggle-pill position-absolute" data-group="lhs" style="top: 24%; right: 2%;">
            <span class="badge bg-primary me-1">LHS</span>
            <div class="btn-group btn-group-sm" role="group">
              <button type="button" class="btn btn-xs btn-primary active"
                onclick="toggleBuildingFloor('lhs', 1)">1F</button>
              <button type="button" class="btn btn-xs btn-outline-primary"
                onclick="toggleBuildingFloor('lhs', 2)">2F</button>
            </div>
          </div>

          <!-- DRAFT / LAB Toggle -->
          <div class="building-toggle-pill position-absolute" data-group="draft" style="top: 55%; right: 68%;">
            <span class="badge bg-primary me-1">LAB</span>
            <div class="btn-group btn-group-sm" role="group">
              <button type="button" class="btn btn-xs btn-primary active"
                onclick="toggleBuildingFloor('draft', 1)">1F</button>
              <button type="button" class="btn btn-xs btn-outline-primary"
                onclick="toggleBuildingFloor('draft', 2)">2F</button>
            </div>
          </div>
        </div>
        <!-- SVG Wall Overlay -->
        <svg class="map-lines-overlay" viewBox="0 0 100 100" preserveAspectRatio="none">
          <path class="outer-wall" d="
            M 2.8, 97.5 
            L 2.8, 2.5 
            L 95.5, 2.5
            L 95.5, 18
            L 90.5, 18
            M 90.5, 26
            L 95.5, 26  
            M 95.5, 25.8
            L 95.5, 97.5
            L 48, 97.5 
            L 48, 94.5 
            M 45, 97.5 
            L 2.8, 97.5
          " />
          <!-- COED -->
          <line class="inner-wall" x1="17.5" y1="7.3" x2="17.5" y2="75.9" />
          <!-- REG horizontal -->
          <line class="inner-wall" x1="75.6" y1="73.8" x2="27.0" y2="73.8" />
          <!-- REG vertical -->
          <line class="inner-wall" x1="75.5" y1="28.26" x2="75.5" y2="73.9" />
          <!-- CMBT -->
          <line class="inner-wall" x1="7.2" y1="91.3" x2="89.6" y2="91.3" />
          <!-- LHS -->
          <line class="inner-wall" x1="94.5" y1="28.26" x2="94.5" y2="78" />
          <!-- CICT -->
          <line class="inner-wall" x1="78.5" y1="16.7" x2="20.4" y2="16.7" />
        </svg>

        <!-- Room Elements -->
        <div data-target="wp-cict"
          class="cict-faculty position-absolute bg-primary text-white p-1 d-flex flex-column align-items-center justify-content-center bordering">
          <span class="fw-bold text-center small">CICT Faculty</span>
        </div>
        <div data-target="wp-clinic"
          class="clinic position-absolute bg-clinic text-dark p-1 d-flex flex-column align-items-center justify-content-center bordering">
          <span class="fw-bold text-center small">Clinic</span>
        </div>
        <div
          class="stairs-cict1 position-absolute bg-stairs text-white p-1 d-flex flex-column align-items-center justify-content-center bordering">

        </div>
        <div data-target="GROUP_INFO_CICT"
          class="info-cict position-absolute bg-help text-dark p-1 d-flex flex-column align-items-center justify-content-center bordering">
          <span class="fw-bold text-center small">Info</span>
        </div>
        <div data-target="wp-conf"
          class="conference-room position-absolute bg-warning text-dark p-1 d-flex flex-column align-items-center justify-content-center bordering">
          <span class="fw-bold text-center small">Conference Room</span>
        </div>
        <div data-target="wp-dean"
          class="director-office position-absolute bg-offices text-white p-1 d-flex flex-column align-items-center justify-content-center bordering">
          <span class="fw-bold text-center small">Director's Office</span>
        </div>
        <div data-target="wp-room113"
          class="rm113 position-absolute bg-room text-dark p-1 d-flex flex-column align-items-center justify-content-center bordering">
          <span class="fw-bold text-center small">Room 113</span>
        </div>
        <div data-target="wp-room114"
          class="rm114 position-absolute bg-room text-dark p-1 d-flex flex-column align-items-center justify-content-center bordering">
          <span class="fw-bold text-center small">Room 114</span>
        </div>
        <div
          class="stairs-cict2 position-absolute bg-stairs text-white p-1 d-flex flex-column align-items-center justify-content-center bordering">

        </div>
        <div data-target="wp-supplyroom"
          class="supply-room position-absolute bg-secondary text-white p-1 d-flex flex-column align-items-center justify-content-center bordering">
          <span class="fw-bold text-center small">Supply Room</span>
        </div>
        <div
          class="stairs-educ1 position-absolute bg-stairs text-white p-1 d-flex flex-column align-items-center justify-content-center bordering">

        </div>
        <div data-target="wp-cr-staff"
          class="cr-staff position-absolute bg-success text-white p-1 d-flex flex-column align-items-center justify-content-center bordering">
          <span class="fw-bold text-center small">CR Staff</span>
        </div>
        <div data-target="wp-bsiestock"
          class="bsie-stockroom position-absolute bg-secondary text-white p-1 d-flex flex-column align-items-center justify-content-center bordering">
          <span class="fw-bold text-center small">BSIE Stockroom</span>
        </div>
        <div data-target="wp-gender"
          class="gender position-absolute bg-warning text-dark p-1 d-flex flex-column align-items-center justify-content-center bordering">
          <span class="fw-bold text-center small">Gender</span>
        </div>
        <div data-target="wp-ret"
          class="ret position-absolute bg-offices text-white p-1 d-flex flex-column align-items-center justify-content-center bordering">
          <span class="fw-bold text-center small">RET</span>
        </div>
        <div data-target="wp-nstp"
          class="nstp position-absolute bg-offices text-white p-1 d-flex flex-column align-items-center justify-content-center bordering">
          <span class="fw-bold text-center small">NSTP</span>
        </div>
        <div data-target="wp-room110"
          class="rm110 position-absolute bg-room text-dark p-1 d-flex flex-column align-items-center justify-content-center bordering">
          <span class="fw-bold text-center small">Room 110</span>
        </div>
        <div data-target="wp-room109"
          class="rm109 position-absolute bg-room ttext-dark p-1 d-flex flex-column align-items-center justify-content-center bordering">
          <span class="fw-bold text-center small">Room 109</span>
        </div>
        <div data-target="wp-mapeh"
          class="rmmapeh position-absolute bg-room text-dark p-1 d-flex flex-column align-items-center justify-content-center bordering">
          <span class="fw-bold text-center small">Room MAPEH</span>
        </div>
        <div data-target="wp-bped"
          class="bped position-absolute bg-room text-dark p-1 d-flex flex-column align-items-center justify-content-center bordering">
          <span class="fw-bold text-center small">BPED</span>
        </div>
        <div data-target="wp-room108"
          class="rm108 position-absolute bg-room text-dark p-1 d-flex flex-column align-items-center justify-content-center bordering">
          <span class="fw-bold text-center small">Room 108</span>
        </div>
        <div data-target="GROUP_CR_FEMALE-CMBT"
          class="cr-female position-absolute bg-female text-dark p-1 d-flex flex-column align-items-center justify-content-center bordering">
          <span class="fw-bold text-center small">CR</span>
        </div>
        <div data-target="GROUP_CR_MALE-CMBT"
          class="cr-male position-absolute bg-male text-dark p-1 d-flex flex-column align-items-center justify-content-center bordering">
          <span class="fw-bold text-center small">CR</span>
        </div>
        <div
          class="stairs-educ2 position-absolute bg-stairs text-white p-1 d-flex flex-column align-items-center justify-content-center bordering">

        </div>
        <div
          class="stairs-cmbt2 position-absolute bg-stairs text-white p-1 d-flex flex-column align-items-center justify-content-center bordering">

        </div>
        <div data-target="wp-room107"
          class="rm107 position-absolute bg-room text-dark p-1 d-flex flex-column align-items-center justify-content-center bordering">
          <span class="fw-bold text-center small">Room 107</span>
        </div>
        <div data-target="wp-lhs-principal"
          class="lhs position-absolute bg-offices text-white p-1 d-flex flex-column align-items-center justify-content-center bordering">
          <span class="fw-bold text-center small">LHS<br>Principal</span>
        </div>
        <div data-target="wp-cashier"
          class="cashier position-absolute bg-facility text-white p-1 d-flex flex-column align-items-center justify-content-center bordering">
          <span class="fw-bold text-center small">Cashier</span>
        </div>
        <div
          class="stairs-cmbtmid position-absolute bg-stairs text-white p-1 d-flex flex-column align-items-center justify-content-center bordering">

        </div>
        <div data-target="GROUP_INFO_FRONT"
          class="info-front position-absolute bg-help text-dark p-1 d-flex flex-column align-items-center justify-content-center bordering">
          <span class="fw-bold text-center small">Info</span>
        </div>
        <div data-target="wp-registrar"
          class="registrar position-absolute bg-facility text-white p-1 d-flex flex-column align-items-center justify-content-center bordering">
          <span class="fw-bold text-center small">Registrar</span>
        </div>
        <div data-target="wp-osoad"
          class="osoad position-absolute bg-offices text-white p-1 d-flex flex-column align-items-center justify-content-center bordering">
          <span class="fw-bold text-center small">OSOAD</span>
        </div>
        <div
          class="filler position-absolute bg-secondary text-white p-1 d-flex flex-column align-items-center justify-content-center bordering">
        </div>
        <div data-target="wp-bmgs"
          class="bmgs position-absolute bg-offices text-white p-1 d-flex flex-column align-items-center justify-content-center bordering">
          <span class="fw-bold text-center small lh-1 text-break">
            B M G S
          </span>
        </div>
        <div
          class="stairs-cmbt1 position-absolute bg-stairs text-white p-1 d-flex flex-column align-items-center justify-content-center bordering">

        </div>
        <div data-target="wp-room104"
          class="rm104 position-absolute bg-room text-dark p-1 d-flex flex-column align-items-center justify-content-center bordering">
          <span class="fw-bold text-center small">Room 104</span>
        </div>
        <div data-target="wp-room103"
          class="rm103 position-absolute bg-room text-dark p-1 d-flex flex-column align-items-center justify-content-center bordering">
          <span class="fw-bold text-center small">Room 103</span>
        </div>
        <div
          class="stairs-lhs position-absolute bg-stairs text-dark p-1 d-flex flex-column align-items-center justify-content-center bordering">

        </div>
        <div data-target="wp-room102"
          class="rm102 position-absolute bg-room text-dark p-1 d-flex flex-column align-items-center justify-content-center bordering">
          <span class="fw-bold text-center small">Room 102</span>
        </div>
        <div data-target="wp-room101"
          class="rm101 position-absolute bg-room text-dark p-1 d-flex flex-column align-items-center justify-content-center bordering">
          <span class="fw-bold text-center small">Room 101</span>
        </div>
        <div data-target="wp-gym"
          class="gym position-absolute bg-gym text-white p-1 d-flex flex-column align-items-center justify-content-center bordering">
          <span class="fw-bold text-center small">Gym</span>
        </div>
        <div data-target="wp-lab"
          class="laboratory position-absolute text-dark p-1 d-flex flex-column align-items-center justify-content-center bordering">
          <span class="fw-bold text-center small">Laboratory</span>
        </div>
        <div
          class="stairs-lab position-absolute bg-stairs text-white p-1 d-flex flex-column align-items-center justify-content-center bordering">

        </div>
        <div data-target="GROUP_CR_FEMALE-GYM"
          class="cr-gym-female position-absolute bg-female text-dark p-1 d-flex flex-column align-items-center justify-content-center bordering">
          <span class="fw-bold text-center small">CR</span>
        </div>
        <div data-target="GROUP_CR_MALE-GYM"
          class="cr-gym-male position-absolute bg-male text-dark p-1 d-flex flex-column align-items-center justify-content-center bordering">
          <span class="fw-bold text-center small">CR</span>
        </div>
        <div data-target="GROUP_CANTEEN"
          class="canteen position-absolute bg-info text-white p-1 d-flex flex-column align-items-center justify-content-center bordering">
          <span class="fw-bold text-center small">C<br>a<br>n<br>t<br>e<br>e<br>n</span>
        </div>
        <div data-target="GROUP_GUARD"
          class="guarde position-absolute bg-help text-dark p-1 d-flex flex-column align-items-center justify-content-center bordering">
          <span class="fw-bold text-center small">Guard house</span>
        </div>

        <!-- SVG Navigation Overlay Layer -->
        <svg id="nav-overlay" viewBox="0 0 1081 1135" preserveAspectRatio="none">
          <g id="edge-layer"></g>

          <!-- ROOM NODES -->
          <g class="waypoint room" id="wp-clinic" onclick="onWaypointClick('wp-clinic')">
            <circle cx="800" cy="175" r="10" />
            <text x="800" y="175">CLINIC</text>
          </g>
          <g class="waypoint room" id="wp-cict" onclick="onWaypointClick('wp-cict')">
            <circle cx="840" cy="175" r="10" />
            <text x="840" y="175">CICT</text>
          </g>
          <g class="waypoint room" id="wp-infoDesk" onclick="onWaypointClick('wp-infoDesk')">
            <circle cx="690" cy="175" r="10" />
            <text x="690" y="175">INFO</text>
          </g>
          <g class="waypoint room" id="wp-conf" onclick="onWaypointClick('wp-conf')">
            <circle cx="620" cy="175" r="10" />
            <text x="620" y="175">CONF</text>
          </g>
          <g class="waypoint room" id="wp-dean" onclick="onWaypointClick('wp-dean')">
            <circle cx="505" cy="175" r="10" />
            <text x="505" y="175">DEAN</text>
          </g>

          <g class="waypoint room" id="wp-room113" onclick="onWaypointClick('wp-room113')">
            <circle cx="408" cy="175" r="10" />
            <text x="408" y="175">113</text>
          </g>
          <g class="waypoint room" id="wp-room114" onclick="onWaypointClick('wp-room114')">
            <circle cx="333" cy="175" r="10" />
            <text x="333" y="175">114</text>
          </g>

          <g class="waypoint room" id="wp-supplyroom" onclick="onWaypointClick('wp-supplyroom')">
            <circle cx="167" cy="147" r="10" />
            <text x="167" y="147">SUP</text>
          </g>
          <g class="waypoint room" id="wp-cr-staff" onclick="onWaypointClick('wp-cr-staff')">
            <circle cx="167" cy="200" r="10" />
            <text x="167" y="200">CR-STAFF</text>
          </g>
          <g class="waypoint room" id="wp-bsiestock" onclick="onWaypointClick('wp-bsiestock')">
            <circle cx="167" cy="250" r="10" />
            <text x="167" y="250">BSIE</text>
          </g>

          <g class="waypoint room" id="wp-canteen" onclick="onWaypointClick('wp-canteen')">
            <circle cx="83" cy="297" r="10" />
            <text x="83" y="297">CANT</text>
          </g>
          <g class="waypoint room" id="wp-gender" onclick="onWaypointClick('wp-gender')">
            <circle cx="167" cy="357" r="10" />
            <text x="167" y="357">GEN</text>
          </g>
          <g class="waypoint room" id="wp-nstp" onclick="onWaypointClick('wp-nstp')">
            <circle cx="167" cy="465" r="10" />
            <text x="167" y="465">NSTP</text>
          </g>

          <g class="waypoint room" id="wp-room110" onclick="onWaypointClick('wp-room110')">
            <circle cx="167" cy="525" r="10" />
            <text x="167" y="525">110</text>
          </g>
          <g class="waypoint room" id="wp-room109" onclick="onWaypointClick('wp-room109')">
            <circle cx="167" cy="580" r="10" />
            <text x="167" y="580">109</text>
          </g>
          <g class="waypoint room" id="wp-canteen2" onclick="onWaypointClick('wp-canteen2')">
            <circle cx="50" cy="690" r="10" />
            <text x="50" y="690">CANT</text>
          </g>
          <g class="waypoint room" id="wp-mapeh" onclick="onWaypointClick('wp-mapeh')">
            <circle cx="167" cy="635" r="10" />
            <text x="167" y="635">MAPEH</text>
          </g>
          <g class="waypoint room" id="wp-bped" onclick="onWaypointClick('wp-bped')">
            <circle cx="167" cy="693" r="10" />
            <text x="167" y="693">BPED</text>
          </g>
          <g class="waypoint room" id="wp-room108" onclick="onWaypointClick('wp-room108')">
            <circle cx="167" cy="750" r="10" />
            <text x="167" y="750">108</text>
          </g>
          <g class="waypoint room" id="wp-lab" onclick="onWaypointClick('wp-lab')">
            <circle cx="200" cy="750" r="10" />
            <text x="200" y="750">LAB</text>
          </g>
          <g class="waypoint room" id="wp-crFem" onclick="onWaypointClick('wp-crFem')">
            <circle cx="167" cy="798" r="10" />
            <text x="167" y="798">CR-F</text>
          </g>

          <g class="waypoint room" id="wp-entrance" onclick="onWaypointClick('wp-entrance')">
            <circle cx="503" cy="1120" r="12" />
            <text x="503" y="1120">ENT</text>
          </g>
          <g class="waypoint room" id="wp-registrar" onclick="onWaypointClick('wp-registrar')">
            <circle cx="535" cy="1000" r="12" />
            <text x="535" y="1000">REG</text>
          </g>
          <g class="waypoint room" id="wp-cashier" onclick="onWaypointClick('wp-cashier')">
            <circle cx="345" cy="1000" r="12" />
            <text x="345" y="1000">CASH</text>
          </g>
          <g class="waypoint room" id="wp-osoad" onclick="onWaypointClick('wp-osoad')">
            <circle cx="800" cy="1000" r="12" />
            <text x="800" y="1000">OSOAD</text>
          </g>
          <g class="waypoint room" id="wp-bmgs" onclick="onWaypointClick('wp-bmgs')">
            <circle cx="915" cy="1000" r="12" />
            <text x="915" y="1000">BMGS</text>
          </g>

          <g class="waypoint room" id="wp-room101" onclick="onWaypointClick('wp-room101')">
            <circle cx="990" cy="380" r="10" />
            <text x="990" y="380">101</text>
          </g>
          <g class="waypoint room" id="wp-room102" onclick="onWaypointClick('wp-room102')">
            <circle cx="990" cy="490" r="10" />
            <text x="990" y="490">102</text>
          </g>
          <g class="waypoint room" id="wp-room103" onclick="onWaypointClick('wp-room103')">
            <circle cx="990" cy="715" r="10" />
            <text x="990" y="715">103</text>
          </g>
          <g class="waypoint room" id="wp-room104" onclick="onWaypointClick('wp-room104')">
            <circle cx="990" cy="825" r="10" />
            <text x="990" y="825">104</text>
          </g>
          <g class="waypoint room" id="wp-gym" onclick="onWaypointClick('wp-gym')">
            <circle cx="430" cy="485" r="10" />
            <text x="430" y="485">GYM</text>
          </g>

          <!-- JUNCTION NODES WITH LABELS -->
          <g class="waypoint room" id="wp-info-front" onclick="onWaypointClick('wp-info-front')">
            <circle cx="465" cy="1000" r="12" />
            <text x="465" y="1000">INFO</text>
          </g>
          <g class="waypoint room" id="wp-guard" onclick="onWaypointClick('wp-guard')">
            <circle cx="465" cy="1030" r="12" />
            <text x="465" y="1030">GUARD</text>
          </g>
          <g class="waypoint junction" id="wp-hallmid-a">
            <circle cx="640" cy="1000" r="4" />
          </g>
          <g class="waypoint junction" id="wp-lhs-hall">
            <circle cx="990" cy="1000" r="4" />
          </g>

          <g class="waypoint junction" id="wp-exit">
            <circle cx="950" cy="265" r="4" />
          </g>
          <g class="waypoint junction" id="wp-j-exit">
            <circle cx="835" cy="265" r="4" />
          </g>
          <g class="waypoint junction" id="wp-hallmid-b">
            <circle cx="640" cy="860" r="4" />
          </g>
          <g class="waypoint junction" id="wp-old-reg">
            <circle cx="835" cy="860" r="4" />
          </g>
          <g class="waypoint junction" id="wp-front-gym">
            <circle cx="835" cy="485" r="4" />
          </g>

          <g class="waypoint junction" id="wp-dean-win">
            <circle cx="540" cy="175" r="4" />
          </g>

          <g class="waypoint junction" id="wp-stockroom">
            <circle cx="280" cy="175" r="4" />
          </g>
          <g class="waypoint junction" id="wp-j-cmbt-left">
            <circle cx="50" cy="870" r="4" />
          </g>


          <!-- LABELED JUNCTIONS 1 - 36 -->
          <g class="waypoint junction" id="wp-junction1">
            <circle cx="640" cy="485" r="6" /><text x="640" y="485">J1</text>
          </g>
          <g class="waypoint junction" id="wp-junction2">
            <circle cx="640" cy="685" r="6" /><text x="640" y="685">J2</text>
          </g>
          <g class="waypoint junction" id="wp-junction3">
            <circle cx="640" cy="265" r="6" /><text x="640" y="265">J3</text>
          </g>
          <g class="waypoint junction" id="wp-junction8">
            <circle cx="580" cy="265" r="6" /><text x="580" y="265">J8</text>
          </g>
          <g class="waypoint junction" id="wp-junction4">
            <circle cx="408" cy="265" r="6" /><text x="408" y="265">J4</text>
          </g>
          <g class="waypoint junction" id="wp-junction5">
            <circle cx="505" cy="265" r="6" /><text x="505" y="265">J5</text>
          </g>
          <g class="waypoint junction" id="wp-junction6">
            <circle cx="333" cy="265" r="6" /><text x="333" y="265">J6</text>
          </g>
          <g class="waypoint junction" id="wp-junction7">
            <circle cx="238" cy="265" r="6" /><text x="238" y="265">J7</text>
          </g>
          <g class="waypoint junction" id="wp-junction9">
            <circle cx="700" cy="265" r="6" /><text x="700" y="265">J9</text>
          </g>
          <g class="waypoint junction" id="wp-junction10">
            <circle cx="760" cy="265" r="6" /><text x="760" y="265">J10</text>
          </g>

          <g class="waypoint junction" id="wp-junction11">
            <circle cx="700" cy="375" r="6" /><text x="700" y="375">J11</text>
          </g>
          <g class="waypoint junction" id="wp-junction12">
            <circle cx="700" cy="600" r="6" /><text x="700" y="600">J12</text>
          </g>
          <g class="waypoint junction" id="wp-junction13">
            <circle cx="700" cy="485" r="6" /><text x="700" y="485">J13</text>
          </g>
          <g class="waypoint junction" id="wp-junction14">
            <circle cx="700" cy="685" r="6" /><text x="700" y="685">J14</text>
          </g>
          <g class="waypoint junction" id="wp-junction15">
            <circle cx="700" cy="785" r="6" /><text x="700" y="785">J15</text>
          </g>

          <g class="waypoint junction" id="wp-junction16">
            <circle cx="760" cy="375" r="6" /><text x="760" y="375">J16</text>
          </g>
          <g class="waypoint junction" id="wp-junction17">
            <circle cx="760" cy="600" r="6" /><text x="760" y="600">J17</text>
          </g>
          <g class="waypoint junction" id="wp-junction18">
            <circle cx="760" cy="485" r="6" /><text x="760" y="485">J18</text>
          </g>
          <g class="waypoint junction" id="wp-junction19">
            <circle cx="760" cy="685" r="6" /><text x="760" y="685">J19</text>
          </g>
          <g class="waypoint junction" id="wp-junction20">
            <circle cx="760" cy="785" r="6" /><text x="760" y="785">J20</text>
          </g>

          <g class="waypoint junction" id="wp-junction21">
            <circle cx="640" cy="375" r="6" /><text x="640" y="375">J21</text>
          </g>
          <g class="waypoint junction" id="wp-junction22">
            <circle cx="640" cy="600" r="6" /><text x="640" y="600">J22</text>
          </g>
          <g class="waypoint junction" id="wp-junction23">
            <circle cx="640" cy="785" r="6" /><text x="640" y="785">J23</text>
          </g>

          <g class="waypoint junction" id="wp-junction24">
            <circle cx="580" cy="685" r="6" /><text x="580" y="685">J24</text>
          </g>
          <g class="waypoint junction" id="wp-junction25">
            <circle cx="580" cy="785" r="6" /><text x="580" y="785">J25</text>
          </g>

          <g class="waypoint junction" id="wp-junction26">
            <circle cx="505" cy="685" r="6" /><text x="505" y="685">J26</text>
          </g>
          <g class="waypoint junction" id="wp-junction27">
            <circle cx="505" cy="785" r="6" /><text x="505" y="785">J27</text>
          </g>

          <g class="waypoint junction" id="wp-junction28">
            <circle cx="408" cy="685" r="6" /><text x="408" y="685">J28</text>
          </g>
          <g class="waypoint junction" id="wp-junction29">
            <circle cx="408" cy="785" r="6" /><text x="408" y="785">J29</text>
          </g>

          <g class="waypoint junction" id="wp-junction30">
            <circle cx="345" cy="785" r="6" /><text x="345" y="785">J30</text>
          </g>

          <g class="waypoint junction" id="wp-junction31">
            <circle cx="72" cy="410" r="6" /><text x="72" y="410">J31</text>
          </g>
          <g class="waypoint junction" id="wp-junction32">
            <circle cx="72" cy="635" r="6" /><text x="72" y="635">J32</text>
          </g>
          <g class="waypoint junction" id="wp-junction33">
            <circle cx="72" cy="297" r="6" /><text x="72" y="297">J33</text>
          </g>
          <g class="waypoint junction" id="wp-junction34">
            <circle cx="50" cy="635" r="6" /><text x="50" y="635">J34</text>
          </g>
          <g class="waypoint junction" id="wp-junction35">
            <circle cx="167" cy="870" r="6" /><text x="167" y="870">J35</text>
          </g>
          <g class="waypoint junction" id="wp-junction36">
            <circle cx="238" cy="330" r="4" /><text x="238" y="330">J36</text>
          </g>

          <g class="waypoint junction" id="wp-junction37">
            <circle cx="200" cy="330" r="4" />
            <text x="200" y="330">J37</text>
          </g>

          <g class="waypoint junction" id="wp-junction38">
            <circle cx="200" cy="515" r="4" />
            <text x="200" y="515">J38</text>
          </g>

          <g class="waypoint junction" id="wp-junction39">
            <circle cx="200" cy="600" r="4" />
            <text x="200" y="600">J39</text>
          </g>


          <g class="waypoint room" id="wp-room107" onclick="onWaypointClick('wp-room107')">
            <circle cx="188" cy="1000" r="12" />
            <text style="font-size:10px;" x="188" y="1000">107</text>
          </g>

          <g class="waypoint room" id="wp-lhs-principal" onclick="onWaypointClick('wp-lhs-principal')">
            <circle cx="260" cy="1000" r="12" />
            <text style="font-size:10px;" x="260" y="1000">LHS PR.</text>
          </g>
          <g class="waypoint room" id="wp-reg-win" onclick="onWaypointClick('wp-reg-win')">
            <circle cx="535" cy="860" r="12" />
            <text style="font-size:10px;" x="535" y="860">R. WIN</text>
          </g>
          <g class="waypoint room" id="wp-cashier-win" onclick="onWaypointClick('wp-cashier-win')">
            <circle cx="345" cy="860" r="12" />
            <text style="font-size:10px;" x="345" y="860">C. WIN</text>
          </g>
          <g class="waypoint room" id="wp-ret" onclick="onWaypointClick('wp-ret')">
            <circle cx="167" cy="410" r="12" />
            <text style="font-size:10px;" x="167" y="410">RET</text>
          </g>
          <g class="waypoint room" id="wp-cr-male" onclick="onWaypointClick('wp-cr-male')">
            <circle cx="230" cy="600" r="12" />
            <text style="font-size:10px;" x="230" y="600">CR. M.</text>
          </g>
          <g class="waypoint room" id="wp-cr-female" onclick="onWaypointClick('wp-cr-female')">
            <circle cx="230" cy="375" r="12" />
            <text style="font-size:10px;" x="230" y="375">CR. F.</text>
          </g>

          <g class="waypoint junction" id="wp-open-court">
            <circle cx="345" cy="685" r="4" />
          </g>

          <g class="waypoint junction" id="wp-chempath">
            <circle cx="200" cy="685" r="4" />
          </g>


          <g class="waypoint room" id="wp-cr-male-j" onclick="onWaypointClick('wp-cr-male-j')">
            <circle cx="110" cy="870" r="12" />
            <text style="font-size:10px;" x="110" y="870">CR. M.</text>
          </g>
          <g class="waypoint junction" id="wp-canteen-hall">
            <circle cx="167" cy="297" r="4" />
          </g>
          <g class="waypoint junction" id="wp-j-cmbt-cr-stair">
            <circle cx="50" cy="1000" r="4" />
          </g>

          <!-- 1F Stairs -->
          <g class="waypoint junction" id="wp-1stair-coed1">
            <circle cx="167" cy="845" r="4" />
          </g>
          <g class="waypoint junction" id="wp-1stair-draft">
            <circle cx="260" cy="685" r="4" />
          </g>
          <g class="waypoint junction" id="wp-1j-cmbt-stair">
            <circle cx="95" cy="1000" r="4" />
          </g>
          <g class="waypoint junction" id="wp-1stair-cmbt-mid">
            <circle cx="450" cy="1000" r="4" />
          </g>
          <g class="waypoint junction" id="wp-1stair-cict">
            <circle cx="730" cy="175" r="4" />
          </g>
          <g class="waypoint junction" id="wp-1stair-cict2">
            <circle cx="238" cy="175" r="4" />
          </g>
          <g class="waypoint junction" id="wp-1stair-right-cmbt">
            <circle cx="948" cy="1000" r="4" />
          </g>
          <g class="waypoint junction" id="wp-1stair-lhs">
            <circle cx="990" cy="600" r="4" />
          </g>
          <!-- to 2f Stairs -->
          <g class="waypoint junction" id="wp-12stair-coed1">
            <circle cx="117" cy="845" r="4" />
          </g>
          <g class="waypoint junction" id="wp-12stair-coed2">
            <circle cx="117" cy="147" r="4" />
          </g>
          <g class="waypoint junction" id="wp-12stair-draft">
            <circle cx="260" cy="685" r="4" />
          </g>
          <g class="waypoint junction" id="wp-12j-cmbt-stair">
            <circle cx="95" cy="930" r="4" />
          </g>
          <g class="waypoint junction" id="wp-12stair-right-cmbt">
            <circle cx="948" cy="930" r="4" />
          </g>
          <g class="waypoint junction" id="wp-12stair-cmbt-mid">
            <circle cx="450" cy="930" r="4" />
          </g>
          <g class="waypoint junction" id="wp-12stair-cict">
            <circle cx="730" cy="125" r="4" />
          </g>
          <g class="waypoint junction" id="wp-12stair-cict2">
            <circle cx="238" cy="125" r="4" />
          </g>

          <g class="waypoint junction" id="wp-12stair-lhs">
            <circle cx="890" cy="600" r="4" />
          </g>
        </svg>

        <div id="floor2-layer" class="floor-layer hidden">
          <!-- FLOOR 2 WRAPPER -->
          <svg class="map-lines-overlay" viewBox="0 0 100 100" preserveAspectRatio="none">
            <path class="outer-wall" d="
            M 2.8, 97.5 
            L 2.8, 2.5 
            L 95.5, 2.5
            L 95.5, 18
            L 90.5, 18
            M 90.5, 26
            L 95.5, 26  
            M 95.5, 25.8
            L 95.5, 97.5
            L 48, 97.5 
            L 48, 94.5 
            M 45, 97.5 
            L 2.8, 97.5
          " />
            <!-- COED -->
            <line class="inner-wall building-coed" x1="17.5" y1="10" x2="17.5" y2="13.7" />
            <line class="inner-wall building-coed" x1="17.5" y1="16.7" x2="17.5" y2="75.9" />
            <line class="inner-wall building-coed" x1="14" y1="75.7" x2="17.5" y2="75.7" />
            <!-- CMBT -->
            <line class="inner-wall building-cmbt" x1="7.4" y1="91.3" x2="89.5" y2="91.3" />
            <line class="inner-wall building-cmbt" x1="7.5" y1="86.5" x2="7.5" y2="91.3" />
            <line class="inner-wall building-cmbt" x1="89.4" y1="86.5" x2="89.4" y2="91.3" />
            <!-- LHS -->
            <line class="inner-wall building-lhs" x1="94.5" y1="28.4" x2="94.5" y2="77.8" />
            <line class="inner-wall building-lhs" x1="89.3" y1="28.5" x2="94.5" y2="28.5" />
            <line class="inner-wall building-lhs" x1="89.3" y1="77.7" x2="94.5" y2="77.7" />
            <!-- CICT -->
            <line class="inner-wall building-cict" x1="78.5" y1="16.7" x2="20.4" y2="16.7" />
            <line class="inner-wall building-cict" x1="17.5" y1="13.7" x2="20.5" y2="13.7" />
            <line class="inner-wall building-cict" x1="17.5" y1="16.7" x2="20.5" y2="16.7" />
          </svg>

          <!-- Room Elements -->
          <div
            class="rm222 building-cict position-absolute bg-room text-dark p-1 d-flex flex-column align-items-center justify-content-center bordering"
            data-target="wp-room222">
            <span class="fw-bold text-center small">Room 222</span>
          </div>
          <div
            class="rm221 building-cict position-absolute bg-room text-dark p-1 d-flex flex-column align-items-center justify-content-center bordering"
            data-target="wp-room221">
            <span class="fw-bold text-center small">Room 221</span>
          </div>
          <div
            class="stairs-cict21 building-cict position-absolute bg-stairs text-dark p-1 d-flex flex-column align-items-center justify-content-center bordering">

          </div>
          <div
            class="rm220 building-cict position-absolute bg-room text-dark p-1 d-flex flex-column align-items-center justify-content-center bordering"
            data-target="wp-room220">
            <span class="fw-bold text-center small">Room 220</span>
          </div>
          <div
            class="rm219 building-cict position-absolute bg-room text-dark p-1 d-flex flex-column align-items-center justify-content-center bordering"
            data-target="wp-room219">
            <span class="fw-bold text-center small">Room 219</span>
          </div>
          <div
            class="rm218 building-cict position-absolute bg-room text-dark p-1 d-flex flex-column align-items-center justify-content-center bordering"
            data-target="wp-room218">
            <span class="fw-bold text-center small">Room 218</span>
          </div>
          <div
            class="coed-faculty building-cict position-absolute bg-primary text-white p-1 d-flex flex-column align-items-center justify-content-center bordering"
            data-target="wp-coed-faculty">
            <span class="fw-bold text-center small">COED Faculty</span>
          </div>
          <div
            class="stairs-cict22 building-cict position-absolute bg-stairs text-dark p-1 d-flex flex-column align-items-center justify-content-center bordering">
          </div>
          <div
            class="filler3 building-cict position-absolute bg-secondary p-1 d-flex flex-column align-items-center justify-content-center bordering">
          </div>
          <div
            class="guidance building-cict position-absolute bg-offices text-white p-1 d-flex flex-column align-items-center justify-content-center bordering"
            data-target="wp-guidance">
            <span class="fw-bold text-center small">Guidance</span>
          </div>
          <div
            class="coed-office building-coed position-absolute bg-offices text-white p-1 d-flex flex-column align-items-center justify-content-center bordering"
            data-target="wp-coed-office">
            <span class="fw-bold text-center small">COED Office</span>
          </div>
          <div
            class="stairs-educ21 building-coed position-absolute bg-stairs text-dark p-1 d-flex flex-column align-items-center justify-content-center bordering">
          </div>
          <div
            class="ed-tech building-coed position-absolute bg-offices text-white p-1 d-flex flex-column align-items-center justify-content-center bordering"
            data-target="wp-ed-tech">
            <span class="fw-bold text-center small">Ed Tech</span>
          </div>
          <div
            class="rm215 building-coed position-absolute bg-room text-dark p-1 d-flex flex-column align-items-center justify-content-center bordering"
            data-target="wp-room215">
            <span class="fw-bold text-center small">Room 215</span>
          </div>
          <div
            class="rm214 building-coed position-absolute bg-room text-dark p-1 d-flex flex-column align-items-center justify-content-center bordering"
            data-target="wp-room214">
            <span class="fw-bold text-center small">Room 214</span>
          </div>
          <div
            class="rm213 building-coed position-absolute bg-room text-dark p-1 d-flex flex-column align-items-center justify-content-center bordering"
            data-target="wp-room213">
            <span class="fw-bold text-center small">Room 213</span>
          </div>
          <div
            class="rm212 building-coed position-absolute bg-room text-dark p-1 d-flex flex-column align-items-center justify-content-center bordering"
            data-target="wp-room212">
            <span class="fw-bold text-center small">Room 212</span>
          </div>
          <div
            class="field-study building-coed position-absolute bg-offices text-white p-1 d-flex flex-column align-items-center justify-content-center bordering"
            data-target="wp-field-study-office">
            <span class="fw-bold text-center small">Field Study Office</span>
          </div>
          <div
            class="rm211 building-coed position-absolute bg-room text-dark p-1 d-flex flex-column align-items-center justify-content-center bordering"
            data-target="wp-room211">
            <span class="fw-bold text-center small">Room 211</span>
          </div>

          <div
            class="stairs-educ22 building-coed position-absolute bg-stairs text-dark p-1 d-flex flex-column align-items-center justify-content-center bordering">

          </div>
          <div
            class="stairs-cmbt22 building-cmbt position-absolute bg-stairs text-dark p-1 d-flex flex-column align-items-center justify-content-center bordering">
          </div>
          <div class="filler2 building-cmbt position-absolute bg-secondary bordering">
          </div>
          <div
            class="rm207 building-cmbt position-absolute bg-room text-dark p-1 d-flex flex-column align-items-center justify-content-center bordering"
            data-target="wp-room207">
            <span class="fw-bold text-center small">Room 207</span>
          </div>
          <div
            class="rm208 building-cmbt position-absolute bg-room text-dark p-1 d-flex flex-column align-items-center justify-content-center bordering"
            data-target="wp-room208">
            <span class="fw-bold text-center small">Room 208</span>
          </div>
          <div
            class="rm209 building-cmbt position-absolute bg-room text-dark p-1 d-flex flex-column align-items-center justify-content-center bordering"
            data-target="wp-room209">
            <span class="fw-bold text-center small">Room 209</span>
          </div>
          <div
            class="stairs-cmbt2mid building-cmbt position-absolute bg-stairs text-dark p-1 d-flex flex-column align-items-center justify-content-center bordering">

          </div>
          <div
            class="rm206 building-cmbt position-absolute bg-room text-dark p-1 d-flex flex-column align-items-center justify-content-center bordering"
            data-target="wp-room206">
            <span class="fw-bold text-center small">Room 206</span>
          </div>
          <div
            class="accreditation building-cmbt position-absolute bg-offices text-dark p-1 d-flex flex-column align-items-center justify-content-center bordering"
            data-target="wp-accreditation">
            <span class="fw-bold text-center small">Accreditation</span>
          </div>
          <div
            class="anihan building-cmbt position-absolute bg-offices text-dark p-1 d-flex flex-column align-items-center justify-content-center bordering"
            data-target="wp-anihan">
            <span class="fw-bold text-center small">Anihan</span>
          </div>
          <div class="filler building-cmbt position-absolute bg-secondary bordering">
          </div>
          <div
            class="stairs-cmbt21 building-cmbt position-absolute bg-stairs text-dark p-1 d-flex flex-column align-items-center justify-content-center bordering">

          </div>
          <div
            class="rm204 building-lhs position-absolute bg-room text-dark p-1 d-flex flex-column align-items-center justify-content-center bordering"
            data-target="wp-room204">
            <span class="fw-bold text-center small">Room 204</span>
          </div>
          <div
            class="rm203 building-lhs position-absolute bg-room text-dark p-1 d-flex flex-column align-items-center justify-content-center bordering"
            data-target="wp-room203">
            <span class="fw-bold text-center small">Room 203</span>
          </div>
          <div
            class="stairs-lhs2 building-lhs position-absolute bg-stairs text-dark p-1 d-flex flex-column align-items-center justify-content-center bordering">

          </div>
          <div
            class="rm202 building-lhs position-absolute bg-room text-dark p-1 d-flex flex-column align-items-center justify-content-center bordering"
            data-target="wp-room202">
            <span class="fw-bold text-center small">Room 202</span>
          </div>
          <div
            class="rm201 building-lhs position-absolute bg-room text-dark p-1 d-flex flex-column align-items-center justify-content-center bordering"
            data-target="wp-room201">
            <span class="fw-bold text-center small">Room 201</span>
          </div>
          <div
            class="gym position-absolute bg-gym text-white p-1 d-flex flex-column align-items-center justify-content-center bordering"
            data-target="wp-gym">
            <span class="fw-bold text-center small">Gym</span>
          </div>
          <div
            class="drafting building-draft position-absolute text-dark p-1 d-flex flex-column align-items-center justify-content-center bordering"
            data-target="wp-drafting">
            <span class="fw-bold text-center small">Drafting Room</span>
          </div>
          <div
            class="stairs-lab2 building-draft position-absolute bg-stairs text-white p-1 d-flex flex-column align-items-center justify-content-center bordering">
          </div>
          <svg id="nav-overlay-2f" viewBox="0 0 1081 1135" preserveAspectRatio="none">
            <g id="edge-layer-2f"></g>

            <!-- ROOM NODES -->
            <!-- CICT -->
            <g class="waypoint room" id="wp-room221">
              <circle cx="800" cy="175" r="10" />
              <text x="800" y="175">221</text>
            </g>
            <g class="waypoint room" id="wp-guidance">
              <circle cx="690" cy="125" r="10" />
              <text x="690" y="125">Guidance</text>
            </g>
            <g class="waypoint room" id="wp-room222">
              <circle cx="840" cy="175" r="10" />
              <text x="840" y="175">222</text>
            </g>
            <g class="waypoint room" id="wp-room220">
              <circle cx="625" cy="175" r="10" />
              <text x="625" y="175">220</text>
            </g>
            <g class="waypoint room" id="wp-room219">
              <circle cx="530" cy="175" r="10" />
              <text x="530" y="175">219</text>
            </g>
            <g class="waypoint room" id="wp-room218">
              <circle cx="408" cy="175" r="10" />
              <text x="408" y="175">218</text>
            </g>
            <g class="waypoint room" id="wp-coed-faculty">
              <circle cx="340" cy="175" r="10" />
              <text x="340" y="175">COED F.</text>
            </g>
            <!-- COED -->
            <g class="waypoint room" id="wp-coed-office">
              <circle cx="167" cy="147" r="10" />
              <text x="167" y="147">COED O.</text>
            </g>
            <g class="waypoint room" id="wp-ed-tech">
              <circle cx="167" cy="230" r="10" />
              <text x="167" y="230">Ed Tech</text>
            </g>
            <g class="waypoint room" id="wp-room215">
              <circle cx="167" cy="320" r="10" />
              <text x="167" y="320">215</text>
            </g>
            <g class="waypoint room" id="wp-room214">
              <circle cx="167" cy="410" r="10" />
              <text x="167" y="410">214</text>
            </g>
            <g class="waypoint room" id="wp-room213">
              <circle cx="167" cy="500" r="10" />
              <text x="167" y="500">213</text>
            </g>
            <g class="waypoint room" id="wp-room212">
              <circle cx="167" cy="590" r="10" />
              <text x="167" y="590">212</text>
            </g>
            <g class="waypoint room" id="wp-field-study-office">
              <circle cx="167" cy="680" r="10" />
              <text x="167" y="680">F.S.O.</text>
            </g>
            <g class="waypoint room" id="wp-room211">
              <circle cx="167" cy="770" r="10" />
              <text x="167" y="770">211</text>
            </g>
            <!-- CMBT -->
            <g class="waypoint room" id="wp-room209">
              <circle cx="200" cy="1000" r="12" />
              <text style="font-size:10px;" x="200" y="1000">209</text>
            </g>
            <g class="waypoint room" id="wp-room208">
              <circle cx="290" cy="1000" r="12" />
              <text style="font-size:10px;" x="290" y="1000">208</text>
            </g>
            <g class="waypoint room" id="wp-room207">
              <circle cx="380" cy="1000" r="12" />
              <text style="font-size:10px;" x="380" y="1000">207</text>
            </g>
            <g class="waypoint room" id="wp-room206">
              <circle cx="545" cy="1000" r="12" />
              <text x="545" y="1000">206</text>
            </g>
            <g class="waypoint room" id="wp-anihan">
              <circle cx="685" cy="1000" r="12" />
              <text x="685" y="1000">Anihan</text>
            </g>
            <g class="waypoint room" id="wp-accreditation">
              <circle cx="825" cy="1000" r="12" />
              <text x="825" y="1000">Accreditation</text>
            </g>



            <g class="waypoint room" id="wp-drafting">
              <circle cx="300" cy="685" r="12" />
              <text style="font-size:10px;" x="300" y="685">Drafting</text>
            </g>
            <!-- LHS -->
            <g class="waypoint room" id="wp-room201">
              <circle cx="990" cy="380" r="10" />
              <text x="990" y="380">201</text>
            </g>
            <g class="waypoint room" id="wp-room202">
              <circle cx="990" cy="490" r="10" />
              <text x="990" y="490">202</text>
            </g>
            <g class="waypoint room" id="wp-room203">
              <circle cx="990" cy="715" r="10" />
              <text x="990" y="715">203</text>
            </g>
            <g class="waypoint room" id="wp-room204">
              <circle cx="990" cy="825" r="10" />
              <text x="990" y="825">204</text>
            </g>

            <!-- 2F stairs junction -->
            <g class="waypoint junction" id="wp-2stair-right-cmbt">
              <circle cx="935" cy="1000" r="4" />
            </g>
            <g class="waypoint junction" id="wp-2j-cmbt-stair">
              <circle cx="115" cy="1000" r="4" />
            </g>
            <g class="waypoint junction" id="wp-2stair-cmbt-mid">
              <circle cx="470" cy="1000" r="4" />
            </g>

            <g class="waypoint junction" id="wp-2stair-lhs">
              <circle cx="990" cy="570" r="4" />
            </g>

            <g class="waypoint junction" id="wp-2stair-coed1">
              <circle cx="167" cy="830" r="4" />
            </g>
            <g class="waypoint junction" id="wp-2stair-coed2">
              <circle cx="167" cy="170" r="4" />
            </g>

            <g class="waypoint junction" id="wp-2stair-cict">
              <circle cx="690" cy="175" r="4" />
            </g>
            <g class="waypoint junction" id="wp-2stair-cict2">
              <circle cx="270" cy="175" r="4" />
            </g>


            <g class="waypoint junction" id="wp-2stair-draft">
              <circle cx="260" cy="685" r="4" />
            </g>

            <!-- from/to 1f stairs -->
            <g class="waypoint junction" id="wp-21stair-right-cmbt">
              <circle cx="935" cy="930" r="4" />
            </g>
            <g class="waypoint junction" id="wp-21j-cmbt-stair">
              <circle cx="115" cy="930" r="4" />
            </g>
            <g class="waypoint junction" id="wp-21stair-cmbt-mid">
              <circle cx="470" cy="930" r="4" />
            </g>

            <g class="waypoint junction" id="wp-21stair-lhs">
              <circle cx="890" cy="570" r="4" />
            </g>

            <g class="waypoint junction" id="wp-21stair-coed1">
              <circle cx="117" cy="830" r="4" />
            </g>
            <g class="waypoint junction" id="wp-21stair-coed2">
              <circle cx="117" cy="170" r="4" />
            </g>

            <g class="waypoint junction" id="wp-21stair-cict">
              <circle cx="690" cy="125" r="4" />
            </g>
            <g class="waypoint junction" id="wp-21stair-cict2">
              <circle cx="270" cy="125" r="4" />
            </g>

            <g class="waypoint junction" id="wp-21stair-draft">
              <circle cx="260" cy="685" r="4" />
            </g>

          </svg>
        </div>

      </div>
    </div>
  </div>

  <script>
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
      'wp-1stair-cmbt-mid': { x: 413, y: 1000 },
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
      'wp-junction37': { x: 200, y: 375 },
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
      'wp-2stair-draft': { x: 260, y: 685 },
      'wp-21stair-right-cmbt': { x: 935, y: 930 },
      'wp-21j-cmbt-stair': { x: 115, y: 930 },
      'wp-21stair-cmbt-mid': { x: 470, y: 930 },
      'wp-21stair-lhs': { x: 890, y: 570 },
      'wp-21stair-coed1': { x: 117, y: 830 },
      'wp-21stair-coed2': { x: 117, y: 170 },
      'wp-21stair-cict': { x: 690, y: 125 },
      'wp-21stair-cict2': { x: 270, y: 125 },
      'wp-21stair-draft': { x: 260, y: 685 }
    };

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

      'wp-coed-faculty': 'coed',
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
      'wp-gym': 'lhs',
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
      ['wp-21stair-cmbt-mid', 'wp-2stair-cmbt-mid']
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
        const edgeId = `edge-${a.replace('wp-', '')}-${b.replace('wp-', '')}` + (FLOOR_2F_NODE_IDS.has(a) && FLOOR_2F_NODE_IDS.has(b) ? '-2f' : '');
        line.setAttribute('id', edgeId);
        line.setAttribute('class', preservedClasses[edgeId] || 'edge');

        const both2f = FLOOR_2F_NODE_IDS.has(a) && FLOOR_2F_NODE_IDS.has(b);
        if (both2f) {
          const buildingA = FLOOR_2F_BUILDING_MAP[a];
          const buildingB = FLOOR_2F_BUILDING_MAP[b];
          if (buildingA && buildingA === buildingB) {
            line.classList.add(`building-${buildingA}`);
          }
        }
        const targetLayer = both2f ? layer2 : layer1;
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
          'wp-cict', 'wp-clinic', 'wp-conf', 'wp-dean', 'wp-room113', 'wp-coed-faculty', 'wp-room114', 'wp-supplyroom', 'GROUP_INFO_CICT'
        ],
        classes: ['building-cict', 'cict-faculty', 'coed-faculty', 'clinic', 'stairs-cict1', 'info-cict', 'conference-room', 'director-office', 'rm113', 'rm114', 'stairs-cict2', 'supply-room', 'rm222', 'rm221', 'rm220', 'rm219', 'rm218', 'guidance']
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
          'wp-cashier', 'wp-registrar', 'wp-osoad', 'wp-bmgs', 'wp-room107'
        ],
        classes: ['building-cmbt', 'stairs-cmbt22', 'rm207', 'rm208', 'rm209', 'stairs-cmbt2mid', 'rm206', 'accreditation', 'anihan', 'stairs-cmbt21', 'cashier', 'registrar', 'osoad', 'bmgs', 'rm107']
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

    // RESOLVE GROUPS OR SINGLE NODES
    function resolveTargets(targetVal) {
      if (NODE_GROUPS[targetVal]) {
        return NODE_GROUPS[targetVal];
      }
      return [targetVal];
    }

    // CLICK HANDLERS
    let routeStart = null;
    let activeRouteGroups = new Set();

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

        applyBuildingGroupVisibility();
        document.getElementById('msg').textContent =
          'Starting point selected. Choose destination.';
        return;
      }

      // Otherwise set destination and draw route
      selTo.value = target;
      applyBuildingGroupVisibility();
      findPath();
    }

    // FIND PATH EXECUTION
    function findPath() {
      const startVal = document.getElementById('sel-from').value;
      const endVal = document.getElementById('sel-to').value;

      if (!startVal || !endVal) {
        activeRouteGroups.clear();
        applyBuildingGroupVisibility();
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
      setFloor2ActiveBuilding(primaryPath);

      if (!primaryPath || primaryPath.length === 0) {
        activeRouteGroups.clear();
        applyBuildingGroupVisibility();
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

      activeRouteGroups = new Set(
        primaryPath
          .map(id => ROOM_BUILDING_MAP[id]?.group)
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
      setFloor2LayerVisible(false);

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

    // POPULATE THE DROPDOWNS
    dropdownOptions.forEach(opt => {
      selFrom.appendChild(new Option(opt.label, opt.value));
      selTo.appendChild(new Option(opt.label, opt.value));
    });

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
      'wp-coed-faculty': { group: 'coed', floor: 2 },
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
    const buildingFloors = { cict: 1, coed: 1, cmbt: 1, lhs: 1, draft: 1 };

    // Toggle building floor manually via toggle pills
    function toggleBuildingFloor(group, floor) {
      const floorNum = Number(floor);
      if (!Number.isInteger(floorNum) || floorNum < 1 || floorNum > 2) return;
      buildingFloors[group] = floorNum;
      updateToggleUI(group, floorNum);
      applyBuildingGroupVisibility();
    }
    function toggleCictCoedFloor(floor) {
      const floorNum = Number(floor);

      if (![1, 2].includes(floorNum)) return;

      // CICT and COED always move together
      buildingFloors.cict = floorNum;
      buildingFloors.coed = floorNum;

      updateToggleUI('cict', floorNum);
      updateToggleUI('coed', floorNum);
      updateCombinedToggleUI(floorNum);

      applyBuildingGroupVisibility();
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
      const pill = document.querySelector(`.building-toggle-pill[data-group="${group}"]`);
      if (!pill) return;
      const btns = pill.querySelectorAll('.btn');
      btns.forEach(btn => {
        const is1FBtn = btn.textContent.trim() === '1F';
        if ((floor === 1 && is1FBtn) || (floor === 2 && !is1FBtn)) {
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
      const allGroups = ['cict', 'coed', 'cmbt', 'lhs', 'draft'];

      const fromVal = document.getElementById('sel-from')?.value;
      const toVal = document.getElementById('sel-to')?.value;

      const fromData = fromVal ? ROOM_BUILDING_MAP[fromVal] : null;
      const toData = toVal ? ROOM_BUILDING_MAP[toVal] : null;

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
      const isCictCoedPair =
        fromData &&
        toData &&
        (
          (fromData.group === 'cict' && toData.group === 'coed') ||
          (fromData.group === 'coed' && toData.group === 'cict')
        ) &&
        fromData.floor === 2 &&
        toData.floor === 2;

      const combinedPill = document.querySelector(
        '.building-toggle-pill[data-group="cict-coed"]'
      );

      if (combinedPill) {
        combinedPill.style.display =
          isCictCoedPair ? 'flex' : 'none';
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
         * Only endpoint buildings can produce a solo toggle.
         */
        const endpointData = [fromData, toData].filter(
          data => data && data.group === group
        );

        if (endpointData.length > 0) {

          /*
           * Keep your previous rule:
           * solo toggle only appears when the endpoint
           * belongs to Floor 2.
           */
          shouldShowSolo = endpointData.some(
            data => data.floor === 2
          );
        }

        /*
         * CICT + COED pair gets the combined toggle instead.
         */
        if (
          isCictCoedPair &&
          (group === 'cict' || group === 'coed')
        ) {
          shouldShowSolo = false;
        }

        pill.style.display = shouldShowSolo ? 'flex' : 'none';
      });

      /*
       * ============================================================
       * FLOOR 2 VISIBILITY
       * ============================================================
       */
      const floor2Layer = document.getElementById('floor2-layer');

      const isFloor2ActiveAnywhere =
        allGroups.some(group => buildingFloors[group] === 2);

      if (floor2Layer) {
        if (isFloor2ActiveAnywhere) {
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

        group2FElements.forEach(el => {

          /*
           * CICT + COED combined mode:
           * both buildings follow the same floor.
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
           * Normal solo behavior.
           */
          const endpointData =
            group === 'cict' ? fromData :
              group === 'coed' ? toData :
                null;

          const show2F =
            groupFloor === 2 &&
            (
              (fromData?.group === group) ||
              (toData?.group === group)
            );

          if (show2F) {
            el.classList.remove('building-group-hidden');
          } else {
            el.classList.add('building-group-hidden');
          }
        });
      });
    }

    // Auto-sync building floor toggles when dropdown selection changes
    function syncTogglesFromSelection() {
      const fromVal = document.getElementById('sel-from')?.value;
      const toVal = document.getElementById('sel-to')?.value;

      const fromData = fromVal ? ROOM_BUILDING_MAP[fromVal] : null;
      const toData = toVal ? ROOM_BUILDING_MAP[toVal] : null;

      // Combined CICT + COED mode ONLY when:
      // CICT 2F <-> COED 2F
      const isCictCoedPair =
        fromData &&
        toData &&
        (
          (fromData.group === 'cict' && toData.group === 'coed') ||
          (fromData.group === 'coed' && toData.group === 'cict')
        ) &&
        fromData.floor === 2 &&
        toData.floor === 2;

      if (isCictCoedPair) {

        // Both buildings are controlled together.
        buildingFloors.cict = 2;
        buildingFloors.coed = 2;

        updateToggleUI('cict', 2);
        updateToggleUI('coed', 2);
        updateCombinedToggleUI(2);

      } else {

        // Normal independent behavior.
        // Only the actual endpoint's building gets its floor.
        [fromData, toData].forEach(roomData => {
          if (!roomData) return;

          buildingFloors[roomData.group] = roomData.floor;

          updateToggleUI(
            roomData.group,
            roomData.floor
          );
        });

        // Keep combined toggle UI hidden/not relevant
        updateCombinedToggleUI(1);
      }

      applyBuildingGroupVisibility();
    }
  </script>
</body>

</html>