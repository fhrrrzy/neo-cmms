<script setup>
import AppLayout from '@/layouts/AppLayout.vue';
import { Head } from '@inertiajs/vue3';
import { ref, onMounted } from 'vue';
import axios from 'axios';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
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
    XCircle,
    Activity,
    TrendingUp,
    Database,
} from 'lucide-vue-next';
import DataTable from '@/components/tables/sync-log/DataTable.vue';

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

const filterSyncType = ref('all');
const filterStatus = ref('all');
const filterDays = ref(7);

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

const handlePageChange = (page) => fetchLogs(page);

const handlePageSizeChange = (perPage) => {
    pagination.value.per_page = perPage;
    fetchLogs(1);
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
            <div class="flex flex-wrap items-end gap-4">
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

            <!-- Sync Logs Table -->
            <DataTable :data="logs" :loading="loading" :error="error" :pagination="pagination"
                @page-change="handlePageChange" @page-size-change="handlePageSizeChange" />
        </div>
    </AppLayout>
</template>
