import baseConfig from '../../../base-tailwind.config';

/** @type {import('tailwindcss').Config} */
const updatedConfig = {
    ...baseConfig,
    content: [
        './modules/table-rate-shipping/src/js/vendor-dashboard/**/*.{js,jsx,ts,tsx}',
    ],
};

export default updatedConfig;
