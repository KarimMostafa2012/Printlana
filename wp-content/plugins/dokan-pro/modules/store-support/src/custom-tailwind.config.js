import baseConfig from '../../../base-tailwind.config';

/** @type {import('tailwindcss').Config} */
const updatedConfig = {
    ...baseConfig,
    content: [ './modules/store-support/src/**/*.{js,jsx,ts,tsx}' ],
};

export default updatedConfig;
