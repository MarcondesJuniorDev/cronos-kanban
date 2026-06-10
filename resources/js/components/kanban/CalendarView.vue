<script setup lang="ts">
import { ChevronLeft, ChevronRight, CheckSquare } from '@lucide/vue';
import { ref, computed } from 'vue';
import { Button } from '@/components/ui/button';
import { getPriorityConfig } from '@/lib/kanbanHelpers';
import type { Column, Task, Subtask } from '@/types/kanban';

const props = defineProps<{
    columns: Column[];
    matchesFilter: (task: Task) => boolean;
}>();

const emit = defineEmits<{
    (e: 'editTask', task: Task): void;
}>();

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
    props.columns.forEach(column => {
        column.tasks.forEach(task => {
            if (task.due_date === dateStr) {
                tasks.push(task);
            }
        });
    });
    
    return tasks.filter(props.matchesFilter);
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

const getCompletedSubtasksCount = (task: Task) => {
    if (!task.subtasks) {
        return 0;
    }

    return task.subtasks.filter((s: Subtask) => s.is_completed).length;
};
</script>

<template>
    <div class="flex flex-col gap-4 bg-white/60 dark:bg-zinc-900/20 border border-zinc-200/80 dark:border-zinc-800/80 rounded-2xl p-6 shadow-xs backdrop-blur-md">
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
                        @click="emit('editTask', task)"
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
