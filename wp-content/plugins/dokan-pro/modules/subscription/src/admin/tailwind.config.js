import baseConfig from '../../../../base-tailwind.config';

/** @type {import('tailwindcss').Config} */
const updatedConfig = {
    ...baseConfig,
    content: [
        ...baseConfig.content,
        './modules/subscription/src/admin/**/*.{js,jsx,ts,tsx}',
        './modules/subscription/src/js/frontend/components/PricingCard.{js,jsx,ts,tsx}',
        './modules/subscription/src/js/frontend/components/SubscriptionPacks.{js,jsx,ts,tsx}',
    ],
};

export default updatedConfig;
