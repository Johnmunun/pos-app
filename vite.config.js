import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import react from '@vitejs/plugin-react';

export default defineConfig({
    plugins: [
        laravel({
            input: 'resources/js/app.jsx',
            refresh: true,
        }),
        react(),
    ],
    build: {
        rollupOptions: {
            output: {
                manualChunks(id) {
                    if (!id.includes('node_modules')) {
                        return;
                    }
                    if (id.includes('react-dom') || id.includes('react/') || id.includes('scheduler')) {
                        return 'vendor-react';
                    }
                    if (id.includes('@inertiajs')) {
                        return 'vendor-inertia';
                    }
                    if (id.includes('lucide-react')) {
                        return 'vendor-icons';
                    }
                    if (id.includes('recharts') || id.includes('chart.js')) {
                        return 'vendor-charts';
                    }
                    if (id.includes('firebase')) {
                        return 'vendor-firebase';
                    }
                    return 'vendor';
                },
            },
        },
    },
    optimizeDeps: {
        include: ['react-quill-new', 'quill'],
    },
});
