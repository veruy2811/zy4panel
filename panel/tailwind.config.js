import forms from '@tailwindcss/forms';

export default {
    content: [
        './resources/**/*.blade.php',
        './resources/**/*.js',
        './app/**/*.php',
    ],
    theme: {
        extend: {
            fontFamily: {
                sans: ['Inter', 'ui-sans-serif', 'system-ui'],
                mono: ['JetBrains Mono', 'ui-monospace', 'SFMono-Regular'],
            },
            colors: {
                void: '#070A12',
                panel: '#0B1020',
                panel2: '#11182D',
                line: '#26314F',
                neon: '#16D9E3',
                violet: '#8B5CF6',
            },
            boxShadow: {
                glow: '0 0 32px rgba(22, 217, 227, 0.14)',
            },
        },
    },
    plugins: [forms],
};
