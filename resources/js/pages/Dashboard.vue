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
    GripVertical,
    Search,
    Archive,
    Download,
    Upload,
    BarChart3,
    ChevronLeft,
    ChevronRight,
    Calendar
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
import subtasksRoutes from '@/routes/subtasks';
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

interface Tag {
    id: number;
    name: string;
    color: string;
}

interface Subtask {
    id: number;
    task_id: number;
    title: string;
    is_completed: boolean;
    position: number;
}

interface Task {
    id: number;
    column_id: number;
    title: string;
    description: string | null;
    priority: 'low' | 'medium' | 'high';
    due_date: string | null;
    position: number;
    subtasks?: Subtask[];
    tags?: Tag[];
}

interface Column {
    id: number;
    name: string;
    position: number;
    tasks: Task[];
}

const props = defineProps<{
    columns: Column[];
    tags: Tag[];
    archivedTasks: Task[];
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
const taskForm = useForm({
    column_id: '',
    title: '',
    description: '',
    priority: 'medium',
    due_date: '',
    tag_ids: [] as number[],
});

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
    taskForm.tag_ids = [];
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
    taskForm.tag_ids = task.tags ? task.tags.map(t => t.id) : [];
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


const isOverdue = (dueDate: string | null) => {
    if (!dueDate) {
        return false;
    }

    const today = new Date();
    today.setHours(0, 0, 0, 0);

    return new Date(`${dueDate}T00:00:00`) < today;
};

const searchQuery = ref('');
const selectedPriority = ref<'all' | 'low' | 'medium' | 'high'>('all');
const selectedFilterTags = ref<number[]>([]);

const getTagColorConfig = (color: string) => {
    const colors: Record<string, { bg: string; text: string; border: string }> = {
        red: {
            bg: 'bg-rose-50/70 text-rose-700 border-rose-200/50 dark:bg-rose-950/20 dark:text-rose-400 dark:border-rose-900/30',
            text: 'text-rose-700 dark:text-rose-400',
            border: 'border-rose-200/50 dark:border-rose-900/30',
        },
        blue: {
            bg: 'bg-blue-50/70 text-blue-700 border-blue-200/50 dark:bg-blue-950/20 dark:text-blue-400 dark:border-blue-900/30',
            text: 'text-blue-700 dark:text-blue-400',
            border: 'border-blue-200/50 dark:border-blue-900/30',
        },
        green: {
            bg: 'bg-emerald-50/70 text-emerald-700 border-emerald-200/50 dark:bg-emerald-950/20 dark:text-emerald-400 dark:border-emerald-900/30',
            text: 'text-emerald-700 dark:text-emerald-400',
            border: 'border-emerald-200/50 dark:border-emerald-900/30',
        },
        amber: {
            bg: 'bg-amber-50/70 text-amber-700 border-amber-200/50 dark:bg-amber-950/20 dark:text-amber-400 dark:border-amber-900/30',
            text: 'text-amber-700 dark:text-amber-400',
            border: 'border-amber-200/50 dark:border-amber-900/30',
        },
        purple: {
            bg: 'bg-purple-50/70 text-purple-700 border-purple-200/50 dark:bg-purple-950/20 dark:text-purple-400 dark:border-purple-900/30',
            text: 'text-purple-700 dark:text-purple-400',
            border: 'border-purple-200/50 dark:border-purple-900/30',
        },
        orange: {
            bg: 'bg-orange-50/70 text-orange-700 border-orange-200/50 dark:bg-orange-950/20 dark:text-orange-400 dark:border-orange-900/30',
            text: 'text-orange-700 dark:text-orange-400',
            border: 'border-orange-200/50 dark:border-orange-900/30',
        },
        pink: {
            bg: 'bg-pink-50/70 text-pink-700 border-pink-200/50 dark:bg-pink-950/20 dark:text-pink-400 dark:border-pink-900/30',
            text: 'text-pink-700 dark:text-pink-400',
            border: 'border-pink-200/50 dark:border-pink-900/30',
        },
        indigo: {
            bg: 'bg-indigo-50/70 text-indigo-700 border-indigo-200/50 dark:bg-indigo-950/20 dark:text-indigo-400 dark:border-indigo-900/30',
            text: 'text-indigo-700 dark:text-indigo-400',
            border: 'border-indigo-200/50 dark:border-indigo-900/30',
        },
        gray: {
            bg: 'bg-zinc-100 text-zinc-700 border-zinc-200 dark:bg-zinc-950/20 dark:text-zinc-400 dark:border-zinc-900/30',
            text: 'text-zinc-700 dark:text-zinc-400',
            border: 'border-zinc-200/50 dark:border-zinc-900/30',
        },
    };

    return colors[color] || colors.gray;
};

const toggleTagFilter = (tagId: number) => {
    const index = selectedFilterTags.value.indexOf(tagId);

    if (index > -1) {
        selectedFilterTags.value.splice(index, 1);
    } else {
        selectedFilterTags.value.push(tagId);
    }
};

const newTagName = ref('');
const newTagColor = ref('blue');
const isCreatingTag = ref(false);

const colorOptions = [
    { name: 'red', bg: 'bg-rose-500', label: 'Vermelho' },
    { name: 'blue', bg: 'bg-blue-500', label: 'Azul' },
    { name: 'green', bg: 'bg-emerald-500', label: 'Verde' },
    { name: 'amber', bg: 'bg-amber-500', label: 'Amarelo' },
    { name: 'purple', bg: 'bg-purple-500', label: 'Roxo' },
    { name: 'orange', bg: 'bg-orange-500', label: 'Laranja' },
    { name: 'pink', bg: 'bg-pink-500', label: 'Rosa' },
    { name: 'indigo', bg: 'bg-indigo-500', label: 'Índigo' },
    { name: 'gray', bg: 'bg-zinc-500', label: 'Cinza' },
];

const createCustomTag = () => {
    if (!newTagName.value.trim()) {
        toast.error('Informe um nome para a etiqueta.');

        return;
    }

    isCreatingTag.value = true;
    router.post('/tags', {
        name: newTagName.value.trim(),
        color: newTagColor.value,
    }, {
        preserveScroll: true,
        onSuccess: () => {
            newTagName.value = '';
            toast.success('Etiqueta criada com sucesso!');
            isCreatingTag.value = false;
        },
        onError: () => {
            toast.error('Erro ao criar etiqueta.');
            isCreatingTag.value = false;
        }
    });
};

const deleteSystemTag = (tag: Tag) => {
    if (confirm(`Tem certeza que deseja excluir permanentemente a etiqueta "${tag.name}"? Isso removerá a etiqueta de todas as tarefas.`)) {
        router.delete(`/tags/${tag.id}`, {
            preserveScroll: true,
            onSuccess: () => {
                toast.success('Etiqueta excluída!');
                const index = taskForm.tag_ids.indexOf(tag.id);

                if (index > -1) {
                    taskForm.tag_ids.splice(index, 1);
                }

                const filterIndex = selectedFilterTags.value.indexOf(tag.id);

                if (filterIndex > -1) {
                    selectedFilterTags.value.splice(filterIndex, 1);
                }
            }
        });
    }
};

const toggleFormTag = (tagId: number) => {
    const idx = taskForm.tag_ids.indexOf(tagId);

    if (idx > -1) {
        taskForm.tag_ids.splice(idx, 1);
    } else {
        taskForm.tag_ids.push(tagId);
    }
};

const isArchiveModalOpen = ref(false);
const filterOverdueOnly = ref(false);

const getFriendlyDueDate = (dueDate: string | null) => {
    if (!dueDate) {
return { text: '', class: '' };
}

    const today = new Date();
    today.setHours(0, 0, 0, 0);

    const due = new Date(`${dueDate}T00:00:00`);
    due.setHours(0, 0, 0, 0);

    const diffTime = due.getTime() - today.getTime();
    const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));

    if (diffDays === 0) {
        return { text: 'Vence hoje', class: 'bg-amber-50 text-amber-700 border-amber-200/50 dark:bg-amber-950/20 dark:text-amber-400 dark:border-amber-900/30' };
    } else if (diffDays === 1) {
        return { text: 'Vence amanhã', class: 'bg-indigo-50 text-indigo-700 border-indigo-200/50 dark:bg-indigo-950/20 dark:text-indigo-400 dark:border-indigo-900/30' };
    } else if (diffDays === -1) {
        return { text: 'Venceu ontem', class: 'bg-rose-50 text-rose-700 border-rose-200/50 dark:bg-rose-950/20 dark:text-rose-400 dark:border-rose-900/30' };
    } else if (diffDays < -1) {
        return { text: `Atrasado há ${Math.abs(diffDays)} dias`, class: 'bg-rose-50 text-rose-700 border-rose-200/50 dark:bg-rose-950/20 dark:text-rose-400 dark:border-rose-900/30 font-bold' };
    } else if (diffDays <= 7) {
        return { text: `Vence em ${diffDays} dias`, class: 'bg-zinc-150 text-zinc-650 border-zinc-250 dark:bg-zinc-950/20 dark:text-zinc-350 dark:border-zinc-900/30' };
    } else {
        const formatted = new Intl.DateTimeFormat('pt-BR', {
            day: '2-digit',
            month: 'short',
        }).format(due);

        return { text: `Prazo: ${formatted}`, class: 'bg-zinc-100 text-zinc-550 border-zinc-200/50 dark:bg-zinc-950/20 dark:text-zinc-400 dark:border-zinc-900/30' };
    }
};

const archiveTask = (task: Task) => {
    router.put(`/tasks/${task.id}/archive`, {}, {
        preserveScroll: true,
        onSuccess: () => {
            toast.success('Tarefa arquivada!', {
                description: 'Você pode encontrá-la no painel de arquivadas.',
                duration: 2000
            });
        }
    });
};

const restoreTask = (task: Task) => {
    router.put(`/tasks/${task.id}/restore`, {}, {
        preserveScroll: true,
        onSuccess: () => {
            toast.success('Tarefa restaurada com sucesso!');
        }
    });
};

const isMetricsModalOpen = ref(false);

const priorityStats = computed(() => {
    let low = 0, medium = 0, high = 0;
    localColumns.value.forEach(column => {
        column.tasks.forEach(task => {
            if (task.priority === 'low') {
low++;
} else if (task.priority === 'medium') {
medium++;
} else if (task.priority === 'high') {
high++;
}
        });
    });
    const total = low + medium + high || 1;

    return {
        low,
        medium,
        high,
        lowPct: Math.round((low / total) * 100),
        mediumPct: Math.round((medium / total) * 100),
        highPct: Math.round((high / total) * 100),
    };
});

const subtaskStats = computed(() => {
    let total = 0;
    let completed = 0;
    localColumns.value.forEach(column => {
        column.tasks.forEach(task => {
            if (task.subtasks) {
                total += task.subtasks.length;
                completed += task.subtasks.filter(s => s.is_completed).length;
            }
        });
    });

    return {
        total,
        completed,
        pct: total > 0 ? Math.round((completed / total) * 100) : 0,
    };
});

const overdueStats = computed(() => {
    let count = 0;
    localColumns.value.forEach(column => {
        column.tasks.forEach(task => {
            if (task.due_date && isOverdue(task.due_date)) {
                count++;
            }
        });
    });

    return count;
});

const fileInput = ref<HTMLInputElement | null>(null);
const isImporting = ref(false);

const exportBoard = () => {
    const dataStr = JSON.stringify({
        columns: props.columns,
        tags: props.tags
    }, null, 2);
    
    const dataUri = 'data:application/json;charset=utf-8,'+ encodeURIComponent(dataStr);
    const exportFileDefaultName = `cronos-kanban-backup-${new Date().toISOString().split('T')[0]}.json`;
    
    const linkElement = document.createElement('a');
    linkElement.setAttribute('href', dataUri);
    linkElement.setAttribute('download', exportFileDefaultName);
    linkElement.click();
    
    toast.success('Quadro exportado com sucesso!', {
        description: 'Seu arquivo de backup foi baixado.'
    });
};

const triggerImportFile = () => {
    if (fileInput.value) {
        fileInput.value.click();
    }
};

const handleImportFile = (event: Event) => {
    const target = event.target as HTMLInputElement;

    if (!target.files || target.files.length === 0) {
return;
}
    
    const file = target.files[0];

    if (file.type !== 'application/json' && !file.name.endsWith('.json')) {
        toast.error('Por favor, envie um arquivo .json válido.');

        return;
    }
    
    if (!confirm('Atenção: A importação irá substituir completamente todas as colunas, tarefas e etiquetas atuais. Deseja prosseguir?')) {
        target.value = ''; // Reset input

        return;
    }
    
    isImporting.value = true;
    const formData = new FormData();
    formData.append('file', file);
    
    router.post('/board/import', formData, {
        onSuccess: () => {
            toast.success('Quadro restaurado com sucesso!', {
                description: 'Todos os dados foram atualizados do backup.'
            });
            isImporting.value = false;
            target.value = '';
        },
        onError: (err) => {
            const message = err.file || 'Erro ao processar importação.';
            toast.error(message);
            isImporting.value = false;
            target.value = '';
        }
    });
};

const matchesFilter = (task: Task) => {
    if (searchQuery.value) {
        const query = searchQuery.value.toLowerCase();
        const matchesTitle = task.title.toLowerCase().includes(query);
        const matchesDesc = task.description ? task.description.toLowerCase().includes(query) : false;

        if (!matchesTitle && !matchesDesc) {
            return false;
        }
    }

    if (selectedPriority.value !== 'all') {
        if (task.priority !== selectedPriority.value) {
            return false;
        }
    }

    if (selectedFilterTags.value.length > 0) {
        if (!task.tags || !task.tags.some(tag => selectedFilterTags.value.includes(tag.id))) {
            return false;
        }
    }

    if (filterOverdueOnly.value) {
        if (!task.due_date || !isOverdue(task.due_date)) {
            return false;
        }
    }

    return true;
};

const filteredTasksCount = computed(() => {
    return localColumns.value.reduce((count, column) => {
        return count + column.tasks.filter(matchesFilter).length;
    }, 0);
});

const newSubtaskTitle = ref('');

const currentTaskSubtasks = computed<Subtask[]>(() => {
    if (!editingTaskId.value) {
return [];
}

    for (const column of localColumns.value) {
        const task = column.tasks.find(t => t.id === editingTaskId.value);

        if (task) {
            return task.subtasks || [];
        }
    }

    return [];
});

const handleAddSubtask = () => {
    if (!newSubtaskTitle.value.trim() || !editingTaskId.value) {
return;
}

    router.post(subtasksRoutes.store.url(), {
        task_id: editingTaskId.value,
        title: newSubtaskTitle.value.trim()
    }, {
        preserveScroll: true,
        onSuccess: () => {
            newSubtaskTitle.value = '';
            toast.success('Subtarefa adicionada!');
        }
    });
};

const toggleSubtask = (subtask: Subtask) => {
    router.put(subtasksRoutes.update.url(subtask.id), {
        is_completed: !subtask.is_completed
    }, {
        preserveScroll: true,
        onSuccess: () => {
            toast.success(subtask.is_completed ? 'Subtarefa desmarcada!' : 'Subtarefa concluída!');
        }
    });
};

const deleteSubtask = (id: number) => {
    router.delete(subtasksRoutes.destroy.url(id), {
        preserveScroll: true,
        onSuccess: () => {
            toast.success('Subtarefa removida!');
        }
    });
};

const getCompletedSubtasksCount = (task: Task) => {
    if (!task.subtasks) {
        return 0;
    }

    return task.subtasks.filter((s: Subtask) => s.is_completed).length;
};

const currentView = ref<'kanban' | 'calendar'>('kanban');
const calendarDate = ref(new Date());

const currentYear = computed(() => calendarDate.value.getFullYear());
const currentMonth = computed(() => calendarDate.value.getMonth());

const monthNames = [
    'Janeiro', 'Fevereiro', 'Março', 'Abril', 'Maio', 'Junho',
    'Julho', 'Agosto', 'Setembro', 'Outubro', 'Novembro', 'Dezembro'
];

const nextMonth = () => {
    calendarDate.value = new Date(currentYear.value, currentMonth.value + 1, 1);
};

const prevMonth = () => {
    calendarDate.value = new Date(currentYear.value, currentMonth.value - 1, 1);
};

const goToToday = () => {
    calendarDate.value = new Date();
};

const getTasksForDate = (dateStr: string) => {
    const tasks: Task[] = [];
    localColumns.value.forEach(column => {
        column.tasks.forEach(task => {
            if (task.due_date === dateStr) {
                tasks.push(task);
            }
        });
    });

    return tasks.filter(matchesFilter);
};

const calendarDays = computed(() => {
    const year = currentYear.value;
    const month = currentMonth.value;

    const firstDay = new Date(year, month, 1);
    const startDayOfWeek = firstDay.getDay();

    const totalDays = new Date(year, month + 1, 0).getDate();
    const prevMonthTotalDays = new Date(year, month, 0).getDate();

    const days: { dateStr: string; dayNum: number; isCurrentMonth: boolean; tasks: Task[] }[] = [];

    // Add days of the previous month
    for (let i = startDayOfWeek - 1; i >= 0; i--) {
        const d = prevMonthTotalDays - i;
        const prevMonthDate = new Date(year, month - 1, d);
        const dateStr = prevMonthDate.toISOString().split('T')[0];
        days.push({
            dateStr,
            dayNum: d,
            isCurrentMonth: false,
            tasks: getTasksForDate(dateStr)
        });
    }

    // Add days of the current month
    for (let d = 1; d <= totalDays; d++) {
        const currentDate = new Date(year, month, d);
        const dateStr = currentDate.toISOString().split('T')[0];
        days.push({
            dateStr,
            dayNum: d,
            isCurrentMonth: true,
            tasks: getTasksForDate(dateStr)
        });
    }

    // Fill the rest with days of the next month
    const remainingCells = 42 - days.length;

    for (let d = 1; d <= remainingCells; d++) {
        const nextMonthDate = new Date(year, month + 1, d);
        const dateStr = nextMonthDate.toISOString().split('T')[0];
        days.push({
            dateStr,
            dayNum: d,
            isCurrentMonth: false,
            tasks: getTasksForDate(dateStr)
        });
    }

    return days;
});

const isDateToday = (dateStr: string) => {
    const todayStr = new Date().toISOString().split('T')[0];

    return dateStr === todayStr;
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
                    <p class="text-xs font-semibold uppercase tracking-[0.2em] text-indigo-600 dark:text-indigo-400">Espaço de Trabalho</p>
                </div>
                <h2 class="mt-1.5 text-3xl font-extrabold tracking-tight text-zinc-900 dark:text-zinc-50">
                    Cronos Kanban
                </h2>
                <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">
                    Gerencie suas atividades com colunas organizadas, prioridades dinâmicas e reordenação inteligente.
                </p>
            </div>
            
            <div class="flex flex-wrap items-center gap-3 shrink-0">
                <!-- Hidden file input for import -->
                <input 
                    type="file" 
                    ref="fileInput" 
                    class="hidden" 
                    accept=".json,application/json" 
                    @change="handleImportFile" 
                />

                <Button @click="exportBoard" variant="outline" class="border-zinc-200 hover:bg-zinc-100 text-zinc-700 dark:border-zinc-800 dark:text-zinc-300 dark:hover:bg-zinc-950/40 font-medium transition-all duration-200 gap-2">
                    <Download class="size-4" />
                    Exportar
                </Button>

                <Button @click="triggerImportFile" variant="outline" class="border-zinc-200 hover:bg-zinc-100 text-zinc-700 dark:border-zinc-800 dark:text-zinc-300 dark:hover:bg-zinc-950/40 font-medium transition-all duration-200 gap-2" :disabled="isImporting">
                    <Upload class="size-4 animate-bounce" v-if="isImporting" />
                    <Upload class="size-4" v-else />
                    Importar
                </Button>

                <Button @click="isMetricsModalOpen = true" variant="outline" class="border-zinc-200 hover:bg-zinc-100 text-zinc-700 dark:border-zinc-800 dark:text-zinc-300 dark:hover:bg-zinc-950/40 font-medium transition-all duration-200 gap-2">
                    <BarChart3 class="size-4" />
                    Métricas
                </Button>

                <Button @click="isArchiveModalOpen = true" variant="outline" class="border-zinc-200 hover:bg-zinc-100 text-zinc-700 dark:border-zinc-800 dark:text-zinc-300 dark:hover:bg-zinc-950/40 font-medium transition-all duration-200 gap-2">
                    <Archive class="size-4" />
                    Arquivadas
                    <Badge v-if="props.archivedTasks.length" variant="secondary" class="ml-1 bg-zinc-200 text-zinc-700 dark:bg-zinc-800 dark:text-zinc-300 font-bold text-[10px] rounded-full px-1.5 py-0.2">
                        {{ props.archivedTasks.length }}
                    </Badge>
                </Button>

                <Button @click="isColumnModalOpen = true" class="bg-indigo-600 hover:bg-indigo-700 text-white font-medium shadow-sm transition-all duration-200 gap-2">
                    <Plus class="size-4" />
                    Nova Coluna
                </Button>
            </div>
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
                    <p class="text-2xl font-bold text-zinc-900 dark:text-zinc-50 mt-0.5">
                        <span v-if="searchQuery || selectedPriority !== 'all'">{{ filteredTasksCount }} / </span>{{ totalTasks }}
                    </p>
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

        <!-- Search and Filters Bar -->
        <div v-if="hasColumns" class="flex flex-col gap-4 rounded-xl border border-zinc-200/80 bg-white/60 p-4 shadow-2xs backdrop-blur-md dark:border-zinc-800/60 dark:bg-zinc-900/40">
            <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                <div class="relative flex-1 max-w-md">
                    <span class="absolute inset-y-0 left-3 flex items-center text-zinc-400 dark:text-zinc-500">
                        <Search class="size-4" />
                    </span>
                    <Input
                        v-model="searchQuery"
                        placeholder="Buscar tarefas pelo título ou descrição..."
                        class="pl-9 h-9 border-zinc-200 focus:border-indigo-500 focus:ring-indigo-500 dark:border-zinc-800 dark:bg-zinc-950/50"
                    />
                    <button
                        v-if="searchQuery"
                        @click="searchQuery = ''"
                        class="absolute inset-y-0 right-3 flex items-center text-zinc-400 hover:text-zinc-600 dark:hover:text-zinc-200"
                    >
                        <X class="size-4" />
                    </button>
                </div>
                
                <div class="flex flex-wrap items-center gap-3">
                    <span class="text-xs font-semibold text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">Visualização:</span>
                    <div class="flex gap-1 bg-zinc-100 dark:bg-zinc-800 p-0.5 rounded-lg border border-zinc-200/50 dark:border-zinc-700/50">
                        <button
                            @click="currentView = 'kanban'"
                            :class="[
                                'px-3 py-1 text-xs font-medium rounded-md transition duration-150 flex items-center gap-1.5 cursor-pointer',
                                currentView === 'kanban'
                                    ? 'bg-white text-zinc-900 shadow-2xs dark:bg-zinc-700 dark:text-zinc-100'
                                    : 'text-zinc-500 hover:text-zinc-800 dark:text-zinc-400 dark:hover:text-zinc-200'
                            ]"
                        >
                            <Kanban class="size-3.5" />
                            Quadro
                        </button>
                        <button
                            @click="currentView = 'calendar'"
                            :class="[
                                'px-3 py-1 text-xs font-medium rounded-md transition duration-150 flex items-center gap-1.5 cursor-pointer',
                                currentView === 'calendar'
                                    ? 'bg-white text-zinc-900 shadow-2xs dark:bg-zinc-700 dark:text-zinc-100'
                                    : 'text-zinc-500 hover:text-zinc-800 dark:text-zinc-400 dark:hover:text-zinc-200'
                            ]"
                        >
                            <Calendar class="size-3.5" />
                            Calendário
                        </button>
                    </div>

                    <span class="text-xs font-semibold text-zinc-500 dark:text-zinc-400 uppercase tracking-wider ml-2">Filtrar por Prioridade:</span>
                    <div class="flex gap-1 bg-zinc-100 dark:bg-zinc-800 p-0.5 rounded-lg border border-zinc-200/50 dark:border-zinc-700/50">
                        <button
                            v-for="p in ['all', 'low', 'medium', 'high'] as const"
                            :key="p"
                            @click="selectedPriority = p"
                            :class="[
                                'px-3 py-1 text-xs font-medium rounded-md transition duration-150',
                                selectedPriority === p
                                    ? 'bg-white text-zinc-900 shadow-2xs dark:bg-zinc-700 dark:text-zinc-100'
                                    : 'text-zinc-500 hover:text-zinc-800 dark:text-zinc-400 dark:hover:text-zinc-200'
                            ]"
                        >
                            {{ p === 'all' ? 'Todas' : p === 'low' ? 'Baixa' : p === 'medium' ? 'Média' : 'Alta' }}
                        </button>
                    </div>

                    <!-- Overdue filter button -->
                    <button
                        @click="filterOverdueOnly = !filterOverdueOnly"
                        :class="[
                            'px-3 py-1.5 text-xs font-semibold rounded-lg border transition-all duration-250 cursor-pointer select-none',
                            filterOverdueOnly
                                ? 'bg-rose-50 text-rose-700 border-rose-200 dark:bg-rose-950/20 dark:text-rose-400 dark:border-rose-900/30 ring-2 ring-rose-500/20 scale-102'
                                : 'bg-zinc-50 text-zinc-500 border-zinc-200 hover:bg-zinc-100 dark:bg-zinc-900 dark:text-zinc-400 dark:border-zinc-800 dark:hover:bg-zinc-800'
                        ]"
                    >
                        ⚠️ Apenas Vencidos
                    </button>
                </div>
            </div>

            <!-- Tags Filter Row -->
            <div v-if="props.tags.length > 0" class="flex flex-wrap items-center gap-2 pt-3 border-t border-zinc-200/60 dark:border-zinc-800/80">
                <span class="text-xs font-semibold text-zinc-500 dark:text-zinc-400 uppercase tracking-wider mr-1">Filtrar por Etiquetas:</span>
                <button
                    v-for="tag in props.tags"
                    :key="tag.id"
                    @click="toggleTagFilter(tag.id)"
                    :class="[
                        'inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold border transition-all duration-150 cursor-pointer select-none',
                        selectedFilterTags.includes(tag.id)
                            ? `${getTagColorConfig(tag.color).bg} ring-2 ring-indigo-500/50 ring-offset-1 dark:ring-offset-zinc-900 scale-105`
                            : 'bg-zinc-50 text-zinc-500 border-zinc-200 hover:bg-zinc-100 dark:bg-zinc-900 dark:text-zinc-400 dark:border-zinc-800 dark:hover:bg-zinc-800'
                    ]"
                >
                    {{ tag.name }}
                </button>
                <button
                    v-if="selectedFilterTags.length > 0"
                    @click="selectedFilterTags = []"
                    class="text-xs text-indigo-600 hover:text-indigo-800 dark:text-indigo-400 dark:hover:text-indigo-300 font-medium transition cursor-pointer select-none ml-2"
                >
                </button>
            </div>
        </div>

        <!-- Kanban / Calendar Board Container -->
        <div class="flex-1 min-h-0">
            <template v-if="hasColumns">
                <!-- Kanban View -->
                <div v-if="currentView === 'kanban'" class="flex gap-6 overflow-x-auto pb-6 pt-2 items-start select-none scrollbar-thin scrollbar-thumb-zinc-200 dark:scrollbar-thumb-zinc-800">
                    
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
                                            <span v-if="searchQuery || selectedPriority !== 'all'">{{ col.tasks.filter(matchesFilter).length }} / </span>{{ col.tasks.length }}
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
                                        <div v-show="matchesFilter(t)" class="group relative cursor-grab rounded-xl border border-zinc-200 bg-white p-4 shadow-2xs hover:shadow-md hover:border-indigo-500/50 dark:border-zinc-800/60 dark:bg-zinc-900/90 dark:hover:border-indigo-400/50 transition-all duration-200 active:cursor-grabbing">
                                            
                                            <!-- Hover Actions Menu -->
                                            <div class="absolute right-2.5 top-2.5 flex items-center gap-1 opacity-0 group-hover:opacity-100 transition-opacity duration-200 bg-white/90 dark:bg-zinc-900/90 p-1 rounded-md shadow-2xs border border-zinc-200 dark:border-zinc-800 backdrop-blur-xs">
                                                <button @click.stop="openEditTaskModal(t)" class="p-1 text-zinc-400 hover:text-indigo-600 hover:bg-zinc-100 dark:hover:bg-zinc-800 rounded-md transition" title="Editar">
                                                    <Pencil class="size-3.5" />
                                                </button>
                                                <button @click.stop="archiveTask(t)" class="p-1 text-zinc-400 hover:text-amber-650 hover:bg-zinc-150 dark:hover:bg-zinc-800 rounded-md transition" title="Arquivar">
                                                    <Archive class="size-3.5" />
                                                </button>
                                                <button @click.stop="openDeleteConfirm('task', t.id, t.title)" class="p-1 text-zinc-400 hover:text-rose-600 hover:bg-rose-50 dark:hover:bg-rose-950/40 rounded-md transition" title="Excluir">
                                                    <Trash2 class="size-3.5" />
                                                </button>
                                            </div>

                                            <!-- Card Tags -->
                                            <div v-if="t.tags && t.tags.length" class="flex flex-wrap gap-1 mb-2 pr-12">
                                                <span 
                                                    v-for="tag in t.tags" 
                                                    :key="tag.id"
                                                    :class="['inline-flex items-center rounded-md px-1.5 py-0.5 text-[10px] font-semibold border', getTagColorConfig(tag.color).bg]"
                                                >
                                                    {{ tag.name }}
                                                </span>
                                            </div>

                                            <!-- Card Title -->
                                            <h4 class="font-semibold text-zinc-900 dark:text-zinc-100 leading-snug mb-1 text-sm pr-12">
                                                {{ t.title }}
                                            </h4>
                                            
                                            <!-- Card Description -->
                                            <p v-if="t.description" class="line-clamp-2 text-xs text-zinc-500 dark:text-zinc-400 mt-1.5 leading-relaxed">
                                                {{ t.description }}
                                            </p>
                                            
                                            <!-- Subtask mini progress bar -->
                                            <div v-if="t.subtasks && t.subtasks.length" class="mt-2.5 w-full bg-zinc-100 dark:bg-zinc-800 rounded-full h-1 overflow-hidden">
                                                <div 
                                                    class="bg-indigo-600 dark:bg-indigo-500 h-1 rounded-full transition-all duration-300"
                                                    :style="{ width: `${(getCompletedSubtasksCount(t) / t.subtasks.length) * 100}%` }"
                                                />
                                            </div>

                                            <!-- Subtasks List on Card -->
                                            <div v-if="t.subtasks && t.subtasks.length" class="mt-3.5 space-y-1.5 border-t border-zinc-150 dark:border-zinc-800/60 pt-2.5">
                                                <div 
                                                    v-for="sub in t.subtasks" 
                                                    :key="sub.id" 
                                                    class="flex items-center gap-2 text-xs"
                                                    @click.stop
                                                >
                                                    <input 
                                                        type="checkbox" 
                                                        :checked="sub.is_completed" 
                                                        @change="toggleSubtask(sub)"
                                                        class="rounded border-zinc-350 text-indigo-600 focus:ring-indigo-500 cursor-pointer h-3.5 w-3.5"
                                                    />
                                                    <span 
                                                        :class="['truncate select-none cursor-pointer flex-1 transition duration-150', sub.is_completed ? 'line-through text-zinc-400 dark:text-zinc-500' : 'text-zinc-700 dark:text-zinc-300 hover:text-indigo-600 dark:hover:text-indigo-400']"
                                                        @click="toggleSubtask(sub)"
                                                    >
                                                        {{ sub.title }}
                                                    </span>
                                                </div>
                                            </div>
                                            
                                            <!-- Card Footer Details -->
                                            <div class="mt-4 flex flex-wrap gap-2 items-center">
                                                
                                                <!-- Priority Badge -->
                                                <span :class="['inline-flex items-center gap-1.5 rounded-full px-2 py-0.5 text-[10px] font-semibold border tracking-wider uppercase', getPriorityConfig(t.priority).bg]">
                                                    <span :class="['size-1.5 rounded-full', getPriorityConfig(t.priority).dot]" />
                                                    {{ getPriorityConfig(t.priority).label }}
                                                </span>

                                                <!-- Checklist Progress Badge -->
                                                <span v-if="t.subtasks && t.subtasks.length" class="inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-[10px] font-semibold border bg-indigo-50/70 text-indigo-700 border-indigo-200/50 dark:bg-indigo-950/20 dark:text-indigo-400 dark:border-indigo-900/30 tracking-wide">
                                                    <CheckSquare class="size-3" />
                                                    <span>{{ getCompletedSubtasksCount(t) }}/{{ t.subtasks.length }}</span>
                                                </span>
                                                
                                                <!-- Due Date Badge -->
                                                <span v-if="t.due_date" :class="['inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-[10px] font-semibold border tracking-wide', getFriendlyDueDate(t.due_date).class]">
                                                    <Clock class="size-3" />
                                                    <span>{{ getFriendlyDueDate(t.due_date).text }}</span>
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

                <!-- Calendar View -->
                <div v-else-if="currentView === 'calendar'" class="flex flex-col gap-4 bg-white/60 dark:bg-zinc-900/20 border border-zinc-200/80 dark:border-zinc-800/80 rounded-2xl p-6 shadow-xs backdrop-blur-md">
                    <!-- Calendar Header (Month navigation) -->
                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 pb-4 border-b border-zinc-200 dark:border-zinc-800">
                        <div class="flex items-center gap-2">
                            <h3 class="text-xl font-bold text-zinc-900 dark:text-zinc-50">
                                {{ monthNames[currentMonth] }} {{ currentYear }}
                            </h3>
                        </div>
                        <div class="flex items-center gap-1 bg-zinc-100 dark:bg-zinc-800 p-0.5 rounded-lg border border-zinc-200/50 dark:border-zinc-700/50 self-start sm:self-auto">
                            <Button @click="prevMonth" variant="ghost" size="icon" class="h-8 w-8 text-zinc-600 dark:text-zinc-350 hover:bg-white dark:hover:bg-zinc-700 hover:text-zinc-900 dark:hover:text-zinc-50 rounded-md cursor-pointer">
                                <ChevronLeft class="size-4" />
                            </Button>
                            <Button @click="goToToday" variant="ghost" class="h-8 px-3 text-xs font-semibold text-zinc-650 dark:text-zinc-300 hover:bg-white dark:hover:bg-zinc-700 hover:text-zinc-900 dark:hover:text-zinc-50 rounded-md cursor-pointer">
                                Hoje
                            </Button>
                            <Button @click="nextMonth" variant="ghost" size="icon" class="h-8 w-8 text-zinc-600 dark:text-zinc-350 hover:bg-white dark:hover:bg-zinc-700 hover:text-zinc-900 dark:hover:text-zinc-50 rounded-md cursor-pointer">
                                <ChevronRight class="size-4" />
                            </Button>
                        </div>
                    </div>

                    <!-- Weekdays Header Grid -->
                    <div class="grid grid-cols-7 gap-2 text-center text-xs font-bold uppercase tracking-wider text-zinc-500 dark:text-zinc-400 py-2">
                        <div>Dom</div>
                        <div>Seg</div>
                        <div>Ter</div>
                        <div>Qua</div>
                        <div>Qui</div>
                        <div>Sex</div>
                        <div>Sáb</div>
                    </div>

                    <!-- Days Grid -->
                    <div class="grid grid-cols-7 gap-2 min-h-[500px]">
                        <div
                            v-for="day in calendarDays"
                            :key="day.dateStr"
                            :class="[
                                'flex flex-col min-h-[100px] p-2.5 rounded-xl border transition-all duration-200 hover:border-zinc-300 dark:hover:border-zinc-700',
                                day.isCurrentMonth
                                    ? 'bg-zinc-50/50 dark:bg-zinc-900/10 border-zinc-200/80 dark:border-zinc-800/80'
                                    : 'bg-zinc-100/20 dark:bg-zinc-950/5 border-zinc-150/50 dark:border-zinc-900/30 opacity-60',
                                isDateToday(day.dateStr)
                                    ? 'ring-2 ring-indigo-500/50 bg-indigo-50/10 dark:bg-indigo-950/5 border-indigo-200 dark:border-indigo-900/50'
                                    : ''
                            ]"
                        >
                            <!-- Day Number / Date Header -->
                            <div class="flex items-center justify-between mb-1.5">
                                <span
                                    :class="[
                                        'text-xs font-bold flex h-5 w-5 items-center justify-center rounded-full',
                                        isDateToday(day.dateStr)
                                            ? 'bg-indigo-600 text-white shadow-2xs font-semibold'
                                            : day.isCurrentMonth
                                                ? 'text-zinc-700 dark:text-zinc-300'
                                                : 'text-zinc-400 dark:text-zinc-500'
                                    ]"
                                >
                                    {{ day.dayNum }}
                                </span>
                                <!-- Small badge for number of tasks if any -->
                                <span v-if="day.tasks.length" class="text-[9px] font-bold text-zinc-500 dark:text-zinc-450 px-1.5 py-0.5 rounded bg-zinc-200/60 dark:bg-zinc-850/60">
                                    {{ day.tasks.length }}
                                </span>
                            </div>

                            <!-- Tasks list in this day (Scrollable if too many) -->
                            <div class="flex-1 space-y-1.5 overflow-y-auto max-h-[140px] scrollbar-thin scrollbar-thumb-zinc-200 dark:scrollbar-thumb-zinc-800 pr-0.5">
                                <div
                                    v-for="task in day.tasks"
                                    :key="task.id"
                                    @click="openEditTaskModal(task)"
                                    :class="[
                                        'group relative cursor-pointer rounded-lg border p-1.5 text-left transition-all duration-150 hover:shadow-2xs select-none border-zinc-200 hover:border-indigo-500/50 dark:border-zinc-800/60 dark:hover:border-indigo-400/50',
                                        getPriorityConfig(task.priority).bg
                                    ]"
                                >
                                    <!-- Task title inside day cell -->
                                    <div class="font-semibold text-zinc-900 dark:text-zinc-100 text-[10px] leading-tight truncate group-hover:text-indigo-600 dark:group-hover:text-indigo-400">
                                        {{ task.title }}
                                    </div>
                                    <!-- Subtask progress if exists -->
                                    <div v-if="task.subtasks && task.subtasks.length" class="flex items-center gap-0.5 mt-0.5 text-[8px] text-zinc-500 dark:text-zinc-455 font-medium">
                                        <CheckSquare class="size-2 text-indigo-500" />
                                        <span>{{ getCompletedSubtasksCount(task) }}/{{ task.subtasks.length }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </template>

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
                                
                                <!-- Tags / Etiquetas selection & creation section inside task modal -->
                                <div class="border-t border-zinc-200 dark:border-zinc-800 pt-4 space-y-3">
                                    <Label class="text-xs font-semibold uppercase tracking-wider text-zinc-500">Etiquetas / Tags</Label>
                                    
                                    <!-- List of all available user tags -->
                                    <div class="flex flex-wrap gap-2">
                                        <button
                                            v-for="tag in props.tags"
                                            :key="tag.id"
                                            type="button"
                                            @click="toggleFormTag(tag.id)"
                                            :class="[
                                                'inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-semibold border transition duration-150 cursor-pointer select-none',
                                                taskForm.tag_ids.includes(tag.id)
                                                    ? getTagColorConfig(tag.color).bg + ' ring-2 ring-indigo-500/50'
                                                    : 'bg-zinc-50 text-zinc-500 border-zinc-200 hover:bg-zinc-100 dark:bg-zinc-900/50 dark:text-zinc-400 dark:border-zinc-800 dark:hover:bg-zinc-900'
                                            ]"
                                        >
                                            {{ tag.name }}
                                            <span 
                                                @click.stop="deleteSystemTag(tag)" 
                                                class="text-zinc-400 hover:text-rose-600 dark:hover:text-rose-400 ml-0.5 rounded-full p-0.5"
                                                title="Excluir etiqueta do sistema"
                                            >
                                                <X class="size-3" />
                                            </span>
                                        </button>
                                        <div v-if="!props.tags.length" class="text-xs text-zinc-400 dark:text-zinc-500 italic">
                                            Nenhuma etiqueta criada ainda.
                                        </div>
                                    </div>

                                    <!-- Create custom tag inline form -->
                                    <div class="bg-zinc-50 dark:bg-zinc-900/30 p-3 rounded-lg border border-zinc-200 dark:border-zinc-800 space-y-2.5">
                                        <div class="text-xs font-bold text-zinc-700 dark:text-zinc-300">Nova Etiqueta</div>
                                        <div class="flex gap-2">
                                            <Input 
                                                v-model="newTagName" 
                                                type="text" 
                                                placeholder="Nome da etiqueta..." 
                                                class="flex-1 h-8 text-xs"
                                                @keydown.enter.prevent="createCustomTag"
                                            />
                                            <Button 
                                                type="button" 
                                                @click="createCustomTag" 
                                                class="h-8 px-3 text-xs bg-indigo-600 hover:bg-indigo-700 text-white font-medium"
                                                :disabled="isCreatingTag"
                                            >
                                                Criar
                                            </Button>
                                        </div>
                                        <div class="flex flex-wrap gap-2 items-center">
                                            <span class="text-[10px] text-zinc-500 uppercase tracking-wider">Cor:</span>
                                            <button
                                                v-for="colorOpt in colorOptions"
                                                :key="colorOpt.name"
                                                type="button"
                                                @click="newTagColor = colorOpt.name"
                                                :class="[
                                                    'size-5 rounded-full border transition-all duration-150',
                                                    colorOpt.bg,
                                                    newTagColor === colorOpt.name
                                                        ? 'ring-2 ring-indigo-500 scale-110 border-white'
                                                        : 'border-zinc-300 dark:border-zinc-700 hover:scale-105'
                                                ]"
                                                :title="colorOpt.label"
                                            />
                                        </div>
                                    </div>
                                </div>

                                <!-- Subtasks checklist editor inside task modal -->
                                <div v-if="isEditMode" class="border-t border-zinc-200 dark:border-zinc-800 pt-4 space-y-3">
                                    <Label class="text-xs font-semibold uppercase tracking-wider text-zinc-500">Subtarefas / Checklist</Label>
                                    
                                    <!-- Form to add a subtask -->
                                    <div class="flex gap-2">
                                        <Input 
                                            v-model="newSubtaskTitle" 
                                            type="text" 
                                            placeholder="Nova subtarefa..." 
                                            class="flex-1 h-9 text-xs" 
                                            @keydown.enter.prevent="handleAddSubtask"
                                        />
                                        <Button 
                                            type="button" 
                                            @click="handleAddSubtask" 
                                            class="h-9 px-3 text-xs bg-zinc-950 hover:bg-zinc-900 text-white dark:bg-zinc-800 dark:hover:bg-zinc-700 font-medium"
                                        >
                                            Adicionar
                                        </Button>
                                    </div>

                                    <!-- List of current subtasks -->
                                    <div class="space-y-2 max-h-[160px] overflow-y-auto pr-1">
                                        <div 
                                            v-for="sub in currentTaskSubtasks" 
                                            :key="sub.id" 
                                            class="flex items-center justify-between gap-2 p-2 rounded-lg bg-zinc-50 dark:bg-zinc-900/50 text-xs border border-zinc-150 dark:border-zinc-800"
                                        >
                                            <label class="flex items-center gap-2 cursor-pointer flex-1 min-w-0 select-none">
                                                <input 
                                                    type="checkbox" 
                                                    :checked="sub.is_completed" 
                                                    @change="toggleSubtask(sub)"
                                                    class="rounded border-zinc-300 text-indigo-600 focus:ring-indigo-500"
                                                />
                                                <span :class="['truncate', sub.is_completed ? 'line-through text-zinc-400 dark:text-zinc-500' : 'text-zinc-700 dark:text-zinc-300']">
                                                    {{ sub.title }}
                                                </span>
                                            </label>
                                            <button 
                                                type="button" 
                                                @click="deleteSubtask(sub.id)" 
                                                class="text-zinc-400 hover:text-rose-600 p-1 rounded-md hover:bg-rose-50 dark:hover:bg-rose-950/20 transition"
                                            >
                                                <Trash2 class="size-3.5" />
                                            </button>
                                        </div>
                                        <div v-if="!currentTaskSubtasks.length" class="text-center py-4 text-xs text-zinc-450 dark:text-zinc-500 italic">
                                            Nenhuma subtarefa adicionada.
                                        </div>
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

    <!-- Archived Tasks Modal -->
    <Dialog v-model:open="isArchiveModalOpen">
        <DialogContent class="sm:max-w-[500px] border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-950">
            <DialogHeader>
                <DialogTitle class="text-lg font-bold text-zinc-900 dark:text-zinc-50">Tarefas Arquivadas</DialogTitle>
                <DialogDescription class="text-zinc-500 dark:text-zinc-400 text-sm">
                    Veja as tarefas que foram arquivadas. Você pode restaurá-las para o quadro ou excluí-las permanentemente.
                </DialogDescription>
            </DialogHeader>
            <div class="space-y-3 max-h-[300px] overflow-y-auto pr-1 py-2">
                <div 
                    v-for="task in props.archivedTasks" 
                    :key="task.id" 
                    class="flex flex-col gap-2 p-3 rounded-lg border border-zinc-200 dark:border-zinc-800 bg-zinc-50/50 dark:bg-zinc-900/30"
                >
                    <div class="flex items-start justify-between gap-4">
                        <div class="min-w-0 flex-1">
                            <h5 class="font-semibold text-xs text-zinc-900 dark:text-zinc-100 truncate">{{ task.title }}</h5>
                            <p v-if="task.description" class="text-[11px] text-zinc-500 dark:text-zinc-400 line-clamp-1 mt-0.5">{{ task.description }}</p>
                        </div>
                        <div class="flex items-center gap-1.5 shrink-0">
                            <Button 
                                @click="restoreTask(task)" 
                                type="button" 
                                size="sm" 
                                variant="outline" 
                                class="text-xs px-2 py-1 h-7 border-zinc-200 dark:border-zinc-800 text-zinc-700 dark:text-zinc-300 hover:bg-zinc-150"
                            >
                                Restaurar
                            </Button>
                            <Button 
                                @click="openDeleteConfirm('task', task.id, task.title)" 
                                type="button" 
                                size="sm" 
                                variant="destructive" 
                                class="text-xs px-2 py-1 h-7 text-white bg-rose-600 hover:bg-rose-700"
                            >
                                Excluir
                            </Button>
                        </div>
                    </div>
                </div>
                <div v-if="!props.archivedTasks.length" class="text-center py-8 text-xs text-zinc-450 dark:text-zinc-500 italic">
                    Nenhuma tarefa arquivada no momento.
                </div>
            </div>
            <DialogFooter class="pt-2">
                <Button type="button" variant="outline" @click="isArchiveModalOpen = false">Fechar</Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>

    <!-- Productivity Metrics Modal -->
    <Dialog v-model:open="isMetricsModalOpen">
        <DialogContent class="sm:max-w-[500px] border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-950">
            <DialogHeader>
                <DialogTitle class="text-lg font-bold text-zinc-900 dark:text-zinc-50">Métricas e Produtividade</DialogTitle>
                <DialogDescription class="text-zinc-500 dark:text-zinc-400 text-sm">
                    Acompanhe indicadores-chave de desempenho e status do seu quadro Kanban.
                </DialogDescription>
            </DialogHeader>
            <div class="space-y-6 py-2">
                <!-- Summary Stats Grid -->
                <div class="grid grid-cols-2 gap-3">
                    <div class="bg-zinc-50 dark:bg-zinc-900/50 p-3.5 rounded-xl border border-zinc-150 dark:border-zinc-850">
                        <div class="text-[10px] font-bold text-zinc-455 dark:text-zinc-500 uppercase tracking-wider">Tarefas Ativas</div>
                        <div class="text-xl font-bold text-zinc-900 dark:text-zinc-50 mt-0.5">{{ totalTasks }}</div>
                    </div>
                    <div class="bg-zinc-50 dark:bg-zinc-900/50 p-3.5 rounded-xl border border-zinc-150 dark:border-zinc-850">
                        <div class="text-[10px] font-bold text-zinc-455 dark:text-zinc-500 uppercase tracking-wider">Tarefas Vencidas</div>
                        <div class="text-xl font-bold mt-0.5 text-rose-650 dark:text-rose-455">{{ overdueStats }}</div>
                    </div>
                    <div class="bg-zinc-50 dark:bg-zinc-900/50 p-3.5 rounded-xl border border-zinc-150 dark:border-zinc-850">
                        <div class="text-[10px] font-bold text-zinc-455 dark:text-zinc-500 uppercase tracking-wider">Checklist (Itens Concluídos)</div>
                        <div class="text-xl font-bold text-zinc-900 dark:text-zinc-50 mt-0.5">{{ subtaskStats.completed }} / {{ subtaskStats.total }}</div>
                    </div>
                    <div class="bg-zinc-50 dark:bg-zinc-900/50 p-3.5 rounded-xl border border-zinc-150 dark:border-zinc-850">
                        <div class="text-[10px] font-bold text-zinc-455 dark:text-zinc-500 uppercase tracking-wider">Conclusão Geral</div>
                        <div class="text-xl font-bold text-emerald-650 dark:text-emerald-455 mt-0.5">{{ subtaskStats.pct }}%</div>
                    </div>
                </div>

                <!-- Priority Breakdown Progress Bars -->
                <div class="space-y-3">
                    <h5 class="text-xs font-bold uppercase tracking-wider text-zinc-550 dark:text-zinc-450">Distribuição por Prioridade</h5>
                    <div class="space-y-2">
                        <!-- High Priority -->
                        <div>
                            <div class="flex justify-between text-xs font-medium text-zinc-650 dark:text-zinc-400 mb-1">
                                <span>Alta</span>
                                <span>{{ priorityStats.high }} ({{ priorityStats.highPct }}%)</span>
                            </div>
                            <div class="w-full bg-zinc-100 dark:bg-zinc-800 rounded-full h-2 overflow-hidden">
                                <div class="bg-rose-500 h-2 rounded-full transition-all duration-300" :style="{ width: `${priorityStats.highPct}%` }" />
                            </div>
                        </div>
                        <!-- Medium Priority -->
                        <div>
                            <div class="flex justify-between text-xs font-medium text-zinc-655 dark:text-zinc-400 mb-1">
                                <span>Média</span>
                                <span>{{ priorityStats.medium }} ({{ priorityStats.mediumPct }}%)</span>
                            </div>
                            <div class="w-full bg-zinc-100 dark:bg-zinc-800 rounded-full h-2 overflow-hidden">
                                <div class="bg-amber-500 h-2 rounded-full transition-all duration-300" :style="{ width: `${priorityStats.mediumPct}%` }" />
                            </div>
                        </div>
                        <!-- Low Priority -->
                        <div>
                            <div class="flex justify-between text-xs font-medium text-zinc-655 dark:text-zinc-400 mb-1">
                                <span>Baixa</span>
                                <span>{{ priorityStats.low }} ({{ priorityStats.lowPct }}%)</span>
                            </div>
                            <div class="w-full bg-zinc-100 dark:bg-zinc-800 rounded-full h-2 overflow-hidden">
                                <div class="bg-emerald-500 h-2 rounded-full transition-all duration-300" :style="{ width: `${priorityStats.lowPct}%` }" />
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Tasks Per Column Progress Bars -->
                <div class="space-y-3 pt-2 border-t border-zinc-200/60 dark:border-zinc-850">
                    <h5 class="text-xs font-bold uppercase tracking-wider text-zinc-550 dark:text-zinc-450">Carga de Trabalho por Coluna</h5>
                    <div class="space-y-2">
                        <div v-for="col in localColumns" :key="col.id">
                            <div class="flex justify-between text-xs font-medium text-zinc-655 dark:text-zinc-400 mb-1">
                                <span>{{ col.name }}</span>
                                <span>{{ col.tasks.length }} tarefas</span>
                            </div>
                            <div class="w-full bg-zinc-100 dark:bg-zinc-800 rounded-full h-2 overflow-hidden">
                                <div class="bg-indigo-650 dark:bg-indigo-500 h-2 rounded-full transition-all duration-300" :style="{ width: `${totalTasks > 0 ? (col.tasks.length / totalTasks) * 100 : 0}%` }" />
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <DialogFooter class="pt-2">
                <Button type="button" variant="outline" @click="isMetricsModalOpen = false">Fechar</Button>
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

