<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Toastmasters Timer</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;600;800&display=swap');
        
        body {
            font-family: 'Inter', sans-serif;
            /* Smooth background transitions for Toastmasters colors */
            transition: background-color 0.5s ease, color 0.5s ease;
            /* Prevent scrolling on mobile for a native app feel */
            touch-action: manipulation;
        }

        /* Utility classes for active states and contrast */
        .mode-active {
            background-color: #1f2937 !important; /* tailwind gray-800 */
            color: #ffffff !important;
        }
        
        .timer-text {
            font-variant-numeric: tabular-nums;
        }
    </style>
</head>
<body id="app-body" class="bg-white text-gray-900 h-screen w-screen overflow-hidden flex flex-col justify-between">
    
    <!-- Top Navigation: Modes & Reset -->
    <div class="w-full px-2 mt-6 sm:mt-10 max-w-lg mx-auto">
        <div class="grid grid-cols-4 gap-2">
            <button id="btn-speaker" onclick="setMode('speaker')" class="mode-btn mode-active bg-gray-200 hover:bg-gray-300 text-gray-800 font-semibold py-3 px-1 rounded-lg text-xs sm:text-sm shadow-sm transition-colors">
                Speaker
            </button>
            <button id="btn-evaluation" onclick="setMode('evaluation')" class="mode-btn bg-gray-200 hover:bg-gray-300 text-gray-800 font-semibold py-3 px-1 rounded-lg text-xs sm:text-sm shadow-sm transition-colors">
                Evaluation
            </button>
            <button id="btn-table" onclick="setMode('table')" class="mode-btn bg-gray-200 hover:bg-gray-300 text-gray-800 font-semibold py-3 px-1 rounded-lg text-xs sm:text-sm shadow-sm transition-colors">
                Table
            </button>
            <button onclick="resetTimer()" class="bg-red-100 hover:bg-red-200 text-red-700 font-semibold py-3 px-1 rounded-lg text-xs sm:text-sm shadow-sm border border-red-300 transition-colors">
                Reset
            </button>
        </div>
    </div>

    <!-- Center: Stopwatch Display -->
    <div class="flex-1 flex flex-col items-center justify-center">
        <h2 id="mode-label" class="text-xl sm:text-2xl font-semibold opacity-70 mb-2 uppercase tracking-widest">Speaker Mode</h2>
        <div id="time-display" class="timer-text text-8xl sm:text-9xl font-extrabold tracking-tighter">
            00:00
        </div>
    </div>

    <!-- Bottom: Start/Stop Button -->
    <div class="w-full px-4 mb-10 max-w-lg mx-auto">
        <button id="start-stop-btn" onclick="toggleTimer()" class="w-full bg-blue-600 hover:bg-blue-700 active:bg-blue-800 text-white font-bold py-6 rounded-2xl text-3xl shadow-lg transition-transform transform active:scale-95">
            Start
        </button>
    </div>

    <script>
        // --- State Variables ---
        let timeInSeconds = 0;
        let isRunning = false;
        let timerInterval = null;
        let currentMode = 'speaker';

        // --- Toastmasters Timing Rules (in seconds) ---
        const timingRules = {
            speaker: { green: 300, yellow: 360, red: 420, brown: 450, label: "Speaker Mode" },     // 5, 6, 7, 7.5 mins
            evaluation: { green: 120, yellow: 150, red: 180, brown: 210, label: "Evaluation Mode" }, // 2, 2.5, 3, 3.5 mins
            table: { green: 60, yellow: 90, red: 120, brown: 150, label: "Table Topics Mode" }       // 1, 1.5, 2, 2.5 mins
        };

        // --- DOM Elements ---
        const bodyEl = document.getElementById('app-body');
        const timeDisplayEl = document.getElementById('time-display');
        const startStopBtn = document.getElementById('start-stop-btn');
        const modeLabelEl = document.getElementById('mode-label');

        // --- Core Functions ---
        
        function formatTime(totalSeconds) {
            const minutes = Math.floor(totalSeconds / 60);
            const seconds = totalSeconds % 60;
            return `${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`;
        }

        function updateDisplay() {
            // Update Text
            timeDisplayEl.textContent = formatTime(timeInSeconds);

            // Determine Colors based on rules
            const rules = timingRules[currentMode];
            let bgColorClass = 'bg-white';
            let textColorClass = 'text-gray-900'; // Default black text on white

            if (timeInSeconds >= rules.brown) {
                bgColorClass = 'bg-[#654321]'; // Dark brown for end of grace period
                textColorClass = 'text-white'; // White text on brown
            } else if (timeInSeconds >= rules.red) {
                bgColorClass = 'bg-red-600';
                textColorClass = 'text-white'; // White text on red
            } else if (timeInSeconds >= rules.yellow) {
                bgColorClass = 'bg-yellow-400';
                textColorClass = 'text-gray-900'; // Black text on yellow for readability
            } else if (timeInSeconds >= rules.green) {
                bgColorClass = 'bg-green-500';
                textColorClass = 'text-white'; // White text on green
            }

            // Apply background color to body
            bodyEl.className = `${bgColorClass} ${textColorClass} h-screen w-screen overflow-hidden flex flex-col justify-between`;
        }

        function toggleTimer() {
            if (isRunning) {
                // Stop Timer
                clearInterval(timerInterval);
                isRunning = false;
                startStopBtn.textContent = "Start";
                startStopBtn.classList.replace('bg-gray-800', 'bg-blue-600');
                startStopBtn.classList.replace('hover:bg-gray-900', 'hover:bg-blue-700');
            } else {
                // Start Timer
                isRunning = true;
                startStopBtn.textContent = "Stop";
                startStopBtn.classList.replace('bg-blue-600', 'bg-gray-800');
                startStopBtn.classList.replace('hover:bg-blue-700', 'hover:bg-gray-900');
                
                timerInterval = setInterval(() => {
                    timeInSeconds++;
                    updateDisplay();
                }, 1000);
            }
        }

        function resetTimer() {
            // Stop the timer if running
            if (isRunning) {
                toggleTimer(); 
            }
            // Reset time and background
            timeInSeconds = 0;
            updateDisplay();
        }

        function setMode(newMode) {
            currentMode = newMode;
            
            // Update Label
            modeLabelEl.textContent = timingRules[currentMode].label;

            // Manage Active Button Styles
            document.querySelectorAll('.mode-btn').forEach(btn => {
                btn.classList.remove('mode-active');
            });
            document.getElementById(`btn-${newMode}`).classList.add('mode-active');

            // Reset the timer automatically when switching modes to prevent confusion
            resetTimer();
        }

        // Initialize Display on load
        updateDisplay();
    </script>
</body>
</html>