import baseConfig from '../../../base-tailwind.config';

/** @type {import('tailwindcss').Config} */
const updatedConfig = {
    ...baseConfig,
    content: [ './modules/subscription/src/**/*.{js,jsx,ts,tsx}' ],
    theme: {
        ...baseConfig?.theme,
        extend: {
            ...baseConfig?.theme?.extend,
            typography: {
                ...baseConfig?.theme?.extend?.typography,
                DEFAULT: {
                    css: {
                        ul: {
                            marginTop: '1rem',
                            marginBottom: '1rem',
                            paddingLeft: '1.25rem',
                        },
                        'ul li': {
                            marginBottom: '.75rem !important',
                            listStyleType: 'inherit !important',
                        },
                    },
                },
            },
        },
    },
};

export default updatedConfig;
