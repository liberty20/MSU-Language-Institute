<template>
    <!-- Warning modal: shown 2 minutes before expiry -->
    <Teleport to="body">
        <Transition name="modal-fade">
            <div v-if="showWarning" class="modal-backdrop" role="dialog" aria-modal="true" aria-labelledby="timeout-title">
                <div class="modal-card">
                    <div class="modal-header">
                        <div class="modal-icon-wrap">
                            <svg class="modal-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                      d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/>
                            </svg>
                        </div>
                        <h2 id="timeout-title" class="modal-title">Session Expiring Soon</h2>
                    </div>

                    <p class="modal-body">
                        Your session will automatically expire due to inactivity in
                    </p>
                    <div class="countdown-display" :class="{ 'countdown-urgent': remainingSeconds <= 30 }">
                        {{ formattedRemaining }}
                    </div>
                    <p class="modal-body-sub">
                        Click <strong>Stay Logged In</strong> to continue your session, or <strong>Log Out</strong> to exit now.
                    </p>

                    <div class="modal-actions">
                        <button
                            id="btn-stay-logged-in"
                            class="btn-stay"
                            @click="stayLoggedIn"
                            :disabled="pingPending"
                        >
                            <span v-if="pingPending" class="btn-spinner"></span>
                            <svg v-else class="btn-sm-icon" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                            </svg>
                            Stay Logged In
                        </button>
                        <form :action="route('logout')" method="POST" @submit="loggingOut = true">
                            <input type="hidden" name="_token" :value="csrfToken" />
                            <button type="submit" class="btn-logout" id="btn-timeout-logout">
                                Log Out Now
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </Transition>
    </Teleport>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted, watch } from 'vue';
import { usePage } from '@inertiajs/inertia-vue3';
import axios from 'axios';

const page = usePage();

const idleTimeoutMinutes = computed(() =>
    page.props.value.sessionConfig?.idle_timeout_minutes ?? 20
);

const WARNING_SECONDS = 120; // show modal 2 min before expiry

const showWarning      = ref(false);
const remainingSeconds = ref(WARNING_SECONDS);
const pingPending      = ref(false);
const loggingOut       = ref(false);

const csrfToken = computed(() =>
    document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? ''
);

const formattedRemaining = computed(() => {
    const m = Math.floor(remainingSeconds.value / 60);
    const s = remainingSeconds.value % 60;
    return m > 0 ? `${m}:${String(s).padStart(2, '0')}` : `${s}s`;
});

let idleTimer    = null; // fires when idle threshold is reached → show warning
let countdownTimer = null; // ticks down the visible countdown
let lastActivity = Date.now();

const resetIdleTimer = () => {
    lastActivity = Date.now();
    if (showWarning.value) return; // don't reset if user hasn't interacted
    clearTimeout(idleTimer);
    const warnAfterMs = (idleTimeoutMinutes.value * 60 - WARNING_SECONDS) * 1000;
    idleTimer = setTimeout(showTimeoutWarning, Math.max(warnAfterMs, 0));
};

const showTimeoutWarning = () => {
    showWarning.value      = true;
    remainingSeconds.value = WARNING_SECONDS;
    countdownTimer = setInterval(() => {
        remainingSeconds.value--;
        if (remainingSeconds.value <= 0) {
            // Auto-logout — submit the logout form programmatically
            clearInterval(countdownTimer);
            document.getElementById('btn-timeout-logout')?.click();
        }
    }, 1000);
};

const stayLoggedIn = async () => {
    pingPending.value = true;
    try {
        await axios.post(route('session.ping'));
        showWarning.value = false;
        clearInterval(countdownTimer);
        lastActivity = Date.now();
        resetIdleTimer();
    } catch (e) {
        // Network error — degrade gracefully
        console.warn('Session ping failed', e);
    } finally {
        pingPending.value = false;
    }
};

// Track user activity
const activityEvents = ['mousemove', 'keydown', 'mousedown', 'touchstart', 'scroll'];
const onActivity = () => {
    if (!showWarning.value) resetIdleTimer();
};

onMounted(() => {
    resetIdleTimer();
    activityEvents.forEach(e => window.addEventListener(e, onActivity, { passive: true }));
});

onUnmounted(() => {
    clearTimeout(idleTimer);
    clearInterval(countdownTimer);
    activityEvents.forEach(e => window.removeEventListener(e, onActivity));
});
</script>

<style scoped>
.modal-backdrop {
    position: fixed;
    inset: 0;
    z-index: 99999;
    background: rgba(0, 0, 0, 0.55);
    backdrop-filter: blur(4px);
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 1rem;
}

.modal-card {
    background: #fff;
    border-radius: 1.25rem;
    padding: 2rem;
    max-width: 420px;
    width: 100%;
    box-shadow: 0 20px 60px rgba(0,0,0,0.35);
    text-align: center;
    animation: slide-up 0.25s ease;
}

@keyframes slide-up {
    from { transform: translateY(20px); opacity: 0; }
    to   { transform: translateY(0);    opacity: 1; }
}

.modal-header {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 0.75rem;
    margin-bottom: 1rem;
}

.modal-icon-wrap {
    background: #fef3c7;
    border-radius: 50%;
    padding: 0.75rem;
}

.modal-icon {
    width: 2rem;
    height: 2rem;
    color: #d97706;
}

.modal-title {
    font-size: 1.25rem;
    font-weight: 700;
    color: #1e293b;
    margin: 0;
}

.modal-body {
    color: #475569;
    font-size: 0.9rem;
    margin-bottom: 0.5rem;
}

.modal-body-sub {
    color: #64748b;
    font-size: 0.8rem;
    margin-top: 0.75rem;
    margin-bottom: 1.5rem;
}

.countdown-display {
    font-size: 3rem;
    font-weight: 800;
    font-variant-numeric: tabular-nums;
    color: #d97706;
    font-family: monospace;
    transition: color 0.3s;
    line-height: 1;
    margin: 0.5rem 0;
}

.countdown-urgent {
    color: #dc2626;
    animation: urgent-pulse 0.5s ease-in-out infinite alternate;
}

@keyframes urgent-pulse {
    to { transform: scale(1.05); }
}

.modal-actions {
    display: flex;
    gap: 0.75rem;
    justify-content: center;
    flex-wrap: wrap;
}

.btn-stay {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    background: #1e40af;
    color: #fff;
    border: none;
    border-radius: 0.625rem;
    padding: 0.6rem 1.25rem;
    font-size: 0.875rem;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.15s;
}

.btn-stay:hover:not(:disabled) {
    background: #1d4ed8;
    box-shadow: 0 4px 12px rgba(29,78,216,0.35);
    transform: translateY(-1px);
}

.btn-stay:disabled {
    opacity: 0.7;
    cursor: not-allowed;
}

.btn-logout {
    background: transparent;
    color: #64748b;
    border: 1.5px solid #e2e8f0;
    border-radius: 0.625rem;
    padding: 0.6rem 1.25rem;
    font-size: 0.875rem;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.15s;
}

.btn-logout:hover {
    color: #dc2626;
    border-color: #dc2626;
}

.btn-sm-icon {
    width: 1rem;
    height: 1rem;
}

.btn-spinner {
    display: inline-block;
    width: 0.9rem;
    height: 0.9rem;
    border: 2px solid rgba(255,255,255,0.4);
    border-top-color: #fff;
    border-radius: 50%;
    animation: spin 0.6s linear infinite;
}

@keyframes spin {
    to { transform: rotate(360deg); }
}

.modal-fade-enter-active,
.modal-fade-leave-active {
    transition: opacity 0.2s ease;
}

.modal-fade-enter-from,
.modal-fade-leave-to {
    opacity: 0;
}
</style>
