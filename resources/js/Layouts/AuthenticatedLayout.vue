<script setup>
import { computed, ref, watch } from 'vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { route } from 'ziggy-js';
import Avatar from 'primevue/avatar';
import Button from 'primevue/button';
import InputText from 'primevue/inputtext';
import Tag from 'primevue/tag';
import Toast from 'primevue/toast';
import { createAppMenu } from './appMenu';

const props = defineProps({
    title: {
        type: String,
        default: 'SmileWorks Dental OS',
    },
});

const page = usePage();
const user = computed(() => page.props.auth.user);
const flashSuccess = computed(() => page.props.flash?.success);
const mobileMenuOpen = ref(false);

const menuGroups = computed(() =>
    createAppMenu(route)
        .map((group) => ({
            ...group,
            items: group.items.filter((item) => item.roles.includes(user.value?.role)),
        }))
        .filter((group) => group.items.length > 0),
);

const activePath = computed(() => page.url);
const roleLabel = computed(() => user.value?.role_record?.name || user.value?.role?.replace(/_/g, ' ') || 'staff');
const branchLabel = computed(() => user.value?.branch?.name || 'Global access');
const initials = computed(() => user.value?.name?.charAt(0)?.toUpperCase() || 'S');

watch(
    () => page.url,
    () => {
        mobileMenuOpen.value = false;
    },
);

function isActiveLink(href) {
    try {
        const path = new globalThis.URL(href).pathname;
        return activePath.value.startsWith(path);
    } catch {
        return activePath.value.startsWith(href);
    }
}

function logout() {
    router.post(route('logout'));
}
</script>

<template>
    <Head :title="title" />
    <Toast />

    <div class="layout-shell">
        <div
            v-if="mobileMenuOpen"
            class="layout-mask"
            @click="mobileMenuOpen = false"
        />

        <aside :class="['layout-sidebar', mobileMenuOpen ? 'layout-sidebar--active' : '']">
            <div class="layout-sidebar__header">
                <Link href="/" class="layout-sidebar__brand">
                    <div class="layout-sidebar__logo">S</div>
                    <div>
                        <div class="layout-sidebar__brand-name">SmileWorks</div>
                        <div class="layout-sidebar__brand-meta">Dental Clinic Management</div>
                    </div>
                </Link>

                <Button
                    icon="pi pi-times"
                    text
                    rounded
                    class="lg:hidden"
                    @click="mobileMenuOpen = false"
                />
            </div>

            <div class="layout-sidebar__profile">
                <Avatar
                    :label="initials"
                    size="large"
                    shape="circle"
                    style="background: linear-gradient(135deg, #4f46e5, #06b6d4); color: white"
                />
                <div>
                    <div class="layout-sidebar__user-name">{{ user?.name }}</div>
                    <div class="layout-sidebar__user-role">{{ user?.job_title || roleLabel }}</div>
                </div>
                <div class="flex flex-wrap gap-2 pt-3">
                    <Tag :value="roleLabel" rounded severity="contrast" />
                    <Tag :value="branchLabel" rounded severity="info" />
                </div>
            </div>

            <div class="layout-sidebar__menu">
                <div
                    v-for="group in menuGroups"
                    :key="group.label"
                    class="layout-sidebar__group"
                >
                    <div class="layout-sidebar__group-title">{{ group.label }}</div>
                    <nav class="layout-sidebar__group-items">
                        <Link
                            v-for="item in group.items"
                            :key="item.label"
                            :href="item.href"
                            :class="[
                                'layout-sidebar__item',
                                isActiveLink(item.href) ? 'layout-sidebar__item--active' : '',
                            ]"
                        >
                            <span :class="item.icon" class="layout-sidebar__item-icon" />
                            <span>{{ item.label }}</span>
                        </Link>
                    </nav>
                </div>
            </div>

            <div class="layout-sidebar__footer">
                <Button
                    label="Logout"
                    icon="pi pi-sign-out"
                    severity="secondary"
                    fluid
                    outlined
                    @click="logout"
                />
            </div>
        </aside>

        <div class="layout-main">
            <header class="layout-topbar">
                <div class="layout-topbar__left">
                    <Button
                        icon="pi pi-bars"
                        text
                        rounded
                        class="layout-topbar__menu-button"
                        @click="mobileMenuOpen = true"
                    />
                    <div>
                        <div class="layout-topbar__subtitle">PrimeVue Sakai style workspace</div>
                        <h1 class="layout-topbar__title">{{ title }}</h1>
                    </div>
                </div>

                <div class="layout-topbar__right">
                    <div class="layout-topbar__search">
                        <span class="pi pi-search layout-topbar__search-icon" />
                        <InputText placeholder="Search modules" />
                    </div>
                    <Button icon="pi pi-calendar" rounded text severity="secondary" />
                    <Button icon="pi pi-bell" rounded text severity="secondary" />
                    <Avatar
                        :label="initials"
                        shape="circle"
                        style="background: #e2e8f0; color: #0f172a"
                    />
                </div>
            </header>

            <main class="layout-content">
                <section class="layout-pagehead">
                    <div v-if="flashSuccess" class="layout-pagehead__flash">
                        {{ flashSuccess }}
                    </div>
                </section>

                <slot />
            </main>
        </div>
    </div>
</template>
