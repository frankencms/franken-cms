import postcssNesting from 'postcss-nesting'
import cssnano from 'cssnano'

export default {
    plugins: [
        postcssNesting,
        cssnano,
    ],
}
