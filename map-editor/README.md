# Campus Map Editor

This folder contains a lightweight, fully customizable campus map layout editor.

## Included built-in functions

- Add room nodes
- Add junction nodes
- Connect nodes with edges
- Drag nodes to reposition them
- Edit node label, floor, group, and type
- Duplicate selected nodes
- Delete nodes and routes
- Export map as JSON/JS snippet
- Import saved map data
- Save to browser localStorage
- Load a demo campus layout
- Zoom in/out and center the canvas

## Usage

1. Open `map-editor/index.html` in the browser.
2. Choose a mode from the left sidebar.
3. Click on the canvas to create or select nodes.
4. Use the properties panel to customize each node.
5. Export your layout for use in the main navigation map.

## Output format

The editor exports a portable JSON layout object and keeps the same node/edge structure used by the campus navigation graph:

```json
{
  "nodes": {
    "wp-main-entrance": {
      "id": "wp-main-entrance",
      "label": "Main Entrance",
      "x": 220,
      "y": 870,
      "floor": 1,
      "type": "facility",
      "group": "main"
    }
  },
  "edges": [
    ["wp-main-entrance", "wp-admin-office"]
  ]
}
```
