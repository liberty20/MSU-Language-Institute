<template>
    <div v-if="impersonation.active"
         class="impersonation-banner"
         role="alert"
         aria-live="assertive">
        <div class="banner-inner">
            <!-- Icon + Text -->
            <div class="banner-info">
                <span class="banner-icon" aria-hidden="true">👁️</span>
                <div class="banner-text">
                    <span class="banner-label">Impersonation Active</span>
                    <span class="banner-detail">
                        You are viewing as
                        <strong>{{ $page.props.auth.user?.name }}</strong>
                        &nbsp;({{ $page.props.auth.user?.email }})
                        &nbsp;·&nbsp;
                        <span class="banner-timer">{{ formattedDuration }}</span>
                    </span>
                </div>
            </div>

            <!-- Return button -->
            <button
                type="button"
                class="banner-return-btn"
                :disabled="submitting"
                id="btn-return-to-account"
                @click="returnToAccount"
            >
                <span v-if="submitting" class="spinner" aria-hidden="true"></span>
                <svg v-else class="btn-icon" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                    <path fill-rule="evenodd" d="M9.707 16.707a1 1 0 01-1.414 0l-6-6a1 1 0 010-1.414l6-6a1 1 0 011.414 1.414L5.414 9H17a1 1 0 110 2H5.414l4.293 4.293a1 1 0 010 1.414z" clip-rule="evenodd"/>
                </svg>
                Return to My Account
            </button>
        </div>
    </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { usePage } from '@inertiajs/inertia-vue3';
import { Inertia } from '@inertiajs/inertia';

const page      = usePage();
const submitting = ref(false);

const returnToAccount = () => {
    submitting.value = true;
    Inertia.post(route('admin.impersonate.stop'), {}, {
        onFinish: () => { submitting.value = false; }
    });
};

const impersonation = computed(() => page.props.value.impersonation ?? {});

// Live duration counter
const startedAt  = computed(() => impersonation.value.started_at ? new Date(impersonation.value.started_at) : null);
const elapsed    = ref(0);
let timer        = null;

const formattedDuration = computed(() => {
    const s = elapsed.value;
    const h = Math.floor(s / 3600);
    const m = Math.floor((s % 3600) / 60);
    const sec = s % 60;
    if (h > 0) return `${h}h ${m}m ${sec}s`;
    if (m > 0) return `${m}m ${sec}s`;
    return `${sec}s`;
});

onMounted(() => {
    if (startedAt.value) {
        const update = () => {
            elapsed.value = Math.floor((Date.now() - startedAt.value.getTime()) / 1000);
        };
        update();
        timer = setInterval(update, 1000);
    }
});

onUnmounted(() => {
    if (timer) clearInterval(timer);
});
</script>

<style scoped>
.impersonation-banner {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    z-index: 9999;
    background: linear-gradient(90deg, #b45309 0%, #d97706 50%, #b45309 100%);
    background-size: 200% 100%;
    animation: banner-pulse 3s ease-in-out infinite;
    box-shadow: 0 2px 12px rgba(180, 83, 9, 0.5);
    padding: 0.6rem 1.5rem;
}

@keyframes banner-pulse {
    0%, 100% { background-position: 0% 50%; }
    50%       { background-position: 100% 50%; }
}

.banner-inner {
    display: flex;
    align-items: center;
    justify-content: space-between;
    max-width: 1400px;
    margin: 0 auto;
    gap: 1rem;
}

.banner-info {
    display: flex;
    align-items: center;
    gap: 0.75rem;
}

.banner-icon {
    font-size: 1.2rem;
    animation: blink 1.5s step-start infinite;
}

@keyframes blink {
    0%, 100% { opacity: 1; }
    50%       { opacity: 0.4; }
}

.banner-text {
    display: flex;
    flex-direction: column;
    line-height: 1.3;
}

.banner-label {
    font-size: 0.7rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.08em;
    color: #fef3c7;
}

.banner-detail {
    font-size: 0.875rem;
    color: #ffffff;
}

.banner-timer {
    font-family: monospace;
    font-size: 0.875rem;
    color: #fef3c7;
    font-weight: 600;
}

.banner-return-btn {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    background: #ffffff;
    color: #92400e;
    border: none;
    border-radius: 0.5rem;
    padding: 0.45rem 1rem;
    font-size: 0.8rem;
    font-weight: 700;
    cursor: pointer;
    transition: all 0.15s ease;
    white-space: nowrap;
    flex-shrink: 0;
}

.banner-return-btn:hover:not(:disabled) {
    background: #fef3c7;
    box-shadow: 0 2px 8px rgba(0,0,0,0.15);
    transform: translateY(-1px);
}

.banner-return-btn:disabled {
    opacity: 0.7;
    cursor: not-allowed;
}

.btn-icon {
    width: 1rem;
    height: 1rem;
}

.spinner {
    display: inline-block;
    width: 0.9rem;
    height: 0.9rem;
    border: 2px solid #92400e;
    border-top-color: transparent;
    border-radius: 50%;
    animation: spin 0.6s linear infinite;
}

@keyframes spin {
    to { transform: rotate(360deg); }
}
</style>
