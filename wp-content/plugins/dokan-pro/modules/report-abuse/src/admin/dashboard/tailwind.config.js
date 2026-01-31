import baseConfig from '../../../../../base-tailwind.config';

/** @type {import('tailwindcss').Config} */
const updatedConfig = {
    ...baseConfig,
    content: [
        './modules/report-abuse/src/admin/dashboard/**/*.{js,jsx,ts,tsx}', // update the location according to your location
    ],
};

export default updatedConfig;
