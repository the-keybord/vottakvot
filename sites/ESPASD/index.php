<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no, maximum-scale=1.0" />
  <title>ESP32 BLE Keypad Controller</title>
  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
  <link href="https://fonts.googleapis.com/css2?family=JetBrains+Mono:wght@400;600;700&family=Outfit:wght@400;600;700;800&display=swap" rel="stylesheet" />
  
  <style>
    :root {
      --bg-color: #0b0f19;
      --card-bg: rgba(18, 24, 38, 0.85);
      --card-border: rgba(255, 255, 255, 0.08);
      --accent-cyan: #00f0ff;
      --accent-cyan-glow: rgba(0, 240, 255, 0.35);
      --accent-purple: #a855f7;
      --accent-emerald: #10b981;
      --accent-red: #ef4444;
      --key-base: #1a2234;
      --key-border: #2e3a54;
      --key-shadow: #0e1320;
      --key-text: #e2e8f0;
      --key-active-bg: #00f0ff;
      --key-active-text: #050b14;
      --key-active-shadow: #008899;
    }

    * {
      box-sizing: border-box;
      margin: 0;
      padding: 0;
      -webkit-tap-highlight-color: transparent;
      user-select: none;
    }

    body {
      background-color: var(--bg-color);
      background-image: 
        radial-gradient(ellipse at 50% -20%, rgba(0, 240, 255, 0.12), transparent 70%),
        radial-gradient(ellipse at 80% 120%, rgba(168, 85, 247, 0.1), transparent 60%);
      color: #f1f5f9;
      font-family: 'Outfit', sans-serif;
      min-height: 100vh;
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: space-between;
      padding: 1.25rem 1rem;
      overflow-x: hidden;
    }

    /* Container */
    .app-container {
      width: 100%;
      max-width: 520px;
      display: flex;
      flex-direction: column;
      align-items: center;
      gap: 1.25rem;
    }

    /* Header */
    header {
      width: 100%;
      display: flex;
      justify-content: space-between;
      align-items: center;
      padding: 0.5rem 0.25rem;
    }

    .brand-title {
      display: flex;
      flex-direction: column;
      gap: 2px;
    }

    .brand-title h1 {
      font-size: 1.35rem;
      font-weight: 800;
      letter-spacing: 0.05em;
      background: linear-gradient(135deg, #fff 30%, var(--accent-cyan) 100%);
      -webkit-background-clip: text;
      -webkit-text-fill-color: transparent;
    }

    .brand-title span {
      font-size: 0.72rem;
      font-weight: 600;
      letter-spacing: 0.12em;
      color: #64748b;
      text-transform: uppercase;
      font-family: 'JetBrains Mono', monospace;
    }

    /* BLE Connect Button & Status Badge */
    .connection-controls {
      display: flex;
      align-items: center;
      gap: 0.75rem;
    }

    .btn-connect {
      background: linear-gradient(135deg, rgba(0, 240, 255, 0.15), rgba(168, 85, 247, 0.15));
      border: 1px solid rgba(0, 240, 255, 0.4);
      color: #ffffff;
      padding: 0.55rem 1.1rem;
      border-radius: 9999px;
      font-family: 'Outfit', sans-serif;
      font-size: 0.85rem;
      font-weight: 700;
      letter-spacing: 0.04em;
      cursor: pointer;
      display: flex;
      align-items: center;
      gap: 0.45rem;
      transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
      box-shadow: 0 0 16px rgba(0, 240, 255, 0.15);
    }

    .btn-connect:hover {
      background: linear-gradient(135deg, rgba(0, 240, 255, 0.28), rgba(168, 85, 247, 0.28));
      border-color: var(--accent-cyan);
      box-shadow: 0 0 22px rgba(0, 240, 255, 0.35);
      transform: translateY(-1px);
    }

    .btn-connect.connected {
      background: rgba(239, 68, 68, 0.12);
      border-color: rgba(239, 68, 68, 0.45);
      color: #fca5a5;
      box-shadow: 0 0 16px rgba(239, 68, 68, 0.15);
    }

    .btn-connect.connected:hover {
      background: rgba(239, 68, 68, 0.22);
      border-color: var(--accent-red);
    }

    .status-dot {
      width: 8px;
      height: 8px;
      border-radius: 50%;
      background-color: #64748b;
      transition: all 0.3s ease;
    }

    .status-dot.active {
      background-color: var(--accent-emerald);
      box-shadow: 0 0 10px var(--accent-emerald);
    }

    .status-dot.connecting {
      background-color: #f59e0b;
      box-shadow: 0 0 10px #f59e0b;
      animation: pulse 1s infinite;
    }

    @keyframes pulse {
      0%, 100% { opacity: 1; transform: scale(1); }
      50% { opacity: 0.5; transform: scale(1.3); }
    }

    /* Notice Banner for HTTP or unsupported browsers */
    .notice-banner {
      width: 100%;
      padding: 0.65rem 0.9rem;
      border-radius: 10px;
      font-size: 0.78rem;
      line-height: 1.4;
      display: none;
      background: rgba(245, 158, 11, 0.12);
      border: 1px solid rgba(245, 158, 11, 0.35);
      color: #fde68a;
    }

    /* Keypad Card */
    .keypad-container {
      width: 100%;
      background: var(--card-bg);
      border: 1px solid var(--card-border);
      border-radius: 20px;
      padding: 1.25rem;
      box-shadow: 0 20px 40px rgba(0, 0, 0, 0.5), inset 0 1px 0 rgba(255, 255, 255, 0.05);
      backdrop-filter: blur(12px);
      -webkit-backdrop-filter: blur(12px);
    }

    .grid-4x4 {
      display: grid;
      grid-template-columns: repeat(4, 1fr);
      gap: 0.75rem;
      touch-action: none; /* Prevents scroll/zoom interference on touch */
    }

    /* Mechanical Keyboard Style Key */
    .key-btn {
      aspect-ratio: 1 / 1;
      position: relative;
      background: var(--key-base);
      border: 1px solid var(--key-border);
      border-radius: 14px;
      color: var(--key-text);
      font-family: 'JetBrains Mono', monospace;
      font-size: 1.45rem;
      font-weight: 700;
      cursor: pointer;
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
      transition: transform 0.06s ease, background-color 0.06s ease, box-shadow 0.06s ease, color 0.06s ease;
      box-shadow: 0 6px 0 var(--key-shadow), 0 8px 14px rgba(0, 0, 0, 0.45);
      touch-action: none;
    }

    /* Subtle row accents for WASD distinction */
    .key-btn[data-key="W"],
    .key-btn[data-key="A"],
    .key-btn[data-key="S"],
    .key-btn[data-key="D"] {
      border-color: rgba(0, 240, 255, 0.35);
      color: #ffffff;
    }

    .key-btn .key-sub {
      position: absolute;
      bottom: 5px;
      font-size: 0.55rem;
      color: #64748b;
      font-family: 'Outfit', sans-serif;
      text-transform: uppercase;
      letter-spacing: 0.05em;
    }

    /* Pressed / Active State (Mouse down, Touch active, or Keyboard down) */
    .key-btn.active,
    .key-btn:active {
      transform: translateY(5px);
      background: var(--key-active-bg);
      color: var(--key-active-text);
      border-color: #ffffff;
      box-shadow: 0 1px 0 var(--key-active-shadow), 0 0 24px var(--accent-cyan-glow);
    }

    .key-btn.active .key-sub,
    .key-btn:active .key-sub {
      color: rgba(5, 11, 20, 0.75);
    }

    /* Live Activity & Log Section */
    .telemetry-card {
      width: 100%;
      background: rgba(14, 19, 30, 0.85);
      border: 1px solid rgba(255, 255, 255, 0.05);
      border-radius: 14px;
      padding: 0.85rem 1rem;
      display: flex;
      flex-direction: column;
      gap: 0.45rem;
      font-family: 'JetBrains Mono', monospace;
      font-size: 0.78rem;
    }

    .telemetry-header {
      display: flex;
      justify-content: space-between;
      color: #64748b;
      font-size: 0.7rem;
      text-transform: uppercase;
      letter-spacing: 0.08em;
    }

    .telemetry-feed {
      display: flex;
      justify-content: space-between;
      align-items: center;
      min-height: 22px;
    }

    .log-last-action {
      color: var(--accent-cyan);
      font-weight: 600;
    }

    .log-esp-reply {
      color: var(--accent-purple);
      font-size: 0.73rem;
    }

    /* Footer info */
    footer {
      font-size: 0.72rem;
      color: #475569;
      text-align: center;
      letter-spacing: 0.02em;
      margin-top: 0.75rem;
    }

    footer code {
      color: #94a3b8;
      background: rgba(255, 255, 255, 0.05);
      padding: 2px 5px;
      border-radius: 4px;
      font-family: 'JetBrains Mono', monospace;
    }
  </style>
</head>
<body>

  <div class="app-container">
    <!-- Header -->
    <header>
      <div class="brand-title">
        <h1>ESP32 WASD PAD</h1>
        <span>BLE 4x4 Controller</span>
      </div>

      <div class="connection-controls">
        <div class="status-dot" id="statusDot" title="Connection status"></div>
        <button id="connectBtn" class="btn-connect">
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
            <polyline points="6.5 6.5 17.5 17.5 12 23 12 1 17.5 6.5 6.5 17.5"></polyline>
          </svg>
          <span id="connectBtnText">Connect</span>
        </button>
      </div>
    </header>

    <!-- HTTPS & Web Bluetooth Warning Banner -->
    <div id="noticeBanner" class="notice-banner"></div>

    <!-- 4x4 Keypad -->
    <main class="keypad-container">
      <div class="grid-4x4" id="keypadGrid">
        <!-- Row 1: 1, 2, 3, 4 -->
        <button class="key-btn" data-key="1">1<span class="key-sub">Slot 1</span></button>
        <button class="key-btn" data-key="2">2<span class="key-sub">Slot 2</span></button>
        <button class="key-btn" data-key="3">3<span class="key-sub">Slot 3</span></button>
        <button class="key-btn" data-key="4">4<span class="key-sub">Slot 4</span></button>

        <!-- Row 2: Q, W, E, R -->
        <button class="key-btn" data-key="Q">Q<span class="key-sub">Skill</span></button>
        <button class="key-btn" data-key="W">W<span class="key-sub">Forward</span></button>
        <button class="key-btn" data-key="E">E<span class="key-sub">Use</span></button>
        <button class="key-btn" data-key="R">R<span class="key-sub">Reload</span></button>

        <!-- Row 3: A, S, D, F -->
        <button class="key-btn" data-key="A">A<span class="key-sub">Left</span></button>
        <button class="key-btn" data-key="S">S<span class="key-sub">Back</span></button>
        <button class="key-btn" data-key="D">D<span class="key-sub">Right</span></button>
        <button class="key-btn" data-key="F">F<span class="key-sub">Flash</span></button>

        <!-- Row 4: Z, X, C, V -->
        <button class="key-btn" data-key="Z">Z<span class="key-sub">Crouch</span></button>
        <button class="key-btn" data-key="X">X<span class="key-sub">Prone</span></button>
        <button class="key-btn" data-key="C">C<span class="key-sub">Chat</span></button>
        <button class="key-btn" data-key="V">V<span class="key-sub">Voice</span></button>
      </div>
    </main>

    <!-- Real-time Packet & ACK Feed -->
    <section class="telemetry-card">
      <div class="telemetry-header">
        <span>Sent to ESP32</span>
        <span>ESP32 Response</span>
      </div>
      <div class="telemetry-feed">
        <span class="log-last-action" id="lastTx">IDLE (Press a key)</span>
        <span class="log-esp-reply" id="lastRx">No response yet</span>
      </div>
    </section>

    <footer>
      Supports Touch, Mouse, & Physical Keyboard (1-4, Q-R, A-F, Z-V)<br/>
      Sends <code>+&lt;KEY&gt;</code> on tap down &amp; <code>-&lt;KEY&gt;</code> on release.
    </footer>
  </div>

  <script>
    // --- Nordic UART Service (NUS) UUIDs matching ESP32 sketch ---
    const NUS_SERVICE_UUID = '6e400001-b5a3-f393-e0a9-e50e24dcca9e';
    const NUS_RX_CHAR_UUID = '6e400002-b5a3-f393-e0a9-e50e24dcca9e'; // Web writes to ESP
    const NUS_TX_CHAR_UUID = '6e400003-b5a3-f393-e0a9-e50e24dcca9e'; // ESP notifies Web

    let bleDevice = null;
    let rxCharacteristic = null;
    let txCharacteristic = null;
    let isConnected = false;

    // Track active keys to prevent duplicate event dispatches
    const activeKeys = new Set();
    // Map touch identifiers to key elements for smooth multi-touch tracking
    const ongoingTouches = new Map();

    // DOM Elements
    const connectBtn = document.getElementById('connectBtn');
    const connectBtnText = document.getElementById('connectBtnText');
    const statusDot = document.getElementById('statusDot');
    const noticeBanner = document.getElementById('noticeBanner');
    const lastTxEl = document.getElementById('lastTx');
    const lastRxEl = document.getElementById('lastRx');
    const keypadButtons = document.querySelectorAll('.key-btn');

    // Check Web Bluetooth API compatibility and HTTPS context
    function checkPrerequisites() {
      if (!navigator.bluetooth) {
        noticeBanner.style.display = 'block';
        noticeBanner.innerHTML = '⚠️ <strong>Web Bluetooth not supported</strong> in this browser. Please use Chrome, Edge, or an Android/Desktop Chromium browser.';
        connectBtn.disabled = true;
        connectBtn.style.opacity = '0.5';
        return false;
      }

      if (window.location.protocol !== 'https:' && window.location.hostname !== 'localhost' && window.location.hostname !== '127.0.0.1') {
        noticeBanner.style.display = 'block';
        noticeBanner.innerHTML = '⚠️ <strong>HTTPS Required:</strong> Web Bluetooth requires a secure HTTPS host or localhost to access Bluetooth devices.';
      }
      return true;
    }

    // Connect / Disconnect Toggle
    async function toggleConnection() {
      if (isConnected) {
        disconnectFromEsp();
      } else {
        await connectToEsp();
      }
    }

    // Connect to ESP32 over BLE
    async function connectToEsp() {
      try {
        setConnectingState();
        
        console.log('[BLE] Requesting Bluetooth Device...');
        bleDevice = await navigator.bluetooth.requestDevice({
          filters: [
            { namePrefix: 'ESP32' },
            { services: [NUS_SERVICE_UUID] }
          ],
          optionalServices: [NUS_SERVICE_UUID]
        });

        bleDevice.addEventListener('gattserverdisconnected', onDisconnected);

        console.log('[BLE] Connecting to GATT Server...');
        const server = await bleDevice.gatt.connect();

        console.log('[BLE] Getting Primary Service...');
        const service = await server.getPrimaryService(NUS_SERVICE_UUID);

        console.log('[BLE] Getting RX Characteristic (Write)...');
        rxCharacteristic = await service.getCharacteristic(NUS_RX_CHAR_UUID);

        console.log('[BLE] Getting TX Characteristic (Notify)...');
        txCharacteristic = await service.getCharacteristic(NUS_TX_CHAR_UUID);

        // Subscribe to notifications from ESP32
        await txCharacteristic.startNotifications();
        txCharacteristic.addEventListener('characteristicvaluechanged', handleEspNotification);

        setConnectedState();
        console.log('[BLE] Connected successfully to', bleDevice.name);
      } catch (error) {
        console.error('[BLE] Connection failed:', error);
        setDisconnectedState();
        if (error.name !== 'NotFoundError') { // Don't alert if user merely cancelled device picker
          alert('Failed to connect to ESP32: ' + error.message);
        }
      }
    }

    // Disconnect from ESP32
    function disconnectFromEsp() {
      if (bleDevice && bleDevice.gatt.connected) {
        bleDevice.gatt.disconnect();
      }
      setDisconnectedState();
    }

    function onDisconnected() {
      console.log('[BLE] Device disconnected');
      setDisconnectedState();
    }

    // Handle incoming messages transmitted by the ESP32
    function handleEspNotification(event) {
      const decoder = new TextDecoder('utf-8');
      const message = decoder.decode(event.target.value);
      console.log('[BLE RX from ESP32]:', message);
      lastRxEl.textContent = message;
    }

    // Send data to ESP32 via BLE
    async function sendToEsp(message) {
      lastTxEl.textContent = message;
      if (!rxCharacteristic || !isConnected) {
        return;
      }
      try {
        const encoder = new TextEncoder();
        const data = encoder.encode(message);
        // Write without response for minimal latency
        if (rxCharacteristic.properties.writeWithoutResponse) {
          await rxCharacteristic.writeValueWithoutResponse(data);
        } else {
          await rxCharacteristic.writeValue(data);
        }
      } catch (err) {
        console.error('[BLE TX Error]:', err);
      }
    }

    // Key event handlers: Press Down & Release Up
    function handleKeyDown(keyChar, buttonElement) {
      if (activeKeys.has(keyChar)) return; // Avoid duplicate triggers
      activeKeys.add(keyChar);

      if (buttonElement) {
        buttonElement.classList.add('active');
      }

      // Send "+<KEY>" to ESP32
      const packet = `+${keyChar}`;
      console.log('[Action] Down:', packet);
      sendToEsp(packet);
    }

    function handleKeyUp(keyChar, buttonElement) {
      if (!activeKeys.has(keyChar)) return;
      activeKeys.delete(keyChar);

      if (buttonElement) {
        buttonElement.classList.remove('active');
      }

      // Send "-<KEY>" to ESP32
      const packet = `-${keyChar}`;
      console.log('[Action] Up:', packet);
      sendToEsp(packet);
    }

    // Helper to find button by character
    function getButtonByKey(keyChar) {
      return document.querySelector(`.key-btn[data-key="${keyChar.toUpperCase()}"]`);
    }

    // Attach Multi-Touch & Pointer Event Listeners
    keypadButtons.forEach(button => {
      const keyChar = button.getAttribute('data-key');

      // Mouse events
      button.addEventListener('mousedown', (e) => {
        e.preventDefault();
        handleKeyDown(keyChar, button);
      });

      button.addEventListener('mouseup', (e) => {
        e.preventDefault();
        handleKeyUp(keyChar, button);
      });

      button.addEventListener('mouseleave', (e) => {
        e.preventDefault();
        if (activeKeys.has(keyChar)) {
          handleKeyUp(keyChar, button);
        }
      });

      // Touch events (multi-touch support)
      button.addEventListener('touchstart', (e) => {
        e.preventDefault();
        for (let i = 0; i < e.changedTouches.length; i++) {
          const touch = e.changedTouches[i];
          ongoingTouches.set(touch.identifier, { keyChar, button });
          handleKeyDown(keyChar, button);
        }
      }, { passive: false });

      button.addEventListener('touchend', (e) => {
        e.preventDefault();
        for (let i = 0; i < e.changedTouches.length; i++) {
          const touch = e.changedTouches[i];
          const touchData = ongoingTouches.get(touch.identifier);
          if (touchData) {
            handleKeyUp(touchData.keyChar, touchData.button);
            ongoingTouches.delete(touch.identifier);
          }
        }
      }, { passive: false });

      button.addEventListener('touchcancel', (e) => {
        e.preventDefault();
        for (let i = 0; i < e.changedTouches.length; i++) {
          const touch = e.changedTouches[i];
          const touchData = ongoingTouches.get(touch.identifier);
          if (touchData) {
            handleKeyUp(touchData.keyChar, touchData.button);
            ongoingTouches.delete(touch.identifier);
          }
        }
      }, { passive: false });
    });

    // Global Physical Keyboard Support (1-4, Q-R, A-F, Z-V)
    const validKeys = new Set([
      '1', '2', '3', '4',
      'Q', 'W', 'E', 'R',
      'A', 'S', 'D', 'F',
      'Z', 'X', 'C', 'V'
    ]);

    window.addEventListener('keydown', (e) => {
      if (e.repeat) return;
      const upperKey = e.key.toUpperCase();
      if (validKeys.has(upperKey)) {
        const btn = getButtonByKey(upperKey);
        handleKeyDown(upperKey, btn);
      }
    });

    window.addEventListener('keyup', (e) => {
      const upperKey = e.key.toUpperCase();
      if (validKeys.has(upperKey)) {
        const btn = getButtonByKey(upperKey);
        handleKeyUp(upperKey, btn);
      }
    });

    // UI State helpers
    function setConnectingState() {
      statusDot.className = 'status-dot connecting';
      connectBtnText.textContent = 'Connecting...';
    }

    function setConnectedState() {
      isConnected = true;
      statusDot.className = 'status-dot active';
      connectBtn.classList.add('connected');
      connectBtnText.textContent = 'Disconnect';
      lastRxEl.textContent = 'Connected to ESP32';
    }

    function setDisconnectedState() {
      isConnected = false;
      rxCharacteristic = null;
      txCharacteristic = null;
      statusDot.className = 'status-dot';
      connectBtn.classList.remove('connected');
      connectBtnText.textContent = 'Connect';
      lastRxEl.textContent = 'Disconnected';
      
      // Release any stuck keys
      activeKeys.forEach(k => {
        const btn = getButtonByKey(k);
        if (btn) btn.classList.remove('active');
      });
      activeKeys.clear();
      ongoingTouches.clear();
    }

    // Attach connect button click
    connectBtn.addEventListener('click', toggleConnection);

    // Initial check
    checkPrerequisites();
  </script>
</body>
</html>