import './bootstrap';
import searchBox from './search-box';

// Alpine.js comes with Livewire. Do not import or start Alpine here.
// Chart.js and SortableJS are imported only in admin scripts (Phase 4 and 7),
// so storefront visitors never download them.

// Livewire starts Alpine after this script, so the component is known in time.
document.addEventListener('alpine:init', () => {
    window.Alpine.data('searchBox', searchBox);
});
