<script setup lang="ts">
import type { HTMLAttributes } from 'vue';

defineOptions({
    inheritAttrs: false,
});

type Props = {
    className?: HTMLAttributes['class'];
};

defineProps<Props>();
</script>

<template>
    <svg
        xmlns="http://www.w3.org/2000/svg"
        viewBox="0 0 24 24"
        :class="className"
        v-bind="$attrs"
        fill="currentColor"
    >
        <!-- Column 1 (Medium - To Do) -->
        <rect x="3" y="6" width="5" height="12" rx="1.5" />
        <!-- Column 2 (Tall - In Progress) -->
        <rect x="9.5" y="3" width="5" height="18" rx="1.5" />
        <!-- Column 3 (Short - Done) -->
        <rect x="16" y="9" width="5" height="9" rx="1.5" />
    </svg>
</template>


