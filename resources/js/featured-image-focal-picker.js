// Alpine.js Featured Image Focal Point Picker Component for Filament Modal
function featuredImageFocalPicker() {
    // Ensure this function is preserved by bundlers
    featuredImageFocalPicker.displayName = 'featuredImageFocalPicker';

    return {
        focalX: 50,
        focalY: 50,
        imagePreview: null,
        componentId: null,

        init() {
            // Generate unique component ID for this instance
            this.componentId = 'featured-focal-picker-' + Math.random().toString(36).substr(2, 9);

            // Initialize focal points from hidden inputs
            this.initializeFocalPoints();

            // Watch for modal inputs and sync with our display
            this.$watch('focalX', (value) => {
                this.updateHiddenInput('modal_featured_image_focal_x', value);
            });

            this.$watch('focalY', (value) => {
                this.updateHiddenInput('modal_featured_image_focal_y', value);
            });

            // Check for existing image in the modal
            this.checkForExistingImage();

            // Listen for file uploads in the modal
            this.setupFileUploadListener();

            // Setup input change listeners
            this.setupInputListeners();

            // Listen for Livewire events
            this.setupLivewireEventListener();
        },

        initializeFocalPoints() {
            // In Filament modals, inputs use ID attributes, not name attributes
            const focalXInput = document.querySelector('[id*="modal_featured_image_focal_x"]');
            const focalYInput = document.querySelector('[id*="modal_featured_image_focal_y"]');

            if (focalXInput && focalXInput.value) {
                this.focalX = parseFloat(focalXInput.value) || 50;
            }
            if (focalYInput && focalYInput.value) {
                this.focalY = parseFloat(focalYInput.value) || 50;
            }
        },

        checkForExistingImage() {
            // Check if there's already an image in the featured image upload component
            const modalContainer = this.$el.closest('.fi-modal');
            if (!modalContainer) return;

            // Multiple strategies to find the uploaded image

            // Strategy 1: Look for FilePond hidden input with image URL
            const filePondInput = modalContainer.querySelector('input[name="filepond"][type="hidden"]');
            if (filePondInput && filePondInput.value && (filePondInput.value.startsWith('http') || filePondInput.value.startsWith('/'))) {
                this.imagePreview = filePondInput.value;
                return;
            }

            // Strategy 2: Look for Filament file upload preview images
            const uploadPreview = modalContainer.querySelector('[data-field-wrapper="featured_image"] img, [wire\\:key*="featured_image"] img');
            if (uploadPreview && uploadPreview.src && !uploadPreview.src.includes('data:')) {
                this.imagePreview = uploadPreview.src;
                return;
            }

            // Strategy 3: Look for any image in the file upload area
            const fileUploadImages = modalContainer.querySelectorAll('[x-data*="fileUpload"] img[src], .fi-fo-file-upload img[src]');
            for (const img of fileUploadImages) {
                if (img.src && !img.src.includes('data:') && !img.src.includes('placeholder')) {
                    this.imagePreview = img.src;
                    return;
                }
            }

            // Strategy 4: Look for Spatie Media Library images
            const spatieImages = modalContainer.querySelectorAll('img[src*="/storage/"], img[src*="livewire-tmp"]');
            for (const img of spatieImages) {
                if (img.src) {
                    this.imagePreview = img.src;
                    return;
                }
            }

            // Strategy 5: Check all images in the modal and pick the most likely candidate
            const allImages = modalContainer.querySelectorAll('img[src]');
            for (const img of allImages) {
                // Skip placeholder, icon, or data URLs
                if (img.src &&
                    !img.src.includes('data:') &&
                    !img.src.includes('placeholder') &&
                    !img.src.includes('icon') &&
                    img.naturalWidth > 50 && // Skip small icons
                    img.naturalHeight > 50) {
                    this.imagePreview = img.src;
                    return;
                }
            }
        },

        setupFileUploadListener() {
            // Watch for file upload changes in the modal
            const modalContainer = this.$el.closest('.fi-modal');
            if (!modalContainer) return;

            const fileInput = modalContainer.querySelector('input[type="file"]');
            if (fileInput) {
                fileInput.addEventListener('change', (e) => {
                    if (e.target.files && e.target.files.length > 0) {
                        this.loadImagePreview(e.target.files[0]);
                    }
                });
            }

            // Also listen for Livewire file upload events
            if (window.Livewire) {
                window.Livewire.hook('message.processed', (message, component) => {
                    // Small delay to let the DOM update
                    setTimeout(() => {
                        this.checkForExistingImage();
                    }, 100);
                });
            }
        },

        setupInputListeners() {
            // Listen for changes to the hidden inputs (from external sources)
            // In Filament modals, use ID selectors
            const focalXInput = document.querySelector('[id*="modal_featured_image_focal_x"]');
            const focalYInput = document.querySelector('[id*="modal_featured_image_focal_y"]');

            if (focalXInput) {
                focalXInput.addEventListener('input', (e) => {
                    this.focalX = parseFloat(e.target.value) || 50;
                });
            }

            if (focalYInput) {
                focalYInput.addEventListener('input', (e) => {
                    this.focalY = parseFloat(e.target.value) || 50;
                });
            }
        },

        loadImagePreview(file) {
            if (!file) {
                this.imagePreview = null;
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

            this.updateFocalPoint();
        },

        updateFocalPoint() {
            // Ensure values are within bounds
            this.focalX = Math.max(0, Math.min(100, parseFloat(this.focalX) || 50));
            this.focalY = Math.max(0, Math.min(100, parseFloat(this.focalY) || 50));

            // Update the hidden inputs
            this.updateHiddenInput('modal_featured_image_focal_x', this.focalX);
            this.updateHiddenInput('modal_featured_image_focal_y', this.focalY);
        },

        updateHiddenInput(fieldName, value) {
            // In Filament modals, inputs use ID attributes with wire:model
            // Look for inputs by ID pattern instead of name
            const input = document.querySelector(`[id*="${fieldName}"]`);
            if (input) {
                input.value = value;
                // Trigger input event to notify Livewire
                input.dispatchEvent(new Event('input', { bubbles: true }));
            }
        },

        setupLivewireEventListener() {
            // Listen for custom Livewire event when featured image is uploaded/changed
            if (window.Livewire) {
                Livewire.on('featuredImageUploaded', (eventData) => {
                    const data = Array.isArray(eventData) ? eventData[0] : eventData;

                    if (data && data.imageUrl) {
                        this.imagePreview = data.imageUrl;

                        // Reset focal points to center or use provided values
                        this.focalX = data.focalX || 50;
                        this.focalY = data.focalY || 50;
                        this.updateFocalPoint();
                    }
                });
            }
        },

        resetFocalPoint() {
            this.focalX = 50;
            this.focalY = 50;
            this.updateFocalPoint();
        },

        // Helper to get current focal point values as percentage string for CSS
        getFocalPointStyle() {
            return `left: ${this.focalX}%; top: ${this.focalY}%;`;
        }
    };
}

// Make it globally available for x-load
if (typeof window !== 'undefined') {
    window.featuredImageFocalPicker = featuredImageFocalPicker;
}

export default featuredImageFocalPicker;