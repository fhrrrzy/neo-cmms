// Utility functions for jam-jalan-summary table

export const formatDate = (dateString) => {
    const date = new Date(dateString);
    return date.toLocaleDateString('id-ID', {
        day: '2-digit',
        month: 'short',
    });
};

export const getCellClass = (isMengolah, count) => {
    // If is_mengolah is true but count is 0 or null, make it red
    if (isMengolah && (count === 0 || count === null)) return 'bg-red-500/80 dark:bg-red-500/50 group-hover:bg-red-500/70';
    // If is_mengolah is false, make it blue
    if (!isMengolah) return 'bg-blue-500/80 dark:bg-blue-500/50 group-hover:bg-blue-500/70';
    return 'border border-gray-300 dark:border-zinc-800 group-hover:border-gray-400'; 
};

