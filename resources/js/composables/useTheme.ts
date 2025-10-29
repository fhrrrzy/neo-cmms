import { ref, onMounted } from 'vue';

export type Theme = 'default' | 'amber-minimal' | 'solar-dusk' | 'supabase' | 'twitter' | 'vintage-paper';

const THEME_STORAGE_KEY = 'app-theme';
const DEFAULT_THEME: Theme = 'default';

const currentTheme = ref<Theme>(DEFAULT_THEME);

export function useTheme() {
    const loadTheme = async (theme: Theme) => {
        // Remove all theme stylesheets
        const existingThemes = document.querySelectorAll('link[data-theme]');
        existingThemes.forEach(link => link.remove());

        // Dynamically import the theme CSS file using Vite's import
        try {
            let themeModule;
            switch (theme) {
                case 'default':
                    themeModule = await import('../../css/theme/default.css?url');
                    break;
                case 'amber-minimal':
                    themeModule = await import('../../css/theme/amber-minimal.css?url');
                    break;
                case 'solar-dusk':
                    themeModule = await import('../../css/theme/solar-dusk.css?url');
                    break;
                case 'supabase':
                    themeModule = await import('../../css/theme/supabase.css?url');
                    break;
                case 'twitter':
                    themeModule = await import('../../css/theme/twitter.css?url');
                    break;
                case 'vintage-paper':
                    themeModule = await import('../../css/theme/vintage-paper.css?url');
                    break;
            }

            // Add the new theme stylesheet
            const link = document.createElement('link');
            link.rel = 'stylesheet';
            link.href = themeModule.default;
            link.setAttribute('data-theme', theme);
            document.head.appendChild(link);
        } catch (error) {
            console.error(`Failed to load theme: ${theme}`, error);
        }
    };

    const updateTheme = (theme: Theme) => {
        currentTheme.value = theme;
        localStorage.setItem(THEME_STORAGE_KEY, theme);
        loadTheme(theme);
    };

    const initializeTheme = () => {
        const storedTheme = localStorage.getItem(THEME_STORAGE_KEY) as Theme | null;
        const theme = storedTheme || DEFAULT_THEME;
        currentTheme.value = theme;
        loadTheme(theme);
    };

    onMounted(() => {
        initializeTheme();
    });

    return {
        currentTheme,
        updateTheme,
        initializeTheme,
    };
}
