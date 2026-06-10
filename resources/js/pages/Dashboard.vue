<script setup lang="ts">
import { Head, router, useForm } from '@inertiajs/vue3';
import { computed, nextTick, ref, watch } from 'vue';
import draggable from 'vuedraggable';
import columnsRoutes from '@/routes/columns';
import tasksRoutes from '@/routes/tasks';

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Kanban',
                href: '/dashboard',
            },
        ],
    },
});

interface Task {
    id: number;
    column_id: number;
    title: string;
    description: string | null;
    priority: 'low' | 'medium' | 'high';
    due_date: string | null;
    position: number;
}

interface Column {
    id: number;
    name: string;
    position: number;
    tasks: Task[];
}

const props = defineProps<{
    columns: Column[];
}>();

const cloneColumns = (columns: Column[]) => JSON.parse(JSON.stringify(columns)) as Column[];

const localColumns = ref<Column[]>(cloneColumns(props.columns));

const orderedColumns = computed(() => [...localColumns.value].sort((left, right) => left.position - right.position));

const totalTasks = computed(() => orderedColumns.value.reduce((count, column) => count + column.tasks.length, 0));
const hasColumns = computed(() => orderedColumns.value.length > 0);

watch(() => props.columns, (newColumns) => {
    localColumns.value = cloneColumns(newColumns);
}, { deep: true });

const handleDragChange = async () => {
    await nextTick();

    const payload: { id: number; column_id: number; position: number }[] = [];
    localColumns.value.forEach((column) => {
        column.tasks.forEach((task, index) => {
            payload.push({ id: task.id, column_id: column.id, position: index });
        });
    });
    router.put(tasksRoutes.reorder.url(), { tasks: payload }, { preserveState: true, preserveScroll: true });
};

const isColumnModalOpen = ref(false);
const isTaskModalOpen = ref(false);
const isEditMode = ref(false);
const editingTaskId = ref<number | null>(null);

const columnForm = useForm({ name: '' });
const taskForm = useForm({ column_id: '', title: '', description: '', priority: 'medium', due_date: '' });

const submitColumn = () => {
    columnForm.post(columnsRoutes.store.url(), {
        onSuccess: () => {
            isColumnModalOpen.value = false;
            columnForm.reset();
        },
    });
};

const openTaskModal = (columnId: number) => {
    isEditMode.value = false;
    editingTaskId.value = null;
    taskForm.reset();
    taskForm.column_id = columnId.toString();
    isTaskModalOpen.value = true;
};

const openEditTaskModal = (task: Task) => {
    isEditMode.value = true;
    editingTaskId.value = task.id;
    taskForm.column_id = task.column_id.toString();
    taskForm.title = task.title;
    taskForm.description = task.description || '';
    taskForm.priority = task.priority;
    taskForm.due_date = task.due_date || '';
    isTaskModalOpen.value = true;
};

const submitTask = () => {
    if (isEditMode.value && editingTaskId.value) {
        taskForm.put(`/tasks/${editingTaskId.value}`, {
            preserveScroll: true,
            onSuccess: () => {
                isTaskModalOpen.value = false;
                taskForm.reset();
            },
        });
    } else {
        taskForm.post(tasksRoutes.store.url(), {
            preserveScroll: true,
            onSuccess: () => {
                isTaskModalOpen.value = false;
                taskForm.reset();
            },
        });
    }
};

const deleteTask = (taskId: number) => {
    if (confirm('Deseja excluir esta tarefa?')) {
        router.delete(`/tasks/${taskId}`, { preserveScroll: true });
    }
};

const deleteColumn = (columnId: number) => {
    if (confirm('Deseja excluir esta coluna e todas as tarefas vinculadas?')) {
        router.delete(columnsRoutes.destroy.url(columnId));
    }
};

const getPriorityClass = (prio: string) => {
    if (prio === 'high') {
        return 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400';
    }

    if (prio === 'medium') {
        return 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400';
    }

    return 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400';
};

const getPriorityLabel = (prio: string) => {
    if (prio === 'high') {
        return 'Alta';
    }

    if (prio === 'medium') {
        return 'Média';
    }

    return 'Baixa';
};

const formatDueDate = (dueDate: string | null) => {
    if (!dueDate) {
        return '';
    }

    return new Intl.DateTimeFormat('pt-BR', {
        day: '2-digit',
        month: 'short',
        year: 'numeric',
    }).format(new Date(`${dueDate}T00:00:00`));
};
</script>

<template>
    <Head title="Cronos Kanban" />
    <div class="flex flex-1 flex-col gap-6 p-6">
        <div class="flex flex-col gap-4 border-b border-gray-200 pb-4 dark:border-zinc-800 lg:flex-row lg:items-end lg:justify-between">
            <div>
                <p class="text-xs font-semibold uppercase tracking-[0.24em] text-indigo-600 dark:text-indigo-400">Kanban</p>
                <h2 class="text-2xl font-bold text-gray-900 dark:text-gray-100">Cronos Kanban</h2>
                <p class="mt-1 text-sm text-gray-500 dark:text-zinc-400">Organize colunas, acompanhe prioridades e arraste cartões entre etapas.</p>
            </div>
            <button @click="isColumnModalOpen = true" class="inline-flex items-center justify-center rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white shadow-xs transition duration-150 hover:bg-indigo-700">
                +
                Nova Coluna
            </button>
        </div>

            <div class="grid gap-4 md:grid-cols-3">
                <div class="rounded-2xl border border-gray-200 bg-white p-4 shadow-sm dark:border-zinc-800 dark:bg-zinc-900/50">
                    <p class="text-sm text-gray-500 dark:text-zinc-400">Colunas</p>
                    <p class="mt-2 text-2xl font-bold text-gray-900 dark:text-gray-100">{{ orderedColumns.length }}</p>
                </div>
                <div class="rounded-2xl border border-gray-200 bg-white p-4 shadow-sm dark:border-zinc-800 dark:bg-zinc-900/50">
                    <p class="text-sm text-gray-500 dark:text-zinc-400">Cartões</p>
                    <p class="mt-2 text-2xl font-bold text-gray-900 dark:text-gray-100">{{ totalTasks }}</p>
                </div>
                <div class="rounded-2xl border border-gray-200 bg-linear-to-br from-indigo-600 to-indigo-500 p-4 text-white shadow-sm">
                    <p class="text-sm text-indigo-100">Ação rápida</p>
                    <p class="mt-2 text-2xl font-bold">Arraste e reorganize</p>
                </div>
            </div>

            <div v-if="hasColumns" class="grid grid-cols-1 gap-6 md:grid-cols-3 items-start">
                <div v-for="col in orderedColumns" :key="col.id" class="flex min-h-125 flex-col rounded-xl border border-gray-200 bg-gray-100 p-4 dark:border-zinc-800 dark:bg-zinc-900/50">

                    <div class="mb-4 flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <h3 class="font-bold text-gray-800 dark:text-gray-200">{{ col.name }}</h3>
                            <span class="px-2 py-0.5 text-xs bg-gray-200 dark:bg-zinc-800 text-gray-600 dark:text-zinc-400 rounded-full font-semibold">
                                {{ col.tasks.length }}
                            </span>
                        </div>
                        <button @click="deleteColumn(col.id)" class="text-gray-400 hover:text-red-500 transition text-sm">✕</button>
                    </div>

                    <draggable v-model="col.tasks" group="tasks" item-key="id" class="mb-4 flex-1 space-y-3 drop-zone" ghost-class="opacity-40" @change="handleDragChange">
                        <template #item="{ element: t }">
                            <div class="cursor-grab rounded-lg border border-gray-200 bg-white p-4 shadow-xs transition duration-150 hover:border-indigo-500 active:cursor-grabbing dark:border-zinc-800 dark:bg-zinc-900 dark:hover:border-indigo-400">
                                <div class="flex items-start justify-between gap-2 mb-2">
                                    <h4 class="font-semibold text-gray-900 dark:text-gray-100 leading-tight">{{ t.title }}</h4>
                                    <div class="flex items-center gap-1 shrink-0">
                                        <span :class="['rounded px-2 py-0.5 text-[12px] font-bold uppercase mr-1', getPriorityClass(t.priority)]">{{ getPriorityLabel(t.priority) }}</span>
                                        <button @click.stop="openEditTaskModal(t)" class="text-gray-400 hover:text-indigo-500 transition" title="Editar">
                                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"></path></svg>
                                        </button>
                                        <button @click.stop="deleteTask(t.id)" class="text-gray-400 hover:text-red-500 transition" title="Excluir">
                                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                        </button>
                                    </div>
                                </div>
                                <p v-if="t.description" class="line-clamp-2 text-sm text-gray-600 dark:text-zinc-400">{{ t.description }}</p>
                                <div v-if="t.due_date" class="mt-3 inline-flex items-center rounded-full bg-gray-100 px-2.5 py-1 text-[11px] font-medium text-gray-600 dark:bg-zinc-800 dark:text-zinc-300">
                                    Prazo: {{ formatDueDate(t.due_date) }}
                                </div>
                            </div>
                        </template>
                    </draggable>

                    <div v-if="!col.tasks.length" class="mb-4 rounded-lg border border-dashed border-gray-300 bg-white px-4 py-6 text-center text-sm text-gray-500 dark:border-zinc-800 dark:bg-zinc-900/60 dark:text-zinc-400">
                        Solte cartões aqui ou crie uma nova tarefa nesta coluna.
                    </div>

                    <button @click="openTaskModal(col.id)" class="w-full rounded-lg border border-dashed border-gray-300 bg-white py-2 text-sm font-medium text-gray-600 transition hover:bg-gray-50 dark:border-zinc-800 dark:bg-zinc-900 dark:text-zinc-400 dark:hover:bg-zinc-800/50">
                        + Adicionar Cartão
                    </button>
                </div>
            </div>

            <div v-else class="rounded-2xl border border-dashed border-gray-300 bg-white p-8 text-center dark:border-zinc-800 dark:bg-zinc-900/40">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Nenhuma coluna criada ainda</h3>
                <p class="mt-2 text-sm text-gray-500 dark:text-zinc-400">Crie a primeira coluna para começar a organizar suas tarefas.</p>
                <button @click="isColumnModalOpen = true" class="mt-5 inline-flex items-center justify-center rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white transition hover:bg-indigo-700">
                    Criar primeira coluna
                </button>
            </div>

    </div>

    <div v-if="isColumnModalOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-xs">
        <div class="w-full max-w-md p-6 bg-white dark:bg-zinc-900 border border-gray-200 dark:border-zinc-800 rounded-xl shadow-xl">
            <h3 class="text-lg font-bold text-gray-900 dark:text-gray-100 mb-4">Nova Coluna</h3>
            <form @submit.prevent="submitColumn">
                <input v-model="columnForm.name" type="text" class="w-full px-3 py-2 mb-4 border border-gray-300 dark:border-zinc-800 rounded-lg bg-transparent dark:text-white focus:outline-none focus:border-indigo-500" placeholder="Nome da Coluna" required />
                <div class="flex justify-end gap-2">
                    <button type="button" @click="isColumnModalOpen = false" class="px-4 py-2 text-sm text-gray-500 hover:underline">Cancelar</button>
                    <button type="submit" class="px-4 py-2 text-sm font-medium text-white bg-indigo-600 rounded-lg hover:bg-indigo-700">Salvar</button>
                </div>
            </form>
        </div>
    </div>

    <div v-if="isTaskModalOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-xs">
        <div class="w-full max-w-md p-6 bg-white dark:bg-zinc-900 border border-gray-200 dark:border-zinc-800 rounded-xl shadow-xl">
            <h3 class="text-lg font-bold text-gray-900 dark:text-gray-100 mb-4">{{ isEditMode ? 'Editar Tarefa' : 'Nova Tarefa' }}</h3>
            <form @submit.prevent="submitTask">
                <input v-model="taskForm.title" type="text" class="w-full px-3 py-2 mb-3 border border-gray-300 dark:border-zinc-800 rounded-lg bg-transparent dark:text-white focus:outline-none focus:border-indigo-500" placeholder="Título" required />
                <textarea v-model="taskForm.description" class="w-full px-3 py-2 mb-3 border border-gray-300 dark:border-zinc-800 rounded-lg bg-transparent dark:text-white focus:outline-none focus:border-indigo-500" placeholder="Descrição" rows="2"></textarea>
                <div class="grid grid-cols-2 gap-4 mb-4">
                    <select v-model="taskForm.priority" class="w-full px-3 py-2 border border-gray-300 dark:border-zinc-800 rounded-lg bg-white dark:bg-zinc-900 dark:text-white focus:outline-none focus:border-indigo-500">
                        <option value="low">Baixa</option>
                        <option value="medium">Média</option>
                        <option value="high">Alta</option>
                    </select>
                    <input v-model="taskForm.due_date" type="date" class="w-full px-3 py-2 border border-gray-300 dark:border-zinc-800 rounded-lg bg-transparent dark:text-white focus:outline-none focus:border-indigo-500" />
                </div>
                <div class="flex justify-end gap-2">
                    <button type="button" @click="isTaskModalOpen = false" class="px-4 py-2 text-sm text-gray-500 hover:underline">Cancelar</button>
                    <button type="submit" class="px-4 py-2 text-sm font-medium text-white bg-indigo-600 rounded-lg hover:bg-indigo-700">{{ isEditMode ? 'Salvar' : 'Adicionar' }}</button>
                </div>
            </form>
        </div>
    </div>
</template>

<style scoped>
.drop-zone { min-height: 120px; }
</style>
