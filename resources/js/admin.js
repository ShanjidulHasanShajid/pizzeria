import Sortable from 'sortablejs';

// Any <ul data-sortable> becomes drag-to-reorder. Each item needs data-id,
// and its drag handle needs data-handle. After a drop, the list sends a
// "sorted" event whose detail.ids is the new order of the ids.
document.querySelectorAll('[data-sortable]').forEach((list) => {
    Sortable.create(list, {
        handle: '[data-handle]',
        animation: 150,
        onEnd: () => {
            const ids = [...list.querySelectorAll('[data-id]')].map((item) => item.dataset.id);
            list.dispatchEvent(new CustomEvent('sorted', { detail: { ids }, bubbles: true }));
        },
    });
});