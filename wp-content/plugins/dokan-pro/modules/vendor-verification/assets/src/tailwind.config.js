import baseConfig from '../../../../base-tailwind.config';

/** @type {import('tailwindcss').Config} */
const updatedConfig = {
    ...baseConfig,
    content: [
        ...baseConfig.content,
        './modules/vendor-verification/assets/src/**/*.{js,jsx,ts,tsx}',
    ],
};

export default updatedConfig;
