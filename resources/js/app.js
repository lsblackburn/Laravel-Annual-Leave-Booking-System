import Alpine from 'alpinejs';

window.Alpine = Alpine;

Alpine.start();


function themeHandler() {
    return {
        dark: false,

        init() {
            // load saved theme
            const saved = localStorage.getItem('theme');

            if (saved) {
                this.dark = saved === 'dark';
            } else {
                // fallback to system preference
                this.dark = window.matchMedia('(prefers-color-scheme: dark)').matches;
            }

            this.apply();
        },

        toggle() {
            this.dark = !this.dark;
            localStorage.setItem('theme', this.dark ? 'dark' : 'light');
            this.apply();
        },

        apply() {
            document.documentElement.setAttribute(
                'data-theme',
                this.dark ? 'dark' : 'light'
            );
        }
    }
}