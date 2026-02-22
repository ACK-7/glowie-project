import { defineConfig } from 'vite'
import react from '@vitejs/plugin-react'

// https://vitejs.dev/config/
export default defineConfig({
  plugins: [react()],
  server: {
    host: '0.0.0.0', // Listen on all network interfaces
    port: 5173,
    watch: {
      usePolling: true, // Required for hot reload in Docker
      interval: 1000,
    },
    hmr: {
      host: 'localhost',
      protocol: 'ws',
    },
  },
})
