/** @type {import('tailwindcss').Config} */
export default {
  content: [
    './app/Modules/**/Views/**/*.php',
    './app/Views/**/*.php',
  ],
  theme: {
    extend: {
      fontFamily: {
        sans: ['"Inter"', 'system-ui', '-apple-system', 'Segoe UI', 'sans-serif'],
        display: ['"Fraunces"', '"Inter"', 'serif'],
      },
      colors: {
        // Khoobie brand palette — derived from the "khoobie" logo coral + dots
        brand: {
          50:  '#FFF5F3',
          100: '#FFE4DF',
          200: '#FFC7BC',
          300: '#FFA191',
          400: '#FF7B65',
          500: '#FF6F61', // primary coral (the "khoobie" colour)
          600: '#E94B3C',
          700: '#C53A2C',
          800: '#A02E22',
          900: '#7A2218',
        },
        // The yellow "o" in the logo
        accent: {
          50:  '#FFFBEB',
          100: '#FEF3C7',
          400: '#FDD45C',
          500: '#F9C13C',
          600: '#E0A721',
        },
        // The blue "o" in the logo
        sky: {
          // Tailwind sky-* already exists — extending with brand-tuned variants
          550: '#1EA5D8',
        },
        // The pink dots in the logo
        bloom: {
          400: '#FF9DC1',
          500: '#F574A8',
          600: '#D9518C',
        },
      },
      boxShadow: {
        'cta':       '0 10px 25px -5px rgba(255, 111, 97, 0.4)',
        'cta-lg':    '0 20px 40px -10px rgba(255, 111, 97, 0.45)',
        'soft':      '0 4px 20px -4px rgba(15, 23, 42, 0.06)',
        'soft-lg':   '0 12px 36px -10px rgba(15, 23, 42, 0.12)',
      },
      borderRadius: {
        '4xl': '2rem',
      },
      animation: {
        'fade-in':  'fadeIn 0.4s ease-out',
        'slide-up': 'slideUp 0.3s ease-out',
      },
      keyframes: {
        fadeIn:   { '0%': { opacity: 0 },  '100%': { opacity: 1 } },
        slideUp:  { '0%': { opacity: 0, transform: 'translateY(12px)' }, '100%': { opacity: 1, transform: 'translateY(0)' } },
      },
    },
  },
  plugins: [],
}
