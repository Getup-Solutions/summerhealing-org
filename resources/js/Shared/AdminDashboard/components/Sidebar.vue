<template>
    <aside id="logo-sidebar"
        class="fixed top-0 left-0 z-40 w-72 h-screen pt-20 transition-transform -translate-x-full bg-white border-r border-gray-200 sm:translate-x-0 dark:bg-gray-800 dark:border-gray-700"
        aria-label="Sidebar">
        <div class="h-full px-3 pb-4 overflow-y-auto bg-white dark:bg-gray-800">
            <ul class="space-y-2 font-medium">
                <SidebarItem :sidebarItem="item" v-for="item in sidebarMenu" :key="item"></SidebarItem>
            </ul>
        </div>
    </aside>
    <slot></slot>
</template>
<script>
export default {
    props: ["sidebarMenu"]
}
</script>
<script setup>
import SidebarItem from './SidebarComponents/SidebarItem.vue';
</script>