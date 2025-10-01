import prettier from 'eslint-config-prettier/flat';
import vue from 'eslint-plugin-vue';

export default [
    ...vue.configs['flat/essential'],
    {
        ignores: ['vendor', 'node_modules', 'public', 'bootstrap/ssr', 'tailwind.config.js', 'resources/js/components/ui/*'],
    },
    {
        files: ['**/*.vue'],
        languageOptions: {
            parserOptions: {
                parser: '@typescript-eslint/parser',
                extraFileExtensions: ['.vue'],
            },
        },
        rules: {
            'vue/multi-word-component-names': 'off',
            'vue/block-lang': 'off', // Allow JavaScript in Vue files
        },
    },
    prettier,
];
