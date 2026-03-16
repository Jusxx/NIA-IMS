<?php
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/auth.php';
require_roles(['Administrator','Operations Staff','IMO']);
require_once __DIR__ . '/../includes/config.php';

$topTitle = 'Add Area';
$active = 'areas';

$error = '';
$area = $mun = $prov = '';
$ha = '';
$lat = '';
$lng = '';

// ✅ Simple PH province -> municipalities dataset (ADD MORE as needed)
$PH = [
  'South Cotabato' => ['Koronadal City', 'Polomolok', 'Tupi', 'Tampakan', 'Surallah', 'Banga', 'Norala', 'Sto. Niño', 'Lake Sebu', 'Tantangan'],
  'Cotabato'       => ['Kidapawan City', 'M\'lang', 'Makilala', 'Tulunan', 'Matalam', 'Kabacan', 'Pikit', 'Carmen'],
  'Sultan Kudarat' => ['Isulan', 'Tacurong City', 'Lutayan', 'Palimbang', 'Bagumbayan', 'Columbio', 'Lambayong'],
  'Sarangani'      => ['Alabel', 'Glan', 'Kiamba', 'Maasim', 'Maitum', 'Malapatan'],
  'Maguindanao del Norte' => ['Datu Odin Sinsuat', 'Parang', 'Barira'],
  'Maguindanao del Sur'   => ['Buluan', 'Datu Paglas', 'Pagalungan']
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $area = trim($_POST['area_name'] ?? '');
  $prov = trim($_POST['province'] ?? '');
  $mun  = trim($_POST['municipality'] ?? '');
  $ha   = trim($_POST['total_area_ha'] ?? '');
  $lat  = trim($_POST['latitude'] ?? '');
  $lng  = trim($_POST['longitude'] ?? '');

  // ✅ validations
  if ($area === '') {
    $error = "Area name is required.";

  } elseif ($prov === '' || !array_key_exists($prov, $PH)) {
    $error = "Please select a valid Province.";

  } elseif ($mun === '' || !in_array($mun, $PH[$prov], true)) {
    $error = "Please select a valid Municipality.";

  } elseif ($ha !== '' && (!is_numeric($ha) || (float)$ha < 0)) {
    $error = "Total Area (ha) must be a valid number.";

  } elseif ($lat === '' || $lng === '' || !is_numeric($lat) || !is_numeric($lng)) {
    $error = "Please pin the location on the map.";

  } else {
    $haVal = ($ha === '') ? null : (float)$ha;
    $latVal = (float)$lat;
    $lngVal = (float)$lng;

    // ✅ Insert (handles NULL ha cleanly)
    if ($haVal === null) {
      $stmt = $conn->prepare("
        INSERT INTO service_areas(area_name, municipality, province, total_area_ha, latitude, longitude)
        VALUES (?,?,?,NULL,?,?)
      ");
      $stmt->bind_param("sssdd", $area, $mun, $prov, $latVal, $lngVal);
    } else {
      $stmt = $conn->prepare("
        INSERT INTO service_areas(area_name, municipality, province, total_area_ha, latitude, longitude)
        VALUES (?,?,?,?,?,?)
      ");
      $stmt->bind_param("sssddd", $area, $mun, $prov, $haVal, $latVal, $lngVal);
    }

    $stmt->execute();
    $stmt->close();

    header("Location: " . route('areas'));
    exit;
  }
}

include __DIR__ . '/../includes/head.php';
?>

<!-- Leaflet CSS -->
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">

<div class="flex min-h-screen w-full">
  <?php include __DIR__ . '/../includes/sidebar.php'; ?>
  <div class="flex-1 flex flex-col">
    <?php include __DIR__ . '/../includes/topbar.php'; ?>

    <main class="p-6 lg:p-8 flex-1">
      <div class="w-full bg-card-light dark:bg-card-dark border border-border-light dark:border-border-dark rounded-lg p-6">
        <div class="flex items-center justify-between gap-3">
          <div>
            <h2 class="text-xl font-black text-text-light dark:text-text-dark">Add Area / Canal</h2>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Create a new service area record with map location.</p>
          </div>
          <a
            class="px-4 py-2 rounded-full bg-gray-200 dark:bg-gray-700 text-text-light dark:text-text-dark inline-flex items-center gap-1.5"
            href="<?= route('areas') ?>"
            title="Back"
            aria-label="Back to areas"
          >
            <span class="material-symbols-outlined text-[18px] leading-none">arrow_back</span>
            <span>Back</span>
          </a>
        </div>

        <?php if ($error): ?>
          <div class="mt-4 p-3 rounded bg-red-100 text-red-700"><?= h($error) ?></div>
        <?php endif; ?>

        <form method="POST" class="mt-6 space-y-6">
          <div class="grid grid-cols-1 xl:grid-cols-3 gap-6">
            <div class="space-y-4 xl:col-span-1">
              <div>
                <label class="block text-sm font-medium text-text-light dark:text-text-dark">Area Name *</label>
                <input name="area_name" value="<?= h($area) ?>" required
                       class="mt-1 w-full rounded-full px-4 py-2 border-border-light dark:border-border-dark bg-background-light dark:bg-background-dark">
              </div>

              <div>
                <label class="block text-sm font-medium text-text-light dark:text-text-dark">Province *</label>
                <select id="province" name="province" required
                        class="mt-1 w-full rounded-full px-4 py-2 border-border-light dark:border-border-dark bg-background-light dark:bg-background-dark">
                  <option value="">Select Province</option>
                  <?php foreach (array_keys($PH) as $p): ?>
                    <option value="<?= h($p) ?>" <?= $prov === $p ? 'selected' : '' ?>><?= h($p) ?></option>
                  <?php endforeach; ?>
                </select>
              </div>

              <div>
                <label class="block text-sm font-medium text-text-light dark:text-text-dark">Municipality *</label>
                <select id="municipality" name="municipality" required
                        class="mt-1 w-full rounded-full px-4 py-2 border-border-light dark:border-border-dark bg-background-light dark:bg-background-dark">
                  <option value="">Select Municipality</option>
                </select>
              </div>

              <div>
                <label class="block text-sm font-medium text-text-light dark:text-text-dark">Total Area (ha)</label>
                <input type="number" step="0.01" name="total_area_ha" value="<?= h($ha) ?>"
                       class="mt-1 w-full rounded-full px-4 py-2 border-border-light dark:border-border-dark bg-background-light dark:bg-background-dark">
              </div>
            </div>

            <div class="xl:col-span-2">
              <label class="block text-sm font-medium text-text-light dark:text-text-dark">Pin Location *</label>
              <div class="mt-2 flex flex-col sm:flex-row gap-2">
                <div class="relative w-full">
                  <span class="material-symbols-outlined pointer-events-none absolute left-2.5 top-1/2 -translate-y-1/2 text-gray-400 text-[18px] leading-none">search</span>
                  <input type="text" id="mapSearch" placeholder="Search landmark or place (example: barangay hall, school)"
                         class="w-full pl-9 pr-4 rounded-full border-border-light dark:border-border-dark bg-background-light dark:bg-background-dark">
                </div>
                <button type="button" id="mapSearchBtn"
                        class="w-10 h-10 rounded-full bg-secondary text-white inline-flex items-center justify-center shrink-0"
                        title="Search location"
                        aria-label="Search location">
                  <span class="material-symbols-outlined text-[20px] leading-none">search</span>
                </button>
              </div>
              <p id="mapSearchStatus" class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                Tip: Search a landmark, then click the map to adjust the exact pin.
              </p>
              <div id="mapSearchResults" class="mt-2 hidden rounded-lg border border-border-light dark:border-border-dark bg-card-light dark:bg-card-dark max-h-52 overflow-auto"></div>
              <div id="map" class="mt-2 w-full rounded-lg border border-border-light dark:border-border-dark" style="height: 430px;"></div>
              <p class="text-xs text-gray-500 dark:text-gray-400 mt-2">Click on the map to drop a marker.</p>

              <input type="hidden" id="latitude" name="latitude" value="<?= h($lat) ?>">
              <input type="hidden" id="longitude" name="longitude" value="<?= h($lng) ?>">

              <div class="mt-2 text-xs text-gray-600 dark:text-gray-300">
                <span>Lat: <span id="latText"><?= h($lat) ?: '-' ?></span></span>
                <span class="ml-4">Lng: <span id="lngText"><?= h($lng) ?: '-' ?></span></span>
              </div>
            </div>
          </div>

          <div class="flex flex-wrap gap-2">
            <button class="px-4 py-2 rounded-full bg-primary text-white font-bold inline-flex items-center gap-1.5">
              <span class="material-symbols-outlined text-[18px] leading-none">save</span>
              <span>Save</span>
            </button>
            <a class="px-4 py-2 rounded-full bg-gray-200 dark:bg-gray-700 text-text-light dark:text-text-dark inline-flex items-center gap-1.5"
               href="<?= route('areas') ?>">
              <span class="material-symbols-outlined text-[18px] leading-none">arrow_back</span>
              <span>Cancel</span>
            </a>
          </div>
        </form>
      </div>
    </main>
  </div>
</div>

<!-- Leaflet JS -->
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

<script>
const PH = <?php echo json_encode($PH, JSON_UNESCAPED_UNICODE); ?>;

const provSelect = document.getElementById('province');
const munSelect  = document.getElementById('municipality');

const savedProv = <?= json_encode($prov) ?>;
const savedMun  = <?= json_encode($mun) ?>;

function loadMunicipalities(prov) {
  munSelect.innerHTML = '<option value="">Select Municipality</option>';
  if (!prov || !PH[prov]) return;

  PH[prov].forEach(m => {
    const opt = document.createElement('option');
    opt.value = m;
    opt.textContent = m;
    if (m === savedMun) opt.selected = true;
    munSelect.appendChild(opt);
  });
}

provSelect.addEventListener('change', () => {
  // reset saved municipality when changing province
  loadMunicipalities(provSelect.value);
});

loadMunicipalities(savedProv || provSelect.value);

// ===== Leaflet Map =====
// SOCCSKSARGEN bounds and center
const SOX_BOUNDS = L.latLngBounds([5.45, 123.50], [7.55, 126.35]);
const SOX_CENTER = [6.35, 124.95];
const SOX_VIEWBOX = '123.50,7.55,126.35,5.45';
const SOX_HINT_RE = /koronadal|general santos|gensan|kidapawan|tacurong|cotabato|south cotabato|sultan kudarat|sarangani|soccsksargen/i;

const latInput = document.getElementById('latitude');
const lngInput = document.getElementById('longitude');
const latText  = document.getElementById('latText');
const lngText  = document.getElementById('lngText');
const mapSearchInput = document.getElementById('mapSearch');
const mapSearchBtn = document.getElementById('mapSearchBtn');
const mapSearchStatus = document.getElementById('mapSearchStatus');
const mapSearchResults = document.getElementById('mapSearchResults');

const initialLat = latInput.value ? parseFloat(latInput.value) : null;
const initialLng = lngInput.value ? parseFloat(lngInput.value) : null;

const savedInSox = initialLat && initialLng && SOX_BOUNDS.contains([initialLat, initialLng]);

const map = L.map('map', {
  maxBounds: SOX_BOUNDS,
  maxBoundsViscosity: 1.0,
  minZoom: 8
}).setView(
  savedInSox ? [initialLat, initialLng] : SOX_CENTER,
  savedInSox ? 14 : 9
);

if (!savedInSox) {
  map.fitBounds(SOX_BOUNDS);
}

L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
  maxZoom: 19
}).addTo(map);

let marker = null;

function setMarker(lat, lng) {
  if (marker) marker.remove();
  marker = L.marker([lat, lng]).addTo(map);

  latInput.value = lat.toFixed(7);
  lngInput.value = lng.toFixed(7);

  latText.textContent = latInput.value;
  lngText.textContent = lngInput.value;
}

if (initialLat && initialLng) {
  setMarker(initialLat, initialLng);
}

map.on('click', (e) => {
  setMarker(e.latlng.lat, e.latlng.lng);
});

async function searchMapLocation() {
  return performMapSearch(true);
}

let mapSearchTimer = null;
let mapSearchAbort = null;
let mapSearchSeq = 0;

function hideMapSearchResults() {
  if (!mapSearchResults) return;
  mapSearchResults.classList.add('hidden');
  mapSearchResults.innerHTML = '';
}

function pickMapSearchResult(item) {
  const lat = parseFloat(item?.lat ?? '');
  const lng = parseFloat(item?.lon ?? '');
  if (!Number.isFinite(lat) || !Number.isFinite(lng)) {
    mapSearchStatus.textContent = 'Location result is invalid.';
    return;
  }

  map.setView([lat, lng], 16);
  setMarker(lat, lng);
  const label = (item.display_name || 'Location found').split(',').slice(0, 3).join(',').trim();
  mapSearchStatus.textContent = `Found: ${label}`;
  hideMapSearchResults();
}

function renderMapSearchResults(items) {
  if (!mapSearchResults) return;
  mapSearchResults.innerHTML = '';

  if (!items.length) {
    hideMapSearchResults();
    return;
  }

  items.forEach((item) => {
    const btn = document.createElement('button');
    btn.type = 'button';
    btn.className = 'w-full text-left px-3 py-2 text-sm hover:bg-gray-100 dark:hover:bg-gray-800 border-b border-border-light dark:border-border-dark last:border-b-0';
    btn.textContent = (item.display_name || '').split(',').slice(0, 4).join(',').trim() || 'Unknown location';
    btn.addEventListener('click', () => pickMapSearchResult(item));
    mapSearchResults.appendChild(btn);
  });

  mapSearchResults.classList.remove('hidden');
}

function isResultInsideSox(item) {
  const lat = parseFloat(item?.lat ?? '');
  const lng = parseFloat(item?.lon ?? '');
  if (!Number.isFinite(lat) || !Number.isFinite(lng)) return false;
  return SOX_BOUNDS.contains([lat, lng]);
}

function buildNominatimUrl(q, bounded) {
  const params = new URLSearchParams({
    format: 'jsonv2',
    limit: '8',
    countrycodes: 'ph',
    viewbox: SOX_VIEWBOX,
    bounded: bounded ? '1' : '0',
    q,
  });
  return `https://nominatim.openstreetmap.org/search?${params.toString()}`;
}

async function fetchNominatimCandidates(query, signal) {
  const base = query.trim();
  const variants = Array.from(new Set([
    base,
    SOX_HINT_RE.test(base) ? base : `${base}, SOCCSKSARGEN, Philippines`,
    /philippines/i.test(base) ? base : `${base}, Philippines`,
  ]));

  // Strict SOX-bounded pass.
  for (const q of variants) {
    try {
      const res = await fetch(buildNominatimUrl(q, true), {
        signal,
        headers: { 'Accept': 'application/json' }
      });
      if (!res.ok) continue;
      const data = await res.json();
      if (Array.isArray(data) && data.length > 0) return data;
    } catch (e) {
      if (e?.name === 'AbortError') throw e;
    }
  }

  // Broader PH pass, then keep only SOX results.
  for (const q of variants) {
    try {
      const res = await fetch(buildNominatimUrl(q, false), {
        signal,
        headers: { 'Accept': 'application/json' }
      });
      if (!res.ok) continue;
      const data = await res.json();
      if (!Array.isArray(data) || data.length === 0) continue;
      const inside = data.filter(isResultInsideSox);
      if (inside.length > 0) return inside;
    } catch (e) {
      if (e?.name === 'AbortError') throw e;
    }
  }

  return [];
}

async function performMapSearch(pickFirst = false) {
  if (!mapSearchInput || !mapSearchBtn || !mapSearchStatus) return;
  const query = mapSearchInput.value.trim();
  if (query.length < 3) {
    mapSearchStatus.textContent = 'Type at least 3 letters to search.';
    hideMapSearchResults();
    return;
  }

  if (mapSearchAbort) mapSearchAbort.abort();
  const controller = new AbortController();
  mapSearchAbort = controller;
  const seq = ++mapSearchSeq;
  const timeoutId = window.setTimeout(() => controller.abort(), 10000);

  mapSearchBtn.disabled = true;
  mapSearchStatus.textContent = 'Searching...';

  try {
    const data = await fetchNominatimCandidates(query, controller.signal);
    if (seq !== mapSearchSeq) return;

    if (!Array.isArray(data) || data.length === 0) {
      mapSearchStatus.textContent = 'No SOCCSKSARGEN match found. Try adding municipality (e.g., Koronadal).';
      hideMapSearchResults();
      return;
    }

    if (pickFirst) {
      pickMapSearchResult(data[0]);
      return;
    }

    mapSearchStatus.textContent = 'Select a location from the list below.';
    renderMapSearchResults(data);
  } catch (e) {
    if (seq !== mapSearchSeq) return;
    if (e?.name === 'AbortError') {
      mapSearchStatus.textContent = 'Search timed out. Please try again.';
      hideMapSearchResults();
      return;
    }
    mapSearchStatus.textContent = 'Search failed. Check internet connection and try again.';
    hideMapSearchResults();
  } finally {
    window.clearTimeout(timeoutId);
    if (seq === mapSearchSeq) {
      mapSearchBtn.disabled = false;
    }
  }
}

if (mapSearchBtn && mapSearchInput) {
  mapSearchBtn.addEventListener('click', searchMapLocation);
  mapSearchInput.addEventListener('input', () => {
    if (mapSearchTimer !== null) window.clearTimeout(mapSearchTimer);
    mapSearchTimer = window.setTimeout(() => {
      performMapSearch(false);
    }, 350);
  });

  mapSearchInput.addEventListener('keydown', (e) => {
    if (e.key === 'Enter') {
      e.preventDefault();
      searchMapLocation();
    }
  });
}

document.addEventListener('click', (e) => {
  if (!mapSearchResults || !mapSearchInput) return;
  if (mapSearchResults.contains(e.target) || e.target === mapSearchInput) return;
  hideMapSearchResults();
});
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
