import React, { useState, useMemo } from 'react';

// 1. Define Nodes: Rooms + Hallway Junctions (estimations based on your image)
const NODES = {
  // Rooms
  'wp-gym': { x: 440, y: 400, label: 'Gym', isRoom: true },
  'wp-clinic': { x: 730, y: 100, label: 'Clinic', isRoom: true },
  'wp-cict': { x: 840, y: 100, label: 'CICT Faculty', isRoom: true },
  'wp-registrar': { x: 500, y: 860, label: 'Office of Registrar', isRoom: true },
  'wp-lab': { x: 270, y: 710, label: 'Laboratory', isRoom: true },
  'wp-osoad': { x: 680, y: 860, label: 'OSOAD', isRoom: true },
  
  // Junctions (The "invisible" path through the hallways)
  'j-top-right': { x: 730, y: 220, label: 'J1', isRoom: false },
  'j-bottom-right': { x: 860, y: 810, label: 'J2', isRoom: false },
  'j-bottom-mid': { x: 500, y: 810, label: 'J3', isRoom: false },
  'j-gym-left': { x: 270, y: 400, label: 'J4', isRoom: false },
};

// 2. Define Edges (Which nodes are connected?)
const EDGES = [
  ['wp-clinic', 'j-top-right'],
  ['wp-cict', 'j-top-right'],
  ['j-top-right', 'j-bottom-right'],
  ['j-bottom-right', 'j-bottom-mid'],
  ['j-bottom-mid', 'wp-registrar'],
  ['j-bottom-mid', 'wp-osoad'],
  ['j-gym-left', 'wp-lab'],
  ['j-gym-left', 'wp-gym'],
];

const MapNavigation = () => {
  const [startNode, setStartNode] = useState(null);
  const [endNode, setEndNode] = useState(null);

  // 3. Dijkstra Algorithm Logic
  const findPath = (start, end) => {
    if (!start || !end) return [];
    
    const distances = {};
    const previous = {};
    const queue = new Set(Object.keys(NODES));

    Object.keys(NODES).forEach(node => {
      distances[node] = Infinity;
      previous[node] = null;
    });
    distances[start] = 0;

    while (queue.size > 0) {
      let shortestNode = null;
      queue.forEach(node => {
        if (shortestNode === null || distances[node] < distances[shortestNode]) {
          shortestNode = node;
        }
      });

      if (distances[shortestNode] === Infinity || shortestNode === end) break;
      queue.delete(shortestNode);

      // Check neighbors
      EDGES.forEach(([u, v]) => {
        if (u !== shortestNode && v !== shortestNode) return;
        const neighbor = u === shortestNode ? v : u;
        if (!queue.has(neighbor)) return;

        const weight = Math.hypot(NODES[u].x - NODES[v].x, NODES[u].y - NODES[v].y);
        const alt = distances[shortestNode] + weight;
        
        if (alt < distances[neighbor]) {
          distances[neighbor] = alt;
          previous[neighbor] = shortestNode;
        }
      });
    }

    const path = [];
    let current = end;
    while (current) {
      path.unshift(current);
      current = previous[current];
    }
    return path[0] === start ? path : [];
  };

  const path = useMemo(() => findPath(startNode, endNode), [startNode, endNode]);

  const handleRoomClick = (id) => {
    if (!NODES[id].isRoom) return; // Only allow clicking rooms, not junctions
    if (!startNode || (startNode && endNode)) {
      setStartNode(id);
      setEndNode(null);
    } else {
      setEndNode(id);
    }
  };

  return (
    <div className="flex flex-col items-center p-6 bg-slate-50 min-h-screen font-sans">
      <div className="mb-6 text-center">
        <h1 className="text-3xl font-extrabold text-slate-800">Campus Wayfinder</h1>
        <p className="text-slate-500">Select two rooms to see the shortest path</p>
      </div>

      <div className="relative border-8 border-slate-800 rounded-xl shadow-2xl overflow-hidden bg-white"
           style={{ width: '900px', height: '940px' }}>
        
        <img 
          src="/image_3ef1b5.png" 
          alt="Floor Plan" 
          className="absolute inset-0 w-full h-full object-fill opacity-90"
        />

        <svg viewBox="0 0 900 940" className="absolute inset-0 w-full h-full pointer-events-none">
          {/* Render Path Lines */}
          {path.length > 1 && (
            <polyline
              points={path.map(id => `${NODES[id].x},${NODES[id].y}`).join(' ')}
              fill="none"
              stroke="#10b981"
              strokeWidth="8"
              strokeLinecap="round"
              strokeLinejoin="round"
              className="drop-shadow-lg"
            />
          )}

          {/* Render Nodes */}
          {Object.entries(NODES).map(([id, node]) => (
            node.isRoom && (
              <g key={id} className="pointer-events-auto cursor-pointer" onClick={() => handleRoomClick(id)}>
                <circle 
                  cx={node.x} 
                  cy={node.y} 
                  r="14" 
                  fill={startNode === id ? '#10b981' : endNode === id ? '#ef4444' : '#3b82f6'}
                  className="transition-all duration-300 hover:r-18 stroke-white stroke-2"
                />
                <text x={node.x} y={node.y - 25} textAnchor="middle" className="text-[14px] font-bold fill-slate-800 bg-white">
                  {node.label}
                </text>
              </g>
            )
          ))}
        </svg>
      </div>

      {/* Control Panel */}
      <div className="mt-8 grid grid-cols-3 gap-4 w-[900px]">
        <div className="p-4 bg-white rounded-lg shadow border-l-4 border-emerald-500">
          <span className="text-xs uppercase text-slate-400 font-bold">From</span>
          <p className="text-lg font-semibold">{startNode ? NODES[startNode].label : '---'}</p>
        </div>
        <div className="p-4 bg-white rounded-lg shadow border-l-4 border-red-500">
          <span className="text-xs uppercase text-slate-400 font-bold">To</span>
          <p className="text-lg font-semibold">{endNode ? NODES[endNode].label : '---'}</p>
        </div>
        <button 
          onClick={() => {setStartNode(null); setEndNode(null);}}
          className="bg-slate-800 text-white rounded-lg font-bold hover:bg-slate-700 transition"
        >
          Reset Navigation
        </button>
      </div>
    </div>
  );
};

export default MapNavigation;