import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import html from '@rollup/plugin-html';
import { glob } from 'glob';

/**
 * Get Files from a directory
 * @param {string} query
 * @returns array
 */
function GetFilesArray(query) {
  return glob.sync(query);
}
/**
 * Js Files
 */
// Page JS Files
const pageJsFiles = GetFilesArray('resources/assets/js/*.js');

// Processing Vendor JS Files
const vendorJsFiles = GetFilesArray('resources/assets/vendor/js/*.js');

// Processing Libs JS Files
const LibsJsFiles = GetFilesArray('resources/assets/vendor/libs/**/*.js');

/**
 * Scss Files
 */
// Processing Core, Themes & Pages Scss Files
const CoreScssFiles = GetFilesArray('resources/assets/vendor/scss/**/!(_)*.scss');

// Processing Custom Pages & Components Scss Files
const CustomScssFiles = GetFilesArray('resources/assets/scss/**/*.scss');

// Processing Libs Scss & Css Files
const LibsScssFiles = GetFilesArray('resources/assets/vendor/libs/**/!(_)*.scss');
const LibsCssFiles = GetFilesArray('resources/assets/vendor/libs/**/*.css');

// Processing Fonts Scss Files
const FontsScssFiles = GetFilesArray('resources/assets/vendor/fonts/!(_)*.scss');

/**
 * Css Files
 */
// Processing Custom Css Files
const CssFiles = GetFilesArray('resources/assets/css/!(_)*.css');

/**
 * J Files
 */
//Processing Js Files that need window assignment
const JsAudit = GetFilesArray('resources/assets/js/audit/!(_)*.js');
const JsCategories = GetFilesArray('resources/assets/js/categories/!(_)*.js');
const JsKnowledge = GetFilesArray('resources/assets/js/knowledgebase/!(_)*.js');
const JsModules = GetFilesArray('resources/assets/js/modules/!(_)*.js');
const JsOfficer = GetFilesArray('resources/assets/js/officers/!(_)*.js');
const JsPermission = GetFilesArray('resources/assets/js/permissions/!(_)*.js');
const JsReport = GetFilesArray('resources/assets/js/report/!(_)*.js');
const JsRole = GetFilesArray('resources/assets/js/roles/!(_)*.js');
const JsSla = GetFilesArray('resources/assets/js/sla/!(_)*.js');
const JsSubCat = GetFilesArray('resources/assets/js/subcategories/!(_)*.js');
const JsTicket = GetFilesArray('resources/assets/js/ticket/!(_)*.js');
const JsUser = GetFilesArray('resources/assets/js/user/*.js');

const LibsJsFilesCustom = [
  ...JsAudit,
  ...JsCategories,
  ...JsKnowledge,
  ...JsModules,
  ...JsOfficer,
  ...JsPermission,
  ...JsReport,
  ...JsRole,
  ...JsSla,
  ...JsSubCat,
  ...JsTicket,
  ...JsUser
];


// Merging all Js Files that need window assignment

// Processing Window Assignment for Libs like jKanban, pdfMake
function libsWindowAssignment() {
  return {
    name: 'libsWindowAssignment',

    transform(src, id) {
      if (id.includes('jkanban.js')) {
        return src.replace('this.jKanban', 'window.jKanban');
      } else if (id.includes('vfs_fonts')) {
        return src.replaceAll('this.pdfMake', 'window.pdfMake');
      }
    }
  };
}

export default defineConfig({
  plugins: [
    laravel({
      input: [
        ...pageJsFiles,
        ...vendorJsFiles,
        ...LibsJsFiles,
        ...CoreScssFiles,
        ...CustomScssFiles,
        ...LibsScssFiles,
        ...LibsCssFiles,
        ...FontsScssFiles,
        ...CssFiles,
        ...LibsJsFilesCustom
      ],
      refresh: true
    }),
    html(),
    libsWindowAssignment()
  ],
  server: {
    host: '127.0.0.1',
    port: 5173,
    strictPort: true,
    hmr: {
      host: '127.0.0.1'
    }
  }
});
