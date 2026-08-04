<script setup>
import Button from 'primevue/button';
import Checkbox from 'primevue/checkbox';
import InputText from 'primevue/inputtext';
import Password from 'primevue/password';
import Message from 'primevue/message';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { route } from 'ziggy-js';

defineProps({
    status: String,
    canResetPassword: Boolean,
});

const form = useForm({
    email: 'admin@smileworks.test',
    password: 'password',
    remember: false,
});

const submit = () => {
    form.post(route('login'), {
        onFinish: () => form.reset('password'),
    });
};
</script>

<template>
    <Head title="Login" />

    <div class="login-page">
        <!-- Background image -->
        <div class="login-bg"></div>

        <!-- Frosted glass overlay -->
        <div class="login-overlay"></div>

        <!-- Centered login card -->
        <div class="login-card-wrapper">
            <div class="login-card">
                <!-- Logo / Branding -->
                <div class="login-brand">
                    <div class="login-brand-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor">
                            <path d="M12 2C9.5 2 7.5 3.5 6.5 5.5C5.5 3.5 3.5 2 1 2C1 8 5 12 6.5 14C7 15 7 16 7 17C7 19.2 8.8 21 11 21H13C15.2 21 17 19.2 17 17C17 16 17 15 17.5 14C19 12 23 8 23 2C20.5 2 18.5 3.5 17.5 5.5C16.5 3.5 14.5 2 12 2Z"/>
                        </svg>
                    </div>
                    <h1 class="login-brand-name">SmileWorks</h1>
                    <p class="login-brand-tagline">Dental Clinic Management</p>
                </div>

                <Message v-if="status" severity="success" class="mb-4">{{ status }}</Message>

                <form class="login-form" @submit.prevent="submit">
                    <div class="form-group">
                        <label class="form-label">Email Address</label>
                        <InputText v-model="form.email" class="w-full form-input" placeholder="you@example.com" />
                        <small v-if="form.errors.email" class="form-error">{{ form.errors.email }}</small>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Password</label>
                        <Password
                            v-model="form.password"
                            class="w-full"
                            input-class="w-full form-input"
                            :feedback="false"
                            toggle-mask
                            placeholder="••••••••"
                        />
                        <small v-if="form.errors.password" class="form-error">{{ form.errors.password }}</small>
                    </div>

                    <div class="form-row">
                        <div class="remember-me">
                            <Checkbox v-model="form.remember" binary input-id="remember" />
                            <label for="remember" class="remember-label">Remember me</label>
                        </div>
                        <Link v-if="canResetPassword" :href="route('password.request')" class="forgot-link">
                            Forgot password?
                        </Link>
                    </div>

                    <Button
                        label="Sign In"
                        type="submit"
                        class="w-full login-btn"
                        :loading="form.processing"
                    />
                </form>
            </div>
        </div>
    </div>
</template>

<style scoped>
/* ── Page shell ── */
.login-page {
    position: relative;
    min-height: 100vh;
    width: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-family: 'Inter', 'Segoe UI', sans-serif;
    overflow: hidden;
}

/* ── Background image ── */
.login-bg {
    position: absolute;
    inset: 0;
    background-image: url('/Uploads/smile-bg.png');
    background-size: cover;
    background-position: center;
    background-repeat: no-repeat;
    transform: scale(1.03);
    filter: brightness(0.88);
    z-index: 0;
}

/* ── Subtle dark overlay for contrast ── */
.login-overlay {
    position: absolute;
    inset: 0;
    background: linear-gradient(135deg, rgba(15, 30, 60, 0.45) 0%, rgba(0, 80, 120, 0.25) 100%);
    z-index: 1;
}

/* ── Card wrapper ── */
.login-card-wrapper {
    position: relative;
    z-index: 2;
    display: flex;
    align-items: center;
    justify-content: center;
    width: 100%;
    padding: 1.5rem;
}

/* ── Glass card ── */
.login-card {
    background: rgba(255, 255, 255, 0.82);
    backdrop-filter: blur(24px) saturate(1.6);
    -webkit-backdrop-filter: blur(24px) saturate(1.6);
    border: 1px solid rgba(255, 255, 255, 0.6);
    border-radius: 20px;
    padding: 2.5rem 2.25rem;
    width: 100%;
    max-width: 420px;
    box-shadow:
        0 8px 32px rgba(0, 80, 140, 0.18),
        0 2px 8px rgba(0, 0, 0, 0.08);
    animation: cardFadeIn 0.5s ease;
}

@keyframes cardFadeIn {
    from { opacity: 0; transform: translateY(24px); }
    to   { opacity: 1; transform: translateY(0); }
}

/* ── Brand section ── */
.login-brand {
    display: flex;
    flex-direction: column;
    align-items: center;
    margin-bottom: 2rem;
}

.login-brand-icon {
    width: 56px;
    height: 56px;
    background: linear-gradient(135deg, #0ea5e9, #0284c7);
    border-radius: 14px;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-bottom: 0.75rem;
    box-shadow: 0 4px 16px rgba(14, 165, 233, 0.4);
}

.login-brand-icon svg {
    width: 30px;
    height: 30px;
    color: white;
    fill: white;
}

.login-brand-name {
    font-size: 1.6rem;
    font-weight: 700;
    color: #0c2d48;
    margin: 0;
    letter-spacing: -0.5px;
}

.login-brand-tagline {
    font-size: 0.8rem;
    color: #64748b;
    margin: 0.25rem 0 0;
    letter-spacing: 0.5px;
    text-transform: uppercase;
}

/* ── Form ── */
.login-form {
    display: flex;
    flex-direction: column;
    gap: 1.1rem;
}

.form-group {
    display: flex;
    flex-direction: column;
    gap: 0.4rem;
}

.form-label {
    font-size: 0.82rem;
    font-weight: 600;
    color: #334155;
    letter-spacing: 0.2px;
}

.form-error {
    font-size: 0.75rem;
    color: #ef4444;
}

/* ── Remember / forgot row ── */
.form-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
}

.remember-me {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.remember-label {
    font-size: 0.82rem;
    color: #475569;
    cursor: pointer;
}

.forgot-link {
    font-size: 0.82rem;
    color: #0284c7;
    text-decoration: none;
    font-weight: 500;
    transition: color 0.2s;
}

.forgot-link:hover {
    color: #0ea5e9;
    text-decoration: underline;
}

/* ── Sign-in button ── */
.login-btn {
    margin-top: 0.4rem;
    height: 2.75rem;
    font-size: 0.95rem;
    font-weight: 600;
    background: linear-gradient(135deg, #0284c7, #0ea5e9) !important;
    border: none !important;
    border-radius: 10px !important;
    box-shadow: 0 4px 14px rgba(14, 165, 233, 0.35) !important;
    transition: transform 0.15s, box-shadow 0.15s !important;
}

.login-btn:hover:not(:disabled) {
    transform: translateY(-1px);
    box-shadow: 0 6px 20px rgba(14, 165, 233, 0.5) !important;
}

.login-btn:active:not(:disabled) {
    transform: translateY(0);
}

/* ── Responsive tweak ── */
@media (max-width: 480px) {
    .login-card {
        padding: 2rem 1.5rem;
        border-radius: 16px;
    }
}
</style>
