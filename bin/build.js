import * as esbuild from 'esbuild'

async function compile(options) {
    const context = await esbuild.context(options)

    await context.rebuild()
    await context.dispose()
}

compile({
    define: {
        'process.env.NODE_ENV': `'production'`,
    },
    bundle: true,
    mainFields: ['module', 'main'],
    platform: 'neutral',
    sourcemap: false,
    sourcesContent: false,
    treeShaking: true,
    target: ['es2020'],
    minify: true,
    entryPoints: ['./resources/js/filament/rich-content-plugins/enhanced-image.js'],
    outfile: './resources/dist/filament/rich-content-plugins/enhanced-image.js',
})




compile({
    define: {
        'process.env.NODE_ENV': `'production'`,
    },
    bundle: true,
    mainFields: ['module', 'main'],
    platform: 'neutral',
    sourcemap: false,
    sourcesContent: false,
    treeShaking: true,
    target: ['es2020'],
    minify: true,
    entryPoints: ['./resources/js/focal-point-picker.js'],
    outfile: './resources/dist/focal-point-picker.js',
})





// compile({
//     ...defaultOptions,
//     entryPoints: ['./resources/js/focal-point-picker.js'],
//     outfile: './resources/dist/focal-point-picker.js',
// })
