/** @type {import('tailwindcss').Config} */
module.exports = {
  content: [
    './*.php',
    './template-parts/**/*.php',
    './blocks/**/*.php',
    './inc/**/*.php',
    './src/**/*.js',
  ],
  theme: {
    container: {
      screens: {},
    },
    fontFamily: {
      sans: ['Inter', 'ui-sans-serif', 'system-ui', 'sans-serif'],
      serif: ['Playfair Display', 'ui-serif', 'Georgia', 'serif'],
    },
    extend: {
      colors: {
        'jxm-navy': '#1a2238',
        'jxm-black': '#121212',
        'jxm-gold': '#c5a46e',
        'jxm-cream': '#f7f4ef',
        'jxm-slate': '#e2e8f0',
        primary: '#0F2138',
        accent: '#C9A661',
        secondary: '#EDE6D6',
        supporting: '#3C4F68',
        base: '#FFFFFF',
      },
      boxShadow: {
        soft: '0 4px 20px rgba(0,0,0,0.08)',
      }
    },
  },
  plugins: [],
};
