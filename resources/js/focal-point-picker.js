// Alpine.js Focal Point Picker Component for Filament
export default function focalPointPicker(existingImageSrc = null, existingFocalX = 50, existingFocalY = 50) {
    // Ensure this function is preserved by bundlers
    focalPointPicker.displayName = 'focalPointPicker';

    return {
        focalX: existingFocalX,
        focalY: existingFocalY,
        imagePreview: existingImageSrc,

        init() {
            console.log('Focal-picker-init', {
                existingImageSrc: existingImageSrc,
                existingFocalX: existingFocalX,
                existingFocalY: existingFocalY,
                imagePreview: this.imagePreview,
                wire: this.$wire
            });

            // If we have an existing image, load it immediately
            if (existingImageSrc) {
                console.log('Loading existing image:', existingImageSrc);
                this.imagePreview = existingImageSrc;
            }

            // Initialize from Livewire data - wait a moment for Livewire to be ready
            this.$nextTick(() => {
                this.updateFromLivewireData();
            });

            // Watch for changes in Livewire data to reinitialize when switching images
            this.$watch('$wire.data.focal_x', (newValue) => {
                if (newValue !== undefined && newValue !== this.focalX) {
                    console.log('Livewire focal_x changed from', this.focalX, 'to', newValue);
                    this.focalX = parseFloat(newValue) || 50;
                }
            });

            this.$watch('$wire.data.focal_y', (newValue) => {
                if (newValue !== undefined && newValue !== this.focalY) {
                    console.log('Livewire focal_y changed from', this.focalY, 'to', newValue);
                    this.focalY = parseFloat(newValue) || 50;
                }
            });

            // Listen for Livewire events when new images are uploaded
            this.setupLivewireEventListener();

            // Watch for focal point changes and sync to Livewire
            this.$watch('focalX', (value) => {
                console.log('FocalX changed to:', value);
                console.log('$wire before set:', this.$wire.data);
                try {
                    this.$wire.set('data.focal_x', value);
                    console.log('Successfully set data.focal_x to:', value);
                    console.log('$wire after set:', this.$wire.data);
                } catch (error) {
                    console.error('Error setting focal_x:', error);
                }
            });

            this.$watch('focalY', (value) => {
                console.log('FocalY changed to:', value);
                console.log('$wire before set:', this.$wire.data);
                try {
                    this.$wire.set('data.focal_y', value);
                    console.log('Successfully set data.focal_y to:', value);
                    console.log('$wire after set:', this.$wire.data);
                } catch (error) {
                    console.error('Error setting focal_y:', error);
                }
            });

            // Always check for uploaded files, even if we have an existing image
            this.checkForExistingImage();
            setTimeout(() => {
                console.log('Delayed check for existing image, $wire:', this.$wire);
                this.checkForExistingImage();
            }, 100);

            // Note: Removed $wire.data watchers since they point to the wrong component
        },

        checkForExistingImage() {
            console.log('Checking for existing image...', {
                wire: this.$wire,
                data: this.$wire?.data,
                dataKeys: this.$wire?.data ? Object.keys(this.$wire.data) : [],
                wireKeys: this.$wire ? Object.keys(this.$wire) : []
            });

            // Scenario 2: New upload - check data.file
            if (this.$wire?.data?.file) {
                console.log('Found new upload in data.file:', this.$wire.data.file);
                this.loadImagePreview(this.$wire.data.file);
                return;
            }

            // Scenario 3: Check all data properties for potential file references
            if (this.$wire?.data) {
                const data = this.$wire.data;
                console.log('Inspecting all data properties:', data);

                // Look for any property that might contain file data
                for (const [key, value] of Object.entries(data)) {
                    console.log(`Data property ${key}:`, value);
                    if (value && typeof value === 'object' && (value.temporary_url || value.url)) {
                        console.log(`Found file in data.${key}:`, value);
                        this.loadImagePreview(value);
                        return;
                    }
                }
            }

            // Scenario 4: Look for Filament file upload preview
            const filePreview = document.querySelector('[x-data*="fileUpload"] img[src]');
            if (filePreview) {
                console.log('Found file preview in DOM:', filePreview.src);
                this.loadImagePreview(filePreview.src);
                return;
            }

            // Scenario 5: Try to find file upload component and its current value
            const fileInput = document.querySelector('[wire\\:model*="file"]');
            if (fileInput && fileInput.files && fileInput.files.length > 0) {
                console.log('Found file in DOM input:', fileInput.files[0]);
                this.loadImagePreview(fileInput.files[0]);
                return;
            }

            console.log('No image found to display');
        },

        loadImagePreview(file) {
            console.log('Loading image preview for:', file);

            if (!file) {
                this.imagePreview = null;
                return;
            }

            // Handle Livewire temporary file object (has temporary_url property)
            if (typeof file === 'object' && file.temporary_url) {
                console.log('Using temporary URL:', file.temporary_url);
                this.imagePreview = file.temporary_url;
                return;
            }

            // Handle Livewire temporary file (might have different structure)
            if (typeof file === 'object' && file.url) {
                console.log('Using file URL:', file.url);
                this.imagePreview = file.url;
                return;
            }

            // Handle File object from direct upload
            if (file && typeof file === 'object' && file.constructor && file.constructor.name === 'File') {
                console.log('Using FileReader for File object');
                const reader = new FileReader();
                reader.onload = (e) => {
                    this.imagePreview = e.target.result;
                    console.log('FileReader result set');
                };
                reader.readAsDataURL(file);
                return;
            }

            // Handle URL string (for existing images)
            if (typeof file === 'string' && (file.startsWith('http') || file.startsWith('/') || file.includes('livewire-tmp'))) {
                console.log('Using string URL:', file);
                this.imagePreview = file;
                return;
            }

            console.log('Unable to load image preview for:', typeof file, file);
        },

        setFocalPoint(event) {
            if (!this.imagePreview) return;

            const rect = this.$refs.previewArea.getBoundingClientRect();
            const x = ((event.clientX - rect.left) / rect.width) * 100;
            const y = ((event.clientY - rect.top) / rect.height) * 100;

            this.focalX = Math.round(Math.max(0, Math.min(100, x)) * 10) / 10;
            this.focalY = Math.round(Math.max(0, Math.min(100, y)) * 10) / 10;

            console.log('setFocalPoint called:', { x: this.focalX, y: this.focalY });

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
            const oldX = this.focalX;
            const oldY = this.focalY;

            this.focalX = Math.max(0, Math.min(100, parseFloat(this.focalX) || 50));
            this.focalY = Math.max(0, Math.min(100, parseFloat(this.focalY) || 50));

            console.log('updateFocalPoint called:', {
                oldX, oldY,
                newX: this.focalX,
                newY: this.focalY,
                wireData: this.$wire?.data
            });
        },

        resetFocalPoint() {
            this.focalX = 50;
            this.focalY = 50;
            this.updateFocalPoint();
        },

        resetFocalPointToCenter() {
            console.log('Resetting focal point to center for new upload');
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
                console.log('Initialized focalX from Livewire:', this.focalX);
            }
            if (this.$wire.data && this.$wire.data.focal_y !== undefined) {
                this.focalY = parseFloat(this.$wire.data.focal_y) || 50;
                console.log('Initialized focalY from Livewire:', this.focalY);
            }
        },

        setupLivewireEventListener() {
            console.log('Setting up Livewire event listener for image uploads');

            // Listen for the Livewire event dispatched when a new image is uploaded
            Livewire.on('enhancedImageUploaded', (eventData) => {
                console.log('Received enhancedImageUploaded event:', eventData);

                // Livewire events come as an array, get the first element
                const data = Array.isArray(eventData) ? eventData[0] : eventData;

                // Data should contain: temporaryUrl, focalX, focalY
                if (data && data.temporaryUrl) {
                    console.log('Loading new temporary image:', data.temporaryUrl);
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

                    console.log('Focal point reset to center for new upload');
                }
            });
        }
    };
}


