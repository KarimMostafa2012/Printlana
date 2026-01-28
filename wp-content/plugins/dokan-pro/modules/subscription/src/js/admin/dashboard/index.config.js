import baseConfig from '../../../../../../base-tailwind.config';

/** @type {import('tailwindcss').Config} */
const updatedConfig = {
    ...baseConfig,
    content: [
        './modules/subscription/src/js/admin/dashboard/**/*.{js,jsx,ts,tsx}',
    ],
};

export default updatedConfig;
