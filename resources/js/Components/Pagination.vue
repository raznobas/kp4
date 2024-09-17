<script setup>
import { Link } from '@inertiajs/vue3';

const props = defineProps({
    items: Object,
});

const getLocalizedLabel = (label) => {
    if (label === '&laquo; Previous') {
        return '&laquo; Предыдущая';
    } else if (label === 'Next &raquo;') {
        return 'Следующая &raquo;';
    }
    return label;
};
</script>
<template>
    <nav class="mt-4">
        <ul class="flex justify-center">
            <li v-for="link in items.links" :key="link.label" class="mx-1">
                <Link :href="link.url || ''" v-html="getLocalizedLabel(link.label)" class="px-3 py-2 rounded-md" :class="{ 'bg-gray-800 text-white': link.active }"/>
            </li>
        </ul>
    </nav>
</template>
