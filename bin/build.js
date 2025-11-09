import * as esbuild from 'esbuild'
import { exec } from 'child_process'
import { promisify } from 'util'

const execAsync = promisify(exec)

async function compile(options) {
    const context = await esbuild.context(options)

    await context.rebuild()
    await context.dispose()
}

async function buildCSS() {
    console.log('Building CSS files...')
    try {
        const { stdout, stderr } = await execAsync('postcss resources/css/filament-focal-point-picker.css -o resources/dist/filament-focal-point-picker.css')
        if (stdout) console.log(stdout)
        if (stderr) console.error(stderr)
        console.log('CSS build complete!')
    } catch (error) {
        console.error('CSS build failed:', error.message)
        if (error.stdout) console.log('stdout:', error.stdout)
        if (error.stderr) console.error('stderr:', error.stderr)
        throw error
    }
}

async function build() {
    await compile({
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

    await buildCSS()
}

build().catch(() => process.exit(1))

