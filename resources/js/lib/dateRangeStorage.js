// Simple localStorage helpers for a global date range
const DATE_RANGE_KEY = 'global_date_range';

export const loadDateRange = () => {
    try {
        const raw = localStorage.getItem(DATE_RANGE_KEY);
        if (!raw) return null;
        const parsed = JSON.parse(raw);
        if (
            parsed &&
            typeof parsed.start === 'string' &&
            typeof parsed.end === 'string'
        ) {
            return { start: parsed.start, end: parsed.end };
        }
        return null;
    } catch (_) {
        return null;
    }
};

export const saveDateRange = (range) => {
    try {
        if (!range || !range.start || !range.end) return;
        localStorage.setItem(DATE_RANGE_KEY, JSON.stringify(range));
    } catch (_) {
        // ignore
    }
};

export const clearDateRange = () => {
    try {
        localStorage.removeItem(DATE_RANGE_KEY);
    } catch (_) {
        // ignore
    }
};


