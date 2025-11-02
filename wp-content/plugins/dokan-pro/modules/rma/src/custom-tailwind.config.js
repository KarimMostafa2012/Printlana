import baseConfig from '../../../base-tailwind.config';

/** @type {import('tailwindcss').Config} */
const updatedConfig = {
    ...baseConfig,
    content: [
        ...baseConfig.content,
        './modules/rma/src/**/*.{js,jsx,ts,tsx}',
    ],
};

export default updatedConfig;
