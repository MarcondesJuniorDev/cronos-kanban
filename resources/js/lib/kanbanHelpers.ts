
export const getPriorityConfig = (priority: string) => {
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

export const isOverdue = (dueDate: string | null) => {
    if (!dueDate) {
        return false;
    }

    const today = new Date();
    today.setHours(0, 0, 0, 0);

    return new Date(`${dueDate}T00:00:00`) < today;
};

export const getTagColorConfig = (color: string) => {
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

export const getFriendlyDueDate = (dueDate: string | null) => {
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
        return { text: `Atrasada há ${Math.abs(diffDays)} dias`, class: 'bg-rose-50 text-rose-700 border-rose-200/50 dark:bg-rose-950/20 dark:text-rose-400 dark:border-rose-900/30' };
    } else {
        return { text: `Vence em ${diffDays} dias`, class: 'bg-emerald-50 text-emerald-700 border-emerald-200/50 dark:bg-emerald-950/20 dark:text-emerald-400 dark:border-emerald-900/30' };
    }
};
