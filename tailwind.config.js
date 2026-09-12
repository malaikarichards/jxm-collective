/** @type {import('tailwindcss').Config} */
module.exports = {
  content: [
    './*.php',
    './template-parts/**/*.php',
    './src/**/*.js',
  ],
  theme: {
    fontFamily: {
      sans: ['Outfit', 'ui-sans-serif', 'system-ui', 'sans-serif'],
      serif: ['Cormorant Garamond', 'ui-serif', 'Georgia', 'serif'],
    },
    extend: {
      colors: {
        'jxm-navy': '#1a2238',
        'jxm-black': '#121212',
        'jxm-gold': '#c5a46e',
        'jxm-cream': '#f7f4ef',
        'jxm-slate': '#e2e8f0',
      },
    },
  },
  plugins: [],
};
