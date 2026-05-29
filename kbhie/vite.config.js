import { defineConfig } from 'vite'
import { resolve } from 'path'

export default defineConfig({
  publicDir: false,
  build: {
    outDir: 'public/assets',
    emptyOutDir: false,
    manifest: false,
    rollupOptions: {
      input: {
        app: resolve(__dirname, 'resources/js/app.js'),
        style: resolve(__dirname, 'resources/css/app.css'),
      },
      output: {
        entryFileNames: '[name].js',
        chunkFileNames: '[name].js',
        assetFileNames: (info) => {
          if (info.name === 'style.css') return 'app.css'
          return '[name][extname]'
        },
      },
    },
  },
})
