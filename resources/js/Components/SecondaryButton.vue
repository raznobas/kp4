<script setup>
defineProps({
    type: {
        type: String,
        default: 'button',
    },
    size: {
        type: String,
        default: 'large',
        validator: (value) => ['small', 'medium', 'large'].includes(value),
    },
});
</script>

<template>
    <button
        :type="type"
        :class="[
            'inline-flex items-center bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 disabled:opacity-25 transition ease-in-out duration-150',
            {
                'px-3 py-1': size === 'small',
                'px-4 py-2': size === 'large',
            },
        ]"
    >
        <slot />
    </button>
</template>
