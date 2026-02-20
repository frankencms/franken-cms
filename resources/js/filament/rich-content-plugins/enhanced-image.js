import { Node, nodeInputRule } from '@tiptap/core';

export const EnhancedImage = Node.create({
    name: 'image',

    addOptions() {
        return {
            HTMLAttributes: {},
        };
    },

    inline: false,

    group: 'block',

    draggable: true,

    addAttributes() {
        return {
            src: {
                default: null,
            },
            alt: {
                default: null,
            },
            title: {
                default: null,
            },
            caption: {
                default: null,
            },
            attribution: {
                default: null,
            },
            loading: {
                default: 'lazy',
            },
            focal_x: {
                default: 50,
                parseHTML: element => {
                    // Check both figure and img elements for data-focal-x
                    const img = element.querySelector('img');
                    return img?.getAttribute('data-focal-x') || element.getAttribute('data-focal-x') || 50;
                },
                renderHTML: attributes => {
                    if (!attributes.focal_x) return {};
                    return {
                        'data-focal-x': attributes.focal_x,
                    };
                },
            },
            focal_y: {
                default: 50,
                parseHTML: element => {
                    // Check both figure and img elements for data-focal-y
                    const img = element.querySelector('img');
                    return img?.getAttribute('data-focal-y') || element.getAttribute('data-focal-y') || 50;
                },
                renderHTML: attributes => {
                    if (!attributes.focal_y) return {};
                    return {
                        'data-focal-y': attributes.focal_y,
                    };
                },
            },
            width: {
                default: null,
            },
            height: {
                default: null,
            },
            id: {
                default: null,
                parseHTML: element => {
                    // Check both figure and img elements for data-id
                    const img = element.querySelector('img');
                    return img?.getAttribute('data-id') || element.getAttribute('data-id');
                },
                renderHTML: attributes => {
                    if (!attributes.id) return {};
                    return {
                        'data-id': attributes.id,
                    };
                },
            },
            css: {
                default: null,
                parseHTML: element => element.querySelector('img')?.className || null,
                renderHTML: attributes => {
                    return attributes.css ? { class: attributes.css } : {};
                },
            },
        };
    },

    parseHTML() {
        return [
            {
                tag: 'figure[data-type="enhanced-image"]',
                getAttrs: (element) => {
                    const img = element.querySelector('img');
                    const figcaption = element.querySelector('figcaption');
                    const attribution = element.querySelector('.enhanced-image-attribution');

                    return {
                        src: img?.getAttribute('src') || null,
                        alt: img?.getAttribute('alt') || null,
                        title: img?.getAttribute('title') || null,
                        loading: img?.getAttribute('loading') || 'lazy',
                        width: img?.getAttribute('width') || null,
                        height: img?.getAttribute('height') || null,
                        css: img?.getAttribute('class') || null,
                        caption: figcaption?.textContent || null,
                        attribution: attribution?.textContent || null,
                        focal_x: img?.getAttribute('data-focal-x') || element.getAttribute('data-focal-x') || 50,
                        focal_y: img?.getAttribute('data-focal-y') || element.getAttribute('data-focal-y') || 50,
                        id: img?.getAttribute('data-id') || element.getAttribute('data-id') || null,
                    };
                },
            },
            {
                // Also parse regular img tags as enhanced images to maintain compatibility
                tag: 'img[src]',
                getAttrs: (element) => {
                    return {
                        src: element.getAttribute('src') || null,
                        alt: element.getAttribute('alt') || null,
                        title: element.getAttribute('title') || null,
                        loading: element.getAttribute('loading') || 'lazy',
                        width: element.getAttribute('width') || null,
                        height: element.getAttribute('height') || null,
                        css: element.getAttribute('class') || null,
                        focal_x: element.getAttribute('data-focal-x') || 50,
                        focal_y: element.getAttribute('data-focal-y') || 50,
                        id: element.getAttribute('data-id') || null,
                    };
                },
            },
        ];
    },

    renderHTML({ HTMLAttributes }) {
        const {
            src,
            alt,
            title,
            caption,
            attribution,
            loading,
            focal_x,
            focal_y,
            width,
            height,
            id,
            css,
            ...rest
        } = HTMLAttributes;

        const imageAttrs = {
            src,
            alt,
            title,
            loading,
            width,
            height,
            ...rest,
        };

        // Apply CSS classes if provided
        if (css) {
            imageAttrs.class = css;
        }

        // Apply focal point as object-position if supported
        if (focal_x && focal_y) {
            imageAttrs.style = `object-position: ${focal_x}% ${focal_y}%;`;
        }

        // Also add data attributes to the img element for consistency
        if (id) {
            imageAttrs['data-id'] = id;
        }
        if (focal_x) {
            imageAttrs['data-focal-x'] = focal_x;
        }
        if (focal_y) {
            imageAttrs['data-focal-y'] = focal_y;
        }

        // Clean up undefined/null attributes
        Object.keys(imageAttrs).forEach(key => {
            if (imageAttrs[key] === null || imageAttrs[key] === undefined) {
                delete imageAttrs[key];
            }
        });

        const elements = [
            ['img', imageAttrs],
        ];

        // Add caption if present
        if (caption) {
            elements.push([
                'figcaption',
                { class: 'enhanced-image-caption' },
                caption,
            ]);
        }

        // Add attribution if present
        if (attribution) {
            elements.push([
                'div',
                { class: 'enhanced-image-attribution' },
                attribution,
            ]);
        }

        return [
            'figure',
            {
                'data-type': 'enhanced-image',
                'data-id': id,
                'data-focal-x': focal_x,
                'data-focal-y': focal_y,
            },
            ...elements,
        ];
    },

    addCommands() {
        return {
            setEnhancedImage: options => ({ commands }) => {
                return commands.insertContent({
                    type: this.name,
                    attrs: options,
                });
            },
        };
    },

    addInputRules() {
        return [
            nodeInputRule({
                find: /!\[(.+|:?)]\((\S+)(?:(?:\s+)["'](\S+)["'])?\)/,
                type: this.type,
                getAttributes: match => {
                    const [, alt, src, title] = match;
                    return { src, alt, title };
                },
            }),
        ];
    },
});

export default EnhancedImage;