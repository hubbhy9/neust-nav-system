<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lol</title>

    <!-- Bootstrap 5 CSS & FontAwesome -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="map.css">


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
                       <!-- Dropdowns & Sort Control -->
                        <div class="col-md-3">
                            <label
                                class="form-label fw-semibold small text-uppercase tracking-wider text-muted">From</label>
                            <div class="input-group">
                                <span class="input-group-text bg-white border-end-0"><i
                                        class="fa fa-street-view text-primary"></i></span>
                                <select id="sel-from" class="form-select border-start-0 ps-0">
                                    <option value="">Choose starting point...</option>
                                </select>
                            </div>
                        </div>

                        <div class="col-md-3">
                            <label
                                class="form-label fw-semibold small text-uppercase tracking-wider text-muted">To</label>
                            <div class="input-group">
                                <span class="input-group-text bg-white border-end-0"><i
                                        class="fa fa-location-dot text-danger"></i></span>
                                <select id="sel-to" class="form-select border-start-0 ps-0">
                                    <option value="">Choose destination...</option>
                                </select>
                            </div>
                        </div>

                        <div class="col-md-2">
                            <label
                                class="form-label fw-semibold small text-uppercase tracking-wider text-muted">Sort By</label>
                            <div class="input-group">
                                <span class="input-group-text bg-white border-end-0"><i
                                        class="fa fa-arrow-down-a-z text-secondary"></i></span>
                                <select id="sel-sort" class="form-select border-start-0 ps-0" onchange="sortDropdowns()">
                                    <option value="alpha">A-Z</option>
                                    <option value="building">Building</option>
                                    <option value="floor">Floor</option>
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
                                <!-- button for debugging -->
                                <button class="btn btn-outline-info" onclick="toggleDebugNodes()" title="Toggle Nodes">
                                    <i class="fa fa-eye"></i> Nodes
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
                    class="position-absolute w-100 h-100 top-0 start-0" style="z-index: 99;"></div>
                <!-- Floating Per-Building Floor Toggle Buttons -->
                <div id="building-floor-toggles" class="position-absolute w-100 h-100"
                    style="z-index: 999; top: 0; left: 0; pointer-events: auto;">
                    <!-- CICT Toggle -->
                    <div class="building-toggle-pill position-absolute" data-group="cict" style="top: 2%; right: 40%;">
                        <span class="badge bg-primary me-1">CICT</span>
                        <div class="btn-group btn-group-sm" role="group">
                            <button type="button" style="pointer-events: auto; z-index:2000;" class="btn btn-xs btn-primary active"
                                onclick="toggleBuildingFloor('cict', 1)">1F</button>
                            <button type="button" style="pointer-events: auto; z-index:2000;" class="btn btn-xs btn-outline-primary"
                                onclick="toggleBuildingFloor('cict', 2)">2F</button>
                        </div>
                    </div>

                    <!-- COED Toggle -->
                    <div class="building-toggle-pill position-absolute" data-group="coed" style="top: 10%; right: 88%;">
                        <span class="badge bg-primary me-1">COED</span>
                        <div class="btn-group btn-group-sm" role="group">
                            <button type="button" style="pointer-events: auto; z-index:2000;" class="btn btn-xs btn-primary active"
                                onclick="toggleBuildingFloor('coed', 1)">1F</button>
                            <button type="button" style="pointer-events: auto; z-index:2000;" class="btn btn-xs btn-outline-primary"
                                onclick="toggleBuildingFloor('coed', 2)">2F</button>
                        </div>
                    </div>
                    <!-- CICT + COED Combined Toggle -->
                    <div class="building-toggle-pill position-absolute" data-group="cict-coed"
                        style="top: 2%; right: 40%; display: none;">

                        <span class="badge bg-primary me-1">CICT + COED</span>

                        <div class="btn-group btn-group-sm" role="group">
                            <button type="button" style="pointer-events: auto; z-index:2000;" class="btn btn-xs btn-primary active"
                                onclick="toggleCictCoedFloor(1)">
                                1F
                            </button>

                            <button type="button" style="pointer-events: auto; z-index:2000;" class="btn btn-xs btn-outline-primary"
                                onclick="toggleCictCoedFloor(2)">
                                2F
                            </button>
                        </div>
                    </div>
                    <!-- CMBT Toggle -->
                    <div class="building-toggle-pill position-absolute" data-group="cmbt"
                        style="bottom: 2%; right: 45%;">
                        <span class="badge bg-primary me-1">CMBT</span>
                        <div class="btn-group btn-group-sm" role="group">
                            <button type="button" style="pointer-events: auto; z-index:2000;" class="btn btn-xs btn-primary active"
                                onclick="toggleBuildingFloor('cmbt', 1)">1F</button>
                            <button type="button" style="pointer-events: auto; z-index:2000;" class="btn btn-xs btn-outline-primary"
                                onclick="toggleBuildingFloor('cmbt', 2)">2F</button>
                            <button type="button" style="pointer-events: auto; z-index:2000;" class="btn btn-xs btn-outline-primary"
                                onclick="toggleBuildingFloor('cmbt', 3)">3F</button>
                        </div>
                    </div>

                    <!-- LHS Toggle -->
                    <div class="building-toggle-pill position-absolute" data-group="lhs" style="top: 24%; right: 2%;">
                        <span class="badge bg-primary me-1">LHS</span>
                        <div class="btn-group btn-group-sm" role="group">
                            <button type="button" style="pointer-events: auto; z-index:2000;" class="btn btn-xs btn-primary active"
                                onclick="toggleBuildingFloor('lhs', 1)">1F</button>
                            <button type="button" style="pointer-events: auto; z-index:2000;" class="btn btn-xs btn-outline-primary"
                                onclick="toggleBuildingFloor('lhs', 2)">2F</button>
                        </div>
                    </div>

                    <!-- DRAFT / LAB Toggle -->
                    <div class="building-toggle-pill position-absolute" data-group="draft"
                        style="top: 55%; right: 68%;">
                        <span class="badge bg-primary me-1">LAB</span>
                        <div class="btn-group btn-group-sm" role="group">
                            <button type="button" style="pointer-events: auto; z-index:2000;" class="btn btn-xs btn-primary active"
                                onclick="toggleBuildingFloor('draft', 1)">1F</button>
                            <button type="button" style="pointer-events: auto; z-index:2000;" class="btn btn-xs btn-outline-primary"
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
                    class="rm109 position-absolute bg-room dark p-1 d-flex flex-column align-items-center justify-content-center bordering">
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
                        <circle cx="640" cy="1000" r="4" /><text x="640" y="1000">Hall A</text> 
                    </g>
                    <g class="waypoint junction" id="wp-lhs-hall">
                        <circle cx="990" cy="1000" r="4" />
                        <text x="990" y="1000">LHS Hall</text>
                    </g>

                    <g class="waypoint junction" id="wp-exit">
                        <circle cx="950" cy="265" r="4" /><text x="950" y="265">Exit</text>
                    </g>
                    <g class="waypoint junction" id="wp-j-exit">
                        <circle cx="835" cy="265" r="4" />
                        <text x="835" y="265">J-Exit</text>
                    </g>
                    <g class="waypoint junction" id="wp-hallmid-b">
                        <circle cx="640" cy="860" r="4" />
                        <text x="640" y="860">Hall B</text>
                    </g>
                    <g class="waypoint junction" id="wp-old-reg">
                        <circle cx="835" cy="860" r="4" />
                        <text x="835" y="860">OR</text>
                    </g>
                    <g class="waypoint junction" id="wp-front-gym">
                        <circle cx="835" cy="485" r="4" />
                        <text x="835" y="485">FG</text>
                    </g>

                    <g class="waypoint junction" id="wp-dean-win">
                        <circle cx="540" cy="175" r="4" />
                        <text x="540" y="175">DO</text>
                    </g>

                    <g class="waypoint junction" id="wp-stockroom">
                        <circle cx="280" cy="175" r="4" />
                        <text x="280" y="175">Sr</text>
                    </g>
                    <g class="waypoint junction" id="wp-j-cmbt-left">
                        <circle cx="50" cy="870" r="4" />
                        <text x="50" y="870">CL</text>
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
                        <circle cx="345" cy="685" r="4" /><text x="345" y="685">OC</text>
                    </g>

                    <g class="waypoint junction" id="wp-chempath">
                        <circle cx="200" cy="685" r="4" />
                        <text x="200" y="685">CP</text>
                    </g>


                    <g class="waypoint room" id="wp-cr-male-j" onclick="onWaypointClick('wp-cr-male-j')">
                        <circle cx="110" cy="870" r="12" />
                        <text style="font-size:10px;" x="110" y="870">CR. M.</text>
                    </g>
                    <g class="waypoint junction" id="wp-canteen-hall">
                        <circle cx="167" cy="297" r="4" />
                        <text x="167" y="297">CH</text>
                    </g>
                    <g class="waypoint junction" id="wp-j-cmbt-cr-stair">
                        <circle cx="50" cy="1000" r="4" />
                        <text x="50" y="1000">CRS</text>
                    </g>

                    <!-- 1F Stairs -->
                    <g class="waypoint junction" id="wp-1stair-coed1">
                        <circle cx="167" cy="845" r="4" />
                        <text x="167" y="845">1FD</text>
                    </g>
                    <g class="waypoint junction" id="wp-1stair-draft">
                        <circle cx="260" cy="685" r="4" />
                        <text x="260" y="685">1FDraft</text>
                    </g>
                    <g class="waypoint junction" id="wp-1j-cmbt-stair">
                        <circle cx="95" cy="1000" r="4" />
                        <text x="95" y="1000">1FM</text>
                    </g>
                    <g class="waypoint junction" id="wp-1stair-cmbt-mid">
                        <circle cx="450" cy="1000" r="4" />
                        <text x="450" y="1000">1FMM</text>
                    </g>
                    <g class="waypoint junction" id="wp-1stair-cict">
                        <circle cx="730" cy="175" r="4" />
                        <text x="730" y="175">1FIT</text>
                    </g>
                    <g class="waypoint junction" id="wp-1stair-cict2">
                        <circle cx="238" cy="175" r="4" />
                        <text x="238" y="175">1FIT2</text>
                    </g>
                    <g class="waypoint junction" id="wp-1stair-right-cmbt">
                        <circle cx="948" cy="1000" r="4" />
                        <text x="948" y="1000">1FMR</text>
                    </g>
                    <g class="waypoint junction" id="wp-1stair-lhs">
                        <circle cx="990" cy="600" r="4" />
                        <text x="990" y="600">1FL</text>
                    </g>
                    <!-- to 2f Stairs -->
                    <g class="waypoint junction" id="wp-12stair-coed1">
                        <circle cx="117" cy="845" r="4" />
                        <text x="117" y="845">2FD1</text>
                    </g>
                    <g class="waypoint junction" id="wp-12stair-coed2">
                        <circle cx="117" cy="147" r="4" />
                        <text x="117" y="147">2FD2</text>
                    </g>
                    <g class="waypoint junction" id="wp-12stair-draft">
                        <circle cx="260" cy="685" r="4" />
                        <text x="260" y="685">2FDraft</text>
                    </g>
                    <g class="waypoint junction" id="wp-12j-cmbt-stair">
                        <circle cx="95" cy="930" r="4" />
                        <text x="95" y="930">2FM</text>
                    </g>
                    <g class="waypoint junction" id="wp-12stair-right-cmbt">
                        <circle cx="948" cy="930" r="4" />
                        <text x="948" y="930">2FMR</text>
                    </g>
                    <g class="waypoint junction" id="wp-12stair-cmbt-mid">
                        <circle cx="450" cy="930" r="4" />
                        <text x="450" y="930">2FMM</text>
                    </g>
                    <g class="waypoint junction" id="wp-12stair-cict">
                        <circle cx="730" cy="125" r="4" />
                        <text x="730" y="125">2FIT</text>
                    </g>
                    <g class="waypoint junction" id="wp-12stair-cict2">
                        <circle cx="238" cy="125" r="4" />
                        <text x="238" y="125">2FIT2</text>
                    </g>

                    <g class="waypoint junction" id="wp-12stair-lhs">
                        <circle cx="890" cy="600" r="4" />
                        <text x="890" y="600">2FL</text>
                    </g>
                </svg>

                <div id="floor2-layer" class="floor-layer hidden">
                    <!-- FLOOR 2 WRAPPER -->

                    <!-- Room Elements -->
                    <div class="rm222 building-cict position-absolute bg-room text-dark p-1 d-flex flex-column align-items-center justify-content-center bordering"
                        data-target="wp-room222">
                        <span class="fw-bold text-center small">Room 222</span>
                    </div>
                    <div class="rm221 building-cict position-absolute bg-room text-dark p-1 d-flex flex-column align-items-center justify-content-center bordering"
                        data-target="wp-room221">
                        <span class="fw-bold text-center small">Room 221</span>
                    </div>
                    <div
                        class="stairs-cict21 building-cict position-absolute bg-stairs text-dark p-1 d-flex flex-column align-items-center justify-content-center bordering">

                    </div>
                    <div class="rm220 building-cict position-absolute bg-room text-dark p-1 d-flex flex-column align-items-center justify-content-center bordering"
                        data-target="wp-room220">
                        <span class="fw-bold text-center small">Room 220</span>
                    </div>
                    <div class="rm219 building-cict position-absolute bg-room text-dark p-1 d-flex flex-column align-items-center justify-content-center bordering"
                        data-target="wp-room219">
                        <span class="fw-bold text-center small">Room 219</span>
                    </div>
                    <div class="rm218 building-cict position-absolute bg-room text-dark p-1 d-flex flex-column align-items-center justify-content-center bordering"
                        data-target="wp-room218">
                        <span class="fw-bold text-center small">Room 218</span>
                    </div>
                    <div class="cict-coed-faculty building-cict position-absolute bg-primary text-white p-1 d-flex flex-column align-items-center justify-content-center bordering"
                        data-target="wp-cict-coed-faculty">
                        <span class="fw-bold text-center small">COED Faculty</span>
                    </div>
                    <div
                        class="stairs-cict22 building-cict position-absolute bg-stairs text-dark p-1 d-flex flex-column align-items-center justify-content-center bordering">
                    </div>
                    <div
                        class="filler3 building-cict position-absolute bg-secondary p-1 d-flex flex-column align-items-center justify-content-center bordering">
                    </div>
                    <div class="guidance building-cict position-absolute bg-offices text-white p-1 d-flex flex-column align-items-center justify-content-center bordering"
                        data-target="wp-guidance">
                        <span class="fw-bold text-center small">Guidance</span>
                    </div>
                    <div class="coed-office building-coed position-absolute bg-offices text-white p-1 d-flex flex-column align-items-center justify-content-center bordering"
                        data-target="wp-coed-office">
                        <span class="fw-bold text-center small">COED Office</span>
                    </div>
                    <div
                        class="stairs-educ21 building-coed position-absolute bg-stairs text-dark p-1 d-flex flex-column align-items-center justify-content-center bordering">
                    </div>
                    <div class="ed-tech building-coed position-absolute bg-offices text-white p-1 d-flex flex-column align-items-center justify-content-center bordering"
                        data-target="wp-ed-tech">
                        <span class="fw-bold text-center small">Ed Tech</span>
                    </div>
                    <div class="rm215 building-coed position-absolute bg-room text-dark p-1 d-flex flex-column align-items-center justify-content-center bordering"
                        data-target="wp-room215">
                        <span class="fw-bold text-center small">Room 215</span>
                    </div>
                    <div class="rm214 building-coed position-absolute bg-room text-dark p-1 d-flex flex-column align-items-center justify-content-center bordering"
                        data-target="wp-room214">
                        <span class="fw-bold text-center small">Room 214</span>
                    </div>
                    <div class="rm213 building-coed position-absolute bg-room text-dark p-1 d-flex flex-column align-items-center justify-content-center bordering"
                        data-target="wp-room213">
                        <span class="fw-bold text-center small">Room 213</span>
                    </div>
                    <div class="rm212 building-coed position-absolute bg-room text-dark p-1 d-flex flex-column align-items-center justify-content-center bordering"
                        data-target="wp-room212">
                        <span class="fw-bold text-center small">Room 212</span>
                    </div>
                    <div class="field-study building-coed position-absolute bg-offices text-white p-1 d-flex flex-column align-items-center justify-content-center bordering"
                        data-target="wp-field-study-office">
                        <span class="fw-bold text-center small">Field Study Office</span>
                    </div>
                    <div class="rm211 building-coed position-absolute bg-room text-dark p-1 d-flex flex-column align-items-center justify-content-center bordering"
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
                    <div class="rm207 building-cmbt position-absolute bg-room text-dark p-1 d-flex flex-column align-items-center justify-content-center bordering"
                        data-target="wp-room207">
                        <span class="fw-bold text-center small">Room 207</span>
                    </div>
                    <div class="rm208 building-cmbt position-absolute bg-room text-dark p-1 d-flex flex-column align-items-center justify-content-center bordering"
                        data-target="wp-room208">
                        <span class="fw-bold text-center small">Room 208</span>
                    </div>
                    <div class="rm209 building-cmbt position-absolute bg-room text-dark p-1 d-flex flex-column align-items-center justify-content-center bordering"
                        data-target="wp-room209">
                        <span class="fw-bold text-center small">Room 209</span>
                    </div>
                    <div
                        class="stairs-cmbt2mid building-cmbt position-absolute bg-stairs text-dark p-1 d-flex flex-column align-items-center justify-content-center bordering">

                    </div>
                    <div class="rm206 building-cmbt position-absolute bg-room text-dark p-1 d-flex flex-column align-items-center justify-content-center bordering"
                        data-target="wp-room206">
                        <span class="fw-bold text-center small">Room 206</span>
                    </div>
                    <div class="accreditation building-cmbt position-absolute bg-offices text-dark p-1 d-flex flex-column align-items-center justify-content-center bordering"
                        data-target="wp-accreditation">
                        <span class="fw-bold text-center small">Accreditation</span>
                    </div>
                    <div class="anihan building-cmbt position-absolute bg-offices text-dark p-1 d-flex flex-column align-items-center justify-content-center bordering"
                        data-target="wp-anihan">
                        <span class="fw-bold text-center small">Anihan</span>
                    </div>
                    <div class="filler building-cmbt position-absolute bg-secondary bordering">
                    </div>
                    <div
                        class="stairs-cmbt21 building-cmbt position-absolute bg-stairs text-dark p-1 d-flex flex-column align-items-center justify-content-center bordering">

                    </div>
                    <div class="rm204 building-lhs position-absolute bg-room text-dark p-1 d-flex flex-column align-items-center justify-content-center bordering"
                        data-target="wp-room204">
                        <span class="fw-bold text-center small">Room 204</span>
                    </div>
                    <div class="rm203 building-lhs position-absolute bg-room text-dark p-1 d-flex flex-column align-items-center justify-content-center bordering"
                        data-target="wp-room203">
                        <span class="fw-bold text-center small">Room 203</span>
                    </div>
                    <div
                        class="stairs-lhs2 building-lhs position-absolute bg-stairs text-dark p-1 d-flex flex-column align-items-center justify-content-center bordering">

                    </div>
                    <div class="rm202 building-lhs position-absolute bg-room text-dark p-1 d-flex flex-column align-items-center justify-content-center bordering"
                        data-target="wp-room202">
                        <span class="fw-bold text-center small">Room 202</span>
                    </div>
                    <div class="rm201 building-lhs position-absolute bg-room text-dark p-1 d-flex flex-column align-items-center justify-content-center bordering"
                        data-target="wp-room201">
                        <span class="fw-bold text-center small">Room 201</span>
                    </div>
                    <div class="drafting building-draft position-absolute text-dark p-1 d-flex flex-column align-items-center justify-content-center bordering"
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
                        <g class="waypoint room" id="wp-cict-coed-faculty">
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
                            <text x="935" y="1000">2FMR1</text>
                        </g>
                        <g class="waypoint junction" id="wp-2j-cmbt-stair">
                            <circle cx="115" cy="1000" r="4" />
                            <text x="115" y="1000">here</text>
                        </g>
                        <g class="waypoint junction" id="wp-2stair-cmbt-mid">
                            <circle cx="470" cy="1000" r="4" />
                            <text x="470" y="1000">2FMM</text>
                        </g>

                        <g class="waypoint junction" id="wp-2stair-lhs">
                            <circle cx="990" cy="570" r="4" />
                            <text x="990" y="570">2FL</text>
                        </g>

                        <g class="waypoint junction" id="wp-2stair-coed1">
                            <circle cx="167" cy="830" r="4" />
                            <text x="167" y="830">2FD1</text>
                        </g>
                        <g class="waypoint junction" id="wp-2stair-coed2">
                            <circle cx="167" cy="170" r="4" />
                            <text x="167" y="170">2FD2</text>
                        </g>

                        <g class="waypoint junction" id="wp-2stair-cict">
                            <circle cx="690" cy="175" r="4" />
                            <text x="690" y="175">2FIT</text>
                        </g>
                        <g class="waypoint junction" id="wp-2stair-cict2">
                            <circle cx="270" cy="175" r="4" />
                            <text x="270" y="175">2FIT2</text>
                        </g>


                        <g class="waypoint junction" id="wp-2stair-draft">
                            <circle cx="260" cy="685" r="4" />
                            <text x="260" y="685">2FDraft</text>
                        </g>

                        <!-- from/to 1f stairs -->
                        <g class="waypoint junction" id="wp-21stair-right-cmbt">
                            <circle cx="935" cy="930" r="4" />
                            <text x="935" y="930">1FMR</text>
                        </g>
                        <g class="waypoint junction" id="wp-21j-cmbt-stair">
                            <circle cx="115" cy="930" r="4" />
                            <text x="115" y="930">2FM</text>
                        </g>
                        <g class="waypoint junction" id="wp-21stair-cmbt-mid">
                            <circle cx="470" cy="930" r="4" />
                            <text x="470" y="930">1FMM</text>
                        </g>

                        <g class="waypoint junction" id="wp-21stair-lhs">
                            <circle cx="890" cy="570" r="4" />
                            <text x="890" y="570">1FL</text>
                        </g>

                        <g class="waypoint junction" id="wp-21stair-coed1">
                            <circle cx="117" cy="830" r="4" />
                            <text x="117" y="830">1FD1</text>
                        </g>
                        <g class="waypoint junction" id="wp-21stair-coed2">
                            <circle cx="117" cy="170" r="4" />
                            <text x="117" y="170">1FD2</text>
                        </g>

                        <g class="waypoint junction" id="wp-21stair-cict">
                            <circle cx="690" cy="125" r="4" />
                            <text x="690" y="125">1FIT</text>
                        </g>
                        <g class="waypoint junction" id="wp-21stair-cict2">
                            <circle cx="270" cy="125" r="4" />
                            <text x="270" y="125">1FIT2</text>
                        </g>

                        <g class="waypoint junction" id="wp-21stair-draft">
                            <circle cx="260" cy="685" r="4" />
                            <text x="260" y="685">1FDraft</text>
                        </g>

                    </svg>
                </div>
                <!-- FLOOR 3 LAYER -->
                <div id="floor3-layer" class="floor-layer hidden">
                    <!-- Room Divs -->
                    <div
                        class="stairs-cmbt32 cmbt-stairs building-cmbt bg-stairs position-absolute text-dark p-1 d-flex flex-column align-items-center justify-content-center">
                    </div>
                    <div class="rm301 cmbt-room building-cmbt position-absolute bg-room bordering text-dark p-1 d-flex flex-column align-items-center justify-content-center"
                        data-target="wp-room301">
                        <span class="fw-bold text-center small">Room 301</span>
                    </div>
                    <div class="rm303 cmbt-room building-cmbt position-absolute bg-room bordering  text-dark p-1 d-flex flex-column align-items-center justify-content-center"
                        data-target="wp-room303">
                        <span class="fw-bold text-center small">Room 303</span>
                    </div>
                    <div class="rm302 cmbt-room building-cmbt position-absolute bg-room bordering  text-dark p-1 d-flex flex-column align-items-center justify-content-center"
                        data-target="wp-room302">
                        <span class="fw-bold text-center small">Room 302</span>
                    </div>
                    <div class="library cmbt-office building-cmbt position-absolute  bg-offices bordering text-dark p-1 d-flex flex-column align-items-center justify-content-center"
                        data-target="wp-library">
                        <span class="fw-bold text-center small">Library</span>
                    </div>

                    <div
                        class="filler2 building-cmbt position-absolute bg-secondary p-1 d-flex flex-column align-items-center justify-content-center bordering">
                    </div>
                    <div
                        class="stairs-cmbt31 building-cmbt cmbt-stairs position-absolute text-dark p-1 d-flex flex-column align-items-center justify-content-center">
                    </div>
                    <div class="filler building-cmbt level-3-room position-absolute bg-secondary bordering">
                </div>
                <!-- 3F Waypoints SVG -->
                <svg id="nav-overlay-3f" viewBox="0 0 1081 1135" preserveAspectRatio="none">
                    <g id="edge-layer-3f"></g>

                    <g class="waypoint room" id="wp-room301" onclick="onWaypointClick('wp-room301')">
                        <circle cx="200" cy="1000" r="12" />
                        <text style="font-size:10px;" x="200" y="1000">301</text>
                    </g>
                    <g class="waypoint room" id="wp-room302" onclick="onWaypointClick('wp-room302')">
                        <circle cx="380" cy="1000" r="12" />
                        <text x="380" y="1000">302</text>
                    </g>
                    <g class="waypoint room" id="wp-room303" onclick="onWaypointClick('wp-room303')">
                        <circle cx="290" cy="1000" r="12" />
                        <text style="font-size:10px;" x="290" y="1000">303</text>
                    </g>
                    <g class="waypoint room" id="wp-library" onclick="onWaypointClick('wp-library')">
                        <circle cx="655" cy="1000" r="12" />
                        <text x="655" y="1000">LIB</text>
                    </g>

                    <!-- 3F Stair Junctions -->
                    <g class="waypoint junction" id="wp-3stair-right-cmbt">
                        <circle cx="948" cy="1000" r="4" />
                        <text x="948" y="1000">3FMR</text>
                    </g>
                    <g class="waypoint junction" id="wp-3j-cmbt-stair">
                        <circle cx="115" cy="1000" r="4" />
                        <text x="115" y="1000">3FML</text>
                    </g>
                    <g class="waypoint junction" id="wp-23stair-right-cmbt">
                        <circle cx="948" cy="930" r="4" />
                        <text x="948" y="930">23FMR</text>
                    </g>
                    <g class="waypoint junction" id="wp-23j-cmbt-stair">
                        <circle cx="95" cy="930" r="4" />
                        <text x="95" y="930">23FM</text>
                    </g>
                </svg>

            </div>

        </div>
    </div>
    </div>
    <script src="map.js"></script>
</body>

</html>