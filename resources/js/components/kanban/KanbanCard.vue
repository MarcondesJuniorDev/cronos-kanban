<script setup lang="ts">
import { Pencil, Archive, Trash2, CheckSquare, Clock } from '@lucide/vue';
import { getPriorityConfig, getTagColorConfig, getFriendlyDueDate } from '@/lib/kanbanHelpers';
import type { Task, Subtask } from '@/types/kanban';

const props = defineProps<{
    task: Task;
}>();

const emit = defineEmits<{
    (e: 'edit', task: Task): void;
    (e: 'archive', task: Task): void;
    (e: 'delete', task: Task): void;
    (e: 'toggleSubtask', subtask: Subtask): void;
}>();

const getCompletedSubtasksCount = (task: Task) => {
    if (!task.subtasks) {
        return 0;
    }

    return task.subtasks.filter((s: Subtask) => s.is_completed).length;
};
</script>

<template>
    <div class="group relative cursor-grab rounded-xl border border-zinc-200 bg-white p-4 shadow-2xs hover:shadow-md hover:border-indigo-500/50 dark:border-zinc-800/60 dark:bg-zinc-900/90 dark:hover:border-indigo-400/50 transition-all duration-200 active:cursor-grabbing">
        
        <!-- Hover Actions Menu -->
        <div class="absolute right-2.5 top-2.5 flex items-center gap-1 opacity-0 group-hover:opacity-100 transition-opacity duration-200 bg-white/90 dark:bg-zinc-900/90 p-1 rounded-md shadow-2xs border border-zinc-200 dark:border-zinc-800 backdrop-blur-xs z-10">
            <button @click.stop="emit('edit', props.task)" class="p-1 text-zinc-400 hover:text-indigo-600 hover:bg-zinc-100 dark:hover:bg-zinc-800 rounded-md transition cursor-pointer" title="Editar">
                <Pencil class="size-3.5" />
            </button>
            <button @click.stop="emit('archive', props.task)" class="p-1 text-zinc-400 hover:text-amber-650 hover:bg-zinc-150 dark:hover:bg-zinc-800 rounded-md transition cursor-pointer" title="Arquivar">
                <Archive class="size-3.5" />
            </button>
            <button @click.stop="emit('delete', props.task)" class="p-1 text-zinc-400 hover:text-rose-600 hover:bg-rose-50 dark:hover:bg-rose-950/40 rounded-md transition cursor-pointer" title="Excluir">
                <Trash2 class="size-3.5" />
            </button>
        </div>

        <!-- Card Tags -->
        <div v-if="props.task.tags && props.task.tags.length" class="flex flex-wrap gap-1 mb-2 pr-12">
            <span 
                v-for="tag in props.task.tags" 
                :key="tag.id"
                :class="['inline-flex items-center rounded-md px-1.5 py-0.5 text-[10px] font-semibold border', getTagColorConfig(tag.color).bg]"
            >
                {{ tag.name }}
            </span>
        </div>

        <!-- Card Title -->
        <h4 class="font-semibold text-zinc-900 dark:text-zinc-100 leading-snug mb-1 text-sm pr-12">
            {{ props.task.title }}
        </h4>
        
        <!-- Card Description -->
        <p v-if="props.task.description" class="line-clamp-2 text-xs text-zinc-500 dark:text-zinc-400 mt-1.5 leading-relaxed">
            {{ props.task.description }}
        </p>
        
        <!-- Subtask mini progress bar -->
        <div v-if="props.task.subtasks && props.task.subtasks.length" class="mt-2.5 w-full bg-zinc-100 dark:bg-zinc-800 rounded-full h-1 overflow-hidden">
            <div 
                class="bg-indigo-600 dark:bg-indigo-500 h-1 rounded-full transition-all duration-300"
                :style="{ width: `${(getCompletedSubtasksCount(props.task) / props.task.subtasks.length) * 100}%` }"
            />
        </div>

        <!-- Subtasks List on Card -->
        <div v-if="props.task.subtasks && props.task.subtasks.length" class="mt-3.5 space-y-1.5 border-t border-zinc-150 dark:border-zinc-800/60 pt-2.5">
            <div 
                v-for="sub in props.task.subtasks" 
                :key="sub.id" 
                class="flex items-center gap-2 text-xs"
                @click.stop
            >
                <input 
                    type="checkbox" 
                    :checked="sub.is_completed" 
                    @change="emit('toggleSubtask', sub)"
                    class="rounded border-zinc-350 text-indigo-600 focus:ring-indigo-500 cursor-pointer h-3.5 w-3.5"
                />
                <span 
                    :class="['truncate select-none cursor-pointer flex-1 transition duration-150', sub.is_completed ? 'line-through text-zinc-400 dark:text-zinc-500' : 'text-zinc-700 dark:text-zinc-300 hover:text-indigo-600 dark:hover:text-indigo-400']"
                    @click="emit('toggleSubtask', sub)"
                >
                    {{ sub.title }}
                </span>
            </div>
        </div>
        
        <!-- Card Footer Details -->
        <div class="mt-4 flex flex-wrap gap-2 items-center">
            
            <!-- Priority Badge -->
            <span :class="['inline-flex items-center gap-1.5 rounded-full px-2 py-0.5 text-[10px] font-semibold border tracking-wider uppercase', getPriorityConfig(props.task.priority).bg]">
                <span :class="['size-1.5 rounded-full', getPriorityConfig(props.task.priority).dot]" />
                {{ getPriorityConfig(props.task.priority).label }}
            </span>

            <!-- Checklist Progress Badge -->
            <span v-if="props.task.subtasks && props.task.subtasks.length" class="inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-[10px] font-semibold border bg-indigo-50/70 text-indigo-700 border-indigo-200/50 dark:bg-indigo-950/20 dark:text-indigo-400 dark:border-indigo-900/30 tracking-wide">
                <CheckSquare class="size-3" />
                <span>{{ getCompletedSubtasksCount(props.task) }}/{{ props.task.subtasks.length }}</span>
            </span>
            
            <!-- Due Date Badge -->
            <span v-if="props.task.due_date" :class="['inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-[10px] font-semibold border tracking-wide', getFriendlyDueDate(props.task.due_date).class]">
                <Clock class="size-3" />
                <span>{{ getFriendlyDueDate(props.task.due_date).text }}</span>
            </span>
        </div>
    </div>
</template>
