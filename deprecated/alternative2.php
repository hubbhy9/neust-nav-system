<?php
include __DIR__ . '/libraries/include.php';
?>
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

    .bordering:not(.bg-stairs, .filler):hover {
      outline: 2px solid #000;
      z-index: 10 !important;
    }

    /* DYNAMIC CLICK STYLING FOR ROOM DIVS */
    .bordering.selected-room {
      outline: 3px solid #ff0055 !important;
      box-shadow: 0 0 10px rgba(255, 0, 85, 0.8);
      z-index: 15 !important;
    }

    /* SVG Overlay Layer */
    .map-lines-overlay,
    #nav-overlay {
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

    #nav-overlay {
      z-index: 20;
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
    }

    /* Alternative Path Styling (Gray & Broken/Dashed Line) */
    .edge.alt-active {
      stroke: #8d99ae;
      stroke-width: 6;
      stroke-dasharray: 8, 8;
    }

    #edge-layer {
      z-index: 1;
    }

    .waypoint {
      position: relative;
      display: none;
    }

    #wp-reg-win,
    #wp-cashier-win {
      display: inline !important;
    }

    .waypoint.start,
    .waypoint.end,
    .waypoint.alt-end,
    .waypoint.selected-waypoint {
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
    }

    .waypoint.end circle {
      fill: #dc3545 !important;
    }

    .waypoint.alt-end circle {
      fill: #8d99ae !important;
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
      height: 6.7%;
      top: 7.3%;
      right: 58.77%;
    }

    .rm114 {
      width: 7.19%;
      height: 6.7%;
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
      height: 5.81%;
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
      top: 80.3%;
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
      width: 7.3%;
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

      <!-- Modern Grid Legend -->
      <div class="card shadow-sm border-0 rounded-4 mx-auto" style="max-width: 1100px;">
        <div class="card-body p-3">
          <div class="row row-cols-2 row-cols-md-3 row-cols-lg-5 g-3 justify-content-center">
            <div class="col d-flex align-items-center gap-2">
              <div class="rounded-circle shadow-sm"
                style="width:14px; height:14px; background: #7BFF74; border: 1px solid rgba(0,0,0,0.1);"></div>
              <span class="small fw-medium">Help Desks</span>
            </div>
            <div class="col d-flex align-items-center gap-2">
              <div class="rounded-circle shadow-sm"
                style="width:14px; height:14px; background: #53D9FF; border: 1px solid rgba(0,0,0,0.1);"></div>
              <span class="small fw-medium">Instructional</span>
            </div>
            <div class="col d-flex align-items-center gap-2">
              <div class="rounded-circle shadow-sm"
                style="width:14px; height:14px; background: #FF9A0B; border: 1px solid rgba(0,0,0,0.1);"></div>
              <span class="small fw-medium">Offices</span>
            </div>
            <div class="col d-flex align-items-center gap-2">
              <div class="rounded-circle shadow-sm"
                style="width:14px; height:14px; background: #F0FFF8; border: 1px solid rgba(0,0,0,0.1);"></div>
              <span class="small fw-medium">Clinic</span>
            </div>
            <div class="col d-flex align-items-center gap-2">
              <div class="rounded-circle shadow-sm"
                style="width:14px; height:14px; background: #FF7AEE; border: 1px solid rgba(0,0,0,0.1);"></div>
              <span class="small fw-medium">Student Fac.</span>
            </div>
            <div class="col d-flex align-items-center gap-2">
              <div class="rounded-circle shadow-sm"
                style="width:14px; height:14px; background: #73006F; border: 1px solid rgba(0,0,0,0.1);"></div>
            </div>
            <div class="col d-flex align-items-center gap-2">
              <div class="rounded-circle shadow-sm"
                style="width:14px; height:14px; background: #97001C; border: 1px solid rgba(0,0,0,0.1);"></div>
              <span class="small fw-medium">Utilities</span>
            </div>
            <div class="col d-flex align-items-center gap-2">
              <div class="rounded-circle shadow-sm"
                style="width:14px; height:14px; background: #000DFD; border: 1px solid rgba(0,0,0,0.1);"></div>
              <span class="small fw-medium">Canteen</span>
            </div>
            <div class="col d-flex align-items-center gap-2">
              <div class="rounded-circle shadow-sm"
                style="width:14px; height:14px; background: #F1FF0B; border: 1px solid rgba(0,0,0,0.1);"></div>
              <span class="small fw-medium">General</span>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- MAP WRAPPER -->
    <div class="map-wrapper mx-auto mt-4" style="max-width: 1050px;">
      <div class="border position-relative fixed-canvas shadow-sm">

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

      </div>
    </div>
  </div>

  <script>
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
      const layer = document.getElementById('edge-layer');
      layer.innerHTML = '';

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
        line.setAttribute(
          'id',
          `edge-${a.replace('wp-', '')}-${b.replace('wp-', '')}`
        );
        line.setAttribute('class', 'edge');
        layer.appendChild(line);
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
      return document.getElementById(
        `edge-${a.replace('wp-', '')}-${b.replace('wp-', '')}`
      ) ||
        document.getElementById(
          `edge-${b.replace('wp-', '')}-${a.replace('wp-', '')}`
        );
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
  </script>
</body>

</html>