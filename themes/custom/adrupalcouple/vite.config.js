import { defineConfig } from 'vite';
import { resolve, join, relative } from 'path';
import { globSync } from 'glob';
import { viteStaticCopy } from 'vite-plugin-static-copy';
import svgSpritePlugin from '@pivanov/vite-plugin-svg-sprite';

/**
 * Defines glob patterns for CSS source files.
 */
const cssGlob = {
  '': 'css/**/*.pcss.css',
  'css': 'components/**/*.pcss.css',
};

/**
 * Defines icon lib folder.
 * You can define one icon lib for each spritemap.
 */
const iconLibs = {
  'default': ['icons/default'],
};


export default defineConfig(() => {
  const projectRoot = __dirname;

  // Generate entry points for Rollup
  const entries = Object.entries(cssGlob).reduce((acc, [folder, pattern]) => {
    const files = globSync(join(projectRoot, pattern));
    files.forEach((file) => {
      const outputKey = join(folder, relative(projectRoot, file)).replace('.pcss.css', '');
      acc[outputKey] = resolve(projectRoot, file);
    });
    return acc;
  }, {});


  // Create plugins array with svgSpritePlugin instances
  const plugins = Object.entries(iconLibs).map(([lib, folders]) =>
    svgSpritePlugin({
      iconDirs: folders,
      symbolId: '[dir]-[name]',
      fileName: `${lib}-icons.svg`,
      svgDomId: 'svg-sprite',
    })
  );

  return {
    plugins: [
      ...plugins,
      viteStaticCopy({
        targets: [
          {
            src: 'dist/css/components', // Copy compiled CSS directory
            dest: '../', // to component and folder.
          },
          // Self-host the brand webfonts: PostCSS inlines the @fontsource @imports but Vite does
          // not emit the deep url(./files/*.woff2) assets, so the compiled app.css references
          // dist/css/files/*.woff2 that never exist (404 -> fonts fall back to Georgia/system).
          // Copy the @fontsource font files into dist/css/files/ so they serve LOCALLY (no CDN).
          // EN/ES is latin-script: copy ONLY latin + latin-ext, woff2 only (drop vietnamese/cyrillic/
          // greek subsets + the legacy .woff fallback — woff2 is universal in modern browsers). The
          // non-latin url()s the @fontsource CSS still references are never requested for latin content
          // (unicode-range gating), so no 404s in practice.
          // Exactly the 6 files the compiled app.css references for latin/latin-ext (all -normal;
          // no italic referenced). Fraunces variable = the single `full` axis file (NOT the per-axis
          // opsz/soft/wght/wonk variants); Atkinson + IBM Plex Mono = weight 400 only.
          { src: 'node_modules/@fontsource-variable/fraunces/files/*-latin*-full-normal.woff2', dest: 'css/files' },
          { src: 'node_modules/@fontsource/atkinson-hyperlegible/files/*-latin*-400-normal.woff2', dest: 'css/files' },
          { src: 'node_modules/@fontsource/ibm-plex-mono/files/*-latin*-400-normal.woff2', dest: 'css/files' },
        ],
      }),
    ],
    css: {
      postcss: true,
    },
    build: {
      minify: process.env.NODE_ENV === 'production',
      rollupOptions: {
        input: entries,
        output: {
          assetFileNames: '[name].[ext]',
        },
      },
    },
  };
});
