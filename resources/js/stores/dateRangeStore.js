import { ref, computed } from 'vue';
import { loadDateRange, saveDateRange } from '@/lib/dateRangeStorage';

const todayIso = () => new Date().toISOString().split('T')[0];
const daysAgoIso = (days) =>
    new Date(Date.now() - days * 24 * 60 * 60 * 1000)
        .toISOString()
        .split('T')[0];

const stored = loadDateRange();

const start = ref(stored?.start ?? daysAgoIso(7));
const end = ref(stored?.end ?? todayIso());

const range = computed(() => ({ start: start.value, end: end.value }));

function setRange(newRange) {
    if (!newRange) return;
    if (newRange.start) start.value = newRange.start;
    if (newRange.end) end.value = newRange.end;
    if (start.value && end.value) {
        saveDateRange({ start: start.value, end: end.value });
    }
}

export const dateRangeStore = {
    start,
    end,
    range,
    setRange,
};


