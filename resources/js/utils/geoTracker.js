/**
 * Geo Tracker Utility
 *
 * Real-time GPS tracking for Promotor monitoring using navigator.geolocation.
 * Includes anti-cheat detection: tab visibility & GPS permission denial.
 *
 * Usage (Vue 3 Composition API):
 *   import { startTracking, stopTracking } from '@/utils/geoTracker';
 *   onMounted(() => startTracking(userId));
 *   onUnmounted(() => stopTracking());
 */

import axios from 'axios';

// ─── State ────────────────────────────────────────────────────
let watchId = null;
let visibilityHandler = null;
let sendInterval = null;
let currentUserId = null;

/** Buffered position — always holds the latest reading */
let latestPosition = null;

/** How often (ms) we push the latest position to backend */
const SEND_INTERVAL_MS = 10_000; // every 10 seconds

/** Geolocation options */
const GEO_OPTIONS = {
  enableHighAccuracy: true,
  maximumAge: 5_000,        // accept cached pos up to 5s old
  timeout: 15_000,          // wait max 15s for a fix
};

// ─── Anti-Cheat: Violation Reporter ──────────────────────────

/**
 * Report a violation to the backend.
 * Fire-and-forget — we don't block tracking on this.
 *
 * @param {'tab_hidden'|'gps_denied'|'gps_unavailable'|'gps_timeout'} type
 * @param {object} meta  Extra context to attach
 */
function reportViolation(type, meta = {}) {
  axios
    .post('/promotor/tracking/violation', {
      type,
      timestamp: new Date().toISOString(),
      ...meta,
    })
    .catch((err) => {
      console.warn('[GeoTracker] Failed to report violation:', err.message);
    });
}

// ─── Anti-Cheat: Visibility Listener ─────────────────────────

function handleVisibilityChange() {
  if (document.visibilityState === 'hidden') {
    reportViolation('tab_hidden', {
      user_id: currentUserId,
      last_position: latestPosition
        ? { lat: latestPosition.coords.latitude, lng: latestPosition.coords.longitude }
        : null,
    });
  }
}

// ─── Core: Send Position to Backend ──────────────────────────

function sendPosition(position) {
  const payload = {
    latitude: position.coords.latitude,
    longitude: position.coords.longitude,
    accuracy: position.coords.accuracy,
    speed: position.coords.speed,
    heading: position.coords.heading,
    recorded_at: new Date(position.timestamp).toISOString(),
  };

  axios.post('/promotor/tracking/update', payload).catch((err) => {
    console.warn('[GeoTracker] Failed to send position:', err.message);
  });
}

// ─── Core: Buffered send on interval ─────────────────────────

function startSendLoop() {
  if (sendInterval) return;

  sendInterval = setInterval(() => {
    if (latestPosition) {
      sendPosition(latestPosition);
    }
  }, SEND_INTERVAL_MS);
}

function stopSendLoop() {
  if (sendInterval) {
    clearInterval(sendInterval);
    sendInterval = null;
  }
}

// ─── Geolocation Callbacks ───────────────────────────────────

function onPositionSuccess(position) {
  latestPosition = position;
}

function onPositionError(error) {
  switch (error.code) {
    case error.PERMISSION_DENIED:
      reportViolation('gps_denied', { user_id: currentUserId });
      console.error('[GeoTracker] GPS permission denied by user.');
      // Stop watching — no point continuing without permission
      stopTracking();
      break;

    case error.POSITION_UNAVAILABLE:
      reportViolation('gps_unavailable', { user_id: currentUserId });
      console.warn('[GeoTracker] GPS position unavailable.');
      break;

    case error.TIMEOUT:
      reportViolation('gps_timeout', { user_id: currentUserId });
      console.warn('[GeoTracker] GPS request timed out.');
      break;

    default:
      console.warn('[GeoTracker] Unknown geolocation error:', error);
      break;
  }
}

// ─── Public API ──────────────────────────────────────────────

/**
 * Start GPS tracking and anti-cheat monitoring.
 *
 * @param {number|string} userId  Current authenticated user ID
 * @returns {boolean} true if tracking started successfully
 */
export function startTracking(userId) {
  if (watchId !== null) {
    console.warn('[GeoTracker] Already tracking. Call stopTracking() first.');
    return false;
  }

  if (!navigator.geolocation) {
    reportViolation('gps_unavailable', { user_id: userId });
    console.error('[GeoTracker] Geolocation API not supported by this browser.');
    return false;
  }

  currentUserId = userId;

  // Start watching position
  watchId = navigator.geolocation.watchPosition(
    onPositionSuccess,
    onPositionError,
    GEO_OPTIONS,
  );

  // Start buffered send loop
  startSendLoop();

  // Anti-cheat: listen for tab visibility changes
  visibilityHandler = handleVisibilityChange;
  document.addEventListener('visibilitychange', visibilityHandler);

  console.info('[GeoTracker] Tracking started for user:', userId);
  return true;
}

/**
 * Stop GPS tracking and clean up all listeners.
 */
export function stopTracking() {
  // Stop geolocation watch
  if (watchId !== null) {
    navigator.geolocation.clearWatch(watchId);
    watchId = null;
  }

  // Stop send loop
  stopSendLoop();

  // Remove visibility listener
  if (visibilityHandler) {
    document.removeEventListener('visibilitychange', visibilityHandler);
    visibilityHandler = null;
  }

  // Send final position if available
  if (latestPosition) {
    sendPosition(latestPosition);
  }

  latestPosition = null;
  currentUserId = null;

  console.info('[GeoTracker] Tracking stopped.');
}

/**
 * Get the current position as a one-shot Promise.
 *
 * @returns {Promise<GeolocationPosition>}
 */
export function getCurrentPosition() {
  return new Promise((resolve, reject) => {
    if (!navigator.geolocation) {
      reject(new Error('Geolocation API not supported'));
      return;
    }

    navigator.geolocation.getCurrentPosition(resolve, reject, GEO_OPTIONS);
  });
}

/**
 * Check if tracking is currently active.
 *
 * @returns {boolean}
 */
export function isTracking() {
  return watchId !== null;
}
