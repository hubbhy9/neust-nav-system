<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Trial</title>

    <!-- Bootstrap 5 CSS & FontAwesome -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="map.css">


</head>
<div class="card shadow-sm border-0 rounded-4 mb-4 mx-auto" style="max-width: 1100px;">
    <div class="card-body p-4">
        <div class="row g-3 align-items-end">
            <!-- Dropdowns & Sort Control -->
            <div class="col-md-3">
                <label class="form-label fw-semibold small text-uppercase tracking-wider text-muted">From</label>
                <div class="input-group">
                    <span class="input-group-text bg-white border-end-0"><i
                            class="fa fa-street-view text-primary"></i></span>
                    <select id="sel-from" class="form-select border-start-0 ps-0">
                        <option value="">Choose starting point...</option>
                    </select>
                </div>
            </div>

            <div class="col-md-3">
                <label class="form-label fw-semibold small text-uppercase tracking-wider text-muted">To</label>
                <div class="input-group">
                    <span class="input-group-text bg-white border-end-0"><i
                            class="fa fa-location-dot text-danger"></i></span>
                    <select id="sel-to" class="form-select border-start-0 ps-0">
                        <option value="">Choose destination...</option>
                    </select>
                </div>
            </div>

            <div class="col-md-3">
                <label class="form-label fw-semibold small text-uppercase tracking-wider text-muted">Sort By</label>
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

            <!-- Search beside Sort -->
            <div class="col-md-3">
                <label class="form-label fw-semibold small text-uppercase tracking-wider text-muted">Search</label>
                <div class="input-group" style="position:relative;">
                    <span class="input-group-text bg-white border-end-0"><i
                            class="fa fa-search text-secondary"></i></span>
                    <input id="sel-search" type="search" class="form-control border-start-0 ps-0"
                        placeholder="Search locations..." autocomplete="off">
                    <div id="poi-suggestions" class="list-group" style="position:absolute; top:100%; left:0; z-index:2000; width:100%; max-height:220px; overflow:auto; display:none;">
                    </div>
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="d-flex justify-content-center">
                <div class="d-flex gap-2">
                    <button class="btn btn-primary fw-bold" onclick="findPath()">
                        <i class="fa fa-route me-2"></i>Find Path
                    </button>
                    <button class="btn btn-outline-secondary" onclick="resetNav()" title="Reset Map">
                        <i class="fa fa-rotate-left"></i>
                    </button>
                    <!-- button for debugging -->
                    <button class="btn btn-outline-info" onclick="toggleDebugNodes()" title="Toggle Nodes">
                        <i class="fa fa-eye"></i> Nodes
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- POI search script (NODES-based predictive tags) -->
    <script src="poi-search.js"></script>
</div>