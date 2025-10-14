import './bootstrap';

function generateUUID() {
    return 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, function(c) {
        const r = Math.random() * 16 | 0,
        v = c === 'x' ? r : (r & 0x3 | 0x8);
        return v.toString(16);
    });
}

// Expose to global scope so Blade inline scripts can call it when Vite bundles the file
window.generateUUID = generateUUID;

export { generateUUID };
