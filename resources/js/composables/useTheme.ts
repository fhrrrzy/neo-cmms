import { ref, onMounted, watch } from 'vue';
import { useAppearance } from './useAppearance';

export type Theme = 'default' | 'amber-minimal' | 'modern-minimal' | 'nature' | 'nothern-lights' | 'ocean-breeze' | 'solar-dusk' | 'supabase' | 'twitter' | 'vintage-paper';

const THEME_STORAGE_KEY = 'app-theme';
const DEFAULT_THEME: Theme = 'default';

// Get initial theme from storage immediately
const getInitialTheme = (): Theme => {
    if (typeof window === 'undefined') {
        return DEFAULT_THEME;
    }
    return (localStorage.getItem(THEME_STORAGE_KEY) as Theme | null) || DEFAULT_THEME;
};

const currentTheme = ref<Theme>(getInitialTheme());
let isThemeInitialized = false;

export function useTheme() {
    const { appearance } = useAppearance();

    const loadTheme = async (theme: Theme) => {
        if (typeof window === 'undefined') {
            return;
        }

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
                case 'modern-minimal':
                    themeModule = await import('../../css/theme/modern-minimal.css?url');
                    break;
                case 'nature':
                    themeModule = await import('../../css/theme/nature.css?url');
                    break;
                case 'nothern-lights':
                    themeModule = await import('../../css/theme/nothern-lights.css?url');
                    break;
                case 'ocean-breeze':
                    themeModule = await import('../../css/theme/ocean-breeze.css?url');
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
        if (typeof window === 'undefined') {
            return;
        }
        const storedTheme = localStorage.getItem(THEME_STORAGE_KEY) as Theme | null;
        const theme = storedTheme || DEFAULT_THEME;
        currentTheme.value = theme;
        loadTheme(theme);
    };

    // Initialize theme on first mount only
    onMounted(() => {
        if (!isThemeInitialized) {
            initializeTheme();
            isThemeInitialized = true;
        }
    });

    // Watch for appearance changes to ensure theme CSS is reapplied
    // This ensures the .dark class changes work with the current theme
    watch(appearance, () => {
        // Theme CSS files contain both :root and .dark selectors
        // So we don't need to reload, just let the appearance handle the .dark class
    });

    return {
        currentTheme,
        updateTheme,
        initializeTheme,
    };
}
