// Alpine.js Featured Image Focal Point Picker Component for Filament
export default function featuredImageFocalPicker(existingImageSrc = null, existingFocalX = 50, existingFocalY = 50, focalXPath = 'data.featured_image_focal_x', focalYPath = 'data.featured_image_focal_y') {
    // Ensure this function is preserved by bundlers
    featuredImageFocalPicker.displayName = 'featuredImageFocalPicker';

    return {
        focalX: existingFocalX || 50,
        focalY: existingFocalY || 50,
        imagePreview: existingImageSrc || null,
        componentId: null,
        focalXPath: focalXPath,
        focalYPath: focalYPath,

        init() {
            // Generate unique component ID for this instance
            this.componentId = 'featured-focal-picker-' + Math.random().toString(36).substr(2, 9);

            // If we have an existing image, load it immediately
            if (existingImageSrc) {
                this.imagePreview = existingImageSrc;
            }

            // Set initial focal points from parameters (these come from existing image data)
            this.focalX = parseFloat(existingFocalX) || 50;
            this.focalY = parseFloat(existingFocalY) || 50;

            // Force update Livewire data immediately with our parameter values
            // This ensures each instance gets the correct focal points
            if (this.$wire && this.$wire.set) {
                this.$wire.set(this.focalXPath, this.focalX);
                this.$wire.set(this.focalYPath, this.focalY);
            }

            // Initialize from Livewire data - wait a moment for Livewire to be ready
            this.$nextTick(() => {
                // Double-check our values are set correctly in Livewire
                if (this.$wire && this.$wire.set) {
                    this.$wire.set(this.focalXPath, this.focalX);
                    this.$wire.set(this.focalYPath, this.focalY);
                }
            });

            // Watch for changes in Livewire data to reinitialize when switching images
            if (this.$wire && this.$wire.data) {
                this.$watch(`$wire.${this.focalXPath}`, (newValue) => {
                    if (newValue !== undefined && newValue !== this.focalX) {
                        this.focalX = parseFloat(newValue) || 50;
                    }
                });

                this.$watch(`$wire.${this.focalYPath}`, (newValue) => {
                    if (newValue !== undefined && newValue !== this.focalY) {
                        this.focalY = parseFloat(newValue) || 50;
                    }
                });
            }

            // Listen for Livewire events when new images are uploaded
            this.setupLivewireEventListener();

            // Watch for focal point changes and sync to Livewire
            this.$watch('focalX', (value) => {
                if (this.$wire && this.$wire.set) {
                    this.$wire.set(this.focalXPath, value);
                }
            });

            this.$watch('focalY', (value) => {
                if (this.$wire && this.$wire.set) {
                    this.$wire.set(this.focalYPath, value);
                }
            });

        },

        checkForExistingImage() {
            // Ensure we have proper wire context
            if (!this.$wire) {
                return;
            }

            // Scenario 1: New upload - check data.featured_image
            if (this.$wire?.data?.featured_image) {
                this.loadImagePreview(this.$wire.data.featured_image);
                return;
            }

            // Scenario 2: Check all data properties for potential file references
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

            // Scenario 3: Look for Filament file upload preview
            const filePreview = document.querySelector('[x-data*="fileUpload"] img[src]');
            if (filePreview) {
                this.loadImagePreview(filePreview.src);
                return;
            }

            // Scenario 4: Try to find file upload component and its current value
            const fileInput = document.querySelector('[wire\\:model*="featured_image"]');
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

            // Values will be synced to Livewire via $watch
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
            // Values will be synced to Livewire via $watch
        },

        setupLivewireEventListener() {
            // Listen for the Livewire event dispatched when a featured image is uploaded
            Livewire.on('featuredImageUploaded', (eventData) => {
                // Livewire events come as an array, get the first element
                const data = Array.isArray(eventData) ? eventData[0] : eventData;

                // Data should contain: imageUrl, focalX, focalY
                if (data && data.imageUrl) {
                    this.imagePreview = data.imageUrl;

                    // Reset focal points to center or use provided values
                    this.focalX = data.focalX || 50;
                    this.focalY = data.focalY || 50;

                    // Values will be synced to Livewire via $watch
                }
            });
        }
    };
}