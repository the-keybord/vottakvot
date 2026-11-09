<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Alliance Roster</title>
    <style>
        body {
            font-family: 'Inter', 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f7f9fc;
            margin: 0;
            padding: 40px 20px;
            text-align: center;
            color: #333;
        }
        h1 {
            margin-top: 0;
            margin-bottom: 20px;
            font-size: 2.8em;
            color: #2c3e50;
            letter-spacing: 1px;
        }
        .header-actions {
            margin-bottom: 40px;
        }
        button {
            padding: 10px 20px;
            background-color: #3498db;
            color: white;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 1em;
            font-weight: 600;
            transition: background-color 0.2s;
        }
        button:hover {
            background-color: #2980b9;
        }
        .csv-editor-container {
            display: none;
            margin: 0 auto 30px auto;
            max-width: 800px;
            text-align: left;
        }
        .csv-editor-container.active {
            display: block;
        }
        textarea {
            width: 100%;
            height: 150px;
            padding: 10px;
            border-radius: 8px;
            border: 1px solid #ccc;
            font-family: monospace;
            box-sizing: border-box;
            margin-bottom: 10px;
        }
        .grid-container {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(240px, 1fr));
            gap: 25px;
            max-width: 1400px;
            margin: 0 auto;
        }
        .alliance-card {
            position: relative;
            border-radius: 16px;
            padding: 50px 20px;
            color: white;
            font-weight: 600;
            text-shadow: 1px 1px 3px rgba(0,0,0,0.5);
            box-shadow: 0 6px 12px rgba(0,0,0,0.08);
            transition: all 0.3s ease;
            cursor: pointer;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            min-height: 100px;
            overflow: visible;
        }
        .alliance-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 12px 24px rgba(0,0,0,0.15);
        }
        /* Availability Frames */
        .status-frame {
            position: absolute;
            top: 0; left: 0; right: 0; bottom: 0;
            border-radius: 16px;
            pointer-events: none;
            transition: all 0.3s ease;
        }
        .available .status-frame {
            border: 4px dashed rgba(255, 255, 255, 0.8);
        }
        .occupied .status-frame {
            border: 4px solid rgba(0, 0, 0, 0.3);
        }
        /* Top right badge */
        .status-badge {
            position: absolute;
            top: 12px;
            right: 12px;
            background: rgba(255, 255, 255, 0.95);
            color: #333;
            padding: 4px 10px;
            border-radius: 12px;
            font-size: 0.75em;
            text-shadow: none;
            font-weight: 700;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .alliance-name {
            font-size: 1.3em;
            z-index: 1;
            letter-spacing: 0.5px;
        }
        /* Hover Tooltip */
        .tooltip {
            visibility: hidden;
            width: 200px;
            background-color: #2c3e50;
            color: #fff;
            text-align: center;
            border-radius: 10px;
            padding: 12px;
            position: absolute;
            z-index: 10;
            bottom: 105%; 
            left: 50%;
            transform: translateX(-50%);
            opacity: 0;
            transition: opacity 0.3s, bottom 0.3s;
            font-size: 0.9em;
            text-shadow: none;
            font-weight: normal;
            box-shadow: 0 8px 16px rgba(0,0,0,0.2);
            pointer-events: none;
        }
        .tooltip::after {
            content: "";
            position: absolute;
            top: 100%;
            left: 50%;
            transform: translateX(-50%);
            border-width: 6px;
            border-style: solid;
            border-color: #2c3e50 transparent transparent transparent;
        }
        .alliance-card:hover .tooltip {
            visibility: visible;
            opacity: 1;
            bottom: 110%;
        }
        /* Dynamic text contrast for light colors */
        .text-dark {
            color: #2c3e50 !important;
            text-shadow: 1px 1px 2px rgba(255, 255, 255, 0.8) !important;
        }
        
        /* Modal Styles */
        .modal-overlay {
            display: none;
            position: fixed;
            top: 0; left: 0; right: 0; bottom: 0;
            background: rgba(0,0,0,0.6);
            z-index: 1000;
            justify-content: center;
            align-items: center;
        }
        .modal-overlay.active {
            display: flex;
        }
        .modal-content {
            background: white;
            padding: 30px;
            border-radius: 12px;
            width: 90%;
            max-width: 400px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.2);
            text-align: left;
        }
        .modal-content h3 {
            margin-top: 0;
            color: #2c3e50;
        }
        .modal-content input {
            width: 100%;
            padding: 10px;
            margin: 15px 0;
            border: 1px solid #ccc;
            border-radius: 6px;
            box-sizing: border-box;
            font-size: 1em;
            color: #333;
        }
        .modal-actions {
            display: flex;
            justify-content: flex-end;
            gap: 10px;
        }
        .modal-actions button {
            padding: 8px 16px;
        }
        .btn-cancel {
            background-color: #95a5a6;
        }
        .btn-cancel:hover {
            background-color: #7f8c8d;
        }
        .btn-save {
            background-color: #27ae60;
        }
        .btn-save:hover {
            background-color: #2ecc71;
        }
    </style>
</head>
<body>

    <h1>Alliance Roster</h1>
    
    <div class="header-actions">
        <button onclick="toggleEditor()">Toggle Manual CSV Editor</button>
        <button onclick="exportCSV()" style="background-color: #27ae60; margin-left: 10px;">Export CSV Data</button>
    </div>

    <div id="csv-editor" class="csv-editor-container">
        <p style="margin-top:0; font-size:0.9em; color:#666;">Edit the CSV below and click apply, or simply click any card to edit its members.</p>
        <textarea id="csv-data"></textarea>
        <button onclick="applyCSV()">Apply Manual Changes</button>
    </div>

    <div class="grid-container" id="grid">
        <!-- Cards injected via JavaScript -->
    </div>

    <!-- Edit Modal -->
    <div id="edit-modal" class="modal-overlay">
        <div class="modal-content">
            <h3 id="modal-alliance-name">Edit Alliance</h3>
            <p style="color: #666; margin-bottom: 5px; font-size: 0.9em;">Edit members (comma separated):</p>
            <input type="text" id="modal-members-input" placeholder="e.g. John, Sarah">
            <input type="hidden" id="modal-alliance-id">
            <div class="modal-actions">
                <button class="btn-cancel" onclick="closeModal()">Cancel</button>
                <button class="btn-save" onclick="saveModal()">Save</button>
            </div>
        </div>
    </div>

    <script>
        // Default Full Data
        const defaultCSV = `id,name,color,members
01,SkyBlue Alliance,#87CEEB,
02,Scarlet Alliance,#FF2400,
03,Yellow Alliance,#FFD700,
04,Mint Alliance,#3EB489,
05,Cobalt Alliance,#0047AB,
06,Crimson Alliance,#DC143C,
07,Emerald Alliance,#50C878,
08,Amber Alliance,#FFBF00,
09,Cyan Alliance,#00E5FF,
10,Magenta Alliance,#FF00FF,
11,Violet Alliance,#8F00FF,
12,Coral Alliance,#FF7F50,
13,Teal Alliance,#008080,
14,Indigo Alliance,#4B0082,
15,Lime Alliance,#32CD32,
16,Tangerine Alliance,#F28500,
17,Ruby Alliance,#E0115F,
18,Sapphire Alliance,#0F52BA,
19,Jade Alliance,#00A86B,
20,Amethyst Alliance,#9966CC,
21,Turquoise Alliance,#40E0D0,
22,Chartreuse Alliance,#7FFF00,
23,Maroon Alliance,#800000,
24,Navy Alliance,#000080,
25,Olive Alliance,#808000,
26,Peach Alliance,#FFCBA4,
27,Lavender Alliance,#B57EDC,
28,Slate Alliance,#708090,
29,Silver Alliance,#C0C0C0,
30,Obsidian Alliance,#1B1B1B,`;

        let currentData = [];

        // Manual robust parsing logic to prevent regex quote issues
        function parseCSV(text) {
            const lines = text.trim().split('\n');
            const data = [];
            for (let i = 1; i < lines.length; i++) {
                const line = lines[i].trim();
                if (!line) continue;
                
                let idEnd = line.indexOf(',');
                let nameEnd = line.indexOf(',', idEnd + 1);
                let colorEnd = line.indexOf(',', nameEnd + 1);
                
                if (idEnd !== -1 && nameEnd !== -1) {
                    let idVal = line.substring(0, idEnd);
                    let nameVal = line.substring(idEnd + 1, nameEnd);
                    let colorVal = "";
                    let membersVal = "";
                    
                    if (colorEnd === -1) {
                        // Empty members case
                        colorVal = line.substring(nameEnd + 1);
                    } else {
                        colorVal = line.substring(nameEnd + 1, colorEnd);
                        membersVal = line.substring(colorEnd + 1);
                        // Clean quotes
                        if (membersVal.startsWith('"') && membersVal.endsWith('"')) {
                            membersVal = membersVal.substring(1, membersVal.length - 1);
                        }
                    }
                    data.push({ id: idVal, name: nameVal, color: colorVal, members: membersVal });
                }
            }
            return data;
        }

        // Generate the CSV string back from memory
        function generateCSVString() {
            let csv = "id,name,color,members\n";
            currentData.forEach(row => {
                let m = row.members;
                if (m.includes(',')) m = '"' + m + '"';
                csv += `${row.id},${row.name},${row.color},${m}\n`;
            });
            return csv.trim();
        }

        function exportCSV() {
            const csvContent = generateCSVString();
            const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
            const link = document.createElement("a");
            const url = URL.createObjectURL(blob);
            link.setAttribute("href", url);
            link.setAttribute("download", "alliances.csv");
            link.style.visibility = 'hidden';
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
        }

        function renderGrid() {
            const container = document.getElementById('grid');
            container.innerHTML = '';
            
            const lightColors = ['#FFD700', '#FFBF00', '#00E5FF', '#32CD32', '#7FFF00', '#FFCBA4', '#C0C0C0', '#87CEEB', '#40E0D0', '#3EB489'];

            currentData.forEach(a => {
                const isAvailable = !a.members || a.members.trim() === '';
                const statusClass = isAvailable ? 'available' : 'occupied';
                const statusText = isAvailable ? 'Available' : 'Occupied';
                
                let membersText = isAvailable 
                    ? 'No members yet.<br><strong>Ready to claim!</strong>' 
                    : `<strong>Members:</strong><br>${a.members.replace(/,/g, '<br>')}`;
                
                const textClass = lightColors.includes(a.color.toUpperCase()) ? 'text-dark' : '';

                const card = document.createElement('div');
                card.className = `alliance-card ${statusClass} ${textClass}`;
                card.style.backgroundColor = a.color;
                
                // Add click listener
                card.onclick = () => openModal(a.id);
                
                card.innerHTML = `
                    <div class='status-frame'></div>
                    <div class='status-badge'>${statusText}</div>
                    <div class='alliance-name'>${a.name}</div>
                    <div class='tooltip'>${membersText}</div>
                `;
                container.appendChild(card);
            });
            
            // Sync text area
            document.getElementById('csv-data').value = generateCSVString();
        }

        function toggleEditor() {
            const editor = document.getElementById('csv-editor');
            editor.classList.toggle('active');
        }

        function applyCSV() {
            const text = document.getElementById('csv-data').value;
            currentData = parseCSV(text);
            renderGrid();
        }

        // --- Modal Logic ---
        function openModal(id) {
            const item = currentData.find(d => d.id === id);
            if (!item) return;
            
            document.getElementById('modal-alliance-name').innerText = 'Edit ' + item.name;
            document.getElementById('modal-members-input').value = item.members;
            document.getElementById('modal-alliance-id').value = id;
            document.getElementById('edit-modal').classList.add('active');
            document.getElementById('modal-members-input').focus();
        }

        function closeModal() {
            document.getElementById('edit-modal').classList.remove('active');
        }

        function saveModal() {
            const id = document.getElementById('modal-alliance-id').value;
            const newMembers = document.getElementById('modal-members-input').value;
            
            const item = currentData.find(d => d.id === id);
            if (item) {
                item.members = newMembers;
            }
            
            renderGrid(); // This will auto-sync the textarea as well
            closeModal();
        }

        // Initialize on load
        currentData = parseCSV(defaultCSV);
        renderGrid();
    </script>
</body>
</html>