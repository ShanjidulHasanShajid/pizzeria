// Suggestions below a search field. The form around it works without this script.
// Used by the component x-storefront.search-form as x-data="searchBox(url)".
const MIN_LENGTH = 2;

export default (url) => ({
    items: [],
    open: false,
    active: -1,
    request: null,

    async load(text) {
        this.request?.abort(); // an older answer must not overwrite a newer one
        const term = text.trim();

        if (term.length < MIN_LENGTH) {
            this.items = [];
            this.close();

            return;
        }

        const request = new AbortController();
        this.request = request;

        try {
            const response = await window.axios.get(url, { params: { q: term }, signal: request.signal });
            this.items = response.data;
            this.active = -1;
            this.open = this.items.length > 0;
        } catch {
            // Cancelled, too many requests or offline: simply no suggestions.
            if (this.request === request) {
                this.items = [];
                this.close();
            }
        }
    },

    move(step) {
        if (this.items.length === 0) {
            return;
        }

        this.open = true;
        this.active = (this.active + step + this.items.length + (this.active < 0 && step < 0 ? 1 : 0)) % this.items.length;
    },

    // Enter opens the marked suggestion; without one the form is sent.
    choose(event) {
        if (this.open && this.active >= 0) {
            event.preventDefault();
            window.location.href = this.items[this.active].url;
        }
    },

    close() {
        this.open = false;
        this.active = -1;
    },
});
