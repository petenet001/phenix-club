/** @type {import('tailwindcss').Config} */
export default {
  content: ['./index.html', './src/**/*.{js,jsx}'],
  theme: {
    extend: {
      colors: {
        // Mêmes teintes que l'application mobile (AppPalette) : orange de la marque en accent,
        // neutres légèrement chauds, aplats sans ombre colorée.
        phenix: {
          50: '#FBF3EC',
          100: '#F5E3D3',
          200: '#EBC6A6',
          300: '#DFA273',
          400: '#D5772F',
          500: '#C8611A',
          600: '#A84F14',
          700: '#873F12',
          800: '#6A3311',
          900: '#552B10',
        },
        slate: {
          50: '#FAFAF8',
          100: '#F6F5F2',
          200: '#E4E1DA',
          300: '#D3CFC7',
          400: '#9A9EA6',
          500: '#6B6F76',
          600: '#4B4F56',
          700: '#363A40',
          800: '#26282D',
          900: '#1C1D21',
          950: '#141518',
        },
      },
      // Animations d'entrée : "backwards" et non "both", pour ne laisser aucun transform
      // résiduel (il casserait le position: fixed de l'impression du reçu).
      keyframes: {
        'fade-in': { from: { opacity: '0' }, to: { opacity: '1' } },
        'fade-up': { from: { opacity: '0', transform: 'translateY(8px)' }, to: { opacity: '1', transform: 'translateY(0)' } },
        'scale-in': { from: { opacity: '0', transform: 'translateY(8px) scale(.96)' }, to: { opacity: '1', transform: 'translateY(0) scale(1)' } },
        'slide-in-right': { from: { opacity: '0', transform: 'translateX(24px)' }, to: { opacity: '1', transform: 'translateX(0)' } },
        pop: { '0%': { opacity: '0', transform: 'scale(.92)' }, '60%': { opacity: '1', transform: 'scale(1.02)' }, '100%': { transform: 'scale(1)' } },
        shake: { '0%, 100%': { transform: 'translateX(0)' }, '20%, 60%': { transform: 'translateX(-6px)' }, '40%, 80%': { transform: 'translateX(6px)' } },
        'grow-up': { from: { transform: 'scaleY(0)' }, to: { transform: 'scaleY(1)' } },
        float: { '0%, 100%': { transform: 'translateY(0)' }, '50%': { transform: 'translateY(-4px)' } },
      },
      animation: {
        'fade-in': 'fade-in .2s ease-out backwards',
        'fade-up': 'fade-up .35s cubic-bezier(.16,1,.3,1) backwards',
        'scale-in': 'scale-in .25s cubic-bezier(.16,1,.3,1) backwards',
        'slide-in-right': 'slide-in-right .3s cubic-bezier(.16,1,.3,1) backwards',
        pop: 'pop .4s cubic-bezier(.16,1,.3,1) backwards',
        shake: 'shake .4s ease-in-out',
        'grow-up': 'grow-up .6s cubic-bezier(.16,1,.3,1) backwards',
        float: 'float 4s ease-in-out infinite',
      },
    },
  },
  plugins: [],
}
