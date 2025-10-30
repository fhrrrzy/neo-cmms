import { ref, onMounted, watch } from 'vue';
import { useAppearance } from './useAppearance';

export type Theme = 'default' | 'amber-minimal' | 'caffeine' | 'claymorphism' | 'modern-minimal' | 'nature' | 'nothern-lights' | 'ocean-breeze' | 'solar-dusk' | 'supabase' | 'twitter' | 'vintage-paper';

const THEME_STORAGE_KEY = 'app-theme';
const DEFAULT_THEME: Theme = 'default';

// Font families required by each theme
const THEME_FONTS: Record<Theme, string[]> = {
    'default': [],
    'amber-minimal': ['Inter:ital,opsz,wght@0,14..32,100..900;1,14..32,100..900', 'Source+Serif+4:ital,opsz,wght@0,8..60,200..900;1,8..60,200..900', 'JetBrains+Mono:ital,wght@0,100..800;1,100..800'],
    'caffeine': ['Inter:ital,opsz,wght@0,14..32,100..900;1,14..32,100..900'],
    'claymorphism': ['Plus+Jakarta+Sans:ital,wght@0,200..800;1,200..800', 'Lora:ital,wght@0,400..700;1,400..700', 'Roboto+Mono:ital,wght@0,100..700;1,100..700'],
    'modern-minimal': ['Inter:ital,opsz,wght@0,14..32,100..900;1,14..32,100..900', 'Source+Serif+4:ital,opsz,wght@0,8..60,200..900;1,8..60,200..900', 'JetBrains+Mono:ital,wght@0,100..800;1,100..800'],
    'nature': ['DM+Sans:ital,opsz,wght@0,9..40,100..1000;1,9..40,100..1000', 'Lora:ital,wght@0,400..700;1,400..700', 'IBM+Plex+Mono:ital,wght@0,300..700;1,300..700'],
    'nothern-lights': ['Plus+Jakarta+Sans:ital,wght@0,200..800;1,200..800', 'Source+Serif+4:ital,opsz,wght@0,8..60,200..900;1,8..60,200..900', 'JetBrains+Mono:ital,wght@0,100..800;1,100..800'],
    'ocean-breeze': ['DM+Sans:ital,opsz,wght@0,9..40,100..1000;1,9..40,100..1000', 'Lora:ital,wght@0,400..700;1,400..700', 'IBM+Plex+Mono:ital,wght@0,300..700;1,300..700'],
    'solar-dusk': ['Oxanium:wght@300..700', 'Merriweather:ital,wght@0,300;0,400;0,700;1,300;1,400;1,700', 'Fira+Code:wght@300..700'],
    'supabase': ['Outfit:wght@300..700'],
    'twitter': ['Open+Sans:ital,wght@0,300..700;1,300..700'],
    'vintage-paper': ['Libre+Baskerville:ital,wght@0,400;0,700;1,400', 'Lora:ital,wght@0,400..700;1,400..700', 'IBM+Plex+Mono:ital,wght@0,300..700;1,300..700'],
};

// Track loaded fonts to avoid duplicates
const loadedFonts = new Set<string>();

// Load fonts for a specific theme
const loadThemeFonts = (theme: Theme) => {
    const fonts = THEME_FONTS[theme];
    if (!fonts || fonts.length === 0) return;

    // Check if fonts are already loaded
    const allLoaded = fonts.every(font => loadedFonts.has(font));
    if (allLoaded) return;

    // Build Google Fonts URL
    const fontParams = fonts.map(f => `family=${f}`).join('&');
    const fontUrl = `https://fonts.googleapis.com/css2?${fontParams}&display=swap`;

    // Check if link already exists
    const existingLink = document.querySelector(`link[href="${fontUrl}"]`);
    if (existingLink) return;

    // Create and append font link
    const link = document.createElement('link');
    link.rel = 'stylesheet';
    link.href = fontUrl;
    link.setAttribute('data-theme-fonts', theme);
    document.head.appendChild(link);

    // Mark fonts as loaded
    fonts.forEach(font => loadedFonts.add(font));
};

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
                case 'caffeine':
                    themeModule = await import('../../css/theme/caffeine.css?url');
                    break;
                case 'claymorphism':
                    themeModule = await import('../../css/theme/claymorphism.css?url');
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
            
            // Wait for theme CSS to load, then load fonts
            link.onload = () => {
                loadThemeFonts(theme);
            };
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
