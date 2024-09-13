<script setup>
defineProps({
    size: {
        type: String,
        default: 'large',
        validator: (value) => ['small', 'medium', 'large'].includes(value),
    },
});
</script>
<template>
    <button
        :class="[
            'inline-flex items-center bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700 focus:bg-gray-700 active:bg-gray-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150',
            {
                'px-3 py-1': size === 'small',
                'px-4 py-2': size === 'large',
            },
        ]"
    >
        <slot />
    </button>
</template>
