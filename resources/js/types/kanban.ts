export interface Tag {
    id: number;
    name: string;
    color: string;
}

export interface Subtask {
    id: number;
    task_id: number;
    title: string;
    is_completed: boolean;
    position: number;
}

export interface Task {
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

export interface Column {
    id: number;
    name: string;
    position: number;
    tasks: Task[];
}
