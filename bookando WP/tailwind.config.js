/** @type {import('tailwindcss').Config} */
export default {
  important: '.bookando-admin-page',
  corePlugins: {
    preflight: false,
  },
  content: [
    './src/**/*.{vue,js,ts,jsx,tsx}',
    './src/**/*.php',
  ],
  theme: {
    extend: {
      fontFamily: {
        sans: ['Inter', 'sans-serif'],
      },
      fontSize: {
        xs: 'clamp(0.7rem, 0.65rem + 0.2vw, 0.75rem)',
        sm: 'clamp(0.8rem, 0.75rem + 0.25vw, 0.875rem)',
        base: 'clamp(0.9rem, 0.85rem + 0.25vw, 1rem)',
        lg: 'clamp(1.05rem, 1rem + 0.3vw, 1.125rem)',
        xl: 'clamp(1.15rem, 1.1rem + 0.4vw, 1.25rem)',
        '2xl': 'clamp(1.35rem, 1.25rem + 0.5vw, 1.5rem)',
        '3xl': 'clamp(1.75rem, 1.5rem + 1vw, 2rem)',
        '4xl': 'clamp(2.25rem, 2rem + 1.5vw, 2.75rem)',
      },
      colors: {
        // Brand/Primary Colors (Original Design)
        brand: {
          50: '#f0f9ff',
          100: '#e0f2fe',
          500: '#0ea5e9',
          600: '#0284c7',
          700: '#0369a1',
          900: '#0c4a6e',
        },
        slate: {
          850: '#15202b',
        },
        // Status Colors
        success: {
          DEFAULT: '#28c76f',
          50: '#e8faf0',
          100: '#d1f5e1',
          500: '#28c76f',
          700: '#1f9e59',
        },
        warning: {
          DEFAULT: '#F7C948',
          50: '#fef9e7',
          100: '#fdf3ce',
          500: '#F7C948',
          700: '#d4a62d',
        },
        danger: {
          DEFAULT: '#E14343',
          50: '#fdeaea',
          100: '#fbd5d5',
          500: '#E14343',
          700: '#b83636',
        },
        info: {
          DEFAULT: '#38BDF8',
          50: '#e6f7ff',
          100: '#cdefff',
          500: '#38BDF8',
          700: '#0891c9',
        },
        // Accent Blue
        accent: {
          DEFAULT: '#0ea5e9',
          50: '#f0f9ff',
          100: '#e0f2fe',
          500: '#0ea5e9',
          700: '#0369a1',
        },
      },
    },
  },
  plugins: [],
}
