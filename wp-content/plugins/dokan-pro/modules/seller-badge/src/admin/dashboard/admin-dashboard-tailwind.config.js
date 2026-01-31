import baseConfig from '../../../../../base-tailwind.config';

/** @type {import('tailwindcss').Config} */
const updatedConfig = {
    ...baseConfig,
    content: [
        './modules/seller-badge/src/admin/dashboard/**/*.{jsx,ts,tsx}',
    ],
};

export default updatedConfig;
