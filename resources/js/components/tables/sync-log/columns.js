import { h } from 'vue';
import { Badge } from '@/components/ui/badge';
import { CheckCircle2, XCircle, Clock, RefreshCw, AlertCircle } from 'lucide-vue-next';

const getStatusColor = (status) => {
    const colors = {
        pending: 'secondary',
        running: 'default',
        completed: 'default',
        failed: 'destructive',
        cancelled: 'secondary',
    };
    return colors[status] || 'secondary';
};

const getStatusIcon = (status) => {
    const icons = {
        pending: Clock,
        running: RefreshCw,
        completed: CheckCircle2,
        failed: XCircle,
        cancelled: AlertCircle,
    };
    return icons[status] || Clock;
};

const formatDate = (dateString) => {
    if (!dateString) return '-';
    try {
        const date = new Date(dateString);
        const months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
        const month = months[date.getMonth()];
        const day = String(date.getDate()).padStart(2, '0');
        const hours = String(date.getHours()).padStart(2, '0');
        const minutes = String(date.getMinutes()).padStart(2, '0');
        return `${month} ${day}, ${hours}:${minutes}`;
    } catch (e) {
        return '-';
    }
};

const formatDuration = (seconds) => {
    if (!seconds) return '-';
    const mins = Math.floor(seconds / 60);
    const secs = seconds % 60;
    return `${mins}m ${secs}s`;
};

const formatSuccessRate = (processed, success) => {
    if (processed === 0) return '0%';
    return ((success / processed) * 100).toFixed(1) + '%';
};

export const columns = [
    {
        accessorKey: 'sync_type',
        header: () => h('div', { class: 'font-medium' }, 'Sync Type'),
        cell: ({ row }) => h(Badge, { variant: 'outline' }, () => row.getValue('sync_type')),
    },
    {
        accessorKey: 'status',
        header: () => h('div', { class: 'font-medium' }, 'Status'),
        cell: ({ row }) => {
            const status = row.getValue('status');
            const Icon = getStatusIcon(status);
            return h(
                Badge,
                { variant: getStatusColor(status) },
                () => [
                    h(Icon, { class: ['mr-1 h-3 w-3', status === 'running' && 'animate-spin'].filter(Boolean).join(' ') }),
                    status,
                ],
            );
        },
    },
    {
        accessorKey: 'records_processed',
        header: () => h('div', { class: 'text-right font-medium' }, 'Processed'),
        cell: ({ row }) => h('div', { class: 'text-right font-mono' }, row.getValue('records_processed') || 0),
    },
    {
        accessorKey: 'records_success',
        header: () => h('div', { class: 'text-right font-medium' }, 'Success'),
        cell: ({ row }) => h('div', { class: 'text-right font-mono text-green-600' }, row.getValue('records_success') || 0),
    },
    {
        accessorKey: 'records_failed',
        header: () => h('div', { class: 'text-right font-medium' }, 'Failed'),
        cell: ({ row }) => h('div', { class: 'text-right font-mono text-red-600' }, row.getValue('records_failed') || 0),
    },
    {
        id: 'success_rate',
        header: () => h('div', { class: 'text-right font-medium' }, 'Success Rate'),
        cell: ({ row }) => h(
            'div',
            { class: 'text-right font-mono' },
            formatSuccessRate(row.original.records_processed, row.original.records_success),
        ),
    },
    {
        accessorKey: 'sync_started_at',
        header: () => h('div', { class: 'font-medium' }, 'Started'),
        cell: ({ row }) => h('div', {}, formatDate(row.getValue('sync_started_at'))),
    },
    {
        accessorKey: 'sync_completed_at',
        header: () => h('div', { class: 'font-medium' }, 'Completed'),
        cell: ({ row }) => h('div', {}, formatDate(row.getValue('sync_completed_at'))),
    },
    {
        id: 'duration',
        header: () => h('div', { class: 'text-right font-medium' }, 'Duration'),
        cell: ({ row }) => h('div', { class: 'text-right' }, formatDuration(row.original.duration)),
    },
];
