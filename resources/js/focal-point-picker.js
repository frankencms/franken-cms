// Alpine.js Focal Point Picker Component for Filament
export default function focalPointPicker() {
    // Ensure this function is preserved by bundlers
    focalPointPicker.displayName = 'focalPointPicker';

    return {
        focalX: 50,
        focalY: 50,
        imagePreview: null,

        init() {

            console.log('Focal-picker-init');
            // Initialize from wire model values if they exist
            const focalXInput = this.$el.querySelector('input[x-model="focalX"]');
            const focalYInput = this.$el.querySelector('input[x-model="focalY"]');

            if (focalXInput && focalXInput.value) {
                this.focalX = parseFloat(focalXInput.value) || 50;
            }
            if (focalYInput && focalYInput.value) {
                this.focalY = parseFloat(focalYInput.value) || 50;
            }

            // Watch for file input changes
            this.$watch('$wire.data.file', (newFile) => {
                if (newFile) {
                    this.loadImagePreview(newFile);
                }
            });

            // Check if there's already a file selected
            if (this.$wire.data.file) {
                this.loadImagePreview(this.$wire.data.file);
            }
        },

        loadImagePreview(file) {
            if (file && typeof file === 'object' && file.constructor.name === 'File') {
                const reader = new FileReader();
                reader.onload = (e) => {
                    this.imagePreview = e.target.result;
                };
                reader.readAsDataURL(file);
            } else if (typeof file === 'string' && file.startsWith('http')) {
                this.imagePreview = file;
            }
        },

        setFocalPoint(event) {
            if (!this.imagePreview) return;

            const rect = this.$refs.previewArea.getBoundingClientRect();
            const x = ((event.clientX - rect.left) / rect.width) * 100;
            const y = ((event.clientY - rect.top) / rect.height) * 100;

            this.focalX = Math.round(Math.max(0, Math.min(100, x)) * 10) / 10;
            this.focalY = Math.round(Math.max(0, Math.min(100, y)) * 10) / 10;

            this.updateFocalPoint();
        },

        updateFocalPoint() {
            // Ensure values are within bounds
            this.focalX = Math.max(0, Math.min(100, parseFloat(this.focalX) || 50));
            this.focalY = Math.max(0, Math.min(100, parseFloat(this.focalY) || 50));
        },

        resetFocalPoint() {
            this.focalX = 50;
            this.focalY = 50;
            this.updateFocalPoint();
        }
    };
}


