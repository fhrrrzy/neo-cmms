<script setup>
import AppLayout from '@/layouts/AppLayout.vue';
import { Head } from '@inertiajs/vue3';
import { ref, onMounted, computed } from 'vue';
import axios from 'axios';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Skeleton } from '@/components/ui/skeleton';
import {
    RefreshCw,
    CheckCircle2,
    XCircle,
    Clock,
    AlertCircle,
    Activity,
    TrendingUp,
    Database,
} from 'lucide-vue-next';

const breadcrumbs = [
    {
        title: 'Sync Log',
        href: '/sync-log',
    },
];

const logs = ref([]);
const stats = ref(null);
const loading = ref(false);
const error = ref(null);

// Filters
const filterSyncType = ref('all');
const filterStatus = ref('all');
const filterDays = ref(7);

// Pagination
const pagination = ref({
    current_page: 1,
    last_page: 1,
    per_page: 15,
    total: 0,
});

const syncTypes = [
    { value: 'all', label: 'All Types' },
    { value: 'equipment', label: 'Equipment' },
    { value: 'running_time', label: 'Running Time' },
    { value: 'work_order', label: 'Work Orders' },
    { value: 'equipment_work_order_materials', label: 'Equipment Work Order Materials' },
    { value: 'daily_plant_data', label: 'Daily Plant Data' },
    { value: 'full', label: 'Full Sync' },
];

const statuses = [
    { value: 'all', label: 'All Status' },
    { value: 'pending', label: 'Pending' },
    { value: 'running', label: 'Running' },
    { value: 'completed', label: 'Completed' },
    { value: 'failed', label: 'Failed' },
    { value: 'cancelled', label: 'Cancelled' },
];

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

const fetchLogs = async (page = 1) => {
    loading.value = true;
    error.value = null;

    try {
        const params = {
            page,
            per_page: pagination.value.per_page,
        };

        if (filterSyncType.value !== 'all') {
            params.sync_type = filterSyncType.value;
        }

        if (filterStatus.value !== 'all') {
            params.status = filterStatus.value;
        }

        if (filterDays.value) {
            params.days = filterDays.value;
        }

        const response = await axios.get('/api/sync-logs', { params });

        logs.value = response.data.data;
        pagination.value = {
            current_page: response.data.current_page,
            last_page: response.data.last_page,
            per_page: response.data.per_page,
            total: response.data.total,
        };
    } catch (err) {
        error.value = err.response?.data?.message || 'Failed to fetch sync logs';
        console.error('Error fetching sync logs:', err);
    } finally {
        loading.value = false;
    }
};

const fetchStats = async () => {
    try {
        const response = await axios.get('/api/sync-logs/stats');
        stats.value = response.data;
    } catch (err) {
        console.error('Error fetching stats:', err);
    }
};

const handlePageChange = (page) => {
    fetchLogs(page);
};

const handleRefresh = () => {
    fetchLogs(pagination.value.current_page);
    fetchStats();
};

const handleFilterChange = () => {
    fetchLogs(1);
};

onMounted(() => {
    fetchLogs();
    fetchStats();
});
</script>

<template>

    <Head title="API Sync Log" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="space-y-6">
            <!-- Page Header -->
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-bold tracking-tight">API Sync Log</h1>
                    <p class="text-muted-foreground">
                        Monitor and track API synchronization activities
                    </p>
                </div>
                <Button @click="handleRefresh" variant="outline" size="sm">
                    <RefreshCw class="mr-2 h-4 w-4" :class="{ 'animate-spin': loading }" />
                    Refresh
                </Button>
            </div>

            <!-- Stats Cards -->
            <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
                <Card>
                    <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle class="text-sm font-medium">Total Syncs (7d)</CardTitle>
                        <Database class="h-4 w-4 text-muted-foreground" />
                    </CardHeader>
                    <CardContent>
                        <div v-if="stats" class="text-2xl font-bold">{{ stats.total_syncs || 0 }}</div>
                        <Skeleton v-else class="h-8 w-20" />
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle class="text-sm font-medium">Success Rate</CardTitle>
                        <TrendingUp class="h-4 w-4 text-muted-foreground" />
                    </CardHeader>
                    <CardContent>
                        <div v-if="stats" class="text-2xl font-bold text-green-600">
                            {{ stats.success_rate || '0%' }}
                        </div>
                        <Skeleton v-else class="h-8 w-20" />
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle class="text-sm font-medium">Running Now</CardTitle>
                        <Activity class="h-4 w-4 text-muted-foreground" />
                    </CardHeader>
                    <CardContent>
                        <div v-if="stats" class="text-2xl font-bold text-blue-600">
                            {{ stats.running_syncs || 0 }}
                        </div>
                        <Skeleton v-else class="h-8 w-20" />
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle class="text-sm font-medium">Failed (7d)</CardTitle>
                        <XCircle class="h-4 w-4 text-muted-foreground" />
                    </CardHeader>
                    <CardContent>
                        <div v-if="stats" class="text-2xl font-bold text-red-600">
                            {{ stats.failed_syncs || 0 }}
                        </div>
                        <Skeleton v-else class="h-8 w-20" />
                    </CardContent>
                </Card>
            </div>

            <!-- Filters -->
            <Card>
                <CardHeader>
                    <CardTitle>Filters</CardTitle>
                    <CardDescription>Filter sync logs by type, status, and time period</CardDescription>
                </CardHeader>
                <CardContent>
                    <div class="flex flex-wrap gap-4">
                        <div class="w-full sm:w-auto">
                            <label class="mb-2 block text-sm font-medium">Sync Type</label>
                            <Select v-model="filterSyncType" @update:model-value="handleFilterChange">
                                <SelectTrigger class="w-full sm:w-[200px]">
                                    <SelectValue placeholder="Select type" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem v-for="type in syncTypes" :key="type.value" :value="type.value">
                                        {{ type.label }}
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                        </div>

                        <div class="w-full sm:w-auto">
                            <label class="mb-2 block text-sm font-medium">Status</label>
                            <Select v-model="filterStatus" @update:model-value="handleFilterChange">
                                <SelectTrigger class="w-full sm:w-[180px]">
                                    <SelectValue placeholder="Select status" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem v-for="status in statuses" :key="status.value" :value="status.value">
                                        {{ status.label }}
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                        </div>

                        <div class="w-full sm:w-auto">
                            <label class="mb-2 block text-sm font-medium">Time Period</label>
                            <Select v-model="filterDays" @update:model-value="handleFilterChange">
                                <SelectTrigger class="w-full sm:w-[180px]">
                                    <SelectValue placeholder="Select period" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem :value="1">Last 24 hours</SelectItem>
                                    <SelectItem :value="7">Last 7 days</SelectItem>
                                    <SelectItem :value="30">Last 30 days</SelectItem>
                                    <SelectItem :value="90">Last 90 days</SelectItem>
                                </SelectContent>
                            </Select>
                        </div>
                    </div>
                </CardContent>
            </Card>

            <!-- Sync Logs Table -->
            <Card>
                <CardHeader>
                    <CardTitle>Sync Logs</CardTitle>
                    <CardDescription>
                        Showing {{ logs.length }} of {{ pagination.total }} total logs
                    </CardDescription>
                </CardHeader>
                <CardContent>
                    <div class="relative overflow-x-auto">
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>Sync Type</TableHead>
                                    <TableHead>Status</TableHead>
                                    <TableHead class="text-right">Processed</TableHead>
                                    <TableHead class="text-right">Success</TableHead>
                                    <TableHead class="text-right">Failed</TableHead>
                                    <TableHead class="text-right">Success Rate</TableHead>
                                    <TableHead>Started</TableHead>
                                    <TableHead>Completed</TableHead>
                                    <TableHead class="text-right">Duration</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                <!-- Loading State -->
                                <TableRow v-if="loading">
                                    <TableCell colspan="9" class="text-center">
                                        <div class="flex items-center justify-center py-8">
                                            <RefreshCw class="mr-2 h-5 w-5 animate-spin" />
                                            <span>Loading sync logs...</span>
                                        </div>
                                    </TableCell>
                                </TableRow>

                                <!-- Error State -->
                                <TableRow v-else-if="error">
                                    <TableCell colspan="9" class="text-center">
                                        <div class="flex flex-col items-center justify-center py-8 text-destructive">
                                            <XCircle class="mb-2 h-8 w-8" />
                                            <span>{{ error }}</span>
                                        </div>
                                    </TableCell>
                                </TableRow>

                                <!-- Empty State -->
                                <TableRow v-else-if="logs.length === 0">
                                    <TableCell colspan="9" class="text-center">
                                        <div
                                            class="flex flex-col items-center justify-center py-8 text-muted-foreground">
                                            <Database class="mb-2 h-8 w-8" />
                                            <span>No sync logs found</span>
                                        </div>
                                    </TableCell>
                                </TableRow>

                                <!-- Data Rows -->
                                <TableRow v-else v-for="log in logs" :key="log.id">
                                    <TableCell>
                                        <Badge variant="outline">{{ log.sync_type }}</Badge>
                                    </TableCell>
                                    <TableCell>
                                        <Badge :variant="getStatusColor(log.status)">
                                            <component :is="getStatusIcon(log.status)" class="mr-1 h-3 w-3"
                                                :class="{ 'animate-spin': log.status === 'running' }" />
                                            {{ log.status }}
                                        </Badge>
                                    </TableCell>
                                    <TableCell class="text-right">
                                        {{ log.records_processed || 0 }}
                                    </TableCell>
                                    <TableCell class="text-right text-green-600">
                                        {{ log.records_success || 0 }}
                                    </TableCell>
                                    <TableCell class="text-right text-red-600">
                                        {{ log.records_failed || 0 }}
                                    </TableCell>
                                    <TableCell class="text-right">
                                        {{ formatSuccessRate(log.records_processed, log.records_success) }}
                                    </TableCell>
                                    <TableCell>
                                        {{ log.sync_started_at ? formatDate(log.sync_started_at) : '-' }}
                                    </TableCell>
                                    <TableCell>
                                        {{ log.sync_completed_at ? formatDate(log.sync_completed_at) : '-' }}
                                    </TableCell>
                                    <TableCell class="text-right">
                                        {{ log.duration ? formatDuration(log.duration) : '-' }}
                                    </TableCell>
                                </TableRow>
                            </TableBody>
                        </Table>
                    </div>

                    <!-- Pagination -->
                    <div v-if="pagination.last_page > 1" class="mt-4 flex items-center justify-between">
                        <div class="text-sm text-muted-foreground">
                            Page {{ pagination.current_page }} of {{ pagination.last_page }}
                        </div>
                        <div class="flex gap-2">
                            <Button variant="outline" size="sm" :disabled="pagination.current_page === 1"
                                @click="handlePageChange(pagination.current_page - 1)">
                                Previous
                            </Button>
                            <Button variant="outline" size="sm"
                                :disabled="pagination.current_page === pagination.last_page"
                                @click="handlePageChange(pagination.current_page + 1)">
                                Next
                            </Button>
                        </div>
                    </div>
                </CardContent>
            </Card>
        </div>
    </AppLayout>
</template>
