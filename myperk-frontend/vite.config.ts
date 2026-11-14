import react from '@vitejs/plugin-react'
import { defineConfig } from 'vite'

// https://vite.dev/config/
export default defineConfig({
  plugins: [react()],
  server: {
    allowedHosts: ['sb-3b9qi4ynf3p5.vercel.run'],
  },
  define: {
    'process.env.VITE_API_BASE': JSON.stringify(process.env.VITE_API_BASE || '/myperk/v1'),
  },
  css: {
    preprocessorOptions: {
      scss: {
        additionalData: '@import "./src/assets/css/myperk-design-system.css";',
      },
    },
  },
})
