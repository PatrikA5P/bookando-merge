/** @type {import('tailwindcss').Config} */
export default {
  important: '.bookando-admin-page',
  content: [
    './src/**/*.{vue,js,ts,jsx,tsx}',
    './src/**/*.php',
  ],
  theme: {
    extend: {
      colors: {
        // Brand/Primary Colors (bookando WP existing)
        brand: {
          50: '#e3fbf3',
          100: '#c7f7e8',
          200: '#90eed0',
          300: '#58e6b9',
          400: '#21dda1',
          500: '#12DE9D', // Primary
          600: '#0FB87F', // Primary Dark
          700: '#0c9465',
          800: '#09704b',
          900: '#064c32',
          950: '#033818',
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
          DEFAULT: '#0087E2',
          50: '#e6f4ff',
          100: '#cce9ff',
          500: '#0087E2',
          700: '#006bb3',
        },
      },
      borderRadius: {
        'sm': '8px',   // Inputs
        'md': '12px',  // Cards
        'lg': '16px',
        'xl': '20px',
        '2xl': '24px',
      },
      boxShadow: {
        'sm': '0 1px 4px rgba(0, 0, 0, 0.05)',
        'DEFAULT': '0 2px 8px rgba(0, 0, 0, 0.08)',
        'md': '0 4px 16px rgba(0, 0, 0, 0.10)',
        'lg': '0 6px 20px rgba(0, 0, 0, 0.12)',
        'xl': '0 8px 32px rgba(0, 0, 0, 0.15)',
      },
      spacing: {
        'xs': '4px',
        'sm': '8px',
        'md': '12px',
        'lg': '16px',
        'xl': '24px',
        '2xl': '32px',
        '3xl': '48px',
        '4xl': '64px',
      },
      fontSize: {
        'xs': '0.75rem',    // 12px
        'sm': '0.875rem',   // 14px
        'base': '1rem',     // 16px
        'lg': '1.125rem',   // 18px
        'xl': '1.25rem',    // 20px
        '2xl': '1.5rem',    // 24px
        '3xl': '1.875rem',  // 30px
        '4xl': '2.25rem',   // 36px
      },
      fontWeight: {
        'normal': '400',
        'medium': '500',
        'semibold': '600',
        'bold': '700',
        'extrabold': '800',
      },
    },
  },
  plugins: [],
}
