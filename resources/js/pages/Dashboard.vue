<script setup lang="ts">
import { Head, router, useForm } from '@inertiajs/vue3';
import {
    Plus,
    Trash2,
    Pencil,
    Clock,
    X,
    Kanban,
    CheckSquare,
    AlertTriangle,
    Layers,
    ListTodo,
    GripVertical
} from '@lucide/vue';
import { computed, nextTick, ref, watch } from 'vue';
import { toast } from 'vue-sonner';
import draggable from 'vuedraggable';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
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

const cloneAndSortColumns = (columns: Column[]) => {
    const cloned = JSON.parse(JSON.stringify(columns)) as Column[];

    return cloned.sort((left, right) => left.position - right.position);
};

const localColumns = ref<Column[]>(cloneAndSortColumns(props.columns));

const totalTasks = computed(() => localColumns.value.reduce((count, column) => count + column.tasks.length, 0));
const hasColumns = computed(() => localColumns.value.length > 0);

watch(() => props.columns, (newColumns) => {
    localColumns.value = cloneAndSortColumns(newColumns);
}, { deep: true });

const handleDragChange = async () => {
    await nextTick();

    const payload: { id: number; column_id: number; position: number }[] = [];
    localColumns.value.forEach((column) => {
        column.tasks.forEach((task, index) => {
            payload.push({ id: task.id, column_id: column.id, position: index });
        });
    });
    router.put(tasksRoutes.reorder.url(), { tasks: payload }, {
        preserveState: true,
        preserveScroll: true,
        onSuccess: () => {
            toast.success('Quadro atualizado com sucesso!', {
                description: 'A nova ordem dos cartões foi salva.',
                duration: 2000
            });
        }
    });
};

const handleColumnDragChange = async () => {
    await nextTick();

    const payload = localColumns.value.map((column, index) => ({
        id: column.id,
        position: index,
    }));

    router.put(columnsRoutes.reorder.url(), { columns: payload }, {
        preserveState: true,
        preserveScroll: true,
        onSuccess: () => {
            toast.success('Colunas reordenadas com sucesso!', {
                description: 'A nova disposição das colunas foi salva.',
                duration: 2000
            });
        }
    });
};

const isColumnModalOpen = ref(false);
const isTaskModalOpen = ref(false);
const isEditMode = ref(false);
const editingTaskId = ref<number | null>(null);

// Custom delete confirmation modal state
const isDeleteModalOpen = ref(false);
const deleteType = ref<'column' | 'task' | null>(null);
const deleteTargetId = ref<number | null>(null);
const deleteTargetName = ref('');

const columnForm = useForm({ name: '' });
const taskForm = useForm({ column_id: '', title: '', description: '', priority: 'medium', due_date: '' });

const submitColumn = () => {
    columnForm.post(columnsRoutes.store.url(), {
        onSuccess: () => {
            isColumnModalOpen.value = false;
            columnForm.reset();
            toast.success('Coluna criada com sucesso!');
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
                toast.success('Tarefa atualizada com sucesso!');
            },
        });
    } else {
        taskForm.post(tasksRoutes.store.url(), {
            preserveScroll: true,
            onSuccess: () => {
                isTaskModalOpen.value = false;
                taskForm.reset();
                toast.success('Tarefa criada com sucesso!');
            },
        });
    }
};

const openDeleteConfirm = (type: 'column' | 'task', id: number, name: string) => {
    deleteType.value = type;
    deleteTargetId.value = id;
    deleteTargetName.value = name;
    isDeleteModalOpen.value = true;
};

const executeDelete = () => {
    if (!deleteType.value || deleteTargetId.value === null) {
return;
}

    if (deleteType.value === 'column') {
        router.delete(columnsRoutes.destroy.url(deleteTargetId.value), {
            onSuccess: () => {
                isDeleteModalOpen.value = false;
                toast.success('Coluna excluída com sucesso!');
            }
        });
    } else {
        router.delete(`/tasks/${deleteTargetId.value}`, {
            preserveScroll: true,
            onSuccess: () => {
                isDeleteModalOpen.value = false;
                toast.success('Tarefa excluída com sucesso!');
            }
        });
    }
};

const getPriorityConfig = (priority: string) => {
    switch (priority) {
        case 'high':
            return {
                bg: 'bg-rose-50/70 text-rose-700 border-rose-200/50 dark:bg-rose-950/20 dark:text-rose-400 dark:border-rose-900/30',
                dot: 'bg-rose-500',
                label: 'Alta'
            };
        case 'medium':
            return {
                bg: 'bg-amber-50/70 text-amber-700 border-amber-200/50 dark:bg-amber-950/20 dark:text-amber-400 dark:border-amber-900/30',
                dot: 'bg-amber-500',
                label: 'Média'
            };
        default:
            return {
                bg: 'bg-emerald-50/70 text-emerald-700 border-emerald-200/50 dark:bg-emerald-950/20 dark:text-emerald-400 dark:border-emerald-900/30',
                dot: 'bg-emerald-500',
                label: 'Baixa'
            };
    }
};

const formatDueDate = (dueDate: string | null) => {
    if (!dueDate) {
        return '';
    }

    return new Intl.DateTimeFormat('pt-BR', {
        day: '2-digit',
        month: 'short',
    }).format(new Date(`${dueDate}T00:00:00`));
};

const isOverdue = (dueDate: string | null) => {
    if (!dueDate) {
return false;
}

    const today = new Date();
    today.setHours(0, 0, 0, 0);

    return new Date(`${dueDate}T00:00:00`) < today;
};
</script>

<template>
    <Head title="Cronos Kanban" />
    
    <div class="flex flex-1 flex-col gap-6 p-6 min-h-screen bg-zinc-50/30 dark:bg-zinc-950/10">
        
        <!-- Header Section -->
        <div class="flex flex-col gap-4 border-b border-zinc-200/80 pb-6 dark:border-zinc-800/80 lg:flex-row lg:items-center lg:justify-between">
            <div>
                <div class="flex items-center gap-2">
                    <span class="flex h-6 w-6 items-center justify-center rounded-md bg-indigo-50 text-indigo-600 dark:bg-indigo-950/50 dark:text-indigo-400">
                        <Kanban class="size-4" />
                    </span>
                    <p class="text-xs font-semibold uppercase tracking-[0.2em] text-indigo-600 dark:text-indigo-400">Workspace</p>
                </div>
                <h2 class="mt-1.5 text-3xl font-extrabold tracking-tight text-zinc-900 dark:text-zinc-50">
                    Cronos Kanban
                </h2>
                <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">
                    Gerencie suas atividades com colunas organizadas, prioridades dinâmicas e reordenação inteligente.
                </p>
            </div>
            
            <Button @click="isColumnModalOpen = true" class="bg-indigo-600 hover:bg-indigo-700 text-white font-medium shadow-sm transition-all duration-200 gap-2 shrink-0">
                <Plus class="size-4" />
                Nova Coluna
            </Button>
        </div>

        <!-- Dashboard Stats Grid -->
        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
            <div class="flex items-center gap-4 rounded-xl border border-zinc-200/80 bg-white/60 p-5 shadow-xs backdrop-blur-md transition-all duration-300 hover:shadow-md dark:border-zinc-800/60 dark:bg-zinc-900/40">
                <div class="flex h-12 w-12 items-center justify-center rounded-lg bg-indigo-50 text-indigo-600 dark:bg-indigo-950/40 dark:text-indigo-400">
                    <Layers class="size-6" />
                </div>
                <div>
                    <p class="text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">Colunas</p>
                    <p class="text-2xl font-bold text-zinc-900 dark:text-zinc-50 mt-0.5">{{ localColumns.length }}</p>
                </div>
            </div>
            
            <div class="flex items-center gap-4 rounded-xl border border-zinc-200/80 bg-white/60 p-5 shadow-xs backdrop-blur-md transition-all duration-300 hover:shadow-md dark:border-zinc-800/60 dark:bg-zinc-900/40">
                <div class="flex h-12 w-12 items-center justify-center rounded-lg bg-emerald-50 text-emerald-600 dark:bg-emerald-950/40 dark:text-emerald-400">
                    <CheckSquare class="size-6" />
                </div>
                <div>
                    <p class="text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">Total de Tarefas</p>
                    <p class="text-2xl font-bold text-zinc-900 dark:text-zinc-50 mt-0.5">{{ totalTasks }}</p>
                </div>
            </div>
            
            <div class="flex items-center gap-4 rounded-xl border border-zinc-200/80 bg-linear-to-br from-indigo-600 to-violet-600 p-5 shadow-xs text-white sm:col-span-2 lg:col-span-1">
                <div class="flex h-12 w-12 items-center justify-center rounded-lg bg-white/20 text-white">
                    <ListTodo class="size-6 animate-pulse" />
                </div>
                <div>
                    <p class="text-xs font-medium text-indigo-100 uppercase tracking-wider">Ação Rápida</p>
                    <p class="text-lg font-bold mt-0.5 leading-snug">Arraste e solte para ordenar</p>
                </div>
            </div>
        </div>

        <!-- Kanban Board Horizontal Scroll Container -->
        <div class="flex-1 min-h-0">
            <div v-if="hasColumns" class="flex gap-6 overflow-x-auto pb-6 pt-2 items-start select-none scrollbar-thin scrollbar-thumb-zinc-200 dark:scrollbar-thumb-zinc-800">
                
                <draggable 
                    v-model="localColumns" 
                    group="columns" 
                    item-key="id" 
                    handle=".column-drag-handle"
                    ghost-class="column-ghost" 
                    drag-class="column-drag"
                    class="flex gap-6 items-start"
                    @change="handleColumnDragChange"
                >
                    <template #item="{ element: col }">
                        <div class="w-[320px] shrink-0 flex flex-col rounded-xl border border-zinc-200/80 bg-zinc-100/70 p-4 transition-all duration-300 shadow-xs hover:shadow-md dark:border-zinc-800/80 dark:bg-zinc-900/30 dark:backdrop-blur-md max-h-[75vh]">
                            
                            <!-- Column Header -->
                            <div class="mb-4 flex items-center justify-between">
                                <div class="flex items-center gap-1.5 min-w-0">
                                    <span class="column-drag-handle p-1 text-zinc-400 hover:text-zinc-600 dark:hover:text-zinc-200 cursor-grab active:cursor-grabbing shrink-0 transition duration-150">
                                        <GripVertical class="size-4" />
                                    </span>
                                    <h3 class="font-bold text-zinc-800 dark:text-zinc-200 truncate text-sm tracking-tight">{{ col.name }}</h3>
                                    <Badge variant="secondary" class="bg-zinc-200/60 text-zinc-700 dark:bg-zinc-800 dark:text-zinc-300 font-semibold px-2 py-0.5 text-[10px] rounded-full shrink-0">
                                        {{ col.tasks.length }}
                                    </Badge>
                                </div>
                                
                                <Button @click="openDeleteConfirm('column', col.id, col.name)" variant="ghost" size="icon" class="h-6 w-6 text-zinc-400 hover:text-rose-600 hover:bg-rose-50 dark:hover:bg-rose-950/30 rounded-md transition duration-150 shrink-0">
                                    <X class="size-4" />
                                </Button>
                            </div>

                            <!-- Tasks Draggable Area -->
                            <draggable 
                                v-model="col.tasks" 
                                group="tasks" 
                                item-key="id" 
                                class="mb-4 flex-1 space-y-3 drop-zone overflow-y-auto pr-1 scrollbar-thin scrollbar-thumb-zinc-200 dark:scrollbar-thumb-zinc-800" 
                                ghost-class="kanban-ghost" 
                                drag-class="kanban-drag"
                                @change="handleDragChange"
                            >
                                <template #item="{ element: t }">
                                    <div class="group relative cursor-grab rounded-xl border border-zinc-200 bg-white p-4 shadow-2xs hover:shadow-md hover:border-indigo-500/50 dark:border-zinc-800/60 dark:bg-zinc-900/90 dark:hover:border-indigo-400/50 transition-all duration-200 active:cursor-grabbing">
                                        
                                        <!-- Hover Actions Menu -->
                                        <div class="absolute right-2.5 top-2.5 flex items-center gap-1 opacity-0 group-hover:opacity-100 transition-opacity duration-200 bg-white/90 dark:bg-zinc-900/90 p-1 rounded-md shadow-2xs border border-zinc-200 dark:border-zinc-800 backdrop-blur-xs">
                                            <button @click.stop="openEditTaskModal(t)" class="p-1 text-zinc-400 hover:text-indigo-600 hover:bg-zinc-100 dark:hover:bg-zinc-800 rounded-md transition" title="Editar">
                                                <Pencil class="size-3.5" />
                                            </button>
                                            <button @click.stop="openDeleteConfirm('task', t.id, t.title)" class="p-1 text-zinc-400 hover:text-rose-600 hover:bg-rose-50 dark:hover:bg-rose-950/40 rounded-md transition" title="Excluir">
                                                <Trash2 class="size-3.5" />
                                            </button>
                                        </div>

                                        <!-- Card Title -->
                                        <h4 class="font-semibold text-zinc-900 dark:text-zinc-100 leading-snug mb-1 text-sm pr-12">
                                            {{ t.title }}
                                        </h4>
                                        
                                        <!-- Card Description -->
                                        <p v-if="t.description" class="line-clamp-2 text-xs text-zinc-500 dark:text-zinc-400 mt-1.5 leading-relaxed">
                                            {{ t.description }}
                                        </p>
                                        
                                        <!-- Card Footer Details -->
                                        <div class="mt-4 flex flex-wrap gap-2 items-center">
                                            
                                            <!-- Priority Badge -->
                                            <span :class="['inline-flex items-center gap-1.5 rounded-full px-2 py-0.5 text-[10px] font-semibold border tracking-wider uppercase', getPriorityConfig(t.priority).bg]">
                                                <span :class="['size-1.5 rounded-full', getPriorityConfig(t.priority).dot]" />
                                                {{ getPriorityConfig(t.priority).label }}
                                            </span>

                                            <!-- Due Date Badge -->
                                            <span v-if="t.due_date" :class="[
                                                'inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-[10px] font-semibold border tracking-wide',
                                                isOverdue(t.due_date) 
                                                    ? 'bg-rose-50 text-rose-700 border-rose-200/50 dark:bg-rose-950/20 dark:text-rose-400 dark:border-rose-900/30' 
                                                    : 'bg-zinc-100 text-zinc-600 border-zinc-200/50 dark:bg-zinc-800 dark:text-zinc-300 dark:border-zinc-700/50'
                                            ]">
                                                <Clock class="size-3" />
                                                <span>Prazo: {{ formatDueDate(t.due_date) }}</span>
                                            </span>
                                        </div>
                                    </div>
                                </template>
                            </draggable>

                            <!-- Empty Column Placeholder -->
                            <div v-if="!col.tasks.length" class="mb-4 rounded-xl border-2 border-dashed border-zinc-200 dark:border-zinc-800/80 bg-white/40 dark:bg-zinc-900/20 px-4 py-8 text-center text-xs text-zinc-400 dark:text-zinc-500">
                                Solte cartões aqui ou adicione tarefas.
                            </div>

                            <!-- Add Card Button inside column -->
                            <Button @click="openTaskModal(col.id)" variant="ghost" class="w-full justify-center text-zinc-500 hover:text-indigo-600 dark:text-zinc-400 dark:hover:text-indigo-400 hover:bg-zinc-200/40 dark:hover:bg-zinc-800/40 py-2 border border-dashed border-zinc-300/80 dark:border-zinc-800 rounded-lg text-xs font-semibold gap-1.5 transition-all">
                                <Plus class="size-3.5" />
                                Adicionar Cartão
                            </Button>
                        </div>
                    </template>
                </draggable>

                <!-- Add Column Placeholder Card at the end of columns list -->
                <div @click="isColumnModalOpen = true" class="w-[320px] shrink-0 min-h-[150px] flex flex-col items-center justify-center border-2 border-dashed border-zinc-300 dark:border-zinc-800 hover:border-indigo-500 dark:hover:border-indigo-400 rounded-xl cursor-pointer bg-zinc-50/20 dark:bg-zinc-950/5 hover:bg-zinc-50/60 dark:hover:bg-zinc-950/20 transition-all duration-300 group">
                    <Plus class="size-6 text-zinc-400 group-hover:text-indigo-500 dark:group-hover:text-indigo-400 transition-colors" />
                    <span class="mt-2 text-sm font-semibold text-zinc-500 group-hover:text-indigo-600 dark:group-hover:text-indigo-400">
                        Criar Nova Coluna
                    </span>
                </div>

            </div>

            <!-- Dashboard Entirely Empty State -->
            <div v-else class="flex flex-col items-center justify-center rounded-2xl border-2 border-dashed border-zinc-200 bg-white/50 p-12 text-center dark:border-zinc-800 dark:bg-zinc-900/10 backdrop-blur-md">
                <div class="flex h-16 w-16 items-center justify-center rounded-full bg-indigo-50 text-indigo-600 dark:bg-indigo-950/50 dark:text-indigo-400">
                    <Kanban class="size-8" />
                </div>
                <h3 class="mt-6 text-lg font-bold text-zinc-900 dark:text-zinc-100">
                    Nenhuma coluna criada ainda
                </h3>
                <p class="mt-2 text-sm text-zinc-500 dark:text-zinc-400 max-w-sm">
                    Crie a sua primeira coluna para começar a organizar e acompanhar suas tarefas de maneira eficiente.
                </p>
                <Button @click="isColumnModalOpen = true" class="mt-6 bg-indigo-600 hover:bg-indigo-700 text-white font-medium shadow-sm transition-all duration-200">
                    Criar primeira coluna
                </Button>
            </div>
        </div>
    </div>

    <!-- Modals Section (Using Shadcn components) -->

    <!-- Column Modal -->
    <Dialog v-model:open="isColumnModalOpen">
        <DialogContent class="sm:max-w-[420px] border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-950">
            <DialogHeader>
                <DialogTitle class="text-lg font-bold text-zinc-900 dark:text-zinc-50">Nova Coluna</DialogTitle>
                <DialogDescription class="text-zinc-500 dark:text-zinc-400 text-sm">
                    Dê um nome para a coluna onde os cartões serão organizados.
                </DialogDescription>
            </DialogHeader>
            <form @submit.prevent="submitColumn" class="space-y-4 py-2">
                <div class="space-y-2">
                    <Label for="column-name" class="text-xs font-semibold uppercase tracking-wider text-zinc-500">Nome da Coluna</Label>
                    <Input 
                        id="column-name"
                        v-model="columnForm.name" 
                        type="text" 
                        class="w-full" 
                        placeholder="Ex: A Fazer, Em Progresso..." 
                        required 
                    />
                </div>
                <DialogFooter class="pt-2">
                    <Button type="button" variant="ghost" @click="isColumnModalOpen = false">Cancelar</Button>
                    <Button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white">Salvar Coluna</Button>
                </DialogFooter>
            </form>
        </DialogContent>
    </Dialog>

    <!-- Task Modal -->
    <Dialog v-model:open="isTaskModalOpen">
        <DialogContent class="sm:max-w-[450px] border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-950">
            <DialogHeader>
                <DialogTitle class="text-lg font-bold text-zinc-900 dark:text-zinc-50">
                    {{ isEditMode ? 'Editar Tarefa' : 'Nova Tarefa' }}
                </DialogTitle>
                <DialogDescription class="text-zinc-500 dark:text-zinc-400 text-sm">
                    Preencha os detalhes do cartão no quadro do Kanban.
                </DialogDescription>
            </DialogHeader>
            <form @submit.prevent="submitTask" class="space-y-4 py-2">
                <div class="space-y-2">
                    <Label for="task-title" class="text-xs font-semibold uppercase tracking-wider text-zinc-500">Título</Label>
                    <Input 
                        id="task-title"
                        v-model="taskForm.title" 
                        type="text" 
                        placeholder="O que precisa ser feito?" 
                        required 
                    />
                </div>
                
                <div class="space-y-2">
                    <Label for="task-desc" class="text-xs font-semibold uppercase tracking-wider text-zinc-500">Descrição</Label>
                    <textarea 
                        id="task-desc"
                        v-model="taskForm.description" 
                        class="flex w-full min-h-[80px] rounded-md border border-zinc-200 bg-transparent px-3 py-2 text-sm shadow-xs transition-colors placeholder:text-zinc-400 focus-visible:outline-hidden focus-visible:ring-1 focus-visible:ring-ring dark:border-zinc-800 dark:bg-zinc-900/30 dark:text-zinc-100" 
                        placeholder="Detalhes opcionais da tarefa..."
                        rows="3"
                    ></textarea>
                </div>
                
                <div class="grid grid-cols-2 gap-4">
                    <div class="space-y-2">
                        <Label for="task-priority" class="text-xs font-semibold uppercase tracking-wider text-zinc-500">Prioridade</Label>
                        <select 
                            id="task-priority"
                            v-model="taskForm.priority" 
                            class="flex h-9 w-full rounded-md border border-zinc-200 bg-transparent px-3 py-1 text-sm shadow-xs focus:outline-hidden focus:ring-1 focus:ring-ring dark:border-zinc-800 dark:bg-zinc-950 dark:text-zinc-100"
                        >
                            <option value="low">Baixa</option>
                            <option value="medium">Média</option>
                            <option value="high">Alta</option>
                        </select>
                    </div>
                    <div class="space-y-2">
                        <Label for="task-due" class="text-xs font-semibold uppercase tracking-wider text-zinc-500">Prazo de Entrega</Label>
                        <Input 
                            id="task-due"
                            v-model="taskForm.due_date" 
                            type="date" 
                            class="w-full text-zinc-700 dark:text-zinc-200" 
                        />
                    </div>
                </div>
                
                <DialogFooter class="pt-4">
                    <Button type="button" variant="ghost" @click="isTaskModalOpen = false">Cancelar</Button>
                    <Button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white">
                        {{ isEditMode ? 'Salvar Alterações' : 'Criar Cartão' }}
                    </Button>
                </DialogFooter>
            </form>
        </DialogContent>
    </Dialog>

    <!-- Custom Delete Confirmation Dialog -->
    <Dialog v-model:open="isDeleteModalOpen">
        <DialogContent class="sm:max-w-[400px] border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-950">
            <DialogHeader class="flex flex-col items-center text-center">
                <div class="flex h-12 w-12 items-center justify-center rounded-full bg-rose-50 text-rose-600 dark:bg-rose-950/40 dark:text-rose-400 mb-2">
                    <AlertTriangle class="size-6" />
                </div>
                <DialogTitle class="text-lg font-bold text-zinc-900 dark:text-zinc-50">Excluir Item?</DialogTitle>
                <DialogDescription class="text-zinc-500 dark:text-zinc-400 text-sm mt-1">
                    Você tem certeza que deseja excluir <strong>"{{ deleteTargetName }}"</strong>? 
                    <span v-if="deleteType === 'column'" class="block mt-1.5 text-rose-600 dark:text-rose-400 font-medium text-xs">
                        Esta ação excluirá permanentemente esta coluna e todas as suas tarefas vinculadas!
                    </span>
                    <span v-else class="block mt-1.5 text-zinc-500 dark:text-zinc-400 text-xs">
                        Esta ação não pode ser desfeita.
                    </span>
                </DialogDescription>
            </DialogHeader>
            <DialogFooter class="mt-4 flex sm:justify-center gap-2">
                <Button type="button" variant="ghost" @click="isDeleteModalOpen = false" class="flex-1">Cancelar</Button>
                <Button type="button" variant="destructive" @click="executeDelete" class="flex-1">Excluir</Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>

<style scoped>
.drop-zone { min-height: 150px; }
.kanban-ghost {
    opacity: 0.3 !important;
    transform: scale(0.98) rotate(1deg);
}
.kanban-drag {
    box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05) !important;
    opacity: 0.9 !important;
    transform: scale(1.02);
}
.column-ghost {
    opacity: 0.2 !important;
    transform: scale(0.98);
}
.column-drag {
    opacity: 0.9 !important;
    box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.15), 0 10px 10px -5px rgba(0, 0, 0, 0.05) !important;
}
</style>

