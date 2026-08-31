const fs = require('fs');
const path = require('path');
const gettextParser = require('gettext-parser');

const root = path.resolve(__dirname, '..');
const languageDirectory = path.join(root, 'src', 'Languages');

function potFilesIn(directory) {
  if (!fs.existsSync(directory)) return [];

  return fs.readdirSync(directory, { withFileTypes: true }).flatMap((entry) => {
    const fullPath = path.join(directory, entry.name);
    if (entry.isDirectory()) return potFilesIn(fullPath);
    return entry.isFile() && entry.name.toLowerCase().endsWith('.pot') ? [fullPath] : [];
  });
}

const catalogs = potFilesIn(languageDirectory).sort();

if (catalogs.length === 0) {
  throw new Error('No POT files were found. Run npm run i18n:pot first.');
}

catalogs.forEach((potPath) => {
  const moPath = potPath.replace(/\.pot$/i, '.mo');
  const poPath = potPath.replace(/\.pot$/i, '.po');
  const sourcePath = fs.existsSync(poPath) ? poPath : potPath;
  const parsed = gettextParser.po.parse(fs.readFileSync(sourcePath));
  const compiled = gettextParser.mo.compile(parsed);
  fs.writeFileSync(moPath, compiled);
  const sourceType = sourcePath === poPath ? 'PO' : 'POT template';
  console.log(`${path.relative(root, moPath)}: ${compiled.length} bytes (${sourceType})`);
});
