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
const desktopSidebarCollapsed = ref(false);

function toggleSidebar() {
    if (window.innerWidth >= 1024) {
        desktopSidebarCollapsed.value = !desktopSidebarCollapsed.value;
    } else {
        mobileMenuOpen.value = !mobileMenuOpen.value;
    }
}
const userRoleCode = computed(
    () =>
        user.value?.role ??
        user.value?.role_record?.code ??
        user.value?.roleRecord?.code ??
        '',
);

const menuGroups = computed(() =>
    createAppMenu(route)
        .map((group) => ({
            ...group,
            items: group.items,
        }))
        .filter((group) => group.items.length > 0),
);
console.log('menuGroups', menuGroups.value);
const activePath = computed(() => page.url);
const roleLabel = computed(
    () =>
        user.value?.role_record?.name ??
        user.value?.roleRecord?.name ??
        userRoleCode.value?.replace(/_/g, ' ') ??
        'staff',
);
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
        <header class="layout-topbar">
            <div class="layout-topbar__left w-full md:w-auto">
                <Link href="/" class="flex items-center gap-3 w-[13.5rem]">
                    <div class="flex h-9 w-9 items-center justify-center rounded-lg bg-gradient-to-br from-indigo-600 to-cyan-500 font-bold text-white shadow-md">S</div>
                    <div>
                        <div class="text-[1.1rem] font-bold text-slate-900 leading-none">SmileWorks</div>
                    </div>
                </Link>

                <Button
                    :icon="desktopSidebarCollapsed ? 'pi pi-chevron-right' : 'pi pi-chevron-left'"
                    severity="secondary"
                    outlined
                    class="hidden lg:flex h-8 w-8 items-center justify-center rounded-md border-slate-200 bg-white text-slate-600 shadow-sm hover:bg-slate-100 transition-all !p-0"
                    @click="toggleSidebar"
                    aria-label="Toggle Sidebar"
                />
                <!-- <Button
                    icon="pi pi-bars"
                    severity="secondary"
                    outlined
                    class="flex lg:hidden h-8 w-8 items-center justify-center rounded-md border-slate-200 bg-white text-slate-600 shadow-sm hover:bg-slate-100 transition-all !p-0"
                    @click="toggleSidebar"
                    aria-label="Toggle Mobile Sidebar"
                /> -->
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

        <div
            v-if="mobileMenuOpen"
            class="layout-mask"
            @click="mobileMenuOpen = false"
        />

        <aside :class="[
            'layout-sidebar',
            mobileMenuOpen ? 'layout-sidebar--active' : '',
            desktopSidebarCollapsed ? 'layout-sidebar--collapsed' : ''
        ]">



            <div class="layout-sidebar__menu pt-8">
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

            <div class="layout-sidebar__footer p-4 border-t border-slate-200 mt-auto">
                <div class="flex items-center justify-between gap-2">
                    <div class="flex min-w-0 items-center gap-3">
                        <Avatar
                            :label="initials"
                            shape="circle"
                            style="background: linear-gradient(135deg, #4f46e5, #06b6d4); color: white"
                            class="shrink-0"
                        />
                        <div class="flex min-w-0 flex-col">
                            <div class="truncate text-sm font-bold text-slate-900">{{ user?.name || 'User' }}</div>
                            <div class="truncate text-[10px] font-semibold uppercase tracking-widest text-slate-500">
                                {{ user?.job_title || roleLabel || 'Admin Title' }}
                            </div>
                        </div>
                    </div>
                    <Button
                        icon="pi pi-sign-out"
                        text
                        rounded
                        severity="secondary"
                        @click="logout"
                        title="Logout"
                    />
                </div>
            </div>
        </aside>

        <div :class="[
            'layout-main',
            desktopSidebarCollapsed ? 'layout-main--sidebar-collapsed' : ''
        ]">
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
