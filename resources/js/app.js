import './bootstrap';

import Alpine from 'alpinejs';

window.Alpine = Alpine;

// Dark mode initialization
document.addEventListener('DOMContentLoaded', function() {
    // Check for saved dark mode preference or default to system preference
    const darkMode = localStorage.getItem('darkMode') === 'true' || 
                    (!localStorage.getItem('darkMode') && window.matchMedia('(prefers-color-scheme: dark)').matches);
    
    if (darkMode) {
        document.documentElement.classList.add('dark');
    }
});

Alpine.start();
