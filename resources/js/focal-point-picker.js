// Alpine.js Focal Point Picker Component for Filament
export default function focalPointPicker(existingImageSrc = null, existingFocalX = 50, existingFocalY = 50) {
    // Ensure this function is preserved by bundlers
    focalPointPicker.displayName = 'focalPointPicker';

    return {
        focalX: existingFocalX,
        focalY: existingFocalY,
        imagePreview: existingImageSrc,

        init() {
            // If we have an existing image, load it immediately
            if (existingImageSrc) {
                this.imagePreview = existingImageSrc;
            }

            // Initialize from Livewire data - wait a moment for Livewire to be ready
            this.$nextTick(() => {
                this.updateFromLivewireData();
            });

            // Watch for changes in Livewire data to reinitialize when switching images
            this.$watch('$wire.data.focal_x', (newValue) => {
                if (newValue !== undefined && newValue !== this.focalX) {
                    this.focalX = parseFloat(newValue) || 50;
                }
            });

            this.$watch('$wire.data.focal_y', (newValue) => {
                if (newValue !== undefined && newValue !== this.focalY) {
                    this.focalY = parseFloat(newValue) || 50;
                }
            });

            // Listen for Livewire events when new images are uploaded
            this.setupLivewireEventListener();

            // Watch for focal point changes and sync to Livewire
            this.$watch('focalX', (value) => {
                this.$wire.set('data.focal_x', value);
            });

            this.$watch('focalY', (value) => {
                this.$wire.set('data.focal_y', value);
            });

            // Always check for uploaded files, even if we have an existing image
            this.checkForExistingImage();
            setTimeout(() => {
                this.checkForExistingImage();
            }, 100);

            // Note: Removed $wire.data watchers since they point to the wrong component
        },

        checkForExistingImage() {
            // Scenario 2: New upload - check data.file
            if (this.$wire?.data?.file) {
                this.loadImagePreview(this.$wire.data.file);
                return;
            }

            // Scenario 3: Check all data properties for potential file references
            if (this.$wire?.data) {
                const data = this.$wire.data;

                // Look for any property that might contain file data
                for (const [key, value] of Object.entries(data)) {
                    if (value && typeof value === 'object' && (value.temporary_url || value.url)) {
                        this.loadImagePreview(value);
                        return;
                    }
                }
            }

            // Scenario 4: Look for Filament file upload preview
            const filePreview = document.querySelector('[x-data*="fileUpload"] img[src]');
            if (filePreview) {
                this.loadImagePreview(filePreview.src);
                return;
            }

            // Scenario 5: Try to find file upload component and its current value
            const fileInput = document.querySelector('[wire\\:model*="file"]');
            if (fileInput && fileInput.files && fileInput.files.length > 0) {
                this.loadImagePreview(fileInput.files[0]);
                return;
            }
        },

        loadImagePreview(file) {
            if (!file) {
                this.imagePreview = null;
                return;
            }

            // Handle Livewire temporary file object (has temporary_url property)
            if (typeof file === 'object' && file.temporary_url) {
                this.imagePreview = file.temporary_url;
                return;
            }

            // Handle Livewire temporary file (might have different structure)
            if (typeof file === 'object' && file.url) {
                this.imagePreview = file.url;
                return;
            }

            // Handle File object from direct upload
            if (file && typeof file === 'object' && file.constructor && file.constructor.name === 'File') {
                const reader = new FileReader();
                reader.onload = (e) => {
                    this.imagePreview = e.target.result;
                };
                reader.readAsDataURL(file);
                return;
            }

            // Handle URL string (for existing images)
            if (typeof file === 'string' && (file.startsWith('http') || file.startsWith('/') || file.includes('livewire-tmp'))) {
                this.imagePreview = file;
                return;
            }
        },

        setFocalPoint(event) {
            if (!this.imagePreview) return;

            const rect = this.$refs.previewArea.getBoundingClientRect();
            const x = ((event.clientX - rect.left) / rect.width) * 100;
            const y = ((event.clientY - rect.top) / rect.height) * 100;

            this.focalX = Math.round(Math.max(0, Math.min(100, x)) * 10) / 10;
            this.focalY = Math.round(Math.max(0, Math.min(100, y)) * 10) / 10;

            // Also directly trigger input events to ensure wire:model picks up the change
            const focalXInput = document.querySelector('[wire\\:model*="focal_x"]');
            const focalYInput = document.querySelector('[wire\\:model*="focal_y"]');

            if (focalXInput) {
                focalXInput.value = this.focalX;
                focalXInput.dispatchEvent(new Event('input', { bubbles: true }));
            }
            if (focalYInput) {
                focalYInput.value = this.focalY;
                focalYInput.dispatchEvent(new Event('input', { bubbles: true }));
            }

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
        },

        resetFocalPointToCenter() {
            this.focalX = 50;
            this.focalY = 50;
            // Trigger the manual input updates to sync with Livewire
            const focalXInput = document.querySelector('[wire\\:model*="focal_x"]');
            const focalYInput = document.querySelector('[wire\\:model*="focal_y"]');

            if (focalXInput) {
                focalXInput.value = 50;
                focalXInput.dispatchEvent(new Event('input', { bubbles: true }));
            }
            if (focalYInput) {
                focalYInput.value = 50;
                focalYInput.dispatchEvent(new Event('input', { bubbles: true }));
            }
        },

        updateFromLivewireData() {
            if (this.$wire.data && this.$wire.data.focal_x !== undefined) {
                this.focalX = parseFloat(this.$wire.data.focal_x) || 50;
            }
            if (this.$wire.data && this.$wire.data.focal_y !== undefined) {
                this.focalY = parseFloat(this.$wire.data.focal_y) || 50;
            }
        },

        setupLivewireEventListener() {
            // Listen for the Livewire event dispatched when a new image is uploaded
            Livewire.on('enhancedImageUploaded', (eventData) => {
                // Livewire events come as an array, get the first element
                const data = Array.isArray(eventData) ? eventData[0] : eventData;

                // Data should contain: temporaryUrl, focalX, focalY
                if (data && data.temporaryUrl) {
                    this.imagePreview = data.temporaryUrl;

                    // Reset focal points to center
                    this.focalX = data.focalX || 50;
                    this.focalY = data.focalY || 50;

                    // Update the hidden inputs to sync with Livewire
                    const focalXInput = document.querySelector('[wire\\:model*="focal_x"]');
                    const focalYInput = document.querySelector('[wire\\:model*="focal_y"]');

                    if (focalXInput) {
                        focalXInput.value = this.focalX;
                        focalXInput.dispatchEvent(new Event('input', { bubbles: true }));
                    }
                    if (focalYInput) {
                        focalYInput.value = this.focalY;
                        focalYInput.dispatchEvent(new Event('input', { bubbles: true }));
                    }
                }
            });
        }
    };
}


