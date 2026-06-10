<script setup lang="ts">
import { GripVertical, X, Plus } from '@lucide/vue';
import { computed } from 'vue';
import draggable from 'vuedraggable';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import type { Column, Task, Subtask } from '@/types/kanban';
import KanbanCard from './KanbanCard.vue';

const props = defineProps<{
    column: Column;
    searchQuery: string;
    selectedPriority: string;
    matchesFilter: (task: Task) => boolean;
}>();

const emit = defineEmits<{
    (e: 'update:tasks', tasks: Task[]): void;
    (e: 'dragChange', event: any): void;
    (e: 'deleteColumn', id: number, name: string): void;
    (e: 'addTask', columnId: number): void;
    (e: 'editTask', task: Task): void;
    (e: 'archiveTask', task: Task): void;
    (e: 'deleteTask', task: Task): void;
    (e: 'toggleSubtask', subtask: Subtask): void;
}>();

const tasksList = computed({
    get: () => props.column.tasks,
    set: (val) => {
        emit('update:tasks', val);
    }
});

const filteredTasksCount = computed(() => {
    return props.column.tasks.filter(props.matchesFilter).length;
});
</script>

<template>
    <div class="w-[320px] shrink-0 flex flex-col rounded-xl border border-zinc-200/80 bg-zinc-100/70 p-4 transition-all duration-300 shadow-xs hover:shadow-md dark:border-zinc-800/80 dark:bg-zinc-900/30 dark:backdrop-blur-md max-h-[75vh]">
        
        <!-- Column Header -->
        <div class="mb-4 flex items-center justify-between">
            <div class="flex items-center gap-1.5 min-w-0">
                <span class="column-drag-handle p-1 text-zinc-400 hover:text-zinc-600 dark:hover:text-zinc-200 cursor-grab active:cursor-grabbing shrink-0 transition duration-150">
                    <GripVertical class="size-4" />
                </span>
                <h3 class="font-bold text-zinc-800 dark:text-zinc-200 truncate text-sm tracking-tight">{{ props.column.name }}</h3>
                <Badge variant="secondary" class="bg-zinc-200/60 text-zinc-700 dark:bg-zinc-800 dark:text-zinc-300 font-semibold px-2 py-0.5 text-[10px] rounded-full shrink-0">
                    <span v-if="props.searchQuery || props.selectedPriority !== 'all'">{{ filteredTasksCount }} / </span>{{ props.column.tasks.length }}
                </Badge>
            </div>
            
            <Button @click="emit('deleteColumn', props.column.id, props.column.name)" variant="ghost" size="icon" class="h-6 w-6 text-zinc-400 hover:text-rose-600 hover:bg-rose-50 dark:hover:bg-rose-950/30 rounded-md transition duration-150 shrink-0">
                <X class="size-4" />
            </Button>
        </div>

        <!-- Tasks Draggable Area -->
        <draggable 
            v-model="tasksList" 
            group="tasks" 
            item-key="id" 
            class="mb-4 flex-1 space-y-3 drop-zone overflow-y-auto pr-1 scrollbar-thin scrollbar-thumb-zinc-200 dark:scrollbar-thumb-zinc-800 min-h-[50px]" 
            ghost-class="kanban-ghost" 
            drag-class="kanban-drag"
            @change="emit('dragChange', $event)"
        >
            <template #item="{ element: t }">
                <div v-show="props.matchesFilter(t)">
                    <KanbanCard 
                        :task="t"
                        @edit="(task) => emit('editTask', task)"
                        @archive="(task) => emit('archiveTask', task)"
                        @delete="(task) => emit('deleteTask', task)"
                        @toggle-subtask="(subtask) => emit('toggleSubtask', subtask)"
                    />
                </div>
            </template>
        </draggable>

        <!-- Empty Column Placeholder -->
        <div v-if="!props.column.tasks.length" class="mb-4 rounded-xl border-2 border-dashed border-zinc-200 dark:border-zinc-800/80 bg-white/40 dark:bg-zinc-900/20 px-4 py-8 text-center text-xs text-zinc-400 dark:text-zinc-500">
            Solte cartões aqui ou adicione tarefas.
        </div>

        <!-- Add Card Button inside column -->
        <Button @click="emit('addTask', props.column.id)" variant="ghost" class="w-full justify-center text-zinc-500 hover:text-indigo-600 dark:text-zinc-400 dark:hover:text-indigo-400 hover:bg-zinc-200/40 dark:hover:bg-zinc-800/40 py-2 border border-dashed border-zinc-300/80 dark:border-zinc-800 rounded-lg text-xs font-semibold gap-1.5 transition-all cursor-pointer">
            <Plus class="size-3.5" />
            Adicionar Cartão
        </Button>
    </div>
</template>
