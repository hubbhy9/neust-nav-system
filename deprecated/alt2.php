<?php
include __DIR__ . '/libraries/include.php';
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CMBT 3rd Floor Map</title>
    <style>
        body {
            margin: 0;
            font-family: Arial, sans-serif;
            background: #f4f7f6;
        }

        .map-wrapper {
            max-width: 1050px;
            margin: 30px auto;
            background: #fff;
            box-shadow: 0 4px 12px rgba(0, 0, 0, .05);
            padding: 1.5rem;
            border-radius: 12px;
        }

        .filler2 {
            width: 6.92%;
            height: 8.81%;
            top: 77.58%;
            right: 85.7%;
            position: absolute;
            z-index: 1;
        }

        .filler {
            width: 6.82%;
            height: 8.81%;
            top: 77.6%;
            right: 10.5%;
            position: absolute;
            z-index: 1;
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

        .cmbt-room {
            background-color: #0dcaf0;
            color: #111;
        }

        .cmbt-office {
            background-color: #ffc107;
            color: #111;
        }

        .cmbt-stairs {
            background-color: #b28859 !important;
            background-image: repeating-linear-gradient(0deg, rgba(0, 0, 0, .25) 0 2px, transparent 2px 6px);
            border: 1px solid rgba(0, 0, 0, .2);
            z-index: 2;
        }

        .cmbt-room,
        .cmbt-office,
        .cmbt-stairs {
            border: 1px solid rgba(0, 0, 0, .25) !important;
            border-radius: 0 !important;
            box-sizing: border-box;
            position: absolute;
        }

        .map-lines-overlay,
        #nav-overlay {
            position: absolute;
            inset: 0;
            width: 100%;
            height: 100%;
        }

        .map-lines-overlay {
            pointer-events: none;
            z-index: 1;
        }

        #nav-overlay {
            z-index: 2;
        }

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

        .edge {
            stroke: rgba(40, 100, 230, .0);
            stroke-width: 8;
            stroke-linecap: round;
            fill: none;
        }

        .edge.active {
            stroke: #00d25a;
            stroke-width: 10;
        }

        .waypoint {
            position: relative;
            display: inline !important;
        }

        .waypoint.room circle {
            fill: #2d6cdf;
            stroke: white;
            stroke-width: 2;
            cursor: pointer;
        }

        .waypoint text {
            fill: #111;
            font-size: 10px;
            font-weight: bold;
            text-anchor: middle;
            dominant-baseline: middle;
            pointer-events: none;
        }

        .stairs-cmbt22 {
            width: 4%;
            height: 6.09%;
            top: 80.3%;
            right: 88.6%;
        }

        .rm301 {
            width: 8.29%;
            height: 8.81%;
            top: 77.58%;
            right: 77.49%;
        }

        .rm303 {
            width: 8.29%;
            height: 8.81%;
            top: 77.58%;
            right: 69.2%;
        }

        .rm302 {
            width: 8.29%;
            height: 8.81%;
            top: 77.58%;
            right: 60.91%;
        }

        .library {
            width: 44.41%;
            height: 8.81%;
            top: 77.58%;
            right: 17%;
        }

        .stairs-cmbt21 {
            width: 4%;
            height: 6.09%;
            top: 80.3%;
            right: 10.5%;
        }
    </style>
</head>

<body>
    <div class="map-wrapper">
        <div class="border position-relative fixed-canvas shadow-sm">
            <svg class="map-lines-overlay" viewBox="0 0 100 100" preserveAspectRatio="none">
                <path class="outer-wall"
                    d="M 2.8, 97.5 L 2.8, 2.5 L 95.5, 2.5 L 95.5, 18 L 90.5, 18 M 90.5, 26 L 95.5, 26 M 95.5, 25.8 L 95.5, 97.5 L 48, 97.5 L 48, 94.5 M 45, 97.5 L 2.8, 97.5" />
                <line class="inner-wall" x1="7.4" y1="91.3" x2="89.5" y2="91.3" />
                <line class="inner-wall" x1="7.5" y1="86.5" x2="7.5" y2="91.3" />
                <line class="inner-wall" x1="89.4" y1="86.5" x2="89.4" y2="91.3" />
            </svg>

            <div
                class="stairs-cmbt22 cmbt-stairs position-absolute text-dark p-1 d-flex flex-column align-items-center justify-content-center">
            </div>
            <div
                class="rm301 cmbt-room position-absolute text-dark p-1 d-flex flex-column align-items-center justify-content-center">
                <span class="fw-bold text-center small">Room 301</span>
            </div>
            <div
                class="rm303 cmbt-room position-absolute text-dark p-1 d-flex flex-column align-items-center justify-content-center">
                <span class="fw-bold text-center small">Room 303</span>
            </div>
            <div
                class="rm302 cmbt-room position-absolute text-dark p-1 d-flex flex-column align-items-center justify-content-center">
                <span class="fw-bold text-center small">Room 302</span>
            </div>
            <div
                class="library cmbt-office position-absolute text-dark p-1 d-flex flex-column align-items-center justify-content-center">
                <span class="fw-bold text-center small">Library</span>
            </div>
            <div
                class="filler2 position-absolute bg-secondary p-1 d-flex flex-column align-items-center justify-content-center bordering">
            </div>
            <div
                class="stairs-cmbt21 cmbt-stairs position-absolute text-dark p-1 d-flex flex-column align-items-center justify-content-center">
            </div>
            <div class="filler building-cmbt level-2-room position-absolute bg-secondary bordering">
            </div>
            <svg id="nav-overlay" viewBox="0 0 1081 1135" preserveAspectRatio="none">
                <g id="edge-layer"></g>
                <g class="waypoint room" id="wp-room301">
                    <circle cx="200" cy="1000" r="12" />
                    <text style="font-size:10px;" x="200" y="1000">301</text>
                </g>
                <g class="waypoint room" id="wp-room302">
                    <circle cx="380" cy="1000" r="12" />
                    <text x="380" y="1000">302</text>
                </g>
                <g class="waypoint room" id="wp-room303">
                    <circle cx="290" cy="1000" r="12" />
                    <text style="font-size:10px;" x="290" y="1000">303</text>
                </g>
                <g class="waypoint room" id="wp-library">
                    <circle cx="655" cy="1000" r="12" />
                    <text x="655" y="1000">LIB</text>
                </g>

                <g class="waypoint junction" id="wp-3stair-right-cmbt">
                    <circle cx="935" cy="1000" r="4" />
                </g>
                <g class="waypoint junction" id="wp-3j-cmbt-stair">
                    <circle cx="115" cy="1000" r="4" />
                </g>
            </svg>
        </div>
    </div>
    <script>
        const NODES = {
            'wp-room302': { x: 380, y: 1000, label: 'Room 302' },
            'wp-room303':  { x: 290, y: 1000, label: 'Room 303' },
            'wp-room301': { x: 200, y: 1000, label: 'Room 301' },
            'wp-library': { x: 733, y: 1000, label: 'Library' },
            'wp-3stair-right-cmbt': { x: 935, y: 1000 },
            'wp-3j-cmbt-stair': { x: 115, y: 1000 },
        };

        const EDGES = [
            ['wp-3j-cmbt-stair', 'wp-room301'],
            ['wp-room301', 'wp-room303'],
            ['wp-room303', 'wp-room302'],
            ['wp-room302','wp-library'],
            ['wp-library', 'wp-3stair-right-cmbt'],


            // CONNECTION TO 2ND floor
            ['wp-3j-cmbt-stair', 'wp-2j-cmbt-stair'],
            ['wp-3stair-right-cmbt', 'wp-2stair-right-cmbt'],
        ];

        function renderEdges() {
            const layer = document.getElementById('edge-layer');
            layer.innerHTML = '';

            EDGES.forEach(([a, b]) => {
                if (!NODES[a] || !NODES[b]) return;

                const line = document.createElementNS('http://www.w3.org/2000/svg', 'line');
                line.setAttribute('x1', NODES[a].x);
                line.setAttribute('y1', NODES[a].y);
                line.setAttribute('x2', NODES[b].x);
                line.setAttribute('y2', NODES[b].y);
                line.setAttribute('id', `edge-${a.replace('wp-', '')}-${b.replace('wp-', '')}`);
                line.setAttribute('class', 'edge');
                layer.appendChild(line);
            });
        }

        renderEdges();
    </script>
</body>

</html>